<?php
namespace Wexoe\Core\Helpers;

use Wexoe\Core\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * SSOT collection-lookups (core_coworkers, core_partners, core_testimonials).
 *
 * Scope-array stöder:
 *   - country         (Code som "SE", record_id, eller utelämnat)
 *   - division        (slug, record_id, eller utelämnat)
 *   - customer_type   (slug eller record_id; bara för testimonials)
 *   - limit           (int, max antal records att returnera; default obegränsat)
 *   - featured_only   (bool, bara testimonials med Featured=true)
 *
 * Records returneras sorterade på `order` (lägre värde först).
 * Records med tom scope-länk räknas som "globalt synliga" (matchar alla scope-värden).
 * Records med `active = false` filtreras alltid bort.
 */
class Collections {

    /** @param array $scope */
    public static function coworkers_for_scope($scope = []) {
        return self::filter_collection('core_coworkers', $scope);
    }

    /** @param array $scope */
    public static function partners_for_scope($scope = []) {
        return self::filter_collection('core_partners', $scope);
    }

    /** @param array $scope */
    public static function testimonials_for_scope($scope = []) {
        return self::filter_collection('core_testimonials', $scope, true);
    }

    /* --------------------------------------------------------
       INTERNAL
       -------------------------------------------------------- */

    private static function filter_collection($entity_name, array $scope, $allow_customer_type = false) {
        $repo = Core::entity($entity_name);
        if ($repo === null) return [];

        $country_id = self::resolve_country_id($scope);
        $division_id = self::resolve_division_id($scope);
        $customer_type_id = $allow_customer_type ? self::resolve_customer_type_id($scope) : null;
        $featured_only = !empty($scope['featured_only']);
        $limit = isset($scope['limit']) ? max(0, (int) $scope['limit']) : 0;

        $all = $repo->all(['active' => true]);
        $matches = [];
        foreach ($all as $rec) {
            if ($featured_only && empty($rec['featured'])) continue;
            if (!self::scope_link_matches($rec, 'country_ids', $country_id)) continue;
            if (!self::scope_link_matches($rec, 'division_ids', $division_id)) continue;
            if ($allow_customer_type && !self::scope_link_matches($rec, 'customer_type_ids', $customer_type_id)) continue;
            $matches[] = $rec;
        }

        usort($matches, function ($a, $b) {
            $oa = isset($a['order']) ? (float) $a['order'] : 999.0;
            $ob = isset($b['order']) ? (float) $b['order'] : 999.0;
            if ($oa === $ob) return 0;
            return $oa < $ob ? -1 : 1;
        });

        if ($limit > 0 && count($matches) > $limit) {
            $matches = array_slice($matches, 0, $limit);
        }
        return $matches;
    }

    /**
     * Records match a scope if the scope is unset (no filter), OR
     * the record's link-array is empty (globally visible), OR
     * the record's link-array contains the scope record_id.
     */
    private static function scope_link_matches($record, $field, $scope_record_id) {
        if ($scope_record_id === null) return true;
        $ids = isset($record[$field]) && is_array($record[$field]) ? $record[$field] : [];
        if (empty($ids)) return true;
        return in_array($scope_record_id, $ids, true);
    }

    private static function resolve_country_id(array $scope) {
        if (!isset($scope['country']) || $scope['country'] === '' || $scope['country'] === null) return null;
        $v = $scope['country'];
        if (is_string($v) && strpos($v, 'rec') === 0 && strlen($v) === 17) return $v;
        $repo = Core::entity('core_countries');
        if ($repo === null) return null;
        $rec = $repo->find_by('code', strtoupper((string) $v));
        return $rec !== null && isset($rec['_record_id']) ? $rec['_record_id'] : null;
    }

    private static function resolve_division_id(array $scope) {
        if (!isset($scope['division']) || $scope['division'] === '' || $scope['division'] === null) return null;
        $v = $scope['division'];
        if (is_string($v) && strpos($v, 'rec') === 0 && strlen($v) === 17) return $v;
        $repo = Core::entity('core_divisions');
        if ($repo === null) return null;
        $rec = $repo->find_by('slug', strtolower((string) $v));
        return $rec !== null && isset($rec['_record_id']) ? $rec['_record_id'] : null;
    }

    private static function resolve_customer_type_id(array $scope) {
        if (!isset($scope['customer_type']) || $scope['customer_type'] === '' || $scope['customer_type'] === null) return null;
        $v = $scope['customer_type'];
        if (is_string($v) && strpos($v, 'rec') === 0 && strlen($v) === 17) return $v;
        $repo = Core::entity('core_customer_types');
        if ($repo === null) return null;
        $rec = $repo->find_by('slug', strtolower((string) $v));
        return $rec !== null && isset($rec['_record_id']) ? $rec['_record_id'] : null;
    }
}
