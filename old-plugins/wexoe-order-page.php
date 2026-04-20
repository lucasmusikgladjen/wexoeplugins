<?php
/**
 * Plugin Name: Wexoe Order Page
 * Description: Standalone order/request page with collapsible product menu, article cards, variants, and request form. Use [wexoe_order] or [wexoe_order areas="fiber,kablar" mode="light"].
 * Version: 1.6.0
 * Author: Wexoe
 * Text Domain: wexoe-order-page
 */

if (!defined('ABSPATH')) exit;

/* ============================================================
   SHARED CONSTANTS (safe if wexoe-product-area is also active)
   ============================================================ */

if (!defined('WEXOE_PA_AIRTABLE_API_KEY')) {
    define('WEXOE_PA_AIRTABLE_API_KEY', '');
}
if (!defined('WEXOE_PA_AIRTABLE_BASE_ID')) {
    define('WEXOE_PA_AIRTABLE_BASE_ID', 'appXoUcK68dQwASjF');
}
if (!defined('WEXOE_PA_TABLE_PRODUCT_AREAS')) {
    define('WEXOE_PA_TABLE_PRODUCT_AREAS', 'Product Areas');
}
if (!defined('WEXOE_PA_TABLE_PRODUCTS')) {
    define('WEXOE_PA_TABLE_PRODUCTS', 'Products');
}
if (!defined('WEXOE_PA_TABLE_ARTICLES')) {
    define('WEXOE_PA_TABLE_ARTICLES', 'Articles');
}
if (!defined('WEXOE_PA_TABLE_CUSTOMERS')) {
    define('WEXOE_PA_TABLE_CUSTOMERS', 'Customers');
}
if (!defined('WEXOE_PA_TABLE_DIVISIONS')) {
    define('WEXOE_PA_TABLE_DIVISIONS', 'Divisions');
}
if (!defined('WEXOE_PA_CACHE_TTL')) {
    define('WEXOE_PA_CACHE_TTL', 300);
}

/* ============================================================
   SHARED HELPERS (only define if not already loaded)
   ============================================================ */

if (!function_exists('wexoe_pa_airtable_request')) {
    function wexoe_pa_airtable_request($table, $params = []) {
        $url = 'https://api.airtable.com/v0/' . WEXOE_PA_AIRTABLE_BASE_ID . '/' . rawurlencode($table);
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        $response = wp_remote_get($url, [
            'headers' => ['Authorization' => 'Bearer ' . WEXOE_PA_AIRTABLE_API_KEY],
            'timeout' => 15,
        ]);
        if (is_wp_error($response)) return ['error' => $response->get_error_message()];
        return json_decode(wp_remote_retrieve_body($response), true);
    }
}

if (!function_exists('wexoe_pa_field')) {
    function wexoe_pa_field($data, $field, $default = '') {
        return isset($data[$field]) && $data[$field] !== '' && $data[$field] !== null ? $data[$field] : $default;
    }
}

if (!function_exists('wexoe_pa_md')) {
    function wexoe_pa_md($text) {
        if (empty($text)) return '';
        $t = esc_html($text);
        $t = preg_replace('/\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/', '<a href="$2" target="_blank" rel="noopener">$1</a>', $t);
        $t = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $t);
        $t = preg_replace('/__(.+?)__/', '<strong>$1</strong>', $t);
        $t = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $t);
        $t = preg_replace('/(?<!\w)_(.+?)_(?!\w)/', '<em>$1</em>', $t);
        $t = preg_replace('/~~(.+?)~~/', '<del>$1</del>', $t);
        $t = preg_replace('/`([^`]+)`/', '<code>$1</code>', $t);
        $t = nl2br($t);
        return $t;
    }
}

if (!function_exists('wexoe_pa_lines_to_array')) {
    function wexoe_pa_lines_to_array($text) {
        if (empty($text)) return [];
        $lines = preg_split('/\r\n|\r|\n/', $text);
        return array_values(array_filter(array_map('trim', $lines), function($l) { return $l !== ''; }));
    }
}

if (!function_exists('wexoe_pa_parse_variants')) {
    function wexoe_pa_parse_variants($text) {
        if (empty($text)) return null;
        $lines = preg_split('/\r\n|\r|\n/', $text);
        $dimensions = [];
        $map = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            if (strpos($line, '@') === 0) {
                $colon = strpos($line, ':');
                if ($colon === false) continue;
                $dim_name = trim(substr($line, 1, $colon - 1));
                $options = array_values(array_filter(array_map('trim', explode(',', substr($line, $colon + 1))), function($o) { return $o !== ''; }));
                if ($dim_name && !empty($options)) $dimensions[] = ['name' => $dim_name, 'options' => $options];
            } elseif (strpos($line, '=') !== false) {
                $parts = explode('=', $line, 2);
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                if ($key !== '' && $value !== '') $map[$key] = $value;
            }
        }
        if (empty($dimensions)) return null;
        return ['dimensions' => $dimensions, 'map' => $map];
    }
}

if (!function_exists('wexoe_pa_parse_prices')) {
    function wexoe_pa_parse_prices($text) {
        if (empty($text)) return [];
        $lines = preg_split('/\r\n|\r|\n/', $text);
        $prices = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '=') === false) continue;
            $parts = explode('=', $line, 2);
            $artnr = trim($parts[0]);
            $price = trim($parts[1]);
            if ($artnr !== '' && $price !== '') $prices[$artnr] = $price;
        }
        return $prices;
    }
}

if (!function_exists('wexoe_pa_render_media')) {
    function wexoe_pa_render_media($url, $alt = '', $class_prefix = '') {
        if (empty($url)) return '';
        if (preg_match('/[?&]v=([a-zA-Z0-9_-]{11})/', $url, $m) || preg_match('/youtu\.be\/([a-zA-Z0-9_-]{11})/', $url, $m)) {
            return '<div class="'.$class_prefix.'video-wrap"><iframe src="https://www.youtube-nocookie.com/embed/'.$m[1].'?rel=0" frameborder="0" allowfullscreen loading="lazy"></iframe></div>';
        }
        return '<div class="'.$class_prefix.'img-wrap"><img src="'.esc_url($url).'" alt="'.esc_attr($alt).'" loading="lazy"/></div>';
    }
}

/* ============================================================
   DATA FETCHING
   ============================================================ */

function wexoe_op_fetch_all_areas($slugs = []) {
    $cache_key = 'wexoe_op_areas_' . md5(implode(',', $slugs));
    $cached = get_transient($cache_key);
    if ($cached !== false) return $cached;

    $params = [
        'filterByFormula' => '{Side menu}=TRUE()',
        'pageSize' => 100,
    ];

    $all = [];
    $offset = null;
    do {
        if ($offset) $params['offset'] = $offset;
        $result = wexoe_pa_airtable_request(WEXOE_PA_TABLE_PRODUCT_AREAS, $params);
        if (isset($result['error']) || !isset($result['records'])) break;
        foreach ($result['records'] as $rec) {
            $fields = $rec['fields'];
            $fields['_record_id'] = $rec['id'];
            // Filter by slugs if provided
            if (!empty($slugs) && !in_array($fields['Slug'] ?? '', $slugs)) continue;
            $all[] = $fields;
        }
        $offset = $result['offset'] ?? null;
    } while ($offset);

    set_transient($cache_key, $all, WEXOE_PA_CACHE_TTL);
    return $all;
}

function wexoe_op_fetch_products($record_ids) {
    if (empty($record_ids)) return [];
    $cache_key = 'wexoe_op_prods_' . md5(implode(',', $record_ids));
    $cached = get_transient($cache_key);
    if ($cached !== false) return $cached;

    $parts = [];
    foreach ($record_ids as $id) $parts[] = 'RECORD_ID()="' . $id . '"';
    $formula = 'OR(' . implode(',', $parts) . ')';

    $all = [];
    $offset = null;
    do {
        $params = ['filterByFormula' => $formula, 'pageSize' => 100];
        if ($offset) $params['offset'] = $offset;
        $result = wexoe_pa_airtable_request(WEXOE_PA_TABLE_PRODUCTS, $params);
        if (isset($result['error']) || !isset($result['records'])) break;
        foreach ($result['records'] as $rec) {
            $fields = $rec['fields'];
            $fields['_record_id'] = $rec['id'];
            $all[] = $fields;
        }
        $offset = $result['offset'] ?? null;
    } while ($offset);

    // Sort by Order, filter by Visa
    usort($all, function($a, $b) {
        return (($a['Order'] ?? 999) - ($b['Order'] ?? 999));
    });
    $all = array_values(array_filter($all, function($r) { return !empty($r['Visa']); }));

    set_transient($cache_key, $all, WEXOE_PA_CACHE_TTL);
    return $all;
}

function wexoe_op_fetch_articles($products) {
    if (empty($products)) return [];
    $all_ids = [];
    $map = [];
    foreach ($products as $p) {
        $pid = $p['_record_id'] ?? '';
        $aids = (isset($p['Articles']) && is_array($p['Articles'])) ? $p['Articles'] : [];
        $map[$pid] = $aids;
        $all_ids = array_merge($all_ids, $aids);
    }
    $all_ids = array_values(array_unique($all_ids));
    if (empty($all_ids)) {
        $grouped = [];
        foreach ($products as $p) $grouped[$p['_record_id'] ?? ''] = [];
        return $grouped;
    }

    $cache_key = 'wexoe_op_arts_' . md5(implode(',', $all_ids));
    $cached = get_transient($cache_key);
    $articles = [];

    if ($cached !== false) {
        $articles = $cached;
    } else {
        $parts = [];
        foreach ($all_ids as $aid) $parts[] = 'RECORD_ID()="' . $aid . '"';
        $formula = 'OR(' . implode(',', $parts) . ')';
        $offset = null;
        do {
            $params = ['filterByFormula' => $formula, 'pageSize' => 100];
            if ($offset) $params['offset'] = $offset;
            $result = wexoe_pa_airtable_request(WEXOE_PA_TABLE_ARTICLES, $params);
            if (isset($result['error']) || !isset($result['records'])) break;
            foreach ($result['records'] as $rec) {
                $f = $rec['fields'];
                $f['_record_id'] = $rec['id'];
                $articles[] = $f;
            }
            $offset = $result['offset'] ?? null;
        } while ($offset);
        set_transient($cache_key, $articles, WEXOE_PA_CACHE_TTL);
    }

    $by_id = [];
    foreach ($articles as $a) $by_id[$a['_record_id'] ?? ''] = $a;

    $grouped = [];
    foreach ($map as $pid => $aids) {
        $grouped[$pid] = [];
        foreach ($aids as $aid) {
            if (isset($by_id[$aid])) $grouped[$pid][] = $by_id[$aid];
        }
    }
    return $grouped;
}

/* ============================================================
   FETCH SUPPLIER LOGOS (Partners table)
   ============================================================ */

function wexoe_op_fetch_supplier_logos($supplier_ids) {
    if (empty($supplier_ids)) return [];
    $supplier_ids = array_values(array_unique($supplier_ids));
    $cache_key = 'wexoe_op_suppl_' . md5(implode(',', $supplier_ids));
    $cached = get_transient($cache_key);
    if ($cached !== false) return $cached;

    $parts = [];
    foreach ($supplier_ids as $sid) $parts[] = 'RECORD_ID()="' . $sid . '"';
    $formula = 'OR(' . implode(',', $parts) . ')';

    $logos = [];
    $offset = null;
    do {
        $params = ['filterByFormula' => $formula, 'pageSize' => 100];
        if ($offset) $params['offset'] = $offset;
        $result = wexoe_pa_airtable_request('Partners', $params);
        if (isset($result['error']) || !isset($result['records'])) break;
        foreach ($result['records'] as $rec) {
            $logo = $rec['fields']['Logo transparent'] ?? '';
            if ($logo !== '') {
                $logos[$rec['id']] = $logo;
            }
        }
        $offset = $result['offset'] ?? null;
    } while ($offset);

    set_transient($cache_key, $logos, WEXOE_PA_CACHE_TTL);
    return $logos;
}

/* ============================================================
   MAIN SHORTCODE
   ============================================================ */

