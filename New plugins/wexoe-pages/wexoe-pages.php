<?php
/**
 * Plugin Name: Wexoe Pages
 * Plugin URI:  https://wexoe.se
 * Description: One-off informational pages (start, about, pillar). Composes polymorphic sections from cms_page_sections. Use [wexoe_page slug="..."]. Beroende: wexoe-core ≥ 0.9.0.
 * Version:     1.0.0
 * Author:      Wexoe Industry AB
 * Requires PHP: 7.4
 * Requires at least: 6.0
 */

if (!defined('ABSPATH')) exit;

define('WEXOE_PAGES_VERSION', '1.0.0');
define('WEXOE_PAGES_DIR', plugin_dir_path(__FILE__));

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
   SECTION TYPE → RENDERER FILE MAP
   ============================================================ */

/**
 * Map section_type singleSelect-värden till filer under sections/.
 * En typ utan entry här renderas inte (felmeddelande loggas via debug-kommentar).
 */
function wexoe_pages_section_renderers() {
    return [
        'hero'               => 'hero.php',
        'text_image'         => 'text-image.php',
        'text_only'          => 'text-only.php',
        'company_data_strip' => 'company-data-strip.php',
        'news_text_split'    => 'news-text-split.php',
        'case_grid'          => 'case-grid.php',
        'news_grid'          => 'news-grid.php',
        'catalog'            => 'catalog.php',
        'tabs'               => 'tabs.php',
        'team_grid'          => 'team-grid.php',
        'partner_list'       => 'partner-list.php',
        'faq'                => 'faq.php',
        'testimonial'        => 'testimonial.php',
        'cta_banner'         => 'cta-banner.php',
        'contact_form'       => 'contact-form.php',
    ];
}

/* ============================================================
   SHORTCODE
   ============================================================ */

add_shortcode('wexoe_page', 'wexoe_pages_shortcode');

function wexoe_pages_shortcode($atts) {
    $atts = shortcode_atts([
        'slug'  => '',
        'debug' => 'false',
    ], $atts, 'wexoe_page');

    $slug = trim((string) $atts['slug']);
    if ($slug === '') {
        return wexoe_pages_debug_comment('wexoe-pages: ingen slug angiven');
    }

    if (!wexoe_pages_core_ready()) {
        return wexoe_pages_debug_comment('wexoe-pages: wexoe-core är inte aktivt');
    }

    if ($atts['debug'] === 'true') {
        return wexoe_pages_render_debug($slug);
    }

    return wexoe_pages_render($slug);
}

/* ============================================================
   MAIN RENDER (also called directly by wexoe-alb-blocks)
   ============================================================ */

/**
 * Public — renderar en hel cms_pages-record till HTML.
 *
 * Anropas av shortcoden men också direkt av wexoe-alb-blocks via
 * wexoe_alb_render_unique_page() — håll signaturen stabil (string $slug → string).
 *
 * Tom sträng returneras om sidan inte finns eller inte är publicerad.
 *
 * @param string $slug
 * @return string
 */
function wexoe_pages_render($slug) {
    $page = wexoe_pages_load_page($slug);
    if ($page === null) {
        return wexoe_pages_debug_comment('wexoe-pages: hittade inte slug=' . esc_html($slug));
    }

    $sections = wexoe_pages_load_sections($page);
    $ctx = wexoe_pages_build_context($page);

    $wrapper_id = 'wxp-' . substr(preg_replace('/[^a-z0-9-]/', '', strtolower($slug)), 0, 12) . '-' . wp_generate_password(6, false, false);

    ob_start();
    echo '<article id="' . esc_attr($wrapper_id) . '" class="wxp wxp--' . esc_attr($ctx['page_theme']) . '">';

    // Topp-H1 visas bara om INGEN hero-sektion finns. Hero äger sin egen H1.
    $has_hero = false;
    foreach ($sections as $s) {
        if (($s['section_type'] ?? '') === 'hero') { $has_hero = true; break; }
    }
    if (!$has_hero && !empty($page['h1'])) {
        echo '<h1 class="wxp__h1">' . esc_html($page['h1']) . '</h1>';
    }

    foreach ($sections as $section) {
        $type = (string) ($section['section_type'] ?? '');
        $renderer = wexoe_pages_load_renderer($type);
        if ($renderer === null) {
            echo wexoe_pages_debug_comment('wexoe-pages: saknar renderer för section_type=' . esc_html($type));
            continue;
        }
        $html = (string) $renderer($section, $page, $ctx);
        if ($html !== '') {
            echo $html;
        }
    }

    // Tredjepart kan haka in extra sektioner (för testning eller add-on plugins).
    do_action('wexoe_pages_after_sections', $page, $sections, $ctx);

    echo '</article>';
    return ob_get_clean();
}

