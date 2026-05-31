<?php
namespace Wexoe\Core\Admin;

use Wexoe\Core\Plugin;
use Wexoe\Core\Cache;
use Wexoe\Core\Logger;
use Wexoe\Core\Core;
use Wexoe\Core\SchemaRegistry;
use Wexoe\Core\EntityRepository;
use Wexoe\Core\RestApi;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * "Wexoe Cache" admin page — Tools → Wexoe Cache.
 *
 * Why this exists alongside Admin\Page:
 *   The original "Verktyg → Wexoe Core" page disables its "Rensa cache" button
 *   when Cache::count() == 0, but the plugin also persists a stale-fallback
 *   payload in wp_options (prefix "wexoe_core_stale_entity_"). So after the
 *   transient TTL passes, stale data keeps being served and the only button
 *   that could clear it is greyed out. This page provides a working
 *   always-clickable cache panel plus the webhook-secret setting that the
 *   Wexoe Builder uses to hit the /cache/clear REST endpoint after publish.
 */
class CacheTools {

    const MENU_SLUG = 'wexoe-cache-tools';
    const NONCE_ACTION = 'wexoe_cache_tools_action';
    const POST_ACTION = 'wexoe_cache_tools_action';

    const NOTICE_TRANSIENT_PREFIX = 'wexoe_cache_tools_notice_';
    const NOTICE_TTL = 60;

