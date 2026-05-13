<?php
/**
 * Entity schema: audience_heroes
 *
 * Dynamic hero + value-proposition sections for audience landing pages.
 * Airtable-tabell: Audience Heroes (tblvNf1CqAYEFvTpu) i base appXoUcK68dQwASjF.
 *
 * Primärnyckel: 'slug' — matchar [wexoe_audience slug="..."] shortcodes.
 * 'active' är en checkbox-flagga; feature-pluginet filtrerar själv.
 */

if (!defined('ABSPATH')) exit;

return [
    'table_id' => 'tblvNf1CqAYEFvTpu',
    'primary_key' => 'slug',
    'cache_ttl' => 86400,
    'required' => ['slug'],
    'fields' => [
        // Core
        'slug' => 'Slug',
        'active' => ['source' => 'Active', 'type' => 'bool'],

        // Hero
        'eyebrow' => 'Eyebrow',
        'title' => 'Title',
        'description' => 'Description',
        'cta_text' => 'CTA Text',
        'cta_url' => 'CTA URL',
        'hero_image' => 'Hero Image',
        'stat_number' => ['source' => 'Stat Number', 'type' => 'int'],
        'stat_label' => 'Stat Label',

        // Value proposition
        'value_h2' => 'Value H2',
        'value_text_1' => 'Value Text 1',
        'value_text_2' => 'Value Text 2',
        'benefit_1' => 'Benefit 1',
        'benefit_2' => 'Benefit 2',
        'benefit_3' => 'Benefit 3',

        // Case card
        'case_title' => 'Case Title',
        'case_description' => 'Case Description',
        'case_result' => 'Case Result',
        'case_link_text' => 'Case Link Text',
        'case_link_url' => 'Case Link URL',

        // Contact Form (delad med ContactForm-renderer)
        'contact_form_show' => ['source' => 'Show Contact Form', 'type' => 'bool'],
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
