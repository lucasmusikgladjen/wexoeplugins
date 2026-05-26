<?php
/**
 * Plugin Name: Wexoe Case
 * Description: Renderar kundcase i editorial artikel-format med sticky "Caset i korthet"-sidebar. Data via Wexoe Core (cms_cases). Använd [wexoe_case slug="..."].
 * Version: 1.0.0
 * Author: Wexoe
 * Requires PHP: 7.4
 * Requires at least: 6.0
 */

if (!defined('ABSPATH')) exit;

/* ============================================================
   CORE DEPENDENCY CHECK
   ============================================================ */

if (!function_exists('wexoe_case_core_ready')) {
    function wexoe_case_core_ready() {
        return class_exists('\\Wexoe\\Core\\Core')
            && method_exists('\\Wexoe\\Core\\Core', 'entity');
    }
}

/* ============================================================
   PLUGIN CLASS
   ============================================================ */

class Wexoe_Case {

    /** Request-scope cache för partner-name lookups (core_partners). */
    private $partner_cache = [];

    public function __construct() {
        add_shortcode('wexoe_case', [$this, 'render_shortcode']);
    }

    /* ============================================================
       SHORTCODE ENTRY POINT
       ============================================================ */

    public function render_shortcode($atts) {
        $atts = shortcode_atts([
            'slug'  => '',
            'debug' => 'false',
        ], $atts, 'wexoe_case');

        $slug  = sanitize_text_field((string) $atts['slug']);
        $debug = ($atts['debug'] === 'true');

        if ($slug === '') {
            return '<p style="color:red;">Wexoe Case: slug-parameter krävs.</p>';
        }

        if (!wexoe_case_core_ready()) {
            return '<p style="color:red;">Wexoe Case: Wexoe Core-pluginet är inte aktivt.</p>';
        }

        $repo = \Wexoe\Core\Core::entity('cms_cases');
        if (!$repo) {
            return '<p style="color:red;">Wexoe Case: entity-schema "cms_cases" saknas i Wexoe Core.</p>';
        }

        $data = $repo->find($slug);

        if ($debug) {
            return '<pre style="background:#f5f5f5;padding:20px;overflow:auto;max-height:600px;font-size:12px;">'
                . esc_html(print_r($data, true)) . '</pre>';
        }

        if (!$data) {
            return '<p style="color:red;">Wexoe Case: hittade inget case med slug "' . esc_html($slug) . '".</p>';
        }

        if (empty($data['is_active'])) {
            return '<p style="color:red;">Wexoe Case: caset "' . esc_html($slug) . '" är inte aktivt.</p>';
        }

        $wrapper_id = 'wexoe-case-' . wp_generate_password(8, false, false);

        ob_start();
        ?>
        <div id="<?php echo esc_attr($wrapper_id); ?>" class="wexoe-case-wrap">
            <?php echo $this->render_css($wrapper_id); ?>
            <div class="case-wrap">
                <?php echo $this->render_header($data); ?>
                <div class="case-container">
                    <div class="case-grid">
                        <main class="case-article">
                            <?php
                            echo $this->render_lead($data);
                            echo $this->render_stats_strip($data);
                            echo $this->render_challenge($data);
                            echo $this->render_pullquote($data);
                            echo $this->render_solution_text($data);
                            echo $this->render_products($data);
                            echo $this->render_solution_image($data);
                            echo $this->render_results($data);
                            echo $this->render_testimonial($data);
                            echo $this->render_gallery($data);
                            echo $this->render_about_customer($data);
                            ?>
                        </main>
                        <?php echo $this->render_glance_sidebar($data); ?>
                    </div>
                </div>
                <?php echo $this->render_contact_form($data); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /* ============================================================
       HELPERS
       ============================================================ */

    /** Safe field accessor: returnerar string default ifall fältet saknas/null/tomt. */
    private function field($data, $key, $default = '') {
        if (!isset($data[$key])) return $default;
        $v = $data[$key];
        if ($v === null || $v === '') return $default;
        return $v;
    }

    /** Markdown → HTML med <p>-omslag (bevarar paragrafer). */
    private function md_html($text) {
        $text = (string) $text;
        if ($text === '') return '';
        return \Wexoe\Core\Helpers\Markdown::to_html($text);
    }

    /** Markdown → inline HTML (utan <p>-omslag på single-paragraph input). */
    private function md_inline($text) {
        $text = (string) $text;
        if ($text === '') return '';
        return \Wexoe\Core\Helpers\Markdown::to_inline($text);
    }

    /** Lines → array (handles already-array input from Core's lines-type). */
    private function as_lines($value) {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $l) {
                $l = trim((string) $l);
                if ($l !== '') $out[] = $l;
            }
            return $out;
        }
        return \Wexoe\Core\Helpers\Lines::to_array((string) $value);
    }

    /**
     * Slå upp första leverantörens namn för en länkad produkt/artikel.
     * Cacha per record-ID inom request — same partner i flera produkter ska
     * inte trigga separata array-resoluções.
     */
    private function lookup_partner_name($partner_id) {
        if (!is_string($partner_id) || $partner_id === '') return '';
        if (array_key_exists($partner_id, $this->partner_cache)) {
            return $this->partner_cache[$partner_id];
        }
        $repo = \Wexoe\Core\Core::entity('core_partners');
        if (!$repo) {
            $this->partner_cache[$partner_id] = '';
            return '';
        }
        $records = $repo->find_by_ids([$partner_id]);
        $name = (isset($records[0]['name']) && is_string($records[0]['name'])) ? $records[0]['name'] : '';
        $this->partner_cache[$partner_id] = $name;
        return $name;
    }

    /** Trunkera UTF-8-text till N tecken med "…" om längre. */
    private function truncate($text, $max = 120) {
        $text = trim((string) $text);
        if ($text === '') return '';
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($text, 'UTF-8') <= $max) return $text;
            return rtrim(mb_substr($text, 0, $max, 'UTF-8')) . '…';
        }
        if (strlen($text) <= $max) return $text;
        return rtrim(substr($text, 0, $max)) . '…';
    }

    /* ============================================================
       SECTION: HEADER
       ============================================================ */

    private function render_header($data) {
        $industry      = $this->field($data, 'industry');
        $title         = $this->field($data, 'title');
        $subtitle      = $this->field($data, 'subtitle');
        $customer_name = $this->field($data, 'customer_name');
        $location      = $this->field($data, 'location');
        $project_year  = $this->field($data, 'project_year');
        $project_type  = $this->field($data, 'project_type');
        $reading_time  = $this->field($data, 'reading_time');
        $header_logos  = $this->as_lines($this->field($data, 'header_logos', []));

        ob_start();
        ?>
        <header class="case-header case-fullwidth">
            <div class="case-container case-header-inner">
                <div class="case-eyebrow">
                    <span>Kundcase</span>
                    <?php if ($industry !== ''): ?>
                        <span class="case-eyebrow-divider"></span>
                        <span class="case-eyebrow-tag"><?php echo esc_html($industry); ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($title !== ''): ?>
                    <h1 class="case-h1"><?php echo esc_html($title); ?></h1>
                <?php endif; ?>

                <?php if ($subtitle !== ''): ?>
                    <p class="case-subtitle"><?php echo esc_html($subtitle); ?></p>
                <?php endif; ?>

                <div class="case-byline-strip">
                    <?php
                    $byline_items = [
                        ['label' => 'Kund',       'value' => $customer_name],
                        ['label' => 'Plats',      'value' => $location],
                        ['label' => 'År',         'value' => $project_year],
                        ['label' => 'Projekttyp', 'value' => $project_type],
                        ['label' => 'Lästid',     'value' => $reading_time],
                    ];
                    foreach ($byline_items as $item):
                        if ($item['value'] === '') continue;
                        ?>
                        <div class="case-byline-item">
                            <span class="case-byline-label"><?php echo esc_html($item['label']); ?></span>
                            <span class="case-byline-value"><?php echo esc_html($item['value']); ?></span>
                        </div>
                        <?php
                    endforeach;
                    ?>

                    <?php if (!empty($header_logos)): ?>
                        <div class="case-byline-logos">
                            <?php foreach ($header_logos as $logo_url): ?>
                                <div class="case-byline-logo-wrap">
                                    <img class="case-byline-logo"
                                         src="<?php echo esc_url($logo_url); ?>"
                                         alt="">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </header>
        <?php
        return ob_get_clean();
    }

    /* ============================================================
       SECTION: LEAD (image + paragraph)
       ============================================================ */

    private function render_lead($data) {
        $image_url     = $this->field($data, 'lead_image_url');
        $image_caption = $this->field($data, 'lead_image_caption');
        $paragraph     = $this->field($data, 'lead_paragraph');

        if ($image_url === '' && $paragraph === '') return '';

        ob_start();
        if ($image_url !== ''):
            ?>
            <figure class="case-lead-image">
                <img src="<?php echo esc_url($image_url); ?>" alt="">
                <?php if ($image_caption !== ''): ?>
                    <figcaption><?php echo esc_html($image_caption); ?></figcaption>
                <?php endif; ?>
            </figure>
            <?php
        endif;

        if ($paragraph !== ''):
            // Use a <div> wrapper so Markdown::to_html's <p> tags nest cleanly.
            // CSS ::first-letter on the div applies to the first character inside
            // the first <p>, giving the drop-cap effect.
            ?>
            <div class="case-lead-paragraph"><?php echo $this->md_html($paragraph); ?></div>
            <?php
        endif;

        return ob_get_clean();
    }

    /* ============================================================
       SECTION: STATS STRIP
       ============================================================ */

    private function render_stats_strip($data) {
        $show = !empty($data['show_stats_strip']);
        if (!$show) return '';

        $stats = isset($data['quick_stats']) && is_array($data['quick_stats']) ? $data['quick_stats'] : [];
        if (count($stats) === 0) return '';

        ob_start();
        ?>
        <div class="case-stats-inline">
            <?php foreach ($stats as $stat): ?>
                <div class="case-stat">
                    <div class="case-stat-value"><?php echo esc_html($this->field($stat, 'value')); ?></div>
                    <div class="case-stat-label"><?php echo esc_html($this->field($stat, 'label')); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /* ============================================================
       SECTION: CHALLENGE
       ============================================================ */

    private function render_challenge($data) {
        $eyebrow   = $this->field($data, 'challenge_eyebrow', 'Utmaningen');
        $title     = $this->field($data, 'challenge_title');
        $text      = $this->field($data, 'challenge_text');
        $bullets   = $this->as_lines($this->field($data, 'challenge_bullets', []));
        $image_url = $this->field($data, 'challenge_image_url');
        $caption   = $this->field($data, 'challenge_image_caption');

        if ($title === '' && $text === '' && empty($bullets) && $image_url === '') return '';

        ob_start();
        ?>
        <section class="case-article-section">
            <div class="case-section-eyebrow"><?php echo esc_html($eyebrow); ?></div>
            <?php if ($title !== ''): ?>
                <h2 class="case-section-h2"><?php echo esc_html($title); ?></h2>
            <?php endif; ?>
            <?php if ($text !== ''): ?>
                <div class="case-section-body"><?php echo $this->md_html($text); ?></div>
            <?php endif; ?>
            <?php if (!empty($bullets)): ?>
                <ul class="case-bullet-list">
                    <?php foreach ($bullets as $bullet): ?>
                        <li><?php echo $this->md_inline($bullet); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if ($image_url !== ''): ?>
                <figure class="case-inline-image">
                    <img src="<?php echo esc_url($image_url); ?>" alt="">
                    <?php if ($caption !== ''): ?>
                        <figcaption><?php echo esc_html($caption); ?></figcaption>
                    <?php endif; ?>
                </figure>
            <?php endif; ?>
        </section>
        <?php
        return ob_get_clean();
    }

    /* ============================================================
       SECTION: PULLQUOTE
       ============================================================ */

    private function render_pullquote($data) {
        if (empty($data['show_pullquote'])) return '';

        $text        = $this->field($data, 'pullquote_text');
        $attribution = $this->field($data, 'pullquote_attribution');
        if ($text === '') return '';

        ob_start();
        ?>
        <aside class="case-pullquote-inline">
            <blockquote><?php echo $this->md_inline($text); ?></blockquote>
            <?php if ($attribution !== ''): ?>
                <div class="case-pullquote-attr"><?php echo esc_html($attribution); ?></div>
            <?php endif; ?>
        </aside>
        <?php
        return ob_get_clean();
    }

    /* ============================================================
       SECTION: SOLUTION (text) — products och image renderas separat
       så att produktlistan kommer mellan text och bild i artikelflödet.
       ============================================================ */

    private function render_solution_text($data) {
        $eyebrow = $this->field($data, 'solution_eyebrow', 'Lösningen');
        $title   = $this->field($data, 'solution_title');
        $text    = $this->field($data, 'solution_text');

        if ($title === '' && $text === '') return '';

        ob_start();
        ?>
        <section class="case-article-section">
            <div class="case-section-eyebrow"><?php echo esc_html($eyebrow); ?></div>
            <?php if ($title !== ''): ?>
                <h2 class="case-section-h2"><?php echo esc_html($title); ?></h2>
            <?php endif; ?>
            <?php if ($text !== ''): ?>
                <div class="case-section-body"><?php echo $this->md_html($text); ?></div>
            <?php endif; ?>
        </section>
        <?php
        return ob_get_clean();
    }

    private function render_solution_image($data) {
        $image_url = $this->field($data, 'solution_image_url');
        if ($image_url === '') return '';
        $caption = $this->field($data, 'solution_image_caption');

        ob_start();
        ?>
        <figure class="case-inline-image">
            <img src="<?php echo esc_url($image_url); ?>" alt="">
            <?php if ($caption !== ''): ?>
                <figcaption><?php echo esc_html($caption); ?></figcaption>
            <?php endif; ?>
        </figure>
        <?php
        return ob_get_clean();
    }

    /* ============================================================
       SECTION: PRODUCTS — polymorf grid över cms_products + cms_articles
       ============================================================ */

    private function render_products($data) {
        $product_ids = (isset($data['product_ids']) && is_array($data['product_ids'])) ? $data['product_ids'] : [];
        $article_ids = (isset($data['article_ids']) && is_array($data['article_ids'])) ? $data['article_ids'] : [];

        if (empty($product_ids) && empty($article_ids)) return '';

        // Resolve linked records via Core.
        $products = [];
        if (!empty($product_ids)) {
            $repo = \Wexoe\Core\Core::entity('products');
            if ($repo) {
                $products = $repo->find_by_ids($product_ids);
            }
        }

        $articles = [];
        if (!empty($article_ids)) {
            $repo = \Wexoe\Core\Core::entity('articles');
            if ($repo) {
                $articles = $repo->find_by_ids($article_ids);
            }
        }

        // Filter out inactive records. is_active is bool in Normalizer-output,
        // so === false filters those out without dropping records where the
        // field is missing (legacy or sub-tables without that toggle).
        $filter_active = function ($rec) {
            return !isset($rec['is_active']) || $rec['is_active'] !== false;
        };
        $products = array_values(array_filter($products, $filter_active));
        $articles = array_values(array_filter($articles, $filter_active));

        $total = count($products) + count($articles);
        if ($total === 0) return '';

        $title = $this->field($data, 'products_title', 'Produkter i lösningen');
        $meta  = $this->field($data, 'products_meta');

        $grid_class = 'case-products-grid';
        if ($total === 1) $grid_class .= ' case-products-grid--single';

        ob_start();
        ?>
        <section class="case-products-box">
            <header class="case-products-box-header">
                <h3 class="case-products-box-title"><?php echo esc_html($title); ?></h3>
                <?php if ($meta !== ''): ?>
                    <span class="case-products-box-meta"><?php echo esc_html($meta); ?></span>
                <?php endif; ?>
            </header>
            <div class="<?php echo esc_attr($grid_class); ?>">
                <?php
                foreach ($products as $rec) {
                    echo $this->render_product_card($rec, 'product');
                }
                foreach ($articles as $rec) {
                    echo $this->render_product_card($rec, 'article');
                }
                ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * Polymorf product card för både cms_products och cms_articles.
     *
     * @param array  $rec   Normaliserad linked-record (från Core)
     * @param string $kind  'product' eller 'article' — styr vilka fält som mappas
     */
    private function render_product_card($rec, $kind) {
        $image_url = (string) ($rec['image_url'] ?? '');
        $name      = (string) ($rec['name'] ?? '');

        // Brand: första supplier-namnet via core_partners.
        $supplier_ids = (isset($rec['supplier_ids']) && is_array($rec['supplier_ids'])) ? $rec['supplier_ids'] : [];
        $brand = '';
        if (!empty($supplier_ids)) {
            $brand = $this->lookup_partner_name($supplier_ids[0]);
        }

        // Role + secondary label (article_number) + link, beroende på kind.
        $article_number = '';
        $role = '';
        $link_url = '#';
        if ($kind === 'article') {
            $article_number = (string) ($rec['article_number'] ?? '');
            $role = (string) ($rec['description'] ?? '');
            $candidate = (string) ($rec['webshop_url'] ?? '');
            if ($candidate !== '') $link_url = $candidate;
        } else {
            $role = (string) ($rec['description'] ?? '');
            $candidate = (string) ($rec['button_1_url'] ?? '');
            if ($candidate !== '') $link_url = $candidate;
        }
        $role = $this->truncate($role, 120);

        ob_start();
        ?>
        <a href="<?php echo esc_url($link_url); ?>" class="case-product-card">
            <?php if ($image_url !== ''): ?>
                <img class="case-product-image" src="<?php echo esc_url($image_url); ?>" alt="">
            <?php else: ?>
                <span class="case-product-image case-product-image--placeholder" aria-hidden="true"></span>
            <?php endif; ?>
            <div class="case-product-body">
                <?php if ($brand !== ''): ?>
                    <div class="case-product-brand"><?php echo esc_html($brand); ?></div>
                <?php endif; ?>
                <?php if ($name !== ''): ?>
                    <h4 class="case-product-name"><?php echo esc_html($name); ?></h4>
                <?php endif; ?>
                <?php if ($article_number !== ''): ?>
                    <div class="case-product-article-number">Art.nr <?php echo esc_html($article_number); ?></div>
                <?php endif; ?>
                <?php if ($role !== ''): ?>
                    <p class="case-product-role"><?php echo esc_html($role); ?></p>
                <?php endif; ?>
            </div>
            <span class="case-product-arrow" aria-hidden="true">→</span>
        </a>
        <?php
        return ob_get_clean();
    }

    /* ============================================================
       SECTION: RESULTS
       ============================================================ */

    private function render_results($data) {
        $eyebrow = $this->field($data, 'results_eyebrow', 'Resultatet');
        $title   = $this->field($data, 'results_title');
        $text    = $this->field($data, 'results_text');
        $results = isset($data['results']) && is_array($data['results']) ? $data['results'] : [];

        if ($title === '' && $text === '' && count($results) === 0) return '';

        ob_start();
        ?>
        <section class="case-article-section">
            <div class="case-section-eyebrow"><?php echo esc_html($eyebrow); ?></div>
            <?php if ($title !== ''): ?>
                <h2 class="case-section-h2"><?php echo esc_html($title); ?></h2>
            <?php endif; ?>
            <?php if ($text !== ''): ?>
                <div class="case-section-body"><?php echo $this->md_html($text); ?></div>
            <?php endif; ?>
            <?php if (count($results) > 0): ?>
                <div class="case-stats-inline case-stats-large">
                    <?php foreach ($results as $r): ?>
                        <div class="case-stat">
                            <div class="case-stat-value"><?php echo esc_html($this->field($r, 'value')); ?></div>
                            <div class="case-stat-label"><?php echo esc_html($this->field($r, 'label')); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php
        return ob_get_clean();
    }

    /* ============================================================
       SECTION: TESTIMONIAL
       ============================================================ */

    private function render_testimonial($data) {
        if (empty($data['show_testimonial'])) return '';

        $quote        = $this->field($data, 'testimonial_quote');
        $photo_url    = $this->field($data, 'testimonial_photo_url');
        $author_name  = $this->field($data, 'testimonial_author_name');
        $author_title = $this->field($data, 'testimonial_author_title');

        if ($quote === '' && $author_name === '') return '';

        ob_start();
        ?>
        <aside class="case-testimonial-inline">
            <?php if ($quote !== ''): ?>
                <blockquote><?php echo $this->md_inline($quote); ?></blockquote>
            <?php endif; ?>
            <?php if ($author_name !== '' || $photo_url !== '' || $author_title !== ''): ?>
                <div class="case-testimonial-author">
                    <?php if ($photo_url !== ''): ?>
                        <img src="<?php echo esc_url($photo_url); ?>" alt="">
                    <?php endif; ?>
                    <div>
                        <?php if ($author_name !== ''): ?>
                            <div class="case-testimonial-author-name"><?php echo esc_html($author_name); ?></div>
                        <?php endif; ?>
                        <?php if ($author_title !== ''): ?>
                            <div class="case-testimonial-author-title"><?php echo esc_html($author_title); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </aside>
        <?php
        return ob_get_clean();
    }

    /* ============================================================
       SECTION: GALLERY
       ============================================================ */

    private function render_gallery($data) {
        if (empty($data['show_gallery'])) return '';

        $images = isset($data['gallery_images']) && is_array($data['gallery_images']) ? $data['gallery_images'] : [];
        // Filtrera bort poster utan URL — pseudo_array kan ha tomma sektioner kvar.
        $images = array_values(array_filter($images, function ($img) {
            return is_array($img) && !empty($img['url']);
        }));
        if (count($images) === 0) return '';

        $title = $this->field($data, 'gallery_title');

        ob_start();
        ?>
        <section class="case-article-section">
            <?php if ($title !== ''): ?>
                <h3 class="case-section-h3"><?php echo esc_html($title); ?></h3>
            <?php endif; ?>
            <div class="case-gallery-grid">
                <?php foreach ($images as $img):
                    $url = (string) $img['url'];
                    $caption = isset($img['caption']) ? (string) $img['caption'] : '';
                    ?>
                    <figure class="case-gallery-item">
                        <img src="<?php echo esc_url($url); ?>" alt="">
                        <?php if ($caption !== ''): ?>
                            <figcaption><?php echo esc_html($caption); ?></figcaption>
                        <?php endif; ?>
                    </figure>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /* ============================================================
       SECTION: ABOUT CUSTOMER
       ============================================================ */

    private function render_about_customer($data) {
        if (empty($data['show_about_customer'])) return '';

        $logo_url   = $this->field($data, 'about_customer_logo_url');
        $title      = $this->field($data, 'about_customer_title');
        $text       = $this->field($data, 'about_customer_text');
        $link_label = $this->field($data, 'about_customer_link_label');
        $link_url   = $this->field($data, 'about_customer_url');

        if ($title === '' && $text === '' && $logo_url === '') return '';

        $section_class = 'case-about-customer-inline';
        if ($logo_url === '') $section_class .= ' case-about-customer-inline--no-logo';

        ob_start();
        ?>
        <section class="<?php echo esc_attr($section_class); ?>">
            <?php if ($logo_url !== ''): ?>
                <img src="<?php echo esc_url($logo_url); ?>" alt="">
            <?php endif; ?>
            <div>
                <?php if ($title !== ''): ?>
                    <h3><?php echo esc_html($title); ?></h3>
                <?php endif; ?>
                <?php if ($text !== ''): ?>
                    <?php echo $this->md_html($text); ?>
                <?php endif; ?>
                <?php if ($link_url !== ''): ?>
                    <a class="case-about-customer-link" href="<?php echo esc_url($link_url); ?>">
                        <?php echo esc_html($link_label !== '' ? $link_label : 'Läs mer'); ?> <span aria-hidden="true">→</span>
                    </a>
                <?php endif; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /* ============================================================
       SECTION: GLANCE SIDEBAR ("Caset i korthet")
       Renderas alltid, men individuella block hoppas över om tomma.
       ============================================================ */

    private function render_glance_sidebar($data) {
        $blocks = [
            ['label' => 'Utmaning', 'value' => $this->field($data, 'glance_challenge')],
            ['label' => 'Lösning',  'value' => $this->field($data, 'glance_solution')],
            ['label' => 'Resultat', 'value' => $this->field($data, 'glance_result')],
        ];

        ob_start();
        ?>
        <aside class="case-glance">
            <h3 class="case-glance-title">Caset i korthet</h3>
            <?php foreach ($blocks as $block):
                if ($block['value'] === '') continue;
                ?>
                <div class="case-glance-block">
                    <div class="case-glance-block-label"><?php echo esc_html($block['label']); ?></div>
                    <div class="case-glance-block-text"><?php echo $this->md_inline($block['value']); ?></div>
                </div>
            <?php endforeach; ?>
        </aside>
        <?php
        return ob_get_clean();
    }

    /* ============================================================
       SECTION: CONTACT FORM (delad Core-renderer)
       ============================================================ */

    private function render_contact_form($data) {
        if (empty($data['show_contact_form'])) return '';

        $class = \Wexoe\Core\Core::renderer('contact-form');
        if ($class === '') return '';

        $html = $class::render([
            'eyebrow'        => $data['contact_form_eyebrow'] ?? '',
            'title'          => $data['contact_form_title'] ?? '',
            'subtitle'       => $data['contact_form_subtitle'] ?? '',
            'layout'         => $data['contact_form_layout'] ?? 'split',
            'theme'          => $data['contact_form_theme'] ?? 'dark',
            'show_company'   => $data['contact_form_show_company'] ?? true,
            'show_phone'     => $data['contact_form_show_phone'] ?? true,
            'show_dropdown'  => $data['contact_form_show_dropdown'] ?? true,
            'dropdown_label' => $data['contact_form_dropdown_label'] ?? '',
            'options'        => $data['contact_form_options'] ?? null,
            'cta_text'       => $data['contact_form_cta_text'] ?? '',
            'message_label'  => $data['contact_form_message_label'] ?? '',
            'trust_signals'  => $data['contact_form_trust_signals'] ?? null,
            'source_plugin'  => 'wexoe-case',
            'page_slug'      => $data['slug'] ?? '',
            'contact_person' => null,
        ]);

        return '<section id="kontakt" class="case-fullwidth">' . $html . '</section>';
    }

    /* ============================================================
       CSS — scoped till wrapper-ID. Kopierad baseline från prototypen,
       med varje selector prefixad med #wrapper_id för isolering.
       Ingen !important. Ingen global CSS. Två instanser av shortcoden
       på samma sida ska inte krocka eftersom wrapper-ID:t är unikt.
       ============================================================ */

    private function render_css($id) {
        $w = '#' . $id;
        ob_start();
        ?>
<style>
@import url("https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400;1,9..40,500&display=swap");

<?php echo $w; ?> {
    --case-main: #11325D;
    --case-secondary: #2B4D7A;
    --case-green: #10B981;
    --case-text: #1a1a1a;
    --case-muted: #6B7280;
    --case-rule: #E5E7EB;
    --case-bg-soft: #F8F9FB;
    --case-bg-mid: #F0F2F5;
    font-family: "DM Sans", system-ui, sans-serif;
    font-size: 17px;
    line-height: 1.7;
    color: var(--case-text);
    background: #fff;
}
<?php echo $w; ?> *, <?php echo $w; ?> *::before, <?php echo $w; ?> *::after { box-sizing: border-box; }
<?php echo $w; ?> img { max-width: 100%; height: auto; display: block; }
<?php echo $w; ?> a { color: var(--case-main); }

<?php echo $w; ?> .case-wrap { overflow-x: hidden; }
<?php echo $w; ?> .case-container { max-width: 1100px; margin: 0 auto; padding: 0 32px; }
<?php echo $w; ?> .case-fullwidth { width: 100vw; max-width: none; margin-left: calc(-50vw + 50%); margin-right: calc(-50vw + 50%); }
<?php echo $w; ?> .case-wrap > .case-header.case-fullwidth {
    width: 100vw;
    max-width: none;
    margin-left: calc(50% - 50vw);
    margin-right: calc(50% - 50vw);
}

/* ============================================================
   ARTICLE HEADER
   ============================================================ */
<?php echo $w; ?> .case-header {
    background: var(--case-main);
    color: #fff;
    padding: 64px 0 56px;
    position: relative;
    overflow: hidden;
}
<?php echo $w; ?> .case-header::before {
    content: "";
    position: absolute;
    width: 320px; height: 320px;
    background: rgba(255,255,255,0.025);
    border-radius: 50%;
    top: -80px; right: -80px;
}
<?php echo $w; ?> .case-header::after {
    content: "";
    position: absolute;
    width: 200px; height: 200px;
    border: 2px solid rgba(255,255,255,0.04);
    transform: rotate(45deg);
    bottom: -60px; left: 8%;
}
<?php echo $w; ?> .case-header-inner { position: relative; z-index: 2; }

<?php echo $w; ?> .case-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1.6px;
    text-transform: uppercase;
    color: var(--case-secondary);
    margin-bottom: 28px;
}
<?php echo $w; ?> .case-eyebrow .case-eyebrow-divider {
    width: 24px; height: 1px;
    background: rgba(255,255,255,0.3);
}
<?php echo $w; ?> .case-eyebrow-tag {
    color: rgba(255,255,255,0.7);
    font-weight: 500;
    letter-spacing: 1.2px;
}

<?php echo $w; ?> .case-h1 {
    font-size: clamp(2rem, 4.2vw, 3rem);
    font-weight: 700;
    line-height: 1.15;
    letter-spacing: -0.02em;
    margin: 0 0 20px;
    max-width: 900px;
    color: #fff;
}
<?php echo $w; ?> .case-subtitle {
    font-size: clamp(1.05rem, 1.5vw, 1.2rem);
    line-height: 1.55;
    font-weight: 400;
    color: rgba(255,255,255,0.85);
    max-width: 720px;
    margin: 0 0 40px;
}

<?php echo $w; ?> .case-byline-strip {
    display: flex;
    flex-wrap: wrap;
    gap: 32px;
    align-items: center;
    padding-top: 28px;
    border-top: 1px solid rgba(255,255,255,0.15);
    font-size: 14px;
}
<?php echo $w; ?> .case-byline-item { display: flex; flex-direction: column; gap: 4px; }
<?php echo $w; ?> .case-byline-label {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: rgba(255,255,255,0.55);
}
<?php echo $w; ?> .case-byline-value { color: #fff; font-weight: 500; }

<?php echo $w; ?> .case-byline-logos {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
<?php echo $w; ?> .case-byline-logo-wrap {
    background: rgba(255,255,255,0.95);
    padding: 8px 14px;
    border-radius: 4px;
    height: 44px;
    display: flex;
    align-items: center;
}
<?php echo $w; ?> .case-byline-logo {
    width: auto;
    height: 24px;
    max-width: 130px;
    object-fit: contain;
    display: block;
}

/* ============================================================
   ARTICLE GRID
   ============================================================ */
<?php echo $w; ?> .case-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 280px;
    gap: 56px;
    padding: 64px 0 80px;
    align-items: start;
}
<?php echo $w; ?> .case-article { min-width: 0; }
<?php echo $w; ?> .case-article > * + * { margin-top: 56px; }

<?php echo $w; ?> .case-glance {
    position: sticky;
    top: 32px;
    background: var(--case-bg-soft);
    border-top: 4px solid var(--case-secondary);
    padding: 28px 28px 32px;
}
<?php echo $w; ?> .case-glance-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--case-main);
    margin: 0 0 22px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--case-rule);
    letter-spacing: -0.01em;
    line-height: 1.25;
}
<?php echo $w; ?> .case-glance-block { margin-bottom: 22px; }
<?php echo $w; ?> .case-glance-block:last-child { margin-bottom: 0; }
<?php echo $w; ?> .case-glance-block-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1.3px;
    text-transform: uppercase;
    color: var(--case-secondary);
    margin-bottom: 8px;
}
<?php echo $w; ?> .case-glance-block-text {
    font-size: 14px;
    line-height: 1.6;
    color: var(--case-text);
}

/* ============================================================
   LEAD IMAGE + PARAGRAPH
   ============================================================ */
<?php echo $w; ?> .case-lead-image { margin: 0 0 36px; }
<?php echo $w; ?> .case-lead-image img {
    width: 100%;
    aspect-ratio: 16 / 10;
    object-fit: cover;
    border-radius: 4px;
}
<?php echo $w; ?> .case-lead-image figcaption {
    font-size: 13px;
    color: var(--case-muted);
    font-style: italic;
    margin-top: 10px;
    padding-left: 0;
    border-left: 0;
}

<?php echo $w; ?> .case-lead-paragraph {
    font-size: 1.18rem;
    line-height: 1.65;
    color: #2a2a2a;
    margin: 0;
}
<?php echo $w; ?> .case-lead-paragraph p { margin: 0 0 1.1em; color: inherit; }
<?php echo $w; ?> .case-lead-paragraph p:last-child { margin-bottom: 0; }
/* ============================================================
   SECTION HEADINGS
   ============================================================ */
<?php echo $w; ?> .case-section-eyebrow {
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1.6px;
    text-transform: uppercase;
    color: var(--case-secondary);
    margin: 0 0 14px;
    display: flex;
    align-items: center;
    gap: 12px;
}
<?php echo $w; ?> .case-section-eyebrow::before {
    content: "";
    display: inline-block;
    width: 32px; height: 2px;
    background: var(--case-secondary);
}
<?php echo $w; ?> .case-section-h2 {
    font-size: clamp(1.55rem, 2.4vw, 1.95rem);
    font-weight: 700;
    line-height: 1.2;
    color: var(--case-main);
    margin: 0 0 24px;
    letter-spacing: -0.015em;
}
<?php echo $w; ?> .case-section-h3 {
    font-size: 1.25rem;
    font-weight: 700;
    line-height: 1.3;
    color: var(--case-main);
    margin: 0 0 18px;
    letter-spacing: -0.01em;
}
<?php echo $w; ?> .case-section-body {
    font-size: 1rem;
    line-height: 1.75;
    color: #2a2a2a;
}
<?php echo $w; ?> .case-section-body p { margin: 0 0 1.1em; }
<?php echo $w; ?> .case-section-body p:last-child { margin-bottom: 0; }

<?php echo $w; ?> .case-bullet-list,
<?php echo $w; ?> .case-section-body ul.case-bullet-list,
<?php echo $w; ?> .case-section-body ol.case-bullet-list,
<?php echo $w; ?> .case-section-body + ul.case-bullet-list,
<?php echo $w; ?> .case-section-body + ol.case-bullet-list {
    list-style: none;
    padding: 0;
    margin: 24px 0 0;
}
<?php echo $w; ?> .case-bullet-list li,
<?php echo $w; ?> .case-section-body ul.case-bullet-list li,
<?php echo $w; ?> .case-section-body ol.case-bullet-list li,
<?php echo $w; ?> .case-section-body + ul.case-bullet-list li,
<?php echo $w; ?> .case-section-body + ol.case-bullet-list li {
    position: relative;
    padding-left: 30px;
    margin-bottom: 12px;
    line-height: 1.65;
    font-size: 1rem;
    list-style: none;
}
<?php echo $w; ?> .case-bullet-list li::before,
<?php echo $w; ?> .case-section-body ul.case-bullet-list li::before,
<?php echo $w; ?> .case-section-body ol.case-bullet-list li::before,
<?php echo $w; ?> .case-section-body + ul.case-bullet-list li::before,
<?php echo $w; ?> .case-section-body + ol.case-bullet-list li::before {
    content: "✓";
    position: absolute;
    left: 0;
    top: 2px;
    color: var(--case-green);
    font-weight: 700;
}
<?php echo $w; ?> .case-bullet-list li::after,
<?php echo $w; ?> .case-section-body ul.case-bullet-list li::after,
<?php echo $w; ?> .case-section-body ol.case-bullet-list li::after,
<?php echo $w; ?> .case-section-body + ul.case-bullet-list li::after,
<?php echo $w; ?> .case-section-body + ol.case-bullet-list li::after {
    content: none;
}

<?php echo $w; ?> .case-inline-image { margin: 32px 0 0; }
<?php echo $w; ?> .case-inline-image img {
    width: 100%;
    border-radius: 4px;
}
<?php echo $w; ?> .case-inline-image figcaption {
    font-size: 13px;
    color: var(--case-muted);
    font-style: italic;
    margin-top: 10px;
    padding-left: 0;
    border-left: 0;
}

/* ============================================================
   STATS / RESULTS
   ============================================================ */
<?php echo $w; ?> .case-stats-inline {
    display: flex;
    flex-wrap: wrap;
    padding: 28px 0;
    border-top: 1px solid var(--case-rule);
    border-bottom: 1px solid var(--case-rule);
}
<?php echo $w; ?> .case-stats-inline .case-stat {
    flex: 1 1 0;
    min-width: 140px;
    padding: 0 24px;
    border-left: 1px solid var(--case-rule);
}
<?php echo $w; ?> .case-stats-inline .case-stat:first-child {
    padding-left: 0;
    border-left: none;
}
<?php echo $w; ?> .case-stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--case-main);
    letter-spacing: -0.025em;
    line-height: 1;
    margin-bottom: 6px;
}
<?php echo $w; ?> .case-stat-label {
    font-size: 12.5px;
    color: var(--case-muted);
    font-weight: 500;
    line-height: 1.4;
}
<?php echo $w; ?> .case-stats-inline.case-stats-large .case-stat-value {
    font-size: clamp(2rem, 3vw, 2.4rem);
}
<?php echo $w; ?> .case-stats-inline.case-stats-large { margin-top: 32px; }

