<?php
/**
 * Entity schema: lp_tabs
 *
 * LP-flikar — polymorfa (textimage, fullmedia, faq, calameo, downloads, compare, steps).
 * Airtable-tabell: cms_landing_page_tabs (tblp8d32aj5BgGMvE) i Wexoe NY.
 *
 * Primärnyckel: 'name' (visningsnamn på flik-pill).
 *
 * Synlighetsfilter: tabs har 'is_active' + 'order' som styr vilka som visas och i vilken ordning.
 * Filtrering görs i feature-pluginet, inte i schemat — Core normaliserar alla rader.
 */

if (!defined('ABSPATH')) exit;

return [
    'base_id' => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    'table_id' => 'tblp8d32aj5BgGMvE',
    'primary_key' => 'name',
    'cache_ttl' => 86400,
    'required' => ['name'],
    'fields' => [
        // Core
        'name' => 'name',
        'internal_notes' => 'internal_notes',
        'is_active' => ['source' => 'is_active', 'type' => 'bool'],
        'order' => ['source' => 'order', 'type' => 'float'],
        'tab_type' => 'tab_type',

        // Text + Image tab
        'ti_h2' => 'ti_h2',
        'ti_text' => 'ti_text',
        'ti_benefits' => ['source' => 'ti_benefits', 'type' => 'lines'],
        'ti_image_url' => 'ti_image_url',
        'ti_inverted' => ['source' => 'ti_inverted', 'type' => 'bool'],

        // Full media tab
        'fm_url' => 'fm_url',

        // FAQ tab
        'faq_items' => 'faq_items',

        // Calameo tab — tre slots
        'calameo_1_title' => 'calameo_1_title',
        'calameo_1_src' => 'calameo_1_src',
        'calameo_2_title' => 'calameo_2_title',
        'calameo_2_src' => 'calameo_2_src',
        'calameo_3_title' => 'calameo_3_title',
        'calameo_3_src' => 'calameo_3_src',

        // Downloads tab
        'download_ids' => [
            'source' => 'download_ids',
            'type' => 'link',
            'entity' => 'lp_downloads',
        ],

        // Compare tab
        'compare_title' => 'compare_title',
        'compare_col_a' => 'compare_col_a',
        'compare_col_b' => 'compare_col_b',
        'compare_rows' => 'compare_rows',

        // Steps tab
        'steps_title' => 'steps_title',
        'steps' => 'steps',

        // Back-link
        'landing_page_ids' => [
            'source' => 'landing_page_ids',
            'type' => 'link',
            'entity' => 'landing_pages',
        ],
    ],
];
