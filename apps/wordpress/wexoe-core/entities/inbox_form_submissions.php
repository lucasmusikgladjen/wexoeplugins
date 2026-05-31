<?php
/**
 * Entity schema: inbox_form_submissions
 *
 * Inkomna formulär-submissions från publika sidor. Append-only.
 * Airtable-tabell: inbox_form_submissions i Wexoe NY (skapad i migrationen).
 *
 * Ersätter gamla `User data`-tabellen i Wexoe-basen (som var tom).
 *
 * Skrivs av `wexoe-core/src/ContactForm/Handler.php` när användare submittar
 * publika kontaktformulär. Read-mostly från Airtables sida.
 */

if (!defined('ABSPATH')) exit;

return [
    'base_id' => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    'table_id' => null, // Sätts efter MCP-skapande
    'primary_key' => 'submission_id',
    'cache_ttl' => 300, // Kort cache; inkommande data ska vara aktuell
    'required' => ['submission_id'],
    'fields' => [
        'submission_id' => 'submission_id',
        'submitted_at' => 'submitted_at',
        'submission_type' => 'submission_type',
        'source_plugin' => 'source_plugin',
        'page_slug' => 'page_slug',
        'email' => 'email',
        'name' => 'name',
        'company' => 'company',
        'phone' => 'phone',
        'message' => 'message',
        'newsletter_consent' => ['source' => 'newsletter_consent', 'type' => 'bool'],
        'magnet_name' => 'magnet_name',
        'event_title' => 'event_title',
        'calculator_data' => 'calculator_data',
        'extra' => 'extra',
        'sent_to_crm' => ['source' => 'sent_to_crm', 'type' => 'bool'],
    ],
];
