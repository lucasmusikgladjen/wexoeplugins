<?php
/**
 * Section: hero (section_type = "hero")
 *
 * Page-toppen. Stora rubriken + subtitle + 2 CTAs + valfri bild.
 * Full-width (100vw) navy. Tre varianter via hero_variant:
 *
 *   gradient        — navy linear-gradient + radial accenter + dekor-shapes
 *                     (default). Två kolumner om hero_image_url är satt.
 *   image_overlay   — stor bg-bild (hero_bg_image_url) över hela sektionen
 *                     + mörk overlay-gradient så vit text förblir läsbar.
 *                     Två kolumner om hero_image_url är satt; annars fyller
 *                     texten hela bredden.
 *   diagonal_split  — kopierad från wexoe-landing-page: blå (50–60%) till
 *                     vänster, bild till höger, diagonal triangel-klipp i
 *                     mitten + gradient-fade. Kräver hero_image_url.
 *
 * Renderar en <h1> så pluginet undertrycker page-level H1 när hero finns.
 */

if (!defined('ABSPATH')) exit;

return function ($section, $page, $ctx) {
    $h1 = trim((string) ($section['hero_h1'] ?? $page['h1'] ?? ''));
    if ($h1 === '') return '';

    $eyebrow  = (string) ($section['hero_eyebrow']   ?? '');
    $subtitle = (string) ($section['hero_subtitle']  ?? '');
    $image    = (string) ($section['hero_image_url'] ?? '');
    $bg_image = (string) ($section['hero_bg_image_url'] ?? '');
    $cta1_t   = (string) ($section['hero_cta_text']  ?? '');
    $cta1_u   = (string) ($section['hero_cta_url']   ?? '');
    $cta2_t   = (string) ($section['hero_cta2_text'] ?? '');
    $cta2_u   = (string) ($section['hero_cta2_url']  ?? '');

    $variant = (string) ($section['hero_variant'] ?? 'gradient');
    if (!in_array($variant, ['gradient', 'image_overlay', 'diagonal_split'], true)) {
        $variant = 'gradient';
    }
    // Fallback: image_overlay utan bg-bild → gradient. diagonal_split utan
    // right-column-bild → gradient. Båda varianterna är meningslösa utan
    // sin nyckelbild.
    if ($variant === 'image_overlay' && $bg_image === '') $variant = 'gradient';
    if ($variant === 'diagonal_split' && $image === '')   $variant = 'gradient';

    // Hero har alltid full-bleed contained inner. Mörk bg + vit text är
    // hard-coded i sektionens egen CSS (.wxp-hero) och beror inte på
    // background_color-fältet på cms_page_sections.
    $wid = (string) ($ctx['wrapper_id'] ?? '');
    $extra_class = 'wxp-hero wxp-fullbleed wxp-hero--' . $variant;
    if ($image !== '') $extra_class .= ' wxp-hero--has-image';
    $attrs = wexoe_pages_section_attrs(array_merge($section, ['layout' => 'contained']), $ctx, $extra_class);

    ob_start();
    ?>
    <section <?= $attrs ?>>
        <?php if ($variant === 'gradient'): ?>
            <div class="wxp-hero__shapes" aria-hidden="true">
                <span class="wxp-hero__shape wxp-hero__shape--1"></span>
                <span class="wxp-hero__shape wxp-hero__shape--2"></span>
            </div>
        <?php elseif ($variant === 'image_overlay'): ?>
            <div class="wxp-hero__bg" aria-hidden="true" style="background-image: url('<?= esc_url($bg_image) ?>');"></div>
            <div class="wxp-hero__bg-overlay" aria-hidden="true"></div>
        <?php elseif ($variant === 'diagonal_split'): ?>
            <div class="wxp-hero__shapes" aria-hidden="true">
                <span class="wxp-hero__shape wxp-hero__shape--1"></span>
                <span class="wxp-hero__shape wxp-hero__shape--2"></span>
            </div>
            <div class="wxp-hero__diagonal" aria-hidden="true">
                <img src="<?= esc_url($image) ?>" alt="" loading="eager" decoding="async" />
            </div>
        <?php endif; ?>

        <div class="wxp-section__inner wxp-hero__inner">
            <div class="wxp-hero__text">
                <?php if ($eyebrow !== ''): ?><p class="wxp-eyebrow wxp-hero__eyebrow"><?= esc_html($eyebrow) ?></p><?php endif; ?>
                <h1 class="wxp-hero__h1"><?= esc_html($h1) ?></h1>
                <?php if ($subtitle !== ''): ?>
                    <p class="wxp-hero__subtitle"><?= nl2br(esc_html($subtitle)) ?></p>
                <?php endif; ?>
                <?php if (($cta1_t !== '' && $cta1_u !== '') || ($cta2_t !== '' && $cta2_u !== '')): ?>
                    <div class="wxp-actions wxp-hero__actions">
                        <?php if ($cta1_t !== '' && $cta1_u !== ''): ?>
                            <a class="wxp-btn wxp-btn--primary" href="<?= esc_url($cta1_u) ?>"><?= esc_html($cta1_t) ?> <span aria-hidden="true">→</span></a>
                        <?php endif; ?>
                        <?php if ($cta2_t !== '' && $cta2_u !== ''): ?>
                            <a class="wxp-btn wxp-btn--secondary" href="<?= esc_url($cta2_u) ?>"><?= esc_html($cta2_t) ?></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($image !== '' && $variant !== 'diagonal_split'): ?>
                <div class="wxp-hero__media">
                    <img src="<?= esc_url($image) ?>" alt="" loading="eager" decoding="async" />
                </div>
            <?php endif; ?>
        </div>
    </section>
    <style>
/* ============ Base (alla varianter) ============ */
#<?= esc_attr($wid) ?> .wxp-hero { position: relative !important; color: #fff !important; overflow: hidden !important; padding: 0 !important; min-height: 480px !important; display: flex !important; align-items: center !important; background: linear-gradient(135deg, #11325D 0%, #1a4a7a 55%, #2d6a9f 100%) !important; }
#<?= esc_attr($wid) ?> .wxp-hero__inner { position: relative !important; z-index: 5 !important; padding-top: 88px !important; padding-bottom: 88px !important; display: grid !important; grid-template-columns: 1fr !important; gap: 48px !important; align-items: center !important; }
#<?= esc_attr($wid) ?> .wxp-hero--has-image .wxp-hero__inner { grid-template-columns: minmax(0, 1.05fr) minmax(0, 1fr) !important; }
#<?= esc_attr($wid) ?> .wxp-hero__text { max-width: 640px !important; position: relative !important; z-index: 5 !important; }
/* Eyebrow ärver muted-vit från .wxp-section--on-dark .wxp-eyebrow (basstil).
   Hero har dock dark bg utan att vara på-dark-flaggad via section_attrs, så
   tvinga den varianten lokalt. */
#<?= esc_attr($wid) ?> .wxp-hero__eyebrow { color: rgba(255,255,255,0.72) !important; }
#<?= esc_attr($wid) ?> .wxp-hero__eyebrow::before { background: rgba(255,255,255,0.72) !important; }
#<?= esc_attr($wid) ?> .wxp-hero__h1 { font-family: 'DM Sans', system-ui, sans-serif !important; font-size: clamp(2.25rem, 5vw, 3.5rem) !important; font-weight: 700 !important; line-height: 1.08 !important; letter-spacing: -0.02em !important; color: #fff !important; margin: 0 0 18px !important; padding: 0 !important; background: none !important; text-shadow: none !important; }
#<?= esc_attr($wid) ?> .wxp-hero__subtitle { font-family: 'DM Sans', system-ui, sans-serif !important; font-size: clamp(1.05rem, 1.4vw, 1.2rem) !important; line-height: 1.6 !important; color: rgba(255,255,255,0.86) !important; max-width: 560px !important; margin: 0 0 30px !important; padding: 0 !important; background: none !important; }
#<?= esc_attr($wid) ?> .wxp-hero__actions { margin: 0 !important; }
#<?= esc_attr($wid) ?> .wxp-hero__media { position: relative !important; z-index: 5 !important; border-radius: 2px !important; overflow: hidden !important; box-shadow: 0 28px 60px rgba(0,0,0,0.35) !important; aspect-ratio: 4 / 3 !important; }
#<?= esc_attr($wid) ?> .wxp-hero__media img { width: 100% !important; height: 100% !important; object-fit: cover !important; display: block !important; border-radius: 0 !important; }

/* ============ Variant: gradient ============ */
#<?= esc_attr($wid) ?> .wxp-hero--gradient::before { content: '' !important; position: absolute !important; inset: 0 !important; background-image: radial-gradient(circle at 18% 80%, rgba(255,255,255,0.05) 0%, transparent 45%), radial-gradient(circle at 82% 22%, rgba(255,255,255,0.07) 0%, transparent 40%) !important; pointer-events: none !important; z-index: 1 !important; }
#<?= esc_attr($wid) ?> .wxp-hero__shapes { position: absolute !important; inset: 0 !important; pointer-events: none !important; z-index: 1 !important; overflow: hidden !important; }
#<?= esc_attr($wid) ?> .wxp-hero__shape { position: absolute !important; display: block !important; }
#<?= esc_attr($wid) ?> .wxp-hero__shape--1 { width: 320px !important; height: 320px !important; border-radius: 50% !important; background: rgba(255,255,255,0.04) !important; top: -80px !important; left: 12% !important; }
#<?= esc_attr($wid) ?> .wxp-hero__shape--2 { width: 220px !important; height: 220px !important; border: 2px solid rgba(255,255,255,0.05) !important; transform: rotate(45deg) !important; bottom: -50px !important; left: 6% !important; }

/* ============ Variant: image_overlay ============ */
#<?= esc_attr($wid) ?> .wxp-hero--image_overlay { background: #0A1A2E !important; }
#<?= esc_attr($wid) ?> .wxp-hero__bg { position: absolute !important; inset: 0 !important; background-size: cover !important; background-position: center !important; background-repeat: no-repeat !important; z-index: 1 !important; }
#<?= esc_attr($wid) ?> .wxp-hero__bg-overlay { position: absolute !important; inset: 0 !important; background: linear-gradient(135deg, rgba(10,26,46,0.78) 0%, rgba(17,50,93,0.62) 60%, rgba(17,50,93,0.42) 100%) !important; z-index: 2 !important; pointer-events: none !important; }
#<?= esc_attr($wid) ?> .wxp-hero--image_overlay .wxp-hero__h1 { text-shadow: 0 2px 16px rgba(0,0,0,0.32) !important; }
#<?= esc_attr($wid) ?> .wxp-hero--image_overlay .wxp-hero__subtitle { text-shadow: 0 1px 8px rgba(0,0,0,0.28) !important; }

/* ============ Variant: diagonal_split ============ */
#<?= esc_attr($wid) ?> .wxp-hero--diagonal_split { background: #11325D !important; }
#<?= esc_attr($wid) ?> .wxp-hero--diagonal_split .wxp-hero__inner { grid-template-columns: 1fr !important; }
#<?= esc_attr($wid) ?> .wxp-hero--diagonal_split .wxp-hero__text { max-width: 520px !important; }
#<?= esc_attr($wid) ?> .wxp-hero__diagonal { position: absolute !important; top: 0 !important; right: 0 !important; width: 55% !important; height: 100% !important; z-index: 2 !important; overflow: hidden !important; }
#<?= esc_attr($wid) ?> .wxp-hero__diagonal img { position: absolute !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; object-fit: cover !important; object-position: center !important; display: block !important; }
#<?= esc_attr($wid) ?> .wxp-hero__diagonal::before { content: '' !important; position: absolute !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; background: #11325D !important; clip-path: polygon(0 0, 25% 0, 0 100%, 0 100%) !important; z-index: 2 !important; }
#<?= esc_attr($wid) ?> .wxp-hero__diagonal::after { content: '' !important; position: absolute !important; top: 0 !important; left: 0 !important; width: 35% !important; height: 100% !important; background: linear-gradient(90deg, #11325D 0%, transparent 100%) !important; z-index: 3 !important; }

/* ============ Responsive ============ */
@media (max-width: 900px) {
    #<?= esc_attr($wid) ?> .wxp-hero { min-height: auto !important; }
    #<?= esc_attr($wid) ?> .wxp-hero__inner { padding-top: 64px !important; padding-bottom: 64px !important; gap: 36px !important; }
    #<?= esc_attr($wid) ?> .wxp-hero--has-image .wxp-hero__inner { grid-template-columns: 1fr !important; }
    #<?= esc_attr($wid) ?> .wxp-hero__media { aspect-ratio: 16 / 10 !important; }
    #<?= esc_attr($wid) ?> .wxp-hero__shape--1 { width: 220px !important; height: 220px !important; left: 5% !important; }
    #<?= esc_attr($wid) ?> .wxp-hero__shape--2 { display: none !important; }
    /* diagonal_split: stapla — bild blir bandet ovanför texten med en
       fade-overlay från botten så texten startar mjukt. .wxp-hero är
       display:flex (row default), så när .wxp-hero__diagonal byter till
       position:relative måste vi tvinga column-riktning för att inte få
       bilden + textinnehållet sida-vid-sida. */
    #<?= esc_attr($wid) ?> .wxp-hero--diagonal_split { flex-direction: column !important; align-items: stretch !important; }
    #<?= esc_attr($wid) ?> .wxp-hero--diagonal_split .wxp-hero__inner { padding-top: 0 !important; }
    #<?= esc_attr($wid) ?> .wxp-hero--diagonal_split .wxp-hero__diagonal { position: relative !important; width: 100% !important; height: 240px !important; flex: 0 0 auto !important; }
    #<?= esc_attr($wid) ?> .wxp-hero__diagonal::before { display: none !important; }
    #<?= esc_attr($wid) ?> .wxp-hero__diagonal::after { width: 100% !important; height: 50% !important; top: auto !important; bottom: 0 !important; left: 0 !important; background: linear-gradient(0deg, #11325D 0%, transparent 100%) !important; }
    #<?= esc_attr($wid) ?> .wxp-hero--diagonal_split .wxp-hero__text { padding: 24px 0 0 !important; max-width: 100% !important; }
}
@media (max-width: 600px) {
    #<?= esc_attr($wid) ?> .wxp-hero__inner { padding-top: 48px !important; padding-bottom: 48px !important; }
    #<?= esc_attr($wid) ?> .wxp-hero__actions { flex-direction: column !important; align-items: stretch !important; }
    #<?= esc_attr($wid) ?> .wxp-hero__actions .wxp-btn { width: 100% !important; }
}
    </style>
    <?php
    return ob_get_clean();
};
