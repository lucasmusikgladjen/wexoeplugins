<?php
/**
 * Entity schema: lp_tabs
 *
 * LP-flikar — polymorfa (textimage, fullmedia, faq, calameo, downloads, compare, steps).
 * Airtable-tabell: LP Tabs (tblvecOh3rAGmw3mw)
 *
 * Primärnyckel: saknas (ingen slug). Uppslag sker via _record_id (find_by_ids).
 *
 * Synlighetsfilter: tabs har 'visa' + 'order' som styr vilka som visas och i vilken ordning.
 * Filtrering görs i feature-pluginet, inte i schemat — Core normaliserar alla rader.
 *
 * Calameo: tre uppsättningar (Calameo 1-3 Title/Src) hanteras som pseudo_array.
 */

if (!defined('ABSPATH')) exit;

return [
    'table_id' => 'tblvecOh3rAGmw3mw',
    'cache_ttl' => 86400,
    'required' => ['name'],
    'fields' => [
        // Core
        'name' => 'Name',
        'order' => ['source' => 'Order', 'type' => 'float'],
        'visa' => ['source' => 'Visa', 'type' => 'bool'],
        'tab_type' => 'Tab Type',

        // Text + Image tab
        'ti_h2' => 'TI H2',
        'ti_text' => 'TI Text',
        'ti_benefits' => [
            'source' => 'TI Benefits',
            'type' => 'lines',
        ],
        'ti_image' => 'TI Image',
        'ti_inverted' => ['source' => 'TI Inverted', 'type' => 'bool'],

        // Full media tab
        'fm_url' => 'FM URL',

        // FAQ tab
        'faq_items' => 'FAQ Items',

        // Calameo tab — pseudo-array (3 slots)
        'calameos' => [
            'type' => 'pseudo_array',
            'prefix' => 'Calameo',
            'count' => 3,
            'fields' => [
                'title' => 'Title',
                'src' => 'Src',
            ],
        ],

        // Downloads tab
        'download_ids' => [
            'source' => 'LP Downloads',
            'type' => 'link',
            'entity' => 'lp_downloads',
        ],

        // Compare tab
        'compare_title' => 'Compare Title',
        'compare_col_a' => 'Compare Col A',
        'compare_col_b' => 'Compare Col B',
        'compare_rows' => 'Compare Rows',

        // Steps tab
        'steps_title' => 'Steps Title',
        'steps' => 'Steps',

        // Back-link (not used in rendering but part of schema)
        'landing_page_ids' => [
            'source' => 'Landing Page',
            'type' => 'link',
        ],
    ],
];
