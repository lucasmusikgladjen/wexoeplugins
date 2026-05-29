<?php
/**
 * Entity schema: cms_page_sections
 *
 * Polymorfa sektion-records för cms_pages.
 * Airtable-tabell: cms_page_sections (tblWDvAe3s45P2Nok) i Wexoe NY.
 *
 * `section_type` är diskriminator — branchar vilka prefixade fält som är
 * meningsfulla. Buildern ansvarar för att rensa fält när section_type ändras;
 * pluginet får anta att fält som inte hör till aktuell typ är tomma.
 *
 * Sektionstyper och deras prefix:
 *   hero                  hero_*
 *   text_image            ti_*
 *   text_only             to_*
 *   company_data_strip    cds_*
 *   news_text_split       nts_*
 *   case_grid             cg_*
 *   news_grid             ng_*
 *   catalog               cat_*
 *   tabs                  tabs_*  (+ länk till cms_section_tabs)
 *   team_grid             tg_*
 *   partner_list          pl_*
 *   faq                   faq_*
 *   testimonial           t_*
 *   cta_banner            cta_*
 *   contact_form          cf_*
 *
 * Manuella urval + scope: list-sektionerna (news_*, cg_*, ng_*, tg_*, pl_*)
 * stödjer pin-then-scope — manuellt länkade records visas först, scope-resultat
 * fyller på upp till `*_limit`.
 *
 * Konvention: snake_case överallt — passthrough.
 */

if (!defined('ABSPATH')) exit;

