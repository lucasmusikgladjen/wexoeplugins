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
 * Usage:
 *   $result = Core::writer('tblXXX')->create([
 *       'Email'   => sanitize_email($email),
 *       'Namn'    => sanitize_text_field($name),
 *       'Meddelande' => sanitize_textarea_field($message),
 *   ]);
 *   if (!$result['success']) {
 *       Core::log('error', 'Kunde inte spara lead', ['error' => $result['error']]);
 *   }
 */
class WriteRepository {

    /** @var string */
    private $table_id;

    /** @var string|null */
    private $base_id;

    /**
     * @param string      $table_id  Airtable table ID (tblXXXXXXXXXXXXXX)
     * @param string|null $base_id   Optional override; uses plugin config if null
     */
    public function __construct($table_id, $base_id = null) {
        $this->table_id = $table_id;
        $this->base_id  = $base_id ?: null;
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
