<?php
/**
 * Section: company_data_strip (section_type = "company_data_strip")
 *
 * "Wexoe i siffror"-strip. Navy-bg by default, öppen layout utan per-stat-
 * cards. Layout: ikon + flerradig titel (auto), 1px vertikal divider, siffer-
 * grid (1fr). Stora vita siffror med orange suffix som unit-accent
 * (Mkr, %, +, år).
 *
 * Datakällor (samma som tidigare):
 *   1. cds_use_company_singleton=true → core_company för cds_country_code
 *      eller sidans country. Items byggs från company-fält (org_number,
 *      email, phone, address).
 *   2. cds_items (lines) — "Label | Value | Suffix" per rad.
 *
 * Ny ikon: hämtas från core_graphic_profile.icon_light_url för sidans
 * division (eller default-profilen). Tom URL → ikon-slot rendreras inte.
 */

if (!defined('ABSPATH')) exit;

return function ($section, $page, $ctx) {
    $h2   = (string) ($section['cds_h2']   ?? '');
    $body = (string) ($section['cds_body'] ?? '');
    $items = [];

    if (!empty($section['cds_use_company_singleton'])) {
        $country_code = (string) ($section['cds_country_code'] ?? '');
        if ($country_code === '' && !empty($ctx['page_country_code'])) {
            $country_code = (string) $ctx['page_country_code'];
        }
        $company = null;
        if (class_exists('\\Wexoe\\Core\\Helpers\\Singletons')) {
            $company = \Wexoe\Core\Helpers\Singletons::company_for_country($country_code);
        }
        if (is_array($company)) {
            if (!empty($company['company_name'])) $items[] = ['label' => 'Företag', 'value' => $company['company_name'], 'suffix' => ''];
            if (!empty($company['org_number']))   $items[] = ['label' => 'Org.nr',  'value' => $company['org_number'],   'suffix' => ''];
            if (!empty($company['vat_number']))   $items[] = ['label' => 'VAT',     'value' => $company['vat_number'],   'suffix' => ''];
            if (!empty($company['phone']))        $items[] = ['label' => 'Telefon', 'value' => $company['phone'],        'suffix' => ''];
            if (!empty($company['email']))        $items[] = ['label' => 'E-post',  'value' => $company['email'],        'suffix' => ''];
            if (!empty($company['address_line_1'])) {
                $addr = (string) $company['address_line_1'];
                if (!empty($company['address_postal_code']) || !empty($company['address_city'])) {
                    $addr .= ', ' . trim(($company['address_postal_code'] ?? '') . ' ' . ($company['address_city'] ?? ''));
                }
                $items[] = ['label' => 'Adress', 'value' => $addr, 'suffix' => ''];
            }
        }
    } else {
        $raw = is_array($section['cds_items'] ?? null) ? $section['cds_items'] : [];
        foreach ($raw as $line) {
            if (!is_string($line)) continue;
            $parts = array_map('trim', explode('|', $line));
            $label = $parts[0] ?? '';
            $value = $parts[1] ?? '';
            $suffix = $parts[2] ?? '';
            if ($label === '' && $value === '') continue;
            $items[] = ['label' => $label, 'value' => $value, 'suffix' => $suffix];
        }
    }

    if (empty($items)) return '';

    // Ikon: graphic_profile.icon_light_url för sidans division (eller default).
    $icon_url = '';
    if (class_exists('\\Wexoe\\Core\\Helpers\\Singletons')) {
        $division = !empty($ctx['page_division_slug']) ? $ctx['page_division_slug'] : null;
        $profile = \Wexoe\Core\Helpers\Singletons::graphic_profile_for_division($division);
        if (is_array($profile) && !empty($profile['icon_light_url'])) {
            $icon_url = (string) $profile['icon_light_url'];
        }
    }

    $wid = (string) ($ctx['wrapper_id'] ?? '');
    $has_header = ($h2 !== '' || $body !== '');
    $extra_class = 'wxp-cds';
    if ($has_header) $extra_class .= ' wxp-cds--with-header';
    if ($icon_url !== '') $extra_class .= ' wxp-cds--with-icon';
    $attrs = wexoe_pages_section_attrs($section, $ctx, $extra_class);
    ob_start();
    ?>
    <section <?= $attrs ?>>
        <div class="wxp-section__inner wxp-cds__inner">
            <?php if ($has_header): ?>
                <div class="wxp-cds__title-wrap">
                    <?php if ($icon_url !== ''): ?>
                        <span class="wxp-cds__icon-slot">
                            <img class="wxp-cds__icon" src="<?= esc_url($icon_url) ?>" alt="" loading="lazy" />
                        </span>
                    <?php endif; ?>
                    <div class="wxp-cds__title-block">
                        <?php if ($h2 !== ''): ?><h2 class="wxp-cds__h2"><?= nl2br(esc_html($h2)) ?></h2><?php endif; ?>
                        <?php if ($body !== ''): ?><div class="wxp-cds__body"><?= wexoe_pages_md($body) ?></div><?php endif; ?>
                    </div>
                </div>
                <div class="wxp-cds__divider" aria-hidden="true"></div>
            <?php endif; ?>
            <dl class="wxp-cds__grid">
                <?php foreach ($items as $item): ?>
                    <div class="wxp-cds__item">
                        <dd class="wxp-cds__value"><?= esc_html($item['value']) ?><?php if ($item['suffix'] !== ''): ?><span class="wxp-cds__suffix"><?= esc_html($item['suffix']) ?></span><?php endif; ?></dd>
                        <?php if (!empty($item['label'])): ?><dt class="wxp-cds__label"><?= esc_html($item['label']) ?></dt><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </dl>
        </div>
    </section>
    <style>
/* Default-bg: navy. background_color på sektionen overrides (då används
   --on-dark/--on-light klasserna från section_attrs istället). */
#<?= esc_attr($wid) ?> .wxp-cds:not(.wxp-section--custom-bg) { background: #11325D !important; color: #fff !important; }
#<?= esc_attr($wid) ?> .wxp-cds__inner { display: grid !important; grid-template-columns: 1fr !important; gap: 32px !important; align-items: center !important; }
#<?= esc_attr($wid) ?> .wxp-cds--with-header .wxp-cds__inner { grid-template-columns: minmax(0, auto) 1px minmax(0, 1fr) !important; gap: 56px !important; }
#<?= esc_attr($wid) ?> .wxp-cds__title-wrap { display: flex !important; align-items: flex-start !important; gap: 18px !important; }
#<?= esc_attr($wid) ?> .wxp-cds__icon-slot { display: flex !important; align-items: center !important; justify-content: center !important; width: 52px !important; height: 52px !important; flex-shrink: 0 !important; background: rgba(255,255,255,0.04) !important; border: 1px solid rgba(255,255,255,0.10) !important; border-radius: 2px !important; padding: 8px !important; }
#<?= esc_attr($wid) ?> .wxp-cds__icon { width: 100% !important; height: 100% !important; object-fit: contain !important; display: block !important; }
#<?= esc_attr($wid) ?> .wxp-cds__title-block { min-width: 0 !important; padding-top: 2px !important; }
#<?= esc_attr($wid) ?> .wxp-cds__h2 { font-family: 'DM Sans', system-ui, sans-serif !important; font-size: clamp(1.5rem, 2.4vw, 1.875rem) !important; font-weight: 700 !important; line-height: 1.12 !important; letter-spacing: -0.02em !important; color: inherit !important; margin: 0 !important; padding: 0 !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-cds__body { font-size: 14.5px !important; line-height: 1.6 !important; opacity: 0.78 !important; color: inherit !important; margin-top: 10px !important; }
#<?= esc_attr($wid) ?> .wxp-cds__body p { margin: 0 0 6px !important; color: inherit !important; }
#<?= esc_attr($wid) ?> .wxp-cds__body p:last-child { margin-bottom: 0 !important; }
#<?= esc_attr($wid) ?> .wxp-cds__divider { width: 1px !important; height: 96px !important; background: rgba(255,255,255,0.22) !important; justify-self: center !important; align-self: center !important; }
#<?= esc_attr($wid) ?> .wxp-cds__grid { display: grid !important; grid-template-columns: repeat(4, 1fr) !important; gap: 32px !important; margin: 0 !important; padding: 0 !important; }
#<?= esc_attr($wid) ?> .wxp-cds__item { margin: 0 !important; padding: 0 !important; background: none !important; border: none !important; display: flex !important; flex-direction: column !important; gap: 8px !important; text-align: center !important; }
#<?= esc_attr($wid) ?> .wxp-cds__value { font-family: 'DM Sans', system-ui, sans-serif !important; font-size: clamp(2rem, 3.4vw, 2.75rem) !important; font-weight: 700 !important; line-height: 1 !important; letter-spacing: -0.025em !important; color: inherit !important; font-variant-numeric: tabular-nums !important; margin: 0 !important; padding: 0 !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-cds__suffix { font-weight: 500 !important; color: inherit !important; font-size: 0.6em !important; margin-left: 0.06em !important; letter-spacing: 0 !important; }
#<?= esc_attr($wid) ?> .wxp-cds__label { font-family: 'DM Sans', system-ui, sans-serif !important; font-size: 14px !important; font-weight: 400 !important; text-transform: none !important; letter-spacing: -0.003em !important; opacity: 0.74 !important; margin: 0 !important; padding: 0 !important; color: inherit !important; background: none !important; line-height: 1.35 !important; }

/* On-light override (när användaren satt custom ljus bakgrund) */
#<?= esc_attr($wid) ?> .wxp-cds.wxp-section--on-light .wxp-cds__icon-slot { background: rgba(17,50,93,0.04) !important; border-color: rgba(17,50,93,0.12) !important; }
#<?= esc_attr($wid) ?> .wxp-cds.wxp-section--on-light .wxp-cds__divider { background: rgba(17,50,93,0.14) !important; }

/* Färre items (1–3) → grid-template anpassas så cellerna inte sträcker sig orimligt brett. */
#<?= esc_attr($wid) ?> .wxp-cds__grid:has(.wxp-cds__item:only-child) { grid-template-columns: minmax(0, auto) !important; justify-content: start !important; }

@media (max-width: 880px) {
    #<?= esc_attr($wid) ?> .wxp-cds--with-header .wxp-cds__inner { grid-template-columns: 1fr !important; gap: 28px !important; }
    #<?= esc_attr($wid) ?> .wxp-cds__divider { display: none !important; }
    #<?= esc_attr($wid) ?> .wxp-cds__grid { grid-template-columns: repeat(2, 1fr) !important; gap: 24px 32px !important; }
}
@media (max-width: 480px) {
    #<?= esc_attr($wid) ?> .wxp-cds__grid { grid-template-columns: 1fr !important; gap: 18px !important; }
}
    </style>
    <?php
    return ob_get_clean();
};
