<?php
/**
 * Entity schema: cms_section_tabs (shim)
 *
 * Faltlistan bor pa EXAKT ett stalle: schema/cms_section_tabs.json (committad synk-kopia av
 * packages/schema/entities/cms_section_tabs.json). Den har filen ar en tunn shim som later
 * Schema::from_json() oversatta JSON-schemat till den array-form
 * SchemaRegistry/Normalizer forvantar sig — read-beteendet ar oforandrat och
 * bevisat byte-identiskt (Normalizer doman-output ===). Samma JSON last av
 * buildern (TS) sa att en faltandring gors pa ett stalle.
 *
 * Lagg till/andra falt i JSON-filen, inte har.
 */

if (!defined('ABSPATH')) exit;

return \Wexoe\Core\Schema::from_json('cms_section_tabs');
