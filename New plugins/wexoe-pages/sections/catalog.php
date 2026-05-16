<?php
/**
 * Section: catalog (section_type = "catalog")
 *
 * Sökbar/filtrerbar produktkatalog. Datakällor:
 *   - cms_products  (när cat_include_products=true)
 *   - cms_articles  (när cat_include_articles=true)
 *
 * Båda renderas som kort. Alla data inline:as som JSON och klient-side JS
 * filtrerar mot:
 *   1. Search (substring + token-AND mot search_text)
 *   2. Facets från cat_facet_fields (en grupp per rad).
 *
 * Stödda facet-fält för MVP:
 *   - "supplier"  → resolv:as från supplier_ids (link → core_partners.name)
 *
 * Okända facet-namn ignoreras (debug-kommentar i WP_DEBUG).
 *
 * Scope (cat_scope_division, cat_scope_country) påverkar idag bara products
 * indirekt (products har product_page_ids → product_areas, men ingen direkt
 * country/division-länk). Kvar för forward-compat när scope-länkar läggs till.
 */

if (!defined('ABSPATH')) exit;

return function ($section, $page, $ctx) {
    $eyebrow = (string) ($section['cat_eyebrow'] ?? '');
    $h2      = (string) ($section['cat_h2']      ?? '');
    $intro   = (string) ($section['cat_intro_body'] ?? '');
    $include_products = !empty($section['cat_include_products']);
    $include_articles = !empty($section['cat_include_articles']);
    $facet_names = is_array($section['cat_facet_fields'] ?? null) ? $section['cat_facet_fields'] : [];
    $placeholder = (string) ($section['cat_placeholder'] ?? 'Sök i katalogen…');
    $empty_text = (string) ($section['cat_empty_text'] ?? 'Inga träffar.');

    if (!$include_products && !$include_articles) return '';

    // --- Resolva partner-namn för supplier-facet (en gång) ---
    $partner_name_by_id = [];
    if (in_array('supplier', $facet_names, true)) {
        $partner_repo = \Wexoe\Core\Core::entity('core_partners');
        if ($partner_repo !== null) {
            foreach ($partner_repo->all() as $p) {
                if (!empty($p['_record_id']) && !empty($p['name'])) {
                    $partner_name_by_id[$p['_record_id']] = (string) $p['name'];
                }
            }
        }
    }

    // --- Bygg item-listan ---
    $items = [];
    $facet_values = []; // facet_name => [unique values]

    $add_facet_value = function ($facet, $value) use (&$facet_values) {
        if (!isset($facet_values[$facet])) $facet_values[$facet] = [];
        if ($value !== '' && !in_array($value, $facet_values[$facet], true)) {
            $facet_values[$facet][] = $value;
        }
    };

    if ($include_products) {
        $repo = \Wexoe\Core\Core::entity('products');
        if ($repo !== null) {
            foreach ($repo->all(['is_active' => true]) as $p) {
                $name = (string) ($p['name'] ?? '');
                if ($name === '') continue;
                $desc = (string) ($p['description'] ?? $p['ecosystem_description'] ?? '');
                $bullets = is_array($p['bullets'] ?? null) ? implode(' ', $p['bullets']) : '';
                $facets = [];
                if (in_array('supplier', $facet_names, true)) {
                    $supplier_ids = is_array($p['supplier_ids'] ?? null) ? $p['supplier_ids'] : [];
                    $names = [];
                    foreach ($supplier_ids as $sid) {
                        if (isset($partner_name_by_id[$sid])) {
                            $names[] = $partner_name_by_id[$sid];
                            $add_facet_value('supplier', $partner_name_by_id[$sid]);
                        }
                    }
                    if (!empty($names)) $facets['supplier'] = $names;
                }
                $items[] = [
                    'id'    => (string) ($p['_record_id'] ?? ''),
                    'kind'  => 'product',
                    'kind_label' => 'Produkt',
                    'name'  => $name,
                    'desc'  => wp_trim_words(strip_tags($desc), 26),
                    'image' => (string) ($p['image_url'] ?? ''),
                    'link'  => (string) ($p['button_1_url'] ?? ''),
                    'link_label' => (string) ($p['button_1_text'] ?? ''),
                    'search' => mb_strtolower($name . ' ' . strip_tags($desc) . ' ' . $bullets . ' ' . implode(' ', array_values($facets['supplier'] ?? []))),
                    'facets' => $facets,
                ];
            }
        }
    }

    if ($include_articles) {
        $repo = \Wexoe\Core\Core::entity('articles');
        if ($repo !== null) {
            foreach ($repo->all(['is_active' => true]) as $a) {
                $name = (string) ($a['name'] ?? '');
                if ($name === '') continue;
                $desc = (string) ($a['description'] ?? '');
                $article_num = (string) ($a['article_number'] ?? '');
                $facets = [];
                if (in_array('supplier', $facet_names, true)) {
                    $supplier_ids = is_array($a['supplier_ids'] ?? null) ? $a['supplier_ids'] : [];
                    $names = [];
                    foreach ($supplier_ids as $sid) {
                        if (isset($partner_name_by_id[$sid])) {
                            $names[] = $partner_name_by_id[$sid];
                            $add_facet_value('supplier', $partner_name_by_id[$sid]);
                        }
                    }
                    if (!empty($names)) $facets['supplier'] = $names;
                }
                $items[] = [
                    'id'    => (string) ($a['_record_id'] ?? ''),
                    'kind'  => 'article',
                    'kind_label' => 'Artikel',
                    'name'  => $name,
                    'desc'  => $article_num !== '' ? ($article_num . ' — ' . wp_trim_words(strip_tags($desc), 22)) : wp_trim_words(strip_tags($desc), 26),
                    'image' => (string) ($a['image_url'] ?? ''),
                    'link'  => (string) ($a['webshop_url'] ?? $a['datasheet_url'] ?? ''),
                    'link_label' => !empty($a['webshop_url']) ? 'Webbshop' : (!empty($a['datasheet_url']) ? 'Datablad' : ''),
                    'search' => mb_strtolower($name . ' ' . $article_num . ' ' . strip_tags($desc) . ' ' . implode(' ', array_values($facets['supplier'] ?? []))),
                    'facets' => $facets,
                ];
            }
        }
    }

    if (empty($items)) return wexoe_pages_debug_comment('wexoe-pages: catalog har inga items');

    // Sortera alfabetiskt på namn för stabil ordning.
    usort($items, function ($a, $b) { return strcasecmp($a['name'], $b['name']); });
    foreach ($facet_values as &$vals) { sort($vals, SORT_NATURAL | SORT_FLAG_CASE); }
    unset($vals);

    $instance = 'wxp-cat-' . wp_generate_password(8, false, false);
    $attrs = wexoe_pages_section_attrs($section, $ctx, 'wxp-cat');
    $json = wp_json_encode([
        'items' => $items,
        'empty_text' => $empty_text,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    ob_start();
    ?>
    <section <?= $attrs ?>>
        <div class="wxp-section__inner">
            <?php if ($eyebrow !== ''): ?><p class="wxp-eyebrow"><?= esc_html($eyebrow) ?></p><?php endif; ?>
            <?php if ($h2 !== ''): ?><h2 class="wxp-h2"><?= esc_html($h2) ?></h2><?php endif; ?>
            <?php if ($intro !== ''): ?><div class="wxp-body wxp-cat__intro"><?= wexoe_pages_md($intro) ?></div><?php endif; ?>

            <div class="wxp-cat__controls" data-wxp-catalog="<?= esc_attr($instance) ?>">
                <div class="wxp-cat__search-wrap">
                    <input type="search" class="wxp-cat__search" placeholder="<?= esc_attr($placeholder) ?>" aria-label="<?= esc_attr($placeholder) ?>" />
                </div>
                <?php foreach ($facet_values as $facet => $values): ?>
                    <fieldset class="wxp-cat__facet" data-facet="<?= esc_attr($facet) ?>">
                        <legend class="wxp-cat__facet-label"><?= esc_html(ucfirst($facet)) ?></legend>
                        <div class="wxp-cat__chips">
                            <?php foreach ($values as $val): ?>
                                <label class="wxp-cat__chip">
                                    <input type="checkbox" value="<?= esc_attr($val) ?>" />
                                    <span><?= esc_html($val) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                <?php endforeach; ?>
            </div>

            <p class="wxp-cat__count"><span class="wxp-cat__count-shown"><?= count($items) ?></span> av <?= count($items) ?> visas</p>

            <ul class="wxp-cat__grid">
                <?php foreach ($items as $i): ?>
                    <li class="wxp-cat__item" data-search="<?= esc_attr($i['search']) ?>" data-facets='<?= esc_attr(wp_json_encode($i['facets'])) ?>'>
                        <article class="wxp-cat__card">
                            <?php if ($i['image'] !== ''): ?>
                                <div class="wxp-cat__image-wrap"><img class="wxp-cat__image" src="<?= esc_url($i['image']) ?>" alt="<?= esc_attr($i['name']) ?>" loading="lazy" /></div>
                            <?php endif; ?>
                            <div class="wxp-cat__body-wrap">
                                <span class="wxp-cat__kind wxp-cat__kind--<?= esc_attr($i['kind']) ?>"><?= esc_html($i['kind_label']) ?></span>
                                <h3 class="wxp-cat__title"><?= esc_html($i['name']) ?></h3>
                                <?php if ($i['desc'] !== ''): ?><p class="wxp-cat__desc"><?= esc_html($i['desc']) ?></p><?php endif; ?>
                                <?php if ($i['link'] !== ''): ?>
                                    <a class="wxp-cat__link" href="<?= esc_url($i['link']) ?>"><?= esc_html($i['link_label'] !== '' ? $i['link_label'] : 'Läs mer') ?> <span aria-hidden="true">→</span></a>
                                <?php endif; ?>
                            </div>
                        </article>
                    </li>
                <?php endforeach; ?>
            </ul>

            <p class="wxp-cat__empty" hidden><?= esc_html($empty_text) ?></p>
        </div>
    </section>
    <style>
.wxp-cat__intro { margin-bottom: 24px; max-width: 60ch; }
.wxp-cat__controls { display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-start; margin-bottom: 16px; padding: 16px; background: rgba(0,0,0,0.03); border-radius: 12px; }
.wxp-section--theme-dark .wxp-cat__controls { background: rgba(255,255,255,0.05); }
.wxp-cat__search-wrap { flex: 1 1 220px; min-width: 220px; }
.wxp-cat__search { width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid rgba(0,0,0,0.15); font-size: 15px; background: #fff; color: #1A1A1A; font-family: inherit; }
.wxp-section--theme-dark .wxp-cat__search { background: rgba(0,0,0,0.3); border-color: rgba(255,255,255,0.2); color: #fff; }
.wxp-cat__facet { border: 0; padding: 0; margin: 0; }
.wxp-cat__facet-label { font-size: 12px; text-transform: uppercase; letter-spacing: 0.06em; opacity: 0.7; padding: 0 0 6px; font-weight: 500; }
.wxp-cat__chips { display: flex; flex-wrap: wrap; gap: 6px; }
.wxp-cat__chip { display: inline-flex; align-items: center; gap: 4px; padding: 4px 12px; border-radius: 999px; background: rgba(0,0,0,0.06); cursor: pointer; font-size: 13px; transition: background 0.15s; }
.wxp-section--theme-dark .wxp-cat__chip { background: rgba(255,255,255,0.08); }
.wxp-cat__chip:hover { background: rgba(0,0,0,0.12); }
.wxp-cat__chip input { position: absolute; opacity: 0; pointer-events: none; }
.wxp-cat__chip:has(input:checked) { background: #11325D; color: #fff; }
.wxp-section--theme-dark .wxp-cat__chip:has(input:checked) { background: #F28C28; color: #fff; }
.wxp-cat__count { font-size: 13px; opacity: 0.7; margin: 0 0 16px; }
.wxp-cat__grid { list-style: none; padding: 0; margin: 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 20px; }
.wxp-cat__item { display: flex; }
.wxp-cat__item[hidden] { display: none; }
.wxp-cat__card { display: flex; flex-direction: column; background: #fff; border-radius: 10px; overflow: hidden; border: 1px solid rgba(0,0,0,0.06); width: 100%; }
.wxp-section--theme-dark .wxp-cat__card { background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.1); }
.wxp-cat__image-wrap { aspect-ratio: 1/1; background: #F5F6F8; }
.wxp-cat__image { width: 100%; height: 100%; object-fit: contain; padding: 16px; box-sizing: border-box; display: block; }
.wxp-cat__body-wrap { padding: 14px 16px 16px; display: flex; flex-direction: column; gap: 6px; flex: 1; }
.wxp-cat__kind { font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; opacity: 0.7; align-self: flex-start; padding: 2px 8px; border-radius: 4px; background: rgba(0,0,0,0.05); }
.wxp-section--theme-dark .wxp-cat__kind { background: rgba(255,255,255,0.08); }
.wxp-cat__title { font-size: 15px; margin: 0; font-weight: 600; line-height: 1.3; }
.wxp-cat__desc { font-size: 13px; line-height: 1.5; opacity: 0.85; margin: 0; }
.wxp-cat__link { font-size: 13px; font-weight: 500; color: #11325D; text-decoration: none; margin-top: auto; padding-top: 4px; }
.wxp-section--theme-dark .wxp-cat__link { color: #F28C28; }
.wxp-cat__empty { padding: 48px 24px; text-align: center; opacity: 0.7; font-size: 16px; }
    </style>
    <script>
(function(){
    var root = document.querySelector('[data-wxp-catalog="<?= esc_js($instance) ?>"]');
    if (!root) return;
    var section = root.closest('section');
    if (!section) return;
    var input = root.querySelector('.wxp-cat__search');
    var facetGroups = Array.prototype.slice.call(root.querySelectorAll('.wxp-cat__facet'));
    var items = Array.prototype.slice.call(section.querySelectorAll('.wxp-cat__item'));
    var emptyEl = section.querySelector('.wxp-cat__empty');
    var countEl = section.querySelector('.wxp-cat__count-shown');
    var total = items.length;

    function activeFacets() {
        var result = {};
        facetGroups.forEach(function(g){
            var name = g.getAttribute('data-facet');
            var checked = Array.prototype.slice.call(g.querySelectorAll('input:checked')).map(function(i){ return i.value; });
            if (checked.length) result[name] = checked;
        });
        return result;
    }

    function tokenize(s) {
        return s.toLowerCase().split(/\s+/).filter(function(t){ return t.length > 0; });
    }

    function matchesSearch(item, tokens) {
        if (!tokens.length) return true;
        var hay = item.getAttribute('data-search') || '';
        for (var i = 0; i < tokens.length; i++) {
            if (hay.indexOf(tokens[i]) === -1) return false;
        }
        return true;
    }

    function matchesFacets(item, active) {
        var keys = Object.keys(active);
        if (!keys.length) return true;
        var parsed;
        try { parsed = JSON.parse(item.getAttribute('data-facets') || '{}'); }
        catch (e) { parsed = {}; }
        for (var i = 0; i < keys.length; i++) {
            var k = keys[i];
            var requiredAny = active[k];
            var has = parsed[k] || [];
            var found = false;
            for (var j = 0; j < requiredAny.length; j++) {
                if (has.indexOf(requiredAny[j]) !== -1) { found = true; break; }
            }
            if (!found) return false;
        }
        return true;
    }

    function apply() {
        var tokens = tokenize(input ? input.value : '');
        var facets = activeFacets();
        var shown = 0;
        items.forEach(function(it){
            var visible = matchesSearch(it, tokens) && matchesFacets(it, facets);
            if (visible) { it.removeAttribute('hidden'); shown++; }
            else it.setAttribute('hidden', '');
        });
        if (countEl) countEl.textContent = String(shown);
        if (emptyEl) {
            if (shown === 0) emptyEl.removeAttribute('hidden');
            else emptyEl.setAttribute('hidden', '');
        }
    }

    if (input) input.addEventListener('input', apply);
    facetGroups.forEach(function(g){
        g.addEventListener('change', apply);
    });
})();
    </script>
    <?php
    return ob_get_clean();
};
