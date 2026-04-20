<?php
namespace Wexoe\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Domain-aware data access for one entity.
 *
 * Each repository instance is bound to a single entity (e.g. "partners") and
 * its schema. Feature plugins should never construct this directly — go through
 * Core::entity('partners') or SchemaRegistry::get_repository('partners').
 *
 * Caching strategy:
 *   - All records cached as a single blob under 'entity:{name}:all'
 *   - Default TTL 24h with ±5% jitter (to prevent thundering herd)
 *   - On cache miss: fetch from Airtable, normalize, cache, return
 *   - On Airtable failure with stale cache available: return stale + log warning
 *   - On Airtable failure with no cache: return empty array + log error
 *
 * find() and findBy() iterate the cached all() result. For small-to-medium
 * tables (sub-1000 records) this is a non-issue. Larger tables would benefit
 * from filterByFormula queries — a future optimization.
 */
class EntityRepository {

    /** Cache key suffix for the "all records" blob. */
    const CACHE_SUFFIX_ALL = ':all';

    /** Default TTL jitter percentage. */
    const JITTER_PERCENT = 5;

    /** @var string */
    private $name;

    /** @var array */
    private $schema;

    public function __construct($name, $schema) {
        $this->name = $name;
        $this->schema = $schema;
    }

    /* --------------------------------------------------------
       PUBLIC API
       -------------------------------------------------------- */

    /**
     * Find a single record by its primary key value.
     *
     * @param string|int $primary_value
     * @return array|null Normalized record or null if not found
     */
    public function find($primary_value) {
        $primary_key = $this->get_primary_key();
        if ($primary_key === null) {
            Logger::error('Cannot find() — entity has no primary_key defined', [
                'entity' => $this->name,
            ]);
            return null;
        }
        return $this->find_by($primary_key, $primary_value);
    }

    /**
     * Return all records, optionally filtered by a map of field => value.
     *
     * @param array $filters  e.g. ['visible' => true, 'category' => 'automation']
     * @return array  Array of normalized records (empty array if none/error)
     */
    public function all($filters = []) {
        $records = $this->fetch_with_cache();
        if (!empty($filters)) {
            return $this->apply_filters($records, $filters);
        }
        return $records;
    }

    /**
     * Find the first record where $field === $value.
     *
     * @return array|null
     */
    public function find_by($field, $value) {
        $records = $this->fetch_with_cache();
        foreach ($records as $record) {
            if (array_key_exists($field, $record) && $record[$field] === $value) {
                return $record;
            }
        }
        return null;
    }

    /**
     * Find all records whose _record_id is in the given array.
     * Essential for resolving linked records (Airtable returns arrays of record IDs).
     *
     * @param string[] $record_ids Array of Airtable record IDs (rec...)
     * @return array Normalized records in the same order as the input IDs (missing IDs are skipped)
     */
    public function find_by_ids($record_ids) {
        if (empty($record_ids) || !is_array($record_ids)) {
            return [];
        }
        $records = $this->fetch_with_cache();
        // Build a lookup map for O(n) resolution
        $map = [];
        foreach ($records as $record) {
            if (isset($record['_record_id'])) {
                $map[$record['_record_id']] = $record;
            }
        }
        // Return in requested order, skip missing
        $result = [];
        foreach ($record_ids as $id) {
            if (isset($map[$id])) {
                $result[] = $map[$id];
            }
        }
        return $result;
    }

    /**
     * Clear this entity's cache. Returns number of cache entries removed.
     */
    public function clear_cache() {
        return Cache::delete_by_prefix($this->cache_key_prefix());
    }

    /**
     * Force refresh — clear cache and re-fetch from Airtable synchronously.
     *
     * @return array The freshly fetched records
     */
    public function force_refresh() {
        $this->clear_cache();
        return $this->fetch_with_cache();
    }

    /* --------------------------------------------------------
       INTROSPECTION (used by admin UI)
       -------------------------------------------------------- */

    public function get_name() {
        return $this->name;
    }

    public function get_schema() {
        return $this->schema;
    }

    public function get_primary_key() {
        return isset($this->schema['primary_key']) ? $this->schema['primary_key'] : null;
    }

    public function get_table_id() {
        return isset($this->schema['table_id']) ? $this->schema['table_id'] : null;
    }

    public function get_ttl() {
        return isset($this->schema['cache_ttl']) ? (int) $this->schema['cache_ttl'] : DAY_IN_SECONDS;
    }

