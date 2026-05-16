<?php
/**
 * Entity schema: automation_product_navigation
 *
 * ============================================================================
 * !!! LEGACY — PENDING MIGRATION !!!
 * ============================================================================
 * `cms_product_navigation`-tabellen finns INTE i Wexoe NY ännu. table_id är
 * null, vilket gör att `Core::entity('automation_product_navigation')->all()`
 * returnerar en tom array och loggar `missing_table_id`. Konsekvens:
 * `[wexoe_product_nav]`-shortcoden (`wexoe-product-nav.php`) renderar tom
 * mega-meny utan synligt fel.
 *
 * Att göra när migrationen tas upp igen:
 *   1. Skapa tabellen `cms_product_navigation` i Wexoe NY via MCP enligt
 *      schemat i `MIGRATION-PLAN.md` (avsnittet "cms_product_navigation").
 *   2. Kopiera 20 records från gamla `Product navigation`
 *      (`tblJa2Kd6QHjFXPJZ`).
 *   3. Fyll i `table_id` nedan och ta bort denna varning.
 *
 * Navigation-länkar i mega-meny (gamla Product navigation-tabellen).
 * Airtable-tabell: cms_product_navigation i Wexoe NY (skapas senare).
 */

if (!defined('ABSPATH')) exit;

return [
    'base_id' => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    // LEGACY — tabellen finns inte ännu. Se kommentaren ovan.
    'table_id' => null,
    'cache_ttl' => 86400,
    'required' => ['name', 'type'],
    'fields' => [
        'name' => 'name',
        'internal_notes' => 'internal_notes',
        'is_active' => ['source' => 'is_active', 'type' => 'bool'],
        'order' => ['source' => 'order', 'type' => 'float'],
        'type' => 'type',
        'url' => 'url',
        'icon' => 'icon',
        'description' => 'description',
        'button_text' => 'button_text',
        'benefit_1' => 'benefit_1',
        'benefit_2' => 'benefit_2',
        'division_ids' => ['source' => 'division_ids', 'type' => 'link', 'entity' => 'core_divisions'],
    ],
];
