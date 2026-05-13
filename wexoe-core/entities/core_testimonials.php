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
        'internal_name' => 'Internal Name',
        'quote' => 'Quote',
        'author_name' => 'Author Name',
        'author_title' => 'Author Title',
        'author_image' => ['source' => 'Author Image', 'type' => 'attachment'],
        'order' => ['source' => 'Order', 'type' => 'float'],
        'active' => ['source' => 'Active', 'type' => 'bool'],
        'featured' => ['source' => 'Featured', 'type' => 'bool'],
        'customer_type_ids' => ['source' => 'Customer Type', 'type' => 'link', 'entity' => 'core_customer_types'],
        'division_ids' => ['source' => 'Division', 'type' => 'link', 'entity' => 'core_divisions'],
        'country_ids' => ['source' => 'Country', 'type' => 'link', 'entity' => 'core_countries'],
    ],
];
