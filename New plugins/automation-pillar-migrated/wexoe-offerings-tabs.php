<?php
/**
 * Plugin Name: Wexoe Offerings Tabs
 * Description: Tabbad sektion för kundtyper (OEM, Integratörer, Slutkunder) migrerad till Wexoe Core.
 * Version: 2.0.0
 * Author: Wexoe
 */

if (!defined('ABSPATH')) exit;


if (!function_exists('wexoe_offerings_core_ready')) {
function wexoe_offerings_core_ready() {
    return class_exists('\\Wexoe\\Core\\Core')
        && method_exists('\\Wexoe\\Core\\Core', 'entity');
}
}

class Wexoe_Offerings_Tabs {
    
    
    public function __construct() {
        add_shortcode('wexoe_offerings', array($this, 'render_shortcode'));
    }
    
    /**
     * Parse simple markdown to HTML
     * Supports: links, bold, italic
     */
    private function parse_markdown($text) {
        // Escape HTML first
        $text = esc_html($text);
        
        // Links: [text](url)
        $text = preg_replace('/\[([^\]]+)\]\(([^\)]+)\)/', '<a href="$2" target="_blank" rel="noopener">$1</a>', $text);
        
        // Bold: **text** or __text__
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/__(.+?)__/', '<strong>$1</strong>', $text);
        
        // Italic: *text* or _text_ (but not inside links)
        $text = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/', '<em>$1</em>', $text);
        $text = preg_replace('/(?<!_)_(?!_)(.+?)(?<!_)_(?!_)/', '<em>$1</em>', $text);
        
        // Line breaks
        $text = nl2br($text);
        
        return $text;
    }
    
    /**
     * Hämta data via Wexoe Core
     */
    private function fetch_data($division = '') {
        if (!wexoe_offerings_core_ready()) {
            return array('error' => 'Wexoe Core-pluginet är inte aktivt.');
        }

        $repo = \Wexoe\Core\Core::entity('automation_offerings');
        if (!$repo) {
            return array('error' => 'Entity-schema "automation_offerings" saknas i Wexoe Core.');
        }

        $records = $repo->all();
        if (!empty($division)) {
            $records = array_values(array_filter($records, function($item) use ($division) {
                if (!isset($item['division']) || empty($item['division'])) {
                    return false;
                }
                if (is_array($item['division'])) {
                    foreach ($item['division'] as $candidate) {
                        if (strcasecmp((string) $candidate, (string) $division) === 0) {
                            return true;
                        }
                    }
                    return false;
                }
                return strcasecmp((string) $item['division'], (string) $division) === 0;
            }));
        }

        return $records;
    }
    