/* ============================================================
   PULL QUOTE
   ============================================================ */
<?php echo $w; ?> .case-pullquote-inline {
    padding: 4px 0 4px 28px;
    border-left: 4px solid var(--case-secondary);
    margin-top: 56px; margin-bottom: 56px;
}
<?php echo $w; ?> .case-pullquote-inline blockquote {
    font-style: italic;
    font-size: clamp(1.25rem, 2vw, 1.5rem);
    line-height: 1.4;
    font-weight: 500;
    color: var(--case-main);
    margin: 0 0 14px;
    letter-spacing: -0.005em;
    border: 0;
    padding: 0;
}
<?php echo $w; ?> .case-pullquote-inline blockquote p { margin: 0 0 10px; color: inherit; }
<?php echo $w; ?> .case-pullquote-inline blockquote p:last-child { margin-bottom: 0; }
<?php echo $w; ?> .case-pullquote-inline .case-pullquote-attr {
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 1.4px;
    text-transform: uppercase;
    color: var(--case-muted);
}

/* ============================================================
   TESTIMONIAL
   ============================================================ */
<?php echo $w; ?> .case-testimonial-inline {
    margin-top: 56px; margin-bottom: 56px;
    padding: 32px 32px 28px;
    background: var(--case-bg-soft);
    border-left: 4px solid var(--case-main);
}
<?php echo $w; ?> .case-testimonial-inline blockquote {
    font-style: italic;
    font-size: clamp(1.15rem, 1.7vw, 1.3rem);
    line-height: 1.55;
    font-weight: 500;
    color: var(--case-main);
    margin: 0 0 22px;
    position: relative;
    border: 0;
    padding: 0;
}
<?php echo $w; ?> .case-testimonial-inline blockquote::before {
    content: "\201C";
    position: absolute;
    left: -8px;
    top: -22px;
    font-size: 3rem;
    color: #B0BBD0;
    line-height: 1;
    font-style: normal;
    font-weight: 700;
}
<?php echo $w; ?> .case-testimonial-inline blockquote p { margin: 0 0 14px; color: inherit; }
<?php echo $w; ?> .case-testimonial-inline blockquote p:last-child { margin-bottom: 0; }
<?php echo $w; ?> .case-testimonial-author {
    display: flex;
    align-items: center;
    gap: 14px;
}
<?php echo $w; ?> .case-testimonial-author img {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
    background: var(--case-bg-mid);
    flex-shrink: 0;
}
<?php echo $w; ?> .case-testimonial-author-name {
    font-size: 14px;
    font-weight: 700;
    color: var(--case-main);
    margin-bottom: 1px;
}
<?php echo $w; ?> .case-testimonial-author-title {
    font-size: 13px;
    color: var(--case-muted);
}

