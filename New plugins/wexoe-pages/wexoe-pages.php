<?php
/**
 * Plugin Name: Wexoe Pages
 * Plugin URI:  https://wexoe.se
 * Description: Tier 2-sidor (one-off meta-sidor: om-oss, karriär etc.). Renderar via [wexoe_page slug="..."]-shortcoden. Data lagras i cms_unique_pages-tabellen i Wexoe NY-basen. Beroende: wexoe-core ≥ 0.9.0.
 * Version:     0.1.0
 * Author:      Wexoe Industry AB
 * Requires PHP: 7.4
 * Requires at least: 6.0
 */

if (!defined('ABSPATH')) exit;

define('WEXOE_PAGES_VERSION', '0.1.0');

/* ============================================================
   CORE DEPENDENCY CHECK
   ============================================================ */

if (!function_exists('wexoe_pages_core_ready')) {
    function wexoe_pages_core_ready() {
        return class_exists('\\Wexoe\\Core\\Core')
            && method_exists('\\Wexoe\\Core\\Core', 'entity');
    }
}

/* ============================================================
   SHORTCODE
   ============================================================ */

add_shortcode('wexoe_page', 'wexoe_pages_shortcode');

function wexoe_pages_shortcode($atts) {
    $atts = shortcode_atts([
        'slug' => '',
    ], $atts, 'wexoe_page');

    $slug = trim((string) $atts['slug']);
    if ($slug === '') {
        return wexoe_pages_debug_comment('wexoe-pages: ingen slug angiven');
    }

    if (!wexoe_pages_core_ready()) {
        return wexoe_pages_debug_comment('wexoe-pages: wexoe-core är inte aktivt');
    }

    return wexoe_pages_render($slug);
}

/**
 * Render a Tier 2-sida från cms_unique_pages.
 *
 * Returnerar tom sträng om sidan inte finns eller inte är publicerad.
 * Sektioner renderas i fast ordning baserat på `show_<x>`-flaggor.
 */
