<?php
namespace Wexoe\Core;

use Wexoe\Core\Helpers\Color;
use Wexoe\Core\Helpers\Singletons;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Delade design-tokens (ARKITEKTURPLAN FAS 4).
 *
 * EN källa för färg/typografi: `core_graphic_profile` → CSS-variabler som
 * skrivs i `<head>` på varje publik sida, plus en delad, SCOPED bas-stylesheet
 * (`assets/wexoe-base.css`). Tidigare hade varje feature-plugin egna
 * hårdkodade hex-färger + egen box-model/formulär-CSS inline (~28 KB
 * duplicerat). Med detta lager refererar plugins `var(--wexoe-color-*)` och
 * `.wexoe-block`-klasserna i stället.
 *
 * ADDITIVT: bas-CSS:en är scoped till `.wexoe-block` och rör därför ALDRIG
 * Enfold-temat globalt. Befintliga plugins fortsätter fungera oförändrat tills
 * de migreras (en i taget) till tokens/klasser — inget styling-brott smyger in.
 *
 * Tokens speglas på builder-sidan (`wexoebuilder/lib/design-tokens.ts`) så att
 * live-previewen och den publika sidan delar exakt samma palett.
 */
class DesignTokens {

    /** Fallback-palett (Wexoe navy/orange) när profil/fält saknas. */
    const DEFAULTS = [
        '--wexoe-color-primary'        => '#11325D',
        '--wexoe-color-secondary'      => '#F28C28',
        '--wexoe-color-accent'         => '#F28C28',
        '--wexoe-color-bg-light'       => '#FFFFFF',
        '--wexoe-color-bg-dark'        => '#11325D',
        '--wexoe-color-text'           => '#11325D',
        '--wexoe-color-text-secondary' => '#5A6B82',
    ];

    /** Registrera front-end-hookarna. Anropas från Plugin::boot(). */
    public static function register() {
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets']);
        // Prioritet 5 så variablerna finns före plugin-CSS som refererar dem.
        add_action('wp_head', [self::class, 'print_tokens'], 5);
    }

    /** Skriv :root-variabel-blocket i <head>. */
    public static function print_tokens() {
        if (is_admin()) {
            return;
        }
        echo self::css_variables_block(); // phpcs:ignore — färdig-escapad nedan
    }

    /** Enqueue delad bas-stylesheet + ev. font-CSS från profilen. */
    public static function enqueue_assets() {
        if (is_admin()) {
            return;
        }
        $path = WEXOE_CORE_PATH . 'assets/wexoe-base.css';
        $ver = file_exists($path) ? (string) filemtime($path) : '1';
        wp_enqueue_style('wexoe-base', WEXOE_CORE_URL . 'assets/wexoe-base.css', [], $ver);

        $profile = self::profile();
        $font_css = isset($profile['font_css_url']) ? trim((string) $profile['font_css_url']) : '';
        if ($font_css !== '' && filter_var($font_css, FILTER_VALIDATE_URL)) {
            wp_enqueue_style('wexoe-fonts', $font_css, [], null);
        }
    }

    /**
     * Bygg `<style>:root{…}</style>` ur default-grafikprofilen. Publik så att
     * t.ex. ett admin-preview eller en debug-vy kan återanvända samma block.
     */
    public static function css_variables_block() {
        $vars = self::build_vars(self::profile());
        $decls = '';
        foreach ($vars as $name => $value) {
            if ($value !== '') {
                $decls .= $name . ':' . $value . ';';
            }
        }
        return "<style id=\"wexoe-design-tokens\">:root{" . $decls . "}</style>\n";
    }

    /** Hämta default-grafikprofilen (eller [] om ingen finns). */
    private static function profile() {
        if (!class_exists(Singletons::class)) {
            return [];
        }
        $profile = Singletons::graphic_profile_for_division(null);
        return is_array($profile) ? $profile : [];
    }

    /**
     * Mappa profil-fält → CSS-variabler. Hex valideras via Color::normalize_hex
     * (ogiltig/tom → fallback). Font-namn saneras till CSS-säkra tecken.
     *
     * @return array<string,string>
     */
    private static function build_vars(array $p) {
        $hex = static function ($value, $fallback) {
            $normalized = class_exists(Color::class) ? Color::normalize_hex($value) : null;
            return $normalized ?: $fallback;
        };

        $vars = [
            '--wexoe-color-primary'        => $hex($p['color_primary'] ?? '', self::DEFAULTS['--wexoe-color-primary']),
            '--wexoe-color-secondary'      => $hex($p['color_secondary'] ?? '', self::DEFAULTS['--wexoe-color-secondary']),
            '--wexoe-color-accent'         => $hex($p['color_accent'] ?? '', self::DEFAULTS['--wexoe-color-accent']),
            '--wexoe-color-bg-light'       => $hex($p['color_background_light'] ?? '', self::DEFAULTS['--wexoe-color-bg-light']),
            '--wexoe-color-bg-dark'        => $hex($p['color_background_dark'] ?? '', self::DEFAULTS['--wexoe-color-bg-dark']),
            '--wexoe-color-text'           => $hex($p['color_text_primary'] ?? '', self::DEFAULTS['--wexoe-color-text']),
            '--wexoe-color-text-secondary' => $hex($p['color_text_secondary'] ?? '', self::DEFAULTS['--wexoe-color-text-secondary']),
        ];

        $heading = self::safe_font($p['font_heading'] ?? '');
        $body = self::safe_font($p['font_body'] ?? '');
        if ($heading !== '') {
            $vars['--wexoe-font-heading'] = $heading;
        }
        if ($body !== '') {
            $vars['--wexoe-font-body'] = $body;
        }
        return $vars;
    }

    /** Sanera ett font-family-värde till CSS-säkra tecken. */
    private static function safe_font($value) {
        $value = preg_replace('/[^A-Za-z0-9 ,\'"_-]/', '', (string) $value);
        return trim((string) $value);
    }
}
