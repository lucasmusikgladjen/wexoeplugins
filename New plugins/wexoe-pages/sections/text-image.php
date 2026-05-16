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

    $extra = 'wxp-ti' . ($reversed ? ' wxp-ti--reversed' : '');
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
                            <li><span class="wxp-ti__check" aria-hidden="true">✓</span> <?= wexoe_pages_md_inline($b) ?></li>
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
.wxp-ti__grid { display: grid; grid-template-columns: 1fr 1fr; gap: 48px; align-items: center; }
.wxp-ti--reversed .wxp-ti__text { order: 2; }
.wxp-ti__bullets { list-style: none; padding: 0; margin: 16px 0 24px; }
.wxp-ti__bullets li { display: flex; gap: 8px; align-items: baseline; padding: 6px 0; }
.wxp-ti__check { color: #16A34A; font-weight: 700; flex-shrink: 0; }
.wxp-ti__image { width: 100%; height: auto; border-radius: 12px; display: block; }
.wxp-ti__actions { margin-top: 8px; }
@media (max-width: 720px) {
    .wxp-ti__grid { grid-template-columns: 1fr; gap: 24px; }
    .wxp-ti--reversed .wxp-ti__text { order: 0; }
}
    </style>
    <?php
    return ob_get_clean();
};
