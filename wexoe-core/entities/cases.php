<?php
/**
 * Entity schema: cases
 *
 * Kundcase i den nya, rikare formaten (separat från legacy `case_pages` som
 * pekar mot cms_case_pages). En record per case-story.
 *
 * Airtable-tabell: cms_cases (tblxH3ECSMvDTYrIQ) i Wexoe NY.
 * Primärnyckel: 'slug'.
 *
 * Visas i två kontexter:
 *   1. Som kort på partner-sidor (cms_partner_pages.case_ids) — feature-pluginet
 *      läser slug, title, subtitle, lead_image_url och bygger korten direkt.
 *   2. Som dedikerad full case-sida via [wexoe_case slug="..."] (separat plugin) —
 *      med challenge/solution/results/testimonial/gallery/about-customer.
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

        // SEO
        'seo_title' => 'seo_title',
        'seo_description' => 'seo_description',
        'og_image_url' => 'og_image_url',

        // Header / lead
        'industry' => 'industry',
        'title' => 'title',
        'subtitle' => 'subtitle',
        'customer_name' => 'customer_name',
        'location' => 'location',
        'project_year' => 'project_year',
        'project_type' => 'project_type',
        'reading_time' => 'reading_time',
        'header_logos' => 'header_logos',
        'lead_image_url' => 'lead_image_url',
        'lead_image_caption' => 'lead_image_caption',
        'lead_paragraph' => 'lead_paragraph',

        // Quick stats strip
        'show_stats_strip' => ['source' => 'show_stats_strip', 'type' => 'bool'],
        'quick_stat_1_value' => 'quick_stat_1_value',
        'quick_stat_1_label' => 'quick_stat_1_label',
        'quick_stat_2_value' => 'quick_stat_2_value',
        'quick_stat_2_label' => 'quick_stat_2_label',
        'quick_stat_3_value' => 'quick_stat_3_value',
        'quick_stat_3_label' => 'quick_stat_3_label',
        'quick_stat_4_value' => 'quick_stat_4_value',
        'quick_stat_4_label' => 'quick_stat_4_label',

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

        // Products / articles referenced
        'products_title' => 'products_title',
        'products_meta' => 'products_meta',
        'product_ids' => ['source' => 'product_ids', 'type' => 'link', 'entity' => 'products'],
        'article_ids' => ['source' => 'article_ids', 'type' => 'link', 'entity' => 'articles'],

        // Results
        'results_eyebrow' => 'results_eyebrow',
        'results_title' => 'results_title',
        'results_text' => 'results_text',
        'result_1_value' => 'result_1_value',
        'result_1_label' => 'result_1_label',
        'result_2_value' => 'result_2_value',
        'result_2_label' => 'result_2_label',
        'result_3_value' => 'result_3_value',
        'result_3_label' => 'result_3_label',
        'result_4_value' => 'result_4_value',
        'result_4_label' => 'result_4_label',

        // Testimonial
        'show_testimonial' => ['source' => 'show_testimonial', 'type' => 'bool'],
        'testimonial_quote' => 'testimonial_quote',
        'testimonial_photo_url' => 'testimonial_photo_url',
        'testimonial_author_name' => 'testimonial_author_name',
        'testimonial_author_title' => 'testimonial_author_title',

        // Gallery (pseudo_array count:6)
        'show_gallery' => ['source' => 'show_gallery', 'type' => 'bool'],
        'gallery_title' => 'gallery_title',
        'gallery_image_1_url' => 'gallery_image_1_url',
        'gallery_image_1_caption' => 'gallery_image_1_caption',
        'gallery_image_2_url' => 'gallery_image_2_url',
        'gallery_image_2_caption' => 'gallery_image_2_caption',
        'gallery_image_3_url' => 'gallery_image_3_url',
        'gallery_image_3_caption' => 'gallery_image_3_caption',
        'gallery_image_4_url' => 'gallery_image_4_url',
        'gallery_image_4_caption' => 'gallery_image_4_caption',
        'gallery_image_5_url' => 'gallery_image_5_url',
        'gallery_image_5_caption' => 'gallery_image_5_caption',
        'gallery_image_6_url' => 'gallery_image_6_url',
        'gallery_image_6_caption' => 'gallery_image_6_caption',

        // About customer
        'show_about_customer' => ['source' => 'show_about_customer', 'type' => 'bool'],
        'about_customer_logo_url' => 'about_customer_logo_url',
        'about_customer_title' => 'about_customer_title',
        'about_customer_text' => 'about_customer_text',
        'about_customer_link_label' => 'about_customer_link_label',
        'about_customer_url' => 'about_customer_url',

        // At-a-glance summary (sidebar / sammanfattning)
        'glance_challenge' => 'glance_challenge',
        'glance_solution' => 'glance_solution',
        'glance_result' => 'glance_result',

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
    ],
];
