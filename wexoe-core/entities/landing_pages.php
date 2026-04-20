<?php
/**
 * Entity schema: landing_pages
 *
 * CMS-tabell med modulära landningssidor.
 * Airtable-tabell: Landing Pages (tbl8KDqGq0Ray1uqS)
 *
 * Primärnyckel: 'slug' — matchar [wexoe_landing slug="..."] shortcodes.
 *
 * Sidebars: fyra typer (case, calculator, event, leadmagnet) styrs av
 * sidebar_type single-select. Varje typ har sina egna fält som bara är
 * relevanta om den typen är aktiv. Vi normaliserar alla för enkelhetens skull.
 */

if (!defined('ABSPATH')) exit;

return [
    'table_id' => 'tbl8KDqGq0Ray1uqS',
    'primary_key' => 'slug',
    'cache_ttl' => 86400,
    'required' => ['slug'],
    'fields' => [
        // Core
        'name' => 'Name',
        'slug' => 'Slug',
        'h1' => 'H1',

        // Hero
        'hero_description' => 'Hero Description',
        'hero_image' => 'Hero Image',
        'hero_cta_text' => 'Hero CTA Text',
        'hero_cta_url' => 'Hero CTA URL',
        'hero_cta2_text' => 'Hero CTA2 Text',
        'hero_cta2_url' => 'Hero CTA2 URL',

        // Content
        'content_h2' => 'Content H2',
        'content_text' => 'Content Text',
        'content_benefits' => [
            'source' => 'Content Benefits',
            'type' => 'lines',
        ],

        // Sidebar type
        'sidebar_type' => 'Sidebar Type',

        // Sidebar: case
        'case_title' => 'Case Title',
        'case_description' => 'Case Description',
        'case_image' => 'Case Image',
        'case_outcomes' => [
            'source' => 'Case Outcomes',
            'type' => 'lines',
        ],
        'case_cta_text' => 'Case CTA Text',
        'case_cta_url' => 'Case CTA URL',

        // Sidebar: calculator
        'calc_title' => 'Calc Title',
        'calc_html' => 'Calc HTML',

        // Sidebar: event
        'event_type' => 'Event Type',
        'event_title' => 'Event Title',
        'event_description' => 'Event Description',
        'event_date' => 'Event Date',
        'event_location' => 'Event Location',
        'event_webhook' => 'Event Webhook',

        // Sidebar: leadmagnet
        'magnet_title' => 'Magnet Title',
        'magnet_format' => 'Magnet Format',
        'magnet_description' => 'Magnet Description',
        'magnet_file_url' => 'Magnet File URL',
        'magnet_webhook' => 'Magnet Webhook',

        // Contact
        'contact_name' => 'Contact Name',
        'contact_title' => 'Contact Title',
        'contact_email' => 'Contact Email',
        'contact_phone' => 'Contact Phone',
        'contact_image' => 'Contact Image',
        'contact_quote' => 'Contact Quote',

        // Visibility toggles
        'show_content' => ['source' => 'Show Content', 'type' => 'bool'],
        'show_sidebar' => ['source' => 'Show Sidebar', 'type' => 'bool'],
        'show_contact' => ['source' => 'Show Contact', 'type' => 'bool'],
        'show_tabs' => ['source' => 'Show Tabs', 'type' => 'bool'],

        // Colors
        'color_main' => 'Color Main',
        'color_secondary' => 'Color Secondary',

        // Linked records
        'tab_ids' => [
            'source' => 'LP Tabs',
            'type' => 'link',
            'entity' => 'lp_tabs',
        ],
    ],
];
