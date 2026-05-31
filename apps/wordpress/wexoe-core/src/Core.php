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
     * Get a write repository for a table using raw Airtable field names.
     *
     * Use when you want direct, schema-free access to any table. Field names
     * must be the actual Airtable column names. Sanitize all values before
     * passing them here.
     *
     * @param string      $table_id  Airtable table ID (tblXXXXXXXXXXXXXX)
     * @param string|null $base_id   Optional base ID override (uses plugin config if null)
     * @return WriteRepository
     */
    public static function writer($table_id, $base_id = null) {
        return new WriteRepository($table_id, $base_id);
    }

    /**
     * Get a write repository backed by a named write-entity schema.
     *
     * Write-entity schemas live in write-entities/*.php in the wexoe-core plugin
     * directory. They map domain field names to Airtable field names and store
     * the table ID, so plugins don't hardcode table IDs or Airtable column names.
     *
     * Use create_mapped() on the returned repository to write with domain keys.
     *
     *   $result = Core::submission('user_submissions')->create_mapped([
     *       'email'           => sanitize_email($email),
     *       'submission_type' => 'leadmagnet',
     *       'newsletter_consent' => true,
     *       'extra'           => ['custom_key' => 'value'],
     *   ]);
     *
     * Returns null if the write-entity schema is not found or invalid. Callers
     * should null-check before use.
     *
     * @param string $entity_name  Write-entity name (e.g. 'user_submissions')
     * @return WriteRepository|null
     */
    public static function submission($entity_name) {
        $config = WriteRegistry::get_config($entity_name);
        if ($config === null) {
            Logger::error('Core::submission() — write-entity not found', [
                'entity' => $entity_name,
            ]);
            return null;
        }
        $base_id = isset($config['base_id']) && is_string($config['base_id']) && $config['base_id'] !== ''
            ? $config['base_id']
            : null;
        $field_types = isset($config['field_types']) && is_array($config['field_types'])
            ? $config['field_types']
            : [];
        return new WriteRepository($config['table_id'], $base_id, $config['fields'], $field_types);
    }

    /**
     * Map a renderer type → fully qualified class name.
     *
     * Returnerar klassnamn för att kunna anropa `::render($cfg)` på det.
     * Returnerar tom sträng om typen är okänd.
     *
     * Användning:
     *   $class = Core::renderer('contact-form');
     *   if ($class !== '') echo $class::render([...]);
     *
     * @param string $type
     * @return string
     */
    public static function renderer($type) {
        $map = [
            'contact-form' => Renderers\ContactForm::class,
        ];
        return isset($map[$type]) && class_exists($map[$type]) ? $map[$type] : '';
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
