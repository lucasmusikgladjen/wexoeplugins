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

    $wid = (string) ($ctx['wrapper_id'] ?? '');
    $attrs = wexoe_pages_section_attrs($section, $ctx, 'wxp-t');
    ob_start();
    ?>
    <section <?= $attrs ?>>
        <div class="wxp-section__inner wxp-t__inner">
            <?php if ($eyebrow !== ''): ?><p class="wxp-eyebrow wxp-t__eyebrow"><?= esc_html($eyebrow) ?></p><?php endif; ?>
            <span class="wxp-t__mark" aria-hidden="true">&ldquo;</span>
            <blockquote class="wxp-t__quote"><?= esc_html($quote) ?></blockquote>
            <?php if ($a_name !== '' || $a_image !== ''): ?>
                <figcaption class="wxp-t__author">
                    <?php if ($a_image !== ''): ?>
                        <img class="wxp-t__photo" src="<?= esc_url($a_image) ?>" alt="" loading="lazy" />
                    <?php else: ?>
                        <span class="wxp-t__photo wxp-t__photo--placeholder" aria-hidden="true"></span>
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
/* Default-bg: ljusgrå. Användaren kan sätta background_color på sektionen i
   Airtable för att t.ex. få en mörk gradient — då övertar inline-style. */
#<?= esc_attr($wid) ?> .wxp-t { background: #F5F6F8 !important; color: #1A1A1A !important; }
#<?= esc_attr($wid) ?> .wxp-t.wxp-section--custom-bg { background: none !important; color: inherit !important; }
#<?= esc_attr($wid) ?> .wxp-t__inner { max-width: 860px !important; text-align: center !important; position: relative !important; }
#<?= esc_attr($wid) ?> .wxp-t__eyebrow { justify-content: center !important; }
#<?= esc_attr($wid) ?> .wxp-t__mark { display: block !important; font-family: Georgia, 'Times New Roman', serif !important; font-size: 6rem !important; line-height: 0.6 !important; color: #F28C28 !important; opacity: 0.55 !important; margin: 0 0 12px !important; padding: 0 !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-t__quote { font-family: 'DM Sans', system-ui, sans-serif !important; font-size: clamp(1.25rem, 2.6vw, 1.9rem) !important; line-height: 1.5 !important; font-style: normal !important; font-weight: 500 !important; margin: 0 0 32px !important; padding: 0 !important; color: inherit !important; background: none !important; border: none !important; quotes: none !important; }
#<?= esc_attr($wid) ?> .wxp-t__quote::before, #<?= esc_attr($wid) ?> .wxp-t__quote::after { content: none !important; }
#<?= esc_attr($wid) ?> .wxp-t__author { display: inline-flex !important; align-items: center !important; gap: 14px !important; }
#<?= esc_attr($wid) ?> .wxp-t__photo { width: 54px !important; height: 54px !important; border-radius: 50% !important; object-fit: cover !important; flex-shrink: 0 !important; display: block !important; }
#<?= esc_attr($wid) ?> .wxp-t__photo--placeholder { background: linear-gradient(135deg, #F28C28, #11325D) !important; }
#<?= esc_attr($wid) ?> .wxp-t__byline { text-align: left !important; display: flex !important; flex-direction: column !important; gap: 2px !important; }
#<?= esc_attr($wid) ?> .wxp-t__byline strong { font-weight: 700 !important; font-size: 15px !important; color: inherit !important; }
#<?= esc_attr($wid) ?> .wxp-t__byline span { font-size: 13px !important; opacity: 0.78 !important; color: inherit !important; }
    </style>
    <?php
    return ob_get_clean();
};
