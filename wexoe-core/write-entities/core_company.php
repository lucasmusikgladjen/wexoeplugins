<?php
/**
 * Write-entity schema: core_company
 *
 * Domain-key → Airtable field name. Speglar entities/core_company.php
 * men för WRITE-vägen (builder /globals/company).
 *
 * Post-migration: snake_case överallt — passthrough.
 */

if (!defined('ABSPATH')) exit;

return [
    'table_id' => 'tblwq9y74ertsNyYG',
    'base_id'  => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    'field_types' => [
        'country_ids' => 'link',
    ],
    'fields' => [
        'slug' => 'slug',
        'is_default' => 'is_default',
        'country_ids' => 'country_ids',
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
        'internal_notes' => 'internal_notes',
    ],
];
