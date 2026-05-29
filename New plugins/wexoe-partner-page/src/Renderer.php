<?php
/**
 * Renderer för wexoe-partner-page.
 *
 * Tar ett normaliserat partner_pages-record från Core::entity('partner_pages')
 * och producerar prototype_4.html-strukturen. Resolva linked records
 * (core_partners, cases, product_areas) i konstruktorn och cache:a på instansen.
 *
 * Markup matchar prototypens .sup-*-prefixade klasser. Visual styling kommer
 * från ../assets/style.css (enqueueas av bootstrap-filen).
 */

namespace Wexoe\PartnerPage;

if (!defined('ABSPATH')) exit;

class Renderer {

    /** @var array Hela partner_pages-recordet. */
    private $data;

    /** @var string Aktuell slug — används för logg-kontext. */
    private $slug;

    /** @var array|null Första partner-record från core_partners, eller null. */
    private $partner;

    /** @var array Resolva success cases (max 3, aktiva, i länkad ordning). */
    private $cases;

    /** @var array Resolva product-area cards (aktiva, i länkad ordning). */
    private $categories;

    /** @var array Parsade FAQ-items från faqs JSON-fältet. */
    private $faqs;

    /**
     * Inline-SVG-content per icon-key. Värdet är innehållet INNE i
     * <svg class="sup-fact-icon" viewBox="0 0 24 24" fill="none">…</svg>.
     * Marknadsförare anger en av nycklarna i facts_N_icon-fältet.
     * Okända keys → ikonen utelämnas (logg-warning).
     */
    private static $icons = [
        'calendar' =>
            '<rect x="3" y="4" width="18" height="17" rx="2" stroke="currentColor" stroke-width="1.8"/>'
            . '<path d="M3 9h18M8 2v4M16 2v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'users' =>
            '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>'
            . '<circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.8"/>',
        'globe' =>
            '<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.8"/>'
            . '<path d="M2 12h20M12 2c2.5 2.5 4 6 4 10s-1.5 7.5-4 10c-2.5-2.5-4-6-4-10s1.5-7.5 4-10z" stroke="currentColor" stroke-width="1.8"/>',
        'shield' =>
            '<path d="M12 2L3 7v6c0 5 3.5 9.5 9 11 5.5-1.5 9-6 9-11V7l-9-5z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'building' =>
            '<rect x="4" y="2" width="16" height="20" rx="1" stroke="currentColor" stroke-width="1.8"/>'
            . '<path d="M9 7h2M13 7h2M9 11h2M13 11h2M9 15h2M13 15h2M10 22v-3h4v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'factory' =>
            '<path d="M2 22V10l6 4V10l6 4V6l8 5v11H2z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>'
            . '<path d="M6 18h2M11 18h2M16 18h2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'award' =>
            '<circle cx="12" cy="9" r="6" stroke="currentColor" stroke-width="1.8"/>'
            . '<path d="M8.21 13.89L7 23l5-3 5 3-1.21-9.12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'package' =>
            '<path d="M12 2l9 5v10l-9 5-9-5V7l9-5z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>'
            . '<path d="M3.27 6.96L12 12l8.73-5.04M12 22V12" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>',
        'briefcase' =>
            '<rect x="2" y="7" width="20" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/>'
            . '<path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M2 13h20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'target' =>
            '<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.8"/>'
            . '<circle cx="12" cy="12" r="6" stroke="currentColor" stroke-width="1.8"/>'
            . '<circle cx="12" cy="12" r="2" stroke="currentColor" stroke-width="1.8" fill="currentColor"/>',
    ];

    public function __construct(array $data) {
        $this->data = $data;
        $this->slug = (string) ($data['slug'] ?? '');
        $this->partner = $this->resolve_partner();
        $this->cases = $this->resolve_cases();
        $this->categories = $this->resolve_categories();
        $this->faqs = $this->parse_faqs();
    }

    /* =====================================================
       PUBLIC ENTRY
       ===================================================== */

