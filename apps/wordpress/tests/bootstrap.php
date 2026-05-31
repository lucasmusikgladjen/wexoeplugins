<?php

/**
 * Testbootstrap. wexoe-core-filer har en WordPress-guard
 * `if (!defined('ABSPATH')) exit;` högst upp — så utan en definierad ABSPATH
 * dödar de tyst test-runnern när de laddas. Definiera den innan composer-
 * autoloadern drar in klasserna, så WP-fria delar (helpers, Schema-parsing)
 * kan testas isolerat.
 */
if (!defined('ABSPATH')) {
    define('ABSPATH', sys_get_temp_dir() . '/wexoe-test-wp/');
}

// Schema::from_json() läser schema-kopian under WEXOE_CORE_PATH . 'schema/'.
// I drift sätts WEXOE_CORE_PATH av wexoe-core.php (plugin_dir_path). I test
// pekar vi den på wexoe-core/ så from_json hittar den committade synk-kopian
// (annars fatalar Schema::schema_path() på en odefinierad konstant).
if (!defined('WEXOE_CORE_PATH')) {
    define('WEXOE_CORE_PATH', dirname(__DIR__) . '/wexoe-core/');
}

require __DIR__ . '/../vendor/autoload.php';
