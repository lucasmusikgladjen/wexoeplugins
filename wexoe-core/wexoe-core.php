<?php
/**
 * Plugin Name: Wexoe Core
 * Plugin URI:  https://wexoe.se
 * Description: Unified Airtable data layer for Wexoe's WordPress plugins. Phase 4: landing page migration + find_by_ids.
 * Version:     0.5.0
 * Author:      Wexoe Industry AB
 * Text Domain: wexoe-core
 * Requires PHP: 7.4
 * Requires at least: 6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ============================================================
   CONSTANTS
   ============================================================ */

define('WEXOE_CORE_VERSION', '0.5.0');
define('WEXOE_CORE_FILE', __FILE__);
define('WEXOE_CORE_PATH', plugin_dir_path(__FILE__));
define('WEXOE_CORE_URL', plugin_dir_url(__FILE__));

/* ============================================================
   AUTOLOADER (PSR-4-style for Wexoe\Core namespace)
   ============================================================ */

spl_autoload_register(function ($class) {
    $prefix = 'Wexoe\\Core\\';
    $base_dir = WEXOE_CORE_PATH . 'src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

/* ============================================================
   BOOTSTRAP
   ============================================================ */

add_action('plugins_loaded', function () {
    \Wexoe\Core\Plugin::instance()->boot();
});

/* ============================================================
   ACTIVATION / DEACTIVATION
   ============================================================ */

register_activation_hook(__FILE__, function () {
    // Reserved for future setup
});

register_deactivation_hook(__FILE__, function () {
    global $wpdb;
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '\\_transient\\_wexoe_core_notice_%'
            OR option_name LIKE '\\_transient\\_timeout\\_wexoe_core_notice_%'
            OR option_name LIKE '\\_transient\\_wexoe_core_test_result_%'
            OR option_name LIKE '\\_transient\\_timeout\\_wexoe_core_test_result_%'"
    );
});
