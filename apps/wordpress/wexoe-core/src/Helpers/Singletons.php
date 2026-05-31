<?php
namespace Wexoe\Core\Helpers;

use Wexoe\Core\Core;
use Wexoe\Core\Logger;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * SSOT singleton-lookups (core_company, core_graphic_profile).
 *
 * Mönster per anrop:
 *   1. Försök matcha record där det relevanta länk-fältet pekar på scope-record.
 *   2. Om ingen match: returnera record med `is_default = true`.
 *   3. Om fortfarande ingen match: returnera null + logga warning.
 */
class Singletons {

    /**
     * Hämta `core_company`-record för ett land. Land identifieras via Code (t.ex. "SE")
     * eller record_id (rec…). Fall tillbaka till default.
     *
     * @param string|null $country  Code, record_id, eller null för auto-detect via Context.
     * @return array|null
     */
    public static function company_for_country($country = null) {
        $company_repo = Core::entity('core_company');
        $country_repo = Core::entity('core_countries');
        if ($company_repo === null) return null;

        $country_record_id = self::resolve_country_record_id($country, $country_repo);

        if ($country_record_id !== null && $country_repo !== null) {
            $all = $company_repo->all();
            foreach ($all as $c) {
                if (isset($c['country_ids']) && in_array($country_record_id, $c['country_ids'], true)) {
                    return $c;
                }
            }
        }

        $default = $company_repo->find_by('is_default', true);
        if ($default !== null) return $default;

        Logger::warning('Singletons::company_for_country: ingen match och ingen default', [
            'country' => $country,
        ]);
        return null;
    }

    /**
     * Hämta `core_graphic_profile`-record för en division (slug eller record_id).
     * Fall tillbaka till default-profil om ingen division-match finns.
     *
     * @param string|null $division
     * @return array|null
     */
    public static function graphic_profile_for_division($division = null) {
        $profile_repo = Core::entity('core_graphic_profile');
        $division_repo = Core::entity('core_divisions');
        if ($profile_repo === null) return null;

        $division_record_id = self::resolve_division_record_id($division, $division_repo);

        if ($division_record_id !== null) {
            $all = $profile_repo->all();
            foreach ($all as $p) {
                if (isset($p['division_ids']) && in_array($division_record_id, $p['division_ids'], true)) {
                    return $p;
                }
            }
        }

        $default = $profile_repo->find_by('is_default', true);
        if ($default !== null) return $default;

        Logger::warning('Singletons::graphic_profile_for_division: ingen match och ingen default', [
            'division' => $division,
        ]);
        return null;
    }

    /* --------------------------------------------------------
       INTERNAL
       -------------------------------------------------------- */

    private static function resolve_country_record_id($country, $country_repo) {
        if ($country === null || $country === '') {
            $rec = Context::current_country_record();
            return $rec !== null && isset($rec['_record_id']) ? $rec['_record_id'] : null;
        }
        if (is_string($country) && strpos($country, 'rec') === 0 && strlen($country) === 17) {
            return $country;
        }
        if ($country_repo === null) return null;
        $rec = $country_repo->find_by('code', strtoupper((string) $country));
        return $rec !== null && isset($rec['_record_id']) ? $rec['_record_id'] : null;
    }

    private static function resolve_division_record_id($division, $division_repo) {
        if ($division === null || $division === '') return null;
        if (is_string($division) && strpos($division, 'rec') === 0 && strlen($division) === 17) {
            return $division;
        }
        if ($division_repo === null) return null;
        $rec = $division_repo->find_by('slug', strtolower((string) $division));
        return $rec !== null && isset($rec['_record_id']) ? $rec['_record_id'] : null;
    }
}
