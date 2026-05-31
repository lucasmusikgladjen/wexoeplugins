<?php
/**
 * Write-entity schema: cms_section_tabs
 *
 * Speglar entities/cms_section_tabs.php för WRITE-vägen.
 * Sub-records för `tabs`-section-typen i cms_page_sections.
 */

if (!defined('ABSPATH')) exit;

return [
    'table_id' => 'tblxEtcLO4N9k83rn',
    'base_id'  => \Wexoe\Core\Plugin::SSOT_BASE_ID,

    'field_types' => [
        'section_ids' => 'link',
    ],

    'fields' => [
        'name' => 'name',
        'internal_notes' => 'internal_notes',
        'is_active' => 'is_active',
        'order' => 'order',
        'eyebrow' => 'eyebrow',
        'h2' => 'h2',
        'body' => 'body',
        'bullets' => 'bullets',
        'image_url' => 'image_url',
        'image_alt' => 'image_alt',
        'cta_text' => 'cta_text',
        'cta_url' => 'cta_url',
        'cta2_text' => 'cta2_text',
        'cta2_url' => 'cta2_url',
        'section_ids' => 'section_ids',
    ],
];
