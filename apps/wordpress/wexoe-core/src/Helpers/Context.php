<?php
namespace Wexoe\Core\Helpers;

use Wexoe\Core\Core;
use Wexoe\Core\Logger;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Country/Division-context detection for the current request.
 *
 * Resolve-kedja:
 *   1. Match home_url() host mot `core_countries.Domain`
 *   2. Match request URI:s första path-segment mot `core_countries.URL Prefix`
 *   3. Falla tillbaka på `core_company.Is Default = true` → dess Country-länk
 *   4. Returnera null + logga warning
 *
 * Per-request-cache: alla helpers slår upp samma scope max en gång per request.
 */
class Context {

    /** @var array|null|false  null = unresolved, false = resolved but missing, array = record */
    private static $current_country_cache = null;

    /** Reset cache (test-hook och cache-invalidate-flow). */
    public static function reset() {
        self::$current_country_cache = null;
    }

    /**
     * Returnera record-arrayen för aktuell country, eller null om ej känd.
     */
    public static function current_country_record() {
        if (self::$current_country_cache !== null) {
            return self::$current_country_cache === false ? null : self::$current_country_cache;
        }

        $repo = Core::entity('core_countries');
        if ($repo === null) {
            self::$current_country_cache = false;
            return null;
        }

        // 1. Domain-match
        $host = self::current_host();
        if ($host !== '') {
            $countries = $repo->all(['active' => true]);
            foreach ($countries as $c) {
                $domain = isset($c['domain']) ? trim($c['domain']) : '';
                if ($domain !== '' && self::host_matches($host, $domain)) {
                    self::$current_country_cache = $c;
                    return $c;
                }
            }
        }

        // 2. URL Prefix-match
        $prefix = self::current_url_prefix();
        if ($prefix !== '') {
            $countries = isset($countries) ? $countries : $repo->all(['active' => true]);
            foreach ($countries as $c) {
                $cp = isset($c['url_prefix']) ? trim($c['url_prefix'], "/ \t\n\r") : '';
                if ($cp !== '' && strcasecmp($cp, $prefix) === 0) {
                    self::$current_country_cache = $c;
                    return $c;
                }
            }
        }

        // 3. Fall tillbaka på default-company → Country
        $company_repo = Core::entity('core_company');
        if ($company_repo !== null) {
            $default = $company_repo->find_by('is_default', true);
            if ($default !== null && !empty($default['country_ids'])) {
                $repo_countries = isset($countries) ? $countries : $repo->all();
                foreach ($repo_countries as $c) {
                    if (isset($c['_record_id']) && in_array($c['_record_id'], $default['country_ids'], true)) {
                        self::$current_country_cache = $c;
                        return $c;
                    }
                }
            }
        }

        // 4. Inget hittat
        Logger::warning('Context: kunde inte härleda aktuell country', [
            'host' => $host,
            'url_prefix' => $prefix,
        ]);
        self::$current_country_cache = false;
        return null;
    }

    /**
     * Returnera nuvarande country-kod (t.ex. "SE") eller null.
     */
    public static function current_country_code() {
        $rec = self::current_country_record();
        return $rec !== null && isset($rec['code']) ? $rec['code'] : null;
    }

    /**
     * Returnera nuvarande division-slug om en sådan kan härledas från URL.
     * MVP: ingen URL-division-detektering. Returnerar null tills regler finns.
     */
    public static function current_division_slug() {
        return null;
    }

    /* --------------------------------------------------------
       INTERNAL
       -------------------------------------------------------- */

    private static function current_host() {
        $url = function_exists('home_url') ? home_url('/') : '';
        if ($url === '') return '';
        $parsed = parse_url($url);
        return isset($parsed['host']) ? strtolower($parsed['host']) : '';
    }

    private static function current_url_prefix() {
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        if ($uri === '') return '';
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') return '';
        $segments = array_values(array_filter(explode('/', $path), function ($s) {
            return $s !== '';
        }));
        return isset($segments[0]) ? strtolower($segments[0]) : '';
    }

    /**
     * Match host against a configured domain — accepts exact match
     * and "subdomain of"-match (so wp.wexoe.se matches wexoe.se).
     */
    private static function host_matches($host, $domain) {
        $h = strtolower(ltrim($host, '.'));
        $d = strtolower(ltrim($domain, '.'));
        if ($h === '' || $d === '') return false;
        if ($h === $d) return true;
        $suffix = '.' . $d;
        return substr($h, -strlen($suffix)) === $suffix;
    }
}
