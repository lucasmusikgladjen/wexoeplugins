<?php
/**
 * Section: testimonial (section_type = "testimonial")
 *
 * Visar ETT citat. Ren sans-look — ingen Georgia-quote-mark. Stöd för
 * highlight på nyckeltal: skriv ord(en) med markdown-emphasis `*73 %*`
 * i quote-fältet så renderas det som <em> med orange linear-gradient-wash
 * (navy ink, tabular-nums). Allt annat blir vanligt text.
 *
 * Tre datakällor i prioritetsordning:
 *   1. t_quote-override på sektionen + t_author_* (inline-data, ingen SSOT)
 *   2. Första matchande record i t_testimonial_manual_ids
 *   3. Första record från Collections::testimonials_for_scope() med scope
 *      (+ t_featured_only)
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

    // Tillåt markdown-emphasis (`*ord*` eller `_ord_`) som nyckeltal-highlight.
    // Markdown::to_inline ger oss `<em>` runt emphased text, vi escapear resten
    // automatiskt — vi behöver INTE esc_html på outputen sen.
    $quote_html = wexoe_pages_md_inline($quote);

    $wid = (string) ($ctx['wrapper_id'] ?? '');
    $attrs = wexoe_pages_section_attrs($section, $ctx, 'wxp-t');
    ob_start();
    ?>
    <section <?= $attrs ?>>
        <div class="wxp-section__inner wxp-t__inner">
            <?php if ($eyebrow !== ''): ?><p class="wxp-eyebrow wxp-t__eyebrow"><?= esc_html($eyebrow) ?></p><?php endif; ?>
            <blockquote class="wxp-t__quote"><?= $quote_html ?></blockquote>
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
/* Default-bg: varmt paper. Användaren kan sätta background_color på sektionen
   i Airtable för att t.ex. få en mörk gradient — då övertar inline-style. */
#<?= esc_attr($wid) ?> .wxp-t { background: #FAFAF7 !important; color: #0F0F0F !important; }
#<?= esc_attr($wid) ?> .wxp-t.wxp-section--custom-bg { background: none !important; color: inherit !important; }
#<?= esc_attr($wid) ?> .wxp-t__inner { max-width: 760px !important; text-align: center !important; }
#<?= esc_attr($wid) ?> .wxp-t__eyebrow { justify-content: center !important; margin: 0 0 40px !important; }
/* Bookend-bar EFTER eyebrow-texten (basstilen har redan ::before). */
#<?= esc_attr($wid) ?> .wxp-t__eyebrow::after { content: '' !important; display: inline-block !important; width: 22px !important; height: 1px !important; background: currentColor !important; flex-shrink: 0 !important; }
#<?= esc_attr($wid) ?> .wxp-t__quote { font-family: 'DM Sans', system-ui, sans-serif !important; font-size: clamp(1.375rem, 2.4vw, 1.75rem) !important; line-height: 1.4 !important; font-style: normal !important; font-weight: 500 !important; letter-spacing: -0.018em !important; margin: 0 auto 48px !important; padding: 0 !important; max-width: 36ch !important; color: inherit !important; background: none !important; border: none !important; quotes: none !important; }
#<?= esc_attr($wid) ?> .wxp-t__quote::before, #<?= esc_attr($wid) ?> .wxp-t__quote::after { content: none !important; }
/* Highlight på nyckeltal — orange linear-gradient-underline-wash, navy ink. */
#<?= esc_attr($wid) ?> .wxp-t__quote em { font-style: normal !important; font-weight: 600 !important; color: #11325D !important; font-variant-numeric: tabular-nums !important; background: linear-gradient(to top, rgba(242,140,40,0.16) 0%, rgba(242,140,40,0.16) 36%, transparent 36%) !important; padding: 0 0.1em !important; letter-spacing: -0.005em !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-t__quote em { color: #fff !important; background: linear-gradient(to top, rgba(242,140,40,0.32) 0%, rgba(242,140,40,0.32) 36%, transparent 36%) !important; }
#<?= esc_attr($wid) ?> .wxp-t__author { display: inline-flex !important; align-items: center !important; gap: 14px !important; }
#<?= esc_attr($wid) ?> .wxp-t__photo { width: 48px !important; height: 48px !important; border-radius: 50% !important; object-fit: cover !important; flex-shrink: 0 !important; display: block !important; box-shadow: 0 0 0 1px rgba(15,15,15,0.06) !important; }
#<?= esc_attr($wid) ?> .wxp-t__photo--placeholder { background: linear-gradient(135deg, #11325D 0%, #1a4a7a 70%, #2d6a9f 100%) !important; position: relative !important; overflow: hidden !important; }
#<?= esc_attr($wid) ?> .wxp-t__photo--placeholder::after { content: '' !important; position: absolute !important; inset: 0 !important; background: radial-gradient(circle at 35% 30%, rgba(255,255,255,0.22), transparent 55%), radial-gradient(circle at 70% 75%, rgba(0,0,0,0.15), transparent 60%) !important; }
#<?= esc_attr($wid) ?> .wxp-t__byline { text-align: left !important; display: flex !important; flex-direction: column !important; gap: 2px !important; }
#<?= esc_attr($wid) ?> .wxp-t__byline strong { font-family: 'DM Sans', system-ui, sans-serif !important; font-weight: 600 !important; font-size: 14px !important; line-height: 1.3 !important; letter-spacing: -0.005em !important; color: inherit !important; }
#<?= esc_attr($wid) ?> .wxp-t__byline span { font-size: 13px !important; font-weight: 400 !important; line-height: 1.4 !important; opacity: 0.66 !important; color: inherit !important; letter-spacing: -0.003em !important; }

@media (max-width: 720px) {
    #<?= esc_attr($wid) ?> .wxp-t__eyebrow { margin-bottom: 32px !important; }
    #<?= esc_attr($wid) ?> .wxp-t__quote { margin-bottom: 36px !important; }
}
    </style>
    <?php
    return ob_get_clean();
};
