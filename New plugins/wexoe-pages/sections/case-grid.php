<?php
/**
 * Section: case_grid (section_type = "case_grid")
 *
 * Grid med case-kort. Datakälla: cms_case_pages (entity 'case_pages').
 * Pin-then-scope: cg_case_manual_ids först, fyll på via scope (country,
 * division, customer_type) upp till cg_limit. cg_columns styr grid-bredd.
 *
 * Korten använder card_*-fält från case_pages-recordet. Länkar till
 * legacy_external_url om satt, annars / + slug.
 */

if (!defined('ABSPATH')) exit;

return function ($section, $page, $ctx) {
    $eyebrow = (string) ($section['cg_eyebrow'] ?? '');
    $h2      = (string) ($section['cg_h2']      ?? '');
    $body    = (string) ($section['cg_body']    ?? '');
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
            // Filtrera mot scope. case_pages har country_ids + customer_type_ids
            // (men inte division_ids — så scope.division är no-op här).
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

    $wid = (string) ($ctx['wrapper_id'] ?? '');
    $attrs = wexoe_pages_section_attrs($section, $ctx, 'wxp-cg wxp-cg--cols-' . $columns);
    ob_start();
    ?>
    <section <?= $attrs ?>>
        <div class="wxp-section__inner">
            <div class="wxp-cg__header">
                <?php if ($eyebrow !== ''): ?><p class="wxp-eyebrow"><?= esc_html($eyebrow) ?></p><?php endif; ?>
                <?php if ($h2 !== ''): ?><h2 class="wxp-h2"><?= esc_html($h2) ?></h2><?php endif; ?>
                <?php if ($body !== ''): ?><div class="wxp-body wxp-cg__body"><?= wexoe_pages_md($body) ?></div><?php endif; ?>
            </div>
            <?php if (!empty($cases)): ?>
                <ul class="wxp-cg__grid">
                    <?php foreach ($cases as $case):
                        $title = (string) ($case['card_title'] ?? $case['h1'] ?? $case['slug'] ?? '');
                        if ($title === '') continue;
                        $result = (string) ($case['card_result'] ?? '');
                        $image = (string) ($case['card_image_url'] ?? '');
                        $cta_text = (string) ($case['card_cta_text'] ?? '') ?: 'Läs caset';
                        $href = !empty($case['legacy_external_url']) ? (string) $case['legacy_external_url'] : ('/' . ($case['slug'] ?? ''));
                        // Första partner från partner_ids (om finns).
                        $partner_name = '';
                        $partner_logo = '';
                        if (!empty($case['partner_ids']) && is_array($case['partner_ids'])) {
                            foreach ($case['partner_ids'] as $pid) {
                                if (isset($partner_by_id[$pid])) {
                                    $p = $partner_by_id[$pid];
                                    $partner_name = (string) ($p['name'] ?? '');
                                    $partner_logo = (string) ($p['logo_transparent_url'] ?? $p['logo_url'] ?? '');
                                    if ($partner_name !== '' || $partner_logo !== '') break;
                                }
                            }
                        }
                    ?>
                        <li class="wxp-cg__item">
                            <a class="wxp-cg__card" href="<?= esc_url($href) ?>">
                                <?php if ($image !== ''): ?>
                                    <div class="wxp-cg__image-wrap"><img src="<?= esc_url($image) ?>" alt="<?= esc_attr($title) ?>" class="wxp-cg__image" loading="lazy" /></div>
                                <?php endif; ?>
                                <div class="wxp-cg__body-wrap">
                                    <h3 class="wxp-cg__title"><?= esc_html($title) ?></h3>
                                    <?php if ($result !== ''): ?>
                                        <p class="wxp-cg__result"><?= esc_html($result) ?></p>
                                    <?php endif; ?>
                                    <?php if ($partner_name !== '' || $partner_logo !== ''): ?>
                                        <div class="wxp-cg__partner">
                                            <?php if ($partner_logo !== ''): ?>
                                                <img class="wxp-cg__partner-logo" src="<?= esc_url($partner_logo) ?>" alt="<?= esc_attr($partner_name) ?>" loading="lazy" />
                                            <?php endif; ?>
                                            <?php if ($partner_name !== ''): ?>
                                                <span class="wxp-cg__partner-name">Med <?= esc_html($partner_name) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <span class="wxp-cg__cta"><?= esc_html($cta_text) ?> <span aria-hidden="true">→</span></span>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>
    <style>
#<?= esc_attr($wid) ?> .wxp-cg__header { max-width: 720px !important; margin-bottom: 32px !important; }
#<?= esc_attr($wid) ?> .wxp-cg__body { margin-top: 12px !important; max-width: 60ch !important; }
#<?= esc_attr($wid) ?> .wxp-cg__grid { list-style: none !important; padding: 0 !important; margin: 0 !important; display: grid !important; gap: 20px !important; }
#<?= esc_attr($wid) ?> .wxp-cg--cols-2 .wxp-cg__grid { grid-template-columns: repeat(2, 1fr) !important; }
#<?= esc_attr($wid) ?> .wxp-cg--cols-3 .wxp-cg__grid { grid-template-columns: repeat(3, 1fr) !important; }
#<?= esc_attr($wid) ?> .wxp-cg--cols-4 .wxp-cg__grid { grid-template-columns: repeat(4, 1fr) !important; }
#<?= esc_attr($wid) ?> .wxp-cg__item { display: flex !important; list-style: none !important; padding: 0 !important; margin: 0 !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-cg__item::before { content: none !important; display: none !important; }
#<?= esc_attr($wid) ?> .wxp-cg__card { display: flex !important; flex-direction: column !important; background: #fff !important; border-radius: 12px !important; overflow: hidden !important; text-decoration: none !important; color: #1A1A1A !important; border: 1px solid rgba(17,50,93,0.08) !important; box-shadow: 0 4px 12px rgba(10,26,46,0.05) !important; transition: transform 0.2s ease, box-shadow 0.2s ease !important; width: 100% !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-cg__card { background: rgba(255,255,255,0.04) !important; color: #fff !important; border-color: rgba(255,255,255,0.1) !important; box-shadow: none !important; }
#<?= esc_attr($wid) ?> .wxp-cg__card:hover { transform: translateY(-3px) !important; box-shadow: 0 12px 28px rgba(10,26,46,0.10) !important; color: #1A1A1A !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-cg__card:hover { color: #fff !important; }
#<?= esc_attr($wid) ?> .wxp-cg__image-wrap { aspect-ratio: 16 / 10 !important; overflow: hidden !important; background: linear-gradient(135deg, #11325D, #2d6a9f) !important; }
#<?= esc_attr($wid) ?> .wxp-cg__image { width: 100% !important; height: 100% !important; object-fit: cover !important; display: block !important; }
#<?= esc_attr($wid) ?> .wxp-cg__body-wrap { padding: 18px 20px 18px !important; display: flex !important; flex-direction: column !important; gap: 10px !important; flex: 1 !important; }
#<?= esc_attr($wid) ?> .wxp-cg__title { font-family: 'DM Sans', system-ui, sans-serif !important; font-size: 16px !important; margin: 0 !important; padding: 0 !important; font-weight: 700 !important; line-height: 1.3 !important; color: #11325D !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-cg__title { color: #fff !important; }
#<?= esc_attr($wid) ?> .wxp-cg__result { font-size: 12.5px !important; margin: 0 !important; padding: 7px 12px !important; background: rgba(16,163,74,0.10) !important; color: #15803D !important; border-radius: 6px !important; align-self: flex-start !important; font-weight: 600 !important; line-height: 1.4 !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-cg__result { background: rgba(34,197,94,0.18) !important; color: #4ADE80 !important; }
#<?= esc_attr($wid) ?> .wxp-cg__partner { display: flex !important; align-items: center !important; gap: 10px !important; padding-top: 10px !important; border-top: 1px solid rgba(17,50,93,0.08) !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-cg__partner { border-top-color: rgba(255,255,255,0.10) !important; }
#<?= esc_attr($wid) ?> .wxp-cg__partner-logo { max-height: 28px !important; max-width: 80px !important; width: auto !important; height: auto !important; object-fit: contain !important; display: block !important; opacity: 0.85 !important; }
#<?= esc_attr($wid) ?> .wxp-cg__partner-name { font-size: 12px !important; opacity: 0.72 !important; line-height: 1.3 !important; color: inherit !important; }
#<?= esc_attr($wid) ?> .wxp-cg__cta { font-size: 13px !important; font-weight: 600 !important; color: #F28C28 !important; margin-top: auto !important; padding-top: 4px !important; }
@media (max-width: 900px) {
    #<?= esc_attr($wid) ?> .wxp-cg--cols-3 .wxp-cg__grid, #<?= esc_attr($wid) ?> .wxp-cg--cols-4 .wxp-cg__grid { grid-template-columns: repeat(2, 1fr) !important; }
}
@media (max-width: 600px) {
    #<?= esc_attr($wid) ?> .wxp-cg__grid { grid-template-columns: 1fr !important; }
}
    </style>
    <?php
    return ob_get_clean();
};

/* --------------------------------------------------------
   Inline helpers (lazy declared — har samma namn så declaration
   sker bara en gång oavsett hur många case_grid-sektioner sidan har).
   -------------------------------------------------------- */
