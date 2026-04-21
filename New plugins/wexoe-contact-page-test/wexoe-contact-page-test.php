<?php
/**
 * Plugin Name: Wexoe Contact Page
 * Description: Modern kontaktsida med dynamisk status och webhook-integration. Migrerad till Wexoe Core-infrastruktur (testversion).
 * Version: 2.0.0-test
 * Author: Wexoe
 */

if (!defined('ABSPATH')) exit;


if (!function_exists('wexoe_cp_test_core_ready')) {
function wexoe_cp_test_core_ready() {
    return class_exists('\\Wexoe\\Core\\Core')
        && method_exists('\\Wexoe\\Core\\Core', 'entity');
}
}

class Wexoe_Contact_Page_Test {
    
    public function __construct() {
        add_shortcode('wexoe_contact', array($this, 'render_shortcode'));
    }
    
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(), $atts);

        if (!wexoe_cp_test_core_ready()) {
            return '<p style="color:red;">Wexoe Contact Page TEST: Wexoe Core-pluginet är inte aktivt.</p>';
        }

        // Generate unique ID for CSS scoping
        $id = 'wexoe-cp-' . uniqid();
        
        ob_start();
        ?>
        
        <style>
            /* ========================================
               WEXOE CONTACT PAGE - SCOPED CSS
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
               HERO
               ======================================== */
            #top #<?php echo $id; ?> .wcp-hero {
                background: #11325D !important;
                padding: 64px 0 100px !important;
                position: relative !important;
                overflow: hidden !important;
                margin: 0 !important;
                width: 100vw !important;
                margin-left: calc(-50vw + 50%) !important;
            }

            #top #<?php echo $id; ?> .wcp-hero::before {
                content: '' !important;
                position: absolute !important;
                top: -50% !important;
                right: -10% !important;
                width: 500px !important;
                height: 500px !important;
                background: rgba(255,255,255,0.03) !important;
                border-radius: 40px !important;
                transform: rotate(45deg) !important;
                pointer-events: none !important;
            }

            #top #<?php echo $id; ?> .wcp-hero-container {
                position: relative !important;
                max-width: 1200px !important;
                margin: 0 auto !important;
                padding: 0 24px !important;
                display: flex !important;
                justify-content: space-between !important;
                align-items: flex-end !important;
            }

            #top #<?php echo $id; ?> .wcp-hero-content {
                max-width: 500px !important;
            }

            #top #<?php echo $id; ?> .wcp-hero-title {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 42px !important;
                font-weight: 700 !important;
                color: #fff !important;
                margin: 0 0 12px 0 !important;
                padding: 0 !important;
                line-height: 1.1 !important;
                background: none !important;
                border: none !important;
            }

            #top #<?php echo $id; ?> .wcp-hero-subtitle {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 17px !important;
                color: rgba(255,255,255,0.7) !important;
                line-height: 1.6 !important;
                margin: 0 !important;
                padding: 0 !important;
                background: none !important;
                border: none !important;
            }

            /* ========================================
               MAIN CONTENT
               ======================================== */
            #top #<?php echo $id; ?> .wcp-main {
                max-width: 1200px !important;
                margin: -50px auto 0 !important;
                padding: 0 24px 80px !important;
                position: relative !important;
                z-index: 2 !important;
            }

            #top #<?php echo $id; ?> .wcp-grid {
                display: grid !important;
                grid-template-columns: 340px 1fr !important;
                gap: 24px !important;
                align-items: stretch !important;
            }

            /* Desktop/Mobile visibility */
            #top #<?php echo $id; ?> .wcp-desktop-only {
                display: block !important;
            }

            #top #<?php echo $id; ?> .wcp-mobile-only,
            #top #<?php echo $id; ?> .wcp-info-card.wcp-mobile-only,
            #top #<?php echo $id; ?> div.wcp-mobile-only {
                display: none !important;
            }

            /* ========================================
               INFO CARD
               ======================================== */
            #top #<?php echo $id; ?> .wcp-info-card {
                background: #fff !important;
                border-radius: 16px !important;
                box-shadow: 0 4px 24px rgba(0,0,0,0.08) !important;
                padding: 28px !important;
                display: flex !important;
                flex-direction: column !important;
                border: none !important;
            }

            #top #<?php echo $id; ?> .wcp-info-header {
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                margin-bottom: 24px !important;
            }

            #top #<?php echo $id; ?> .wcp-info-title {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 18px !important;
                font-weight: 700 !important;
                color: #11325D !important;
                margin: 0 !important;
                padding: 0 !important;
                background: none !important;
                border: none !important;
            }

            /* Status badge */
            #top #<?php echo $id; ?> .wcp-status {
                display: flex !important;
                align-items: center !important;
                gap: 6px !important;
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 11px !important;
                font-weight: 600 !important;
                text-transform: uppercase !important;
                letter-spacing: 0.5px !important;
            }

            #top #<?php echo $id; ?> .wcp-status.online {
                color: #10B981 !important;
            }

            #top #<?php echo $id; ?> .wcp-status.offline {
                color: #F28C28 !important;
            }

            #top #<?php echo $id; ?> .wcp-status-dot {
                width: 6px !important;
                height: 6px !important;
                border-radius: 50% !important;
                animation: wcpPulse 2s ease-in-out infinite !important;
            }

            #top #<?php echo $id; ?> .wcp-status.online .wcp-status-dot {
                background: #10B981 !important;
            }

            #top #<?php echo $id; ?> .wcp-status.offline .wcp-status-dot {
                background: #F28C28 !important;
                animation: none !important;
            }

            @keyframes wcpPulse {
                0%, 100% { opacity: 1; transform: scale(1); }
                50% { opacity: 0.6; transform: scale(0.9); }
            }

            /* Contact list */
            #top #<?php echo $id; ?> .wcp-contact-list {
                display: flex !important;
                flex-direction: column !important;
                gap: 4px !important;
                margin-bottom: 20px !important;
                padding: 0 !important;
                list-style: none !important;
            }

            #top #<?php echo $id; ?> .wcp-contact-item {
                display: flex !important;
                align-items: center !important;
                gap: 12px !important;
                padding: 10px 0 !important;
                text-decoration: none !important;
                transition: opacity 0.2s ease !important;
                background: none !important;
                border: none !important;
                margin: 0 !important;
            }

            #top #<?php echo $id; ?> .wcp-contact-item:hover {
                opacity: 0.7 !important;
            }

            #top #<?php echo $id; ?> .wcp-contact-icon {
                width: 36px !important;
                height: 36px !important;
                background: linear-gradient(135deg, #11325D 0%, #1a4a7a 100%) !important;
                border-radius: 8px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                flex-shrink: 0 !important;
                border: none !important;
                padding: 0 !important;
            }

            #top #<?php echo $id; ?> .wcp-contact-icon svg {
                width: 16px !important;
                height: 16px !important;
                color: #fff !important;
            }

            #top #<?php echo $id; ?> .wcp-contact-text {
                flex: 1 !important;
                min-width: 0 !important;
            }

            #top #<?php echo $id; ?> .wcp-contact-label {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 11px !important;
                font-weight: 500 !important;
                color: #888 !important;
                line-height: 1 !important;
                margin-bottom: 2px !important;
            }

            #top #<?php echo $id; ?> .wcp-contact-value {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 14px !important;
                font-weight: 600 !important;
                color: #11325D !important;
            }

            /* Hours section */
            #top #<?php echo $id; ?> .wcp-hours {
                background: #f8f9fa !important;
                border-radius: 10px !important;
                padding: 16px !important;
                margin-bottom: 20px !important;
                border: none !important;
            }

            #top #<?php echo $id; ?> .wcp-hours-header {
                display: flex !important;
                align-items: center !important;
                gap: 8px !important;
                margin-bottom: 10px !important;
            }

            #top #<?php echo $id; ?> .wcp-hours-icon {
                width: 20px !important;
                height: 20px !important;
                color: #F28C28 !important;
                flex-shrink: 0 !important;
            }

            #top #<?php echo $id; ?> .wcp-hours-title {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 12px !important;
                font-weight: 600 !important;
                color: #11325D !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            #top #<?php echo $id; ?> .wcp-hours-row {
                display: flex !important;
                font-size: 13px !important;
                gap: 20px !important;
                padding-left: 28px !important;
            }

            #top #<?php echo $id; ?> .wcp-hours-item {
                display: flex !important;
                gap: 6px !important;
            }

            #top #<?php echo $id; ?> .wcp-hours-day {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                color: #666 !important;
            }

            #top #<?php echo $id; ?> .wcp-hours-time {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-weight: 600 !important;
                color: #11325D !important;
            }

            /* Address section */
            #top #<?php echo $id; ?> .wcp-address {
                padding-top: 20px !important;
                border-top: 1px solid #e8e8e8 !important;
            }

            #top #<?php echo $id; ?> .wcp-address-header {
                display: flex !important;
                align-items: center !important;
                gap: 8px !important;
                margin-bottom: 8px !important;
            }

            #top #<?php echo $id; ?> .wcp-address-icon {
                width: 20px !important;
                height: 20px !important;
                color: #F28C28 !important;
                flex-shrink: 0 !important;
            }

            #top #<?php echo $id; ?> .wcp-address-title {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 12px !important;
                font-weight: 600 !important;
                color: #11325D !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            #top #<?php echo $id; ?> .wcp-address-content {
                padding-left: 28px !important;
            }

            #top #<?php echo $id; ?> .wcp-address-text {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 13px !important;
                color: #555 !important;
                line-height: 1.4 !important;
                margin: 0 !important;
            }

            #top #<?php echo $id; ?> .wcp-address-link {
                display: inline-block !important;
                margin-top: 8px !important;
                margin-left: 28px !important;
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 12px !important;
                font-weight: 600 !important;
                color: #11325D !important;
                text-decoration: none !important;
            }

            #top #<?php echo $id; ?> .wcp-address-link:hover {
                color: #F28C28 !important;
            }

            /* Info footer */
            #top #<?php echo $id; ?> .wcp-info-footer {
                margin-top: auto !important;
                padding-top: 24px !important;
                border-top: 1px solid #e8e8e8 !important;
                text-align: center !important;
            }

            #top #<?php echo $id; ?> .wcp-coffee-icon {
                font-size: 20px !important;
                margin-bottom: 6px !important;
            }

            #top #<?php echo $id; ?> .wcp-info-footer-text {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 13px !important;
                color: #888 !important;
                line-height: 1.5 !important;
                margin: 0 !important;
            }

            #top #<?php echo $id; ?> .wcp-info-footer-text strong {
                color: #11325D !important;
            }

            /* ========================================
               FORM CARD
               ======================================== */
            #top #<?php echo $id; ?> .wcp-form-card {
                background: #fff !important;
                border-radius: 16px !important;
                box-shadow: 0 4px 24px rgba(0,0,0,0.08) !important;
                padding: 32px !important;
                display: flex !important;
                flex-direction: column !important;
                border: none !important;
                position: relative !important;
            }

            /* Diskret person-länk */
            #top #<?php echo $id; ?> .wcp-person-link,
            #top #<?php echo $id; ?> a.wcp-person-link {
                display: inline !important;
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 15px !important;
                font-weight: 400 !important;
                color: #11325D !important;
                text-decoration: underline !important;
                text-align: right !important;
                flex-shrink: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
                background: none !important;
                border: none !important;
                transition: color 0.2s ease !important;
                white-space: nowrap !important;
                line-height: 1.4 !important;
            }

            #top #<?php echo $id; ?> .wcp-person-link:hover,
            #top #<?php echo $id; ?> a.wcp-person-link:hover {
                color: #F28C28 !important;
                text-decoration: underline !important;
            }

            #top #<?php echo $id; ?> .wcp-form-header {
                display: flex !important;
                justify-content: space-between !important;
                align-items: flex-start !important;
                gap: 16px !important;
                margin-bottom: 28px !important;
                padding: 0 !important;
                background: none !important;
                border: none !important;
            }

            #top #<?php echo $id; ?> .wcp-form-title {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 20px !important;
                font-weight: 700 !important;
                color: #11325D !important;
                margin: 0 0 6px 0 !important;
                padding: 0 !important;
                background: none !important;
                border: none !important;
                line-height: 1.3 !important;
                text-transform: none !important;
                letter-spacing: 0 !important;
            }

            #top #<?php echo $id; ?> .wcp-form-grid {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                gap: 18px !important;
                flex: 1 !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            #top #<?php echo $id; ?> .wcp-form-group {
                display: flex !important;
                flex-direction: column !important;
                gap: 6px !important;
                margin: 0 !important;
                padding: 0 !important;
                background: none !important;
                border: none !important;
            }

            #top #<?php echo $id; ?> .wcp-form-group.full {
                grid-column: 1 / -1 !important;
            }

            /* Labels */
            #top #<?php echo $id; ?> .wcp-form-label,
            #top #<?php echo $id; ?> .wcp-form-grid label,
            #top #<?php echo $id; ?> .wcp-form-group label,
            #top #<?php echo $id; ?> label {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 13px !important;
                font-weight: 600 !important;
                color: #333 !important;
                margin: 0 !important;
                padding: 0 !important;
                background: none !important;
                border: none !important;
                line-height: 1.4 !important;
                text-transform: none !important;
                letter-spacing: 0 !important;
                display: block !important;
            }

            #top #<?php echo $id; ?> .wcp-form-label .required,
            #top #<?php echo $id; ?> .wcp-form-group label .required {
                color: #F28C28 !important;
            }

            /* Inputs */
            #top #<?php echo $id; ?> .wcp-form-input,
            #top #<?php echo $id; ?> .wcp-form-grid input,
            #top #<?php echo $id; ?> .wcp-form-group input,
            #top #<?php echo $id; ?> input[type="text"],
            #top #<?php echo $id; ?> input[type="email"],
            #top #<?php echo $id; ?> input[type="tel"],
            #top #<?php echo $id; ?> input {
                width: 100% !important;
                height: auto !important;
                min-height: 0 !important;
                max-height: none !important;
                min-width: 0 !important;
                max-width: 100% !important;
                padding: 12px 14px !important;
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 15px !important;
                font-weight: 400 !important;
                color: #333 !important;
                background: #f8f9fa !important;
                background-image: none !important;
                border: 2px solid #e8e8e8 !important;
                border-radius: 8px !important;
                box-shadow: none !important;
                outline: none !important;
                transition: all 0.2s ease !important;
                margin: 0 !important;
                -webkit-appearance: none !important;
                -moz-appearance: none !important;
                appearance: none !important;
                line-height: 1.5 !important;
                text-transform: none !important;
                letter-spacing: 0 !important;
                float: none !important;
            }

            #top #<?php echo $id; ?> .wcp-form-input:focus,
            #top #<?php echo $id; ?> .wcp-form-grid input:focus,
            #top #<?php echo $id; ?> .wcp-form-group input:focus,
            #top #<?php echo $id; ?> input:focus {
                outline: none !important;
                border-color: #11325D !important;
                background: #fff !important;
                box-shadow: none !important;
            }

            #top #<?php echo $id; ?> .wcp-form-input::placeholder,
            #top #<?php echo $id; ?> input::placeholder {
                color: #aaa !important;
                opacity: 1 !important;
            }

            /* Select */
            #top #<?php echo $id; ?> .wcp-form-select,
            #top #<?php echo $id; ?> .wcp-form-grid select,
            #top #<?php echo $id; ?> .wcp-form-group select,
            #top #<?php echo $id; ?> select {
                width: 100% !important;
                height: auto !important;
                min-height: 0 !important;
                max-height: none !important;
                min-width: 0 !important;
                max-width: 100% !important;
                padding: 12px 40px 12px 14px !important;
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 15px !important;
                font-weight: 400 !important;
                color: #333 !important;
                background-color: #f8f9fa !important;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E") !important;
                background-repeat: no-repeat !important;
                background-position: right 12px center !important;
                background-size: 16px !important;
                border: 2px solid #e8e8e8 !important;
                border-radius: 8px !important;
                box-shadow: none !important;
                outline: none !important;
                cursor: pointer !important;
                -webkit-appearance: none !important;
                -moz-appearance: none !important;
                appearance: none !important;
                margin: 0 !important;
                line-height: 1.5 !important;
                text-transform: none !important;
                letter-spacing: 0 !important;
                float: none !important;
            }

            #top #<?php echo $id; ?> .wcp-form-select:focus,
            #top #<?php echo $id; ?> select:focus {
                outline: none !important;
                border-color: #11325D !important;
                background-color: #fff !important;
                box-shadow: none !important;
            }

            #top #<?php echo $id; ?> select option {
                background: #fff !important;
                color: #333 !important;
                padding: 8px !important;
            }

            /* Textarea */
            #top #<?php echo $id; ?> .wcp-form-textarea,
            #top #<?php echo $id; ?> .wcp-form-grid textarea,
            #top #<?php echo $id; ?> .wcp-form-group textarea,
            #top #<?php echo $id; ?> textarea {
                width: 100% !important;
                min-height: 120px !important;
                max-height: none !important;
                min-width: 0 !important;
                max-width: 100% !important;
                padding: 12px 14px !important;
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 15px !important;
                font-weight: 400 !important;
                color: #333 !important;
                background: #f8f9fa !important;
                background-image: none !important;
                border: 2px solid #e8e8e8 !important;
                border-radius: 8px !important;
                box-shadow: none !important;
                outline: none !important;
                resize: vertical !important;
                margin: 0 !important;
                -webkit-appearance: none !important;
                -moz-appearance: none !important;
                appearance: none !important;
                line-height: 1.5 !important;
                text-transform: none !important;
                letter-spacing: 0 !important;
                float: none !important;
            }

            #top #<?php echo $id; ?> .wcp-form-textarea:focus,
            #top #<?php echo $id; ?> textarea:focus {
                outline: none !important;
                border-color: #11325D !important;
                background: #fff !important;
                box-shadow: none !important;
            }

            #top #<?php echo $id; ?> textarea::placeholder {
                color: #aaa !important;
                opacity: 1 !important;
            }

            /* Form footer */
            #top #<?php echo $id; ?> .wcp-form-footer {
                grid-column: 1 / -1 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                margin-top: 12px !important;
                padding-top: 20px !important;
                border-top: 1px solid #e8e8e8 !important;
                background: none !important;
            }

            /* Submit button - Override #top input[type="submit"] */
            #top #<?php echo $id; ?> .wcp-form-submit,
            #top #<?php echo $id; ?> button[type="submit"],
            #top #<?php echo $id; ?> input[type="submit"],
            #top #<?php echo $id; ?> .wcp-form-footer button,
            #top #<?php echo $id; ?> form button {
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 10px !important;
                width: auto !important;
                height: auto !important;
                min-width: 0 !important;
                max-width: none !important;
                min-height: 0 !important;
                max-height: none !important;
                background: #F28C28 !important;
                background-image: none !important;
                color: #fff !important;
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 15px !important;
                font-weight: 600 !important;
                line-height: 1 !important;
                text-transform: none !important;
                letter-spacing: 0 !important;
                text-decoration: none !important;
                text-shadow: none !important;
                text-align: center !important;
                padding: 14px 28px !important;
                margin: 0 !important;
                border: none !important;
                border-radius: 8px !important;
                box-shadow: none !important;
                outline: none !important;
                cursor: pointer !important;
                transition: all 0.2s ease !important;
                -webkit-appearance: none !important;
                -moz-appearance: none !important;
                appearance: none !important;
                float: none !important;
                position: relative !important;
                top: auto !important;
                left: auto !important;
                right: auto !important;
                bottom: auto !important;
                transform: none !important;
            }

            #top #<?php echo $id; ?> .wcp-form-submit:hover,
            #top #<?php echo $id; ?> button[type="submit"]:hover,
            #top #<?php echo $id; ?> .wcp-form-footer button:hover {
                background: #e07b1a !important;
                background-image: none !important;
                color: #fff !important;
                transform: translateY(-1px) !important;
                box-shadow: 0 4px 12px rgba(242, 140, 40, 0.3) !important;
                border: none !important;
                text-decoration: none !important;
            }

            #top #<?php echo $id; ?> .wcp-form-submit:focus,
            #top #<?php echo $id; ?> button[type="submit"]:focus {
                outline: none !important;
                box-shadow: 0 4px 12px rgba(242, 140, 40, 0.3) !important;
            }

            #top #<?php echo $id; ?> .wcp-form-submit:disabled,
            #top #<?php echo $id; ?> button[type="submit"]:disabled {
                opacity: 0.6 !important;
                cursor: not-allowed !important;
                transform: none !important;
                background: #F28C28 !important;
            }

            #top #<?php echo $id; ?> .wcp-form-submit svg,
            #top #<?php echo $id; ?> button[type="submit"] svg {
                width: 18px !important;
                height: 18px !important;
                transition: transform 0.2s ease !important;
                fill: none !important;
                stroke: currentColor !important;
            }

            #top #<?php echo $id; ?> .wcp-form-submit:hover svg,
            #top #<?php echo $id; ?> button[type="submit"]:hover svg {
                transform: translateX(3px) !important;
            }

            #top #<?php echo $id; ?> .wcp-privacy-text {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 12px !important;
                color: #888 !important;
                max-width: 280px !important;
                margin: 0 !important;
                padding: 0 !important;
                background: none !important;
                border: none !important;
                line-height: 1.5 !important;
            }

            #top #<?php echo $id; ?> .wcp-privacy-text a {
                color: #11325D !important;
                text-decoration: underline !important;
            }

            #top #<?php echo $id; ?> .wcp-privacy-text a:hover {
                color: #F28C28 !important;
            }

            /* ========================================
               NEWSLETTER CHECKBOX
               ======================================== */
            #top #<?php echo $id; ?> .wcp-newsletter {
                grid-column: 1 / -1 !important;
                display: table !important;
                margin: 8px 0 0 0 !important;
                padding: 0 !important;
                background: none !important;
            }

            #top #<?php echo $id; ?> .wcp-newsletter input[type="checkbox"] {
                display: table-cell !important;
                vertical-align: top !important;
                width: 16px !important;
                height: 16px !important;
                min-width: 16px !important;
                min-height: 16px !important;
                margin: 1px 10px 0 0 !important;
                padding: 0 !important;
                opacity: 1 !important;
                position: static !important;
                cursor: pointer !important;
                -webkit-appearance: checkbox !important;
                appearance: checkbox !important;
                accent-color: #11325D !important;
            }

            #top #<?php echo $id; ?> .wcp-newsletter span {
                display: table-cell !important;
                vertical-align: top !important;
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 11px !important;
                color: #888 !important;
                line-height: 1.5 !important;
                cursor: pointer !important;
            }

            /* ========================================
               SUCCESS STATE
               ======================================== */
            #top #<?php echo $id; ?> .wcp-success {
                display: none;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
                text-align: center !important;
                padding: 60px 40px !important;
                min-height: 400px !important;
            }

            #top #<?php echo $id; ?> .wcp-success.show {
                display: flex !important;
            }

            #top #<?php echo $id; ?> .wcp-success-icon {
                width: 64px !important;
                height: 64px !important;
                background: #10B981 !important;
                border-radius: 50% !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                margin-bottom: 24px !important;
            }

            #top #<?php echo $id; ?> .wcp-success-icon svg {
                width: 32px !important;
                height: 32px !important;
                color: #fff !important;
            }

            #top #<?php echo $id; ?> .wcp-success-title {
                font-family: 'DM Sans', system-ui, sans-serif !important;
                font-size: 24px !important;
                font-weight: 700 !important;
                color: #11325D !important;
                margin: 0 !important;
            }

            #top #<?php echo $id; ?> .wcp-form-container.hidden {
                display: none !important;
            }

            /* ========================================
               RESPONSIVE
               ======================================== */
            @media (max-width: 1024px) {
                #top #<?php echo $id; ?> .wcp-grid {
                    grid-template-columns: 1fr !important;
                    align-items: start !important;
                }

                #top #<?php echo $id; ?> .wcp-form-card {
                    order: -1 !important;
                }

                /* Fortfarande dölja mobile-only på tablet */
                #top #<?php echo $id; ?> .wcp-mobile-only,
                #top #<?php echo $id; ?> .wcp-info-card.wcp-mobile-only,
                #top #<?php echo $id; ?> div.wcp-mobile-only {
                    display: none !important;
                }
            }

            @media (max-width: 767px) {
                #top #<?php echo $id; ?> .wcp-hero {
                    padding: 48px 0 80px !important;
                }

                #top #<?php echo $id; ?> .wcp-hero-container {
                    width: 100% !important;
                    max-width: 100% !important;
                    margin: 0 auto !important;
                    padding: 0 !important;
                    box-sizing: border-box !important;
                }

                #top #<?php echo $id; ?> .wcp-hero-title {
                    font-size: 32px !important;
                    text-align: center !important;
                }

                #top #<?php echo $id; ?> .wcp-hero-subtitle {
                    text-align: center !important;
                }

                #top #<?php echo $id; ?> .wcp-hero-content {
                    text-align: center !important;
                }

                #top #<?php echo $id; ?> .wcp-main {
                    width: 100% !important;
                    min-width: 100% !important;
                    max-width: 100% !important;
                    margin-left: auto !important;
                    margin-right: auto !important;
                    margin-top: -50px !important;
                    padding: 0 0 60px 0 !important;
                    box-sizing: border-box !important;
                }

                #top #<?php echo $id; ?> .wcp-grid {
                    display: flex !important;
                    flex-direction: column !important;
                    width: 100% !important;
                    gap: 16px !important;
                }

                /* Desktop/Mobile visibility */
                #top #<?php echo $id; ?> .wcp-desktop-only {
                    display: none !important;
                }

                #top #<?php echo $id; ?> .wcp-mobile-only,
                #top #<?php echo $id; ?> .wcp-info-card.wcp-mobile-only,
                #top #<?php echo $id; ?> div.wcp-mobile-only {
                    display: block !important;
                }

                /* Order: 1. Kontakt, 2. Formulär, 3. Öppettider/Adress */
                #top #<?php echo $id; ?> .wcp-contact-card {
                    order: 1 !important;
                }

                #top #<?php echo $id; ?> .wcp-form-card {
                    order: 2 !important;
                }

                #top #<?php echo $id; ?> .wcp-location-card {
                    order: 3 !important;
                }

                #top #<?php echo $id; ?> .wcp-info-card {
                    padding: 20px !important;
                    padding-bottom: 16px !important;
                    margin-bottom: 0 !important;
                    width: 100% !important;
                    box-sizing: border-box !important;
                }

                #top #<?php echo $id; ?> .wcp-contact-card .wcp-contact-list {
                    margin-bottom: 0 !important;
                }

                #top #<?php echo $id; ?> .wcp-form-card {
                    padding: 24px !important;
                    width: 100% !important;
                    box-sizing: border-box !important;
                }

                #top #<?php echo $id; ?> .wcp-form-header {
                    flex-direction: column !important;
                    gap: 8px !important;
                }

                #top #<?php echo $id; ?> .wcp-person-link,
                #top #<?php echo $id; ?> a.wcp-person-link {
                    text-align: left !important;
                    font-size: 14px !important;
                    max-width: none !important;
                    order: 2 !important;
                    white-space: normal !important;
                }

                #top #<?php echo $id; ?> .wcp-form-grid {
                    grid-template-columns: 1fr !important;
                }

                #top #<?php echo $id; ?> .wcp-form-footer {
                    flex-direction: column !important;
                    gap: 16px !important;
                    align-items: stretch !important;
                }

                #top #<?php echo $id; ?> .wcp-form-submit,
                #top #<?php echo $id; ?> button[type="submit"] {
                    justify-content: center !important;
                }

                #top #<?php echo $id; ?> .wcp-privacy-text {
                    text-align: center !important;
                    max-width: none !important;
                }

                #top #<?php echo $id; ?> .wcp-hours-row {
                    flex-direction: column !important;
                    gap: 4px !important;
                }
            }
        </style>
        
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
        
        <div id="<?php echo $id; ?>">
            
            <!-- Hero -->
            <section class="wcp-hero">
                <div class="wcp-hero-container">
                    <div class="wcp-hero-content">
                        <h1 class="wcp-hero-title">Kontakta oss</h1>
                        <p class="wcp-hero-subtitle">Teknisk support, projektdiskussion eller en snabb fråga – vi finns här för dig.</p>
                    </div>
                </div>
            </section>

            <!-- Main Content -->
            <main class="wcp-main">
                <div class="wcp-grid">
                    
                    <!-- Contact Card (Mobil: visas först) -->
                    <div class="wcp-info-card wcp-contact-card">
                        <div class="wcp-info-header">
                            <h2 class="wcp-info-title">Nå oss direkt</h2>
                            <div class="wcp-status online" id="<?php echo $id; ?>-status">
                                <span class="wcp-status-dot"></span>
                                <span id="<?php echo $id; ?>-status-text">Tillgängliga</span>
                            </div>
                        </div>
                        
                        <div class="wcp-contact-list">
                            <a href="tel:+46406820616" class="wcp-contact-item">
                                <div class="wcp-contact-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                    </svg>
                                </div>
                                <div class="wcp-contact-text">
                                    <div class="wcp-contact-label">Telefon</div>
                                    <div class="wcp-contact-value">+46 (0)40 682 06 16</div>
                                </div>
                            </a>
                            
                            <a href="mailto:industry@wexoeindustry.se" class="wcp-contact-item">
                                <div class="wcp-contact-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                        <polyline points="22,6 12,13 2,6"/>
                                    </svg>
                                </div>
                                <div class="wcp-contact-text">
                                    <div class="wcp-contact-label">E-post</div>
                                    <div class="wcp-contact-value">industry@wexoeindustry.se</div>
                                </div>
                            </a>
                            
                            <a href="mailto:order@wexoeindustry.se" class="wcp-contact-item">
                                <div class="wcp-contact-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="9" cy="21" r="1"/>
                                        <circle cx="20" cy="21" r="1"/>
                                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                                    </svg>
                                </div>
                                <div class="wcp-contact-text">
                                    <div class="wcp-contact-label">Beställningar</div>
                                    <div class="wcp-contact-value">order@wexoeindustry.se</div>
                                </div>
                            </a>
                        </div>

                        <!-- Desktop only: öppettider, adress, kaffe i samma box -->
                        <div class="wcp-desktop-only">
                            <div class="wcp-hours">
                                <div class="wcp-hours-header">
                                    <svg class="wcp-hours-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"/>
                                        <polyline points="12 6 12 12 16 14"/>
                                    </svg>
                                    <span class="wcp-hours-title">Öppettider</span>
                                </div>
                                <div class="wcp-hours-row">
                                    <div class="wcp-hours-item">
                                        <span class="wcp-hours-day">Mån–Tor</span>
                                        <span class="wcp-hours-time">08–16</span>
                                    </div>
                                    <div class="wcp-hours-item">
                                        <span class="wcp-hours-day">Fre</span>
                                        <span class="wcp-hours-time">08–15:30</span>
                                    </div>
                                </div>
                            </div>

                            <div class="wcp-address">
                                <div class="wcp-address-header">
                                    <svg class="wcp-address-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <span class="wcp-address-title">Besöksadress</span>
                                </div>
                                <div class="wcp-address-content">
                                    <p class="wcp-address-text">
                                        Wexoe Industry AB<br>
                                        Scheelevägen 17, 223 70 Lund
                                    </p>
                                </div>
                                <a href="https://maps.google.com/?q=Scheelevägen+17+Lund" target="_blank" rel="noopener" class="wcp-address-link">
                                    Visa på karta →
                                </a>
                            </div>

                            <div class="wcp-info-footer">
                                <div class="wcp-coffee-icon">☕</div>
                                <p class="wcp-info-footer-text">
                                    Förbi Lund? <strong>Kom in på en kaffe!</strong>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Form Card -->
                    <div class="wcp-form-card">
                        <div class="wcp-form-header">
                            <h2 class="wcp-form-title">Skicka ett meddelande</h2>
                            <a href="https://wexoe.se/kontaktpersoner/" class="wcp-person-link">
                                Söker du kontaktuppgifter till en specifik person? →
                            </a>
                        </div>

                        <!-- Form container -->
                        <div class="wcp-form-container" id="<?php echo $id; ?>-form-container">
                            <form class="wcp-form-grid" id="<?php echo $id; ?>-form">
                                <div class="wcp-form-group">
                                    <label class="wcp-form-label">Namn <span class="required">*</span></label>
                                    <input type="text" class="wcp-form-input" name="namn" placeholder="Ditt namn" required>
                                </div>
                                
                                <div class="wcp-form-group">
                                    <label class="wcp-form-label">Företag</label>
                                    <input type="text" class="wcp-form-input" name="foretag" placeholder="Företagsnamn">
                                </div>
                                
                                <div class="wcp-form-group">
                                    <label class="wcp-form-label">E-post <span class="required">*</span></label>
                                    <input type="email" class="wcp-form-input" name="epost" placeholder="din@epost.se" required>
                                </div>
                                
                                <div class="wcp-form-group">
                                    <label class="wcp-form-label">Telefon</label>
                                    <input type="tel" class="wcp-form-input" name="telefon" placeholder="+46 70 123 45 67">
                                </div>
                                
                                <div class="wcp-form-group full">
                                    <label class="wcp-form-label">Vad kan vi hjälpa dig med?</label>
                                    <select class="wcp-form-select" name="behov">
                                        <option value="">Välj det som passar bäst...</option>
                                        <option value="snabb-fraga">Snabb fråga</option>
                                        <option value="hjalp-valja">Hjälp att välja rätt produkt</option>
                                        <option value="offert">Få en offert</option>
                                        <option value="dimensionering">Dimensionering</option>
                                        <option value="bollplank">Tekniskt bollplank</option>
                                        <option value="besok-demo">Boka besök eller demo</option>
                                        <option value="annat">Annat</option>
                                    </select>
                                </div>
                                
                                <div class="wcp-form-group full">
                                    <label class="wcp-form-label">Meddelande <span class="required">*</span></label>
                                    <textarea class="wcp-form-textarea" name="meddelande" placeholder="Beskriv ditt ärende..." required></textarea>
                                </div>

                                <div class="wcp-newsletter">
                                    <input type="checkbox" name="gdpr_consent" value="1" id="<?php echo $id; ?>-consent">
                                    <span onclick="document.getElementById('<?php echo $id; ?>-consent').click()">Ja, jag vill ta emot nyheter, tips och erbjudanden från Wexoe via e-post. Du kan när som helst avregistrera dig.</span>
                                </div>

                                <div class="wcp-form-footer">
                                    <p class="wcp-privacy-text">
                                        Genom att skicka godkänner du vår <a href="/integritetspolicy">integritetspolicy</a>.
                                    </p>
                                    <button type="submit" class="wcp-form-submit">
                                        Skicka
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M5 12h14M12 5l7 7-7 7"/>
                                        </svg>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Success state -->
                        <div class="wcp-success" id="<?php echo $id; ?>-success">
                            <div class="wcp-success-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </div>
                            <h3 class="wcp-success-title">Tack för ditt meddelande</h3>
                        </div>
                    </div>

                    <!-- Hours & Address Card (Mobil only: visas sist) -->
                    <div class="wcp-info-card wcp-location-card wcp-mobile-only">
                        <div class="wcp-hours">
                            <div class="wcp-hours-header">
                                <svg class="wcp-hours-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                                <span class="wcp-hours-title">Öppettider</span>
                            </div>
                            <div class="wcp-hours-row">
                                <div class="wcp-hours-item">
                                    <span class="wcp-hours-day">Mån–Tor</span>
                                    <span class="wcp-hours-time">08–16</span>
                                </div>
                                <div class="wcp-hours-item">
                                    <span class="wcp-hours-day">Fre</span>
                                    <span class="wcp-hours-time">08–15:30</span>
                                </div>
                            </div>
                        </div>

                        <div class="wcp-address">
                            <div class="wcp-address-header">
                                <svg class="wcp-address-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                                <span class="wcp-address-title">Besöksadress</span>
                            </div>
                            <div class="wcp-address-content">
                                <p class="wcp-address-text">
                                    Wexoe AB<br>
                                    Scheelevägen 17, 223 70 Lund
                                </p>
                            </div>
                            <a href="https://maps.google.com/?q=Scheelevägen+17+Lund" target="_blank" rel="noopener" class="wcp-address-link">
                                Visa på karta →
                            </a>
                        </div>

                        <div class="wcp-info-footer">
                            <div class="wcp-coffee-icon">☕</div>
                            <p class="wcp-info-footer-text">
                                Förbi Lund? <strong>Kom in på en kaffe!</strong>
                            </p>
                        </div>
                    </div>

                </div>
            </main>
            
        </div>

        <script>
        (function() {
            var id = '<?php echo $id; ?>';
            
            // Dynamic office hours status
            function updateStatus() {
                var badge = document.getElementById(id + '-status');
                var text = document.getElementById(id + '-status-text');
                if (!badge || !text) return;
                
                var now = new Date();
                var day = now.getDay();
                var hour = now.getHours();
                var minute = now.getMinutes();
                var time = hour + minute / 60;

                var isOpen = false;

                // Monday-Friday: 07:00-17:00
                if (day >= 1 && day <= 5) {
                    isOpen = time >= 7 && time < 17;
                }

                if (isOpen) {
                    badge.className = 'wcp-status online';
                    text.textContent = 'Tillgängliga';
                } else {
                    badge.className = 'wcp-status offline';
                    text.textContent = 'Out of office';
                }
            }

            updateStatus();
            setInterval(updateStatus, 60000);

            // Form submission
            var form = document.getElementById(id + '-form');
            var formContainer = document.getElementById(id + '-form-container');
            var successDiv = document.getElementById(id + '-success');
            
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    var submitBtn = form.querySelector('.wcp-form-submit');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = 'Skickar...';
                    
                    var formData = new FormData(form);
                    var data = {
                        namn: formData.get('namn') || '',
                        epost: formData.get('epost') || '',
                        telefon: formData.get('telefon') || '',
                        foretag: formData.get('foretag') || '',
                        behov: formData.get('behov') || '',
                        page_url: window.location.href,
                        meddelande: formData.get('meddelande') || '',
                        gdpr_consent: formData.get('gdpr_consent') ? true : false
                    };
                    
                    fetch('https://hook.eu1.make.com/hvb0ba1oq00ps6fn3rzc9ldoidvpn8pb', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(data)
                    })
                    .then(function(response) {
                        // Show success regardless of response
                        formContainer.classList.add('hidden');
                        successDiv.classList.add('show');
                    })
                    .catch(function(error) {
                        // Show success anyway (webhook might not return proper response)
                        formContainer.classList.add('hidden');
                        successDiv.classList.add('show');
                    });
                });
            }
        })();
        </script>
        
        <?php
        return ob_get_clean();
    }
}

// Initialize
new Wexoe_Contact_Page_Test();