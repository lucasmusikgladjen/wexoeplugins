<?php
/**
 * Section: company_data_strip (section_type = "company_data_strip")
 *
 * Smal datastrimla — fakta-/siffer-items i en rad (anställda, omsättning,
 * kontor, etc.). Två data-källor:
 *   1. cds_use_company_singleton=true → hämta från core_company för cds_country_code
 *      (eller sidans country som fallback). Bygger items från fasta fält
 *      (org_number, email, phone, address).
 *   2. cds_items (lines) — format "Label | Value | Suffix" per rad. Suffix
 *      är valfri (t.ex. "+", "%", "år").
 */

if (!defined('ABSPATH')) exit;

return function ($section, $page, $ctx) {
    $h2 = (string) ($section['cds_h2'] ?? '');
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
            if (!empty($company['company_name'])) {
                $items[] = ['label' => 'Företag', 'value' => $company['company_name'], 'suffix' => ''];
            }
            if (!empty($company['org_number'])) {
                $items[] = ['label' => 'Org.nr', 'value' => $company['org_number'], 'suffix' => ''];
            }
            if (!empty($company['vat_number'])) {
                $items[] = ['label' => 'VAT', 'value' => $company['vat_number'], 'suffix' => ''];
            }
            if (!empty($company['phone'])) {
                $items[] = ['label' => 'Telefon', 'value' => $company['phone'], 'suffix' => ''];
            }
            if (!empty($company['email'])) {
                $items[] = ['label' => 'E-post', 'value' => $company['email'], 'suffix' => ''];
            }
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

    $wid = (string) ($ctx['wrapper_id'] ?? '');
    $attrs = wexoe_pages_section_attrs($section, $ctx, 'wxp-cds');
    ob_start();
    ?>
    <section <?= $attrs ?>>
        <div class="wxp-section__inner">
            <?php if ($h2 !== ''): ?><h2 class="wxp-h2 wxp-cds__h2"><?= esc_html($h2) ?></h2><?php endif; ?>
            <dl class="wxp-cds__grid">
                <?php foreach ($items as $item): ?>
                    <div class="wxp-cds__item">
                        <dt class="wxp-cds__label"><?= esc_html($item['label']) ?></dt>
                        <dd class="wxp-cds__value"><?= esc_html($item['value']) ?><?php if ($item['suffix'] !== ''): ?><span class="wxp-cds__suffix"><?= esc_html($item['suffix']) ?></span><?php endif; ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </div>
    </section>
    <style>
#<?= esc_attr($wid) ?> .wxp-cds__h2 { margin: 0 0 28px !important; font-size: clamp(1.5rem, 2.8vw, 2rem) !important; }
#<?= esc_attr($wid) ?> .wxp-cds__grid { display: grid !important; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) !important; gap: 12px !important; margin: 0 !important; padding: 0 !important; }
#<?= esc_attr($wid) ?> .wxp-cds__item { margin: 0 !important; padding: 22px 20px !important; border-radius: 14px !important; background: rgba(17,50,93,0.04) !important; border: 1px solid rgba(17,50,93,0.06) !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-cds__item { background: rgba(255,255,255,0.06) !important; border-color: rgba(255,255,255,0.08) !important; }
#<?= esc_attr($wid) ?> .wxp-cds__label { font-family: 'DM Sans', system-ui, sans-serif !important; font-size: 12px !important; text-transform: uppercase !important; letter-spacing: 0.08em !important; opacity: 0.72 !important; margin: 0 0 8px !important; padding: 0 !important; font-weight: 600 !important; color: inherit !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-cds__value { font-family: 'DM Sans', system-ui, sans-serif !important; font-size: clamp(1.5rem, 2.2vw, 1.85rem) !important; font-weight: 800 !important; margin: 0 !important; padding: 0 !important; line-height: 1.15 !important; color: #F28C28 !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-cds__suffix { font-size: 0.7em !important; font-weight: 600 !important; opacity: 0.85 !important; margin-left: 2px !important; }
    </style>
    <?php
    return ob_get_clean();
};
