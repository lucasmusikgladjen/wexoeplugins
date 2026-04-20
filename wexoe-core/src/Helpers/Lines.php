<?php
namespace Wexoe\Core\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Line-based text utilities.
 *
 * Used for multi-line text fields where each line is a distinct value
 * (bullet lists, URL lists, tag lists, benefit lists, etc.).
 *
 * The schema-driven Normalizer already has a 'lines' type that does the same
 * thing for Airtable-sourced data. This helper duplicates the logic in a
 * stand-alone form for use cases outside Core's schema pipeline — e.g. a
 * shortcode attribute, a WP option, or a text block from elsewhere.
 */
class Lines {

    /**
     * Split multi-line text into an array of non-empty trimmed lines.
     *
     * Handles all line-ending styles (\n, \r\n, \r).
     * Whitespace-only lines are removed.
     * Empty input returns an empty array.
     *
     * @param string $text
     * @return string[]
     */
    public static function to_array($text) {
        if (!is_string($text) || $text === '') return [];

        $lines = preg_split('/\r\n|\r|\n/', $text);
        if (!is_array($lines)) return [];

        $lines = array_map('trim', $lines);
        $lines = array_filter($lines, function ($line) {
            return $line !== '';
        });

        return array_values($lines);
    }

    /**
     * Return the first non-empty line, or null.
     */
    public static function first($text) {
        $lines = self::to_array($text);
        return !empty($lines) ? $lines[0] : null;
    }

    /**
     * Return the last non-empty line, or null.
     */
    public static function last($text) {
        $lines = self::to_array($text);
        return !empty($lines) ? end($lines) : null;
    }

    /**
     * Count non-empty lines.
     */
    public static function count_non_empty($text) {
        return count(self::to_array($text));
    }

    /**
     * Join an array of lines back to a multi-line string with \n separators.
     * Empty entries are filtered out before joining.
     */
    public static function from_array($lines) {
        if (!is_array($lines)) return '';
        $filtered = array_filter(array_map('trim', $lines), function ($l) {
            return is_string($l) && $l !== '';
        });
        return implode("\n", $filtered);
    }
}
