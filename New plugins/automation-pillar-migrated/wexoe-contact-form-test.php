<?php
/**
 * Plugin Name: Wexoe Contact Form
 * Description: Konverteringsoptimerat kontaktformulär för Wexoe (testversion).
 * Version: 1.4.0-test
 * Author: Wexoe
 */

if (!defined('ABSPATH')) exit;

class WexoeContactFormTest {
    
    private $webhook_url = 'https://hook.eu1.make.com/sulae2u3lux9g9dqfabtsdngiwz46s6g';
    
    public function __construct() {
        add_shortcode('wexoe_contact_form', array($this, 'render_shortcode'));
        add_action('wp_ajax_wexoe_contact_submit_test', array($this, 'handle_submission'));
        add_action('wp_ajax_nopriv_wexoe_contact_submit_test', array($this, 'handle_submission'));
    }
    
    /**
     * Hantera formulärsubmission
     */
    public function handle_submission() {
        // Verifiera nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wexoe_contact_nonce_test')) {
            wp_send_json_error('Säkerhetsfel. Ladda om sidan och försök igen.');
            return;
        }
        
        // Samla data
        $data = array(
            'namn' => sanitize_text_field($_POST['namn'] ?? ''),
            'foretag' => sanitize_text_field($_POST['foretag'] ?? ''),
            'telefon' => sanitize_text_field($_POST['telefon'] ?? ''),
            'epost' => sanitize_email($_POST['epost'] ?? ''),
            'behov' => sanitize_text_field($_POST['behov'] ?? ''),
            'meddelande' => sanitize_textarea_field($_POST['meddelande'] ?? ''),
            'gdpr_consent' => isset($_POST['gdpr_consent']) ? true : false,
            'submitted_at' => current_time('mysql'),
            'page_url' => esc_url($_POST['page_url'] ?? ''),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '')
        );
        
        // Validera required fields
        if (empty($data['namn']) || empty($data['epost']) || empty($data['foretag']) || empty($data['telefon'])) {
            wp_send_json_error('Vänligen fyll i alla obligatoriska fält.');
            return;
        }
        
        if (!is_email($data['epost'])) {
            wp_send_json_error('Vänligen ange en giltig e-postadress.');
            return;
        }
        
        // Skicka till webhook
        $response = wp_remote_post($this->webhook_url, array(
            'body' => json_encode($data),
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Något gick fel. Försök igen eller ring oss direkt.');
            return;
        }
        
        wp_send_json_success(array(
            'message' => 'Tack ' . $data['namn'] . '! Vi har mottagit ditt meddelande och återkommer inom kort.'
        ));
    }
    
    /**
     * Render shortcode
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Prata med någon som kan automation',
            'subtitle' => '',
            'inverted' => 'false',
            'trust1' => '30+ års erfarenhet|av Rockwell och svensk industri',
            'trust2' => 'Vi säljer inte bara produkter|vi löser problem',
            'trust3' => 'Lager i Köpenhamn|snabb leverans när det krisar',
            'options' => 'Generell fråga,Diskutera ett projekt,Lägga en order,Minska stillestånd,Förbättra OEE,Info om produkt'
        ), $atts);
        
        $is_inverted = filter_var($atts['inverted'], FILTER_VALIDATE_BOOLEAN);
        
        // Parse trust signals
        $trust_signals = array();
        for ($i = 1; $i <= 3; $i++) {
            $trust = $atts["trust{$i}"];
            if (!empty($trust)) {
                $parts = explode('|', $trust);
                $trust_signals[] = array(
                    'bold' => $parts[0] ?? '',
                    'text' => $parts[1] ?? ''
                );
            }
        }
        
        // Parse dropdown options
        $options = array();
        if (!empty($atts['options'])) {
            $option_items = explode(',', $atts['options']);
            foreach ($option_items as $item) {
                $item = trim($item);
                if (!empty($item)) {
                    // Skapa value från label (lowercase, bindestreck)
                    $value = sanitize_title($item);
                    $options[] = array(
                        'value' => $value,
                        'label' => $item
                    );
                }
            }
        }
        
        $unique_id = 'wexoe-contact-' . uniqid();
        
        ob_start();
        echo $this->render_styles($unique_id, $is_inverted);
        echo $this->render_html($unique_id, $atts, $is_inverted, $trust_signals, $options);
        echo $this->render_scripts($unique_id);
        return ob_get_clean();
    }
    
    /**
     * Render CSS
     */
    private function render_styles($id, $is_inverted = false) {
        $p = '#' . $id;
        
        // Inverted mode styles
        $inverted_styles = '';
        if ($is_inverted) {
            $inverted_styles = <<<CSS
            
            /* === INVERTED MODE === */
            {$p}.wexoe-contact-section.inverted {
                background: #FFFFFF !important;
            }

            {$p}.inverted::before,
            {$p}.inverted::after,
            {$p}.inverted .wexoe-bg-shape {
                display: none !important;
            }

            {$p}.inverted .wexoe-contact-title {
                color: #11325D !important;
            }

            {$p}.inverted .wexoe-contact-subtitle {
                color: #555 !important;
            }

            {$p}.inverted .wexoe-trust-text {
                color: #444 !important;
            }

            {$p}.inverted .wexoe-trust-text strong {
                color: #11325D !important;
            }

            {$p}.inverted .wexoe-trust-icon {
                background: #2e7d32 !important;
            }

            {$p}.inverted .wexoe-contact-form-wrapper {
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12) !important;
                border: 1px solid #e0e0e0 !important;
            }
CSS;
        }
        
        return <<<CSS
        <style>
            /* === RESET === */
            {$p}.wexoe-contact-section * {
                margin: 0 !important;
                padding: 0 !important;
                box-sizing: border-box !important;
            }

            {$p}.wexoe-contact-section {
                font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif !important;
                padding: 60px 20px !important;
                background: #11325D !important;
                position: relative !important;
                /* Bryt ut ur WP container */
                width: 100vw !important;
                max-width: 100vw !important;
                margin-left: 50% !important;
                transform: translateX(-50%) !important;
                overflow: hidden !important;
            }

            /* Subtila geometriska former */
            {$p}.wexoe-contact-section::before {
                content: '' !important;
                position: absolute !important;
                top: -100px !important;
                left: -100px !important;
                width: 400px !important;
                height: 400px !important;
                background: rgba(255, 255, 255, 0.02) !important;
                transform: rotate(45deg) !important;
                pointer-events: none !important;
            }

            {$p}.wexoe-contact-section::after {
                content: '' !important;
                position: absolute !important;
                bottom: -150px !important;
                right: -100px !important;
                width: 500px !important;
                height: 500px !important;
                background: rgba(255, 255, 255, 0.015) !important;
                transform: rotate(45deg) !important;
                pointer-events: none !important;
            }

            {$p} .wexoe-bg-shape {
                position: absolute !important;
                background: rgba(255, 255, 255, 0.02) !important;
                transform: rotate(45deg) !important;
                pointer-events: none !important;
            }

            {$p} .wexoe-bg-shape-1 {
                width: 200px !important;
                height: 200px !important;
                top: 20% !important;
                left: 5% !important;
            }

            {$p} .wexoe-bg-shape-2 {
                width: 120px !important;
                height: 120px !important;
                top: 50% !important;
                right: 10% !important;
                background: rgba(242, 140, 40, 0.04) !important;
            }

            {$p} .wexoe-bg-shape-3 {
                width: 250px !important;
                height: 250px !important;
                top: -50px !important;
                right: 15% !important;
                background: rgba(0, 0, 0, 0.08) !important;
            }

            {$p} .wexoe-contact-container {
                max-width: 900px !important;
                margin: 0 auto !important;
                display: grid !important;
                grid-template-columns: 1fr 1.2fr !important;
                gap: 40px !important;
                align-items: start !important;
                position: relative !important;
                z-index: 1 !important;
            }

            /* === LEFT COLUMN === */
            {$p} .wexoe-contact-info {
                padding: 30px 0 0 0 !important;
            }

            {$p} .wexoe-contact-title {
                font-size: 1.8rem !important;
                font-weight: 700 !important;
                color: #fff !important;
                margin-bottom: 12px !important;
                line-height: 1.2 !important;
            }

            {$p} .wexoe-contact-subtitle {
                font-size: 1.1rem !important;
                color: rgba(255, 255, 255, 0.8) !important;
                line-height: 1.6 !important;
                margin-bottom: 30px !important;
            }

            {$p} .wexoe-trust-signals {
                display: flex !important;
                flex-direction: column !important;
                gap: 16px !important;
                margin-bottom: 30px !important;
            }

            {$p} .wexoe-trust-item {
                display: flex !important;
                align-items: flex-start !important;
                gap: 12px !important;
            }

            {$p} .wexoe-trust-icon {
                width: 24px !important;
                height: 24px !important;
                background: #F28C28 !important;
                border-radius: 50% !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                flex-shrink: 0 !important;
                margin-top: 2px !important;
            }

            {$p} .wexoe-trust-icon svg {
                width: 14px !important;
                height: 14px !important;
                stroke: #fff !important;
                fill: none !important;
                stroke-width: 2.5 !important;
            }

            {$p} .wexoe-trust-text {
                font-size: 0.95rem !important;
                color: rgba(255, 255, 255, 0.85) !important;
                line-height: 1.5 !important;
            }

            {$p} .wexoe-trust-text strong {
                color: #fff !important;
                font-weight: 600 !important;
            }

            {$p} .wexoe-response-badge {
                display: inline-flex !important;
                align-items: center !important;
                gap: 8px !important;
                background: rgba(255, 255, 255, 0.1) !important;
                color: rgba(255, 255, 255, 0.9) !important;
                padding: 10px 16px !important;
                border-radius: 8px !important;
                font-size: 0.9rem !important;
                font-weight: 500 !important;
                border: 1px solid rgba(255, 255, 255, 0.2) !important;
            }

            {$p} .wexoe-response-badge svg {
                width: 18px !important;
                height: 18px !important;
                stroke: #F28C28 !important;
                fill: none !important;
                stroke-width: 2 !important;
            }

            /* === RIGHT COLUMN - FORM === */
            {$p} .wexoe-contact-form-wrapper {
                background: #fff !important;
                border-radius: 12px !important;
                padding: 30px !important;
                box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08) !important;
                border: 1px solid #e5e7eb !important;
            }

            {$p} .wexoe-form-row {
                margin-bottom: 19px !important;
                width: 100% !important;
            }

            {$p} .wexoe-form-row-half {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                gap: 16px !important;
                margin-bottom: 19px !important;
            }

            {$p} .wexoe-form-label {
                display: block !important;
                font-size: 0.85rem !important;
                font-weight: 600 !important;
                color: #333 !important;
                margin-bottom: 6px !important;
            }

            {$p} .wexoe-form-label .required {
                color: #F28C28 !important;
                margin-left: 2px !important;
            }

            {$p} .wexoe-form-input,
            {$p} .wexoe-form-textarea {
                width: 100% !important;
                max-width: 100% !important;
                padding: 12px 16px !important;
                font-size: 1rem !important;
                font-family: inherit !important;
                border: 2px solid #e5e7eb !important;
                border-radius: 8px !important;
                background: #fff !important;
                color: #333 !important;
                transition: all 0.2s ease !important;
                outline: none !important;
                box-sizing: border-box !important;
            }

            {$p} .wexoe-form-input:focus,
            {$p} .wexoe-form-textarea:focus {
                border-color: #11325D !important;
                box-shadow: 0 0 0 3px rgba(17, 50, 93, 0.1) !important;
            }

            {$p} .wexoe-form-textarea {
                min-height: 80px !important;
                resize: vertical !important;
            }

            /* Select dropdown */
            {$p} .wexoe-form-select {
                width: 100% !important;
                max-width: 100% !important;
                padding: 12px 16px !important;
                font-size: 1rem !important;
                font-family: inherit !important;
                border: 2px solid #e5e7eb !important;
                border-radius: 8px !important;
                background: #fff !important;
                color: #333 !important;
                transition: all 0.2s ease !important;
                outline: none !important;
                cursor: pointer !important;
                appearance: none !important;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") !important;
                background-repeat: no-repeat !important;
                background-position: right 12px center !important;
                background-size: 18px !important;
                padding-right: 44px !important;
                box-sizing: border-box !important;
            }

            {$p} .wexoe-form-select:focus {
                border-color: #11325D !important;
                box-shadow: 0 0 0 3px rgba(17, 50, 93, 0.1) !important;
            }

            {$p} .wexoe-form-select option[disabled] {
                color: #9ca3af !important;
            }

            {$p} .wexoe-form-select option:not([disabled]) {
                color: #333 !important;
            }

            /* GDPR */
            {$p} .wexoe-gdpr-wrapper {
                margin: 23px 0 !important;
                padding: 15px !important;
                background: #f8f9fa !important;
                border-radius: 8px !important;
            }

            {$p} .wexoe-gdpr-item {
                display: flex !important;
                align-items: flex-start !important;
                gap: 12px !important;
                cursor: pointer !important;
            }

            {$p} .wexoe-gdpr-item input {
                position: absolute !important;
                opacity: 0 !important;
                width: 0 !important;
                height: 0 !important;
            }

            {$p} .wexoe-gdpr-checkbox {
                width: 20px !important;
                height: 20px !important;
                border: 2px solid #ccc !important;
                border-radius: 4px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                flex-shrink: 0 !important;
                margin-top: 2px !important;
                transition: all 0.2s ease !important;
            }

            {$p} .wexoe-gdpr-item input:checked + .wexoe-gdpr-checkbox {
                background: #11325D !important;
                border-color: #11325D !important;
            }

            {$p} .wexoe-gdpr-checkbox svg {
                width: 12px !important;
                height: 12px !important;
                stroke: #fff !important;
                fill: none !important;
                stroke-width: 3 !important;
                opacity: 0 !important;
            }

            {$p} .wexoe-gdpr-item input:checked + .wexoe-gdpr-checkbox svg {
                opacity: 1 !important;
            }

            {$p} .wexoe-gdpr-text {
                font-size: 0.8rem !important;
                color: #666 !important;
                line-height: 1.5 !important;
            }

            /* Submit button */
            {$p} .wexoe-submit-btn {
                width: 100% !important;
                min-width: unset !important;
                max-width: 100% !important;
                padding: 16px 32px !important;
                font-size: 1.1rem !important;
                font-weight: 600 !important;
                font-family: inherit !important;
                color: #fff !important;
                background: linear-gradient(135deg, #F28C28 0%, #e07b1a 100%) !important;
                border: none !important;
                border-radius: 8px !important;
                cursor: pointer !important;
                transition: all 0.3s ease !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 10px !important;
                box-shadow: 0 4px 12px rgba(242, 140, 40, 0.3) !important;
                box-sizing: border-box !important;
            }

            {$p} .wexoe-submit-btn:hover {
                background: linear-gradient(135deg, #e07b1a 0%, #d06a0a 100%) !important;
                transform: translateY(-2px) !important;
                box-shadow: 0 6px 20px rgba(242, 140, 40, 0.4) !important;
            }

            {$p} .wexoe-submit-btn:active {
                transform: translateY(0) !important;
            }

            {$p} .wexoe-submit-btn svg {
                width: 20px !important;
                height: 20px !important;
                stroke: currentColor !important;
                fill: none !important;
                stroke-width: 2.5 !important;
            }

            {$p} .wexoe-submit-btn.loading {
                pointer-events: none !important;
                opacity: 0.8 !important;
            }

            {$p} .wexoe-submit-btn .spinner {
                width: 20px !important;
                height: 20px !important;
                border: 2px solid rgba(255,255,255,0.3) !important;
                border-top-color: #fff !important;
                border-radius: 50% !important;
                animation: wexoe-spin 0.8s linear infinite !important;
            }

            @keyframes wexoe-spin {
                to { transform: rotate(360deg); }
            }

            /* Success message */
            {$p} .wexoe-success-message {
                display: none !important;
                text-align: center !important;
                padding: 40px 20px !important;
            }

            {$p} .wexoe-success-message.show {
                display: block !important;
            }

            {$p} .wexoe-success-icon {
                width: 64px !important;
                height: 64px !important;
                background: #e8f5e9 !important;
                border-radius: 50% !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                margin: 0 auto 20px auto !important;
            }

            {$p} .wexoe-success-icon svg {
                width: 32px !important;
                height: 32px !important;
                stroke: #2e7d32 !important;
                fill: none !important;
                stroke-width: 2.5 !important;
            }

            {$p} .wexoe-success-title {
                font-size: 1.5rem !important;
                font-weight: 700 !important;
                color: #11325D !important;
                margin-bottom: 12px !important;
            }

            {$p} .wexoe-success-text {
                font-size: 1rem !important;
                color: #666 !important;
                line-height: 1.6 !important;
                margin-bottom: 24px !important;
            }

            {$p} .wexoe-success-next {
                display: inline-flex !important;
                align-items: center !important;
                gap: 8px !important;
                padding: 12px 24px !important;
                background: #f8f9fa !important;
                border-radius: 8px !important;
                font-size: 0.9rem !important;
                color: #11325D !important;
                text-decoration: none !important;
            }

            /* Error message */
            {$p} .wexoe-error-message {
                display: none !important;
                background: #fef2f2 !important;
                border: 1px solid #fecaca !important;
                color: #b91c1c !important;
                padding: 12px 16px !important;
                border-radius: 8px !important;
                margin-bottom: 20px !important;
                font-size: 0.9rem !important;
            }

            {$p} .wexoe-error-message.show {
                display: block !important;
            }

            /* === RESPONSIVE === */
            @media (max-width: 800px) {
                {$p} .wexoe-contact-container {
                    grid-template-columns: 1fr !important;
                    gap: 20px !important;
                    display: flex !important;
                    flex-direction: column !important;
                    align-items: center !important;
                }

                {$p} .wexoe-contact-info {
                    padding: 0 !important;
                    text-align: center !important;
                    width: 100% !important;
                }

                {$p} .wexoe-trust-signals {
                    display: none !important;
                }

                {$p} .wexoe-contact-subtitle {
                    margin-bottom: 0 !important;
                }

                {$p} .wexoe-contact-form-wrapper {
                    max-width: 85vw !important;
                    width: 85vw !important;
                    margin: 0 auto !important;
                    margin-left: auto !important;
                    margin-right: auto !important;
                    float: none !important;
                }

                {$p} .wexoe-submit-btn {
                    width: 100% !important;
                    min-width: unset !important;
                    max-width: 100% !important;
                    box-sizing: border-box !important;
                }
            }

            @media (max-width: 500px) {
                {$p}.wexoe-contact-section {
                    padding: 40px 16px !important;
                }

                {$p} .wexoe-contact-form-wrapper {
                    padding: 24px 20px !important;
                    max-width: 90vw !important;
                    width: 90vw !important;
                }

                {$p} .wexoe-form-row-half {
                    grid-template-columns: 1fr !important;
                }

                {$p} .wexoe-contact-title {
                    font-size: 1.5rem !important;
                }

                {$p} .wexoe-submit-btn {
                    width: 100% !important;
                    min-width: unset !important;
                    max-width: 100% !important;
                    padding: 14px 24px !important;
                }
            }
            {$inverted_styles}
        </style>
CSS;
    }
    
    /**
     * Render HTML
     */
    private function render_html($id, $atts, $is_inverted = false, $trust_signals = array(), $options = array()) {
        $nonce = wp_create_nonce('wexoe_contact_nonce_test');
        $inverted_class = $is_inverted ? ' inverted' : '';
        
        ob_start();
        ?>
        <section id="<?php echo esc_attr($id); ?>" class="wexoe-contact-section<?php echo esc_attr($inverted_class); ?>">
            <div class="wexoe-bg-shape wexoe-bg-shape-1"></div>
            <div class="wexoe-bg-shape wexoe-bg-shape-2"></div>
            <div class="wexoe-bg-shape wexoe-bg-shape-3"></div>
            <div class="wexoe-contact-container">
                
                <!-- Left column - Value proposition -->
                <div class="wexoe-contact-info">
                    <h2 class="wexoe-contact-title"><?php echo esc_html($atts['title']); ?></h2>
                    <?php if (!empty($atts['subtitle'])) : ?>
                    <p class="wexoe-contact-subtitle"><?php echo esc_html($atts['subtitle']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($trust_signals)) : ?>
                    <div class="wexoe-trust-signals">
                        <?php foreach ($trust_signals as $trust) : ?>
                        <div class="wexoe-trust-item">
                            <div class="wexoe-trust-icon">
                                <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                            </div>
                            <span class="wexoe-trust-text"><strong><?php echo esc_html($trust['bold']); ?></strong> <?php echo esc_html($trust['text']); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Right column - Form -->
                <div class="wexoe-contact-form-wrapper">
                    <form class="wexoe-contact-form" data-nonce="<?php echo esc_attr($nonce); ?>">
                        
                        <div class="wexoe-error-message"></div>
                        
                        <div class="wexoe-form-row-half">
                            <div class="wexoe-form-field">
                                <label class="wexoe-form-label">Namn <span class="required">*</span></label>
                                <input type="text" name="namn" class="wexoe-form-input" required>
                            </div>
                            <div class="wexoe-form-field">
                                <label class="wexoe-form-label">Företag <span class="required">*</span></label>
                                <input type="text" name="foretag" class="wexoe-form-input" required>
                            </div>
                        </div>
                        
                        <div class="wexoe-form-row-half">
                            <div class="wexoe-form-field">
                                <label class="wexoe-form-label">E-post <span class="required">*</span></label>
                                <input type="email" name="epost" class="wexoe-form-input" required>
                            </div>
                            <div class="wexoe-form-field">
                                <label class="wexoe-form-label">Telefon <span class="required">*</span></label>
                                <input type="tel" name="telefon" class="wexoe-form-input" required>
                            </div>
                        </div>
                        
                        <div class="wexoe-form-row">
                            <label class="wexoe-form-label">Vad kan vi hjälpa dig med?</label>
                            <select name="behov" class="wexoe-form-select">
                                <option value="" disabled selected>Välj ett alternativ</option>
                                <?php foreach ($options as $option) : ?>
                                <option value="<?php echo esc_attr($option['value']); ?>"><?php echo esc_html($option['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="wexoe-form-row">
                            <label class="wexoe-form-label">Berätta mer <span style="font-weight: 400; color: #888;">(valfritt)</span></label>
                            <textarea name="meddelande" class="wexoe-form-textarea"></textarea>
                        </div>
                        
                        <div class="wexoe-gdpr-wrapper">
                            <label class="wexoe-gdpr-item">
                                <input type="checkbox" name="gdpr_consent" value="1">
                                <span class="wexoe-gdpr-checkbox">
                                    <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                                </span>
                                <span class="wexoe-gdpr-text">
                                    Ja, jag vill ta emot nyheter, tips och erbjudanden från Wexoe Industry via e-post. 
                                    Du kan när som helst avregistrera dig.
                                </span>
                            </label>
                        </div>
                        
                        <button type="submit" class="wexoe-submit-btn">
                            <span class="btn-text">Skicka</span>
                            <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        </button>
                        
                    </form>
                    
                    <div class="wexoe-success-message">
                        <div class="wexoe-success-icon">
                            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <h3 class="wexoe-success-title">Tack för ditt meddelande!</h3>
                        <p class="wexoe-success-text">
                            Vi har mottagit din förfrågan och återkommer till dig så snart som möjligt, 
                            vanligtvis inom några timmar under kontorstid.
                        </p>
                        <div class="wexoe-success-next">
                            <svg viewBox="0 0 24 24" style="width:18px;height:18px;stroke:currentColor;fill:none;stroke-width:2;">
                                <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/>
                            </svg>
                            Brådskande? Ring oss: +46 (0)40 682 06 16
                        </div>
                    </div>
                </div>
                
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render JavaScript
     */
    private function render_scripts($id) {
        return <<<JS
        <script>
        (function() {
            const container = document.getElementById('{$id}');
            if (!container) return;
            
            const form = container.querySelector('.wexoe-contact-form');
            const submitBtn = form.querySelector('.wexoe-submit-btn');
            const btnText = submitBtn.querySelector('.btn-text');
            const btnArrow = submitBtn.querySelector('svg');
            const errorMsg = container.querySelector('.wexoe-error-message');
            const successMsg = container.querySelector('.wexoe-success-message');
            const nonce = form.dataset.nonce;
            
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                // Hide error
                errorMsg.classList.remove('show');
                errorMsg.textContent = '';
                
                // Show loading state
                submitBtn.classList.add('loading');
                btnText.textContent = 'Skickar...';
                btnArrow.style.display = 'none';
                
                // Create spinner
                const spinner = document.createElement('span');
                spinner.className = 'spinner';
                submitBtn.appendChild(spinner);
                
                // Collect form data
                const formData = new FormData(form);
                formData.append('action', 'wexoe_contact_submit_test');
                formData.append('nonce', nonce);
                formData.append('page_url', window.location.href);
                
                try {
                    const response = await fetch('{$this->get_ajax_url()}', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // Show success message
                        form.style.display = 'none';
                        successMsg.classList.add('show');
                        
                        // Scroll to success message
                        successMsg.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    } else {
                        // Show error
                        errorMsg.textContent = result.data || 'Något gick fel. Försök igen.';
                        errorMsg.classList.add('show');
                        
                        // Reset button
                        submitBtn.classList.remove('loading');
                        btnText.textContent = 'Skicka';
                        btnArrow.style.display = '';
                        spinner.remove();
                    }
                } catch (error) {
                    errorMsg.textContent = 'Ett fel uppstod. Försök igen eller ring oss direkt.';
                    errorMsg.classList.add('show');
                    
                    // Reset button
                    submitBtn.classList.remove('loading');
                    btnText.textContent = 'Skicka';
                    btnArrow.style.display = '';
                    spinner.remove();
                }
            });
        })();
        </script>
JS;
    }
    
    /**
     * Hämta AJAX URL
     */
    private function get_ajax_url() {
        return admin_url('admin-ajax.php');
    }
}

// Initiera plugin
new WexoeContactFormTest();
