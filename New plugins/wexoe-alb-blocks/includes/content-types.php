<?php
/**
 * Content-type registry för Wexoe ALB Blocks.
 *
 * Varje typ definierar:
 *   - label   : visningsnamn i builder-modalen
 *   - entity  : motsvarande wexoe-core-entitet (för list-callback)
 *   - pk      : primärnyckel (default 'slug')
 *   - list    : callback som returnerar [['id'=>..., 'label'=>...], ...]
 *   - render  : callback (raw_id) som returnerar HTML
 *
 * Registry är filtrerbar via `wexoe_alb_content_types` — externa plugins kan
 * lägga till egna typer utan att modifiera detta plugin.
 */

if (!defined('ABSPATH')) exit;

/* ============================================================
   REGISTRY
   ============================================================ */

function wexoe_alb_content_types() {
    return apply_filters('wexoe_alb_content_types', [
        'cms_unique_pages' => [
            'label'  => __('Wexoe Page (meta-sida)', 'wexoe'),
            'entity' => 'cms_unique_pages',
            'pk'     => 'slug',
            'list'   => 'wexoe_alb_list_by_slug',
            'render' => 'wexoe_alb_render_unique_page',
        ],
        'landing_pages' => [
            'label'  => __('Landningssida', 'wexoe'),
            'entity' => 'landing_pages',
            'pk'     => 'slug',
            'list'   => 'wexoe_alb_list_by_slug',
            'render' => 'wexoe_alb_render_landing_page',
        ],
        'audience_heroes' => [
            'label'  => __('Audience Hero', 'wexoe'),
            'entity' => 'audience_heroes',
            'pk'     => 'slug',
            'list'   => 'wexoe_alb_list_by_slug',
            'render' => 'wexoe_alb_render_audience_hero',
        ],
        'product_areas' => [
            'label'  => __('Product Area', 'wexoe'),
            'entity' => 'product_areas',
            'pk'     => 'slug',
            'list'   => 'wexoe_alb_list_by_slug',
            'render' => 'wexoe_alb_render_product_area',
        ],
    ]);
}

/* ============================================================
   GENERISK LIST-CALLBACK
   ============================================================ */

/**
 * Lista poster för en entitet med slug-primärnyckel.
 *
 * Returnerar [['id' => slug, 'label' => visningsnamn], ...] sorterad alfabetiskt
 * på label. Etiketten väljs i prioritetsordning: name → title → h1 → slug.
 *
 * @param string $entity_name  wexoe-core entitetsnamn (skickas från registry-loopen)
 * @return array<array{id:string,label:string}>
 */
function wexoe_alb_list_by_slug($entity_name) {
    if (!wexoe_alb_core_ready()) return [];

    $repo = \Wexoe\Core\Core::entity($entity_name);
    if ($repo === null) return [];

    $records = $repo->all();
    $items = [];

    foreach ($records as $rec) {
        $slug = isset($rec['slug']) ? trim((string) $rec['slug']) : '';
        if ($slug === '') continue;

        $label = '';
        foreach (['name', 'title', 'h1'] as $field) {
            if (!empty($rec[$field])) {
                $label = (string) $rec[$field];
                break;
            }
        }
        if ($label === '') {
            $label = $slug;
        }

        $items[] = [
            'id'    => $slug,
            'label' => $label . ' (' . $slug . ')',
        ];
    }

    usort($items, function ($a, $b) {
        return strcasecmp($a['label'], $b['label']);
    });

    return $items;
}

/* ============================================================
   RENDER-WRAPPERS
   ============================================================ */

/**
 * Returnerar [type => [['id'=>..., 'label'=>...], ...], ...] för alla typer.
 * Cachad per request — list-callbacks gör Core::entity()->all() som redan
 * är cachad i 24h.
 */
function wexoe_alb_collect_all_options() {
    static $cache = null;
    if ($cache !== null) return $cache;

    $cache = [];
    foreach (wexoe_alb_content_types() as $type => $cfg) {
        if (!is_callable($cfg['list'])) continue;
        $entity = isset($cfg['entity']) ? $cfg['entity'] : $type;
        $cache[$type] = call_user_func($cfg['list'], $entity);
    }
    return $cache;
}

/**
 * Bygger den platta subtype-arrayen som matas till Enfolds select.
 *
 * Format: ['cms_unique_pages:om-oss' => 'Om oss (om-oss)', ...]
 * Den första posten är en tom platshållare så att stå-värdet inte
 * råkar mata in en post som redaktören inte valt aktivt.
 */
function wexoe_alb_initial_options() {
    $options = ['' => __('— Välj post —', 'wexoe')];

    foreach (wexoe_alb_collect_all_options() as $type => $items) {
        foreach ($items as $item) {
            $key = $type . ':' . $item['id'];
            $options[$key] = $item['label'];
        }
    }
    return $options;
}

/**
 * Central render-dispatch. Anropas av ALB-modulen och frontend-shortcoden.
 * Strippar typ-prefix från content_id och verifierar att det matchar
 * content_type. Tomt resultat returneras tyst vid fel — frontend ska
 * aldrig läcka redaktör-fel.
 */
function wexoe_alb_render($content_type, $content_id) {
    // Hård guard: render-wrappers anropar funktioner som refererar
    // \Wexoe\Core\Core direkt (t.ex. wexoe_pages_render). Om wexoe-core
    // inte är aktivt skulle det fatala. Returnera tomt istället så att
    // sidor som råkar innehålla [wexoe_content] inte kraschar när
    // beroendet saknas (deaktiverad plugin, uppgradering pågår).
    if (!function_exists('wexoe_alb_core_ready') || !wexoe_alb_core_ready()) {
        return '';
    }

    $types = wexoe_alb_content_types();
    if (empty($types[$content_type])) return '';

    $cfg = $types[$content_type];
    if (!is_callable($cfg['render']) || $content_id === '') return '';

    // Strippa "{type}:{id}"-prefix om det finns. Lämnar råa IDs orörda
    // för bakåtkompatibilitet med ev. shortcodes som sparats utan prefix.
    $raw_id = (string) $content_id;
    if (strpos($raw_id, ':') !== false) {
        list($prefix, $stripped) = explode(':', $raw_id, 2);
        if ($prefix !== $content_type) return '';
        $raw_id = $stripped;
    }
    if ($raw_id === '') return '';

    return (string) call_user_func($cfg['render'], $raw_id);
}

/* ============================================================
   RENDER-CALLBACKS PER TYP
   ============================================================ */

function wexoe_alb_render_unique_page($slug) {
    if (!function_exists('wexoe_pages_render')) return '';
    return wexoe_pages_render($slug);
}

function wexoe_alb_render_landing_page($slug) {
    if (!function_exists('wexoe_landing_page_test_shortcode')) return '';
    return wexoe_landing_page_test_shortcode(['slug' => $slug]);
}

function wexoe_alb_render_audience_hero($slug) {
    // Klassmetod — säkraste anropet är via shortcode. Slug går genom
    // sanitize_text_field i shortcode_handler:n så vi escapar för att
    // undvika att en slug med citationstecken bryter shortcode-parsningen.
    $safe = sanitize_text_field($slug);
    if ($safe === '') return '';
    return do_shortcode('[wexoe_audience slug="' . esc_attr($safe) . '"]');
}

function wexoe_alb_render_product_area($slug) {
    if (!function_exists('wexoe_product_area_test_shortcode')) return '';
    return wexoe_product_area_test_shortcode(['slug' => $slug]);
}
