<?php
/**
 * Plugin Name: Wexoe Team Rack
 * Description: Visar teammedlemmar som CompactLogix I/O-moduler via Wexoe Core.
 * Version: 2.0.0
 * Author: Wexoe
 */

if (!defined('ABSPATH')) exit;


if (!function_exists('wexoe_team_rack_core_ready')) {
function wexoe_team_rack_core_ready() {
    return class_exists('\Wexoe\Core\Core')
        && method_exists('\Wexoe\Core\Core', 'entity');
}
}

class WexoeTeamRack {
    
    
    public function __construct() {
        add_shortcode('wexoe_team_rack', array($this, 'render_shortcode'));
    }
    
    /**
     * Hämta teammedlemmar från Airtable
     */
    private function get_team_members($tag = null) {
        if (!wexoe_team_rack_core_ready()) {
            return array();
        }

        $repo = \Wexoe\Core\Core::entity('automation_team_members');
        if (!$repo) {
            return array();
        }

        $members = $repo->all();
        $members = array_map(function($member) {
            if (!empty($member['image']['url'])) {
                $member['image'] = $member['image']['url'];
            } else {
                $member['image'] = '';
            }
            return $member;
        }, $members);

        $members = array_values(array_filter($members, function($member) use ($tag) {
            if (empty($member['visa'])) {
                return false;
            }
            if ($tag) {
                $tags = $member['tags'] ?? array();
                if (is_string($tags)) {
                    $tags = array($tags);
                }
                return in_array($tag, $tags, true);
            }
            return true;
        }));

        usort($members, function($a, $b) {
            return (float) ($a['order'] ?? 999) <=> (float) ($b['order'] ?? 999);
        });

        return $members;
    }
    
