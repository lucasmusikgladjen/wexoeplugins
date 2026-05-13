<?php
if (!defined('ABSPATH')) exit;
return [
    'table_id' => 'tblZ5YIYFelxA0nBm',
    'base_id'  => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    'field_types' => [
        'logo'             => 'attachment_url',
        'logo_transparent' => 'attachment_url',
        'division_ids'     => 'link',
        'country_ids'      => 'link',
    ],
    'fields' => [
        'name' => 'Name',
        'logo' => 'Logo',
        'logo_transparent' => 'Logo Transparent',
        'url' => 'URL',
        'description' => 'Description',
        'order' => 'Order',
        'active' => 'Active',
        'division_ids' => 'Division',
        'country_ids' => 'Country',
    ],
];
