<?php
/**
 * Entity schema: core_coworkers
 *
 * SSOT — Medarbetare (collection).
 * Airtable-tabell: core_coworkers (tblYwMQlW9HFd41pg) i Wexoe NY.
 *
 * Filtrera via `Wexoe\Core\Helpers\Collections::coworkers_for_scope($scope)`.
 */

if (!defined('ABSPATH')) exit;

return [
    'base_id' => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    'table_id' => 'tblYwMQlW9HFd41pg',
    'primary_key' => 'full_name',
    'cache_ttl' => 3600,
    'required' => ['full_name'],
    'fields' => [
        'full_name' => 'Full Name',
        'title' => 'Title',
        'email' => 'Email',
        'phone' => 'Phone',
        'image' => ['source' => 'Image', 'type' => 'attachment'],
        'linkedin_url' => 'LinkedIn URL',
        'bio' => 'Bio',
        'order' => ['source' => 'Order', 'type' => 'float'],
        'active' => ['source' => 'Active', 'type' => 'bool'],
        'division_ids' => ['source' => 'Division', 'type' => 'link', 'entity' => 'core_divisions'],
        'country_ids' => ['source' => 'Country', 'type' => 'link', 'entity' => 'core_countries'],
    ],
];
