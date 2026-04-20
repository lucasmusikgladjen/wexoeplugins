<?php
namespace Wexoe\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Schema-driven normalizer: Airtable records -> domain-shaped arrays.
 *
 * Input: raw Airtable record like
 *   ['id' => 'rec...', 'fields' => ['Name' => 'Rockwell', 'Division' => ['rec...']]]
 *
 * Output (per schema):
 *   ['_record_id' => 'rec...', 'name' => 'Rockwell', 'divisions' => ['rec...']]
 *
 * Schema field definitions support:
 *   'domain_key' => 'Airtable Field Name'                        # simple passthrough
 *   'domain_key' => ['source' => 'Field', 'type' => 'link']      # linked records -> array of record IDs
 *   'domain_key' => ['source' => 'Field', 'type' => 'link_ids']  # alias of 'link'
 *   'domain_key' => ['source' => 'Field', 'type' => 'attachment'] # Airtable attachment -> {url, width, height, filename}
 *   'domain_key' => ['source' => 'Field', 'type' => 'attachments'] # list of attachments
 *   'domain_key' => ['source' => 'Field', 'type' => 'bool']       # coerce to boolean
 *   'domain_key' => ['source' => 'Field', 'type' => 'int']        # coerce to int
 *   'domain_key' => ['source' => 'Field', 'type' => 'float']      # coerce to float
 *   'domain_key' => ['source' => 'Field', 'type' => 'string']     # coerce to string
 *   'domain_key' => ['source' => 'Field', 'type' => 'lines']      # multiline text -> array of non-empty lines
 *   'domain_key' => [
 *       'type' => 'pseudo_array',
 *       'prefix' => 'Normal',     # Airtable fields named "Normal 1 H2", "Normal 2 H2", ...
 *       'count' => 4,
 *       'fields' => [
 *           'h2' => 'H2',
 *           'text' => 'Text',
 *           ...
 *       ],
 *   ]
 */
class Normalizer {

    /**
     * Normalize a single Airtable record according to a schema.
     */
    public static function normalize_record($airtable_record, $schema) {
        $result = [
            '_record_id' => isset($airtable_record['id']) ? $airtable_record['id'] : null,
        ];

        $fields = isset($airtable_record['fields']) && is_array($airtable_record['fields'])
            ? $airtable_record['fields']
            : [];

        $field_specs = isset($schema['fields']) && is_array($schema['fields'])
            ? $schema['fields']
            : [];

        foreach ($field_specs as $domain_key => $spec) {
            $result[$domain_key] = self::normalize_one_field($fields, $spec);
        }

        return $result;
    }

    /**
     * Check whether a normalized record has all required fields populated.
     * Returns true if valid, false otherwise (and logs the missing fields).
     */
    public static function validate_required($normalized, $schema, $entity_name) {
        $required = isset($schema['required']) && is_array($schema['required'])
            ? $schema['required']
            : [];

        if (empty($required)) {
            return true;
        }

        $missing = [];
        foreach ($required as $field) {
            if (!isset($normalized[$field])
                || $normalized[$field] === null
                || $normalized[$field] === ''
                || $normalized[$field] === []) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            Logger::warning('Record missing required fields', [
                'entity' => $entity_name,
                'record_id' => isset($normalized['_record_id']) ? $normalized['_record_id'] : 'unknown',
                'missing_fields' => $missing,
            ]);
            return false;
        }
        return true;
    }

    /* --------------------------------------------------------
       INTERNAL: per-field normalization
       -------------------------------------------------------- */

    private static function normalize_one_field($fields, $spec) {
        // Simple passthrough: 'domain_key' => 'Airtable Field Name'
        if (is_string($spec)) {
            return array_key_exists($spec, $fields) ? $fields[$spec] : null;
        }

        if (!is_array($spec)) {
            return null;
        }

        $type = isset($spec['type']) ? $spec['type'] : 'text';
        $source = isset($spec['source']) ? $spec['source'] : null;

        switch ($type) {
            case 'link':
            case 'link_ids':
                return self::normalize_link($fields, $source);

            case 'pseudo_array':
                return self::normalize_pseudo_array($fields, $spec);

            case 'attachment':
                return self::normalize_single_attachment($fields, $source);

            case 'attachments':
                return self::normalize_attachments($fields, $source);

            case 'bool':
                return !empty($fields[$source]);

            case 'int':
                return isset($fields[$source]) ? (int) $fields[$source] : null;

            case 'float':
                return isset($fields[$source]) ? (float) $fields[$source] : null;

            case 'string':
                return isset($fields[$source]) ? (string) $fields[$source] : null;

            case 'lines':
                return self::normalize_lines($fields, $source);

            case 'text':
            default:
                return array_key_exists($source, $fields) ? $fields[$source] : null;
        }
    }

