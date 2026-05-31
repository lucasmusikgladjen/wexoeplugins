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

    $wid = (string) ($ctx['wrapper_id'] ?? '');
    $attrs = wexoe_pages_section_attrs($section, $ctx, 'wxp-tg wxp-tg--' . $variant);
    ob_start();
    ?>
    <section <?= $attrs ?>>
        <div class="wxp-section__inner">
            <?php if ($eyebrow !== '' || $h2 !== '' || $body !== ''): ?>
                <div class="wxp-tg__header">
                    <?php if ($eyebrow !== ''): ?><p class="wxp-eyebrow"><?= esc_html($eyebrow) ?></p><?php endif; ?>
                    <?php if ($h2 !== ''): ?><h2 class="wxp-h2"><?= esc_html($h2) ?></h2><?php endif; ?>
                    <?php if ($body !== ''): ?><div class="wxp-body wxp-tg__body"><?= wexoe_pages_md($body) ?></div><?php endif; ?>
                </div>
            <?php endif; ?>
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
                                        <div class="wxp-tg__contact">
                                            <?php if ($email !== ''): ?><a href="mailto:<?= esc_attr($email) ?>"><?= esc_html($email) ?></a><?php endif; ?>
                                            <?php if ($phone !== ''): ?><a href="tel:<?= esc_attr(preg_replace('/\s+/', '', $phone)) ?>"><?= esc_html($phone) ?></a><?php endif; ?>
                                        </div>
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
#<?= esc_attr($wid) ?> .wxp-tg__header { max-width: 720px !important; margin-bottom: 36px !important; }
#<?= esc_attr($wid) ?> .wxp-tg__body { margin-top: 12px !important; }
#<?= esc_attr($wid) ?> .wxp-tg__grid { list-style: none !important; padding: 0 !important; margin: 0 !important; }
#<?= esc_attr($wid) ?> .wxp-tg__item { list-style: none !important; padding: 0 !important; margin: 0 !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-tg__item::before { content: none !important; display: none !important; }
#<?= esc_attr($wid) ?> .wxp-tg__photo { display: block !important; object-fit: cover !important; background: linear-gradient(135deg, #11325D, #2d6a9f) !important; color: #fff !important; font-weight: 700 !important; text-align: center !important; }
#<?= esc_attr($wid) ?> .wxp-tg__photo--initials { display: flex !important; align-items: center !important; justify-content: center !important; letter-spacing: 0.04em !important; }
#<?= esc_attr($wid) ?> .wxp-tg__meta { min-width: 0 !important; }
#<?= esc_attr($wid) ?> .wxp-tg__name { font-family: 'DM Sans', system-ui, sans-serif !important; font-weight: 700 !important; font-size: 16px !important; margin: 0 !important; padding: 0 !important; color: #11325D !important; line-height: 1.3 !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-tg__name { color: #fff !important; }
#<?= esc_attr($wid) ?> .wxp-tg__title { font-size: 13px !important; opacity: 0.72 !important; margin: 4px 0 0 !important; padding: 0 !important; color: inherit !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-tg__contact { margin: 10px 0 0 !important; padding: 0 !important; display: flex !important; flex-direction: column !important; gap: 4px !important; }
#<?= esc_attr($wid) ?> .wxp-tg__contact a { color: #11325D !important; text-decoration: none !important; font-size: 13px !important; padding: 0 !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-tg__contact a:hover { color: #F28C28 !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-tg__contact a { color: rgba(255,255,255,0.85) !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-tg__contact a:hover { color: #F28C28 !important; }

/* variant: cards — skarpa 2px-kort med orange hover-top-accent + divider mellan
   titel och kontaktinfo. */
#<?= esc_attr($wid) ?> .wxp-tg--cards .wxp-tg__grid { display: grid !important; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)) !important; gap: 24px !important; }
#<?= esc_attr($wid) ?> .wxp-tg--cards .wxp-tg__item { position: relative !important; display: flex !important; flex-direction: column !important; text-align: center !important; align-items: center !important; background: #fff !important; border: 1px solid rgba(15,15,15,0.10) !important; border-radius: 2px !important; padding: 28px 20px !important; box-shadow: none !important; transition: border-color 0.22s ease, transform 0.22s ease, box-shadow 0.22s ease !important; }
/* Orange hover-accent: 2px top-edge via ::before — sitter ovanpå border, byter
   bara på hover. */
