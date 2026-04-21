<?php
namespace Wexoe\Core\Admin;

use Wexoe\Core\Plugin;
use Wexoe\Core\AirtableClient;
use Wexoe\Core\Cache;
use Wexoe\Core\Logger;
use Wexoe\Core\SchemaRegistry;
use Wexoe\Core\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin page — Phase 2 adds the Entities section (schema registry, cache per entity,
 * inspect, force-refresh).
 *
 * Rendering is split into section methods so this file stays navigable.
 */
class Page {

    const MENU_SLUG = 'wexoe-core';
    const NONCE_ACTION = 'wexoe_core_admin_action';
    const POST_ACTION = 'wexoe_core_admin_action';

    const NOTICE_TRANSIENT_PREFIX = 'wexoe_core_notice_';
    const NOTICE_TTL = 60;

    const TEST_RESULT_TRANSIENT_PREFIX = 'wexoe_core_test_result_';
    const TEST_RESULT_TTL = 300;
    const SCHEMA_HEALTH_TRANSIENT_PREFIX = 'wexoe_core_schema_health_';
    const SCHEMA_HEALTH_TTL = 300;

    const HELPER_RESULT_TRANSIENT_PREFIX = 'wexoe_core_helper_result_';
    const HELPER_RESULT_TTL = 300;

    /** @var Page|null */
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function register() {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_post_' . self::POST_ACTION, [$this, 'handle_action']);
    }

    public function add_menu_page() {
        add_management_page(
            'Wexoe Core',
            'Wexoe Core',
            'manage_options',
            self::MENU_SLUG,
            [$this, 'render_page']
        );
    }

    /* --------------------------------------------------------
       FLASH NOTICES
       -------------------------------------------------------- */

    private static function set_notice($type, $message) {
        $user_id = get_current_user_id();
        if ($user_id <= 0) return false;
        return set_transient(
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

    private static function set_test_result($result) {
        $user_id = get_current_user_id();
        if ($user_id <= 0) return false;
        return set_transient(
            self::TEST_RESULT_TRANSIENT_PREFIX . $user_id,
            $result,
            self::TEST_RESULT_TTL
        );
    }

    private static function get_test_result() {
        $user_id = get_current_user_id();
        if ($user_id <= 0) return null;
        return get_transient(self::TEST_RESULT_TRANSIENT_PREFIX . $user_id);
    }

    private static function clear_test_result() {
        $user_id = get_current_user_id();
        if ($user_id <= 0) return;
        delete_transient(self::TEST_RESULT_TRANSIENT_PREFIX . $user_id);
    }

    private static function set_schema_health_result($result) {
        $user_id = get_current_user_id();
        if ($user_id <= 0) return false;
        return set_transient(
            self::SCHEMA_HEALTH_TRANSIENT_PREFIX . $user_id,
            $result,
            self::SCHEMA_HEALTH_TTL
        );
    }

    private static function get_schema_health_result() {
        $user_id = get_current_user_id();
        if ($user_id <= 0) return null;
        return get_transient(self::SCHEMA_HEALTH_TRANSIENT_PREFIX . $user_id);
    }

    private static function set_helper_result($result) {
        $user_id = get_current_user_id();
        if ($user_id <= 0) return false;
        return set_transient(
            self::HELPER_RESULT_TRANSIENT_PREFIX . $user_id,
            $result,
            self::HELPER_RESULT_TTL
        );
    }

    private static function get_helper_result() {
        $user_id = get_current_user_id();
        if ($user_id <= 0) return null;
        return get_transient(self::HELPER_RESULT_TRANSIENT_PREFIX . $user_id);
    }

    /* --------------------------------------------------------
       MAIN RENDER
       -------------------------------------------------------- */

    public function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Du har inte behörighet att se denna sida.', 'wexoe-core'));
        }

        $notice = self::consume_notice();
        ?>
        <div class="wrap">
            <h1>Wexoe Core</h1>
            <p style="max-width: 720px; color: #555;">
                Unified Airtable data layer. Kontrollpanel för API-konfiguration, anslutningstest, entiteter, cache och loggar.
            </p>

            <?php if ($notice): ?>
                <div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible">
                    <p><strong><?php echo esc_html($notice['message']); ?></strong></p>
                </div>
            <?php endif; ?>

            <?php
            $this->render_diagnostics();
            $this->render_settings();
            $this->render_connection_test();
            $this->render_schema_health();
            $this->render_entities();
            $this->render_helpers();
            $this->render_cache();
            $this->render_logs();
            $this->render_roadmap();
            ?>
        </div>
        <?php
    }

    /* --------------------------------------------------------
       SECTION RENDERERS
       -------------------------------------------------------- */

