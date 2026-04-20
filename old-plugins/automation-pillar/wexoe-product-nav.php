<?php
/**
 * Plugin Name:       Wexoe Product Navigation
 * Plugin URI:        https://wexoe.se
 * Description:       Modulär produktnavigation med nav-kort, event och kampanjer hämtade från Airtable. Stödjer division-filtrering och är helt datadriven.
 * Version:           2.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Wexoe
 * Author URI:        https://wexoe.se
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wexoe-product-nav
 * Domain Path:       /languages
 * 
 * @package Wexoe_Product_Nav
 * @version 2.0.0
 * 
 * == Changelog ==
 * 
 * = 2.0.0 (2025-02-03) =
 * * BREAKING: All data now fetched from Airtable instead of WordPress
 * * NEW: Division filtering via shortcode parameter [wexoe_product_nav div="Industry"]
 * * NEW: Dynamic nav cards - unlimited items supported
 * * NEW: Icons stored as SVG code in Airtable text field
 * * REMOVED: WordPress CPT "arrangement" integration
 * * REMOVED: Meta box for featured event selection
 * * IMPROVED: Cleaner code structure with comprehensive comments
 * * IMPROVED: Prepared for future caching implementation
 * 
 * = 1.3.0 =
 * * Initial version with hardcoded product areas
 * * Featured event from WordPress CPT
 * * Featured campaign from Airtable
 * 
 * == Shortcode Usage ==
 * 
 * Basic usage:
 * [wexoe_product_nav]
 * 
 * With all parameters:
 * [wexoe_product_nav 
 *     title="Utforska våra produktområden" 
 *     subtitle="Hitta rätt produkt för ditt behov"
 *     div="Industry"
 *     show_event="true"
 *     show_campaign="true"
 *     padding_top="30"
 *     padding_bottom="30"
 * ]
 * 
 * == Airtable Setup ==
 * 
 * Required fields in Airtable table:
 * - Name (text): Display name for the item
 * - URL (url): Link destination
 * - Type (single select): "Nav", "Event", or "Campaign"
 * - Icon (long text): SVG code for nav items
 * - Division (linked record): Link to Divisions table
 * - Description (long text): For Event/Campaign cards
 * - Button text (text): CTA button text
 * - Benefit 1 (text): First benefit for campaigns
 * - Benefit 2 (text): Second benefit for campaigns
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('WEXOE_PN_VERSION', '2.0.0');
define('WEXOE_PN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WEXOE_PN_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class
 * 
 * Handles all functionality for the Wexoe Product Navigation plugin.
 * Fetches data from Airtable and renders a responsive product navigation
 * component with nav cards, event card, and campaign card.
 * 
 * @since 1.0.0
 * @since 2.0.0 Refactored to fetch all data from Airtable
 */
class Wexoe_Product_Nav {

    /**
     * Airtable API credentials
     * 
     * @var string
     */
    private $airtable_api_key = '.';
    
    /**
     * Airtable Base ID
     * 
     * @var string
     */
    private $airtable_base_id = 'appXoUcK68dQwASjF';
    
    /**
     * Airtable Table ID for Product Navigation
     * 
     * @var string
     */
    private $airtable_table_id = 'tblJa2Kd6QHjFXPJZ';

    /**
     * Cache expiration time in seconds
     * Set to 0 to disable caching (current default)
     * Recommended: 300 (5 minutes) for production
     * 
     * @var int
     */
    private $cache_expiration = 0;

    /**
     * Constructor - Register shortcode
     * 
     * @since 1.0.0
     */
    public function __construct() {
        add_shortcode('wexoe_product_nav', array($this, 'render_shortcode'));
    }

    /**
     * Fetch items from Airtable with optional division filtering
     * 
     * Retrieves all product navigation items from Airtable and categorizes
     * them into nav items, event, and campaign based on the Type field.
     * 
     * @since 2.0.0
     * 
     * @param string|null $division Division name to filter by (optional)
     * @return array {
     *     Categorized items from Airtable
     *     
     *     @type array  $nav      Array of nav items with name, url, icon
     *     @type array|null $event    Event item or null if none found
     *     @type array|null $campaign Campaign item or null if none found
     * }
     */
    private function get_airtable_items($division = null) {
        // Initialize result structure
        // featured_1 and featured_2 hold the two bottom cards (Event, Campaign, or Case)
        $result = array(
            'nav' => array(),
            'featured_1' => null,
            'featured_2' => null
        );

        // Check cache first (if enabled)
        if ($this->cache_expiration > 0) {
            $cache_key = 'wexoe_pn_' . md5($division ?? 'all');
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                return $cached;
            }
        }

