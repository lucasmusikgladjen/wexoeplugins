<?php
/**
 * Section: news_grid (section_type = "news_grid")
 *
 * Grid med artikel-kort. Datakälla: cms_articles (entity 'articles').
 * Pin-then-scope. cms_articles saknar idag country/division/topic-fält så
 * scope_*-fält fungerar som no-op tills entitet utökas.
 */

if (!defined('ABSPATH')) exit;

return function ($section, $page, $ctx) {
    $eyebrow = (string) ($section['ng_eyebrow'] ?? '');
    $h2      = (string) ($section['ng_h2']      ?? '');
    $columns = in_array(($section['ng_columns'] ?? ''), ['2', '3', '4'], true) ? (int) $section['ng_columns'] : 3;
    $limit   = max(0, (int) ($section['ng_limit'] ?? 0));
    $manual_ids = is_array($section['ng_article_manual_ids'] ?? null) ? $section['ng_article_manual_ids'] : [];

    $articles = wexoe_pages_pin_then_scope(
        $manual_ids,
        'articles',
        function () {
            $repo = \Wexoe\Core\Core::entity('articles');
            if ($repo === null) return [];
            return $repo->all(['is_active' => true]);
        },
        $limit
    );

    if (empty($articles) && $h2 === '') return '';

    $attrs = wexoe_pages_section_attrs($section, $ctx, 'wxp-ng wxp-ng--cols-' . $columns);
    ob_start();
    ?>
    <section <?= $attrs ?>>
        <div class="wxp-section__inner">
            <?php if ($eyebrow !== ''): ?><p class="wxp-eyebrow"><?= esc_html($eyebrow) ?></p><?php endif; ?>
            <?php if ($h2 !== ''): ?><h2 class="wxp-h2"><?= esc_html($h2) ?></h2><?php endif; ?>
            <?php if (!empty($articles)): ?>
                <ul class="wxp-ng__grid">
                    <?php foreach ($articles as $a):
                        $title = (string) ($a['name'] ?? '');
                        if ($title === '') continue;
                        $desc = (string) ($a['description'] ?? '');
                        $image = (string) ($a['image_url'] ?? '');
                        $link = (string) ($a['webshop_url'] ?? $a['datasheet_url'] ?? '');
                    ?>
                        <li class="wxp-ng__item">
                            <?php if ($link !== ''): ?><a href="<?= esc_url($link) ?>" class="wxp-ng__card"><?php else: ?><div class="wxp-ng__card wxp-ng__card--static"><?php endif; ?>
                                <?php if ($image !== ''): ?>
                                    <div class="wxp-ng__image-wrap"><img src="<?= esc_url($image) ?>" alt="<?= esc_attr($title) ?>" class="wxp-ng__image" loading="lazy" /></div>
                                <?php endif; ?>
                                <div class="wxp-ng__body-wrap">
                                    <h3 class="wxp-ng__title"><?= esc_html($title) ?></h3>
                                    <?php if ($desc !== ''): ?><p class="wxp-ng__desc"><?= esc_html(wp_trim_words(strip_tags($desc), 22)) ?></p><?php endif; ?>
                                    <?php if ($link !== ''): ?><span class="wxp-ng__cta">Läs mer <span aria-hidden="true">→</span></span><?php endif; ?>
                                </div>
                            <?php if ($link !== ''): ?></a><?php else: ?></div><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>
    <style>
.wxp-ng__grid { list-style: none; padding: 0; margin: 0; display: grid; gap: 20px; }
.wxp-ng--cols-2 .wxp-ng__grid { grid-template-columns: repeat(2, 1fr); }
.wxp-ng--cols-3 .wxp-ng__grid { grid-template-columns: repeat(3, 1fr); }
.wxp-ng--cols-4 .wxp-ng__grid { grid-template-columns: repeat(4, 1fr); }
.wxp-ng__item { display: flex; }
.wxp-ng__card { display: flex; flex-direction: column; background: #fff; border-radius: 10px; overflow: hidden; text-decoration: none; color: #1A1A1A; border: 1px solid rgba(0,0,0,0.06); transition: transform 0.15s, border-color 0.15s; width: 100%; }
.wxp-section--theme-dark .wxp-ng__card { background: rgba(255,255,255,0.04); color: #fff; border-color: rgba(255,255,255,0.1); }
.wxp-ng__card:hover { transform: translateY(-2px); border-color: rgba(17,50,93,0.2); color: #1A1A1A; }
.wxp-section--theme-dark .wxp-ng__card:hover { color: #fff; border-color: rgba(255,255,255,0.2); }
.wxp-ng__card--static { cursor: default; }
.wxp-ng__image-wrap { aspect-ratio: 16/10; overflow: hidden; background: #F5F6F8; }
.wxp-ng__image { width: 100%; height: 100%; object-fit: cover; display: block; }
.wxp-ng__body-wrap { padding: 16px; display: flex; flex-direction: column; gap: 6px; flex: 1; }
.wxp-ng__title { font-size: 16px; margin: 0; font-weight: 600; line-height: 1.3; }
.wxp-ng__desc { font-size: 13px; line-height: 1.5; opacity: 0.85; margin: 0; }
.wxp-ng__cta { font-size: 13px; font-weight: 500; color: #11325D; margin-top: auto; padding-top: 4px; }
.wxp-section--theme-dark .wxp-ng__cta { color: #F28C28; }
@media (max-width: 900px) {
    .wxp-ng--cols-3 .wxp-ng__grid, .wxp-ng--cols-4 .wxp-ng__grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 600px) { .wxp-ng__grid { grid-template-columns: 1fr !important; } }
    </style>
    <?php
    return ob_get_clean();
};