/* ============================================================
   PRODUCTS
   ============================================================ */
<?php echo $w; ?> .case-products-box {
    border: 1px solid var(--case-rule);
    border-radius: 6px;
    overflow: hidden;
    background: var(--case-rule);
}
<?php echo $w; ?> .case-products-box-header {
    padding: 14px 22px;
    background: var(--case-bg-soft);
    border-bottom: 1px solid var(--case-rule);
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 16px;
}
<?php echo $w; ?> .case-products-box-title {
    font-size: 12px;
    font-weight: 700;
    color: var(--case-main);
    text-transform: uppercase;
    letter-spacing: 1.4px;
    margin: 0;
    line-height: 1.4;
}
<?php echo $w; ?> .case-products-box-meta {
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 0.5px;
    color: var(--case-muted);
    text-transform: uppercase;
}
<?php echo $w; ?> .case-products-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1px;
}
<?php echo $w; ?> .case-products-grid--single {
    grid-template-columns: 1fr;
}
<?php echo $w; ?> .case-product-card {
    display: flex;
    align-items: center;
    gap: 14px;
    background: #fff;
    padding: 16px 18px;
    transition: background 0.15s;
    text-decoration: none;
    color: inherit;
}
<?php echo $w; ?> .case-product-card:hover {
    background: var(--case-bg-soft);
}
<?php echo $w; ?> .case-product-image {
    width: 52px;
    height: 52px;
    object-fit: contain;
    background: var(--case-bg-soft);
    border-radius: 4px;
    padding: 6px;
    flex-shrink: 0;
}
<?php echo $w; ?> .case-product-image--placeholder { display: block; }
<?php echo $w; ?> .case-product-body { flex: 1; min-width: 0; }
<?php echo $w; ?> .case-product-brand {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    color: var(--case-secondary);
    margin-bottom: 3px;
}
<?php echo $w; ?> .case-product-name {
    font-size: 0.95rem;
    font-weight: 600;
    margin: 0 0 3px;
    color: var(--case-main);
    line-height: 1.3;
}
<?php echo $w; ?> .case-product-article-number {
    font-size: 0.78rem;
    color: var(--case-muted);
    margin-bottom: 4px;
    letter-spacing: 0.3px;
}
<?php echo $w; ?> .case-product-role {
    font-size: 0.8rem;
    color: var(--case-muted);
    line-height: 1.45;
    margin: 0;
}
<?php echo $w; ?> .case-product-arrow {
    color: var(--case-rule);
    font-size: 1.1rem;
    flex-shrink: 0;
    transition: color 0.2s, transform 0.2s;
}
<?php echo $w; ?> .case-product-card:hover .case-product-arrow {
    color: var(--case-secondary);
    transform: translateX(3px);
}

