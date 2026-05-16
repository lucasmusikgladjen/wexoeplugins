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

    $attrs = wexoe_pages_section_attrs($section, $ctx, 'wxp-cg wxp-cg--cols-' . $columns);
    ob_start();
    ?>
    <section <?= $attrs ?>>
        <div class="wxp-section__inner">
            <?php if ($eyebrow !== ''): ?><p class="wxp-eyebrow"><?= esc_html($eyebrow) ?></p><?php endif; ?>
            <?php if ($h2 !== ''): ?><h2 class="wxp-h2"><?= esc_html($h2) ?></h2><?php endif; ?>
            <?php if ($body !== ''): ?><div class="wxp-body wxp-cg__body"><?= wexoe_pages_md($body) ?></div><?php endif; ?>
            <?php if (!empty($cases)): ?>
                <ul class="wxp-cg__grid">
                    <?php foreach ($cases as $case):
                        $title = (string) ($case['card_title'] ?? $case['h1'] ?? $case['slug'] ?? '');
                        if ($title === '') continue;
                        $description = (string) ($case['card_description'] ?? '');
                        $result = (string) ($case['card_result'] ?? '');
                        $image = (string) ($case['card_image_url'] ?? '');
                        $cta_text = (string) ($case['card_cta_text'] ?? 'Läs casen');
                        $href = !empty($case['legacy_external_url']) ? (string) $case['legacy_external_url'] : ('/' . ($case['slug'] ?? ''));
                    ?>
                        <li class="wxp-cg__item">
                            <a class="wxp-cg__card" href="<?= esc_url($href) ?>">
                                <?php if ($image !== ''): ?>
                                    <div class="wxp-cg__image-wrap"><img src="<?= esc_url($image) ?>" alt="<?= esc_attr($title) ?>" class="wxp-cg__image" loading="lazy" /></div>
                                <?php endif; ?>
                                <div class="wxp-cg__body-wrap">
                                    <h3 class="wxp-cg__title"><?= esc_html($title) ?></h3>
                                    <?php if ($description !== ''): ?><p class="wxp-cg__desc"><?= esc_html(wp_trim_words($description, 26)) ?></p><?php endif; ?>
                                    <?php if ($result !== ''): ?>
                                        <p class="wxp-cg__result"><span class="wxp-cg__result-label">Resultat:</span> <?= esc_html($result) ?></p>
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
.wxp-cg__body { margin-bottom: 32px; max-width: 60ch; }
.wxp-cg__grid { list-style: none; padding: 0; margin: 0; display: grid; gap: 24px; }
.wxp-cg--cols-2 .wxp-cg__grid { grid-template-columns: repeat(2, 1fr); }
.wxp-cg--cols-3 .wxp-cg__grid { grid-template-columns: repeat(3, 1fr); }
.wxp-cg--cols-4 .wxp-cg__grid { grid-template-columns: repeat(4, 1fr); }
.wxp-cg__item { display: flex; }
.wxp-cg__card { display: flex; flex-direction: column; background: #fff; border-radius: 12px; overflow: hidden; text-decoration: none; color: #1A1A1A; box-shadow: 0 1px 4px rgba(0,0,0,0.06); transition: transform 0.2s, box-shadow 0.2s; width: 100%; }
.wxp-section--theme-dark .wxp-cg__card { background: rgba(255,255,255,0.04); color: #fff; box-shadow: none; border: 1px solid rgba(255,255,255,0.1); }
.wxp-cg__card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); color: #1A1A1A; }
.wxp-section--theme-dark .wxp-cg__card:hover { color: #fff; box-shadow: 0 8px 20px rgba(0,0,0,0.3); }
.wxp-cg__image-wrap { aspect-ratio: 16/9; overflow: hidden; background: #F5F6F8; }
.wxp-cg__image { width: 100%; height: 100%; object-fit: cover; display: block; }
.wxp-cg__body-wrap { padding: 20px; display: flex; flex-direction: column; gap: 8px; flex: 1; }
.wxp-cg__title { font-size: 18px; margin: 0; font-weight: 600; line-height: 1.3; }
.wxp-cg__desc { font-size: 14px; line-height: 1.5; opacity: 0.85; margin: 0; }
.wxp-cg__result { font-size: 13px; margin: 0; padding: 8px 12px; background: rgba(16,163,74,0.1); color: #16A34A; border-radius: 6px; }
.wxp-section--theme-dark .wxp-cg__result { background: rgba(22,163,74,0.15); color: #4ADE80; }
.wxp-cg__result-label { font-weight: 600; }
.wxp-cg__cta { font-size: 14px; font-weight: 500; color: #11325D; margin-top: auto; padding-top: 4px; }
.wxp-section--theme-dark .wxp-cg__cta { color: #F28C28; }
@media (max-width: 900px) {
    .wxp-cg--cols-3 .wxp-cg__grid, .wxp-cg--cols-4 .wxp-cg__grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 600px) {
    .wxp-cg__grid { grid-template-columns: 1fr !important; }
}
    </style>
    <?php
    return ob_get_clean();
};

/* --------------------------------------------------------
   Inline helpers (lazy declared — har samma namn så declaration
   sker bara en gång oavsett hur många case_grid-sektioner sidan har).
   -------------------------------------------------------- */
