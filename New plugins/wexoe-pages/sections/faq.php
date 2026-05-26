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

    $wid = (string) ($ctx['wrapper_id'] ?? '');
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
                            <summary class="wxp-faq__q"><span class="wxp-faq__q-text"><?= esc_html($item['q']) ?></span><span class="wxp-faq__icon" aria-hidden="true"></span></summary>
                            <div class="wxp-faq__a"><?= wexoe_pages_md_inline($item['a']) ?></div>
                        </details>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>
    <style>
#<?= esc_attr($wid) ?> .wxp-faq__inner { max-width: 820px !important; }
#<?= esc_attr($wid) ?> .wxp-faq__body { margin-bottom: 28px !important; }
#<?= esc_attr($wid) ?> .wxp-faq__list { list-style: none !important; padding: 0 !important; margin: 0 !important; display: flex !important; flex-direction: column !important; gap: 10px !important; }
#<?= esc_attr($wid) ?> .wxp-faq__item { list-style: none !important; padding: 0 !important; margin: 0 !important; background: #fff !important; border: 1px solid rgba(15,15,15,0.10) !important; border-radius: 2px !important; box-shadow: none !important; overflow: hidden !important; }
#<?= esc_attr($wid) ?> .wxp-faq__item::before { content: none !important; display: none !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-faq__item { background: rgba(255,255,255,0.06) !important; border-color: rgba(255,255,255,0.12) !important; box-shadow: none !important; }
#<?= esc_attr($wid) ?> .wxp-faq__item details { padding: 0 !important; margin: 0 !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-faq__item summary { display: flex !important; align-items: center !important; justify-content: space-between !important; gap: 16px !important; padding: 18px 22px !important; cursor: pointer !important; font-family: 'DM Sans', system-ui, sans-serif !important; font-size: 16px !important; font-weight: 600 !important; line-height: 1.4 !important; color: #11325D !important; background: none !important; list-style: none !important; user-select: none !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-faq__item summary { color: #fff !important; }
#<?= esc_attr($wid) ?> .wxp-faq__item summary::-webkit-details-marker { display: none !important; }
#<?= esc_attr($wid) ?> .wxp-faq__item summary::marker { display: none !important; content: '' !important; }
#<?= esc_attr($wid) ?> .wxp-faq__q-text { flex: 1 !important; color: inherit !important; }
#<?= esc_attr($wid) ?> .wxp-faq__icon { position: relative !important; flex-shrink: 0 !important; width: 18px !important; height: 18px !important; display: inline-block !important; transition: transform 0.25s ease !important; }
#<?= esc_attr($wid) ?> .wxp-faq__icon::before, #<?= esc_attr($wid) ?> .wxp-faq__icon::after { content: '' !important; position: absolute !important; left: 50% !important; top: 50% !important; background: currentColor !important; transition: transform 0.25s ease, opacity 0.25s ease !important; }
#<?= esc_attr($wid) ?> .wxp-faq__icon::before { width: 14px !important; height: 2px !important; transform: translate(-50%, -50%) !important; }
#<?= esc_attr($wid) ?> .wxp-faq__icon::after { width: 2px !important; height: 14px !important; transform: translate(-50%, -50%) !important; }
#<?= esc_attr($wid) ?> .wxp-faq__item details[open] .wxp-faq__icon::after { opacity: 0 !important; }
#<?= esc_attr($wid) ?> .wxp-faq__item details[open] .wxp-faq__icon { transform: rotate(180deg) !important; }
#<?= esc_attr($wid) ?> .wxp-faq__a { padding: 0 22px 20px !important; line-height: 1.7 !important; color: #4b5563 !important; font-size: 15px !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-faq__a { color: rgba(255,255,255,0.82) !important; }
#<?= esc_attr($wid) ?> .wxp-faq__a a { color: #11325D !important; text-decoration: underline !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-faq__a a { color: #F28C28 !important; }
@media (max-width: 600px) {
    #<?= esc_attr($wid) ?> .wxp-faq__item summary { padding: 16px 18px !important; font-size: 15px !important; }
    #<?= esc_attr($wid) ?> .wxp-faq__a { padding: 0 18px 18px !important; }
}
    </style>
    <?php
    return ob_get_clean();
};
