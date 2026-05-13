<?php
/**
 * Entity schema: cms_unique_pages
 *
 * CMS-tabell för Tier 2-sidor (one-off meta-sidor: om-oss, karriär etc.).
 * Renderas av wexoe-pages-pluginen via shortcoden `[wexoe_page slug="..."]`.
 *
 * Airtable-tabell: cms_unique_pages (tblpAM1wZWDbrpeai) i Wexoe NY.
 *
 * Data-driven: varje sektion har en `show_<x>` checkbox och tillhörande fält.
 * Sektion-ordning är fast (definierad i wexoe-pages.php), inte redaktör-styrd.
 */

if (!defined('ABSPATH')) exit;

return [
    'base_id' => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    'table_id' => 'tblpAM1wZWDbrpeai',
    'primary_key' => 'slug',
    'cache_ttl' => 86400,
    'required' => ['slug'],
    'fields' => [
        // Metadata
        'slug' => 'Slug',
        'h1' => 'H1',
        'seo_title' => 'SEO Title',
        'seo_description' => 'SEO Description',
        'og_image_url' => 'OG Image URL',
        'published' => ['source' => 'Published', 'type' => 'bool'],
        'country_ids' => ['source' => 'Country', 'type' => 'link', 'entity' => 'core_countries'],
        'division_ids' => ['source' => 'Division', 'type' => 'link', 'entity' => 'core_divisions'],

        // Hero
        'show_hero' => ['source' => 'Show Hero', 'type' => 'bool'],
        'hero_eyebrow' => 'Hero Eyebrow',
        'hero_h1_override' => 'Hero H1 Override',
        'hero_subtitle' => 'Hero Subtitle',
        'hero_image_url' => ['source' => 'Hero Image', 'type' => 'attachment'],
        'hero_cta_text' => 'Hero CTA Text',
        'hero_cta_url' => 'Hero CTA URL',
        'hero_theme' => 'Hero Theme',

        // Text-image A
        'show_text_image_a' => ['source' => 'Show Text Image A', 'type' => 'bool'],
        'text_image_a_h2' => 'Text Image A H2',
        'text_image_a_body' => 'Text Image A Body',
        'text_image_a_image_url' => ['source' => 'Text Image A Image', 'type' => 'attachment'],
        'text_image_a_reversed' => ['source' => 'Text Image A Reversed', 'type' => 'bool'],
        'text_image_a_theme' => 'Text Image A Theme',

        // Text-image B
        'show_text_image_b' => ['source' => 'Show Text Image B', 'type' => 'bool'],
        'text_image_b_h2' => 'Text Image B H2',
        'text_image_b_body' => 'Text Image B Body',
        'text_image_b_image_url' => ['source' => 'Text Image B Image', 'type' => 'attachment'],
        'text_image_b_reversed' => ['source' => 'Text Image B Reversed', 'type' => 'bool'],
        'text_image_b_theme' => 'Text Image B Theme',

        // Text-only
        'show_text_only' => ['source' => 'Show Text Only', 'type' => 'bool'],
        'text_only_h2' => 'Text Only H2',
        'text_only_body' => 'Text Only Body',
        'text_only_align' => 'Text Only Align',

        // FAQ
        'show_faq' => ['source' => 'Show FAQ', 'type' => 'bool'],
        'faq_h2' => 'FAQ H2',
        'faq_items' => ['source' => 'FAQ Items', 'type' => 'lines'],

        // Team Grid (SSOT)
        'show_team_grid' => ['source' => 'Show Team Grid', 'type' => 'bool'],
        'team_grid_h2' => 'Team Grid H2',
        'team_grid_scope_division' => 'Team Grid Scope Division',
        'team_grid_scope_country' => 'Team Grid Scope Country',
        'team_grid_limit' => ['source' => 'Team Grid Limit', 'type' => 'int'],

        // Partners Marquee (SSOT)
        'show_partners_marquee' => ['source' => 'Show Partners Marquee', 'type' => 'bool'],
        'partners_marquee_h2' => 'Partners Marquee H2',
        'partners_marquee_scope_division' => 'Partners Marquee Scope Division',
        'partners_marquee_scope_country' => 'Partners Marquee Scope Country',

        // Testimonial Card (SSOT)
        'show_testimonial_card' => ['source' => 'Show Testimonial Card', 'type' => 'bool'],
        'testimonial_scope_customer_type' => 'Testimonial Scope Customer Type',
        'testimonial_scope_division' => 'Testimonial Scope Division',
        'testimonial_scope_country' => 'Testimonial Scope Country',

        // CTA Banner
        'show_cta_banner' => ['source' => 'Show CTA Banner', 'type' => 'bool'],
        'cta_banner_h2' => 'CTA Banner H2',
        'cta_banner_body' => 'CTA Banner Body',
        'cta_banner_cta_text' => 'CTA Banner CTA Text',
        'cta_banner_cta_url' => 'CTA Banner CTA URL',
        'cta_banner_theme' => 'CTA Banner Theme',

        // Contact Form
        'show_contact_form' => ['source' => 'Show Contact Form', 'type' => 'bool'],
        'contact_form_eyebrow' => 'Contact Form Eyebrow',
        'contact_form_title' => 'Contact Form Title',
        'contact_form_subtitle' => 'Contact Form Subtitle',
        'contact_form_layout' => 'Contact Form Layout',
        'contact_form_theme' => 'Contact Form Theme',
        'contact_form_show_company' => ['source' => 'Contact Form Show Company', 'type' => 'bool'],
        'contact_form_show_phone' => ['source' => 'Contact Form Show Phone', 'type' => 'bool'],
        'contact_form_show_dropdown' => ['source' => 'Contact Form Show Dropdown', 'type' => 'bool'],
        'contact_form_dropdown_label' => 'Contact Form Dropdown Label',
        'contact_form_options' => ['source' => 'Contact Form Options', 'type' => 'lines'],
        'contact_form_cta_text' => 'Contact Form CTA Text',
        'contact_form_message_label' => 'Contact Form Message Label',
        'contact_form_trust_signals' => ['source' => 'Contact Form Trust Signals', 'type' => 'lines'],
        'contact_form_show_contact_person' => ['source' => 'Contact Form Show Contact Person', 'type' => 'bool'],
    ],
];
