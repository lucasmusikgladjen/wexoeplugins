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
        'name' => 'name',
        'internal_notes' => 'internal_notes',
        'logo_url' => 'logo_url',
        'logo_transparent_url' => 'logo_transparent_url',
        'url' => 'url',
        'description' => 'description',
        'order' => ['source' => 'order', 'type' => 'float'],
        'is_active' => ['source' => 'is_active', 'type' => 'bool'],
        'division_ids' => ['source' => 'division_ids', 'type' => 'link', 'entity' => 'core_divisions'],
        'country_ids' => ['source' => 'country_ids', 'type' => 'link', 'entity' => 'core_countries'],
    ],
];
