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

    $attrs = wexoe_pages_section_attrs($section, $ctx, 'wxp-cds');
    ob_start();
    ?>
    <section <?= $attrs ?>>
        <div class="wxp-section__inner">
            <?php if ($h2 !== ''): ?><h2 class="wxp-cds__h2"><?= esc_html($h2) ?></h2><?php endif; ?>
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
.wxp-cds__h2 { font-size: 1rem; font-weight: 500; opacity: 0.7; margin: 0 0 16px; text-transform: uppercase; letter-spacing: 0.06em; }
.wxp-cds__grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 24px; margin: 0; padding: 24px 0; border-top: 1px solid rgba(0,0,0,0.08); border-bottom: 1px solid rgba(0,0,0,0.08); }
.wxp-section--theme-dark .wxp-cds__grid { border-color: rgba(255,255,255,0.12); }
.wxp-cds__item { margin: 0; }
.wxp-cds__label { font-size: 12px; text-transform: uppercase; letter-spacing: 0.06em; opacity: 0.7; margin: 0 0 4px; font-weight: 500; }
.wxp-cds__value { font-size: 18px; font-weight: 600; margin: 0; line-height: 1.3; }
.wxp-cds__suffix { font-size: 14px; font-weight: 400; opacity: 0.7; margin-left: 4px; }
    </style>
    <?php
    return ob_get_clean();
};
