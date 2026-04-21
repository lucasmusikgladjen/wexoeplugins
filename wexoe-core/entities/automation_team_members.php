<?php
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
        // Preserve Airtable arrays (multi-select / linked records) as-is.
        'tags' => 'Tags',
        'responsibility' => 'Responsibility',
        'module_name' => 'Module name',
        'module_color' => 'ModuleColor',
        'module_id' => 'ModuleId',
        'visa' => ['source' => 'Visa', 'type' => 'bool'],
        'order' => ['source' => 'Order', 'type' => 'float'],
    ],
];
