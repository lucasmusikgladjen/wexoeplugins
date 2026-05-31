<?php
/**
 * Entity schema: cms_cases
 *
 * Kundcase i editorial artikel-format (en enhetlig artikelspalt med sticky
 * "Caset i korthet"-sidebar). En record per case-story. Renderas av
 * PHP-pluginet wexoe-case (FAS 2) via shortcode [wexoe_case slug="..."].
 *
 * Linkar till cms_products och cms_articles för produktlistan. Brand/leverantör
 * hämtas via supplier_ids på den länkade posten — ingen junction-tabell och
 * ingen role_in_solution-data på case-recordet.
 *
 * KANONISK case-entitet. Tidigare fanns även 'case_pages' (tabell
 * cms_case_pages, nu utfasad) samt en redundant alias 'cases' (samma tabell) —
 * båda konsoliderade hit i PR 2. Schemat täcker nu både kort-lagret (card_*,
 * renderat i case-grid och på customer_type_pages) OCH full editorial-sida
 * (challenge/solution/results/testimonial/gallery), plus scope-länkar
 * (country/customer_type/partner) och teaser-länken legacy_external_url.
 *
 * Airtable-tabell: cms_cases (tblxH3ECSMvDTYrIQ) i Wexoe NY.
 * Primärnyckel: 'slug'.
 *
 * Konvention: snake_case överallt — passthrough.
 */

if (!defined('ABSPATH')) exit;

return [
    'base_id' => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    'table_id' => 'tblxH3ECSMvDTYrIQ',
    'primary_key' => 'slug',
    'cache_ttl' => 86400,
    'required' => ['slug'],
    'fields' => [
        // Core
        'slug' => 'slug',
        'internal_notes' => 'internal_notes',
        'is_active' => ['source' => 'is_active', 'type' => 'bool'],
        'order' => ['source' => 'order', 'type' => 'int'],

        // Scope-länkar (SSOT) — driver case-grid pin-then-scope och audience-kort.
        'country_ids' => ['source' => 'country_ids', 'type' => 'link', 'entity' => 'core_countries'],
        'customer_type_ids' => ['source' => 'customer_type_ids', 'type' => 'link', 'entity' => 'core_customer_types'],
        'customer_type_page_ids' => ['source' => 'customer_type_page_ids', 'type' => 'link', 'entity' => 'customer_type_pages'],
        'partner_ids' => ['source' => 'partner_ids', 'type' => 'link', 'entity' => 'core_partners'],

        // Kort-lager (preview) — renderat i case-grid och på customer_type_pages,
        // skiljt från full-sidans title/lead. legacy_external_url = "Läs mer"-mål
        // för teaser-cases som ännu bor på gammal WP/PDF (migrerat från cms_case_pages).
        'card_title' => 'card_title',
        'card_description' => 'card_description',
        'card_result' => 'card_result',
        'card_image_url' => 'card_image_url',
        'card_cta_text' => 'card_cta_text',
        'card_industry' => 'card_industry',
        'legacy_external_url' => 'legacy_external_url',

        // SEO
        'seo_title' => 'seo_title',
        'seo_description' => 'seo_description',
        'og_image_url' => 'og_image_url',

        // Header
        'industry' => 'industry',
        'title' => 'title',
        'subtitle' => 'subtitle',
        'customer_name' => 'customer_name',
        'location' => 'location',
        'project_year' => 'project_year',
        'project_type' => 'project_type',
        'reading_time' => 'reading_time',
        'header_logos' => ['source' => 'header_logos', 'type' => 'lines'],

        // Lead
        'lead_image_url' => 'lead_image_url',
        'lead_image_caption' => 'lead_image_caption',
        'lead_paragraph' => 'lead_paragraph',

        // Stats strip
        'show_stats_strip' => ['source' => 'show_stats_strip', 'type' => 'bool'],
        'quick_stats' => [
            'type' => 'pseudo_array',
            'prefix' => 'quick_stat',
            'count' => 4,
            'fields' => [
                'value' => 'value',
                'label' => 'label',
            ],
        ],

        // Challenge
        'challenge_eyebrow' => 'challenge_eyebrow',
        'challenge_title' => 'challenge_title',
        'challenge_text' => 'challenge_text',
        'challenge_bullets' => ['source' => 'challenge_bullets', 'type' => 'lines'],
        'challenge_image_url' => 'challenge_image_url',
        'challenge_image_caption' => 'challenge_image_caption',

        // Pullquote
        'show_pullquote' => ['source' => 'show_pullquote', 'type' => 'bool'],
        'pullquote_text' => 'pullquote_text',
        'pullquote_attribution' => 'pullquote_attribution',

        // Solution
        'solution_eyebrow' => 'solution_eyebrow',
        'solution_title' => 'solution_title',
        'solution_text' => 'solution_text',
        'solution_image_url' => 'solution_image_url',
        'solution_image_caption' => 'solution_image_caption',

        // Products
        'products_title' => 'products_title',
        'products_meta' => 'products_meta',
        'product_ids' => ['source' => 'product_ids', 'type' => 'link', 'entity' => 'products'],
        'article_ids' => ['source' => 'article_ids', 'type' => 'link', 'entity' => 'articles'],

        // Results
        'results_eyebrow' => 'results_eyebrow',
        'results_title' => 'results_title',
        'results_text' => 'results_text',
        'results' => [
            'type' => 'pseudo_array',
            'prefix' => 'result',
            'count' => 4,
            'fields' => [
                'value' => 'value',
                'label' => 'label',
            ],
        ],

        // Testimonial
        'show_testimonial' => ['source' => 'show_testimonial', 'type' => 'bool'],
        'testimonial_quote' => 'testimonial_quote',
        'testimonial_photo_url' => 'testimonial_photo_url',
        'testimonial_author_name' => 'testimonial_author_name',
        'testimonial_author_title' => 'testimonial_author_title',

        // Gallery
        'show_gallery' => ['source' => 'show_gallery', 'type' => 'bool'],
        'gallery_title' => 'gallery_title',
        'gallery_images' => [
            'type' => 'pseudo_array',
            'prefix' => 'gallery_image',
            'count' => 6,
            'fields' => [
                'url' => 'url',
                'caption' => 'caption',
            ],
        ],

        // About customer
        'show_about_customer' => ['source' => 'show_about_customer', 'type' => 'bool'],
        'about_customer_logo_url' => 'about_customer_logo_url',
        'about_customer_title' => 'about_customer_title',
        'about_customer_text' => 'about_customer_text',
        'about_customer_link_label' => 'about_customer_link_label',
        'about_customer_url' => 'about_customer_url',

        // Glance sidebar
        'glance_challenge' => 'glance_challenge',
        'glance_solution' => 'glance_solution',
        'glance_result' => 'glance_result',

        // Contact form (delad med Core::renderer('contact-form'))
        'show_contact_form' => ['source' => 'show_contact_form', 'type' => 'bool'],
        // Delat contact_form-block: hela blocket bor i contact_form_json
        // (snake_case-nycklar). ContactForm::from_record() avkodar det.
        'contact_form_json' => 'contact_form_json',
    ],
];
