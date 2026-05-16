<?php
/**
 * Section: cta_banner (section_type = "cta_banner")
 *
 * Slut-banner med rubrik + body + upp till två CTAs. Stödjer bakgrundsbild
 * via cta_image_url (overlay darken för läsbarhet).
 */

if (!defined('ABSPATH')) exit;

return function ($section, $page, $ctx) {
    $eyebrow = (string) ($section['cta_eyebrow']      ?? '');
    $h2      = (string) ($section['cta_h2']           ?? '');
    $body    = (string) ($section['cta_body']         ?? '');
    $cta1_t  = (string) ($section['cta_cta_text']     ?? '');
    $cta1_u  = (string) ($section['cta_cta_url']      ?? '');
    $cta2_t  = (string) ($section['cta_cta2_text']    ?? '');
    $cta2_u  = (string) ($section['cta_cta2_url']     ?? '');
    $image   = (string) ($section['cta_image_url']    ?? '');

    if ($h2 === '' && $body === '' && $cta1_t === '') return '';

    $attrs = wexoe_pages_section_attrs($section, $ctx, 'wxp-cta');
    $bg_style = $image !== ''
        ? 'background-image: linear-gradient(rgba(10,26,46,0.65), rgba(10,26,46,0.65)), url(' . esc_url($image) . '); background-size: cover; background-position: center;'
        : '';

    ob_start();
    ?>
    <section <?= $attrs ?> style="<?= esc_attr($bg_style) ?>">
        <div class="wxp-section__inner wxp-cta__inner">
            <?php if ($eyebrow !== ''): ?><p class="wxp-eyebrow"><?= esc_html($eyebrow) ?></p><?php endif; ?>
            <?php if ($h2 !== ''): ?><h2 class="wxp-h2 wxp-cta__h2"><?= esc_html($h2) ?></h2><?php endif; ?>
            <?php if ($body !== ''): ?><div class="wxp-body wxp-cta__body"><?= wexoe_pages_md($body) ?></div><?php endif; ?>
            <?php if (($cta1_t !== '' && $cta1_u !== '') || ($cta2_t !== '' && $cta2_u !== '')): ?>
                <div class="wxp-actions wxp-cta__actions">
                    <?php if ($cta1_t !== '' && $cta1_u !== ''): ?>
                        <a class="wxp-btn wxp-btn--primary" href="<?= esc_url($cta1_u) ?>"><?= esc_html($cta1_t) ?> <span aria-hidden="true">→</span></a>
                    <?php endif; ?>
                    <?php if ($cta2_t !== '' && $cta2_u !== ''): ?>
                        <a class="wxp-btn wxp-btn--secondary" href="<?= esc_url($cta2_u) ?>"><?= esc_html($cta2_t) ?></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <style>
.wxp-cta { color: #fff; background-color: #11325D; }
.wxp-cta.wxp-section--theme-light { color: #1A1A1A; background-color: #F5F6F8; }
.wxp-cta__inner { max-width: 820px; text-align: center; }
.wxp-cta__h2 { font-size: clamp(1.75rem, 3.5vw, 2.5rem); }
.wxp-cta__body { margin: 0 auto 24px; max-width: 60ch; opacity: 0.92; }
.wxp-cta__actions { justify-content: center; }
.wxp-cta .wxp-btn--secondary { color: #fff; border-color: rgba(255,255,255,0.5); }
.wxp-cta.wxp-section--theme-light .wxp-btn--secondary { color: #11325D; border-color: rgba(17,50,93,0.3); }
    </style>
    <?php
    return ob_get_clean();
};
