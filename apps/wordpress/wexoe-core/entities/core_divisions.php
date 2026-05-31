<?php
/**
 * Entity schema: core_divisions
 *
 * SSOT — Divisioner (taxonomi).
 * Airtable-tabell: core_divisions (tblyxs2zsoRBozxQS) i Wexoe NY.
 */

if (!defined('ABSPATH')) exit;

return [
    'base_id' => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    'table_id' => 'tblyxs2zsoRBozxQS',
    'primary_key' => 'slug',
    'cache_ttl' => 3600,
    'required' => ['name', 'slug'],
    'fields' => [
        'name' => 'name',
        'internal_notes' => 'internal_notes',
        'slug' => 'slug',
        'description' => 'description',
        'order' => ['source' => 'order', 'type' => 'int'],
        'is_active' => ['source' => 'is_active', 'type' => 'bool'],
        'country_ids' => ['source' => 'country_ids', 'type' => 'link', 'entity' => 'core_countries'],
    ],
];
