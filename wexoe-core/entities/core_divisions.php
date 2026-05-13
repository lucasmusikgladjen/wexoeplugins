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
        'name' => 'Name',
        'slug' => 'Slug',
        'description' => 'Description',
        'order' => ['source' => 'Order', 'type' => 'int'],
        'active' => ['source' => 'Active', 'type' => 'bool'],
        'country_ids' => ['source' => 'Country', 'type' => 'link', 'entity' => 'core_countries'],
    ],
];
