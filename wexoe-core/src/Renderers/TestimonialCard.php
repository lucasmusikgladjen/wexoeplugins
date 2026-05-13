<?php
namespace Wexoe\Core\Renderers;

use Wexoe\Core\Helpers\Collections;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Testimonial card-sektion (SSOT). CSS-prefix `wxr-tc__`.
 *
 * Config:
 *   scope — array {customer_type?, division?, country?}
 *
 * Visar första matchande testimonial (Featured = first if available).
 */
class TestimonialCard {
    public static function render(array $config): string {
        $scope = isset($config['scope']) && is_array($config['scope']) ? $config['scope'] : [];
        $scope_with_limit = $scope + ['limit' => 1];

        // Försök först med featured-only.
        $featured_scope = $scope_with_limit + ['featured_only' => true];
        $testimonials = Collections::testimonials_for_scope($featured_scope);
        if (empty($testimonials)) {
            // Fall tillbaka på vanlig matchning.
            $testimonials = Collections::testimonials_for_scope($scope_with_limit);
        }
        if (empty($testimonials)) return '';

        $t = $testimonials[0];
        $quote = (string) ($t['quote'] ?? '');
        if ($quote === '') return '';

        $author_image = $t['author_image'] ?? null;
        $author_image_url = is_array($author_image) ? ($author_image['url'] ?? '') : '';

        ob_start();
        ?>
        <section class="wxr-tc">
            <div class="wxr-tc__inner">
                <blockquote class="wxr-tc__quote">"<?= esc_html($quote) ?>"</blockquote>
                <div class="wxr-tc__author">
                    <?php if ($author_image_url !== ''): ?>
                        <img class="wxr-tc__img" src="<?= esc_url($author_image_url) ?>" alt="" loading="lazy" />
                    <?php endif; ?>
                    <div class="wxr-tc__byline">
                        <?php if (!empty($t['author_name'])): ?>
                            <strong><?= esc_html($t['author_name']) ?></strong>
                        <?php endif; ?>
                        <?php if (!empty($t['author_title'])): ?>
                            <span><?= esc_html($t['author_title']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
        <style>
            .wxr-tc { padding: 64px 24px; background: #11325D; color: #fff; }
            .wxr-tc__inner { max-width: 720px; margin: 0 auto; text-align: center; }
            .wxr-tc__quote { font-size: clamp(1.125rem, 2vw, 1.5rem); line-height: 1.5; font-style: italic; margin: 0 0 24px; }
            .wxr-tc__author { display: inline-flex; align-items: center; gap: 12px; }
            .wxr-tc__img { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; }
            .wxr-tc__byline { text-align: left; display: flex; flex-direction: column; }
            .wxr-tc__byline strong { font-weight: 500; }
            .wxr-tc__byline span { font-size: 13px; opacity: 0.75; }
        </style>
        <?php
        return ob_get_clean();
    }
}
