<?php
namespace Wexoe\Core\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Markdown-to-HTML conversion.
 *
 * Two strategies, tried in order:
 *   1. If the global \Parsedown class is loaded (e.g. by another plugin like
 *      wexoe-product-area), use it in safe mode.
 *   2. Otherwise, fall back to a minimal internal parser that handles:
 *        - Headers (# ## ###)
 *        - Bold (**text** or __text__)
 *        - Italic (*text* or _text_)
 *        - Inline code (`code`)
 *        - Links ([text](url))
 *        - Unordered lists (- item or * item)
 *        - Paragraphs (blank line separator)
 *        - Line breaks (two trailing spaces + newline)
 *
 * For advanced markdown features (tables, footnotes, code blocks with syntax
 * highlighting, etc.), install Parsedown via Composer and this class will
 * pick it up automatically.
 */
class Markdown {

    /**
     * Convert markdown text to HTML.
     *
     * @param string $text
     * @return string HTML string (empty if input is not a non-empty string)
     */
    public static function to_html($text) {
        if (!is_string($text) || $text === '') {
            return '';
        }

        // Prefer Parsedown if available (safer + more complete)
        if (class_exists('\\Parsedown')) {
            try {
                $pd = new \Parsedown();
                if (method_exists($pd, 'setSafeMode')) {
                    $pd->setSafeMode(true);
                }
                return $pd->text($text);
            } catch (\Throwable $e) {
                // Fall through to internal parser
            }
        }

        return self::simple_parse($text);
    }

    /**
     * Strip all markdown formatting and return plain text.
     * Useful for excerpts, meta descriptions, etc.
     */
    public static function strip($text) {
        if (!is_string($text) || $text === '') {
            return '';
        }
        // Remove inline formatting
        $text = preg_replace('/\*\*([^\*]+)\*\*/', '$1', $text);
        $text = preg_replace('/__([^_]+)__/', '$1', $text);
        $text = preg_replace('/(?<!\*)\*([^\*]+)\*(?!\*)/', '$1', $text);
        $text = preg_replace('/(?<!_)_([^_]+)_(?!_)/', '$1', $text);
        $text = preg_replace('/`([^`]+)`/', '$1', $text);
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text);
        // Remove headers
        $text = preg_replace('/^#+\s+/m', '', $text);
        // Remove list markers
        $text = preg_replace('/^[\-\*]\s+/m', '', $text);
        return trim($text);
    }

    /**
     * Inline conversion — no paragraph wrapping. Good for single-line text
     * fields where <p>-tags would be wrong.
     */
    public static function to_inline($text) {
        if (!is_string($text) || $text === '') {
            return '';
        }
        $html = self::to_html($text);
        // Strip outer <p> if the entire output is a single paragraph
        if (preg_match('/^\s*<p>(.*?)<\/p>\s*$/s', trim($html), $m)) {
            return $m[1];
        }
        return $html;
    }

    /* --------------------------------------------------------
       INTERNAL: minimal markdown parser (fallback)
       -------------------------------------------------------- */

    private static function simple_parse($text) {
        // Normalize line endings
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Escape HTML first for safety (we re-insert tags where we want them)
        $text = htmlspecialchars($text, ENT_NOQUOTES | ENT_HTML5, 'UTF-8');

        // Split into blocks separated by blank lines
        $blocks = preg_split('/\n{2,}/', $text);
        $html_blocks = [];

        foreach ($blocks as $block) {
            $block = trim($block);
            if ($block === '') continue;

            // Headers
            if (preg_match('/^(#{1,3})\s+(.+)$/', $block, $m)) {
                $level = strlen($m[1]);
                $content = self::apply_inline($m[2]);
                $html_blocks[] = "<h{$level}>{$content}</h{$level}>";
                continue;
            }

            // Unordered list
            if (preg_match('/^[\-\*]\s+/', $block)) {
                $items = preg_split('/\n(?=[\-\*]\s+)/', $block);
                $list_items = [];
                foreach ($items as $item) {
                    $item = preg_replace('/^[\-\*]\s+/', '', trim($item));
                    $list_items[] = '<li>' . self::apply_inline($item) . '</li>';
                }
                $html_blocks[] = '<ul>' . implode('', $list_items) . '</ul>';
                continue;
            }

            // Default: paragraph. Inside, two trailing spaces become <br>
            $block = preg_replace('/  \n/', "<br>\n", $block);
            $html_blocks[] = '<p>' . self::apply_inline($block) . '</p>';
        }

        return implode("\n", $html_blocks);
    }

    /**
     * Apply inline markdown (bold, italic, code, links) to a string that's
     * already HTML-escaped.
     */
    private static function apply_inline($text) {
        // Bold **x** or __x__
        $text = preg_replace('/\*\*([^\*]+)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/__([^_]+)__/', '<strong>$1</strong>', $text);

        // Italic *x* or _x_ (after bold, so we don't interfere)
        $text = preg_replace('/(?<!\*)\*([^\*\n]+)\*(?!\*)/', '<em>$1</em>', $text);
        $text = preg_replace('/(?<!_)_([^_\n]+)_(?!_)/', '<em>$1</em>', $text);

        // Inline code
        $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);

        // Links [text](url) — URL is already escaped, but re-validate it's safe
        $text = preg_replace_callback(
            '/\[([^\]]+)\]\(([^)]+)\)/',
            function ($m) {
                $label = $m[1];
                $url = $m[2];
                // Only allow http(s) and mailto; reject javascript:, data:, etc.
                if (!preg_match('/^(https?:|mailto:|\/|#)/i', html_entity_decode($url))) {
                    return $m[0]; // Leave as-is if URL is suspicious
                }
                return '<a href="' . $url . '">' . $label . '</a>';
            },
            $text
        );

        return $text;
    }
}
