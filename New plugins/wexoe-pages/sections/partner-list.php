<?php
/**
 * Section: partner_list (section_type = "partner_list")
 *
 * Lista av leverantörer/partners i tre varianter:
 *   marquee — horisontell strip med logotyper (grayscale, hover-fade-in)
 *   grid    — rutnät med logotyper + namn
 *   list    — full lista med logo + namn + beskrivning + URL
 *
 * Datakälla: core_partners via Collections + pin-then-scope.
 */

if (!defined('ABSPATH')) exit;

return function ($section, $page, $ctx) {
    $eyebrow = (string) ($section['pl_eyebrow'] ?? '');
    $h2      = (string) ($section['pl_h2']      ?? '');
    $body    = (string) ($section['pl_body']    ?? '');
    $variant = in_array(($section['pl_variant'] ?? ''), ['marquee', 'grid', 'list'], true) ? $section['pl_variant'] : 'marquee';
    $limit   = max(0, (int) ($section['pl_limit'] ?? 0));
    $manual_ids = is_array($section['pl_partner_manual_ids'] ?? null) ? $section['pl_partner_manual_ids'] : [];

    $scope = wexoe_pages_resolve_scope($section, $ctx, [
        'country'  => 'pl_scope_country',
        'division' => 'pl_scope_division',
    ]);

    $partners = wexoe_pages_pin_then_scope(
        $manual_ids,
        'core_partners',
        function () use ($scope) {
            if (!class_exists('\\Wexoe\\Core\\Helpers\\Collections')) return [];
            return \Wexoe\Core\Helpers\Collections::partners_for_scope($scope);
        },
        $limit
    );

    if (empty($partners) && $h2 === '') return '';

    $attrs = wexoe_pages_section_attrs($section, $ctx, 'wxp-pl wxp-pl--' . $variant);
    ob_start();
    ?>
    <section <?= $attrs ?>>
        <div class="wxp-section__inner">
            <?php if ($eyebrow !== ''): ?><p class="wxp-eyebrow"><?= esc_html($eyebrow) ?></p><?php endif; ?>
            <?php if ($h2 !== ''): ?><h2 class="wxp-h2"><?= esc_html($h2) ?></h2><?php endif; ?>
            <?php if ($body !== ''): ?><div class="wxp-body wxp-pl__body"><?= wexoe_pages_md($body) ?></div><?php endif; ?>
            <?php if (!empty($partners)): ?>
                <ul class="wxp-pl__items">
                    <?php foreach ($partners as $p):
                        $name = (string) ($p['name'] ?? '');
                        $logo = (string) ($p['logo_transparent_url'] ?? $p['logo_url'] ?? '');
                        $url = (string) ($p['url'] ?? '');
                        $desc = (string) ($p['description'] ?? '');
                        if ($name === '' && $logo === '') continue;
                    ?>
                        <li class="wxp-pl__item">
                            <?php if ($variant === 'list'): ?>
                                <div class="wxp-pl__list-row">
                                    <?php if ($logo !== ''): ?><img class="wxp-pl__logo" src="<?= esc_url($logo) ?>" alt="<?= esc_attr($name) ?>" loading="lazy" /><?php endif; ?>
                                    <div class="wxp-pl__list-meta">
                                        <p class="wxp-pl__name"><?= esc_html($name) ?></p>
                                        <?php if ($desc !== ''): ?><p class="wxp-pl__desc"><?= esc_html(wp_trim_words($desc, 22)) ?></p><?php endif; ?>
                                    </div>
                                    <?php if ($url !== ''): ?><a class="wxp-pl__link" href="<?= esc_url($url) ?>" target="_blank" rel="noopener">Besök <span aria-hidden="true">↗</span></a><?php endif; ?>
                                </div>
                            <?php elseif ($logo !== ''): ?>
                                <?php if ($url !== ''): ?>
                                    <a class="wxp-pl__tile" href="<?= esc_url($url) ?>" target="_blank" rel="noopener" title="<?= esc_attr($name) ?>"><img class="wxp-pl__logo" src="<?= esc_url($logo) ?>" alt="<?= esc_attr($name) ?>" loading="lazy" /></a>
                                <?php else: ?>
                                    <span class="wxp-pl__tile"><img class="wxp-pl__logo" src="<?= esc_url($logo) ?>" alt="<?= esc_attr($name) ?>" loading="lazy" /></span>
                                <?php endif; ?>
                                <?php if ($variant === 'grid' && $name !== ''): ?>
                                    <p class="wxp-pl__name wxp-pl__name--below"><?= esc_html($name) ?></p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="wxp-pl__name"><?= esc_html($name) ?></p>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>
    <style>
.wxp-pl__body { margin-bottom: 24px; max-width: 60ch; }
.wxp-pl__items { list-style: none; padding: 0; margin: 0; }
.wxp-pl__logo { max-height: 56px; max-width: 140px; width: auto; height: auto; object-fit: contain; display: block; }
/* variant: marquee */
.wxp-pl--marquee .wxp-pl__items { display: flex; flex-wrap: wrap; gap: 36px; align-items: center; justify-content: center; }
.wxp-pl--marquee .wxp-pl__tile { display: block; opacity: 0.6; filter: grayscale(1); transition: opacity 0.2s, filter 0.2s; }
.wxp-pl--marquee .wxp-pl__tile:hover { opacity: 1; filter: none; }
/* variant: grid */
.wxp-pl--grid .wxp-pl__items { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 24px; }
.wxp-pl--grid .wxp-pl__item { display: flex; flex-direction: column; align-items: center; padding: 20px; border: 1px solid rgba(0,0,0,0.08); border-radius: 8px; }
.wxp-section--theme-dark .wxp-pl--grid .wxp-pl__item { border-color: rgba(255,255,255,0.1); }
.wxp-pl--grid .wxp-pl__tile { display: block; min-height: 56px; display: flex; align-items: center; justify-content: center; }
.wxp-pl__name--below { font-size: 12px; opacity: 0.7; margin: 12px 0 0; text-align: center; }
/* variant: list */
.wxp-pl--list .wxp-pl__items { display: flex; flex-direction: column; gap: 12px; }
.wxp-pl__list-row { display: grid; grid-template-columns: auto 1fr auto; gap: 20px; align-items: center; padding: 16px; background: rgba(0,0,0,0.02); border-radius: 10px; }
.wxp-section--theme-dark .wxp-pl__list-row { background: rgba(255,255,255,0.04); }
.wxp-pl__list-meta .wxp-pl__name { font-weight: 600; margin: 0; }
.wxp-pl__desc { font-size: 13px; opacity: 0.75; margin: 4px 0 0; }
.wxp-pl__link { text-decoration: none; color: #11325D; font-weight: 500; font-size: 13px; white-space: nowrap; }
.wxp-section--theme-dark .wxp-pl__link { color: #F28C28; }
@media (max-width: 600px) {
    .wxp-pl__list-row { grid-template-columns: 1fr; text-align: left; }
}
    </style>
    <?php
    return ob_get_clean();
};