    /**
     * Generera initialer från namn
     */
    private function get_initials($name) {
        $words = explode(' ', trim($name));
        $initials = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= mb_strtoupper(mb_substr($word, 0, 1));
            }
        }
        return mb_substr($initials, 0, 2);
    }
    
    /**
     * Hämta modulfärg baserat på index eller responsibility
     */
    private function get_module_color($index, $responsibility = array()) {
        $colors = array(
            array('bg' => '#f97316', 'name' => 'orange'),
            array('bg' => '#3b82f6', 'name' => 'blue'),
            array('bg' => '#22c55e', 'name' => 'green'),
            array('bg' => '#eab308', 'name' => 'yellow'),
            array('bg' => '#8b5cf6', 'name' => 'purple'),
            array('bg' => '#ec4899', 'name' => 'pink'),
            array('bg' => '#14b8a6', 'name' => 'teal'),
            array('bg' => '#f43f5e', 'name' => 'red')
        );
        
        return $colors[$index % count($colors)];
    }
    
    /**
     * Hämta modul-tab text
     */
    private function get_module_tab_text($member) {
        // Prioritera module_name från Airtable
        if (!empty($member['module_name'])) {
            return $member['module_name'];
        }
        
        // Fallback till responsibility
        $responsibility = $member['responsibility'] ?? array();
        
        // Hantera om det är en sträng
        if (is_string($responsibility) && !empty($responsibility)) {
            return $responsibility;
        }
        // Hantera om det är en array
        if (!empty($responsibility) && is_array($responsibility)) {
            return $responsibility[0];
        }
        return 'Team';
    }
    
    /**
     * Hämta modul-ID
     */
    private function get_module_id($responsibility = array(), $index = 0) {
        $ids = array(
            'OEM' => '1769-OEM',
            'Maskinbyggare' => '1769-OEM',
            'Systemintegratörer' => '1769-SI',
            'Slutkund' => '1769-END',
            'Servo' => '1769-SERVO',
            'Safety' => '1769-SAFE',
            'Support' => '1769-SUP',
            'Division' => '1769-DIV',
            'Account Manager' => '1769-AM'
        );
        
        // Hantera om det är en sträng
        if (is_string($responsibility) && !empty($responsibility)) {
            if (isset($ids[$responsibility])) {
                return $ids[$responsibility];
            }
        }
        
        // Hantera om det är en array
        if (!empty($responsibility) && is_array($responsibility)) {
            foreach ($responsibility as $resp) {
                if (isset($ids[$resp])) {
                    return $ids[$resp];
                }
            }
            // Om inget matchade, returnera baserat på första värdet
            return '1769-' . strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $responsibility[0]), 0, 4));
        }
        
        return '1769-MOD' . ($index + 1);
    }
    
    /**
     * Render shortcode
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'tag' => '',
            'title' => 'Det är vi som är Wexoe',
            'subtitle' => 'Riktiga experter som faktiskt svarar i telefon',
            'debug' => 'false'
        ), $atts);
        
        // Debug-läge - visa all info
        if ($atts['debug'] === 'true') {
            $debug_output = '<div style="background:#1a1a1a;color:#0f0;padding:20px;font-family:monospace;font-size:12px;margin:20px 0;border-radius:8px;">';
            $debug_output .= '<h3 style="color:#0f0;margin:0 0 15px 0;">🔧 WEXOE TEAM RACK DEBUG</h3>';
            $debug_output .= '<strong>DATAKÄLLA:</strong> Wexoe Core entity automation_team_members<br><br>';
            $members = $this->get_team_members($atts['tag'] ?: null);
            $debug_output .= '<strong>ANTAL MEMBERS:</strong> ' . count($members) . '<br><br>';
            
            if (!empty($members)) {
                $debug_output .= '<strong>PROCESSED DATA:</strong><br>';
                $debug_output .= '<pre style="color:#0f0;background:#000;padding:10px;overflow:auto;max-height:300px;">' . htmlspecialchars(print_r($members, true)) . '</pre>';
            } else {
                $debug_output .= '⚠️ Inga members hittades. Kontrollera entity-data i Wexoe Core.<br>';
            }
            
            $debug_output .= '</div>';
            return $debug_output;
        }
        
        if (!wexoe_team_rack_core_ready()) {
            return '<div style="background:#fee;border:1px solid #c00;padding:15px;margin:20px 0;border-radius:4px;"><strong>⚠️ Wexoe Team Rack:</strong> Wexoe Core-pluginet är inte aktivt.</div>';
        }
        
        $members = $this->get_team_members($atts['tag'] ?: null);
        
        if (empty($members)) {
            if (current_user_can('manage_options')) {
                return '<div style="background:#ffeeba;border:1px solid #856404;padding:15px;margin:20px 0;border-radius:4px;color:#856404;">
                    <strong>⚠️ Wexoe Team Rack:</strong> Inga teammedlemmar hittades.<br>
                    Kontrollera att det finns personer med Visa=TRUE i Airtable.<br>
                    <small>Använd [wexoe_team_rack debug="true"] för mer info.</small>
                </div>';
            }
            return '';
        }
        
        $unique_id = 'wexoe-rack-' . uniqid();
        
        ob_start();
        echo $this->render_styles($unique_id);
        echo $this->render_html($members, $unique_id, $atts);
        return ob_get_clean();
    }
    
    /**
     * Render CSS
     */
    private function render_styles($id) {
        $p = '#' . $id;
        
        return <<<CSS
        <style>
            /* === RESET & BASE === */
            {$p}.wexoe-team-rack-section * {
                margin: 0 !important;
                padding: 0 !important;
                box-sizing: border-box !important;
            }

            {$p}.wexoe-team-rack-section {
                font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif !important;
                padding: 0 !important;
                background: transparent !important;
            }

            /* === SECTION HEADER === */
            {$p} .wexoe-rack-header {
                text-align: center !important;
                margin-bottom: 24px !important;
            }

            {$p} .wexoe-rack-header h2 {
                font-size: 34px !important;
                font-weight: 700 !important;
                color: #11325D !important;
                margin: 0 0 8px 0 !important;
                padding: 0 !important;
                line-height: 1.3 !important;
            }

            {$p} .wexoe-rack-header p {
                font-size: 1.1rem !important;
                color: #666 !important;
                margin: 0 !important;
                padding: 0 !important;
                line-height: 1.5 !important;
            }

            /* === RACK CONTAINER === */
            {$p} .wexoe-compactlogix-rack {
                max-width: 1000px !important;
                margin: 0 auto !important;
                background: linear-gradient(180deg, #e8e8e8 0%, #d0d0d0 100%) !important;
                border-radius: 4px !important;
                padding: 8px !important;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.8), inset 0 -1px 0 rgba(0, 0, 0, 0.1) !important;
            }

            /* === RACK INNER === */
            {$p} .wexoe-rack-inner {
                background: linear-gradient(180deg, #c5c5c5 0%, #b0b0b0 100%) !important;
                border: 1px solid #999 !important;
                display: flex !important;
                flex-direction: row !important;
                overflow: hidden !important;
            }

            /* === CONTROLLER MODULE === */
            {$p} .wexoe-controller-module {
                width: 160px !important;
                background: linear-gradient(180deg, #d8d8d8 0%, #c0c0c0 100%) !important;
                border-right: 2px solid #888 !important;
                display: flex !important;
                flex-direction: column !important;
                flex-shrink: 0 !important;
            }

            {$p} .wexoe-controller-header {
                background: linear-gradient(180deg, #2d2d2d 0%, #1a1a1a 100%) !important;
                padding: 16px 12px !important;
                text-align: center !important;
            }

            {$p} .wexoe-logo-container {
                background: #fff !important;
                padding: 8px 12px !important;
                border-radius: 3px !important;
                display: inline-block !important;
                margin-bottom: 10px !important;
            }

            {$p} .wexoe-ab-logo {
                height: 28px !important;
                width: auto !important;
                display: block !important;
                border: none !important;
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            {$p} .wexoe-mobile-title {
                display: none !important;
            }

            {$p} .wexoe-controller-name {
                font-family: 'JetBrains Mono', 'Courier New', monospace !important;
                font-size: 0.7rem !important;
                color: #fff !important;
                letter-spacing: 1px !important;
                text-transform: uppercase !important;
                display: block !important;
            }

            {$p} .wexoe-controller-body {
                flex: 1 !important;
                padding: 16px 12px !important;
                display: flex !important;
                flex-direction: column !important;
                gap: 16px !important;
            }

            /* Status LEDs */
            {$p} .wexoe-status-group {
                display: flex !important;
                flex-direction: column !important;
                gap: 8px !important;
            }

            {$p} .wexoe-status-label {
                font-family: 'JetBrains Mono', 'Courier New', monospace !important;
                font-size: 0.55rem !important;
                color: #555 !important;
                text-transform: uppercase !important;
                letter-spacing: 0.5px !important;
                margin-bottom: 4px !important;
            }

            {$p} .wexoe-led-row {
                display: flex !important;
                gap: 8px !important;
                align-items: center !important;
            }

            {$p} .wexoe-led {
                width: 12px !important;
                height: 12px !important;
                border-radius: 50% !important;
                border: 1px solid rgba(0,0,0,0.3) !important;
                flex-shrink: 0 !important;
            }

            {$p} .wexoe-led.green {
                background: radial-gradient(circle at 30% 30%, #4ade80, #22c55e) !important;
                box-shadow: 0 0 8px #22c55e !important;
            }

            {$p} .wexoe-led.off {
                background: radial-gradient(circle at 30% 30%, #888, #666) !important;
                box-shadow: none !important;
            }

            {$p} .wexoe-led.orange {
                background: radial-gradient(circle at 30% 30%, #fb923c, #f97316) !important;
                box-shadow: 0 0 8px #f97316 !important;
            }

            {$p} .wexoe-led-label {
                font-family: 'JetBrains Mono', 'Courier New', monospace !important;
                font-size: 0.55rem !important;
                color: #444 !important;
            }

            /* Ethernet */
            {$p} .wexoe-ethernet-section {
                margin-top: auto !important;
                padding-top: 12px !important;
                border-top: 1px solid #aaa !important;
            }

            {$p} .wexoe-ethernet-label {
                font-family: 'JetBrains Mono', 'Courier New', monospace !important;
                font-size: 0.55rem !important;
                color: #555 !important;
                text-transform: uppercase !important;
                margin-bottom: 8px !important;
                text-align: center !important;
            }

            {$p} .wexoe-ethernet-port {
                width: 55px !important;
                height: 22px !important;
                background: #1a1a1a !important;
                border-radius: 2px !important;
                margin: 0 auto !important;
                position: relative !important;
            }

            {$p} .wexoe-ethernet-port::before {
                content: '' !important;
                position: absolute !important;
                top: 4px !important;
                left: 4px !important;
                right: 4px !important;
                bottom: 4px !important;
                background: #333 !important;
                border-radius: 1px !important;
            }

            /* === I/O MODULES === */
            {$p} .wexoe-io-modules {
                display: flex !important;
                flex: 1 !important;
                flex-direction: row !important;
            }

            {$p} .wexoe-io-module {
                flex: 1 !important;
                min-width: 0 !important;
                display: flex !important;
                flex-direction: column !important;
                border-right: 1px solid #999 !important;
                background: linear-gradient(180deg, #e0e0e0 0%, #d0d0d0 100%) !important;
                transition: all 0.3s ease !important;
                cursor: pointer !important;
                position: relative !important;
            }

            {$p} .wexoe-io-module:last-child {
                border-right: none !important;
            }

            {$p} .wexoe-io-module:hover {
                background: linear-gradient(180deg, #e8e8e8 0%, #ddd 100%) !important;
                z-index: 10 !important;
            }

            {$p} .wexoe-io-module:hover .wexoe-module-tab-inner {
                transform: translateY(-3px) !important;
            }

            /* Module tab */
            {$p} .wexoe-module-tab {
                height: 28px !important;
                display: flex !important;
                align-items: flex-end !important;
                justify-content: center !important;
                padding: 0 4px !important;
            }

            {$p} .wexoe-module-tab-inner {
                width: 95% !important;
                height: 22px !important;
                border-radius: 3px 3px 0 0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                box-shadow: 0 -2px 4px rgba(0,0,0,0.15) !important;
                transition: transform 0.2s ease !important;
            }

            {$p} .wexoe-module-tab-text {
                font-family: 'JetBrains Mono', 'Courier New', monospace !important;
                font-size: 0.6rem !important;
                color: #fff !important;
                text-transform: uppercase !important;
                letter-spacing: 0.5px !important;
                text-shadow: 0 1px 1px rgba(0,0,0,0.3) !important;
                font-weight: 600 !important;
            }

            /* Module colors */
            {$p} .wexoe-io-module:nth-child(1) .wexoe-module-tab-inner { background: linear-gradient(180deg, #f97316 0%, #ea580c 100%) !important; }
            {$p} .wexoe-io-module:nth-child(2) .wexoe-module-tab-inner { background: linear-gradient(180deg, #3b82f6 0%, #2563eb 100%) !important; }
            {$p} .wexoe-io-module:nth-child(3) .wexoe-module-tab-inner { background: linear-gradient(180deg, #22c55e 0%, #16a34a 100%) !important; }
            {$p} .wexoe-io-module:nth-child(4) .wexoe-module-tab-inner { background: linear-gradient(180deg, #eab308 0%, #ca8a04 100%) !important; }
            {$p} .wexoe-io-module:nth-child(5) .wexoe-module-tab-inner { background: linear-gradient(180deg, #8b5cf6 0%, #7c3aed 100%) !important; }
            {$p} .wexoe-io-module:nth-child(6) .wexoe-module-tab-inner { background: linear-gradient(180deg, #ec4899 0%, #db2777 100%) !important; }
            {$p} .wexoe-io-module:nth-child(7) .wexoe-module-tab-inner { background: linear-gradient(180deg, #14b8a6 0%, #0d9488 100%) !important; }
            {$p} .wexoe-io-module:nth-child(8) .wexoe-module-tab-inner { background: linear-gradient(180deg, #f43f5e 0%, #e11d48 100%) !important; }

            /* Module label */
            {$p} .wexoe-module-label {
                padding: 6px 10px !important;
                background: rgba(255,255,255,0.6) !important;
                border-bottom: 1px solid #bbb !important;
            }

            {$p} .wexoe-module-type {
                display: none !important;
            }

            {$p} .wexoe-module-status-row {
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
            }

            {$p} .wexoe-module-id {
                font-family: 'JetBrains Mono', 'Courier New', monospace !important;
                font-size: 0.65rem !important;
                color: #333 !important;
                font-weight: 600 !important;
            }

            {$p} .wexoe-mod-status {
                display: flex !important;
                gap: 5px !important;
                align-items: center !important;
            }

            {$p} .wexoe-mod-led {
                width: 8px !important;
                height: 8px !important;
                border-radius: 50% !important;
                background: #22c55e !important;
                box-shadow: 0 0 6px #22c55e !important;
                animation: wexoe-pulse 2s ease-in-out infinite !important;
            }

            {$p} .wexoe-mod-status-text {
                font-family: 'JetBrains Mono', 'Courier New', monospace !important;
                font-size: 0.5rem !important;
                color: #22c55e !important;
                text-transform: uppercase !important;
                font-weight: 500 !important;
            }

            /* Module content */
            {$p} .wexoe-module-content {
                flex: 1 !important;
                padding: 14px 10px !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                text-align: center !important;
            }

            {$p} .wexoe-module-photo {
                width: 120px !important;
                height: 120px !important;
                border-radius: 4px !important;
                overflow: hidden !important;
                border: 2px solid #aaa !important;
                background: #ccc !important;
                margin-bottom: 12px !important;
                box-shadow: inset 0 2px 4px rgba(0,0,0,0.1) !important;
            }

            {$p} .wexoe-module-photo img {
                width: 100% !important;
                height: 100% !important;
                object-fit: cover !important;
                filter: grayscale(30%) !important;
                transition: filter 0.3s ease !important;
                border: none !important;
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            {$p} .wexoe-io-module:hover .wexoe-module-photo img {
                filter: grayscale(0%) !important;
            }

            {$p} .wexoe-module-photo .wexoe-initials {
                width: 100% !important;
                height: 100% !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                font-family: 'JetBrains Mono', 'Courier New', monospace !important;
                font-size: 1.8rem !important;
                color: #888 !important;
                background: #ddd !important;
            }

            {$p} .wexoe-module-name {
                font-family: 'DM Sans', -apple-system, sans-serif !important;
                font-size: 1rem !important;
                font-weight: 700 !important;
                color: #1a1a1a !important;
                margin-bottom: 3px !important;
                line-height: 1.2 !important;
            }

            {$p} .wexoe-module-title {
                font-family: 'JetBrains Mono', 'Courier New', monospace !important;
                font-size: 0.6rem !important;
                color: #666 !important;
                text-transform: uppercase !important;
                letter-spacing: 0.5px !important;
                margin-bottom: 12px !important;
            }

            /* Contact terminals */
            {$p} .wexoe-module-terminals {
                width: 100% !important;
                display: flex !important;
                flex-direction: column !important;
                gap: 6px !important;
                margin-top: auto !important;
            }

            {$p} .wexoe-terminal {
                display: flex !important;
                align-items: center !important;
                gap: 8px !important;
                padding: 7px 10px !important;
                background: rgba(0,0,0,0.05) !important;
                border: 1px solid #bbb !important;
                border-radius: 2px !important;
                text-decoration: none !important;
                color: #333 !important;
                font-family: 'JetBrains Mono', 'Courier New', monospace !important;
                font-size: 0.65rem !important;
                transition: all 0.2s ease !important;
            }

            {$p} .wexoe-terminal:hover {
                background: #11325D !important;
                border-color: #11325D !important;
                color: #fff !important;
                text-decoration: none !important;
            }

            {$p} .wexoe-terminal svg {
                width: 14px !important;
                height: 14px !important;
                stroke: currentColor !important;
                fill: none !important;
                stroke-width: 2 !important;
                flex-shrink: 0 !important;
            }

            /* Terminal blocks */
            {$p} .wexoe-module-terminals-block {
                padding: 10px 8px !important;
                background: linear-gradient(180deg, #bbb 0%, #aaa 100%) !important;
                border-top: 1px solid #999 !important;
            }

            {$p} .wexoe-terminal-row {
                display: flex !important;
                justify-content: center !important;
                gap: 3px !important;
            }

            {$p} .wexoe-terminal-pin {
                width: 6px !important;
                height: 10px !important;
                background: linear-gradient(180deg, #888 0%, #666 100%) !important;
                border-radius: 1px !important;
            }

            /* === RACK FOOTER === */
            {$p} .wexoe-rack-footer {
                margin-top: 8px !important;
                padding: 10px 16px !important;
                background: linear-gradient(180deg, #d0d0d0 0%, #c0c0c0 100%) !important;
                border-radius: 2px !important;
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
            }

            {$p} .wexoe-rack-info {
                font-family: 'JetBrains Mono', 'Courier New', monospace !important;
                font-size: 0.6rem !important;
                color: #666 !important;
            }

            {$p} .wexoe-rack-brand {
                display: flex !important;
                align-items: center !important;
                gap: 12px !important;
            }

            {$p} .wexoe-rack-brand img {
                height: 18px !important;
                width: auto !important;
                opacity: 0.7 !important;
                border: none !important;
                box-shadow: none !important;
            }

            {$p} .wexoe-rack-brand-text {
                font-family: 'JetBrains Mono', 'Courier New', monospace !important;
                font-size: 0.85rem !important;
                font-weight: 700 !important;
                color: #11325D !important;
                letter-spacing: 2px !important;
            }

            /* === ANIMATIONS === */
            @keyframes wexoe-pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.4; }
            }

            {$p} .wexoe-io-module:nth-child(1) .wexoe-mod-led { animation-delay: 0s !important; }
            {$p} .wexoe-io-module:nth-child(2) .wexoe-mod-led { animation-delay: 0.3s !important; }
            {$p} .wexoe-io-module:nth-child(3) .wexoe-mod-led { animation-delay: 0.6s !important; }
            {$p} .wexoe-io-module:nth-child(4) .wexoe-mod-led { animation-delay: 0.9s !important; }

            /* === RESPONSIVE - TABLET === */
            @media (max-width: 1000px) {
                {$p} .wexoe-rack-inner {
                    flex-direction: column !important;
                }

                {$p} .wexoe-controller-module {
                    width: 100% !important;
                    border-right: none !important;
                    border-bottom: 2px solid #888 !important;
                    flex-direction: row !important;
                    align-items: stretch !important;
                }

                {$p} .wexoe-controller-header {
                    width: auto !important;
                    min-width: 140px !important;
                    display: flex !important;
                    flex-direction: column !important;
                    justify-content: center !important;
                    padding: 12px 16px !important;
                }

                {$p} .wexoe-controller-body {
                    flex-direction: row !important;
                    flex-wrap: wrap !important;
                    align-items: center !important;
                    gap: 24px !important;
                    padding: 12px 20px !important;
                }

                {$p} .wexoe-status-group {
                    gap: 6px !important;
                }

                {$p} .wexoe-ethernet-section {
                    margin-top: 0 !important;
                    margin-left: auto !important;
                    padding-top: 0 !important;
                    padding-left: 20px !important;
                    border-top: none !important;
                    border-left: 1px solid #aaa !important;
                    display: flex !important;
                    flex-direction: column !important;
                    justify-content: center !important;
                }

                {$p} .wexoe-io-modules {
                    flex-wrap: wrap !important;
                }

                {$p} .wexoe-io-module {
                    min-width: 50% !important;
                    max-width: 50% !important;
                    flex: none !important;
                    border-bottom: 1px solid #999 !important;
                }

                {$p} .wexoe-io-module:nth-child(2n) {
                    border-right: none !important;
                }

                {$p} .wexoe-io-module:nth-last-child(-n+2) {
                    border-bottom: none !important;
                }
            }

            /* === RESPONSIVE - MOBILE === */
            @media (max-width: 600px) {
                {$p}.wexoe-team-rack-section {
                    padding: 0 !important;
                }

                {$p} .wexoe-rack-header h2 {
                    font-size: 1.5rem !important;
                }

                {$p} .wexoe-rack-header p {
                    font-size: 0.95rem !important;
                }

                {$p} .wexoe-controller-module {
                    flex-direction: column !important;
                }

                {$p} .wexoe-controller-header {
                    width: 100% !important;
                    min-width: auto !important;
                    padding: 16px !important;
                }

                {$p} .wexoe-logo-container {
                    padding: 12px 16px !important;
                    margin-bottom: 10px !important;
                }

                {$p} .wexoe-ab-logo {
                    display: none !important;
                }

                {$p} .wexoe-mobile-title {
                    display: block !important;
                    font-family: 'DM Sans', -apple-system, sans-serif !important;
                    font-size: 1.25rem !important;
                    font-weight: 700 !important;
                    color: #11325D !important;
                    text-align: center !important;
                    margin: 0 !important;
                }

                {$p} .wexoe-controller-name {
                    display: none !important;
                }

                {$p} .wexoe-rack-header {
                    display: none !important;
                }

                {$p} .wexoe-controller-body {
                    flex-direction: row !important;
                    justify-content: space-between !important;
                    align-items: flex-start !important;
                    padding: 12px 16px !important;
                    gap: 10px !important;
                }

                {$p} .wexoe-status-group {
                    flex: 1 !important;
                    min-width: 0 !important;
                }

                {$p} .wexoe-status-label {
                    font-size: 0.5rem !important;
                    margin-bottom: 6px !important;
                }

                {$p} .wexoe-led-row {
                    gap: 5px !important;
                }

                {$p} .wexoe-led {
                    width: 10px !important;
                    height: 10px !important;
                }

                {$p} .wexoe-led-label {
                    font-size: 0.5rem !important;
                }

                {$p} .wexoe-ethernet-section {
                    flex: 1 !important;
                    margin-left: 0 !important;
                    padding-left: 0 !important;
                    border-left: none !important;
                    margin-top: 0 !important;
                    padding-top: 0 !important;
                    border-top: none !important;
                }

                {$p} .wexoe-ethernet-label {
                    font-size: 0.5rem !important;
                    margin-bottom: 6px !important;
                }

                {$p} .wexoe-ethernet-port {
                    width: 45px !important;
                    height: 18px !important;
                }

                {$p} .wexoe-io-modules {
                    flex-direction: column !important;
                }

                {$p} .wexoe-io-module {
                    min-width: 100% !important;
                    max-width: 100% !important;
                    border-right: none !important;
                    border-bottom: 1px solid #999 !important;
                }

                {$p} .wexoe-io-module:last-child {
                    border-bottom: none !important;
                }

                {$p} .wexoe-module-terminals-block {
                    display: none !important;
                }

                {$p} .wexoe-rack-footer {
                    flex-direction: column !important;
                    gap: 8px !important;
                    text-align: center !important;
                }

                {$p} .wexoe-rack-info {
                    font-size: 0.55rem !important;
                }
            }
        </style>
CSS;
    }
    
    /**
     * Render HTML
     */
    private function render_html($members, $id, $atts) {
        ob_start();
        ?>
        <section id="<?php echo esc_attr($id); ?>" class="wexoe-team-rack-section">
            
            <div class="wexoe-rack-header">
                <h2><?php echo esc_html($atts['title']); ?></h2>
                <p><?php echo esc_html($atts['subtitle']); ?></p>
            </div>

            <div class="wexoe-compactlogix-rack">
                <div class="wexoe-rack-inner">
                    
                    <!-- Controller Module -->
                    <div class="wexoe-controller-module">
                        <div class="wexoe-controller-header">
                            <div class="wexoe-logo-container">
                                <img src="https://wexoe.se/wp-content/uploads/2026/01/allen-bradley-logo-modified.png" alt="Allen-Bradley" class="wexoe-ab-logo">
                                <span class="wexoe-mobile-title"><?php echo esc_html($atts['title']); ?></span>
                            </div>
                            <span class="wexoe-controller-name">CompactLogix</span>
                        </div>
                        <div class="wexoe-controller-body">
                            <div class="wexoe-status-group">
                                <div class="wexoe-status-label">System Status</div>
                                <div class="wexoe-led-row">
                                    <div class="wexoe-led green"></div>
                                    <span class="wexoe-led-label">RUN</span>
                                </div>
                                <div class="wexoe-led-row">
                                    <div class="wexoe-led green"></div>
                                    <span class="wexoe-led-label">OK</span>
                                </div>
                                <div class="wexoe-led-row">
                                    <div class="wexoe-led off"></div>
                                    <span class="wexoe-led-label">FAULT</span>
                                </div>
                            </div>
                            <div class="wexoe-status-group">
                                <div class="wexoe-status-label">Network</div>
                                <div class="wexoe-led-row">
                                    <div class="wexoe-led green"></div>
                                    <span class="wexoe-led-label">NET A</span>
                                </div>
                                <div class="wexoe-led-row">
                                    <div class="wexoe-led orange"></div>
                                    <span class="wexoe-led-label">NET B</span>
                                </div>
                            </div>
                            <div class="wexoe-ethernet-section">
                                <div class="wexoe-ethernet-label">EtherNet/IP</div>
                                <div class="wexoe-ethernet-port"></div>
                            </div>
                        </div>
                    </div>

                    <!-- I/O Modules -->
                    <div class="wexoe-io-modules">
                        <?php foreach ($members as $index => $member) : ?>
                            <?php echo $this->render_module($member, $index); ?>
                        <?php endforeach; ?>
                    </div>

                </div>

                <!-- Footer -->
                <div class="wexoe-rack-footer">
                    <div class="wexoe-rack-info">CHASSIS: 1769-L33ER • FIRMWARE: v32.011 • SLOTS: <?php echo count($members); ?>/8</div>
                    <div class="wexoe-rack-brand">
                        <img src="https://wexoe.se/wp-content/uploads/2026/01/allen-bradley-logo-modified.png" alt="Allen-Bradley">
                        <span class="wexoe-rack-brand-text">WEXOE</span>
                    </div>
                </div>
            </div>

        </section>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render en modul
     */
    private function render_module($member, $index) {
        $tab_text = $this->get_module_tab_text($member);
        $module_id = $this->get_module_id($member['responsibility'], $index);
        
        ob_start();
        ?>
        <div class="wexoe-io-module">
            <div class="wexoe-module-tab">
                <div class="wexoe-module-tab-inner">
                    <span class="wexoe-module-tab-text"><?php echo esc_html($tab_text); ?></span>
                </div>
            </div>
            <div class="wexoe-module-label">
                <div class="wexoe-module-type">Team Module</div>
                <div class="wexoe-module-status-row">
                    <span class="wexoe-module-id"><?php echo esc_html($module_id); ?></span>
                    <div class="wexoe-mod-status">
                        <div class="wexoe-mod-led"></div>
                        <span class="wexoe-mod-status-text">Online</span>
                    </div>
                </div>
            </div>
            <div class="wexoe-module-content">
                <div class="wexoe-module-photo">
                    <?php if (!empty($member['image'])) : ?>
                        <img src="<?php echo esc_url($member['image']); ?>" alt="<?php echo esc_attr($member['name']); ?>">
                    <?php else : ?>
                        <span class="wexoe-initials"><?php echo esc_html($this->get_initials($member['name'])); ?></span>
                    <?php endif; ?>
                </div>
                <div class="wexoe-module-name"><?php echo esc_html($member['name']); ?></div>
                <div class="wexoe-module-title"><?php echo esc_html($member['title']); ?></div>
                <div class="wexoe-module-terminals">
                    <?php if (!empty($member['email'])) : ?>
                        <a href="mailto:<?php echo esc_attr($member['email']); ?>" class="wexoe-terminal">
                            <svg viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 6l-10 7L2 6"/></svg>
                            <?php echo esc_html($member['email']); ?>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($member['phone'])) : ?>
                        <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $member['phone'])); ?>" class="wexoe-terminal">
                            <svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                            <?php echo esc_html($member['phone']); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="wexoe-module-terminals-block">
                <div class="wexoe-terminal-row">
                    <?php for ($i = 0; $i < 8; $i++) : ?>
                        <div class="wexoe-terminal-pin"></div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initiera plugin
new WexoeTeamRack();
