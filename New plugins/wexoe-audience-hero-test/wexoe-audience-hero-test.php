<?php
/**
 * Plugin Name: Wexoe Audience Hero
 * Description: Dynamic hero + value section for audience landing pages. Data via Wexoe Core. Parallel test version of the original Wexoe Audience Hero plugin.
 * Version: 2.0.0-test
 * Author: Wexoe
 */

if (!defined('ABSPATH')) exit;

if (!function_exists('wexoe_ah_test_core_ready')) {
function wexoe_ah_test_core_ready() {
    return class_exists('\\Wexoe\\Core\\Core')
        && method_exists('\\Wexoe\\Core\\Core', 'entity');
}
}

class Wexoe_Audience_Hero_Test {

    public function __construct() {
        add_shortcode('wexoe_audience', array($this, 'render_shortcode'));
    }


    /**
     * Parse markdown-style formatting
     * *text* = orange highlight (for title)
     * **text** = bold (for benefits)
     */
    private function parse_title_formatting($text) {
        // *text* becomes <em>text</em> (styled as orange)
        return preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $text);
    }
    
    private function parse_benefit_formatting($text) {
        // **text** becomes <strong>text</strong>
        return preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);
    }
    
    /**
     * Parse Airtable rich text (markdown) to HTML
     * Handles: [text](url), _italic_, **bold**, and newlines
     */
    private function parse_rich_text($text) {
        if (empty($text)) return '';
        
        // Escape HTML first
        $text = esc_html($text);
        
        // Convert markdown links: [text](url) → <a href="url">text</a>
        $text = preg_replace(
            '/\[([^\]]+)\]\(([^)]+)\)/',
            '<a href="$2" target="_blank" rel="noopener">$1</a>',
            $text
        );
        
        // Convert **bold** → <strong>bold</strong>
        $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);
        
        // Convert _italic_ → <em>italic</em> (Airtable style)
        $text = preg_replace('/\_([^_]+)\_/', '<em>$1</em>', $text);
        
        // Convert *italic* → <em>italic</em> (standard markdown)
        $text = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $text);
        
        // Convert newlines to <br> for paragraph breaks
        $text = nl2br($text);
        
        return $text;
    }
    
    /**
     * Render shortcode
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'slug' => '',
            'debug' => 'false'
        ), $atts);
        
        if (empty($atts['slug'])) {
            return '<p style="color:red;">Wexoe Audience Hero: slug parameter required</p>';
        }

        if (!wexoe_ah_test_core_ready()) {
            return '<p style="color:red;">Wexoe Audience Hero: Wexoe Core-pluginet är inte aktivt.</p>';
        }

        $repo = \Wexoe\Core\Core::entity('audience_heroes');
        if (!$repo) {
            return '<p style="color:red;">Wexoe Audience Hero: entity-schema "audience_heroes" saknas i Wexoe Core.</p>';
        }

        $data = $repo->find($atts['slug']);
        if ($data && empty($data['active'])) {
            $data = null;
        }

        if ($atts['debug'] === 'true') {
            return '<pre style="background:#f5f5f5;padding:20px;overflow:auto;">' . 
                   esc_html(print_r($data, true)) . '</pre>';
        }
        
        if (!$data) {
            return '<p style="color:red;">Wexoe Audience Hero: No data found for slug "' . 
                   esc_html($atts['slug']) . '"</p>';
        }
        
        // Generate unique ID for CSS scoping
        $id = 'wexoe-ah-' . uniqid();
        
        // Extract fields with defaults
        $eyebrow = esc_html($data['eyebrow'] ?? '');
        $title = $this->parse_title_formatting(esc_html($data['title'] ?? ''));
        $description = esc_html($data['description'] ?? '');
        $cta_text = esc_html($data['cta_text'] ?? 'Kontakta oss');
        $cta_url = esc_url($data['cta_url'] ?? '/kontakt');
        $hero_image = esc_url($data['hero_image'] ?? '');
        $stat_number = intval($data['stat_number'] ?? 0);
        $stat_label = esc_html($data['stat_label'] ?? '');

        $value_h2 = esc_html($data['value_h2'] ?? '');
        $value_text_1 = $this->parse_rich_text($data['value_text_1'] ?? '');
        $value_text_2 = $this->parse_rich_text($data['value_text_2'] ?? '');
        $benefit_1 = $this->parse_benefit_formatting(esc_html($data['benefit_1'] ?? ''));
        $benefit_2 = $this->parse_benefit_formatting(esc_html($data['benefit_2'] ?? ''));
        $benefit_3 = $this->parse_benefit_formatting(esc_html($data['benefit_3'] ?? ''));

        $case_title = esc_html($data['case_title'] ?? '');
        $case_description = esc_html($data['case_description'] ?? '');
        $case_result = esc_html($data['case_result'] ?? '');
        $case_link_text = esc_html($data['case_link_text'] ?? 'Läs mer');
        $case_link_url = esc_url($data['case_link_url'] ?? '#');
        
        ob_start();
        ?>
        
        <style>
            /* ========================================
               WEXOE AUDIENCE HERO - SCOPED CSS
               ======================================== */
            
            #<?php echo $id; ?> *,
            #<?php echo $id; ?> *::before,
            #<?php echo $id; ?> *::after {
                box-sizing: border-box !important;
            }
            
            /* Reset list styles */
            #<?php echo $id; ?> li::before {
                content: none !important;
                display: none !important;
            }
            
            /* ========================================
               HERO SECTION
               ======================================== */
            #<?php echo $id; ?> .wah-hero {
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
            
            #<?php echo $id; ?> .wah-hero::before {
                content: '' !important;
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                background-image: 
                    radial-gradient(circle at 20% 80%, rgba(255,255,255,0.03) 0%, transparent 50%),
                    radial-gradient(circle at 80% 20%, rgba(255,255,255,0.05) 0%, transparent 40%) !important;
                pointer-events: none !important;
            }
            
            /* Decorative shapes */
            #<?php echo $id; ?> .wah-shape {
                position: absolute !important;
                border-radius: 20px !important;
                transform: rotate(45deg) !important;
                pointer-events: none !important;
            }
            
            #<?php echo $id; ?> .wah-shape-1 {
                width: 300px !important;
                height: 300px !important;
                background: rgba(255, 255, 255, 0.02) !important;
                top: -100px !important;
                left: -50px !important;
            }
            
            #<?php echo $id; ?> .wah-shape-2 {
                width: 200px !important;
                height: 200px !important;
                border: 2px solid rgba(255, 255, 255, 0.05) !important;
                background: transparent !important;
                bottom: 10% !important;
                left: 30% !important;
            }
            
            #<?php echo $id; ?> .wah-shape-3 {
                width: 150px !important;
                height: 150px !important;
                background: rgba(242, 140, 40, 0.1) !important;
                top: 20% !important;
                right: 45% !important;
            }
            
            #<?php echo $id; ?> .wah-hero-container {
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
            
            #<?php echo $id; ?> .wah-hero-content {
                color: #fff !important;
            }
            
            #<?php echo $id; ?> .wah-eyebrow {
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
                background: none !important;
                border: none !important;
                padding: 0 !important;
            }
            
            #<?php echo $id; ?> .wah-eyebrow::before {
                content: '' !important;
                display: block !important;
                width: 24px !important;
                height: 2px !important;
                background: #F28C28 !important;
            }
            
            #<?php echo $id; ?> .wah-title {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: clamp(36px, 5vw, 56px) !important;
                font-weight: 700 !important;
                line-height: 1.15 !important;
                margin: 0 0 24px 0 !important;
                padding: 0 !important;
                color: #fff !important;
                background: none !important;
                border: none !important;
            }
            
            #<?php echo $id; ?> .wah-title em {
                font-style: normal !important;
                color: #F28C28 !important;
            }
            
            #<?php echo $id; ?> .wah-description {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 18px !important;
                line-height: 1.7 !important;
                color: rgba(255, 255, 255, 0.85) !important;
                margin: 0 0 32px 0 !important;
                padding: 0 !important;
                max-width: 500px !important;
                background: none !important;
                border: none !important;
            }
            
            #<?php echo $id; ?> .wah-cta {
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
                border: none !important;
                cursor: pointer !important;
            }
            
            #<?php echo $id; ?> .wah-cta:hover {
                background: #e07b1a !important;
                transform: translateY(-2px) !important;
                box-shadow: 0 8px 24px rgba(242, 140, 40, 0.3) !important;
                color: #fff !important;
            }
            
            #<?php echo $id; ?> .wah-cta svg {
                transition: transform 0.3s ease !important;
                flex-shrink: 0 !important;
            }
            
            #<?php echo $id; ?> .wah-cta:hover svg {
                transform: translateX(4px) !important;
            }
            
            /* Hero image */
            #<?php echo $id; ?> .wah-image-wrapper {
                position: relative !important;
            }
            
            #<?php echo $id; ?> .wah-image {
                position: relative !important;
                border-radius: 16px !important;
                overflow: hidden !important;
                box-shadow: 0 32px 64px rgba(0, 0, 0, 0.3) !important;
            }
            
            #<?php echo $id; ?> .wah-image img {
                display: block !important;
                width: 100% !important;
                height: auto !important;
                aspect-ratio: 4/3 !important;
                object-fit: cover !important;
                border-radius: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            /* Floating stat card */
            #<?php echo $id; ?> .wah-stat-card {
                position: absolute !important;
                bottom: -30px !important;
                left: -40px !important;
                background: #fff !important;
                padding: 20px 28px !important;
                border-radius: 12px !important;
                box-shadow: 0 16px 48px rgba(0, 0, 0, 0.15) !important;
                border: none !important;
            }
            
            #<?php echo $id; ?> .wah-stat-number {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 36px !important;
                font-weight: 700 !important;
                color: #11325D !important;
                line-height: 1 !important;
                margin: 0 !important;
                padding: 0 !important;
                background: none !important;
                border: none !important;
            }
            
            #<?php echo $id; ?> .wah-stat-label {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 14px !important;
                color: #666 !important;
                margin-top: 4px !important;
                padding: 0 !important;
                background: none !important;
                border: none !important;
            }
            
            /* ========================================
               VALUE PROPOSITION SECTION
               ======================================== */
            #<?php echo $id; ?> .wah-value {
                background: #f8f9fa !important;
                padding: 100px 0 !important;
                margin: 0 !important;
                width: 100vw !important;
                margin-left: calc(-50vw + 50%) !important;
            }
            
            #<?php echo $id; ?> .wah-value-container {
                max-width: 1270px !important;
                margin: 0 auto !important;
                padding: 0 40px !important;
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                gap: 80px !important;
                align-items: start !important;
            }
            
            #<?php echo $id; ?> .wah-value-content h2 {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: clamp(28px, 3.5vw, 40px) !important;
                font-weight: 700 !important;
                color: #11325D !important;
                line-height: 1.2 !important;
                margin: 0 0 24px 0 !important;
                padding: 0 !important;
                background: none !important;
                border: none !important;
            }
            
            #<?php echo $id; ?> .wah-value-content p {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 17px !important;
                line-height: 1.8 !important;
                color: #555 !important;
                margin: 0 0 16px 0 !important;
                padding: 0 !important;
                background: none !important;
                border: none !important;
            }
            
            #<?php echo $id; ?> .wah-value-content p a {
                color: #11325D !important;
                text-decoration: underline !important;
            }
            
            #<?php echo $id; ?> .wah-value-content p a:hover {
                color: #F28C28 !important;
            }
            
            #<?php echo $id; ?> .wah-benefits {
                margin-top: 32px !important;
                display: flex !important;
                flex-direction: column !important;
                gap: 16px !important;
                padding: 0 !important;
                list-style: none !important;
            }
            
            #<?php echo $id; ?> .wah-benefit {
                display: flex !important;
                align-items: flex-start !important;
                gap: 14px !important;
                margin: 0 !important;
                padding: 0 !important;
                background: none !important;
                border: none !important;
                list-style: none !important;
            }
            
            #<?php echo $id; ?> .wah-benefit-icon {
                flex-shrink: 0 !important;
                width: 24px !important;
                height: 24px !important;
                background: #10B981 !important;
                border-radius: 50% !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                margin-top: 2px !important;
                padding: 0 !important;
                border: none !important;
            }
            
            #<?php echo $id; ?> .wah-benefit-icon svg {
                width: 14px !important;
                height: 14px !important;
                color: #fff !important;
            }
            
            #<?php echo $id; ?> .wah-benefit-text {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 16px !important;
                color: #333 !important;
                line-height: 1.5 !important;
                margin: 0 !important;
                padding: 0 !important;
                background: none !important;
                border: none !important;
            }
            
            #<?php echo $id; ?> .wah-benefit-text strong {
                color: #11325D !important;
                font-weight: 600 !important;
            }
            
            /* Case Card */
            #<?php echo $id; ?> .wah-case-card {
                background: #fff !important;
                border-radius: 16px !important;
                padding: 32px !important;
                box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06) !important;
                border: none !important;
            }
            
            #<?php echo $id; ?> .wah-case-label {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 12px !important;
                font-weight: 600 !important;
                text-transform: uppercase !important;
                letter-spacing: 1px !important;
                color: #F28C28 !important;
                margin: 0 0 12px 0 !important;
                padding: 0 !important;
                background: none !important;
                border: none !important;
            }
            
            #<?php echo $id; ?> .wah-case-title {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 18px !important;
                font-weight: 600 !important;
                color: #11325D !important;
                line-height: 1.4 !important;
                margin: 0 0 12px 0 !important;
                padding: 0 !important;
                background: none !important;
                border: none !important;
            }
            
            #<?php echo $id; ?> .wah-case-description {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 14px !important;
                color: #666 !important;
                line-height: 1.6 !important;
                margin: 0 0 16px 0 !important;
                padding: 0 !important;
                background: none !important;
                border: none !important;
            }
            
            #<?php echo $id; ?> .wah-case-result {
                display: flex !important;
                align-items: center !important;
                gap: 8px !important;
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 14px !important;
                font-weight: 600 !important;
                color: #10B981 !important;
                margin: 0 !important;
                padding: 0 !important;
                background: none !important;
                border: none !important;
            }
            
            #<?php echo $id; ?> .wah-case-result svg {
                width: 18px !important;
                height: 18px !important;
                flex-shrink: 0 !important;
            }
            
            #<?php echo $id; ?> .wah-case-link {
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
                background: none !important;
                border: none !important;
                padding: 0 !important;
            }
            
            #<?php echo $id; ?> .wah-case-link:hover {
                color: #F28C28 !important;
            }
            
            /* ========================================
               RESPONSIVE
               ======================================== */
            @media (max-width: 989px) {
                #<?php echo $id; ?> .wah-hero-container {
                    grid-template-columns: 1fr !important;
                    gap: 40px !important;
                    padding: 60px 40px !important;
                }
                
                #<?php echo $id; ?> .wah-image-wrapper {
                    order: -1 !important;
                }
                
                #<?php echo $id; ?> .wah-stat-card {
                    bottom: -20px !important;
                    left: 20px !important;
                }
                
                #<?php echo $id; ?> .wah-value-container {
                    grid-template-columns: 1fr !important;
                    gap: 48px !important;
                }
            }
            
            @media (max-width: 767px) {
                #<?php echo $id; ?> .wah-hero {
                    min-height: auto !important;
                }
                
                #<?php echo $id; ?> .wah-hero-container {
                    padding: 48px 24px !important;
                }
                
                #<?php echo $id; ?> .wah-title {
                    font-size: 32px !important;
                }
                
                #<?php echo $id; ?> .wah-description {
                    font-size: 16px !important;
                }
                
                #<?php echo $id; ?> .wah-cta {
                    width: 100% !important;
                    justify-content: center !important;
                }
                
                /* Stat card: small and on image */
                #<?php echo $id; ?> .wah-image-wrapper {
                    position: relative !important;
                }
                
                #<?php echo $id; ?> .wah-stat-card {
                    position: absolute !important;
                    bottom: 12px !important;
                    right: 12px !important;
                    left: auto !important;
                    margin-top: 0 !important;
                    padding: 10px 14px !important;
                    border-radius: 8px !important;
                    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2) !important;
                }
                
                #<?php echo $id; ?> .wah-stat-number {
                    font-size: 20px !important;
                }
                
                #<?php echo $id; ?> .wah-stat-label {
                    font-size: 10px !important;
                    margin-top: 2px !important;
                }
                
                #<?php echo $id; ?> .wah-value-container {
                    padding: 0 24px !important;
                }
                
                #<?php echo $id; ?> .wah-value {
                    padding: 60px 0 !important;
                }
                
                #<?php echo $id; ?> .wah-case-card {
                    padding: 20px !important;
                }
                
                #<?php echo $id; ?> .wah-case-title {
                    font-size: 16px !important;
                }
            }
        </style>
        
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
        
        <div id="<?php echo $id; ?>">
            
            <!-- HERO SECTION -->
            <section class="wah-hero">
                <div class="wah-shape wah-shape-1"></div>
                <div class="wah-shape wah-shape-2"></div>
                <div class="wah-shape wah-shape-3"></div>
                
                <div class="wah-hero-container">
                    <div class="wah-hero-content">
                        <?php if ($eyebrow): ?>
                            <div class="wah-eyebrow"><?php echo $eyebrow; ?></div>
                        <?php endif; ?>
                        <h1 class="wah-title"><?php echo $title; ?></h1>
                        <p class="wah-description"><?php echo $description; ?></p>
                        <a href="<?php echo $cta_url; ?>" class="wah-cta">
                            <?php echo $cta_text; ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 12h14M12 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                    
                    <div class="wah-image-wrapper">
                        <?php if ($hero_image): ?>
                            <div class="wah-image">
                                <img src="<?php echo $hero_image; ?>" alt="<?php echo $eyebrow; ?>">
                            </div>
                        <?php endif; ?>
                        <?php if ($stat_number > 0): ?>
                            <div class="wah-stat-card">
                                <div class="wah-stat-number"><?php echo $stat_number; ?></div>
                                <div class="wah-stat-label"><?php echo $stat_label; ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
            
            <!-- VALUE PROPOSITION SECTION -->
            <section class="wah-value">
                <div class="wah-value-container">
                    <div class="wah-value-content">
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
                            <div class="wah-benefits">
                                <?php if ($benefit_1): ?>
                                    <div class="wah-benefit">
                                        <div class="wah-benefit-icon">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                        </div>
                                        <div class="wah-benefit-text"><?php echo $benefit_1; ?></div>
                                    </div>
                                <?php endif; ?>
                                <?php if ($benefit_2): ?>
                                    <div class="wah-benefit">
                                        <div class="wah-benefit-icon">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                        </div>
                                        <div class="wah-benefit-text"><?php echo $benefit_2; ?></div>
                                    </div>
                                <?php endif; ?>
                                <?php if ($benefit_3): ?>
                                    <div class="wah-benefit">
                                        <div class="wah-benefit-icon">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                        </div>
                                        <div class="wah-benefit-text"><?php echo $benefit_3; ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($case_title): ?>
                        <div class="wah-case-card">
                            <div class="wah-case-label">Kundcase</div>
                            <div class="wah-case-title"><?php echo $case_title; ?></div>
                            <?php if ($case_description): ?>
                                <p class="wah-case-description"><?php echo $case_description; ?></p>
                            <?php endif; ?>
                            <?php if ($case_result): ?>
                                <div class="wah-case-result">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                        <polyline points="22 4 12 14.01 9 11.01"/>
                                    </svg>
                                    <?php echo $case_result; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($case_link_url && $case_link_url !== '#'): ?>
                                <a href="<?php echo $case_link_url; ?>" class="wah-case-link">
                                    <?php echo $case_link_text; ?>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M5 12h14M12 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
            
        </div>
        
        <?php
        return ob_get_clean();
    }
}

// Initialize
new Wexoe_Audience_Hero_Test();