    /** @var CacheTools|null */
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function register() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_post_' . self::POST_ACTION, [$this, 'handle_action']);
    }

    public function add_menu() {
        add_management_page(
            'Wexoe Cache',
            'Wexoe Cache',
            'manage_options',
            self::MENU_SLUG,
            [$this, 'render']
        );
    }

    /* --------------------------------------------------------
       NOTICES
       -------------------------------------------------------- */

    private static function set_notice($type, $message) {
        $user_id = get_current_user_id();
        if ($user_id <= 0) return;
        set_transient(
            self::NOTICE_TRANSIENT_PREFIX . $user_id,
            ['type' => $type, 'message' => $message],
            self::NOTICE_TTL
        );
    }

    private static function consume_notice() {
        $user_id = get_current_user_id();
        if ($user_id <= 0) return null;
        $key = self::NOTICE_TRANSIENT_PREFIX . $user_id;
        $notice = get_transient($key);
        if ($notice) {
            delete_transient($key);
            return $notice;
        }
        return null;
    }

    /* --------------------------------------------------------
       RENDER
       -------------------------------------------------------- */

    public function render() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Du har inte behörighet.', 'wexoe-core'));
        }

        $notice = self::consume_notice();
        $cache_count = Cache::count();
        $stale_count = EntityRepository::count_all_stale_options();
        $has_anything = ($cache_count + $stale_count) > 0;
        $entities = SchemaRegistry::list_registered();
        $webhook_secret = Plugin::get_webhook_secret();
        $has_webhook_secret = !empty($webhook_secret);
        $masked_secret = Plugin::mask_api_key($webhook_secret);
        $webhook_url = rest_url(RestApi::ROUTE_NAMESPACE . '/cache/clear');
        ?>
        <div class="wrap">
            <h1>Wexoe Cache</h1>
            <p style="max-width: 720px; color: #555;">
                Rensa cache som servas av Wexoe Core. Den här sidan kompletterar
                <em>Verktyg → Wexoe Core</em> och visar både transient-cache och
                stale-fallback-poster i <code>wp_options</code> (prefix
                <code>wexoe_core_stale_entity_</code>). Knappen nedan är klickbar
                så länge minst en av dem innehåller data — vilket motsvarar att
                en redaktör fortfarande riskerar att se gammal data efter
                ändringar i Airtable.
            </p>

            <?php if ($notice): ?>
                <div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible">
                    <p><strong><?php echo esc_html($notice['message']); ?></strong></p>
                </div>
            <?php endif; ?>

            <h2 style="margin-top: 24px;">Cache-status</h2>
            <table class="widefat striped" style="max-width: 540px;">
                <tbody>
                    <tr>
                        <td style="width: 280px;"><strong>Transients (<code>wp_options</code>)</strong></td>
                        <td><?php echo (int) $cache_count; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Stale-fallback-poster</strong></td>
                        <td>
                            <?php echo (int) $stale_count; ?>
                            <?php if ($stale_count > 0 && $cache_count === 0): ?>
                                <span style="color:#EF9F27;font-weight:600;margin-left:8px;">
                                    ← det här är varför "Rensa cache" på den andra sidan är gråtonad
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p style="margin-top: 16px;">
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
                    <?php wp_nonce_field(self::NONCE_ACTION); ?>
                    <input type="hidden" name="action" value="<?php echo esc_attr(self::POST_ACTION); ?>">
                    <input type="hidden" name="op" value="clear_all">
                    <button
                        type="submit"
                        class="button button-primary button-large"
                        onclick="return confirm('<?php echo esc_js(__('Rensa all Core-cache (transients + stale-options)?', 'wexoe-core')); ?>');"
                        <?php echo $has_anything ? '' : 'disabled'; ?>
                    >Rensa all cache nu</button>
                </form>
            </p>

            <h2 style="margin-top: 32px;">Per entitet</h2>
            <?php if (empty($entities)): ?>
                <p style="color:#999;font-style:italic;">Inga entiteter registrerade.</p>
            <?php else: ?>
                <table class="widefat striped" style="max-width: 900px;">
                    <thead>
                        <tr>
                            <th style="width: 180px;">Namn</th>
                            <th style="width: 130px;">Records</th>
                            <th style="width: 200px;">Status</th>
                            <th>Åtgärd</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($entities as $name):
                        $repo = Core::entity($name);
                        if ($repo === null) {
                            ?>
                            <tr>
                                <td><code><?php echo esc_html($name); ?></code></td>
                                <td colspan="3" style="color:#EF4444;">Schema kunde inte laddas.</td>
                            </tr>
                            <?php
                            continue;
                        }
                        $status = $repo->get_cache_status();
                        $entity_has_anything = !empty($status['cached']) || !empty($status['has_stale']);
                    ?>
                        <tr>
                            <td><code><?php echo esc_html($name); ?></code></td>
                            <td>
                                <?php if (!empty($status['cached'])): ?>
                                    <?php echo (int) $status['record_count']; ?>
                                <?php elseif (!empty($status['has_stale'])): ?>
                                    <?php echo (int) $status['stale_record_count']; ?>
                                    <span style="color:#999;font-size:11px;">(stale)</span>
                                <?php else: ?>
                                    <span style="color:#999;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (empty($status['cached']) && empty($status['has_stale'])): ?>
                                    <span style="color:#999;">Inte cachad</span>
                                <?php elseif (empty($status['cached']) && !empty($status['has_stale'])): ?>
                                    <span style="color:#EF9F27;font-weight:600;">Stale-fallback aktiv</span>
                                <?php elseif (!empty($status['is_expired'])): ?>
                                    <span style="color:#EF9F27;font-weight:600;">Utgången</span>
                                <?php else: ?>
                                    <span style="color:#10B981;">Färsk</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
                                    <?php wp_nonce_field(self::NONCE_ACTION); ?>
                                    <input type="hidden" name="action" value="<?php echo esc_attr(self::POST_ACTION); ?>">
                                    <input type="hidden" name="op" value="clear_entity">
                                    <input type="hidden" name="entity" value="<?php echo esc_attr($name); ?>">
                                    <button type="submit" class="button button-small" <?php echo $entity_has_anything ? '' : 'disabled'; ?>>Rensa</button>
                                </form>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;margin-left:4px;">
                                    <?php wp_nonce_field(self::NONCE_ACTION); ?>
                                    <input type="hidden" name="action" value="<?php echo esc_attr(self::POST_ACTION); ?>">
                                    <input type="hidden" name="op" value="refresh_entity">
                                    <input type="hidden" name="entity" value="<?php echo esc_attr($name); ?>">
                                    <button type="submit" class="button button-small">Refresh från Airtable</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <h2 style="margin-top: 32px;">Builder-webhook</h2>
            <p style="max-width: 720px; color: #555;">
                När den här hemligheten är satt kan Wexoe Builder kalla
                <code>/cache/clear</code> efter en lyckad publicering så att
                redaktören ser ändringen direkt på sajten — utan att behöva gå in
                hit och klicka på en knapp. Sätt samma värde i builderns
                env-variabel <code>WEXOE_CORE_WEBHOOK_SECRET</code> (Vercel
                project settings) och peka <code>WEXOE_CORE_WEBHOOK_URL</code>
                till:<br>
                <code><?php echo esc_html($webhook_url); ?></code>
            </p>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width: 720px;">
                <?php wp_nonce_field(self::NONCE_ACTION); ?>
                <input type="hidden" name="action" value="<?php echo esc_attr(self::POST_ACTION); ?>">
                <input type="hidden" name="op" value="save_webhook_secret">

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="wexoe_core_webhook_secret">Webhook-secret</label></th>
                        <td>
                            <input
                                type="password"
                                id="wexoe_core_webhook_secret"
                                name="webhook_secret"
                                value=""
                                class="regular-text"
                                placeholder="<?php echo $has_webhook_secret ? esc_attr__('Lämna tom för att behålla nuvarande', 'wexoe-core') : 'minst 24 tecken'; ?>"
                                autocomplete="off"
                                spellcheck="false"
                            >
                            <p class="description">
                                <?php if ($has_webhook_secret): ?>
                                    Aktuellt värde: <code><?php echo esc_html($masked_secret); ?></code> ✓
                                    <br>Fältet är tomt av säkerhetsskäl.
                                <?php else: ?>
                                    Inte konfigurerat ännu. Webhook-endpointen avvisar alla anrop tills detta sätts.
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary">Spara webhook-secret</button>
                    <?php if ($has_webhook_secret): ?>
                        <button
                            type="submit"
                            name="clear_secret"
                            value="1"
                            class="button"
                            style="margin-left: 8px;"
                            onclick="return confirm('<?php echo esc_js(__('Ta bort webhook-secret? Builder kommer inte längre kunna rensa cache.', 'wexoe-core')); ?>');"
                        >Rensa secret</button>
                    <?php endif; ?>
                </p>
            </form>
        </div>
        <?php
    }

    /* --------------------------------------------------------
       ACTIONS
       -------------------------------------------------------- */

    public function handle_action() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Du har inte behörighet.', 'wexoe-core'));
        }
        check_admin_referer(self::NONCE_ACTION);

        $op = isset($_POST['op']) ? sanitize_key(wp_unslash($_POST['op'])) : '';
        switch ($op) {
            case 'clear_all':           $this->op_clear_all(); break;
            case 'clear_entity':        $this->op_clear_entity(); break;
            case 'refresh_entity':      $this->op_refresh_entity(); break;
            case 'save_webhook_secret': $this->op_save_webhook_secret(); break;
            default:
                self::set_notice('error', __('Okänd operation.', 'wexoe-core'));
        }

        wp_redirect(admin_url('tools.php?page=' . self::MENU_SLUG));
        exit;
    }

    private function op_clear_all() {
        $deleted = Cache::clear_all();
        $stale_deleted = EntityRepository::clear_all_stale_options();
        Logger::info('Cache cleared via Wexoe Cache page', [
            'deleted_count' => $deleted,
            'stale_deleted_count' => $stale_deleted,
        ]);
        self::set_notice('success', sprintf(
            __('Cache rensad. %d transients och %d stale-poster borttagna.', 'wexoe-core'),
            $deleted,
            $stale_deleted
        ));
    }

    private function op_clear_entity() {
        $name = isset($_POST['entity']) ? sanitize_key(wp_unslash($_POST['entity'])) : '';
        $repo = Core::entity($name);
        if ($repo === null) {
            self::set_notice('error', sprintf(__('Entitet "%s" hittades inte.', 'wexoe-core'), $name));
            return;
        }
        $deleted = $repo->clear_cache();
        self::set_notice('success', sprintf(
            __('Cache rensad för "%s". %d transient(s) borttagna (+ stale-options).', 'wexoe-core'),
            $name,
            $deleted
        ));
    }

    private function op_refresh_entity() {
        $name = isset($_POST['entity']) ? sanitize_key(wp_unslash($_POST['entity'])) : '';
        $repo = Core::entity($name);
        if ($repo === null) {
            self::set_notice('error', sprintf(__('Entitet "%s" hittades inte.', 'wexoe-core'), $name));
            return;
        }
        $records = $repo->force_refresh();
        self::set_notice('success', sprintf(
            __('Entitet "%s" refreshad — hämtade %d records från Airtable.', 'wexoe-core'),
            $name,
            count($records)
        ));
    }

    private function op_save_webhook_secret() {
        if (isset($_POST['clear_secret']) && $_POST['clear_secret'] === '1') {
            Plugin::delete_webhook_secret();
            self::set_notice('warning', __('Webhook-secret borttagen.', 'wexoe-core'));
            return;
        }
        $secret = isset($_POST['webhook_secret']) ? trim(wp_unslash($_POST['webhook_secret'])) : '';
        if ($secret === '') {
            self::set_notice('info', __('Inget värde angivet — befintlig secret oförändrad.', 'wexoe-core'));
            return;
        }
        if (strlen($secret) < 24) {
            self::set_notice('error', __('Webhook-secret måste vara minst 24 tecken.', 'wexoe-core'));
            return;
        }
        Plugin::set_webhook_secret($secret);
        self::set_notice('success', __('Webhook-secret sparad.', 'wexoe-core'));
    }

    /* --------------------------------------------------------
       PATCH FOR THE OLD PAGE'S DISABLED BUTTON
       -------------------------------------------------------- */

    /**
     * The original Admin\Page disables "Rensa all cache" when Cache::count()
     * is zero — even when stale-options still exist. Until that file is
     * patched directly, inject a tiny script on its admin page that flips
     * the disabled attribute when our own EntityRepository sees stale rows.
     */
    public static function patch_legacy_page_button() {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->id !== 'tools_page_' . Page::MENU_SLUG) {
            return;
        }
        $stale = EntityRepository::count_all_stale_options();
        if ($stale <= 0) {
            return;
        }
        ?>
        <script>
        (function () {
            // Find the "Rensa all cache" button in the original Wexoe Core page
            // and re-enable it when wp_options still contains stale entries —
            // those are what's serving stale data even though Cache::count()==0.
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('input[name="op"][value="clear_cache"]').forEach(function (input) {
                    var form = input.closest('form');
                    if (!form) return;
                    var btn = form.querySelector('button[type="submit"]');
                    if (btn && btn.disabled) {
                        btn.disabled = false;
                        btn.title = 'Aktiverad av Wexoe Cache: <?php echo (int) $stale; ?> stale-fallback-poster i wp_options';
                    }
                });
                // Add a small banner pointing to the new page.
                var wrap = document.querySelector('.wrap');
                if (wrap) {
                    var note = document.createElement('div');
                    note.className = 'notice notice-warning';
                    note.style.maxWidth = '720px';
                    note.innerHTML = '<p><strong>OBS:</strong> Det finns <?php echo (int) $stale; ?> stale-fallback-poster (wp_options) som inte räknas in i transient-cachen. ' +
                        '"Rensa all cache" har aktiverats automatiskt. För full kontroll, se <a href="<?php echo esc_url(admin_url('tools.php?page=' . self::MENU_SLUG)); ?>">Verktyg → Wexoe Cache</a>.</p>';
                    wrap.insertBefore(note, wrap.firstChild.nextSibling);
                }
            });
        })();
        </script>
        <?php
    }
}
