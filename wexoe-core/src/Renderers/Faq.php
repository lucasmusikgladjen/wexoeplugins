<?php
namespace Wexoe\Core\Renderers;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * FAQ-sektion. CSS-prefix `wxr-faq__`.
 *
 * Config:
 *   h2    — string
 *   items — string[] (varje line har format "**Fråga** | Svar") eller string (multiline)
 */
class Faq {
    public static function render(array $config): string {
        $h2 = (string) ($config['h2'] ?? '');
        $items_raw = $config['items'] ?? '';

        // Acceptera både array (från lines-typ) och rå sträng.
        $lines = is_array($items_raw)
            ? $items_raw
            : array_values(array_filter(array_map('trim', explode("\n", (string) $items_raw)), function ($l) {
                return $l !== '';
            }));

        $parsed = [];
        foreach ($lines as $line) {
            if (preg_match('/^\s*\*\*(.+?)\*\*\s*\|\s*(.+)$/u', $line, $m)) {
                $parsed[] = ['q' => trim($m[1]), 'a' => trim($m[2])];
            }
        }

        if (empty($parsed)) return '';

        ob_start();
        ?>
        <section class="wxr-faq">
            <div class="wxr-faq__inner">
                <?php if ($h2 !== ''): ?><h2 class="wxr-faq__h2"><?= esc_html($h2) ?></h2><?php endif; ?>
                <ul class="wxr-faq__list">
                    <?php foreach ($parsed as $i => $item): ?>
                        <li class="wxr-faq__item">
                            <details<?= $i === 0 ? ' open' : '' ?>>
                                <summary class="wxr-faq__q"><?= esc_html($item['q']) ?></summary>
                                <div class="wxr-faq__a"><?= nl2br(esc_html($item['a'])) ?></div>
                            </details>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>
        <style>
            .wxr-faq { padding: 64px 24px; background: #F5F6F8; color: #1A1A1A; }
            .wxr-faq__inner { max-width: 800px; margin: 0 auto; }
            .wxr-faq__h2 { font-size: clamp(1.5rem, 3vw, 2rem); margin: 0 0 24px; font-weight: 600; }
            .wxr-faq__list { list-style: none; padding: 0; margin: 0; }
            .wxr-faq__item { background: #fff; margin-bottom: 8px; border-radius: 8px; padding: 16px 20px; }
            .wxr-faq__q { font-weight: 500; cursor: pointer; outline: none; }
            .wxr-faq__a { margin-top: 8px; line-height: 1.6; color: #555; }
        </style>
        <?php
        return ob_get_clean();
    }
}
