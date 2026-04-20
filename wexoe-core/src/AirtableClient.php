<?php
namespace Wexoe\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * HTTP client for Airtable's REST API.
 *
 * Thin wrapper around wp_remote_get. Knows nothing about entities, schemas,
 * or cache — only how to speak Airtable on the wire.
 *
 * All methods return a structured array:
 *
 *   SUCCESS:
 *     ['success' => true, 'records' => [...], 'pages_fetched' => N]
 *     ['success' => true, 'tables' => [...]]          // for fetch_tables
 *     ['success' => true, 'record' => {...}]          // for fetch_record
 *
 *   FAILURE:
 *     ['success' => false, 'error' => 'message',
 *      'error_type' => 'auth|not_found|rate_limit|server|network|parse|config',
 *      'http_code' => int|null]
 *
 * Callers should check $result['success'] — never catch exceptions.
 */
class AirtableClient {

    const API_BASE = 'https://api.airtable.com/v0';
    const TIMEOUT_SECONDS = 15;
    const MAX_PAGES = 100; // safety — stops runaway pagination at ~10k records

    /**
     * Fetch all records from a table, paginating automatically.
     *
     * @param string $table_id  Airtable table ID (tblXXX) or table name
     * @param array  $options   Supported: filterByFormula, maxRecords, sort, fields, view
     * @param string|null $base_id  If null, uses Plugin::get_base_id()
     * @return array
     */
    public static function fetch_records($table_id, $options = [], $base_id = null) {
        $base_id = $base_id ?: Plugin::get_base_id();
        if (empty($base_id)) {
            return self::error('Ingen Airtable base ID är konfigurerad.', 'config');
        }
        if (empty($table_id)) {
            return self::error('Tabell-ID saknas.', 'config');
        }

        $records = [];
        $pages = 0;
        $offset = null;

        do {
            $query = $options;
            if ($offset) {
                $query['offset'] = $offset;
            }

            $path = '/' . rawurlencode($base_id) . '/' . rawurlencode($table_id);
            $result = self::request($path, $query);

            if (!$result['success']) {
                return $result; // Propagate error up
            }

            $body = $result['body'];
            if (isset($body['records']) && is_array($body['records'])) {
                $records = array_merge($records, $body['records']);
            }

            $offset = isset($body['offset']) ? $body['offset'] : null;
            $pages++;

            if ($pages >= self::MAX_PAGES) {
                Logger::warning('Airtable pagination hit MAX_PAGES safety limit', [
                    'table' => $table_id,
                    'records_so_far' => count($records),
                ]);
                break;
            }
        } while ($offset);

        Logger::info('Airtable fetch_records succeeded', [
            'table' => $table_id,
            'records' => count($records),
            'pages' => $pages,
        ]);

        return [
            'success' => true,
            'records' => $records,
            'pages_fetched' => $pages,
        ];
    }

    /**
     * Fetch a single record by its Airtable record ID.
     */
    public static function fetch_record($table_id, $record_id, $base_id = null) {
        $base_id = $base_id ?: Plugin::get_base_id();
        if (empty($base_id)) {
            return self::error('Ingen Airtable base ID är konfigurerad.', 'config');
        }

        $path = '/' . rawurlencode($base_id) . '/' . rawurlencode($table_id) . '/' . rawurlencode($record_id);
        $result = self::request($path);

        if (!$result['success']) {
            return $result;
        }

        return [
            'success' => true,
            'record' => $result['body'],
        ];
    }

    /**
     * List all tables in a base. Requires PAT scope: schema.bases:read.
     * Used by the admin "test connection" button.
     */
    public static function fetch_tables($base_id = null) {
        $base_id = $base_id ?: Plugin::get_base_id();
        if (empty($base_id)) {
            return self::error('Ingen Airtable base ID är konfigurerad.', 'config');
        }

        $path = '/meta/bases/' . rawurlencode($base_id) . '/tables';
        $result = self::request($path);

        if (!$result['success']) {
            return $result;
        }

        $tables = isset($result['body']['tables']) ? $result['body']['tables'] : [];

        Logger::info('Airtable fetch_tables succeeded', [
            'base' => $base_id,
            'table_count' => count($tables),
        ]);

        return [
            'success' => true,
            'tables' => $tables,
        ];
    }

    /* --------------------------------------------------------
       INTERNAL HELPERS
       -------------------------------------------------------- */

    /**
     * Core request method. All fetches go through this.
     * Returns ['success' => bool, 'body' => array, ...] on success
     *      or ['success' => false, 'error' => ..., 'error_type' => ..., 'http_code' => ...] on failure
     */
    private static function request($path, $query = []) {
        $api_key = Plugin::get_api_key();
        if (empty($api_key)) {
            return self::error('Ingen API-nyckel konfigurerad.', 'config');
        }

        $url = self::API_BASE . $path;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'timeout' => self::TIMEOUT_SECONDS,
        ]);

        // Network-level failure (DNS, timeout, connection refused)
        if (is_wp_error($response)) {
            $msg = $response->get_error_message();
            Logger::error('Airtable network error', [
                'url_path' => $path,
                'message' => $msg,
            ]);
            return self::error($msg, 'network');
        }

        $http_code = (int) wp_remote_retrieve_response_code($response);
        $body_raw = wp_remote_retrieve_body($response);
        $body = json_decode($body_raw, true);

        // Non-2xx HTTP status
        if ($http_code < 200 || $http_code >= 300) {
            $error_type = self::classify_http_code($http_code);
            $airtable_msg = isset($body['error']['message'])
                ? $body['error']['message']
                : (isset($body['error']['type']) ? $body['error']['type'] : 'HTTP ' . $http_code);

            Logger::error('Airtable HTTP error', [
                'url_path' => $path,
                'http_code' => $http_code,
                'error_type' => $error_type,
                'airtable_message' => $airtable_msg,
            ]);

            return [
                'success' => false,
                'error' => $airtable_msg,
                'error_type' => $error_type,
                'http_code' => $http_code,
            ];
        }

        // 2xx but body didn't parse as JSON
        if (!is_array($body)) {
            Logger::error('Airtable response parse error', [
                'url_path' => $path,
                'http_code' => $http_code,
                'body_preview' => substr($body_raw, 0, 200),
            ]);
            return self::error('Kunde inte tolka svar från Airtable (ogiltig JSON).', 'parse', $http_code);
        }

        return [
            'success' => true,
            'body' => $body,
            'http_code' => $http_code,
        ];
    }

    /**
     * Map HTTP status to our error_type taxonomy.
     */
    private static function classify_http_code($code) {
        if ($code === 401 || $code === 403) return 'auth';
        if ($code === 404) return 'not_found';
        if ($code === 429) return 'rate_limit';
        if ($code >= 500) return 'server';
        return 'unknown';
    }

    /**
     * Build a standardized error response.
     */
    private static function error($message, $type, $http_code = null) {
        return [
            'success' => false,
            'error' => $message,
            'error_type' => $type,
            'http_code' => $http_code,
        ];
    }
}