function wexoe_order_page_shortcode($atts) {
    $atts = shortcode_atts([
        'areas' => '',
        'mode' => 'dark',
        'debug' => 'false',
        'nocache' => 'false',
    ], $atts, 'wexoe_order');

    $debug = ($atts['debug'] === 'true');
    $is_dark = ($atts['mode'] !== 'light');

    // Clear caches
    if ($atts['nocache'] === 'true') {
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wexoe_op_%' OR option_name LIKE '_transient_timeout_wexoe_op_%'");
    }

    // Parse slugs
    $slugs = [];
    if (!empty($atts['areas'])) {
        $slugs = array_map('trim', explode(',', $atts['areas']));
    }

    // Fetch all areas with Side menu = true
    $areas = wexoe_op_fetch_all_areas($slugs);
    if (empty($areas) && !$debug) return '';

    // Fetch products and articles for each area
    $area_data = [];
    $all_articles_flat = [];
    foreach ($areas as $area) {
        $product_ids = (isset($area['Products']) && is_array($area['Products'])) ? $area['Products'] : [];
        $products = wexoe_op_fetch_products($product_ids);
        $articles_grouped = wexoe_op_fetch_articles($products);

        // Flatten articles for search
        foreach ($articles_grouped as $pid => $arts) {
            foreach ($arts as $art) {
                $v = wexoe_pa_parse_variants(wexoe_pa_field($art, 'Varianter', ''));
                $all_articles_flat[] = [
                    'name' => wexoe_pa_field($art, 'Name', ''),
                    'nr' => wexoe_pa_field($art, 'Artikelnummer', ''),
                    'variants' => $v,
                ];
            }
        }

        $area_data[] = [
            'area' => $area,
            'products' => $products,
            'articles' => $articles_grouped,
        ];
    }

    // Collect all supplier record IDs from articles and fetch logos
    $all_supplier_ids = [];
    foreach ($area_data as $ad) {
        foreach ($ad['articles'] as $pid => $arts) {
            foreach ($arts as $art) {
                $sids = (isset($art['Supplier']) && is_array($art['Supplier'])) ? $art['Supplier'] : [];
                $all_supplier_ids = array_merge($all_supplier_ids, $sids);
            }
        }
    }
    $supplier_logos = wexoe_op_fetch_supplier_logos($all_supplier_ids);

    $id = 'wexoe-op-' . uniqid();
    $mode_class = $is_dark ? 'wexoe-op-dark' : 'wexoe-op-light';
    $nonce = wp_create_nonce('wexoe_pa_request_nonce');
    $ajax_url = admin_url('admin-ajax.php');

    // Group areas by Division
    $division_map = ['IT INFRA' => 'IT Infrastruktur', 'INDUSTRY' => 'Automation'];
    $div_record_ids = [];
    foreach ($area_data as $ad) {
        $div_links = (isset($ad['area']['Division']) && is_array($ad['area']['Division'])) ? $ad['area']['Division'] : [];
        $div_record_ids = array_merge($div_record_ids, $div_links);
    }
    $div_record_ids = array_values(array_unique($div_record_ids));

    // Fetch division names
    $div_names = [];
    if (!empty($div_record_ids)) {
        $div_parts = [];
        foreach ($div_record_ids as $did) $div_parts[] = 'RECORD_ID()="' . $did . '"';
        $div_result = wexoe_pa_airtable_request(WEXOE_PA_TABLE_DIVISIONS, [
            'filterByFormula' => 'OR(' . implode(',', $div_parts) . ')',
        ]);
        if (!empty($div_result['records'])) {
            foreach ($div_result['records'] as $dr) {
                $dname = $dr['fields']['Name'] ?? '';
                $div_names[$dr['id']] = $dname;
            }
        }
    }

    // Build grouped structure: division → [area_data entries]
    $divisions_grouped = [];
    foreach ($area_data as $ai => $ad) {
        $div_links = (isset($ad['area']['Division']) && is_array($ad['area']['Division'])) ? $ad['area']['Division'] : [];
        $div_id = !empty($div_links) ? $div_links[0] : 'other';
        $raw_name = $div_names[$div_id] ?? 'Övrigt';
        $display_name = $division_map[$raw_name] ?? $raw_name;
        if (!isset($divisions_grouped[$div_id])) {
            $divisions_grouped[$div_id] = ['name' => $display_name, 'areas' => []];
        }
        $divisions_grouped[$div_id]['areas'][] = ['ai' => $ai, 'data' => $ad];
    }

    $html = '';

    // Debug
    if ($debug) {
        $html .= '<div style="background:#1a1a2e;color:#0f8;font-family:monospace;font-size:12px;padding:20px;margin:20px 0;white-space:pre-wrap;border-radius:8px;overflow-x:auto;">';
        $html .= "=== ORDER PAGE DEBUG ===\nAreas: " . count($areas) . "\n";
        foreach ($area_data as $ad) {
            $html .= "\nArea: " . ($ad['area']['Name'] ?? '?') . " | Products: " . count($ad['products']);
            foreach ($ad['products'] as $p) {
                $pid = $p['_record_id'] ?? '';
                $artcount = count($ad['articles'][$pid] ?? []);
                $html .= "\n  Product: " . ($p['Name'] ?? '?') . " | Articles: " . $artcount;
            }
        }
        $html .= "\n\nAll articles for search: " . count($all_articles_flat);
        $html .= '</div>';
    }

    // CSS
    $html .= wexoe_op_render_css($id, $is_dark);

    // Wrapper
    $html .= '<div id="'.$id.'" class="wexoe-op-wrapper '.$mode_class.'">';

    // Layout: sidebar + content
    $html .= '<div class="wexoe-op-layout">';

    // === SIDEBAR (3-level: Division → Area → Product) ===
    $html .= '<nav class="wexoe-op-sidebar">';
    $first_product_set = false;
    $first_div = true;
    foreach ($divisions_grouped as $div_id => $div_data) {
        $html .= '<div class="wexoe-op-nav-division'.($first_div ? ' wexoe-op-div-open' : '').'">';
        $html .= '<button class="wexoe-op-nav-div-header">';
        $html .= '<span>'.esc_html($div_data['name']).'</span>';
        $html .= '<span class="wexoe-op-nav-chevron">&rsaquo;</span>';
        $html .= '</button>';
        $html .= '<div class="wexoe-op-nav-div-body'.($first_div ? ' wexoe-op-nav-open' : '').'">';

        $first_area_in_div = true;
        foreach ($div_data['areas'] as $ae) {
            $ai = $ae['ai'];
            $ad = $ae['data'];
            $area_name = wexoe_pa_field($ad['area'], 'Name', 'Kategori');
            $html .= '<div class="wexoe-op-nav-group'.($first_div && $first_area_in_div ? ' wexoe-op-group-open' : '').'">';
            $html .= '<button class="wexoe-op-nav-header">';
            $html .= '<span>'.esc_html($area_name).'</span>';
            $html .= '<span class="wexoe-op-nav-chevron">&rsaquo;</span>';
            $html .= '</button>';
            $html .= '<div class="wexoe-op-nav-items'.($first_div && $first_area_in_div ? ' wexoe-op-nav-open' : '').'">';
            foreach ($ad['products'] as $pi => $product) {
                $pname = wexoe_pa_field($product, 'Name', 'Produkt');
                $active = (!$first_product_set) ? ' wexoe-op-nav-active' : '';
                $html .= '<button class="wexoe-op-nav-item'.$active.'" data-area="'.$ai.'" data-product="'.$pi.'">'.esc_html($pname).'</button>';
                $first_product_set = true;
            }
            $html .= '</div></div>';
            $first_area_in_div = false;
        }

        $html .= '</div></div>';
        $first_div = false;
    }
    // Mobile selects: 3 levels (division + area on one row, product full width)
    // Build navigation map for JS cascading
    $nav_map = [];
    foreach ($divisions_grouped as $div_id => $div_data) {
        $div_entry = ['name' => $div_data['name'], 'areas' => []];
        foreach ($div_data['areas'] as $ae) {
            $ai = $ae['ai'];
            $ad = $ae['data'];
            $area_name = wexoe_pa_field($ad['area'], 'Name', '');
            $products_list = [];
            foreach ($ad['products'] as $pi => $product) {
                $products_list[] = ['name' => wexoe_pa_field($product, 'Name', ''), 'key' => $ai.'-'.$pi];
            }
            $div_entry['areas'][] = ['name' => $area_name, 'products' => $products_list];
        }
        $nav_map[] = $div_entry;
    }

    $html .= '<div class="wexoe-op-mobile-nav" data-nav-map="'.esc_attr(json_encode($nav_map, JSON_UNESCAPED_UNICODE)).'">';
    $html .= '<div class="wexoe-op-mobile-row">';
    // Division select
    $html .= '<select class="wexoe-op-mob-division">';
    foreach ($divisions_grouped as $div_id => $div_data) {
        $html .= '<option>'.esc_html($div_data['name']).'</option>';
    }
    $html .= '</select>';
    // Area select
    $html .= '<select class="wexoe-op-mob-area"></select>';
    $html .= '</div>';
    // Product select (full width)
    $html .= '<select class="wexoe-op-mob-product"></select>';
    $html .= '</div>';
    $html .= '</nav>';

    // === CONTENT AREA ===
    $html .= '<div class="wexoe-op-content">';
    $first_panel = true;
    foreach ($area_data as $ai => $ad) {
        foreach ($ad['products'] as $pi => $product) {
            $pid = $product['_record_id'] ?? '';
            $name = wexoe_pa_field($product, 'Name', 'Produkt');
            $heading = wexoe_pa_field($product, 'Header side menu', $name);
            $desc = wexoe_pa_field($product, 'Description', '');
            $bullets = wexoe_pa_lines_to_array(wexoe_pa_field($product, 'Bullets', ''));
            $horizontal = !empty($product['Horizontal']);
            $product_articles = $ad['articles'][$pid] ?? [];

            $vis = $first_panel ? '' : ' style="display:none;"';
            $html .= '<div class="wexoe-op-panel" data-panel="'.$ai.'-'.$pi.'"'.$vis.'>';

            // Heading
            $html .= '<h2 class="wexoe-op-panel-h2">'.esc_html($heading).'</h2>';

            // Content row: text left, image right
            $prod_image = wexoe_pa_field($product, 'Image', '');
            $btn2_text = wexoe_pa_field($product, 'Button 2 Text', '');
            $btn2_url = wexoe_pa_field($product, 'Button 2 URL', '');

            if ($prod_image) {
                $html .= '<div class="wexoe-op-panel-row">';
            }

            $html .= '<div class="wexoe-op-panel-text-col">';

            // Description
            if ($desc) {
                $html .= '<p class="wexoe-op-panel-desc">'.wexoe_pa_md($desc).'</p>';
            }

            // Bullets
            if (!empty($bullets)) {
                $html .= '<ul class="wexoe-op-panel-checks">';
                foreach ($bullets as $b) {
                    $html .= '<li><span class="wexoe-op-check">&#10003;</span> '.wexoe_pa_md($b).'</li>';
                }
                $html .= '</ul>';
            }

            // Buttons: only show "Gör en förfrågan" for products WITHOUT articles
            if (empty($product_articles)) {
                $html .= '<div class="wexoe-op-panel-btns">';
                $html .= '<a href="javascript:void(0)" class="wexoe-op-btn-product-inquiry wexoe-op-panel-btn-primary" data-product-name="'.esc_attr($heading).'">Gör en förfrågan &rarr;</a>';
                if ($btn2_text && $btn2_url) {
                    $html .= '<a href="'.esc_url($btn2_url).'" class="wexoe-op-panel-btn-secondary" target="_blank" rel="noopener">'.esc_html($btn2_text).' &rarr;</a>';
                }
                $html .= '</div>';
            } else {
                // Products with articles: only show btn2 if present
                if ($btn2_text && $btn2_url) {
                    $html .= '<div class="wexoe-op-panel-btns">';
                    $html .= '<a href="'.esc_url($btn2_url).'" class="wexoe-op-panel-btn-secondary" target="_blank" rel="noopener">'.esc_html($btn2_text).' &rarr;</a>';
                    $html .= '</div>';
                }
            }

            $html .= '</div>'; // text-col

            // Image column
            if ($prod_image) {
                $html .= '<div class="wexoe-op-panel-img">';
                $html .= '<img src="'.esc_url($prod_image).'" alt="'.esc_attr($heading).'" loading="lazy"/>';
                $html .= '</div>';
                $html .= '</div>'; // panel-row
            }

            // Article cards
            if (!empty($product_articles)) {
                $grid_class = $horizontal ? 'wexoe-op-articles-grid wexoe-op-articles-horiz' : 'wexoe-op-articles-grid';
                $html .= '<div class="wexoe-op-articles">';
                $html .= '<div class="'.$grid_class.'">';
                foreach ($product_articles as $arti => $article) {
                    $art_name = wexoe_pa_field($article, 'Name', '');
                    $art_nr = wexoe_pa_field($article, 'Artikelnummer', '');
                    $art_ds = wexoe_pa_field($article, 'Datablad', '');
                    $art_ws = wexoe_pa_field($article, 'Länk till webshop', '');
                    $art_img = wexoe_pa_field($article, 'Bild', '');
                    $art_desc = wexoe_pa_field($article, 'Description', '');
                    $variants = wexoe_pa_parse_variants(wexoe_pa_field($article, 'Varianter', ''));
                    $has_v = ($variants !== null && !empty($variants['dimensions']));

                    $card_class = $horizontal ? 'wexoe-op-article-card wexoe-op-article-horiz' : 'wexoe-op-article-card';
                    $html .= '<div class="'.$card_class.'">';

                    // Supplier logo
                    $supplier_ids = (isset($article['Supplier']) && is_array($article['Supplier'])) ? $article['Supplier'] : [];
                    $supplier_logo = '';
                    foreach ($supplier_ids as $sid) {
                        if (!empty($supplier_logos[$sid])) { $supplier_logo = $supplier_logos[$sid]; break; }
                    }

                    // Image + badge logic
                    if ($art_img) {
                        // Has product image: show badge on top
                        if ($supplier_logo) {
                            $html .= '<div class="wexoe-op-supplier-badge">';
                            if (strpos(trim($supplier_logo), '<') === 0) {
                                $html .= '<div class="wexoe-op-supplier-svg">' . $supplier_logo . '</div>';
                            } else {
                                $html .= '<img src="'.esc_url($supplier_logo).'" alt="Supplier" loading="lazy"/>';
                            }
                            $html .= '</div>';
                        }
                        $html .= '<div class="wexoe-op-article-img"><img src="'.esc_url($art_img).'" alt="'.esc_attr($art_name).'" loading="lazy"/></div>';
                    } elseif ($supplier_logo) {
                        // No product image: supplier logo becomes the main image
                        $html .= '<div class="wexoe-op-article-img wexoe-op-article-supplier-main">';
                        if (strpos(trim($supplier_logo), '<') === 0) {
                            $html .= '<div class="wexoe-op-supplier-main-svg">' . $supplier_logo . '</div>';
                        } else {
                            $html .= '<img src="'.esc_url($supplier_logo).'" alt="'.esc_attr($art_name).'" loading="lazy"/>';
                        }
                        $html .= '</div>';
                    } else {
                        $html .= '<div class="wexoe-op-article-img wexoe-op-article-placeholder"></div>';
                    }

                    // Info
                    $html .= '<div class="wexoe-op-article-info">';
                    $html .= '<div class="wexoe-op-article-name">'.esc_html($art_name).'</div>';

                    $html .= '<div class="wexoe-op-article-mid">';
                    $initial_key = '';
                    if ($has_v) {
                        $html .= '<div class="wexoe-op-variant-wrap" data-variant-map="'.esc_attr(json_encode($variants['map'], JSON_UNESCAPED_UNICODE)).'">';
                        foreach ($variants['dimensions'] as $di => $dim) {
                            $html .= '<div class="wexoe-op-variant-row">';
                            $html .= '<select class="wexoe-op-variant-select" data-dim="'.$di.'">';
                            foreach ($dim['options'] as $oi => $opt) {
                                $html .= '<option value="'.esc_attr($opt).'"'.($oi === 0 ? ' selected' : '').'>'.esc_html($opt).'</option>';
                            }
                            $html .= '</select></div>';
                        }
                        $html .= '</div>';
                        $ip = [];
                        foreach ($variants['dimensions'] as $d) $ip[] = $d['options'][0];
                        $initial_key = implode('/', $ip);
                        $initial_nr = $variants['map'][$initial_key] ?? $art_nr;
                        $html .= '<div class="wexoe-op-article-nr wexoe-op-variant-artnr"><span class="wexoe-op-nr-label">Art.</span><span class="wexoe-op-nr-value">'.esc_html($initial_nr).'</span></div>';
                    } else {
                        if ($art_desc) {
                            $html .= '<p class="wexoe-op-article-desc">'.esc_html($art_desc).'</p>';
                        }
                        if ($art_nr) {
                            $html .= '<div class="wexoe-op-article-nr"><span class="wexoe-op-nr-label">Art.</span><span class="wexoe-op-nr-value">'.esc_html($art_nr).'</span></div>';
                        }
                    }
                    $html .= '</div>'; // mid

                    // Buttons
                    $html .= '<div class="wexoe-op-article-btns">';
                    if ($art_ds) {
                        $html .= '<a href="'.esc_url($art_ds).'" target="_blank" rel="noopener" class="wexoe-op-btn-ds">Datablad</a>';
                    }
                    if ($art_ws) {
                        $html .= '<a href="'.esc_url($art_ws).'" target="_blank" rel="noopener" class="wexoe-op-btn-ds">Webshop</a>';
                    }
                    $display_nr = $has_v ? ($variants['map'][$initial_key] ?? $art_nr) : $art_nr;
                    $vj = $has_v ? esc_attr(json_encode($variants, JSON_UNESCAPED_UNICODE)) : '';
                    $html .= '<a href="javascript:void(0)" class="wexoe-op-btn-order" data-art-name="'.esc_attr($art_name).'"'.($vj ? ' data-variants="'.$vj.'"' : '').'><span class="wexoe-op-btn-full">Lägg till i förfrågan</span><span class="wexoe-op-btn-short">Lägg till</span></a>';
                    $html .= '</div>';

                    $html .= '</div>'; // info
                    $html .= '</div>'; // card
                }
                $html .= '</div></div>';
            }

            $html .= '</div>'; // panel
            $first_panel = false;
        }
    }
    $html .= '</div>'; // content
    $html .= '</div>'; // layout

    // === REQUEST FORM ===
    $html .= '<section class="wexoe-op-request" id="wexoe-op-request-form">';
    $html .= '<div class="wexoe-op-request-inner">';
    $html .= '<div class="wexoe-op-request-header-row">';
    $html .= '<div><h2 class="wexoe-op-request-h2">Prisförfrågan</h2>';
    $html .= '<p class="wexoe-op-request-subtitle">Lägg till artiklar, specificera variant och antal. Vi återkommer inom kort med prisförslag.</p></div>';
    $html .= '<div class="wexoe-op-customer-toggle">';
    $html .= '<button type="button" class="wexoe-op-customer-trigger">Har du ett kund-ID? <span class="wexoe-op-chevron">&rsaquo;</span></button>';
    $html .= '<div class="wexoe-op-customer-panel">';
    $html .= '<div class="wexoe-op-customer-status"></div>';
    $html .= '<div class="wexoe-op-customer-input-wrap">';
    $html .= '<input type="text" name="customer_id" class="wexoe-op-customer-input">';
    $html .= '<button type="button" class="wexoe-op-customer-btn">Hämta priser</button>';
    $html .= '</div></div></div></div>';

    $html .= '<form class="wexoe-op-request-form" data-nonce="'.esc_attr($nonce).'" data-ajax="'.esc_attr($ajax_url).'">';
    $html .= '<div class="wexoe-op-request-error"></div>';
    $html .= '<div class="wexoe-op-request-fields">';
    $html .= '<div class="wexoe-op-request-field"><label>Namn *</label><input type="text" name="namn" required></div>';
    $html .= '<div class="wexoe-op-request-field"><label>Företag *</label><input type="text" name="foretag" required></div>';
    $html .= '<div class="wexoe-op-request-field"><label>Telefon *</label><input type="tel" name="telefon" required></div>';
    $html .= '<div class="wexoe-op-request-field"><label>E-post *</label><input type="email" name="epost" required></div>';
    $html .= '</div>';

    // Articles table
    $html .= '<div class="wexoe-op-request-articles" data-all-articles="'.esc_attr(json_encode($all_articles_flat, JSON_UNESCAPED_UNICODE)).'">';
    $html .= '<div class="wexoe-op-req-table-head">';
    $html .= '<span class="wexoe-op-col-name">Artikel</span>';
    $html .= '<span class="wexoe-op-col-artnr">Art.</span>';
    $html .= '<span class="wexoe-op-col-variant">Variant</span>';
    $html .= '<span class="wexoe-op-col-price wexoe-op-price-col">Pris</span>';
    $html .= '<span class="wexoe-op-col-qty">Antal</span>';
    $html .= '<span class="wexoe-op-col-sum wexoe-op-price-col" style="text-align:center!important;">Summa</span>';
    $html .= '<span class="wexoe-op-col-del"></span>';
    $html .= '</div>';
    $html .= '<div class="wexoe-op-req-table-body">';
    $html .= '<div class="wexoe-op-req-empty">Inga artiklar tillagda ännu.</div>';
    $html .= '</div>';
    $html .= '<div class="wexoe-op-req-footer">';
    $html .= '<div class="wexoe-op-add-wrap">';
    $html .= '<button type="button" class="wexoe-op-add-btn">+ Lägg till artikel</button>';
    $html .= '<div class="wexoe-op-search-dropdown">';
    $html .= '<input type="text" class="wexoe-op-search-input" placeholder="Sök artikel...">';
    $html .= '<div class="wexoe-op-search-results"></div>';
    $html .= '</div></div>';
    $html .= '<div class="wexoe-op-req-total wexoe-op-price-col">';
    $html .= '<span class="wexoe-op-total-label">Totalt:</span>';
    $html .= '<span class="wexoe-op-total-value">0 kr</span>';
    $html .= '</div></div></div>';

    // Message
    $html .= '<div class="wexoe-op-msg-row"><label>Meddelande</label>';
    $html .= '<textarea name="meddelande" rows="3"></textarea></div>';

    // Bottom
    $html .= '<div class="wexoe-op-bottom">';
    $html .= '<label class="wexoe-op-gdpr"><input type="checkbox" name="gdpr_consent" value="1"><span>Ja, jag vill ta emot nyheter, tips och erbjudanden från Wexoe Industry via e-post.</span></label>';
    $html .= '<button type="submit" class="wexoe-op-submit"><span class="wexoe-op-submit-text">Skicka prisförfrågan</span> <span>&rarr;</span></button>';
    $html .= '</div></form>';

    // Success
    $html .= '<div class="wexoe-op-success">';
    $html .= '<div class="wexoe-op-success-icon">&#10003;</div>';
    $html .= '<h3>Tack för din förfrågan!</h3>';
    $html .= '<p>Vi återkommer så snart som möjligt.</p>';
    $html .= '</div>';

    $html .= '</div></section>';
    $html .= '</div>'; // wrapper

    // JavaScript
    $html .= wexoe_op_render_js($id);

    return $html;
}

