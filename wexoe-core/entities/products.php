<?php
/**
 * Entity schema: products
 *
 * CMS-rendering-metadata för produktblock på produktområdessidor.
 * Airtable-tabell: cms_products (tblN23V7uAMpeZoO1) i Wexoe NY.
 *
 * Innehåller bara CMS-fält (header text, image, button URLs). Produktdata
 * (artikelnummer, pris, stock) tillkommer via framtida `pim_articles`-tabell.
 *
 * Konvention: snake_case överallt — passthrough.
 */

if (!defined('ABSPATH')) exit;

return [
    'base_id' => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    'table_id' => 'tblN23V7uAMpeZoO1',
    'primary_key' => 'name',
    'cache_ttl' => 86400,
    'required' => ['name'],
    'fields' => [
        'name' => 'name',
        'internal_notes' => 'internal_notes',
        'is_active' => ['source' => 'is_active', 'type' => 'bool'],
        'order' => ['source' => 'order', 'type' => 'float'],
        'ecosystem_description' => 'ecosystem_description',
        'description' => 'description',
        'bullets' => ['source' => 'bullets', 'type' => 'lines'],
        'image_url' => 'image_url',
        'button_1_text' => 'button_1_text',
        'button_1_url' => 'button_1_url',
        'button_2_text' => 'button_2_text',
        'button_2_url' => 'button_2_url',
        'horizontal' => ['source' => 'horizontal', 'type' => 'bool'],
        'header_side_menu' => 'header_side_menu',
        'product_page_ids' => ['source' => 'product_page_ids', 'type' => 'link', 'entity' => 'product_areas'],
        'article_ids' => ['source' => 'article_ids', 'type' => 'link', 'entity' => 'articles'],
        'supplier_ids' => ['source' => 'supplier_ids', 'type' => 'link', 'entity' => 'core_partners'],
    ],
];
