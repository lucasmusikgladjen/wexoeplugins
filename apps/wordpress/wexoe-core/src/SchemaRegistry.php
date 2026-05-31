<?php
namespace Wexoe\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lazy-loading registry for entity schemas.
 *
 * Schemas live in entities/*.php at the plugin root. Each file returns an
 * associative array describing one entity (its Airtable table, fields, cache TTL).
 *
 * Design:
 *   - Per-request memoization: same schema is loaded from disk at most once per request
 *   - Per-request memoization of repositories: Core::entity('partners') returns the
 *     same EntityRepository instance throughout a single request
 *   - Missing schemas log a warning and return null (never throw)
 *
 * The registry is purely procedural — all state is static because there's only
 * one registry per request.
 */
class SchemaRegistry {

    /** @var array<string, array> Loaded schemas by entity name */
    private static $schemas = [];

    /** @var array<string, EntityRepository> Repository instances by entity name */
    private static $repositories = [];

    /**
     * Get the schema array for an entity. Returns null if not found or invalid.
     * Caches the result for the rest of the request.
     */
    public static function get_schema($entity_name) {
        $entity_name = self::sanitize_name($entity_name);
        if ($entity_name === '') {
            return null;
        }

        // Cache hit
        if (array_key_exists($entity_name, self::$schemas)) {
            return self::$schemas[$entity_name];
        }

        $file = self::get_entities_path() . $entity_name . '.php';
        if (!file_exists($file)) {
            Logger::warning('Entity schema file not found', [
                'entity' => $entity_name,
                'path' => $file,
            ]);
            self::$schemas[$entity_name] = null;
            return null;
        }

        // Include the file. It should return an array.
        $schema = self::safe_include($file);
        if (!is_array($schema)) {
            Logger::error('Entity schema did not return an array', [
                'entity' => $entity_name,
                'path' => $file,
            ]);
            self::$schemas[$entity_name] = null;
            return null;
        }

        // Validate required top-level keys
        $required = ['table_id', 'fields'];
        foreach ($required as $key) {
            if (!isset($schema[$key])) {
                Logger::error('Entity schema missing required key', [
                    'entity' => $entity_name,
                    'missing_key' => $key,
                ]);
                self::$schemas[$entity_name] = null;
                return null;
            }
        }

        // Validate primary_key references an actual field in 'fields'
        if (isset($schema['primary_key'])) {
            $pk = $schema['primary_key'];
            if (!isset($schema['fields'][$pk])) {
                Logger::error('Entity schema primary_key does not match any declared field', [
                    'entity' => $entity_name,
                    'primary_key' => $pk,
                ]);
                self::$schemas[$entity_name] = null;
                return null;
            }
        }

        self::$schemas[$entity_name] = $schema;
        return $schema;
    }

    /**
     * Get an EntityRepository for an entity. Returns null if schema is invalid.
     */
    public static function get_repository($entity_name) {
        $entity_name = self::sanitize_name($entity_name);
        if ($entity_name === '') {
            return null;
        }

        if (isset(self::$repositories[$entity_name])) {
            return self::$repositories[$entity_name];
        }

        $schema = self::get_schema($entity_name);
        if ($schema === null) {
            return null;
        }

        $repo = new EntityRepository($entity_name, $schema);
        self::$repositories[$entity_name] = $repo;
        return $repo;
    }

    /**
     * List all entity names that have a schema file present on disk.
     * Used by the admin UI.
     */
    public static function list_registered() {
        $dir = self::get_entities_path();
        if (!is_dir($dir)) {
            return [];
        }
        $files = glob($dir . '*.php');
        if (!is_array($files)) {
            return [];
        }
        $names = [];
        foreach ($files as $file) {
            $names[] = basename($file, '.php');
        }
        sort($names);
        return $names;
    }

    /* --------------------------------------------------------
       INTERNAL HELPERS
       -------------------------------------------------------- */

    private static function get_entities_path() {
        return WEXOE_CORE_PATH . 'entities/';
    }

    /**
     * Safe-include a schema file. Returns the file's return value, or null
     * if the file throws or produces errors during loading.
     */
    private static function safe_include($file) {
        try {
            return include $file;
        } catch (\Throwable $e) {
            Logger::error('Exception while loading schema file', [
                'path' => $file,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Sanitize entity name — only lowercase letters, digits, and underscores.
     * Prevents path traversal (../foo) and ensures file paths are safe.
     */
    private static function sanitize_name($name) {
        if (!is_string($name)) return '';
        return preg_match('/^[a-z][a-z0-9_]*$/', $name) === 1 ? $name : '';
    }

    /**
     * Reset internal caches. Used by tests and admin "reload schemas" action.
     */
    public static function reset() {
        self::$schemas = [];
        self::$repositories = [];
    }
}
