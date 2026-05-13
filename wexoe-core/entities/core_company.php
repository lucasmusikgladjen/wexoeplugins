<?php
/**
 * Entity schema: core_company
 *
 * SSOT — Global företagsinformation (singleton per land + Is Default-fallback).
 * Airtable-tabell: core_company (tblwq9y74ertsNyYG) i Wexoe NY-basen.
 *
 * Singleton-mönster: ett record per land med `Is Default = true` på det record
 * som ska användas när inget land-kontext finns. Wexoe Core slår upp via
 * `Wexoe\Core\Helpers\Singletons::company_for_country($code)`.
 */

if (!defined('ABSPATH')) exit;

return [
    'base_id' => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    'table_id' => 'tblwq9y74ertsNyYG',
    'primary_key' => 'slug',
    'cache_ttl' => 3600,
    'required' => ['slug'],
    'fields' => [
        'slug' => 'Slug',
        'is_default' => ['source' => 'Is Default', 'type' => 'bool'],
        'country_ids' => ['source' => 'Country', 'type' => 'link', 'entity' => 'core_countries'],
        'company_name' => 'Company Name',
        'tagline' => 'Tagline',
        'org_number' => 'Org Number',
        'vat_number' => 'VAT Number',
        'email' => 'Email',
        'phone' => 'Phone',
        'phone_emergency' => 'Phone Emergency',
        'address_line_1' => 'Address Line 1',
        'address_postal_code' => 'Address Postal Code',
        'address_city' => 'Address City',
        'linkedin_url' => 'LinkedIn URL',
        'facebook_url' => 'Facebook URL',
        'instagram_url' => 'Instagram URL',
        'youtube_url' => 'YouTube URL',
        'hours_mon_fri' => 'Hours Mon-Fri',
        'hours_saturday' => 'Hours Saturday',
        'hours_sunday' => 'Hours Sunday',
        'hours_lunch' => 'Hours Lunch',
        'hours_override' => ['source' => 'Hours Override', 'type' => 'lines'],
        'internal_notes' => 'Internal Notes',
    ],
];
