<?php
/**
 * Section: faq (section_type = "faq")
 *
 * Hopfällbara Q&A-rader via <details>/<summary> (ingen JS).
 * faq_items-format: en rad per Q&A med pipe-separator: "Question | Answer".
 * Svar stödjer inline-markdown (**bold**, [link](url), `code`).
 */

if (!defined('ABSPATH')) exit;

return function ($section, $page, $ctx) {
    $eyebrow = (string) ($section['faq_eyebrow'] ?? '');
    $h2      = (string) ($section['faq_h2']      ?? '');
    $body    = (string) ($section['faq_body']    ?? '');
    $items_raw = is_array($section['faq_items'] ?? null) ? $section['faq_items'] : [];

    $items = [];
    foreach ($items_raw as $line) {
        if (!is_string($line)) continue;
        $parts = explode('|', $line, 2);
        if (count($parts) !== 2) continue;
        $q = trim($parts[0]);
        $a = trim($parts[1]);
        if ($q !== '' && $a !== '') {
            $items[] = ['q' => $q, 'a' => $a];
        }
    }

    if (empty($items)) return '';

    $attrs = wexoe_pages_section_attrs($section, $ctx, 'wxp-faq');
    ob_start();
    ?>
    <section <?= $attrs ?>>
        <div class="wxp-section__inner wxp-faq__inner">
            <?php if ($eyebrow !== ''): ?><p class="wxp-eyebrow"><?= esc_html($eyebrow) ?></p><?php endif; ?>
            <?php if ($h2 !== ''): ?><h2 class="wxp-h2"><?= esc_html($h2) ?></h2><?php endif; ?>
            <?php if ($body !== ''): ?><div class="wxp-body wxp-faq__body"><?= wexoe_pages_md($body) ?></div><?php endif; ?>
            <ul class="wxp-faq__list">
                <?php foreach ($items as $i => $item): ?>
                    <li class="wxp-faq__item">
                        <details<?= $i === 0 ? ' open' : '' ?>>
                            <summary class="wxp-faq__q"><?= esc_html($item['q']) ?></summary>
                            <div class="wxp-faq__a"><?= wexoe_pages_md_inline($item['a']) ?></div>
                        </details>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>
    <style>
.wxp-faq__inner { max-width: 820px; }
.wxp-faq__body { margin-bottom: 24px; }
.wxp-faq__list { list-style: none; padding: 0; margin: 0; }
.wxp-faq__item { border: 1px solid rgba(0,0,0,0.08); border-radius: 10px; margin-bottom: 8px; background: rgba(255,255,255,0.6); }
.wxp-section--theme-dark .wxp-faq__item { border-color: rgba(255,255,255,0.12); background: rgba(255,255,255,0.04); }
.wxp-faq__item details > summary { padding: 16px 20px; cursor: pointer; font-weight: 500; list-style: none; position: relative; padding-right: 48px; }
.wxp-faq__item details > summary::-webkit-details-marker { display: none; }
.wxp-faq__item details > summary::after { content: '+'; position: absolute; right: 20px; top: 50%; transform: translateY(-50%); font-size: 22px; line-height: 1; opacity: 0.6; }
.wxp-faq__item details[open] > summary::after { content: '−'; }
.wxp-faq__a { padding: 0 20px 18px; line-height: 1.6; opacity: 0.85; }
.wxp-faq__a a { color: inherit; text-decoration: underline; }
    </style>
    <?php
    return ob_get_clean();
};
