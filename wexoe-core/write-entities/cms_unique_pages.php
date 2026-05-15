<?php
/**
 * Write-entity schema: cms_unique_pages
 *
 * Domain-key → Airtable field name. Speglar entities/cms_unique_pages.php
 * men för WRITE-vägen (builder /editor/unique).
 *
 * Post-migration: snake_case överallt — passthrough.
 */

if (!defined('ABSPATH')) exit;

return [
    'table_id' => 'tblpAM1wZWDbrpeai',
    'base_id'  => \Wexoe\Core\Plugin::SSOT_BASE_ID,

    /**
     * Bild-fält är nu singleLineText URL-strängar — ingen attachment_url-konvertering.
     * Linked-record-fält passerar som-är via 'link'-typen.
     */
    'field_types' => [
        'country_ids'  => 'link',
        'division_ids' => 'link',
    ],

    'fields' => [
        // Metadata
        'slug' => 'slug',
        'internal_notes' => 'internal_notes',
        'h1' => 'h1',
        'seo_title' => 'seo_title',
        'seo_description' => 'seo_description',
        'og_image_url' => 'og_image_url',
        'is_published' => 'is_published',
        'country_ids' => 'country_ids',
        'division_ids' => 'division_ids',

        // Hero
        'show_hero' => 'show_hero',
        'hero_eyebrow' => 'hero_eyebrow',
        'hero_h1_override' => 'hero_h1_override',
        'hero_subtitle' => 'hero_subtitle',
        'hero_image_url' => 'hero_image_url',
        'hero_cta_text' => 'hero_cta_text',
        'hero_cta_url' => 'hero_cta_url',
        'hero_theme' => 'hero_theme',

        // Text-image A
        'show_text_image_a' => 'show_text_image_a',
        'text_image_a_h2' => 'text_image_a_h2',
        'text_image_a_body' => 'text_image_a_body',
        'text_image_a_image_url' => 'text_image_a_image_url',
        'text_image_a_reversed' => 'text_image_a_reversed',
        'text_image_a_theme' => 'text_image_a_theme',

        // Text-image B
        'show_text_image_b' => 'show_text_image_b',
        'text_image_b_h2' => 'text_image_b_h2',
        'text_image_b_body' => 'text_image_b_body',
        'text_image_b_image_url' => 'text_image_b_image_url',
        'text_image_b_reversed' => 'text_image_b_reversed',
        'text_image_b_theme' => 'text_image_b_theme',

        // Text-only
        'show_text_only' => 'show_text_only',
        'text_only_h2' => 'text_only_h2',
        'text_only_body' => 'text_only_body',
        'text_only_align' => 'text_only_align',

        // FAQ
        'show_faq' => 'show_faq',
        'faq_h2' => 'faq_h2',
        'faq_items' => 'faq_items',

        // Team Grid
        'show_team_grid' => 'show_team_grid',
        'team_grid_h2' => 'team_grid_h2',
        'team_grid_scope_division' => 'team_grid_scope_division',
        'team_grid_scope_country' => 'team_grid_scope_country',
        'team_grid_limit' => 'team_grid_limit',

        // Partners Marquee
        'show_partners_marquee' => 'show_partners_marquee',
        'partners_marquee_h2' => 'partners_marquee_h2',
        'partners_marquee_scope_division' => 'partners_marquee_scope_division',
        'partners_marquee_scope_country' => 'partners_marquee_scope_country',

        // Testimonial Card
        'show_testimonial_card' => 'show_testimonial_card',
        'testimonial_scope_customer_type' => 'testimonial_scope_customer_type',
        'testimonial_scope_division' => 'testimonial_scope_division',
        'testimonial_scope_country' => 'testimonial_scope_country',

        // CTA Banner
        'show_cta_banner' => 'show_cta_banner',
        'cta_banner_h2' => 'cta_banner_h2',
        'cta_banner_body' => 'cta_banner_body',
        'cta_banner_cta_text' => 'cta_banner_cta_text',
        'cta_banner_cta_url' => 'cta_banner_cta_url',
        'cta_banner_theme' => 'cta_banner_theme',

        // Contact Form
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