    public function render(): string {
        ob_start();
        echo $this->google_fonts();
        echo '<div class="wexoe-partner-page">';
        echo $this->render_hero();
        if (!empty($this->data['show_quick_facts'])) {
            echo $this->render_quick_facts();
        }
        if (!empty($this->data['show_about'])) {
            echo $this->render_about();
        }
        if (!empty($this->data['show_why_wexoe'])) {
            echo $this->render_why_wexoe();
        }
        if (!empty($this->data['show_categories'])) {
            echo $this->render_categories();
        }
        if (!empty($this->data['show_faq'])) {
            echo $this->render_faq();
        }
        if (!empty($this->data['show_contact_person'])) {
            echo $this->render_contact_person();
        }
        if (!empty($this->data['show_contact_form'])) {
            echo $this->render_contact_form_section();
        }
        echo '</div>';
        return (string) ob_get_clean();
    }

    /* =====================================================
       RESOLVERS — kör i konstruktorn
       ===================================================== */

    /**
     * partner_ids → första core_partners-record (eller null).
     * Logo + namn används i hero-lockupet.
     */
    private function resolve_partner() {
        $ids = isset($this->data['partner_ids']) && is_array($this->data['partner_ids'])
            ? $this->data['partner_ids']
            : [];
        if (empty($ids)) return null;

        if (count($ids) > 1) {
            \Wexoe\Core\Core::log('warning', 'partner_pages.partner_ids has multiple links; using first', [
                'slug' => $this->slug,
                'count' => count($ids),
            ]);
        }

        $repo = \Wexoe\Core\Core::entity('core_partners');
        if (!$repo) return null;

        $records = $repo->find_by_ids([$ids[0]]);
        return !empty($records) ? $records[0] : null;
    }

    /**
     * case_ids → success cases. Filter is_active, behåll länkad ordning,
     * slice till max 3. cases-entiteten har inget order-fält så marknadsförarens
     * länkningsordning är auktoritativ.
     */
    private function resolve_cases(): array {
        $ids = isset($this->data['case_ids']) && is_array($this->data['case_ids'])
            ? $this->data['case_ids']
            : [];
        if (empty($ids)) return [];

        $repo = \Wexoe\Core\Core::entity('cms_cases');
        if (!$repo) return [];

        $records = $repo->find_by_ids($ids);
        $active = array_values(array_filter($records, function ($r) {
            return !empty($r['is_active']);
        }));
        return array_slice($active, 0, 3);
    }

    /**
     * category_ids → produktområdes-kort. Filter is_active, behåll länkad
     * ordning. Inget order-fält på product_areas.
     */
    private function resolve_categories(): array {
        $ids = isset($this->data['category_ids']) && is_array($this->data['category_ids'])
            ? $this->data['category_ids']
            : [];
        if (empty($ids)) return [];

        $repo = \Wexoe\Core\Core::entity('product_areas');
        if (!$repo) return [];

        $records = $repo->find_by_ids($ids);
        return array_values(array_filter($records, function ($r) {
            return !empty($r['is_active']);
        }));
    }

    /**
     * faqs är ett longText-fält som lagrar JSON: [{question, answer}, ...].
     * Skippa hela FAQ-sektionen tyst vid parse-fel — logga warning.
     */
    private function parse_faqs(): array {
        $raw = $this->data['faqs'] ?? '';
        if (!is_string($raw) || trim($raw) === '') return [];

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            \Wexoe\Core\Core::log('warning', 'Failed to parse partner_pages.faqs JSON', [
                'slug'  => $this->slug,
                'error' => json_last_error_msg(),
            ]);
            return [];
        }

