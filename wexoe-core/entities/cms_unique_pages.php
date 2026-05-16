<?php
/**
 * Entity schema: cms_unique_pages — @deprecated 2026-05
 *
 * UTFASAS. Ersatts av `cms_pages` + `cms_page_sections` (se motsv. filer i
 * denna mapp). Den nya wexoe-pages-pluginen (v1.0.0+) läser ENBART från
 * `cms_pages` — denna entitet finns kvar för att inte bryta:
 *   - wexoe-core/src/EntityRestApi.php whitelist (om någon builder-route
 *     fortfarande POST/PATCH:ar mot cms_unique_pages)
 *   - wexoe-core/write-entities/cms_unique_pages.php
 *   - Eventuella admin/cache-tools som listar entitetsnamn
 *
 * Migrationsväg: bygg om sidor i wexoebuilder mot cms_pages och radera när
 * den gamla tabellen är tom. Airtable-tabellen `cms_unique_pages`
 * (tblpAM1wZWDbrpeai) lever kvar tills migrationen är klar.
 *
 * INGA NYA SIDOR ska skrivas mot denna tabell.
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
        'slug' => 'slug',
        'internal_notes' => 'internal_notes',
        'h1' => 'h1',
        'seo_title' => 'seo_title',
        'seo_description' => 'seo_description',
        'og_image_url' => 'og_image_url',
        'is_published' => ['source' => 'is_published', 'type' => 'bool'],
        'country_ids' => ['source' => 'country_ids', 'type' => 'link', 'entity' => 'core_countries'],
        'division_ids' => ['source' => 'division_ids', 'type' => 'link', 'entity' => 'core_divisions'],

        // Hero
        'show_hero' => ['source' => 'show_hero', 'type' => 'bool'],
        'hero_eyebrow' => 'hero_eyebrow',
        'hero_h1_override' => 'hero_h1_override',
        'hero_subtitle' => 'hero_subtitle',
        'hero_image_url' => 'hero_image_url',
        'hero_cta_text' => 'hero_cta_text',
        'hero_cta_url' => 'hero_cta_url',
        'hero_theme' => 'hero_theme',

        // Text-image A
        'show_text_image_a' => ['source' => 'show_text_image_a', 'type' => 'bool'],
        'text_image_a_h2' => 'text_image_a_h2',
        'text_image_a_body' => 'text_image_a_body',
        'text_image_a_image_url' => 'text_image_a_image_url',
        'text_image_a_reversed' => ['source' => 'text_image_a_reversed', 'type' => 'bool'],
        'text_image_a_theme' => 'text_image_a_theme',

        // Text-image B
        'show_text_image_b' => ['source' => 'show_text_image_b', 'type' => 'bool'],
        'text_image_b_h2' => 'text_image_b_h2',
        'text_image_b_body' => 'text_image_b_body',
        'text_image_b_image_url' => 'text_image_b_image_url',
        'text_image_b_reversed' => ['source' => 'text_image_b_reversed', 'type' => 'bool'],
        'text_image_b_theme' => 'text_image_b_theme',

        // Text-only
        'show_text_only' => ['source' => 'show_text_only', 'type' => 'bool'],
        'text_only_h2' => 'text_only_h2',
        'text_only_body' => 'text_only_body',
        'text_only_align' => 'text_only_align',

        // FAQ
        'show_faq' => ['source' => 'show_faq', 'type' => 'bool'],
        'faq_h2' => 'faq_h2',
        'faq_items' => ['source' => 'faq_items', 'type' => 'lines'],

        // Team Grid (SSOT)
        'show_team_grid' => ['source' => 'show_team_grid', 'type' => 'bool'],
        'team_grid_h2' => 'team_grid_h2',
        'team_grid_scope_division' => 'team_grid_scope_division',
        'team_grid_scope_country' => 'team_grid_scope_country',
        'team_grid_limit' => ['source' => 'team_grid_limit', 'type' => 'int'],

        // Partners Marquee (SSOT)
        'show_partners_marquee' => ['source' => 'show_partners_marquee', 'type' => 'bool'],
        'partners_marquee_h2' => 'partners_marquee_h2',
        'partners_marquee_scope_division' => 'partners_marquee_scope_division',
        'partners_marquee_scope_country' => 'partners_marquee_scope_country',

        // Testimonial Card (SSOT)
        'show_testimonial_card' => ['source' => 'show_testimonial_card', 'type' => 'bool'],
        'testimonial_scope_customer_type' => 'testimonial_scope_customer_type',
        'testimonial_scope_division' => 'testimonial_scope_division',
        'testimonial_scope_country' => 'testimonial_scope_country',

        // CTA Banner
        'show_cta_banner' => ['source' => 'show_cta_banner', 'type' => 'bool'],
        'cta_banner_h2' => 'cta_banner_h2',
        'cta_banner_body' => 'cta_banner_body',
        'cta_banner_cta_text' => 'cta_banner_cta_text',
        'cta_banner_cta_url' => 'cta_banner_cta_url',
        'cta_banner_theme' => 'cta_banner_theme',

        // Contact Form
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
