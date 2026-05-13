<?php
/**
 * Entity schema: core_partners
 *
 * SSOT — Samarbetspartners (collection).
 * Airtable-tabell: core_partners (tblZ5YIYFelxA0nBm) i Wexoe NY.
 */

if (!defined('ABSPATH')) exit;

return [
    'base_id' => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    'table_id' => 'tblZ5YIYFelxA0nBm',
    'primary_key' => 'name',
    'cache_ttl' => 3600,
    'required' => ['name'],
    'fields' => [
        'name' => 'Name',
        'logo' => ['source' => 'Logo', 'type' => 'attachment'],
        'logo_transparent' => ['source' => 'Logo Transparent', 'type' => 'attachment'],
        'url' => 'URL',
        'description' => 'Description',
        'order' => ['source' => 'Order', 'type' => 'float'],
        'active' => ['source' => 'Active', 'type' => 'bool'],
        'division_ids' => ['source' => 'Division', 'type' => 'link', 'entity' => 'core_divisions'],
        'country_ids' => ['source' => 'Country', 'type' => 'link', 'entity' => 'core_countries'],
    ],
];
