<?php
/**
 * Plugin Name: Wexoe Landing Page
 * Description: Modular, high-converting landing pages driven by Wexoe Core data. Use [wexoe_landing slug="fjarraccess"] to render.
 * Version: 2.0.0-test
 * Author: Wexoe
 * Text Domain: wexoe-landing-page
 */

if (!defined('ABSPATH')) exit;

/* ============================================================
   CORE CHECK
   ============================================================ */

if (!function_exists('wexoe_lp_test_core_ready')) {
function wexoe_lp_test_core_ready() {
    return class_exists('\\Wexoe\\Core\\Core')
        && method_exists('\\Wexoe\\Core\\Core', 'entity');
}
}

/* ============================================================
   HELPERS
   ============================================================ */

if (!function_exists('wexoe_lp_test_field')) {
function wexoe_lp_test_field($data, $field, $default = '') {
    return isset($data[$field]) && $data[$field] !== '' && $data[$field] !== null ? $data[$field] : $default;
}
}

if (!function_exists('wexoe_lp_test_lines_to_array')) {
function wexoe_lp_test_lines_to_array($text) {
    if (is_array($text)) {
        return array_values(array_filter(array_map('trim', $text), function($l) { return $l !== ''; }));
    }
    return \Wexoe\Core\Helpers\Lines::to_array((string) $text);
}
}

if (!function_exists('wexoe_lp_test_md')) {
function wexoe_lp_test_md($text) {
    return \Wexoe\Core\Helpers\Markdown::to_inline((string) $text);
}
}

if (!function_exists('wexoe_lp_test_hex')) {
function wexoe_lp_test_hex($value, $default) {
    return \Wexoe\Core\Helpers\Color::normalize_hex((string) $value) ?: $default;
}
}

if (!function_exists('wexoe_lp_test_youtube_id')) {
function wexoe_lp_test_youtube_id($url) {
    return \Wexoe\Core\Helpers\YouTube::extract_id((string) $url);
}
}

/**
 * Parse FAQ text field into array of Q&A pairs
 * Format: Q: Question\nA: Answer\n\nQ: Question 2\nA: Answer 2
 */
function wexoe_lp_test_parse_faq($text) {
    if (empty($text)) return [];
    $blocks = preg_split('/\n\s*\n/', $text);
    $faqs = [];
    foreach ($blocks as $block) {
        $block = trim($block);
        if (empty($block)) continue;
        $q = ''; $a = '';
        $lines = preg_split('/\r\n|\r|\n/', $block);
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^Q:\s*(.+)$/i', $line, $m)) {
                $q = $m[1];
            } elseif (preg_match('/^A:\s*(.+)$/i', $line, $m)) {
                $a = $m[1];
            }
        }
        if ($q) $faqs[] = ['q' => $q, 'a' => $a];
    }
    return $faqs;
}

/**
 * Parse compare rows: "Label | Value A | Value B" per line
 */
function wexoe_lp_test_parse_compare_rows($text) {
    if (empty($text)) return [];
    $lines = wexoe_lp_test_lines_to_array($text);
    $rows = [];
    foreach ($lines as $line) {
        $parts = array_map('trim', explode('|', $line));
        if (count($parts) >= 3) {
            $rows[] = ['label' => $parts[0], 'a' => $parts[1], 'b' => $parts[2]];
        }
    }
    return $rows;
}

/**
 * Parse steps: "Title | Description" per line
 */
function wexoe_lp_test_parse_steps($text) {
    if (empty($text)) return [];
    $lines = wexoe_lp_test_lines_to_array($text);
    $steps = [];
    foreach ($lines as $line) {
        $parts = array_map('trim', explode('|', $line, 2));
        $steps[] = ['title' => $parts[0], 'desc' => isset($parts[1]) ? $parts[1] : ''];
    }
    return $steps;
}

/**
 * Render compare cell value — replace ✓/✕ with styled icons
 */
function wexoe_lp_test_compare_cell($val) {
    $val = esc_html($val);
    $val = str_replace('✓', '<span class="wexoe-lp-cmp-check">✓</span>', $val);
    $val = str_replace('✕', '<span class="wexoe-lp-cmp-cross">✕</span>', $val);
    return $val;
}

function wexoe_lp_test_get_ids($data, $field) {
    return (isset($data[$field]) && is_array($data[$field])) ? $data[$field] : [];
}

/* ============================================================
   SECTION RENDERERS
   ============================================================ */

/**
 * HERO
 */
function wexoe_lp_test_render_hero($data, $id) {
    $h1 = wexoe_lp_test_field($data, 'h1', '');
    if (empty($h1)) return '';

    $desc = wexoe_lp_test_field($data, 'hero_description', '');
    $image = wexoe_lp_test_field($data, 'hero_image', '');
    $cta1_text = wexoe_lp_test_field($data, 'hero_cta_text', 'Kontakta oss');
    $cta1_url = wexoe_lp_test_field($data, 'hero_cta_url', '/kontakt/');
    $cta2_text = wexoe_lp_test_field($data, 'hero_cta2_text', '');
    $cta2_url = wexoe_lp_test_field($data, 'hero_cta2_url', '');

    $html = '<section class="wexoe-lp-hero wexoe-lp-fullwidth">';
    $html .= '<div class="wexoe-lp-hero-shapes"><div class="wexoe-lp-hero-shape1"></div><div class="wexoe-lp-hero-shape2"></div></div>';
    $html .= '<div class="wexoe-lp-hero-text">';
    $html .= '<h1 class="wexoe-lp-hero-h1">'.esc_html($h1).'</h1>';
    if ($desc) $html .= '<p class="wexoe-lp-hero-desc">'.wexoe_lp_test_md($desc).'</p>';
    $html .= '<div class="wexoe-lp-hero-buttons">';
    $html .= '<a href="'.esc_url($cta1_url).'" class="wexoe-lp-btn-primary">'.esc_html($cta1_text).' <span>&rarr;</span></a>';
    if ($cta2_text && $cta2_url) {
        $html .= '<a href="'.esc_url($cta2_url).'" class="wexoe-lp-btn-secondary">'.esc_html($cta2_text).' <span>&rarr;</span></a>';
    }
    $html .= '</div></div>';
    if ($image) {
        $html .= '<div class="wexoe-lp-hero-image-wrapper"><img src="'.esc_url($image).'" alt="'.esc_attr($h1).'" loading="eager"/></div>';
    }
    $html .= '</section>';
    return $html;
}

/**
 * CONTENT + SIDEBAR
 */
function wexoe_lp_test_render_content_sidebar($data, $id) {
    $show_content = wexoe_lp_test_field($data, 'show_content', true);
    $show_sidebar = wexoe_lp_test_field($data, 'show_sidebar', true);
    $sidebar_type = wexoe_lp_test_field($data, 'sidebar_type', '');

    $h2 = wexoe_lp_test_field($data, 'content_h2', '');
    $text = wexoe_lp_test_field($data, 'content_text', '');
    $benefits = wexoe_lp_test_lines_to_array(wexoe_lp_test_field($data, 'content_benefits', ''));

    $has_content = !empty($h2) || !empty($text);
    $has_sidebar = !empty($sidebar_type);

    if (!$has_content && !$has_sidebar) return '';
    if (!$show_content && !$show_sidebar) return '';

    $html = '<section class="wexoe-lp-content-section">';
    $html .= '<div class="wexoe-lp-container">';
    $html .= '<div class="wexoe-lp-content-grid'.(!$has_sidebar ? ' wexoe-lp-no-sidebar' : '').'">';

    // Main content
    if ($has_content && $show_content) {
        $html .= '<div class="wexoe-lp-content-main">';
        if ($h2) $html .= '<h2 class="wexoe-lp-content-h2">'.esc_html($h2).'</h2>';
        if ($text) $html .= '<div class="wexoe-lp-content-text">'.wexoe_lp_test_md($text).'</div>';
        if (!empty($benefits)) {
            $html .= '<ul class="wexoe-lp-benefit-list">';
            foreach ($benefits as $b) $html .= '<li><span class="wexoe-lp-check">&#10003;</span> '.wexoe_lp_test_md($b).'</li>';
            $html .= '</ul>';
        }
        $html .= '</div>';
    }

    // Sidebar
    if ($has_sidebar && $show_sidebar) {
        $html .= '<div class="wexoe-lp-sidebar">';
        switch ($sidebar_type) {
            case 'case': $html .= wexoe_lp_test_render_sidebar_case($data, $id); break;
            case 'calculator': $html .= wexoe_lp_test_render_sidebar_calculator($data, $id); break;
            case 'event': $html .= wexoe_lp_test_render_sidebar_event($data, $id); break;
            case 'leadmagnet': $html .= wexoe_lp_test_render_sidebar_leadmagnet($data, $id); break;
        }
        $html .= '</div>';
    }

    $html .= '</div></div></section>';
    return $html;
}