/* ============================================================
   GALLERY
   ============================================================ */
<?php echo $w; ?> .case-gallery-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-top: 24px;
}
<?php echo $w; ?> .case-gallery-item { margin: 0; }
<?php echo $w; ?> .case-gallery-item img {
    width: 100%;
    aspect-ratio: 4 / 3;
    object-fit: cover;
    border-radius: 4px;
}
<?php echo $w; ?> .case-gallery-item figcaption {
    font-size: 12.5px;
    color: var(--case-muted);
    font-style: italic;
    margin-top: 8px;
    padding-left: 10px;
    border-left: 2px solid var(--case-rule);
}
<?php echo $w; ?> .case-gallery-item:nth-child(3n+1) {
    grid-column: span 2;
}
<?php echo $w; ?> .case-gallery-item:nth-child(3n+1) img {
    aspect-ratio: 16 / 9;
}

/* ============================================================
   ABOUT CUSTOMER
   ============================================================ */
<?php echo $w; ?> .case-about-customer-inline {
    margin-top: 64px;
    padding-top: 32px;
    border-top: 1px solid var(--case-rule);
    display: grid;
    grid-template-columns: 130px 1fr;
    gap: 28px;
    align-items: start;
}
<?php echo $w; ?> .case-about-customer-inline--no-logo {
    grid-template-columns: 1fr;
}
<?php echo $w; ?> .case-about-customer-inline img {
    max-height: 60px;
    max-width: 130px;
    object-fit: contain;
    object-position: left center;
}
<?php echo $w; ?> .case-about-customer-inline h3 {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--case-main);
    margin: 0 0 10px;
    letter-spacing: -0.005em;
}
<?php echo $w; ?> .case-about-customer-inline p {
    font-size: 0.95rem;
    line-height: 1.65;
    color: #2a2a2a;
    margin: 0 0 12px;
}
<?php echo $w; ?> .case-about-customer-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.88rem;
    font-weight: 600;
    color: var(--case-main);
    text-decoration: none;
    border-bottom: 2px solid var(--case-secondary);
    padding-bottom: 1px;
}

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media (max-width: 900px) {
    <?php echo $w; ?> .case-grid {
        grid-template-columns: 1fr;
        gap: 40px;
        padding: 48px 0 56px;
    }
    <?php echo $w; ?> .case-glance {
        position: static;
        order: -1;
    }
    <?php echo $w; ?> .case-lead-paragraph { font-size: 1.08rem; }
    <?php echo $w; ?> .case-products-grid,
    <?php echo $w; ?> .case-products-grid--single { grid-template-columns: 1fr; }
    <?php echo $w; ?> .case-gallery-item:nth-child(n) { grid-column: span 1; }
    <?php echo $w; ?> .case-gallery-item img { aspect-ratio: 4 / 3; }
    <?php echo $w; ?> .case-stats-inline .case-stat {
        flex: 1 1 45%;
        border-left: none;
        padding: 16px 0;
        border-top: 1px solid var(--case-rule);
    }
    <?php echo $w; ?> .case-stats-inline .case-stat:first-child,
    <?php echo $w; ?> .case-stats-inline .case-stat:nth-child(2) {
        border-top: none;
        padding-top: 0;
    }
    <?php echo $w; ?> .case-about-customer-inline { grid-template-columns: 1fr; gap: 18px; }
    <?php echo $w; ?> .case-byline-strip { gap: 20px; }
    <?php echo $w; ?> .case-byline-logos { margin-left: 0; width: 100%; padding-top: 8px; }
}
@media (max-width: 600px) {
    <?php echo $w; ?> .case-container { padding: 0 20px; }
    <?php echo $w; ?> .case-header { padding: 44px 0 36px; }
    <?php echo $w; ?> .case-pullquote-inline { padding-left: 20px; margin: 40px 0; }
    <?php echo $w; ?> .case-testimonial-inline { padding: 24px 22px; margin: 40px 0; }
}
</style>
        <?php
        return ob_get_clean();
    }
}

