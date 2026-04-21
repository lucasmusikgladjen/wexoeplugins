<?php
if (!defined('ABSPATH')) exit;

return [
    'table_id' => 'tblJa2Kd6QHjFXPJZ',
    'cache_ttl' => 86400,
    'required' => ['name', 'type'],
    'fields' => [
        'name' => 'Name',
        'url' => 'URL',
        'type' => 'Type',
        'icon' => 'Icon',
        'division' => 'Division',
        'description' => 'Description',
        'button_text' => 'Button text',
        'benefit_1' => 'Benefit 1',
        'benefit_2' => 'Benefit 2',
        'active' => ['source' => 'Active', 'type' => 'bool'],
        'order' => ['source' => 'Order', 'type' => 'float'],
    ],
];
