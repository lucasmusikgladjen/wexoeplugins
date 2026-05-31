<?php
namespace Wexoe\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Single-source schema loader (ARKITEKTURPLAN FAS 0–1).
 *
 * Ett fälts definition ska bo på EXAKT ett ställe: en neutral JSON-fil i
 * `wexoe-core/schema/<table>.json`. Den filen läses av både wexoe-core (här,
 * PHP-read) och av buildern (TS). Tidigare upprepades samma fältlista i
 * `entities/*.php`, `write-entities/*.php`, builderns `*-types.ts`,
 * `*-mapper.ts` och `airtable-schema-*.md` — 6–10 kopior per sidtyp.
 *
 * `from_json()` översätter JSON-schemat till exakt den array-form som
 * `SchemaRegistry` + `Normalizer` redan förväntar sig. Read-beteendet är
 * därför oförändrat: en entity-fil blir en enrads-shim
 * (`return \Wexoe\Core\Schema::from_json('<table>');`) i stället för en
 * handskriven fältlista.
 *
 * JSON-format (superset av Normalizer-typerna):
 *   {
 *     "table": "cms_customer_type_pages",   // dokumentation
 *     "table_id": "tbl...",                 // krävs
 *     "base": "ssot" | "legacy" | "app...", // valfritt; "ssot" → Plugin::SSOT_BASE_ID
 *     "primary_key": "slug",
 *     "cache_ttl": 86400,
 *     "required": ["slug"],
 *     "fields": {
 *       "<airtable_field>": {
 *         "type": "text|richtext|int|float|bool|image|url|link|lines",
 *         "source": "<airtable_field>",   // valfritt, default = nyckeln
 *         "entity": "<entity>",           // för type=link (dokumentation)
 *         // Builder-only hints — IGNORERAS av PHP:
 *         "php_only": true,               // fält som bara läses av PHP
 *         "block": "contact_form",        // fält som hör till ett delat block
 *         "builder_as": "string"          // hur buildern representerar fältet
 *       }
 *     }
 *   }
 *
 * Read-typ-mappning (JSON → Normalizer):
 *   text|richtext|image|url → 'text'  (string passthrough, identiskt resultat)
 *   int → 'int', float → 'float', bool → 'bool', lines → 'lines', link → 'link'
 */
class Schema {

    /** @var array<string, array> Memoiserade JSON-scheman per tabell. */
    private static $cache = [];

    /**
     * Ladda ett JSON-schema och returnera det i SchemaRegistry/Normalizer-form.
     *
     * @param string $table Filnamn utan .php/.json (t.ex. 'cms_customer_type_pages').
     * @return array Schema-array, eller en tom (men giltig) stub vid fel så att
     *               SchemaRegistry loggar "missing key" i stället för att fatala.
     */
    public static function from_json($table) {
        if (isset(self::$cache[$table])) {
            return self::$cache[$table];
        }

        $file = self::schema_path() . $table . '.json';
        if (!file_exists($file)) {
            self::log_error('JSON-schema saknas', ['table' => $table, 'path' => $file]);
            return [];
        }

        $raw = file_get_contents($file);
        $json = json_decode($raw, true);
        if (!is_array($json)) {
            self::log_error('JSON-schema kunde inte parsas', [
                'table' => $table,
                'json_error' => json_last_error_msg(),
            ]);
            return [];
        }

        $schema = self::from_array($json);
        self::$cache[$table] = $schema;
        return $schema;
    }

    /**
     * Bygg ett SchemaRegistry/Normalizer-redo schema direkt ur en redan
     * avkodad JSON-array (in-memory). `from_json()` = läs fil + `from_array()`.
     *
     * Publik så att ekvivalenstester (och buildern, vid behov) kan bygga ett
     * schema utan att gå via disk.
     *
     * @param array $json Avkodat JSON-objekt (samma form som en *.json-fil).
     * @return array Schema-array i Normalizer-form.
     */
    public static function from_array(array $json) {
        return self::build_schema($json);
    }

