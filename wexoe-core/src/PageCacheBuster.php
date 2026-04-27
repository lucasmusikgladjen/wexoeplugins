<?php
namespace Wexoe\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Tells common WP page-cache plugins to flush their stored HTML.
 *
 * Wexoe Core's own cache (transients + stale options) is the data layer.
 * On top of that, most sites run a page-cache plugin that stores the
 * rendered HTML — clearing the data layer alone won't refresh what the
 * visitor sees until the page cache also drops. This class runs every
 * known plugin's "purge everything" entry point; missing plugins are
 * skipped silently.
 *
 * flush_all() returns a list of plugin keys that responded so the webhook
 * caller can see which cache layers were actually touched.
 */
class PageCacheBuster {

    /**
     * Try every known cache plugin. Returns the list of keys that fired.
     *
     * @return string[]
     */
    public static function flush_all() {
        $cleared = [];

        // WordPress core object cache (Memcached/Redis backends honour this).
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
            $cleared[] = 'wp_object_cache';
        }

        // WP Rocket
        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
            if (function_exists('rocket_clean_minify')) {
                rocket_clean_minify();
            }
            $cleared[] = 'wp_rocket';
        }

        // W3 Total Cache
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
            $cleared[] = 'w3_total_cache';
        } elseif (function_exists('w3tc_pgcache_flush')) {
            w3tc_pgcache_flush();
            $cleared[] = 'w3_total_cache';
        }

        // LiteSpeed Cache
        if (defined('LSCWP_V') || class_exists('\LiteSpeed\Purge')) {
            do_action('litespeed_purge_all');
            $cleared[] = 'litespeed_cache';
        }

        // WP Super Cache
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
            $cleared[] = 'wp_super_cache';
        } elseif (function_exists('prune_super_cache') && function_exists('get_supercache_dir')) {
            prune_super_cache(get_supercache_dir(), true);
            $cleared[] = 'wp_super_cache';
        }

        // WP Fastest Cache
        if (class_exists('WpFastestCache')) {
            $wpfc = new \WpFastestCache();
            if (method_exists($wpfc, 'deleteCache')) {
                $wpfc->deleteCache(true);
                $cleared[] = 'wp_fastest_cache';
            }
        }

        // Cache Enabler
        if (class_exists('\Cache_Enabler')) {
            \Cache_Enabler::clear_complete_cache();
            $cleared[] = 'cache_enabler';
        }

        // Hummingbird
        if (function_exists('hummingbird_purge_page_cache')) {
            hummingbird_purge_page_cache();
            $cleared[] = 'hummingbird';
        } elseif (class_exists('\Hummingbird\WP_Hummingbird')) {
            do_action('wphb_clear_page_cache');
            $cleared[] = 'hummingbird';
        }

        // Autoptimize (CSS/JS aggregation cache)
        if (class_exists('\autoptimizeCache')) {
            \autoptimizeCache::clearall();
            $cleared[] = 'autoptimize';
        }

        // SG Optimizer (SiteGround)
        if (function_exists('sg_cachepress_purge_cache')) {
            sg_cachepress_purge_cache();
            $cleared[] = 'sg_optimizer';
        }

        // Breeze (Cloudways)
        if (class_exists('\Breeze_PurgeCache')) {
            \Breeze_PurgeCache::breeze_cache_flush();
            $cleared[] = 'breeze';
        }

        // Cloudflare WP plugin (purges Cloudflare edge cache)
        if (class_exists('\CF\WordPress\Hooks')) {
            do_action('cloudflare_purge_everything');
            $cleared[] = 'cloudflare_plugin';
        }

        // Kinsta MU plugin
        if (class_exists('\Kinsta\Cache_Purge')) {
            do_action('kinsta_clear_cache');
            $cleared[] = 'kinsta';
        }

        // Generic hook so site owners can attach extra purge logic
        // (custom CDN, fragment cache, etc.) without forking this file.
        do_action('wexoe_core_after_cache_clear', $cleared);

        Logger::info('Page cache buster ran', ['cleared' => $cleared]);

        return $cleared;
    }
}
