<?php
/**
 * Write-entity schema: user_submissions
 *
 * Samlar in alla typer av användarinlämningar från Wexoe-plugins:
 * lead magnets, eventanmälningar, kontaktformulär, bokningar, etc.
 *
 * Airtable-tabell: inbox_user_submissions
 * Bas: Wexoe NY (\Wexoe\Core\Plugin::SSOT_BASE_ID)
 *
 * Skalbarhetsdesign:
 *   - Fält som inte behövs för en viss inlämningstyp lämnas tomma.
 *   - Typ-specifik data som inte har ett dedikerat fält skickas som
 *     JSON i "extra"-fältet via nyckeln 'extra'.
 *   - Nya inlämningstyper kan lägga till fält till detta schema eller
 *     packa extra data i 'extra' utan att behöva ändra Airtable-strukturen.
 */

if (!defined('ABSPATH')) exit;

return [
    // Airtable accepterar tabellnamn i API-pathen, så vi använder namnet tills
    // ett stabilt tbl-id för inbox_user_submissions finns dokumenterat i repo:t.
    'table_id' => 'inbox_user_submissions',
    'base_id'  => \Wexoe\Core\Plugin::SSOT_BASE_ID,

    /**
     * Fältmappning: domän-nyckel => Airtable-fältnamn
     *
     * Domän-nycklar används i Core::submission('user_submissions')->create_mapped([...]).
     * Lägg till fler nycklar här om nya Airtable-fält skapas i framtiden.
     */
    'fields' => [
        // Identitet
        'submission_id'      => 'submission_id',
        'email'              => 'email',
        'name'               => 'name',
        'company'            => 'company',
        'phone'              => 'phone',

        // Metadata
        'submission_type'    => 'submission_type',
        'submitted_at'       => 'submitted_at',
        'page_slug'          => 'page_slug',
        'source_plugin'      => 'source_plugin',

        // Innehåll
        'message'            => 'message',
        'newsletter_consent' => 'newsletter_consent',

        // Typ-specifika fält
        'magnet_name'        => 'magnet_name',
        'event_title'        => 'event_title',
        'calculator_data'    => 'calculator_data',

        // CRM
        'sent_to_crm'        => 'sent_to_crm',

        // Spill: valfri JSON för plugin-specifika fält utan dedikerad kolumn
        'extra'              => 'extra',
    ],

    'field_types' => [
        'calculator_data' => 'json',
        'extra'           => 'json',
    ],
];
