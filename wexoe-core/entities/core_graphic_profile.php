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
        'slug' => 'Slug',
        'is_default' => ['source' => 'Is Default', 'type' => 'bool'],
        'logo_primary' => ['source' => 'Logo Primary', 'type' => 'attachment'],
        'logo_dark_background' => ['source' => 'Logo Dark Background', 'type' => 'attachment'],
        'favicon' => ['source' => 'Favicon', 'type' => 'attachment'],
        'color_primary' => 'Color Primary',
        'color_secondary' => 'Color Secondary',
        'color_accent' => 'Color Accent',
        'color_background_light' => 'Color Background Light',
        'color_background_dark' => 'Color Background Dark',
        'color_text_primary' => 'Color Text Primary',
        'color_text_secondary' => 'Color Text Secondary',
        'font_heading' => 'Font Heading',
        'font_body' => 'Font Body',
        'font_css_url' => 'Font CSS URL',
        'division_ids' => ['source' => 'Division', 'type' => 'link', 'entity' => 'core_divisions'],
    ],
];
