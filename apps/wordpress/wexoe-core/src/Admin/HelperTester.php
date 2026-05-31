<?php
namespace Wexoe\Core\Admin;

use Wexoe\Core\Helpers\Markdown;
use Wexoe\Core\Helpers\Color;
use Wexoe\Core\Helpers\YouTube;
use Wexoe\Core\Helpers\Lines;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Built-in smoke tester for Helpers classes.
 *
 * Runs a curated suite of assertion cases against every Helpers class and
 * returns a structured result array that the admin UI can render as a
 * pass/fail grid.
 *
 * Not meant to replace proper unit tests — just to give a one-click sanity
 * check that all helpers behave correctly after install, upgrade, or PHP
 * version change.
 */
class HelperTester {

    /**
     * Run all test cases. Returns an array grouped by helper class.
     *
     * Shape:
     *   [
     *     'Markdown' => [
     *       ['label' => 'to_html bold', 'passed' => true, 'actual' => '...', 'expected' => '...'],
     *       ...
     *     ],
     *     'Color' => [...],
     *     ...
     *   ]
     */
    public static function run_all() {
        return [
            'Markdown' => self::test_markdown(),
            'Color' => self::test_color(),
            'YouTube' => self::test_youtube(),
            'Lines' => self::test_lines(),
        ];
    }

    /**
     * Aggregate counts across all groups.
     *
     * @param array $all  Result from run_all()
     * @return array ['total' => N, 'passed' => N, 'failed' => N]
     */
    public static function summarize($all) {
        $total = 0;
        $passed = 0;
        foreach ($all as $group) {
            foreach ($group as $case) {
                $total++;
                if ($case['passed']) $passed++;
            }
        }
        return [
            'total' => $total,
            'passed' => $passed,
            'failed' => $total - $passed,
        ];
    }

    /* --------------------------------------------------------
       MARKDOWN
       -------------------------------------------------------- */

    private static function test_markdown() {
        return [
            self::assert_contains(
                'to_html: bold',
                Markdown::to_html('**bold**'),
                '<strong>bold</strong>'
            ),
            self::assert_contains(
                'to_html: italic',
                Markdown::to_html('*italic*'),
                '<em>italic</em>'
            ),
            self::assert_contains(
                'to_html: header h2',
                Markdown::to_html('## Hello'),
                '<h2>Hello</h2>'
            ),
            self::assert_contains(
                'to_html: link',
                Markdown::to_html('[Link](https://example.com)'),
                '<a href="https://example.com">Link</a>'
            ),
            self::assert_contains(
                'to_html: unordered list',
                Markdown::to_html("- One\n- Two\n- Three"),
                '<li>One</li>'
            ),
            self::assert_equals(
                'to_html: empty input returns empty',
                Markdown::to_html(''),
                ''
            ),
            self::assert_equals(
                'to_html: non-string returns empty',
                Markdown::to_html(null),
                ''
            ),
            self::assert_equals(
                'strip: removes all formatting',
                Markdown::strip('**bold** and *italic* and [link](url)'),
                'bold and italic and link'
            ),
            self::assert_not_contains(
                'to_html: rejects javascript: URL (security)',
                Markdown::to_html('[xss](javascript:alert(1))'),
                'href="javascript:'
            ),
        ];
    }

    /* --------------------------------------------------------
       COLOR
       -------------------------------------------------------- */

    private static function test_color() {
        return [
            self::assert_equals(
                'normalize_hex: short form expands',
                Color::normalize_hex('#abc'),
                '#aabbcc'
            ),
            self::assert_equals(
                'normalize_hex: without hash',
                Color::normalize_hex('aabbcc'),
                '#aabbcc'
            ),
            self::assert_equals(
                'normalize_hex: uppercase to lowercase',
                Color::normalize_hex('#ABC123'),
                '#abc123'
            ),
            self::assert_equals(
                'normalize_hex: invalid returns null',
                Color::normalize_hex('not-a-color'),
                null
            ),
            self::assert_equals(
                'is_dark: black is dark',
                Color::is_dark('#000000'),
                true
            ),
            self::assert_equals(
                'is_dark: white is not dark',
                Color::is_dark('#ffffff'),
                false
            ),
            self::assert_equals(
                'is_dark: blue is dark',
                Color::is_dark('#0000ff'),
                true
            ),
            self::assert_equals(
                'is_dark: yellow is NOT dark (WCAG luminance check)',
                Color::is_dark('#ffff00'),
                false
            ),
            self::assert_equals(
                'text_color: black on white',
                Color::text_color('#ffffff'),
                '#000000'
            ),
            self::assert_equals(
                'text_color: white on black',
                Color::text_color('#000000'),
                '#ffffff'
            ),
            self::assert_equals(
                'contrast_ratio: black/white = 21',
                Color::contrast_ratio('#000000', '#ffffff'),
                21.0
            ),
        ];
    }

    /* --------------------------------------------------------
       YOUTUBE
       -------------------------------------------------------- */

