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
.wxp-tabs__intro { margin-bottom: 24px; max-width: 60ch; }
.wxp-tabs__bar { display: flex; flex-wrap: wrap; gap: 8px; padding-bottom: 16px; border-bottom: 1px solid rgba(0,0,0,0.1); margin-bottom: 32px; }
.wxp-section--theme-dark .wxp-tabs__bar { border-color: rgba(255,255,255,0.15); }
.wxp-tabs__pill { padding: 10px 18px; border-radius: 999px; border: 1px solid transparent; background: rgba(0,0,0,0.04); color: inherit; cursor: pointer; font-size: 14px; font-weight: 500; transition: background 0.15s, color 0.15s; font-family: inherit; }
.wxp-section--theme-dark .wxp-tabs__pill { background: rgba(255,255,255,0.06); }
.wxp-tabs__pill:hover { background: rgba(0,0,0,0.08); }
.wxp-section--theme-dark .wxp-tabs__pill:hover { background: rgba(255,255,255,0.12); }
.wxp-tabs__pill.is-active { background: #11325D; color: #fff; }
.wxp-section--theme-dark .wxp-tabs__pill.is-active { background: #F28C28; color: #fff; }
.wxp-tabs__panel { display: none; }
.wxp-tabs__panel.is-active { display: block; }
.wxp-tabs__panel-grid { display: grid; grid-template-columns: 1.1fr 1fr; gap: 48px; align-items: center; }
.wxp-tabs__panel-text > *:first-child { margin-top: 0; }
.wxp-tabs__h3 { font-size: clamp(1.4rem, 2.5vw, 1.85rem); margin: 0 0 12px; font-weight: 600; line-height: 1.25; }
.wxp-tabs__bullets { list-style: none; padding: 0; margin: 16px 0; }
.wxp-tabs__bullets li { padding: 6px 0; display: flex; gap: 8px; align-items: baseline; }
.wxp-tabs__bullets span { color: #16A34A; font-weight: 700; flex-shrink: 0; }
.wxp-tabs__actions { margin-top: 8px; }
.wxp-tabs__panel-image { width: 100%; height: auto; border-radius: 12px; display: block; }
@media (max-width: 720px) {
    .wxp-tabs__panel-grid { grid-template-columns: 1fr; gap: 24px; }
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
