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
.wxp-to__inner { max-width: 760px; }
.wxp-to--center .wxp-to__inner { text-align: center; }
.wxp-to__body p { margin: 0 0 16px; }
.wxp-to__body h3 { font-size: 1.25rem; margin: 24px 0 12px; }
.wxp-to__body ul, .wxp-to__body ol { padding-left: 1.4em; margin: 0 0 16px; }
.wxp-to__body a { color: #11325D; text-decoration: underline; }
.wxp-section--theme-dark .wxp-to__body a { color: #F28C28; }
    </style>
    <?php
    return ob_get_clean();
};
