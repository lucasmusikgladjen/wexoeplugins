<?php
/**
 * Section: news_grid (section_type = "news_grid")
 *
 * Grid med nyhets-kort. Datakälla: WordPress posts (post_type=post, publicerade).
 * Sorteras på publiceringsdatum (senaste först).
 *
 * Fält som idag är no-op (kvar för bakåtkompatibilitet med builder):
 *   - ng_article_manual_ids  (länkade artiklar i Airtable — inte WP-posts)
 *   - ng_scope_*             (country/division/topic-filter; behöver mappning till
 *                             WP-taxonomier/meta för att kunna användas)
 */

if (!defined('ABSPATH')) exit;

return function ($section, $page, $ctx) {
    $eyebrow = (string) ($section['ng_eyebrow'] ?? '');
    $h2      = (string) ($section['ng_h2']      ?? '');
    $columns = in_array(($section['ng_columns'] ?? ''), ['2', '3', '4'], true) ? (int) $section['ng_columns'] : 3;
    $limit   = max(0, (int) ($section['ng_limit'] ?? 0));
    if ($limit === 0) $limit = 6;

    $posts = get_posts([
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'numberposts'    => $limit,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'suppress_filters' => false,
    ]);

    if (empty($posts) && $h2 === '') return '';

    $wid = (string) ($ctx['wrapper_id'] ?? '');
    $attrs = wexoe_pages_section_attrs($section, $ctx, 'wxp-ng wxp-ng--cols-' . $columns);
    ob_start();
    ?>
    <section <?= $attrs ?>>
        <div class="wxp-section__inner">
            <?php if ($eyebrow !== '' || $h2 !== ''): ?>
                <div class="wxp-ng__header">
                    <?php if ($eyebrow !== ''): ?><p class="wxp-eyebrow"><?= esc_html($eyebrow) ?></p><?php endif; ?>
                    <?php if ($h2 !== ''): ?><h2 class="wxp-h2"><?= esc_html($h2) ?></h2><?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($posts)): ?>
                <ul class="wxp-ng__grid">
                    <?php foreach ($posts as $post):
                        $title = get_the_title($post);
                        if ($title === '') continue;
                        $link  = get_permalink($post);
                        $image = get_the_post_thumbnail_url($post, 'medium_large') ?: '';
                        $excerpt = wexoe_pages_post_excerpt($post, 22);
                        if ($excerpt === '') {
                            $raw_content = wp_strip_all_tags((string) get_post_field('post_content', $post->ID));
                            $excerpt = wp_trim_words(preg_replace('/\s+/', ' ', trim($raw_content)), 22);
                        }
                        $date = get_the_date('j M Y', $post);
                        $cats = get_the_category($post->ID);
                        $cat_name = (!empty($cats) && isset($cats[0])) ? $cats[0]->name : '';
                    ?>
                        <li class="wxp-ng__item">
                            <a href="<?= esc_url($link) ?>" class="wxp-ng__card">
                                <?php if ($image !== ''): ?>
                                    <div class="wxp-ng__image-wrap"><img src="<?= esc_url($image) ?>" alt="<?= esc_attr($title) ?>" class="wxp-ng__image" loading="lazy" /></div>
                                <?php else: ?>
                                    <div class="wxp-ng__image-wrap wxp-ng__image-wrap--placeholder" aria-hidden="true"></div>
                                <?php endif; ?>
                                <div class="wxp-ng__body-wrap">
                                    <p class="wxp-ng__meta">
                                        <?php if ($cat_name !== ''): ?><span class="wxp-ng__cat"><?= esc_html($cat_name) ?></span><?php endif; ?>
                                        <span class="wxp-ng__date"><?= esc_html($date) ?></span>
                                    </p>
                                    <h3 class="wxp-ng__title"><?= esc_html($title) ?></h3>
                                    <?php if ($excerpt !== ''): ?><p class="wxp-ng__desc"><?= esc_html($excerpt) ?></p><?php endif; ?>
                                    <span class="wxp-ng__cta">Läs mer <span aria-hidden="true">→</span></span>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>
    <style>
