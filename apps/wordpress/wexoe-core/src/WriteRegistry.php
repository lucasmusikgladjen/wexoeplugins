<?php
namespace Wexoe\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registry for write-entity schemas.
 *
 * Write-entity files live in write-entities/*.php at the plugin root.
 * Each file returns an associative array with at least:
 *   - 'table_id' (string)  Airtable table ID (tblXXX)
 *   - 'fields'   (array)   domain_key => Airtable field name
 *
 * Usage:
 *   $config = WriteRegistry::get_config('user_submissions');
 *   // or via the public facade:
 *   $repo = Core::submission('user_submissions');
 *   $repo->create_mapped(['email' => 'a@b.com', 'submission_type' => 'leadmagnet']);
 */
class WriteRegistry {

    /** @var array<string, array|null> Per-request config cache */
    private static $configs = [];

    /**
     * Load and return a write-entity config by name.
     * Returns null if the file is missing or invalid.
     */
    public static function get_config($name) {
        $name = self::sanitize_name($name);
        if ($name === '') {
            return null;
        }

        if (array_key_exists($name, self::$configs)) {
            return self::$configs[$name];
        }

        $file = WEXOE_CORE_PATH . 'write-entities/' . $name . '.php';
        if (!file_exists($file)) {
            Logger::warning('Write-entity schema file not found', [
                'entity' => $name,
                'path'   => $file,
            ]);
            self::$configs[$name] = null;
            return null;
        }

        $config = self::safe_include($file);
        if (!is_array($config)) {
            Logger::error('Write-entity schema did not return an array', [
                'entity' => $name,
                'path'   => $file,
            ]);
            self::$configs[$name] = null;
            return null;
        }

        if (empty($config['table_id']) || empty($config['fields']) || !is_array($config['fields'])) {
            Logger::error('Write-entity schema missing required keys (table_id, fields)', [
                'entity' => $name,
            ]);
            self::$configs[$name] = null;
            return null;
        }

        self::$configs[$name] = $config;
        return $config;
    }

    /**
     * List all write-entity names present on disk.
     */
    public static function list_registered() {
        $dir = WEXOE_CORE_PATH . 'write-entities/';
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

    /** Reset cache (for tests / admin reload). */
    public static function reset() {
        self::$configs = [];
    }

    /* --------------------------------------------------------
       INTERNAL
       -------------------------------------------------------- */

    private static function sanitize_name($name) {
        if (!is_string($name)) return '';
        return preg_match('/^[a-z][a-z0-9_]*$/', $name) === 1 ? $name : '';
    }

    private static function safe_include($file) {
        try {
            return include $file;
        } catch (\Throwable $e) {
            Logger::error('Exception loading write-entity schema', [
                'path'  => $file,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