    /**
     * Linked records: Airtable returns an array of record IDs.
     * Default behavior is "lazy" — return IDs as-is. Eager resolution
     * (following the link to the target entity) is planned for a future phase.
     */
    private static function normalize_link($fields, $source) {
        if (!isset($fields[$source]) || !is_array($fields[$source])) {
            return [];
        }
        return array_values($fields[$source]);
    }

    /**
     * Airtable attachment field returns an array of attachment objects.
     * Single-attachment normalizer picks the first one, or null if empty.
     */
    private static function normalize_single_attachment($fields, $source) {
        if (!isset($fields[$source]) || !is_array($fields[$source]) || empty($fields[$source])) {
            return null;
        }
        $first = $fields[$source][0];
        return self::shape_attachment($first);
    }

    private static function normalize_attachments($fields, $source) {
        if (!isset($fields[$source]) || !is_array($fields[$source])) {
            return [];
        }
        $result = [];
        foreach ($fields[$source] as $att) {
            $shaped = self::shape_attachment($att);
            if ($shaped !== null) {
                $result[] = $shaped;
            }
        }
        return $result;
    }

    private static function shape_attachment($att) {
        if (!is_array($att)) return null;
        return [
            'url' => isset($att['url']) ? $att['url'] : null,
            'filename' => isset($att['filename']) ? $att['filename'] : null,
            'width' => isset($att['width']) ? (int) $att['width'] : null,
            'height' => isset($att['height']) ? (int) $att['height'] : null,
            'size' => isset($att['size']) ? (int) $att['size'] : null,
            'mime_type' => isset($att['type']) ? $att['type'] : null,
            'thumbnails' => isset($att['thumbnails']) ? $att['thumbnails'] : null,
        ];
    }

    /**
     * Multiline text -> array of non-empty trimmed lines.
     */
    private static function normalize_lines($fields, $source) {
        if (!isset($fields[$source]) || !is_string($fields[$source])) {
            return [];
        }
        $lines = preg_split('/\r\n|\r|\n/', $fields[$source]);
        $lines = array_map('trim', $lines);
        $lines = array_filter($lines, function ($line) {
            return $line !== '';
        });
        return array_values($lines);
    }

    /**
     * Pseudo-array: fields like "Normal 1 H2", "Normal 1 Text", "Normal 2 H2", ...
     * are collapsed into an array of sections.
     *
     * Sections with ALL inner fields empty are filtered out. Sections with at
     * least one non-empty inner field are kept, with empty inner fields as null.
     */
    private static function normalize_pseudo_array($fields, $spec) {
        $prefix = isset($spec['prefix']) ? (string) $spec['prefix'] : '';
        $count = isset($spec['count']) ? (int) $spec['count'] : 0;
        $inner_fields = isset($spec['fields']) && is_array($spec['fields']) ? $spec['fields'] : [];

        if ($prefix === '' || $count <= 0 || empty($inner_fields)) {
            return [];
        }

        $sections = [];

        for ($i = 1; $i <= $count; $i++) {
            $section = ['_index' => $i];
            $has_content = false;

            foreach ($inner_fields as $domain_key => $suffix) {
                // Airtable field name convention: "{prefix} {index} {suffix}"
                // Example: "Normal 1 H2", "Normal 2 Text"
                $airtable_key = $prefix . ' ' . $i . ' ' . $suffix;
                $value = array_key_exists($airtable_key, $fields) ? $fields[$airtable_key] : null;
                $section[$domain_key] = $value;
                if (self::has_content($value)) {
                    $has_content = true;
                }
            }

            if ($has_content) {
                $sections[] = $section;
            }
        }

        return $sections;
    }

    /**
     * "Has content" check — treats null, empty string, empty array, false as empty.
     * Zero (0) is considered content.
     */
    private static function has_content($value) {
        if ($value === null) return false;
        if ($value === '') return false;
        if ($value === false) return false;
        if (is_array($value) && empty($value)) return false;
        return true;
    }
}
