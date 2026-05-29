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

        // Sidebar: case — länk till ETT kundcase (cms_cases). Renderaren läser
        // kort-lagret (card_*) + permalink från det länkade caset. Ersätter de
        // gamla inline-fälten case_title/description/image_url/outcomes/cta_*.
        'case_id' => ['source' => 'case_id', 'type' => 'link', 'entity' => 'cms_cases'],

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
        'contact_form_eyebrow' => 'contact_form_eyebrow',
        'contact_form_title' => 'contact_form_title',
        'contact_form_subtitle' => 'contact_form_subtitle',
        'contact_form_layout' => 'contact_form_layout',
        'contact_form_theme' => 'contact_form_theme',
        'contact_form_show_company' => ['source' => 'contact_form_show_company', 'type' => 'bool'],
        'contact_form_show_phone' => ['source' => 'contact_form_show_phone', 'type' => 'bool'],
        'contact_form_show_dropdown' => ['source' => 'contact_form_show_dropdown', 'type' => 'bool'],
        'contact_form_dropdown_label' => 'contact_form_dropdown_label',
        'contact_form_options' => ['source' => 'contact_form_options', 'type' => 'lines'],
        'contact_form_cta_text' => 'contact_form_cta_text',
        'contact_form_message_label' => 'contact_form_message_label',
        'contact_form_trust_signals' => ['source' => 'contact_form_trust_signals', 'type' => 'lines'],
        'contact_form_show_contact_person' => ['source' => 'contact_form_show_contact_person', 'type' => 'bool'],

        // Linked records
        'tab_ids' => [
            'source' => 'tab_ids',
            'type' => 'link',
            'entity' => 'lp_tabs',
        ],
    ],
];
