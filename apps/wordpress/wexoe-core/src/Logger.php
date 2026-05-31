<?php
namespace Wexoe\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ring-buffer logger stored in a single WP option row.
 *
 * Trade-offs:
 *  - Simple to implement, no extra DB tables
 *  - Every log() reads + writes the whole buffer (~acceptable at MAX_ENTRIES=500)
 *  - Not suitable for high-volume event logging; we log one entry per
 *    Airtable call + errors, so volume stays very low
 *
 * All methods are static — no per-request state to hold.
 */
class Logger {

    const OPTION_KEY = 'wexoe_core_log';
    const MAX_ENTRIES = 500;

    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';

    /**
     * Core log method. Prepends the new entry so newest shows first.
     */
    public static function log($level, $message, $context = []) {
        if (!in_array($level, [self::LEVEL_INFO, self::LEVEL_WARNING, self::LEVEL_ERROR], true)) {
            $level = self::LEVEL_INFO;
        }

        $entry = [
            'ts' => microtime(true),
            'level' => $level,
            'msg' => (string) $message,
            'ctx' => is_array($context) ? $context : [],
        ];

        $entries = self::get_entries_raw();
        array_unshift($entries, $entry);

        if (count($entries) > self::MAX_ENTRIES) {
            $entries = array_slice($entries, 0, self::MAX_ENTRIES);
        }

        // autoload=false because log can grow and we don't need it on every page load
        update_option(self::OPTION_KEY, $entries, false);
    }

    public static function info($message, $context = []) {
        self::log(self::LEVEL_INFO, $message, $context);
    }

    public static function warning($message, $context = []) {
        self::log(self::LEVEL_WARNING, $message, $context);
    }

    public static function error($message, $context = []) {
        self::log(self::LEVEL_ERROR, $message, $context);
    }

    /**
     * Get log entries, newest first. Optional level filter.
     *
     * @param int $limit Max entries to return
     * @param string|null $level Filter by level ('info'|'warning'|'error') or null for all
     * @return array
     */
    public static function get_entries($limit = 100, $level = null) {
        $entries = self::get_entries_raw();

        if ($level !== null) {
            $entries = array_values(array_filter($entries, function($e) use ($level) {
                return isset($e['level']) && $e['level'] === $level;
            }));
        }

        return array_slice($entries, 0, max(1, (int) $limit));
    }

    /**
     * Count entries by level. Returns ['info' => N, 'warning' => N, 'error' => N, 'total' => N]
     */
    public static function count_by_level() {
        $entries = self::get_entries_raw();
        $counts = [
            self::LEVEL_INFO => 0,
            self::LEVEL_WARNING => 0,
            self::LEVEL_ERROR => 0,
            'total' => count($entries),
        ];
        foreach ($entries as $e) {
            if (isset($e['level']) && isset($counts[$e['level']])) {
                $counts[$e['level']]++;
            }
        }
        return $counts;
    }

    /**
     * Clear all log entries.
     */
    public static function clear() {
        return delete_option(self::OPTION_KEY);
    }

    /**
     * Internal: read raw entries from WP option. Always returns an array.
     */
    private static function get_entries_raw() {
        $entries = get_option(self::OPTION_KEY, []);
        return is_array($entries) ? $entries : [];
    }
}
