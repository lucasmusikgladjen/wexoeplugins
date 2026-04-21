<?php
/**
 * Plugin Name: Wexoe Product Area
 * Description: Modular product area pages driven by Airtable CMS data via Wexoe Core. Use [wexoe_product_area slug="plc"] to render.
 * Version: 3.0.1
 * Author: Wexoe
 * Text Domain: wexoe-product-area
 */

if (!defined('ABSPATH')) exit;

/* ============================================================
   CONSTANTS
   ============================================================ */

if (!defined('WEXOE_PA_TABLE_PRODUCT_AREAS')) define('WEXOE_PA_TABLE_PRODUCT_AREAS', 'Product Areas');
if (!defined('WEXOE_PA_TABLE_PRODUCTS')) define('WEXOE_PA_TABLE_PRODUCTS', 'Products');
if (!defined('WEXOE_PA_TABLE_SOLUTIONS')) define('WEXOE_PA_TABLE_SOLUTIONS', 'Solutions & Concepts');
if (!defined('WEXOE_PA_TABLE_ARTICLES')) define('WEXOE_PA_TABLE_ARTICLES', 'Articles');
if (!defined('WEXOE_PA_TABLE_CUSTOMERS')) define('WEXOE_PA_TABLE_CUSTOMERS', 'Customers');

/* ============================================================
   HELPERS
   ============================================================ */

/**
 * Check if a hex color is dark (luminance < 0.5)
 */
function wexoe_pa_test_is_dark_bg($hex) {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    $r = hexdec(substr($hex, 0, 2)) / 255;
    $g = hexdec(substr($hex, 2, 2)) / 255;
    $b = hexdec(substr($hex, 4, 2)) / 255;
    $luminance = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    return $luminance < 0.5;
}

/**
 * Get text color based on background
 */
function wexoe_pa_test_text_color($bg_hex) {
    return wexoe_pa_test_is_dark_bg($bg_hex) ? '#FFFFFF' : '#041327';
}

/**
 * Get secondary text color (lighter) based on background
 */
function wexoe_pa_test_text_secondary($bg_hex) {
    return wexoe_pa_test_is_dark_bg($bg_hex) ? '#FFFFFF' : '#555555';
}

/**
 * Split long text field into array of non-empty lines
 */
if (!function_exists('wexoe_pa_test_lines_to_array')) {
function wexoe_pa_test_lines_to_array($text) {
    return \Wexoe\Core\Helpers\Lines::to_array((string) $text);
}
}

/**
 * Lightweight markdown to HTML
 * Supports: **bold**, *italic*, [link](url), `code`, ~~strikethrough~~, line breaks
 * Does NOT support: headers, lists, images (use dedicated fields for those)
 */
if (!function_exists('wexoe_pa_test_md')) {
function wexoe_pa_test_md($text) {
    return \Wexoe\Core\Helpers\Markdown::to_inline((string) $text);
}
}

/**
 * Get a field value with fallback
 */
if (!function_exists('wexoe_pa_test_field')) {
function wexoe_pa_test_field($data, $field, $default = '') {
    return isset($data[$field]) && $data[$field] !== '' && $data[$field] !== null ? $data[$field] : $default;
}
}

/**
 * Sanitize hex color
 */
function wexoe_pa_test_hex($value, $default) {
    return \Wexoe\Core\Helpers\Color::normalize_hex($value) ?? $default;
}

/**
 * Extract YouTube video ID from various URL formats
 */
function wexoe_pa_test_youtube_id($url) {
    return \Wexoe\Core\Helpers\YouTube::extract_id((string) $url);
}

/**
 * Render media element — YouTube embed or image
 * Returns HTML string for either an iframe or img tag
 */
if (!function_exists('wexoe_pa_test_render_media')) {
function wexoe_pa_test_render_media($url, $alt = '', $class_prefix = '') {
    if (empty($url)) return '';
    $yt_id = wexoe_pa_test_youtube_id($url);
    if ($yt_id) {
        return '<div class="'.$class_prefix.'video-wrap"><iframe src="https://www.youtube-nocookie.com/embed/'.$yt_id.'?rel=0" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe></div>';
    }
    return '<div class="'.$class_prefix.'img-wrap"><img src="'.esc_url($url).'" alt="'.esc_attr($alt).'" loading="lazy"/></div>';
}
}

/**
 * Parse a "Varianter" text field into structured data
 *
 * Format (in Airtable long text field):
 *   @Färg: Gul, Röd, Blå
 *   @Längd: 1m, 2m, 5m
 *   Gul/1m = AX2639-Y1
 *   Gul/2m = AX2639-Y2
 *   Röd/1m = AX2639-R1
 *
 * Returns: [
 *   'dimensions' => [ ['name'=>'Färg','options'=>['Gul','Röd','Blå']], ... ],
 *   'map' => [ 'Gul/1m' => 'AX2639-Y1', ... ]
 * ]
 */
if (!function_exists('wexoe_pa_test_parse_variants')) {
function wexoe_pa_test_parse_variants($text) {
    if (empty($text)) return null;

    $lines = preg_split('/\r\n|\r|\n/', $text);
    $dimensions = [];
    $map = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;

        // Dimension definition: @Name: val1, val2, val3
        if (strpos($line, '@') === 0) {
            $colon = strpos($line, ':');
            if ($colon === false) continue;
            $dim_name = trim(substr($line, 1, $colon - 1));
            $options_str = substr($line, $colon + 1);
            $options = array_map('trim', explode(',', $options_str));
            $options = array_values(array_filter($options, function($o) { return $o !== ''; }));
            if ($dim_name && !empty($options)) {
                $dimensions[] = ['name' => $dim_name, 'options' => $options];
            }
        }
        // Mapping: val1/val2 = ARTNR
        elseif (strpos($line, '=') !== false) {
            $parts = explode('=', $line, 2);
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            if ($key !== '' && $value !== '') {
                $map[$key] = $value;
            }
        }
    }

    if (empty($dimensions)) return null;

    return [
        'dimensions' => $dimensions,
        'map' => $map,
    ];
}
}

/* ============================================================
   CORE INTEGRATION
   ============================================================ */

if (!function_exists('wexoe_pa_test_core_ready')) {
function wexoe_pa_test_core_ready() {
    return class_exists('\Wexoe\\Core\\Core')
        && method_exists('\Wexoe\\Core\\Core', 'entity');
}
}

function wexoe_pa_test_get_repo($entity) {
    if (!wexoe_pa_test_core_ready()) return null;
    return \Wexoe\Core\Core::entity($entity);
}