function wexoe_lp_test_render_sidebar_case($data, $id) {
    $title = wexoe_lp_test_field($data, 'case_title', '');
    $desc = wexoe_lp_test_field($data, 'case_description', '');
    $image = wexoe_lp_test_field($data, 'case_image', '');
    $outcomes = wexoe_lp_test_lines_to_array(wexoe_lp_test_field($data, 'case_outcomes', ''));
    $cta_text = wexoe_lp_test_field($data, 'case_cta_text', '');
    $cta_url = wexoe_lp_test_field($data, 'case_cta_url', '');

    $html = '<div class="wexoe-lp-sb-badge wexoe-lp-sb-badge-text">KUNDCASE</div>';
    if ($title) $html .= '<h3 class="wexoe-lp-sb-title">'.esc_html($title).'</h3>';
    if ($image) $html .= '<img class="wexoe-lp-sb-case-img" src="'.esc_url($image).'" alt="'.esc_attr($title).'" loading="lazy"/>';
    if ($desc) $html .= '<p class="wexoe-lp-sb-desc">'.wexoe_lp_test_md($desc).'</p>';
    foreach ($outcomes as $o) {
        $html .= '<div class="wexoe-lp-sb-outcome"><svg width="18" height="18" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="10" fill="#10B981" opacity="0.15"/><path d="M6 10.5L8.5 13L14 7.5" stroke="#10B981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><span>'.wexoe_lp_test_md($o).'</span></div>';
    }
    if ($cta_text && $cta_url) {
        $html .= '<a href="'.esc_url($cta_url).'" class="wexoe-lp-sb-cta-link">'.esc_html($cta_text).' &rarr;</a>';
    }
    return $html;
}

function wexoe_lp_test_render_sidebar_calculator($data, $id) {
    $title = wexoe_lp_test_field($data, 'calc_title', '');
    $calc_html = wexoe_lp_test_field($data, 'calc_html', '');

    $html = '<div class="wexoe-lp-sb-badge wexoe-lp-sb-badge-text">BERÄKNA</div>';
    if ($title) $html .= '<h3 class="wexoe-lp-sb-title">'.esc_html($title).'</h3>';
    $html .= '<div class="wexoe-lp-sb-calc-canvas" id="'.$id.'-calc">'.$calc_html.'</div>';
    return $html;
}

function wexoe_lp_test_render_sidebar_event($data, $id) {
    $type = wexoe_lp_test_field($data, 'event_type', '');
    $title = wexoe_lp_test_field($data, 'event_title', '');
    $desc = wexoe_lp_test_field($data, 'event_description', '');
    $date = wexoe_lp_test_field($data, 'event_date', '');
    $location = wexoe_lp_test_field($data, 'event_location', '');
    $webhook = wexoe_lp_test_field($data, 'event_webhook', '');

    $html = '';
    if ($type) $html .= '<div class="wexoe-lp-sb-event-type">'.esc_html($type).'</div>';
    if ($title) $html .= '<h3 class="wexoe-lp-sb-title">'.esc_html($title).'</h3>';
    if ($desc) $html .= '<p class="wexoe-lp-sb-desc">'.wexoe_lp_test_md($desc).'</p>';

    $html .= '<div class="wexoe-lp-sb-event-meta">';
    if ($date) $html .= '<div class="wexoe-lp-sb-meta-item"><svg width="16" height="16" fill="none" viewBox="0 0 16 16"><rect x="1" y="2" width="14" height="13" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M1 6h14" stroke="currentColor" stroke-width="1.5"/><path d="M5 1v2M11 1v2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg><span>'.esc_html($date).'</span></div>';
    if ($location) $html .= '<div class="wexoe-lp-sb-meta-item"><svg width="16" height="16" fill="none" viewBox="0 0 16 16"><circle cx="8" cy="7" r="2.5" stroke="currentColor" stroke-width="1.5"/><path d="M8 15S2 9.5 2 6.5a6 6 0 1112 0C14 9.5 8 15 8 15z" stroke="currentColor" stroke-width="1.5"/></svg><span>'.esc_html($location).'</span></div>';
    $html .= '</div>';

    $html .= '<hr class="wexoe-lp-sb-divider"/>';
    $html .= '<div class="wexoe-lp-sb-form" data-type="event" data-webhook="'.esc_attr($webhook).'">';
    $html .= '<input type="email" class="wexoe-lp-sb-input" placeholder="Din e-postadress" required/>';
    $html .= '<button type="button" class="wexoe-lp-sb-submit wexoe-lp-sb-submit-orange">Anmäl mig &rarr;</button>';
    $html .= '<div class="wexoe-lp-sb-msg"></div>';
    $html .= '</div>';
    return $html;
}

function wexoe_lp_test_render_sidebar_leadmagnet($data, $id) {
    $title = wexoe_lp_test_field($data, 'magnet_title', '');
    $format = wexoe_lp_test_field($data, 'magnet_format', '');
    $desc = wexoe_lp_test_field($data, 'magnet_description', '');
    $file_url = wexoe_lp_test_field($data, 'magnet_file_url', '');
    $webhook = wexoe_lp_test_field($data, 'magnet_webhook', '');

    $html = '<div class="wexoe-lp-sb-badge wexoe-lp-sb-badge-text" style="color:var(--lp-main) !important;">GRATIS RESURS</div>';
    $html .= '<div class="wexoe-lp-sb-magnet-asset">';
    $html .= '<div class="wexoe-lp-sb-magnet-icon"><svg width="24" height="24" fill="none" viewBox="0 0 24 24"><path d="M12 2L6 7v10a2 2 0 002 2h8a2 2 0 002-2V7l-6-5z" stroke="#fff" stroke-width="1.5"/><path d="M9 13h6M9 16h4" stroke="#fff" stroke-width="1.5" stroke-linecap="round"/></svg></div>';
    $html .= '<div class="wexoe-lp-sb-magnet-info">';
    if ($title) $html .= '<h4>'.esc_html($title).'</h4>';
    if ($format) $html .= '<p>'.esc_html($format).'</p>';
    $html .= '</div></div>';
    if ($desc) $html .= '<p class="wexoe-lp-sb-desc">'.wexoe_lp_test_md($desc).'</p>';

    $html .= '<div class="wexoe-lp-sb-form" data-type="leadmagnet" data-webhook="'.esc_attr($webhook).'" data-file="'.esc_attr($file_url).'">';
    $html .= '<input type="email" class="wexoe-lp-sb-input" placeholder="Din e-postadress" required/>';
    $html .= '<label class="wexoe-lp-sb-consent"><input type="checkbox" class="wexoe-lp-sb-checkbox"/><span>Jag godkänner att Wexoe skickar mig relevanta nyheter och erbjudanden via e-post.</span></label>';
    $html .= '<button type="button" class="wexoe-lp-sb-submit wexoe-lp-sb-submit-orange">Ladda ner &rarr;</button>';
    $html .= '<div class="wexoe-lp-sb-msg"></div>';
    $html .= '</div>';
    return $html;
}

/**
 * TABS
 */
function wexoe_lp_test_render_tabs($tabs, $downloads_map, $data, $id) {
    if (empty($tabs)) return '';
    $show = wexoe_lp_test_field($data, 'show_tabs', true);
    if (!$show) return '';

    // Pill bar
    $html = '<section class="wexoe-lp-tabs-wrapper wexoe-lp-fullwidth">';
    $html .= '<div class="wexoe-lp-tabs-bar">';
    $html .= '<div class="wexoe-lp-tabs-nav">';
    foreach ($tabs as $i => $tab) {
        $active = ($i === 0) ? ' wexoe-lp-tab-active' : '';
        $html .= '<button class="wexoe-lp-tab-btn'.$active.'" data-tab="'.$id.'-tab-'.$i.'">'.esc_html(wexoe_lp_test_field($tab, 'name', 'Tab '.($i+1))).'</button>';
    }
    $html .= '</div></div>';

    // Content panels
    $html .= '<div class="wexoe-lp-tabs-content">';
    $html .= '<div class="wexoe-lp-container">';
    foreach ($tabs as $i => $tab) {
        $active = ($i === 0) ? ' wexoe-lp-tab-panel-active' : '';
        $type = wexoe_lp_test_field($tab, 'tab_type', '');
        $html .= '<div class="wexoe-lp-tab-panel'.$active.'" id="'.$id.'-tab-'.$i.'">';
        switch ($type) {
            case 'textimage': $html .= wexoe_lp_test_render_tab_textimage($tab, $id); break;
            case 'fullmedia': $html .= wexoe_lp_test_render_tab_fullmedia($tab, $id); break;
            case 'faq': $html .= wexoe_lp_test_render_tab_faq($tab, $id); break;
            case 'calameo': $html .= wexoe_lp_test_render_tab_calameo($tab, $id); break;
            case 'downloads':
                $dl_ids = wexoe_lp_test_get_ids($tab, 'download_ids');
                $dls = isset($downloads_map[$tab['_record_id']]) ? $downloads_map[$tab['_record_id']] : [];
                $html .= wexoe_lp_test_render_tab_downloads($tab, $dls, $id);
                break;
            case 'compare': $html .= wexoe_lp_test_render_tab_compare($tab, $id); break;
            case 'steps': $html .= wexoe_lp_test_render_tab_steps($tab, $id); break;
        }
        $html .= '</div>';
    }
    $html .= '</div></div></section>';
    return $html;
}

