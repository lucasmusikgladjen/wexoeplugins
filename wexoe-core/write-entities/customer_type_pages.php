<?php
/**
 * Write-entity schema: customer_type_pages
 *
 * Domain-key → Airtable field name. Speglar entities/customer_type_pages.php
 * men för WRITE-vägen (builder /editor/customer-type).
 *
 * Konvention: snake_case överallt — passthrough.
 */

if (!defined('ABSPATH')) exit;

return [
    'table_id' => 'tblZufoWVNKPuJdMK',
    'base_id'  => \Wexoe\Core\Plugin::SSOT_BASE_ID,

    'field_types' => [
        'country_ids' => 'link',
        'customer_type_ids' => 'link',
        'case_ids' => 'link',
    ],

    'fields' => [
        'slug' => 'slug',
        'internal_notes' => 'internal_notes',
        'is_active' => 'is_active',
        'country_ids' => 'country_ids',
        'customer_type_ids' => 'customer_type_ids',

        'name' => 'name',
        'eyebrow' => 'eyebrow',
        'title' => 'title',
        'description' => 'description',
        'cta_text' => 'cta_text',
        'cta_url' => 'cta_url',
        'hero_image_url' => 'hero_image_url',
        'stat_number' => 'stat_number',
        'stat_label' => 'stat_label',

        'value_h2' => 'value_h2',
        'value_text_1' => 'value_text_1',
        'value_text_2' => 'value_text_2',
        'benefit_1' => 'benefit_1',
        'benefit_2' => 'benefit_2',
        'benefit_3' => 'benefit_3',

        'case_ids' => 'case_ids',

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
