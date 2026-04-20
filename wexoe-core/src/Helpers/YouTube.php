<?php
namespace Wexoe\Core\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * YouTube URL handling — extract IDs from any URL format, render embed iframes,
 * build thumbnail URLs.
 *
 * Supported URL formats:
 *   https://www.youtube.com/watch?v=VIDEO_ID
 *   https://youtube.com/watch?v=VIDEO_ID&t=42s
 *   https://youtu.be/VIDEO_ID
 *   https://youtu.be/VIDEO_ID?t=42
 *   https://www.youtube.com/embed/VIDEO_ID
 *   https://www.youtube.com/v/VIDEO_ID
 *   https://www.youtube.com/shorts/VIDEO_ID
 *   Also accepts the bare video ID (11 chars).
 *
 * A YouTube video ID is always exactly 11 characters, using [A-Za-z0-9_-].
 */
class YouTube {

    const ID_PATTERN = '/^[A-Za-z0-9_-]{11}$/';

    /**
     * Extract the 11-character video ID from a URL (or return the input if it
     * already IS a valid video ID).
     *
     * @return string|null Video ID, or null if no valid ID found
     */
    public static function extract_id($url) {
        if (!is_string($url) || $url === '') return null;
        $url = trim($url);

        // Already an ID?
        if (preg_match(self::ID_PATTERN, $url)) {
            return $url;
        }

        // Patterns covering all known URL forms
        $patterns = [
            '#(?:youtube\.com/watch\?(?:.*&)?v=)([A-Za-z0-9_-]{11})#i',
            '#(?:youtu\.be/)([A-Za-z0-9_-]{11})#i',
            '#(?:youtube\.com/embed/)([A-Za-z0-9_-]{11})#i',
            '#(?:youtube\.com/v/)([A-Za-z0-9_-]{11})#i',
            '#(?:youtube\.com/shorts/)([A-Za-z0-9_-]{11})#i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Render a responsive YouTube embed iframe.
     *
     * Options:
     *   - width    (int)    default 560
     *   - height   (int)    default 315
     *   - autoplay (bool)   default false
     *   - mute     (bool)   default false (note: autoplay requires mute on most browsers)
     *   - controls (bool)   default true
     *   - rel      (bool)   default false (don't show related videos)
     *   - title    (string) default 'YouTube video'
     *   - class    (string) default 'wexoe-youtube-embed'
     *   - wrapper  (bool)   default true — wrap in responsive 16:9 div
     *
     * @return string HTML for the embed, or empty string if URL is invalid
     */
    public static function render_embed($url_or_id, $options = []) {
        $id = self::extract_id($url_or_id);
        if ($id === null) return '';

        $defaults = [
            'width' => 560,
            'height' => 315,
            'autoplay' => false,
            'mute' => false,
            'controls' => true,
            'rel' => false,
            'title' => 'YouTube video',
            'class' => 'wexoe-youtube-embed',
            'wrapper' => true,
        ];
        $opts = array_merge($defaults, is_array($options) ? $options : []);

        $params = [];
        if ($opts['autoplay']) $params['autoplay'] = 1;
        if ($opts['mute']) $params['mute'] = 1;
        if (!$opts['controls']) $params['controls'] = 0;
        if (!$opts['rel']) $params['rel'] = 0;

        $src = 'https://www.youtube-nocookie.com/embed/' . $id;
        if (!empty($params)) {
            $src .= '?' . http_build_query($params);
        }

        $iframe = sprintf(
            '<iframe src="%s" width="%d" height="%d" title="%s" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen class="%s" loading="lazy"></iframe>',
            esc_url($src),
            (int) $opts['width'],
            (int) $opts['height'],
            esc_attr($opts['title']),
            esc_attr($opts['class'])
        );

        if ($opts['wrapper']) {
            return '<div class="wexoe-youtube-wrapper" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;"><style>.wexoe-youtube-wrapper iframe{position:absolute;top:0;left:0;width:100%;height:100%;}</style>' . $iframe . '</div>';
        }

        return $iframe;
    }

    /**
     * Get URL to a YouTube thumbnail image.
     *
     * Sizes:
     *   - default  (120x90)
     *   - medium   (320x180)  alias 'mqdefault'
     *   - high     (480x360)  alias 'hqdefault'  [DEFAULT]
     *   - standard (640x480)  alias 'sddefault'
     *   - maxres   (1280x720) alias 'maxresdefault' (not always available)
     *
     * @return string|null URL to thumbnail, or null if video ID can't be extracted
     */
    public static function thumbnail_url($url_or_id, $size = 'high') {
        $id = self::extract_id($url_or_id);
        if ($id === null) return null;

        $sizes = [
            'default'  => 'default',
            'medium'   => 'mqdefault',
            'high'     => 'hqdefault',
            'standard' => 'sddefault',
            'maxres'   => 'maxresdefault',
        ];
        $suffix = isset($sizes[$size]) ? $sizes[$size] : 'hqdefault';

        return 'https://i.ytimg.com/vi/' . $id . '/' . $suffix . '.jpg';
    }

    /**
     * Check whether a string looks like a YouTube URL or ID at all.
     * (Cheap check — doesn't verify the video actually exists.)
     */
    public static function is_valid($url_or_id) {
        return self::extract_id($url_or_id) !== null;
    }
}
