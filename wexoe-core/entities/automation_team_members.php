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
        'tags' => ['source' => 'Tags', 'type' => 'lines'],
        'responsibility' => ['source' => 'Responsibility', 'type' => 'lines'],
        'module_name' => 'Module name',
        'module_color' => 'ModuleColor',
        'module_id' => 'ModuleId',
        'visa' => ['source' => 'Visa', 'type' => 'bool'],
        'order' => ['source' => 'Order', 'type' => 'float'],
    ],
];