/**
 * Lazy-load + cache renderer-closure för en section_type.
 *
 * Returnerar null om typen saknar mapping eller filen inte returnerar callable.
 * Renderer-filen require:as max en gång per request — säkert att deklarera
 * top-level-funktioner inuti med `if (!function_exists(...))`-guard.
 *
 * @param string $type section_type-värde
 * @return callable|null
 */
function wexoe_pages_load_renderer($type) {
    static $cache = [];
    if (array_key_exists($type, $cache)) {
        return $cache[$type];
    }
    $renderers = wexoe_pages_section_renderers();
    if (!isset($renderers[$type])) {
        $cache[$type] = null;
        return null;
    }
    $file = WEXOE_PAGES_DIR . 'sections/' . $renderers[$type];
    if (!is_file($file)) {
        $cache[$type] = null;
        return null;
    }
    $renderer = require $file;
    $cache[$type] = is_callable($renderer) ? $renderer : null;
    return $cache[$type];
}

/* ============================================================
   DEBUG DUMP
   ============================================================ */

function wexoe_pages_render_debug($slug) {
    $page = wexoe_pages_load_page($slug);
    if ($page === null) {
        return '<pre>wexoe-pages: hittade inte slug=' . esc_html($slug) . '</pre>';
    }
    $sections = wexoe_pages_load_sections($page);
    $ctx = wexoe_pages_build_context($page);
    return '<pre style="overflow:auto;max-height:600px;background:#f4f4f4;padding:16px;">'
        . esc_html(print_r(['page' => $page, 'sections' => $sections, 'context' => $ctx], true))
        . '</pre>';
}

/* ============================================================
   PAGE + SECTIONS LOADING
   ============================================================ */

/**
 * @return array|null Normalized page record or null
 */
function wexoe_pages_load_page($slug) {
    $repo = \Wexoe\Core\Core::entity('cms_pages');
    if ($repo === null) return null;

    $page = $repo->find($slug);
    if ($page === null) return null;
    if (empty($page['is_published'])) return null;
    return $page;
}

/**
 * Returnerar sektion-records i ordningen de är länkade på sidan, filtrerade
 * på is_active=true. Saknad länk eller raderad section hoppas tyst över
 * (find_by_ids() städar bort dem).
 */
function wexoe_pages_load_sections($page) {
    $section_ids = isset($page['section_ids']) && is_array($page['section_ids']) ? $page['section_ids'] : [];
    if (empty($section_ids)) return [];

    $repo = \Wexoe\Core\Core::entity('cms_page_sections');
    if ($repo === null) return [];

    $all = $repo->find_by_ids($section_ids);
    return array_values(array_filter($all, function ($s) {
        return !empty($s['is_active']);
    }));
}

/* ============================================================
   PAGE CONTEXT (för scope-fallback från section till sida)
   ============================================================ */

/**
 * Bygg ett context-objekt med sidans country/division/theme. Sektioner
 * använder detta som fallback när deras egna scope-fält är tomma.
 *
 * Returnerar:
 *   page_country_code  string|null  — kortkod (SE, NO, ...) eller null
 *   page_division_slug string|null
 *   page_theme         string       — 'light' (default) eller 'dark'
 *   page_max_width     string       — 'narrow'|'normal'|'wide'|'full'
 */
