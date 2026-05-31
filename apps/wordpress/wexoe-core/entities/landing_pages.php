<?php
/**
 * Entity schema: landing_pages
 *
 * CMS-tabell med modulära landningssidor.
 * Airtable-tabell: cms_landing_pages (tblpPlk17FZIKawXY) i Wexoe NY.
 *
 * Primärnyckel: 'slug' — matchar [wexoe_landing slug="..."] shortcodes.
 *
 * Sidebars: fyra typer (case, calculator, event, leadmagnet) styrs av
 * sidebar_type single-select. Varje typ har sina egna fält som bara är
 * relevanta om den typen är aktiv. Vi normaliserar alla för enkelhetens skull.
 *
 * Konvention: snake_case både i kod och Airtable display-namn — passthrough.
 */

if (!defined('ABSPATH')) exit;

return [
    'base_id' => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    'table_id' => 'tblpPlk17FZIKawXY',
    'primary_key' => 'slug',
    'cache_ttl' => 86400,
    'required' => ['slug'],
    'fields' => [
        // Core
        'slug' => 'slug',
        'internal_notes' => 'internal_notes',
        'is_active' => ['source' => 'is_active', 'type' => 'bool'],
        'country_ids' => ['source' => 'country_ids', 'type' => 'link', 'entity' => 'core_countries'],
        'h1' => 'h1',

        // SEO
        'seo_title' => 'seo_title',
        'seo_description' => 'seo_description',
        'og_image_url' => 'og_image_url',

        // Hero
        'hero_description' => 'hero_description',
        'hero_image_url' => 'hero_image_url',
        'hero_cta_text' => 'hero_cta_text',
        'hero_cta_url' => 'hero_cta_url',
        'hero_cta2_text' => 'hero_cta2_text',
        'hero_cta2_url' => 'hero_cta2_url',

        // Content
        'content_h2' => 'content_h2',
        'content_text' => 'content_text',
        'content_benefits' => ['source' => 'content_benefits', 'type' => 'lines'],

        // Sidebar type
        'sidebar_type' => 'sidebar_type',

        // Sidebar: case
        'case_title' => 'case_title',
        'case_description' => 'case_description',
        'case_image_url' => 'case_image_url',
        'case_outcomes' => ['source' => 'case_outcomes', 'type' => 'lines'],
        'case_cta_text' => 'case_cta_text',
        'case_cta_url' => 'case_cta_url',

        // Sidebar: calculator
        'calc_title' => 'calc_title',
        'calc_html' => 'calc_html',

        // Sidebar: event
        'event_type' => 'event_type',
        'event_title' => 'event_title',
        'event_description' => 'event_description',
        'event_date' => 'event_date',
        'event_location' => 'event_location',
        'event_webhook' => 'event_webhook',

        // Sidebar: leadmagnet
        'magnet_title' => 'magnet_title',
        'magnet_format' => 'magnet_format',
        'magnet_description' => 'magnet_description',
        'magnet_file_url' => 'magnet_file_url',
        'magnet_webhook' => 'magnet_webhook',

        // Contact (legacy visitkort)
        'contact_name' => 'contact_name',
        'contact_title' => 'contact_title',
        'contact_email' => 'contact_email',
        'contact_phone' => 'contact_phone',
        'contact_image_url' => 'contact_image_url',
        'contact_quote' => 'contact_quote',

        // Visibility toggles
        'show_content' => ['source' => 'show_content', 'type' => 'bool'],
        'show_sidebar' => ['source' => 'show_sidebar', 'type' => 'bool'],
        'show_contact' => ['source' => 'show_contact', 'type' => 'bool'],
        'show_tabs' => ['source' => 'show_tabs', 'type' => 'bool'],

        // Colors
        'color_main' => 'color_main',
        'color_secondary' => 'color_secondary',

        // Contact Form (delad med ContactForm-renderer)
        'show_contact_form' => ['source' => 'show_contact_form', 'type' => 'bool'],
        // Delat contact_form-block: hela blocket bor i contact_form_json
        // (snake_case-nycklar). ContactForm::from_record() avkodar det.
        'contact_form_json' => 'contact_form_json',

        // Linked records
        'tab_ids' => [
            'source' => 'tab_ids',
            'type' => 'link',
            'entity' => 'lp_tabs',
        ],
    ],
];