function wexoe_pa_test_map_product_area_to_legacy($row) {
    if (empty($row) || !is_array($row)) return null;

    $mapped = [
        '_record_id' => $row['_record_id'] ?? '',
        'H1' => $row['h1'] ?? '',
        'Top BG' => $row['top_bg'] ?? '',
        'Hero H2' => $row['hero_h2'] ?? '',
        'Hero Text' => $row['hero_text'] ?? '',
        'Hero CTA Text' => $row['hero_cta_text'] ?? '',
        'Hero CTA URL' => $row['hero_cta_url'] ?? '',
        'Hero Benefits' => isset($row['hero_benefits']) && is_array($row['hero_benefits']) ? implode("
", $row['hero_benefits']) : '',
        'Hero Image' => $row['hero_image'] ?? '',
        'Hero BG' => $row['hero_bg'] ?? '',
        'Hero Accent' => $row['hero_accent'] ?? '',
        'NPI Title' => $row['npi_title'] ?? '',
        'NPI Description' => $row['npi_description'] ?? '',
        'NPI Image' => $row['npi_image'] ?? '',
        'NPI Link' => $row['npi_link'] ?? '',
        'Toggle BG' => $row['toggle_bg'] ?? '',
        'Toggle Header BG' => $row['toggle_header_bg'] ?? '',
        'Toggle Accent' => $row['toggle_accent'] ?? '',
        'Solutions Title' => $row['solutions_title'] ?? '',
        'Solutions BG' => $row['solutions_bg'] ?? '',
        'Solutions Card BG' => $row['solutions_card_bg'] ?? '',
        'Contact Name' => $row['contact_name'] ?? '',
        'Contact Title' => $row['contact_title'] ?? '',
        'Contact Email' => $row['contact_email'] ?? '',
        'Contact Phone' => $row['contact_phone'] ?? '',
        'Contact Image' => $row['contact_image'] ?? '',
        'Contact Text' => $row['contact_text'] ?? '',
        'Contact BG' => $row['contact_bg'] ?? '',
        'Docs Title' => $row['docs_title'] ?? '',
        'Docs Iframe' => $row['docs_iframe'] ?? '',
        'Docs BG' => $row['docs_bg'] ?? '',
        'Side menu' => !empty($row['use_side_menu']),
        'Request' => !empty($row['show_request']),
        'Products' => $row['product_ids'] ?? [],
        'Solutions' => $row['solution_ids'] ?? [],
    ];

    $sections = isset($row['sections']) && is_array($row['sections']) ? $row['sections'] : [];
    for ($n = 1; $n <= 4; $n++) {
        $sec = $sections[$n - 1] ?? [];
        $prefix = 'Normal ' . $n;
        $mapped[$prefix . ' H2'] = $sec['h2'] ?? '';
        $mapped[$prefix . ' Text'] = $sec['text'] ?? '';
        $mapped[$prefix . ' Bullets'] = $sec['bullets'] ?? '';
        $mapped[$prefix . ' Image'] = $sec['image'] ?? '';
        $mapped[$prefix . ' Reversed'] = !empty($sec['reversed']);
        $mapped[$prefix . ' BG'] = $sec['bg'] ?? '';
        $mapped[$prefix . ' upp'] = !empty($sec['shown_top']);
    }

    return $mapped;
}

function wexoe_pa_test_map_product_to_legacy($row) {
    return [
        '_record_id' => $row['_record_id'] ?? '',
        'Name' => $row['name'] ?? '',
        'Ecosystem Description' => $row['ecosystem_description'] ?? '',
        'Description' => $row['description'] ?? '',
        'Bullets' => isset($row['bullets']) && is_array($row['bullets']) ? implode("
", $row['bullets']) : ($row['bullets'] ?? ''),
        'Image' => $row['image'] ?? '',
        'Button 1 Text' => $row['button_1_text'] ?? '',
        'Button 1 URL' => $row['button_1_url'] ?? '',
        'Button 2 Text' => $row['button_2_text'] ?? '',
        'Button 2 URL' => $row['button_2_url'] ?? '',
        'Horizontal' => !empty($row['horizontal']),
        'Header side menu' => $row['header_side_menu'] ?? '',
        'Articles' => $row['article_ids'] ?? [],
        'Order' => $row['order'] ?? null,
        'Visa' => !empty($row['visa']),
    ];
}

function wexoe_pa_test_map_solution_to_legacy($row) {
    return [
        '_record_id' => $row['_record_id'] ?? '',
        'Name' => $row['name'] ?? '',
        'Image' => $row['image'] ?? '',
        'URL' => $row['url'] ?? '',
        'Description' => $row['description'] ?? '',
        'CTA Text' => $row['cta_text'] ?? '',
        'Category' => $row['category'] ?? '',
        'Order' => $row['order'] ?? null,
        'Visa' => !empty($row['visa']),
    ];
}

function wexoe_pa_test_map_article_to_legacy($row) {
    return [
        '_record_id' => $row['_record_id'] ?? '',
        'Name' => $row['name'] ?? '',
        'Artikelnummer' => $row['article_number'] ?? '',
        'Datablad' => $row['datasheet'] ?? '',
        'Länk till webshop' => $row['webshop_url'] ?? '',
        'Bild' => $row['image'] ?? '',
        'Varianter' => $row['variants'] ?? '',
        'Description' => $row['description'] ?? '',
    ];
}

function wexoe_pa_test_fetch_product_area($slug) {
    $repo = wexoe_pa_test_get_repo('product_areas');
    if (!$repo) return null;
    return wexoe_pa_test_map_product_area_to_legacy($repo->find($slug));
}

function wexoe_pa_test_fetch_linked_records($table, $record_ids, $cache_prefix) {
    if (empty($record_ids)) return [];

    $entity_by_prefix = [
        'products' => 'products',
        'solutions' => 'solutions',
        'articles' => 'articles',
    ];

    $entity = $entity_by_prefix[$cache_prefix] ?? '';
    if (empty($entity)) return [];

    $repo = wexoe_pa_test_get_repo($entity);
    if (!$repo) return [];

    $records = $repo->find_by_ids($record_ids);

    if ($cache_prefix === 'products') {
        $records = array_map('wexoe_pa_test_map_product_to_legacy', $records);
    } elseif ($cache_prefix === 'solutions') {
        $records = array_map('wexoe_pa_test_map_solution_to_legacy', $records);
    } elseif ($cache_prefix === 'articles') {
        $records = array_map('wexoe_pa_test_map_article_to_legacy', $records);
    }

    usort($records, function($a, $b) {
        $oa = isset($a['Order']) ? (float)$a['Order'] : 999;
        $ob = isset($b['Order']) ? (float)$b['Order'] : 999;
        return $oa - $ob;
    });

    $records = array_filter($records, function($r) {
        return !array_key_exists('Visa', $r) || !empty($r['Visa']);
    });

    return array_values($records);
}

function wexoe_pa_test_get_linked_ids($data, $field) {
    if (!isset($data[$field]) || !is_array($data[$field])) return [];
    return $data[$field];
}

function wexoe_pa_test_fetch_articles_for_products($products) {
    if (empty($products)) return [];

    $all_article_ids = [];
    $product_article_map = [];
    foreach ($products as $product) {
        $pid = isset($product['_record_id']) ? $product['_record_id'] : '';
        $article_ids = isset($product['Articles']) && is_array($product['Articles']) ? $product['Articles'] : [];
        $product_article_map[$pid] = $article_ids;
        $all_article_ids = array_merge($all_article_ids, $article_ids);
    }
    $all_article_ids = array_values(array_unique($all_article_ids));

    if (empty($all_article_ids)) {
        $grouped = [];
        foreach ($products as $product) {
            $pid = isset($product['_record_id']) ? $product['_record_id'] : '';
            $grouped[$pid] = [];
        }
        return $grouped;
    }

    $article_repo = wexoe_pa_test_get_repo('articles');
    if (!$article_repo) return [];

    $articles_list = array_map('wexoe_pa_test_map_article_to_legacy', $article_repo->find_by_ids($all_article_ids));

    $articles_by_id = [];
    foreach ($articles_list as $article) {
        $aid = isset($article['_record_id']) ? $article['_record_id'] : '';
        if ($aid) $articles_by_id[$aid] = $article;
    }

    $grouped = [];
    foreach ($product_article_map as $pid => $article_ids) {
        $grouped[$pid] = [];
        foreach ($article_ids as $aid) {
            if (isset($articles_by_id[$aid])) {
                $grouped[$pid][] = $articles_by_id[$aid];
            }
        }
    }

    return $grouped;
}

/* ============================================================
   SECTION RENDERERS
   ============================================================ */

/**
 * Section 1: TOP BANNER
 */
function wexoe_pa_test_render_top($data, $id) {
    $h1 = wexoe_pa_test_field($data, 'H1', '');
    if (empty($h1)) return '';

    $bg = wexoe_pa_test_hex(wexoe_pa_test_field($data, 'Top BG', ''), '#11325D');
    $text = wexoe_pa_test_text_color($bg);

    return '
    <section class="wexoe-pa-top" style="--top-bg:'.$bg.';--top-text:'.$text.';">
        <div class="wexoe-pa-top-inner">
            <h1 class="wexoe-pa-top-h1">'.esc_html($h1).'</h1>
        </div>
    </section>';
}

/**
 * Section 2: HERO
 */
function wexoe_pa_test_render_hero($data, $id) {
    $h2 = wexoe_pa_test_field($data, 'Hero H2', '');
    $text = wexoe_pa_test_field($data, 'Hero Text', '');
    if (empty($h2) && empty($text)) return '';

    $bg = wexoe_pa_test_hex(wexoe_pa_test_field($data, 'Hero BG', ''), '#FFFFFF');
    $accent = wexoe_pa_test_hex(wexoe_pa_test_field($data, 'Hero Accent', ''), '#F28C28');
    $text_color = wexoe_pa_test_text_color($bg);
    $text_secondary = wexoe_pa_test_text_secondary($bg);
    $cta_text = wexoe_pa_test_field($data, 'Hero CTA Text', '');
    $cta_url = wexoe_pa_test_field($data, 'Hero CTA URL', '');
    $benefits = wexoe_pa_test_lines_to_array(wexoe_pa_test_field($data, 'Hero Benefits', ''));

    // NPI fields
    $npi_title = wexoe_pa_test_field($data, 'NPI Title', '');
    $npi_desc = wexoe_pa_test_field($data, 'NPI Description', '');
    $npi_image = wexoe_pa_test_field($data, 'NPI Image', '');
    $npi_link = wexoe_pa_test_field($data, 'NPI Link', '');
    $has_npi = !empty($npi_title);

    // Hero Image
    $hero_image = wexoe_pa_test_field($data, 'Hero Image', '');

    $html = '<section class="wexoe-pa-hero" style="--hero-bg:'.$bg.';--hero-accent:'.$accent.';--hero-text:'.$text_color.';--hero-text-secondary:'.$text_secondary.';">';
    $html .= '<div class="wexoe-pa-hero-inner">';
    $html .= '<div class="wexoe-pa-hero-content">';

    if ($h2) {
        $html .= '<h2 class="wexoe-pa-hero-h2">'.esc_html($h2).'</h2>';
    }
    if ($text) {
        $html .= '<p class="wexoe-pa-hero-text">'.wexoe_pa_test_md($text).'</p>';
    }
    if ($cta_text && $cta_url) {
        $html .= '<a href="'.esc_url($cta_url).'" class="wexoe-pa-hero-cta">'.esc_html($cta_text).' <span class="wexoe-pa-cta-arrow">&rarr;</span></a>';
    }

    $html .= '</div>'; // .hero-content

    // Right side: NPI > Hero Image > Benefits
    if ($has_npi) {
        // NPI Card — always link if npi_link exists
        $tag_open = $npi_link ? '<a href="'.esc_url($npi_link).'" class="wexoe-pa-npi-card">' : '<div class="wexoe-pa-npi-card">';
        $tag_close = $npi_link ? '</a>' : '</div>';
        $html .= $tag_open;
        if ($npi_image) {
            $html .= wexoe_pa_test_render_media($npi_image, $npi_title, 'wexoe-pa-npi-');
        }
        $html .= '<div class="wexoe-pa-npi-body">';
        $html .= '<span class="wexoe-pa-npi-badge">NYHET</span>';
        $html .= '<div class="wexoe-pa-npi-title">'.esc_html($npi_title).'</div>';
        if ($npi_desc) {
            $html .= '<p class="wexoe-pa-npi-desc">'.esc_html($npi_desc).'</p>';
        }
        if ($npi_link) {
            $html .= '<span class="wexoe-pa-npi-cta">Läs mer <span class="wexoe-pa-cta-arrow">&rarr;</span></span>';
        }
        $html .= '</div>';
        $html .= $tag_close;
    } elseif (!empty($hero_image)) {
        // Hero Image or Video
        $html .= '<div class="wexoe-pa-hero-image">' . wexoe_pa_test_render_media($hero_image) . '</div>';
    } elseif (!empty($benefits)) {
        // Benefits list
        $html .= '<div class="wexoe-pa-hero-benefits">';
        $html .= '<ul class="wexoe-pa-benefits-list">';
        foreach ($benefits as $benefit) {
            $html .= '<li><span class="wexoe-pa-check">&#10003;</span> '.wexoe_pa_test_md($benefit).'</li>';
        }
        $html .= '</ul></div>';
    }

    $html .= '</div></section>';
    return $html;
}

/**
 * Section 3: PRODUCT TOGGLE
 */
function wexoe_pa_test_render_products($products, $data, $id) {
    if (empty($products)) return '';

    $bg = wexoe_pa_test_hex(wexoe_pa_test_field($data, 'Toggle BG', ''), '#11325D');
    $header_bg = wexoe_pa_test_hex(wexoe_pa_test_field($data, 'Toggle Header BG', ''), '#FFFFFF');
    $accent = wexoe_pa_test_hex(wexoe_pa_test_field($data, 'Toggle Accent', ''), '#F28C28');
    $text_color = wexoe_pa_test_text_color($bg);

    $html = '<section class="wexoe-pa-toggle" style="--toggle-bg:'.$bg.';--toggle-header-bg:'.$header_bg.';--toggle-accent:'.$accent.';--toggle-text:'.$text_color.';">';
    $html .= '<div class="wexoe-pa-toggle-inner">';

    foreach ($products as $i => $product) {
        $name = wexoe_pa_test_field($product, 'Name', 'Produkt');
        $eco = wexoe_pa_test_field($product, 'Ecosystem Description', '');
        $desc = wexoe_pa_test_field($product, 'Description', '');
        $bullets = wexoe_pa_test_lines_to_array(wexoe_pa_test_field($product, 'Bullets', ''));
        $image = wexoe_pa_test_field($product, 'Image', '');
        $btn1_text = wexoe_pa_test_field($product, 'Button 1 Text', '');
        $btn1_url = wexoe_pa_test_field($product, 'Button 1 URL', '');
        $btn2_text = wexoe_pa_test_field($product, 'Button 2 Text', '');
        $btn2_url = wexoe_pa_test_field($product, 'Button 2 URL', '');
        $is_open = ($i === 0) ? ' wexoe-pa-toggle-open' : '';
        $has_one_btn = (!empty($btn1_text) && empty($btn2_text)) || (empty($btn1_text) && !empty($btn2_text));

        $html .= '<div class="wexoe-pa-toggle-item'.$is_open.'">';

        // Header
        $html .= '<button class="wexoe-pa-toggle-header" aria-expanded="'.($i === 0 ? 'true' : 'false').'">';
        $html .= '<div class="wexoe-pa-toggle-header-text">';
        $html .= '<span class="wexoe-pa-toggle-name">'.esc_html($name).'</span>';
        if ($eco) {
            $html .= '<span class="wexoe-pa-toggle-eco"> — '.esc_html($eco).'</span>';
        }
        $html .= '</div>';
        $html .= '<span class="wexoe-pa-toggle-icon"><svg class="wexoe-pa-svg-plus" width="16" height="16" viewBox="0 0 16 16"><line x1="8" y1="2" x2="8" y2="14" stroke="#11325D" stroke-width="2" stroke-linecap="round"/><line x1="2" y1="8" x2="14" y2="8" stroke="#11325D" stroke-width="2" stroke-linecap="round"/></svg><svg class="wexoe-pa-svg-minus" width="16" height="16" viewBox="0 0 16 16"><line x1="2" y1="8" x2="14" y2="8" stroke="#11325D" stroke-width="2" stroke-linecap="round"/></svg></span>';
        $html .= '</button>';

        // Body
        $html .= '<div class="wexoe-pa-toggle-body">';
        $html .= '<div class="wexoe-pa-toggle-body-inner">';

        // Left column — text + buttons
        $html .= '<div class="wexoe-pa-toggle-left">';
        if ($desc) {
            $html .= '<p class="wexoe-pa-toggle-desc">'.wexoe_pa_test_md($desc).'</p>';
        }
        if (!empty($bullets)) {
            $html .= '<ul class="wexoe-pa-toggle-checks">';
            foreach ($bullets as $b) {
                $html .= '<li><span class="wexoe-pa-check">&#10003;</span> '.wexoe_pa_test_md($b).'</li>';
            }
            $html .= '</ul>';
        }
        if ($btn1_text || $btn2_text) {
            $btn_class = $has_one_btn ? ' wexoe-pa-btns-single' : '';
            $html .= '<div class="wexoe-pa-toggle-btns'.$btn_class.'">';
            if ($btn2_text && $btn2_url) {
                $html .= '<a href="'.esc_url($btn2_url).'" class="wexoe-pa-btn wexoe-pa-btn-primary">'.esc_html($btn2_text).' <span class="wexoe-pa-cta-arrow">&rarr;</span></a>';
            }
            if ($btn1_text && $btn1_url) {
                $html .= '<a href="'.esc_url($btn1_url).'" class="wexoe-pa-btn wexoe-pa-btn-secondary" target="_blank" rel="noopener">'.esc_html($btn1_text).' <span class="wexoe-pa-cta-arrow">&rarr;</span></a>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';

        // Right column — image only
        if ($image) {
            $html .= '<div class="wexoe-pa-toggle-right">';
            $html .= '<div class="wexoe-pa-toggle-img-wrap"><img src="'.esc_url($image).'" alt="'.esc_attr($name).'" class="wexoe-pa-toggle-img" loading="lazy"/></div>';
            $html .= '</div>';
        }

        $html .= '</div></div>'; // body-inner, body
        $html .= '</div>'; // toggle-item
    }

    $html .= '</div></section>';
    return $html;
}

/**
 * Section 3b: SIDE MENU (alternative to toggle when "Side menu" checkbox is set)
 */
function wexoe_pa_test_render_side_menu($products, $articles_grouped, $data, $id, $request_mode = false) {
    if (empty($products)) return '';

    $bg = wexoe_pa_test_hex(wexoe_pa_test_field($data, 'Toggle BG', ''), '#11325D');
    $accent = wexoe_pa_test_hex(wexoe_pa_test_field($data, 'Toggle Accent', ''), '#F28C28');
    $text_color = wexoe_pa_test_text_color($bg);

    $html = '<section class="wexoe-pa-sidemenu" style="--sm-bg:'.$bg.';--sm-accent:'.$accent.';--sm-text:'.$text_color.';">';
    $html .= '<div class="wexoe-pa-sidemenu-inner">';

    // Mobile: select dropdown
    $html .= '<div class="wexoe-pa-sm-mobile-select-wrap">';
    $html .= '<select class="wexoe-pa-sm-mobile-select">';
    foreach ($products as $i => $product) {
        $name = wexoe_pa_test_field($product, 'Name', 'Produkt');
        $html .= '<option value="'.$i.'"'.($i === 0 ? ' selected' : '').'>'.esc_html($name).'</option>';
    }
    $html .= '</select>';
    $html .= '</div>';

    // Desktop: sidebar
    $html .= '<nav class="wexoe-pa-sm-sidebar">';
    foreach ($products as $i => $product) {
        $name = wexoe_pa_test_field($product, 'Name', 'Produkt');
        $active_class = ($i === 0) ? ' wexoe-pa-sm-nav-active' : '';
        $html .= '<button class="wexoe-pa-sm-nav-item'.$active_class.'" data-sm-index="'.$i.'">'.esc_html($name).'</button>';
    }
    $html .= '</nav>';

    // Content panels
    $html .= '<div class="wexoe-pa-sm-content">';
    foreach ($products as $i => $product) {
        $name = wexoe_pa_test_field($product, 'Name', 'Produkt');
        $desc = wexoe_pa_test_field($product, 'Description', '');
        $eco = wexoe_pa_test_field($product, 'Ecosystem Description', '');
        $bullets = wexoe_pa_test_lines_to_array(wexoe_pa_test_field($product, 'Bullets', ''));
        $image = wexoe_pa_test_field($product, 'Image', '');
        $btn1_text = wexoe_pa_test_field($product, 'Button 1 Text', '');
        $btn1_url = wexoe_pa_test_field($product, 'Button 1 URL', '');
        $btn2_text = wexoe_pa_test_field($product, 'Button 2 Text', '');
        $btn2_url = wexoe_pa_test_field($product, 'Button 2 URL', '');
        $record_id = wexoe_pa_test_field($product, '_record_id', '');
        $product_articles = isset($articles_grouped[$record_id]) ? $articles_grouped[$record_id] : [];
        $has_one_btn = (!empty($btn1_text) && empty($btn2_text)) || (empty($btn1_text) && !empty($btn2_text));
        $horizontal = !empty($product['Horizontal']);

        $visible = ($i === 0) ? '' : ' style="display:none;"';
        $html .= '<div class="wexoe-pa-sm-panel" data-sm-panel="'.$i.'"'.$visible.'>';

        // Header — use "Header side menu" if set, else Name
        $heading = wexoe_pa_test_field($product, 'Header side menu', $name);
        $html .= '<h2 class="wexoe-pa-sm-h2">'.esc_html($heading).'</h2>';

        // Content row: text left, image right
        $has_text_content = $desc || !empty($bullets) || $btn1_text || $btn2_text;
        if ($image && $has_text_content) {
            $html .= '<div class="wexoe-pa-sm-row">';
        }

        // Text column
        if ($has_text_content) {
            $html .= '<div class="wexoe-pa-sm-text-col">';
        }

        // Description
        if ($desc) {
            $html .= '<p class="wexoe-pa-sm-desc">'.wexoe_pa_test_md($desc).'</p>';
        }

        // Bullets
        if (!empty($bullets)) {
            $html .= '<ul class="wexoe-pa-sm-checks">';
            foreach ($bullets as $b) {
                $html .= '<li><span class="wexoe-pa-check">&#10003;</span> '.wexoe_pa_test_md($b).'</li>';
            }
            $html .= '</ul>';
        }

        // Buttons
        if ($btn1_text || $btn2_text) {
            $btn_class = $has_one_btn ? ' wexoe-pa-btns-single' : '';
            $html .= '<div class="wexoe-pa-sm-btns'.$btn_class.'">';
            if ($btn2_text && $btn2_url) {
                $html .= '<a href="'.esc_url($btn2_url).'" class="wexoe-pa-btn wexoe-pa-btn-primary">'.esc_html($btn2_text).' <span class="wexoe-pa-cta-arrow">&rarr;</span></a>';
            }
            if ($btn1_text && $btn1_url) {
                $html .= '<a href="'.esc_url($btn1_url).'" class="wexoe-pa-btn wexoe-pa-btn-secondary" target="_blank" rel="noopener">'.esc_html($btn1_text).' <span class="wexoe-pa-cta-arrow">&rarr;</span></a>';
            }
            $html .= '</div>';
        }

        if ($has_text_content) {
            $html .= '</div>'; // text-col
        }

        // Image column
        if ($image) {
            $html .= '<div class="wexoe-pa-sm-img-wrap">';
            $html .= wexoe_pa_test_render_media($image, $name, 'wexoe-pa-sm-');
            $html .= '</div>';
        }

        if ($image && $has_text_content) {
            $html .= '</div>'; // row
        }

        // Article cards
        if (!empty($product_articles)) {
            $grid_class = $horizontal ? 'wexoe-pa-sm-articles-grid wexoe-pa-sm-articles-horiz' : 'wexoe-pa-sm-articles-grid';
            $html .= '<div class="wexoe-pa-sm-articles">';
            $html .= '<div class="'.$grid_class.'">';
            foreach ($product_articles as $art_idx => $article) {
                $art_name = wexoe_pa_test_field($article, 'Name', '');
                $art_nr = wexoe_pa_test_field($article, 'Artikelnummer', '');
                $art_datasheet = wexoe_pa_test_field($article, 'Datablad', '');
                $art_webshop = wexoe_pa_test_field($article, 'Länk till webshop', '');
                $art_image = wexoe_pa_test_field($article, 'Bild', '');
                $variants = wexoe_pa_test_parse_variants(wexoe_pa_test_field($article, 'Varianter', ''));
                $has_variants = ($variants !== null && !empty($variants['dimensions']));

                $card_id = 'art-' . $i . '-' . $art_idx;
                $card_class = $horizontal ? 'wexoe-pa-sm-article-card wexoe-pa-sm-article-horiz' : 'wexoe-pa-sm-article-card';
                $html .= '<div class="'.$card_class.'" data-card-id="'.$card_id.'">';

                // Image
                if ($art_image) {
                    $html .= '<div class="wexoe-pa-sm-article-img"><img src="'.esc_url($art_image).'" alt="'.esc_attr($art_name).'" loading="lazy"/></div>';
                } else {
                    $html .= '<div class="wexoe-pa-sm-article-img wexoe-pa-sm-article-placeholder"></div>';
                }

                // Info
                $html .= '<div class="wexoe-pa-sm-article-info">';
                $html .= '<div class="wexoe-pa-sm-article-name">'.esc_html($art_name).'</div>';

                // Middle section (flex-grows to fill space, aligning buttons at bottom)
                $html .= '<div class="wexoe-pa-sm-article-mid">';

                $initial_key = '';
                if ($has_variants) {
                    // Variant selectors
                    $html .= '<div class="wexoe-pa-sm-variant-wrap" data-variant-map="'.esc_attr(json_encode($variants['map'], JSON_UNESCAPED_UNICODE)).'">';
                    foreach ($variants['dimensions'] as $dim_idx => $dim) {
                        $html .= '<div class="wexoe-pa-sm-variant-row">';
                        $html .= '<select class="wexoe-pa-sm-variant-select" data-dim="'.$dim_idx.'">';
                        foreach ($dim['options'] as $opt_idx => $opt) {
                            $html .= '<option value="'.esc_attr($opt).'"'.($opt_idx === 0 ? ' selected' : '').'>'.esc_html($opt).'</option>';
                        }
                        $html .= '</select>';
                        $html .= '</div>';
                    }
                    $html .= '</div>'; // variant-wrap (selects only)
                    $initial_parts = [];
                    foreach ($variants['dimensions'] as $dim) {
                        $initial_parts[] = $dim['options'][0];
                    }
                    $initial_key = implode('/', $initial_parts);
                    $initial_nr = isset($variants['map'][$initial_key]) ? $variants['map'][$initial_key] : $art_nr;
                    $html .= '<div class="wexoe-pa-sm-article-nr wexoe-pa-sm-variant-artnr"><span class="wexoe-pa-sm-article-nr-label">Art.</span><span class="wexoe-pa-sm-article-nr-value">'.esc_html($initial_nr).'</span></div>';
                } else {
                    $art_desc = wexoe_pa_test_field($article, 'Description', '');
                    if ($art_desc) {
                        $html .= '<p class="wexoe-pa-sm-article-desc">'.esc_html($art_desc).'</p>';
                    }
                    if ($art_nr) {
                        $html .= '<div class="wexoe-pa-sm-article-nr"><span class="wexoe-pa-sm-article-nr-label">Art.</span><span class="wexoe-pa-sm-article-nr-value">'.esc_html($art_nr).'</span></div>';
                    }
                }

                $html .= '</div>'; // mid

                // Buttons
                $html .= '<div class="wexoe-pa-sm-article-btns">';
                if ($art_datasheet) {
                    $html .= '<a href="'.esc_url($art_datasheet).'" target="_blank" rel="noopener" class="wexoe-pa-sm-article-btn-ds">Datablad</a>';
                }
                if ($art_webshop) {
                    $html .= '<a href="'.esc_url($art_webshop).'" target="_blank" rel="noopener" class="wexoe-pa-sm-article-btn-ds">Webshop</a>';
                }
                $display_nr = $has_variants ? (isset($variants['map'][$initial_key]) ? $variants['map'][$initial_key] : $art_nr) : $art_nr;
                $order_msg = 'Hej! Jag är intresserad av att få en prisuppgift på ' . $art_name . ($display_nr ? ' ' . $display_nr : '') . '.';
                $order_href = $request_mode ? 'javascript:void(0)' : '#kontakt';
                $order_label = $request_mode ? '<span class="wexoe-pa-btn-full">Lägg till i förfrågan</span><span class="wexoe-pa-btn-short">Lägg till</span>' : 'Beställ';
                $variant_json = $has_variants ? esc_attr(json_encode($variants, JSON_UNESCAPED_UNICODE)) : '';
                $html .= '<a href="'.$order_href.'" class="wexoe-pa-sm-article-btn-order" data-prefill-msg="'.esc_attr($order_msg).'" data-art-name="'.esc_attr($art_name).'"'.($variant_json ? ' data-variants="'.$variant_json.'"' : '').'>'.$order_label.'</a>';
                $html .= '</div>';

                $html .= '</div>'; // info

                $html .= '</div>'; // card
            }
            $html .= '</div>'; // grid
            $html .= '</div>'; // articles
        }

        $html .= '</div>'; // panel
    }
    $html .= '</div>'; // content

    $html .= '</div></section>';
    return $html;
}

/**
 * Section 4: SOLUTIONS AND CONCEPTS
 */
function wexoe_pa_test_render_solutions($solutions, $data, $id) {
    if (empty($solutions)) return '';

    $bg = wexoe_pa_test_hex(wexoe_pa_test_field($data, 'Solutions BG', ''), '#FFFFFF');
    $card_bg = wexoe_pa_test_hex(wexoe_pa_test_field($data, 'Solutions Card BG', ''), '#FFFFFF');
    $title = wexoe_pa_test_field($data, 'Solutions Title', 'Lösningar & Koncept');
    $text_color = wexoe_pa_test_text_color($bg);

    $html = '<section class="wexoe-pa-solutions" style="--solutions-bg:'.$bg.';--solutions-card-bg:'.$card_bg.';--solutions-text:'.$text_color.';">';
    $html .= '<div class="wexoe-pa-solutions-inner">';
    $html .= '<h2 class="wexoe-pa-solutions-h2">'.esc_html($title).'</h2>';
    $html .= '<div class="wexoe-pa-solutions-grid">';

    foreach ($solutions as $sol) {
        $name = wexoe_pa_test_field($sol, 'Name', '');
        $image = wexoe_pa_test_field($sol, 'Image', '');
        $url = wexoe_pa_test_field($sol, 'URL', '#');
        $desc = wexoe_pa_test_field($sol, 'Description', '');
        $cta = wexoe_pa_test_field($sol, 'CTA Text', 'Läs mer');
        $category = wexoe_pa_test_field($sol, 'Category', '');

        $html .= '<a href="'.esc_url($url).'" class="wexoe-pa-solution-card">';
        if ($image) {
            $html .= '<div class="wexoe-pa-solution-img-wrap"><img src="'.esc_url($image).'" alt="'.esc_attr($name).'" class="wexoe-pa-solution-img" loading="lazy"/></div>';
        } else {
            $html .= '<div class="wexoe-pa-solution-img-wrap wexoe-pa-solution-placeholder"></div>';
        }
        $html .= '<div class="wexoe-pa-solution-body">';
        if ($category) {
            $html .= '<span class="wexoe-pa-solution-cat">'.esc_html(strtoupper($category)).'</span>';
        }
        $html .= '<div class="wexoe-pa-solution-title">'.esc_html($name).'</div>';
        if ($desc) {
            $html .= '<p class="wexoe-pa-solution-desc">'.esc_html($desc).'</p>';
        }
        $html .= '<span class="wexoe-pa-solution-cta">'.esc_html($cta).'</span>';
        $html .= '</div>';
        $html .= '</a>';
    }

    $html .= '</div></div></section>';
    return $html;
}

/**
 * Section 5: NORMAL (×4)
 */
function wexoe_pa_test_render_normal($data, $n, $id) {
    $prefix = 'Normal ' . $n;
    $h2 = wexoe_pa_test_field($data, $prefix . ' H2', '');
    $text = wexoe_pa_test_field($data, $prefix . ' Text', '');
    if (empty($h2) && empty($text)) return '';

    $default_bg = ($n % 2 === 0) ? '#F8F9FA' : '#FFFFFF';
    $bg = wexoe_pa_test_hex(wexoe_pa_test_field($data, $prefix . ' BG', ''), $default_bg);
    $text_color = wexoe_pa_test_text_color($bg);
    $text_secondary = wexoe_pa_test_text_secondary($bg);
    $bullets = wexoe_pa_test_lines_to_array(wexoe_pa_test_field($data, $prefix . ' Bullets', ''));
    $image = wexoe_pa_test_field($data, $prefix . ' Image', '');
    $reversed = !empty($data[$prefix . ' Reversed']);

    $rev_class = $reversed ? ' wexoe-pa-normal-reversed' : '';

    $html = '<section class="wexoe-pa-normal'.$rev_class.'" style="--normal-bg:'.$bg.';--normal-text:'.$text_color.';--normal-text-secondary:'.$text_secondary.';">';
    $html .= '<div class="wexoe-pa-normal-inner">';

    // Text column
    $html .= '<div class="wexoe-pa-normal-text">';
    if ($h2) {
        $html .= '<h2 class="wexoe-pa-normal-h2">'.esc_html($h2).'</h2>';
    }
    if ($text) {
        $html .= '<p class="wexoe-pa-normal-body">'.wexoe_pa_test_md($text).'</p>';
    }
    if (!empty($bullets)) {
        $html .= '<ul class="wexoe-pa-normal-checks">';
        foreach ($bullets as $b) {
            $html .= '<li><span class="wexoe-pa-check">&#10003;</span> '.wexoe_pa_test_md($b).'</li>';
        }
        $html .= '</ul>';
    }
    $html .= '</div>';

    // Image column
    if ($image) {
        $html .= '<div class="wexoe-pa-normal-image"><div class="wexoe-pa-normal-img-wrap"><img src="'.esc_url($image).'" alt="'.esc_attr($h2).'" loading="lazy"/></div></div>';
    }

    $html .= '</div></section>';
    return $html;
}

/**
 * Section 6: OUR GUY
 */
function wexoe_pa_test_render_our_guy($data, $id) {
    $name = wexoe_pa_test_field($data, 'Contact Name', '');
    if (empty($name)) return '';

    $bg = wexoe_pa_test_hex(wexoe_pa_test_field($data, 'Contact BG', ''), '#11325D');
    $title = wexoe_pa_test_field($data, 'Contact Title', '');
    $email = wexoe_pa_test_field($data, 'Contact Email', '');
    $phone = wexoe_pa_test_field($data, 'Contact Phone', '');
    $image = wexoe_pa_test_field($data, 'Contact Image', '');
    $bio = wexoe_pa_test_field($data, 'Contact Text', '');

    $html = '<section class="wexoe-pa-contact" style="--contact-bg:'.$bg.';">';
    $html .= '<div class="wexoe-pa-contact-inner">';

    // Left: photo + info
    $html .= '<div class="wexoe-pa-contact-left">';
    if ($image) {
        $html .= '<div class="wexoe-pa-contact-photo"><img src="'.esc_url($image).'" alt="'.esc_attr($name).'" loading="lazy"/></div>';
    }
    $html .= '<div class="wexoe-pa-contact-info">';
    $html .= '<div class="wexoe-pa-contact-name">'.esc_html($name).'</div>';
    if ($title) {
        $html .= '<div class="wexoe-pa-contact-title">'.esc_html($title).'</div>';
    }
    if ($email) {
        $html .= '<div class="wexoe-pa-contact-detail"><a href="mailto:'.esc_attr($email).'">'.esc_html($email).'</a></div>';
    }
    if ($phone) {
        $html .= '<div class="wexoe-pa-contact-detail"><a href="tel:'.esc_attr(preg_replace('/[^+0-9]/', '', $phone)).'">'.esc_html($phone).'</a></div>';
    }
    $html .= '</div></div>';

    // Right: quote
    if ($bio) {
        $html .= '<div class="wexoe-pa-contact-quote">';
        $html .= '<svg class="wexoe-pa-quote-open" width="32" height="24" viewBox="0 0 32 24" fill="none"><path d="M0 24V14.4C0 10.4 0.8 7.2 2.4 4.8C4.1 2.4 6.7 0.7 10.2 0L11.4 3C9.3 3.5 7.7 4.5 6.6 6C5.5 7.5 5 9.2 5 11.2H10V24H0ZM18 24V14.4C18 10.4 18.8 7.2 20.4 4.8C22.1 2.4 24.7 0.7 28.2 0L29.4 3C27.3 3.5 25.7 4.5 24.6 6C23.5 7.5 23 9.2 23 11.2H28V24H18Z" fill="rgba(255,255,255,0.15)"/></svg>';
        $html .= '<p>'.wexoe_pa_test_md($bio).'</p>';
        $html .= '<svg class="wexoe-pa-quote-close" width="32" height="24" viewBox="0 0 32 24" fill="none"><path d="M32 0V9.6C32 13.6 31.2 16.8 29.6 19.2C27.9 21.6 25.3 23.3 21.8 24L20.6 21C22.7 20.5 24.3 19.5 25.4 18C26.5 16.5 27 14.8 27 12.8H22V0H32ZM14 0V9.6C14 13.6 13.2 16.8 11.6 19.2C9.9 21.6 7.3 23.3 3.8 24L2.6 21C4.7 20.5 6.3 19.5 7.4 18C8.5 16.5 9 14.8 9 12.8H4V0H14Z" fill="rgba(255,255,255,0.15)"/></svg>';
        $html .= '</div>';
    }

    $html .= '</div></section>';
    return $html;
}

/**
 * Section 7: DOCUMENTATION
 */
function wexoe_pa_test_render_docs($data, $id) {
    $iframe_src = wexoe_pa_test_field($data, 'Docs Iframe', '');
    if (empty($iframe_src)) return '';

    $bg = wexoe_pa_test_hex(wexoe_pa_test_field($data, 'Docs BG', ''), '#FFFFFF');
    $text_color = wexoe_pa_test_text_color($bg);
    $title = wexoe_pa_test_field($data, 'Docs Title', 'Dokumentation');

    $html = '<section class="wexoe-pa-docs" style="--docs-bg:'.$bg.';--docs-text:'.$text_color.';">';
    $html .= '<div class="wexoe-pa-docs-inner">';
    $html .= '<h2 class="wexoe-pa-docs-h2">'.esc_html($title).'</h2>';
    $html .= '<div class="wexoe-pa-docs-iframe-wrap">';
    $html .= '<iframe src="'.esc_url($iframe_src, ['http', 'https', '//v.calameo.com']).'" width="100%" height="400" frameborder="0" allowfullscreen allowtransparency loading="lazy"></iframe>';
    $html .= '</div>';
    $html .= '</div></section>';
    return $html;
}

/**
 * Section: REQUEST FORM (shown when "Request" checkbox is set in Product Areas)
 */
function wexoe_pa_test_render_request_form($data, $id, $articles_grouped = []) {
    $nonce = wp_create_nonce('wexoe_pa_test_request_nonce');
    $ajax_url = admin_url('admin-ajax.php');

    // Build flat list of all articles for search
    $all_articles = [];
    foreach ($articles_grouped as $pid => $arts) {
        foreach ($arts as $art) {
            $variants = wexoe_pa_test_parse_variants(wexoe_pa_test_field($art, 'Varianter', ''));
            $all_articles[] = [
                'name' => wexoe_pa_test_field($art, 'Name', ''),
                'nr' => wexoe_pa_test_field($art, 'Artikelnummer', ''),
                'variants' => $variants,
            ];
        }
    }

    $html = '<section class="wexoe-pa-request" id="wexoe-pa-request-form">';
    $html .= '<div class="wexoe-pa-request-inner">';

    $html .= '<h2 class="wexoe-pa-request-h2">Prisförfrågan</h2>';

    // Subtitle left + Customer ID toggle right
    $html .= '<div class="wexoe-pa-request-header-row">';
    $html .= '<p class="wexoe-pa-request-subtitle">Lägg till artiklar, specificera variant och antal. Vi återkommer inom kort med prisförslag.</p>';
    $html .= '<div class="wexoe-pa-request-customer-toggle">';
    $html .= '<button type="button" class="wexoe-pa-request-customer-trigger">Har du ett kund-ID? <span class="wexoe-pa-cta-arrow">&rsaquo;</span></button>';
    $html .= '<div class="wexoe-pa-request-customer-panel">';
    $html .= '<div class="wexoe-pa-request-customer-status"></div>';
    $html .= '<div class="wexoe-pa-request-customer-input-wrap">';
    $html .= '<input type="text" name="customer_id" class="wexoe-pa-request-customer-input">';
    $html .= '<button type="button" class="wexoe-pa-request-customer-btn">Hämta priser</button>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '<form class="wexoe-pa-request-form" data-nonce="'.esc_attr($nonce).'" data-ajax="'.esc_attr($ajax_url).'">';

    // Error message
    $html .= '<div class="wexoe-pa-request-error"></div>';

    // Contact fields row
    $html .= '<div class="wexoe-pa-request-fields">';
    $html .= '<div class="wexoe-pa-request-field"><label>Namn *</label><input type="text" name="namn" required></div>';
    $html .= '<div class="wexoe-pa-request-field"><label>Företag *</label><input type="text" name="foretag" required></div>';
    $html .= '<div class="wexoe-pa-request-field"><label>Telefon *</label><input type="tel" name="telefon" required></div>';
    $html .= '<div class="wexoe-pa-request-field"><label>E-post *</label><input type="email" name="epost" required></div>';
    $html .= '</div>';

    // Articles table
    $html .= '<div class="wexoe-pa-request-articles" data-all-articles="'.esc_attr(json_encode($all_articles, JSON_UNESCAPED_UNICODE)).'">';
    $html .= '<div class="wexoe-pa-request-table-head">';
    $html .= '<span class="wexoe-pa-req-col-name">Artikel</span>';
    $html .= '<span class="wexoe-pa-req-col-artnr">Art.</span>';
    $html .= '<span class="wexoe-pa-req-col-variant">Variant</span>';
    $html .= '<span class="wexoe-pa-req-col-price wexoe-pa-price-col">Pris</span>';
    $html .= '<span class="wexoe-pa-req-col-qty">Antal</span>';
    $html .= '<span class="wexoe-pa-req-col-sum wexoe-pa-price-col">Summa</span>';
    $html .= '<span class="wexoe-pa-req-col-del"></span>';
    $html .= '</div>';
    $html .= '<div class="wexoe-pa-request-table-body">';
    $html .= '<div class="wexoe-pa-request-empty">Inga artiklar tillagda ännu.</div>';
    $html .= '</div>';
    // Footer row: add article + total on same line
    $html .= '<div class="wexoe-pa-request-footer-row">';
    $html .= '<div class="wexoe-pa-request-add-wrap">';
    $html .= '<button type="button" class="wexoe-pa-request-add-btn">+ Lägg till artikel</button>';
    $html .= '<div class="wexoe-pa-request-search-dropdown">';
    $html .= '<input type="text" class="wexoe-pa-request-search-input" placeholder="Sök artikel...">';
    $html .= '<div class="wexoe-pa-request-search-results"></div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="wexoe-pa-request-total wexoe-pa-price-col">';
    $html .= '<span class="wexoe-pa-request-total-label">Totalt:</span>';
    $html .= '<span class="wexoe-pa-request-total-value">0 kr</span>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    // Message field
    $html .= '<div class="wexoe-pa-request-msg-row">';
    $html .= '<label>Meddelande</label>';
    $html .= '<textarea name="meddelande" rows="3"></textarea>';
    $html .= '</div>';

    // GDPR + Submit
    $html .= '<div class="wexoe-pa-request-bottom">';
    $html .= '<label class="wexoe-pa-request-gdpr"><input type="checkbox" name="gdpr_consent" value="1"><span>Ja, jag vill ta emot nyheter, tips och erbjudanden från Wexoe Industry via e-post.</span></label>';
    $html .= '<button type="submit" class="wexoe-pa-request-submit"><span class="wexoe-pa-req-btn-text">Skicka prisförfrågan</span> <span class="wexoe-pa-cta-arrow">&rarr;</span></button>';
    $html .= '</div>';

    $html .= '</form>';

    // Success message
    $html .= '<div class="wexoe-pa-request-success">';
    $html .= '<div class="wexoe-pa-request-success-icon">&#10003;</div>';
    $html .= '<h3>Tack för din prisförfrågan!</h3>';
    $html .= '<p>Vi har mottagit din förfrågan och återkommer till dig så snart som möjligt.</p>';
    $html .= '</div>';

    $html .= '</div></section>';
    return $html;
}

/* ============================================================
   CSS
   ============================================================ */

function wexoe_pa_test_render_css($id) {
    return '
    <style>
    @import url("https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap");

    /* === GLOBAL RESET === */
    #'.$id.' {
        font-family: "DM Sans", system-ui, -apple-system, sans-serif !important;
        line-height: 1.6 !important;
        box-sizing: border-box !important;
        color: #11325D !important;
    }
    #'.$id.' *,
    #'.$id.' *::before,
    #'.$id.' *::after {
        box-sizing: border-box !important;
    }
    #'.$id.' li::before {
        content: none !important;
        display: none !important;
    }
    #'.$id.' img {
        max-width: 100% !important;
        height: auto !important;
    }
    #'.$id.' a {
        text-decoration: none !important;
    }
    /* Markdown inline styles */
    #'.$id.' p a,
    #'.$id.' li a,
    #'.$id.' .wexoe-pa-contact-quote a {
        text-decoration: underline !important;
        text-underline-offset: 2px !important;
    }
    #'.$id.' p a:hover,
    #'.$id.' li a:hover {
        opacity: 0.8 !important;
    }
    #'.$id.' code {
        background: rgba(0,0,0,0.06) !important;
        padding: 1px 5px !important;
        border-radius: 3px !important;
        font-size: 0.9em !important;
        font-family: monospace !important;
    }
    #'.$id.' del {
        opacity: 0.6 !important;
    }

    /* === FULLWIDTH HELPER === */
    #'.$id.' .wexoe-pa-fullwidth {
        width: 100vw !important;
        margin-left: calc(-50vw + 50%) !important;
    }

    /* === SECTION 1: TOP BANNER === */
    #'.$id.' .wexoe-pa-top {
        background: var(--top-bg) !important;
        width: 100vw !important;
        margin-left: calc(-50vw + 50%) !important;
        padding: 0 !important;
        overflow: hidden !important;
        position: relative !important;
        margin-top: -1px !important;
    }
    #'.$id.' .wexoe-pa-top-inner {
        max-width: 1270px !important;
        margin: 0 auto !important;
        padding: 32px 40px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        position: relative !important;
        min-height: 90px !important;
    }
    #'.$id.' .wexoe-pa-top-h1 {
        color: var(--top-text) !important;
        font-size: 26px !important;
        font-weight: 600 !important;
        letter-spacing: 0.08em !important;
        text-transform: uppercase !important;
        margin: 0 !important;
        padding: 0 !important;
        text-align: center !important;
    }

    /* === SECTION 2: HERO === */
    #'.$id.' .wexoe-pa-hero {
        background: var(--hero-bg) !important;
        width: 100vw !important;
        margin-left: calc(-50vw + 50%) !important;
        padding: 0 !important;
    }
    #'.$id.' .wexoe-pa-hero-inner {
        max-width: 1270px !important;
        margin: 0 auto !important;
        padding: 60px 40px !important;
        display: flex !important;
        gap: 50px !important;
        align-items: flex-start !important;
    }
    #'.$id.' .wexoe-pa-hero-content {
        flex: 1 1 58% !important;
        min-width: 0 !important;
    }
    #'.$id.' .wexoe-pa-hero-h2 {
        color: var(--hero-text) !important;
        font-size: 36px !important;
        font-weight: 700 !important;
        line-height: 1.2 !important;
        margin: 0 0 20px 0 !important;
        padding: 0 !important;
    }
    #'.$id.' .wexoe-pa-hero-text {
        color: var(--hero-text-secondary) !important;
        font-size: 17px !important;
        line-height: 1.7 !important;
        margin: 0 0 28px 0 !important;
        padding: 0 !important;
    }
    #'.$id.' .wexoe-pa-hero-text strong,
    #'.$id.' .wexoe-pa-hero-text em,
    #'.$id.' .wexoe-pa-hero-text del,
    #'.$id.' .wexoe-pa-hero-text code,
    #'.$id.' .wexoe-pa-hero-text span {
        color: inherit !important;
    }
    #'.$id.' .wexoe-pa-benefits-list li strong,
    #'.$id.' .wexoe-pa-benefits-list li em,
    #'.$id.' .wexoe-pa-benefits-list li del,
    #'.$id.' .wexoe-pa-benefits-list li code,
    #'.$id.' .wexoe-pa-benefits-list li span {
        color: inherit !important;
    }
    #'.$id.' .wexoe-pa-hero-cta {
        display: inline-flex !important;
        align-items: center !important;
        gap: 10px !important;
        background: var(--hero-accent) !important;
        color: #FFFFFF !important;
        font-size: 16px !important;
        font-weight: 600 !important;
        padding: 14px 32px !important;
        border-radius: 6px !important;
        border: none !important;
        cursor: pointer !important;
        transition: opacity 0.2s ease, transform 0.2s ease !important;
        text-decoration: none !important;
    }
    #'.$id.' .wexoe-pa-hero-cta:hover {
        opacity: 0.9 !important;
        transform: translateY(-1px) !important;
        color: #FFFFFF !important;
    }
    #'.$id.' .wexoe-pa-cta-arrow {
        transition: transform 0.2s ease !important;
        display: inline-block !important;
    }
    #'.$id.' .wexoe-pa-hero-cta:hover .wexoe-pa-cta-arrow {
        transform: translateX(3px) !important;
    }
    #'.$id.' .wexoe-pa-hero-benefits {
        flex: 1 1 38% !important;
        min-width: 280px !important;
        background: #F8F9FA !important;
        border: 1px solid #E8E8E8 !important;
        border-radius: 10px !important;
        padding: 30px 28px !important;
        align-self: center !important;
    }
    #'.$id.' .wexoe-pa-benefits-list {
        list-style: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    #'.$id.' .wexoe-pa-benefits-list li {
        font-size: 15px !important;
        line-height: 1.6 !important;
        color: var(--hero-text) !important;
        padding: 8px 0 !important;
        padding-left: 0 !important;
        margin: 0 !important;
        display: flex !important;
        align-items: flex-start !important;
        gap: 12px !important;
        background-image: none !important;
        list-style: none !important;
    }
    #'.$id.' .wexoe-pa-benefits-list li + li {
        border-top: 1px solid #E8E8E8 !important;
    }
    #'.$id.' .wexoe-pa-check {
        color: #10B981 !important;
        font-weight: 700 !important;
        font-size: 16px !important;
        flex-shrink: 0 !important;
        margin-top: 2px !important;
    }

    /* NPI Card */
    #'.$id.' .wexoe-pa-npi-card {
        flex: 1 1 34% !important;
        min-width: 250px !important;
        max-width: 380px !important;
        background: #FFFFFF !important;
        border-radius: 10px !important;
        overflow: hidden !important;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08) !important;
        text-decoration: none !important;
        display: flex !important;
        flex-direction: column !important;
        align-self: center !important;
        transition: transform 0.25s ease, box-shadow 0.25s ease !important;
    }
    #'.$id.' a.wexoe-pa-npi-card:hover {
        transform: translateY(-3px) !important;
        box-shadow: 0 6px 20px rgba(0,0,0,0.12) !important;
    }
    #'.$id.' .wexoe-pa-npi-img-wrap {
        aspect-ratio: 16/9 !important;
        overflow: hidden !important;
        background: #F5F5F5 !important;
    }
    #'.$id.' .wexoe-pa-npi-img-wrap img {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        display: block !important;
        border-radius: 0 !important;
    }
    #'.$id.' .wexoe-pa-npi-body {
        padding: 16px 18px 20px !important;
    }
    #'.$id.' .wexoe-pa-npi-badge {
        display: inline-block !important;
        background: #11325D !important;
        color: #FFFFFF !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        letter-spacing: 0.08em !important;
        padding: 3px 10px !important;
        border-radius: 4px !important;
        margin-bottom: 10px !important;
    }
    #'.$id.' .wexoe-pa-npi-title {
        font-size: 18px !important;
        font-weight: 700 !important;
        color: #11325D !important;
        margin-bottom: 6px !important;
        line-height: 1.3 !important;
    }
    #'.$id.' .wexoe-pa-npi-desc {
        font-size: 14px !important;
        line-height: 1.6 !important;
        color: #555 !important;
        margin: 0 0 14px 0 !important;
        padding: 0 !important;
        display: -webkit-box !important;
        -webkit-line-clamp: 3 !important;
        -webkit-box-orient: vertical !important;
        overflow: hidden !important;
    }
    #'.$id.' .wexoe-pa-npi-cta {
        font-size: 14px !important;
        font-weight: 600 !important;
        color: #11325D !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 4px !important;
        text-decoration: none !important;
    }

    /* Hero Image */
    #'.$id.' .wexoe-pa-hero-image {
        flex: 1 1 38% !important;
        min-width: 280px !important;
        align-self: center !important;
    }
    #'.$id.' .wexoe-pa-hero-image img {
        width: 100% !important;
        height: auto !important;
        border-radius: 10px !important;
        display: block !important;
        object-fit: contain !important;
    }
    /* Video embeds (NPI + Hero) */
    #'.$id.' .wexoe-pa-npi-video-wrap,
    #'.$id.' .wexoe-pa-hero-image .video-wrap {
        position: relative !important;
        width: 100% !important;
        padding-bottom: 56.25% !important;
        height: 0 !important;
        overflow: hidden !important;
        z-index: 1 !important;
    }
    #'.$id.' .wexoe-pa-npi-video-wrap iframe,
    #'.$id.' .wexoe-pa-hero-image .video-wrap iframe {
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 100% !important;
        border: none !important;
        border-radius: 0 !important;
    }
    #'.$id.' .wexoe-pa-hero-image .video-wrap {
        border-radius: 10px !important;
    }

    /* === SECTION 3: PRODUCT TOGGLE === */
    #'.$id.' .wexoe-pa-toggle {
        background: var(--toggle-bg) !important;
        width: 100vw !important;
        margin-left: calc(-50vw + 50%) !important;
        padding: 0 !important;
    }
    #'.$id.' .wexoe-pa-toggle-inner {
        max-width: 1270px !important;
        margin: 0 auto !important;
        padding: 50px 40px !important;
    }
    #'.$id.' .wexoe-pa-toggle-item {
        background: var(--toggle-header-bg) !important;
        border-radius: 8px !important;
        margin-bottom: 10px !important;
        overflow: hidden !important;
        border: none !important;
        transition: box-shadow 0.2s ease !important;
    }
    #'.$id.' .wexoe-pa-toggle-item:hover {
        box-shadow: 0 2px 12px rgba(0,0,0,0.06) !important;
    }
    #'.$id.' .wexoe-pa-toggle-header {
        all: unset !important;
        display: flex !important;
        align-items: center !important;
        width: 100% !important;
        padding: 22px 28px !important;
        cursor: pointer !important;
        font-family: "DM Sans", system-ui, sans-serif !important;
        background: transparent !important;
        border: none !important;
        text-align: left !important;
        transition: background 0.15s ease !important;
        box-sizing: border-box !important;
        overflow: hidden !important;
    }
    #'.$id.' .wexoe-pa-toggle-header:hover {
        background: rgba(0,0,0,0.015) !important;
    }
    #'.$id.' .wexoe-pa-toggle-header-text {
        flex: 1 !important;
        min-width: 0 !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        display: flex !important;
        align-items: baseline !important;
        gap: 8px !important;
        flex-wrap: wrap !important;
        margin-right: 16px !important;
    }
    #'.$id.' .wexoe-pa-toggle-name {
        font-size: 17px !important;
        font-weight: 600 !important;
        color: #11325D !important;
    }
    #'.$id.' .wexoe-pa-toggle-eco {
        font-size: 14px !important;
        font-weight: 400 !important;
        color: #999 !important;
    }
    #'.$id.' .wexoe-pa-toggle-icon {
        flex-shrink: 0 !important;
        width: 16px !important;
        height: 16px !important;
        position: relative !important;
    }
    #'.$id.' .wexoe-pa-svg-plus,
    #'.$id.' .wexoe-pa-svg-minus {
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
    }
    #'.$id.' .wexoe-pa-svg-minus {
        opacity: 0 !important;
    }
    #'.$id.' .wexoe-pa-toggle-open .wexoe-pa-svg-plus {
        opacity: 0 !important;
    }
    #'.$id.' .wexoe-pa-toggle-open .wexoe-pa-svg-minus {
        opacity: 1 !important;
    }
    #'.$id.' .wexoe-pa-toggle-body {
        overflow: hidden !important;
        transition: max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    #'.$id.' .wexoe-pa-toggle-body-inner {
        display: flex !important;
        gap: 36px !important;
        padding: 0 28px 28px 28px !important;
        padding-top: 8px !important;
    }
    #'.$id.' .wexoe-pa-toggle-left {
        flex: 1 1 55% !important;
        min-width: 0 !important;
    }
    #'.$id.' .wexoe-pa-toggle-right {
        flex: 0 0 260px !important;
        min-width: 0 !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
    }
    #'.$id.' .wexoe-pa-toggle-desc {
        font-size: 15px !important;
        line-height: 1.7 !important;
        color: #555 !important;
        margin: 0 0 16px 0 !important;
        padding: 0 !important;
    }
    #'.$id.' .wexoe-pa-toggle-desc strong,
    #'.$id.' .wexoe-pa-toggle-desc em,
    #'.$id.' .wexoe-pa-toggle-desc del,
    #'.$id.' .wexoe-pa-toggle-desc code,
    #'.$id.' .wexoe-pa-toggle-desc span {
        color: inherit !important;
    }
    #'.$id.' .wexoe-pa-toggle-checks {
        list-style: none !important;
        padding: 0 !important;
        margin: 0 0 20px 0 !important;
    }
    #'.$id.' .wexoe-pa-toggle-checks li {
        font-size: 14px !important;
        line-height: 1.65 !important;
        color: #555 !important;
        padding: 4px 0 !important;
        margin: 0 !important;
        background-image: none !important;
        padding-left: 0 !important;
        display: flex !important;
        align-items: flex-start !important;
        gap: 10px !important;
    }
    #'.$id.' .wexoe-pa-toggle-checks li strong,
    #'.$id.' .wexoe-pa-toggle-checks li em,
    #'.$id.' .wexoe-pa-toggle-checks li del,
    #'.$id.' .wexoe-pa-toggle-checks li code,
    #'.$id.' .wexoe-pa-toggle-checks li span {
        color: inherit !important;
    }
    #'.$id.' .wexoe-pa-toggle-checks .wexoe-pa-check {
        color: #10B981 !important;
        font-weight: 700 !important;
        font-size: 15px !important;
        flex-shrink: 0 !important;
        margin-top: 1px !important;
    }
    #'.$id.' .wexoe-pa-toggle-btns {
        display: flex !important;
        gap: 10px !important;
        margin-top: 4px !important;
    }
    #'.$id.' .wexoe-pa-toggle-btns .wexoe-pa-btn {
        flex: 0 0 auto !important;
        min-width: 180px !important;
    }
    #'.$id.' .wexoe-pa-toggle-btns.wexoe-pa-btns-single .wexoe-pa-btn {
        flex: 0 0 auto !important;
        min-width: 220px !important;
    }
    #'.$id.' .wexoe-pa-toggle-img-wrap {
        width: 260px !important;
        height: 200px !important;
        overflow: hidden !important;
        border-radius: 8px !important;
        background: #FFFFFF !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    #'.$id.' .wexoe-pa-toggle-img {
        max-width: 100% !important;
        max-height: 100% !important;
        width: auto !important;
        height: auto !important;
        object-fit: contain !important;
        border-radius: 0 !important;
        display: block !important;
    }
    #'.$id.' .wexoe-pa-btn {
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
        padding: 12px 22px !important;
        border-radius: 6px !important;
        font-size: 14px !important;
        font-weight: 600 !important;
        font-family: "DM Sans", system-ui, sans-serif !important;
        cursor: pointer !important;
        transition: opacity 0.2s ease, transform 0.15s ease !important;
        text-decoration: none !important;
        flex: 1 1 0 !important;
        justify-content: center !important;
        text-align: center !important;
    }
    #'.$id.' .wexoe-pa-btn:hover {
        transform: translateY(-1px) !important;
        opacity: 0.9 !important;
    }
    #'.$id.' .wexoe-pa-btn-primary {
        background: var(--toggle-accent) !important;
        color: #FFFFFF !important;
        border: none !important;
    }
    #'.$id.' .wexoe-pa-btn-primary:hover {
        color: #FFFFFF !important;
    }
    #'.$id.' .wexoe-pa-btn-secondary {
        background: transparent !important;
        color: #11325D !important;
        border: 2px solid #11325D !important;
    }
    #'.$id.' .wexoe-pa-btn-secondary:hover {
        background: #11325D !important;
        color: #FFFFFF !important;
    }

    /* === SECTION 4: SOLUTIONS === */
    #'.$id.' .wexoe-pa-solutions {
        background: var(--solutions-bg) !important;
        width: 100vw !important;
        margin-left: calc(-50vw + 50%) !important;
        padding: 0 !important;
    }
    #'.$id.' .wexoe-pa-solutions-inner {
        max-width: 1270px !important;
        margin: 0 auto !important;
        padding: 60px 40px !important;
    }
    #'.$id.' .wexoe-pa-solutions-h2 {
        font-size: 30px !important;
        font-weight: 700 !important;
        color: var(--solutions-text) !important;
        margin: 0 0 32px 0 !important;
        padding: 0 !important;
        text-align: center !important;
    }
    #'.$id.' .wexoe-pa-solutions-grid {
        display: grid !important;
        grid-template-columns: repeat(auto-fit, minmax(240px, 280px)) !important;
        gap: 24px !important;
    }
    @media (min-width: 768px) {
        #'.$id.' .wexoe-pa-solutions-grid {
            justify-content: center !important;
        }
    }
    #'.$id.' .wexoe-pa-solution-card {
        background: var(--solutions-card-bg) !important;
        border-radius: 10px !important;
        overflow: hidden !important;
        box-shadow: 0 1px 6px rgba(0,0,0,0.07) !important;
        transition: transform 0.25s ease, box-shadow 0.25s ease !important;
        display: flex !important;
        flex-direction: column !important;
        text-decoration: none !important;
        border: 1px solid rgba(0,0,0,0.04) !important;
    }
    #'.$id.' .wexoe-pa-solution-card:hover {
        transform: translateY(-4px) !important;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12) !important;
    }
    #'.$id.' .wexoe-pa-solution-img-wrap {
        aspect-ratio: 16/9 !important;
        overflow: hidden !important;
        background: #FFFFFF !important;
        flex-shrink: 0 !important;
    }
    #'.$id.' .wexoe-pa-solution-img {
        width: 100% !important;
        height: 100% !important;
        object-fit: contain !important;
        object-position: center !important;
        transition: transform 0.3s ease !important;
        border-radius: 0 !important;
        display: block !important;
    }
    #'.$id.' .wexoe-pa-solution-card:hover .wexoe-pa-solution-img {
        transform: scale(1.05) !important;
    }
    #'.$id.' .wexoe-pa-solution-body {
        padding: 20px 22px 24px !important;
        display: flex !important;
        flex-direction: column !important;
        flex: 1 !important;
    }
    #'.$id.' .wexoe-pa-solution-cat {
        font-size: 11px !important;
        font-weight: 600 !important;
        letter-spacing: 0.08em !important;
        color: #777 !important;
        margin-bottom: 6px !important;
        display: block !important;
    }
    #'.$id.' .wexoe-pa-solution-title {
        font-size: 18px !important;
        font-weight: 700 !important;
        color: #11325D !important;
        margin-bottom: 10px !important;
        line-height: 1.3 !important;
    }
    #'.$id.' .wexoe-pa-solution-desc {
        font-size: 14px !important;
        line-height: 1.6 !important;
        color: #555 !important;
        margin: 0 0 16px 0 !important;
        padding: 0 !important;
        display: -webkit-box !important;
        -webkit-line-clamp: 4 !important;
        -webkit-box-orient: vertical !important;
        overflow: hidden !important;
        flex: 1 !important;
    }
    #'.$id.' .wexoe-pa-solution-cta {
        font-size: 14px !important;
        font-weight: 600 !important;
        color: #F28C28 !important;
        margin-top: auto !important;
        display: inline-block !important;
        transition: color 0.2s ease !important;
    }
    #'.$id.' .wexoe-pa-solution-card:hover .wexoe-pa-solution-cta {
        color: #11325D !important;
    }

    /* === SECTION 5: NORMAL === */
    #'.$id.' .wexoe-pa-normal {
        background: var(--normal-bg) !important;
        width: 100vw !important;
        margin-left: calc(-50vw + 50%) !important;
        padding: 0 !important;
    }
    #'.$id.' .wexoe-pa-normal-inner {
        max-width: 1270px !important;
        margin: 0 auto !important;
        padding: 70px 40px !important;
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 60px !important;
        align-items: center !important;
    }
    #'.$id.' .wexoe-pa-normal-text {
        min-width: 0 !important;
    }
    #'.$id.' .wexoe-pa-normal-image {
        min-width: 0 !important;
    }
    #'.$id.' .wexoe-pa-normal-reversed .wexoe-pa-normal-image {
        order: -1 !important;
    }
    #'.$id.' .wexoe-pa-normal-img-wrap {
        width: 100% !important;
        border-radius: 10px !important;
        overflow: hidden !important;
    }
    #'.$id.' .wexoe-pa-normal-img-wrap img {
        width: 100% !important;
        height: auto !important;
        display: block !important;
        border-radius: 0 !important;
    }
    #'.$id.' .wexoe-pa-normal-h2 {
        font-size: 28px !important;
        font-weight: 700 !important;
        color: var(--normal-text) !important;
        margin: 0 0 18px 0 !important;
        padding: 0 !important;
        line-height: 1.35 !important;
    }
    #'.$id.' .wexoe-pa-normal-body {
        font-size: 16px !important;
        line-height: 1.7 !important;
        color: var(--normal-text-secondary) !important;
        margin: 0 0 18px 0 !important;
        padding: 0 !important;
    }
    #'.$id.' .wexoe-pa-normal-body strong,
    #'.$id.' .wexoe-pa-normal-body em,
    #'.$id.' .wexoe-pa-normal-body del,
    #'.$id.' .wexoe-pa-normal-body code,
    #'.$id.' .wexoe-pa-normal-body a,
    #'.$id.' .wexoe-pa-normal-body span {
        color: var(--normal-text-secondary) !important;
    }
    #'.$id.' .wexoe-pa-normal p {
        color: var(--normal-text-secondary) !important;
    }
    #'.$id.' .wexoe-pa-normal-checks li,
    #'.$id.' .wexoe-pa-normal-checks strong,
    #'.$id.' .wexoe-pa-normal-checks em,
    #'.$id.' .wexoe-pa-normal-checks a {
        color: var(--normal-text-secondary) !important;
    }
    #'.$id.' .wexoe-pa-normal-checks {
        list-style: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    #'.$id.' .wexoe-pa-normal-checks li {
        font-size: 15px !important;
        line-height: 1.65 !important;
        color: var(--normal-text-secondary) !important;
        padding: 4px 0 !important;
        margin: 0 !important;
        background-image: none !important;
        padding-left: 0 !important;
        display: flex !important;
        align-items: flex-start !important;
        gap: 10px !important;
    }
    #'.$id.' .wexoe-pa-normal-checks .wexoe-pa-check {
        color: #10B981 !important;
        font-weight: 700 !important;
        font-size: 15px !important;
        flex-shrink: 0 !important;
        margin-top: 1px !important;
    }

    /* === SECTION 6: OUR GUY === */
    #'.$id.' .wexoe-pa-contact {
        background: var(--contact-bg) !important;
        width: 100vw !important;
        margin-left: calc(-50vw + 50%) !important;
        padding: 0 !important;
    }
    #'.$id.' .wexoe-pa-contact-inner {
        max-width: 800px !important;
        margin: 0 auto !important;
        padding: 50px 40px !important;
        display: flex !important;
        gap: 40px !important;
        align-items: center !important;
    }
    #'.$id.' .wexoe-pa-contact-left {
        display: flex !important;
        align-items: center !important;
        gap: 24px !important;
        flex-shrink: 0 !important;
    }
    #'.$id.' .wexoe-pa-contact-photo {
        flex-shrink: 0 !important;
    }
    #'.$id.' .wexoe-pa-contact-photo img {
        width: 110px !important;
        height: 110px !important;
        border-radius: 50% !important;
        object-fit: cover !important;
        border: 3px solid rgba(255,255,255,0.2) !important;
    }
    #'.$id.' .wexoe-pa-contact-info {
        flex-shrink: 0 !important;
    }
    #'.$id.' .wexoe-pa-contact-name {
        font-size: 19px !important;
        font-weight: 700 !important;
        color: #FFFFFF !important;
        margin-bottom: 2px !important;
    }
    #'.$id.' .wexoe-pa-contact-title {
        font-size: 14px !important;
        color: #FFFFFF !important;
        margin-bottom: 10px !important;
        opacity: 0.85 !important;
    }
    #'.$id.' .wexoe-pa-contact-detail {
        font-size: 14px !important;
        margin-bottom: 3px !important;
    }
    #'.$id.' .wexoe-pa-contact-detail a {
        color: #FFFFFF !important;
        text-decoration: underline !important;
        text-underline-offset: 2px !important;
        opacity: 0.85 !important;
    }
    #'.$id.' .wexoe-pa-contact-detail a:hover {
        opacity: 1 !important;
    }
    #'.$id.' .wexoe-pa-contact-quote {
        flex: 1 !important;
        min-width: 0 !important;
        position: relative !important;
        padding: 0 0 0 20px !important;
        border-left: 2px solid rgba(255,255,255,0.2) !important;
    }
    #'.$id.' .wexoe-pa-quote-open {
        display: block !important;
        margin-bottom: 6px !important;
        opacity: 0.35 !important;
    }
    #'.$id.' .wexoe-pa-quote-close {
        display: block !important;
        margin-top: 6px !important;
        margin-left: auto !important;
        margin-bottom: 0 !important;
        opacity: 0.35 !important;
    }
    #'.$id.' .wexoe-pa-contact-quote p {
        font-size: 16px !important;
        line-height: 1.7 !important;
        color: #FFFFFF !important;
        margin: 0 !important;
        padding: 0 !important;
        font-style: italic !important;
        position: relative !important;
        z-index: 1 !important;
    }
    #'.$id.' .wexoe-pa-contact-quote p strong,
    #'.$id.' .wexoe-pa-contact-quote p em,
    #'.$id.' .wexoe-pa-contact-quote p del,
    #'.$id.' .wexoe-pa-contact-quote p code,
    #'.$id.' .wexoe-pa-contact-quote p a,
    #'.$id.' .wexoe-pa-contact-quote p span {
        color: inherit !important;
    }

    /* === SECTION 7: DOCUMENTATION === */
    #'.$id.' .wexoe-pa-docs {
        background: var(--docs-bg) !important;
        width: 100vw !important;
        margin-left: calc(-50vw + 50%) !important;
        padding: 0 !important;
    }
    #'.$id.' .wexoe-pa-docs-inner {
        max-width: 1270px !important;
        margin: 0 auto !important;
        padding: 60px 40px !important;
        text-align: center !important;
    }
    #'.$id.' .wexoe-pa-docs-h2 {
        font-size: 30px !important;
        font-weight: 700 !important;
        color: var(--docs-text) !important;
        margin: 0 0 28px 0 !important;
        padding: 0 !important;
    }
    #'.$id.' .wexoe-pa-docs-iframe-wrap {
        width: 100% !important;
        border-radius: 8px !important;
        overflow: hidden !important;
    }
    #'.$id.' .wexoe-pa-docs-iframe-wrap iframe {
        display: block !important;
        width: 100% !important;
        min-height: 300px !important;
        border: none !important;
    }

    /* === SECTION 3b: SIDE MENU === */
    #'.$id.' .wexoe-pa-sidemenu {
        background: var(--sm-bg) !important;
        width: 100vw !important;
        margin-left: calc(-50vw + 50%) !important;
        padding: 0 !important;
    }
    #'.$id.' .wexoe-pa-sidemenu-inner {
        max-width: 1270px !important;
        margin: 0 auto !important;
        padding: 70px 40px !important;
        display: flex !important;
        gap: 40px !important;
        align-items: flex-start !important;
    }
    /* Mobile select - hidden on desktop */
    #'.$id.' .wexoe-pa-sm-mobile-select-wrap {
        display: none !important;
    }
    /* Sidebar nav */
    #'.$id.' .wexoe-pa-sm-sidebar {
        flex: 0 0 200px !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 0 !important;
    }
    #'.$id.' .wexoe-pa-sm-nav-item {
        all: unset !important;
        display: block !important;
        padding: 12px 16px !important;
        font-family: "DM Sans", system-ui, sans-serif !important;
        font-size: 16px !important;
        font-weight: 500 !important;
        color: rgba(255,255,255,0.85) !important;
        cursor: pointer !important;
        border-left: 3px solid transparent !important;
        transition: color 0.2s ease, border-color 0.2s ease, background 0.2s ease !important;
        box-sizing: border-box !important;
        line-height: 1.4 !important;
        text-align: left !important;
    }
    #'.$id.' .wexoe-pa-sm-nav-item:hover {
        color: #FFFFFF !important;
        background: rgba(255,255,255,0.04) !important;
    }
    #'.$id.' .wexoe-pa-sm-nav-active {
        color: var(--sm-accent) !important;
        border-left-color: var(--sm-accent) !important;
        font-weight: 600 !important;
    }
    #'.$id.' .wexoe-pa-sm-nav-active:hover {
        color: var(--sm-accent) !important;
    }
    /* Content area */
    #'.$id.' .wexoe-pa-sm-content {
        flex: 1 !important;
        min-width: 0 !important;
    }
    #'.$id.' .wexoe-pa-sm-h2 {
        font-size: 28px !important;
        font-weight: 700 !important;
        color: var(--sm-text) !important;
        margin: 0 0 16px 0 !important;
        padding: 0 !important;
        line-height: 1.3 !important;
    }
    #'.$id.' .wexoe-pa-sm-desc {
        font-size: 15px !important;
        line-height: 1.7 !important;
        color: #FFFFFF !important;
        margin: 0 0 16px 0 !important;
        padding: 0 !important;
    }
    #'.$id.' .wexoe-pa-sm-desc strong,
    #'.$id.' .wexoe-pa-sm-desc em,
    #'.$id.' .wexoe-pa-sm-desc del,
    #'.$id.' .wexoe-pa-sm-desc code,
    #'.$id.' .wexoe-pa-sm-desc a:not(.wexoe-pa-btn),
    #'.$id.' .wexoe-pa-sm-desc span {
        color: inherit !important;
    }
    #'.$id.' .wexoe-pa-sm-checks li strong,
    #'.$id.' .wexoe-pa-sm-checks li em,
    #'.$id.' .wexoe-pa-sm-checks li del,
    #'.$id.' .wexoe-pa-sm-checks li code,
    #'.$id.' .wexoe-pa-sm-checks li span {
        color: inherit !important;
    }
    #'.$id.' .wexoe-pa-sm-panel a:not(.wexoe-pa-btn):not(.wexoe-pa-sm-article-btn-order):not(.wexoe-pa-sm-article-btn-ds) {
        color: #64B5F6 !important;
    }
    #'.$id.' .wexoe-pa-sm-panel a:not(.wexoe-pa-btn):not(.wexoe-pa-sm-article-btn-order):not(.wexoe-pa-sm-article-btn-ds):hover {
        color: #90CAF9 !important;
    }
    #'.$id.' .wexoe-pa-sm-checks {
        list-style: none !important;
        padding: 0 !important;
        margin: 0 0 20px 0 !important;
    }
    #'.$id.' .wexoe-pa-sm-checks li {
        font-size: 14px !important;
        line-height: 1.65 !important;
        color: #FFFFFF !important;
        padding: 4px 0 !important;
        margin: 0 !important;
        background-image: none !important;
        padding-left: 0 !important;
        display: flex !important;
        align-items: flex-start !important;
        gap: 10px !important;
    }
    /* Content row: text + image side by side */
    #'.$id.' .wexoe-pa-sm-row {
        display: flex !important;
        gap: 32px !important;
        align-items: flex-start !important;
        margin-bottom: 4px !important;
    }
    #'.$id.' .wexoe-pa-sm-text-col {
        flex: 1 !important;
        min-width: 0 !important;
    }
    #'.$id.' .wexoe-pa-sm-img-wrap {
        flex: 0 0 220px !important;
        margin: 0 !important;
        max-width: 220px !important;
    }
    #'.$id.' .wexoe-pa-sm-img-wrap img {
        width: 100% !important;
        height: auto !important;
        border-radius: 8px !important;
        display: block !important;
    }
    #'.$id.' .wexoe-pa-sm-img-wrap .wexoe-pa-sm-video-wrap {
        position: relative !important;
        width: 100% !important;
        padding-bottom: 56.25% !important;
        height: 0 !important;
        overflow: hidden !important;
        border-radius: 8px !important;
    }
    #'.$id.' .wexoe-pa-sm-img-wrap .wexoe-pa-sm-video-wrap iframe {
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 100% !important;
        border: none !important;
    }
    #'.$id.' .wexoe-pa-sm-btns {
        display: flex !important;
        gap: 10px !important;
        margin: 20px 0 !important;
    }
    #'.$id.' .wexoe-pa-sm-btns .wexoe-pa-btn {
        flex: 0 0 auto !important;
        min-width: 160px !important;
    }
    /* Side menu uses light button variants */
    #'.$id.' .wexoe-pa-sidemenu .wexoe-pa-btn-primary {
        background: var(--sm-accent) !important;
        color: #FFFFFF !important;
    }
    #'.$id.' .wexoe-pa-sm-btns .wexoe-pa-btn-secondary {
        color: #FFFFFF !important;
        border-color: rgba(255,255,255,0.6) !important;
    }
    #'.$id.' .wexoe-pa-sm-btns .wexoe-pa-btn-secondary:hover {
        background: rgba(255,255,255,0.1) !important;
        border-color: #FFFFFF !important;
        color: #FFFFFF !important;
    }

    /* Article cards */
    #'.$id.' .wexoe-pa-sm-articles {
        margin-top: 32px !important;
        padding-top: 28px !important;
        border-top: 1px solid rgba(255,255,255,0.1) !important;
    }
    #'.$id.' .wexoe-pa-sm-articles-grid {
        display: grid !important;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)) !important;
        gap: 20px !important;
    }
    #'.$id.' .wexoe-pa-sm-article-card {
        background: rgba(255,255,255,0.06) !important;
        border-radius: 8px !important;
        overflow: hidden !important;
        border: 1px solid rgba(255,255,255,0.08) !important;
        transition: background 0.2s ease, transform 0.2s ease !important;
        display: flex !important;
        flex-direction: column !important;
    }
    #'.$id.' .wexoe-pa-sm-article-card:hover {
        background: rgba(255,255,255,0.1) !important;
        transform: translateY(-2px) !important;
    }
    #'.$id.' .wexoe-pa-sm-article-img {
        aspect-ratio: 4/3 !important;
        overflow: hidden !important;
        background: #FFFFFF !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    #'.$id.' .wexoe-pa-sm-article-img img {
        width: 100% !important;
        height: 100% !important;
        object-fit: contain !important;
        display: block !important;
        border-radius: 0 !important;
        padding: 0 !important;
    }
    #'.$id.' .wexoe-pa-sm-article-placeholder {
        min-height: 140px !important;
        background: #FFFFFF !important;
    }
    #'.$id.' .wexoe-pa-sm-article-info {
        padding: 14px 16px 16px !important;
        display: flex !important;
        flex-direction: column !important;
        flex: 1 !important;
    }
    #'.$id.' .wexoe-pa-sm-article-name {
        font-size: 15px !important;
        font-weight: 600 !important;
        color: #FFFFFF !important;
        margin-bottom: 8px !important;
        line-height: 1.3 !important;
        min-height: 2.6em !important;
        display: -webkit-box !important;
        -webkit-line-clamp: 2 !important;
        -webkit-box-orient: vertical !important;
        overflow: hidden !important;
    }
    #'.$id.' .wexoe-pa-sm-article-mid {
        flex: 1 !important;
        display: flex !important;
        flex-direction: column !important;
    }
    #'.$id.' .wexoe-pa-sm-article-nr {
        font-size: 13px !important;
        color: #FFFFFF !important;
        margin-top: auto !important;
        padding-top: 10px !important;
        margin-bottom: 0 !important;
        line-height: 1.4 !important;
        display: flex !important;
        align-items: baseline !important;
        gap: 6px !important;
    }
    #'.$id.' .wexoe-pa-sm-article-nr-label {
        font-size: 11px !important;
        color: rgba(255,255,255,0.5) !important;
        font-weight: 400 !important;
        flex-shrink: 0 !important;
    }
    #'.$id.' .wexoe-pa-sm-article-nr-value {
        font-weight: 600 !important;
    }
    #'.$id.' .wexoe-pa-sm-article-desc {
        font-size: 11px !important;
        line-height: 1.5 !important;
        color: rgba(255,255,255,0.6) !important;
        margin: 0 0 8px 0 !important;
        padding: 0 !important;
        display: -webkit-box !important;
        -webkit-line-clamp: 3 !important;
        -webkit-box-orient: vertical !important;
        overflow: hidden !important;
    }
    #'.$id.' .wexoe-pa-sm-article-btns {
        display: flex !important;
        flex-direction: column !important;
        gap: 6px !important;
        margin-top: 8px !important;
    }
    #'.$id.' .wexoe-pa-sm-article-btn-order {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        min-width: 160px !important;
        padding: 9px 16px !important;
        background: #F28C28 !important;
        color: #FFFFFF !important;
        font-family: "DM Sans", system-ui, sans-serif !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        border-radius: 5px !important;
        border: none !important;
        text-decoration: none !important;
        cursor: pointer !important;
        transition: opacity 0.2s ease, transform 0.15s ease, background 0.2s ease !important;
    }
    #'.$id.' .wexoe-pa-sm-article-btn-order:hover {
        opacity: 0.9 !important;
        transform: translateY(-1px) !important;
        color: #FFFFFF !important;
    }
    #'.$id.' .wexoe-pa-btn-short { display: none !important; }
    #'.$id.' .wexoe-pa-btn-full { display: inline !important; }
    #'.$id.' .wexoe-pa-sm-article-btn-ds {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 9px 16px !important;
        background: #FFFFFF !important;
        color: #11325D !important;
        font-family: "DM Sans", system-ui, sans-serif !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        border-radius: 5px !important;
        border: none !important;
        text-decoration: none !important;
        cursor: pointer !important;
        transition: opacity 0.2s ease, transform 0.15s ease !important;
    }
    #'.$id.' .wexoe-pa-sm-article-btn-ds:hover {
        opacity: 0.85 !important;
        transform: translateY(-1px) !important;
        color: #11325D !important;
    }

    /* Horizontal article cards */
    #'.$id.' .wexoe-pa-sm-articles-horiz {
        grid-template-columns: 1fr !important;
        gap: 10px !important;
    }
    #'.$id.' .wexoe-pa-sm-article-horiz {
        flex-direction: row !important;
        min-height: 90px !important;
    }
    #'.$id.' .wexoe-pa-sm-article-horiz .wexoe-pa-sm-article-img {
        aspect-ratio: auto !important;
        flex: 0 0 110px !important;
        max-width: 110px !important;
        min-height: 90px !important;
        align-self: stretch !important;
    }
    #'.$id.' .wexoe-pa-sm-article-horiz .wexoe-pa-sm-article-img img {
        width: 100% !important;
        height: 100% !important;
        object-fit: contain !important;
        object-position: center !important;
    }
    #'.$id.' .wexoe-pa-sm-article-horiz .wexoe-pa-sm-article-placeholder {
        min-height: 0 !important;
    }
    #'.$id.' .wexoe-pa-sm-article-horiz .wexoe-pa-sm-article-info {
        flex-direction: row !important;
        align-items: center !important;
        gap: 12px !important;
        padding: 14px 16px !important;
        flex-wrap: nowrap !important;
    }
    #'.$id.' .wexoe-pa-sm-article-horiz .wexoe-pa-sm-article-name {
        min-height: 0 !important;
        -webkit-line-clamp: 2 !important;
        overflow: hidden !important;
        flex: 0 0 130px !important;
        margin-bottom: 0 !important;
        font-size: 13px !important;
    }
    #'.$id.' .wexoe-pa-sm-article-horiz .wexoe-pa-sm-article-mid {
        flex: 1 !important;
        flex-direction: row !important;
        align-items: center !important;
        gap: 12px !important;
        min-width: 0 !important;
    }
    #'.$id.' .wexoe-pa-sm-article-horiz .wexoe-pa-sm-variant-wrap {
        margin-bottom: 0 !important;
        display: flex !important;
        gap: 5px !important;
        flex-wrap: nowrap !important;
        flex-shrink: 1 !important;
        min-width: 0 !important;
    }
    #'.$id.' .wexoe-pa-sm-article-horiz .wexoe-pa-sm-variant-row {
        margin-bottom: 0 !important;
        flex-shrink: 1 !important;
        min-width: 0 !important;
    }
    #'.$id.' .wexoe-pa-sm-article-horiz .wexoe-pa-sm-variant-select {
        font-size: 11px !important;
        padding: 5px 22px 5px 8px !important;
        min-width: 0 !important;
        max-width: 130px !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }
    #'.$id.' .wexoe-pa-sm-article-horiz .wexoe-pa-sm-article-nr,
    #'.$id.' .wexoe-pa-sm-article-horiz .wexoe-pa-sm-variant-artnr {
        margin-top: 0 !important;
        padding-top: 0 !important;
        flex-shrink: 0 !important;
        white-space: nowrap !important;
        margin-left: auto !important;
        font-size: 12px !important;
    }
    #'.$id.' .wexoe-pa-sm-article-horiz .wexoe-pa-sm-article-desc {
        margin: 0 !important;
        -webkit-line-clamp: 1 !important;
        font-size: 11px !important;
    }
    #'.$id.' .wexoe-pa-sm-article-horiz .wexoe-pa-sm-article-btns {
        flex-direction: row !important;
        gap: 5px !important;
        margin-top: 0 !important;
        flex-shrink: 0 !important;
    }
    #'.$id.' .wexoe-pa-sm-article-horiz .wexoe-pa-sm-article-btn-order,
    #'.$id.' .wexoe-pa-sm-article-horiz .wexoe-pa-sm-article-btn-ds {
        font-size: 11px !important;
        padding: 7px 12px !important;
        white-space: nowrap !important;
    }
    /* Horizontal mobile fallback — stack on small screens */
    @media (max-width: 767px) {
        /* Horizontal cards revert to standard vertical on mobile */
        #'.$id.' .wexoe-pa-sm-articles-horiz {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)) !important;
        }
        #'.$id.' .wexoe-pa-sm-article-horiz {
            flex-direction: column !important;
            min-height: 0 !important;
        }
        #'.$id.' .wexoe-pa-sm-article-horiz .wexoe-pa-sm-article-img {
            flex: none !important;
            max-width: 100% !important;
            aspect-ratio: 4/3 !important;
            min-height: 0 !important;
            align-self: stretch !important;
        }
        #'.$id.' .wexoe-pa-sm-article-horiz .wexoe-pa-sm-article-info {
            flex-direction: column !important;
            align-items: stretch !important;
            padding: 14px 16px 16px !important;
            gap: 0 !important;
        }
        #'.$id.' .wexoe-pa-sm-article-horiz .wexoe-pa-sm-article-name {
            flex: none !important;
            font-size: 15px !important;
            margin-bottom: 8px !important;
            min-height: 2.6em !important;
            -webkit-line-clamp: 2 !important;
        }
        #'.$id.' .wexoe-pa-sm-article-horiz .wexoe-pa-sm-article-mid {
            flex-direction: column !important;
            flex: 1 !important;
            gap: 0 !important;
        }
        #'.$id.' .wexoe-pa-sm-article-horiz .wexoe-pa-sm-variant-wrap {
            flex-direction: column !important;
            margin-bottom: 8px !important;
        }
        #'.$id.' .wexoe-pa-sm-article-horiz .wexoe-pa-sm-variant-row {
            margin-bottom: 6px !important;
        }
        #'.$id.' .wexoe-pa-sm-article-horiz .wexoe-pa-sm-variant-select {
            display: block !important;
            width: 100% !important;
            max-width: none !important;
            font-size: 13px !important;
            padding: 7px 28px 7px 12px !important;
        }
        #'.$id.' .wexoe-pa-sm-article-horiz .wexoe-pa-sm-article-nr,
        #'.$id.' .wexoe-pa-sm-article-horiz .wexoe-pa-sm-variant-artnr {
            margin-left: 0 !important;
            margin-top: auto !important;
            padding-top: 10px !important;
        }
        #'.$id.' .wexoe-pa-sm-article-horiz .wexoe-pa-sm-article-btns {
            flex-direction: column !important;
            margin-top: 8px !important;
        }
        #'.$id.' .wexoe-pa-sm-article-horiz .wexoe-pa-sm-article-btn-order,
        #'.$id.' .wexoe-pa-sm-article-horiz .wexoe-pa-sm-article-btn-ds {
            font-size: 12px !important;
            padding: 9px 16px !important;
            min-width: 0 !important;
        }
    }

    /* Variant selectors */
    #'.$id.' .wexoe-pa-sm-variant-wrap {
        margin-bottom: 8px !important;
    }
    #'.$id.' .wexoe-pa-sm-variant-row {
        margin-bottom: 6px !important;
    }
    #'.$id.' .wexoe-pa-sm-variant-select {
        all: unset !important;
        display: block !important;
        width: 100% !important;
        padding: 7px 28px 7px 12px !important;
        font-family: "DM Sans", system-ui, sans-serif !important;
        font-size: 13px !important;
        font-weight: 500 !important;
        color: #FFFFFF !important;
        background-color: rgba(255,255,255,0.08) !important;
        border: 1px solid rgba(255,255,255,0.15) !important;
        border-radius: 5px !important;
        cursor: pointer !important;
        box-sizing: border-box !important;
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        appearance: none !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'10\' height=\'6\' fill=\'none\'%3E%3Cpath d=\'M1 1l4 4 4-4\' stroke=\'white\' stroke-width=\'1.5\' stroke-linecap=\'round\' stroke-linejoin=\'round\'/%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important;
        background-position: right 10px center !important;
        background-size: 10px 6px !important;
        line-height: 1.4 !important;
    }
    #'.$id.' .wexoe-pa-sm-variant-select option {
        color: #11325D !important;
        background: #FFFFFF !important;
    }
    #'.$id.' .wexoe-pa-sm-variant-artnr {
        font-size: 13px !important;
        margin-top: auto !important;
        padding-top: 10px !important;
        margin-bottom: 0 !important;
        transition: opacity 0.15s ease !important;
        display: flex !important;
        align-items: baseline !important;
        gap: 6px !important;
        color: #FFFFFF !important;
    }
    #'.$id.' .wexoe-pa-sm-variant-artnr .wexoe-pa-sm-article-nr-label {
        font-size: 11px !important;
        color: rgba(255,255,255,0.5) !important;
        font-weight: 400 !important;
        flex-shrink: 0 !important;
    }
    #'.$id.' .wexoe-pa-sm-variant-artnr .wexoe-pa-sm-article-nr-value {
        font-weight: 600 !important;
    }
    #'.$id.' .wexoe-pa-sm-variant-artnr.wexoe-pa-variant-nomatch {
        color: rgba(255,255,255,0.4) !important;
        font-style: italic !important;
        font-weight: 400 !important;
    }

    /* === REQUEST FORM === */
    #'.$id.' .wexoe-pa-request {
        background: #F8F9FA !important;
        width: 100vw !important;
        margin-left: calc(-50vw + 50%) !important;
        padding: 0 !important;
    }
    #'.$id.' .wexoe-pa-request-inner {
        max-width: 1000px !important;
        margin: 0 auto !important;
        padding: 60px 40px !important;
    }
    #'.$id.' .wexoe-pa-request-h2 {
        font-size: 26px !important;
        font-weight: 700 !important;
        color: #11325D !important;
        margin: 0 0 8px 0 !important;
        padding: 0 !important;
    }
    #'.$id.' .wexoe-pa-request-subtitle {
        font-size: 14px !important;
        color: #666 !important;
        margin: 0 !important;
        padding: 0 !important;
        flex: 1 !important;
        min-width: 0 !important;
    }
    #'.$id.' .wexoe-pa-request-header-row {
        display: flex !important;
        align-items: flex-start !important;
        justify-content: space-between !important;
        gap: 24px !important;
        margin-bottom: 24px !important;
    }
    #'.$id.' .wexoe-pa-request-error {
        display: none !important;
        background: #FEE2E2 !important;
        color: #B91C1C !important;
        padding: 12px 16px !important;
        border-radius: 6px !important;
        font-size: 14px !important;
        margin-bottom: 16px !important;
    }
    #'.$id.' .wexoe-pa-request-error.wexoe-pa-show {
        display: block !important;
    }
    /* Contact fields */
    #'.$id.' .wexoe-pa-request-fields {
        display: grid !important;
        grid-template-columns: 1fr 1fr 1fr 1fr !important;
        gap: 12px !important;
        margin-bottom: 24px !important;
    }
    #'.$id.' .wexoe-pa-request-field label {
        display: block !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        color: #11325D !important;
        margin-bottom: 4px !important;
    }
    #'.$id.' .wexoe-pa-request-field input {
        all: unset !important;
        display: block !important;
        width: 100% !important;
        padding: 10px 12px !important;
        font-family: "DM Sans", system-ui, sans-serif !important;
        font-size: 14px !important;
        color: #11325D !important;
        background: #FFFFFF !important;
        border: 1px solid #D1D5DB !important;
        border-radius: 6px !important;
        box-sizing: border-box !important;
        transition: border-color 0.2s ease !important;
    }
    #'.$id.' .wexoe-pa-request-field input:focus {
        border-color: #F28C28 !important;
        outline: none !important;
    }
    /* Articles table */
    #'.$id.' .wexoe-pa-request-articles {
        background: #FFFFFF !important;
        border: 1px solid #E5E7EB !important;
        border-radius: 8px !important;
        overflow: hidden !important;
        margin-bottom: 20px !important;
    }
    /* Price columns hidden by default */
    #'.$id.' .wexoe-pa-price-col {
        display: none !important;
    }
    /* Show price columns when customer prices loaded */
    #'.$id.' .wexoe-pa-request-articles.wexoe-pa-has-prices .wexoe-pa-price-col {
        display: block !important;
    }
    #'.$id.' .wexoe-pa-request-table-head {
        display: grid !important;
        grid-template-columns: 1.4fr 1.2fr 2.5fr 42px 20px !important;
        gap: 6px !important;
        padding: 10px 12px !important;
        background: #F3F4F6 !important;
        border-bottom: 1px solid #E5E7EB !important;
        font-size: 11px !important;
        font-weight: 600 !important;
        color: #6B7280 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.04em !important;
    }
    #'.$id.' .wexoe-pa-request-articles.wexoe-pa-has-prices .wexoe-pa-request-table-head {
        grid-template-columns: 1.4fr 0.9fr 2.2fr 60px 42px 90px 20px !important;
    }
    #'.$id.' .wexoe-pa-request-table-body {
        min-height: 48px !important;
    }
    #'.$id.' .wexoe-pa-request-empty {
        padding: 20px 12px !important;
        font-size: 13px !important;
        color: #9CA3AF !important;
        text-align: center !important;
        font-style: italic !important;
    }
    #'.$id.' .wexoe-pa-request-row {
        display: grid !important;
        grid-template-columns: 1.4fr 1.2fr 2.5fr 42px 20px !important;
        gap: 6px !important;
        padding: 10px 12px !important;
        border-bottom: 1px solid #F3F4F6 !important;
        align-items: center !important;
        font-size: 13px !important;
        color: #374151 !important;
        animation: wexoe-pa-row-in 0.25s ease !important;
    }
    #'.$id.' .wexoe-pa-request-articles.wexoe-pa-has-prices .wexoe-pa-request-row {
        grid-template-columns: 1.4fr 0.9fr 2.2fr 60px 42px 90px 20px !important;
    }
    @keyframes wexoe-pa-row-in {
        from { opacity: 0; transform: translateY(-8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    #'.$id.' .wexoe-pa-request-row:last-child {
        border-bottom: none !important;
    }
    #'.$id.' .wexoe-pa-req-row-name {
        font-size: 12px !important;
        font-weight: 600 !important;
        color: #11325D !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }
    #'.$id.' .wexoe-pa-req-row-artnr {
        font-family: monospace, monospace !important;
        font-size: 12px !important;
        color: #555 !important;
    }
    #'.$id.' .wexoe-pa-req-row-variant {
        font-size: 12px !important;
        color: #777 !important;
        display: flex !important;
        gap: 4px !important;
        align-items: center !important;
        flex-wrap: nowrap !important;
        min-width: 0 !important;
    }
    #'.$id.' .wexoe-pa-req-row-qty input {
        all: unset !important;
        display: block !important;
        width: 100% !important;
        padding: 5px 4px !important;
        font-family: "DM Sans", system-ui, sans-serif !important;
        font-size: 13px !important;
        color: #11325D !important;
        background: #F9FAFB !important;
        border: 1px solid #D1D5DB !important;
        border-radius: 4px !important;
        box-sizing: border-box !important;
        text-align: center !important;
        -moz-appearance: textfield !important;
    }
    #'.$id.' .wexoe-pa-req-row-qty input::-webkit-outer-spin-button,
    #'.$id.' .wexoe-pa-req-row-qty input::-webkit-inner-spin-button {
        -webkit-appearance: none !important;
        margin: 0 !important;
    }
    #'.$id.' .wexoe-pa-req-row-qty input:focus {
        border-color: #F28C28 !important;
    }
    #'.$id.' .wexoe-pa-req-row-del {
        all: unset !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 20px !important;
        height: 20px !important;
        cursor: pointer !important;
        color: #9CA3AF !important;
        border-radius: 3px !important;
        transition: color 0.15s ease, background 0.15s ease !important;
        font-size: 16px !important;
        line-height: 1 !important;
    }
    #'.$id.' .wexoe-pa-req-row-del:hover {
        color: #EF4444 !important;
        background: #FEE2E2 !important;
    }
    /* Message */
    #'.$id.' .wexoe-pa-request-msg-row {
        margin-bottom: 20px !important;
    }
    #'.$id.' .wexoe-pa-request-msg-row label {
        display: block !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        color: #11325D !important;
        margin-bottom: 4px !important;
    }
    #'.$id.' .wexoe-pa-request-optional {
        font-weight: 400 !important;
        color: #999 !important;
    }
    /* Customer ID toggle */
    #'.$id.' .wexoe-pa-request-customer-toggle {
        flex-shrink: 0 !important;
        display: flex !important;
        align-items: center !important;
    }
    #'.$id.' .wexoe-pa-request-customer-trigger {
        all: unset !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 4px !important;
        font-family: "DM Sans", system-ui, sans-serif !important;
        font-size: 13px !important;
        font-weight: 500 !important;
        color: #6B7280 !important;
        cursor: pointer !important;
        box-sizing: border-box !important;
    }
    #'.$id.' .wexoe-pa-request-customer-trigger:hover {
        color: #11325D !important;
    }
    #'.$id.' .wexoe-pa-request-customer-trigger .wexoe-pa-cta-arrow {
        transition: transform 0.2s ease !important;
        font-size: 16px !important;
    }
    #'.$id.' .wexoe-pa-request-customer-trigger.wexoe-pa-open {
        display: none !important;
    }
    #'.$id.' .wexoe-pa-request-customer-panel {
        display: none !important;
        flex-direction: column !important;
        align-items: flex-end !important;
        gap: 4px !important;
    }
    #'.$id.' .wexoe-pa-request-customer-panel.wexoe-pa-show {
        display: flex !important;
    }
    #'.$id.' .wexoe-pa-request-customer-input-wrap {
        display: flex !important;
        gap: 6px !important;
    }
    #'.$id.' .wexoe-pa-request-customer-input {
        all: unset !important;
        display: block !important;
        width: 160px !important;
        padding: 8px 12px !important;
        font-family: "DM Sans", system-ui, sans-serif !important;
        font-size: 13px !important;
        color: #11325D !important;
        background: #FFFFFF !important;
        border: 1px solid #D1D5DB !important;
        border-radius: 5px !important;
        box-sizing: border-box !important;
    }
    #'.$id.' .wexoe-pa-request-customer-input:focus {
        border-color: #F28C28 !important;
    }
    #'.$id.' .wexoe-pa-request-customer-btn {
        all: unset !important;
        display: inline-flex !important;
        align-items: center !important;
        padding: 8px 16px !important;
        font-family: "DM Sans", system-ui, sans-serif !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        color: #11325D !important;
        background: #FFFFFF !important;
        border: 1px solid #D1D5DB !important;
        border-radius: 5px !important;
        cursor: pointer !important;
        box-sizing: border-box !important;
        transition: border-color 0.2s ease !important;
        white-space: nowrap !important;
    }
    #'.$id.' .wexoe-pa-request-customer-btn:hover {
        border-color: #11325D !important;
    }
    #'.$id.' .wexoe-pa-request-customer-status {
        font-size: 12px !important;
        min-height: 0 !important;
    }
    #'.$id.' .wexoe-pa-request-customer-status:empty {
        display: none !important;
    }
    #'.$id.' .wexoe-pa-request-customer-status.wexoe-pa-status-ok {
        color: #10B981 !important;
        font-weight: 500 !important;
    }
    #'.$id.' .wexoe-pa-request-customer-status.wexoe-pa-status-err {
        color: #EF4444 !important;
    }
    #'.$id.' .wexoe-pa-request-customer-status.wexoe-pa-status-loading {
        color: #999 !important;
        font-style: italic !important;
    }
    #'.$id.' .wexoe-pa-req-col-sum {
        text-align: center !important;
    }
    /* Price and sum cells in rows */
    #'.$id.' .wexoe-pa-req-row-price {
        font-size: 13px !important;
        color: #374151 !important;
        font-weight: 500 !important;
        white-space: nowrap !important;
    }
    #'.$id.' .wexoe-pa-req-row-price.wexoe-pa-no-price {
        color: #9CA3AF !important;
        font-style: italic !important;
        font-weight: 400 !important;
        font-size: 11px !important;
    }
    #'.$id.' .wexoe-pa-req-row-sum {
        font-size: 13px !important;
        color: #11325D !important;
        font-weight: 600 !important;
        padding-left: 6px !important;
        white-space: nowrap !important;
        text-align: center !important;
    }
    /* Total row */
    #'.$id.' .wexoe-pa-request-footer-row {
        display: flex !important;
        align-items: flex-start !important;
        justify-content: space-between !important;
        padding: 10px 12px !important;
        border-top: 1px solid #E5E7EB !important;
    }
    #'.$id.' .wexoe-pa-request-total {
        display: none !important;
        align-items: center !important;
        gap: 10px !important;
        padding: 0 !important;
    }
    #'.$id.' .wexoe-pa-request-articles.wexoe-pa-has-prices .wexoe-pa-request-total {
        display: flex !important;
    }
    #'.$id.' .wexoe-pa-request-total-label {
        font-size: 14px !important;
        font-weight: 600 !important;
        color: #6B7280 !important;
    }
    #'.$id.' .wexoe-pa-request-total-value {
        font-size: 18px !important;
        font-weight: 700 !important;
        color: #11325D !important;
    }
    #'.$id.' .wexoe-pa-request-msg-row textarea {
        all: unset !important;
        display: block !important;
        width: 100% !important;
        padding: 10px 12px !important;
        font-family: "DM Sans", system-ui, sans-serif !important;
        font-size: 14px !important;
        color: #11325D !important;
        background: #FFFFFF !important;
        border: 1px solid #D1D5DB !important;
        border-radius: 6px !important;
        box-sizing: border-box !important;
        resize: vertical !important;
        min-height: 70px !important;
        white-space: pre-wrap !important;
        overflow-wrap: break-word !important;
    }
    #'.$id.' .wexoe-pa-request-msg-row textarea:focus {
        border-color: #F28C28 !important;
    }
    /* Bottom row */
    #'.$id.' .wexoe-pa-request-bottom {
        display: flex !important;
        align-items: center !important;
        justify-content: flex-end !important;
        gap: 20px !important;
    }
    #'.$id.' .wexoe-pa-request-gdpr {
        display: flex !important;
        flex-direction: row-reverse !important;
        align-items: center !important;
        gap: 8px !important;
        font-size: 12px !important;
        color: #666 !important;
        cursor: pointer !important;
    }
    #'.$id.' .wexoe-pa-request-gdpr input {
        width: 16px !important;
        height: 16px !important;
        cursor: pointer !important;
    }
    #'.$id.' .wexoe-pa-request-submit {
        all: unset !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
        padding: 14px 32px !important;
        background: #F28C28 !important;
        color: #FFFFFF !important;
        font-family: "DM Sans", system-ui, sans-serif !important;
        font-size: 15px !important;
        font-weight: 600 !important;
        border-radius: 6px !important;
        cursor: pointer !important;
        transition: opacity 0.2s ease, transform 0.15s ease !important;
        box-sizing: border-box !important;
        flex-shrink: 0 !important;
    }
    #'.$id.' .wexoe-pa-request-submit:hover {
        opacity: 0.9 !important;
        transform: translateY(-1px) !important;
    }
    #'.$id.' .wexoe-pa-request-submit.wexoe-pa-loading {
        opacity: 0.6 !important;
        pointer-events: none !important;
    }
    /* Success */
    #'.$id.' .wexoe-pa-request-success {
        display: none !important;
        text-align: center !important;
        padding: 40px 20px !important;
    }
    #'.$id.' .wexoe-pa-request-success.wexoe-pa-show {
        display: block !important;
    }
    #'.$id.' .wexoe-pa-request-success-icon {
        width: 56px !important;
        height: 56px !important;
        background: #10B981 !important;
        color: #FFFFFF !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 28px !important;
        margin: 0 auto 16px auto !important;
    }
    #'.$id.' .wexoe-pa-request-success h3 {
        font-size: 22px !important;
        font-weight: 700 !important;
        color: #11325D !important;
        margin-bottom: 8px !important;
    }
    #'.$id.' .wexoe-pa-request-success p {
        font-size: 15px !important;
        color: #666 !important;
    }
    /* Beställ button "added" state */
    #'.$id.' .wexoe-pa-sm-article-btn-order.wexoe-pa-btn-added {
        background: #6B7280 !important;
        pointer-events: none !important;
        opacity: 0.7 !important;
    }
    /* Request form: row variant selects */
    #'.$id.' .wexoe-pa-req-row-variant select {
        all: unset !important;
        display: inline-block !important;
        padding: 3px 20px 3px 6px !important;
        font-family: "DM Sans", system-ui, sans-serif !important;
        font-size: 11px !important;
        color: #374151 !important;
        background: #F9FAFB !important;
        border: 1px solid #D1D5DB !important;
        border-radius: 3px !important;
        cursor: pointer !important;
        box-sizing: border-box !important;
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        appearance: none !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'8\' height=\'5\' fill=\'none\'%3E%3Cpath d=\'M1 1l3 3 3-3\' stroke=\'%23999\' stroke-width=\'1.2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'/%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important;
        background-position: right 5px center !important;
        background-size: 8px 5px !important;
        line-height: 1.4 !important;
        min-width: 0 !important;
        max-width: 120px !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        flex-shrink: 1 !important;
    }
    /* Add article search */
    #'.$id.' .wexoe-pa-request-add-wrap {
        position: relative !important;
    }
    #'.$id.' .wexoe-pa-request-add-btn {
        all: unset !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 4px !important;
        font-family: "DM Sans", system-ui, sans-serif !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        color: #6B7280 !important;
        cursor: pointer !important;
        padding: 4px 0 !important;
        box-sizing: border-box !important;
    }
    #'.$id.' .wexoe-pa-request-add-btn:hover {
        opacity: 0.8 !important;
    }
    #'.$id.' .wexoe-pa-request-search-dropdown {
        display: none !important;
        margin-top: 8px !important;
    }
    #'.$id.' .wexoe-pa-request-search-dropdown.wexoe-pa-show {
        display: block !important;
    }
    #'.$id.' .wexoe-pa-request-search-input {
        all: unset !important;
        display: block !important;
        width: 100% !important;
        padding: 10px 12px !important;
        font-family: "DM Sans", system-ui, sans-serif !important;
        font-size: 14px !important;
        color: #11325D !important;
        background: #FFFFFF !important;
        border: 1px solid #D1D5DB !important;
        border-radius: 6px !important;
        box-sizing: border-box !important;
        margin-bottom: 6px !important;
    }
    #'.$id.' .wexoe-pa-request-search-input:focus {
        border-color: #F28C28 !important;
    }
    #'.$id.' .wexoe-pa-request-search-results {
        max-height: 200px !important;
        overflow-y: auto !important;
        border: 1px solid #E5E7EB !important;
        border-radius: 6px !important;
        background: #FFFFFF !important;
    }
    #'.$id.' .wexoe-pa-request-search-item {
        padding: 10px 14px !important;
        font-size: 13px !important;
        color: #374151 !important;
        cursor: pointer !important;
        border-bottom: 1px solid #F3F4F6 !important;
        transition: background 0.1s ease !important;
    }
    #'.$id.' .wexoe-pa-request-search-item:last-child {
        border-bottom: none !important;
    }
    #'.$id.' .wexoe-pa-request-search-item:hover {
        background: #FFF7ED !important;
        color: #11325D !important;
    }
    #'.$id.' .wexoe-pa-request-search-item-nr {
        font-size: 11px !important;
        color: #999 !important;
        margin-left: 6px !important;
    }
    #'.$id.' .wexoe-pa-request-search-none {
        padding: 12px 14px !important;
        font-size: 13px !important;
        color: #999 !important;
        font-style: italic !important;
    }

    /* === RESPONSIVE: TABLET (768–989px) === */
    @media (max-width: 989px) {
        #'.$id.' .wexoe-pa-hero-inner {
            flex-direction: column !important;
            padding: 40px 30px !important;
        }
        #'.$id.' .wexoe-pa-hero-benefits {
            min-width: 0 !important;
            width: 100% !important;
        }
        #'.$id.' .wexoe-pa-npi-card {
            min-width: 0 !important;
            width: 100% !important;
        }
        #'.$id.' .wexoe-pa-hero-image {
            min-width: 0 !important;
            width: 100% !important;
        }
        #'.$id.' .wexoe-pa-solutions-grid {
            grid-template-columns: repeat(2, 1fr) !important;
        }
        #'.$id.' .wexoe-pa-toggle-body-inner {
            flex-direction: column !important;
            gap: 24px !important;
        }
        #'.$id.' .wexoe-pa-toggle-right {
            flex: 1 1 100% !important;
            align-self: flex-start !important;
        }
        #'.$id.' .wexoe-pa-normal-inner {
            gap: 30px !important;
            padding: 40px 30px !important;
        }
        #'.$id.' .wexoe-pa-contact-inner {
            flex-wrap: wrap !important;
            padding: 40px 30px !important;
        }
        #'.$id.' .wexoe-pa-contact-quote {
            flex-basis: 100% !important;
        }
        #'.$id.' .wexoe-pa-sidemenu-inner {
            gap: 28px !important;
            padding: 55px 30px !important;
        }
        #'.$id.' .wexoe-pa-sm-sidebar {
            flex: 0 0 170px !important;
        }
        #'.$id.' .wexoe-pa-request-fields {
            grid-template-columns: 1fr 1fr !important;
        }
    }

    /* === RESPONSIVE: MOBILE (<768px) === */
    @media (max-width: 767px) {
        #'.$id.' .wexoe-pa-top-inner {
            padding: 22px 20px !important;
        }
        #'.$id.' .wexoe-pa-top-h1 {
            font-size: 19px !important;
        }
        #'.$id.' .wexoe-pa-hero-inner {
            padding: 32px 20px !important;
            gap: 28px !important;
        }
        #'.$id.' .wexoe-pa-hero-h2 {
            font-size: 26px !important;
        }
        #'.$id.' .wexoe-pa-hero-cta {
            width: 100% !important;
            justify-content: center !important;
        }
        #'.$id.' .wexoe-pa-toggle-inner {
            padding: 30px 16px !important;
        }
        #'.$id.' .wexoe-pa-toggle-header {
            padding: 16px 18px !important;
        }
        #'.$id.' .wexoe-pa-toggle-eco {
            display: none !important;
        }
        #'.$id.' .wexoe-pa-toggle-body-inner {
            padding: 0 18px 20px 18px !important;
        }
        #'.$id.' .wexoe-pa-toggle-btns {
            flex-direction: column !important;
        }
        #'.$id.' .wexoe-pa-solutions-inner {
            padding: 40px 0 40px 20px !important;
        }
        #'.$id.' .wexoe-pa-solutions-h2 {
            font-size: 24px !important;
            padding-right: 20px !important;
        }
        #'.$id.' .wexoe-pa-solutions-grid {
            display: flex !important;
            overflow-x: auto !important;
            scroll-snap-type: x mandatory !important;
            -webkit-overflow-scrolling: touch !important;
            gap: 16px !important;
            padding-right: 20px !important;
            padding-bottom: 4px !important;
            scrollbar-width: none !important;
        }
        #'.$id.' .wexoe-pa-solutions-grid::-webkit-scrollbar {
            display: none !important;
        }
        #'.$id.' .wexoe-pa-solution-card {
            flex: 0 0 75vw !important;
            max-width: 300px !important;
            scroll-snap-align: start !important;
        }
        #'.$id.' .wexoe-pa-normal-inner {
            grid-template-columns: 1fr !important;
            padding: 36px 20px !important;
            gap: 24px !important;
        }
        #'.$id.' .wexoe-pa-normal-reversed .wexoe-pa-normal-image {
            order: 0 !important;
        }
        #'.$id.' .wexoe-pa-normal-h2 {
            font-size: 22px !important;
        }
        #'.$id.' .wexoe-pa-contact-inner {
            flex-direction: column !important;
            text-align: center !important;
            padding: 36px 20px !important;
            gap: 20px !important;
        }
        #'.$id.' .wexoe-pa-contact-info {
            min-width: 0 !important;
        }
        #'.$id.' .wexoe-pa-docs-inner {
            padding: 40px 20px !important;
        }
        #'.$id.' .wexoe-pa-docs-h2 {
            font-size: 24px !important;
        }
        /* Side menu mobile: select dropdown */
        #'.$id.' .wexoe-pa-sidemenu-inner {
            flex-direction: column !important;
            padding: 45px 20px !important;
            gap: 20px !important;
        }
        #'.$id.' .wexoe-pa-sm-sidebar {
            display: none !important;
        }
        #'.$id.' .wexoe-pa-sm-mobile-select-wrap {
            display: block !important;
            width: 100% !important;
        }
        #'.$id.' .wexoe-pa-sm-mobile-select {
            all: unset !important;
            display: block !important;
            width: 100% !important;
            padding: 14px 40px 14px 16px !important;
            font-family: "DM Sans", system-ui, sans-serif !important;
            font-size: 15px !important;
            font-weight: 600 !important;
            color: #FFFFFF !important;
            background-color: rgba(255,255,255,0.08) !important;
            border: 1px solid rgba(255,255,255,0.2) !important;
            border-radius: 8px !important;
            cursor: pointer !important;
            box-sizing: border-box !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'8\' fill=\'none\'%3E%3Cpath d=\'M1 1.5l5 5 5-5\' stroke=\'white\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'/%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: right 16px center !important;
            background-size: 12px 8px !important;
        }
        #'.$id.' .wexoe-pa-sm-mobile-select option {
            color: #11325D !important;
            background: #FFFFFF !important;
        }
        #'.$id.' .wexoe-pa-sm-h2 {
            font-size: 22px !important;
        }
        #'.$id.' .wexoe-pa-sm-row {
            flex-direction: column !important;
            gap: 20px !important;
        }
        #'.$id.' .wexoe-pa-sm-img-wrap {
            flex: none !important;
            max-width: 100% !important;
            width: 180px !important;
        }
        #'.$id.' .wexoe-pa-sm-btns {
            flex-direction: column !important;
        }
        /* Article grid: 2 columns on mobile */
        #'.$id.' .wexoe-pa-sm-articles-grid {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)) !important;
            gap: 12px !important;
        }
        /* Article buttons: equal sizing on mobile */
        #'.$id.' .wexoe-pa-sm-article-btn-order {
            min-width: 0 !important;
            text-align: center !important;
        }
        #'.$id.' .wexoe-pa-btn-full { display: none !important; }
        #'.$id.' .wexoe-pa-btn-short { display: inline !important; }

        /* === REQUEST FORM MOBILE === */
        #'.$id.' .wexoe-pa-request-inner {
            padding: 40px 20px !important;
        }
        /* Header: stack subtitle + customer ID vertically */
        #'.$id.' .wexoe-pa-request-header-row {
            flex-direction: column !important;
            gap: 12px !important;
        }
        #'.$id.' .wexoe-pa-request-customer-toggle {
            align-self: flex-start !important;
        }
        #'.$id.' .wexoe-pa-request-customer-panel {
            align-items: flex-start !important;
        }
        /* Fields: single column */
        #'.$id.' .wexoe-pa-request-fields {
            grid-template-columns: 1fr 1fr !important;
        }
        /* Table: hide grid header, vertical card per row */
        #'.$id.' .wexoe-pa-request-table-head {
            display: none !important;
        }
        #'.$id.' .wexoe-pa-request-row {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: wrap !important;
            align-items: flex-start !important;
            gap: 6px !important;
            padding: 16px !important;
            position: relative !important;
        }
        /* Name: full width row */
        #'.$id.' .wexoe-pa-req-row-name {
            font-size: 15px !important;
            font-weight: 700 !important;
            padding-right: 28px !important;
            white-space: normal !important;
            order: 1 !important;
            flex: 0 0 100% !important;
        }
        /* Variants: full width row */
        #'.$id.' .wexoe-pa-req-row-variant {
            flex-wrap: wrap !important;
            order: 2 !important;
            flex: 0 0 100% !important;
        }
        #'.$id.' .wexoe-pa-req-row-variant select {
            max-width: none !important;
            flex-shrink: 0 !important;
        }
        /* Art.nr: full width row */
        #'.$id.' .wexoe-pa-req-row-artnr {
            font-size: 13px !important;
            order: 3 !important;
            flex: 0 0 100% !important;
        }
        /* === Bottom metrics row: Antal | Pris | Summa === */
        /* Qty: value + label stacked */
        #'.$id.' .wexoe-pa-req-row-qty {
            order: 4 !important;
            display: inline-flex !important;
            flex-direction: column !important;
            align-items: flex-start !important;
            margin-top: 6px !important;
        }
        #'.$id.' .wexoe-pa-req-row-qty input {
            width: 60px !important;
            margin-bottom: 2px !important;
        }
        #'.$id.' .wexoe-pa-req-row-qty::after {
            content: "Antal" !important;
            font-size: 11px !important;
            color: #999 !important;
            font-weight: 500 !important;
            font-family: "DM Sans", system-ui, sans-serif !important;
        }
        /* Price: value + label stacked */
        #'.$id.' .wexoe-pa-req-row-price {
            order: 5 !important;
            display: inline-flex !important;
            flex-direction: column !important;
            align-items: flex-start !important;
            font-size: 14px !important;
            margin-top: 6px !important;
            padding-top: 5px !important;
            margin-left: 12px !important;
        }
        #'.$id.' .wexoe-pa-req-row-price::after {
            content: "Pris" !important;
            font-size: 11px !important;
            color: #999 !important;
            font-weight: 500 !important;
            font-family: "DM Sans", system-ui, sans-serif !important;
        }
        /* Sum: value + label stacked, pushed right */
        #'.$id.' .wexoe-pa-req-row-sum {
            order: 6 !important;
            display: inline-flex !important;
            flex-direction: column !important;
            align-items: flex-end !important;
            text-align: right !important;
            padding-left: 0 !important;
            font-size: 15px !important;
            margin-top: 6px !important;
            padding-top: 5px !important;
            margin-left: auto !important;
        }
        #'.$id.' .wexoe-pa-req-row-sum::after {
            content: "Summa" !important;
            font-size: 11px !important;
            color: #999 !important;
            font-weight: 500 !important;
            font-family: "DM Sans", system-ui, sans-serif !important;
        }
        /* Delete button: top-right corner */
        #'.$id.' .wexoe-pa-req-row-del {
            position: absolute !important;
            top: 16px !important;
            right: 12px !important;
            order: 99 !important;
        }
        /* Override has-prices display:block on mobile */
        #'.$id.' .wexoe-pa-request-articles.wexoe-pa-has-prices .wexoe-pa-req-row-price,
        #'.$id.' .wexoe-pa-request-articles.wexoe-pa-has-prices .wexoe-pa-req-row-sum {
            display: inline-flex !important;
        }
        #'.$id.' .wexoe-pa-request-articles.wexoe-pa-has-prices .wexoe-pa-request-row {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: wrap !important;
        }
        #'.$id.' .wexoe-pa-request-articles.wexoe-pa-has-prices .wexoe-pa-request-table-head {
            display: none !important;
        }
        /* Footer */
        #'.$id.' .wexoe-pa-request-footer-row {
            flex-direction: column !important;
            gap: 10px !important;
        }
        #'.$id.' .wexoe-pa-request-total {
            align-self: flex-end !important;
        }
        /* Bottom: stack */
        #'.$id.' .wexoe-pa-request-bottom {
            flex-direction: column !important;
            align-items: stretch !important;
        }
        #'.$id.' .wexoe-pa-request-gdpr {
            flex-direction: row !important;
            justify-content: center !important;
        }
        #'.$id.' .wexoe-pa-request-submit {
            justify-content: center !important;
        }
    }

    /* === DEBUG === */
    #'.$id.' .wexoe-pa-debug {
        background: #1a1a2e !important;
        color: #00ff88 !important;
        font-family: "Courier New", monospace !important;
        font-size: 13px !important;
        padding: 24px !important;
        border-radius: 8px !important;
        overflow-x: auto !important;
        margin: 20px 0 !important;
        white-space: pre-wrap !important;
        word-break: break-word !important;
    }
    </style>';
}

