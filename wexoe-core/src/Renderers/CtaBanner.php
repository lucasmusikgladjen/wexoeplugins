<?php
namespace Wexoe\Core\Renderers;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * CTA banner-sektion. CSS-prefix `wxr-cta__`.
 *
 * Config:
 *   h2       — string
 *   body     — string (multiline)
 *   cta_text — string
 *   cta_url  — string
 *   theme    — 'dark' | 'light'
 */
class CtaBanner {
    public static function render(array $config): string {
        $h2 = (string) ($config['h2'] ?? '');
        $body = (string) ($config['body'] ?? '');
        $cta_text = (string) ($config['cta_text'] ?? '');
        $cta_url = (string) ($config['cta_url'] ?? '');
        $theme = ($config['theme'] ?? 'dark') === 'light' ? 'light' : 'dark';

        if ($h2 === '' && $body === '') return '';

        ob_start();
        ?>
        <section class="wxr-cta wxr-cta--<?= esc_attr($theme) ?>">
            <div class="wxr-cta__inner">
                <?php if ($h2 !== ''): ?><h2 class="wxr-cta__h2"><?= esc_html($h2) ?></h2><?php endif; ?>
                <?php if ($body !== ''): ?><p class="wxr-cta__body"><?= nl2br(esc_html($body)) ?></p><?php endif; ?>
                <?php if ($cta_text !== '' && $cta_url !== ''): ?>
                    <a class="wxr-cta__button" href="<?= esc_url($cta_url) ?>"><?= esc_html($cta_text) ?></a>
                <?php endif; ?>
            </div>
        </section>
        <style>
            .wxr-cta { padding: 64px 24px; }
            .wxr-cta--dark { background: #11325D; color: #fff; }
            .wxr-cta--light { background: #F5F6F8; color: #1A1A1A; }
            .wxr-cta__inner { max-width: 800px; margin: 0 auto; text-align: center; }
            .wxr-cta__h2 { font-size: clamp(1.5rem, 3vw, 2.25rem); margin: 0 0 16px; font-weight: 600; }
            .wxr-cta__body { font-size: 16px; line-height: 1.5; margin: 0 0 24px; opacity: 0.85; }
            .wxr-cta__button { display: inline-block; padding: 12px 28px; border-radius: 8px; background: #F28C28; color: #fff; text-decoration: none; font-weight: 500; }
            .wxr-cta--light .wxr-cta__button { background: #11325D; }
        </style>
        <?php
        return ob_get_clean();
    }
}