function wexoe_pages_build_context($page) {
    $ctx = [
        'page_country_code'  => null,
        'page_division_slug' => null,
        'page_theme'         => (($page['page_theme'] ?? 'light') === 'dark') ? 'dark' : 'light',
        'page_max_width'     => in_array(($page['max_width'] ?? 'normal'), ['narrow', 'normal', 'wide', 'full'], true)
            ? $page['max_width']
            : 'normal',
    ];

    if (!empty($page['country_ids'])) {
        $country_repo = \Wexoe\Core\Core::entity('core_countries');
        if ($country_repo !== null) {
            $records = $country_repo->find_by_ids($page['country_ids']);
            if (!empty($records) && !empty($records[0]['code'])) {
                $ctx['page_country_code'] = strtoupper((string) $records[0]['code']);
            }
        }
    }

    if (!empty($page['division_ids'])) {
        $division_repo = \Wexoe\Core\Core::entity('core_divisions');
        if ($division_repo !== null) {
            $records = $division_repo->find_by_ids($page['division_ids']);
            if (!empty($records) && !empty($records[0]['slug'])) {
                $ctx['page_division_slug'] = (string) $records[0]['slug'];
            }
        }
    }

    return $ctx;
}

/* ============================================================
   SHARED HELPERS (för sektioner)
   ============================================================ */

/**
 * Bygg en scope-array som ärver från sidan när sektionens scope-fält är tomt.
 * Passar in i \Wexoe\Core\Helpers\Collections::*_for_scope().
 *
 * @param array       $section
 * @param array       $ctx        sidans context
 * @param array<string,string> $field_map  ['country' => 'pl_scope_country', ...]
 * @return array
 */
function wexoe_pages_resolve_scope($section, $ctx, $field_map) {
    $scope = [];
    foreach ($field_map as $scope_key => $section_field) {
        $value = $section[$section_field] ?? '';
        if ($value !== '' && $value !== null) {
            $scope[$scope_key] = $value;
        }
    }
    if (!isset($scope['country']) && !empty($ctx['page_country_code'])) {
        $scope['country'] = $ctx['page_country_code'];
    }
    if (!isset($scope['division']) && !empty($ctx['page_division_slug'])) {
        $scope['division'] = $ctx['page_division_slug'];
    }
    return $scope;
}

/**
 * Pin-then-scope: returnerar manuellt valda records först, sen scope-resultat
 * upp till `$limit` totalt. Duplikater (record-id matchar) tas bort.
 *
 * @param string[]     $manual_ids    Airtable record IDs (rec...) från ett link-fält
 * @param string       $entity_name   Entity för manuell-resolution (samma entitet som scope-resultatet)
 * @param callable     $scope_fetcher Callable som returnerar scoped records (call_user_func)
 * @param int          $limit         0 = obegränsat
 * @return array
 */
function wexoe_pages_pin_then_scope(array $manual_ids, $entity_name, callable $scope_fetcher, $limit = 0) {
    $result = [];
    $seen = [];

    if (!empty($manual_ids)) {
        $repo = \Wexoe\Core\Core::entity($entity_name);
        if ($repo !== null) {
            foreach ($repo->find_by_ids($manual_ids) as $rec) {
                $id = isset($rec['_record_id']) ? $rec['_record_id'] : null;
                if ($id === null || isset($seen[$id])) continue;
                if (isset($rec['is_active']) && $rec['is_active'] === false) continue;
                $result[] = $rec;
                $seen[$id] = true;
                if ($limit > 0 && count($result) >= $limit) {
                    return $result;
                }
            }
        }
    }

    $scoped = call_user_func($scope_fetcher);
    if (is_array($scoped)) {
        foreach ($scoped as $rec) {
            $id = isset($rec['_record_id']) ? $rec['_record_id'] : null;
            if ($id === null || isset($seen[$id])) continue;
            $result[] = $rec;
            $seen[$id] = true;
            if ($limit > 0 && count($result) >= $limit) break;
        }
    }

    return $result;
}

/**
 * Compute section-wrapping HTML attributes (class + id) from common fields.
 *
 * Returnerar string som ska gå in i <section ...>:
 *   class="wxp-section wxp-section--<type> wxp-section--theme-<theme> wxp-section--top-<pad> wxp-section--bot-<pad>"
 *   id="<anchor_id>" (om satt)
 */