/* ============================================================
   JAVASCRIPT
   ============================================================ */

function wexoe_pa_test_render_js($id, $request_mode = false) {
    $request_js = $request_mode ? 'true' : 'false';
    return '
    <script>
    (function() {
        var wrap = document.getElementById("'.$id.'");
        if (!wrap) return;
        var requestMode = '.$request_js.';

        // Hide #kontakt section when request form is active
        if (requestMode) {
            function hideKontakt() {
                var el = document.getElementById("kontakt");
                if (el) el.style.display = "none";
            }
            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", hideKontakt);
            } else {
                hideKontakt();
            }
        }

        // Fix bottom gap (main.template-page padding-bottom creates white space before footer)
        function fixBottomGap() {
            var mainEl = document.querySelector("main.template-page, main");
            if (mainEl) {
                var pb = parseInt(getComputedStyle(mainEl).paddingBottom) || 0;
                if (pb > 0) {
                    mainEl.style.setProperty("padding-bottom", "0px", "important");
                }
            }
        }
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", fixBottomGap);
        } else {
            fixBottomGap();
        }

        // Init toggle heights — defer to window.load so images/fonts are ready
        function initToggles() {
            wrap.querySelectorAll(".wexoe-pa-toggle-item").forEach(function(item) {
                var body = item.querySelector(".wexoe-pa-toggle-body");
                if (item.classList.contains("wexoe-pa-toggle-open")) {
                    body.style.maxHeight = body.scrollHeight + "px";
                } else {
                    body.style.maxHeight = "0px";
                }
            });
        }

        // Set closed items immediately, defer open items to after load
        wrap.querySelectorAll(".wexoe-pa-toggle-item").forEach(function(item) {
            var body = item.querySelector(".wexoe-pa-toggle-body");
            if (!item.classList.contains("wexoe-pa-toggle-open")) {
                body.style.maxHeight = "0px";
            }
        });

        if (document.readyState === "complete") {
            initToggles();
        } else {
            window.addEventListener("load", initToggles);
        }

        // Click handlers
        wrap.querySelectorAll(".wexoe-pa-toggle-header").forEach(function(btn) {
            btn.addEventListener("click", function() {
                var item = this.closest(".wexoe-pa-toggle-item");
                var body = item.querySelector(".wexoe-pa-toggle-body");

                if (item.classList.contains("wexoe-pa-toggle-open")) {
                    body.style.maxHeight = body.scrollHeight + "px";
                    body.offsetHeight;
                    body.style.maxHeight = "0px";
                    function onClose(e) {
                        if (e.propertyName !== "max-height") return;
                        body.removeEventListener("transitionend", onClose);
                        item.classList.remove("wexoe-pa-toggle-open");
                    }
                    body.addEventListener("transitionend", onClose);
                } else {
                    item.classList.add("wexoe-pa-toggle-open");
                    body.style.maxHeight = body.scrollHeight + "px";
                }
            });
        });

        // Reset solutions scroll to start
        var grid = wrap.querySelector(".wexoe-pa-solutions-grid");
        if (grid) {
            grid.scrollLeft = 0;
            window.addEventListener("load", function() { grid.scrollLeft = 0; });
        }

        // Side menu navigation
        var sidebar = wrap.querySelector(".wexoe-pa-sm-sidebar");
        var mobileSelect = wrap.querySelector(".wexoe-pa-sm-mobile-select");
        if (sidebar || mobileSelect) {
            function switchPanel(index) {
                // Update sidebar active state
                var navItems = wrap.querySelectorAll(".wexoe-pa-sm-nav-item");
                navItems.forEach(function(item) {
                    item.classList.remove("wexoe-pa-sm-nav-active");
                });
                var target = wrap.querySelector(".wexoe-pa-sm-nav-item[data-sm-index=\"" + index + "\"]");
                if (target) target.classList.add("wexoe-pa-sm-nav-active");

                // Show/hide panels
                var panels = wrap.querySelectorAll(".wexoe-pa-sm-panel");
                panels.forEach(function(panel) {
                    if (panel.getAttribute("data-sm-panel") === String(index)) {
                        panel.style.display = "";
                    } else {
                        panel.style.display = "none";
                    }
                });

                // Sync mobile select
                if (mobileSelect) mobileSelect.value = index;
            }

            // Desktop sidebar clicks
            if (sidebar) {
                sidebar.addEventListener("click", function(e) {
                    var btn = e.target.closest(".wexoe-pa-sm-nav-item");
                    if (!btn) return;
                    switchPanel(parseInt(btn.getAttribute("data-sm-index"), 10));
                });
            }

            // Mobile select change
            if (mobileSelect) {
                mobileSelect.addEventListener("change", function() {
                    switchPanel(parseInt(this.value, 10));
                });
            }
        }

        // Variant selectors — cascading filters + article number update
        wrap.querySelectorAll(".wexoe-pa-sm-variant-wrap").forEach(function(varWrap) {
            var card = varWrap.closest(".wexoe-pa-sm-article-card");
            var map = {};
            try { map = JSON.parse(varWrap.getAttribute("data-variant-map") || "{}"); } catch(e) {}
            var selects = varWrap.querySelectorAll(".wexoe-pa-sm-variant-select");
            var artnrEl = card ? card.querySelector(".wexoe-pa-sm-variant-artnr") : varWrap.parentElement.querySelector(".wexoe-pa-sm-variant-artnr");
            var orderBtn = card ? card.querySelector(".wexoe-pa-sm-article-btn-order") : null;
            var artName = orderBtn ? (orderBtn.getAttribute("data-art-name") || "") : "";

            // Pre-parse all map keys into arrays for fast lookup
            var mapKeys = [];
            for (var k in map) {
                mapKeys.push(k.split("/"));
            }

            // Find which options are valid for a given dimension,
            // holding all OTHER dimensions to their current values
            function getValidOptions(dimIndex) {
                var currentValues = [];
                selects.forEach(function(s) { currentValues.push(s.value); });
                var valid = {};
                mapKeys.forEach(function(parts) {
                    if (parts.length !== selects.length) return;
                    var othersMatch = true;
                    for (var d = 0; d < parts.length; d++) {
                        if (d === dimIndex) continue;
                        if (parts[d] !== currentValues[d]) { othersMatch = false; break; }
                    }
                    if (othersMatch) valid[parts[dimIndex]] = true;
                });
                return valid;
            }

            function updateFilters(changedIdx) {
                // Filter options in all OTHER selects based on current selection
                selects.forEach(function(s, sIdx) {
                    if (sIdx === changedIdx) return;
                    var validOpts = getValidOptions(sIdx);
                    var options = s.querySelectorAll("option");
                    var currentValid = false;

                    options.forEach(function(opt) {
                        if (validOpts[opt.value]) {
                            opt.disabled = false;
                            opt.style.display = "";
                            if (opt.value === s.value) currentValid = true;
                        } else {
                            opt.disabled = true;
                            opt.style.display = "none";
                        }
                    });

                    // If current selection is no longer valid, pick first valid option
                    if (!currentValid) {
                        for (var i = 0; i < options.length; i++) {
                            if (!options[i].disabled) {
                                s.value = options[i].value;
                                break;
                            }
                        }
                    }
                });
            }

            function updateArticleNr() {
                var parts = [];
                selects.forEach(function(s) { parts.push(s.value); });
                var key = parts.join("/");
                var nr = map[key] || null;

                if (artnrEl) {
                    if (nr) {
                        artnrEl.innerHTML = "<span class=\"wexoe-pa-sm-article-nr-label\">Art.</span><span class=\"wexoe-pa-sm-article-nr-value\">" + nr + "</span>";
                        artnrEl.classList.remove("wexoe-pa-variant-nomatch");
                    } else {
                        artnrEl.innerHTML = "Kombination saknas";
                        artnrEl.classList.add("wexoe-pa-variant-nomatch");
                    }
                }
                if (orderBtn) {
                    var msg = "Hej! Jag är intresserad av att få en prisuppgift på " + artName + (nr ? " " + nr : "") + ".";
                    orderBtn.setAttribute("data-prefill-msg", msg);
                }
            }

            selects.forEach(function(s, sIdx) {
                s.addEventListener("change", function() {
                    updateFilters(sIdx);
                    updateArticleNr();
                });
            });

            // Run initial filter based on first option defaults
            if (selects.length > 1) {
                updateFilters(0);
                updateArticleNr();
            }
        });

        // Customer prices state
        var customerPrices = null;

        function getPrice(artnr) {
            if (!customerPrices || !artnr) return null;
            return customerPrices[artnr] || null;
        }

        function updateRowPrice(row) {
            var priceCell = row.querySelector(".wexoe-pa-req-row-price");
            var sumCell = row.querySelector(".wexoe-pa-req-row-sum");
            if (!priceCell || !sumCell) return;
            var artnr = row.getAttribute("data-art-nr") || "";
            var price = getPrice(artnr);
            var qtyInput = row.querySelector("input[type=\"number\"]");
            var qty = qtyInput ? parseFloat(qtyInput.value) || 0 : 0;

            if (price !== null) {
                var priceNum = parseFloat(String(price).replace(",", ".")) || 0;
                priceCell.textContent = price + " kr";
                priceCell.className = "wexoe-pa-req-row-price";
                if (qty > 0 && priceNum > 0) {
                    var sum = Math.round(priceNum * qty);
                    sumCell.textContent = sum + " kr";
                } else {
                    sumCell.textContent = "–";
                }
            } else if (customerPrices) {
                priceCell.textContent = "Ej i avtal";
                priceCell.className = "wexoe-pa-req-row-price wexoe-pa-no-price";
                sumCell.textContent = "–";
            }
        }

        function updateTotal() {
            var totalEl = wrap.querySelector(".wexoe-pa-request-total-value");
            if (!totalEl || !customerPrices) return;
            var total = 0;
            wrap.querySelectorAll(".wexoe-pa-request-row").forEach(function(row) {
                var artnr = row.getAttribute("data-art-nr") || "";
                var price = getPrice(artnr);
                var qtyInput = row.querySelector("input[type=\"number\"]");
                var qty = qtyInput ? parseFloat(qtyInput.value) || 0 : 0;
                if (price !== null && qty > 0) {
                    total += (parseFloat(String(price).replace(",", ".")) || 0) * qty;
                }
            });
            totalEl.textContent = total > 0 ? total.toFixed(2).replace(".", ",") + " kr" : "0 kr";
        }

        function refreshAllPrices() {
            wrap.querySelectorAll(".wexoe-pa-request-row").forEach(function(row) {
                updateRowPrice(row);
            });
            updateTotal();
        }

        // Shared: add article row to request form
        function addArticleRow(name, artnr, variantText, variantData) {
            var tbody = wrap.querySelector(".wexoe-pa-request-table-body");
            if (!tbody) return;
            var emptyMsg = tbody.querySelector(".wexoe-pa-request-empty");
            if (emptyMsg) emptyMsg.remove();

            var row = document.createElement("div");
            row.className = "wexoe-pa-request-row";
            row.setAttribute("data-art-name", name);
            row.setAttribute("data-art-nr", artnr);
            row.setAttribute("data-art-variant", variantText);

            // Name cell
            row.innerHTML = "<span class=\"wexoe-pa-req-row-name\">" + name + "</span>";

            // Art.nr cell
            var artnrCell = document.createElement("span");
            artnrCell.className = "wexoe-pa-req-row-artnr";
            artnrCell.textContent = artnr;
            row.appendChild(artnrCell);

            // Variant cell
            var variantCell = document.createElement("span");
            variantCell.className = "wexoe-pa-req-row-variant";

            if (variantData && variantData.dimensions && variantData.dimensions.length > 0) {
                var mapKeys = [];
                for (var k in variantData.map) { mapKeys.push(k.split("/")); }

                variantData.dimensions.forEach(function(dim, dimIdx) {
                    var sel = document.createElement("select");
                    sel.setAttribute("data-dim", dimIdx);
                    dim.options.forEach(function(opt) {
                        var o = document.createElement("option");
                        o.value = opt; o.textContent = opt;
                        sel.appendChild(o);
                    });
                    variantCell.appendChild(sel);
                });

                var initialParts = variantText.split(" / ");
                var rowSelects = variantCell.querySelectorAll("select");
                rowSelects.forEach(function(sel, idx) {
                    if (initialParts[idx]) sel.value = initialParts[idx];
                });

                function updateRowVariants(changedIdx) {
                    var currentValues = [];
                    rowSelects.forEach(function(s) { currentValues.push(s.value); });
                    rowSelects.forEach(function(s, sIdx) {
                        if (sIdx === changedIdx) return;
                        var valid = {};
                        mapKeys.forEach(function(parts) {
                            if (parts.length !== rowSelects.length) return;
                            var ok = true;
                            for (var d = 0; d < parts.length; d++) {
                                if (d === sIdx) continue;
                                if (parts[d] !== currentValues[d]) { ok = false; break; }
                            }
                            if (ok) valid[parts[sIdx]] = true;
                        });
                        var opts = s.querySelectorAll("option");
                        var curValid = false;
                        opts.forEach(function(o) {
                            if (valid[o.value]) { o.disabled = false; o.style.display = ""; if (o.value === s.value) curValid = true; }
                            else { o.disabled = true; o.style.display = "none"; }
                        });
                        if (!curValid) {
                            for (var i = 0; i < opts.length; i++) { if (!opts[i].disabled) { s.value = opts[i].value; break; } }
                        }
                    });
                    var vals = [];
                    rowSelects.forEach(function(s) { vals.push(s.value); });
                    var key = vals.join("/");
                    var nr = variantData.map[key] || "";
                    artnrCell.textContent = nr;
                    row.setAttribute("data-art-nr", nr);
                    row.setAttribute("data-art-variant", vals.join(" / "));
                    updateRowPrice(row);
                    updateTotal();
                }

                rowSelects.forEach(function(s, sIdx) {
                    s.addEventListener("change", function() { updateRowVariants(sIdx); });
                });
                if (rowSelects.length > 1) updateRowVariants(0);
            } else {
                variantCell.textContent = variantText;
            }
            row.appendChild(variantCell);

            // Price cell (hidden until prices loaded)
            var priceCell = document.createElement("span");
            priceCell.className = "wexoe-pa-req-row-price wexoe-pa-price-col";
            priceCell.textContent = "–";
            row.appendChild(priceCell);

            // Qty cell
            var qtyCell = document.createElement("span");
            qtyCell.className = "wexoe-pa-req-row-qty";
            var qtyInput = document.createElement("input");
            qtyInput.type = "number"; qtyInput.min = "1"; qtyInput.placeholder = "–";
            qtyInput.addEventListener("input", function() {
                updateRowPrice(row);
                updateTotal();
            });
            qtyCell.appendChild(qtyInput);
            row.appendChild(qtyCell);

            // Sum cell (hidden until prices loaded)
            var sumCell = document.createElement("span");
            sumCell.className = "wexoe-pa-req-row-sum wexoe-pa-price-col";
            sumCell.textContent = "–";
            row.appendChild(sumCell);

            // Delete button
            var delBtn = document.createElement("button");
            delBtn.className = "wexoe-pa-req-row-del";
            delBtn.title = "Ta bort";
            delBtn.innerHTML = "&times;";
            delBtn.addEventListener("click", function() {
                row.remove();
                if (!tbody.querySelector(".wexoe-pa-request-row")) {
                    tbody.innerHTML = "<div class=\"wexoe-pa-request-empty\">Inga artiklar tillagda ännu.</div>";
                }
                updateTotal();
            });
            row.appendChild(delBtn);
            tbody.appendChild(row);

            // If prices already loaded, update this row
            if (customerPrices) updateRowPrice(row);
            updateTotal();
        }

        // Article "Lägg till i förfrågan" / "Beställ" button
        wrap.querySelectorAll(".wexoe-pa-sm-article-btn-order").forEach(function(btn) {
            btn.addEventListener("click", function(e) {
                e.preventDefault();
                var self = this;

                if (requestMode) {
                    var card = self.closest(".wexoe-pa-sm-article-card");
                    if (!card) return;
                    var artNameEl = card.querySelector(".wexoe-pa-sm-article-name");
                    var name = artNameEl ? artNameEl.textContent.trim() : "";

                    var artnr = "";
                    var variantArtnr = card.querySelector(".wexoe-pa-sm-variant-artnr .wexoe-pa-sm-article-nr-value");
                    var staticArtnr = card.querySelector(".wexoe-pa-sm-article-nr:not(.wexoe-pa-sm-variant-artnr) .wexoe-pa-sm-article-nr-value");
                    if (variantArtnr) artnr = variantArtnr.textContent.trim();
                    else if (staticArtnr) artnr = staticArtnr.textContent.trim();

                    var variantText = "";
                    var cardSelects = card.querySelectorAll(".wexoe-pa-sm-variant-select");
                    if (cardSelects.length > 0) {
                        var parts = [];
                        cardSelects.forEach(function(s) { parts.push(s.options[s.selectedIndex] ? s.options[s.selectedIndex].text : s.value); });
                        variantText = parts.join(" / ");
                    }

                    var variantData = null;
                    try { variantData = JSON.parse(self.getAttribute("data-variants") || "null"); } catch(ex) {}

                    addArticleRow(name, artnr, variantText, variantData);

                    var origHtml = self.innerHTML;
                    self.innerHTML = "Tillagd";
                    self.classList.add("wexoe-pa-btn-added");
                    setTimeout(function() {
                        self.innerHTML = origHtml;
                        self.classList.remove("wexoe-pa-btn-added");
                    }, 3000);

                } else {
                    var msg = self.getAttribute("data-prefill-msg") || "";
                    var formSelect = document.querySelector(".wexoe-form-select[name=\"behov\"]");
                    var formTextarea = document.querySelector(".wexoe-form-textarea[name=\"meddelande\"]");
                    if (formSelect) { formSelect.value = "info-om-produkt"; formSelect.dispatchEvent(new Event("change", { bubbles: true })); }
                    if (formTextarea) { formTextarea.value = msg; formTextarea.dispatchEvent(new Event("input", { bubbles: true })); }

                    var kontakt = document.getElementById("kontakt") || document.querySelector("[id*=\"kontakt\"]");
                    if (kontakt) {
                        var headerBottom = 0;
                        var header = document.querySelector("header.header, #header, .av-header-area");
                        if (header) { headerBottom = header.getBoundingClientRect().bottom; }
                        window.scrollBy({ top: kontakt.getBoundingClientRect().top - headerBottom, behavior: "smooth" });
                    } else { window.location.hash = "kontakt"; }
                }
            });
        });

        // REQUEST FORM — customer lookup, search, submission
        if (requestMode) {
            var artWrap = wrap.querySelector(".wexoe-pa-request-articles");

            // Customer ID toggle — one-way reveal
            var custTrigger = wrap.querySelector(".wexoe-pa-request-customer-trigger");
            var custPanel = wrap.querySelector(".wexoe-pa-request-customer-panel");
            if (custTrigger && custPanel) {
                custTrigger.addEventListener("click", function() {
                    custTrigger.classList.add("wexoe-pa-open");
                    custPanel.classList.add("wexoe-pa-show");
                    var inp = custPanel.querySelector(".wexoe-pa-request-customer-input");
                    if (inp) setTimeout(function() { inp.focus(); }, 50);
                });
            }

            // Customer ID lookup
            var custBtn = wrap.querySelector(".wexoe-pa-request-customer-btn");
            var custInput = wrap.querySelector(".wexoe-pa-request-customer-input");
            var custStatus = wrap.querySelector(".wexoe-pa-request-customer-status");

            if (custBtn && custInput) {
                function doCustomerLookup() {
                    var cid = custInput.value.trim();
                    if (!cid) { custStatus.textContent = "Ange ett kund-ID."; custStatus.className = "wexoe-pa-request-customer-status wexoe-pa-status-err"; return; }

                    custStatus.textContent = "Hämtar priser...";
                    custStatus.className = "wexoe-pa-request-customer-status wexoe-pa-status-loading";
                    custBtn.disabled = true;

                    var fd = new FormData();
                    fd.append("action", "wexoe_pa_test_customer_lookup");
                    fd.append("customer_id", cid);

                    fetch(wrap.querySelector(".wexoe-pa-request-form").getAttribute("data-ajax"), { method: "POST", body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(result) {
                        custBtn.disabled = false;
                        if (result.success) {
                            customerPrices = result.data.prices || {};
                            var custName = result.data.name || cid;
                            custStatus.textContent = "✓ Priser laddade för " + custName;
                            custStatus.className = "wexoe-pa-request-customer-status wexoe-pa-status-ok";

                            // Show price columns
                            if (artWrap) artWrap.classList.add("wexoe-pa-has-prices");

                            // Update submit button text
                            var submitBtnText = wrap.querySelector(".wexoe-pa-req-btn-text");
                            if (submitBtnText) submitBtnText.textContent = "Skicka orderförfrågan";

                            // Update all existing rows
                            refreshAllPrices();
                        } else {
                            customerPrices = null;
                            custStatus.textContent = result.data || "Kunde inte hitta kund-ID.";
                            custStatus.className = "wexoe-pa-request-customer-status wexoe-pa-status-err";
                            if (artWrap) artWrap.classList.remove("wexoe-pa-has-prices");

                            // Revert submit button text
                            var submitBtnText = wrap.querySelector(".wexoe-pa-req-btn-text");
                            if (submitBtnText) submitBtnText.textContent = "Skicka prisförfrågan";
                        }
                    }).catch(function() {
                        custBtn.disabled = false;
                        custStatus.textContent = "Fel vid hämtning. Försök igen.";
                        custStatus.className = "wexoe-pa-request-customer-status wexoe-pa-status-err";
                    });
                }

                custBtn.addEventListener("click", doCustomerLookup);
                custInput.addEventListener("keydown", function(e) {
                    if (e.key === "Enter") { e.preventDefault(); doCustomerLookup(); }
                });
            }

            // Article search
            var allArticles = [];
            try { allArticles = JSON.parse(artWrap.getAttribute("data-all-articles") || "[]"); } catch(ex) {}

            var addBtn = wrap.querySelector(".wexoe-pa-request-add-btn");
            var searchDd = wrap.querySelector(".wexoe-pa-request-search-dropdown");
            var searchInput = wrap.querySelector(".wexoe-pa-request-search-input");
            var searchResults = wrap.querySelector(".wexoe-pa-request-search-results");

            if (addBtn && searchDd) {
                addBtn.addEventListener("click", function() {
                    searchDd.classList.toggle("wexoe-pa-show");
                    if (searchDd.classList.contains("wexoe-pa-show")) {
                        searchInput.value = "";
                        renderSearchResults("");
                        setTimeout(function() { searchInput.focus(); }, 50);
                    }
                });

                function renderSearchResults(query) {
                    var q = query.toLowerCase().trim();
                    var html = "";
                    var count = 0;
                    allArticles.forEach(function(art, idx) {
                        var match = !q || art.name.toLowerCase().indexOf(q) >= 0 || (art.nr && art.nr.toLowerCase().indexOf(q) >= 0);
                        if (match && count < 20) {
                            html += "<div class=\"wexoe-pa-request-search-item\" data-idx=\"" + idx + "\">"
                                + art.name
                                + (art.nr ? "<span class=\"wexoe-pa-request-search-item-nr\">" + art.nr + "</span>" : "")
                                + "</div>";
                            count++;
                        }
                    });
                    if (!count) html = "<div class=\"wexoe-pa-request-search-none\">Inga artiklar hittades</div>";
                    searchResults.innerHTML = html;

                    searchResults.querySelectorAll(".wexoe-pa-request-search-item").forEach(function(item) {
                        item.addEventListener("click", function() {
                            var art = allArticles[parseInt(this.getAttribute("data-idx"), 10)];
                            if (!art) return;
                            var initNr = art.nr || "";
                            var initVariant = "";
                            if (art.variants && art.variants.dimensions && art.variants.dimensions.length > 0) {
                                var parts = [];
                                art.variants.dimensions.forEach(function(d) { parts.push(d.options[0]); });
                                initVariant = parts.join(" / ");
                                var key = parts.join("/");
                                if (art.variants.map[key]) initNr = art.variants.map[key];
                            }
                            addArticleRow(art.name, initNr, initVariant, art.variants);
                            searchDd.classList.remove("wexoe-pa-show");
                        });
                    });
                }

                searchInput.addEventListener("input", function() { renderSearchResults(this.value); });
            }

            // Form submission
            var reqForm = wrap.querySelector(".wexoe-pa-request-form");
            if (reqForm) {
                var reqSubmit = reqForm.querySelector(".wexoe-pa-request-submit");
                var reqError = wrap.querySelector(".wexoe-pa-request-error");
                var reqSuccess = wrap.querySelector(".wexoe-pa-request-success");
                var reqBtnText = reqSubmit ? reqSubmit.querySelector(".wexoe-pa-req-btn-text") : null;

                reqForm.addEventListener("submit", function(e) {
                    e.preventDefault();
                    reqError.classList.remove("wexoe-pa-show");

                    var artiklar = [];
                    wrap.querySelectorAll(".wexoe-pa-request-row").forEach(function(row) {
                        var qtyInput = row.querySelector("input[type=\"number\"]");
                        var artnr = row.getAttribute("data-art-nr") || "";
                        var price = getPrice(artnr);
                        artiklar.push({
                            namn: row.getAttribute("data-art-name") || "",
                            artikelnummer: artnr,
                            variant: row.getAttribute("data-art-variant") || "",
                            antal: qtyInput ? qtyInput.value : "",
                            pris: price !== null ? String(price) : ""
                        });
                    });

                    var meddelande = reqForm.querySelector("textarea[name=\"meddelande\"]");
                    if (artiklar.length === 0 && (!meddelande || !meddelande.value.trim())) {
                        reqError.textContent = "Lägg till minst en artikel eller skriv ett meddelande.";
                        reqError.classList.add("wexoe-pa-show");
                        return;
                    }

                    reqSubmit.classList.add("wexoe-pa-loading");
                    if (reqBtnText) reqBtnText.textContent = "Skickar...";

                    var formData = new FormData(reqForm);
                    formData.append("action", "wexoe_pa_test_request_submit");
                    formData.append("nonce", reqForm.getAttribute("data-nonce"));
                    formData.append("page_url", window.location.href);
                    formData.append("artiklar", JSON.stringify(artiklar));
                    var custIdInput = wrap.querySelector(".wexoe-pa-request-customer-input");
                    if (custIdInput) formData.append("customer_id", custIdInput.value);

                    fetch(reqForm.getAttribute("data-ajax"), { method: "POST", body: formData })
                    .then(function(r) { return r.json(); })
                    .then(function(result) {
                        if (result.success) {
                            reqForm.style.display = "none";
                            reqSuccess.classList.add("wexoe-pa-show");
                            reqSuccess.scrollIntoView({ behavior: "smooth", block: "center" });
                        } else {
                            reqError.textContent = result.data || "Något gick fel.";
                            reqError.classList.add("wexoe-pa-show");
                            reqSubmit.classList.remove("wexoe-pa-loading");
                            if (reqBtnText) reqBtnText.textContent = customerPrices ? "Skicka orderförfrågan" : "Skicka prisförfrågan";
                        }
                    }).catch(function() {
                        reqError.textContent = "Ett fel uppstod. Försök igen.";
                        reqError.classList.add("wexoe-pa-show");
                        reqSubmit.classList.remove("wexoe-pa-loading");
                        if (reqBtnText) reqBtnText.textContent = customerPrices ? "Skicka orderförfrågan" : "Skicka prisförfrågan";
                    });
                });
            }
        }
    })();
    </script>';
}