    /**
     * Render shortcode
     * 
     * Användning:
     * [wexoe_offerings] - Visa alla
     * [wexoe_offerings division="Industry"] - Filtrera på division
     * [wexoe_offerings debug="true"] - Visa debug-info
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'division' => '',
            'title' => 'Hur kan vi hjälpa dig?',
            'debug' => 'false'
        ), $atts);
        
        $debug = $atts['debug'] === 'true';
        $records = $this->fetch_data($atts['division']);
        
        // Debug output
        if ($debug) {
            return '<pre style="background:#f5f5f5;padding:20px;overflow:auto;">' . 
                   'Division filter: ' . ($atts['division'] ?: '(ingen)') . "\n\n" .
                   'API Response: ' . print_r($records, true) . 
                   '</pre>';
        }
        
        // Hantera fel
        if (isset($records['error'])) {
            return '<!-- Wexoe Offerings Error: ' . esc_html($records['error']) . ' -->';
        }
        
        // Ingen data
        if (empty($records)) {
            return '<!-- Wexoe Offerings: Inga poster hittades -->';
        }
        
        // Generera unik ID
        $id = 'wexoe-offerings-' . uniqid();
        
        // Sortera efter Order-fält
        usort($records, function($a, $b) {
            $order_a = isset($a['order']) ? intval($a['order']) : 999;
            $order_b = isset($b['order']) ? intval($b['order']) : 999;
            return $order_a - $order_b;
        });
        
        // Bygg HTML
        $html = $this->render_styles($id);
        $html .= $this->render_html($id, $records, $atts['title']);
        $html .= $this->render_scripts($id);
        
        return $html;
    }
    
    /**
     * Render HTML
     */
    private function render_html($id, $records, $title = '') {
        $html = '<section id="' . esc_attr($id) . '" class="wexoe-offerings-section">';
        
        // Title
        if (!empty($title)) {
            $html .= '<h2 class="wexoe-off-title">' . esc_html($title) . '</h2>';
        }
        
        // Tabs
        $html .= '<div class="wexoe-off-tabs">';
        $first = true;
        foreach ($records as $record) {
            $name = isset($record['name']) ? $record['name'] : 'Untitled';
            $tab_id = sanitize_title($name);
            $active = $first ? ' active' : '';
            
            $html .= '<button class="wexoe-off-tab' . $active . '" data-tab="' . esc_attr($tab_id) . '">';
            $html .= esc_html($name);
            $html .= '</button>';
            
            $first = false;
        }
        $html .= '</div>';
        
        // Content
        $first = true;
        foreach ($records as $record) {
            $name = isset($record['name']) ? $record['name'] : 'Untitled';
            $tab_id = sanitize_title($name);
            $heading = isset($record['heading']) ? $record['heading'] : '';
            $description = isset($record['description']) ? $record['description'] : '';
            $active = $first ? ' active' : '';
            
            // Bild - kan vara Airtable attachment eller direkt URL
            $image_url = '';
            if (!empty($record['image']['url'])) {
                $image_url = $record['image']['url'];
            } elseif (!empty($record['image_url'])) {
                $image_url = $record['image_url'];
            }
            
            // Benefits (om de finns)
            $benefits = array();
            for ($i = 1; $i <= 5; $i++) {
                $benefit_field = 'benefit_' . $i;
                if (!empty($record[$benefit_field])) {
                    $benefits[] = $record[$benefit_field];
                }
            }
            
            // CTA
            $cta_text = isset($record['cta_text']) ? $record['cta_text'] : 'Kontakta oss';
            $cta_url = isset($record['cta_url']) ? $record['cta_url'] : '#kontakt';
            
            $html .= '<div class="wexoe-off-content' . $active . '" id="' . esc_attr($id) . '-' . esc_attr($tab_id) . '">';
            $html .= '<div class="wexoe-off-grid">';
            
            // Text column
            $html .= '<div class="wexoe-off-text">';
            if ($heading) {
                $html .= '<h3>' . esc_html($heading) . '</h3>';
            }
            if ($description) {
                $html .= '<p>' . $this->parse_markdown($description) . '</p>';
            }
            
            // Benefits list
            if (!empty($benefits)) {
                $html .= '<ul class="wexoe-off-list">';
                foreach ($benefits as $benefit) {
                    $html .= '<li>' . esc_html($benefit) . '</li>';
                }
                $html .= '</ul>';
            }
            
            // CTA
            $html .= '<div class="wexoe-off-cta">';
            $html .= '<a href="' . esc_url($cta_url) . '" class="wexoe-off-btn">' . esc_html($cta_text) . '</a>';
            $html .= '</div>';
            
            $html .= '</div>'; // .wexoe-off-text
            
            // Image column
            $html .= '<div class="wexoe-off-image">';
            if ($image_url) {
                $html .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($heading) . '">';
            } else {
                $html .= '<div class="wexoe-off-placeholder">📦</div>';
            }
            $html .= '</div>';
            
            $html .= '</div>'; // .wexoe-off-grid
            $html .= '</div>'; // .wexoe-off-content
            
            $first = false;
        }
        
        $html .= '</section>';
        
        return $html;
    }
    
