<?php
if (!defined('ABSPATH')) exit;

return [
    'table_id' => 'Products',
    'fields' => [
        'name' => 'Name',
        'ecosystem_description' => 'Ecosystem Description',
        'description' => 'Description',
        'bullets' => ['source' => 'Bullets', 'type' => 'lines'],
        'image' => 'Image',
        'button_1_text' => 'Button 1 Text',
        'button_1_url' => 'Button 1 URL',
        'button_2_text' => 'Button 2 Text',
        'button_2_url' => 'Button 2 URL',
        'horizontal' => ['source' => 'Horizontal', 'type' => 'bool'],
        'header_side_menu' => 'Header side menu',
        'order' => ['source' => 'Order', 'type' => 'float'],
        'visa' => ['source' => 'Visa', 'type' => 'bool'],
        'article_ids' => ['source' => 'Articles', 'type' => 'link', 'entity' => 'articles'],
    ],
];
