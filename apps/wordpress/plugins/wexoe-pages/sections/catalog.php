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
    $include_products = true;
    $include_articles = false;
    $facet_names = is_array($section['cat_facet_fields'] ?? null) ? $section['cat_facet_fields'] : [];
    $placeholder = (string) ($section['cat_placeholder'] ?? 'Sök i katalogen…');
    $empty_text = (string) ($section['cat_empty_text'] ?? 'Inga träffar.');

    
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


    if (empty($items)) return wexoe_pages_debug_comment('wexoe-pages: catalog har inga items');

    // Sortera alfabetiskt på namn för stabil ordning.
    usort($items, function ($a, $b) { return strcasecmp($a['name'], $b['name']); });
    foreach ($facet_values as &$vals) { sort($vals, SORT_NATURAL | SORT_FLAG_CASE); }
    unset($vals);

    $instance = 'wxp-cat-' . wp_generate_password(8, false, false);
    $wid = (string) ($ctx['wrapper_id'] ?? '');
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

            <ul class="wxp-cat__grid" data-initial-limit="12">
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

            <div class="wxp-cat__more-wrap" hidden>
                <button type="button" class="wxp-btn wxp-btn--secondary wxp-cat__more">Visa fler</button>
            </div>

            <p class="wxp-cat__empty" hidden><?= esc_html($empty_text) ?></p>
        </div>
    </section>
    <style>
#<?= esc_attr($wid) ?> .wxp-cat__intro { margin-bottom: 28px !important; max-width: 60ch !important; }
#<?= esc_attr($wid) ?> .wxp-cat__controls { display: flex !important; flex-wrap: wrap !important; gap: 20px !important; align-items: flex-start !important; margin-bottom: 20px !important; padding: 20px !important; background: #FAFAF7 !important; border-radius: 2px !important; border: 1px solid rgba(15,15,15,0.08) !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-cat__controls { background: rgba(255,255,255,0.05) !important; border-color: rgba(255,255,255,0.10) !important; }
#<?= esc_attr($wid) ?> .wxp-cat__search-wrap { flex: 1 1 240px !important; min-width: 220px !important; }
#<?= esc_attr($wid) ?> .wxp-cat__search { width: 100% !important; padding: 11px 14px !important; border-radius: 2px !important; border: 1px solid rgba(17,50,93,0.15) !important; font-size: 15px !important; background: #fff !important; color: #1A1A1A !important; font-family: 'DM Sans', system-ui, sans-serif !important; outline: none !important; transition: border-color 0.2s ease !important; }
#<?= esc_attr($wid) ?> .wxp-cat__search:focus { border-color: #11325D !important; box-shadow: 0 0 0 3px rgba(17,50,93,0.08) !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-cat__search { background: rgba(0,0,0,0.25) !important; border-color: rgba(255,255,255,0.20) !important; color: #fff !important; }
#<?= esc_attr($wid) ?> .wxp-cat__facet { border: 0 !important; padding: 0 !important; margin: 0 !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-cat__facet-label { font-family: 'DM Sans', system-ui, sans-serif !important; font-size: 11px !important; text-transform: uppercase !important; letter-spacing: 0.1em !important; opacity: 0.75 !important; padding: 0 0 8px !important; font-weight: 700 !important; color: inherit !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-cat__chips { display: flex !important; flex-wrap: wrap !important; gap: 6px !important; }
#<?= esc_attr($wid) ?> .wxp-cat__chip { position: relative !important; display: inline-flex !important; align-items: center !important; padding: 6px 14px !important; border-radius: 999px !important; background: #fff !important; border: 1px solid rgba(17,50,93,0.12) !important; cursor: pointer !important; font-size: 13px !important; font-weight: 500 !important; color: inherit !important; transition: all 0.15s ease !important; margin: 0 !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-cat__chip { background: rgba(255,255,255,0.06) !important; border-color: rgba(255,255,255,0.12) !important; }
#<?= esc_attr($wid) ?> .wxp-cat__chip:hover { border-color: rgba(17,50,93,0.3) !important; }
#<?= esc_attr($wid) ?> .wxp-cat__chip input { position: absolute !important; opacity: 0 !important; pointer-events: none !important; }
#<?= esc_attr($wid) ?> .wxp-cat__chip:has(input:checked) { background: #11325D !important; color: #fff !important; border-color: #11325D !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-cat__chip:has(input:checked) { background: #fff !important; color: #11325D !important; border-color: #fff !important; }
#<?= esc_attr($wid) ?> .wxp-cat__count { font-size: 13px !important; opacity: 0.72 !important; margin: 0 0 20px !important; padding: 0 !important; color: inherit !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-cat__grid { list-style: none !important; padding: 0 !important; margin: 0 !important; display: grid !important; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)) !important; gap: 20px !important; }
#<?= esc_attr($wid) ?> .wxp-cat__item { display: flex !important; list-style: none !important; padding: 0 !important; margin: 0 !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-cat__item::before { content: none !important; display: none !important; }
#<?= esc_attr($wid) ?> .wxp-cat__item[hidden] { display: none !important; }
#<?= esc_attr($wid) ?> .wxp-cat__card { display: flex !important; flex-direction: column !important; background: #fff !important; border-radius: 2px !important; overflow: hidden !important; border: 1px solid rgba(15,15,15,0.10) !important; box-shadow: none !important; width: 100% !important; transition: border-color 0.22s ease, transform 0.22s ease, box-shadow 0.22s ease !important; }
#<?= esc_attr($wid) ?> .wxp-cat__card:hover { transform: translateY(-2px) !important; border-color: rgba(15,15,15,0.32) !important; box-shadow: 0 14px 32px rgba(10,26,46,0.07) !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-cat__card { background: rgba(255,255,255,0.04) !important; border-color: rgba(255,255,255,0.10) !important; box-shadow: none !important; }
#<?= esc_attr($wid) ?> .wxp-cat__image-wrap { aspect-ratio: 1 / 1 !important; background: #FAFAF7 !important; padding: 16px !important; display: flex !important; align-items: center !important; justify-content: center !important; }
#<?= esc_attr($wid) ?> .wxp-cat__image { max-width: 100% !important; max-height: 100% !important; width: auto !important; height: auto !important; object-fit: contain !important; display: block !important; }
#<?= esc_attr($wid) ?> .wxp-cat__body-wrap { padding: 16px 18px 18px !important; display: flex !important; flex-direction: column !important; gap: 8px !important; flex: 1 !important; }
#<?= esc_attr($wid) ?> .wxp-cat__kind { font-size: 11px !important; text-transform: uppercase !important; letter-spacing: 0.08em !important; opacity: 0.78 !important; align-self: flex-start !important; padding: 3px 10px !important; border-radius: 2px !important; background: rgba(17,50,93,0.08) !important; color: #11325D !important; font-weight: 600 !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-cat__kind { background: rgba(255,255,255,0.08) !important; color: #fff !important; }
#<?= esc_attr($wid) ?> .wxp-cat__title { font-family: 'DM Sans', system-ui, sans-serif !important; font-size: 15px !important; margin: 0 !important; padding: 0 !important; font-weight: 700 !important; line-height: 1.3 !important; color: #11325D !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-cat__title { color: #fff !important; }
#<?= esc_attr($wid) ?> .wxp-cat__desc { font-size: 13px !important; line-height: 1.55 !important; opacity: 0.82 !important; margin: 0 !important; padding: 0 !important; color: inherit !important; background: none !important; }
/* CTA-länk: orange-action (matchar "Läs mer →"-mönstret i news-grid). */
#<?= esc_attr($wid) ?> .wxp-cat__link { font-size: 13px !important; font-weight: 600 !important; color: #F28C28 !important; text-decoration: none !important; margin-top: auto !important; padding-top: 4px !important; }
#<?= esc_attr($wid) ?> .wxp-cat__empty { padding: 48px 24px !important; text-align: center !important; opacity: 0.72 !important; font-size: 16px !important; }
#<?= esc_attr($wid) ?> .wxp-cat__more-wrap { display: flex !important; justify-content: center !important; margin-top: 28px !important; }
#<?= esc_attr($wid) ?> .wxp-cat__more-wrap[hidden] { display: none !important; }
    </style>
    <script>
