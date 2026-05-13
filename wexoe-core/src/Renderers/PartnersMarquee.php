<?php
namespace Wexoe\Core\Renderers;

use Wexoe\Core\Helpers\Collections;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Partners marquee-sektion (SSOT). CSS-prefix `wxr-pm__`.
 *
 * Config:
 *   h2    — string
 *   scope — array {country?, division?}
 */
class PartnersMarquee {
    public static function render(array $config): string {
        $h2 = (string) ($config['h2'] ?? '');
        $scope = isset($config['scope']) && is_array($config['scope']) ? $config['scope'] : [];

        $partners = Collections::partners_for_scope($scope);
        if (empty($partners)) return '';

        ob_start();
        ?>
        <section class="wxr-pm">
            <div class="wxr-pm__inner">
                <?php if ($h2 !== ''): ?><h2 class="wxr-pm__h2"><?= esc_html($h2) ?></h2><?php endif; ?>
                <div class="wxr-pm__row">
                    <?php foreach ($partners as $p):
                        $logo = $p['logo'] ?? null;
                        $logo_url = is_array($logo) ? ($logo['url'] ?? '') : '';
                        if ($logo_url === '') continue;
                        $name = (string) ($p['name'] ?? '');
                        $url = (string) ($p['url'] ?? '');
                    ?>
                        <?php if ($url !== ''): ?>
                            <a class="wxr-pm__item" href="<?= esc_url($url) ?>" rel="noopener" target="_blank">
                                <img src="<?= esc_url($logo_url) ?>" alt="<?= esc_attr($name) ?>" loading="lazy" />
                            </a>
                        <?php else: ?>
                            <span class="wxr-pm__item">
                                <img src="<?= esc_url($logo_url) ?>" alt="<?= esc_attr($name) ?>" loading="lazy" />
                            </span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <style>
            .wxr-pm { padding: 48px 24px; background: #F5F6F8; }
            .wxr-pm__inner { max-width: 1200px; margin: 0 auto; text-align: center; }
            .wxr-pm__h2 { font-size: clamp(1.25rem, 2.5vw, 1.75rem); margin: 0 0 24px; font-weight: 600; opacity: 0.75; }
            .wxr-pm__row { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 32px; }
            .wxr-pm__item img { max-height: 48px; width: auto; opacity: 0.7; filter: grayscale(0.5); transition: opacity 0.2s, filter 0.2s; }
            .wxr-pm__item:hover img { opacity: 1; filter: none; }
        </style>
        <?php
        return ob_get_clean();
    }
}