function wexoe_pages_section_attrs($section, $ctx, $extra_class = '') {
    $type = preg_replace('/[^a-z_]/', '', (string) ($section['section_type'] ?? ''));
    $theme = ($section['theme'] ?? 'inherit');
    if ($theme === 'inherit' || !in_array($theme, ['light', 'dark'], true)) {
        $theme = $ctx['page_theme'];
    }
    $top = in_array(($section['top_padding'] ?? ''), ['none', 'sm', 'md', 'lg'], true) ? $section['top_padding'] : 'md';
    $bot = in_array(($section['bottom_padding'] ?? ''), ['none', 'sm', 'md', 'lg'], true) ? $section['bottom_padding'] : 'md';
    $layout = in_array(($section['layout'] ?? ''), ['contained', 'full_bleed', 'narrow'], true) ? $section['layout'] : 'contained';

    $classes = [
        'wxp-section',
        'wxp-section--' . $type,
        'wxp-section--theme-' . $theme,
        'wxp-section--top-' . $top,
        'wxp-section--bot-' . $bot,
        'wxp-section--layout-' . $layout,
    ];
    if ($extra_class !== '') $classes[] = $extra_class;

    $attrs = 'class="' . esc_attr(implode(' ', $classes)) . '"';
    if (!empty($section['anchor_id'])) {
        $attrs .= ' id="' . esc_attr($section['anchor_id']) . '"';
    }
    return $attrs;
}

/**
 * Resolva ett scope-värde (slug, country-code, eller record ID) till
 * Airtable record-ID för att kunna jämföra mot linked-record-fält.
 *
 * Returnerar null om scope-nyckeln är osatt eller inte går att resolva.
 * Stödda nycklar: 'country', 'division', 'customer_type'.
 */
function wexoe_pages_resolve_link_id_for_scope($scope, $key) {
    if (!isset($scope[$key]) || $scope[$key] === '' || $scope[$key] === null) return null;
    $v = (string) $scope[$key];

    // Redan ett record-ID (rec + 14 chars).
    if (strpos($v, 'rec') === 0 && strlen($v) === 17) return $v;

    $entity_map = [
        'country'       => ['entity' => 'core_countries',      'lookup' => 'code',  'transform' => 'strtoupper'],
        'division'      => ['entity' => 'core_divisions',      'lookup' => 'slug',  'transform' => 'strtolower'],
        'customer_type' => ['entity' => 'core_customer_types', 'lookup' => 'slug',  'transform' => 'strtolower'],
    ];
    if (!isset($entity_map[$key])) return null;

    $cfg = $entity_map[$key];
    $repo = \Wexoe\Core\Core::entity($cfg['entity']);
    if ($repo === null) return null;
    $lookup_value = call_user_func($cfg['transform'], $v);
    $rec = $repo->find_by($cfg['lookup'], $lookup_value);
    return ($rec !== null && isset($rec['_record_id'])) ? $rec['_record_id'] : null;
}

/**
 * Bestäm om en record matchar ett scope-target via ett linked-record-fält.
 *
 * Mirror av Collections::scope_link_matches: matchar om
 *   - target är null (filter avstängt), ELLER
 *   - record's link-array är tom (globalt synlig), ELLER
 *   - record's link-array innehåller target.
 */
function wexoe_pages_link_matches($record, $field, $target_record_id) {
    if ($target_record_id === null) return true;
    $ids = isset($record[$field]) && is_array($record[$field]) ? $record[$field] : [];
    if (empty($ids)) return true;
    return in_array($target_record_id, $ids, true);
}

/**
 * Markdown helpers — passa genom Core om tillgänglig, annars fallback till
 * nl2br(esc_html()) så att rendering aldrig kraschar i edge cases.
 */
function wexoe_pages_md($text) {
    $text = (string) $text;
    if ($text === '') return '';
    if (class_exists('\\Wexoe\\Core\\Helpers\\Markdown')) {
        return \Wexoe\Core\Helpers\Markdown::to_html($text);
    }
    return '<p>' . nl2br(esc_html($text)) . '</p>';
}

function wexoe_pages_md_inline($text) {
    $text = (string) $text;
    if ($text === '') return '';
    if (class_exists('\\Wexoe\\Core\\Helpers\\Markdown')) {
        return \Wexoe\Core\Helpers\Markdown::to_inline($text);
    }
    return esc_html($text);
}

/**
 * I WP_DEBUG-läge: returnera HTML-kommentar. Production: tom sträng.
 */
function wexoe_pages_debug_comment($msg) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        return '<!-- ' . esc_html($msg) . ' -->';
    }
    return '';
}

/* ============================================================
   GLOBAL CSS — emit once per page-render
   ============================================================
   Single inline stylesheet med basklasser som alla sektioner delar:
   layout-container, theme-färger, padding-skalor, basic typography.
   Sektion-specifik CSS bor i respektive sections/<type>.php.
*/

