<?php
/**
 * Write-entity schema: cms_pages
 *
 * Speglar entities/cms_pages.php för WRITE-vägen (builder /editor/page).
 * snake_case-passthrough.
 */

if (!defined('ABSPATH')) exit;

return [
    'table_id' => 'tblglNKHehRWy3lEM',
    'base_id'  => \Wexoe\Core\Plugin::SSOT_BASE_ID,

    'field_types' => [
        'country_ids'  => 'link',
        'division_ids' => 'link',
        'section_ids'  => 'link',
    ],

    'fields' => [
        'slug' => 'slug',
        'internal_label' => 'internal_label',
        'internal_notes' => 'internal_notes',
        'h1' => 'h1',
        'seo_title' => 'seo_title',
        'seo_description' => 'seo_description',
        'og_image_url' => 'og_image_url',
        'is_published' => 'is_published',
        'country_ids' => 'country_ids',
        'division_ids' => 'division_ids',
        'page_theme' => 'page_theme',
        'max_width' => 'max_width',
        'section_ids' => 'section_ids',
    ],
];
