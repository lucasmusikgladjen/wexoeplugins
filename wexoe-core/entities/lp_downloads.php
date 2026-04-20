<?php
/**
 * Entity schema: lp_downloads
 *
 * Nedladdningsbara resurser kopplade till LP Tabs.
 * Airtable-tabell: LP Downloads (tblbLM827DzjWGjCR)
 *
 * Primärnyckel: saknas. Uppslag via _record_id (find_by_ids).
 */

if (!defined('ABSPATH')) exit;

return [
    'table_id' => 'tblbLM827DzjWGjCR',
    'cache_ttl' => 86400,
    'required' => ['name'],
    'fields' => [
        'name' => 'Name',
        'description' => 'Description',
        'thumbnail' => 'Thumbnail',
        'file_url' => 'File URL',
        'button_text' => 'Button Text',
        'order' => ['source' => 'Order', 'type' => 'float'],
        'visa' => ['source' => 'Visa', 'type' => 'bool'],

        // Back-link
        'tab_ids' => [
            'source' => 'Tab',
            'type' => 'link',
        ],
    ],
];
