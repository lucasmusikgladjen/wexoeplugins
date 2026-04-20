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
 * Future additions (later phases):
 *   $mission = Core::copy('mission');        // key/value SSOT
 *   $phone = Core::company()->phone_main;    // singleton SSOT
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
