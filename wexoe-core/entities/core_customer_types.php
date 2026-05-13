<?php
/**
 * Entity schema: core_customer_types
 *
 * SSOT — Kundtyper (taxonomi).
 * Airtable-tabell: core_customer_types (tblLsYRMZz6JA6GBK) i Wexoe NY.
 *
 * OBS: Inte att förväxla med audience_heroes (Customer types-tabellen i Wexoe-basen)
 * som håller publika audience-sidor.
 */

if (!defined('ABSPATH')) exit;

return [
    'base_id' => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    'table_id' => 'tblLsYRMZz6JA6GBK',
    'primary_key' => 'slug',
    'cache_ttl' => 3600,
    'required' => ['name', 'slug'],
    'fields' => [
        'name' => 'Name',
        'slug' => 'Slug',
        'description' => 'Description',
        'icon' => ['source' => 'Icon', 'type' => 'attachment'],
        'order' => ['source' => 'Order', 'type' => 'int'],
        'active' => ['source' => 'Active', 'type' => 'bool'],
    ],
];
