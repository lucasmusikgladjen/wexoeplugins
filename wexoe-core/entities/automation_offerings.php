<?php
/**
 * Entity schema: automation_offerings
 *
 * ============================================================================
 * !!! LEGACY — PENDING MIGRATION !!!
 * ============================================================================
 * `cms_offerings`-tabellen finns INTE i Wexoe NY ännu. table_id är null,
 * vilket gör att `Core::entity('automation_offerings')->all()` returnerar
 * en tom array och loggar `missing_table_id`. Konsekvens:
 * `[wexoe_offerings]`-shortcoden (`wexoe-offerings-tabs.php`) renderar tom
 * sektion utan synligt fel.
 *
 * Att göra när migrationen tas upp igen:
 *   1. Skapa tabellen `cms_offerings` i Wexoe NY via MCP enligt schemat i
 *      `MIGRATION-PLAN.md` (avsnittet "cms_offerings").
 *   2. Kopiera 7 records från gamla `Offerings` (`tbldQZJu3NHHP5dUh`).
 *   3. Fyll i `table_id` nedan och ta bort denna varning.
 *
 * Erbjudande-block (gamla Offerings-tabellen).
 * Airtable-tabell: cms_offerings i Wexoe NY (skapas senare).
 */

if (!defined('ABSPATH')) exit;

return [
    'base_id' => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    // LEGACY — tabellen finns inte ännu. Se kommentaren ovan.
    'table_id' => null,
    'cache_ttl' => 86400,
    'required' => ['name'],
    'fields' => [
        'name' => 'name',
        'internal_notes' => 'internal_notes',
        'is_active' => ['source' => 'is_active', 'type' => 'bool'],
        'order' => ['source' => 'order', 'type' => 'float'],
        'division' => 'division',
        'heading' => 'heading',
        'description' => 'description',
        'image_url' => 'image_url',
        'benefit_1' => 'benefit_1',
        'benefit_2' => 'benefit_2',
        'benefit_3' => 'benefit_3',
        'benefit_4' => 'benefit_4',
        'benefit_5' => 'benefit_5',
        'cta_text' => 'cta_text',
        'cta_url' => 'cta_url',
    ],
];
