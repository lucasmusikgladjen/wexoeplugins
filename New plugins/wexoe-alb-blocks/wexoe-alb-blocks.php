<?php
/**
 * Plugin Name: Wexoe ALB Blocks
 * Plugin URI:  https://wexoe.se
 * Description: Avia Layout Builder-element för Wexoe-innehåll. Ersätter manuella shortcodes med en builder-modul ("Wexoe Content") som låter redaktören välja innehållstyp och post. Beroenden: Enfold-tema, wexoe-core.
 * Version:     0.1.0
 * Author:      Wexoe Industry AB
 * Requires PHP: 7.4
 * Requires at least: 6.0
 */

if (!defined('ABSPATH')) exit;

define('WEXOE_ALB_VERSION', '0.1.0');
define('WEXOE_ALB_FILE', __FILE__);
define('WEXOE_ALB_DIR', plugin_dir_path(__FILE__));
define('WEXOE_ALB_URL', plugin_dir_url(__FILE__));

/* ============================================================
   DEPENDENCY CHECKS
   ============================================================ */

function wexoe_alb_core_ready() {
    return class_exists('\\Wexoe\\Core\\Core')
        && method_exists('\\Wexoe\\Core\\Core', 'entity');
}

function wexoe_alb_enfold_ready() {
    return class_exists('aviaShortcodeTemplate');
}

/* ============================================================
   BOOTSTRAP
   ============================================================ */

require_once WEXOE_ALB_DIR . 'includes/content-types.php';

/**
 * Registrera ALB-modulen när Enfold laddar sina shortcodes.
 *
 * Enfold kör `avia_register_shortcodes`-filter sent under init, vid den punkten
 * är basklassen aviaShortcodeTemplate garanterat laddad.
 */
add_filter('avia_register_shortcodes', 'wexoe_alb_register_module', 10, 1);

function wexoe_alb_register_module($shortcodes) {
    if (!wexoe_alb_enfold_ready() || !wexoe_alb_core_ready()) {
        return $shortcodes;
    }
    require_once WEXOE_ALB_DIR . 'includes/class-wexoe-content-block.php';
    $shortcodes[] = 'Wexoe_Content_Block';
    return $shortcodes;
}

/**
 * Frontend shortcode-fallback.
 *
 * ALB-modulens shortcode_handler() registrerar `[wexoe_content]` automatiskt
 * när modulen laddas via builder-pipelinen. Men om en sida som innehåller
 * shortcode:n renderas utanför Enfolds normala flöde (eller om modulen inte
 * hinner registreras före shortcode_unautop) behöver vi en standalone-handler.
 */
add_action('init', 'wexoe_alb_register_frontend_shortcode', 20);

function wexoe_alb_register_frontend_shortcode() {
    if (shortcode_exists('wexoe_content')) return;
    add_shortcode('wexoe_content', 'wexoe_alb_render_content_shortcode');
}

function wexoe_alb_render_content_shortcode($atts) {
    $atts = shortcode_atts([
        'content_type' => '',
        'content_id'   => '',
    ], $atts, 'wexoe_content');

    return wexoe_alb_render($atts['content_type'], $atts['content_id']);
}

/* ============================================================
   ADMIN: JS-filter för dynamisk dropdown
   ============================================================ */

add_action('admin_enqueue_scripts', 'wexoe_alb_enqueue_builder_assets');

function wexoe_alb_enqueue_builder_assets($hook) {
    if (!in_array($hook, ['post.php', 'post-new.php'], true)) return;
    if (!wexoe_alb_enfold_ready()) return;

    wp_enqueue_script(
        'wexoe-alb-builder',
        WEXOE_ALB_URL . 'assets/builder.js',
        ['jquery'],
        WEXOE_ALB_VERSION,
        true
    );
}
