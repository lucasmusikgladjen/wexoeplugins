<?php

use Wexoe\Core\Schema;
use Wexoe\Core\Normalizer;

/**
 * Lås single-source-schemat (FAS 1.3).
 *
 * Bevisar att JSON-källan i packages/schema/entities/ är den auktoritativa
 * fältlistan och att den synkade kopian (wexoe-core/schema/) som drift läser
 * via Schema::from_json() bygger EXAKT samma schema som from_array() på
 * originalet — samt att varje entitet normaliserar ett syntetiskt record utan
 * fel och att pseudo_array/type-strukturen bevaras genom byggaren.
 *
 * WEXOE_CORE_PATH definieras i tests/bootstrap.php → from_json hittar kopian.
 */

/** Repo-rot (apps/wordpress/tests/Schema → 4 nivåer upp). */
function se_repo_root(): string
{
    return dirname(__DIR__, 4);
}

/** Originalkatalogen (sanningskällan). */
function se_src_dir(): string
{
    return se_repo_root() . '/packages/schema/entities';
}

/** Lista alla entiteter (filnamn utan .json) från originalet. */
function se_entities(): array
{
    $out = [];
    foreach (glob(se_src_dir() . '/*.json') as $f) {
        $out[] = basename($f, '.json');
    }
    sort($out);
    return $out;
}

/** Avkoda ett original-JSON till array. */
function se_load_json(string $entity): array
{
    $raw = file_get_contents(se_src_dir() . '/' . $entity . '.json');
    $json = json_decode($raw, true);
    expect($json)->toBeArray();
    return $json;
}

/**
 * Bygg ett syntetiskt Airtable-record som ger varje källfält ett typkorrekt
 * värde (bool→true, int→7, float→3.5, lines→"a\nb\nc", link→[..],
 * attachment→[{..}], pseudo_array→fyll alla numrerade slots, text→sträng).
 */
function se_synth_record(array $schema): array
{
    $fields = [];
    $specs = isset($schema['fields']) && is_array($schema['fields']) ? $schema['fields'] : [];
    foreach ($specs as $key => $spec) {
        if (is_string($spec)) {
            $fields[$spec] = 'val_' . $spec;
            continue;
        }
        if (!is_array($spec)) {
            continue;
        }
        $type = $spec['type'] ?? 'text';
        $source = $spec['source'] ?? $key;
        switch ($type) {
            case 'bool':
                $fields[$source] = true;
                break;
            case 'int':
                $fields[$source] = 7;
                break;
            case 'float':
                $fields[$source] = 3.5;
                break;
            case 'lines':
                $fields[$source] = "a\nb\nc";
                break;
            case 'link':
            case 'link_ids':
                $fields[$source] = ['rec1', 'rec2'];
                break;
            case 'attachment':
                $fields[$source] = [[
                    'url' => 'https://x/y.png', 'filename' => 'y.png',
                    'width' => 100, 'height' => 50, 'size' => 1234, 'type' => 'image/png',
                ]];
                break;
            case 'attachments':
                $fields[$source] = [
                    ['url' => 'https://x/a.png', 'filename' => 'a.png', 'width' => 10, 'height' => 20, 'size' => 1, 'type' => 'image/png'],
                ];
                break;
            case 'pseudo_array':
                $prefix = $spec['prefix'] ?? '';
                $count = (int) ($spec['count'] ?? 0);
                $sep = $spec['separator'] ?? '_';
                foreach (($spec['fields'] ?? []) as $suffix) {
                    for ($n = 1; $n <= $count; $n++) {
                        $fields[$prefix . $sep . $n . $sep . $suffix] = 'ps';
                    }
                }
                break;
            case 'string':
                $fields[$source] = 'str_' . $source;
                break;
            default: // text + diskriminator 'type'
                $fields[$source] = 'val_' . $source;
                break;
        }
    }
    return ['id' => 'recSYNTH', 'fields' => $fields];
}

it('har minst de 26 migrerade entiteterna i packages/schema/entities', function () {
    expect(count(se_entities()))->toBeGreaterThanOrEqual(26);
});

it('(a) varje JSON-original bygger ett giltigt schema via from_array', function (string $entity) {
    $schema = Schema::from_array(se_load_json($entity));
    expect($schema)->toBeArray()->toHaveKey('fields');
    expect($schema['fields'])->toBeArray();
    // Varje fält-spec ska vara en array med en 'type' efter byggaren.
    foreach ($schema['fields'] as $spec) {
        expect($spec)->toBeArray()->toHaveKey('type');
    }
})->with(se_entities());

it('(b) from_json (synkad kopia) === from_array (original) per entitet', function (string $entity) {
    $fromArray = Schema::from_array(se_load_json($entity));
    $fromJson = Schema::from_json($entity); // läser wexoe-core/schema/<entity>.json
    expect($fromJson)->toBe($fromArray); // strikt === (samma nycklar, ordning, värden)
})->with(se_entities());

it('(c) pseudo_array- och type-strukturen bevaras genom byggaren', function () {
    // cms_cases har tre pseudo_array-fält; automation_product_navigation har en
    // 'type'-diskriminator. Bygg dem och verifiera strukturen.
    $cases = Schema::from_array(se_load_json('cms_cases'));
    foreach (['quick_stats', 'results', 'gallery_images'] as $pa) {
        expect($cases['fields'])->toHaveKey($pa);
        $spec = $cases['fields'][$pa];
        expect($spec['type'])->toBe('pseudo_array');
        expect($spec)->toHaveKey('prefix')->toHaveKey('count')->toHaveKey('fields');
        expect($spec['count'])->toBeInt();
        expect($spec['fields'])->toBeArray()->not->toBeEmpty();
    }
    $nav = Schema::from_array(se_load_json('automation_product_navigation'));
    expect($nav['fields'])->toHaveKey('type');
    expect($nav['fields']['type']['type'])->toBe('type');
    expect($nav['fields']['type']['source'])->toBe('type');
});

it('(d) varje entitet normaliserar ett syntetiskt record utan fel', function (string $entity) {
    $schema = Schema::from_json($entity);
    $record = se_synth_record($schema);
    $out = Normalizer::normalize_record($record, $schema);
    expect($out)->toBeArray()->toHaveKey('_record_id');
    expect($out['_record_id'])->toBe('recSYNTH');
    // Output ska ha exakt ett domännyckel-element per schema-fält (+ _record_id).
    expect(count($out))->toBe(count($schema['fields']) + 1);
})->with(se_entities());

it('(e) table_id => null bevaras för legacy/pending-migration-entiteter', function () {
    // automation_offerings, automation_product_navigation, inbox_form_submissions
    // har table_id: null i JSON och MÅSTE byggas med table_id-nyckel = null
    // (samma som den handskrivna arrayen — annars driftar SchemaRegistry-utfallet).
    foreach (['automation_offerings', 'automation_product_navigation', 'inbox_form_submissions'] as $e) {
        $schema = Schema::from_array(se_load_json($e));
        expect($schema)->toHaveKey('table_id');
        expect($schema['table_id'])->toBeNull();
    }
});
