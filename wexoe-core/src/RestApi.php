<?php
namespace Wexoe\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST API surface for Wexoe Core.
 *
 * Single endpoint today: POST /wp-json/wexoe-core/v1/cache/clear
 *
 * Used by the Wexoe Builder (Vercel) to invalidate entity caches as soon as
 * a page is created or updated in Airtable, so editors don't have to wait
 * for the 24h transient TTL or click "Rensa cache" by hand.
 *
 * Auth: shared secret in header `X-Wexoe-Webhook-Secret`. The same secret is
 * stored on both ends — in WP under the `wexoe_core_webhook_secret` option
 * (Verktyg → Webhook), and as `WEXOE_CORE_WEBHOOK_SECRET` on the builder.
 */
class RestApi {

    const ROUTE_NAMESPACE = 'wexoe-core/v1';
    const SECRET_HEADER = 'X-Wexoe-Webhook-Secret';

    public static function register_routes() {
        register_rest_route(self::ROUTE_NAMESPACE, '/cache/clear', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_cache_clear'],
            'permission_callback' => [__CLASS__, 'check_secret'],
            'args' => [
                'entities' => [
                    'required' => false,
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Entity names to clear. Empty/missing = clear all.',
                ],
            ],
        ]);
    }

    /**
     * Permission callback — accepts the request only if the X-Wexoe-Webhook-Secret
     * header matches the stored secret. We use hash_equals to dodge timing leaks
     * and refuse the call outright when no secret is configured (so a bare-bones
     * install doesn't accidentally expose cache-clear to the world).
     */
    public static function check_secret(\WP_REST_Request $request) {
        $expected = Plugin::get_webhook_secret();
        if ($expected === '') {
            return new \WP_Error(
                'wexoe_core_webhook_disabled',
                'Webhook-secret är inte konfigurerad i Wexoe Core.',
                ['status' => 503]
            );
        }
        $provided = $request->get_header(self::header_lookup_key());
        if (!is_string($provided) || $provided === '') {
            return new \WP_Error(
                'wexoe_core_webhook_unauthorized',
                'Saknad eller ogiltig webhook-secret.',
                ['status' => 401]
            );
        }
        if (!hash_equals($expected, $provided)) {
            return new \WP_Error(
                'wexoe_core_webhook_unauthorized',
                'Saknad eller ogiltig webhook-secret.',
                ['status' => 401]
            );
        }
        return true;
    }

    /**
     * WP normalizes incoming header names to lower-case + underscores when you
     * call get_header(). Translate "X-Wexoe-Webhook-Secret" to that form once.
     */
    private static function header_lookup_key() {
        return strtolower(str_replace('-', '_', self::SECRET_HEADER));
    }

    /**
     * Handle the cache-clear call. Body shape:
     *
     *   { "entities": ["landing_pages", "lp_tabs", "lp_downloads"] }
     *
     * Missing/empty `entities` clears every Core cache entry (transients +
     * stale options) — same effect as the admin "Rensa all cache" button.
     *
     * Always 200 with a per-entity result map, even if some entity names
     * are unknown — the builder shouldn't fail a publish just because the
     * core schema list and the builder list drifted.
     */
    public static function handle_cache_clear(\WP_REST_Request $request) {
        $entities = $request->get_param('entities');

        // Sweep-everything mode
        if (!is_array($entities) || empty($entities)) {
            $transient_deleted = Cache::clear_all();
            $stale_deleted = EntityRepository::clear_all_stale_options();
            $page_caches_cleared = PageCacheBuster::flush_all();
            Logger::info('Cache cleared via webhook (all entities)', [
                'transient_deleted' => $transient_deleted,
                'stale_deleted' => $stale_deleted,
                'page_caches_cleared' => $page_caches_cleared,
            ]);
            return new \WP_REST_Response([
                'success' => true,
                'mode' => 'all',
                'transient_deleted' => $transient_deleted,
                'stale_deleted' => $stale_deleted,
                'page_caches_cleared' => $page_caches_cleared,
            ], 200);
        }

        // Per-entity mode — clear only what's listed
        $results = [];
        $cleared_total = 0;
        $unknown = [];
        foreach ($entities as $raw_name) {
            $name = is_string($raw_name) ? sanitize_key($raw_name) : '';
            if ($name === '') {
                continue;
            }
            $repo = Core::entity($name);
            if ($repo === null) {
                $unknown[] = $name;
                $results[$name] = ['cleared' => false, 'reason' => 'unknown_entity'];
                continue;
            }
            $deleted = $repo->clear_cache();
            $results[$name] = ['cleared' => true, 'transient_deleted' => $deleted];
            $cleared_total += (int) $deleted;
        }

        Logger::info('Cache cleared via webhook', [
            'entities' => array_keys($results),
            'unknown' => $unknown,
            'transient_deleted' => $cleared_total,
        ]);

        // Page-cache plugins store rendered HTML on top of our data layer —
        // clearing entity transients alone won't refresh the live page until
        // they drop too. Run after the per-entity clears so the data is
        // re-fetched on the next request.
        $page_caches_cleared = PageCacheBuster::flush_all();

        return new \WP_REST_Response([
            'success' => true,
            'mode' => 'per_entity',
            'results' => $results,
            'unknown' => $unknown,
            'page_caches_cleared' => $page_caches_cleared,
        ], 200);
    }
}