add_shortcode('wexoe_order', 'wexoe_order_page_shortcode');

/* ============================================================
   AJAX HANDLERS (reuse from product-area if available)
   ============================================================ */

if (!function_exists('wexoe_pa_handle_request_submit')) {
    function wexoe_pa_handle_request_submit() {
        if (!wp_verify_nonce($_POST['nonce'], 'wexoe_pa_request_nonce')) {
            wp_send_json_error('Säkerhetsfel.');
            return;
        }
        $artiklar_raw = stripslashes($_POST['artiklar'] ?? '[]');
        $artiklar = json_decode($artiklar_raw, true);
        if (!is_array($artiklar)) $artiklar = [];
        $cid = sanitize_text_field($_POST['customer_id'] ?? '');
        $data = [
            'namn' => sanitize_text_field($_POST['namn'] ?? ''),
            'foretag' => sanitize_text_field($_POST['foretag'] ?? ''),
            'telefon' => sanitize_text_field($_POST['telefon'] ?? ''),
            'epost' => sanitize_email($_POST['epost'] ?? ''),
            'behov' => !empty($cid) ? 'orderforfragan' : 'prisforfragan',
            'meddelande' => sanitize_textarea_field($_POST['meddelande'] ?? ''),
            'gdpr_consent' => isset($_POST['gdpr_consent']) ? true : false,
            'customer_id' => $cid,
            'artiklar' => $artiklar,
            'submitted_at' => current_time('mysql'),
            'page_url' => esc_url($_POST['page_url'] ?? ''),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
        ];
        if (empty($data['namn']) || empty($data['epost'])) {
            wp_send_json_error('Fyll i alla obligatoriska fält.');
            return;
        }
        if (empty($cid) && (empty($data['foretag']) || empty($data['telefon']))) {
            wp_send_json_error('Fyll i alla obligatoriska fält.');
            return;
        }
        if (!is_email($data['epost'])) {
            wp_send_json_error('Ange en giltig e-postadress.');
            return;
        }
        if (empty($artiklar) && empty($data['meddelande'])) {
            wp_send_json_error('Lägg till minst en artikel eller skriv ett meddelande.');
            return;
        }
        $response = wp_remote_post('https://hook.eu1.make.com/sulae2u3lux9g9dqfabtsdngiwz46s6g', [
            'body' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 15,
        ]);
        if (is_wp_error($response)) {
            wp_send_json_error('Något gick fel.');
            return;
        }
        wp_send_json_success(['message' => 'Tack!']);
    }
    add_action('wp_ajax_wexoe_pa_request_submit', 'wexoe_pa_handle_request_submit');
    add_action('wp_ajax_nopriv_wexoe_pa_request_submit', 'wexoe_pa_handle_request_submit');
}

if (!function_exists('wexoe_pa_customer_lookup')) {
    function wexoe_pa_customer_lookup() {
        $cid = sanitize_text_field($_POST['customer_id'] ?? '');
        if (empty($cid)) { wp_send_json_error('Ange ett kund-ID.'); return; }
        $result = wexoe_pa_airtable_request(WEXOE_PA_TABLE_CUSTOMERS, [
            'filterByFormula' => '{Customer ID}="' . addslashes($cid) . '"',
            'maxRecords' => 1,
        ]);
        if (isset($result['error']) || empty($result['records'])) {
            wp_send_json_error('Inget kundkonto hittades.');
            return;
        }
        $record = $result['records'][0]['fields'];
        wp_send_json_success([
            'name' => $record['Name'] ?? '',
            'prices' => wexoe_pa_parse_prices($record['Prices'] ?? ''),
        ]);
    }
    add_action('wp_ajax_wexoe_pa_customer_lookup', 'wexoe_pa_customer_lookup');
    add_action('wp_ajax_nopriv_wexoe_pa_customer_lookup', 'wexoe_pa_customer_lookup');
}

/* ============================================================
   CSS
   ============================================================ */

