<?php
namespace Wexoe\Core\Renderers;

use Wexoe\Core\Helpers\Collections;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Team grid-sektion (SSOT). CSS-prefix `wxr-tg__`.
 *
 * Config:
 *   h2    — string
 *   scope — array {country?, division?, limit?}
 */
class TeamGrid {
    public static function render(array $config): string {
        $h2 = (string) ($config['h2'] ?? '');
        $scope = isset($config['scope']) && is_array($config['scope']) ? $config['scope'] : [];

        $coworkers = Collections::coworkers_for_scope($scope);
        if (empty($coworkers)) return '';

        ob_start();
        ?>
        <section class="wxr-tg">
            <div class="wxr-tg__inner">
                <?php if ($h2 !== ''): ?><h2 class="wxr-tg__h2"><?= esc_html($h2) ?></h2><?php endif; ?>
                <div class="wxr-tg__list">
                    <?php foreach ($coworkers as $c): ?>
                        <div class="wxr-tg__card">
                            <?php
                            $img = $c['image'] ?? null;
                            $img_url = is_array($img) ? ($img['url'] ?? '') : '';
                            if ($img_url !== ''):
                            ?>
                                <img class="wxr-tg__image" src="<?= esc_url($img_url) ?>" alt="" loading="lazy" />
                            <?php else: ?>
                                <div class="wxr-tg__image-placeholder"><?= esc_html(self::initials($c['full_name'] ?? '')) ?></div>
                            <?php endif; ?>
                            <h3 class="wxr-tg__name"><?= esc_html($c['full_name'] ?? '') ?></h3>
                            <?php if (!empty($c['title'])): ?>
                                <p class="wxr-tg__title"><?= esc_html($c['title']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($c['email']) || !empty($c['phone'])): ?>
                                <p class="wxr-tg__contact">
                                    <?php if (!empty($c['email'])): ?>
                                        <a href="mailto:<?= esc_attr($c['email']) ?>"><?= esc_html($c['email']) ?></a>
                                    <?php endif; ?>
                                    <?php if (!empty($c['phone'])): ?>
                                        <a href="tel:<?= esc_attr(preg_replace('/\s+/', '', $c['phone'])) ?>"><?= esc_html($c['phone']) ?></a>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <style>
            .wxr-tg { padding: 64px 24px; background: #fff; }
            .wxr-tg__inner { max-width: 1100px; margin: 0 auto; }
            .wxr-tg__h2 { font-size: clamp(1.5rem, 3vw, 2rem); margin: 0 0 32px; font-weight: 600; }
            .wxr-tg__list { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 24px; }
            .wxr-tg__card { text-align: center; }
            .wxr-tg__image, .wxr-tg__image-placeholder { width: 140px; height: 140px; border-radius: 50%; object-fit: cover; margin: 0 auto 12px; display: block; background: #F5F6F8; }
            .wxr-tg__image-placeholder { display: flex; align-items: center; justify-content: center; font-size: 36px; color: #999; font-weight: 500; }
            .wxr-tg__name { font-size: 16px; margin: 0 0 4px; font-weight: 500; }
            .wxr-tg__title { font-size: 13px; color: #777; margin: 0 0 8px; }
            .wxr-tg__contact a { display: block; font-size: 13px; color: #11325D; text-decoration: none; margin: 2px 0; }
        </style>
        <?php
        return ob_get_clean();
    }

    private static function initials($name) {
        $parts = preg_split('/\s+/', trim((string) $name));
        $initials = '';
        foreach ($parts as $p) {
            if ($p !== '') $initials .= mb_substr($p, 0, 1);
            if (mb_strlen($initials) >= 2) break;
        }
        return mb_strtoupper($initials);
    }
}
