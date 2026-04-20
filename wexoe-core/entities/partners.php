<?php
/**
 * Entity schema: partners
 *
 * SSOT-tabell med Wexoes samarbetspartners/leverantörer (Rockwell, Fibrain, etc.)
 * Airtable-tabell: Partners (tblsCOF5BPAxN6nmq)
 *
 * Primärnyckel: 'name' — Partners-tabellen har ingen slug-kolumn,
 * så namnet används för uppslag. Byt till 'slug' om en sådan kolumn läggs till.
 */

if (!defined('ABSPATH')) exit;

return [
    'table_id' => 'tblsCOF5BPAxN6nmq',
    'primary_key' => 'name',
    'cache_ttl' => 86400, // 24h
    'required' => ['name'],
    'fields' => [
        // Simple text
        'name' => 'Name',
        'logo_url' => 'Logo',
        'logo_transparent_url' => 'Logo transparent',

        // Linked records — default "lazy" (returns array of Airtable record IDs)
        'division_ids' => [
            'source' => 'Division',
            'type' => 'link',
            'entity' => 'divisions',
        ],
        'campaign_ids' => [
            'source' => 'Campaigns',
            'type' => 'link',
        ],
        'deliverable_ids' => [
            'source' => 'Deliverables',
            'type' => 'link',
        ],
        'activity_ids' => [
            'source' => 'Activities',
            'type' => 'link',
        ],
        'article_ids' => [
            'source' => 'Articles',
            'type' => 'link',
            'entity' => 'articles',
        ],
    ],
];