        $items = [];
        foreach ($decoded as $item) {
            if (!is_array($item)) continue;
            $q = trim((string) ($item['question'] ?? ''));
            if ($q === '') continue;
            $items[] = [
                'question' => $q,
                'answer'   => (string) ($item['answer'] ?? ''),
            ];
        }
        return $items;
    }

    /* =====================================================
       SECTION: HERO
       ===================================================== */

    private function render_hero(): string {
        $eyebrow      = trim((string) ($this->data['hero_eyebrow'] ?? ''));
        $h1           = trim((string) ($this->data['h1'] ?? ''));
        $tagline_raw  = trim((string) ($this->data['hero_tagline'] ?? ''));
        $tagline_html = $tagline_raw !== '' ? \Wexoe\Core\Helpers\Markdown::to_inline($tagline_raw) : '';
        $cta_text     = trim((string) ($this->data['hero_cta_text'] ?? '')) !== ''
            ? (string) $this->data['hero_cta_text']
            : 'Kontakta oss';
        $cta_url      = trim((string) ($this->data['hero_cta_url'] ?? '')) !== ''
            ? (string) $this->data['hero_cta_url']
            : '#kontakt';
        $cta2_text    = trim((string) ($this->data['hero_cta2_text'] ?? ''));
        $cta2_url     = trim((string) ($this->data['hero_cta2_url'] ?? ''));
        $has_cta2     = ($cta2_text !== '' && $cta2_url !== '');
        $hero_image   = trim((string) ($this->data['hero_image_url'] ?? ''));

        $partner_logo = $this->partner['logo_url'] ?? '';
        $partner_name = trim((string) ($this->partner['name'] ?? '')) !== ''
            ? (string) $this->partner['name']
            : ($h1 !== '' ? $h1 : 'Partner');

        ob_start();
        ?>
        <section class="sup-hero">
            <div class="sup-hero-shapes">
                <div class="sup-hero-shape1"></div>
                <div class="sup-hero-shape2"></div>
            </div>
            <div class="sup-hero-text">
                <?php if ($eyebrow !== ''): ?>
                <div class="sup-hero-eyebrow">
                    <svg width="14" height="14" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <circle cx="10" cy="10" r="9" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M6 10.5L8.5 13L14 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span><?php echo esc_html($eyebrow); ?></span>
                </div>
                <?php endif; ?>

                <?php if ($partner_logo !== ''): ?>
                <div class="sup-hero-logo">
                    <img src="<?php echo esc_url($partner_logo); ?>" alt="<?php echo esc_attr($partner_name); ?>"/>
                </div>
                <?php endif; ?>

                <?php if ($h1 !== ''): ?>
                <h1 class="sup-hero-h1"><?php echo esc_html($h1); ?></h1>
                <?php endif; ?>

                <?php if ($tagline_html !== ''): ?>
                <p class="sup-hero-tagline"><?php echo $tagline_html; ?></p>
                <?php endif; ?>

                <div class="sup-hero-buttons">
                    <a href="<?php echo esc_url($cta_url); ?>" class="sup-btn-primary">
                        <?php echo esc_html($cta_text); ?> <span aria-hidden="true">&rarr;</span>
                    </a>
                    <?php if ($has_cta2): ?>
                    <a href="<?php echo esc_url($cta2_url); ?>" class="sup-btn-secondary">
                        <?php echo esc_html($cta2_text); ?> <span aria-hidden="true">&rarr;</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($hero_image !== ''): ?>
            <div class="sup-hero-image-wrapper">
                <img src="<?php echo esc_url($hero_image); ?>" alt=""/>
            </div>
            <?php endif; ?>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    /* =====================================================
       SECTION: QUICK FACTS
       ===================================================== */

    private function render_quick_facts(): string {
        $facts = [];
        for ($i = 1; $i <= 4; $i++) {
            $icon  = trim((string) ($this->data["facts_{$i}_icon"]  ?? ''));
            $value = trim((string) ($this->data["facts_{$i}_value"] ?? ''));
            $label = trim((string) ($this->data["facts_{$i}_label"] ?? ''));
            if ($icon === '' && $value === '' && $label === '') continue;
            $facts[] = ['icon' => $icon, 'value' => $value, 'label' => $label];
        }
        if (empty($facts)) return '';

        ob_start();
        ?>
        <section class="sup-facts">
            <div class="container">
                <div class="sup-facts-grid">
                    <?php foreach ($facts as $fact): ?>
                    <div class="sup-fact">
                        <?php echo $this->icon_svg($fact['icon']); ?>
                        <div>
                            <?php if ($fact['value'] !== ''): ?>
                            <div class="sup-fact-value"><?php echo esc_html($fact['value']); ?></div>
                            <?php endif; ?>
                            <?php if ($fact['label'] !== ''): ?>
                            <div class="sup-fact-label"><?php echo esc_html($fact['label']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * Returnerar inline SVG-markup för en icon-key, eller tom sträng vid
     * okänd key (logg-warning). Wrappar i .sup-fact-icon-elementet.
     */
    private function icon_svg(string $key): string {
        if ($key === '') return '';
        if (!isset(self::$icons[$key])) {
            \Wexoe\Core\Core::log('warning', 'Unknown partner_pages quick-fact icon key', [
                'slug' => $this->slug,
                'icon' => $key,
            ]);
            return '';
        }
        return '<svg class="sup-fact-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">'
            . self::$icons[$key]
            . '</svg>';
    }

    /* =====================================================
       SECTION: ABOUT
       ===================================================== */

    private function render_about(): string {
        $eyebrow     = trim((string) ($this->data['about_eyebrow'] ?? ''));
        $h2          = trim((string) ($this->data['about_h2'] ?? ''));
        $text_raw    = trim((string) ($this->data['about_text'] ?? ''));
        $text_html   = $text_raw !== '' ? \Wexoe\Core\Helpers\Markdown::to_html($text_raw) : '';
        $image       = trim((string) ($this->data['about_image_url'] ?? ''));
        $badge_value = trim((string) ($this->data['about_badge_value'] ?? ''));
        $badge_label = trim((string) ($this->data['about_badge_label'] ?? ''));

        ob_start();
        ?>
        <section class="sup-about">
            <div class="container">
                <div class="sup-about-grid">
                    <div class="sup-about-text-col">
                        <?php if ($eyebrow !== ''): ?>
                        <div class="sup-about-eyebrow"><?php echo esc_html($eyebrow); ?></div>
                        <?php endif; ?>
                        <?php if ($h2 !== ''): ?>
                        <h2 class="sup-about-h2"><?php echo esc_html($h2); ?></h2>
                        <?php endif; ?>
                        <?php if ($text_html !== ''): ?>
                        <div class="sup-about-text"><?php echo $text_html; ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if ($image !== '' || $badge_value !== ''): ?>
                    <div class="sup-about-image">
                        <?php if ($image !== ''): ?>
                        <img src="<?php echo esc_url($image); ?>" alt=""/>
                        <?php endif; ?>
                        <?php if ($badge_value !== ''): ?>
                        <div class="sup-about-badge">
                            <div class="sup-about-badge-value"><?php echo esc_html($badge_value); ?></div>
                            <?php if ($badge_label !== ''): ?>
                            <div class="sup-about-badge-label"><?php echo esc_html($badge_label); ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    /* =====================================================
       SECTION: WHY WEXOE (cases stack + copy + benefits)
       ===================================================== */

    private function render_why_wexoe(): string {
        $h2       = trim((string) ($this->data['why_h2'] ?? ''));
        $text_raw = trim((string) ($this->data['why_text'] ?? ''));
        $text_html = $text_raw !== '' ? \Wexoe\Core\Helpers\Markdown::to_inline($text_raw) : '';
        $benefits = isset($this->data['why_benefits']) && is_array($this->data['why_benefits'])
            ? array_values(array_filter(array_map('trim', $this->data['why_benefits']), function ($b) {
                return $b !== '';
            }))
            : [];

        $view_all_text = trim((string) ($this->data['cases_view_all_text'] ?? ''));
        $view_all_url  = trim((string) ($this->data['cases_view_all_url'] ?? ''));
        $show_view_all = ($view_all_text !== '' && $view_all_url !== '');

        ob_start();
        ?>
        <section class="sup-why">
            <div class="container">
                <div class="sup-why-grid">
                    <div class="sup-cases-col">
                        <?php if (!empty($this->cases)): ?>
                            <?php foreach ($this->cases as $case): ?>
                                <?php echo $this->render_case_card($case); ?>
                            <?php endforeach; ?>
                            <?php if ($show_view_all): ?>
                            <a class="sup-cases-viewall" href="<?php echo esc_url($view_all_url); ?>">
                                <?php echo esc_html($view_all_text); ?>
                                <svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                    <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php echo $this->render_cases_fallback(); ?>
                        <?php endif; ?>
                    </div>
                    <div class="sup-why-text">
                        <?php if ($h2 !== ''): ?>
                        <h2><?php echo esc_html($h2); ?></h2>
                        <?php endif; ?>
                        <?php if ($text_html !== ''): ?>
                        <p><?php echo $text_html; ?></p>
                        <?php endif; ?>
                        <?php if (!empty($benefits)): ?>
                        <ul class="sup-why-list">
                            <?php foreach ($benefits as $benefit): ?>
                            <li>
                                <svg class="check-icon" width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                    <path d="M3 8L6.5 11.5L13 5" stroke="#10B981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <?php echo \Wexoe\Core\Helpers\Markdown::to_inline($benefit); ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * Ett enskilt case-kort. Fältmappning:
     *   cases.lead_image_url → image
     *   cases.title          → title
     *   cases.subtitle       → description
     *   /case/{slug}/        → link (konvention från wexoe-customer-type-page)
     */
    private function render_case_card(array $case): string {
        $image       = trim((string) ($case['lead_image_url'] ?? ''));
        $title       = trim((string) ($case['title'] ?? ''));
        $description = trim((string) ($case['subtitle'] ?? ''));
        $link_url    = \Wexoe\Core\Helpers\Permalink::for_record('cms_cases', $case);
        $link_text   = 'Läs case';

        if ($title === '' && $image === '' && $description === '') return '';

        $tag = $link_url !== '' ? 'a' : 'div';
        $href_attr = $link_url !== '' ? ' href="' . esc_url($link_url) . '"' : '';

        ob_start();
        ?>
        <<?php echo $tag; ?> class="sup-case"<?php echo $href_attr; ?>>
            <div class="sup-case-img-wrap">
                <div class="sup-case-img-inner">
                    <?php if ($image !== ''): ?>
                    <img class="sup-case-img" src="<?php echo esc_url($image); ?>" alt=""/>
                    <?php endif; ?>
                </div>
            </div>
            <div class="sup-case-body">
                <?php if ($title !== ''): ?>
                <div class="sup-case-title"><?php echo esc_html($title); ?></div>
                <?php endif; ?>
                <?php if ($description !== ''): ?>
                <div class="sup-case-desc"><?php echo esc_html($description); ?></div>
                <?php endif; ?>
                <?php if ($link_url !== ''): ?>
                <span class="sup-case-link">
                    <?php echo esc_html($link_text); ?>
                    <svg width="12" height="12" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                        <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <?php endif; ?>
            </div>
        </<?php echo $tag; ?>>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * Fallback i cases-kolumnen när success_cases är tom. Återanvänder
     * contact_*-fälten från samma partner-page-record.
     */
    private function render_cases_fallback(): string {
        $name  = trim((string) ($this->data['contact_name']  ?? ''));
        $title = trim((string) ($this->data['contact_title'] ?? ''));
        $email = trim((string) ($this->data['contact_email'] ?? ''));
        $phone = trim((string) ($this->data['contact_phone'] ?? ''));
        $image = trim((string) ($this->data['contact_image_url'] ?? ''));
        $quote = trim((string) ($this->data['contact_quote'] ?? ''));

        if ($name === '' && $email === '' && $phone === '' && $quote === '') {
            return '';
        }

        ob_start();
        ?>
        <div class="sup-cases-fallback">
            <?php if ($image !== ''): ?>
            <div class="sup-cases-fallback-photo">
                <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($name); ?>"/>
            </div>
            <?php endif; ?>
            <?php if ($name !== ''): ?>
            <h3><?php echo esc_html($name); ?></h3>
            <?php endif; ?>
            <?php if ($title !== ''): ?>
            <div class="role"><?php echo esc_html($title); ?></div>
            <?php endif; ?>
            <?php if ($quote !== ''): ?>
            <div class="quote"><?php echo esc_html($quote); ?></div>
            <?php endif; ?>
            <?php if ($email !== '' || $phone !== ''): ?>
            <div class="links">
                <?php if ($email !== ''): ?>
                <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
                <?php endif; ?>
                <?php if ($phone !== ''): ?>
                <a href="tel:<?php echo esc_attr($this->tel_href($phone)); ?>"><?php echo esc_html($phone); ?></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    /* =====================================================
       SECTION: CATEGORIES
       ===================================================== */

    private function render_categories(): string {
        if (empty($this->categories)) return '';

        $eyebrow = trim((string) ($this->data['categories_eyebrow'] ?? ''));
        $h2      = trim((string) ($this->data['categories_h2'] ?? ''));
        $intro_raw  = trim((string) ($this->data['categories_intro'] ?? ''));
        $intro_html = $intro_raw !== '' ? \Wexoe\Core\Helpers\Markdown::to_inline($intro_raw) : '';

        ob_start();
        ?>
        <section class="sup-categories" id="produkter">
            <div class="container">
                <div class="sup-section-head">
                    <?php if ($eyebrow !== ''): ?>
                    <div class="sup-section-eyebrow"><?php echo esc_html($eyebrow); ?></div>
                    <?php endif; ?>
                    <?php if ($h2 !== ''): ?>
                    <h2 class="sup-section-h2"><?php echo esc_html($h2); ?></h2>
                    <?php endif; ?>
                    <?php if ($intro_html !== ''): ?>
                    <p class="sup-section-intro"><?php echo $intro_html; ?></p>
                    <?php endif; ?>
                </div>
                <div class="sup-cat-grid">
                    <?php foreach ($this->categories as $cat): ?>
                        <?php echo $this->render_category_card($cat); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * Produktområdes-kort. Fältmappning från product_areas-record:
     *   card_image_url   → image
     *   name             → name
     *   card_description → description
     *   /produktomrade/{slug}/ → URL (mappar mot wexoe-product-area-plugin)
     */
    private function render_category_card(array $cat): string {
        $image       = trim((string) ($cat['card_image_url'] ?? ''));
        $name        = trim((string) ($cat['name'] ?? ''));
        $description = trim((string) ($cat['card_description'] ?? ''));
        $cat_slug    = trim((string) ($cat['slug'] ?? ''));
        $link_url    = $cat_slug !== '' ? '/produktomrade/' . rawurlencode($cat_slug) . '/' : '';

        if ($name === '' && $image === '') return '';

        $tag = $link_url !== '' ? 'a' : 'div';
        $href_attr = $link_url !== '' ? ' href="' . esc_url($link_url) . '"' : '';

        ob_start();
        ?>
        <<?php echo $tag; ?> class="sup-cat-card"<?php echo $href_attr; ?>>
            <?php if ($image !== ''): ?>
            <img class="sup-cat-img" src="<?php echo esc_url($image); ?>" alt=""/>
            <?php else: ?>
            <div class="sup-cat-img" aria-hidden="true"></div>
            <?php endif; ?>
            <div class="sup-cat-body">
                <?php if ($name !== ''): ?>
                <div class="sup-cat-name"><?php echo esc_html($name); ?></div>
                <?php endif; ?>
                <?php if ($description !== ''): ?>
                <div class="sup-cat-desc"><?php echo esc_html($description); ?></div>
                <?php endif; ?>
                <?php if ($link_url !== ''): ?>
                <span class="sup-cat-link">Se produkter <span aria-hidden="true">&rarr;</span></span>
                <?php endif; ?>
            </div>
        </<?php echo $tag; ?>>
        <?php
        return (string) ob_get_clean();
    }

    /* =====================================================
       SECTION: FAQ
       ===================================================== */

    private function render_faq(): string {
        if (empty($this->faqs)) return '';

        $h2 = trim((string) ($this->data['faq_h2'] ?? ''));

        ob_start();
        ?>
        <section class="sup-faq">
            <div class="container">
                <div class="sup-section-head">
                    <div class="sup-section-eyebrow">Vanliga frågor</div>
                    <?php if ($h2 !== ''): ?>
                    <h2 class="sup-section-h2"><?php echo esc_html($h2); ?></h2>
                    <?php endif; ?>
                </div>
                <ul class="sup-faq-list">
                    <?php foreach ($this->faqs as $i => $item): ?>
                    <li class="sup-faq-item">
                        <details<?php echo $i === 0 ? ' open' : ''; ?>>
                            <summary class="sup-faq-q">
                                <span class="sup-faq-q-text"><?php echo esc_html($item['question']); ?></span>
                                <span class="sup-faq-icon" aria-hidden="true"></span>
                            </summary>
                            <div class="sup-faq-a"><?php echo \Wexoe\Core\Helpers\Markdown::to_html($item['answer']); ?></div>
                        </details>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    /* =====================================================
       SECTION: CONTACT PERSON (navy strip)
       ===================================================== */

    private function render_contact_person(): string {
        $name  = trim((string) ($this->data['contact_name']  ?? ''));
        $title = trim((string) ($this->data['contact_title'] ?? ''));
        $email = trim((string) ($this->data['contact_email'] ?? ''));
        $phone = trim((string) ($this->data['contact_phone'] ?? ''));
        $image = trim((string) ($this->data['contact_image_url'] ?? ''));
        $quote = trim((string) ($this->data['contact_quote'] ?? ''));

        if ($name === '' && $email === '' && $phone === '' && $quote === '' && $image === '') {
            return '';
        }

        ob_start();
        ?>
        <section class="sup-contact">
            <div class="container">
                <div class="sup-contact-inner">
                    <?php if ($image !== ''): ?>
                    <div class="sup-contact-photo">
                        <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($name); ?>"/>
                    </div>
                    <?php endif; ?>
                    <div class="sup-contact-info">
                        <?php if ($name !== ''): ?>
                        <h3><?php echo esc_html($name); ?></h3>
                        <?php endif; ?>
                        <?php if ($title !== ''): ?>
                        <div class="sup-contact-role"><?php echo esc_html($title); ?></div>
                        <?php endif; ?>
                        <?php if ($email !== '' || $phone !== ''): ?>
                        <div class="sup-contact-links">
                            <?php if ($email !== ''): ?>
                            <a href="mailto:<?php echo esc_attr($email); ?>">
                                <svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                    <rect x="1.5" y="3" width="13" height="10" rx="1.5" stroke="currentColor" stroke-width="1.5"/>
                                    <path d="M2 4l6 5 6-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <?php echo esc_html($email); ?>
                            </a>
                            <?php endif; ?>
                            <?php if ($phone !== ''): ?>
                            <a href="tel:<?php echo esc_attr($this->tel_href($phone)); ?>">
                                <svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                    <path d="M3 2.5C3 2 3.5 1.5 4 1.5h2c0.5 0 1 0.5 1 1l0.5 2.5c0 0.5-0.5 1-1 1l-1 0.5c1 2 2.5 3.5 4.5 4.5l0.5-1c0-0.5 0.5-1 1-1l2.5 0.5c0.5 0 1 0.5 1 1v2c0 0.5-0.5 1-1 1C7 12.5 3 8.5 3 2.5z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                                </svg>
                                <?php echo esc_html($phone); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($quote !== ''): ?>
                    <div class="sup-contact-quote"><?php echo esc_html($quote); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    /* =====================================================
       SECTION: CONTACT FORM (shared Core-renderer)
       ===================================================== */

    private function render_contact_form_section(): string {
        $class = \Wexoe\Core\Core::renderer('contact-form');
        if ($class === '' || !class_exists($class)) return '';

        $contact_person = null;
        if (!empty($this->data['contact_form_show_contact_person'])) {
            $name  = trim((string) ($this->data['contact_name']  ?? ''));
            $title = trim((string) ($this->data['contact_title'] ?? ''));
            $email = trim((string) ($this->data['contact_email'] ?? ''));
            $phone = trim((string) ($this->data['contact_phone'] ?? ''));
            $image = trim((string) ($this->data['contact_image_url'] ?? ''));
            $quote = trim((string) ($this->data['contact_quote'] ?? ''));
            if ($name !== '' || $email !== '' || $phone !== '') {
                $contact_person = [
                    'name'  => $name,
                    'title' => $title,
                    'email' => $email,
                    'phone' => $phone,
                    'image' => $image,
                    'quote' => $quote,
                ];
            }
        }

        $html = $class::render([
            'eyebrow'        => $this->data['contact_form_eyebrow'] ?? '',
            'title'          => $this->data['contact_form_title'] ?? '',
            'subtitle'       => $this->data['contact_form_subtitle'] ?? '',
            'layout'         => $this->data['contact_form_layout'] ?? 'split',
            'theme'          => $this->data['contact_form_theme'] ?? 'dark',
            'show_company'   => $this->data['contact_form_show_company'] ?? true,
            'show_phone'     => $this->data['contact_form_show_phone'] ?? true,
            'show_dropdown'  => $this->data['contact_form_show_dropdown'] ?? true,
            'dropdown_label' => $this->data['contact_form_dropdown_label'] ?? '',
            'options'        => $this->data['contact_form_options'] ?? null,
            'cta_text'       => $this->data['contact_form_cta_text'] ?? '',
            'message_label'  => $this->data['contact_form_message_label'] ?? '',
            'trust_signals'  => $this->data['contact_form_trust_signals'] ?? null,
            'source_plugin'  => 'wexoe-partner-page',
            'page_slug'      => $this->slug,
            'contact_person' => $contact_person,
        ]);

        return '<section id="kontakt">' . $html . '</section>';
    }

    /* =====================================================
       HELPERS
       ===================================================== */

    /**
     * Normalisera ett telefonnummer för tel:-länk: behåll +-prefix och siffror.
     */
    private function tel_href(string $phone): string {
        return preg_replace('/[^0-9+]/', '', $phone) ?? '';
    }

    /**
     * Inline Google Fonts-länk (DM Sans). Matchar prototypens font-stack.
     * Övriga plugins kör samma mönster.
     */
    private function google_fonts(): string {
        return '<link rel="preconnect" href="https://fonts.googleapis.com">'
            . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
            . '<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">';
    }
}