function wexoe_pages_render($slug) {
    $repo = \Wexoe\Core\Core::entity('cms_unique_pages');
    if ($repo === null) {
        return wexoe_pages_debug_comment('wexoe-pages: cms_unique_pages-schema saknas');
    }

    $page = $repo->find_by('slug', $slug);
    if ($page === null) {
        return wexoe_pages_debug_comment('wexoe-pages: hittade inte slug=' . esc_html($slug));
    }

    if (empty($page['is_published'])) {
        return wexoe_pages_debug_comment('wexoe-pages: sidan finns men är inte publicerad');
    }

    ob_start();
    echo '<article class="wxp-page">';

    // Top-level H1 visas bara om Hero-sektionen är AV. Hero-sektionen renderar sin egen H1.
    if (empty($page['show_hero']) && !empty($page['h1'])) {
        echo '<h1 class="wxp-page__h1">' . esc_html($page['h1']) . '</h1>';
    }

    // Sektioner i FAST ordning. Detta är designval — inte redigerbart.
    if (!empty($page['show_hero'])) {
        echo \Wexoe\Core\Renderers\Hero::render([
            'eyebrow'   => $page['hero_eyebrow'] ?? '',
            'title'     => !empty($page['hero_h1_override']) ? $page['hero_h1_override'] : ($page['h1'] ?? ''),
            'subtitle'  => $page['hero_subtitle'] ?? '',
            'image_url' => (string) ($page['hero_image_url'] ?? ''),
            'cta_text'  => $page['hero_cta_text'] ?? '',
            'cta_url'   => $page['hero_cta_url'] ?? '',
            'theme'     => $page['hero_theme'] ?? 'dark',
        ]);
    }

    if (!empty($page['show_text_image_a'])) {
        echo \Wexoe\Core\Renderers\TextImage::render([
            'h2'        => $page['text_image_a_h2'] ?? '',
            'body'      => $page['text_image_a_body'] ?? '',
            'image_url' => (string) ($page['text_image_a_image_url'] ?? ''),
            'reversed'  => !empty($page['text_image_a_reversed']),
            'theme'     => $page['text_image_a_theme'] ?? 'light',
        ]);
    }

    if (!empty($page['show_text_image_b'])) {
        echo \Wexoe\Core\Renderers\TextImage::render([
            'h2'        => $page['text_image_b_h2'] ?? '',
            'body'      => $page['text_image_b_body'] ?? '',
            'image_url' => (string) ($page['text_image_b_image_url'] ?? ''),
            'reversed'  => !empty($page['text_image_b_reversed']),
            'theme'     => $page['text_image_b_theme'] ?? 'light',
        ]);
    }

    if (!empty($page['show_text_only'])) {
        echo \Wexoe\Core\Renderers\TextOnly::render([
            'h2'    => $page['text_only_h2'] ?? '',
            'body'  => $page['text_only_body'] ?? '',
            'align' => $page['text_only_align'] ?? 'left',
        ]);
    }

    if (!empty($page['show_faq'])) {
        echo \Wexoe\Core\Renderers\Faq::render([
            'h2'    => $page['faq_h2'] ?? '',
            'items' => $page['faq_items'] ?? [],
        ]);
    }

    if (!empty($page['show_team_grid'])) {
        echo \Wexoe\Core\Renderers\TeamGrid::render([
            'h2'    => $page['team_grid_h2'] ?? '',
            'scope' => wexoe_pages_resolve_scope($page, [
                'country'  => 'team_grid_scope_country',
                'division' => 'team_grid_scope_division',
                'limit'    => 'team_grid_limit',
            ]),
        ]);
    }

    if (!empty($page['show_partners_marquee'])) {
        echo \Wexoe\Core\Renderers\PartnersMarquee::render([
            'h2'    => $page['partners_marquee_h2'] ?? '',
            'scope' => wexoe_pages_resolve_scope($page, [
                'country'  => 'partners_marquee_scope_country',
                'division' => 'partners_marquee_scope_division',
            ]),
        ]);
    }

    if (!empty($page['show_testimonial_card'])) {
        echo \Wexoe\Core\Renderers\TestimonialCard::render([
            'scope' => wexoe_pages_resolve_scope($page, [
                'country'       => 'testimonial_scope_country',
                'division'      => 'testimonial_scope_division',
                'customer_type' => 'testimonial_scope_customer_type',
            ]),
        ]);
    }

    if (!empty($page['show_cta_banner'])) {
        echo \Wexoe\Core\Renderers\CtaBanner::render([
            'h2'       => $page['cta_banner_h2'] ?? '',
            'body'     => $page['cta_banner_body'] ?? '',
            'cta_text' => $page['cta_banner_cta_text'] ?? '',
            'cta_url'  => $page['cta_banner_cta_url'] ?? '',
            'theme'    => $page['cta_banner_theme'] ?? 'dark',
        ]);
    }

    // Contact form-sektionen.
    if (!empty($page['show_contact_form']) && class_exists('\\Wexoe\\Core\\Renderers\\ContactForm')) {
        echo '<section id="kontakt">';
        echo \Wexoe\Core\Renderers\ContactForm::render(wexoe_pages_build_contact_form_config($page));
        echo '</section>';
    }

    // Tredjepartsutökning.
    do_action('wexoe_pages_render_sections', $page);

    echo '</article>';
    return ob_get_clean();
}

/**
 * Sätt scope-array från sid-fält. Tomma scope-fält faller tillbaka på sidans
 * Country/Division-länkar.
 */
function wexoe_pages_resolve_scope($page, $field_map) {
    $scope = [];
    foreach ($field_map as $scope_key => $page_field) {
        $value = $page[$page_field] ?? '';
        if ($value !== '' && $value !== null) {
            $scope[$scope_key] = $value;
        }
    }
    // Fall tillbaka på sidans Country (via record-ID → kod).
    if (!isset($scope['country']) && !empty($page['country_ids'])) {
        $country_repo = \Wexoe\Core\Core::entity('core_countries');
        if ($country_repo !== null) {
            $records = $country_repo->find_by_ids($page['country_ids']);
            if (!empty($records) && !empty($records[0]['code'])) {
                $scope['country'] = $records[0]['code'];
            }
        }
    }
    // Fall tillbaka på sidans Division (via record-ID → slug).
    if (!isset($scope['division']) && !empty($page['division_ids'])) {
        $division_repo = \Wexoe\Core\Core::entity('core_divisions');
        if ($division_repo !== null) {
            $records = $division_repo->find_by_ids($page['division_ids']);
            if (!empty($records) && !empty($records[0]['slug'])) {
                $scope['division'] = $records[0]['slug'];
            }
        }
    }
    return $scope;
}

