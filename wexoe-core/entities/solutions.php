<?php
/**
 * Entity schema: solutions
 *
 * Mindre solution/concept-block som visas på produktområdessidor.
 * Airtable-tabell: cms_solutions_mini (tblxK7ikOgLFuze6m) i Wexoe NY.
 *
 * Namnsuffixet "mini" reserverar `cms_solution_pages` för framtida fullsides-solutions.
 *
 * Konvention: snake_case överallt — passthrough.
 */

if (!defined('ABSPATH')) exit;

return [
    'base_id' => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    'table_id' => 'tblxK7ikOgLFuze6m',
    'primary_key' => 'name',
    'cache_ttl' => 86400,
    'required' => ['name'],
    'fields' => [
        'name' => 'name',
        'internal_notes' => 'internal_notes',
        'is_active' => ['source' => 'is_active', 'type' => 'bool'],
        'order' => ['source' => 'order', 'type' => 'float'],
        'category' => 'category',
        'image_url' => 'image_url',
        'url' => 'url',
        'description' => 'description',
        'cta_text' => 'cta_text',
        'product_page_ids' => ['source' => 'product_page_ids', 'type' => 'link', 'entity' => 'product_areas'],
    ],
];
