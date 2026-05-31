<?php
/**
 * Entity schema: customer_type_pages  (ARKITEKTURPLAN FAS 1 — pilot)
 *
 * Audience/kundtyp-LP — en publik sida per målgrupp (installatör, OEM,
 * panelbyggare etc.). Airtable-tabell: cms_customer_type_pages
 * (tblZufoWVNKPuJdMK) i Wexoe NY. Primärnyckel: 'slug'.
 *
 * Fältlistan bor numera på EXAKT ett ställe: `schema/cms_customer_type_pages.json`.
 * Den här filen är en tunn shim som översätter JSON-schemat till den array-form
 * SchemaRegistry/Normalizer förväntar sig — read-beteendet är oförändrat. Samma
 * JSON-fil läses av buildern (TS) så att en fältändring görs på ett ställe.
 *
 * Cases visas i strippen via `case_ids`-länken till `cms_cases`. Renderaren
 * hämtar varje case via `Core::entity('cms_cases')->find_by_ids($p['case_ids'])`
 * och läser bara `card_*`-fält + `slug`/`legacy_external_url` för länken.
 *
 * Lägg till/ändra fält i JSON-filen, inte här.
 */

if (!defined('ABSPATH')) exit;

return \Wexoe\Core\Schema::from_json('cms_customer_type_pages');
