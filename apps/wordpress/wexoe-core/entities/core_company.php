<?php
/**
 * Entity schema: core_company
 *
 * SSOT — Global företagsinformation (ett record per land + default).
 * Airtable-tabell: core_company (tblwq9y74ertsNyYG) i Wexoe NY.
 *
 * Slå upp via `Wexoe\Core\Helpers\Singletons::company_for_country($code)` som
 * faller tillbaka på `is_default = true`-recordet.
 *
 * Konvention: snake_case överallt — Airtable display-namn matchar kod-fältnamn.
 */

if (!defined('ABSPATH')) exit;

return [
    'base_id' => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    'table_id' => 'tblwq9y74ertsNyYG',
    'primary_key' => 'slug',
    'cache_ttl' => 3600,
    'required' => ['slug'],
    'fields' => [
        'slug' => 'slug',
        'internal_notes' => 'internal_notes',
        'is_default' => ['source' => 'is_default', 'type' => 'bool'],
        'country_ids' => ['source' => 'country_ids', 'type' => 'link', 'entity' => 'core_countries'],
        'company_name' => 'company_name',
        'tagline' => 'tagline',
        'org_number' => 'org_number',
        'vat_number' => 'vat_number',
        'email' => 'email',
        'email_order' => 'email_order',
        'phone' => 'phone',
        'phone_emergency' => 'phone_emergency',
        'address_line_1' => 'address_line_1',
        'address_postal_code' => 'address_postal_code',
        'address_city' => 'address_city',
        'linkedin_url' => 'linkedin_url',
        'facebook_url' => 'facebook_url',
        'instagram_url' => 'instagram_url',
        'youtube_url' => 'youtube_url',
        'hours_mon_thur' => 'hours_mon_thur',
        'hours_friday' => 'hours_friday',
    ],
];
