<?php
/**
 * Entity schema: cms_section_tabs
 *
 * Sub-records för `tabs`-sektionstypen i cms_page_sections.
 * Airtable-tabell: cms_section_tabs (tblxEtcLO4N9k83rn) i Wexoe NY.
 *
 * Varje record är en flik (pill + panel). Renderas i den ordning som
 * tabs_tab_ids-länken på parent-sektionen anger.
 *
 * Konvention: snake_case överallt — passthrough.
 */

if (!defined('ABSPATH')) exit;

return [
    'base_id' => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    'table_id' => 'tblxEtcLO4N9k83rn',
    'primary_key' => 'name',
    'cache_ttl' => 86400,
    'required' => ['name'],
    'fields' => [
        'name' => 'name',
        'internal_notes' => 'internal_notes',
        'is_active' => ['source' => 'is_active', 'type' => 'bool'],
        'order' => ['source' => 'order', 'type' => 'float'],
        'eyebrow' => 'eyebrow',
        'h2' => 'h2',
        'body' => 'body',
        'bullets' => ['source' => 'bullets', 'type' => 'lines'],
        'image_url' => 'image_url',
        'image_alt' => 'image_alt',
        'cta_text' => 'cta_text',
        'cta_url' => 'cta_url',
        'cta2_text' => 'cta2_text',
        'cta2_url' => 'cta2_url',
        'section_ids' => ['source' => 'section_ids', 'type' => 'link', 'entity' => 'cms_page_sections'],
    ],
];
