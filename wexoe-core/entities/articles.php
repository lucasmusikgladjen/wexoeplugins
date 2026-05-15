<?php
/**
 * Entity schema: articles
 *
 * Artiklar (produktvarianter) som hör till en produkt. Buildern visar dem som
 * read-only lista — aldrig editerbar och aldrig skriven av save-flödet.
 *
 * Airtable-tabell: cms_articles (tblhnz3MQG1JwfKrN) i Wexoe NY.
 * Konvention: snake_case överallt — passthrough.
 */

if (!defined('ABSPATH')) exit;

return [
    'base_id' => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    'table_id' => 'tblhnz3MQG1JwfKrN',
    'primary_key' => 'name',
    'cache_ttl' => 86400,
    'required' => ['name'],
    'fields' => [
        'name' => 'name',
        'internal_notes' => 'internal_notes',
        'is_active' => ['source' => 'is_active', 'type' => 'bool'],
        'article_number' => 'article_number',
        'description' => 'description',
        'datasheet_url' => 'datasheet_url',
        'webshop_url' => 'webshop_url',
        'image_url' => 'image_url',
        'variants' => 'variants',
        'product_ids' => ['source' => 'product_ids', 'type' => 'link', 'entity' => 'products'],
        'supplier_ids' => ['source' => 'supplier_ids', 'type' => 'link', 'entity' => 'core_partners'],
    ],
];
