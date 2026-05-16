<?php
/**
 * Section: hero (section_type = "hero")
 *
 * Page-toppen. Stora rubriken + subtitle + 2 CTAs + bakgrundsbild.
 * Renderar en `<h1>` så pluginet undertrycker page-level H1 när hero finns.
 */

if (!defined('ABSPATH')) exit;

return function ($section, $page, $ctx) {
    $h1 = trim((string) ($section['hero_h1'] ?? $page['h1'] ?? ''));
    if ($h1 === '') return '';

    $eyebrow  = (string) ($section['hero_eyebrow']   ?? '');
    $subtitle = (string) ($section['hero_subtitle']  ?? '');
    $image    = (string) ($section['hero_image_url'] ?? '');
    $cta1_t   = (string) ($section['hero_cta_text']  ?? '');
    $cta1_u   = (string) ($section['hero_cta_url']   ?? '');
    $cta2_t   = (string) ($section['hero_cta2_text'] ?? '');
    $cta2_u   = (string) ($section['hero_cta2_url']  ?? '');

    $attrs = wexoe_pages_section_attrs($section, $ctx, 'wxp-hero');
    $bg_style = $image !== ''
        ? 'background-image: linear-gradient(rgba(10,26,46,0.55), rgba(10,26,46,0.55)), url(' . esc_url($image) . '); background-size: cover; background-position: center;'
        : '';

    ob_start();
    ?>
    <section <?= $attrs ?> style="<?= esc_attr($bg_style) ?>">
        <div class="wxp-section__inner wxp-hero__inner">
            <?php if ($eyebrow !== ''): ?><p class="wxp-eyebrow wxp-hero__eyebrow"><?= esc_html($eyebrow) ?></p><?php endif; ?>
            <h1 class="wxp-hero__h1"><?= esc_html($h1) ?></h1>
            <?php if ($subtitle !== ''): ?>
                <p class="wxp-hero__subtitle"><?= nl2br(esc_html($subtitle)) ?></p>
            <?php endif; ?>
            <?php if (($cta1_t !== '' && $cta1_u !== '') || ($cta2_t !== '' && $cta2_u !== '')): ?>
                <div class="wxp-actions wxp-hero__actions">
                    <?php if ($cta1_t !== '' && $cta1_u !== ''): ?>
                        <a class="wxp-btn wxp-btn--primary" href="<?= esc_url($cta1_u) ?>"><?= esc_html($cta1_t) ?> <span aria-hidden="true">→</span></a>
                    <?php endif; ?>
                    <?php if ($cta2_t !== '' && $cta2_u !== ''): ?>
                        <a class="wxp-btn wxp-btn--secondary" href="<?= esc_url($cta2_u) ?>"><?= esc_html($cta2_t) ?> <span aria-hidden="true">→</span></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <style>
.wxp-hero { color: #fff; background-color: #0A1A2E; min-height: 420px; display: flex; align-items: center; }
.wxp-hero.wxp-section--theme-light { color: #1A1A1A; background-color: #F5F6F8; }
.wxp-hero__inner { max-width: 880px; }
.wxp-hero__h1 { font-size: clamp(2rem, 4.5vw, 3.25rem); line-height: 1.1; margin: 0 0 16px; font-weight: 700; }
.wxp-hero__subtitle { font-size: clamp(1rem, 1.4vw, 1.25rem); line-height: 1.55; opacity: 0.9; margin: 0 0 28px; max-width: 60ch; }
.wxp-hero__actions { margin-top: 8px; }
.wxp-hero .wxp-btn--secondary { color: #fff; border-color: rgba(255,255,255,0.5); }
.wxp-hero .wxp-btn--secondary:hover { background: rgba(255,255,255,0.08); color: #fff; }
    </style>
    <?php
    return ob_get_clean();
};