        // Build Airtable API URL
        $url = "https://api.airtable.com/v0/{$this->airtable_base_id}/{$this->airtable_table_id}";
        
        // Build query parameters
        $query_params = array();
        
        // Build filter formula
        $filters = array();
        
        // Only fetch active items (for Event, Campaign, Case)
        // Nav items don't need Active checkbox
        $filters[] = "OR({Type}='Nav', {Active}=TRUE())";
        
        // Add division filter if specified
        // Division is a linked record field, so we search within ARRAYJOIN
        if ($division) {
            $filters[] = "FIND('" . addslashes($division) . "', ARRAYJOIN({Division}, ','))";
        }
        
        // Combine filters with AND
        if (!empty($filters)) {
            $query_params['filterByFormula'] = 'AND(' . implode(', ', $filters) . ')';
        }
        
        // Sort by Order field (ascending)
        $query_params['sort[0][field]'] = 'Order';
        $query_params['sort[0][direction]'] = 'asc';
        
        if (!empty($query_params)) {
            $url .= '?' . http_build_query($query_params);
        }

        // Make API request
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->airtable_api_key,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 15
        ));

        // Handle errors
        if (is_wp_error($response)) {
            error_log('Wexoe Product Nav: Airtable API error - ' . $response->get_error_message());
            return $result;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data['records'])) {
            return $result;
        }

        // Categorize records by Type field
        foreach ($data['records'] as $record) {
            $fields = $record['fields'];
            $type = $fields['Type'] ?? '';

            switch ($type) {
                case 'Nav':
                    // Nav items: collect all of them
                    $result['nav'][] = array(
                        'name' => $fields['Name'] ?? '',
                        'url' => $fields['URL'] ?? '#',
                        'icon' => $fields['Icon'] ?? '', // SVG code from Airtable
                    );
                    break;

                case 'Event':
                case 'Campaign':
                case 'Case':
                    // Featured cards: fill first available slot (max 2)
                    $featured_item = array(
                        'type' => $type,
                        'name' => $fields['Name'] ?? '',
                        'url' => $fields['URL'] ?? '#',
                        'description' => $fields['Description'] ?? '',
                        'button_text' => $fields['Button text'] ?? 'Läs mer',
                        'benefit_1' => $fields['Benefit 1'] ?? null,
                        'benefit_2' => $fields['Benefit 2'] ?? null,
                    );
                    
                    if ($result['featured_1'] === null) {
                        $result['featured_1'] = $featured_item;
                    } elseif ($result['featured_2'] === null) {
                        $result['featured_2'] = $featured_item;
                    }
                    // Ignore if both slots are filled
                    break;
            }
        }

        // Cache results (if enabled)
        if ($this->cache_expiration > 0) {
            set_transient($cache_key, $result, $this->cache_expiration);
        }

        return $result;
    }

    /**
     * Generate scoped CSS styles
     * 
     * Creates CSS with unique ID prefix to prevent style conflicts
     * with other page elements or themes.
     * 
     * @since 1.0.0
     * @since 2.0.0 Updated title color to white for better contrast
     * 
     * @param string $id           Unique instance ID for CSS scoping
     * @param string $padding_top  Top padding in pixels
     * @param string $padding_bottom Bottom padding in pixels
     * @return string Complete CSS stylesheet
     */
    private function get_styles($id, $padding_top = '30', $padding_bottom = '30') {
        $p = '#' . $id;
        
        return "
            @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap');

            /* === CONTAINER === */
            {$p}.wexoe-product-nav {
                --wexoe-main-blue: #11325D !important;
                --wexoe-light-blue: #1a4175 !important;
                --wexoe-action-orange: #F28C28 !important;
                --wexoe-white: #ffffff !important;
                --wexoe-gray: #f8f9fa !important;
                --wexoe-text: #333 !important;
                --wexoe-text-light: #666 !important;
                
                background: var(--wexoe-main-blue) !important;
                padding-top: {$padding_top}px !important;
                padding-bottom: {$padding_bottom}px !important;
                padding-left: 0 !important;
                padding-right: 0 !important;
                
                /* Break out of Enfold container to full width */
                position: relative !important;
                width: 100vw !important;
                left: 50% !important;
                right: 50% !important;
                margin-left: -50vw !important;
                margin-right: -50vw !important;
            }

            {$p}.wexoe-product-nav,
            {$p}.wexoe-product-nav * {
                box-sizing: border-box !important;
                font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif !important;
            }

            {$p} .wexoe-pn-inner {
                max-width: 1000px !important;
                margin: 0 auto !important;
                padding: 0 40px !important;
            }

            @media (max-width: 1270px) {
                {$p} .wexoe-pn-inner {
                    padding: 0 40px !important;
                }
            }

            /* === HEADER === */
            {$p} .wexoe-pn-header {
                text-align: center !important;
                margin-bottom: 40px !important;
            }

            {$p} .wexoe-pn-title {
                font-size: 2rem !important;
                font-weight: 700 !important;
                color: var(--wexoe-white) !important;
                margin: 0 0 12px 0 !important;
                padding: 0 !important;
            }

            {$p} .wexoe-pn-subtitle {
                font-size: 1.1rem !important;
                color: rgba(255, 255, 255, 0.8) !important;
                max-width: 600px !important;
                margin: 0 auto !important;
                padding: 0 !important;
            }

            /* === NAV CARDS GRID === */
            {$p} .wexoe-pn-grid {
                display: grid !important;
                grid-template-columns: repeat(4, 1fr) !important;
                gap: 16px !important;
                margin-bottom: 20px !important;
            }

            {$p} .wexoe-pn-card {
                background: var(--wexoe-white) !important;
                border-radius: 2px !important;
                padding: 20px 18px !important;
                display: flex !important;
                align-items: center !important;
                gap: 14px !important;
                text-decoration: none !important;
                transition: all 0.2s ease !important;
                cursor: pointer !important;
                position: relative !important;
                overflow: hidden !important;
                border: none !important;
            }

            /* Orange left border on hover */
            {$p} .wexoe-pn-card::before {
                content: '' !important;
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                width: 3px !important;
                height: 100% !important;
                background: var(--wexoe-action-orange) !important;
                transform: scaleY(0) !important;
                transition: transform 0.2s ease !important;
            }

            {$p} .wexoe-pn-card:hover {
                background: var(--wexoe-white) !important;
                transform: translateY(-2px) !important;
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15) !important;
                text-decoration: none !important;
            }

            {$p} .wexoe-pn-card:hover::before {
                transform: scaleY(1) !important;
            }

            /* Icon styling */
            {$p} .wexoe-pn-card-icon {
                width: 40px !important;
                height: 40px !important;
                flex-shrink: 0 !important;
            }

            {$p} .wexoe-pn-card-icon svg {
                width: 100% !important;
                height: 100% !important;
                stroke: var(--wexoe-main-blue) !important;
                fill: none !important;
                stroke-width: 1.5 !important;
                transition: stroke 0.25s ease !important;
            }

            {$p} .wexoe-pn-card:hover .wexoe-pn-card-icon svg {
                stroke: var(--wexoe-action-orange) !important;
            }

            {$p} .wexoe-pn-card-title {
                font-size: 0.95rem !important;
                font-weight: 500 !important;
                color: var(--wexoe-main-blue) !important;
                line-height: 1.3 !important;
            }

            /* === FEATURED CARDS (Event & Campaign) === */
            {$p} .wexoe-pn-featured {
                display: grid !important;
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 16px !important;
                margin-top: 16px !important;
            }

            /* Single card layout */
            {$p} .wexoe-pn-featured--single {
                grid-template-columns: 1fr !important;
                max-width: 500px !important;
                margin-left: auto !important;
                margin-right: auto !important;
            }

            {$p} .wexoe-pn-featured-card {
                background: var(--wexoe-white) !important;
                border-radius: 2px !important;
                overflow: hidden !important;
                display: flex !important;
                flex-direction: column !important;
                transition: all 0.2s ease !important;
                text-decoration: none !important;
                color: inherit !important;
                cursor: pointer !important;
                border: none !important;
            }

            {$p} .wexoe-pn-featured-card:hover {
                transform: translateY(-2px) !important;
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15) !important;
                text-decoration: none !important;
            }

            /* Type label (Event/Kampanj) */
            {$p} .wexoe-pn-featured-label {
                position: absolute !important;
                top: 16px !important;
                right: 16px !important;
                background: transparent !important;
                color: var(--wexoe-main-blue) !important;
                font-size: 0.55rem !important;
                font-weight: 600 !important;
                padding: 2px 6px !important;
                border-radius: 2px !important;
                border: 1px solid var(--wexoe-main-blue) !important;
                text-transform: uppercase !important;
                letter-spacing: 0.5px !important;
                margin: 0 !important;
            }

            {$p} .wexoe-pn-featured-content {
                padding: 24px !important;
                display: flex !important;
                flex-direction: column !important;
                flex-grow: 1 !important;
                min-height: 200px !important;
                position: relative !important;
            }

            {$p} .wexoe-pn-featured-title {
                font-size: 1.15rem !important;
                font-weight: 600 !important;
                color: var(--wexoe-main-blue) !important;
                margin: 0 0 12px 0 !important;
                padding: 0 !important;
                line-height: 1.3 !important;
                padding-right: 70px !important;
            }

            /* Description with line clamping */
            {$p} .wexoe-pn-featured-desc {
                font-size: 0.9rem !important;
                color: var(--wexoe-text-light) !important;
                line-height: 1.5 !important;
                margin: 0 0 16px 0 !important;
                padding: 0 !important;
                display: -webkit-box !important;
                display: box !important;
                -webkit-line-clamp: 3 !important;
                line-clamp: 3 !important;
                -webkit-box-orient: vertical !important;
                box-orient: vertical !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                max-height: 4.5em !important;
                word-wrap: break-word !important;
            }
            
            /* Campaign: 2 lines to make room for benefits */
            {$p} .wexoe-pn-featured-card--campaign .wexoe-pn-featured-desc {
                -webkit-line-clamp: 2 !important;
                line-clamp: 2 !important;
                max-height: 3em !important;
            }

            /* Benefits list */
            {$p} .wexoe-pn-benefits {
                list-style: none !important;
                margin: 0 0 16px 0 !important;
                padding: 0 !important;
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 12px !important;
            }

            {$p} .wexoe-pn-benefits li {
                display: flex !important;
                align-items: center !important;
                gap: 6px !important;
                font-size: 0.85rem !important;
                color: var(--wexoe-text) !important;
                margin: 0 !important;
                padding: 0 !important;
                padding-left: 20px !important;
                list-style: none !important;
                background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2310B981' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='20 6 9 17 4 12'%3E%3C/polyline%3E%3C/svg%3E\") !important;
                background-repeat: no-repeat !important;
                background-position: left center !important;
                background-size: 16px 16px !important;
            }

            {$p} .wexoe-pn-benefits li::before {
                display: none !important;
            }

            /* CTA Button */
            {$p} .wexoe-pn-featured-btn {
                display: inline-flex !important;
                align-items: center !important;
                gap: 8px !important;
                padding: 10px 18px !important;
                background: var(--wexoe-action-orange) !important;
                color: var(--wexoe-white) !important;
                text-decoration: none !important;
                border-radius: 2px !important;
                font-size: 0.9rem !important;
                font-weight: 500 !important;
                transition: all 0.2s ease !important;
                align-self: flex-start !important;
                border: none !important;
                margin-top: auto !important;
            }

            {$p} .wexoe-pn-featured-card:hover .wexoe-pn-featured-btn {
                background: #e07d1f !important;
            }

            {$p} .wexoe-pn-featured-btn-arrow {
                transition: transform 0.2s ease !important;
                font-size: 0.9em !important;
            }

            {$p} .wexoe-pn-featured-card:hover .wexoe-pn-featured-btn-arrow {
                transform: translateX(3px) !important;
            }

            /* === RESPONSIVE === */
            
            /* Tablet: 3 columns */
            @media (max-width: 1024px) {
                {$p} .wexoe-pn-grid {
                    grid-template-columns: repeat(3, 1fr) !important;
                }
            }

            /* Mobile */
            @media (max-width: 768px) {
                {$p} .wexoe-pn-inner {
                    padding: 0 5% !important;
                }

                /* 2 columns for nav cards */
                {$p} .wexoe-pn-grid {
                    grid-template-columns: repeat(2, 1fr) !important;
                    gap: 10px !important;
                }

                {$p} .wexoe-pn-card {
                    padding: 14px 12px !important;
                    gap: 10px !important;
                }

                {$p} .wexoe-pn-card-icon {
                    width: 28px !important;
                    height: 28px !important;
                }

                {$p} .wexoe-pn-card-title {
                    font-size: 0.8rem !important;
                }

                /* Horizontal scroll for featured cards */
                {$p} .wexoe-pn-featured {
                    display: flex !important;
                    grid-template-columns: unset !important;
                    gap: 12px !important;
                    overflow-x: auto !important;
                    scroll-snap-type: x mandatory !important;
                    -webkit-overflow-scrolling: touch !important;
                    scrollbar-width: none !important;
                    margin-left: -5% !important;
                    margin-right: -5% !important;
                    padding: 0 5% !important;
                    align-items: stretch !important;
                }

                {$p} .wexoe-pn-featured::-webkit-scrollbar {
                    display: none !important;
                }

                {$p} .wexoe-pn-featured-card {
                    flex: 0 0 85% !important;
                    scroll-snap-align: center !important;
                    min-width: 85% !important;
                    display: flex !important;
                    flex-direction: column !important;
                }

                {$p} .wexoe-pn-featured--single {
                    max-width: none !important;
                }

                {$p} .wexoe-pn-featured--single .wexoe-pn-featured-card {
                    flex: 0 0 100% !important;
                    min-width: 100% !important;
                }

                {$p} .wexoe-pn-featured-content {
                    display: flex !important;
                    flex-direction: column !important;
                    flex-grow: 1 !important;
                    padding: 20px !important;
                    min-height: 180px !important;
                    position: relative !important;
                }

                {$p} .wexoe-pn-featured-btn {
                    margin-top: auto !important;
                }

                {$p} .wexoe-pn-featured-label {
                    top: 12px !important;
                    right: 12px !important;
                }

                {$p} .wexoe-pn-title {
                    font-size: 1.25rem !important;
                }

                {$p} .wexoe-pn-featured-desc {
                    font-size: 0.85rem !important;
                    line-height: 1.5 !important;
                    -webkit-line-clamp: 3 !important;
                    line-clamp: 3 !important;
                    max-height: 4.5em !important;
                }

                {$p} .wexoe-pn-featured-card--campaign .wexoe-pn-featured-desc {
                    -webkit-line-clamp: 2 !important;
                    line-clamp: 2 !important;
                    max-height: 3em !important;
                }

                {$p} .wexoe-pn-benefits {
                    flex-direction: column !important;
                    gap: 8px !important;
                }
            }

            /* Small mobile */
            @media (max-width: 480px) {
                {$p} .wexoe-pn-card {
                    padding: 12px 10px !important;
                    gap: 8px !important;
                }

                {$p} .wexoe-pn-card-icon {
                    width: 24px !important;
                    height: 24px !important;
                }

                {$p} .wexoe-pn-card-title {
                    font-size: 0.75rem !important;
                }

                {$p} .wexoe-pn-featured-content {
                    min-height: 160px !important;
                }
            }
        ";
    }

    /**
     * Render the shortcode output
     * 
     * Main entry point for the shortcode. Fetches data from Airtable
     * and renders the complete navigation component.
     * 
     * @since 1.0.0
     * @since 2.0.0 Added 'div' parameter for division filtering
     * 
     * @param array $atts {
     *     Shortcode attributes
     *     
     *     @type string $title          Section title (optional)
     *     @type string $subtitle       Section subtitle (optional)
     *     @type string $div            Division name to filter by (optional)
     *     @type string $show_event     Show event card, "true"/"false" (default: "true")
     *     @type string $show_campaign  Show campaign card, "true"/"false" (default: "true")
     *     @type string $padding_top    Top padding in px (default: "30")
     *     @type string $padding_bottom Bottom padding in px (default: "30")
     * }
     * @return string HTML output
     */
    public function render_shortcode($atts) {
        // Parse shortcode attributes with defaults
        $atts = shortcode_atts(array(
            'title' => '',
            'subtitle' => '',
            'div' => '',
            'padding_top' => '30',
            'padding_bottom' => '30',
        ), $atts);

        // Generate unique ID for CSS scoping
        $instance_id = 'wexoe-pn-' . uniqid();
        
        // Parse parameters
        $division = !empty($atts['div']) ? $atts['div'] : null;
        $has_header = !empty($atts['title']) || !empty($atts['subtitle']);

        // Fetch data from Airtable
        $items = $this->get_airtable_items($division);
        
        $nav_items = $items['nav'];
        $featured_1 = $items['featured_1'];
        $featured_2 = $items['featured_2'];

        // If no content at all, return empty
        if (empty($nav_items) && !$featured_1 && !$featured_2) {
            return '';
        }

        // Count featured cards for layout class
        $featured_count = ($featured_1 ? 1 : 0) + ($featured_2 ? 1 : 0);

        // Start output buffering
        ob_start();
        ?>
        <style><?php echo $this->get_styles($instance_id, $atts['padding_top'], $atts['padding_bottom']); ?></style>
        
        <section id="<?php echo esc_attr($instance_id); ?>" class="wexoe-product-nav">
            <div class="wexoe-pn-inner">
                
                <?php if ($has_header) : ?>
                <!-- Section Header -->
                <header class="wexoe-pn-header">
                    <?php if (!empty($atts['title'])) : ?>
                    <h2 class="wexoe-pn-title"><?php echo esc_html($atts['title']); ?></h2>
                    <?php endif; ?>
                    <?php if (!empty($atts['subtitle'])) : ?>
                    <p class="wexoe-pn-subtitle"><?php echo esc_html($atts['subtitle']); ?></p>
                    <?php endif; ?>
                </header>
                <?php endif; ?>

                <?php if (!empty($nav_items)) : ?>
                <!-- Navigation Cards Grid -->
                <div class="wexoe-pn-grid">
                    <?php foreach ($nav_items as $item) : ?>
                    <a href="<?php echo esc_url($item['url']); ?>" class="wexoe-pn-card">
                        <div class="wexoe-pn-card-icon">
                            <?php 
                            // Output SVG icon from Airtable
                            // Note: SVG is stored as raw code in Airtable text field
                            echo $item['icon']; 
                            ?>
                        </div>
                        <span class="wexoe-pn-card-title"><?php echo esc_html($item['name']); ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ($featured_1 || $featured_2) : ?>
                <!-- Featured Cards (Event, Campaign, or Case) -->
                <div class="wexoe-pn-featured<?php echo $featured_count === 1 ? ' wexoe-pn-featured--single' : ''; ?>">
                    
                    <?php 
                    // Render both featured slots
                    foreach (array($featured_1, $featured_2) as $featured) :
                        if (!$featured) continue;
                        
                        // Determine badge label based on type
                        $badge_labels = array(
                            'Event' => 'Event',
                            'Campaign' => 'Kampanj',
                            'Case' => 'Case'
                        );
                        $badge = $badge_labels[$featured['type']] ?? $featured['type'];
                        
                        // Use campaign styling for Campaign and Case (with benefits)
                        $card_class = in_array($featured['type'], array('Campaign', 'Case')) ? 'wexoe-pn-featured-card--campaign' : 'wexoe-pn-featured-card--event';
                    ?>
                    <a href="<?php echo esc_url($featured['url']); ?>" class="wexoe-pn-featured-card <?php echo $card_class; ?>">
                        <div class="wexoe-pn-featured-content">
                            <span class="wexoe-pn-featured-label"><?php echo esc_html($badge); ?></span>
                            <h3 class="wexoe-pn-featured-title"><?php echo esc_html($featured['name']); ?></h3>
                            <p class="wexoe-pn-featured-desc"><?php echo esc_html($featured['description']); ?></p>
                            <?php 
                            // Display benefits for Campaign and Case
                            if (in_array($featured['type'], array('Campaign', 'Case'))) :
                                $benefits = array_filter(array(
                                    $featured['benefit_1'],
                                    $featured['benefit_2']
                                ));
                                if (!empty($benefits)) : ?>
                            <ul class="wexoe-pn-benefits">
                                <?php foreach ($benefits as $benefit) : ?>
                                <li><?php echo esc_html($benefit); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; 
                            endif; ?>
                            <span class="wexoe-pn-featured-btn">
                                <span><?php echo esc_html($featured['button_text']); ?></span>
                                <span class="wexoe-pn-featured-btn-arrow">→</span>
                            </span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                    
                </div>
                <?php endif; ?>
                
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
}

// Initialize plugin
new Wexoe_Product_Nav();