function wexoe_op_render_css($id, $is_dark) {
    $bg = $is_dark ? '#11325D' : '#F5F6F8';
    $card_bg = $is_dark ? 'rgba(255,255,255,0.06)' : '#FFFFFF';
    $card_border = $is_dark ? 'rgba(255,255,255,0.08)' : '#D1D5DB';
    $card_hover = $is_dark ? 'rgba(255,255,255,0.1)' : '#F9FAFB';
    $text = $is_dark ? '#FFFFFF' : '#11325D';
    $text_sec = $is_dark ? 'rgba(255,255,255,0.7)' : '#555';
    $link_color = $is_dark ? '#64B5F6' : '#11325D';
    $nav_bg = $is_dark ? 'transparent' : '#F8F9FA';
    $nav_text = $is_dark ? 'rgba(255,255,255,0.85)' : '#374151';
    $nav_active = '#F28C28';
    $nav_header_text = $is_dark ? '#FFFFFF' : '#11325D';
    $nav_hover_bg = $is_dark ? 'rgba(255,255,255,0.04)' : 'rgba(0,0,0,0.03)';
    $nav_box_bg = $is_dark ? 'rgba(0,0,0,0.2)' : '#FFFFFF';
    $nav_box_border = $is_dark ? 'rgba(255,255,255,0.06)' : '#E5E7EB';
    $img_bg = '#FFFFFF';
    $select_bg = $is_dark ? 'rgba(255,255,255,0.08)' : '#EDEEF1';
    $select_border = $is_dark ? 'rgba(255,255,255,0.15)' : '#D1D5DB';
    $select_color = $is_dark ? '#FFFFFF' : '#374151';
    $select_arrow = $is_dark ? 'white' : '%23666';
    $nr_label_color = $is_dark ? 'rgba(255,255,255,0.5)' : '#999';
    $check_color = '#10B981';
    $desc_color = $is_dark ? 'rgba(255,255,255,0.6)' : '#777';
    $sep_color = $is_dark ? 'rgba(255,255,255,0.1)' : '#D1D5DB';
    $mob_sel_bg = $is_dark ? 'rgba(255,255,255,0.08)' : '#F3F4F6';
    $mob_sel_border = $is_dark ? 'rgba(255,255,255,0.2)' : '#D1D5DB';
    $mob_arrow = $is_dark ? 'white' : '%23333';

    // Request form: dark mode = light form (#F8F9FA), light mode = dark form (#11325D)
    $form_bg = $is_dark ? '#F8F9FA' : '#11325D';
    $form_text = $is_dark ? '#11325D' : '#FFFFFF';
    $form_text_sec = $is_dark ? '#666' : 'rgba(255,255,255,0.75)';
    $form_label = $is_dark ? '#11325D' : '#FFFFFF';
    $form_input_bg = '#FFFFFF';
    $form_input_border = $is_dark ? '#D1D5DB' : 'rgba(255,255,255,0.15)';
    $form_input_color = '#11325D';
    $form_table_bg = '#FFFFFF';
    $form_table_border = $is_dark ? '#E5E7EB' : 'rgba(255,255,255,0.12)';
    $form_table_head_bg = '#F3F4F6';
    $form_table_head_color = '#6B7280';
    $form_row_color = '#374151';
    $form_row_border = '#F3F4F6';
    $form_empty_color = '#9CA3AF';
    $form_footer_border = '#E5E7EB';
    $form_name_color = '#11325D';
    $form_artnr_color = '#555';
    $form_variant_color = '#777';
    $form_qty_bg = '#F9FAFB';
    $form_qty_border = '#D1D5DB';
    $form_qty_color = '#11325D';
    $form_del_color = '#9CA3AF';
    $form_add_color = $is_dark ? '#6B7280' : '#FFFFFF';
    $form_total_label = '#6B7280';
    $form_total_value = '#11325D';
    $form_err_bg = $is_dark ? '#FEE2E2' : 'rgba(254,226,226,0.15)';
    $form_err_color = $is_dark ? '#B91C1C' : '#FCA5A5';
    $form_gdpr_color = $is_dark ? '#666' : '#FFFFFF';
    $form_search_bg = '#FFFFFF';
    $form_search_border = '#E5E7EB';
    $form_search_item_color = '#374151';
    $form_search_hover = '#FFF7ED';
    $form_cust_trigger = $is_dark ? '#6B7280' : 'rgba(255,255,255,0.7)';
    $form_cust_trigger_hover = $is_dark ? '#11325D' : '#FFFFFF';
    $form_select_variant_bg = '#F9FAFB';
    $form_select_variant_border = '#D1D5DB';
    $form_select_variant_color = '#374151';

    return '
    <style>
    @import url("https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap");

    #'.$id.' { font-family:"DM Sans",system-ui,sans-serif!important; line-height:1.6!important; box-sizing:border-box!important; color:'.$text.'!important; }
    #'.$id.' *,#'.$id.' *::before,#'.$id.' *::after { box-sizing:border-box!important; }
    #'.$id.' li::before { content:none!important; display:none!important; }
    #'.$id.' img { max-width:100%!important; height:auto!important; }
    #'.$id.' a { text-decoration:none!important; }

    /* Wrapper */
    #'.$id.'.wexoe-op-wrapper { background:'.$bg.'!important; width:100vw!important; margin-left:calc(-50vw + 50%)!important; padding:0!important; }

    /* Layout */
    #'.$id.' .wexoe-op-layout { max-width:1270px!important; margin:0 auto!important; padding:50px 40px!important; display:flex!important; gap:40px!important; align-items:flex-start!important; }

    /* Sidebar */
    #'.$id.' .wexoe-op-sidebar { flex:0 0 220px!important; display:flex!important; flex-direction:column!important; background:'.$nav_box_bg.'!important; border:1px solid '.$nav_box_border.'!important; border-radius:10px!important; padding:16px 10px!important; align-self:flex-start!important; position:sticky!important; top:100px!important; }
    #'.$id.' .wexoe-op-nav-division { margin-bottom:8px!important; }
    #'.$id.' .wexoe-op-nav-div-header { all:unset!important; display:flex!important; align-items:center!important; justify-content:space-between!important; width:100%!important; padding:10px 14px!important; font-family:"DM Sans",system-ui,sans-serif!important; font-size:16px!important; font-weight:700!important; color:'.$nav_header_text.'!important; cursor:pointer!important; border-radius:6px!important; transition:background 0.15s ease!important; box-sizing:border-box!important; text-transform:uppercase!important; letter-spacing:0.02em!important; }
    #'.$id.' .wexoe-op-nav-div-header:hover { background:'.$nav_hover_bg.'!important; }
    #'.$id.' .wexoe-op-nav-div-body { display:none!important; padding-left:4px!important; }
    #'.$id.' .wexoe-op-nav-div-body.wexoe-op-nav-open { display:block!important; }
    #'.$id.' .wexoe-op-nav-division.wexoe-op-div-open > .wexoe-op-nav-div-header .wexoe-op-nav-chevron { transform:rotate(90deg)!important; }
    #'.$id.' .wexoe-op-nav-group { margin-bottom:2px!important; }
    #'.$id.' .wexoe-op-nav-header { all:unset!important; display:flex!important; align-items:center!important; justify-content:space-between!important; width:100%!important; padding:7px 14px!important; font-family:"DM Sans",system-ui,sans-serif!important; font-size:14px!important; font-weight:500!important; color:'.$nav_header_text.'!important; cursor:pointer!important; border-radius:6px!important; transition:background 0.15s ease!important; box-sizing:border-box!important; }
    #'.$id.' .wexoe-op-nav-header:hover { background:'.$nav_hover_bg.'!important; }
    #'.$id.' .wexoe-op-nav-chevron { font-size:18px!important; transition:transform 0.2s ease!important; opacity:0.5!important; }
    #'.$id.' .wexoe-op-nav-items.wexoe-op-nav-open + .wexoe-op-nav-header .wexoe-op-nav-chevron,
    #'.$id.' .wexoe-op-nav-items.wexoe-op-nav-open ~ .wexoe-op-nav-chevron { transform:rotate(90deg)!important; }
    #'.$id.' .wexoe-op-nav-items { display:none!important; padding-left:12px!important; }
    #'.$id.' .wexoe-op-nav-items.wexoe-op-nav-open { display:block!important; }
    #'.$id.' .wexoe-op-nav-item { all:unset!important; display:block!important; width:100%!important; padding:6px 14px!important; font-family:"DM Sans",system-ui,sans-serif!important; font-size:13px!important; font-weight:500!important; color:'.$nav_text.'!important; cursor:pointer!important; border-left:3px solid transparent!important; transition:color 0.15s,border-color 0.15s,background 0.15s!important; box-sizing:border-box!important; text-align:left!important; }
    #'.$id.' .wexoe-op-nav-item:hover { color:'.$text.'!important; background:'.$nav_hover_bg.'!important; }
    #'.$id.' .wexoe-op-nav-active { color:'.$nav_active.'!important; border-left-color:'.$nav_active.'!important; font-weight:600!important; }

    /* Chevron rotation on open group */
    #'.$id.' .wexoe-op-nav-group.wexoe-op-group-open > .wexoe-op-nav-header .wexoe-op-nav-chevron { transform:rotate(90deg)!important; }

    /* Content */
    #'.$id.' .wexoe-op-content { flex:1!important; min-width:0!important; }
    #'.$id.' .wexoe-op-panel-h2 { font-size:26px!important; font-weight:700!important; color:'.$text.'!important; margin:0 0 14px 0!important; line-height:1.3!important; }
    #'.$id.' .wexoe-op-panel-desc { font-size:15px!important; line-height:1.7!important; color:'.$text.'!important; margin:0 0 14px 0!important; }
    #'.$id.' .wexoe-op-panel-desc a { color:'.$link_color.'!important; text-decoration:underline!important; }
    #'.$id.' .wexoe-op-panel-checks { list-style:none!important; padding:0!important; margin:0 0 18px 0!important; }
    #'.$id.' .wexoe-op-panel-checks li { font-size:14px!important; line-height:1.65!important; color:'.$text.'!important; padding:4px 0!important; margin:0!important; display:flex!important; align-items:flex-start!important; gap:10px!important; background-image:none!important; padding-left:0!important; }
    #'.$id.' .wexoe-op-check { color:'.$check_color.'!important; font-weight:700!important; font-size:15px!important; flex-shrink:0!important; margin-top:1px!important; }

    /* Product content: image + buttons */
    #'.$id.' .wexoe-op-panel-row { display:flex!important; gap:24px!important; align-items:flex-start!important; margin-bottom:16px!important; }
    #'.$id.' .wexoe-op-panel-text-col { flex:1!important; }
    #'.$id.' .wexoe-op-panel-img { flex:0 0 220px!important; }
    #'.$id.' .wexoe-op-panel-img img { width:100%!important; height:auto!important; border-radius:8px!important; display:block!important; }
    #'.$id.' .wexoe-op-panel-btns { display:flex!important; gap:10px!important; flex-wrap:wrap!important; margin-top:4px!important; }
    #'.$id.' .wexoe-op-panel-btn-primary { display:inline-flex!important; align-items:center!important; gap:6px!important; padding:10px 22px!important; background:#F28C28!important; color:#FFFFFF!important; font-family:"DM Sans",system-ui,sans-serif!important; font-size:14px!important; font-weight:600!important; border-radius:5px!important; text-decoration:none!important; transition:opacity 0.2s!important; }
    #'.$id.' .wexoe-op-panel-btn-primary:hover { opacity:0.9!important; color:#FFFFFF!important; }
    #'.$id.' .wexoe-op-panel-btn-secondary { display:inline-flex!important; align-items:center!important; gap:6px!important; padding:10px 22px!important; background:transparent!important; color:'.$text.'!important; font-family:"DM Sans",system-ui,sans-serif!important; font-size:14px!important; font-weight:600!important; border-radius:5px!important; border:1px solid '.$sep_color.'!important; text-decoration:none!important; transition:opacity 0.2s!important; }
    #'.$id.' .wexoe-op-panel-btn-secondary:hover { opacity:0.8!important; }
    /* Product inquiry button */
    #'.$id.' .wexoe-op-btn-product-inquiry.wexoe-op-btn-added { background:#6B7280!important; pointer-events:none!important; opacity:0.7!important; }

    /* Article cards — vertical */
    #'.$id.' .wexoe-op-articles { margin-top:20px!important; border-top:1px solid '.$sep_color.'!important; padding-top:24px!important; }
    #'.$id.' .wexoe-op-articles-grid { display:grid!important; grid-template-columns:repeat(auto-fill,minmax(200px,1fr))!important; gap:16px!important; }
    #'.$id.' .wexoe-op-article-card { background:'.$card_bg.'!important; border-radius:8px!important; overflow:hidden!important; border:1px solid '.$card_border.'!important; display:flex!important; flex-direction:column!important; transition:background 0.2s,transform 0.2s!important; position:relative!important; }
    #'.$id.' .wexoe-op-article-card:hover { background:'.$card_hover.'!important; transform:translateY(-2px)!important; }

    /* Supplier badge */
    #'.$id.' .wexoe-op-supplier-badge { position:absolute!important; top:8px!important; right:8px!important; z-index:2!important; width:80px!important; height:36px!important; background:none!important; display:flex!important; align-items:center!important; justify-content:center!important; padding:0!important; pointer-events:none!important; }
    #'.$id.' .wexoe-op-supplier-badge img { max-width:100%!important; max-height:100%!important; object-fit:contain!important; display:block!important; }
    #'.$id.' .wexoe-op-supplier-svg { width:100%!important; height:100%!important; display:flex!important; align-items:center!important; justify-content:center!important; }
    #'.$id.' .wexoe-op-supplier-svg svg { width:100%!important; height:100%!important; display:block!important; }
    #'.$id.' .wexoe-op-article-img { aspect-ratio:4/3!important; overflow:hidden!important; background:'.$img_bg.'!important; display:flex!important; align-items:center!important; justify-content:center!important; }
    #'.$id.' .wexoe-op-article-img img { width:100%!important; height:100%!important; object-fit:contain!important; padding:0!important; border-radius:0!important; display:block!important; }
    #'.$id.' .wexoe-op-article-placeholder { min-height:100px!important; background:'.$img_bg.'!important; }
    #'.$id.' .wexoe-op-article-supplier-main { display:flex!important; align-items:center!important; justify-content:center!important; padding:20px!important; }
    #'.$id.' .wexoe-op-article-supplier-main img { max-width:70%!important; max-height:70%!important; object-fit:contain!important; display:block!important; width:auto!important; height:auto!important; }
    #'.$id.' .wexoe-op-supplier-main-svg { width:60%!important; display:flex!important; align-items:center!important; justify-content:center!important; }
    #'.$id.' .wexoe-op-supplier-main-svg svg { width:100%!important; height:auto!important; display:block!important; }
    #'.$id.' .wexoe-op-article-info { padding:14px 16px 16px!important; display:flex!important; flex-direction:column!important; flex:1!important; }
    #'.$id.' .wexoe-op-article-name { font-size:15px!important; font-weight:600!important; color:'.$text.'!important; margin-bottom:8px!important; line-height:1.3!important; min-height:2.6em!important; display:-webkit-box!important; -webkit-line-clamp:2!important; -webkit-box-orient:vertical!important; overflow:hidden!important; }
    #'.$id.' .wexoe-op-article-mid { flex:1!important; display:flex!important; flex-direction:column!important; }
    #'.$id.' .wexoe-op-article-nr { margin-top:auto!important; padding-top:10px!important; font-size:13px!important; color:'.$text.'!important; display:flex!important; align-items:baseline!important; gap:6px!important; }
    #'.$id.' .wexoe-op-variant-artnr { margin-top:auto!important; padding-top:10px!important; font-size:13px!important; color:'.$text.'!important; display:flex!important; align-items:baseline!important; gap:6px!important; }
    #'.$id.' .wexoe-op-nr-label { font-size:11px!important; color:'.$nr_label_color.'!important; font-weight:400!important; flex-shrink:0!important; }
    #'.$id.' .wexoe-op-nr-value { font-weight:600!important; }
    #'.$id.' .wexoe-op-article-desc { font-size:11px!important; line-height:1.5!important; color:'.$desc_color.'!important; margin:0 0 8px 0!important; display:-webkit-box!important; -webkit-line-clamp:3!important; -webkit-box-orient:vertical!important; overflow:hidden!important; }
    #'.$id.' .wexoe-op-variant-wrap { margin-bottom:8px!important; }
    #'.$id.' .wexoe-op-variant-row { margin-bottom:6px!important; }
    #'.$id.' .wexoe-op-variant-select { all:unset!important; display:block!important; width:100%!important; padding:7px 28px 7px 12px!important; font-family:"DM Sans",system-ui,sans-serif!important; font-size:13px!important; font-weight:500!important; color:'.$select_color.'!important; background-color:'.$select_bg.'!important; border:1px solid '.$select_border.'!important; border-radius:5px!important; cursor:pointer!important; box-sizing:border-box!important; -webkit-appearance:none!important; -moz-appearance:none!important; appearance:none!important; background-image:url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'10\' height=\'6\' fill=\'none\'%3E%3Cpath d=\'M1 1l4 4 4-4\' stroke=\''.$select_arrow.'\' stroke-width=\'1.5\' stroke-linecap=\'round\' stroke-linejoin=\'round\'/%3E%3C/svg%3E")!important; background-repeat:no-repeat!important; background-position:right 10px center!important; background-size:10px 6px!important; line-height:1.4!important; }
    #'.$id.' .wexoe-op-variant-select option { color:#11325D!important; background:#FFFFFF!important; }
    #'.$id.' .wexoe-op-variant-artnr.wexoe-op-nomatch { color:rgba(255,255,255,0.4)!important; font-style:italic!important; font-weight:400!important; }

    /* Buttons */
    #'.$id.' .wexoe-op-article-btns { display:flex!important; flex-direction:column!important; gap:6px!important; margin-top:8px!important; }
    #'.$id.' .wexoe-op-btn-order { display:flex!important; align-items:center!important; justify-content:center!important; min-width:160px!important; padding:9px 16px!important; background:#F28C28!important; color:#FFFFFF!important; font-family:"DM Sans",system-ui,sans-serif!important; font-size:12px!important; font-weight:600!important; border-radius:5px!important; border:none!important; text-decoration:none!important; cursor:pointer!important; transition:opacity 0.2s,transform 0.15s,background 0.2s!important; }
    #'.$id.' .wexoe-op-btn-order:hover { opacity:0.9!important; transform:translateY(-1px)!important; color:#FFFFFF!important; }
    #'.$id.' .wexoe-op-btn-short { display:none!important; }
    #'.$id.' .wexoe-op-btn-full { display:inline!important; }
    #'.$id.' .wexoe-op-btn-order.wexoe-op-btn-added { background:#6B7280!important; pointer-events:none!important; opacity:0.7!important; }
    #'.$id.' .wexoe-op-btn-ds { display:flex!important; align-items:center!important; justify-content:center!important; padding:9px 16px!important; background:#FFFFFF!important; color:#11325D!important; font-family:"DM Sans",system-ui,sans-serif!important; font-size:12px!important; font-weight:600!important; border-radius:5px!important; border:1px solid '.$card_border.'!important; text-decoration:none!important; cursor:pointer!important; transition:opacity 0.2s!important; }
    #'.$id.' .wexoe-op-btn-ds:hover { opacity:0.85!important; color:#11325D!important; }

    /* Horizontal cards */
    #'.$id.' .wexoe-op-articles-horiz { grid-template-columns:1fr!important; gap:10px!important; }
    #'.$id.' .wexoe-op-article-horiz { flex-direction:row!important; min-height:90px!important; }
    #'.$id.' .wexoe-op-article-horiz .wexoe-op-article-img { aspect-ratio:auto!important; flex:0 0 110px!important; max-width:110px!important; min-height:90px!important; align-self:stretch!important; }
    #'.$id.' .wexoe-op-article-horiz .wexoe-op-article-info { flex-direction:row!important; align-items:center!important; gap:12px!important; padding:14px 16px!important; flex-wrap:nowrap!important; }
    #'.$id.' .wexoe-op-article-horiz .wexoe-op-article-name { min-height:0!important; -webkit-line-clamp:2!important; overflow:hidden!important; flex:0 0 130px!important; margin-bottom:0!important; font-size:13px!important; }
    #'.$id.' .wexoe-op-article-horiz .wexoe-op-article-mid { flex:1!important; flex-direction:row!important; align-items:center!important; gap:12px!important; min-width:0!important; }
    #'.$id.' .wexoe-op-article-horiz .wexoe-op-variant-wrap { margin-bottom:0!important; display:flex!important; gap:5px!important; flex-wrap:nowrap!important; flex-shrink:1!important; min-width:0!important; }
    #'.$id.' .wexoe-op-article-horiz .wexoe-op-variant-row { margin-bottom:0!important; flex-shrink:1!important; min-width:0!important; }
    #'.$id.' .wexoe-op-article-horiz .wexoe-op-variant-select { font-size:11px!important; padding:5px 22px 5px 8px!important; min-width:0!important; max-width:130px!important; overflow:hidden!important; text-overflow:ellipsis!important; }
    #'.$id.' .wexoe-op-article-horiz .wexoe-op-article-nr,
    #'.$id.' .wexoe-op-article-horiz .wexoe-op-variant-artnr { margin-top:0!important; padding-top:0!important; flex-shrink:0!important; white-space:nowrap!important; margin-left:auto!important; font-size:12px!important; }
    #'.$id.' .wexoe-op-article-horiz .wexoe-op-article-btns { flex-direction:row!important; gap:5px!important; margin-top:0!important; flex-shrink:0!important; }
    #'.$id.' .wexoe-op-article-horiz .wexoe-op-btn-order,
    #'.$id.' .wexoe-op-article-horiz .wexoe-op-btn-ds { font-size:11px!important; padding:7px 12px!important; white-space:nowrap!important; min-width:auto!important; }

    /* === REQUEST FORM === */
    #'.$id.' .wexoe-op-request { background:'.$form_bg.'!important; width:100vw!important; margin-left:calc(-50vw + 50%)!important; }
    #'.$id.' .wexoe-op-request-inner { max-width:1000px!important; margin:0 auto!important; padding:60px 40px!important; }
    #'.$id.' .wexoe-op-request-header-row { display:flex!important; align-items:flex-start!important; justify-content:space-between!important; gap:24px!important; margin-bottom:24px!important; }
    #'.$id.' .wexoe-op-request-h2 { font-size:26px!important; font-weight:700!important; color:'.$form_text.'!important; margin:0 0 6px 0!important; }
    #'.$id.' .wexoe-op-request-subtitle { font-size:14px!important; color:'.$form_text_sec.'!important; margin:0!important; }
    #'.$id.' .wexoe-op-request-error { display:none!important; background:'.$form_err_bg.'!important; color:'.$form_err_color.'!important; padding:12px 16px!important; border-radius:6px!important; font-size:14px!important; margin-bottom:16px!important; }
    #'.$id.' .wexoe-op-request-error.wexoe-op-show { display:block!important; }
    #'.$id.' .wexoe-op-request-fields { display:grid!important; grid-template-columns:1fr 1fr 1fr 1fr!important; gap:12px!important; margin-bottom:24px!important; }
    #'.$id.' .wexoe-op-request-field label { display:block!important; font-size:12px!important; font-weight:600!important; color:'.$form_label.'!important; margin-bottom:4px!important; }
    #'.$id.' .wexoe-op-request-field input { all:unset!important; display:block!important; width:100%!important; padding:10px 12px!important; font-family:"DM Sans",system-ui,sans-serif!important; font-size:14px!important; color:'.$form_input_color.'!important; background:'.$form_input_bg.'!important; border:1px solid '.$form_input_border.'!important; border-radius:6px!important; box-sizing:border-box!important; }
    #'.$id.' .wexoe-op-request-field input:focus { border-color:#F28C28!important; }

    /* Customer toggle */
    #'.$id.' .wexoe-op-customer-toggle { flex-shrink:0!important; display:flex!important; align-items:center!important; }
    #'.$id.' .wexoe-op-customer-trigger { all:unset!important; display:inline-flex!important; align-items:center!important; gap:4px!important; font-family:"DM Sans",system-ui,sans-serif!important; font-size:13px!important; font-weight:500!important; color:'.$form_cust_trigger.'!important; cursor:pointer!important; box-sizing:border-box!important; }
    #'.$id.' .wexoe-op-customer-trigger:hover { color:'.$form_cust_trigger_hover.'!important; }
    #'.$id.' .wexoe-op-customer-trigger.wexoe-op-open { display:none!important; }
    #'.$id.' .wexoe-op-customer-panel { display:none!important; flex-direction:column!important; align-items:flex-end!important; gap:4px!important; }
    #'.$id.' .wexoe-op-customer-panel.wexoe-op-show { display:flex!important; }
    #'.$id.' .wexoe-op-customer-input-wrap { display:flex!important; gap:6px!important; }
    #'.$id.' .wexoe-op-customer-input { all:unset!important; display:block!important; width:160px!important; padding:8px 12px!important; font-family:"DM Sans",system-ui,sans-serif!important; font-size:13px!important; color:'.$form_input_color.'!important; background:'.$form_input_bg.'!important; border:1px solid '.$form_input_border.'!important; border-radius:5px!important; box-sizing:border-box!important; }
    #'.$id.' .wexoe-op-customer-input:focus { border-color:'.$form_cust_trigger_hover.'!important; }
    #'.$id.' .wexoe-op-customer-btn { all:unset!important; display:inline-flex!important; align-items:center!important; padding:8px 16px!important; font-family:"DM Sans",system-ui,sans-serif!important; font-size:12px!important; font-weight:600!important; color:'.$form_input_color.'!important; background:'.$form_input_bg.'!important; border:1px solid '.$form_input_border.'!important; border-radius:5px!important; cursor:pointer!important; box-sizing:border-box!important; white-space:nowrap!important; }
    #'.$id.' .wexoe-op-customer-btn:hover { border-color:'.$form_cust_trigger_hover.'!important; }
    #'.$id.' .wexoe-op-customer-status { font-size:12px!important; min-height:0!important; }
    #'.$id.' .wexoe-op-customer-status:empty { display:none!important; }
    #'.$id.' .wexoe-op-customer-status.wexoe-op-status-ok { color:#10B981!important; font-weight:500!important; }
    #'.$id.' .wexoe-op-customer-status.wexoe-op-status-err { color:#EF4444!important; }
    #'.$id.' .wexoe-op-customer-status.wexoe-op-status-loading { color:#999!important; font-style:italic!important; }

    /* Articles table */
    #'.$id.' .wexoe-op-request-articles { background:'.$form_table_bg.'!important; border:1px solid '.$form_table_border.'!important; border-radius:8px!important; overflow:hidden!important; margin-bottom:20px!important; }
    #'.$id.' .wexoe-op-price-col { display:none!important; }
    #'.$id.' .wexoe-op-request-articles.wexoe-op-has-prices .wexoe-op-price-col { display:block!important; }
    #'.$id.' .wexoe-op-request-articles.wexoe-op-has-prices .wexoe-op-req-total { display:flex!important; }
    #'.$id.' .wexoe-op-req-table-head { display:grid!important; grid-template-columns:1.4fr 1.2fr 2.5fr 42px 20px!important; gap:6px!important; padding:10px 12px!important; background:'.$form_table_head_bg.'!important; border-bottom:1px solid '.$form_table_border.'!important; font-size:11px!important; font-weight:600!important; color:'.$form_table_head_color.'!important; text-transform:uppercase!important; letter-spacing:0.04em!important; }
    #'.$id.' .wexoe-op-request-articles.wexoe-op-has-prices .wexoe-op-req-table-head { grid-template-columns:1.4fr 0.9fr 2.2fr 60px 42px 90px 20px!important; }
    #'.$id.' .wexoe-op-req-table-body { min-height:48px!important; }
    #'.$id.' .wexoe-op-req-empty { padding:20px 12px!important; font-size:13px!important; color:'.$form_empty_color.'!important; text-align:center!important; font-style:italic!important; }
    #'.$id.' .wexoe-op-req-row { display:grid!important; grid-template-columns:1.4fr 1.2fr 2.5fr 42px 20px!important; gap:6px!important; padding:10px 12px!important; border-bottom:1px solid '.$form_row_border.'!important; align-items:center!important; font-size:13px!important; color:'.$form_row_color.'!important; animation:wexoe-op-row-in 0.25s ease!important; }
    #'.$id.' .wexoe-op-request-articles.wexoe-op-has-prices .wexoe-op-req-row { grid-template-columns:1.4fr 0.9fr 2.2fr 60px 42px 90px 20px!important; }
    @keyframes wexoe-op-row-in { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:translateY(0)} }
    #'.$id.' .wexoe-op-row-name { font-size:12px!important; font-weight:600!important; color:'.$form_name_color.'!important; white-space:nowrap!important; overflow:hidden!important; text-overflow:ellipsis!important; }
    #'.$id.' .wexoe-op-row-artnr { font-family:monospace!important; font-size:12px!important; color:'.$form_artnr_color.'!important; }
    #'.$id.' .wexoe-op-row-variant { font-size:12px!important; color:'.$form_variant_color.'!important; display:flex!important; gap:4px!important; align-items:center!important; flex-wrap:nowrap!important; min-width:0!important; }
    #'.$id.' .wexoe-op-row-variant select { all:unset!important; display:inline-block!important; padding:3px 20px 3px 6px!important; font-family:"DM Sans",system-ui,sans-serif!important; font-size:11px!important; color:'.$form_select_variant_color.'!important; background:'.$form_select_variant_bg.'!important; border:1px solid '.$form_select_variant_border.'!important; border-radius:3px!important; cursor:pointer!important; box-sizing:border-box!important; -webkit-appearance:none!important; -moz-appearance:none!important; appearance:none!important; background-image:url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'8\' height=\'5\' fill=\'none\'%3E%3Cpath d=\'M1 1l3 3 3-3\' stroke=\'%23999\' stroke-width=\'1.2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'/%3E%3C/svg%3E")!important; background-repeat:no-repeat!important; background-position:right 5px center!important; background-size:8px 5px!important; line-height:1.4!important; min-width:0!important; max-width:120px!important; overflow:hidden!important; text-overflow:ellipsis!important; flex-shrink:1!important; }
    #'.$id.' .wexoe-op-row-qty input { all:unset!important; display:block!important; width:100%!important; padding:5px 4px!important; font-family:"DM Sans",system-ui,sans-serif!important; font-size:13px!important; color:'.$form_qty_color.'!important; background:'.$form_qty_bg.'!important; border:1px solid '.$form_qty_border.'!important; border-radius:4px!important; box-sizing:border-box!important; text-align:center!important; -moz-appearance:textfield!important; }
    #'.$id.' .wexoe-op-row-qty input::-webkit-outer-spin-button,
    #'.$id.' .wexoe-op-row-qty input::-webkit-inner-spin-button { -webkit-appearance:none!important; margin:0!important; }
    #'.$id.' .wexoe-op-row-price { font-size:13px!important; color:'.$form_row_color.'!important; font-weight:500!important; white-space:nowrap!important; }
    #'.$id.' .wexoe-op-row-price.wexoe-op-no-price { color:'.$form_empty_color.'!important; font-style:italic!important; font-weight:400!important; font-size:11px!important; }
    #'.$id.' .wexoe-op-row-sum { font-size:13px!important; color:'.$form_name_color.'!important; font-weight:600!important; padding-left:6px!important; white-space:nowrap!important; text-align:center!important; }
    #'.$id.' .wexoe-op-row-del { all:unset!important; display:flex!important; align-items:center!important; justify-content:center!important; width:20px!important; height:20px!important; cursor:pointer!important; color:'.$form_del_color.'!important; border-radius:3px!important; transition:color 0.15s,background 0.15s!important; font-size:16px!important; line-height:1!important; }
    #'.$id.' .wexoe-op-row-del:hover { color:#EF4444!important; background:#FEE2E2!important; }

    /* Footer row */
    #'.$id.' .wexoe-op-req-footer { display:flex!important; align-items:flex-start!important; justify-content:space-between!important; padding:10px 12px!important; border-top:1px solid '.$form_footer_border.'!important; }
    #'.$id.' .wexoe-op-req-total { display:none!important; align-items:center!important; gap:10px!important; }
    #'.$id.' .wexoe-op-total-label { font-size:14px!important; font-weight:600!important; color:'.$form_total_label.'!important; }
    #'.$id.' .wexoe-op-total-value { font-size:18px!important; font-weight:700!important; color:'.$form_total_value.'!important; }
    #'.$id.' .wexoe-op-add-wrap { position:relative!important; }
    #'.$id.' .wexoe-op-add-btn { all:unset!important; display:inline-flex!important; align-items:center!important; gap:4px!important; font-family:"DM Sans",system-ui,sans-serif!important; font-size:13px!important; font-weight:600!important; color:'.$form_add_color.'!important; cursor:pointer!important; padding:4px 0!important; box-sizing:border-box!important; }
    #'.$id.' .wexoe-op-add-btn:hover { opacity:0.8!important; }
    #'.$id.' .wexoe-op-search-dropdown { display:none!important; margin-top:8px!important; }
    #'.$id.' .wexoe-op-search-dropdown.wexoe-op-show { display:block!important; }
    #'.$id.' .wexoe-op-search-input { all:unset!important; display:block!important; width:100%!important; padding:10px 12px!important; font-family:"DM Sans",system-ui,sans-serif!important; font-size:14px!important; color:'.$form_input_color.'!important; background:'.$form_input_bg.'!important; border:1px solid '.$form_input_border.'!important; border-radius:6px!important; box-sizing:border-box!important; margin-bottom:6px!important; }
    #'.$id.' .wexoe-op-search-results { max-height:200px!important; overflow-y:auto!important; border:1px solid '.$form_search_border.'!important; border-radius:6px!important; background:'.$form_search_bg.'!important; }
    #'.$id.' .wexoe-op-search-item { padding:10px 14px!important; font-size:13px!important; color:'.$form_search_item_color.'!important; cursor:pointer!important; border-bottom:1px solid '.$form_row_border.'!important; }
    #'.$id.' .wexoe-op-search-item:hover { background:'.$form_search_hover.'!important; }
    #'.$id.' .wexoe-op-search-item-nr { font-size:11px!important; color:'.$form_empty_color.'!important; margin-left:6px!important; }
    #'.$id.' .wexoe-op-search-none { padding:12px 14px!important; font-size:13px!important; color:'.$form_empty_color.'!important; font-style:italic!important; }

    /* Message + Bottom */
    #'.$id.' .wexoe-op-msg-row { margin-bottom:20px!important; }
    #'.$id.' .wexoe-op-msg-row label { display:block!important; font-size:12px!important; font-weight:600!important; color:'.$form_label.'!important; margin-bottom:4px!important; }
    #'.$id.' .wexoe-op-msg-row textarea { all:unset!important; display:block!important; width:100%!important; padding:10px 12px!important; font-family:"DM Sans",system-ui,sans-serif!important; font-size:14px!important; color:'.$form_input_color.'!important; background:'.$form_input_bg.'!important; border:1px solid '.$form_input_border.'!important; border-radius:6px!important; box-sizing:border-box!important; resize:vertical!important; min-height:70px!important; white-space:pre-wrap!important; overflow-wrap:break-word!important; }
    #'.$id.' .wexoe-op-bottom { display:flex!important; align-items:center!important; justify-content:flex-end!important; gap:20px!important; }
    #'.$id.' .wexoe-op-gdpr { display:flex!important; flex-direction:row-reverse!important; align-items:center!important; gap:8px!important; font-size:12px!important; color:'.$form_gdpr_color.'!important; cursor:pointer!important; }
    #'.$id.' .wexoe-op-gdpr span, #top #'.$id.' .wexoe-op-gdpr span { color:'.$form_gdpr_color.'!important; }
    #'.$id.' .wexoe-op-gdpr input { width:16px!important; height:16px!important; cursor:pointer!important; }
    #'.$id.' .wexoe-op-submit { all:unset!important; display:inline-flex!important; align-items:center!important; gap:8px!important; padding:14px 32px!important; background:#F28C28!important; color:#FFFFFF!important; font-family:"DM Sans",system-ui,sans-serif!important; font-size:15px!important; font-weight:600!important; border-radius:6px!important; cursor:pointer!important; transition:opacity 0.2s,transform 0.15s!important; box-sizing:border-box!important; flex-shrink:0!important; }
    #'.$id.' .wexoe-op-submit:hover { opacity:0.9!important; transform:translateY(-1px)!important; }
    #'.$id.' .wexoe-op-submit.wexoe-op-loading { opacity:0.6!important; pointer-events:none!important; }
    #'.$id.' .wexoe-op-success { display:none!important; text-align:center!important; padding:40px 20px!important; }
    #'.$id.' .wexoe-op-success.wexoe-op-show { display:block!important; }
    #'.$id.' .wexoe-op-success-icon { width:56px!important; height:56px!important; background:#10B981!important; color:#FFFFFF!important; border-radius:50%!important; display:flex!important; align-items:center!important; justify-content:center!important; font-size:28px!important; margin:0 auto 16px!important; }
    #'.$id.' .wexoe-op-success h3 { font-size:22px!important; font-weight:700!important; color:'.$form_text.'!important; margin-bottom:8px!important; }
    #'.$id.' .wexoe-op-success p { font-size:15px!important; color:'.$form_text_sec.'!important; }

    #'.$id.' .wexoe-op-mobile-nav { display:none!important; }

    /* Mobile */
    @media(max-width:767px) {
        #'.$id.' .wexoe-op-layout { flex-direction:column!important; padding:30px 20px!important; gap:20px!important; }
        #'.$id.' .wexoe-op-sidebar { display:none!important; }
        /* 3-level mobile navigation */
        #'.$id.' .wexoe-op-mobile-nav { display:flex!important; flex-direction:column!important; gap:8px!important; }
        #'.$id.' .wexoe-op-mobile-row { display:flex!important; gap:8px!important; }
        #'.$id.' .wexoe-op-mob-division,
        #'.$id.' .wexoe-op-mob-area { all:unset!important; display:block!important; flex:1!important; padding:10px 32px 10px 12px!important; font-family:"DM Sans",system-ui,sans-serif!important; font-size:13px!important; font-weight:600!important; color:'.$select_color.'!important; background-color:'.$mob_sel_bg.'!important; border:1px solid '.$mob_sel_border.'!important; border-radius:6px!important; cursor:pointer!important; box-sizing:border-box!important; -webkit-appearance:none!important; -moz-appearance:none!important; appearance:none!important; background-image:url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'10\' height=\'6\' fill=\'none\'%3E%3Cpath d=\'M1 1l4 4 4-4\' stroke=\''.$mob_arrow.'\' stroke-width=\'1.5\' stroke-linecap=\'round\' stroke-linejoin=\'round\'/%3E%3C/svg%3E")!important; background-repeat:no-repeat!important; background-position:right 10px center!important; background-size:10px 6px!important; }
        #'.$id.' .wexoe-op-mob-product { all:unset!important; display:block!important; width:100%!important; padding:14px 40px 14px 16px!important; font-family:"DM Sans",system-ui,sans-serif!important; font-size:15px!important; font-weight:600!important; color:'.$select_color.'!important; background-color:'.$mob_sel_bg.'!important; border:1px solid '.$mob_sel_border.'!important; border-radius:8px!important; cursor:pointer!important; box-sizing:border-box!important; -webkit-appearance:none!important; -moz-appearance:none!important; appearance:none!important; background-image:url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'8\' fill=\'none\'%3E%3Cpath d=\'M1 1.5l5 5 5-5\' stroke=\''.$mob_arrow.'\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'/%3E%3C/svg%3E")!important; background-repeat:no-repeat!important; background-position:right 16px center!important; background-size:12px 8px!important; }
        #'.$id.' .wexoe-op-mob-division option,
        #'.$id.' .wexoe-op-mob-area option,
        #'.$id.' .wexoe-op-mob-product option { color:#11325D!important; background:#FFFFFF!important; }
        /* Horizontal cards: revert to vertical */
        #'.$id.' .wexoe-op-articles-grid { grid-template-columns:repeat(auto-fill,minmax(150px,1fr))!important; gap:12px!important; }
        #'.$id.' .wexoe-op-articles-horiz { grid-template-columns:repeat(auto-fill,minmax(150px,1fr))!important; }
        #'.$id.' .wexoe-op-article-horiz { flex-direction:column!important; min-height:0!important; }
        #'.$id.' .wexoe-op-article-horiz .wexoe-op-article-img { flex:none!important; max-width:100%!important; aspect-ratio:4/3!important; min-height:0!important; align-self:stretch!important; }
        #'.$id.' .wexoe-op-article-horiz .wexoe-op-article-info { flex-direction:column!important; align-items:stretch!important; padding:14px 16px 16px!important; gap:0!important; }
        #'.$id.' .wexoe-op-article-horiz .wexoe-op-article-name { flex:none!important; font-size:15px!important; margin-bottom:8px!important; min-height:2.6em!important; -webkit-line-clamp:2!important; }
        #'.$id.' .wexoe-op-article-horiz .wexoe-op-article-mid { flex-direction:column!important; flex:1!important; gap:0!important; }
        #'.$id.' .wexoe-op-article-horiz .wexoe-op-variant-wrap { flex-direction:column!important; margin-bottom:8px!important; }
        #'.$id.' .wexoe-op-article-horiz .wexoe-op-variant-row { margin-bottom:6px!important; }
        #'.$id.' .wexoe-op-article-horiz .wexoe-op-variant-select { display:block!important; width:100%!important; max-width:none!important; font-size:13px!important; padding:7px 28px 7px 12px!important; }
        #'.$id.' .wexoe-op-article-horiz .wexoe-op-article-nr,
        #'.$id.' .wexoe-op-article-horiz .wexoe-op-variant-artnr { margin-left:0!important; margin-top:auto!important; padding-top:10px!important; }
        #'.$id.' .wexoe-op-article-horiz .wexoe-op-article-btns { flex-direction:column!important; margin-top:8px!important; }
        #'.$id.' .wexoe-op-article-horiz .wexoe-op-btn-order,
        #'.$id.' .wexoe-op-article-horiz .wexoe-op-btn-ds { font-size:12px!important; padding:9px 16px!important; min-width:0!important; }
        /* Button text swap */
        #'.$id.' .wexoe-op-btn-order { min-width:0!important; text-align:center!important; }
        #'.$id.' .wexoe-op-btn-full { display:none!important; }
        #'.$id.' .wexoe-op-btn-short { display:inline!important; }
        /* Panel */
        #'.$id.' .wexoe-op-panel-row { flex-direction:column!important; }
        #'.$id.' .wexoe-op-panel-img { flex:none!important; max-width:100%!important; }
        /* Request form */
        #'.$id.' .wexoe-op-request-inner { padding:40px 20px!important; }
        #'.$id.' .wexoe-op-request-header-row { flex-direction:column!important; gap:12px!important; }
        #'.$id.' .wexoe-op-customer-toggle { align-self:flex-start!important; }
        #'.$id.' .wexoe-op-customer-panel { align-items:flex-start!important; }
        #'.$id.' .wexoe-op-request-fields { grid-template-columns:1fr 1fr!important; }
        /* Table: hide header, vertical card per row */
        #'.$id.' .wexoe-op-req-table-head { display:none!important; }
        #'.$id.' .wexoe-op-req-row { display:flex!important; flex-direction:row!important; flex-wrap:wrap!important; align-items:flex-start!important; gap:6px!important; padding:16px!important; position:relative!important; }
        #'.$id.' .wexoe-op-row-name { font-size:15px!important; font-weight:700!important; padding-right:28px!important; white-space:normal!important; order:1!important; flex:0 0 100%!important; }
        #'.$id.' .wexoe-op-row-variant { flex-wrap:wrap!important; order:2!important; flex:0 0 100%!important; }
        #'.$id.' .wexoe-op-row-variant select { max-width:none!important; flex-shrink:0!important; }
        #'.$id.' .wexoe-op-row-artnr { font-size:13px!important; order:3!important; flex:0 0 100%!important; }
        #'.$id.' .wexoe-op-row-qty { order:4!important; display:inline-flex!important; flex-direction:column!important; align-items:flex-start!important; margin-top:6px!important; }
        #'.$id.' .wexoe-op-row-qty input { width:60px!important; margin-bottom:2px!important; }
        #'.$id.' .wexoe-op-row-qty::after { content:"Antal"!important; font-size:11px!important; color:#999!important; font-weight:500!important; font-family:"DM Sans",system-ui,sans-serif!important; }
        #'.$id.' .wexoe-op-row-price { order:5!important; display:inline-flex!important; flex-direction:column!important; align-items:flex-start!important; font-size:14px!important; margin-top:6px!important; padding-top:5px!important; margin-left:12px!important; }
        #'.$id.' .wexoe-op-row-price::after { content:"Pris"!important; font-size:11px!important; color:#999!important; font-weight:500!important; font-family:"DM Sans",system-ui,sans-serif!important; }
        #'.$id.' .wexoe-op-row-sum { order:6!important; display:inline-flex!important; flex-direction:column!important; align-items:flex-end!important; text-align:right!important; padding-left:0!important; font-size:15px!important; margin-top:6px!important; padding-top:5px!important; margin-left:auto!important; }
        #'.$id.' .wexoe-op-row-sum::after { content:"Summa"!important; font-size:11px!important; color:#999!important; font-weight:500!important; font-family:"DM Sans",system-ui,sans-serif!important; }
        #'.$id.' .wexoe-op-row-del { position:absolute!important; top:16px!important; right:12px!important; order:99!important; }
        /* Price col overrides for mobile */
        #'.$id.' .wexoe-op-request-articles.wexoe-op-has-prices .wexoe-op-row-price,
        #'.$id.' .wexoe-op-request-articles.wexoe-op-has-prices .wexoe-op-row-sum { display:inline-flex!important; }
        #'.$id.' .wexoe-op-request-articles.wexoe-op-has-prices .wexoe-op-req-row { display:flex!important; flex-direction:row!important; flex-wrap:wrap!important; }
        #'.$id.' .wexoe-op-request-articles.wexoe-op-has-prices .wexoe-op-req-table-head { display:none!important; }
        /* Footer */
        #'.$id.' .wexoe-op-req-footer { flex-direction:column!important; gap:10px!important; }
        #'.$id.' .wexoe-op-req-total { align-self:flex-end!important; }
        /* Bottom */
        #'.$id.' .wexoe-op-bottom { flex-direction:column!important; align-items:stretch!important; }
        #'.$id.' .wexoe-op-gdpr { flex-direction:row!important; justify-content:center!important; }
        #'.$id.' .wexoe-op-submit { justify-content:center!important; }
    }
    @media(max-width:989px) and (min-width:768px) {
        #'.$id.' .wexoe-op-layout { gap:28px!important; padding:40px 30px!important; }
        #'.$id.' .wexoe-op-sidebar { flex:0 0 180px!important; padding:12px 8px!important; }
        #'.$id.' .wexoe-op-request-fields { grid-template-columns:1fr 1fr!important; }
        #'.$id.' .wexoe-op-panel-img { flex:0 0 160px!important; }
    }
    </style>';
}

