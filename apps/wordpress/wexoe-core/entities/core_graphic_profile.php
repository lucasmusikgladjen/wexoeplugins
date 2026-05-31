<?php
/**
 * Entity schema: core_graphic_profile
 *
 * SSOT — Grafisk profil (singleton/per-division).
 * Airtable-tabell: core_graphic_profile (tbl4c4HjiKVCcJI5v) i Wexoe NY.
 *
 * Slå upp via `Wexoe\Core\Helpers\Singletons::graphic_profile_for_division($slug)`.
 */

if (!defined('ABSPATH')) exit;

return [
    'base_id' => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    'table_id' => 'tbl4c4HjiKVCcJI5v',
    'primary_key' => 'slug',
    'cache_ttl' => 3600,
    'required' => ['slug'],
    'fields' => [
        'slug' => 'slug',
        'internal_notes' => 'internal_notes',
        'is_default' => ['source' => 'is_default', 'type' => 'bool'],
        'logo_primary_url' => 'logo_primary_url',
        'logo_dark_url' => 'logo_dark_url',
        'icon_light_url' => 'icon_light_url',
        'icon_dark_url' => 'icon_dark_url',
        'favicon_url' => 'favicon_url',
        'color_primary' => 'color_primary',
        'color_secondary' => 'color_secondary',
        'color_accent' => 'color_accent',
        'color_background_light' => 'color_background_light',
        'color_background_dark' => 'color_background_dark',
        'color_text_primary' => 'color_text_primary',
        'color_text_secondary' => 'color_text_secondary',
        'font_heading' => 'font_heading',
        'font_body' => 'font_body',
        'font_css_url' => 'font_css_url',
        'division_ids' => ['source' => 'division_ids', 'type' => 'link', 'entity' => 'core_divisions'],
    ],
];
