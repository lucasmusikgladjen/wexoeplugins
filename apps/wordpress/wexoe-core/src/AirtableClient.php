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
    const MAX_RETRIES = 2; // total attempts = 1 + MAX_RETRIES
    const RETRY_BASE_MS = 500;
    const RETRY_MAX_MS = 3000;

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
       WRITE OPERATIONS (create / update)
       -------------------------------------------------------- */

    /**
     * Create a single record in a table.
     *
     * @param string      $table_id   Airtable table ID (tblXXX)
     * @param array       $fields     Airtable field name => value map
     * @param string|null $base_id    Falls back to Plugin::get_base_id()
     * @return array ['success' => true, 'record' => {...}]
     *            or ['success' => false, 'error' => '...', 'error_type' => '...']
     */
    public static function create_record($table_id, $fields, $base_id = null) {
        $base_id = $base_id ?: Plugin::get_base_id();
        if (empty($base_id))   return self::error('Ingen Airtable base ID är konfigurerad.', 'config');
        if (empty($table_id))  return self::error('Tabell-ID saknas.', 'config');
        if (empty($fields) || !is_array($fields)) return self::error('Inga fält att skriva.', 'config');

        $path   = '/' . rawurlencode($base_id) . '/' . rawurlencode($table_id);
        $result = self::write_request('POST', $path, ['fields' => $fields]);

        if (!$result['success']) {
            return $result;
        }

        Logger::info('Airtable create_record succeeded', [
            'table'     => $table_id,
            'record_id' => isset($result['body']['id']) ? $result['body']['id'] : null,
        ]);

        return ['success' => true, 'record' => $result['body']];
    }

    /**
     * Create multiple records in a table. Automatically batches into chunks of 10
     * (Airtable's maximum per request).
     *
     * @param string      $table_id  Airtable table ID (tblXXX)
     * @param array       $records   List of field-maps: [['Email' => '...'], ...]
     * @param string|null $base_id
     * @return array ['success' => true, 'records' => [...]]
     *            or ['success' => false, ...]  on the first failing chunk
     */
    public static function create_records($table_id, $records, $base_id = null) {
        $base_id = $base_id ?: Plugin::get_base_id();
        if (empty($base_id))  return self::error('Ingen Airtable base ID är konfigurerad.', 'config');
        if (empty($table_id)) return self::error('Tabell-ID saknas.', 'config');
        if (empty($records) || !is_array($records)) return self::error('Inga poster att skriva.', 'config');

        $path    = '/' . rawurlencode($base_id) . '/' . rawurlencode($table_id);
        $chunks  = array_chunk($records, 10);
        $created = [];

        foreach ($chunks as $chunk) {
            $body = ['records' => []];
            foreach ($chunk as $fields) {
                $body['records'][] = ['fields' => $fields];
            }

            $result = self::write_request('POST', $path, $body);
            if (!$result['success']) {
                return $result;
            }

            if (isset($result['body']['records']) && is_array($result['body']['records'])) {
                $created = array_merge($created, $result['body']['records']);
            }
        }

        Logger::info('Airtable create_records succeeded', [
            'table'   => $table_id,
            'created' => count($created),
            'chunks'  => count($chunks),
        ]);

        return ['success' => true, 'records' => $created];
    }

    /**
     * Update specific fields on an existing record (PATCH — untouched fields are preserved).
     *
     * @param string      $table_id   Airtable table ID (tblXXX)
     * @param string      $record_id  Airtable record ID (recXXX)
     * @param array       $fields     Fields to update
     * @param string|null $base_id
     * @return array ['success' => true, 'record' => {...}]
     *            or ['success' => false, ...]
     */
    public static function update_record($table_id, $record_id, $fields, $base_id = null) {
        $base_id = $base_id ?: Plugin::get_base_id();
        if (empty($base_id))   return self::error('Ingen Airtable base ID är konfigurerad.', 'config');
        if (empty($table_id))  return self::error('Tabell-ID saknas.', 'config');
        if (empty($record_id)) return self::error('Record-ID saknas.', 'config');
        if (empty($fields) || !is_array($fields)) return self::error('Inga fält att uppdatera.', 'config');

        $path   = '/' . rawurlencode($base_id) . '/' . rawurlencode($table_id) . '/' . rawurlencode($record_id);
        $result = self::write_request('PATCH', $path, ['fields' => $fields]);

        if (!$result['success']) {
            return $result;
        }

        Logger::info('Airtable update_record succeeded', [
            'table'     => $table_id,
            'record_id' => $record_id,
        ]);

        return ['success' => true, 'record' => $result['body']];
    }

    /**
     * Delete a single record.
     *
     * @param  string      $table_id
     * @param  string      $record_id
     * @param  string|null $base_id
     * @return array ['success' => true, 'deleted' => true, 'id' => $record_id]
     *            or ['success' => false, ...]
     */
    public static function delete_record($table_id, $record_id, $base_id = null) {
        $base_id = $base_id ?: Plugin::get_base_id();
        if (empty($base_id))   return self::error('Ingen Airtable base ID är konfigurerad.', 'config');
        if (empty($table_id))  return self::error('Tabell-ID saknas.', 'config');
        if (empty($record_id)) return self::error('Record-ID saknas.', 'config');

        $api_key = Plugin::get_api_key();
        if (empty($api_key)) return self::error('Ingen API-nyckel konfigurerad.', 'config');

        $url = self::API_BASE . '/' . rawurlencode($base_id) . '/' . rawurlencode($table_id) . '/' . rawurlencode($record_id);
        $attempt = 0;

        do {
            $response = wp_remote_request($url, [
                'method'  => 'DELETE',
                'headers' => ['Authorization' => 'Bearer ' . $api_key],
                'timeout' => self::TIMEOUT_SECONDS,
            ]);

            if (is_wp_error($response)) {
                if ($attempt < self::MAX_RETRIES) {
                    self::sleep_before_retry($attempt, null, $url, 'network');
                    $attempt++;
                    continue;
                }
                return self::error($response->get_error_message(), 'network');
            }

            $http_code = (int) wp_remote_retrieve_response_code($response);
            if ($http_code < 200 || $http_code >= 300) {
                $error_type = self::classify_http_code($http_code);
                $should_retry = ($http_code === 429 || $http_code >= 500) && $attempt < self::MAX_RETRIES;
                if ($should_retry) {
                    self::sleep_before_retry($attempt, $response, $url, $error_type);
                    $attempt++;
                    continue;
                }
                $body = json_decode(wp_remote_retrieve_body($response), true);
                $msg = isset($body['error']['message']) ? $body['error']['message'] : 'HTTP ' . $http_code;
                return [
                    'success'    => false,
                    'error'      => $msg,
                    'error_type' => $error_type,
                    'http_code'  => $http_code,
                ];
            }

            Logger::info('Airtable delete_record succeeded', [
                'table'     => $table_id,
                'record_id' => $record_id,
            ]);
            return ['success' => true, 'deleted' => true, 'id' => $record_id];

        } while ($attempt <= self::MAX_RETRIES);

        return self::error('Okänt Airtable-fel.', 'unknown');
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

        $attempt = 0;
        do {
            $response = wp_remote_get($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                ],
                'timeout' => self::TIMEOUT_SECONDS,
            ]);

            // Network-level failure (DNS, timeout, connection refused)
            if (is_wp_error($response)) {
                $msg = $response->get_error_message();
                if ($attempt < self::MAX_RETRIES) {
                    self::sleep_before_retry($attempt, null, $path, 'network');
                    $attempt++;
                    continue;
                }

                Logger::error('Airtable network error', [
                    'url_path' => $path,
                    'message' => $msg,
                    'attempts' => $attempt + 1,
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

                $should_retry = ($http_code === 429 || $http_code >= 500) && $attempt < self::MAX_RETRIES;
                if ($should_retry) {
                    self::sleep_before_retry($attempt, $response, $path, $error_type);
                    $attempt++;
                    continue;
                }

                Logger::error('Airtable HTTP error', [
                    'url_path' => $path,
                    'http_code' => $http_code,
                    'error_type' => $error_type,
                    'airtable_message' => $airtable_msg,
                    'attempts' => $attempt + 1,
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
        } while ($attempt <= self::MAX_RETRIES);

        return self::error('Okänt Airtable-fel.', 'unknown');
    }

    /**
     * Core write method (POST / PATCH). Mirrors the read request() method but
     * sends a JSON body and uses wp_remote_request so the HTTP verb is configurable.
     */
    private static function write_request($method, $path, $body) {
        $api_key = Plugin::get_api_key();
        if (empty($api_key)) {
            return self::error('Ingen API-nyckel konfigurerad.', 'config');
        }

        $json = wp_json_encode($body);
        if ($json === false) {
            return self::error('Kunde inte serialisera request-body till JSON.', 'parse');
        }

        $url     = self::API_BASE . $path;
        $attempt = 0;

        do {
            $response = wp_remote_request($url, [
                'method'  => $method,
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ],
                'body'    => $json,
                'timeout' => self::TIMEOUT_SECONDS,
            ]);

            if (is_wp_error($response)) {
                $msg = $response->get_error_message();
                if ($attempt < self::MAX_RETRIES) {
                    self::sleep_before_retry($attempt, null, $path, 'network');
                    $attempt++;
                    continue;
                }
                Logger::error('Airtable write network error', [
                    'url_path' => $path,
                    'method'   => $method,
                    'message'  => $msg,
                    'attempts' => $attempt + 1,
                ]);
                return self::error($msg, 'network');
            }

            $http_code  = (int) wp_remote_retrieve_response_code($response);
            $body_raw   = wp_remote_retrieve_body($response);
            $body_parse = json_decode($body_raw, true);

            if ($http_code < 200 || $http_code >= 300) {
                $error_type  = self::classify_http_code($http_code);
                $airtable_msg = isset($body_parse['error']['message'])
                    ? $body_parse['error']['message']
                    : (isset($body_parse['error']['type']) ? $body_parse['error']['type'] : 'HTTP ' . $http_code);

                $should_retry = ($http_code === 422) ? false
                    : (($http_code === 429 || $http_code >= 500) && $attempt < self::MAX_RETRIES);

                if ($should_retry) {
                    self::sleep_before_retry($attempt, $response, $path, $error_type);
                    $attempt++;
                    continue;
                }

                Logger::error('Airtable write HTTP error', [
                    'url_path'         => $path,
                    'method'           => $method,
                    'http_code'        => $http_code,
                    'error_type'       => $error_type,
                    'airtable_message' => $airtable_msg,
                    'attempts'         => $attempt + 1,
                ]);

                return [
                    'success'    => false,
                    'error'      => $airtable_msg,
                    'error_type' => $error_type,
                    'http_code'  => $http_code,
                ];
            }

            if (!is_array($body_parse)) {
                Logger::error('Airtable write response parse error', [
                    'url_path'     => $path,
                    'method'       => $method,
                    'http_code'    => $http_code,
                    'body_preview' => substr($body_raw, 0, 200),
                ]);
                return self::error('Kunde inte tolka svar från Airtable (ogiltig JSON).', 'parse', $http_code);
            }

            return [
                'success'   => true,
                'body'      => $body_parse,
                'http_code' => $http_code,
            ];

        } while ($attempt <= self::MAX_RETRIES);

        return self::error('Okänt Airtable-fel.', 'unknown');
    }

    private static function sleep_before_retry($attempt, $response, $path, $reason) {
        $retry_after_seconds = null;
        if (is_array($response)) {
            $retry_after = wp_remote_retrieve_header($response, 'retry-after');
            if (is_string($retry_after) && is_numeric($retry_after)) {
                $retry_after_seconds = (int) $retry_after;
            }
        }

        $base = self::RETRY_BASE_MS * (2 ** $attempt);
        $jitter = random_int(0, 250);
        $delay_ms = min(self::RETRY_MAX_MS, $base + $jitter);
        if ($retry_after_seconds !== null && $retry_after_seconds > 0) {
            // Honor Airtable's server-directed cooldown fully when provided.
            $delay_ms = $retry_after_seconds * 1000;
        }

        Logger::warning('Airtable request retrying', [
            'url_path' => $path,
            'reason' => $reason,
            'attempt' => $attempt + 1,
            'delay_ms' => $delay_ms,
        ]);

        usleep($delay_ms * 1000);
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
