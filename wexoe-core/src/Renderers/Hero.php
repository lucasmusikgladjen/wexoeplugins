<?php
namespace Wexoe\Core\Renderers;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hero-sektion. CSS-prefix `wxr-hero__`.
 *
 * Config:
 *   eyebrow   — string
 *   title     — string (huvudrubrik)
 *   subtitle  — string (multiline)
 *   image_url — string (full URL till bild)
 *   cta_text  — string
 *   cta_url   — string
 *   theme     — 'dark' | 'light'
 */
class Hero {
    public static function render(array $config): string {
        $title = trim((string) ($config['title'] ?? ''));
        if ($title === '') return '';

        $eyebrow = (string) ($config['eyebrow'] ?? '');
        $subtitle = (string) ($config['subtitle'] ?? '');
        $image_url = (string) ($config['image_url'] ?? '');
        $cta_text = (string) ($config['cta_text'] ?? '');
        $cta_url = (string) ($config['cta_url'] ?? '');
        $theme = ($config['theme'] ?? 'dark') === 'light' ? 'light' : 'dark';

        $bg_style = $image_url !== ''
            ? "background-image: url('" . esc_url($image_url) . "'); background-size: cover; background-position: center;"
            : '';

        ob_start();
        ?>
        <section class="wxr-hero wxr-hero--<?= esc_attr($theme) ?>" style="<?= esc_attr($bg_style) ?>">
            <div class="wxr-hero__inner">
                <?php if ($eyebrow !== ''): ?>
                    <p class="wxr-hero__eyebrow"><?= esc_html($eyebrow) ?></p>
                <?php endif; ?>
                <h1 class="wxr-hero__title"><?= esc_html($title) ?></h1>
                <?php if ($subtitle !== ''): ?>
                    <p class="wxr-hero__subtitle"><?= nl2br(esc_html($subtitle)) ?></p>
                <?php endif; ?>
                <?php if ($cta_text !== '' && $cta_url !== ''): ?>
                    <a class="wxr-hero__cta" href="<?= esc_url($cta_url) ?>"><?= esc_html($cta_text) ?></a>
                <?php endif; ?>
            </div>
        </section>
        <style>
            .wxr-hero { padding: 80px 24px; color: #fff; background-color: #11325D; }
            .wxr-hero--light { background-color: #F5F6F8; color: #1A1A1A; }
            .wxr-hero__inner { max-width: 960px; margin: 0 auto; }
            .wxr-hero__eyebrow { text-transform: uppercase; letter-spacing: 0.08em; font-size: 12px; opacity: 0.8; margin: 0 0 12px; }
            .wxr-hero__title { font-size: clamp(2rem, 4vw, 3rem); line-height: 1.15; margin: 0 0 12px; font-weight: 600; }
            .wxr-hero__subtitle { font-size: 18px; line-height: 1.5; opacity: 0.85; margin: 0 0 24px; max-width: 60ch; }
            .wxr-hero__cta { display: inline-block; padding: 12px 24px; border-radius: 8px; background: #F28C28; color: #fff; text-decoration: none; font-weight: 500; }
            .wxr-hero--light .wxr-hero__cta { background: #11325D; }
        </style>
        <?php
        return ob_get_clean();
    }
}
