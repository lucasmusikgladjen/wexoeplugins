<?php
/**
 * Entity schema: customer_type_pages
 *
 * Audience/kundtyp-LP — en publik sida per målgrupp (installatör, OEM,
 * panelbyggare etc.). Tidigare hette entiteten `audience_heroes` och pekade
 * på gamla Wexoe-basens `Customer types`-tabell.
 *
 * Airtable-tabell: cms_customer_type_pages (tblZufoWVNKPuJdMK) i Wexoe NY.
 * Primärnyckel: 'slug' — matchar [wexoe_customer_type slug="..."] shortcoden.
 *
 * Konvention: snake_case överallt — passthrough.
 *
 * Cases visas i strippen via `case_ids`-länken till `cms_cases`. Renderaren
 * hämtar varje case via `Core::entity('cms_cases')->find_by_ids($p['case_ids'])`
 * och läser bara `card_*`-fält + `slug`/`legacy_external_url` för länken.
 */

if (!defined('ABSPATH')) exit;

return [
    'base_id' => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    'table_id' => 'tblZufoWVNKPuJdMK',
    'primary_key' => 'slug',
    'cache_ttl' => 86400,
    'required' => ['slug'],
    'fields' => [
        // Core
        'slug' => 'slug',
        'internal_notes' => 'internal_notes',
        'is_active' => ['source' => 'is_active', 'type' => 'bool'],
        'country_ids' => ['source' => 'country_ids', 'type' => 'link', 'entity' => 'core_countries'],
        'customer_type_ids' => ['source' => 'customer_type_ids', 'type' => 'link', 'entity' => 'core_customer_types'],

        // Hero
        'name' => 'name',
        'eyebrow' => 'eyebrow',
        'title' => 'title',
        'description' => 'description',
        'cta_text' => 'cta_text',
        'cta_url' => 'cta_url',
        'hero_image_url' => 'hero_image_url',
        'stat_number' => ['source' => 'stat_number', 'type' => 'int'],
        'stat_label' => 'stat_label',

        // Value proposition
        'value_h2' => 'value_h2',
        'value_text_1' => 'value_text_1',
        'value_text_2' => 'value_text_2',
        'benefit_1' => 'benefit_1',
        'benefit_2' => 'benefit_2',
        'benefit_3' => 'benefit_3',

        // Cases — länk till cms_cases (kanonisk case-entitet)
        'case_ids' => ['source' => 'case_ids', 'type' => 'link', 'entity' => 'cms_cases'],

        // Contact form
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
