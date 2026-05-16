<?php
/**
 * Section: team_grid (section_type = "team_grid")
 *
 * Medarbetar-rutnät, tre varianter via tg_variant:
 *   cards   — stora rundade kort med foto, namn, titel, kontaktinfo
 *   rack    — kompakt rad-baserad layout (foto, namn, titel, kontaktrad)
 *   compact — minimal med initialer i cirkel (för "vårt team i siffror")
 *
 * Datakälla: core_coworkers via Collections::coworkers_for_scope() + pin-then-scope.
 */

if (!defined('ABSPATH')) exit;

/**
 * Helpers — declarerade FÖRE `return function`-statementen eftersom `require`
 * returnerar vid statementen och allt nedanför aldrig körs. function_exists-
 * guard så att flera require av samma fil inte krockar (loader cachar dock,
 * så detta är belt-and-suspenders).
 */
if (!function_exists('wxp_initials')) {
    function wxp_initials($name) {
        $parts = preg_split('/\s+/', trim((string) $name));
        $initials = '';
        foreach ($parts as $p) {
            if ($p !== '') $initials .= mb_substr($p, 0, 1);
            if (mb_strlen($initials) >= 2) break;
        }
        return mb_strtoupper($initials);
    }
}

return function ($section, $page, $ctx) {
    $eyebrow = (string) ($section['tg_eyebrow'] ?? '');
    $h2      = (string) ($section['tg_h2']      ?? '');
    $body    = (string) ($section['tg_body']    ?? '');
    $variant = in_array(($section['tg_variant'] ?? ''), ['cards', 'rack', 'compact'], true) ? $section['tg_variant'] : 'cards';
    $limit   = max(0, (int) ($section['tg_limit'] ?? 0));
    $manual_ids = is_array($section['tg_coworker_manual_ids'] ?? null) ? $section['tg_coworker_manual_ids'] : [];

    $scope = wexoe_pages_resolve_scope($section, $ctx, [
        'country'  => 'tg_scope_country',
        'division' => 'tg_scope_division',
    ]);

    $coworkers = wexoe_pages_pin_then_scope(
        $manual_ids,
        'core_coworkers',
        function () use ($scope) {
            if (!class_exists('\\Wexoe\\Core\\Helpers\\Collections')) return [];
            return \Wexoe\Core\Helpers\Collections::coworkers_for_scope($scope);
        },
        $limit
    );

    if (empty($coworkers) && $h2 === '') return '';

    $attrs = wexoe_pages_section_attrs($section, $ctx, 'wxp-tg wxp-tg--' . $variant);
    ob_start();
    ?>
    <section <?= $attrs ?>>
        <div class="wxp-section__inner">
            <?php if ($eyebrow !== ''): ?><p class="wxp-eyebrow"><?= esc_html($eyebrow) ?></p><?php endif; ?>
            <?php if ($h2 !== ''): ?><h2 class="wxp-h2"><?= esc_html($h2) ?></h2><?php endif; ?>
            <?php if ($body !== ''): ?><div class="wxp-body wxp-tg__body"><?= wexoe_pages_md($body) ?></div><?php endif; ?>
            <?php if (!empty($coworkers)): ?>
                <ul class="wxp-tg__grid">
                    <?php foreach ($coworkers as $c):
                        $name = (string) ($c['full_name'] ?? '');
                        if ($name === '') continue;
                        $title = (string) ($c['title'] ?? '');
                        $email = (string) ($c['email'] ?? '');
                        $phone = (string) ($c['phone'] ?? '');
                        $image = (string) ($c['image_url'] ?? '');
                        $initials = wxp_initials($name);
                    ?>
                        <li class="wxp-tg__item">
                            <?php if ($image !== ''): ?>
                                <img class="wxp-tg__photo" src="<?= esc_url($image) ?>" alt="" loading="lazy" />
                            <?php else: ?>
                                <span class="wxp-tg__photo wxp-tg__photo--initials" aria-hidden="true"><?= esc_html($initials) ?></span>
                            <?php endif; ?>
                            <div class="wxp-tg__meta">
                                <p class="wxp-tg__name"><?= esc_html($name) ?></p>
                                <?php if ($title !== ''): ?><p class="wxp-tg__title"><?= esc_html($title) ?></p><?php endif; ?>
                                <?php if ($variant !== 'compact'): ?>
                                    <?php if ($email !== '' || $phone !== ''): ?>
                                        <p class="wxp-tg__contact">
                                            <?php if ($email !== ''): ?><a href="mailto:<?= esc_attr($email) ?>"><?= esc_html($email) ?></a><?php endif; ?>
                                            <?php if ($phone !== ''): ?><a href="tel:<?= esc_attr(preg_replace('/\s+/', '', $phone)) ?>"><?= esc_html($phone) ?></a><?php endif; ?>
                                        </p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>
    <style>
.wxp-tg__body { margin-bottom: 32px; max-width: 60ch; }
.wxp-tg__grid { list-style: none; padding: 0; margin: 0; }
.wxp-tg__item { display: flex; }
.wxp-tg__photo { display: block; object-fit: cover; background: #E5E7EB; color: #6B7280; font-weight: 600; }
.wxp-tg__photo--initials { display: flex; align-items: center; justify-content: center; }
.wxp-tg__name { font-weight: 600; margin: 0; }
.wxp-tg__title { font-size: 13px; opacity: 0.7; margin: 2px 0 0; }
.wxp-tg__contact { margin: 8px 0 0; display: flex; flex-direction: column; gap: 2px; }
.wxp-tg__contact a { color: inherit; text-decoration: none; font-size: 13px; }
.wxp-tg__contact a:hover { text-decoration: underline; }
/* variant: cards */
.wxp-tg--cards .wxp-tg__grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 24px; }
.wxp-tg--cards .wxp-tg__item { flex-direction: column; text-align: center; align-items: center; }
.wxp-tg--cards .wxp-tg__photo { width: 140px; height: 140px; border-radius: 50%; margin: 0 0 12px; font-size: 36px; }
/* variant: rack */
.wxp-tg--rack .wxp-tg__grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px; }
.wxp-tg--rack .wxp-tg__item { flex-direction: row; gap: 16px; align-items: center; padding: 12px; background: rgba(0,0,0,0.02); border-radius: 10px; }
.wxp-section--theme-dark .wxp-tg--rack .wxp-tg__item { background: rgba(255,255,255,0.04); }
.wxp-tg--rack .wxp-tg__photo { width: 64px; height: 64px; border-radius: 50%; font-size: 20px; flex-shrink: 0; }
.wxp-tg--rack .wxp-tg__meta { flex: 1; min-width: 0; }
/* variant: compact */
.wxp-tg--compact .wxp-tg__grid { display: flex; flex-wrap: wrap; gap: 8px; }
.wxp-tg--compact .wxp-tg__item { flex-direction: row; gap: 8px; align-items: center; padding: 4px 12px 4px 4px; background: rgba(0,0,0,0.04); border-radius: 999px; }
.wxp-section--theme-dark .wxp-tg--compact .wxp-tg__item { background: rgba(255,255,255,0.08); }
.wxp-tg--compact .wxp-tg__photo { width: 32px; height: 32px; border-radius: 50%; font-size: 11px; flex-shrink: 0; }
.wxp-tg--compact .wxp-tg__name { font-size: 13px; }
.wxp-tg--compact .wxp-tg__title { display: none; }
    </style>
    <?php
    return ob_get_clean();
};
