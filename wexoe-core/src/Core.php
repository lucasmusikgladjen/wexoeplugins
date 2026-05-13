<?php
namespace Wexoe\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Core facade — the ONE public entry point for feature plugins.
 *
 * Everything below (AirtableClient, Cache, Logger, SchemaRegistry,
 * EntityRepository, Normalizer) is implementation. Feature plugins should
 * only ever import/use this class.
 *
 * Usage:
 *   $partner = Core::entity('partners')->find('Rockwell');
 *   $all = Core::entity('partners')->all();
 *   $filtered = Core::entity('coworkers')->all(['visa' => true]);
 *   Core::log('info', 'Something happened', ['context' => 'here']);
 *
 *   // Write operations (forms, lead magnets, event signups, …):
 *   $result = Core::writer('tblXXXXXXXXXXXXXX')->create([
 *       'Email' => sanitize_email($email),
 *       'Namn'  => sanitize_text_field($name),
 *   ]);
 */
class Core {

    /**
     * Get an entity repository. Returns null if the entity has no schema file
     * or the schema is invalid. Feature plugins should null-check.
     *
     * @param string $entity_name  Lowercase entity name (e.g. 'partners')
     * @return EntityRepository|null
     */
    public static function entity($entity_name) {
        return SchemaRegistry::get_repository($entity_name);
    }

    /**
     * List all registered entity names (by scanning entities/ directory).
     */
    public static function list_entities() {
        return SchemaRegistry::list_registered();
    }

    /**
     * Get a write repository for a table. Use for creating and updating records
     * (form submissions, lead magnets, event signups, etc.).
     *
     * Pass the raw Airtable table ID (tblXXXXXXXXXXXXXX). Field names in all
     * write calls must also be the actual Airtable field names — no schema
     * translation is applied. Sanitize all values before passing them here.
     *
     * @param string      $table_id  Airtable table ID (tblXXXXXXXXXXXXXX)
     * @param string|null $base_id   Optional base ID override (uses plugin config if null)
     * @return WriteRepository
     */
    public static function writer($table_id, $base_id = null) {
        return new WriteRepository($table_id, $base_id);
    }

    /**
     * Write a log entry.
     *
     * @param string $level   'info' | 'warning' | 'error'
     * @param string $message
     * @param array  $context Optional structured data
     */
    public static function log($level, $message, $context = []) {
        Logger::log($level, $message, $context);
    }
}