#<?= esc_attr($wid) ?> .wxp-tg--cards .wxp-tg__item::before { content: '' !important; position: absolute !important; top: -1px !important; left: -1px !important; right: -1px !important; height: 2px !important; background: #F28C28 !important; opacity: 0 !important; transition: opacity 0.22s ease !important; pointer-events: none !important; }
#<?= esc_attr($wid) ?> .wxp-tg--cards .wxp-tg__item:hover { transform: translateY(-2px) !important; border-color: rgba(15,15,15,0.18) !important; box-shadow: 0 12px 28px rgba(10,26,46,0.06) !important; }
#<?= esc_attr($wid) ?> .wxp-tg--cards .wxp-tg__item:hover::before { opacity: 1 !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-tg--cards .wxp-tg__item { background: rgba(255,255,255,0.04) !important; border-color: rgba(255,255,255,0.12) !important; box-shadow: none !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-tg--cards .wxp-tg__item:hover { border-color: rgba(255,255,255,0.28) !important; }
#<?= esc_attr($wid) ?> .wxp-tg--cards .wxp-tg__photo { width: 110px !important; height: 110px !important; border-radius: 50% !important; margin: 0 0 16px !important; font-size: 30px !important; }
/* Tunn divider mellan titel och kontakt-info */
#<?= esc_attr($wid) ?> .wxp-tg--cards .wxp-tg__contact { margin-top: 16px !important; padding-top: 16px !important; border-top: 1px solid rgba(15,15,15,0.10) !important; width: 100% !important; align-items: center !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-tg--cards .wxp-tg__contact { border-top-color: rgba(255,255,255,0.14) !important; }

/* variant: rack */
#<?= esc_attr($wid) ?> .wxp-tg--rack .wxp-tg__grid { display: grid !important; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)) !important; gap: 14px !important; }
#<?= esc_attr($wid) ?> .wxp-tg--rack .wxp-tg__item { display: flex !important; flex-direction: row !important; gap: 16px !important; align-items: center !important; padding: 16px !important; background: #fff !important; border: 1px solid rgba(15,15,15,0.10) !important; border-radius: 2px !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-tg--rack .wxp-tg__item { background: rgba(255,255,255,0.04) !important; border-color: rgba(255,255,255,0.10) !important; }
#<?= esc_attr($wid) ?> .wxp-tg--rack .wxp-tg__photo { width: 64px !important; height: 64px !important; border-radius: 50% !important; font-size: 20px !important; flex-shrink: 0 !important; }
#<?= esc_attr($wid) ?> .wxp-tg--rack .wxp-tg__meta { flex: 1 !important; }

/* variant: compact */
#<?= esc_attr($wid) ?> .wxp-tg--compact .wxp-tg__grid { display: flex !important; flex-wrap: wrap !important; gap: 8px !important; }
#<?= esc_attr($wid) ?> .wxp-tg--compact .wxp-tg__item { display: flex !important; flex-direction: row !important; gap: 8px !important; align-items: center !important; padding: 4px 14px 4px 4px !important; background: rgba(17,50,93,0.06) !important; border-radius: 999px !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-tg--compact .wxp-tg__item { background: rgba(255,255,255,0.08) !important; }
#<?= esc_attr($wid) ?> .wxp-tg--compact .wxp-tg__photo { width: 32px !important; height: 32px !important; border-radius: 50% !important; font-size: 11px !important; flex-shrink: 0 !important; }
#<?= esc_attr($wid) ?> .wxp-tg--compact .wxp-tg__name { font-size: 13px !important; }
#<?= esc_attr($wid) ?> .wxp-tg--compact .wxp-tg__title { display: none !important; }
    </style>
    <?php
    return ob_get_clean();
};
