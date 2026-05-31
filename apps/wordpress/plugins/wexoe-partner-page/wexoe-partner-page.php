<?php
/**
 * Plugin Name: Wexoe Partner Page
 * Description: Publik leverantörssida (Rockwell, HMS, Wittenstein, …) — hero + snabbfakta + om + varför Wexoe + success cases + produktkategorier + FAQ + kontaktperson + kontaktform. Data via Wexoe Core (cms_partner_pages + linked cms_cases, cms_product_pages, core_partners).
 * Version: 1.0.0
 * Author: Wexoe
 * Text Domain: wexoe-partner-page
 *
 * Shortcode: [wexoe_partner slug="rockwell"]
 *
 * Parametrar:
 *   slug    (required)  — partner-page slug, primary key på cms_partner_pages.
 *   debug   (optional)  — "true" dumpar print_r($data) i stället för rendering.
 *   nocache (optional)  — "true" tömmer cachen för partner_pages före läsning.
 */

if (!defined('ABSPATH')) exit;

define('WEXOE_PARTNER_PAGE_FILE', __FILE__);
define('WEXOE_PARTNER_PAGE_DIR', plugin_dir_path(__FILE__));
define('WEXOE_PARTNER_PAGE_URL', plugin_dir_url(__FILE__));

require_once WEXOE_PARTNER_PAGE_DIR . 'src/Renderer.php';

if (!function_exists('wexoe_partner_core_ready')) {
    /**
     * Verifierar att Wexoe Core är aktivt och exponerar entity-fasaden.
     */
    function wexoe_partner_core_ready() {
        return class_exists('\\Wexoe\\Core\\Core')
            && method_exists('\\Wexoe\\Core\\Core', 'entity');
    }
}

/**
 * Registrera CSS-handle. Enqueueas conditional inifrån shortcode-callbacken
 * så att stylesheet bara laddas på sidor som faktiskt använder shortcoden.
 */
add_action('wp_enqueue_scripts', function () {
    $css_path = WEXOE_PARTNER_PAGE_DIR . 'assets/style.css';
    if (!file_exists($css_path)) return;
    wp_register_style(
        'wexoe-partner-page',
        WEXOE_PARTNER_PAGE_URL . 'assets/style.css',
        [],
        (string) filemtime($css_path)
    );
});

add_shortcode('wexoe_partner', 'wexoe_partner_render_shortcode');

if (!function_exists('wexoe_partner_render_shortcode')) {
    function wexoe_partner_render_shortcode($atts) {
        $atts = shortcode_atts([
            'slug'    => '',
            'debug'   => 'false',
            'nocache' => 'false',
        ], $atts, 'wexoe_partner');

        $slug = sanitize_text_field((string) $atts['slug']);
        if ($slug === '') {
            return '<p style="color:red;"><strong>Wexoe Partner:</strong> slug-parameter krävs. Använd [wexoe_partner slug="..."].</p>';
        }

        if (!wexoe_partner_core_ready()) {
            return '<p style="color:red;"><strong>Wexoe Partner:</strong> Wexoe Core-pluginet är inte aktivt.</p>';
        }

        $repo = \Wexoe\Core\Core::entity('partner_pages');
        if (!$repo) {
            return '<p style="color:red;"><strong>Wexoe Partner:</strong> Entity-schema "partner_pages" saknas i Wexoe Core.</p>';
        }

        if ($atts['nocache'] === 'true') {
            $repo->clear_cache();
        }

        $data = $repo->find($slug);

        if ($atts['debug'] === 'true') {
            return '<pre style="background:#f5f5f5;padding:20px;overflow:auto;font-size:12px;">'
                . esc_html(print_r($data, true)) . '</pre>';
        }

        if (!$data) {
            return '<p style="color:red;"><strong>Wexoe Partner:</strong> Ingen leverantörssida hittad för slug "'
                . esc_html($slug) . '".</p>';
        }

        if (empty($data['is_active'])) {
            return '';
        }

        wp_enqueue_style('wexoe-partner-page');

        $renderer = new \Wexoe\PartnerPage\Renderer($data);
        return $renderer->render();
    }
}