    /**
     * Översätt det dekodade JSON-objektet till en Normalizer-redo schema-array.
     */
    private static function build_schema(array $json) {
        $schema = [];

        // base → base_id
        $base = isset($json['base']) ? (string) $json['base'] : '';
        if ($base === 'ssot') {
            $schema['base_id'] = Plugin::SSOT_BASE_ID;
        } elseif ($base !== '' && $base !== 'legacy' && strpos($base, 'app') === 0) {
            // Explicit base-id (t.ex. en specifik app...-sträng).
            $schema['base_id'] = $base;
        }
        // "legacy" eller utelämnat → lämna base_id osatt (plugin-konfigens default).

        // table_id: en explicit `null` i JSON bevaras som `table_id => null`
        // (legacy/pending-migration-entiteter, t.ex. automation_offerings).
        // Detta matchar den handskrivna arrayen exakt — `isset()`-konsumenterna
        // (EntityRepository/SchemaRegistry) behandlar null = saknad table_id.
        if (array_key_exists('table_id', $json)) {
            $schema['table_id'] = $json['table_id'] === null ? null : (string) $json['table_id'];
        }
        if (isset($json['primary_key'])) {
            $schema['primary_key'] = (string) $json['primary_key'];
        }
        $schema['cache_ttl'] = isset($json['cache_ttl']) ? (int) $json['cache_ttl'] : 86400;
        $schema['required'] = (isset($json['required']) && is_array($json['required']))
            ? array_values($json['required'])
            : [];

        $schema['fields'] = [];
        $fields = (isset($json['fields']) && is_array($json['fields'])) ? $json['fields'] : [];
        foreach ($fields as $key => $def) {
            $schema['fields'][$key] = self::build_field($key, $def);
        }

        return $schema;
    }

    /**
     * Översätt en fältdefinition till Normalizer-form. Builder-only hints
     * (php_only, block, builder_as) ignoreras här — PHP läser alla fält lika.
     *
     * @param string       $key Domän-/Airtable-fältnamn.
     * @param array|string $def Fältdefinition (objekt) eller kort-form (typsträng).
     * @return array {source, type[, entity]}
     */
    private static function build_field($key, $def) {
        if (is_string($def)) {
            $def = ['type' => $def];
        }
        if (!is_array($def)) {
            $def = ['type' => 'text'];
        }

        $type = isset($def['type']) ? (string) $def['type'] : 'text';
        $source = isset($def['source']) ? (string) $def['source'] : $key;

        // pseudo_array: numrerade slot-fält ("{prefix}{sep}{i}{sep}{suffix}")
        // kollapsas till en array av sektioner av Normalizer. Passa igenom
        // prefix/count/separator/fields exakt så Normalizer hittar dem.
        if ($type === 'pseudo_array') {
            $field = [
                'type' => 'pseudo_array',
                'prefix' => isset($def['prefix']) ? (string) $def['prefix'] : '',
                'count' => isset($def['count']) ? (int) $def['count'] : 0,
            ];
            // separator är valfri (Normalizer-default '_') — bevara bara om satt
            // i JSON, så den handskrivna arrayen (utan separator) matchas exakt.
            if (array_key_exists('separator', $def)) {
                $field['separator'] = (string) $def['separator'];
            }
            $field['fields'] = (isset($def['fields']) && is_array($def['fields']))
                ? $def['fields']
                : [];
            return $field;
        }

        // Diskriminator-fält: ett Airtable-fält vars JSON-typ bokstavligen är
        // "type" (t.ex. section_type-liknande "type"-kolumnen på
        // automation_product_navigation). Bevaras som {source, type:'type'}.
        // Normalizer har ingen 'type'-gren → faller till textpassthrough,
        // dvs samma domän-output som en ren sträng-spec.
        if ($type === 'type') {
            return ['source' => $source, 'type' => 'type'];
        }

        // JSON-typ → Normalizer-typ.
        switch ($type) {
            case 'int':
                $norm_type = 'int';
                break;
            case 'float':
                $norm_type = 'float';
                break;
            case 'bool':
                $norm_type = 'bool';
                break;
            case 'lines':
                $norm_type = 'lines';
                break;
            case 'link':
                $norm_type = 'link';
                break;
            case 'attachment':
                $norm_type = 'attachment';
                break;
            case 'attachments':
                $norm_type = 'attachments';
                break;
            // text, richtext, image, url och okända → ren textpassthrough.
            default:
                $norm_type = 'text';
                break;
        }

        $field = ['source' => $source, 'type' => $norm_type];
        if ($norm_type === 'link' && isset($def['entity'])) {
            $field['entity'] = (string) $def['entity'];
        }
        return $field;
    }

    private static function schema_path() {
        return WEXOE_CORE_PATH . 'schema/';
    }

    private static function log_error($message, array $context) {
        if (class_exists('\\Wexoe\\Core\\Logger')) {
            Logger::error('Schema: ' . $message, $context);
        }
    }
}
