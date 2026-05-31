<?php
namespace Wexoe\Core\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Color utilities — hex normalization, darkness detection, contrast.
 *
 * Uses the WCAG relative-luminance formula for "is dark" detection, not the
 * naive "average of RGB" approach. This gives correct results for pure colors:
 *
 *   #ffff00 (yellow) → relative luminance ≈ 0.93 → NOT dark
 *   #0000ff (blue)   → relative luminance ≈ 0.07 → dark
 *   #888888 (gray)   → relative luminance ≈ 0.26 → dark
 *
 * Naive averaging would mistake yellow for dark because it has no red/green
 * mixing benefit; humans perceive yellow as very light despite the raw channel
 * math. WCAG's linearization step fixes this.
 */
class Color {

    const LUMINANCE_DARK_THRESHOLD = 0.5;

    /**
     * Normalize any hex color string to #rrggbb (lowercase).
     * Accepts:
     *   - "#abc" → "#aabbcc"
     *   - "abc"  → "#aabbcc"
     *   - "#aabbcc" → "#aabbcc"
     *   - "aabbcc" → "#aabbcc"
     *   - "#ABC123" → "#abc123"
     *
     * Returns null for invalid input.
     */
    public static function normalize_hex($color) {
        if (!is_string($color)) return null;
        $color = trim($color);
        $color = ltrim($color, '#');

        // Short form: abc → aabbcc
        if (strlen($color) === 3 && ctype_xdigit($color)) {
            $color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
        }

        if (strlen($color) === 6 && ctype_xdigit($color)) {
            return '#' . strtolower($color);
        }

        return null;
    }

    /**
     * Is the color "dark" enough that light text would be readable on it?
     * Uses WCAG relative luminance < 0.5 as the threshold.
     *
     * @param string $color Hex color (any of the accepted formats)
     * @return bool False for invalid input (safe default — assume light bg)
     */
    public static function is_dark($color) {
        $lum = self::relative_luminance($color);
        if ($lum === null) return false;
        return $lum < self::LUMINANCE_DARK_THRESHOLD;
    }

    /**
     * Pick appropriate text color (#ffffff on dark, #000000 on light).
     *
     * @param string $bg_color Background color as hex
     * @return string '#ffffff' or '#000000'
     */
    public static function text_color($bg_color) {
        return self::is_dark($bg_color) ? '#ffffff' : '#000000';
    }

    /**
     * Compute WCAG relative luminance (0 = black, 1 = white).
     *
     * @param string $color Hex color
     * @return float|null Luminance 0-1, or null for invalid input
     */
    public static function relative_luminance($color) {
        $hex = self::normalize_hex($color);
        if ($hex === null) return null;

        $r = hexdec(substr($hex, 1, 2)) / 255;
        $g = hexdec(substr($hex, 3, 2)) / 255;
        $b = hexdec(substr($hex, 5, 2)) / 255;

        return 0.2126 * self::linearize($r)
             + 0.7152 * self::linearize($g)
             + 0.0722 * self::linearize($b);
    }

    /**
     * Compute WCAG contrast ratio between two colors (1.0 to 21.0).
     * Higher = more contrast. WCAG AA requires 4.5:1 for normal text.
     *
     * @return float|null Contrast ratio, or null if either color is invalid
     */
    public static function contrast_ratio($color1, $color2) {
        $l1 = self::relative_luminance($color1);
        $l2 = self::relative_luminance($color2);
        if ($l1 === null || $l2 === null) return null;

        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);
        return round(($lighter + 0.05) / ($darker + 0.05), 2);
    }

    /**
     * Decompose a hex color to RGB components.
     *
     * @return array|null ['r' => 0-255, 'g' => 0-255, 'b' => 0-255] or null
     */
    public static function to_rgb($color) {
        $hex = self::normalize_hex($color);
        if ($hex === null) return null;
        return [
            'r' => hexdec(substr($hex, 1, 2)),
            'g' => hexdec(substr($hex, 3, 2)),
            'b' => hexdec(substr($hex, 5, 2)),
        ];
    }

    /* --------------------------------------------------------
       INTERNAL
       -------------------------------------------------------- */

    /**
     * WCAG sRGB-to-linear-light transform.
     * Channel values <= 0.03928 use a linear slope; larger values use a gamma curve.
     */
    private static function linearize($c) {
        return ($c <= 0.03928) ? ($c / 12.92) : pow(($c + 0.055) / 1.055, 2.4);
    }
}