    private function render_diagnostics() {
        $api_key = Plugin::get_api_key();
        $masked_key = Plugin::mask_api_key($api_key);
        $has_key = !empty($api_key);
        $base_id = Plugin::get_base_id();
        $has_base_id = !empty($base_id);
        $cache_count = Cache::count();
        $log_counts = Logger::count_by_level();
        $entity_count = count(SchemaRegistry::list_registered());
        ?>
        <h2 style="margin-top: 30px;">Diagnostik</h2>
        <table class="widefat striped" style="max-width: 720px;">
            <tbody>
                <tr>
                    <td style="width: 200px;"><strong>Plugin-version</strong></td>
                    <td><code><?php echo esc_html(WEXOE_CORE_VERSION); ?></code></td>
                </tr>
                <tr>
                    <td><strong>Fas</strong></td>
                    <td>4 — landing page-migration + find_by_ids</td>
                </tr>
                <tr>
                    <td><strong>PHP-version</strong></td>
                    <td>
                        <code><?php echo esc_html(PHP_VERSION); ?></code>
                        <?php echo version_compare(PHP_VERSION, '7.4', '>=')
                            ? '<span style="color:#10B981;font-weight:600;">✓</span>'
                            : '<span style="color:#EF4444;font-weight:600;">Kräver 7.4+</span>'; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>WordPress-version</strong></td>
                    <td>
                        <code><?php echo esc_html(get_bloginfo('version')); ?></code>
                        <?php echo version_compare(get_bloginfo('version'), '6.0', '>=')
                            ? '<span style="color:#10B981;font-weight:600;">✓</span>'
                            : '<span style="color:#EF4444;font-weight:600;">Kräver 6.0+</span>'; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Airtable API-nyckel</strong></td>
                    <td>
                        <?php if ($has_key): ?>
                            <code><?php echo esc_html($masked_key); ?></code>
                            &nbsp;<span style="color:#10B981;font-weight:600;">✓ Satt</span>
                        <?php else: ?>
                            <span style="color:#EF4444;font-weight:600;">Ej satt</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Airtable Base ID</strong></td>
                    <td>
                        <?php if ($has_base_id): ?>
                            <code><?php echo esc_html($base_id); ?></code>
                            &nbsp;<span style="color:#10B981;font-weight:600;">✓ Satt</span>
                        <?php else: ?>
                            <span style="color:#EF4444;font-weight:600;">Ej satt</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Registrerade entiteter</strong></td>
                    <td><?php echo (int) $entity_count; ?></td>
                </tr>
                <tr>
                    <td><strong>Cache</strong></td>
                    <td><?php echo (int) $cache_count; ?> poster</td>
                </tr>
                <tr>
                    <td><strong>Logg</strong></td>
                    <td>
                        <?php printf(
                            '%d poster totalt (%d info, %d varningar, %d fel)',
                            (int) $log_counts['total'],
                            (int) $log_counts['info'],
                            (int) $log_counts['warning'],
                            (int) $log_counts['error']
                        ); ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    private function render_settings() {
        $api_key = Plugin::get_api_key();
        $has_key = !empty($api_key);
        $base_id = Plugin::get_base_id();
        ?>
        <h2 style="margin-top: 40px;">Inställningar</h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width: 720px;">
            <?php wp_nonce_field(self::NONCE_ACTION); ?>
            <input type="hidden" name="action" value="<?php echo esc_attr(self::POST_ACTION); ?>">
            <input type="hidden" name="op" value="save_settings">

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="wexoe_core_api_key">Airtable API-nyckel</label></th>
                    <td>
                        <input
                            type="password"
                            id="wexoe_core_api_key"
                            name="api_key"
                            value=""
                            class="regular-text"
                            placeholder="<?php echo $has_key ? esc_attr__('Lämna tom för att behålla nuvarande', 'wexoe-core') : 'patXXXXXXXXXXXXXX.YYYYYY'; ?>"
                            autocomplete="off"
                            spellcheck="false"
                        >
                        <p class="description">
                            Personal Access Token (PAT). PAT behöver scopen <code>data.records:read</code> och <code>schema.bases:read</code>.
                            <?php if ($has_key): ?>
                                <br><strong>Fältet är tomt av säkerhetsskäl.</strong> Lämna tomt för att behålla nuvarande.
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="wexoe_core_base_id">Airtable Base ID</label></th>
                    <td>
                        <input
                            type="text"
                            id="wexoe_core_base_id"
                            name="base_id"
                            value="<?php echo esc_attr($base_id); ?>"
                            class="regular-text"
                            placeholder="appXXXXXXXXXXXXXX"
                            spellcheck="false"
                        >
                        <p class="description">Base ID för din Airtable-databas.</p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary">Spara inställningar</button>
                <?php if ($has_key): ?>
                    <button
                        type="submit"
                        name="clear_key"
                        value="1"
                        class="button"
                        style="margin-left: 8px;"
                        onclick="return confirm('<?php echo esc_js(__('Är du säker? Alla plugins som använder Core kommer sluta kunna hämta data.', 'wexoe-core')); ?>');"
                    >
                        Rensa nyckel
                    </button>
                <?php endif; ?>
            </p>
        </form>
        <?php
    }

    private function render_connection_test() {
        $has_key = !empty(Plugin::get_api_key());
        $has_base_id = !empty(Plugin::get_base_id());
        $test_result = self::get_test_result();
        ?>
        <h2 style="margin-top: 40px;">Anslutningstest</h2>
        <p style="max-width: 720px; color: #555;">
            Testar om API-nyckel och base ID fungerar genom att hämta listan över tabeller i basen.
        </p>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field(self::NONCE_ACTION); ?>
            <input type="hidden" name="action" value="<?php echo esc_attr(self::POST_ACTION); ?>">
            <input type="hidden" name="op" value="test_connection">
            <button type="submit" class="button button-primary" <?php echo (!$has_key || !$has_base_id) ? 'disabled' : ''; ?>>
                Testa anslutning
            </button>
            <?php if (!$has_key || !$has_base_id): ?>
                <span style="margin-left: 10px; color: #999; font-style: italic;">
                    Kräver att både API-nyckel och base ID är satta.
                </span>
            <?php endif; ?>
        </form>

        <?php if ($test_result): ?>
            <div style="max-width: 720px; margin-top: 20px; padding: 16px 20px; background: <?php echo $test_result['success'] ? '#f0fdf4' : '#fef2f2'; ?>; border-left: 4px solid <?php echo $test_result['success'] ? '#10B981' : '#EF4444'; ?>; border-radius: 4px;">
                <strong style="font-size: 15px;">
                    <?php echo $test_result['success'] ? '✓ Anslutning OK' : '✗ Anslutning misslyckades'; ?>
                </strong>
                <div style="margin-top: 8px; font-size: 13px;">
                    <?php if ($test_result['success']): ?>
                        Hittade <strong><?php echo (int) $test_result['table_count']; ?></strong> tabeller i basen.
                        <?php if (!empty($test_result['table_names'])): ?>
                            <details style="margin-top: 6px;">
                                <summary style="cursor: pointer; color: #555;">Visa tabeller</summary>
                                <ul style="margin: 6px 0 0 20px; font-family: monospace; font-size: 12px;">
                                    <?php foreach ($test_result['table_names'] as $name): ?>
                                        <li><?php echo esc_html($name); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </details>
                        <?php endif; ?>
                    <?php else: ?>
                        <div><strong>Fel:</strong> <?php echo esc_html($test_result['error']); ?></div>
                        <?php if (!empty($test_result['error_type'])): ?>
                            <div style="margin-top: 4px; color: #666;">
                                Typ: <code><?php echo esc_html($test_result['error_type']); ?></code>
                                <?php if (!empty($test_result['http_code'])): ?>
                                    &middot; HTTP-kod: <code><?php echo (int) $test_result['http_code']; ?></code>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div style="margin-top: 8px; font-size: 12px; color: #666;">
                            <?php echo esc_html(self::error_hint($test_result['error_type'] ?? '')); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div style="margin-top: 10px; font-size: 11px; color: #999;">
                    Tid: <?php echo esc_html(date('Y-m-d H:i:s', (int) $test_result['timestamp'])); ?>
                </div>
            </div>
        <?php endif; ?>
        <?php
    }

    private function render_schema_health() {
        $has_key = !empty(Plugin::get_api_key());
        $has_base_id = !empty(Plugin::get_base_id());
        $result = self::get_schema_health_result();
        ?>
        <h2 style="margin-top: 40px;">Schema health check</h2>
        <p style="max-width: 720px; color: #555;">
            Jämför Core-scheman mot Airtable metadata (tabeller + fältnamn) för att hitta mismatch tidigt.
        </p>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field(self::NONCE_ACTION); ?>
            <input type="hidden" name="action" value="<?php echo esc_attr(self::POST_ACTION); ?>">
            <input type="hidden" name="op" value="schema_health_check">
            <button type="submit" class="button" <?php echo (!$has_key || !$has_base_id) ? 'disabled' : ''; ?>>
                Kör schema health check
            </button>
        </form>

        <?php if ($result): ?>
            <?php $ok = (int) $result['errors'] === 0; ?>
            <div style="max-width: 900px; margin-top: 16px; padding: 16px 20px; background: <?php echo $ok ? '#f0fdf4' : '#fef2f2'; ?>; border-left: 4px solid <?php echo $ok ? '#10B981' : '#EF4444'; ?>; border-radius: 4px;">
                <strong><?php echo $ok ? '✓ Inga schemafel hittades' : '✗ Schemafel hittades'; ?></strong>
                <div style="margin-top: 6px; color:#555;">
                    Entiteter: <strong><?php echo (int) $result['entities_checked']; ?></strong> ·
                    Fel: <strong><?php echo (int) $result['errors']; ?></strong> ·
                    Varningar: <strong><?php echo (int) $result['warnings']; ?></strong>
                </div>
            </div>

            <?php if (!empty($result['items'])): ?>
                <table class="widefat striped" style="max-width: 900px; margin-top: 12px;">
                    <thead>
                        <tr>
                            <th style="width:140px;">Entitet</th>
                            <th style="width:90px;">Status</th>
                            <th>Meddelande</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result['items'] as $item): ?>
                            <tr>
                                <td><code><?php echo esc_html($item['entity']); ?></code></td>
                                <td>
                                    <span style="font-weight:600; color: <?php echo $item['status'] === 'error' ? '#EF4444' : ($item['status'] === 'warning' ? '#EF9F27' : '#10B981'); ?>;">
                                        <?php echo esc_html(strtoupper($item['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($item['message']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
        <?php
    }

    /**
     * Entity section — lists registered entities with cache status and actions.
     */
    private function render_entities() {
        $entities = SchemaRegistry::list_registered();
        $inspect_name = isset($_GET['inspect']) ? sanitize_key(wp_unslash($_GET['inspect'])) : '';
        $inspect_repo = $inspect_name !== '' ? Core::entity($inspect_name) : null;
        ?>
        <h2 style="margin-top: 40px;">Entiteter</h2>
        <p style="max-width: 720px; color: #555;">
            Registrerade entiteter (schemafiler i <code>entities/</code>).
            Varje entity kan inspekteras, cache-rensas, eller force-refresh:as.
        </p>

        <?php if (empty($entities)): ?>
            <div style="max-width: 720px; padding: 16px; background: #fff; border-left: 4px solid #EF9F27; border-radius: 4px;">
                <strong>Inga entiteter registrerade.</strong>
                <p style="margin: 4px 0 0 0; font-size: 13px; color: #666;">
                    Skapa schemafiler i <code><?php echo esc_html(WEXOE_CORE_PATH . 'entities/'); ?></code>.
                </p>
            </div>
        <?php else: ?>
            <table class="widefat striped" style="max-width: 900px;">
                <thead>
                    <tr>
                        <th style="width: 140px;">Namn</th>
                        <th style="width: 140px;">Table ID</th>
                        <th style="width: 110px;">Primärnyckel</th>
                        <th style="width: 100px;">Records</th>
                        <th style="width: 140px;">Cache-status</th>
                        <th>Åtgärder</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entities as $name):
                        $repo = Core::entity($name);
                        if ($repo === null) {
                            // Schema file exists but didn't load — show error row
                            ?>
                            <tr>
                                <td><code><?php echo esc_html($name); ?></code></td>
                                <td colspan="5" style="color:#EF4444;">Schema kunde inte läsas. Se loggen.</td>
                            </tr>
                            <?php
                            continue;
                        }

                        $status = $repo->get_cache_status();
                        $table_id = $repo->get_table_id();
                        $pk = $repo->get_primary_key();

                        $inspect_url = add_query_arg('inspect', $name, admin_url('tools.php?page=' . self::MENU_SLUG)) . '#inspect';
                    ?>
                        <tr>
                            <td><code><?php echo esc_html($name); ?></code></td>
                            <td><code style="font-size:11px;"><?php echo esc_html($table_id); ?></code></td>
                            <td><code style="font-size:11px;"><?php echo esc_html($pk ?: '—'); ?></code></td>
                            <td>
                                <?php if ($status['cached']): ?>
                                    <?php echo (int) $status['record_count']; ?>
                                <?php else: ?>
                                    <span style="color:#999;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$status['cached']): ?>
                                    <span style="color:#999;">Inte cachad</span>
                                <?php elseif ($status['is_expired']): ?>
                                    <span style="color:#EF9F27;font-weight:600;">Utgången</span>
                                <?php else:
                                    $age_min = max(1, (int) ((time() - $status['cached_at']) / 60));
                                    $expires_in_h = max(0, round(($status['expires_at'] - time()) / 3600, 1));
                                ?>
                                    <span style="color:#10B981;">Färsk</span>
                                    <span style="color:#999;font-size:11px;"><?php echo esc_html($age_min); ?>m / <?php echo esc_html($expires_in_h); ?>h kvar</span>
                                <?php endif; ?>
                            </td>
                            <td style="white-space: nowrap;">
                                <a href="<?php echo esc_url($inspect_url); ?>" class="button button-small">Inspektera</a>

                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;margin-left:4px;">
                                    <?php wp_nonce_field(self::NONCE_ACTION); ?>
                                    <input type="hidden" name="action" value="<?php echo esc_attr(self::POST_ACTION); ?>">
                                    <input type="hidden" name="op" value="entity_refresh">
                                    <input type="hidden" name="entity" value="<?php echo esc_attr($name); ?>">
                                    <button type="submit" class="button button-small">Refresh</button>
                                </form>

                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;margin-left:4px;">
                                    <?php wp_nonce_field(self::NONCE_ACTION); ?>
                                    <input type="hidden" name="action" value="<?php echo esc_attr(self::POST_ACTION); ?>">
                                    <input type="hidden" name="op" value="entity_clear_cache">
                                    <input type="hidden" name="entity" value="<?php echo esc_attr($name); ?>">
                                    <button
                                        type="submit"
                                        class="button button-small"
                                        <?php echo !$status['cached'] ? 'disabled' : ''; ?>
                                    >Rensa cache</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if ($inspect_repo !== null): ?>
            <?php $this->render_inspection($inspect_repo); ?>
        <?php elseif ($inspect_name !== '' && $inspect_repo === null): ?>
            <div style="max-width: 900px; margin-top: 20px; padding: 16px; background: #fef2f2; border-left: 4px solid #EF4444; border-radius: 4px;">
                Entiteten <code><?php echo esc_html($inspect_name); ?></code> hittades inte eller kunde inte laddas.
            </div>
        <?php endif; ?>
        <?php
    }

    /**
     * Inspection panel — detailed view of one entity.
     */
    private function render_inspection($repo) {
        $name = $repo->get_name();
        $schema = $repo->get_schema();
        $records = $repo->all();
        $close_url = remove_query_arg('inspect', admin_url('tools.php?page=' . self::MENU_SLUG));
        ?>
        <div id="inspect" style="max-width: 900px; margin-top: 24px; padding: 20px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <h3 style="margin: 0;">Inspektera: <code><?php echo esc_html($name); ?></code></h3>
                <a href="<?php echo esc_url($close_url); ?>" class="button button-small">Stäng</a>
            </div>

            <p style="color: #666; margin-top: 8px;">
                <strong><?php echo count($records); ?></strong> records hämtade.
                Primärnyckel: <code><?php echo esc_html($repo->get_primary_key() ?: '—'); ?></code> ·
                Table ID: <code><?php echo esc_html($repo->get_table_id()); ?></code> ·
                TTL: <?php echo esc_html($repo->get_ttl()); ?>s
            </p>

            <details style="margin-top: 12px;">
                <summary style="cursor: pointer; font-weight: 600; color: #11325D;">Schema</summary>
                <pre style="margin: 8px 0 0 0; padding: 12px; background: #f6f7f7; border-radius: 4px; font-size: 11px; max-height: 300px; overflow: auto;"><?php echo esc_html(wp_json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?></pre>
            </details>

            <details open style="margin-top: 12px;">
                <summary style="cursor: pointer; font-weight: 600; color: #11325D;">
                    Första 3 records (normaliserade)
                </summary>
                <?php if (empty($records)): ?>
                    <p style="margin-top: 8px; color: #999; font-style: italic;">
                        Inga records — kontrollera loggen för detaljer.
                    </p>
                <?php else: ?>
                    <pre style="margin: 8px 0 0 0; padding: 12px; background: #f6f7f7; border-radius: 4px; font-size: 11px; max-height: 500px; overflow: auto;"><?php echo esc_html(wp_json_encode(array_slice($records, 0, 3), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?></pre>
                <?php endif; ?>
            </details>

            <?php if (count($records) > 3): ?>
                <details style="margin-top: 12px;">
                    <summary style="cursor: pointer; font-weight: 600; color: #11325D;">
                        Alla <?php echo count($records); ?> records
                    </summary>
                    <pre style="margin: 8px 0 0 0; padding: 12px; background: #f6f7f7; border-radius: 4px; font-size: 11px; max-height: 600px; overflow: auto;"><?php echo esc_html(wp_json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?></pre>
                </details>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Helpers section — Markdown, Color, YouTube, Lines utilities with a
     * one-click smoke-test runner.
     */
    private function render_helpers() {
        $result = self::get_helper_result();
        ?>
        <h2 style="margin-top: 40px;">Helpers</h2>
        <p style="max-width: 720px; color: #555;">
            Återanvändbara utilities (Markdown, Color, YouTube, Lines) tillgängliga via <code>Wexoe\Core\Helpers\*</code>.
            Kör inbyggda smoke-tester för att verifiera att alla fungerar korrekt i din miljö.
        </p>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field(self::NONCE_ACTION); ?>
            <input type="hidden" name="action" value="<?php echo esc_attr(self::POST_ACTION); ?>">
            <input type="hidden" name="op" value="run_helper_tests">
            <button type="submit" class="button button-primary">Kör helper-tester</button>
        </form>

        <?php if ($result): ?>
            <?php $this->render_helper_results($result); ?>
        <?php endif; ?>
        <?php
    }

    /**
     * Render the helper-test results panel.
     */
    private function render_helper_results($result) {
        $summary = $result['summary'];
        $results = $result['results'];
        $all_passed = $summary['failed'] === 0;
        ?>
        <div style="max-width: 900px; margin-top: 20px; padding: 16px 20px; background: <?php echo $all_passed ? '#f0fdf4' : '#fef2f2'; ?>; border-left: 4px solid <?php echo $all_passed ? '#10B981' : '#EF4444'; ?>; border-radius: 4px;">
            <strong style="font-size: 15px;">
                <?php if ($all_passed): ?>
                    ✓ Alla <?php echo (int) $summary['total']; ?> tester passerade
                <?php else: ?>
                    ✗ <?php echo (int) $summary['failed']; ?> av <?php echo (int) $summary['total']; ?> tester misslyckades
                <?php endif; ?>
            </strong>
            <div style="margin-top: 6px; font-size: 12px; color: #666;">
                Tid: <?php echo esc_html(date('Y-m-d H:i:s', (int) $result['timestamp'])); ?>
            </div>
        </div>

        <?php foreach ($results as $group => $cases):
            $group_passed = 0;
            $group_total = count($cases);
            foreach ($cases as $c) {
                if ($c['passed']) $group_passed++;
            }
            $group_ok = ($group_passed === $group_total);
        ?>
            <div style="max-width: 900px; margin-top: 16px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
                <div style="padding: 10px 14px; background: #f6f7f7; border-bottom: 1px solid #ccd0d4; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                    <span style="color: <?php echo $group_ok ? '#10B981' : '#EF4444'; ?>;">
                        <?php echo $group_ok ? '✓' : '✗'; ?>
                    </span>
                    <span><?php echo esc_html($group); ?></span>
                    <span style="color: #999; font-weight: normal; font-size: 12px;">
                        (<?php echo $group_passed; ?>/<?php echo $group_total; ?>)
                    </span>
                </div>
                <div>
                    <?php foreach ($cases as $case): ?>
                        <div style="padding: 8px 14px; border-bottom: 1px solid #f0f0f1; font-size: 12px; display: flex; align-items: flex-start; gap: 10px;">
                            <span style="color: <?php echo $case['passed'] ? '#10B981' : '#EF4444'; ?>; font-weight: 600; flex-shrink: 0; width: 16px;">
                                <?php echo $case['passed'] ? '✓' : '✗'; ?>
                            </span>
                            <div style="flex: 1;">
                                <div style="color: #11325D;"><?php echo esc_html($case['label']); ?></div>
                                <?php if (!$case['passed']): ?>
                                    <div style="margin-top: 4px; font-family: monospace; font-size: 11px; color: #666;">
                                        <div><strong>actual:</strong> <?php echo esc_html($case['actual']); ?></div>
                                        <div><strong>expected:</strong> <?php echo esc_html($case['expected']); ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php
    }

    private function render_cache() {
        $cache_count = Cache::count();
        ?>
        <h2 style="margin-top: 40px;">Cache</h2>
        <p style="max-width: 720px; color: #555;">
            Lokal cache i WP transients (prefix: <code>wexoe_core_</code>). Antal: <strong><?php echo (int) $cache_count; ?></strong>.
        </p>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: inline;">
            <?php wp_nonce_field(self::NONCE_ACTION); ?>
            <input type="hidden" name="action" value="<?php echo esc_attr(self::POST_ACTION); ?>">
            <input type="hidden" name="op" value="test_cache">
            <button type="submit" class="button">Testa cache (skriv + läs)</button>
        </form>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: inline; margin-left: 8px;">
            <?php wp_nonce_field(self::NONCE_ACTION); ?>
            <input type="hidden" name="action" value="<?php echo esc_attr(self::POST_ACTION); ?>">
            <input type="hidden" name="op" value="clear_cache">
            <button
                type="submit"
                class="button"
                onclick="return confirm('<?php echo esc_js(__('Rensa all Core-cache?', 'wexoe-core')); ?>');"
                <?php echo ($cache_count === 0) ? 'disabled' : ''; ?>
            >
                Rensa all cache
            </button>
        </form>
        <?php
    }

    private function render_logs() {
        $log_counts = Logger::count_by_level();
        $log_filter = isset($_GET['log_level']) ? sanitize_text_field(wp_unslash($_GET['log_level'])) : '';
        $log_filter = in_array($log_filter, ['info', 'warning', 'error'], true) ? $log_filter : null;
        $log_entries = Logger::get_entries(100, $log_filter);
        ?>
        <h2 style="margin-top: 40px;">Loggar</h2>
        <p style="max-width: 720px; color: #555;">
            Senaste <?php echo (int) Logger::MAX_ENTRIES; ?> händelserna (ring-buffer). Nyaste först.
        </p>

        <div style="margin-bottom: 12px;">
            <?php
            $base_url = admin_url('tools.php?page=' . self::MENU_SLUG);
            $levels = [
                '' => ['Alla', (int) $log_counts['total']],
                'info' => ['Info', (int) $log_counts['info']],
                'warning' => ['Varningar', (int) $log_counts['warning']],
                'error' => ['Fel', (int) $log_counts['error']],
            ];
            foreach ($levels as $level_key => $info) {
                $url = $level_key === '' ? $base_url : add_query_arg('log_level', $level_key, $base_url);
                $is_active = ($log_filter === null && $level_key === '') || ($log_filter === $level_key);
                $style = $is_active
                    ? 'background:#11325D;color:#fff;'
                    : 'background:#f0f0f1;color:#11325D;';
                echo '<a href="' . esc_url($url) . '#logs" class="button" style="margin-right:4px;' . $style . '">'
                    . esc_html($info[0]) . ' (' . $info[1] . ')</a>';
            }
            ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: inline; margin-left: 12px;">
                <?php wp_nonce_field(self::NONCE_ACTION); ?>
                <input type="hidden" name="action" value="<?php echo esc_attr(self::POST_ACTION); ?>">
                <input type="hidden" name="op" value="clear_logs">
                <button
                    type="submit"
                    class="button"
                    onclick="return confirm('<?php echo esc_js(__('Rensa alla loggposter?', 'wexoe-core')); ?>');"
                    <?php echo ((int) $log_counts['total'] === 0) ? 'disabled' : ''; ?>
                >Rensa loggar</button>
            </form>
        </div>

        <div id="logs" style="max-width: 900px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; max-height: 500px; overflow-y: auto;">
            <?php if (empty($log_entries)): ?>
                <p style="padding: 16px; color: #999; font-style: italic; margin: 0;">
                    Inga loggposter<?php echo $log_filter ? ' med den filtreringen' : ''; ?>.
                </p>
            <?php else: ?>
                <?php foreach ($log_entries as $entry):
                    $level = $entry['level'] ?? 'info';
                    $ts = $entry['ts'] ?? 0;
                    $msg = $entry['msg'] ?? '';
                    $ctx = isset($entry['ctx']) && is_array($entry['ctx']) ? $entry['ctx'] : [];
                    $level_colors = ['info' => '#378ADD', 'warning' => '#EF9F27', 'error' => '#E24B4A'];
                    $level_bg = ['info' => '#E6F1FB', 'warning' => '#FAEEDA', 'error' => '#FCEBEB'];
                    $color = $level_colors[$level] ?? '#888';
                    $bg = $level_bg[$level] ?? '#f9f9f9';
                ?>
                    <div style="padding: 10px 14px; border-bottom: 1px solid #f0f0f1; font-size: 13px;">
                        <div style="display: flex; align-items: baseline; gap: 12px;">
                            <span style="background:<?php echo $bg; ?>;color:<?php echo $color; ?>;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:600;text-transform:uppercase;flex-shrink:0;min-width:70px;text-align:center;">
                                <?php echo esc_html($level); ?>
                            </span>
                            <span style="color:#666;font-size:11px;flex-shrink:0;min-width:140px;">
                                <?php echo esc_html(date('Y-m-d H:i:s', (int) $ts)); ?>
                            </span>
                            <span style="color:#11325D;"><?php echo esc_html($msg); ?></span>
                        </div>
                        <?php if (!empty($ctx)): ?>
                            <details style="margin-top: 6px; margin-left: 94px;">
                                <summary style="cursor: pointer; color: #666; font-size: 11px;">context</summary>
                                <pre style="margin: 6px 0 0 0; padding: 8px; background: #f6f7f7; border-radius: 3px; font-size: 11px; overflow-x: auto;"><?php echo esc_html(wp_json_encode($ctx, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?></pre>
                            </details>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_roadmap() {
        ?>
        <h2 style="margin-top: 40px;">Kommer i kommande faser</h2>
        <table class="widefat" style="max-width: 720px;">
            <thead>
                <tr><th>Fas</th><th>Funktionalitet</th></tr>
            </thead>
            <tbody>
                <tr><td>5</td><td>Andra feature-plugin refaktorerad (team_rack / product_area) — bekräfta mönstret</td></tr>
                <tr><td>6+</td><td>Rullande migration av resterande feature-plugins</td></tr>
            </tbody>
        </table>
        <?php
    }

    /**
     * Map error_type to a user-facing hint.
     */
    private static function error_hint($error_type) {
        switch ($error_type) {
            case 'auth':       return 'Kontrollera att API-nyckeln är korrekt och har rätt scopes.';
            case 'not_found':  return 'Kontrollera att Base ID stämmer och att nyckeln har åtkomst till basen.';
            case 'rate_limit': return 'Airtable rate limit nådd. Vänta en stund.';
            case 'server':     return 'Airtable-tjänsten svarar med serverfel.';
            case 'network':    return 'Nätverksfel mellan din server och Airtable.';
            case 'config':     return 'Kontrollera att både API-nyckel och Base ID är satta.';
            case 'parse':      return 'Airtable returnerade ogiltigt svar.';
            default:           return '';
        }
    }

    /* --------------------------------------------------------
       ACTION ROUTER
       -------------------------------------------------------- */

    public function handle_action() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Du har inte behörighet.', 'wexoe-core'));
        }

        check_admin_referer(self::NONCE_ACTION);

        $op = isset($_POST['op']) ? sanitize_key(wp_unslash($_POST['op'])) : '';
        $redirect_url = admin_url('tools.php?page=' . self::MENU_SLUG);

        switch ($op) {
            case 'save_settings':       $this->op_save_settings(); break;
            case 'test_connection':     $this->op_test_connection(); break;
            case 'schema_health_check': $this->op_schema_health_check(); break;
            case 'test_cache':          $this->op_test_cache(); break;
            case 'clear_cache':         $this->op_clear_cache(); break;
            case 'clear_logs':          $this->op_clear_logs(); break;
            case 'entity_refresh':      $this->op_entity_refresh(); break;
            case 'entity_clear_cache':  $this->op_entity_clear_cache(); break;
            case 'run_helper_tests':    $this->op_run_helper_tests(); break;
            default:
                self::set_notice('error', __('Okänd operation.', 'wexoe-core'));
                break;
        }

        wp_redirect($redirect_url);
        exit;
    }

    private function op_save_settings() {
        if (isset($_POST['clear_key']) && $_POST['clear_key'] === '1') {
            Plugin::delete_api_key();
            self::clear_test_result();
            self::set_notice('warning', __('API-nyckel rensad.', 'wexoe-core'));
            return;
        }

        $api_key = isset($_POST['api_key']) ? trim(wp_unslash($_POST['api_key'])) : '';
        if ($api_key !== '') {
            if (!preg_match('/^(pat|key)[A-Za-z0-9.]+$/', $api_key)) {
                self::set_notice('error', __('Ogiltigt format på API-nyckeln.', 'wexoe-core'));
                return;
            }
            Plugin::set_api_key($api_key);
            self::clear_test_result();
        }

        $base_id = isset($_POST['base_id']) ? trim(wp_unslash($_POST['base_id'])) : '';
        if ($base_id !== '') {
            if (!Plugin::is_valid_base_id_format($base_id)) {
                self::set_notice('error', __('Ogiltigt format på Base ID. Det ska börja med "app" följt av 14 tecken.', 'wexoe-core'));
                return;
            }
            if ($base_id !== Plugin::get_base_id()) {
                Plugin::set_base_id($base_id);
                self::clear_test_result();
            }
        } else {
            if (Plugin::get_base_id() !== '') {
                Plugin::delete_base_id();
                self::clear_test_result();
            }
        }

        if ($api_key === '' && $base_id === Plugin::get_base_id()) {
            self::set_notice('info', __('Inga ändringar att spara.', 'wexoe-core'));
        } else {
            self::set_notice('success', __('Inställningar sparade.', 'wexoe-core'));
        }
    }

    private function op_test_connection() {
        $result = AirtableClient::fetch_tables();

        if ($result['success']) {
            $table_names = array_map(function($t) {
                return isset($t['name']) ? $t['name'] : (isset($t['id']) ? $t['id'] : 'unknown');
            }, $result['tables']);

            self::set_test_result([
                'success' => true,
                'table_count' => count($result['tables']),
                'table_names' => $table_names,
                'timestamp' => time(),
            ]);
            self::set_notice('success', __('Anslutningstest lyckades.', 'wexoe-core'));
        } else {
            self::set_test_result([
                'success' => false,
                'error' => $result['error'],
                'error_type' => $result['error_type'] ?? '',
                'http_code' => $result['http_code'] ?? null,
                'timestamp' => time(),
            ]);
            self::set_notice('error', __('Anslutningstest misslyckades. Se detaljer nedan.', 'wexoe-core'));
        }
    }

    private function op_schema_health_check() {
        $meta = AirtableClient::fetch_tables();
        if (!$meta['success']) {
            self::set_notice('error', __('Schema health check misslyckades: kunde inte läsa Airtable metadata.', 'wexoe-core'));
            self::set_schema_health_result([
                'entities_checked' => 0,
                'errors' => 1,
                'warnings' => 0,
                'items' => [[
                    'entity' => '-',
                    'status' => 'error',
                    'message' => (string) ($meta['error'] ?? 'unknown'),
                ]],
                'timestamp' => time(),
            ]);
            return;
        }

        $tables = is_array($meta['tables']) ? $meta['tables'] : [];
        $table_map = [];
        foreach ($tables as $table) {
            if (!is_array($table)) continue;
            $id = isset($table['id']) ? (string) $table['id'] : '';
            $name = isset($table['name']) ? (string) $table['name'] : '';
            if ($id !== '') $table_map[$id] = $table;
            if ($name !== '') $table_map[$name] = $table;
        }

        $items = [];
        $errors = 0;
        $warnings = 0;
        $entities = SchemaRegistry::list_registered();

        foreach ($entities as $entity_name) {
            $repo = Core::entity($entity_name);
            if ($repo === null) {
                $items[] = ['entity' => $entity_name, 'status' => 'error', 'message' => 'Schema kunde inte laddas.'];
                $errors++;
                continue;
            }

            $schema = $repo->get_schema();
            $table_id = $repo->get_table_id();
            if (!isset($table_map[$table_id])) {
                $items[] = ['entity' => $entity_name, 'status' => 'error', 'message' => 'Tabell saknas i Airtable metadata: ' . $table_id];
                $errors++;
                continue;
            }

            $table = $table_map[$table_id];
            $meta_fields = isset($table['fields']) && is_array($table['fields']) ? $table['fields'] : [];
            $field_names = [];
            foreach ($meta_fields as $f) {
                if (is_array($f) && isset($f['name']) && is_string($f['name'])) {
                    $field_names[$f['name']] = true;
                }
            }

            $missing = [];
            foreach (($schema['fields'] ?? []) as $domain_key => $spec) {
                if (is_string($spec)) {
                    if (!isset($field_names[$spec])) $missing[] = $spec;
                    continue;
                }
                if (!is_array($spec)) continue;
                $type = isset($spec['type']) ? (string) $spec['type'] : 'text';
                if ($type === 'pseudo_array') {
                    continue;
                }
                $source = isset($spec['source']) ? (string) $spec['source'] : '';
                if ($source !== '' && !isset($field_names[$source])) {
                    $missing[] = $source;
                }
            }

            if (!empty($missing)) {
                $items[] = [
                    'entity' => $entity_name,
                    'status' => 'warning',
                    'message' => 'Saknade fält i metadata: ' . implode(', ', array_slice(array_unique($missing), 0, 8)),
                ];
                $warnings++;
            } else {
                $items[] = ['entity' => $entity_name, 'status' => 'ok', 'message' => 'Schema matchar tabellmetadata.'];
            }
        }

        self::set_schema_health_result([
            'entities_checked' => count($entities),
            'errors' => $errors,
            'warnings' => $warnings,
            'items' => $items,
            'timestamp' => time(),
        ]);

        if ($errors > 0) {
            self::set_notice('error', __('Schema health check klar med fel.', 'wexoe-core'));
        } elseif ($warnings > 0) {
            self::set_notice('warning', __('Schema health check klar med varningar.', 'wexoe-core'));
        } else {
            self::set_notice('success', __('Schema health check: alla entiteter ser bra ut.', 'wexoe-core'));
        }
    }

    private function op_test_cache() {
        $key = 'test:' . uniqid();
        $value = ['test' => true, 'nonce' => mt_rand(1000, 9999), 'ts' => microtime(true)];

        $write_ok = Cache::set($key, $value, 30);
        if (!$write_ok) {
            Logger::error('Cache write test failed', ['key' => $key]);
            self::set_notice('error', __('Cache-skrivning misslyckades.', 'wexoe-core'));
            return;
        }

        $read_back = Cache::get($key);
        if ($read_back === null) {
            Logger::error('Cache read test failed — key not found', ['key' => $key]);
            self::set_notice('error', __('Cache-läsning misslyckades.', 'wexoe-core'));
            return;
        }

        if ($read_back !== $value) {
            Logger::error('Cache read test failed — value mismatch', [
                'key' => $key, 'written' => $value, 'read' => $read_back,
            ]);
            self::set_notice('error', __('Cache-läsning returnerade fel värde.', 'wexoe-core'));
            return;
        }

        Cache::delete($key);
        Logger::info('Cache test succeeded', ['key' => $key]);
        self::set_notice('success', __('Cache-test lyckades.', 'wexoe-core'));
    }

    private function op_clear_cache() {
        $deleted = Cache::clear_all();
        Logger::info('Cache cleared via admin', ['deleted_count' => $deleted]);
        self::set_notice('success', sprintf(__('Cache rensad. %d poster borttagna.', 'wexoe-core'), $deleted));
    }

    private function op_clear_logs() {
        Logger::clear();
        Logger::info('Log cleared via admin');
        self::set_notice('success', __('Loggar rensade.', 'wexoe-core'));
    }

    private function op_entity_refresh() {
        $name = isset($_POST['entity']) ? sanitize_key(wp_unslash($_POST['entity'])) : '';
        $repo = Core::entity($name);
        if ($repo === null) {
            self::set_notice('error', sprintf(__('Entitet "%s" hittades inte.', 'wexoe-core'), $name));
            return;
        }

        $records = $repo->force_refresh();
        self::set_notice('success', sprintf(
            __('Entitet "%s" refreshad. Hämtade %d records.', 'wexoe-core'),
            $name,
            count($records)
        ));
    }

    private function op_entity_clear_cache() {
        $name = isset($_POST['entity']) ? sanitize_key(wp_unslash($_POST['entity'])) : '';
        $repo = Core::entity($name);
        if ($repo === null) {
            self::set_notice('error', sprintf(__('Entitet "%s" hittades inte.', 'wexoe-core'), $name));
            return;
        }

        $deleted = $repo->clear_cache();
        self::set_notice('success', sprintf(
            __('Cache rensad för "%s". %d poster borttagna.', 'wexoe-core'),
            $name,
            $deleted
        ));
    }

    private function op_run_helper_tests() {
        $results = HelperTester::run_all();
        $summary = HelperTester::summarize($results);

        self::set_helper_result([
            'results' => $results,
            'summary' => $summary,
            'timestamp' => time(),
        ]);

        if ($summary['failed'] === 0) {
            self::set_notice('success', sprintf(
                __('Alla %d helper-tester passerade.', 'wexoe-core'),
                $summary['total']
            ));
        } else {
            self::set_notice('error', sprintf(
                __('%d av %d helper-tester misslyckades. Se detaljer nedan.', 'wexoe-core'),
                $summary['failed'],
                $summary['total']
            ));
        }
    }
}