(function(){
    var root = document.querySelector('[data-wxp-catalog="<?= esc_js($instance) ?>"]');
    if (!root) return;
    var section = root.closest('section');
    if (!section) return;
    var input = root.querySelector('.wxp-cat__search');
    var facetGroups = Array.prototype.slice.call(root.querySelectorAll('.wxp-cat__facet'));
    var grid = section.querySelector('.wxp-cat__grid');
    var items = Array.prototype.slice.call(section.querySelectorAll('.wxp-cat__item'));
    var emptyEl = section.querySelector('.wxp-cat__empty');
    var countEl = section.querySelector('.wxp-cat__count-shown');
    var moreWrap = section.querySelector('.wxp-cat__more-wrap');
    var moreBtn = section.querySelector('.wxp-cat__more');
    var initialLimit = parseInt(grid && grid.getAttribute('data-initial-limit'), 10) || 12;
    var visibleLimit = initialLimit;
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
        var matchCount = 0; // antal items som passar filter (oavsett limit)
        var shown = 0;      // antal items som faktiskt visas
        items.forEach(function(it){
            var matched = matchesSearch(it, tokens) && matchesFacets(it, facets);
            if (matched) {
                if (matchCount < visibleLimit) {
                    it.removeAttribute('hidden');
                    shown++;
                } else {
                    it.setAttribute('hidden', '');
                }
                matchCount++;
            } else {
                it.setAttribute('hidden', '');
            }
        });
        if (countEl) countEl.textContent = String(shown);
        if (emptyEl) {
            if (matchCount === 0) emptyEl.removeAttribute('hidden');
            else emptyEl.setAttribute('hidden', '');
        }
        if (moreWrap && moreBtn) {
            var remaining = matchCount - shown;
            if (remaining > 0) {
                moreBtn.textContent = 'Visa fler (' + remaining + ' till)';
                moreWrap.removeAttribute('hidden');
            } else {
                moreWrap.setAttribute('hidden', '');
            }
        }
    }

    if (input) input.addEventListener('input', function() {
        // Återställ limit när användaren ändrar filter — nytt sökresultat
        // ska börja om från första batchen istället för att ärva en stor
        // visible-limit från ett tidigare "Visa alla"-klick.
        visibleLimit = initialLimit;
        apply();
    });
    facetGroups.forEach(function(g){
        g.addEventListener('change', function() {
            visibleLimit = initialLimit;
            apply();
        });
    });
    if (moreBtn) moreBtn.addEventListener('click', function() {
        visibleLimit += initialLimit;
        apply();
    });
    apply();
})();
    </script>
    <?php
    return ob_get_clean();
};
