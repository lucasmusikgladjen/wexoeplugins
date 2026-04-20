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
    ],
];
