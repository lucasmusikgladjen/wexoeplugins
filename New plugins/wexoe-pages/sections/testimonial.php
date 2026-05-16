<?php
/**
 * Section: testimonial (section_type = "testimonial")
 *
 * Visar ETT citat. Tre källor i prioritetsordning:
 *   1. t_quote-override på sektionen + t_author_* (inline-data, ingen SSOT)
 *   2. Första matchande record i t_testimonial_manual_ids
 *   3. Första record från Collections::testimonials_for_scope() med scope
 *      (+ t_featured_only)
 *
 * När man väljer (2) eller (3) tas inline-fälten över endast om de är ifyllda
 * (override per fält). Vanligen lämnar man dem tomma och låter SSOT styra.
 */

if (!defined('ABSPATH')) exit;

return function ($section, $page, $ctx) {
    $eyebrow = (string) ($section['t_eyebrow'] ?? '');
    $quote_override = (string) ($section['t_quote'] ?? '');
    $author_name    = (string) ($section['t_author_name'] ?? '');
    $author_title   = (string) ($section['t_author_title'] ?? '');
    $author_image   = (string) ($section['t_author_image_url'] ?? '');
    $manual_ids = is_array($section['t_testimonial_manual_ids'] ?? null) ? $section['t_testimonial_manual_ids'] : [];

    $scope = wexoe_pages_resolve_scope($section, $ctx, [
        'country'       => 't_scope_country',
        'division'      => 't_scope_division',
        'customer_type' => 't_scope_customer_type',
    ]);
    if (!empty($section['t_featured_only'])) {
        $scope['featured_only'] = true;
    }

    // Resolva SSOT-record (manual först, scope sen).
    $ssot = null;
    if (!empty($manual_ids)) {
        $repo = \Wexoe\Core\Core::entity('core_testimonials');
        if ($repo !== null) {
            $candidates = $repo->find_by_ids($manual_ids);
            foreach ($candidates as $rec) {
                if (!empty($rec['is_active'])) { $ssot = $rec; break; }
            }
        }
    }
    if ($ssot === null && class_exists('\\Wexoe\\Core\\Helpers\\Collections')) {
        $scope_one = $scope + ['limit' => 1];
        $list = \Wexoe\Core\Helpers\Collections::testimonials_for_scope($scope_one);
        if (!empty($list)) $ssot = $list[0];
    }

    // Slå ihop override + SSOT (override vinner per fält).
    $quote = $quote_override !== '' ? $quote_override : (string) ($ssot['quote'] ?? '');
    $a_name = $author_name !== '' ? $author_name : (string) ($ssot['author_name'] ?? '');
    $a_title = $author_title !== '' ? $author_title : (string) ($ssot['author_title'] ?? '');
    $a_image = $author_image !== '' ? $author_image : (string) ($ssot['author_image_url'] ?? '');

    if ($quote === '') return '';

    $attrs = wexoe_pages_section_attrs($section, $ctx, 'wxp-t');
    ob_start();
    ?>
    <section <?= $attrs ?>>
        <div class="wxp-section__inner wxp-t__inner">
            <?php if ($eyebrow !== ''): ?><p class="wxp-eyebrow wxp-t__eyebrow"><?= esc_html($eyebrow) ?></p><?php endif; ?>
            <blockquote class="wxp-t__quote">
                <span class="wxp-t__mark" aria-hidden="true">"</span><?= esc_html($quote) ?><span class="wxp-t__mark" aria-hidden="true">"</span>
            </blockquote>
            <?php if ($a_name !== '' || $a_image !== ''): ?>
                <figcaption class="wxp-t__author">
                    <?php if ($a_image !== ''): ?>
                        <img class="wxp-t__photo" src="<?= esc_url($a_image) ?>" alt="" loading="lazy" />
                    <?php endif; ?>
                    <span class="wxp-t__byline">
                        <?php if ($a_name !== ''): ?><strong><?= esc_html($a_name) ?></strong><?php endif; ?>
                        <?php if ($a_title !== ''): ?><span><?= esc_html($a_title) ?></span><?php endif; ?>
                    </span>
                </figcaption>
            <?php endif; ?>
        </div>
    </section>
    <style>
.wxp-t { background-color: #11325D; color: #fff; }
.wxp-t.wxp-section--theme-light { background-color: #F5F6F8; color: #1A1A1A; }
.wxp-t__inner { max-width: 760px; text-align: center; }
.wxp-t__eyebrow { color: #F28C28; opacity: 1; }
.wxp-t__quote { font-size: clamp(1.25rem, 2.4vw, 1.75rem); line-height: 1.45; font-style: normal; font-weight: 500; margin: 0 0 24px; }
.wxp-t__mark { opacity: 0.4; }
.wxp-t__author { display: inline-flex; align-items: center; gap: 12px; }
.wxp-t__photo { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; }
.wxp-t__byline { text-align: left; display: flex; flex-direction: column; }
.wxp-t__byline strong { font-weight: 600; }
.wxp-t__byline span { font-size: 13px; opacity: 0.8; }
    </style>
    <?php
    return ob_get_clean();
};
