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

    $wid = (string) ($ctx['wrapper_id'] ?? '');
    $attrs = wexoe_pages_section_attrs($section, $ctx, 'wxp-cta');
    $bg_style = $image !== ''
        ? 'background-image: linear-gradient(rgba(17,50,93,0.78), rgba(17,50,93,0.78)), url(' . esc_url($image) . '); background-size: cover; background-position: center;'
        : '';

    ob_start();
    ?>
    <section <?= $attrs ?>>
        <div class="wxp-section__inner wxp-cta__outer">
            <div class="wxp-cta__card"<?= $bg_style !== '' ? ' style="' . esc_attr($bg_style) . '"' : '' ?>>
                <?php if ($eyebrow !== ''): ?><p class="wxp-eyebrow wxp-cta__eyebrow"><?= esc_html($eyebrow) ?></p><?php endif; ?>
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
        </div>
    </section>
    <style>
#<?= esc_attr($wid) ?> .wxp-cta__outer { padding-left: 24px !important; padding-right: 24px !important; }
#<?= esc_attr($wid) ?> .wxp-cta__card { position: relative !important; padding: 56px 48px !important; border-radius: 2px !important; background: linear-gradient(135deg, #11325D 0%, #1a4a7a 55%, #2d6a9f 100%) !important; color: #fff !important; text-align: center !important; box-shadow: 0 24px 60px rgba(10,26,46,0.18) !important; overflow: hidden !important; }
#<?= esc_attr($wid) ?> .wxp-cta__card::before { content: '' !important; position: absolute !important; inset: 0 !important; background-image: radial-gradient(circle at 12% 88%, rgba(255,255,255,0.06) 0%, transparent 45%), radial-gradient(circle at 88% 12%, rgba(255,255,255,0.08) 0%, transparent 40%) !important; pointer-events: none !important; }
#<?= esc_attr($wid) ?> .wxp-cta__card > * { position: relative !important; z-index: 1 !important; }
/* Eyebrow ärver muted-vit från on-dark-basstilen via .wxp-cta__card-kontexten. */
#<?= esc_attr($wid) ?> .wxp-cta__eyebrow { justify-content: center !important; color: rgba(255,255,255,0.72) !important; }
#<?= esc_attr($wid) ?> .wxp-cta__eyebrow::before { background: rgba(255,255,255,0.72) !important; }
#<?= esc_attr($wid) ?> .wxp-cta__h2 { color: #fff !important; font-size: clamp(1.75rem, 3.5vw, 2.5rem) !important; margin: 0 0 16px !important; }
#<?= esc_attr($wid) ?> .wxp-cta__body { max-width: 60ch !important; margin: 0 auto 28px !important; opacity: 0.92 !important; color: #fff !important; }
#<?= esc_attr($wid) ?> .wxp-cta__body p { color: #fff !important; }
#<?= esc_attr($wid) ?> .wxp-cta__actions { justify-content: center !important; margin: 0 !important; }

@media (max-width: 700px) {
    #<?= esc_attr($wid) ?> .wxp-cta__card { padding: 40px 24px !important; border-radius: 2px !important; }
    #<?= esc_attr($wid) ?> .wxp-cta__actions { flex-direction: column !important; align-items: stretch !important; }
    #<?= esc_attr($wid) ?> .wxp-cta__actions .wxp-btn { width: 100% !important; }
}
    </style>
    <?php
    return ob_get_clean();
};