/* ============================================================
   MAIN SHORTCODE
   ============================================================ */

function wexoe_product_area_test_shortcode($atts) {
    $atts = shortcode_atts([
        'slug' => '',
        'debug' => 'false',
        'remove_padding' => 'true',
        'nocache' => 'false',
    ], $atts, 'wexoe_product_area');

    $slug = sanitize_text_field($atts['slug']);
    $debug = ($atts['debug'] === 'true');
    $remove_padding = ($atts['remove_padding'] === 'true');

    if (empty($slug)) {
        return '<p style="color:red;font-weight:bold;">[wexoe_product_area] Error: slug parameter is required.</p>';
    }
    if (!wexoe_pa_test_core_ready()) {
        return '<p style="color:red;font-weight:bold;">[wexoe_product_area] Error: Wexoe Core-pluginet är inte aktivt.</p>';
    }

    // Clear entity caches if nocache is set.
    if ($atts['nocache'] === 'true') {
        foreach (['product_areas', 'products', 'solutions', 'articles', 'customers'] as $entity) {
            $repo = wexoe_pa_test_get_repo($entity);
            if ($repo) {
                $repo->clear_cache();
            }
        }
    }

    // Fetch main record
    $data = wexoe_pa_test_fetch_product_area($slug);

    if (!$data) {
        if ($debug) {
            return '<div class="wexoe-pa-debug">No product area found for slug: ' . esc_html($slug) . '</div>';
        }
        return '';
    }

    // Fetch linked products
    $product_ids = wexoe_pa_test_get_linked_ids($data, 'Products');
    $products = wexoe_pa_test_fetch_linked_records(WEXOE_PA_TABLE_PRODUCTS, $product_ids, 'products');

    // Fetch linked solutions
    $solution_ids = wexoe_pa_test_get_linked_ids($data, 'Solutions');
    $solutions = wexoe_pa_test_fetch_linked_records(WEXOE_PA_TABLE_SOLUTIONS, $solution_ids, 'solutions');

    // Check if side menu mode is enabled
    $side_menu = !empty($data['Side menu']);

    // Check if request form mode is enabled
    $request_mode = !empty($data['Request']);

    // Fetch articles if side menu is active
    $articles_grouped = [];
    if ($side_menu && !empty($products)) {
        $articles_grouped = wexoe_pa_test_fetch_articles_for_products($products);
    }

    // Build output
    $id = 'wexoe-pa-' . uniqid();
    $html = '';

    // Debug output
    if ($debug) {
        $html .= '<div class="wexoe-pa-debug">';
        $html .= "=== PRODUCT AREA: " . esc_html($slug) . " ===\n\n";
        $html .= "Side menu: " . ($side_menu ? 'YES' : 'NO') . "\n\n";
        $html .= "Fields:\n" . esc_html(print_r($data, true)) . "\n\n";
        $html .= "Products (" . count($products) . "):\n" . esc_html(print_r($products, true)) . "\n\n";
        $html .= "Solutions (" . count($solutions) . "):\n" . esc_html(print_r($solutions, true));
        if ($side_menu) {
            $html .= "\n\nArticles grouped:\n" . esc_html(print_r($articles_grouped, true));
        }
        $html .= '</div>';
    }

    // CSS
    $html .= wexoe_pa_test_render_css($id);

    // Remove Enfold content padding if enabled
    if ($remove_padding) {
        $html .= '<style>
        .template-page.content { padding-top: 0 !important; }
        </style>';
        $html .= '<script>
        (function() {
            var h = document.querySelector("header.header, #header, .av-header-area");
            var m = document.getElementById("main");
            if (!h || !m) return;
            var fix = function() {
                var pos = getComputedStyle(h).position;
                if (pos === "fixed" || pos === "sticky") {
                    var bottom = h.getBoundingClientRect().bottom;
                    m.style.setProperty("padding-top", bottom + "px", "important");
                    m.style.setProperty("margin-top", "0", "important");
                }
            };
            fix();
            window.addEventListener("resize", fix);
            window.addEventListener("scroll", function() { setTimeout(fix, 100); }, { once: true });
        })();
        </script>';
    }

    // Wrapper
    $html .= '<div id="' . $id . '" class="wexoe-pa-wrapper">';

    // Sections in fixed order
    $html .= wexoe_pa_test_render_top($data, $id);
    $html .= wexoe_pa_test_render_hero($data, $id);

    // Normal sections with "upp" checkbox go before toggle
    for ($n = 1; $n <= 4; $n++) {
        if (!empty($data['Normal ' . $n . ' upp'])) {
            $html .= wexoe_pa_test_render_normal($data, $n, $id);
        }
    }

    // Render toggle (default) or side menu (when "Side menu" checkbox is set)
    if (!$side_menu) {
        $html .= wexoe_pa_test_render_products($products, $data, $id);
    } else {
        $html .= wexoe_pa_test_render_side_menu($products, $articles_grouped, $data, $id, $request_mode);
    }

    // Render request form (when "Request" checkbox is set)
    if ($request_mode) {
        $html .= wexoe_pa_test_render_request_form($data, $id, $articles_grouped);
    }

    $html .= wexoe_pa_test_render_solutions($solutions, $data, $id);

    // Remaining normal sections go after solutions
    for ($n = 1; $n <= 4; $n++) {
        if (empty($data['Normal ' . $n . ' upp'])) {
            $html .= wexoe_pa_test_render_normal($data, $n, $id);
        }
    }

    $html .= wexoe_pa_test_render_our_guy($data, $id);
    $html .= wexoe_pa_test_render_docs($data, $id);

    $html .= '</div>'; // wrapper

    // JavaScript
    $html .= wexoe_pa_test_render_js($id, $request_mode);

    return $html;
}

