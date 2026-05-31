<?php
/**
 * Section: tabs (section_type = "tabs")
 *
 * Pill-baserade tabs (likt automation-pillar "offerings"). Tab-records lever
 * i cms_section_tabs och länkas via tabs_tab_ids. Innehåll per panel:
 * eyebrow + h2 + body markdown + bullets + bild + 2 CTAs.
 *
 * Tabb-bytet är vanilla JS (~30 rader inline), inga externa deps.
 */

if (!defined('ABSPATH')) exit;

return function ($section, $page, $ctx) {
    $eyebrow = (string) ($section['tabs_eyebrow'] ?? '');
    $h2      = (string) ($section['tabs_h2']      ?? '');
    $body    = (string) ($section['tabs_intro_body'] ?? '');
    $tab_ids = is_array($section['tabs_tab_ids'] ?? null) ? $section['tabs_tab_ids'] : [];

    if (empty($tab_ids)) return '';

    $repo = \Wexoe\Core\Core::entity('cms_section_tabs');
    if ($repo === null) return wexoe_pages_debug_comment('wexoe-pages: cms_section_tabs-schema saknas');

    $tabs = array_values(array_filter(
        $repo->find_by_ids($tab_ids),
        function ($t) { return !empty($t['is_active']); }
    ));
    if (empty($tabs)) return '';

    $instance = 'wxp-tabs-' . wp_generate_password(8, false, false);
    $wid = (string) ($ctx['wrapper_id'] ?? '');
    $attrs = wexoe_pages_section_attrs($section, $ctx, 'wxp-tabs');

    ob_start();
    ?>
    <section <?= $attrs ?>>
        <div class="wxp-section__inner">
            <?php if ($eyebrow !== ''): ?><p class="wxp-eyebrow"><?= esc_html($eyebrow) ?></p><?php endif; ?>
            <?php if ($h2 !== ''): ?><h2 class="wxp-h2"><?= esc_html($h2) ?></h2><?php endif; ?>
            <?php if ($body !== ''): ?><div class="wxp-body wxp-tabs__intro"><?= wexoe_pages_md($body) ?></div><?php endif; ?>

            <div class="wxp-tabs__bar" role="tablist" data-wxp-tabs="<?= esc_attr($instance) ?>">
                <?php foreach ($tabs as $i => $t):
                    $name = (string) ($t['name'] ?? '');
                    if ($name === '') continue;
                    $panel_id = $instance . '-panel-' . $i;
                    $tab_id = $instance . '-tab-' . $i;
                ?>
                    <button type="button" role="tab" class="wxp-tabs__pill<?= $i === 0 ? ' is-active' : '' ?>"
                            id="<?= esc_attr($tab_id) ?>"
                            aria-controls="<?= esc_attr($panel_id) ?>"
                            aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"
                            tabindex="<?= $i === 0 ? '0' : '-1' ?>"><?= esc_html($name) ?></button>
                <?php endforeach; ?>
            </div>

            <div class="wxp-tabs__panels">
                <?php foreach ($tabs as $i => $t):
                    $eb     = (string) ($t['eyebrow'] ?? '');
                    $th2    = (string) ($t['h2'] ?? '');
                    $tbody  = (string) ($t['body'] ?? '');
                    $bullets = is_array($t['bullets'] ?? null) ? $t['bullets'] : [];
                    $img    = (string) ($t['image_url'] ?? '');
                    $img_alt = (string) ($t['image_alt'] ?? $th2);
                    $c1_t   = (string) ($t['cta_text'] ?? '');
                    $c1_u   = (string) ($t['cta_url'] ?? '');
                    $c2_t   = (string) ($t['cta2_text'] ?? '');
                    $c2_u   = (string) ($t['cta2_url'] ?? '');
                    $panel_id = $instance . '-panel-' . $i;
                    $tab_id = $instance . '-tab-' . $i;
                ?>
                    <div role="tabpanel" class="wxp-tabs__panel<?= $i === 0 ? ' is-active' : '' ?>"
                         id="<?= esc_attr($panel_id) ?>"
                         aria-labelledby="<?= esc_attr($tab_id) ?>"
                         <?= $i === 0 ? '' : 'hidden' ?>>
                        <div class="wxp-tabs__panel-grid">
                            <div class="wxp-tabs__panel-text">
                                <?php if ($eb !== ''): ?><p class="wxp-eyebrow"><?= esc_html($eb) ?></p><?php endif; ?>
                                <?php if ($th2 !== ''): ?><h3 class="wxp-tabs__h3"><?= esc_html($th2) ?></h3><?php endif; ?>
                                <?php if ($tbody !== ''): ?><div class="wxp-body"><?= wexoe_pages_md($tbody) ?></div><?php endif; ?>
                                <?php if (!empty($bullets)): ?>
                                    <ul class="wxp-tabs__bullets">
                                        <?php foreach ($bullets as $b): ?>
                                            <li><span aria-hidden="true">✓</span> <?= wexoe_pages_md_inline($b) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                <?php if (($c1_t !== '' && $c1_u !== '') || ($c2_t !== '' && $c2_u !== '')): ?>
                                    <div class="wxp-actions wxp-tabs__actions">
                                        <?php if ($c1_t !== '' && $c1_u !== ''): ?>
                                            <a class="wxp-btn wxp-btn--primary" href="<?= esc_url($c1_u) ?>"><?= esc_html($c1_t) ?> <span aria-hidden="true">→</span></a>
                                        <?php endif; ?>
                                        <?php if ($c2_t !== '' && $c2_u !== ''): ?>
                                            <a class="wxp-btn wxp-btn--secondary" href="<?= esc_url($c2_u) ?>"><?= esc_html($c2_t) ?></a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($img !== ''): ?>
                                <div class="wxp-tabs__panel-image-wrap">
                                    <img class="wxp-tabs__panel-image" src="<?= esc_url($img) ?>" alt="<?= esc_attr($img_alt) ?>" loading="lazy" />
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <style>
#<?= esc_attr($wid) ?> .wxp-tabs__intro { margin-bottom: 28px !important; max-width: 60ch !important; }
/* Pills: outlined navy → fylld navy när active. Aggressiv specificity för att slå Enfold/avada/etc. */
#<?= esc_attr($wid) ?> .wxp-tabs__bar,
body #top #<?= esc_attr($wid) ?> .wxp-tabs__bar { display: flex !important; flex-wrap: wrap !important; gap: 10px !important; padding: 0 !important; margin: 0 0 36px !important; background: transparent !important; border-radius: 0 !important; box-shadow: none !important; border: 0 !important; }
#<?= esc_attr($wid) ?> .wxp-tabs__pill,
#<?= esc_attr($wid) ?> button.wxp-tabs__pill,
body #top #<?= esc_attr($wid) ?> .wxp-tabs__pill,
body #top #<?= esc_attr($wid) ?> button.wxp-tabs__pill,
html body #top #<?= esc_attr($wid) ?> button.wxp-tabs__pill,
.avia_codeblock #<?= esc_attr($wid) ?> button.wxp-tabs__pill { padding: 9px 18px !important; border: 2px solid #11325D !important; background: transparent !important; background-color: transparent !important; background-image: none !important; color: #11325D !important; font-family: 'DM Sans', system-ui, sans-serif !important; font-size: 14px !important; font-weight: 600 !important; line-height: 1.3 !important; border-radius: 999px !important; cursor: pointer !important; transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease !important; text-align: center !important; white-space: nowrap !important; min-width: 0 !important; width: auto !important; max-width: none !important; opacity: 1 !important; text-shadow: none !important; box-shadow: none !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-tabs__pill,
body #top #<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-tabs__pill { border-color: rgba(255,255,255,0.6) !important; color: #fff !important; background: transparent !important; background-color: transparent !important; }
#<?= esc_attr($wid) ?> .wxp-tabs__pill:hover,
#<?= esc_attr($wid) ?> button.wxp-tabs__pill:hover,
body #top #<?= esc_attr($wid) ?> .wxp-tabs__pill:hover,
body #top #<?= esc_attr($wid) ?> button.wxp-tabs__pill:hover,
html body #top #<?= esc_attr($wid) ?> button.wxp-tabs__pill:hover,
.avia_codeblock #<?= esc_attr($wid) ?> button.wxp-tabs__pill:hover { background: #11325D !important; background-color: #11325D !important; background-image: none !important; color: #fff !important; border-color: #11325D !important; opacity: 1 !important; }
#<?= esc_attr($wid) ?> .wxp-tabs__pill.is-active,
#<?= esc_attr($wid) ?> button.wxp-tabs__pill.is-active,
body #top #<?= esc_attr($wid) ?> .wxp-tabs__pill.is-active,
body #top #<?= esc_attr($wid) ?> button.wxp-tabs__pill.is-active,
html body #top #<?= esc_attr($wid) ?> button.wxp-tabs__pill.is-active,
.avia_codeblock #<?= esc_attr($wid) ?> button.wxp-tabs__pill.is-active { background: #11325D !important; background-color: #11325D !important; background-image: none !important; color: #fff !important; border-color: #11325D !important; opacity: 1 !important; box-shadow: 0 2px 8px rgba(17,50,93,0.18) !important; }
/* On-dark active: vit fyllning + navy text (orange friställs till action). */
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-tabs__pill.is-active,
body #top #<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-tabs__pill.is-active { background: #fff !important; background-color: #fff !important; color: #11325D !important; border-color: #fff !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-tabs__pill:hover:not(.is-active),
body #top #<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-tabs__pill:hover:not(.is-active) { background: rgba(255,255,255,0.08) !important; background-color: rgba(255,255,255,0.08) !important; color: #fff !important; border-color: #fff !important; }
#<?= esc_attr($wid) ?> .wxp-tabs__panel { display: none !important; }
#<?= esc_attr($wid) ?> .wxp-tabs__panel.is-active { display: block !important; }
#<?= esc_attr($wid) ?> .wxp-tabs__panel-grid { display: grid !important; grid-template-columns: minmax(0, 1.05fr) minmax(0, 1fr) !important; gap: 56px !important; align-items: center !important; }
#<?= esc_attr($wid) ?> .wxp-tabs__panel-text > *:first-child { margin-top: 0 !important; }
#<?= esc_attr($wid) ?> .wxp-tabs__h3 { font-family: 'DM Sans', system-ui, sans-serif !important; font-size: clamp(1.4rem, 2.5vw, 1.85rem) !important; margin: 0 0 14px !important; padding: 0 !important; font-weight: 700 !important; line-height: 1.25 !important; color: #11325D !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-section--on-dark .wxp-tabs__h3 { color: #fff !important; }
#<?= esc_attr($wid) ?> .wxp-tabs__bullets { list-style: none !important; padding: 0 !important; margin: 20px 0 24px !important; display: flex !important; flex-direction: column !important; gap: 10px !important; }
#<?= esc_attr($wid) ?> .wxp-tabs__bullets li { list-style: none !important; padding: 0 !important; margin: 0 !important; display: flex !important; gap: 12px !important; align-items: flex-start !important; line-height: 1.55 !important; color: inherit !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-tabs__bullets li::before { content: none !important; display: none !important; }
#<?= esc_attr($wid) ?> .wxp-tabs__bullets span { flex-shrink: 0 !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; width: 22px !important; height: 22px !important; border-radius: 50% !important; background: #10B981 !important; color: #fff !important; font-size: 13px !important; font-weight: 700 !important; margin-top: 2px !important; }
#<?= esc_attr($wid) ?> .wxp-tabs__actions { margin-top: 8px !important; }
#<?= esc_attr($wid) ?> .wxp-tabs__panel-image-wrap { border-radius: 2px !important; overflow: hidden !important; box-shadow: 0 18px 40px rgba(10,26,46,0.12) !important; aspect-ratio: 4 / 3 !important; background: #FAFAF7 !important; }
#<?= esc_attr($wid) ?> .wxp-tabs__panel-image { width: 100% !important; height: 100% !important; object-fit: cover !important; display: block !important; border-radius: 0 !important; }
@media (max-width: 900px) {
    #<?= esc_attr($wid) ?> .wxp-tabs__panel-grid { grid-template-columns: 1fr !important; gap: 32px !important; }
    #<?= esc_attr($wid) ?> .wxp-tabs__panel-image-wrap { aspect-ratio: 16 / 10 !important; }
}
@media (max-width: 600px) {
    #<?= esc_attr($wid) ?> .wxp-tabs__bar { display: flex !important; overflow-x: auto !important; flex-wrap: nowrap !important; -webkit-overflow-scrolling: touch !important; scrollbar-width: none !important; }
    #<?= esc_attr($wid) ?> .wxp-tabs__bar::-webkit-scrollbar { display: none !important; }
}
    </style>
    <script>
(function(){
    var bar = document.querySelector('[data-wxp-tabs="<?= esc_js($instance) ?>"]');
    if (!bar) return;
    var pills = Array.prototype.slice.call(bar.querySelectorAll('.wxp-tabs__pill'));
    var panels = Array.prototype.slice.call(document.querySelectorAll('.wxp-tabs__panels .wxp-tabs__panel')).filter(function(p){
        var id = p.getAttribute('aria-labelledby') || '';
        return id.indexOf('<?= esc_js($instance) ?>') === 0;
    });
    function activate(idx) {
        pills.forEach(function(p, i){
            var on = i === idx;
            p.classList.toggle('is-active', on);
            p.setAttribute('aria-selected', on ? 'true' : 'false');
            p.setAttribute('tabindex', on ? '0' : '-1');
        });
        panels.forEach(function(panel, i){
            var on = i === idx;
            panel.classList.toggle('is-active', on);
            if (on) panel.removeAttribute('hidden'); else panel.setAttribute('hidden', '');
        });
    }
    pills.forEach(function(p, i){
        p.addEventListener('click', function(){ activate(i); });
        p.addEventListener('keydown', function(e){
            if (e.key === 'ArrowRight') { activate((i + 1) % pills.length); pills[(i + 1) % pills.length].focus(); }
            else if (e.key === 'ArrowLeft') { var prev = (i - 1 + pills.length) % pills.length; activate(prev); pills[prev].focus(); }
        });
    });
})();
    </script>
    <?php
    return ob_get_clean();
};
