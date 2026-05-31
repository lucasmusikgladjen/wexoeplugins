<?php
/**
 * Plugin Name: Wexoe Customer Type Page
 * Description: Publik sida per kundtyp (installatör, OEM, panelbyggare etc.). Hero + värdeproposition + case-strip + kontaktform. Data via Wexoe Core (cms_customer_type_pages + cms_cases).
 * Version: 3.0.0
 * Author: Wexoe
 *
 * Bakåtkompat: `[wexoe_audience slug="..."]` är registrerad som alias för
 * `[wexoe_customer_type slug="..."]` så att befintliga WP-sidor inte går
 * sönder. Migrera shortcodes till nya namnet vid tillfälle.
 */

if (!defined('ABSPATH')) exit;

if (!function_exists('wexoe_ctp_core_ready')) {
    function wexoe_ctp_core_ready() {
        return class_exists('\\Wexoe\\Core\\Core')
            && method_exists('\\Wexoe\\Core\\Core', 'entity');
    }
}

class Wexoe_Customer_Type_Page {

    public function __construct() {
        add_shortcode('wexoe_customer_type', array($this, 'render_shortcode'));
        // Bakåtkompat-alias — befintliga WP-sidor med [wexoe_audience] fortsätter fungera.
        add_shortcode('wexoe_audience', array($this, 'render_shortcode'));
    }

