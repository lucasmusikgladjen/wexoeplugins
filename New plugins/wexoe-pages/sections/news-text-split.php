<?php
/**
 * Section: news_text_split (section_type = "news_text_split")
 *
 * Två kolumner: textblock + "senaste-nytt"-widget. Widget-källan är WordPress
 * posts (post_type=post, publicerade), sorterade på publiceringsdatum.
 *
 * Fält som idag är no-op (kvar för bakåtkompatibilitet):
 *   - nts_news_manual_ids  (Airtable-länkade artiklar — inte WP-posts)
 *   - nts_scope_*          (filter på country/division — behöver mappning till
 *                           WP-taxonomier/meta)
 */

if (!defined('ABSPATH')) exit;

return function ($section, $page, $ctx) {
    $eyebrow = (string) ($section['nts_eyebrow']  ?? '');
    $h2      = (string) ($section['nts_h2']       ?? '');
    $body    = (string) ($section['nts_body']     ?? '');
    $cta_t   = (string) ($section['nts_cta_text'] ?? '');
    $cta_u   = (string) ($section['nts_cta_url']  ?? '');
    $widget_col = (string) ($section['nts_widget_column'] ?? 'right');
    if (!in_array($widget_col, ['right', 'left'], true)) $widget_col = 'right';
    $limit = max(0, (int) ($section['nts_limit'] ?? 3));
    if ($limit === 0) $limit = 3;

    $posts = get_posts([
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'numberposts'    => $limit,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'suppress_filters' => false,
    ]);

    if ($h2 === '' && $body === '' && empty($posts)) return '';

    $wid = (string) ($ctx['wrapper_id'] ?? '');
    $attrs = wexoe_pages_section_attrs($section, $ctx, 'wxp-nts');
    ob_start();
    ?>
    <section <?= $attrs ?>>
        <div class="wxp-section__inner wxp-nts__grid wxp-nts__grid--widget-<?= esc_attr($widget_col) ?>">
            <div class="wxp-nts__text">
                <?php if ($eyebrow !== ''): ?><p class="wxp-eyebrow"><?= esc_html($eyebrow) ?></p><?php endif; ?>
                <?php if ($h2 !== ''): ?><h2 class="wxp-h2"><?= esc_html($h2) ?></h2><?php endif; ?>
                <?php if ($body !== ''): ?><div class="wxp-body"><?= wexoe_pages_md($body) ?></div><?php endif; ?>
                <?php if ($cta_t !== '' && $cta_u !== ''): ?>
                    <p class="wxp-nts__cta-row"><a class="wxp-btn wxp-btn--secondary" href="<?= esc_url($cta_u) ?>"><?= esc_html($cta_t) ?> <span aria-hidden="true">→</span></a></p>
                <?php endif; ?>
            </div>
            <?php if (!empty($posts)): ?>
                <aside class="wxp-nts__widget" aria-label="Senaste nytt">
                    <h3 class="wxp-nts__widget-h3">Senaste nytt</h3>
                    <ul class="wxp-nts__list">
                        <?php foreach ($posts as $post):
                            $title = get_the_title($post);
                            if ($title === '') continue;
                            $link  = get_permalink($post);
                            $excerpt = wexoe_pages_post_excerpt($post, 18);
                            $date = get_the_date('j M Y', $post);
                        ?>
                            <li class="wxp-nts__item">
                                <a href="<?= esc_url($link) ?>" class="wxp-nts__item-link"><?= esc_html($title) ?></a>
                                <p class="wxp-nts__item-date"><?= esc_html($date) ?></p>
                                <?php if ($excerpt !== ''): ?><p class="wxp-nts__item-excerpt"><?= esc_html($excerpt) ?></p><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </aside>
            <?php endif; ?>
        </div>
    </section>
    <style>
#<?= esc_attr($wid) ?> .wxp-nts__grid { display: grid !important; grid-template-columns: minmax(0, 1.3fr) minmax(0, 1fr) !important; gap: 56px !important; align-items: start !important; }
#<?= esc_attr($wid) ?> .wxp-nts__text { min-width: 0 !important; }
#<?= esc_attr($wid) ?> .wxp-nts__cta-row { margin: 24px 0 0 !important; padding: 0 !important; }
#<?= esc_attr($wid) ?> .wxp-nts__widget { padding: 24px 24px 24px 28px !important; background: #fff !important; border: 1px solid rgba(15,15,15,0.10) !important; border-left: 3px solid #11325D !important; border-radius: 2px !important; box-shadow: none !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-nts__widget { background: rgba(255,255,255,0.05) !important; border-color: rgba(255,255,255,0.10) !important; border-left-color: #fff !important; box-shadow: none !important; }
#<?= esc_attr($wid) ?> .wxp-nts__widget-h3 { font-family: 'DM Sans', system-ui, sans-serif !important; font-size: 12px !important; text-transform: uppercase !important; letter-spacing: 0.1em !important; margin: 0 0 16px !important; padding: 0 !important; opacity: 0.78 !important; font-weight: 700 !important; color: inherit !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-nts__list { list-style: none !important; padding: 0 !important; margin: 0 !important; }
#<?= esc_attr($wid) ?> .wxp-nts__item { list-style: none !important; padding: 14px 0 !important; margin: 0 !important; border-bottom: 1px solid rgba(17,50,93,0.08) !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-nts__item::before { content: none !important; display: none !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-nts__item { border-color: rgba(255,255,255,0.08) !important; }
#<?= esc_attr($wid) ?> .wxp-nts__item:last-child { border-bottom: 0 !important; padding-bottom: 0 !important; }
#<?= esc_attr($wid) ?> .wxp-nts__item:first-child { padding-top: 0 !important; }
#<?= esc_attr($wid) ?> .wxp-nts__item-link, #<?= esc_attr($wid) ?> .wxp-nts__item-title { font-family: 'DM Sans', system-ui, sans-serif !important; font-weight: 600 !important; color: inherit !important; text-decoration: none !important; display: block !important; line-height: 1.4 !important; font-size: 15px !important; }
#<?= esc_attr($wid) ?> .wxp-nts__item-link:hover { color: #F28C28 !important; }
#<?= esc_attr($wid) ?> .wxp-nts__item-date { font-size: 12px !important; line-height: 1 !important; opacity: 0.6 !important; margin: 5px 0 0 !important; padding: 0 !important; color: inherit !important; background: none !important; font-weight: 500 !important; }
#<?= esc_attr($wid) ?> .wxp-nts__item-excerpt { font-size: 13px !important; line-height: 1.55 !important; margin: 6px 0 0 !important; padding: 0 !important; opacity: 0.72 !important; color: inherit !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-nts__grid--widget-left .wxp-nts__widget { order: -1 !important; }
@media (max-width: 900px) { #<?= esc_attr($wid) ?> .wxp-nts__grid { grid-template-columns: 1fr !important; gap: 32px !important; } #<?= esc_attr($wid) ?> .wxp-nts__grid--widget-left .wxp-nts__widget { order: initial !important; } }
    </style>
    <?php
    return ob_get_clean();
};
