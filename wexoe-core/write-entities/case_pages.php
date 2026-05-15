<?php
/**
 * Write-entity schema: case_pages
 *
 * Domain-key → Airtable field name. Speglar entities/case_pages.php men för
 * WRITE-vägen (framtida /editor/case när full-case-editor byggs).
 *
 * Konvention: snake_case överallt — passthrough.
 */

if (!defined('ABSPATH')) exit;

return [
    'table_id' => 'tbl3uMV6IpRIZeucA',
    'base_id'  => \Wexoe\Core\Plugin::SSOT_BASE_ID,

    'field_types' => [
        'country_ids' => 'link',
        'customer_type_ids' => 'link',
        'customer_type_page_ids' => 'link',
        'product_ids' => 'link',
        'partner_ids' => 'link',
    ],

    'fields' => [
        'slug' => 'slug',
        'internal_notes' => 'internal_notes',
        'is_active' => 'is_active',
        'order' => 'order',
        'country_ids' => 'country_ids',
        'customer_type_ids' => 'customer_type_ids',
        'customer_type_page_ids' => 'customer_type_page_ids',

        'card_title' => 'card_title',
        'card_description' => 'card_description',
        'card_result' => 'card_result',
        'card_image_url' => 'card_image_url',
        'card_cta_text' => 'card_cta_text',
        'legacy_external_url' => 'legacy_external_url',

        'h1' => 'h1',
        'seo_title' => 'seo_title',
        'seo_description' => 'seo_description',
        'og_image_url' => 'og_image_url',
        'hero_eyebrow' => 'hero_eyebrow',
        'hero_description' => 'hero_description',
        'hero_image_url' => 'hero_image_url',
        'hero_cta_text' => 'hero_cta_text',
        'hero_cta_url' => 'hero_cta_url',

        'customer_name' => 'customer_name',
        'customer_logo_url' => 'customer_logo_url',
        'customer_industry' => 'customer_industry',
        'customer_size' => 'customer_size',

        'challenge_h2' => 'challenge_h2',
        'challenge_text' => 'challenge_text',
        'solution_h2' => 'solution_h2',
        'solution_text' => 'solution_text',
        'solution_bullets' => 'solution_bullets',
        'result_h2' => 'result_h2',
        'result_text' => 'result_text',
        'result_metrics' => 'result_metrics',

        'quote_text' => 'quote_text',
        'quote_author' => 'quote_author',
        'quote_author_title' => 'quote_author_title',
        'quote_author_image_url' => 'quote_author_image_url',

        'image_gallery' => 'image_gallery',
        'product_ids' => 'product_ids',
        'partner_ids' => 'partner_ids',

        'cta_banner_text' => 'cta_banner_text',
        'cta_banner_button_text' => 'cta_banner_button_text',
        'cta_banner_url' => 'cta_banner_url',

        'show_contact_form' => 'show_contact_form',
        'contact_form_eyebrow' => 'contact_form_eyebrow',
        'contact_form_title' => 'contact_form_title',
        'contact_form_subtitle' => 'contact_form_subtitle',
        'contact_form_layout' => 'contact_form_layout',
        'contact_form_theme' => 'contact_form_theme',
        'contact_form_show_company' => 'contact_form_show_company',
        'contact_form_show_phone' => 'contact_form_show_phone',
        'contact_form_show_dropdown' => 'contact_form_show_dropdown',
        'contact_form_dropdown_label' => 'contact_form_dropdown_label',
        'contact_form_options' => 'contact_form_options',
        'contact_form_cta_text' => 'contact_form_cta_text',
        'contact_form_message_label' => 'contact_form_message_label',
        'contact_form_trust_signals' => 'contact_form_trust_signals',
        'contact_form_show_contact_person' => 'contact_form_show_contact_person',
    ],
];
