<?php
/**
 * Entity schema: core_countries
 *
 * SSOT — Länder (taxonomi).
 * Airtable-tabell: core_countries (tblCZ082jWGUBrUAK) i Wexoe NY.
 *
 * Land-kontext härleds av `Wexoe\Core\Helpers\Context` baserat på Domain → URL Prefix.
 */

if (!defined('ABSPATH')) exit;

return [
    'base_id' => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    'table_id' => 'tblCZ082jWGUBrUAK',
    'primary_key' => 'code',
    'cache_ttl' => 3600,
    'required' => ['name', 'code'],
    'fields' => [
        'name' => 'Name',
        'code' => 'Code',
        'domain' => 'Domain',
        'url_prefix' => 'URL Prefix',
        'currency' => 'Currency',
        'locale' => 'Locale',
        'default_language' => 'Default Language',
        'order' => ['source' => 'Order', 'type' => 'int'],
        'active' => ['source' => 'Active', 'type' => 'bool'],
    ],
];
