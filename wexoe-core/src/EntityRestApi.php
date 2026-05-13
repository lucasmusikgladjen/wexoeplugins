<?php
namespace Wexoe\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST CRUD endpoints for the SSOT entities used by the Wexoe Builder
 * `/globals/*` editor.
 *
 * Endpoints:
 *   GET    /wp-json/wexoe-core/v1/entity/{entity}              — list all
 *   GET    /wp-json/wexoe-core/v1/entity/{entity}?record_id=X  — single by record ID
 *   GET    /wp-json/wexoe-core/v1/entity/{entity}?slug=Y       — single by primary key
 *   POST   /wp-json/wexoe-core/v1/entity/{entity}              — create
 *   PATCH  /wp-json/wexoe-core/v1/entity/{entity}?record_id=X  — update
 *   DELETE /wp-json/wexoe-core/v1/entity/{entity}?record_id=X  — delete
 *
 * Auth: shared secret (samma som /cache/clear) i header `X-Wexoe-Webhook-Secret`.
 *
 * Whitelist: bara entiteter i CORE_EDITABLE_ENTITIES kan editeras via denna route.
 * Skydd mot att man av misstag PATCH:ar `landing_pages` el. liknande via builder.
 *
 * Cache: alla mutationer rensar entitetens transient + Context-cache.
 */
class EntityRestApi {

    /**
     * Entiteter som får editeras via denna route. Whitelist.
     * Sid-data-tabeller (landing_pages, product_areas, audience_heroes, user_submissions)
     * ingår INTE — de skrivs via egna dedikerade routes / write-paths.
     */
    const CORE_EDITABLE_ENTITIES = [
        'core_company',
        'core_graphic_profile',
        'core_countries',
        'core_divisions',
        'core_customer_types',
        'core_coworkers',
        'core_partners',
        'core_testimonials',
        'cms_unique_pages',
    ];

    /**
     * Entiteter med singleton-invariant: max ett record får ha is_default=true.
     */
    const SINGLETON_ENTITIES = ['core_company', 'core_graphic_profile'];

    public static function register_routes() {
        register_rest_route(RestApi::ROUTE_NAMESPACE, '/entity/(?P<entity>[a-z][a-z0-9_]*)', [
            [
                'methods'             => 'GET',
                'callback'            => [__CLASS__, 'handle_get'],
                'permission_callback' => [RestApi::class, 'check_secret'],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [__CLASS__, 'handle_post'],
                'permission_callback' => [RestApi::class, 'check_secret'],
            ],
            [
                'methods'             => 'PATCH',
                'callback'            => [__CLASS__, 'handle_patch'],
                'permission_callback' => [RestApi::class, 'check_secret'],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [__CLASS__, 'handle_delete'],
                'permission_callback' => [RestApi::class, 'check_secret'],
            ],
        ]);
    }

    /* --------------------------------------------------------
       HANDLERS
       -------------------------------------------------------- */

    public static function handle_get(\WP_REST_Request $req) {
        $entity = self::sanitize_entity($req->get_param('entity'));
        if ($entity === null) return self::error_not_editable();

        $repo = Core::entity($entity);
        if ($repo === null) return self::error('entity_not_found', 'Entity finns inte.', 404);

        $record_id = $req->get_param('record_id');
        $slug      = $req->get_param('slug');

        if (is_string($record_id) && $record_id !== '') {
            $records = $repo->all();
            foreach ($records as $r) {
                if (isset($r['_record_id']) && $r['_record_id'] === $record_id) {
                    return new \WP_REST_Response(['success' => true, 'record' => $r], 200);
                }
            }
            return self::error('record_not_found', 'Record hittades inte.', 404);
        }

        if (is_string($slug) && $slug !== '') {
            $pk = $repo->get_primary_key();
            if ($pk === null) return self::error('no_primary_key', 'Entity saknar primary_key.', 400);
            $rec = $repo->find_by($pk, $slug);
            if ($rec === null) return self::error('record_not_found', 'Record hittades inte.', 404);
            return new \WP_REST_Response(['success' => true, 'record' => $rec], 200);
        }

        return new \WP_REST_Response(['success' => true, 'records' => $repo->all()], 200);
    }

