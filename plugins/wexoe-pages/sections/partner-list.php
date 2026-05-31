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

    $wid = (string) ($ctx['wrapper_id'] ?? '');
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
#<?= esc_attr($wid) ?> .wxp-pl__body { margin-bottom: 32px !important; max-width: 60ch !important; }
#<?= esc_attr($wid) ?> .wxp-pl__items { list-style: none !important; padding: 0 !important; margin: 0 !important; }
#<?= esc_attr($wid) ?> .wxp-pl__item { list-style: none !important; padding: 0 !important; margin: 0 !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-pl__item::before { content: none !important; display: none !important; }
#<?= esc_attr($wid) ?> .wxp-pl__logo { max-height: 56px !important; max-width: 140px !important; width: auto !important; height: auto !important; object-fit: contain !important; display: block !important; }
/* variant: marquee */
#<?= esc_attr($wid) ?> .wxp-pl--marquee .wxp-pl__items { display: flex !important; flex-wrap: wrap !important; gap: 44px !important; align-items: center !important; justify-content: center !important; }
#<?= esc_attr($wid) ?> .wxp-pl--marquee .wxp-pl__tile { display: block !important; opacity: 0.65 !important; filter: grayscale(1) !important; transition: opacity 0.2s ease, filter 0.2s ease !important; }
#<?= esc_attr($wid) ?> .wxp-pl--marquee .wxp-pl__tile:hover { opacity: 1 !important; filter: none !important; }
/* variant: grid */
#<?= esc_attr($wid) ?> .wxp-pl--grid .wxp-pl__items { display: grid !important; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)) !important; gap: 16px !important; }
#<?= esc_attr($wid) ?> .wxp-pl--grid .wxp-pl__item { display: flex !important; flex-direction: column !important; align-items: center !important; justify-content: center !important; padding: 28px 18px !important; background: #fff !important; border: 1px solid rgba(15,15,15,0.10) !important; border-radius: 2px !important; min-height: 120px !important; transition: border-color 0.2s ease, transform 0.2s ease !important; }
#<?= esc_attr($wid) ?> .wxp-pl--grid .wxp-pl__item:hover { border-color: rgba(17,50,93,0.22) !important; transform: translateY(-2px) !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-pl--grid .wxp-pl__item { background: rgba(255,255,255,0.04) !important; border-color: rgba(255,255,255,0.10) !important; }
#<?= esc_attr($wid) ?> .wxp-pl--grid .wxp-pl__tile { display: flex !important; align-items: center !important; justify-content: center !important; min-height: 56px !important; }
#<?= esc_attr($wid) ?> .wxp-pl__name--below { font-size: 13px !important; opacity: 0.78 !important; margin: 12px 0 0 !important; padding: 0 !important; text-align: center !important; color: inherit !important; background: none !important; }
/* variant: list */
#<?= esc_attr($wid) ?> .wxp-pl--list .wxp-pl__items { display: flex !important; flex-direction: column !important; gap: 10px !important; }
#<?= esc_attr($wid) ?> .wxp-pl__list-row { display: grid !important; grid-template-columns: auto 1fr auto !important; gap: 20px !important; align-items: center !important; padding: 18px 22px !important; background: #fff !important; border: 1px solid rgba(15,15,15,0.10) !important; border-radius: 2px !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-pl__list-row { background: rgba(255,255,255,0.04) !important; border-color: rgba(255,255,255,0.10) !important; }
#<?= esc_attr($wid) ?> .wxp-pl__list-meta { min-width: 0 !important; }
#<?= esc_attr($wid) ?> .wxp-pl__list-meta .wxp-pl__name { font-family: 'DM Sans', system-ui, sans-serif !important; font-weight: 700 !important; font-size: 15px !important; margin: 0 !important; padding: 0 !important; color: #11325D !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-pl__list-meta .wxp-pl__name { color: #fff !important; }
#<?= esc_attr($wid) ?> .wxp-pl__desc { font-size: 13px !important; opacity: 0.78 !important; margin: 4px 0 0 !important; padding: 0 !important; color: inherit !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-pl__link { text-decoration: none !important; color: #11325D !important; font-weight: 600 !important; font-size: 13px !important; white-space: nowrap !important; padding: 8px 14px !important; border-radius: 2px !important; background: rgba(17,50,93,0.06) !important; transition: background 0.2s ease !important; }
#<?= esc_attr($wid) ?> .wxp-pl__link:hover { background: rgba(17,50,93,0.12) !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-pl__link { color: #fff !important; background: rgba(255,255,255,0.10) !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-pl__link:hover { background: rgba(255,255,255,0.18) !important; }
@media (max-width: 600px) {
    #<?= esc_attr($wid) ?> .wxp-pl__list-row { grid-template-columns: 1fr !important; text-align: left !important; }
}
    </style>
    <?php
    return ob_get_clean();
};
