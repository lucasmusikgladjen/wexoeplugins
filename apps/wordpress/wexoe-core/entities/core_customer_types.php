<?php
/**
 * Entity schema: core_customer_types
 *
 * SSOT — Kundtyper (taxonomi).
 * Airtable-tabell: core_customer_types (tblLsYRMZz6JA6GBK) i Wexoe NY.
 *
 * OBS: Inte att förväxla med `audience_heroes` (cms_customer_type_pages-tabellen
 * i Wexoe NY) som håller publika audience-sidor.
 */

if (!defined('ABSPATH')) exit;

return [
    'base_id' => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    'table_id' => 'tblLsYRMZz6JA6GBK',
    'primary_key' => 'slug',
    'cache_ttl' => 3600,
    'required' => ['name', 'slug'],
    'fields' => [
        'name' => 'name',
        'internal_notes' => 'internal_notes',
        'slug' => 'slug',
        'description' => 'description',
        'icon' => 'icon',
        'order' => ['source' => 'order', 'type' => 'int'],
        'is_active' => ['source' => 'is_active', 'type' => 'bool'],
    ],
];
