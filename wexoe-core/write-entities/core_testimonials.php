<?php
if (!defined('ABSPATH')) exit;
return [
    'table_id' => 'tbl1pe0bWz5zdkqJF',
    'base_id'  => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    'field_types' => [
        'author_image'      => 'attachment_url',
        'customer_type_ids' => 'link',
        'division_ids'      => 'link',
        'country_ids'       => 'link',
    ],
    'fields' => [
        'internal_name' => 'Internal Name',
        'quote' => 'Quote',
        'author_name' => 'Author Name',
        'author_title' => 'Author Title',
        'author_image' => 'Author Image',
        'order' => 'Order',
        'active' => 'Active',
        'featured' => 'Featured',
        'customer_type_ids' => 'Customer Type',
        'division_ids' => 'Division',
        'country_ids' => 'Country',
    ],
];
