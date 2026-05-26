<?php
/**
 * Section: text_only (section_type = "text_only")
 *
 * Pelarsida-typisk text-block med eyebrow + h2 + markdown body.
 * Smal layout för läsbarhet, valbar left/center-justering.
 */

if (!defined('ABSPATH')) exit;

return function ($section, $page, $ctx) {
    $eyebrow = (string) ($section['to_eyebrow'] ?? '');
    $h2      = (string) ($section['to_h2']      ?? '');
    $body    = (string) ($section['to_body']    ?? '');
    $align   = (($section['to_align'] ?? 'left') === 'center') ? 'center' : 'left';

    if ($h2 === '' && $body === '' && $eyebrow === '') return '';

    $wid = (string) ($ctx['wrapper_id'] ?? '');
    $attrs = wexoe_pages_section_attrs($section, $ctx, 'wxp-to wxp-to--' . $align);
    ob_start();
    ?>
    <section <?= $attrs ?>>
        <div class="wxp-section__inner wxp-to__inner">
            <?php if ($eyebrow !== ''): ?><p class="wxp-eyebrow"><?= esc_html($eyebrow) ?></p><?php endif; ?>
            <?php if ($h2 !== ''): ?><h2 class="wxp-h2"><?= esc_html($h2) ?></h2><?php endif; ?>
            <?php if ($body !== ''): ?><div class="wxp-body wxp-to__body"><?= wexoe_pages_md($body) ?></div><?php endif; ?>
        </div>
    </section>
    <style>
#<?= esc_attr($wid) ?> .wxp-to__inner { max-width: 760px !important; }
#<?= esc_attr($wid) ?> .wxp-to--center .wxp-to__inner { text-align: center !important; }
#<?= esc_attr($wid) ?> .wxp-to--center .wxp-eyebrow { justify-content: center !important; }
#<?= esc_attr($wid) ?> .wxp-to__body { font-size: 16px !important; line-height: 1.75 !important; color: inherit !important; }
#<?= esc_attr($wid) ?> .wxp-to__body p { margin: 0 0 16px !important; color: inherit !important; }
#<?= esc_attr($wid) ?> .wxp-to__body p:last-child { margin-bottom: 0 !important; }
#<?= esc_attr($wid) ?> .wxp-to__body h3 { font-size: 1.25rem !important; font-weight: 700 !important; margin: 28px 0 12px !important; color: #11325D !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-to__body h3 { color: #fff !important; }
/* Bullets — nolla default disc/circle från child-tema och bygg egna med ::before */
#<?= esc_attr($wid) ?> .wxp-to__body ul, #<?= esc_attr($wid) ?> .wxp-to__body ol { list-style: none !important; padding: 0 !important; margin: 18px 0 !important; display: inline-flex !important; flex-direction: column !important; gap: 8px !important; text-align: left !important; }
#<?= esc_attr($wid) ?> .wxp-to--center .wxp-to__body ul, #<?= esc_attr($wid) ?> .wxp-to--center .wxp-to__body ol { margin-left: auto !important; margin-right: auto !important; }
#<?= esc_attr($wid) ?> .wxp-to__body ul li, #<?= esc_attr($wid) ?> .wxp-to__body ol li { position: relative !important; list-style: none !important; padding: 0 0 0 22px !important; margin: 0 !important; line-height: 1.55 !important; background: none !important; color: inherit !important; }
#<?= esc_attr($wid) ?> .wxp-to__body ul li::before { content: '' !important; position: absolute !important; left: 4px !important; top: 0.7em !important; width: 6px !important; height: 6px !important; border-radius: 50% !important; background: #11325D !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-to__body ul li::before { background: #fff !important; }
#<?= esc_attr($wid) ?> .wxp-to__body ol { counter-reset: wxp-to-ol !important; }
#<?= esc_attr($wid) ?> .wxp-to__body ol li { counter-increment: wxp-to-ol !important; }
#<?= esc_attr($wid) ?> .wxp-to__body ol li::before { content: counter(wxp-to-ol) '.' !important; position: absolute !important; left: 0 !important; top: 0 !important; font-weight: 700 !important; color: #11325D !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-to__body ol li::before { color: #fff !important; }
#<?= esc_attr($wid) ?> .wxp-to__body a { color: #11325D !important; text-decoration: underline !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-to__body a { color: #fff !important; }
    </style>
    <?php
    return ob_get_clean();
};
