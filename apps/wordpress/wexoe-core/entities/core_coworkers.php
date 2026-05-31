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
        'full_name' => 'full_name',
        'internal_notes' => 'internal_notes',
        'title' => 'title',
        'email' => 'email',
        'phone' => 'phone',
        'image_url' => 'image_url',
        'linkedin_url' => 'linkedin_url',
        'bio' => 'bio',
        'order' => ['source' => 'order', 'type' => 'float'],
        'is_active' => ['source' => 'is_active', 'type' => 'bool'],
        'division_ids' => ['source' => 'division_ids', 'type' => 'link', 'entity' => 'core_divisions'],
        'country_ids' => ['source' => 'country_ids', 'type' => 'link', 'entity' => 'core_countries'],
    ],
];
