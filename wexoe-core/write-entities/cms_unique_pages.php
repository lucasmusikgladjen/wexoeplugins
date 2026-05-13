<?php
/**
 * Write-entity schema: cms_unique_pages
 *
 * Domain-key → Airtable field name. Speglar entities/cms_unique_pages.php
 * men för WRITE-vägen (builder /editor/unique).
 */

if (!defined('ABSPATH')) exit;

return [
    'table_id' => 'tblpAM1wZWDbrpeai',
    'base_id'  => \Wexoe\Core\Plugin::SSOT_BASE_ID,

    /**
     * Domain-keys för image-fält slutar på `_url` för att matcha read-schemats
     * `attachment_url`-typing. WriteRepository konverterar URL-string → [{url}]
     * via `field_types`-hinten.
     *
     * Linked-record-fält (country_ids, division_ids) passar genom som-är via
     * 'link'-typen — utan denna typing skulle WriteRepository JSON-encoda dem.
     */
    'field_types' => [
        'country_ids'            => 'link',
        'division_ids'           => 'link',
        'hero_image_url'         => 'attachment_url',
        'text_image_a_image_url' => 'attachment_url',
        'text_image_b_image_url' => 'attachment_url',
    ],

    'fields' => [
        // Metadata
        'slug' => 'Slug',
        'h1' => 'H1',
        'seo_title' => 'SEO Title',
        'seo_description' => 'SEO Description',
        'og_image_url' => 'OG Image URL',
        'published' => 'Published',
        'country_ids' => 'Country',
        'division_ids' => 'Division',

        // Hero
        'show_hero' => 'Show Hero',
        'hero_eyebrow' => 'Hero Eyebrow',
        'hero_h1_override' => 'Hero H1 Override',
        'hero_subtitle' => 'Hero Subtitle',
        'hero_image_url' => 'Hero Image',
        'hero_cta_text' => 'Hero CTA Text',
        'hero_cta_url' => 'Hero CTA URL',
        'hero_theme' => 'Hero Theme',

        // Text-image A
        'show_text_image_a' => 'Show Text Image A',
        'text_image_a_h2' => 'Text Image A H2',
        'text_image_a_body' => 'Text Image A Body',
        'text_image_a_image_url' => 'Text Image A Image',
        'text_image_a_reversed' => 'Text Image A Reversed',
        'text_image_a_theme' => 'Text Image A Theme',

        // Text-image B
        'show_text_image_b' => 'Show Text Image B',
        'text_image_b_h2' => 'Text Image B H2',
        'text_image_b_body' => 'Text Image B Body',
        'text_image_b_image_url' => 'Text Image B Image',
        'text_image_b_reversed' => 'Text Image B Reversed',
        'text_image_b_theme' => 'Text Image B Theme',

        // Text-only
        'show_text_only' => 'Show Text Only',
        'text_only_h2' => 'Text Only H2',
        'text_only_body' => 'Text Only Body',
        'text_only_align' => 'Text Only Align',

        // FAQ
        'show_faq' => 'Show FAQ',
        'faq_h2' => 'FAQ H2',
        'faq_items' => 'FAQ Items',

        // Team Grid
        'show_team_grid' => 'Show Team Grid',
        'team_grid_h2' => 'Team Grid H2',
        'team_grid_scope_division' => 'Team Grid Scope Division',
        'team_grid_scope_country' => 'Team Grid Scope Country',
        'team_grid_limit' => 'Team Grid Limit',

        // Partners Marquee
        'show_partners_marquee' => 'Show Partners Marquee',
        'partners_marquee_h2' => 'Partners Marquee H2',
        'partners_marquee_scope_division' => 'Partners Marquee Scope Division',
        'partners_marquee_scope_country' => 'Partners Marquee Scope Country',

        // Testimonial Card
        'show_testimonial_card' => 'Show Testimonial Card',
        'testimonial_scope_customer_type' => 'Testimonial Scope Customer Type',
        'testimonial_scope_division' => 'Testimonial Scope Division',
        'testimonial_scope_country' => 'Testimonial Scope Country',

        // CTA Banner
        'show_cta_banner' => 'Show CTA Banner',
        'cta_banner_h2' => 'CTA Banner H2',
        'cta_banner_body' => 'CTA Banner Body',
        'cta_banner_cta_text' => 'CTA Banner CTA Text',
        'cta_banner_cta_url' => 'CTA Banner CTA URL',
        'cta_banner_theme' => 'CTA Banner Theme',

        // Contact Form
        'show_contact_form' => 'Show Contact Form',
        'contact_form_eyebrow' => 'Contact Form Eyebrow',
        'contact_form_title' => 'Contact Form Title',
        'contact_form_subtitle' => 'Contact Form Subtitle',
        'contact_form_layout' => 'Contact Form Layout',
        'contact_form_theme' => 'Contact Form Theme',
        'contact_form_show_company' => 'Contact Form Show Company',
        'contact_form_show_phone' => 'Contact Form Show Phone',
        'contact_form_show_dropdown' => 'Contact Form Show Dropdown',
        'contact_form_dropdown_label' => 'Contact Form Dropdown Label',
        'contact_form_options' => 'Contact Form Options',
        'contact_form_cta_text' => 'Contact Form CTA Text',
        'contact_form_message_label' => 'Contact Form Message Label',
        'contact_form_trust_signals' => 'Contact Form Trust Signals',
        'contact_form_show_contact_person' => 'Contact Form Show Contact Person',
    ],
];
