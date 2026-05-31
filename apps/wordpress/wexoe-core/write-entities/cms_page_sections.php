<?php
/**
 * Write-entity schema: cms_page_sections
 *
 * Speglar entities/cms_page_sections.php för WRITE-vägen (builder).
 * Polymorfa section-records — buildern sätter section_type först och tömmer
 * fält som inte hör till aktuell typ (stale-clearing). Alla typ-prefixade
 * fält är skrivbara här eftersom buildern kan växla mellan typer.
 *
 * `bullets`-, `items`- och `lines`-fält tas emot som strängar (multi-line text);
 * läs-schemat normaliserar dem till arrays. Vi gör ingen ytterligare
 * type-konvertering på skrivvägen — buildern skickar text som-är.
 */

if (!defined('ABSPATH')) exit;

return [
    'table_id' => 'tblWDvAe3s45P2Nok',
    'base_id'  => \Wexoe\Core\Plugin::SSOT_BASE_ID,

    'field_types' => [
        'page_ids'                  => 'link',
        'nts_news_manual_ids'       => 'link',
        'cg_case_manual_ids'        => 'link',
        'ng_article_manual_ids'     => 'link',
        'tabs_tab_ids'              => 'link',
        'tg_coworker_manual_ids'    => 'link',
        'pl_partner_manual_ids'     => 'link',
        't_testimonial_manual_ids'  => 'link',
    ],

    'fields' => [
        // Universal
        'internal_label' => 'internal_label',
        'internal_notes' => 'internal_notes',
        'is_active' => 'is_active',
        'order' => 'order',
        'section_type' => 'section_type',
        'anchor_id' => 'anchor_id',
        'layout' => 'layout',
        'theme' => 'theme',
        'top_padding' => 'top_padding',
        'bottom_padding' => 'bottom_padding',
        'page_ids' => 'page_ids',

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
        'ti_bullets' => 'ti_bullets',
        'ti_image_url' => 'ti_image_url',
        'ti_image_alt' => 'ti_image_alt',
        'ti_reversed' => 'ti_reversed',
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
        'cds_use_company_singleton' => 'cds_use_company_singleton',
        'cds_country_code' => 'cds_country_code',
        'cds_items' => 'cds_items',

        // news_text_split
        'nts_eyebrow' => 'nts_eyebrow',
        'nts_h2' => 'nts_h2',
        'nts_body' => 'nts_body',
        'nts_cta_text' => 'nts_cta_text',
        'nts_cta_url' => 'nts_cta_url',
        'nts_news_manual_ids' => 'nts_news_manual_ids',
        'nts_scope_division' => 'nts_scope_division',
        'nts_scope_country' => 'nts_scope_country',
        'nts_limit' => 'nts_limit',

        // case_grid
        'cg_eyebrow' => 'cg_eyebrow',
        'cg_h2' => 'cg_h2',
        'cg_body' => 'cg_body',
        'cg_case_manual_ids' => 'cg_case_manual_ids',
        'cg_scope_country' => 'cg_scope_country',
        'cg_scope_division' => 'cg_scope_division',
        'cg_scope_customer_type' => 'cg_scope_customer_type',
        'cg_limit' => 'cg_limit',
        'cg_columns' => 'cg_columns',

        // news_grid
        'ng_eyebrow' => 'ng_eyebrow',
        'ng_h2' => 'ng_h2',
        'ng_article_manual_ids' => 'ng_article_manual_ids',
        'ng_scope_country' => 'ng_scope_country',
        'ng_scope_division' => 'ng_scope_division',
        'ng_scope_topic' => 'ng_scope_topic',
        'ng_limit' => 'ng_limit',
        'ng_columns' => 'ng_columns',

        // catalog
        'cat_eyebrow' => 'cat_eyebrow',
        'cat_h2' => 'cat_h2',
        'cat_intro_body' => 'cat_intro_body',
        'cat_include_products' => 'cat_include_products',
        'cat_include_articles' => 'cat_include_articles',
        'cat_scope_division' => 'cat_scope_division',
        'cat_scope_country' => 'cat_scope_country',
        'cat_facet_fields' => 'cat_facet_fields',
        'cat_placeholder' => 'cat_placeholder',
        'cat_empty_text' => 'cat_empty_text',

        // tabs
        'tabs_eyebrow' => 'tabs_eyebrow',
        'tabs_h2' => 'tabs_h2',
        'tabs_intro_body' => 'tabs_intro_body',
        'tabs_tab_ids' => 'tabs_tab_ids',

        // team_grid
        'tg_eyebrow' => 'tg_eyebrow',
        'tg_h2' => 'tg_h2',
        'tg_body' => 'tg_body',
        'tg_variant' => 'tg_variant',
        'tg_coworker_manual_ids' => 'tg_coworker_manual_ids',
        'tg_scope_country' => 'tg_scope_country',
        'tg_scope_division' => 'tg_scope_division',
        'tg_limit' => 'tg_limit',

        // partner_list
        'pl_eyebrow' => 'pl_eyebrow',
        'pl_h2' => 'pl_h2',
        'pl_body' => 'pl_body',
        'pl_variant' => 'pl_variant',
        'pl_partner_manual_ids' => 'pl_partner_manual_ids',
        'pl_scope_division' => 'pl_scope_division',
        'pl_scope_country' => 'pl_scope_country',
        'pl_limit' => 'pl_limit',

        // faq
        'faq_eyebrow' => 'faq_eyebrow',
        'faq_h2' => 'faq_h2',
        'faq_body' => 'faq_body',
        'faq_items' => 'faq_items',

        // testimonial
        't_eyebrow' => 't_eyebrow',
        't_quote' => 't_quote',
        't_author_name' => 't_author_name',
        't_author_title' => 't_author_title',
        't_author_image_url' => 't_author_image_url',
        't_testimonial_manual_ids' => 't_testimonial_manual_ids',
        't_scope_country' => 't_scope_country',
        't_scope_division' => 't_scope_division',
        't_scope_customer_type' => 't_scope_customer_type',
        't_featured_only' => 't_featured_only',

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
        'cf_show_company' => 'cf_show_company',
        'cf_show_phone' => 'cf_show_phone',
        'cf_show_dropdown' => 'cf_show_dropdown',
        'cf_dropdown_label' => 'cf_dropdown_label',
        'cf_options' => 'cf_options',
        'cf_cta_text' => 'cf_cta_text',
        'cf_message_label' => 'cf_message_label',
        'cf_trust_signals' => 'cf_trust_signals',
        'cf_show_contact_person' => 'cf_show_contact_person',
        'cf_contact_scope_country' => 'cf_contact_scope_country',
        'cf_contact_scope_division' => 'cf_contact_scope_division',
    ],
];
