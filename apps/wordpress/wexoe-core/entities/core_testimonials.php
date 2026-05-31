<?php
/**
 * Entity schema: core_testimonials
 *
 * SSOT — Kundreferenser/testimonials (collection).
 * Airtable-tabell: core_testimonials (tbl1pe0bWz5zdkqJF) i Wexoe NY.
 */

if (!defined('ABSPATH')) exit;

return [
    'base_id' => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    'table_id' => 'tbl1pe0bWz5zdkqJF',
    'primary_key' => 'internal_name',
    'cache_ttl' => 3600,
    'required' => ['internal_name'],
    'fields' => [
        'internal_name' => 'internal_name',
        'internal_notes' => 'internal_notes',
        'quote' => 'quote',
        'author_name' => 'author_name',
        'author_title' => 'author_title',
        'author_image_url' => 'author_image_url',
        'order' => ['source' => 'order', 'type' => 'float'],
        'is_active' => ['source' => 'is_active', 'type' => 'bool'],
        'is_featured' => ['source' => 'is_featured', 'type' => 'bool'],
        'customer_type_ids' => ['source' => 'customer_type_ids', 'type' => 'link', 'entity' => 'core_customer_types'],
        'division_ids' => ['source' => 'division_ids', 'type' => 'link', 'entity' => 'core_divisions'],
        'country_ids' => ['source' => 'country_ids', 'type' => 'link', 'entity' => 'core_countries'],
    ],
];
