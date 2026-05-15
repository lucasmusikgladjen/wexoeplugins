<?php
/**
 * Entity schema: product_page_sections
 *
 * Sektioner som tillhör en produktområdessida (gamla "Normal 1-4" på Product Areas).
 * Airtable-tabell: cms_product_page_sections (tbl1r3T3ukIPJ0S3N) i Wexoe NY.
 *
 * En section pekar tillbaka på sin parent product_page via `product_page_ids`.
 * Rendering-ordning styrs av `order`. Filtrera per product_page i feature-pluginet
 * eller hämta via PA:s `section_ids`-länk + sortera på `order`.
 *
 * Konvention: snake_case överallt — passthrough.
 */

if (!defined('ABSPATH')) exit;

return [
    'base_id' => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    'table_id' => 'tbl1r3T3ukIPJ0S3N',
    'primary_key' => 'name',
    'cache_ttl' => 86400,
    'required' => ['name'],
    'fields' => [
        'name' => 'name',
        'internal_notes' => 'internal_notes',
        'is_active' => ['source' => 'is_active', 'type' => 'bool'],
        'order' => ['source' => 'order', 'type' => 'float'],
        'product_page_ids' => ['source' => 'product_page_ids', 'type' => 'link', 'entity' => 'product_areas'],
        'h2' => 'h2',
        'text' => 'text',
        'bullets' => ['source' => 'bullets', 'type' => 'lines'],
        'image_url' => 'image_url',
        'bg' => 'bg',
        'reversed' => ['source' => 'reversed', 'type' => 'bool'],
        'shown_top' => ['source' => 'shown_top', 'type' => 'bool'],
    ],
];
