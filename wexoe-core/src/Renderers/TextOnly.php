<?php
namespace Wexoe\Core\Renderers;

use Wexoe\Core\Helpers\Markdown;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Text-only sektion. CSS-prefix `wxr-text__`.
 *
 * Config:
 *   h2    — string
 *   body  — string (markdown)
 *   align — 'left' | 'center'
 */
class TextOnly {
    public static function render(array $config): string {
        $h2 = (string) ($config['h2'] ?? '');
        $body = (string) ($config['body'] ?? '');
        $align = ($config['align'] ?? 'left') === 'center' ? 'center' : 'left';

        if ($h2 === '' && $body === '') return '';

        $body_html = $body !== '' && class_exists('\\Wexoe\\Core\\Helpers\\Markdown')
            ? Markdown::to_html($body)
            : nl2br(esc_html($body));

        ob_start();
        ?>
        <section class="wxr-text wxr-text--<?= esc_attr($align) ?>">
            <div class="wxr-text__inner">
                <?php if ($h2 !== ''): ?><h2 class="wxr-text__h2"><?= esc_html($h2) ?></h2><?php endif; ?>
                <?php if ($body_html !== ''): ?><div class="wxr-text__body"><?= $body_html ?></div><?php endif; ?>
            </div>
        </section>
        <style>
            .wxr-text { padding: 64px 24px; background: #fff; color: #1A1A1A; }
            .wxr-text__inner { max-width: 800px; margin: 0 auto; }
            .wxr-text--center .wxr-text__inner { text-align: center; }
            .wxr-text__h2 { font-size: clamp(1.5rem, 3vw, 2rem); margin: 0 0 16px; font-weight: 600; }
            .wxr-text__body { font-size: 16px; line-height: 1.65; }
        </style>
        <?php
        return ob_get_clean();
    }
}