function wexoe_lp_test_render_tab_textimage($tab, $id) {
    $h2 = wexoe_lp_test_field($tab, 'ti_h2', '');
    $text = wexoe_lp_test_field($tab, 'ti_text', '');
    $benefits = wexoe_lp_test_lines_to_array(wexoe_lp_test_field($tab, 'ti_benefits', ''));
    $image = wexoe_lp_test_field($tab, 'ti_image', '');
    $inverted = !empty($tab['ti_inverted']);

    $html = '<div class="wexoe-lp-tab-textimg'.($inverted ? ' wexoe-lp-tab-textimg-inv' : '').'">';
    $html .= '<div class="wexoe-lp-tab-textimg-text">';
    if ($h2) $html .= '<h3>'.esc_html($h2).'</h3>';
    if ($text) $html .= '<div class="wexoe-lp-tab-ti-body">'.wexoe_lp_test_md($text).'</div>';
    if (!empty($benefits)) {
        $html .= '<ul class="wexoe-lp-tab-ti-list">';
        foreach ($benefits as $b) $html .= '<li><span class="wexoe-lp-check">&#10003;</span> '.wexoe_lp_test_md($b).'</li>';
        $html .= '</ul>';
    }
    $html .= '</div>';
    if ($image) {
        $html .= '<div class="wexoe-lp-tab-textimg-img"><img src="'.esc_url($image).'" alt="'.esc_attr($h2).'" loading="lazy"/></div>';
    }
    $html .= '</div>';
    return $html;
}

function wexoe_lp_test_render_tab_fullmedia($tab, $id) {
    $url = wexoe_lp_test_field($tab, 'fm_url', '');
    if (empty($url)) return '';

    $yt_id = wexoe_lp_test_youtube_id($url);
    $html = '<div class="wexoe-lp-tab-fullmedia">';
    if ($yt_id) {
        $html .= '<div class="wexoe-lp-tab-video-wrap"><iframe src="https://www.youtube-nocookie.com/embed/'.$yt_id.'?rel=0" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe></div>';
    } else {
        $html .= '<img src="'.esc_url($url).'" alt="" loading="lazy"/>';
    }
    $html .= '</div>';
    return $html;
}