return [
    'base_id' => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    'table_id' => 'tblWDvAe3s45P2Nok',
    'primary_key' => 'internal_label',
    'cache_ttl' => 86400,
    'required' => ['internal_label', 'section_type'],
    'fields' => [
        // Universal
        'internal_label' => 'internal_label',
        'internal_notes' => 'internal_notes',
        'is_active' => ['source' => 'is_active', 'type' => 'bool'],
        'order' => ['source' => 'order', 'type' => 'float'],
        'section_type' => 'section_type',
        'anchor_id' => 'anchor_id',
        'layout' => 'layout',
        'background_color' => 'background_color',
        'top_padding' => 'top_padding',
        'bottom_padding' => 'bottom_padding',
        'page_ids' => ['source' => 'page_ids', 'type' => 'link', 'entity' => 'cms_pages'],

        // hero
        'hero_eyebrow' => 'hero_eyebrow',
        'hero_h1' => 'hero_h1',
        'hero_subtitle' => 'hero_subtitle',
        'hero_image_url' => 'hero_image_url',
        'hero_variant' => 'hero_variant',
        'hero_bg_image_url' => 'hero_bg_image_url',
        'hero_cta_text' => 'hero_cta_text',
        'hero_cta_url' => 'hero_cta_url',
        'hero_cta2_text' => 'hero_cta2_text',
        'hero_cta2_url' => 'hero_cta2_url',

        // text_image
        'ti_eyebrow' => 'ti_eyebrow',
        'ti_h2' => 'ti_h2',
        'ti_body' => 'ti_body',
        'ti_bullets' => ['source' => 'ti_bullets', 'type' => 'lines'],
        'ti_image_url' => 'ti_image_url',
        'ti_image_alt' => 'ti_image_alt',
        'ti_reversed' => ['source' => 'ti_reversed', 'type' => 'bool'],
        'ti_cta_text' => 'ti_cta_text',
        'ti_cta_url' => 'ti_cta_url',
        'ti_cta2_text' => 'ti_cta2_text',
        'ti_cta2_url' => 'ti_cta2_url',

        // text_only
        'to_eyebrow' => 'to_eyebrow',
        'to_h2' => 'to_h2',
        'to_body' => 'to_body',
        'to_align' => 'to_align',

        // company_data_strip
        'cds_h2' => 'cds_h2',
        'cds_body' => 'cds_body',
        'cds_use_company_singleton' => ['source' => 'cds_use_company_singleton', 'type' => 'bool'],
        'cds_country_code' => 'cds_country_code',
        'cds_items' => ['source' => 'cds_items', 'type' => 'lines'],

        // news_text_split
        'nts_eyebrow' => 'nts_eyebrow',
        'nts_h2' => 'nts_h2',
        'nts_body' => 'nts_body',
        'nts_cta_text' => 'nts_cta_text',
        'nts_cta_url' => 'nts_cta_url',
        'nts_news_manual_ids' => ['source' => 'nts_news_manual_ids', 'type' => 'link', 'entity' => 'articles'],
        'nts_scope_division' => 'nts_scope_division',
        'nts_scope_country' => 'nts_scope_country',
        'nts_limit' => ['source' => 'nts_limit', 'type' => 'int'],

        // case_grid
        'cg_eyebrow' => 'cg_eyebrow',
        'cg_h2' => 'cg_h2',
        'cg_body' => 'cg_body',
        'cg_case_manual_ids' => ['source' => 'cg_case_manual_ids', 'type' => 'link', 'entity' => 'cms_cases'],
        'cg_scope_country' => 'cg_scope_country',
        'cg_scope_division' => 'cg_scope_division',
        'cg_scope_customer_type' => 'cg_scope_customer_type',
        'cg_limit' => ['source' => 'cg_limit', 'type' => 'int'],
        'cg_columns' => 'cg_columns',
        'cg_cta_text' => 'cg_cta_text',
        'cg_cta_url' => 'cg_cta_url',

        // news_grid
        'ng_eyebrow' => 'ng_eyebrow',
        'ng_h2' => 'ng_h2',
        'ng_article_manual_ids' => ['source' => 'ng_article_manual_ids', 'type' => 'link', 'entity' => 'articles'],
        'ng_scope_country' => 'ng_scope_country',
        'ng_scope_division' => 'ng_scope_division',
        'ng_scope_topic' => 'ng_scope_topic',
        'ng_limit' => ['source' => 'ng_limit', 'type' => 'int'],
        'ng_columns' => 'ng_columns',

        // catalog
        'cat_eyebrow' => 'cat_eyebrow',
        'cat_h2' => 'cat_h2',
        'cat_intro_body' => 'cat_intro_body',
        'cat_include_products' => ['source' => 'cat_include_products', 'type' => 'bool'],
        'cat_include_articles' => ['source' => 'cat_include_articles', 'type' => 'bool'],
        'cat_scope_division' => 'cat_scope_division',
        'cat_scope_country' => 'cat_scope_country',
        'cat_facet_fields' => ['source' => 'cat_facet_fields', 'type' => 'lines'],
        'cat_placeholder' => 'cat_placeholder',
        'cat_empty_text' => 'cat_empty_text',

        // tabs
        'tabs_eyebrow' => 'tabs_eyebrow',
        'tabs_h2' => 'tabs_h2',
        'tabs_intro_body' => 'tabs_intro_body',
        'tabs_tab_ids' => ['source' => 'tabs_tab_ids', 'type' => 'link', 'entity' => 'cms_section_tabs'],

        // team_grid
        'tg_eyebrow' => 'tg_eyebrow',
        'tg_h2' => 'tg_h2',
        'tg_body' => 'tg_body',
        'tg_variant' => 'tg_variant',
        'tg_coworker_manual_ids' => ['source' => 'tg_coworker_manual_ids', 'type' => 'link', 'entity' => 'core_coworkers'],
        'tg_scope_country' => 'tg_scope_country',
        'tg_scope_division' => 'tg_scope_division',
        'tg_limit' => ['source' => 'tg_limit', 'type' => 'int'],

        // partner_list
        'pl_eyebrow' => 'pl_eyebrow',
        'pl_h2' => 'pl_h2',
        'pl_body' => 'pl_body',
        'pl_variant' => 'pl_variant',
        'pl_partner_manual_ids' => ['source' => 'pl_partner_manual_ids', 'type' => 'link', 'entity' => 'core_partners'],
        'pl_scope_division' => 'pl_scope_division',
        'pl_scope_country' => 'pl_scope_country',
        'pl_limit' => ['source' => 'pl_limit', 'type' => 'int'],

        // faq
        'faq_eyebrow' => 'faq_eyebrow',
        'faq_h2' => 'faq_h2',
        'faq_body' => 'faq_body',
        'faq_items' => ['source' => 'faq_items', 'type' => 'lines'],

        // testimonial
        't_eyebrow' => 't_eyebrow',
        't_quote' => 't_quote',
        't_author_name' => 't_author_name',
        't_author_title' => 't_author_title',
        't_author_image_url' => 't_author_image_url',
        't_testimonial_manual_ids' => ['source' => 't_testimonial_manual_ids', 'type' => 'link', 'entity' => 'core_testimonials'],
        't_scope_country' => 't_scope_country',
        't_scope_division' => 't_scope_division',
        't_scope_customer_type' => 't_scope_customer_type',
        't_featured_only' => ['source' => 't_featured_only', 'type' => 'bool'],

        // cta_banner
        'cta_eyebrow' => 'cta_eyebrow',
        'cta_h2' => 'cta_h2',
        'cta_body' => 'cta_body',
        'cta_cta_text' => 'cta_cta_text',
        'cta_cta_url' => 'cta_cta_url',
        'cta_cta2_text' => 'cta_cta2_text',
        'cta_cta2_url' => 'cta_cta2_url',
        'cta_image_url' => 'cta_image_url',

        // contact_form
        'cf_eyebrow' => 'cf_eyebrow',
        'cf_title' => 'cf_title',
        'cf_subtitle' => 'cf_subtitle',
        'cf_layout' => 'cf_layout',
        'cf_show_company' => ['source' => 'cf_show_company', 'type' => 'bool'],
        'cf_show_phone' => ['source' => 'cf_show_phone', 'type' => 'bool'],
        'cf_show_dropdown' => ['source' => 'cf_show_dropdown', 'type' => 'bool'],
        'cf_dropdown_label' => 'cf_dropdown_label',
        'cf_options' => ['source' => 'cf_options', 'type' => 'lines'],
        'cf_cta_text' => 'cf_cta_text',
        'cf_message_label' => 'cf_message_label',
        'cf_trust_signals' => ['source' => 'cf_trust_signals', 'type' => 'lines'],
        'cf_show_contact_person' => ['source' => 'cf_show_contact_person', 'type' => 'bool'],
        'cf_contact_scope_country' => 'cf_contact_scope_country',
        'cf_contact_scope_division' => 'cf_contact_scope_division',
    ],
];
