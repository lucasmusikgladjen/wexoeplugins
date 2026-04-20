<?php
/**
 * Entity schema: product_areas
 *
 * CMS-tabell med produktområdessidor (fiber, vfd, robotics, etc.).
 * Airtable-tabell: Product Areas (tblgatNFYFMwF4EcQ)
 *
 * Primärnyckel: 'slug' — används av [wexoe_product_area slug="..."] shortcode.
 *
 * SPECIAL: Fyra uppsättningar av "Normal {N} *"-fält kollapsas till en
 * 'sections'-array via pseudo_array-typen. Detta är stresstestet för
 * schema-lagret — om det klarar detta klarar det allt.
 */

if (!defined('ABSPATH')) exit;

return [
    'table_id' => 'tblgatNFYFMwF4EcQ',
    'primary_key' => 'slug',
    'cache_ttl' => 86400, // 24h
    'required' => ['slug', 'name'],
    'fields' => [
        // Core identifiers
        'name' => 'Name',
        'slug' => 'Slug',
        'h1' => 'H1',

        // Top banner
        'top_bg' => 'Top BG',

        // Hero section
        'hero_h2' => 'Hero H2',
        'hero_text' => 'Hero Text',
        'hero_cta_text' => 'Hero CTA Text',
        'hero_cta_url' => 'Hero CTA URL',
        'hero_benefits' => [
            'source' => 'Hero Benefits',
            'type' => 'lines',
        ],
        'hero_image' => 'Hero Image',
        'hero_bg' => 'Hero BG',
        'hero_accent' => 'Hero Accent',

        // NPI (new product introduction) card
        'npi_title' => 'NPI Title',
        'npi_description' => 'NPI Description',
        'npi_image' => 'NPI Image',
        'npi_link' => 'NPI Link',

        // Toggle section styling
        'toggle_bg' => 'Toggle BG',
        'toggle_header_bg' => 'Toggle Header BG',
        'toggle_accent' => 'Toggle Accent',

        // Solutions section
        'solutions_title' => 'Solutions Title',
        'solutions_bg' => 'Solutions BG',
        'solutions_card_bg' => 'Solutions Card BG',

        // Contact section
        'contact_name' => 'Contact Name',
        'contact_title' => 'Contact Title',
        'contact_email' => 'Contact Email',
        'contact_phone' => 'Contact Phone',
        'contact_image' => 'Contact Image',
        'contact_text' => 'Contact Text',
        'contact_bg' => 'Contact BG',

        // Docs section
        'docs_title' => 'Docs Title',
        'docs_iframe' => 'Docs Iframe',
        'docs_bg' => 'Docs BG',

        // Boolean flags
        'use_side_menu' => ['source' => 'Side menu', 'type' => 'bool'],
        'show_request' => ['source' => 'Request', 'type' => 'bool'],
        'default_open' => ['source' => 'Default open', 'type' => 'bool'],

        // THE HARD ONE: Normal 1-4 pseudo-array
        // Collapses "Normal 1 H2", "Normal 1 Text", "Normal 1 Bullets", ...
        //         "Normal 2 H2", "Normal 2 Text", ...
        // into a single 'sections' array. Empty sections filtered out.
        'sections' => [
            'type' => 'pseudo_array',
            'prefix' => 'Normal',
            'count' => 4,
            'fields' => [
                'h2' => 'H2',
                'text' => 'Text',
                'bullets' => 'Bullets',
                'image' => 'Image',
                'reversed' => 'Reversed',
                'bg' => 'BG',
                'shown_top' => 'upp',
            ],
        ],

        // Linked records
        'product_ids' => [
            'source' => 'Products',
            'type' => 'link',
            'entity' => 'products',
        ],
        'solution_ids' => [
            'source' => 'Solutions',
            'type' => 'link',
            'entity' => 'solutions',
        ],
        'division_ids' => [
            'source' => 'Division',
            'type' => 'link',
            'entity' => 'divisions',
        ],
    ],
];
