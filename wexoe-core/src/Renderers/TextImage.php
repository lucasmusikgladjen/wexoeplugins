<?php
namespace Wexoe\Core\Renderers;

use Wexoe\Core\Helpers\Markdown;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Text + bild-sektion. CSS-prefix `wxr-ti__`.
 *
 * Config:
 *   h2        — string
 *   body      — string (markdown)
 *   image_url — string
 *   reversed  — bool (true = bild till vänster)
 *   theme     — 'dark' | 'light'
 */
class TextImage {
    public static function render(array $config): string {
        $h2 = (string) ($config['h2'] ?? '');
        $body = (string) ($config['body'] ?? '');
        $image_url = (string) ($config['image_url'] ?? '');
        $reversed = !empty($config['reversed']);
        $theme = ($config['theme'] ?? 'light') === 'dark' ? 'dark' : 'light';

        if ($h2 === '' && $body === '' && $image_url === '') return '';

        $body_html = $body !== '' && class_exists('\\Wexoe\\Core\\Helpers\\Markdown')
            ? Markdown::to_html($body)
            : nl2br(esc_html($body));

        ob_start();
        ?>
        <section class="wxr-ti wxr-ti--<?= esc_attr($theme) ?> <?= $reversed ? 'wxr-ti--reversed' : '' ?>">
            <div class="wxr-ti__inner">
                <div class="wxr-ti__text">
                    <?php if ($h2 !== ''): ?><h2 class="wxr-ti__h2"><?= esc_html($h2) ?></h2><?php endif; ?>
                    <?php if ($body_html !== ''): ?><div class="wxr-ti__body"><?= $body_html ?></div><?php endif; ?>
                </div>
                <?php if ($image_url !== ''): ?>
                    <div class="wxr-ti__image-wrap">
                        <img class="wxr-ti__image" src="<?= esc_url($image_url) ?>" alt="" loading="lazy" />
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <style>
            .wxr-ti { padding: 64px 24px; }
            .wxr-ti--dark { background: #0A1A2E; color: #fff; }
            .wxr-ti--light { background: #fff; color: #1A1A1A; }
            .wxr-ti__inner { max-width: 1100px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 48px; align-items: center; }
            .wxr-ti--reversed .wxr-ti__inner { grid-template-columns: 1fr 1fr; }
            .wxr-ti--reversed .wxr-ti__text { order: 2; }
            .wxr-ti__h2 { font-size: clamp(1.5rem, 3vw, 2rem); margin: 0 0 16px; font-weight: 600; }
            .wxr-ti__body { font-size: 16px; line-height: 1.65; }
            .wxr-ti__image { width: 100%; height: auto; border-radius: 8px; display: block; }
            @media (max-width: 720px) { .wxr-ti__inner { grid-template-columns: 1fr; } .wxr-ti--reversed .wxr-ti__text { order: 0; } }
        </style>
        <?php
        return ob_get_clean();
    }
}
