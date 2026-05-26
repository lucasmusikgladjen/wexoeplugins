<?php
/**
 * Entity schema: partner_pages
 *
 * Publika leverantörssidor — en sida per leverantör (Rockwell, HMS, Wittenstein, …).
 * Airtable-tabell: cms_partner_pages (tblQv5E8pSgwxy6wU) i Wexoe NY.
 *
 * Primärnyckel: 'slug' — matchar [wexoe_partner slug="..."] shortcode (FAS 2).
 *
 * Identitet (name, logo_url, url) läses via partner_ids-länken till core_partners.
 * Sidan duplicerar inte stamdata — bara publik content och SEO.
 *
 * Sektioner mappar 1:1 mot prototype_supplier_page.html:
 *   hero · quick_facts (pseudo_array count:4) · about · why_wexoe · success_cases
 *   (länk → cms_cases) · categories (länk → cms_product_pages) · faq · contact_person
 *   · contact_form (shared)
 *
 * Konvention: snake_case överallt — passthrough.
 */

if (!defined('ABSPATH')) exit;

return [
    'base_id' => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    'table_id' => 'tblQv5E8pSgwxy6wU',
    'primary_key' => 'slug',
    'cache_ttl' => 86400,
    'required' => ['slug'],
    'fields' => [
        // Core identifiers
        'slug' => 'slug',
        'internal_notes' => 'internal_notes',
        'is_active' => ['source' => 'is_active', 'type' => 'bool'],
        'country_ids' => ['source' => 'country_ids', 'type' => 'link', 'entity' => 'core_countries'],
        'partner_ids' => ['source' => 'partner_ids', 'type' => 'link', 'entity' => 'core_partners'],

        // SEO
        'seo_title' => 'seo_title',
        'seo_description' => 'seo_description',
        'og_image_url' => 'og_image_url',

        // Hero — supplier-lockup (logo läses från core_partners.logo_url i feature-pluginet)
        'hero_eyebrow' => 'hero_eyebrow',
        'h1' => 'h1',
        'hero_tagline' => 'hero_tagline',
        'hero_cta_text' => 'hero_cta_text',
        'hero_cta_url' => 'hero_cta_url',
        'hero_cta2_text' => 'hero_cta2_text',
        'hero_cta2_url' => 'hero_cta2_url',
        'hero_image_url' => 'hero_image_url',

        // Quick facts — pseudo_array count:4 (samma mönster som Normal N H2 på landing_pages)
        'show_quick_facts' => ['source' => 'show_quick_facts', 'type' => 'bool'],
        'facts_1_icon' => 'facts_1_icon',
        'facts_1_value' => 'facts_1_value',
        'facts_1_label' => 'facts_1_label',
        'facts_2_icon' => 'facts_2_icon',
        'facts_2_value' => 'facts_2_value',
        'facts_2_label' => 'facts_2_label',
        'facts_3_icon' => 'facts_3_icon',
        'facts_3_value' => 'facts_3_value',
        'facts_3_label' => 'facts_3_label',
        'facts_4_icon' => 'facts_4_icon',
        'facts_4_value' => 'facts_4_value',
        'facts_4_label' => 'facts_4_label',

        // About
        'show_about' => ['source' => 'show_about', 'type' => 'bool'],
        'about_eyebrow' => 'about_eyebrow',
        'about_h2' => 'about_h2',
        'about_text' => 'about_text',
        'about_image_url' => 'about_image_url',
        'about_badge_value' => 'about_badge_value',
        'about_badge_label' => 'about_badge_label',

        // Why Wexoe + [Brand]
        'show_why_wexoe' => ['source' => 'show_why_wexoe', 'type' => 'bool'],
        'why_h2' => 'why_h2',
        'why_text' => 'why_text',
        'why_benefits' => ['source' => 'why_benefits', 'type' => 'lines'],

        // Success cases — länk till cms_cases. Feature-pluginet renderar max 3.
        'case_ids' => ['source' => 'case_ids', 'type' => 'link', 'entity' => 'cases'],
        'cases_view_all_text' => 'cases_view_all_text',
        'cases_view_all_url' => 'cases_view_all_url',

        // Categories — länk till cms_product_pages (entity: product_areas).
        // Korten läser card_image_url + card_description från linked record.
        'show_categories' => ['source' => 'show_categories', 'type' => 'bool'],
        'categories_eyebrow' => 'categories_eyebrow',
        'categories_h2' => 'categories_h2',
        'categories_intro' => 'categories_intro',
        'category_ids' => ['source' => 'category_ids', 'type' => 'link', 'entity' => 'product_areas'],

        // FAQ — faqs lagras som JSON-string (parsas av feature-pluginet i FAS 2).
        // Samma mönster som faq_items på lp_tabs.
        'show_faq' => ['source' => 'show_faq', 'type' => 'bool'],
        'faq_h2' => 'faq_h2',
        'faqs' => 'faqs',

        // Contact person — fullbredds navy strip
        'show_contact_person' => ['source' => 'show_contact_person', 'type' => 'bool'],
        'contact_name' => 'contact_name',
        'contact_title' => 'contact_title',
        'contact_email' => 'contact_email',
        'contact_phone' => 'contact_phone',
        'contact_image_url' => 'contact_image_url',
        'contact_quote' => 'contact_quote',

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
