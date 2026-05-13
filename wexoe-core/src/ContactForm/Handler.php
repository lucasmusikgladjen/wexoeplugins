<?php
namespace Wexoe\Core\ContactForm;

use Wexoe\Core\Core;
use Wexoe\Core\Logger;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX-handler för wxcf_submit. Tar emot form-data, validerar, rate-limit:ar
 * och skriver till user_submissions via Core::submission('user_submissions').
 *
 * Action-namn: `wxcf_submit` (medvetet skild från legacy `wexoe_contact_submit`).
 *
 * Anti-spam:
 *   - Nonce-verifiering (`wxcf_submit`).
 *   - Honeypot-fält `_hp` — silent success vid ifylld (boten tror submission lyckades).
 *   - Rate-limit: 10 submissions per IP per timme via transient.
 */
class Handler {

    const NONCE_ACTION = 'wxcf_submit';
    const RATE_LIMIT_MAX = 10;
    const RATE_LIMIT_WINDOW = HOUR_IN_SECONDS;

    public static function register(): void {
        add_action('wp_ajax_wxcf_submit',        [self::class, 'handle']);
        add_action('wp_ajax_nopriv_wxcf_submit', [self::class, 'handle']);
    }

    public static function handle(): void {
        // 1. Nonce
        $nonce = isset($_POST['_wxcf_nonce']) ? sanitize_text_field((string) $_POST['_wxcf_nonce']) : '';
        if (!wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            wp_send_json(['success' => false, 'error' => 'invalid_nonce'], 403);
        }

        // 2. Honeypot — silent success
        if (!empty($_POST['_hp'])) {
            Logger::info('wxcf_submit: honeypot tripped (silent success)');
            wp_send_json(['success' => true]);
        }

        // 3. Rate limit per IP
        $ip = self::client_ip();
        $rl_key = 'wxcf_rl_' . md5($ip);
        $count = (int) get_transient($rl_key);
        if ($count >= self::RATE_LIMIT_MAX) {
            Logger::warning('wxcf_submit: rate-limited', ['ip' => $ip, 'count' => $count]);
            wp_send_json(['success' => false, 'error' => 'rate_limited'], 429);
        }
        set_transient($rl_key, $count + 1, self::RATE_LIMIT_WINDOW);

        // 4. Sanitize input
        $email = sanitize_email((string) ($_POST['email'] ?? ''));
        if ($email === '' || !is_email($email)) {
            wp_send_json(['success' => false, 'error' => 'invalid_email'], 422);
        }

        $payload = [
            'submission_type'    => 'contact',
            'email'              => $email,
            'name'               => sanitize_text_field((string) ($_POST['name'] ?? '')),
            'company'            => sanitize_text_field((string) ($_POST['company'] ?? '')),
            'phone'              => sanitize_text_field((string) ($_POST['phone'] ?? '')),
            'message'            => sanitize_textarea_field((string) ($_POST['message'] ?? '')),
            'newsletter_consent' => !empty($_POST['newsletter_consent']),
            'submitted_at'       => current_time('c'),
            'page_slug'          => sanitize_text_field((string) ($_POST['page_slug'] ?? '')),
            'page_url'           => esc_url_raw((string) ($_POST['page_url'] ?? '')),
            'source_plugin'      => sanitize_text_field((string) ($_POST['source_plugin'] ?? 'wexoe-core')),
            'extra'              => [
                'behov'      => sanitize_text_field((string) ($_POST['behov'] ?? '')),
                'user_agent' => sanitize_text_field((string) ($_SERVER['HTTP_USER_AGENT'] ?? '')),
                'referer'    => esc_url_raw((string) ($_SERVER['HTTP_REFERER'] ?? '')),
                'ip'         => $ip,
            ],
        ];

        $writer = Core::submission('user_submissions');
        if ($writer === null) {
            Logger::error('wxcf_submit: user_submissions write-entity saknas');
            wp_send_json(['success' => false, 'error' => 'config_missing'], 500);
        }

        $result = $writer->create_mapped($payload);
        if (!empty($result['success'])) {
            Logger::info('wxcf_submit: ny submission skapad', [
                'source_plugin' => $payload['source_plugin'],
                'page_slug'     => $payload['page_slug'],
                'record_id'     => $result['record']['id'] ?? null,
            ]);
            wp_send_json(['success' => true]);
        }

        Logger::error('wxcf_submit: Airtable-skrivning misslyckades', [
            'error'      => $result['error'] ?? 'unknown',
            'error_type' => $result['error_type'] ?? 'unknown',
        ]);
        wp_send_json(['success' => false, 'error' => $result['error'] ?? 'write_failed'], 500);
    }

    /**
     * Best-effort IP-detektion. Trust proxy-headers OM WP är konfigurerad bakom
     * en känd proxy (sätts via `WEXOE_TRUSTED_PROXY`-konstant). Annars REMOTE_ADDR.
     */
    private static function client_ip(): string {
        if (defined('WEXOE_TRUSTED_PROXY') && WEXOE_TRUSTED_PROXY) {
            foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP'] as $h) {
                if (!empty($_SERVER[$h])) {
                    $first = explode(',', (string) $_SERVER[$h])[0];
                    return trim($first);
                }
            }
        }
        return isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
    }
}
