<?php
/**
 * Section: news_text_split (section_type = "news_text_split")
 *
 * Två kolumner: textblock + "senaste-nytt"-widget.
 * Widget-källan är cms_articles (entity 'articles') — pin-then-scope:
 * nts_news_manual_ids först, fyll på med scope upp till nts_limit.
 *
 * cms_articles saknar idag country/division/topic-fält, så scope-fälten är
 * forward-looking och fungerar som no-op tills en dedikerad news-entitet
 * skapas (eller cms_articles utökas).
 */

if (!defined('ABSPATH')) exit;

return function ($section, $page, $ctx) {
    $eyebrow = (string) ($section['nts_eyebrow']  ?? '');
    $h2      = (string) ($section['nts_h2']       ?? '');
    $body    = (string) ($section['nts_body']     ?? '');
    $cta_t   = (string) ($section['nts_cta_text'] ?? '');
    $cta_u   = (string) ($section['nts_cta_url']  ?? '');
    $manual_ids = is_array($section['nts_news_manual_ids'] ?? null) ? $section['nts_news_manual_ids'] : [];
    $limit = max(0, (int) ($section['nts_limit'] ?? 3));
    if ($limit === 0) $limit = 3;

    $articles = wexoe_pages_pin_then_scope(
        $manual_ids,
        'articles',
        function () use ($limit) {
            $repo = \Wexoe\Core\Core::entity('articles');
            if ($repo === null) return [];
            $all = $repo->all(['is_active' => true]);
            // articles har ingen tidsstämpel — vi kan inte sortera "senast först".
            // Returnera oförändrad ordning från Airtable (som typiskt är skapad-ordning).
            return $all;
        },
        $limit
    );

    if ($h2 === '' && $body === '' && empty($articles)) return '';

    $attrs = wexoe_pages_section_attrs($section, $ctx, 'wxp-nts');
    ob_start();
    ?>
    <section <?= $attrs ?>>
        <div class="wxp-section__inner wxp-nts__grid">
            <div class="wxp-nts__text">
                <?php if ($eyebrow !== ''): ?><p class="wxp-eyebrow"><?= esc_html($eyebrow) ?></p><?php endif; ?>
                <?php if ($h2 !== ''): ?><h2 class="wxp-h2"><?= esc_html($h2) ?></h2><?php endif; ?>
                <?php if ($body !== ''): ?><div class="wxp-body"><?= wexoe_pages_md($body) ?></div><?php endif; ?>
                <?php if ($cta_t !== '' && $cta_u !== ''): ?>
                    <p class="wxp-nts__cta-row"><a class="wxp-btn wxp-btn--secondary" href="<?= esc_url($cta_u) ?>"><?= esc_html($cta_t) ?> <span aria-hidden="true">→</span></a></p>
                <?php endif; ?>
            </div>
            <?php if (!empty($articles)): ?>
                <aside class="wxp-nts__widget" aria-label="Senaste nytt">
                    <h3 class="wxp-nts__widget-h3">Senaste nytt</h3>
                    <ul class="wxp-nts__list">
                        <?php foreach ($articles as $a):
                            $title = (string) ($a['name'] ?? '');
                            if ($title === '') continue;
                            $link = (string) ($a['webshop_url'] ?? $a['datasheet_url'] ?? '');
                            $desc = (string) ($a['description'] ?? '');
                            $excerpt = $desc !== '' ? wp_trim_words(strip_tags($desc), 18) : '';
                        ?>
                            <li class="wxp-nts__item">
                                <?php if ($link !== ''): ?>
                                    <a href="<?= esc_url($link) ?>" class="wxp-nts__item-link"><?= esc_html($title) ?></a>
                                <?php else: ?>
                                    <span class="wxp-nts__item-title"><?= esc_html($title) ?></span>
                                <?php endif; ?>
                                <?php if ($excerpt !== ''): ?><p class="wxp-nts__item-excerpt"><?= esc_html($excerpt) ?></p><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </aside>
            <?php endif; ?>
        </div>
    </section>
    <style>
.wxp-nts__grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: 48px; align-items: start; }
.wxp-nts__cta-row { margin: 16px 0 0; }
.wxp-nts__widget { border-left: 3px solid #F28C28; padding: 4px 0 4px 24px; }
.wxp-section--theme-dark .wxp-nts__widget { border-color: #F28C28; }
.wxp-nts__widget-h3 { font-size: 14px; text-transform: uppercase; letter-spacing: 0.08em; margin: 0 0 16px; opacity: 0.8; font-weight: 600; }
.wxp-nts__list { list-style: none; padding: 0; margin: 0; }
.wxp-nts__item { padding: 12px 0; border-bottom: 1px solid rgba(0,0,0,0.06); }
.wxp-section--theme-dark .wxp-nts__item { border-color: rgba(255,255,255,0.08); }
.wxp-nts__item:last-child { border-bottom: 0; }
.wxp-nts__item-link, .wxp-nts__item-title { font-weight: 500; color: inherit; text-decoration: none; display: block; line-height: 1.35; }
.wxp-nts__item-link:hover { color: #11325D; text-decoration: underline; }
.wxp-section--theme-dark .wxp-nts__item-link:hover { color: #F28C28; }
.wxp-nts__item-excerpt { font-size: 13px; line-height: 1.5; margin: 4px 0 0; opacity: 0.7; }
@media (max-width: 720px) { .wxp-nts__grid { grid-template-columns: 1fr; gap: 24px; } }
    </style>
    <?php
    return ob_get_clean();
};
