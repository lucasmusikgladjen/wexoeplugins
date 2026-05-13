<?php
namespace Wexoe\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Write-only repository for a single Airtable table.
 *
 * Provides create / update operations without any caching layer — writes go
 * straight to Airtable. Get an instance via Core::writer('tblXXXXXXXXXXXXXX').
 *
 * Field names must be the actual Airtable field names (strings), not domain
 * aliases. The caller is responsible for sanitizing all values before passing
 * them here; wexoe-core does not alter field values.
 *
 * Usage (raw field names):
 *   $result = Core::writer('tblXXX')->create([
 *       'Email'   => sanitize_email($email),
 *       'Namn'    => sanitize_text_field($name),
 *   ]);
 *
 * Usage (domain names via write-entity schema):
 *   $result = Core::submission('user_submissions')->create_mapped([
 *       'email'           => sanitize_email($email),
 *       'submission_type' => 'leadmagnet',
 *       'newsletter_consent' => true,
 *       'extra'           => ['custom_field' => 'value'],  // auto JSON-encoded
 *   ]);
 *   if (!$result['success']) {
 *       Core::log('error', 'Kunde inte spara submission', ['error' => $result['error']]);
 *   }
 */
class WriteRepository {

    /** @var string */
    private $table_id;

    /** @var string|null */
    private $base_id;

    /**
     * Optional domain_key => Airtable field name map.
     * Set by WriteRegistry when accessed via Core::submission().
     * Empty when accessed via Core::writer() (raw mode).
     *
     * @var array<string, string>
     */
    private $field_map;

    /**
     * Optional domain_key => type-hint map.
     *
     * Styr hur `create_mapped()`/`update_mapped()` serialiserar värden:
     *   'link'           — array passar genom som-är (linked-record-IDs).
     *   'attachment_url' — URL-string konverteras till [['url' => $url]].
     *   'json'           — array JSON-encodas (default-beteendet för arrays).
     *
     * Domain-keys utan typ-hint behandlas som strängar/skalärer. Arrayer utan
     * typ-hint JSON-encodas (bakåtkompatibelt — `extra`-fältet i user_submissions
     * förlitar sig på det).
     *
     * @var array<string, string>
     */
    private $field_types;

    /**
     * @param string      $table_id    Airtable table ID (tblXXXXXXXXXXXXXX)
     * @param string|null $base_id     Optional override; uses plugin config if null
     * @param array       $field_map   domain_key => Airtable field name (optional)
     * @param array       $field_types domain_key => type-hint (optional)
     */
    public function __construct($table_id, $base_id = null, $field_map = [], $field_types = []) {
        $this->table_id    = $table_id;
        $this->base_id     = $base_id ?: null;
        $this->field_map   = is_array($field_map) ? $field_map : [];
        $this->field_types = is_array($field_types) ? $field_types : [];
    }

    /**
     * Create a single record.
     *
     * @param  array $fields  Airtable field name => value (already sanitized)
     * @return array ['success' => true,  'record' => [...raw Airtable record...]]
     *            or ['success' => false, 'error' => '...', 'error_type' => '...', 'http_code' => int|null]
     */
    public function create(array $fields) {
        if (empty($fields)) {
            return $this->config_error('Inga fält att skriva.');
        }
        return AirtableClient::create_record($this->table_id, $fields, $this->base_id);
    }

    /**
     * Create multiple records. Automatically batched in chunks of 10.
     *
     * @param  array $records  List of field-maps: [['Email' => 'a@b.c'], ['Email' => 'x@y.z'], ...]
     * @return array ['success' => true,  'records' => [...]]
     *            or ['success' => false, ...] on first failing chunk
     */
    public function create_many(array $records) {
        if (empty($records)) {
            return $this->config_error('Inga poster att skriva.');
        }
        return AirtableClient::create_records($this->table_id, $records, $this->base_id);
    }

