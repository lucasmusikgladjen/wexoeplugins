<?php
/**
 * Section: case_grid (section_type = "case_grid")
 *
 * Grid med case-kort. Datakälla: cms_case_pages (entity 'case_pages').
 * Pin-then-scope: cg_case_manual_ids först, fyll på via scope (country,
 * division, customer_type) upp till cg_limit. cg_columns styr grid-bredd.
 *
 * Header har eyebrow + h2 + body + valfri "Alla case →"-länk till höger
 * (cg_cta_text + cg_cta_url, båda måste vara satta).
 *
 * Korten: 2px-radius, 1px-border, ingen heavy shadow. Standardiserad text-
 * höjd (116px) klampar 2-rad titel + 4-rad desc så alla kort matchar.
 * Industry-badge i bildens övre vänster (case_pages.card_industry).
 * Result-rad: grön ✓-cirkel + plain text med `<strong>` på nyckeltalet
 * (card_result tolkas som inline-markdown).
 * Partners: uppercase navy 700-text, separator-line mellan multipla, inga
 * logos längre.
 */

if (!defined('ABSPATH')) exit;

return function ($section, $page, $ctx) {
    $eyebrow  = (string) ($section['cg_eyebrow'] ?? '');
    $h2       = (string) ($section['cg_h2']      ?? '');
    $body     = (string) ($section['cg_body']    ?? '');
    $cta_text = (string) ($section['cg_cta_text'] ?? '');
    $cta_url  = (string) ($section['cg_cta_url']  ?? '');
    $columns = in_array(($section['cg_columns'] ?? ''), ['2', '3', '4'], true) ? (int) $section['cg_columns'] : 3;
    $limit   = max(0, (int) ($section['cg_limit'] ?? 0));
    $manual_ids = is_array($section['cg_case_manual_ids'] ?? null) ? $section['cg_case_manual_ids'] : [];

    $scope = wexoe_pages_resolve_scope($section, $ctx, [
        'country'       => 'cg_scope_country',
        'division'      => 'cg_scope_division',
        'customer_type' => 'cg_scope_customer_type',
    ]);

    $cases = wexoe_pages_pin_then_scope(
        $manual_ids,
        'case_pages',
        function () use ($scope) {
            $repo = \Wexoe\Core\Core::entity('case_pages');
            if ($repo === null) return [];
            $all = $repo->all(['is_active' => true]);
            $country_id = wexoe_pages_resolve_link_id_for_scope($scope, 'country');
            $customer_type_id = wexoe_pages_resolve_link_id_for_scope($scope, 'customer_type');
            $matches = [];
            foreach ($all as $rec) {
                if (!wexoe_pages_link_matches($rec, 'country_ids', $country_id)) continue;
                if (!wexoe_pages_link_matches($rec, 'customer_type_ids', $customer_type_id)) continue;
                $matches[] = $rec;
            }
            usort($matches, function ($a, $b) {
                $oa = isset($a['order']) ? (float) $a['order'] : 999.0;
                $ob = isset($b['order']) ? (float) $b['order'] : 999.0;
                return $oa <=> $ob;
            });
            return $matches;
        },
        $limit
    );

    if (empty($cases) && $h2 === '' && $body === '') return '';

    // Förfyll partner-records för alla case (en batch-läsning istället för en per kort).
    $partner_by_id = [];
    $all_partner_ids = [];
    foreach ($cases as $c) {
        if (!empty($c['partner_ids']) && is_array($c['partner_ids'])) {
            foreach ($c['partner_ids'] as $pid) { $all_partner_ids[$pid] = true; }
        }
    }
    if (!empty($all_partner_ids)) {
        $partner_repo = \Wexoe\Core\Core::entity('core_partners');
        if ($partner_repo !== null) {
            foreach ($partner_repo->find_by_ids(array_keys($all_partner_ids)) as $p) {
                if (!empty($p['_record_id'])) $partner_by_id[$p['_record_id']] = $p;
            }
        }
    }

    $has_header = ($eyebrow !== '' || $h2 !== '' || $body !== '' || ($cta_text !== '' && $cta_url !== ''));

    $wid = (string) ($ctx['wrapper_id'] ?? '');
    $attrs = wexoe_pages_section_attrs($section, $ctx, 'wxp-cg wxp-cg--cols-' . $columns);
    ob_start();
    ?>
    <section <?= $attrs ?>>
        <div class="wxp-section__inner">
            <?php if ($has_header): ?>
                <header class="wxp-cg__header">
                    <div class="wxp-cg__title-block">
                        <?php if ($eyebrow !== ''): ?><p class="wxp-eyebrow"><?= esc_html($eyebrow) ?></p><?php endif; ?>
                        <?php if ($h2 !== ''): ?><h2 class="wxp-cg__title"><?= esc_html($h2) ?></h2><?php endif; ?>
                        <?php if ($body !== ''): ?><div class="wxp-body wxp-cg__lede"><?= wexoe_pages_md($body) ?></div><?php endif; ?>
                    </div>
                    <?php if ($cta_text !== '' && $cta_url !== ''): ?>
                        <a class="wxp-cg__cta" href="<?= esc_url($cta_url) ?>"><?= esc_html($cta_text) ?> <span aria-hidden="true">→</span></a>
                    <?php endif; ?>
                </header>
            <?php endif; ?>

            <?php if (!empty($cases)): ?>
                <ul class="wxp-cg__grid">
                    <?php foreach ($cases as $case):
                        $title = (string) ($case['card_title'] ?? $case['h1'] ?? $case['slug'] ?? '');
                        if ($title === '') continue;
                        $desc = (string) ($case['card_description'] ?? '');
                        $result = (string) ($case['card_result'] ?? '');
                        $industry = (string) ($case['card_industry'] ?? '');
                        $image = (string) ($case['card_image_url'] ?? '');
                        $href = !empty($case['legacy_external_url']) ? (string) $case['legacy_external_url'] : ('/' . ($case['slug'] ?? ''));
                        // Samla partner-namn (kan vara flera).
                        $partner_names = [];
                        if (!empty($case['partner_ids']) && is_array($case['partner_ids'])) {
                            foreach ($case['partner_ids'] as $pid) {
                                if (isset($partner_by_id[$pid])) {
                                    $pn = (string) ($partner_by_id[$pid]['name'] ?? '');
                                    if ($pn !== '') $partner_names[] = $pn;
                                }
                            }
                        }
                    ?>
                        <li class="wxp-cg__item">
                            <a class="wxp-cg__card" href="<?= esc_url($href) ?>">
                                <div class="wxp-cg__image-wrap">
                                    <?php if ($industry !== ''): ?>
                                        <span class="wxp-cg__industry"><?= esc_html($industry) ?></span>
                                    <?php endif; ?>
                                    <?php if ($image !== ''): ?>
                                        <img src="<?= esc_url($image) ?>" alt="" class="wxp-cg__image" loading="lazy" />
                                    <?php else: ?>
                                        <div class="wxp-cg__image-placeholder" aria-hidden="true"><?= esc_html(mb_substr($title, 0, 18)) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="wxp-cg__body-wrap">
                                    <div class="wxp-cg__text">
                                        <h3 class="wxp-cg__title-card"><?= esc_html($title) ?></h3>
                                        <?php if ($desc !== ''): ?>
                                            <p class="wxp-cg__desc"><?= esc_html($desc) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($result !== ''): ?>
                                        <div class="wxp-cg__result">
                                            <span class="wxp-cg__check" aria-hidden="true"></span>
                                            <span class="wxp-cg__result-text"><?= wexoe_pages_md_inline($result) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="wxp-cg__footer">
                                        <?php if (!empty($partner_names)): ?>
                                            <div class="wxp-cg__partners">
                                                <?php foreach ($partner_names as $pn): ?>
                                                    <span class="wxp-cg__partner"><?= esc_html($pn) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="wxp-cg__partners" aria-hidden="true"></span>
                                        <?php endif; ?>
                                        <span class="wxp-cg__arrow" aria-hidden="true">→</span>
                                    </div>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>
    <style>
#<?= esc_attr($wid) ?> .wxp-cg__header { display: grid !important; grid-template-columns: 1fr auto !important; align-items: end !important; gap: 32px !important; margin: 0 0 56px !important; padding: 0 0 24px !important; border-bottom: 1px solid rgba(15,15,15,0.10) !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-cg__header { border-bottom-color: rgba(255,255,255,0.14) !important; }
#<?= esc_attr($wid) ?> .wxp-cg__title-block { max-width: 56ch !important; }
#<?= esc_attr($wid) ?> .wxp-cg__title { font-family: 'DM Sans', system-ui, sans-serif !important; font-size: clamp(1.875rem, 3.4vw, 2.625rem) !important; font-weight: 600 !important; line-height: 1.08 !important; letter-spacing: -0.025em !important; color: #11325D !important; margin: 0 0 14px !important; padding: 0 !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-cg__title { color: #fff !important; }
#<?= esc_attr($wid) ?> .wxp-cg__lede { font-size: 15px !important; line-height: 1.6 !important; color: #5A6473 !important; margin: 0 !important; max-width: 52ch !important; }
#<?= esc_attr($wid) ?> .wxp-cg__lede p { margin: 0 !important; color: inherit !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-cg__lede { color: rgba(255,255,255,0.78) !important; }
#<?= esc_attr($wid) ?> .wxp-cg__cta { font-family: 'DM Sans', system-ui, sans-serif !important; font-size: 13px !important; font-weight: 600 !important; letter-spacing: -0.005em !important; color: #0F0F0F !important; text-decoration: none !important; padding-bottom: 6px !important; border-bottom: 1px solid #0F0F0F !important; white-space: nowrap !important; transition: color 0.2s ease, border-color 0.2s ease !important; align-self: end !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-cg__cta { color: #fff !important; border-bottom-color: #fff !important; }
#<?= esc_attr($wid) ?> .wxp-cg__cta:hover { color: #F28C28 !important; border-bottom-color: #F28C28 !important; }
#<?= esc_attr($wid) ?> .wxp-cg__cta span { margin-left: 4px !important; transition: transform 0.2s ease !important; display: inline-block !important; }
#<?= esc_attr($wid) ?> .wxp-cg__cta:hover span { transform: translateX(4px) !important; }

/* Grid */
#<?= esc_attr($wid) ?> .wxp-cg__grid { list-style: none !important; padding: 0 !important; margin: 0 !important; display: grid !important; gap: 24px !important; }
#<?= esc_attr($wid) ?> .wxp-cg--cols-2 .wxp-cg__grid { grid-template-columns: repeat(2, 1fr) !important; }
#<?= esc_attr($wid) ?> .wxp-cg--cols-3 .wxp-cg__grid { grid-template-columns: repeat(3, 1fr) !important; }
#<?= esc_attr($wid) ?> .wxp-cg--cols-4 .wxp-cg__grid { grid-template-columns: repeat(4, 1fr) !important; }
#<?= esc_attr($wid) ?> .wxp-cg__item { display: flex !important; list-style: none !important; padding: 0 !important; margin: 0 !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-cg__item::before { content: none !important; display: none !important; }

/* Card */
#<?= esc_attr($wid) ?> .wxp-cg__card { display: flex !important; flex-direction: column !important; background: #fff !important; border: 1px solid rgba(15,15,15,0.10) !important; border-radius: 2px !important; overflow: hidden !important; text-decoration: none !important; color: inherit !important; width: 100% !important; transition: border-color 0.22s ease, transform 0.22s ease, box-shadow 0.22s ease !important; box-shadow: none !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-cg__card { background: rgba(255,255,255,0.04) !important; border-color: rgba(255,255,255,0.12) !important; color: #fff !important; }
#<?= esc_attr($wid) ?> .wxp-cg__card:hover { border-color: rgba(15,15,15,0.32) !important; transform: translateY(-2px) !important; box-shadow: 0 14px 32px rgba(10,26,46,0.07) !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-cg__card:hover { border-color: rgba(255,255,255,0.32) !important; box-shadow: none !important; }
#<?= esc_attr($wid) ?> .wxp-cg__card:hover .wxp-cg__image { transform: scale(1.04) !important; }
#<?= esc_attr($wid) ?> .wxp-cg__card:hover .wxp-cg__arrow { color: #F28C28 !important; transform: translateX(3px) !important; }

/* Image + industry badge */
#<?= esc_attr($wid) ?> .wxp-cg__image-wrap { position: relative !important; aspect-ratio: 4 / 3 !important; background: #FAFAF7 !important; overflow: hidden !important; }
#<?= esc_attr($wid) ?> .wxp-cg__image { position: absolute !important; inset: 0 !important; width: 100% !important; height: 100% !important; object-fit: cover !important; display: block !important; transition: transform 0.6s ease !important; }
#<?= esc_attr($wid) ?> .wxp-cg__image-placeholder { position: absolute !important; inset: 0 !important; display: flex !important; align-items: center !important; justify-content: center !important; padding: 16px !important; background: linear-gradient(135deg, #11325D 0%, #1a4a7a 100%) !important; color: rgba(255,255,255,0.65) !important; font-family: 'DM Sans', system-ui, sans-serif !important; font-size: 12px !important; font-weight: 500 !important; letter-spacing: 0.06em !important; text-align: center !important; line-height: 1.4 !important; text-transform: uppercase !important; }
#<?= esc_attr($wid) ?> .wxp-cg__industry { position: absolute !important; top: 14px !important; left: 14px !important; z-index: 2 !important; padding: 5px 10px !important; background: rgba(250,250,247,0.95) !important; backdrop-filter: blur(8px) !important; -webkit-backdrop-filter: blur(8px) !important; border-radius: 1px !important; font-family: 'DM Sans', system-ui, sans-serif !important; font-size: 10px !important; font-weight: 600 !important; letter-spacing: 0.14em !important; text-transform: uppercase !important; color: #0F0F0F !important; line-height: 1 !important; }

/* Body */
#<?= esc_attr($wid) ?> .wxp-cg__body-wrap { display: flex !important; flex-direction: column !important; padding: 22px !important; flex: 1 !important; gap: 16px !important; }
/* Text-block: fast totalhöjd så alla kort matchar.
   - Titel 2 rader + Desc 3 rader = 116px (med gap 8)
   - Titel 1 rad + Desc 4 rader = 116px
   Overflow trunkeras via -webkit-line-clamp. */
#<?= esc_attr($wid) ?> .wxp-cg__text { display: flex !important; flex-direction: column !important; gap: 8px !important; height: 116px !important; overflow: hidden !important; }
#<?= esc_attr($wid) ?> .wxp-cg__title-card { font-family: 'DM Sans', system-ui, sans-serif !important; font-size: 16px !important; font-weight: 600 !important; line-height: 1.32 !important; letter-spacing: -0.012em !important; color: #0F0F0F !important; margin: 0 !important; padding: 0 !important; display: -webkit-box !important; -webkit-line-clamp: 2 !important; -webkit-box-orient: vertical !important; overflow: hidden !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-cg__title-card { color: #fff !important; }
#<?= esc_attr($wid) ?> .wxp-cg__desc { font-family: 'DM Sans', system-ui, sans-serif !important; font-size: 14px !important; font-weight: 400 !important; line-height: 1.55 !important; color: #5A6473 !important; margin: 0 !important; padding: 0 !important; letter-spacing: -0.003em !important; flex: 1 !important; display: -webkit-box !important; -webkit-line-clamp: 4 !important; -webkit-box-orient: vertical !important; overflow: hidden !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-cg__desc { color: rgba(255,255,255,0.74) !important; }

/* Result: grön ✓ + text. `<strong>` i text-noden blir navy nyckeltal. */
#<?= esc_attr($wid) ?> .wxp-cg__result { display: flex !important; align-items: center !important; gap: 10px !important; }
#<?= esc_attr($wid) ?> .wxp-cg__check { flex-shrink: 0 !important; width: 18px !important; height: 18px !important; border-radius: 50% !important; background: #16A34A !important; color: #fff !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; font-size: 11px !important; font-weight: 800 !important; line-height: 1 !important; }
#<?= esc_attr($wid) ?> .wxp-cg__check::after { content: '\2713' !important; }
#<?= esc_attr($wid) ?> .wxp-cg__result-text { font-family: 'DM Sans', system-ui, sans-serif !important; font-size: 13px !important; font-weight: 500 !important; color: #0F0F0F !important; line-height: 1.4 !important; letter-spacing: -0.005em !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-cg__result-text { color: #fff !important; }
#<?= esc_attr($wid) ?> .wxp-cg__result-text strong { font-weight: 600 !important; color: #11325D !important; font-variant-numeric: tabular-nums !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-cg__result-text strong { color: #fff !important; }

/* Footer */
#<?= esc_attr($wid) ?> .wxp-cg__footer { margin-top: auto !important; padding-top: 16px !important; display: flex !important; align-items: center !important; justify-content: space-between !important; gap: 12px !important; }
#<?= esc_attr($wid) ?> .wxp-cg__partners { display: flex !important; align-items: center !important; gap: 12px !important; min-width: 0 !important; flex-wrap: wrap !important; }
#<?= esc_attr($wid) ?> .wxp-cg__partner { font-family: 'DM Sans', system-ui, sans-serif !important; font-size: 11px !important; font-weight: 700 !important; color: #11325D !important; letter-spacing: 0.04em !important; text-transform: uppercase !important; line-height: 1 !important; white-space: nowrap !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-cg__partner { color: #fff !important; }
#<?= esc_attr($wid) ?> .wxp-cg__partner + .wxp-cg__partner { padding-left: 12px !important; border-left: 1px solid rgba(15,15,15,0.10) !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-cg__partner + .wxp-cg__partner { border-left-color: rgba(255,255,255,0.18) !important; }
#<?= esc_attr($wid) ?> .wxp-cg__arrow { font-family: 'DM Sans', system-ui, sans-serif !important; font-size: 16px !important; color: #5A6473 !important; font-weight: 400 !important; flex-shrink: 0 !important; transition: color 0.22s ease, transform 0.22s ease !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-cg__arrow { color: rgba(255,255,255,0.6) !important; }

@media (max-width: 1100px) {
    #<?= esc_attr($wid) ?> .wxp-cg--cols-4 .wxp-cg__grid { grid-template-columns: repeat(3, 1fr) !important; }
}
@media (max-width: 880px) {
    #<?= esc_attr($wid) ?> .wxp-cg__grid { grid-template-columns: repeat(2, 1fr) !important; gap: 16px !important; }
    #<?= esc_attr($wid) ?> .wxp-cg__header { grid-template-columns: 1fr !important; align-items: start !important; gap: 20px !important; }
    #<?= esc_attr($wid) ?> .wxp-cg__body-wrap { padding: 18px !important; }
}
@media (max-width: 520px) {
    #<?= esc_attr($wid) ?> .wxp-cg__grid { grid-template-columns: 1fr !important; }
}
    </style>
    <?php
    return ob_get_clean();
};
