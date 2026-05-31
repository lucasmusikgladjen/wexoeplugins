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

require __DIR__ . '/../vendor/autoload.php';
