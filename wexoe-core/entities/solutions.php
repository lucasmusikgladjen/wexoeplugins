<?php
if (!defined('ABSPATH')) exit;

return [
    'table_id' => 'Solutions & Concepts',
    'fields' => [
        'name' => 'Name',
        'image' => 'Image',
        'url' => 'URL',
        'description' => 'Description',
        'cta_text' => 'CTA Text',
        'category' => 'Category',
        'order' => ['source' => 'Order', 'type' => 'float'],
        'visa' => ['source' => 'Visa', 'type' => 'bool'],
    ],
];
