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
    $ctx['wrapper_id'] = $wrapper_id;

    ob_start();
    echo '<article id="' . esc_attr($wrapper_id) . '" class="wxp">';

    // Scoped base styles — emitted INSIDE the wrapper so they come after the
    // WP-themes stylesheet in source order and win at equal specificity. The
    // `#wrapper_id` prefix gives them a higher specificity than bare class
    // selectors in the WP theme.
    echo wexoe_pages_scoped_base_styles($wrapper_id);

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
 * Bygg ett context-objekt med sidans country/division. Sektioner använder
 * detta som fallback när deras egna scope-fält är tomma.
 *
 * Returnerar:
 *   page_country_code  string|null  — kortkod (SE, NO, ...) eller null
 *   page_division_slug string|null
 */
function wexoe_pages_build_context($page) {
    $ctx = [
        'page_country_code'  => null,
        'page_division_slug' => null,
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
 * Compute section-wrapping HTML attributes (class + id) från common fields.
 *
 * Returnerar string som ska gå in i <section ...>:
 *   class="wxp-section wxp-section--<type> wxp-section--top-<pad> wxp-section--bot-<pad>
 *          wxp-section--layout-<layout> [wxp-section--custom-bg wxp-section--on-dark|--on-light]"
 *   style="background-color: <hex>; color: <fg>;" (om background_color satt)
 *   id="<anchor_id>" (om satt)
 */
function wexoe_pages_section_attrs($section, $ctx, $extra_class = '') {
    $type = preg_replace('/[^a-z_]/', '', (string) ($section['section_type'] ?? ''));
    $top = in_array(($section['top_padding'] ?? ''), ['none', 'sm', 'md', 'lg'], true) ? $section['top_padding'] : 'md';
    $bot = in_array(($section['bottom_padding'] ?? ''), ['none', 'sm', 'md', 'lg'], true) ? $section['bottom_padding'] : 'md';
    $layout = in_array(($section['layout'] ?? ''), ['contained', 'full_bleed', 'narrow'], true) ? $section['layout'] : 'contained';

    // background_color: inline-style på sektionen. Vi sätter samtidigt text-färg
    // (svart eller vit) baserat på luminance så body-text och eyebrows blir
    // läsbara, och vi lägger på .wxp-section--on-dark när bakgrunden är mörk
    // — sub-element (kort, listor, etc) kan reagera på den klassen för att
    // få "ljusa-på-mörk"-styling.
    $bg_color = null;
    $on_dark = false;
    $bg_raw = trim((string) ($section['background_color'] ?? ''));
    if ($bg_raw !== '' && class_exists('\\Wexoe\\Core\\Helpers\\Color')) {
        $bg_color = \Wexoe\Core\Helpers\Color::normalize_hex($bg_raw);
        if ($bg_color !== null) {
            $on_dark = \Wexoe\Core\Helpers\Color::is_dark($bg_color);
        }
    }

    $classes = [
        'wxp-section',
        'wxp-section--' . $type,
        'wxp-section--top-' . $top,
        'wxp-section--bot-' . $bot,
        'wxp-section--layout-' . $layout,
    ];
    if ($bg_color !== null) {
        $classes[] = 'wxp-section--custom-bg';
        $classes[] = $on_dark ? 'wxp-section--on-dark' : 'wxp-section--on-light';
    }
    if ($extra_class !== '') $classes[] = $extra_class;

    $attrs = 'class="' . esc_attr(implode(' ', $classes)) . '"';
    if ($bg_color !== null) {
        $text_color = $on_dark ? '#fff' : '#1A1A1A';
        $attrs .= ' style="background-color: ' . esc_attr($bg_color) . '; color: ' . esc_attr($text_color) . ';"';
    }
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
 * Plocka ut en preview-text för en WP post. Letar i ordning:
 *   1. Custom field "manchet" eller "ingress" (svensk journalistik-term)
 *      eller andra vanliga intro-fält.
 *   2. SEO-plugin meta description (Yoast / AIOSEO / Rank Math).
 *   3. WP-postens egna excerpt (post_excerpt), om manuellt satt.
 *   4. Sista fallback: strip-shortcodes + strip_tags på post_content
 *      och trunka till N ord. (the_excerpt-filtren undviker vi eftersom
 *      Enfold/Avia injicerar `[av_textblock …]`-shortcodes som inte
 *      expanderas i ren the_content-strängen.)
 */
function wexoe_pages_post_excerpt($post, $words = 22) {
    if (!$post || !is_object($post) || empty($post->ID)) return '';

    // 1. Custom field "manchet" / "ingress" / "intro".
    foreach (['manchet', 'ingress', 'intro', 'summary', 'preview_text', 'lead'] as $key) {
        $val = get_post_meta($post->ID, $key, true);
        if (is_string($val) && trim($val) !== '') {
            return wp_trim_words(strip_tags($val), $words);
        }
    }

    // 2. SEO-plugin meta description.
    foreach (['_yoast_wpseo_metadesc', '_aioseo_description', 'rank_math_description'] as $key) {
        $val = get_post_meta($post->ID, $key, true);
        if (is_string($val) && trim($val) !== '') {
            return wp_trim_words(strip_tags($val), $words);
        }
    }

    // 3. Manuellt satt WP post_excerpt (skiljer sig från get_the_excerpt
    //    eftersom det senare faller tillbaka till content via filter och
    //    blir spammad med Avia-shortcodes).
    if (!empty($post->post_excerpt)) {
        return wp_trim_words(strip_tags($post->post_excerpt), $words);
    }

    // 4. post_content med shortcodes strippade.
    $content = strip_shortcodes((string) $post->post_content);
    return wp_trim_words(strip_tags($content), $words);
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
   FONT PRELOAD — emit DM Sans link tags once per page
   ============================================================
   Other Wexoe plugins (landing, contact, customer-type, product-area)
   all load DM Sans via Google Fonts. We do the same so typography is
   consistent across plugins even when this is the only one on the page.
*/
add_action('wp_head', 'wexoe_pages_print_font_preload', 4);

function wexoe_pages_print_font_preload() {
    if (!wexoe_pages_should_print_base_styles()) return;
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
    echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap">' . "\n";
}

function wexoe_pages_should_print_base_styles() {
    if (!is_singular()) return false;
    global $post;
    if (!$post || !is_object($post)) return false;
    return strpos($post->post_content, '[wexoe_page') !== false
        || strpos($post->post_content, '[wexoe_content') !== false; // alb-blocks-wrapper
}

/* ============================================================
   SCOPED BASE STYLES — emitted INSIDE the wrapper article
   ============================================================
   All rules are scoped to `#<wrapper_id>` so theme styles cannot win on
   specificity. `!important` is used on properties the theme is known to
   override (h1/h2 color, list ::before markers, link color, button bg).
   The wrapper id is unique per render so this stays self-contained even
   if multiple wexoe_page shortcodes appear on the same WP page.
*/
function wexoe_pages_scoped_base_styles($id) {
    $w = '#' . $id;

    ob_start();
    ?>
<style id="<?= esc_attr($id) ?>-base">
/* ============ RESET ============ */
<?= $w ?> { font-family: 'DM Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important; color: #1A1A1A !important; background: #fff; line-height: 1.6 !important; box-sizing: border-box !important; }
<?= $w ?> *, <?= $w ?> *::before, <?= $w ?> *::after { box-sizing: border-box !important; }
/* Kill the theme's `li::before` markers (which were bleeding through as ✓ on FAQ/bullet lists).
   Do NOT touch list-style/padding/margin at this level — Markdown body copy renders <ul>/<ol>
   inside .wxp-body and needs native bullets. Component lists (.wxp-faq__list, .wxp-tg__grid, …)
   declare their own list-style:none reset where they want a bare list. */
<?= $w ?> li::before, <?= $w ?> ul li::before, <?= $w ?> ol li::before { content: none !important; display: none !important; background: none !important; }
<?= $w ?> strong, <?= $w ?> b, <?= $w ?> em, <?= $w ?> i { color: inherit !important; }
<?= $w ?> img { max-width: 100% !important; height: auto !important; }
<?= $w ?> a { text-decoration: none !important; color: inherit; }

/* ============ TYPOGRAPHY ============ */
<?= $w ?> .wxp__h1 { font-size: clamp(2rem, 4vw, 2.75rem) !important; font-weight: 700 !important; line-height: 1.15 !important; color: inherit !important; margin: 0 0 24px !important; padding: 24px 24px 0 !important; }
<?= $w ?> .wxp-eyebrow { display: inline-flex !important; align-items: center !important; gap: 10px !important; text-transform: uppercase !important; letter-spacing: 0.14em !important; font-size: 11px !important; font-weight: 600 !important; color: #5A6473 !important; margin: 0 0 16px !important; padding: 0 !important; background: none !important; }
<?= $w ?> .wxp-eyebrow::before { content: '' !important; display: inline-block !important; width: 22px !important; height: 1px !important; background: #5A6473 !important; flex-shrink: 0 !important; }
<?= $w ?> .wxp-section--on-dark .wxp-eyebrow { color: rgba(255,255,255,0.72) !important; }
<?= $w ?> .wxp-section--on-dark .wxp-eyebrow::before { background: rgba(255,255,255,0.72) !important; }
<?= $w ?> .wxp-h2 { font-size: clamp(1.75rem, 3.5vw, 2.4rem) !important; font-weight: 700 !important; line-height: 1.18 !important; color: #11325D !important; margin: 0 0 18px !important; padding: 0 !important; letter-spacing: -0.01em !important; background: none !important; }
<?= $w ?> .wxp-section--on-dark .wxp-h2, <?= $w ?> .wxp-section--on-dark.wxp-h2 { color: #fff !important; }
<?= $w ?> .wxp-body { font-size: 16px !important; line-height: 1.7 !important; color: inherit; }
<?= $w ?> .wxp-body p { margin: 0 0 14px !important; color: inherit; }
<?= $w ?> .wxp-body p:last-child { margin-bottom: 0 !important; }
<?= $w ?> .wxp-body a { color: #11325D !important; text-decoration: underline !important; }
<?= $w ?> .wxp-section--on-dark .wxp-body a { color: #F28C28 !important; }
/* Markdown body lists — restore native markers since the reset above is intentionally narrow. */
<?= $w ?> .wxp-body ul, <?= $w ?> .wxp-body ol { list-style: revert !important; padding-left: 1.4em !important; margin: 0 0 16px !important; }
<?= $w ?> .wxp-body ul li, <?= $w ?> .wxp-body ol li { list-style: inherit !important; padding-left: 0 !important; margin: 0 0 6px !important; background: none !important; }
<?= $w ?> .wxp-body ul:last-child, <?= $w ?> .wxp-body ol:last-child { margin-bottom: 0 !important; }

/* ============ SECTION LAYOUT ============ */
/* Alla sektioner sträcker sig 100vw — bryt ut ur WP-themes container så att
   bg-färger och bg-bilder täcker hela skärmens bredd. Inre content stannar
   centrerat inom max-width 1200px (eller 820px för narrow). */
<?= $w ?> .wxp-section { box-sizing: border-box !important; position: relative !important; width: 100vw !important; max-width: 100vw !important; margin-left: calc(50% - 50vw) !important; margin-right: calc(50% - 50vw) !important; }
<?= $w ?> .wxp-section__inner { width: 100% !important; max-width: 1200px !important; margin: 0 auto !important; padding-left: 24px !important; padding-right: 24px !important; }
<?= $w ?> .wxp-section--layout-narrow .wxp-section__inner { max-width: 820px !important; }
<?= $w ?> .wxp-section--layout-full_bleed .wxp-section__inner { max-width: none !important; padding-left: 0 !important; padding-right: 0 !important; }
<?= $w ?> .wxp-section--top-none { padding-top: 0 !important; }
<?= $w ?> .wxp-section--top-sm { padding-top: 32px !important; }
<?= $w ?> .wxp-section--top-md { padding-top: 72px !important; }
<?= $w ?> .wxp-section--top-lg { padding-top: 112px !important; }
<?= $w ?> .wxp-section--bot-none { padding-bottom: 0 !important; }
<?= $w ?> .wxp-section--bot-sm { padding-bottom: 32px !important; }
<?= $w ?> .wxp-section--bot-md { padding-bottom: 72px !important; }
<?= $w ?> .wxp-section--bot-lg { padding-bottom: 112px !important; }

/* Bakåtkompatibel klass — alla sektioner är full-bleed by default sedan ovan,
   så .wxp-fullbleed är no-op men kvarstår för existerande markup (hero). */
<?= $w ?> .wxp-fullbleed { width: 100vw !important; margin-left: calc(-50vw + 50%) !important; margin-right: calc(-50vw + 50%) !important; }

/* ============ BUTTONS ============ */
<?= $w ?> .wxp-actions { display: flex !important; flex-wrap: wrap !important; gap: 12px !important; margin: 0 !important; padding: 0 !important; }
<?= $w ?> .wxp-btn { display: inline-flex !important; align-items: center !important; justify-content: center !important; gap: 8px !important; padding: 14px 28px !important; border-radius: 2px !important; text-decoration: none !important; font-weight: 600 !important; font-size: 15px !important; line-height: 1.2 !important; transition: all 0.2s ease !important; border: 2px solid transparent !important; cursor: pointer !important; white-space: nowrap !important; box-shadow: none !important; text-shadow: none !important; }
<?= $w ?> .wxp-btn--primary { background: #F28C28 !important; color: #fff !important; border-color: #F28C28 !important; }
<?= $w ?> .wxp-btn--primary:hover { background: #e07b1a !important; border-color: #e07b1a !important; color: #fff !important; transform: translateY(-1px) !important; box-shadow: 0 6px 16px rgba(242,140,40,0.28) !important; }
<?= $w ?> .wxp-btn--secondary { background: transparent !important; color: #11325D !important; border-color: rgba(17,50,93,0.28) !important; }
<?= $w ?> .wxp-btn--secondary:hover { border-color: #11325D !important; color: #11325D !important; transform: translateY(-1px) !important; }
/* Secondary-knapp på mörk bakgrund: vit fyllning med navy text (inte
   transparent). Gäller alla mörka kontexter — på-mörk-bg-fältet, hero
   (alla varianter har dark bg) och cta-banner-kortet. */
<?= $w ?> .wxp-section--on-dark .wxp-btn--secondary,
<?= $w ?> .wxp-hero .wxp-btn--secondary,
<?= $w ?> .wxp-cta__card .wxp-btn--secondary { background: #fff !important; color: #11325D !important; border-color: #fff !important; }
<?= $w ?> .wxp-section--on-dark .wxp-btn--secondary:hover,
<?= $w ?> .wxp-hero .wxp-btn--secondary:hover,
<?= $w ?> .wxp-cta__card .wxp-btn--secondary:hover { background: rgba(255,255,255,0.88) !important; color: #11325D !important; border-color: #fff !important; }

/* ============ RESPONSIVE ============ */
@media (max-width: 900px) {
    <?= $w ?> .wxp-section--top-md { padding-top: 48px !important; }
    <?= $w ?> .wxp-section--bot-md { padding-bottom: 48px !important; }
    <?= $w ?> .wxp-section--top-lg { padding-top: 64px !important; }
    <?= $w ?> .wxp-section--bot-lg { padding-bottom: 64px !important; }
    <?= $w ?> .wxp-section__inner { padding-left: 20px !important; padding-right: 20px !important; }
}
@media (max-width: 600px) {
    <?= $w ?> .wxp-btn { padding: 13px 22px !important; font-size: 14px !important; }
}
</style>
    <?php
    return ob_get_clean();
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