// Print BEFORE body så att section-inline-styles (i body) override:ar baset
// vid lika specificitet (source-order wins).
add_action('wp_head', 'wexoe_pages_print_base_styles', 8);

function wexoe_pages_print_base_styles() {
    static $printed = false;
    if ($printed) return;
    // Heuristik: bara printa om vi rendererat minst en wexoe_page på sidan.
    // Vi kan inte veta säkert utan att hooka in render-flödet — för
    // enkelhetens skull skickar vi alltid när shortcode finns någonstans.
    // Cost: ~2kB extra CSS som ignoreras om inte använt.
    if (!wexoe_pages_should_print_base_styles()) return;
    $printed = true;
    ?>
<style id="wxp-base">
.wxp { color: #1A1A1A; font-family: 'DM Sans', system-ui, -apple-system, BlinkMacSystemFont, sans-serif; }
.wxp--dark { color: #fff; background: #0A1A2E; }
.wxp__h1 { font-size: clamp(2rem, 4vw, 2.75rem); margin: 0 0 24px; padding: 24px 24px 0; }
.wxp-section { box-sizing: border-box; width: 100%; }
.wxp-section *, .wxp-section *::before, .wxp-section *::after { box-sizing: border-box; }
.wxp-section__inner { max-width: 1100px; margin: 0 auto; padding-left: 24px; padding-right: 24px; }
.wxp-section--layout-narrow .wxp-section__inner { max-width: 760px; }
.wxp-section--layout-full_bleed .wxp-section__inner { max-width: none; padding-left: 0; padding-right: 0; }
.wxp-section--top-none { padding-top: 0; }
.wxp-section--top-sm { padding-top: 24px; }
.wxp-section--top-md { padding-top: 64px; }
.wxp-section--top-lg { padding-top: 96px; }
.wxp-section--bot-none { padding-bottom: 0; }
.wxp-section--bot-sm { padding-bottom: 24px; }
.wxp-section--bot-md { padding-bottom: 64px; }
.wxp-section--bot-lg { padding-bottom: 96px; }
.wxp-section--theme-light { background: #fff; color: #1A1A1A; }
.wxp-section--theme-dark { background: #0A1A2E; color: #fff; }
.wxp-eyebrow { text-transform: uppercase; letter-spacing: 0.08em; font-size: 12px; opacity: 0.75; margin: 0 0 12px; font-weight: 500; }
.wxp-h2 { font-size: clamp(1.5rem, 3vw, 2rem); margin: 0 0 16px; font-weight: 600; line-height: 1.2; }
.wxp-body { font-size: 16px; line-height: 1.65; }
.wxp-body p:last-child { margin-bottom: 0; }
.wxp-btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 500; transition: transform 0.15s; line-height: 1.2; }
.wxp-btn:hover { transform: translateY(-1px); }
.wxp-btn--primary { background: #F28C28; color: #fff; }
.wxp-btn--primary:hover { background: #e07d1f; color: #fff; }
.wxp-btn--secondary { background: transparent; color: inherit; border: 2px solid currentColor; }
.wxp-section--theme-light .wxp-btn--secondary { color: #11325D; border-color: rgba(17,50,93,0.3); }
.wxp-actions { display: flex; flex-wrap: wrap; gap: 12px; }
@media (max-width: 720px) { .wxp-section--top-md { padding-top: 40px; } .wxp-section--bot-md { padding-bottom: 40px; } .wxp-section--top-lg { padding-top: 56px; } .wxp-section--bot-lg { padding-bottom: 56px; } }
</style>
    <?php
}

function wexoe_pages_should_print_base_styles() {
    if (!is_singular()) return false;
    global $post;
    if (!$post || !is_object($post)) return false;
    return strpos($post->post_content, '[wexoe_page') !== false
        || strpos($post->post_content, '[wexoe_content') !== false; // alb-blocks-wrapper
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
    $page = wexoe_pages_load_page($slug);
    if ($page === null) return;

    $title = !empty($page['seo_title']) ? $page['seo_title'] : ($page['h1'] ?? '');
    $description = (string) ($page['seo_description'] ?? '');
    $og_image = (string) ($page['og_image_url'] ?? '');

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
