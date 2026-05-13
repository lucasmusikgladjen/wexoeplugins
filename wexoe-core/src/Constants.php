<?php
namespace Wexoe\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Wexoe Core constants — exposes RESERVED_SLUGS (used by wexoe-pages and the
 * builder to prevent collisions with dedicated plugin shortcodes).
 */
class Constants {

    /**
     * Slugs som inte får användas för cms_unique_pages eftersom de redan
     * är hanterade av dedikerade plugins (wexoe-contact-page etc.) eller
     * reserverade för framtida bruk.
     */
    const RESERVED_SLUGS = [
        'kontakt',
        'nedladdningar',
        'om-oss-statisk',
    ];

    /** @param string $slug */
    public static function is_reserved_slug($slug) {
        if (!is_string($slug)) return false;
        return in_array(strtolower(trim($slug)), self::RESERVED_SLUGS, true);
    }
}