function wexoe_lp_test_render_tab_faq($tab, $id) {
    $faqs = wexoe_lp_test_parse_faq(wexoe_lp_test_field($tab, 'faq_items', ''));
    if (empty($faqs)) return '';

    $html = '<div class="wexoe-lp-tab-faq">';
    foreach ($faqs as $i => $faq) {
        $open = ($i === 0) ? ' wexoe-lp-faq-open' : '';
        $html .= '<div class="wexoe-lp-faq-item'.$open.'">';
        $html .= '<div class="wexoe-lp-faq-q"><span>'.esc_html($faq['q']).'</span><svg class="wexoe-lp-faq-chevron" width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M5 8l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>';
        $html .= '<div class="wexoe-lp-faq-a"><div class="wexoe-lp-faq-a-inner">'.wexoe_lp_test_md($faq['a']).'</div></div>';
        $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
}

function wexoe_lp_test_render_tab_calameo($tab, $id) {
    $html = '<div class="wexoe-lp-tab-calameo">';
    $calameos = wexoe_lp_test_field($tab, 'calameos', []);
    foreach ($calameos as $calameo) {
        $title = wexoe_lp_test_field($calameo, 'title', '');
        $src = wexoe_lp_test_field($calameo, 'src', '');
        if (empty($src)) continue;
        $html .= '<div class="wexoe-lp-calameo-block">';
        if ($title) $html .= '<h4>'.esc_html($title).'</h4>';
        $html .= '<iframe src="'.esc_url($src).'" width="100%" height="180" frameborder="0" allowfullscreen allowtransparency scrolling="no" loading="lazy"></iframe>';
        $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
}

function wexoe_lp_test_render_tab_downloads($tab, $downloads, $id) {
    if (empty($downloads)) return '<p style="text-align:center;opacity:0.6;">Inga nedladdningar kopplade.</p>';

    $html = '<div class="wexoe-lp-tab-downloads">';
    $html .= '<div class="wexoe-lp-dl-grid">';
    foreach ($downloads as $dl) {
        $name = wexoe_lp_test_field($dl, 'name', '');
        $desc = wexoe_lp_test_field($dl, 'description', '');
        $thumb = wexoe_lp_test_field($dl, 'thumbnail', '');
        $file = wexoe_lp_test_field($dl, 'file_url', '');
        $btn = wexoe_lp_test_field($dl, 'button_text', 'Ladda ner PDF');

        $html .= '<div class="wexoe-lp-dl-card">';
        if ($thumb) $html .= '<img class="wexoe-lp-dl-thumb" src="'.esc_url($thumb).'" alt="'.esc_attr($name).'" loading="lazy"/>';
        $html .= '<div class="wexoe-lp-dl-body">';
        $html .= '<h4>'.esc_html($name).'</h4>';
        if ($desc) $html .= '<p>'.esc_html($desc).'</p>';
        if ($file) $html .= '<a href="'.esc_url($file).'" class="wexoe-lp-dl-btn" download><svg width="14" height="14" fill="none" viewBox="0 0 16 16"><path d="M8 2v9M4 8l4 4 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 14h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg> '.esc_html($btn).'</a>';
        $html .= '</div></div>';
    }
    $html .= '</div></div>';
    return $html;
}

function wexoe_lp_test_render_tab_compare($tab, $id) {
    $title = wexoe_lp_test_field($tab, 'compare_title', '');
    $col_a = wexoe_lp_test_field($tab, 'compare_col_a', 'A');
    $col_b = wexoe_lp_test_field($tab, 'compare_col_b', 'B');
    $rows = wexoe_lp_test_parse_compare_rows(wexoe_lp_test_field($tab, 'compare_rows', ''));
    if (empty($rows)) return '';

    $html = '<div class="wexoe-lp-tab-compare">';
    if ($title) $html .= '<h3>'.esc_html($title).'</h3>';
    $html .= '<div class="wexoe-lp-cmp-scroll"><table class="wexoe-lp-cmp-table">';
    $html .= '<thead><tr><th></th><th>'.esc_html($col_a).'</th><th class="wexoe-lp-cmp-highlight">'.esc_html($col_b).'</th></tr></thead>';
    $html .= '<tbody>';
    foreach ($rows as $row) {
        $html .= '<tr><td>'.esc_html($row['label']).'</td><td>'.wexoe_lp_test_compare_cell($row['a']).'</td><td class="wexoe-lp-cmp-highlight">'.wexoe_lp_test_compare_cell($row['b']).'</td></tr>';
    }
    $html .= '</tbody></table></div></div>';
    return $html;
}

function wexoe_lp_test_render_tab_steps($tab, $id) {
    $title = wexoe_lp_test_field($tab, 'steps_title', '');
    $steps = wexoe_lp_test_parse_steps(wexoe_lp_test_field($tab, 'steps', ''));
    if (empty($steps)) return '';

    $html = '<div class="wexoe-lp-tab-steps">';
    if ($title) $html .= '<h3>'.esc_html($title).'</h3>';
    $html .= '<div class="wexoe-lp-steps-track">';
    foreach ($steps as $i => $step) {
        $html .= '<div class="wexoe-lp-step">';
        $html .= '<div class="wexoe-lp-step-num">'.($i+1).'</div>';
        $html .= '<h4>'.esc_html($step['title']).'</h4>';
        if ($step['desc']) $html .= '<p>'.esc_html($step['desc']).'</p>';
        $html .= '</div>';
    }
    $html .= '</div></div>';
    return $html;
}

/**
 * CONTACT PERSON
 */
function wexoe_lp_test_render_contact($data, $id) {
    $name = wexoe_lp_test_field($data, 'contact_name', '');
    if (empty($name)) return '';
    $show = wexoe_lp_test_field($data, 'show_contact', true);
    if (!$show) return '';

    $title = wexoe_lp_test_field($data, 'contact_title', '');
    $email = wexoe_lp_test_field($data, 'contact_email', '');
    $phone = wexoe_lp_test_field($data, 'contact_phone', '');
    $image = wexoe_lp_test_field($data, 'contact_image', '');
    $quote = wexoe_lp_test_field($data, 'contact_quote', '');

    $html = '<section class="wexoe-lp-contact-section">';
    $html .= '<div class="wexoe-lp-container">';
    $html .= '<div class="wexoe-lp-contact-card">';
    $html .= '<div class="wexoe-lp-contact-top">';
    $html .= '<div class="wexoe-lp-contact-photo">';
    if ($image) $html .= '<img src="'.esc_url($image).'" alt="'.esc_attr($name).'" loading="lazy"/>';
    $html .= '</div>';
    $html .= '<div class="wexoe-lp-contact-info">';
    $html .= '<h3>'.esc_html($name).'</h3>';
    if ($title) $html .= '<div class="wexoe-lp-contact-title-text">'.esc_html($title).'</div>';
    if ($email) $html .= '<a href="mailto:'.esc_attr($email).'">'.esc_html($email).'</a>';
    if ($phone) $html .= '<a href="tel:'.esc_attr(preg_replace('/\s/', '', $phone)).'">'.esc_html($phone).'</a>';
    $html .= '</div></div>';
    if ($quote) {
        $html .= '<div class="wexoe-lp-contact-quote">';
        $html .= '<span class="wexoe-lp-quote-mark wexoe-lp-quote-open">&ldquo;</span>';
        $html .= esc_html($quote);
        $html .= '<span class="wexoe-lp-quote-mark wexoe-lp-quote-close">&rdquo;</span>';
        $html .= '</div>';
    }
    $html .= '</div></div></section>';
    return $html;
}

/* ============================================================
   CSS
   ============================================================ */

function wexoe_lp_test_render_css($id, $main_color, $secondary_color) {
    $mc = $main_color;
    $sc = $secondary_color;

    return '
    <style>
    @import url("https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap");

    /* === RESET === */
    #'.$id.' { font-family: "DM Sans", system-ui, -apple-system, sans-serif !important; line-height: 1.6 !important; box-sizing: border-box !important; color: #333 !important; --lp-main: '.$mc.' !important; --lp-secondary: '.$sc.' !important; --lp-green: #10B981 !important; --lp-light-bg: #F5F6F8 !important; }
    #'.$id.' *, #'.$id.' *::before, #'.$id.' *::after { box-sizing: border-box !important; }
    #'.$id.' li::before { content: none !important; display: none !important; }
    #'.$id.' img { max-width: 100% !important; height: auto !important; border-radius: 0 !important; }
    #'.$id.' a { text-decoration: none !important; color: inherit !important; }

    /* === CONTAINER === */
    #'.$id.' .wexoe-lp-container { max-width: 1100px !important; margin: 0 auto !important; padding: 0 32px !important; }
    #'.$id.' .wexoe-lp-fullwidth { width: 100vw !important; margin-left: calc(-50vw + 50%) !important; }

    /* === HERO === */
    #'.$id.' .wexoe-lp-hero { background: var(--lp-main) !important; color: #fff !important; position: relative !important; overflow: hidden !important; min-height: 440px !important; }
    #'.$id.' .wexoe-lp-hero-text { position: relative !important; z-index: 10 !important; max-width: 480px !important; padding: 72px 0 !important; margin-left: max(32px, calc((100% - 1100px) / 2 + 32px)) !important; }
    #'.$id.' .wexoe-lp-hero-h1 { font-size: 2.2rem !important; font-weight: 700 !important; line-height: 1.2 !important; margin-bottom: 16px !important; color: #fff !important; }
    #'.$id.' .wexoe-lp-hero-desc { font-size: 1rem !important; line-height: 1.7 !important; opacity: 0.9 !important; margin-bottom: 28px !important; color: #fff !important; }
    #'.$id.' .wexoe-lp-hero-desc br { display: inline !important; }
    #'.$id.' .wexoe-lp-hero-buttons { display: flex !important; gap: 12px !important; flex-wrap: wrap !important; }
    #'.$id.' .wexoe-lp-btn-primary { display: inline-flex !important; align-items: center !important; gap: 8px !important; background: var(--lp-secondary) !important; color: #fff !important; padding: 13px 28px !important; border-radius: 4px !important; font-weight: 600 !important; font-size: 0.95rem !important; transition: background 0.2s !important; }
    #'.$id.' .wexoe-lp-btn-primary:hover { background: #e07b1a !important; }
    #'.$id.' .wexoe-lp-btn-secondary { display: inline-flex !important; align-items: center !important; gap: 8px !important; background: transparent !important; color: #fff !important; padding: 13px 28px !important; border-radius: 4px !important; font-weight: 600 !important; font-size: 0.95rem !important; border: 2px solid rgba(255,255,255,0.4) !important; transition: border-color 0.2s !important; }
    #'.$id.' .wexoe-lp-btn-secondary:hover { border-color: #fff !important; }

    /* Hero image diagonal */
    #'.$id.' .wexoe-lp-hero-image-wrapper { position: absolute !important; top: 0 !important; right: 0 !important; width: 55% !important; height: 100% !important; z-index: 5 !important; overflow: hidden !important; }
    #'.$id.' .wexoe-lp-hero-image-wrapper img { position: absolute !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; object-fit: cover !important; object-position: center !important; border-radius: 0 !important; }
    #'.$id.' .wexoe-lp-hero-image-wrapper::before { content: "" !important; position: absolute !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; background: var(--lp-main) !important; clip-path: polygon(0 0, 25% 0, 0 100%, 0 100%) !important; z-index: 2 !important; }
    #'.$id.' .wexoe-lp-hero-image-wrapper::after { content: "" !important; position: absolute !important; top: 0 !important; left: 0 !important; width: 35% !important; height: 100% !important; background: linear-gradient(90deg, var(--lp-main) 0%, transparent 100%) !important; z-index: 3 !important; }
    #'.$id.' .wexoe-lp-hero-shapes { position: absolute !important; inset: 0 !important; z-index: 1 !important; pointer-events: none !important; }
    #'.$id.' .wexoe-lp-hero-shape1 { position: absolute !important; width: 280px !important; height: 280px !important; background: rgba(255,255,255,0.025) !important; border-radius: 50% !important; top: -60px !important; left: 15% !important; }
    #'.$id.' .wexoe-lp-hero-shape2 { position: absolute !important; width: 180px !important; height: 180px !important; border: 2px solid rgba(255,255,255,0.035) !important; transform: rotate(45deg) !important; bottom: -40px !important; left: 8% !important; }

    /* === CONTENT + SIDEBAR === */
    #'.$id.' .wexoe-lp-content-section { padding: 64px 0 56px !important; }
    #'.$id.' .wexoe-lp-content-grid { display: flex !important; gap: 48px !important; align-items: flex-start !important; }
    #'.$id.' .wexoe-lp-content-grid.wexoe-lp-no-sidebar { max-width: 700px !important; }
    #'.$id.' .wexoe-lp-content-main { flex: 1 1 55% !important; }
    #'.$id.' .wexoe-lp-content-h2 { font-size: 1.7rem !important; font-weight: 700 !important; color: var(--lp-main) !important; margin-bottom: 16px !important; line-height: 1.3 !important; }
    #'.$id.' .wexoe-lp-content-text { margin-bottom: 16px !important; line-height: 1.7 !important; }
    #'.$id.' .wexoe-lp-content-text br { display: inline !important; }
    #'.$id.' .wexoe-lp-benefit-list { list-style: none !important; margin: 20px 0 0 !important; padding: 0 !important; }
    #'.$id.' .wexoe-lp-benefit-list li { position: relative !important; padding-left: 28px !important; margin-bottom: 10px !important; font-weight: 500 !important; list-style: none !important; background: none !important; }
    #'.$id.' .wexoe-lp-check { color: var(--lp-green) !important; font-weight: 700 !important; position: absolute !important; left: 0 !important; }

    /* === SIDEBAR === */
    #'.$id.' .wexoe-lp-sidebar { flex: 1 1 40% !important; background: var(--lp-light-bg) !important; border-radius: 10px !important; padding: 32px 28px !important; }
    #'.$id.' .wexoe-lp-sb-badge-text { font-size: 0.72rem !important; font-weight: 700 !important; text-transform: uppercase !important; letter-spacing: 1.2px !important; color: var(--lp-secondary) !important; margin-bottom: 14px !important; }
    #'.$id.' .wexoe-lp-sb-badge-filled { display: none !important; }
    #'.$id.' .wexoe-lp-sb-title { font-size: 1.2rem !important; font-weight: 700 !important; color: var(--lp-main) !important; line-height: 1.3 !important; margin-bottom: 12px !important; }
    #'.$id.' .wexoe-lp-sb-desc { font-size: 0.9rem !important; color: #555 !important; line-height: 1.65 !important; margin-bottom: 18px !important; }
    #'.$id.' .wexoe-lp-sb-case-img { width: 100% !important; border-radius: 6px !important; margin-bottom: 18px !important; object-fit: cover !important; max-height: 160px !important; }
    #'.$id.' .wexoe-lp-sb-outcome { display: flex !important; align-items: flex-start !important; gap: 8px !important; margin-bottom: 10px !important; font-size: 0.88rem !important; color: var(--lp-main) !important; }
    #'.$id.' .wexoe-lp-sb-outcome svg { flex-shrink: 0 !important; margin-top: 2px !important; }
    #'.$id.' .wexoe-lp-sb-cta-link { display: inline-flex !important; align-items: center !important; gap: 6px !important; font-size: 0.9rem !important; font-weight: 600 !important; color: var(--lp-main) !important; margin-top: 16px !important; padding-bottom: 1px !important; border-bottom: 2px solid transparent !important; transition: border-color 0.2s !important; }
    #'.$id.' .wexoe-lp-sb-cta-link:hover { border-color: var(--lp-main) !important; }
    #'.$id.' .wexoe-lp-sb-event-type { font-size: 0.78rem !important; font-weight: 600 !important; color: #888 !important; text-transform: uppercase !important; letter-spacing: 0.5px !important; margin-bottom: 4px !important; }
    #'.$id.' .wexoe-lp-sb-event-meta { display: flex !important; flex-direction: column !important; gap: 8px !important; margin-bottom: 20px !important; }
    #'.$id.' .wexoe-lp-sb-meta-item { display: flex !important; align-items: center !important; gap: 10px !important; font-size: 0.88rem !important; color: var(--lp-main) !important; }
    #'.$id.' .wexoe-lp-sb-meta-item svg { flex-shrink: 0 !important; opacity: 0.6 !important; }
    #'.$id.' .wexoe-lp-sb-divider { border: none !important; border-top: 1px solid #ddd !important; margin: 0 0 18px !important; }
    #'.$id.' .wexoe-lp-sb-input { width: 100% !important; padding: 11px 14px !important; border: 1px solid #ccc !important; border-radius: 6px !important; font-size: 0.9rem !important; font-family: "DM Sans", sans-serif !important; outline: none !important; transition: border-color 0.2s !important; background: #fff !important; color: #333 !important; }
    #'.$id.' .wexoe-lp-sb-input:focus { border-color: var(--lp-main) !important; }
    #'.$id.' .wexoe-lp-sb-submit { width: 100% !important; padding: 12px !important; border: none !important; border-radius: 6px !important; color: #fff !important; font-weight: 600 !important; font-size: 0.92rem !important; font-family: "DM Sans", sans-serif !important; cursor: pointer !important; transition: background 0.2s !important; margin-top: 10px !important; text-align: center !important; }
    #'.$id.' .wexoe-lp-sb-submit-orange { background: var(--lp-secondary) !important; }
    #'.$id.' .wexoe-lp-sb-submit-orange:hover { background: #e07b1a !important; }
    #'.$id.' .wexoe-lp-sb-submit-dark { background: var(--lp-main) !important; }
    #'.$id.' .wexoe-lp-sb-submit-dark:hover { background: #0A1F3B !important; }
    #'.$id.' .wexoe-lp-sb-consent { display: flex !important; align-items: flex-start !important; gap: 8px !important; font-size: 0.78rem !important; color: #888 !important; line-height: 1.4 !important; margin-top: 10px !important; cursor: pointer !important; }
    #'.$id.' .wexoe-lp-sb-checkbox { margin-top: 2px !important; accent-color: var(--lp-main) !important; }
    #'.$id.' .wexoe-lp-sb-msg { font-size: 0.85rem !important; margin-top: 10px !important; text-align: center !important; }
    #'.$id.' .wexoe-lp-sb-calc-canvas { background: #fff !important; border: 1px solid #e0e0e0 !important; border-radius: 8px !important; padding: 20px !important; min-height: 120px !important; }
    #'.$id.' .wexoe-lp-sb-magnet-asset { display: flex !important; align-items: center !important; gap: 16px !important; background: #fff !important; border: 1px solid #e0e0e0 !important; border-radius: 8px !important; padding: 16px !important; margin-bottom: 18px !important; }
    #'.$id.' .wexoe-lp-sb-magnet-icon { width: 52px !important; height: 52px !important; background: var(--lp-main) !important; border-radius: 8px !important; display: flex !important; align-items: center !important; justify-content: center !important; flex-shrink: 0 !important; }
    #'.$id.' .wexoe-lp-sb-magnet-info h4 { font-size: 0.95rem !important; font-weight: 700 !important; color: var(--lp-main) !important; margin-bottom: 2px !important; }
    #'.$id.' .wexoe-lp-sb-magnet-info p { font-size: 0.8rem !important; color: #888 !important; margin: 0 !important; }

    /* === TABS === */
    #'.$id.' .wexoe-lp-tabs-wrapper { position: relative !important; }
    #'.$id.' .wexoe-lp-tabs-bar { display: flex !important; justify-content: center !important; position: relative !important; z-index: 10 !important; margin-bottom: -22px !important; }
    #'.$id.' .wexoe-lp-tabs-nav { display: inline-flex !important; gap: 3px !important; background: #D8DBE2 !important; border-radius: 50px !important; padding: 4px !important; box-shadow: 0 2px 12px rgba(0,0,0,0.06) !important; width: auto !important; max-width: fit-content !important; flex-shrink: 0 !important; }
    #'.$id.' .wexoe-lp-tab-btn { padding: 10px 24px !important; font-family: "DM Sans", sans-serif !important; font-size: 14px !important; font-weight: 600 !important; border: none !important; background: transparent !important; color: #5A6577 !important; cursor: pointer !important; border-radius: 50px !important; transition: all 0.25s ease !important; white-space: nowrap !important; line-height: 1.3 !important; width: auto !important; min-width: 0 !important; max-width: none !important; display: inline-block !important; float: none !important; text-align: center !important; background-image: none !important; box-shadow: none !important; margin: 0 !important; letter-spacing: 0 !important; text-transform: none !important; }
    #'.$id.' .wexoe-lp-tab-btn:hover:not(.wexoe-lp-tab-active) { color: var(--lp-main) !important; background: rgba(255,255,255,0.45) !important; background-image: none !important; }
    #'.$id.' .wexoe-lp-tab-active { background: #fff !important; color: var(--lp-main) !important; box-shadow: 0 1px 6px rgba(0,0,0,0.1) !important; background-image: none !important; }
    #'.$id.' .wexoe-lp-tabs-content { background: var(--lp-main) !important; color: #fff !important; padding: 64px 0 56px !important; }
    #'.$id.' .wexoe-lp-tab-panel { display: none !important; }
    #'.$id.' .wexoe-lp-tab-panel-active { display: block !important; }

    /* Tab: Text + Image */
    #'.$id.' .wexoe-lp-tab-textimg { display: flex !important; gap: 48px !important; align-items: center !important; }
    #'.$id.' .wexoe-lp-tab-textimg-inv { flex-direction: row-reverse !important; }
    #'.$id.' .wexoe-lp-tab-textimg-text { flex: 1 1 50% !important; }
    #'.$id.' .wexoe-lp-tab-textimg-text h3 { font-size: 1.5rem !important; font-weight: 700 !important; margin-bottom: 14px !important; color: #fff !important; }
    #'.$id.' .wexoe-lp-tab-ti-body { opacity: 0.9 !important; line-height: 1.7 !important; margin-bottom: 16px !important; }
    #'.$id.' .wexoe-lp-tab-ti-list { list-style: none !important; padding: 0 !important; margin: 0 !important; }
    #'.$id.' .wexoe-lp-tab-ti-list li { position: relative !important; padding-left: 24px !important; margin-bottom: 8px !important; font-size: 0.92rem !important; list-style: none !important; background: none !important; }
    #'.$id.' .wexoe-lp-tab-textimg-img { flex: 1 1 50% !important; }
    #'.$id.' .wexoe-lp-tab-textimg-img img { width: 100% !important; border-radius: 8px !important; object-fit: cover !important; max-height: 340px !important; }

    /* Tab: Full media */
    #'.$id.' .wexoe-lp-tab-fullmedia { text-align: center !important; max-width: 880px !important; margin: 0 auto !important; }
    #'.$id.' .wexoe-lp-tab-fullmedia img { width: 100% !important; border-radius: 8px !important; }
    #'.$id.' .wexoe-lp-tab-video-wrap { position: relative !important; padding-bottom: 56.25% !important; height: 0 !important; border-radius: 8px !important; overflow: hidden !important; }
    #'.$id.' .wexoe-lp-tab-video-wrap iframe { position: absolute !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; border: none !important; }

    /* Tab: FAQ */
    #'.$id.' .wexoe-lp-tab-faq { max-width: 780px !important; margin: 0 auto !important; }
    #'.$id.' .wexoe-lp-faq-item { border-bottom: 1px solid rgba(255,255,255,0.12) !important; }
    #'.$id.' .wexoe-lp-faq-item:last-child { border-bottom: none !important; }
    #'.$id.' .wexoe-lp-faq-q { display: flex !important; justify-content: space-between !important; align-items: center !important; padding: 20px 0 !important; cursor: pointer !important; font-size: 1.05rem !important; font-weight: 600 !important; gap: 16px !important; color: #fff !important; transition: color 0.2s !important; }
    #'.$id.' .wexoe-lp-faq-q:hover { color: var(--lp-secondary) !important; }
    #'.$id.' .wexoe-lp-faq-chevron { width: 20px !important; height: 20px !important; flex-shrink: 0 !important; transition: transform 0.3s ease !important; }
    #'.$id.' .wexoe-lp-faq-open .wexoe-lp-faq-chevron { transform: rotate(180deg) !important; }
    #'.$id.' .wexoe-lp-faq-a { max-height: 0 !important; overflow: hidden !important; transition: max-height 0.3s ease !important; }
    #'.$id.' .wexoe-lp-faq-open .wexoe-lp-faq-a { max-height: 500px !important; }
    #'.$id.' .wexoe-lp-faq-a-inner { padding-bottom: 20px !important; font-size: 0.92rem !important; opacity: 0.85 !important; line-height: 1.7 !important; }

    /* Tab: Calameo */
    #'.$id.' .wexoe-lp-tab-calameo { max-width: 900px !important; margin: 0 auto !important; }
    #'.$id.' .wexoe-lp-calameo-block { margin-bottom: 36px !important; }
    #'.$id.' .wexoe-lp-calameo-block:last-child { margin-bottom: 0 !important; }
    #'.$id.' .wexoe-lp-calameo-block h4 { font-size: 1.05rem !important; font-weight: 600 !important; margin-bottom: 14px !important; color: #fff !important; }
    #'.$id.' .wexoe-lp-calameo-block iframe { border-radius: 8px !important; }

    /* Tab: Downloads */
    #'.$id.' .wexoe-lp-tab-downloads { max-width: 900px !important; margin: 0 auto !important; }
    #'.$id.' .wexoe-lp-dl-grid { display: grid !important; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)) !important; gap: 20px !important; }
    #'.$id.' .wexoe-lp-dl-card { background: rgba(255,255,255,0.06) !important; border: 1px solid rgba(255,255,255,0.1) !important; border-radius: 10px !important; overflow: hidden !important; transition: transform 0.2s, box-shadow 0.2s !important; display: flex !important; flex-direction: column !important; }
    #'.$id.' .wexoe-lp-dl-card:hover { transform: translateY(-3px) !important; box-shadow: 0 8px 24px rgba(0,0,0,0.2) !important; }
    #'.$id.' .wexoe-lp-dl-thumb { width: 100% !important; height: 140px !important; object-fit: cover !important; display: block !important; border-radius: 0 !important; flex-shrink: 0 !important; }
    #'.$id.' .wexoe-lp-dl-body { padding: 18px 16px !important; display: flex !important; flex-direction: column !important; flex: 1 !important; }
    #'.$id.' .wexoe-lp-dl-body h4 { font-size: 0.95rem !important; font-weight: 700 !important; margin-bottom: 6px !important; color: #fff !important; }
    #'.$id.' .wexoe-lp-dl-body p { font-size: 0.82rem !important; color: #94A3C0 !important; line-height: 1.5 !important; margin-bottom: 14px !important; flex: 1 !important; }
    #'.$id.' .wexoe-lp-dl-btn { display: inline-flex !important; align-items: center !important; gap: 6px !important; background: var(--lp-secondary) !important; color: #fff !important; padding: 8px 16px !important; border-radius: 4px !important; font-size: 0.82rem !important; font-weight: 600 !important; transition: background 0.2s !important; }
    #'.$id.' .wexoe-lp-dl-btn:hover { background: #e07b1a !important; }

    /* Tab: Compare — white card on dark bg, clean hierarchy */
    #'.$id.' .wexoe-lp-tab-compare { max-width: 820px !important; margin: 0 auto !important; }
    #'.$id.' .wexoe-lp-tab-compare h3 { font-size: 1.3rem !important; font-weight: 700 !important; margin-bottom: 24px !important; text-align: center !important; color: #fff !important; }
    #'.$id.' .wexoe-lp-cmp-scroll { overflow-x: auto !important; background: #fff !important; background-color: #fff !important; border-radius: 12px !important; box-shadow: 0 4px 24px rgba(0,0,0,0.12) !important; }
    #'.$id.' .wexoe-lp-cmp-table { width: 100% !important; border-collapse: collapse !important; border-spacing: 0 !important; background: #fff !important; background-color: #fff !important; border: none !important; font-size: 14px !important; border-radius: 12px !important; overflow: hidden !important; }
    #'.$id.' .wexoe-lp-cmp-table thead { background: #fff !important; background-color: #fff !important; }
    #'.$id.' .wexoe-lp-cmp-table tbody { background: #fff !important; background-color: #fff !important; }
    #'.$id.' .wexoe-lp-cmp-table tr { background: #fff !important; background-color: #fff !important; border: none !important; }
    /* Zebra striping */
    #'.$id.' .wexoe-lp-cmp-table tbody tr:nth-child(even) td { background-color: #F8F9FB !important; }
    /* Header */
    #'.$id.' .wexoe-lp-cmp-table thead th { padding: 18px 24px !important; font-size: 12px !important; font-weight: 700 !important; text-align: left !important; border: none !important; border-bottom: 2px solid #E5E7EB !important; text-transform: uppercase !important; letter-spacing: 1px !important; color: #9CA3AF !important; background: #fff !important; background-color: #fff !important; background-image: none !important; }
    /* Highlight column header */
    #'.$id.' .wexoe-lp-cmp-table thead th.wexoe-lp-cmp-highlight { color: var(--lp-main) !important; background: #F0FDF4 !important; background-color: #F0FDF4 !important; border-bottom: 2px solid #BBF7D0 !important; }
    /* Body cells */
    #'.$id.' .wexoe-lp-cmp-table tbody td { padding: 16px 24px !important; font-size: 14px !important; border: none !important; border-bottom: 1px solid #F3F4F6 !important; vertical-align: middle !important; color: #6B7280 !important; background-image: none !important; line-height: 1.5 !important; background: #fff !important; background-color: #fff !important; }
    /* Label column */
    #'.$id.' .wexoe-lp-cmp-table tbody td:first-child { font-weight: 600 !important; color: #374151 !important; font-size: 14px !important; }
    /* Highlight column — soft green tint */
    #'.$id.' .wexoe-lp-cmp-table tbody td.wexoe-lp-cmp-highlight { background: #F0FDF4 !important; background-color: #F0FDF4 !important; color: #111827 !important; font-weight: 600 !important; }
    /* Zebra override for highlight */
    #'.$id.' .wexoe-lp-cmp-table tbody tr:nth-child(even) td.wexoe-lp-cmp-highlight { background-color: #ECFCE5 !important; background: #ECFCE5 !important; }
    #'.$id.' .wexoe-lp-cmp-table tbody tr:last-child td { border-bottom: none !important; border-bottom-width: 0 !important; }
    #'.$id.' .wexoe-lp-cmp-table { border-bottom: none !important; }
    #'.$id.' .wexoe-lp-cmp-scroll { border-bottom: none !important; }
    #'.$id.' .wexoe-lp-cmp-check { color: #16A34A !important; font-weight: 700 !important; font-size: 15px !important; }
    #'.$id.' .wexoe-lp-cmp-cross { color: #DC2626 !important; font-size: 15px !important; }

    /* Tab: Steps */
    #'.$id.' .wexoe-lp-tab-steps { max-width: 900px !important; margin: 0 auto !important; }
    #'.$id.' .wexoe-lp-tab-steps > h3 { font-size: 1.3rem !important; font-weight: 700 !important; margin-bottom: 36px !important; text-align: center !important; color: #fff !important; }
    #'.$id.' .wexoe-lp-steps-track { display: flex !important; gap: 0 !important; position: relative !important; }
    #'.$id.' .wexoe-lp-steps-track::before { content: "" !important; position: absolute !important; top: 28px !important; left: 40px !important; right: 40px !important; height: 2px !important; background: #1E3F6E !important; }
    #'.$id.' .wexoe-lp-step { flex: 1 !important; text-align: center !important; position: relative !important; padding: 0 12px !important; }
    #'.$id.' .wexoe-lp-step-num { width: 56px !important; height: 56px !important; border-radius: 50% !important; background: #1E3F6E !important; border: 2px solid #2A5080 !important; display: flex !important; align-items: center !important; justify-content: center !important; font-size: 1.2rem !important; font-weight: 700 !important; margin: 0 auto 16px !important; position: relative !important; z-index: 2 !important; color: #fff !important; transition: all 0.3s !important; }
    #'.$id.' .wexoe-lp-step:hover .wexoe-lp-step-num { background: var(--lp-secondary) !important; border-color: var(--lp-secondary) !important; }
    #'.$id.' .wexoe-lp-step h4 { font-size: 0.95rem !important; font-weight: 700 !important; margin-bottom: 8px !important; color: #fff !important; }
    #'.$id.' .wexoe-lp-step p { font-size: 0.82rem !important; line-height: 1.5 !important; color: #94A3C0 !important; }

    /* === CONTACT === */
    #'.$id.' .wexoe-lp-contact-section { padding: 56px 0 !important; background: #fff !important; }
    #'.$id.' .wexoe-lp-contact-card { display: flex !important; align-items: center !important; gap: 40px !important; max-width: 900px !important; margin: 0 auto !important; }
    #'.$id.' .wexoe-lp-contact-top { display: contents !important; }
    #'.$id.' .wexoe-lp-contact-photo { width: 110px !important; height: 110px !important; border-radius: 50% !important; background: #E8E8E8 !important; flex-shrink: 0 !important; overflow: hidden !important; border: 3px solid #E8E8E8 !important; }
    #'.$id.' .wexoe-lp-contact-photo img { width: 100% !important; height: 100% !important; object-fit: cover !important; border-radius: 50% !important; }
    #'.$id.' .wexoe-lp-contact-info h3 { font-size: 1.2rem !important; font-weight: 700 !important; color: var(--lp-main) !important; margin-bottom: 2px !important; }
    #'.$id.' .wexoe-lp-contact-title-text { font-size: 0.9rem !important; color: #666 !important; margin-bottom: 6px !important; }
    #'.$id.' .wexoe-lp-contact-info a { display: block !important; font-size: 0.88rem !important; color: var(--lp-main) !important; }
    #'.$id.' .wexoe-lp-contact-info a:hover { text-decoration: underline !important; }
    #'.$id.' .wexoe-lp-contact-quote { flex: 1 !important; font-size: 1.1rem !important; font-style: italic !important; color: var(--lp-main) !important; line-height: 1.6 !important; padding-left: 32px !important; border-left: 3px solid var(--lp-secondary) !important; }
    #'.$id.' .wexoe-lp-quote-mark { display: none !important; }

    /* === RESPONSIVE: TABLET === */
    @media (max-width: 989px) {
        #'.$id.' .wexoe-lp-content-grid { flex-direction: column !important; }
        #'.$id.' .wexoe-lp-content-main { order: 1 !important; }
        #'.$id.' .wexoe-lp-sidebar { order: 2 !important; }
        #'.$id.' .wexoe-lp-tab-textimg, #'.$id.' .wexoe-lp-tab-textimg-inv { flex-direction: column !important; }
        #'.$id.' .wexoe-lp-steps-track { flex-direction: column !important; gap: 24px !important; align-items: center !important; }
        #'.$id.' .wexoe-lp-steps-track::before { display: none !important; }
        #'.$id.' .wexoe-lp-step { max-width: 400px !important; }
        #'.$id.' .wexoe-lp-contact-card { flex-direction: column !important; text-align: center !important; gap: 0 !important; }
        #'.$id.' .wexoe-lp-contact-section { padding: 24px 0 !important; }
        #'.$id.' .wexoe-lp-contact-card { background: var(--lp-main) !important; border-radius: 12px !important; overflow: hidden !important; padding: 0 !important; }
        #'.$id.' .wexoe-lp-contact-top { display: flex !important; align-items: center !important; gap: 16px !important; padding: 28px 24px 20px !important; width: 100% !important; }
        #'.$id.' .wexoe-lp-contact-photo { width: 90px !important; height: 90px !important; border-color: rgba(255,255,255,0.2) !important; }
        #'.$id.' .wexoe-lp-contact-info { text-align: left !important; }
        #'.$id.' .wexoe-lp-contact-info h3 { color: #fff !important; font-size: 1.1rem !important; }
        #'.$id.' .wexoe-lp-contact-title-text { color: rgba(255,255,255,0.7) !important; }
        #'.$id.' .wexoe-lp-contact-info a { color: #fff !important; text-decoration: underline !important; text-decoration-color: rgba(255,255,255,0.3) !important; }
        #'.$id.' .wexoe-lp-contact-quote { padding: 0 28px 28px !important; border-left: none !important; text-align: center !important; color: rgba(255,255,255,0.9) !important; font-size: 1rem !important; }
        #'.$id.' .wexoe-lp-quote-mark { display: block !important; color: rgba(255,255,255,0.12) !important; font-size: 3.5rem !important; font-family: Georgia, serif !important; line-height: 1 !important; }
        #'.$id.' .wexoe-lp-quote-open { text-align: left !important; margin-bottom: -8px !important; }
        #'.$id.' .wexoe-lp-quote-close { text-align: right !important; margin-top: -4px !important; }
    }

    /* === RESPONSIVE: MOBILE === */
    @media (max-width: 767px) {
        #'.$id.' .wexoe-lp-hero { min-height: auto !important; display: flex !important; flex-direction: column !important; }
        #'.$id.' .wexoe-lp-hero-shapes { display: none !important; }
        #'.$id.' .wexoe-lp-hero-image-wrapper { position: relative !important; width: 100% !important; height: 220px !important; order: -1 !important; }
        #'.$id.' .wexoe-lp-hero-image-wrapper::before { display: none !important; }
        #'.$id.' .wexoe-lp-hero-image-wrapper::after { width: 100% !important; height: 50% !important; top: auto !important; bottom: 0 !important; left: 0 !important; background: linear-gradient(0deg, var(--lp-main) 0%, transparent 100%) !important; }
        #'.$id.' .wexoe-lp-hero-text { max-width: 100% !important; margin-left: 0 !important; padding: 24px 24px 40px !important; text-align: left !important; }
        #'.$id.' .wexoe-lp-hero-h1 { font-size: 1.55rem !important; }
        #'.$id.' .wexoe-lp-hero-buttons { flex-direction: column !important; }
        #'.$id.' .wexoe-lp-btn-primary, #'.$id.' .wexoe-lp-btn-secondary { width: 100% !important; justify-content: center !important; padding: 14px 20px !important; }
        #'.$id.' .wexoe-lp-container { padding: 0 20px !important; }
        #'.$id.' .wexoe-lp-tabs-bar { margin-bottom: -20px !important; padding: 0 20px !important; justify-content: center !important; overflow-x: auto !important; -webkit-overflow-scrolling: touch !important; scrollbar-width: none !important; }
        #'.$id.' .wexoe-lp-tabs-bar::-webkit-scrollbar { display: none !important; }
        #'.$id.' .wexoe-lp-tabs-nav { padding: 3px !important; gap: 2px !important; }
        #'.$id.' .wexoe-lp-tab-btn { padding: 9px 16px !important; font-size: 13px !important; }
        #'.$id.' .wexoe-lp-tabs-content { padding: 52px 0 40px !important; }
        #'.$id.' .wexoe-lp-dl-grid { grid-template-columns: 1fr !important; }
        #'.$id.' .wexoe-lp-cmp-table thead th, #'.$id.' .wexoe-lp-cmp-table tbody td { padding: 10px 12px !important; font-size: 0.82rem !important; }
    }
    </style>';
}

/* ============================================================
   JAVASCRIPT
   ============================================================ */

function wexoe_lp_test_render_js($id) {
    return '
    <script>
    (function() {
        var wrap = document.getElementById("'.$id.'");
        if (!wrap) return;

        /* Tab switching */
        wrap.querySelectorAll(".wexoe-lp-tab-btn").forEach(function(btn) {
            btn.addEventListener("click", function() {
                wrap.querySelectorAll(".wexoe-lp-tab-btn").forEach(function(b) { b.classList.remove("wexoe-lp-tab-active"); });
                wrap.querySelectorAll(".wexoe-lp-tab-panel").forEach(function(p) { p.classList.remove("wexoe-lp-tab-panel-active"); });
                btn.classList.add("wexoe-lp-tab-active");
                var panel = document.getElementById(btn.getAttribute("data-tab"));
                if (panel) panel.classList.add("wexoe-lp-tab-panel-active");
            });
        });

        /* FAQ accordion */
        wrap.querySelectorAll(".wexoe-lp-faq-q").forEach(function(q) {
            q.addEventListener("click", function() {
                q.parentElement.classList.toggle("wexoe-lp-faq-open");
            });
        });

        /* Sidebar form submissions */
        wrap.querySelectorAll(".wexoe-lp-sb-form").forEach(function(form) {
            var btn = form.querySelector(".wexoe-lp-sb-submit");
            if (!btn) return;
            btn.addEventListener("click", function() {
                var email = form.querySelector(".wexoe-lp-sb-input");
                var msg = form.querySelector(".wexoe-lp-sb-msg");
                var webhook = form.getAttribute("data-webhook");
                var type = form.getAttribute("data-type");
                var checkbox = form.querySelector(".wexoe-lp-sb-checkbox");

                if (!email || !email.value || !email.value.includes("@")) {
                    if (msg) { msg.style.color = "#EF4444"; msg.textContent = "Ange en giltig e-postadress."; }
                    return;
                }
                if (type === "leadmagnet" && checkbox && !checkbox.checked) {
                    if (msg) { msg.style.color = "#EF4444"; msg.textContent = "Du m\u00e5ste godk\u00e4nna f\u00f6r att ladda ner."; }
                    return;
                }

                btn.disabled = true;
                btn.textContent = "Skickar...";

                var payload = {
                    email: email.value,
                    type: type,
                    page_url: window.location.href,
                    submitted_at: new Date().toISOString()
                };

                if (webhook) {
                    fetch(webhook, {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify(payload)
                    }).then(function() {
                        if (type === "event") {
                            if (msg) { msg.style.color = "#10B981"; msg.textContent = "\u2713 Du \u00e4r anm\u00e4ld!"; }
                        } else {
                            var fileUrl = form.getAttribute("data-file");
                            if (fileUrl) window.open(fileUrl, "_blank");
                            if (msg) { msg.style.color = "#10B981"; msg.textContent = "\u2713 Tack! Nedladdningen startar."; }
                        }
                        btn.textContent = "\u2713 Klart";
                    }).catch(function() {
                        if (msg) { msg.style.color = "#EF4444"; msg.textContent = "N\u00e5got gick fel. F\u00f6rs\u00f6k igen."; }
                        btn.disabled = false;
                        btn.textContent = type === "event" ? "Anm\u00e4l mig \u2192" : "Ladda ner \u2192";
                    });
                } else {
                    /* No webhook — direct file access for lead magnet */
                    if (type === "leadmagnet") {
                        var fileUrl = form.getAttribute("data-file");
                        if (fileUrl) window.open(fileUrl, "_blank");
                    }
                    if (msg) { msg.style.color = "#10B981"; msg.textContent = "\u2713 Tack!"; }
                    btn.textContent = "\u2713 Klart";
                }
            });
        });
    })();
    </script>';
}

/* ============================================================
   SHORTCODE
   ============================================================ */

function wexoe_landing_page_test_shortcode($atts) {
    $atts = shortcode_atts([
        'slug' => '',
        'debug' => 'false',
        'nocache' => 'false',
        'remove_padding' => 'true',
    ], $atts, 'wexoe_landing');

    $slug = sanitize_text_field($atts['slug']);
    $debug = ($atts['debug'] === 'true');

    if (!wexoe_lp_test_core_ready()) {
        return '<p style="color:red;font-weight:bold;">Wexoe Landing Page TEST: Wexoe Core-pluginet är inte aktivt.</p>';
    }

    $pages_repo = \Wexoe\Core\Core::entity('landing_pages');
    $tabs_repo = \Wexoe\Core\Core::entity('lp_tabs');
    $downloads_repo = \Wexoe\Core\Core::entity('lp_downloads');

    if (!$pages_repo || !$tabs_repo || !$downloads_repo) {
        return '<p style="color:red;font-weight:bold;">Wexoe Landing Page TEST: En eller flera Wexoe Core-entiteter saknas (landing_pages, lp_tabs, lp_downloads).</p>';
    }

    if ($atts['nocache'] === 'true') {
        $pages_repo->clear_cache();
        $tabs_repo->clear_cache();
        $downloads_repo->clear_cache();
    }

    if (empty($slug)) {
        return '<p style="color:red;font-weight:bold;">[wexoe_landing] Error: slug parameter is required.</p>';
    }

    $data = $pages_repo->find($slug);
    if (!$data) {
        if ($debug) return '<div style="background:#fee;padding:20px;border:2px solid red;">No landing page found for slug: '.esc_html($slug).'</div>';
        return '';
    }

    // Fetch linked tabs
    $tab_ids = wexoe_lp_test_get_ids($data, 'tab_ids');
    $all_tabs = $tabs_repo->find_by_ids($tab_ids);
    $all_tabs = array_filter($all_tabs, function($tab) {
        return !empty($tab['visa']);
    });
    usort($all_tabs, function($a, $b) {
        return ($a['order'] ?? 999) <=> ($b['order'] ?? 999);
    });
    $tabs = array_values($all_tabs);

    // Fetch downloads for tabs that need them
    $downloads_map = [];
    foreach ($tabs as $tab) {
        if (wexoe_lp_test_field($tab, 'tab_type', '') === 'downloads') {
            $dl_ids = wexoe_lp_test_get_ids($tab, 'download_ids');
            if (!empty($dl_ids)) {
                $dls_all = $downloads_repo->find_by_ids($dl_ids);
                $dls_all = array_filter($dls_all, function($dl) {
                    return !empty($dl['visa']);
                });
                usort($dls_all, function($a, $b) {
                    return ($a['order'] ?? 999) <=> ($b['order'] ?? 999);
                });
                $downloads_map[$tab['_record_id']] = array_values($dls_all);
            }
        }
    }

    // Colors
    $main_color = wexoe_lp_test_hex(wexoe_lp_test_field($data, 'color_main', ''), '#11325D');
    $secondary_color = wexoe_lp_test_hex(wexoe_lp_test_field($data, 'color_secondary', ''), '#F28C28');

    $id = 'wexoe-lp-' . uniqid();
    $html = '';

    // Debug
    if ($debug) {
        $html .= '<div style="background:#f0f0f0;padding:20px;margin:20px 0;font-family:monospace;font-size:12px;white-space:pre-wrap;overflow:auto;max-height:600px;">';
        $html .= "=== LANDING PAGE: " . esc_html($slug) . " ===\n\n";
        $html .= "Fields:\n" . esc_html(print_r($data, true)) . "\n\n";
        $html .= "Tabs (" . count($tabs) . "):\n" . esc_html(print_r($tabs, true)) . "\n\n";
        $html .= "Downloads map:\n" . esc_html(print_r($downloads_map, true));
        $html .= '</div>';
    }

    // CSS
    $html .= wexoe_lp_test_render_css($id, $main_color, $secondary_color);

    // Remove Enfold padding
    if ($atts['remove_padding'] === 'true') {
        $html .= '<style>.template-page.content { padding-top: 0 !important; }</style>';
        $html .= '<script>(function(){var h=document.querySelector("header.header,#header,.av-header-area");var m=document.getElementById("main");if(!h||!m)return;var fix=function(){var pos=getComputedStyle(h).position;if(pos==="fixed"||pos==="sticky"){var b=h.getBoundingClientRect().bottom;m.style.setProperty("padding-top",b+"px","important");m.style.setProperty("margin-top","0","important");}};fix();window.addEventListener("resize",fix);window.addEventListener("scroll",function(){setTimeout(fix,100);},{once:true});})();</script>';
    }

    // Wrapper
    $html .= '<div id="' . $id . '" class="wexoe-lp-wrapper">';

    // Sections
    $html .= wexoe_lp_test_render_hero($data, $id);
    $html .= wexoe_lp_test_render_content_sidebar($data, $id);
    $html .= wexoe_lp_test_render_tabs($tabs, $downloads_map, $data, $id);
    $html .= wexoe_lp_test_render_contact($data, $id);

    $html .= '</div>';

    // JavaScript
    $html .= wexoe_lp_test_render_js($id);

    return $html;
}

add_shortcode('wexoe_landing', 'wexoe_landing_page_test_shortcode');