    public static function handle_post(\WP_REST_Request $req) {
        $entity = self::sanitize_entity($req->get_param('entity'));
        if ($entity === null) return self::error_not_editable();

        $writer = Core::submission($entity);
        if ($writer === null) return self::error('write_schema_missing', 'Write-schema saknas.', 404);

        $domain = $req->get_json_params();
        if (!is_array($domain) || empty($domain)) {
            return self::error('empty_body', 'Body måste vara JSON-objekt med fält.', 400);
        }

        if (in_array($entity, self::SINGLETON_ENTITIES, true)) {
            $err = self::validate_singleton_invariant($entity, $domain, null);
            if ($err) return $err;
        }

        $result = $writer->create_mapped($domain);
        if (empty($result['success'])) {
            return self::error('write_failed', $result['error'] ?? 'Skrivning misslyckades.', 500);
        }

        self::invalidate_entity_cache($entity);

        return new \WP_REST_Response([
            'success'   => true,
            'record_id' => $result['record']['id'] ?? null,
            'record'    => $result['record'] ?? null,
        ], 201);
    }

    public static function handle_patch(\WP_REST_Request $req) {
        $entity = self::sanitize_entity($req->get_param('entity'));
        if ($entity === null) return self::error_not_editable();

        $record_id = $req->get_param('record_id');
        if (!is_string($record_id) || $record_id === '') {
            return self::error('missing_record_id', 'record_id krävs.', 400);
        }

        $writer = Core::submission($entity);
        if ($writer === null) return self::error('write_schema_missing', 'Write-schema saknas.', 404);

        $domain = $req->get_json_params();
        if (!is_array($domain) || empty($domain)) {
            return self::error('empty_body', 'Body måste vara JSON-objekt med fält.', 400);
        }

        if (in_array($entity, self::SINGLETON_ENTITIES, true)) {
            $err = self::validate_singleton_invariant($entity, $domain, $record_id);
            if ($err) return $err;
        }

        $result = $writer->update_mapped($record_id, $domain);
        if (empty($result['success'])) {
            return self::error('write_failed', $result['error'] ?? 'Skrivning misslyckades.', 500);
        }

        self::invalidate_entity_cache($entity);

        return new \WP_REST_Response([
            'success' => true,
            'record'  => $result['record'] ?? null,
        ], 200);
    }

    public static function handle_delete(\WP_REST_Request $req) {
        $entity = self::sanitize_entity($req->get_param('entity'));
        if ($entity === null) return self::error_not_editable();

        $record_id = $req->get_param('record_id');
        if (!is_string($record_id) || $record_id === '') {
            return self::error('missing_record_id', 'record_id krävs.', 400);
        }

        $writer = Core::submission($entity);
        if ($writer === null) return self::error('write_schema_missing', 'Write-schema saknas.', 404);

        $result = $writer->delete($record_id);
        if (empty($result['success'])) {
            return self::error('delete_failed', $result['error'] ?? 'Borttagning misslyckades.', 500);
        }

        self::invalidate_entity_cache($entity);
        return new \WP_REST_Response(['success' => true, 'deleted' => true], 200);
    }

    /* --------------------------------------------------------
       INTERNAL
       -------------------------------------------------------- */

    private static function sanitize_entity($entity) {
        if (!is_string($entity)) return null;
        $clean = strtolower(preg_replace('/[^a-z0-9_]/i', '', $entity));
        if ($clean === '') return null;
        if (!in_array($clean, self::CORE_EDITABLE_ENTITIES, true)) return null;
        return $clean;
    }

    private static function error_not_editable() {
        return self::error('entity_not_editable', 'Entitet är inte editerbar via denna route (whitelist-skydd).', 403);
    }

    private static function error($code, $message, $status) {
        return new \WP_REST_Response([
            'success' => false,
            'code'    => $code,
            'error'   => $message,
        ], $status);
    }

    /**
     * Säkerställ att max ett record har is_default=true för singleton-entiteter.
     */
    private static function validate_singleton_invariant($entity, array $domain, $current_record_id) {
        if (empty($domain['is_default'])) return null; // sätt till false eller utelämnat: OK

        $repo = Core::entity($entity);
        if ($repo === null) return null;
        $existing = $repo->find_by('is_default', true);
        if ($existing === null) return null;
        if ($current_record_id !== null && isset($existing['_record_id']) && $existing['_record_id'] === $current_record_id) {
            return null;
        }
        return self::error(
            'duplicate_default',
            'Ett annat record har redan is_default=true för denna entity. Avmarkera den först.',
            409
        );
    }

    /**
     * Rensa transient för en entity + Context-cache (om scoped data ändrats).
     */
    private static function invalidate_entity_cache($entity) {
        $repo = Core::entity($entity);
        if ($repo !== null) {
            $repo->clear_cache();
        }
        // Country/Division-context kan ha förändrats om vi rörde core_countries/divisions.
        if (in_array($entity, ['core_countries', 'core_divisions', 'core_company'], true)) {
            \Wexoe\Core\Helpers\Context::reset();
        }
    }
}
