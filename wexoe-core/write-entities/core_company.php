<?php
/**
 * Write-entity schema: core_company
 *
 * Domain-key → Airtable field name. Speglar entities/core_company.php
 * men för WRITE-vägen (builder /globals/company).
 */

if (!defined('ABSPATH')) exit;

return [
    'table_id' => 'tblwq9y74ertsNyYG',
    'base_id'  => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    'field_types' => [
        'country_ids' => 'link',
    ],
    'fields' => [
        'slug' => 'Slug',
        'is_default' => 'Is Default',
        'country_ids' => 'Country',
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
        'hours_override' => 'Hours Override',
        'internal_notes' => 'Internal Notes',
    ],
];