new Wexoe_Case();

/* ============================================================
   SEO META — sätts via wp_head + document_title_parts.
   <head> har redan renderats när shortcoden körs, så vi måste
   haka in tidigare. Funktionen letar efter [wexoe_case slug="..."]
   i sidans post_content och plockar fältet ur Core om hittat.
   ============================================================ */

if (!function_exists('wexoe_case_extract_slug_from_post')) {
    /**
     * Returnera första slug-värdet i ett [wexoe_case slug="..."]-shortcode
     * i nuvarande WP-postens content, eller null.
     */
    function wexoe_case_extract_slug_from_post() {
        if (!is_singular()) return null;
        global $post;
        if (!$post || !is_object($post) || empty($post->post_content)) return null;
        if (!preg_match('/\[wexoe_case\s+[^\]]*slug=["\']([^"\']+)["\']/i', $post->post_content, $m)) {
            return null;
        }
        return $m[1];
    }
}

if (!function_exists('wexoe_case_load_for_seo')) {
    /**
     * Hämta cms_cases-record för aktuell sida om en wexoe_case-shortcode finns.
     * Returnerar null om Core saknas, slug saknas eller record inte hittas/inte är aktivt.
     * Cacheas per-request så vi inte slår Core två gånger (title + meta).
     */
    function wexoe_case_load_for_seo() {
        static $cached = false;
        static $result = null;
        if ($cached) return $result;
        $cached = true;

        $slug = wexoe_case_extract_slug_from_post();
        if ($slug === null) return null;
        if (!wexoe_case_core_ready()) return null;

        $repo = \Wexoe\Core\Core::entity('cms_cases');
        if (!$repo) return null;

        $data = $repo->find($slug);
        if (!$data) return null;
        if (empty($data['is_active'])) return null;

        $result = $data;
        return $result;
    }
}

