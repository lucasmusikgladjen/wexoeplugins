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
        'name' => 'name',
        'internal_notes' => 'internal_notes',
        'code' => 'code',
        'domain' => 'domain',
        'url_prefix' => 'url_prefix',
        'currency' => 'currency',
        'locale' => 'locale',
        'default_language' => 'default_language',
        'order' => ['source' => 'order', 'type' => 'int'],
        'is_active' => ['source' => 'is_active', 'type' => 'bool'],
    ],
];
