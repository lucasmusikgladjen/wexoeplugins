<?php
namespace Wexoe\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cache wrapper around WordPress transients.
 *
 * All keys are automatically prefixed with "wexoe_core_" to avoid collisions
 * with other plugins. Callers pass the unprefixed key — e.g. set('partners:rockwell', ...)
 * becomes the transient "wexoe_core_partners:rockwell" internally.
 *
 * Transients have no built-in "delete by prefix" function in WP, so deleteByPrefix()
 * uses a direct SQL query against wp_options. This is the standard pattern.
 */
class Cache {

    const KEY_PREFIX = 'wexoe_core_';
    const DEFAULT_TTL = DAY_IN_SECONDS; // 86400 seconds = 24h

    /**
     * Get a cached value. Returns null if not found (never false — false can be
     * ambiguous if the stored value itself is boolean false).
     *
     * @param string $key Unprefixed key
     * @return mixed|null
     */
    public static function get($key) {
        $transient_key = self::prefix($key);
        $value = get_transient($transient_key);
        return $value === false ? null : $value;
    }

    /**
     * Store a value in cache. TTL defaults to 24h.
     *
     * @param string $key Unprefixed key
     * @param mixed $value Any serializable value
     * @param int|null $ttl Seconds, or null for default
     * @return bool Success
     */
    public static function set($key, $value, $ttl = null) {
        $ttl = $ttl === null ? self::DEFAULT_TTL : max(1, (int) $ttl);
        return set_transient(self::prefix($key), $value, $ttl);
    }

    /**
     * Delete a single cache entry.
     *
     * @param string $key Unprefixed key
     * @return bool Success
     */
    public static function delete($key) {
        return delete_transient(self::prefix($key));
    }

    /**
     * Delete all cache entries whose unprefixed key starts with $prefix.
     * Uses direct SQL since WP has no native deleteByPrefix for transients.
     *
     * Example: deleteByPrefix('partners:') removes 'partners:rockwell',
     * 'partners:fibrain', etc. — but not 'coworkers:anders'.
     *
     * @param string $prefix Unprefixed key prefix
     * @return int Number of transients deleted
     */
    public static function delete_by_prefix($prefix) {
        global $wpdb;

        $full_prefix = self::KEY_PREFIX . $prefix;
        $like_value = '\\_transient\\_' . $wpdb->esc_like($full_prefix) . '%';
        $like_timeout = '\\_transient\\_timeout\\_' . $wpdb->esc_like($full_prefix) . '%';

        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options}
                 WHERE option_name LIKE %s
                    OR option_name LIKE %s",
                $like_value,
                $like_timeout
            )
        );

        // Each transient is 2 option rows (value + timeout), so actual count is half
        return is_numeric($deleted) ? (int) ($deleted / 2) : 0;
    }

    /**
     * Delete ALL Core cache entries. Used by "clear all cache" admin button.
     *
     * @return int Number of transients deleted
     */
    public static function clear_all() {
        return self::delete_by_prefix('');
    }

    /**
     * Count all Core cache entries currently in the database.
     *
     * @return int
     */
    public static function count() {
        global $wpdb;
        $like = '\\_transient\\_' . $wpdb->esc_like(self::KEY_PREFIX) . '%';
        // Only count value rows (not timeout rows) to get actual transient count
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options}
                 WHERE option_name LIKE %s
                   AND option_name NOT LIKE %s",
                $like,
                '\\_transient\\_timeout\\_%'
            )
        );
        return (int) $count;
    }

    /**
     * Internal: add prefix to key.
     */
    private static function prefix($key) {
        return self::KEY_PREFIX . $key;
    }
}
