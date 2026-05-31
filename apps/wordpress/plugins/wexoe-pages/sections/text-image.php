<?php
/**
 * Section: text_image (section_type = "text_image")
 *
 * Två kolumner — text (eyebrow, h2, body markdown, bullets, 2 CTAs) + bild.
 * ti_reversed flippar ordningen (bild vänster, text höger).
 */

if (!defined('ABSPATH')) exit;

return function ($section, $page, $ctx) {
    $eyebrow  = (string) ($section['ti_eyebrow']    ?? '');
    $h2       = (string) ($section['ti_h2']         ?? '');
    $body     = (string) ($section['ti_body']       ?? '');
    $bullets  = is_array($section['ti_bullets'] ?? null) ? $section['ti_bullets'] : [];
    $image    = (string) ($section['ti_image_url']  ?? '');
    $image_alt = (string) ($section['ti_image_alt'] ?? $h2);
    $reversed = !empty($section['ti_reversed']);
    $cta1_t   = (string) ($section['ti_cta_text']   ?? '');
    $cta1_u   = (string) ($section['ti_cta_url']    ?? '');
    $cta2_t   = (string) ($section['ti_cta2_text']  ?? '');
    $cta2_u   = (string) ($section['ti_cta2_url']   ?? '');

    if ($h2 === '' && $body === '' && $image === '' && empty($bullets)) return '';

    $wid = (string) ($ctx['wrapper_id'] ?? '');
    $extra = 'wxp-ti' . ($reversed ? ' wxp-ti--reversed' : '') . ($image === '' ? ' wxp-ti--no-image' : '');
    $attrs = wexoe_pages_section_attrs($section, $ctx, $extra);

    ob_start();
    ?>
    <section <?= $attrs ?>>
        <div class="wxp-section__inner wxp-ti__grid">
            <div class="wxp-ti__text">
                <?php if ($eyebrow !== ''): ?><p class="wxp-eyebrow"><?= esc_html($eyebrow) ?></p><?php endif; ?>
                <?php if ($h2 !== ''): ?><h2 class="wxp-h2"><?= esc_html($h2) ?></h2><?php endif; ?>
                <?php if ($body !== ''): ?><div class="wxp-body"><?= wexoe_pages_md($body) ?></div><?php endif; ?>
                <?php if (!empty($bullets)): ?>
                    <ul class="wxp-ti__bullets">
                        <?php foreach ($bullets as $b): ?>
                            <li><span class="wxp-ti__check" aria-hidden="true">✓</span><span class="wxp-ti__bullet-text"><?= wexoe_pages_md_inline($b) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if (($cta1_t !== '' && $cta1_u !== '') || ($cta2_t !== '' && $cta2_u !== '')): ?>
                    <div class="wxp-actions wxp-ti__actions">
                        <?php if ($cta1_t !== '' && $cta1_u !== ''): ?>
                            <a class="wxp-btn wxp-btn--primary" href="<?= esc_url($cta1_u) ?>"><?= esc_html($cta1_t) ?> <span aria-hidden="true">→</span></a>
                        <?php endif; ?>
                        <?php if ($cta2_t !== '' && $cta2_u !== ''): ?>
                            <a class="wxp-btn wxp-btn--secondary" href="<?= esc_url($cta2_u) ?>"><?= esc_html($cta2_t) ?></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($image !== ''): ?>
                <div class="wxp-ti__image-wrap">
                    <img class="wxp-ti__image" src="<?= esc_url($image) ?>" alt="<?= esc_attr($image_alt) ?>" loading="lazy" />
                </div>
            <?php endif; ?>
        </div>
    </section>
    <style>
#<?= esc_attr($wid) ?> .wxp-ti__grid { display: grid !important; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) !important; gap: 56px !important; align-items: center !important; }
#<?= esc_attr($wid) ?> .wxp-ti--no-image .wxp-ti__grid { grid-template-columns: 1fr !important; max-width: 760px !important; }
#<?= esc_attr($wid) ?> .wxp-ti--reversed .wxp-ti__text { order: 2 !important; }
#<?= esc_attr($wid) ?> .wxp-ti__text { min-width: 0 !important; }
#<?= esc_attr($wid) ?> .wxp-ti__bullets { list-style: none !important; padding: 0 !important; margin: 20px 0 28px !important; display: flex !important; flex-direction: column !important; gap: 10px !important; }
#<?= esc_attr($wid) ?> .wxp-ti__bullets li { display: flex !important; gap: 12px !important; align-items: flex-start !important; padding: 0 !important; margin: 0 !important; background: none !important; list-style: none !important; line-height: 1.55 !important; color: inherit !important; }
#<?= esc_attr($wid) ?> .wxp-ti__bullets li::before { content: none !important; display: none !important; }
#<?= esc_attr($wid) ?> .wxp-ti__check { flex-shrink: 0 !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; width: 22px !important; height: 22px !important; border-radius: 50% !important; background: #10B981 !important; color: #fff !important; font-size: 13px !important; font-weight: 700 !important; line-height: 1 !important; margin-top: 2px !important; }
#<?= esc_attr($wid) ?> .wxp-ti__bullet-text { flex: 1 !important; color: inherit !important; }
#<?= esc_attr($wid) ?> .wxp-ti__image-wrap { position: relative !important; aspect-ratio: 4 / 3 !important; border-radius: 2px !important; overflow: hidden !important; background: #FAFAF7 !important; box-shadow: 0 18px 40px rgba(10,26,46,0.12) !important; }
#<?= esc_attr($wid) ?> .wxp-ti__image { width: 100% !important; height: 100% !important; object-fit: cover !important; display: block !important; border-radius: 0 !important; }
#<?= esc_attr($wid) ?> .wxp-ti__actions { margin-top: 4px !important; }
@media (max-width: 900px) {
    #<?= esc_attr($wid) ?> .wxp-ti__grid { grid-template-columns: 1fr !important; gap: 32px !important; }
    #<?= esc_attr($wid) ?> .wxp-ti--reversed .wxp-ti__text { order: 0 !important; }
    #<?= esc_attr($wid) ?> .wxp-ti__image-wrap { aspect-ratio: 16 / 10 !important; }
}
    </style>
    <?php
    return ob_get_clean();
};