add_shortcode('wexoe_product_area', 'wexoe_product_area_test_shortcode');

/* ============================================================
   REQUEST FORM — AJAX HANDLER
   ============================================================ */

if (!function_exists('wexoe_pa_test_handle_request_submit')) {
function wexoe_pa_test_handle_request_submit() {
    if (!wp_verify_nonce($_POST['nonce'], 'wexoe_pa_test_request_nonce')) {
        wp_send_json_error('Säkerhetsfel. Ladda om sidan och försök igen.');
        return;
    }

    $artiklar_raw = stripslashes($_POST['artiklar'] ?? '[]');
    $artiklar = json_decode($artiklar_raw, true);
    if (!is_array($artiklar)) $artiklar = [];

    $data = [
        'namn'         => sanitize_text_field($_POST['namn'] ?? ''),
        'foretag'      => sanitize_text_field($_POST['foretag'] ?? ''),
        'telefon'      => sanitize_text_field($_POST['telefon'] ?? ''),
        'epost'        => sanitize_email($_POST['epost'] ?? ''),
        'behov'        => !empty(sanitize_text_field($_POST['customer_id'] ?? '')) ? 'orderforfragan' : 'prisforfragan',
        'meddelande'   => sanitize_textarea_field($_POST['meddelande'] ?? ''),
        'gdpr_consent' => isset($_POST['gdpr_consent']) ? true : false,
        'customer_id'  => sanitize_text_field($_POST['customer_id'] ?? ''),
        'artiklar'     => $artiklar,
        'submitted_at' => current_time('mysql'),
        'page_url'     => esc_url($_POST['page_url'] ?? ''),
        'user_agent'   => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
    ];

    // Validate required fields
    if (empty($data['namn']) || empty($data['epost']) || empty($data['foretag']) || empty($data['telefon'])) {
        wp_send_json_error('Vänligen fyll i alla obligatoriska fält.');
        return;
    }
    if (!is_email($data['epost'])) {
        wp_send_json_error('Vänligen ange en giltig e-postadress.');
        return;
    }
    // If no articles, message is required
    if (empty($artiklar) && empty($data['meddelande'])) {
        wp_send_json_error('Lägg till minst en artikel eller skriv ett meddelande.');
        return;
    }

    $webhook_url = 'https://hook.eu1.make.com/sulae2u3lux9g9dqfabtsdngiwz46s6g';
    $response = wp_remote_post($webhook_url, [
        'body'    => json_encode($data, JSON_UNESCAPED_UNICODE),
        'headers' => ['Content-Type' => 'application/json'],
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error('Något gick fel. Försök igen eller ring oss direkt.');
        return;
    }

    wp_send_json_success([
        'message' => 'Tack ' . $data['namn'] . '! Vi har mottagit din prisförfrågan och återkommer inom kort.',
    ]);
}
add_action('wp_ajax_wexoe_pa_test_request_submit', 'wexoe_pa_test_handle_request_submit');
add_action('wp_ajax_nopriv_wexoe_pa_test_request_submit', 'wexoe_pa_test_handle_request_submit');
}

/* ============================================================
   CUSTOMER PRICE LOOKUP — AJAX HANDLER
   ============================================================ */

if (!function_exists('wexoe_pa_test_parse_prices')) {
function wexoe_pa_test_parse_prices($text) {
    if (empty($text)) return [];
    $lines = preg_split('/\r\n|\r|\n/', $text);
    $prices = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '=') === false) continue;
        $parts = explode('=', $line, 2);
        $artnr = trim($parts[0]);
        $price = trim($parts[1]);
        if ($artnr !== '' && $price !== '') {
            $prices[$artnr] = $price;
        }
    }
    return $prices;
}
}

if (!function_exists('wexoe_pa_test_customer_lookup')) {
function wexoe_pa_test_customer_lookup() {
    $customer_id = sanitize_text_field($_POST['customer_id'] ?? '');
    if (empty($customer_id)) {
        wp_send_json_error('Ange ett kund-ID.');
        return;
    }

    $repo = wexoe_pa_test_get_repo('customers');
    if (!$repo) {
        wp_send_json_error('Kunddata kunde inte läsas (customers-schema saknas i Wexoe Core).');
        return;
    }

    $record = $repo->find($customer_id);
    if (empty($record)) {
        wp_send_json_error('Inget kundkonto hittades med det ID:t.');
        return;
    }

    $name = isset($record['name']) ? $record['name'] : '';
    $prices_text = isset($record['prices']) ? $record['prices'] : '';
    $prices = wexoe_pa_test_parse_prices($prices_text);

    wp_send_json_success([
        'name' => $name,
        'prices' => $prices,
    ]);
}
add_action('wp_ajax_wexoe_pa_test_customer_lookup', 'wexoe_pa_test_customer_lookup');
add_action('wp_ajax_nopriv_wexoe_pa_test_customer_lookup', 'wexoe_pa_test_customer_lookup');
}