add_filter('document_title_parts', function ($parts) {
    $data = wexoe_case_load_for_seo();
    if ($data === null) return $parts;

    $seo_title = isset($data['seo_title']) ? trim((string) $data['seo_title']) : '';
    $title     = isset($data['title']) ? trim((string) $data['title']) : '';
    $final     = $seo_title !== '' ? $seo_title : $title;
    if ($final !== '') {
        $parts['title'] = $final;
    }
    return $parts;
});

add_action('wp_head', function () {
    $data = wexoe_case_load_for_seo();
    if ($data === null) return;

    $description = isset($data['seo_description']) ? trim((string) $data['seo_description']) : '';
    $og_image    = isset($data['og_image_url']) ? trim((string) $data['og_image_url']) : '';
    $seo_title   = isset($data['seo_title']) ? trim((string) $data['seo_title']) : '';
    $title       = isset($data['title']) ? trim((string) $data['title']) : '';
    $og_title    = $seo_title !== '' ? $seo_title : $title;

    if ($og_title !== '') {
        echo '<meta property="og:title" content="' . esc_attr($og_title) . '" />' . "\n";
    }
    if ($description !== '') {
        echo '<meta name="description" content="' . esc_attr($description) . '" />' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($description) . '" />' . "\n";
    }
    if ($og_image !== '') {
        echo '<meta property="og:image" content="' . esc_url($og_image) . '" />' . "\n";
    }
}, 5);
