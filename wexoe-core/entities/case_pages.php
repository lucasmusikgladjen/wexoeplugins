<?php
/**
 * Entity schema: case_pages
 *
 * Kundcase — en record per case-story. Visas i två kontexter:
 *   1. Som kort på customer_type_pages (audience-sidor) — `card_*`-fält.
 *   2. Som dedikerad full case-sida via [wexoe_case slug="..."] (kommer i
 *      senare task) — `hero_*`, `challenge_*`, `solution_*`, `result_*`,
 *      `quote_*`, `image_gallery`, `cta_banner_*`, `contact_form_*`.
 *
 * Airtable-tabell: cms_case_pages (tbl3uMV6IpRIZeucA) i Wexoe NY.
 * Primärnyckel: 'slug'.
 *
 * Konvention: snake_case överallt — passthrough.
 *
 * `legacy_external_url` används av audience-rendreraren som "Läs mer"-mål
 * när full case-sida ännu inte är publicerad (slug fanns inte i gamla basen).
 */

if (!defined('ABSPATH')) exit;

return [
    'base_id' => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    'table_id' => 'tbl3uMV6IpRIZeucA',
    'primary_key' => 'slug',
    'cache_ttl' => 86400,
    'required' => ['slug'],
    'fields' => [
        // Core
        'slug' => 'slug',
        'internal_notes' => 'internal_notes',
        'is_active' => ['source' => 'is_active', 'type' => 'bool'],
        'order' => ['source' => 'order', 'type' => 'int'],
        'country_ids' => ['source' => 'country_ids', 'type' => 'link', 'entity' => 'core_countries'],
        'customer_type_ids' => ['source' => 'customer_type_ids', 'type' => 'link', 'entity' => 'core_customer_types'],
        'customer_type_page_ids' => ['source' => 'customer_type_page_ids', 'type' => 'link', 'entity' => 'customer_type_pages'],

        // Card-lager (renderat på customer_type_pages)
        'card_title' => 'card_title',
        'card_description' => 'card_description',
        'card_result' => 'card_result',
        'card_image_url' => 'card_image_url',
        'card_cta_text' => 'card_cta_text',
        'card_industry' => 'card_industry',
        'legacy_external_url' => 'legacy_external_url',

        // Full case-sida — hero & SEO
        'h1' => 'h1',
        'seo_title' => 'seo_title',
        'seo_description' => 'seo_description',
        'og_image_url' => 'og_image_url',
        'hero_eyebrow' => 'hero_eyebrow',
        'hero_description' => 'hero_description',
        'hero_image_url' => 'hero_image_url',
        'hero_cta_text' => 'hero_cta_text',
        'hero_cta_url' => 'hero_cta_url',

        // Customer-info
        'customer_name' => 'customer_name',
        'customer_logo_url' => 'customer_logo_url',
        'customer_industry' => 'customer_industry',
        'customer_size' => 'customer_size',

        // Story: Challenge → Solution → Result
        'challenge_h2' => 'challenge_h2',
        'challenge_text' => 'challenge_text',
        'solution_h2' => 'solution_h2',
        'solution_text' => 'solution_text',
        'solution_bullets' => ['source' => 'solution_bullets', 'type' => 'lines'],
        'result_h2' => 'result_h2',
        'result_text' => 'result_text',
        'result_metrics' => ['source' => 'result_metrics', 'type' => 'lines'],

        // Quote
        'quote_text' => 'quote_text',
        'quote_author' => 'quote_author',
        'quote_author_title' => 'quote_author_title',
        'quote_author_image_url' => 'quote_author_image_url',

        // Gallery + relations
        'image_gallery' => ['source' => 'image_gallery', 'type' => 'lines'],
        'product_ids' => ['source' => 'product_ids', 'type' => 'link', 'entity' => 'products'],
        'partner_ids' => ['source' => 'partner_ids', 'type' => 'link', 'entity' => 'core_partners'],

        // CTA-banner
        'cta_banner_text' => 'cta_banner_text',
        'cta_banner_button_text' => 'cta_banner_button_text',
        'cta_banner_url' => 'cta_banner_url',

        // Contact form
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
    ],
];
