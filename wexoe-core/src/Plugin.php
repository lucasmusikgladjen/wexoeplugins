<?php
namespace Wexoe\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin bootstrap class. Singleton pattern — there is only one Plugin
 * instance per request. Also holds static accessors for plugin-wide config
 * (API key, base ID) that other Core classes read from.
 */
class Plugin {

    /** @var Plugin|null */
    private static $instance = null;

    const OPTION_API_KEY = 'wexoe_core_airtable_api_key';
    const OPTION_BASE_ID = 'wexoe_core_airtable_base_id';

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function boot() {
        add_action('wexoe_core_refresh_entity_cache', [EntityRepository::class, 'cron_refresh_entity_cache'], 10, 1);
        if (is_admin()) {
            Admin\Page::instance()->register();
        }
    }

    /* --------------------------------------------------------
       API KEY
       -------------------------------------------------------- */

    public static function get_api_key() {
        $key = get_option(self::OPTION_API_KEY, '');
        return is_string($key) ? $key : '';
    }

    public static function set_api_key($key) {
        return update_option(self::OPTION_API_KEY, sanitize_text_field($key));
    }

    public static function delete_api_key() {
        return delete_option(self::OPTION_API_KEY);
    }

    /**
     * Mask an API key for display. Shows first 4 and last 4 chars with
     * bullets in between. Short keys are fully bulleted.
     */
    public static function mask_api_key($key) {
        if (empty($key)) return '';
        $len = strlen($key);
        if ($len < 12) return str_repeat('•', $len);
        return substr($key, 0, 4) . str_repeat('•', $len - 8) . substr($key, -4);
    }

    /* --------------------------------------------------------
       BASE ID
       -------------------------------------------------------- */

    public static function get_base_id() {
        $id = get_option(self::OPTION_BASE_ID, '');
        return is_string($id) ? $id : '';
    }

    public static function set_base_id($id) {
        return update_option(self::OPTION_BASE_ID, sanitize_text_field($id));
    }

    public static function delete_base_id() {
        return delete_option(self::OPTION_BASE_ID);
    }

    /**
     * Validate the format of an Airtable base ID.
     * Airtable base IDs always start with "app" followed by 14 alphanumeric chars.
     */
    public static function is_valid_base_id_format($id) {
        return is_string($id) && preg_match('/^app[A-Za-z0-9]{14}$/', $id) === 1;
    }
}
