<?php
if (!defined('ABSPATH')) exit;

return [
    'table_id' => 'tbldQZJu3NHHP5dUh',
    'cache_ttl' => 86400,
    'required' => ['name'],
    'fields' => [
        'name' => 'Name',
        'division' => 'Division',
        'order' => ['source' => 'Order', 'type' => 'float'],
        'heading' => 'Heading',
        'description' => 'Description',
        'image' => ['source' => 'Image', 'type' => 'attachment'],
        'image_url' => 'Image',
        'benefit_1' => 'Benefit 1',
        'benefit_2' => 'Benefit 2',
        'benefit_3' => 'Benefit 3',
        'benefit_4' => 'Benefit 4',
        'benefit_5' => 'Benefit 5',
        'cta_text' => 'CTA Text',
        'cta_url' => 'CTA URL',
    ],
];
