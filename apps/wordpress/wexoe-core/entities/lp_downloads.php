<?php
/**
 * Entity schema: lp_downloads
 *
 * Nedladdningsbara resurser kopplade till LP Tabs.
 * Airtable-tabell: cms_landing_page_downloads (tbltAtilGKnQ2wc7I) i Wexoe NY.
 *
 * Primärnyckel: 'name'.
 */

if (!defined('ABSPATH')) exit;

return [
    'base_id' => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    'table_id' => 'tbltAtilGKnQ2wc7I',
    'primary_key' => 'name',
    'cache_ttl' => 86400,
    'required' => ['name'],
    'fields' => [
        'name' => 'name',
        'internal_notes' => 'internal_notes',
        'is_active' => ['source' => 'is_active', 'type' => 'bool'],
        'order' => ['source' => 'order', 'type' => 'float'],
        'description' => 'description',
        'thumbnail_url' => 'thumbnail_url',
        'file_url' => 'file_url',
        'button_text' => 'button_text',

        // Back-link
        'tab_ids' => [
            'source' => 'tab_ids',
            'type' => 'link',
            'entity' => 'lp_tabs',
        ],
    ],
];
