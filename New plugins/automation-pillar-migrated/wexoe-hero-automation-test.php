<?php
/**
 * Plugin Name: Wexoe Hero Automation
 * Plugin URI: https://wexoeindustry.se
 * Description: En hero-sektion för industriell automation (testversion). Använd shortcode [wexoe_hero_automation].
 * Version: 1.1.0-test
 * Author: Wexoe
 * Author URI: https://wexoeindustry.se
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wexoe-hero-automation
 */

if (!defined('ABSPATH')) {
    exit;
}

class Wexoe_Hero_Automation_Test {

    public function __construct() {
        add_shortcode('wexoe_hero_automation', array($this, 'render_shortcode'));
    }

    /**
     * Generera CSS med unik ID för scoping
     */
    private function get_styles($id) {
        $p = '#' . $id;
        
        return "
            {$p}.wexoe-hero-automation {
                --wexoe-main-blue: #11325D !important;
                --wexoe-darker-blue: #0A1F3B !important;
                --wexoe-action-orange: #F28C28 !important;
                --wexoe-white: #ffffff !important;
                --wexoe-light-blue: #1a4175 !important;
                
                position: relative !important;
                /* Break out ur container */
                width: 100vw !important;
                left: 50% !important;
                right: 50% !important;
                margin-left: -50vw !important;
                margin-right: -50vw !important;
                
                min-height: 480px !important;
                overflow: hidden !important;
                display: flex !important;
                align-items: stretch !important;
                background: var(--wexoe-main-blue) !important;
                padding: 0 !important;
                border: none !important;
                border-radius: 0 !important;
                box-shadow: none !important;
            }
            
            {$p}.wexoe-hero-automation,
            {$p}.wexoe-hero-automation * {
                box-sizing: border-box !important;
                font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif !important;
            }
            
            /* Bakgrundsformer för djup */
            {$p} .wexoe-hero-shape {
                position: absolute !important;
                background: rgba(255, 255, 255, 0.06) !important;
                transform: rotate(45deg) !important;
                z-index: 1 !important;
                pointer-events: none !important;
                border: none !important;
                border-radius: 0 !important;
                box-shadow: none !important;
            }
            
            {$p} .wexoe-hero-shape-1 {
                top: -60px !important;
                left: 8% !important;
                width: 220px !important;
                height: 220px !important;
            }
            
            {$p} .wexoe-hero-shape-2 {
                bottom: -100px !important;
                left: 22% !important;
                width: 320px !important;
                height: 320px !important;
                background: rgba(255, 255, 255, 0.04) !important;
            }
            
            {$p} .wexoe-hero-shape-3 {
                top: 15% !important;
                left: 32% !important;
                width: 140px !important;
                height: 140px !important;
                background: transparent !important;
                border: 2px solid rgba(255, 255, 255, 0.08) !important;
            }
            
            {$p} .wexoe-hero-shape-4 {
                top: 55% !important;
                left: 3% !important;
                width: 100px !important;
                height: 100px !important;
                background: rgba(57, 116, 181, 0.15) !important;
            }
            
            /* Vänster innehållsdel */
            {$p} .wexoe-hero-content {
                position: relative !important;
                z-index: 10 !important;
                width: 50% !important;
                max-width: 600px !important;
                padding: 4rem 3rem 4rem 0 !important;
                margin-left: calc((100vw - 1270px) / 2 + 40px) !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: center !important;
                background: none !important;
                border: none !important;
            }
            
            /* När skärmen är mindre än 1270px, använd bara padding */
            @media (max-width: 1270px) {
                {$p} .wexoe-hero-content {
                    margin-left: 40px !important;
                }
            }
            
            {$p} .wexoe-hero-title {
                font-size: clamp(2rem, 4vw, 2.75rem) !important;
                font-weight: 700 !important;
                color: var(--wexoe-white) !important;
                line-height: 1.2 !important;
                margin: 0 0 1.25rem 0 !important;
                padding: 0 !important;
                border: none !important;
                background: none !important;
                max-width: 500px !important;
                text-transform: none !important;
                letter-spacing: normal !important;
            }
            
            {$p} .wexoe-hero-description {
                font-size: 1.1rem !important;
                font-weight: 400 !important;
                color: rgba(255, 255, 255, 0.85) !important;
                line-height: 1.7 !important;
                margin: 0 0 2rem 0 !important;
                padding: 0 !important;
                max-width: 480px !important;
                background: none !important;
                border: none !important;
            }
            
            /* CTA-knappar */
            {$p} .wexoe-hero-actions {
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 1rem !important;
                align-items: center !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            {$p} .wexoe-hero-btn {
                display: inline-flex !important;
                align-items: center !important;
                gap: 14px !important;
                padding: 14px 24px !important;
                font-size: 1rem !important;
                font-weight: 500 !important;
                text-decoration: none !important;
                border-radius: 2px !important;
                cursor: pointer !important;
                transition: all 0.2s ease !important;
                line-height: 1.2 !important;
                border: 2px solid transparent !important;
                box-shadow: none !important;
                min-width: auto !important;
                background-image: none !important;
            }
            
            {$p} .wexoe-hero-btn-primary {
                background: var(--wexoe-action-orange) !important;
                color: var(--wexoe-white) !important;
                border-color: var(--wexoe-action-orange) !important;
            }
            
            {$p} .wexoe-hero-btn-primary:hover {
                background: #e07d1f !important;
                border-color: #e07d1f !important;
                color: var(--wexoe-white) !important;
                text-decoration: none !important;
            }
            
            {$p} .wexoe-hero-btn-secondary {
                background: transparent !important;
                color: var(--wexoe-white) !important;
                border-color: rgba(255, 255, 255, 0.4) !important;
            }
            
            {$p} .wexoe-hero-btn-secondary:hover {
                background: rgba(255, 255, 255, 0.1) !important;
                border-color: rgba(255, 255, 255, 0.6) !important;
                color: var(--wexoe-white) !important;
                text-decoration: none !important;
            }
            
            {$p} .wexoe-hero-btn-arrow {
                display: inline-block !important;
                transition: transform 0.2s ease !important;
            }
            
            {$p} .wexoe-hero-btn:hover .wexoe-hero-btn-arrow {
                transform: translateX(4px) !important;
            }
            
            /* Höger bilddel med diagonal klippning */
            {$p} .wexoe-hero-image-wrapper {
                position: absolute !important;
                top: 0 !important;
                right: 0 !important;
                width: 55% !important;
                height: 100% !important;
                z-index: 5 !important;
                overflow: hidden !important;
                background: none !important;
                border: none !important;
            }
            
            /* Diagonal mask med CSS clip-path */
            {$p} .wexoe-hero-image-wrapper::before {
                content: '' !important;
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: 100% !important;
                background: var(--wexoe-main-blue) !important;
                clip-path: polygon(0 0, 15% 0, 0 100%, 0 100%) !important;
                z-index: 2 !important;
            }
            
            {$p} .wexoe-hero-image {
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: 100% !important;
                object-fit: cover !important;
                object-position: center !important;
                border: none !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            /* Gradient overlay på bilden */
            {$p} .wexoe-hero-image-wrapper::after {
                content: '' !important;
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                width: 30% !important;
                height: 100% !important;
                background: linear-gradient(90deg, var(--wexoe-main-blue) 0%, transparent 100%) !important;
                z-index: 3 !important;
            }

            /* RESPONSIV - Tablet */
            @media (max-width: 989px) {
                {$p}.wexoe-hero-automation {
                    min-height: 400px !important;
                }
                
                {$p} .wexoe-hero-content {
                    width: 55% !important;
                    max-width: none !important;
                    margin-left: 40px !important;
                    padding: 3rem 2rem 3rem 0 !important;
                }
                
                {$p} .wexoe-hero-image-wrapper {
                    width: 50% !important;
                }
                
                {$p} .wexoe-hero-title {
                    font-size: 1.75rem !important;
                }
                
                {$p} .wexoe-hero-description {
                    font-size: 1rem !important;
                }
                
                {$p} .wexoe-hero-shape-3,
                {$p} .wexoe-hero-shape-4 {
                    display: none !important;
                }
            }
            
            /* RESPONSIV - Mobil */
            @media (max-width: 767px) {
                {$p}.wexoe-hero-automation {
                    flex-direction: column !important;
                    min-height: auto !important;
                }
                
                {$p} .wexoe-hero-content {
                    width: 85% !important;
                    max-width: none !important;
                    margin: 0 auto !important;
                    padding: 2.5rem 0 !important;
                    order: 2 !important;
                }
                
                {$p} .wexoe-hero-image-wrapper {
                    position: relative !important;
                    width: 100% !important;
                    height: 250px !important;
                    order: 1 !important;
                }
                
                {$p} .wexoe-hero-image-wrapper::before {
                    display: none !important;
                }
                
                {$p} .wexoe-hero-image-wrapper::after {
                    width: 100% !important;
                    height: 40% !important;
                    top: auto !important;
                    bottom: 0 !important;
                    background: linear-gradient(180deg, transparent 0%, var(--wexoe-main-blue) 100%) !important;
                }
                
                {$p} .wexoe-hero-image {
                    height: 100% !important;
                }
                
                {$p} .wexoe-hero-title {
                    font-size: 1.6rem !important;
                }
                
                {$p} .wexoe-hero-description {
                    font-size: 0.95rem !important;
                }
                
                {$p} .wexoe-hero-actions {
                    flex-direction: column !important;
                    align-items: flex-start !important;
                }
                
                {$p} .wexoe-hero-btn {
                    width: 100% !important;
                    justify-content: center !important;
                }
                
                {$p} .wexoe-hero-shape {
                    display: none !important;
                }
            }
        ";
    }

    /**
     * Rendera shortcode
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Industriell Automation och Styrsystem',
            'description' => 'Bygg skalbara, smarta maskiner och möjliggör smart tillverkning. Vi levererar industriell automation genom våra styrsystem, motorstyrning och smarta enheter – arkitekterade för att hjälpa dig bygga det mest effektiva systemet.',
            'image' => 'https://wexoeindustry.se/wp-content/uploads/2025/12/dsc00286-edit.jpg',
            'btn1_text' => 'Kontakta oss',
            'btn1_url' => '#kontakt',
            'btn2_text' => 'Utforska produkter',
            'btn2_url' => '#produkter',
            'show_btn2' => 'true',
        ), $atts);

        $instance_id = 'wexoe-hero-' . uniqid();
        $show_btn2 = ($atts['show_btn2'] === 'true');
        
        ob_start();
        ?>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap');
            <?php echo $this->get_styles($instance_id); ?>
        </style>
        <section id="<?php echo esc_attr($instance_id); ?>" class="wexoe-hero-automation">
            <!-- Bakgrundsformer för djup -->
            <div class="wexoe-hero-shape wexoe-hero-shape-1"></div>
            <div class="wexoe-hero-shape wexoe-hero-shape-2"></div>
            <div class="wexoe-hero-shape wexoe-hero-shape-3"></div>
            <div class="wexoe-hero-shape wexoe-hero-shape-4"></div>
            
            <div class="wexoe-hero-content">
                <h1 class="wexoe-hero-title"><?php echo esc_html($atts['title']); ?></h1>
                <p class="wexoe-hero-description"><?php echo esc_html($atts['description']); ?></p>
                <div class="wexoe-hero-actions">
                    <a href="<?php echo esc_url($atts['btn1_url']); ?>" class="wexoe-hero-btn wexoe-hero-btn-primary">
                        <span><?php echo esc_html($atts['btn1_text']); ?></span>
                        <span class="wexoe-hero-btn-arrow">→</span>
                    </a>
                    <?php if ($show_btn2 && !empty($atts['btn2_text'])) : ?>
                    <a href="<?php echo esc_url($atts['btn2_url']); ?>" class="wexoe-hero-btn wexoe-hero-btn-secondary">
                        <span><?php echo esc_html($atts['btn2_text']); ?></span>
                        <span class="wexoe-hero-btn-arrow">→</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="wexoe-hero-image-wrapper">
                <img src="<?php echo esc_url($atts['image']); ?>" 
                     alt="<?php echo esc_attr($atts['title']); ?>" 
                     class="wexoe-hero-image">
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
}

new Wexoe_Hero_Automation_Test();
