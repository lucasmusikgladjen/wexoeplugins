<?php
namespace Wexoe\Core\Helpers;

use Wexoe\Core\Logger;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Central permalink-tjänst — det ENDA stället som vet hur en entitets
 * publika URL ser ut.
 *
 * Bakgrund: tidigare byggde varje feature-plugin sina egna case-länkar för
 * hand ("/case/" + slug), på tre olika sätt och utan att respektera
 * landsprefix. Det gav inkonsekventa och spröda länkar. All sådan logik
 * bor nu här.
 *
 * Användning (feature-plugins):
 *   $url = Permalink::for_record('cms_cases', $case);   // legacy-override + slug
 *   $url = Permalink::build('cms_cases', 'volvo-trucks'); // ren slug → path
 *
 * Multi-country: prependar core_countries.url_prefix när den är ifylld.
 * Default-landet (idag SE) har tom prefix → URL:en lämnas oförändrad, så
 * dagens single-country-setup beter sig exakt som förut.
 */
class Permalink {

    /**
     * Route-mönster per entitet. {slug} ersätts med en URL-encodad slug.
     *
     * cms_cases är den kanoniska case-entiteten; wexoe-case-pluginet renderar
     * den via [wexoe_case slug="..."]. (Tidigare alias 'cases' och 'case_pages'
     * konsoliderades bort i PR 2.) Lägg till nya entiteter HÄR — aldrig genom
     * att handbygga paths i feature-plugins.
     *
     * @return array<string,string>
     */
    private static function patterns() {
        return [
            'cms_cases' => '/case/{slug}/',
        ];
    }

    /**
     * Publik URL för ett normaliserat record. Preferensordning:
     *   1. legacy_external_url (cases som fortfarande bor på gammal WP/PDF)
     *   2. byggd path från slug via entitetens mönster (+ ev. landsprefix)
     *   3. '' (inget visningsbart — anroparen får dölja länken)
     *
     * @param string $entity Entitetsnamn (matchar patterns()-nyckel)
     * @param array  $record Normaliserat record från Core
     * @return string
     */
    public static function for_record($entity, $record) {
        if (!is_array($record)) {
            return '';
        }
        $legacy = isset($record['legacy_external_url']) ? trim((string) $record['legacy_external_url']) : '';
        if ($legacy !== '') {
            return $legacy;
        }
        $slug = isset($record['slug']) ? trim((string) $record['slug']) : '';
        return self::build($entity, $slug);
    }

    /**
     * Ren slug → publik path. Tom slug eller okänd entitet → ''.
     *
     * @param string $entity
     * @param string $slug
     * @return string
     */
    public static function build($entity, $slug) {
        $slug = trim((string) $slug);
        if ($slug === '') {
            return '';
        }
        $patterns = self::patterns();
        if (!isset($patterns[$entity])) {
            Logger::warning('Permalink: okänd entitet — kan inte bygga URL', [
                'entity' => $entity,
                'slug'   => $slug,
            ]);
            return '';
        }
        $path = str_replace('{slug}', rawurlencode($slug), $patterns[$entity]);
        return self::with_country_prefix($path);
    }

    /**
     * Prependa aktuell countrys url_prefix om den är ifylld. Tom prefix
     * (dagens default-land) → path oförändrad.
     */
    private static function with_country_prefix($path) {
        $prefix = '';
        if (class_exists(Context::class)) {
            $rec = Context::current_country_record();
            if (is_array($rec) && isset($rec['url_prefix'])) {
                $prefix = trim((string) $rec['url_prefix'], "/ \t\n\r");
            }
        }
        if ($prefix === '') {
            return $path;
        }
        return '/' . $prefix . $path;
    }
}