    /** *text* → <em>text</em> (orange highlight i title). */
    private function parse_title_formatting($text) {
        return preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $text);
    }

    /** **text** → <strong>text</strong>. */
    private function parse_benefit_formatting($text) {
        return preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);
    }

    /** Airtable rich-text → HTML ([text](url), _italic_, **bold**, *italic*, newlines). */
    private function parse_rich_text($text) {
        if (empty($text)) return '';
        $text = esc_html($text);
        $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank" rel="noopener">$1</a>', $text);
        $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/\_([^_]+)\_/', '<em>$1</em>', $text);
        $text = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $text);
        return nl2br($text);
    }

    /**
     * Returnera "Läs mer"-URL för ett case via den centrala permalink-tjänsten
     * (legacy_external_url-override, annars /case/<slug>/ med ev. landsprefix).
     */
    private function case_link_url($case) {
        return \Wexoe\Core\Helpers\Permalink::for_record('cms_cases', $case);
    }

    /** Hämta alla aktiva cases för en customer_type_page i ordningsföljd. */
    private function load_cases($data) {
        if (empty($data['case_ids']) || !is_array($data['case_ids'])) return [];
        if (!wexoe_ctp_core_ready()) return [];

        $repo = \Wexoe\Core\Core::entity('cms_cases');
        if ($repo === null) return [];

        $cases = [];
        foreach ($data['case_ids'] as $rec_id) {
            $case = method_exists($repo, 'find_by_record_id')
                ? $repo->find_by_record_id($rec_id)
                : null;
            // Fallback: many repos exponerar bara `find($slug)`, så hämta via all() och filtrera.
            if ($case === null && method_exists($repo, 'all')) {
                foreach ($repo->all() as $row) {
                    if (!empty($row['_record_id']) && $row['_record_id'] === $rec_id) {
                        $case = $row;
                        break;
                    }
                }
            }
            if ($case && !empty($case['is_active'])) {
                $cases[] = $case;
            }
        }

        usort($cases, function ($a, $b) {
            $ao = isset($a['order']) ? (int) $a['order'] : 0;
            $bo = isset($b['order']) ? (int) $b['order'] : 0;
            return $ao <=> $bo;
        });

        return $cases;
    }

    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'slug' => '',
            'debug' => 'false',
        ), $atts);

        if (empty($atts['slug'])) {
            return '<p style="color:red;">Wexoe Customer Type: slug parameter required</p>';
        }

        if (!wexoe_ctp_core_ready()) {
            return '<p style="color:red;">Wexoe Customer Type: Wexoe Core-pluginet är inte aktivt.</p>';
        }

        $repo = \Wexoe\Core\Core::entity('customer_type_pages');
        if (!$repo) {
            return '<p style="color:red;">Wexoe Customer Type: entity-schema "customer_type_pages" saknas i Wexoe Core.</p>';
        }

        $data = $repo->find($atts['slug']);
        if ($data && empty($data['is_active'])) {
            $data = null;
        }

        if ($atts['debug'] === 'true') {
            return '<pre style="background:#f5f5f5;padding:20px;overflow:auto;">' .
                   esc_html(print_r($data, true)) . '</pre>';
        }

        if (!$data) {
            return '<p style="color:red;">Wexoe Customer Type: No data found for slug "' .
                   esc_html($atts['slug']) . '"</p>';
        }

        $id = 'wexoe-ctp-' . uniqid();

        // Hero
        $eyebrow = esc_html($data['eyebrow'] ?? '');
        $title = $this->parse_title_formatting(esc_html($data['title'] ?? ''));
        $description = esc_html($data['description'] ?? '');
        $cta_text = esc_html($data['cta_text'] ?? 'Kontakta oss');
        $cta_url = esc_url($data['cta_url'] ?? '/kontakt');
        $hero_image = esc_url($data['hero_image_url'] ?? '');
        $stat_number = intval($data['stat_number'] ?? 0);
        $stat_label = esc_html($data['stat_label'] ?? '');

        // Value
        $value_h2 = esc_html($data['value_h2'] ?? '');
        $value_text_1 = $this->parse_rich_text($data['value_text_1'] ?? '');
        $value_text_2 = $this->parse_rich_text($data['value_text_2'] ?? '');
        $benefit_1 = $this->parse_benefit_formatting(esc_html($data['benefit_1'] ?? ''));
        $benefit_2 = $this->parse_benefit_formatting(esc_html($data['benefit_2'] ?? ''));
        $benefit_3 = $this->parse_benefit_formatting(esc_html($data['benefit_3'] ?? ''));

        // Cases (multi via case_ids → cms_cases)
        $cases = $this->load_cases($data);

        ob_start();
        ?>

        <style>
            #<?php echo $id; ?> *,
            #<?php echo $id; ?> *::before,
            #<?php echo $id; ?> *::after {
                box-sizing: border-box !important;
            }
            #<?php echo $id; ?> li::before {
                content: none !important;
                display: none !important;
            }
            #<?php echo $id; ?> strong,
            #<?php echo $id; ?> b,
            #<?php echo $id; ?> em,
            #<?php echo $id; ?> i {
                color: inherit !important;
            }

            /* HERO */
            #<?php echo $id; ?> .wctp-hero {
                position: relative !important;
                min-height: 85vh !important;
                display: flex !important;
                align-items: center !important;
                background: linear-gradient(135deg, #11325D 0%, #1a4a7a 50%, #2d6a9f 100%) !important;
                overflow: hidden !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100vw !important;
                margin-left: calc(-50vw + 50%) !important;
            }
            #<?php echo $id; ?> .wctp-hero::before {
                content: '' !important;
                position: absolute !important;
                inset: 0 !important;
                background-image:
                    radial-gradient(circle at 20% 80%, rgba(255,255,255,0.03) 0%, transparent 50%),
                    radial-gradient(circle at 80% 20%, rgba(255,255,255,0.05) 0%, transparent 40%) !important;
                pointer-events: none !important;
            }
            #<?php echo $id; ?> .wctp-hero-container {
                position: relative !important;
                z-index: 2 !important;
                width: 100% !important;
                max-width: 1270px !important;
                margin: 0 auto !important;
                padding: 80px 40px !important;
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                gap: 60px !important;
                align-items: center !important;
            }
            #<?php echo $id; ?> .wctp-hero-content { color: #fff !important; }
            #<?php echo $id; ?> .wctp-eyebrow {
                display: inline-flex !important;
                align-items: center !important;
                gap: 8px !important;
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 14px !important;
                font-weight: 600 !important;
                text-transform: uppercase !important;
                letter-spacing: 1.5px !important;
                color: #F28C28 !important;
                margin-bottom: 20px !important;
            }
            #<?php echo $id; ?> .wctp-eyebrow::before {
                content: '' !important;
                display: block !important;
                width: 24px !important;
                height: 2px !important;
                background: #F28C28 !important;
            }
            #<?php echo $id; ?> .wctp-title {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: clamp(36px, 5vw, 56px) !important;
                font-weight: 700 !important;
                line-height: 1.15 !important;
                margin: 0 0 24px 0 !important;
                color: #fff !important;
            }
            #<?php echo $id; ?> .wctp-title em { font-style: normal !important; color: #F28C28 !important; }
            #<?php echo $id; ?> .wctp-description {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 18px !important;
                line-height: 1.7 !important;
                color: rgba(255, 255, 255, 0.85) !important;
                margin: 0 0 32px 0 !important;
                max-width: 500px !important;
            }
            #<?php echo $id; ?> .wctp-cta {
                display: inline-flex !important;
                align-items: center !important;
                gap: 12px !important;
                background: #F28C28 !important;
                color: #fff !important;
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 16px !important;
                font-weight: 600 !important;
                padding: 16px 32px !important;
                border-radius: 6px !important;
                text-decoration: none !important;
                transition: all 0.3s ease !important;
            }
            #<?php echo $id; ?> .wctp-cta:hover {
                background: #e07b1a !important;
                transform: translateY(-2px) !important;
                box-shadow: 0 8px 24px rgba(242, 140, 40, 0.3) !important;
                color: #fff !important;
            }
            #<?php echo $id; ?> .wctp-image-wrapper { position: relative !important; }
            #<?php echo $id; ?> .wctp-image {
                position: relative !important;
                border-radius: 16px !important;
                overflow: hidden !important;
                box-shadow: 0 32px 64px rgba(0, 0, 0, 0.3) !important;
            }
            #<?php echo $id; ?> .wctp-image img {
                display: block !important;
                width: 100% !important;
                aspect-ratio: 4/3 !important;
                object-fit: cover !important;
            }
            #<?php echo $id; ?> .wctp-stat-card {
                position: absolute !important;
                bottom: -30px !important;
                left: -40px !important;
                background: #fff !important;
                padding: 20px 28px !important;
                border-radius: 12px !important;
                box-shadow: 0 16px 48px rgba(0, 0, 0, 0.15) !important;
            }
            #<?php echo $id; ?> .wctp-stat-number {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 36px !important;
                font-weight: 700 !important;
                color: #11325D !important;
                line-height: 1 !important;
            }
            #<?php echo $id; ?> .wctp-stat-label {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 14px !important;
                color: #666 !important;
                margin-top: 4px !important;
            }

            /* VALUE */
            #<?php echo $id; ?> .wctp-value {
                background: #f8f9fa !important;
                padding: 100px 0 !important;
                width: 100vw !important;
                margin-left: calc(-50vw + 50%) !important;
            }
            #<?php echo $id; ?> .wctp-value-container {
                max-width: 1270px !important;
                margin: 0 auto !important;
                padding: 0 40px !important;
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                gap: 80px !important;
                align-items: start !important;
            }
            #<?php echo $id; ?> .wctp-value-content h2 {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: clamp(28px, 3.5vw, 40px) !important;
                font-weight: 700 !important;
                color: #11325D !important;
                line-height: 1.2 !important;
                margin: 0 0 24px 0 !important;
            }
            #<?php echo $id; ?> .wctp-value-content p {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 17px !important;
                line-height: 1.8 !important;
                color: #555 !important;
                margin: 0 0 16px 0 !important;
            }
            #<?php echo $id; ?> .wctp-value-content p a { color: #11325D !important; text-decoration: underline !important; }
            #<?php echo $id; ?> .wctp-value-content p a:hover { color: #F28C28 !important; }
            #<?php echo $id; ?> .wctp-benefits {
                margin-top: 32px !important;
                display: flex !important;
                flex-direction: column !important;
                gap: 16px !important;
                padding: 0 !important;
                list-style: none !important;
            }
            #<?php echo $id; ?> .wctp-benefit {
                display: flex !important;
                align-items: flex-start !important;
                gap: 14px !important;
            }
            #<?php echo $id; ?> .wctp-benefit-icon {
                flex-shrink: 0 !important;
                width: 24px !important;
                height: 24px !important;
                background: #10B981 !important;
                border-radius: 50% !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                margin-top: 2px !important;
            }
            #<?php echo $id; ?> .wctp-benefit-icon svg { width: 14px !important; height: 14px !important; color: #fff !important; }
            #<?php echo $id; ?> .wctp-benefit-text {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 16px !important;
                color: #333 !important;
                line-height: 1.5 !important;
            }
            #<?php echo $id; ?> .wctp-benefit-text strong { color: #11325D !important; font-weight: 600 !important; }

            /* CASES STRIP */
            #<?php echo $id; ?> .wctp-cases-strip {
                display: flex !important;
                flex-direction: column !important;
                gap: 20px !important;
            }
            #<?php echo $id; ?> .wctp-case-card {
                background: #fff !important;
                border-radius: 16px !important;
                padding: 32px !important;
                box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06) !important;
                display: grid !important;
                grid-template-columns: 1fr !important;
                gap: 16px !important;
            }
            #<?php echo $id; ?> .wctp-case-card.has-image {
                grid-template-columns: 160px 1fr !important;
                gap: 24px !important;
                align-items: center !important;
            }
            #<?php echo $id; ?> .wctp-case-image {
                width: 100% !important;
                aspect-ratio: 1/1 !important;
                object-fit: cover !important;
                border-radius: 12px !important;
            }
            #<?php echo $id; ?> .wctp-case-label {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 12px !important;
                font-weight: 600 !important;
                text-transform: uppercase !important;
                letter-spacing: 1px !important;
                color: #F28C28 !important;
                margin: 0 0 12px 0 !important;
            }
            #<?php echo $id; ?> .wctp-case-title {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 18px !important;
                font-weight: 600 !important;
                color: #11325D !important;
                line-height: 1.4 !important;
                margin: 0 0 12px 0 !important;
            }
            #<?php echo $id; ?> .wctp-case-description {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 14px !important;
                color: #666 !important;
                line-height: 1.6 !important;
                margin: 0 0 16px 0 !important;
            }
            #<?php echo $id; ?> .wctp-case-result {
                display: flex !important;
                align-items: center !important;
                gap: 8px !important;
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 14px !important;
                font-weight: 600 !important;
                color: #10B981 !important;
            }
            #<?php echo $id; ?> .wctp-case-result svg { width: 18px !important; height: 18px !important; flex-shrink: 0 !important; }
            #<?php echo $id; ?> .wctp-case-link {
                display: inline-flex !important;
                align-items: center !important;
                gap: 6px !important;
                margin-top: 16px !important;
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 14px !important;
                font-weight: 600 !important;
                color: #11325D !important;
                text-decoration: none !important;
                transition: color 0.2s ease !important;
            }
            #<?php echo $id; ?> .wctp-case-link:hover { color: #F28C28 !important; }

            @media (max-width: 989px) {
                #<?php echo $id; ?> .wctp-hero-container { grid-template-columns: 1fr !important; gap: 40px !important; padding: 60px 40px !important; }
                #<?php echo $id; ?> .wctp-image-wrapper { order: -1 !important; }
                #<?php echo $id; ?> .wctp-stat-card { bottom: -20px !important; left: 20px !important; }
                #<?php echo $id; ?> .wctp-value-container { grid-template-columns: 1fr !important; gap: 48px !important; }
                #<?php echo $id; ?> .wctp-case-card.has-image { grid-template-columns: 1fr !important; }
            }
            @media (max-width: 767px) {
                #<?php echo $id; ?> .wctp-hero { min-height: auto !important; }
                #<?php echo $id; ?> .wctp-hero-container { padding: 48px 24px !important; }
                #<?php echo $id; ?> .wctp-title { font-size: 32px !important; }
                #<?php echo $id; ?> .wctp-description { font-size: 16px !important; }
                #<?php echo $id; ?> .wctp-cta { width: 100% !important; justify-content: center !important; }
                #<?php echo $id; ?> .wctp-stat-card { position: absolute !important; bottom: 12px !important; right: 12px !important; left: auto !important; padding: 10px 14px !important; border-radius: 8px !important; }
                #<?php echo $id; ?> .wctp-stat-number { font-size: 20px !important; }
                #<?php echo $id; ?> .wctp-stat-label { font-size: 10px !important; margin-top: 2px !important; }
                #<?php echo $id; ?> .wctp-value-container { padding: 0 24px !important; }
                #<?php echo $id; ?> .wctp-value { padding: 60px 0 !important; }
                #<?php echo $id; ?> .wctp-case-card { padding: 20px !important; }
                #<?php echo $id; ?> .wctp-case-title { font-size: 16px !important; }
            }
        </style>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">

        <div id="<?php echo $id; ?>">

            <!-- HERO -->
            <section class="wctp-hero">
                <div class="wctp-hero-container">
                    <div class="wctp-hero-content">
                        <?php if ($eyebrow): ?>
                            <div class="wctp-eyebrow"><?php echo $eyebrow; ?></div>
                        <?php endif; ?>
                        <h1 class="wctp-title"><?php echo $title; ?></h1>
                        <p class="wctp-description"><?php echo $description; ?></p>
                        <a href="<?php echo $cta_url; ?>" class="wctp-cta">
                            <?php echo $cta_text; ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 12h14M12 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>

                    <div class="wctp-image-wrapper">
                        <?php if ($hero_image): ?>
                            <div class="wctp-image">
                                <img src="<?php echo $hero_image; ?>" alt="<?php echo $eyebrow; ?>">
                            </div>
                        <?php endif; ?>
                        <?php if ($stat_number > 0): ?>
                            <div class="wctp-stat-card">
                                <div class="wctp-stat-number"><?php echo $stat_number; ?></div>
                                <div class="wctp-stat-label"><?php echo $stat_label; ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- VALUE + CASES STRIP -->
            <section class="wctp-value">
                <div class="wctp-value-container">
                    <div class="wctp-value-content">
                        <?php if ($value_h2): ?>
                            <h2><?php echo $value_h2; ?></h2>
                        <?php endif; ?>
                        <?php if ($value_text_1): ?>
                            <p><?php echo $value_text_1; ?></p>
                        <?php endif; ?>
                        <?php if ($value_text_2): ?>
                            <p><?php echo $value_text_2; ?></p>
                        <?php endif; ?>

                        <?php if ($benefit_1 || $benefit_2 || $benefit_3): ?>
                            <div class="wctp-benefits">
                                <?php foreach ([$benefit_1, $benefit_2, $benefit_3] as $b): ?>
                                    <?php if ($b): ?>
                                        <div class="wctp-benefit">
                                            <div class="wctp-benefit-icon">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                </svg>
                                            </div>
                                            <div class="wctp-benefit-text"><?php echo $b; ?></div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($cases)): ?>
                        <div class="wctp-cases-strip">
                            <?php foreach ($cases as $case): ?>
                                <?php
                                    $card_title = esc_html($case['card_title'] ?? '');
                                    if ($card_title === '') continue;
                                    $card_description = esc_html($case['card_description'] ?? '');
                                    $card_result = esc_html($case['card_result'] ?? '');
                                    $card_image = esc_url($case['card_image_url'] ?? '');
                                    $card_cta = esc_html($case['card_cta_text'] ?? 'Läs mer');
                                    $link_url = esc_url($this->case_link_url($case));
                                    $has_image_class = $card_image ? ' has-image' : '';
                                ?>
                                <div class="wctp-case-card<?php echo $has_image_class; ?>">
                                    <?php if ($card_image): ?>
                                        <img class="wctp-case-image" src="<?php echo $card_image; ?>" alt="<?php echo $card_title; ?>">
                                    <?php endif; ?>
                                    <div>
                                        <div class="wctp-case-label">Kundcase</div>
                                        <div class="wctp-case-title"><?php echo $card_title; ?></div>
                                        <?php if ($card_description): ?>
                                            <p class="wctp-case-description"><?php echo $card_description; ?></p>
                                        <?php endif; ?>
                                        <?php if ($card_result): ?>
                                            <div class="wctp-case-result">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                                    <polyline points="22 4 12 14.01 9 11.01"/>
                                                </svg>
                                                <?php echo $card_result; ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($link_url): ?>
                                            <a href="<?php echo $link_url; ?>" class="wctp-case-link">
                                                <?php echo $card_cta; ?>
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M5 12h14M12 5l7 7-7 7"/>
                                                </svg>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <?php echo $this->render_contact_form_section($data); ?>

        </div>

        <?php
        return ob_get_clean();
    }

    /**
     * Renderar Wexoe Core ContactForm-renderaren om customer_type_page-recordet har
     * show_contact_form = true. "Kontaktperson" faller tillbaka på första aktiva
     * coworker via SSOT.
     */
    private function render_contact_form_section($data) {
        if (empty($data['show_contact_form'])) return '';
        if (!class_exists('\\Wexoe\\Core\\Renderers\\ContactForm')) return '';

        $cf = \Wexoe\Core\Renderers\ContactForm::from_record($data);

        $contact_person = null;
        if (!empty($cf['show_contact_person']) && class_exists('\\Wexoe\\Core\\Helpers\\Collections')) {
            $matches = \Wexoe\Core\Helpers\Collections::coworkers_for_scope(['limit' => 1]);
            if (!empty($matches)) {
                $c = $matches[0];
                $contact_person = [
                    'name'  => $c['full_name'] ?? '',
                    'title' => $c['title'] ?? '',
                    'email' => $c['email'] ?? '',
                    'phone' => $c['phone'] ?? '',
                    'image' => (string) ($c['image_url'] ?? ''),
                ];
            }
        }

        $html = \Wexoe\Core\Renderers\ContactForm::render(array_merge($cf, [
            'source_plugin'  => 'wexoe-customer-type-page',
            'page_slug'      => $data['slug'] ?? '',
            'contact_person' => $contact_person,
        ]));

        return '<section id="kontakt" class="wctp-contact-form-wrap" style="width:100%;">' . $html . '</section>';
    }
}

new Wexoe_Customer_Type_Page();