#<?= esc_attr($wid) ?> .wxp-ng__header { max-width: 720px !important; margin-bottom: 32px !important; }
#<?= esc_attr($wid) ?> .wxp-ng__grid { list-style: none !important; padding: 0 !important; margin: 0 !important; display: grid !important; gap: 24px !important; }
#<?= esc_attr($wid) ?> .wxp-ng--cols-2 .wxp-ng__grid { grid-template-columns: repeat(2, 1fr) !important; }
#<?= esc_attr($wid) ?> .wxp-ng--cols-3 .wxp-ng__grid { grid-template-columns: repeat(3, 1fr) !important; }
#<?= esc_attr($wid) ?> .wxp-ng--cols-4 .wxp-ng__grid { grid-template-columns: repeat(4, 1fr) !important; }
#<?= esc_attr($wid) ?> .wxp-ng__item { display: flex !important; list-style: none !important; padding: 0 !important; margin: 0 !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-ng__item::before { content: none !important; display: none !important; }
#<?= esc_attr($wid) ?> .wxp-ng__card { display: flex !important; flex-direction: column !important; background: #fff !important; border-radius: 2px !important; overflow: hidden !important; text-decoration: none !important; color: #1A1A1A !important; border: 1px solid rgba(15,15,15,0.10) !important; box-shadow: none !important; transition: border-color 0.22s ease, transform 0.22s ease, box-shadow 0.22s ease !important; width: 100% !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-ng__card { background: rgba(255,255,255,0.04) !important; color: #fff !important; border-color: rgba(255,255,255,0.1) !important; box-shadow: none !important; }
#<?= esc_attr($wid) ?> .wxp-ng__card:hover { transform: translateY(-2px) !important; border-color: rgba(15,15,15,0.32) !important; box-shadow: 0 14px 32px rgba(10,26,46,0.07) !important; color: #1A1A1A !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-ng__card:hover { color: #fff !important; }
#<?= esc_attr($wid) ?> .wxp-ng__image-wrap { aspect-ratio: 16 / 10 !important; overflow: hidden !important; background: #F5F6F8 !important; }
#<?= esc_attr($wid) ?> .wxp-ng__image-wrap--placeholder { background: linear-gradient(135deg, #11325D, #2d6a9f) !important; }
#<?= esc_attr($wid) ?> .wxp-ng__image { width: 100% !important; height: 100% !important; object-fit: cover !important; display: block !important; }
#<?= esc_attr($wid) ?> .wxp-ng__body-wrap { padding: 20px !important; display: flex !important; flex-direction: column !important; gap: 8px !important; flex: 1 !important; }
#<?= esc_attr($wid) ?> .wxp-ng__meta { display: flex !important; align-items: center !important; gap: 10px !important; margin: 0 0 4px !important; padding: 0 !important; font-size: 12px !important; line-height: 1 !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-ng__cat { display: inline-block !important; padding: 4px 9px !important; border-radius: 2px !important; background: rgba(17,50,93,0.08) !important; color: #11325D !important; font-weight: 700 !important; text-transform: uppercase !important; letter-spacing: 0.04em !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-ng__cat { background: rgba(255,255,255,0.12) !important; color: #fff !important; }
#<?= esc_attr($wid) ?> .wxp-ng__date { opacity: 0.65 !important; font-weight: 500 !important; color: inherit !important; }
#<?= esc_attr($wid) ?> .wxp-ng__title { font-family: 'DM Sans', system-ui, sans-serif !important; font-size: 17px !important; margin: 0 !important; padding: 0 !important; font-weight: 700 !important; line-height: 1.3 !important; color: #11325D !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-ng__title { color: #fff !important; }
#<?= esc_attr($wid) ?> .wxp-ng__desc { font-size: 14px !important; line-height: 1.55 !important; color: #4b5563 !important; margin: 0 !important; padding: 0 !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-ng__desc { color: rgba(255,255,255,0.75) !important; }
#<?= esc_attr($wid) ?> .wxp-ng__cta { font-size: 14px !important; font-weight: 600 !important; color: #F28C28 !important; margin-top: auto !important; padding-top: 4px !important; }
@media (max-width: 900px) {
    #<?= esc_attr($wid) ?> .wxp-ng--cols-3 .wxp-ng__grid, #<?= esc_attr($wid) ?> .wxp-ng--cols-4 .wxp-ng__grid { grid-template-columns: repeat(2, 1fr) !important; }
}
@media (max-width: 600px) {
    #<?= esc_attr($wid) ?> .wxp-ng__grid { grid-template-columns: 1fr !important; }
}
    </style>
    <?php
    return ob_get_clean();
};