    /**
     * Update specific fields on an existing record (PATCH — untouched fields are preserved).
     *
     * @param  string $record_id  Airtable record ID (recXXXXXXXXXXXXXX)
     * @param  array  $fields     Fields to update (already sanitized)
     * @return array ['success' => true,  'record' => {...}]
     *            or ['success' => false, ...]
     */
    public function update($record_id, array $fields) {
        if (empty($record_id)) {
            return $this->config_error('Record-ID saknas.');
        }
        if (empty($fields)) {
            return $this->config_error('Inga fält att uppdatera.');
        }
        return AirtableClient::update_record($this->table_id, $record_id, $fields, $this->base_id);
    }

    /**
     * Delete a single record.
     */
    public function delete($record_id) {
        if (empty($record_id)) {
            return $this->config_error('Record-ID saknas.');
        }
        return AirtableClient::delete_record($this->table_id, $record_id, $this->base_id);
    }

    /**
     * Create a record using domain field names mapped through the write-entity schema.
     *
     * Domain keys not present in the field map are silently ignored (logged as warning).
     * Null values are skipped — omitting a key is equivalent to passing null.
     * Array values for 'extra' (or any multilineText field) are JSON-encoded automatically.
     *
     * @param  array $domain_fields  domain_key => value (already sanitized)
     * @return array Same shape as create()
     */
    public function create_mapped(array $domain_fields) {
        if (empty($this->field_map)) {
            Logger::warning('WriteRepository::create_mapped called without a field map — falling back to raw create()', [
                'table' => $this->table_id,
            ]);
            return $this->create($domain_fields);
        }

        $airtable_fields = $this->map_domain_to_airtable($domain_fields);

        if (empty($airtable_fields)) {
            return $this->config_error('Inga mappade fält att skriva efter filtrering.');
        }

        return $this->create($airtable_fields);
    }

    /**
     * Update using domain field names.
     *
     * @param  string $record_id    Airtable record ID (recXXXXXXXXXXXXXX)
     * @param  array  $domain_fields
     * @return array Same shape as update()
     */
    public function update_mapped($record_id, array $domain_fields) {
        if (empty($this->field_map)) {
            return $this->update($record_id, $domain_fields);
        }

        $airtable_fields = $this->map_domain_to_airtable($domain_fields);

        if (empty($airtable_fields)) {
            return $this->config_error('Inga mappade fält att uppdatera efter filtrering.');
        }

        return $this->update($record_id, $airtable_fields);
    }

    /**
     * Konvertera domain_key => värde till Airtable-fält-map.
     *
     * Tillämpar `field_types`-hints:
     *   - 'link'           → array av record-IDs passar genom som-är.
     *   - 'attachment_url' → string-URL packas som [['url' => $url]] (tom URL = []).
     *   - 'json'           → array JSON-encodas explicit.
     *   - otypad array     → JSON-encodas (default, bakåtkompatibelt med `extra`).
     *
     * Okända domain-keys loggas + utelämnas.
     */
    private function map_domain_to_airtable(array $domain_fields) {
        $airtable_fields = [];
        foreach ($domain_fields as $domain_key => $value) {
            if ($value === null) {
                continue;
            }
            if (!isset($this->field_map[$domain_key])) {
                Logger::warning('WriteRepository: okänd domain-nyckel, skippas', [
                    'key'   => $domain_key,
                    'table' => $this->table_id,
                ]);
                continue;
            }
            $airtable_name = $this->field_map[$domain_key];
            $type          = isset($this->field_types[$domain_key]) ? $this->field_types[$domain_key] : null;

            switch ($type) {
                case 'link':
                    if (!is_array($value)) {
                        $value = [];
                    }
                    // Passa genom som native array av record-IDs.
                    break;

                case 'attachment_url':
                    if (is_array($value)) {
                        // Antag redan i Airtable-format ([{url}]).
                        break;
                    }
                    $value = is_string($value) && $value !== '' ? [['url' => $value]] : [];
                    break;

                case 'json':
                    $value = wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    break;

                default:
                    if (is_array($value)) {
                        $value = wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                    break;
            }

            $airtable_fields[$airtable_name] = $value;
        }
        return $airtable_fields;
    }

    /* --------------------------------------------------------
       INTERNAL
       -------------------------------------------------------- */

    private function config_error($message) {
        return [
            'success'    => false,
            'error'      => $message,
            'error_type' => 'config',
            'http_code'  => null,
        ];
    }
}