    /**
     * Cache status for admin UI: cached / not cached, age, expiry, record count.
     */
    public function get_cache_status() {
        $cache_key = $this->cache_key_all();
        $full_transient_key = Cache::KEY_PREFIX . $cache_key;
        $timeout = get_option('_transient_timeout_' . $full_transient_key);

        if ($timeout === false || $timeout === '') {
            return ['cached' => false];
        }

        $expires_at = (int) $timeout;
        $ttl = $this->get_ttl();
        $cached_at = $expires_at - $ttl;
        $cached_data = Cache::get($cache_key);

        return [
            'cached' => true,
            'cached_at' => $cached_at,
            'expires_at' => $expires_at,
            'ttl' => $ttl,
            'is_expired' => $expires_at < time(),
            'record_count' => is_array($cached_data) ? count($cached_data) : 0,
        ];
    }

    /* --------------------------------------------------------
       INTERNAL: fetch + cache coordination
       -------------------------------------------------------- */

    /**
     * Get all records, using cache if available. On miss, fetch + normalize + cache.
     * On Airtable failure with stale cache, return stale. Otherwise empty array.
     */
    private function fetch_with_cache() {
        $cache_key = $this->cache_key_all();

        // 1. Try cache first
        $cached = Cache::get($cache_key);
        if ($cached !== null && is_array($cached)) {
            return $cached;
        }

        // 2. Cache miss — fetch from Airtable
        $table_id = $this->get_table_id();
        if (empty($table_id)) {
            Logger::error('Entity has no table_id', ['entity' => $this->name]);
            return [];
        }

        $airtable_result = AirtableClient::fetch_records($table_id);

        if (!$airtable_result['success']) {
            // Airtable failed. Nothing in cache (otherwise we'd have returned above).
            Logger::error('Airtable fetch failed for entity with no cache fallback', [
                'entity' => $this->name,
                'error' => isset($airtable_result['error']) ? $airtable_result['error'] : 'unknown',
                'error_type' => isset($airtable_result['error_type']) ? $airtable_result['error_type'] : 'unknown',
            ]);
            return [];
        }

        // 3. Normalize
        $normalized = $this->normalize_records($airtable_result['records']);

        // 4. Cache with jittered TTL
        $ttl = $this->jittered_ttl();
        Cache::set($cache_key, $normalized, $ttl);

        Logger::info('Entity fetched and cached', [
            'entity' => $this->name,
            'record_count' => count($normalized),
            'ttl_seconds' => $ttl,
        ]);

        return $normalized;
    }

    /**
     * Run Airtable records through the normalizer and filter out invalid ones.
     */
    private function normalize_records($airtable_records) {
        $result = [];
        foreach ($airtable_records as $raw) {
            $normalized = Normalizer::normalize_record($raw, $this->schema);
            if (Normalizer::validate_required($normalized, $this->schema, $this->name)) {
                $result[] = $normalized;
            }
        }
        return $result;
    }

    /**
     * Apply in-memory filter map to a record list.
     * Matches if ALL field/value pairs match (AND semantics).
     */
    private function apply_filters($records, $filters) {
        $out = [];
        foreach ($records as $record) {
            $match = true;
            foreach ($filters as $field => $expected) {
                if (!array_key_exists($field, $record)) {
                    $match = false;
                    break;
                }
                $actual = $record[$field];
                // Handle "tag in list" semantics for array fields
                if (is_array($actual) && !is_array($expected)) {
                    if (!in_array($expected, $actual, true)) {
                        $match = false;
                        break;
                    }
                } elseif ($actual !== $expected) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                $out[] = $record;
            }
        }
        return $out;
    }

    /* --------------------------------------------------------
       INTERNAL: key & TTL helpers
       -------------------------------------------------------- */

    private function cache_key_prefix() {
        return 'entity:' . $this->name . ':';
    }

    private function cache_key_all() {
        return $this->cache_key_prefix() . 'all';
    }

    /**
     * TTL with ±5% random jitter to prevent thundering-herd at expiry.
     */
    private function jittered_ttl() {
        $ttl = $this->get_ttl();
        $jitter_max = (int) round($ttl * (self::JITTER_PERCENT / 100));
        if ($jitter_max <= 0) return $ttl;
        return $ttl + random_int(-$jitter_max, $jitter_max);
    }
}