/* ============================================================
   JAVASCRIPT
   ============================================================ */

function wexoe_op_render_js($id) {
    return '
    <script>
    (function() {
        var wrap = document.getElementById("'.$id.'");
        if (!wrap) return;

        // === HEADER/FOOTER GAP FIX ===
        function fixGaps() {
            // Top: compensate for parent container padding-top
            var el = wrap;
            var topPad = 0;
            while (el && el.parentElement) {
                el = el.parentElement;
                var pt = parseInt(getComputedStyle(el).paddingTop) || 0;
                topPad += pt;
                if (el.tagName === "MAIN" || el.id === "main") break;
            }
            if (topPad > 0) wrap.style.marginTop = "-" + topPad + "px";

            // Bottom: compensate for main padding-bottom (white gap before footer)
            var mainEl = document.querySelector("main.template-page, main");
            if (mainEl) {
                var pb = parseInt(getComputedStyle(mainEl).paddingBottom) || 0;
                if (pb > 0) wrap.style.marginBottom = "-" + pb + "px";
            }
        }
        fixGaps();
        window.addEventListener("load", fixGaps);

        // === SIDEBAR NAVIGATION (3-level: Division → Area → Product) ===
        // Division headers toggle (accordion — only one open at a time)
        wrap.querySelectorAll(".wexoe-op-nav-div-header").forEach(function(hdr) {
            var div = hdr.closest(".wexoe-op-nav-division");
            var body = div.querySelector(".wexoe-op-nav-div-body");
            hdr.addEventListener("click", function() {
                var isOpen = body.classList.contains("wexoe-op-nav-open");
                // Close all divisions first
                wrap.querySelectorAll(".wexoe-op-nav-division").forEach(function(d) {
                    d.classList.remove("wexoe-op-div-open");
                    var b = d.querySelector(".wexoe-op-nav-div-body");
                    if (b) b.classList.remove("wexoe-op-nav-open");
                });
                // Open this one if it was closed
                if (!isOpen) {
                    body.classList.add("wexoe-op-nav-open");
                    div.classList.add("wexoe-op-div-open");
                }
            });
        });

        // Area group headers toggle
        wrap.querySelectorAll(".wexoe-op-nav-header").forEach(function(hdr) {
            var group = hdr.closest(".wexoe-op-nav-group");
            var items = group.querySelector(".wexoe-op-nav-items");
            hdr.addEventListener("click", function() {
                var isOpen = items.classList.contains("wexoe-op-nav-open");
                if (isOpen) {
                    items.classList.remove("wexoe-op-nav-open");
                    group.classList.remove("wexoe-op-group-open");
                } else {
                    items.classList.add("wexoe-op-nav-open");
                    group.classList.add("wexoe-op-group-open");
                }
            });
        });

        // Nav item click
        function switchPanel(areaIdx, prodIdx) {
            var key = areaIdx + "-" + prodIdx;
            wrap.querySelectorAll(".wexoe-op-panel").forEach(function(p) {
                p.style.display = (p.getAttribute("data-panel") === key) ? "" : "none";
            });
            wrap.querySelectorAll(".wexoe-op-nav-item").forEach(function(btn) {
                btn.classList.toggle("wexoe-op-nav-active",
                    btn.getAttribute("data-area") == areaIdx && btn.getAttribute("data-product") == prodIdx);
            });
        }

        wrap.querySelectorAll(".wexoe-op-nav-item").forEach(function(btn) {
            btn.addEventListener("click", function() {
                var ai = this.getAttribute("data-area");
                var pi = this.getAttribute("data-product");
                switchPanel(ai, pi);
                // Also open parent group and division
                var group = this.closest(".wexoe-op-nav-group");
                var items = group.querySelector(".wexoe-op-nav-items");
                items.classList.add("wexoe-op-nav-open");
                group.classList.add("wexoe-op-group-open");
                var div = group.closest(".wexoe-op-nav-division");
                if (div) {
                    div.classList.add("wexoe-op-div-open");
                    var divBody = div.querySelector(".wexoe-op-nav-div-body");
                    if (divBody) divBody.classList.add("wexoe-op-nav-open");
                }
                // Update mobile selects
                // (handled by cascading below)
            });
        });

        // === 3-LEVEL MOBILE SELECTS ===
        var mobNav = wrap.querySelector(".wexoe-op-mobile-nav");
        var navMap = [];
        try { navMap = JSON.parse((mobNav || {}).getAttribute("data-nav-map") || "[]"); } catch(e) {}
        var mobDiv = wrap.querySelector(".wexoe-op-mob-division");
        var mobArea = wrap.querySelector(".wexoe-op-mob-area");
        var mobProd = wrap.querySelector(".wexoe-op-mob-product");

        function populateAreas(divIdx) {
            if (!mobArea || !navMap[divIdx]) return;
            var areas = navMap[divIdx].areas;
            mobArea.innerHTML = "";
            areas.forEach(function(a, i) {
                var o = document.createElement("option");
                o.value = i; o.textContent = a.name;
                mobArea.appendChild(o);
            });
            populateProducts(divIdx, 0);
        }

        function populateProducts(divIdx, areaIdx) {
            if (!mobProd || !navMap[divIdx] || !navMap[divIdx].areas[areaIdx]) return;
            var prods = navMap[divIdx].areas[areaIdx].products;
            mobProd.innerHTML = "";
            prods.forEach(function(p, i) {
                var o = document.createElement("option");
                o.value = p.key; o.textContent = p.name;
                mobProd.appendChild(o);
            });
            if (prods.length > 0) {
                var parts = prods[0].key.split("-");
                switchPanel(parts[0], parts[1]);
            }
        }

        if (mobDiv && mobArea && mobProd && navMap.length > 0) {
            populateAreas(0);

            mobDiv.addEventListener("change", function() {
                populateAreas(parseInt(this.selectedIndex));
            });
            mobArea.addEventListener("change", function() {
                populateProducts(mobDiv.selectedIndex, parseInt(this.value));
            });
            mobProd.addEventListener("change", function() {
                var parts = this.value.split("-");
                switchPanel(parts[0], parts[1]);
            });
        }

        // === VARIANT CASCADING (article cards) ===
        wrap.querySelectorAll(".wexoe-op-variant-wrap").forEach(function(vwrap) {
            var mapData = {};
            try { mapData = JSON.parse(vwrap.getAttribute("data-variant-map") || "{}"); } catch(e) {}
            var sels = vwrap.querySelectorAll(".wexoe-op-variant-select");
            if (sels.length === 0) return;
            var mapKeys = [];
            for (var k in mapData) mapKeys.push(k.split("/"));

            function update(changedIdx) {
                var current = [];
                sels.forEach(function(s) { current.push(s.value); });
                sels.forEach(function(s, sIdx) {
                    if (sIdx === changedIdx) return;
                    var valid = {};
                    mapKeys.forEach(function(parts) {
                        if (parts.length !== sels.length) return;
                        var ok = true;
                        for (var d = 0; d < parts.length; d++) {
                            if (d === sIdx) continue;
                            if (parts[d] !== current[d]) { ok = false; break; }
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
                sels.forEach(function(s) { vals.push(s.value); });
                var key = vals.join("/");
                var nr = mapData[key] || "";
                var card = vwrap.closest(".wexoe-op-article-card");
                if (card) {
                    var artnrEl = card.querySelector(".wexoe-op-variant-artnr");
                    if (artnrEl) {
                        if (nr) {
                            artnrEl.innerHTML = "<span class=\"wexoe-op-nr-label\">Art.</span><span class=\"wexoe-op-nr-value\">" + nr + "</span>";
                            artnrEl.classList.remove("wexoe-op-nomatch");
                        } else {
                            artnrEl.innerHTML = "<span class=\"wexoe-op-nr-label\">Art.</span><span class=\"wexoe-op-nr-value\">Kontakta oss</span>";
                            artnrEl.classList.add("wexoe-op-nomatch");
                        }
                    }
                }
            }

            sels.forEach(function(s, i) {
                s.addEventListener("change", function() { update(i); });
            });
            if (sels.length > 1) update(0);
        });

        // === CUSTOMER PRICES ===
        var customerPrices = null;

        function getPrice(artnr) {
            if (!customerPrices || !artnr) return null;
            return customerPrices[artnr] || null;
        }

        function updateRowPrice(row) {
            var priceCell = row.querySelector(".wexoe-op-row-price");
            var sumCell = row.querySelector(".wexoe-op-row-sum");
            if (!priceCell || !sumCell) return;
            var artnr = row.getAttribute("data-art-nr") || "";
            var price = getPrice(artnr);
            var qtyInput = row.querySelector("input[type=\"number\"]");
            var qty = qtyInput ? parseFloat(qtyInput.value) || 0 : 0;
            if (price !== null) {
                var priceNum = parseFloat(String(price).replace(",", ".")) || 0;
                priceCell.textContent = price + " kr";
                priceCell.className = "wexoe-op-row-price";
                if (qty > 0 && priceNum > 0) {
                    sumCell.textContent = Math.round(priceNum * qty) + " kr";
                } else { sumCell.textContent = "\u2013"; }
            } else if (customerPrices) {
                priceCell.textContent = "Ej i avtal";
                priceCell.className = "wexoe-op-row-price wexoe-op-no-price";
                sumCell.textContent = "\u2013";
            }
        }

        function updateTotal() {
            var totalEl = wrap.querySelector(".wexoe-op-total-value");
            if (!totalEl || !customerPrices) return;
            var total = 0;
            wrap.querySelectorAll(".wexoe-op-req-row").forEach(function(row) {
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
            wrap.querySelectorAll(".wexoe-op-req-row").forEach(function(row) { updateRowPrice(row); });
            updateTotal();
        }

        // === ADD ARTICLE ROW ===
        function addArticleRow(name, artnr, variantText, variantData) {
            var tbody = wrap.querySelector(".wexoe-op-req-table-body");
            if (!tbody) return;
            var emptyMsg = tbody.querySelector(".wexoe-op-req-empty");
            if (emptyMsg) emptyMsg.remove();

            var row = document.createElement("div");
            row.className = "wexoe-op-req-row";
            row.setAttribute("data-art-name", name);
            row.setAttribute("data-art-nr", artnr);
            row.setAttribute("data-art-variant", variantText);

            row.innerHTML = "<span class=\"wexoe-op-row-name\">" + name + "</span>";

            var artnrCell = document.createElement("span");
            artnrCell.className = "wexoe-op-row-artnr";
            artnrCell.textContent = artnr;
            row.appendChild(artnrCell);

            var variantCell = document.createElement("span");
            variantCell.className = "wexoe-op-row-variant";

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

            var priceCell = document.createElement("span");
            priceCell.className = "wexoe-op-row-price wexoe-op-price-col";
            priceCell.textContent = "\u2013";
            row.appendChild(priceCell);

            var qtyCell = document.createElement("span");
            qtyCell.className = "wexoe-op-row-qty";
            var qtyInput = document.createElement("input");
            qtyInput.type = "number"; qtyInput.min = "1"; qtyInput.placeholder = "\u2013";
            qtyInput.addEventListener("input", function() { updateRowPrice(row); updateTotal(); });
            qtyCell.appendChild(qtyInput);
            row.appendChild(qtyCell);

            var sumCell = document.createElement("span");
            sumCell.className = "wexoe-op-row-sum wexoe-op-price-col";
            sumCell.textContent = "\u2013";
            row.appendChild(sumCell);

            var delBtn = document.createElement("button");
            delBtn.className = "wexoe-op-row-del";
            delBtn.title = "Ta bort";
            delBtn.innerHTML = "&times;";
            delBtn.addEventListener("click", function() {
                row.remove();
                if (!tbody.querySelector(".wexoe-op-req-row")) {
                    tbody.innerHTML = "<div class=\"wexoe-op-req-empty\">Inga artiklar tillagda \u00e4nnu.</div>";
                }
                updateTotal();
            });
            row.appendChild(delBtn);
            tbody.appendChild(row);
            if (customerPrices) updateRowPrice(row);
            updateTotal();
        }

        // === ORDER BUTTONS ===
        wrap.querySelectorAll(".wexoe-op-btn-order").forEach(function(btn) {
            btn.addEventListener("click", function(e) {
                e.preventDefault();
                var self = this;
                var card = self.closest(".wexoe-op-article-card");
                if (!card) return;
                var name = (card.querySelector(".wexoe-op-article-name") || {}).textContent || "";
                name = name.trim();
                var artnr = "";
                var variantArtnr = card.querySelector(".wexoe-op-variant-artnr .wexoe-op-nr-value");
                var staticArtnr = card.querySelector(".wexoe-op-article-nr:not(.wexoe-op-variant-artnr) .wexoe-op-nr-value");
                if (variantArtnr) artnr = variantArtnr.textContent.trim();
                else if (staticArtnr) artnr = staticArtnr.textContent.trim();

                var variantText = "";
                var cardSels = card.querySelectorAll(".wexoe-op-variant-select");
                if (cardSels.length > 0) {
                    var parts = [];
                    cardSels.forEach(function(s) { parts.push(s.options[s.selectedIndex] ? s.options[s.selectedIndex].text : s.value); });
                    variantText = parts.join(" / ");
                }

                var variantData = null;
                try { variantData = JSON.parse(self.getAttribute("data-variants") || "null"); } catch(ex) {}

                addArticleRow(name, artnr, variantText, variantData);

                var origHtml = self.innerHTML;
                self.innerHTML = "Tillagd";
                self.classList.add("wexoe-op-btn-added");
                setTimeout(function() {
                    self.innerHTML = origHtml;
                    self.classList.remove("wexoe-op-btn-added");
                }, 3000);
            });
        });

        // === PRODUCT INQUIRY BUTTONS ===
        wrap.querySelectorAll(".wexoe-op-btn-product-inquiry").forEach(function(btn) {
            btn.addEventListener("click", function(e) {
                e.preventDefault();
                var self = this;
                var productName = self.getAttribute("data-product-name") || "";

                // Add a simple product row (no artnr, no variants)
                var tbody = wrap.querySelector(".wexoe-op-req-table-body");
                if (!tbody) return;
                var emptyMsg = tbody.querySelector(".wexoe-op-req-empty");
                if (emptyMsg) emptyMsg.remove();

                var row = document.createElement("div");
                row.className = "wexoe-op-req-row";
                row.setAttribute("data-art-name", productName);
                row.setAttribute("data-art-nr", "");
                row.setAttribute("data-art-variant", "");
                row.innerHTML = "<span class=\"wexoe-op-row-name\">" + productName + "</span>";
                row.innerHTML += "<span class=\"wexoe-op-row-artnr\" style=\"color:#9CA3AF;font-style:italic;font-size:11px;\">Produkt</span>";
                row.innerHTML += "<span class=\"wexoe-op-row-variant\"></span>";

                var priceCell = document.createElement("span");
                priceCell.className = "wexoe-op-row-price wexoe-op-price-col";
                priceCell.textContent = "\u2013";
                row.appendChild(priceCell);

                var qtyCell = document.createElement("span");
                qtyCell.className = "wexoe-op-row-qty";
                qtyCell.textContent = "\u2013";
                row.appendChild(qtyCell);

                var sumCell = document.createElement("span");
                sumCell.className = "wexoe-op-row-sum wexoe-op-price-col";
                sumCell.textContent = "\u2013";
                row.appendChild(sumCell);

                var delBtn = document.createElement("button");
                delBtn.className = "wexoe-op-row-del";
                delBtn.title = "Ta bort";
                delBtn.innerHTML = "&times;";
                delBtn.addEventListener("click", function() {
                    row.remove();
                    if (!tbody.querySelector(".wexoe-op-req-row")) {
                        tbody.innerHTML = "<div class=\"wexoe-op-req-empty\">Inga artiklar tillagda \u00e4nnu.</div>";
                    }
                    updateTotal();
                });
                row.appendChild(delBtn);
                tbody.appendChild(row);

                var origHtml = self.innerHTML;
                self.innerHTML = "Tillagd";
                self.classList.add("wexoe-op-btn-added");
                setTimeout(function() {
                    self.innerHTML = origHtml;
                    self.classList.remove("wexoe-op-btn-added");
                }, 3000);
            });
        });

        // === CUSTOMER TOGGLE + LOOKUP ===
        var custTrigger = wrap.querySelector(".wexoe-op-customer-trigger");
        var custPanel = wrap.querySelector(".wexoe-op-customer-panel");
        if (custTrigger && custPanel) {
            custTrigger.addEventListener("click", function() {
                custTrigger.classList.add("wexoe-op-open");
                custPanel.classList.add("wexoe-op-show");
                var inp = custPanel.querySelector(".wexoe-op-customer-input");
                if (inp) setTimeout(function() { inp.focus(); }, 50);
            });
        }

        var custBtn = wrap.querySelector(".wexoe-op-customer-btn");
        var custInput = wrap.querySelector(".wexoe-op-customer-input");
        var custStatus = wrap.querySelector(".wexoe-op-customer-status");
        var artWrap = wrap.querySelector(".wexoe-op-request-articles");

        if (custBtn && custInput) {
            function doLookup() {
                var cid = custInput.value.trim();
                if (!cid) { custStatus.textContent = "Ange ett kund-ID."; custStatus.className = "wexoe-op-customer-status wexoe-op-status-err"; return; }
                custStatus.textContent = "H\u00e4mtar priser...";
                custStatus.className = "wexoe-op-customer-status wexoe-op-status-loading";
                custBtn.disabled = true;

                var fd = new FormData();
                fd.append("action", "wexoe_pa_customer_lookup");
                fd.append("customer_id", cid);

                var ajaxUrl = wrap.querySelector(".wexoe-op-request-form").getAttribute("data-ajax");
                fetch(ajaxUrl, { method: "POST", body: fd })
                .then(function(r) { return r.json(); })
                .then(function(result) {
                    custBtn.disabled = false;
                    if (result.success) {
                        customerPrices = result.data.prices || {};
                        var nm = result.data.name || cid;
                        custStatus.textContent = "\u2713 Priser laddade f\u00f6r " + nm;
                        custStatus.className = "wexoe-op-customer-status wexoe-op-status-ok";
                        if (artWrap) artWrap.classList.add("wexoe-op-has-prices");
                        var submitText = wrap.querySelector(".wexoe-op-submit-text");
                        if (submitText) submitText.textContent = "Best\u00e4ll";
                        var formHeading = wrap.querySelector(".wexoe-op-request-h2");
                        if (formHeading) formHeading.textContent = "L\u00e4gg en best\u00e4llning";
                        var formSubtitle = wrap.querySelector(".wexoe-op-request-subtitle");
                        if (formSubtitle) formSubtitle.textContent = "L\u00e4gg till artiklar, specificera variant och antal.";
                        // Hide Företag and Telefon fields
                        var allFields = wrap.querySelectorAll(".wexoe-op-request-field");
                        allFields.forEach(function(field) {
                            var inp = field.querySelector("input");
                            if (inp && (inp.name === "foretag" || inp.name === "telefon")) {
                                field.style.display = "none";
                                inp.removeAttribute("required");
                            }
                        });
                        refreshAllPrices();
                    } else {
                        customerPrices = null;
                        custStatus.textContent = result.data || "Kunde inte hitta kund-ID.";
                        custStatus.className = "wexoe-op-customer-status wexoe-op-status-err";
                        if (artWrap) artWrap.classList.remove("wexoe-op-has-prices");
                        var submitText = wrap.querySelector(".wexoe-op-submit-text");
                        if (submitText) submitText.textContent = "Skicka prisf\u00f6rfr\u00e5gan";
                        var formHeading = wrap.querySelector(".wexoe-op-request-h2");
                        if (formHeading) formHeading.textContent = "Prisf\u00f6rfr\u00e5gan";
                        var formSubtitle = wrap.querySelector(".wexoe-op-request-subtitle");
                        if (formSubtitle) formSubtitle.textContent = "L\u00e4gg till artiklar, specificera variant och antal. Vi \u00e5terkommer inom kort med prisf\u00f6rslag.";
                        // Show Företag and Telefon fields again
                        var allFields = wrap.querySelectorAll(".wexoe-op-request-field");
                        allFields.forEach(function(field) {
                            var inp = field.querySelector("input");
                            if (inp && (inp.name === "foretag" || inp.name === "telefon")) {
                                field.style.display = "";
                                inp.setAttribute("required", "");
                            }
                        });
                    }
                }).catch(function() {
                    custBtn.disabled = false;
                    custStatus.textContent = "Fel vid h\u00e4mtning.";
                    custStatus.className = "wexoe-op-customer-status wexoe-op-status-err";
                });
            }
            custBtn.addEventListener("click", doLookup);
            custInput.addEventListener("keydown", function(e) {
                if (e.key === "Enter") { e.preventDefault(); doLookup(); }
            });
        }

        // === ARTICLE SEARCH ===
        var allArticles = [];
        try { allArticles = JSON.parse((artWrap || {}).getAttribute("data-all-articles") || "[]"); } catch(ex) {}

        var addBtn = wrap.querySelector(".wexoe-op-add-btn");
        var searchDd = wrap.querySelector(".wexoe-op-search-dropdown");
        var searchInput = wrap.querySelector(".wexoe-op-search-input");
        var searchResults = wrap.querySelector(".wexoe-op-search-results");

        if (addBtn && searchDd) {
            addBtn.addEventListener("click", function() {
                searchDd.classList.toggle("wexoe-op-show");
                if (searchDd.classList.contains("wexoe-op-show")) {
                    searchInput.value = "";
                    renderSearch("");
                    setTimeout(function() { searchInput.focus(); }, 50);
                }
            });

            function renderSearch(query) {
                var q = query.toLowerCase().trim();
                var html = "";
                var count = 0;
                allArticles.forEach(function(art, idx) {
                    var match = !q || art.name.toLowerCase().indexOf(q) >= 0 || (art.nr && art.nr.toLowerCase().indexOf(q) >= 0);
                    if (match && count < 20) {
                        html += "<div class=\"wexoe-op-search-item\" data-idx=\"" + idx + "\">" + art.name + (art.nr ? "<span class=\"wexoe-op-search-item-nr\">" + art.nr + "</span>" : "") + "</div>";
                        count++;
                    }
                });
                if (!count) html = "<div class=\"wexoe-op-search-none\">Inga artiklar hittades</div>";
                searchResults.innerHTML = html;

                searchResults.querySelectorAll(".wexoe-op-search-item").forEach(function(item) {
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
                        searchDd.classList.remove("wexoe-op-show");
                    });
                });
            }

            searchInput.addEventListener("input", function() { renderSearch(this.value); });
        }

        // === FORM SUBMISSION ===
        var reqForm = wrap.querySelector(".wexoe-op-request-form");
        if (reqForm) {
            var reqSubmit = reqForm.querySelector(".wexoe-op-submit");
            var reqError = wrap.querySelector(".wexoe-op-request-error");
            var reqSuccess = wrap.querySelector(".wexoe-op-success");
            var reqBtnText = reqSubmit ? reqSubmit.querySelector(".wexoe-op-submit-text") : null;

            reqForm.addEventListener("submit", function(e) {
                e.preventDefault();
                reqError.classList.remove("wexoe-op-show");

                var artiklar = [];
                wrap.querySelectorAll(".wexoe-op-req-row").forEach(function(row) {
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
                    reqError.textContent = "L\u00e4gg till minst en artikel eller skriv ett meddelande.";
                    reqError.classList.add("wexoe-op-show");
                    return;
                }

                reqSubmit.classList.add("wexoe-op-loading");
                if (reqBtnText) reqBtnText.textContent = "Skickar...";

                var formData = new FormData(reqForm);
                formData.append("action", "wexoe_pa_request_submit");
                formData.append("nonce", reqForm.getAttribute("data-nonce"));
                formData.append("page_url", window.location.href);
                formData.append("artiklar", JSON.stringify(artiklar));
                var custIdInput = wrap.querySelector(".wexoe-op-customer-input");
                if (custIdInput) formData.append("customer_id", custIdInput.value);

                fetch(reqForm.getAttribute("data-ajax"), { method: "POST", body: formData })
                .then(function(r) { return r.json(); })
                .then(function(result) {
                    if (result.success) {
                        reqForm.style.display = "none";
                        reqSuccess.classList.add("wexoe-op-show");
                        reqSuccess.scrollIntoView({ behavior: "smooth", block: "center" });
                    } else {
                        reqError.textContent = result.data || "N\u00e5got gick fel.";
                        reqError.classList.add("wexoe-op-show");
                        reqSubmit.classList.remove("wexoe-op-loading");
                        if (reqBtnText) reqBtnText.textContent = customerPrices ? "Best\u00e4ll" : "Skicka prisf\u00f6rfr\u00e5gan";
                    }
                }).catch(function() {
                    reqError.textContent = "Ett fel uppstod. F\u00f6rs\u00f6k igen.";
                    reqError.classList.add("wexoe-op-show");
                    reqSubmit.classList.remove("wexoe-op-loading");
                    if (reqBtnText) reqBtnText.textContent = customerPrices ? "Best\u00e4ll" : "Skicka prisf\u00f6rfr\u00e5gan";
                });
            });
        }
    })();
    </script>';
}