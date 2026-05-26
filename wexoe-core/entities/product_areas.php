<?php
/**
 * Entity schema: product_areas
 *
 * CMS-tabell med produktområdessidor (fiber, vfd, robotics, etc.).
 * Airtable-tabell: cms_product_pages (tbl5PQR7FNHCogeya) i Wexoe NY.
 *
 * Primärnyckel: 'slug' — används av [wexoe_product_area slug="..."] shortcode.
 *
 * BREAKING CHANGE: Tidigare hade tabellen fyra pseudo-array-sektioner (Normal 1-4).
 * Dessa har lyfts ut till en separat tabell `cms_product_page_sections` (entity
 * `product_page_sections`). Hämta sektionerna via `section_ids`-länken eller via
 * `Core::entity('product_page_sections')->find_by_ids($page['section_ids'])`.
 *
 * Konvention: snake_case överallt — passthrough.
 */

if (!defined('ABSPATH')) exit;

return [
    'base_id' => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    'table_id' => 'tbl5PQR7FNHCogeya',
    'primary_key' => 'slug',
    'cache_ttl' => 86400,
    'required' => ['slug'],
    'fields' => [
        // Core identifiers
        'slug' => 'slug',
        'internal_notes' => 'internal_notes',
        'is_active' => ['source' => 'is_active', 'type' => 'bool'],
        'country_ids' => ['source' => 'country_ids', 'type' => 'link', 'entity' => 'core_countries'],
        'division_ids' => ['source' => 'division_ids', 'type' => 'link', 'entity' => 'core_divisions'],
        'name' => 'name',
        'h1' => 'h1',

        // Card-presentation (när produktområdet visas som kort på andra sidor,
        // t.ex. categories-sektionen på cms_partner_pages).
        'card_image_url' => 'card_image_url',
        'card_description' => 'card_description',

        // Top banner
        'top_bg' => 'top_bg',

        // Hero section
        'hero_h2' => 'hero_h2',
        'hero_text' => 'hero_text',
        'hero_cta_text' => 'hero_cta_text',
        'hero_cta_url' => 'hero_cta_url',
        'hero_benefits' => ['source' => 'hero_benefits', 'type' => 'lines'],
        'hero_image_url' => 'hero_image_url',
        'hero_bg' => 'hero_bg',
        'hero_accent' => 'hero_accent',

        // NPI (new product introduction) card
        'npi_title' => 'npi_title',
        'npi_description' => 'npi_description',
        'npi_image_url' => 'npi_image_url',
        'npi_link' => 'npi_link',

        // Toggle section styling
        'toggle_bg' => 'toggle_bg',
        'toggle_header_bg' => 'toggle_header_bg',
        'toggle_accent' => 'toggle_accent',

        // Solutions section
        'solutions_title' => 'solutions_title',
        'solutions_bg' => 'solutions_bg',
        'solutions_card_bg' => 'solutions_card_bg',

        // Contact section
        'contact_name' => 'contact_name',
        'contact_title' => 'contact_title',
        'contact_email' => 'contact_email',
        'contact_phone' => 'contact_phone',
        'contact_image_url' => 'contact_image_url',
        'contact_text' => 'contact_text',
        'contact_bg' => 'contact_bg',

        // Docs section
        'docs_title' => 'docs_title',
        'docs_iframe' => 'docs_iframe',
        'docs_bg' => 'docs_bg',

        // Boolean flags
        'use_side_menu' => ['source' => 'use_side_menu', 'type' => 'bool'],
        'show_request' => ['source' => 'show_request', 'type' => 'bool'],
        'default_open' => ['source' => 'default_open', 'type' => 'bool'],

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
        'section_ids' => [
            'source' => 'section_ids',
            'type' => 'link',
            'entity' => 'product_page_sections',
        ],
        'product_ids' => [
            'source' => 'product_ids',
            'type' => 'link',
            'entity' => 'products',
        ],
        'solution_ids' => [
            'source' => 'solution_ids',
            'type' => 'link',
            'entity' => 'solutions',
        ],
    ],
];