    private static function test_youtube() {
        return [
            self::assert_equals(
                'extract_id: youtu.be short URL',
                YouTube::extract_id('https://youtu.be/dQw4w9WgXcQ'),
                'dQw4w9WgXcQ'
            ),
            self::assert_equals(
                'extract_id: watch?v= URL',
                YouTube::extract_id('https://www.youtube.com/watch?v=dQw4w9WgXcQ'),
                'dQw4w9WgXcQ'
            ),
            self::assert_equals(
                'extract_id: watch URL with ampersand params',
                YouTube::extract_id('https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=42s'),
                'dQw4w9WgXcQ'
            ),
            self::assert_equals(
                'extract_id: embed URL',
                YouTube::extract_id('https://www.youtube.com/embed/dQw4w9WgXcQ'),
                'dQw4w9WgXcQ'
            ),
            self::assert_equals(
                'extract_id: shorts URL',
                YouTube::extract_id('https://www.youtube.com/shorts/dQw4w9WgXcQ'),
                'dQw4w9WgXcQ'
            ),
            self::assert_equals(
                'extract_id: bare 11-char ID passes through',
                YouTube::extract_id('dQw4w9WgXcQ'),
                'dQw4w9WgXcQ'
            ),
            self::assert_equals(
                'extract_id: invalid URL returns null',
                YouTube::extract_id('https://example.com/not-youtube'),
                null
            ),
            self::assert_equals(
                'extract_id: empty input returns null',
                YouTube::extract_id(''),
                null
            ),
            self::assert_contains(
                'render_embed: produces iframe',
                YouTube::render_embed('https://youtu.be/dQw4w9WgXcQ'),
                '<iframe'
            ),
            self::assert_contains(
                'render_embed: uses nocookie domain',
                YouTube::render_embed('dQw4w9WgXcQ'),
                'youtube-nocookie.com/embed/dQw4w9WgXcQ'
            ),
            self::assert_equals(
                'render_embed: invalid input returns empty',
                YouTube::render_embed('https://example.com/nope'),
                ''
            ),
            self::assert_equals(
                'thumbnail_url: default size',
                YouTube::thumbnail_url('dQw4w9WgXcQ'),
                'https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg'
            ),
            self::assert_equals(
                'thumbnail_url: maxres size',
                YouTube::thumbnail_url('dQw4w9WgXcQ', 'maxres'),
                'https://i.ytimg.com/vi/dQw4w9WgXcQ/maxresdefault.jpg'
            ),
        ];
    }

    /* --------------------------------------------------------
       LINES
       -------------------------------------------------------- */

    private static function test_lines() {
        return [
            self::assert_equals(
                'to_array: basic unix newlines',
                Lines::to_array("one\ntwo\nthree"),
                ['one', 'two', 'three']
            ),
            self::assert_equals(
                'to_array: windows CRLF',
                Lines::to_array("one\r\ntwo\r\nthree"),
                ['one', 'two', 'three']
            ),
            self::assert_equals(
                'to_array: trims whitespace per line',
                Lines::to_array("  one  \n  two  \n  three  "),
                ['one', 'two', 'three']
            ),
            self::assert_equals(
                'to_array: filters empty lines',
                Lines::to_array("one\n\n\ntwo\n  \nthree"),
                ['one', 'two', 'three']
            ),
            self::assert_equals(
                'to_array: empty input returns empty array',
                Lines::to_array(''),
                []
            ),
            self::assert_equals(
                'to_array: non-string returns empty array',
                Lines::to_array(null),
                []
            ),
            self::assert_equals(
                'first: returns first non-empty',
                Lines::first("\n\nfirst\nsecond"),
                'first'
            ),
            self::assert_equals(
                'first: empty input returns null',
                Lines::first(''),
                null
            ),
            self::assert_equals(
                'count_non_empty: counts correctly',
                Lines::count_non_empty("a\n\nb\n\nc"),
                3
            ),
            self::assert_equals(
                'from_array: roundtrip',
                Lines::from_array(['one', '', 'two', '  ', 'three']),
                "one\ntwo\nthree"
            ),
        ];
    }

    /* --------------------------------------------------------
       ASSERTION HELPERS
       -------------------------------------------------------- */

    private static function assert_equals($label, $actual, $expected) {
        return [
            'label' => $label,
            'passed' => $actual === $expected,
            'actual' => self::format_value($actual),
            'expected' => self::format_value($expected),
            'op' => '===',
        ];
    }

    private static function assert_contains($label, $actual, $needle) {
        $passed = is_string($actual) && strpos($actual, $needle) !== false;
        return [
            'label' => $label,
            'passed' => $passed,
            'actual' => self::format_value($actual),
            'expected' => 'contains: ' . self::format_value($needle),
            'op' => 'contains',
        ];
    }

    private static function assert_not_contains($label, $actual, $needle) {
        $passed = is_string($actual) && strpos($actual, $needle) === false;
        return [
            'label' => $label,
            'passed' => $passed,
            'actual' => self::format_value($actual),
            'expected' => 'does NOT contain: ' . self::format_value($needle),
            'op' => 'not_contains',
        ];
    }

    private static function format_value($value) {
        if ($value === null) return 'null';
        if ($value === true) return 'true';
        if ($value === false) return 'false';
        if (is_array($value)) return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (is_string($value) && strlen($value) > 200) {
            return substr($value, 0, 197) . '...';
        }
        return (string) $value;
    }
}
