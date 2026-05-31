<?php
/**
 * Entity schema: automation_team_members
 *
 * LEGACY/PENDING-MIGRATION: pekar fortfarande på gamla `Coworkers`-tabellen i
 * Wexoe-basen. Används av `plugins/automation-pillar/wexoe-team-rack.php`
 * som behöver migreras till `core_coworkers` innan gamla basen kan tas ned.
 *
 * MIGRATIONSTODO:
 *   1. Lägg till `team_rack_tag` + `module_*`-fält på `core_coworkers` om
 *      team-rack-funktionaliteten ska bevaras.
 *   2. Uppdatera `wexoe-team-rack.php` att använda `Core::entity('core_coworkers')`.
 *   3. Radera denna fil.
 *
 * Tills dess pekar denna entitet på Wexoe-basen (legacy) och kräver att den
 * basen lever vidare. Detta är den enda återstående hårda referensen till
 * gamla basen i `wexoe-core/entities/`.
 *
 * @deprecated Migreras till core_coworkers
 */

if (!defined('ABSPATH')) exit;

return [
    'table_id' => 'tblldarIcIpxlZ9GV',
    'cache_ttl' => 86400,
    'required' => ['name'],
    'fields' => [
        'name' => 'Name',
        'title' => 'Title',
        'description' => 'Description',
        'image' => ['source' => 'Image', 'type' => 'attachment'],
        'email' => 'Email',
        'phone' => 'Phone',
        'tags' => 'Tags',
        'responsibility' => 'Responsibility',
        'module_name' => 'Module name',
        'module_color' => 'ModuleColor',
        'module_id' => 'ModuleId',
        'visa' => ['source' => 'Visa', 'type' => 'bool'],
        'order' => ['source' => 'Order', 'type' => 'float'],
    ],
];