/**
 * Bygg ContactForm-renderer-config från cms_unique_pages-fält.
 */
function wexoe_pages_build_contact_form_config($page) {
    return [
        'eyebrow'        => $page['contact_form_eyebrow'] ?? '',
        'title'          => $page['contact_form_title'] ?? '',
        'subtitle'       => $page['contact_form_subtitle'] ?? '',
        'layout'         => $page['contact_form_layout'] ?? 'split',
        'theme'          => $page['contact_form_theme'] ?? 'dark',
        'show_company'   => $page['contact_form_show_company'] ?? true,
        'show_phone'     => $page['contact_form_show_phone'] ?? true,
        'show_dropdown'  => $page['contact_form_show_dropdown'] ?? true,
        'dropdown_label' => $page['contact_form_dropdown_label'] ?? '',
        'options'        => $page['contact_form_options'] ?? null,
        'cta_text'       => $page['contact_form_cta_text'] ?? '',
        'message_label'  => $page['contact_form_message_label'] ?? '',
        'trust_signals'  => $page['contact_form_trust_signals'] ?? null,
        'source_plugin'  => 'wexoe-pages',
        'page_slug'      => $page['slug'] ?? '',
        'contact_person' => !empty($page['contact_form_show_contact_person'])
            ? wexoe_pages_resolve_contact_person($page)
            : null,
    ];
}

/**
 * Slå upp första aktiva coworker baserat på sidans scope.
 */
function wexoe_pages_resolve_contact_person($page) {
    if (!class_exists('\\Wexoe\\Core\\Helpers\\Collections')) return null;
    $scope = wexoe_pages_resolve_scope($page, []) + ['limit' => 1];
    $matches = \Wexoe\Core\Helpers\Collections::coworkers_for_scope($scope);
    if (empty($matches)) return null;
    $c = $matches[0];
    return [
        'name'  => $c['full_name'] ?? '',
        'title' => $c['title'] ?? '',
        'email' => $c['email'] ?? '',
        'phone' => $c['phone'] ?? '',
        'image' => (string) ($c['image'] ?? ''),
    ];
}

/* ============================================================
   SEO META
   ============================================================ */

add_action('wp_head', 'wexoe_pages_seo_meta', 5);

function wexoe_pages_seo_meta() {
    if (!is_singular()) return;
    global $post;
    if (!$post || !is_object($post)) return;

    if (!preg_match('/\[wexoe_page\s+[^\]]*slug=["\']([^"\']+)["\']/i', $post->post_content, $m)) {
        return;
    }
    $slug = $m[1];

    if (!wexoe_pages_core_ready()) return;
    $repo = \Wexoe\Core\Core::entity('cms_unique_pages');
    if ($repo === null) return;
    $page = $repo->find_by('slug', $slug);
    if ($page === null || empty($page['is_published'])) return;

    $title = !empty($page['seo_title']) ? $page['seo_title'] : ($page['h1'] ?? '');
    $description = !empty($page['seo_description']) ? $page['seo_description'] : '';
    $og_image = !empty($page['og_image_url']) ? $page['og_image_url'] : '';

    if ($title !== '') {
        echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
    }
    if ($description !== '') {
        echo '<meta name="description" content="' . esc_attr($description) . '" />' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($description) . '" />' . "\n";
    }
    if ($og_image !== '') {
        echo '<meta property="og:image" content="' . esc_url($og_image) . '" />' . "\n";
    }
}

/* ============================================================
   HELPERS
   ============================================================ */

/**
 * I WP_DEBUG-läge: returnera HTML-kommentar för debugging.
 * I production: tom sträng.
 */
function wexoe_pages_debug_comment($msg) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        return '<!-- ' . esc_html($msg) . ' -->';
    }
    return '';
}