    /**
     * Render CSS
     */
    private function render_styles($id) {
        return '
        <style>
            /* === WEXOE OFFERINGS TABS v1.0.0 === */
            
            /* Reset */
            #' . $id . ',
            #' . $id . ' * {
                box-sizing: border-box !important;
            }
            
            #' . $id . ' li::before {
                content: none !important;
                display: none !important;
                background: none !important;
            }
            
            /* Section */
            #' . $id . ' {
                font-family: "DM Sans", system-ui, -apple-system, sans-serif !important;
                background: #FFFFFF !important;
                padding: 0 !important;
            }
            
            /* Title */
            #' . $id . ' .wexoe-off-title,
            body #top #' . $id . ' .wexoe-off-title {
                font-size: 34px !important;
                font-weight: 700 !important;
                color: #0f2740 !important;
                margin: 0 0 16px 0 !important;
                line-height: 1.2 !important;
                font-family: "Open Sans", system-ui, sans-serif !important;
            }
            
            /* Tabs */
            #' . $id . ' .wexoe-off-tabs {
                display: flex !important;
                justify-content: flex-start !important;
                gap: 10px !important;
                margin-bottom: 24px !important;
                flex-wrap: nowrap !important;
            }
            
            #' . $id . ' .wexoe-off-tab,
            #' . $id . ' button.wexoe-off-tab,
            body #top #' . $id . ' .wexoe-off-tab,
            .avia_codeblock #' . $id . ' button.wexoe-off-tab {
                padding: 8px 14px !important;
                border: 2px solid #11325D !important;
                background: transparent !important;
                background-color: transparent !important;
                background-image: none !important;
                color: #11325D !important;
                font-family: "DM Sans", system-ui, sans-serif !important;
                font-size: 14px !important;
                font-weight: 600 !important;
                border-radius: 50px !important;
                cursor: pointer !important;
                transition: all 0.25s ease !important;
                text-align: center !important;
                white-space: nowrap !important;
                min-width: 0 !important;
                width: auto !important;
                max-width: none !important;
                opacity: 1 !important;
            }
            
            #' . $id . ' .wexoe-off-tab:hover,
            #' . $id . ' .wexoe-off-tab:focus,
            #' . $id . ' button.wexoe-off-tab:hover,
            #' . $id . ' button.wexoe-off-tab:focus,
            body #top #' . $id . ' .wexoe-off-tab:hover,
            body #top #' . $id . ' .wexoe-off-tab:focus,
            body #top #' . $id . ' button.wexoe-off-tab:hover,
            body #top #' . $id . ' button.wexoe-off-tab:focus,
            html body #top #' . $id . ' .wexoe-off-tab:hover,
            html body #top #' . $id . ' button.wexoe-off-tab:hover,
            .avia_codeblock #' . $id . ' button.wexoe-off-tab:hover,
            body #top .avia_codeblock #' . $id . ' button:hover {
                background: #11325D !important;
                background-color: #11325D !important;
                background-image: none !important;
                color: #FFFFFF !important;
                opacity: 1 !important;
            }
            
            #' . $id . ' .wexoe-off-tab.active,
            #' . $id . ' button.wexoe-off-tab.active,
            body #top #' . $id . ' .wexoe-off-tab.active,
            body #top #' . $id . ' button.wexoe-off-tab.active,
            html body #top #' . $id . ' .wexoe-off-tab.active,
            html body #top #' . $id . ' button.wexoe-off-tab.active,
            .avia_codeblock #' . $id . ' button.wexoe-off-tab.active {
                background: #11325D !important;
                background-color: #11325D !important;
                background-image: none !important;
                color: #FFFFFF !important;
                opacity: 1 !important;
            }
            
            /* Content */
            #' . $id . ' .wexoe-off-content {
                display: none !important;
                animation: wexoeOffFade-' . $id . ' 0.4s ease !important;
            }
            
            #' . $id . ' .wexoe-off-content.active {
                display: block !important;
            }
            
            @keyframes wexoeOffFade-' . $id . ' {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            /* Grid */
            #' . $id . ' .wexoe-off-grid {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                gap: 50px !important;
                align-items: start !important;
            }
            
            /* Text */
            #' . $id . ' .wexoe-off-text h3 {
                font-size: 26px !important;
                font-weight: 700 !important;
                color: #0f2740 !important;
                margin: 0 0 10px 0 !important;
                line-height: 1.3 !important;
            }
            
            #' . $id . ' .wexoe-off-text p {
                font-size: 16px !important;
                font-weight: 400 !important;
                color: #0f2740 !important;
                margin: 0 0 16px 0 !important;
                line-height: 1.6 !important;
            }
            
            #' . $id . ' .wexoe-off-text p a {
                color: #11325D !important;
                text-decoration: underline !important;
            }
            
            #' . $id . ' .wexoe-off-text p a:hover {
                color: #3974B5 !important;
            }
            
            #' . $id . ' .wexoe-off-text p em,
            #' . $id . ' .wexoe-off-text p i {
                font-style: italic !important;
            }
            
            #' . $id . ' .wexoe-off-text p strong,
            #' . $id . ' .wexoe-off-text p b {
                font-weight: 700 !important;
            }
            
            /* List */
            #' . $id . ' .wexoe-off-list {
                list-style: none !important;
                margin: 20px 0 !important;
                padding: 0 !important;
            }
            
            #' . $id . ' .wexoe-off-list li {
                padding: 10px 0 10px 32px !important;
                position: relative !important;
                color: #0f2740 !important;
                font-size: 16px !important;
                line-height: 1.5 !important;
                background: none !important;
                background-image: none !important;
                list-style: none !important;
            }
            
            #' . $id . ' .wexoe-off-list li::after {
                content: "✓" !important;
                position: absolute !important;
                left: 0 !important;
                top: 10px !important;
                color: #10B981 !important;
                font-weight: 700 !important;
                font-size: 16px !important;
                display: inline !important;
            }
            
            /* CTA */
            #' . $id . ' .wexoe-off-cta {
                margin-top: 20px !important;
            }
            
            #' . $id . ' .wexoe-off-btn {
                display: inline-flex !important;
                align-items: center !important;
                gap: 10px !important;
                padding: 14px 28px !important;
                background: #F28C28 !important;
                color: #FFFFFF !important;
                text-decoration: none !important;
                border-radius: 4px !important;
                font-family: "DM Sans", system-ui, sans-serif !important;
                font-weight: 600 !important;
                font-size: 15px !important;
                transition: all 0.25s ease !important;
                border: none !important;
            }
            
            #' . $id . ' .wexoe-off-btn:hover {
                background: #e07b1a !important;
                color: #FFFFFF !important;
                transform: translateX(4px) !important;
            }
            
            #' . $id . ' .wexoe-off-btn::after {
                content: "→" !important;
                font-size: 18px !important;
                transition: transform 0.25s ease !important;
            }
            
            /* Image */
            #' . $id . ' .wexoe-off-image {
                border-radius: 8px !important;
                overflow: hidden !important;
            }
            
            #' . $id . ' .wexoe-off-image img {
                width: 100% !important;
                height: 350px !important;
                object-fit: cover !important;
                border-radius: 8px !important;
            }
            
            #' . $id . ' .wexoe-off-placeholder {
                width: 100% !important;
                height: 350px !important;
                background: linear-gradient(135deg, #11325D 0%, #3974B5 100%) !important;
                border-radius: 8px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                color: #FFFFFF !important;
                font-size: 64px !important;
            }
            
            /* Responsive: Tablet */
            @media (max-width: 1024px) {
                #' . $id . ' .wexoe-off-grid {
                    gap: 40px !important;
                }
                
                #' . $id . ' .wexoe-off-text h3 {
                    font-size: 22px !important;
                }
            }
            
            /* Responsive: Mobile */
            @media (max-width: 767px) {
                #' . $id . ' .wexoe-off-title {
                    font-size: 23px !important;
                    margin-bottom: 12px !important;
                }
                
                #' . $id . ' .wexoe-off-tabs {
                    flex-direction: column !important;
                    flex-wrap: nowrap !important;
                    align-items: stretch !important;
                    gap: 6px !important;
                    margin-bottom: 20px !important;
                }
                
                #' . $id . ' .wexoe-off-tab,
                #' . $id . ' button.wexoe-off-tab,
                body #top #' . $id . ' .wexoe-off-tab,
                body #top #' . $id . ' button.wexoe-off-tab,
                .avia_codeblock #' . $id . ' button.wexoe-off-tab {
                    width: 100% !important;
                    min-width: 0 !important;
                    max-width: none !important;
                    text-align: center !important;
                    padding: 12px 20px !important;
                    font-size: 14px !important;
                    font-family: "Open Sans", system-ui, sans-serif !important;
                    background-image: none !important;
                    opacity: 1 !important;
                }
                
                #' . $id . ' .wexoe-off-text h3 {
                    font-size: 18px !important;
                    font-family: "Open Sans", system-ui, sans-serif !important;
                    margin-bottom: 6px !important;
                }
                
                #' . $id . ' .wexoe-off-text p,
                #' . $id . ' .wexoe-off-list li {
                    font-family: "Open Sans", system-ui, sans-serif !important;
                    font-size: 15px !important;
                }
                
                #' . $id . ' .wexoe-off-grid {
                    grid-template-columns: 1fr !important;
                    gap: 20px !important;
                }
                
                #' . $id . ' .wexoe-off-image,
                body #top #' . $id . ' .wexoe-off-image {
                    display: none !important;
                }
                
                #' . $id . ' .wexoe-off-placeholder,
                body #top #' . $id . ' .wexoe-off-placeholder {
                    display: none !important;
                }
                
                #' . $id . ' .wexoe-off-btn,
                body #top #' . $id . ' .wexoe-off-btn {
                    width: 100% !important;
                    justify-content: center !important;
                    font-family: "Open Sans", system-ui, sans-serif !important;
                }
            }
            
            /* Responsive: Extra small mobile (414px) */
            @media (max-width: 414px) {
                #' . $id . ' .wexoe-off-tab,
                #' . $id . ' button.wexoe-off-tab,
                body #top #' . $id . ' .wexoe-off-tab,
                body #top #' . $id . ' button.wexoe-off-tab {
                    width: 100% !important;
                    min-width: 0 !important;
                    max-width: none !important;
                    padding: 10px 16px !important;
                    font-size: 14px !important;
                    background-image: none !important;
                }
            }
        </style>';
    }
    
    /**
     * Render JavaScript
     */
    private function render_scripts($id) {
        return '
        <script>
        (function() {
            if (document.body && document.body.classList.contains("wp-admin")) return;
            
            function initWexoeOfferings() {
                var container = document.getElementById("' . $id . '");
                if (!container) return;
                
                var tabs = container.querySelectorAll(".wexoe-off-tab");
                var contents = container.querySelectorAll(".wexoe-off-content");
                
                tabs.forEach(function(tab) {
                    tab.addEventListener("click", function() {
                        var target = this.dataset.tab;
                        
                        tabs.forEach(function(t) { t.classList.remove("active"); });
                        contents.forEach(function(c) { c.classList.remove("active"); });
                        
                        this.classList.add("active");
                        var targetContent = document.getElementById("' . $id . '-" + target);
                        if (targetContent) {
                            targetContent.classList.add("active");
                        }
                    });
                });
            }
            
            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", initWexoeOfferings);
            } else {
                initWexoeOfferings();
            }
        })();
        </script>';
    }
}

// Init
new Wexoe_Offerings_Tabs();

// Load Google Fonts
add_action('wp_head', function() {
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">
          <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
          <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">';
}, 5);
