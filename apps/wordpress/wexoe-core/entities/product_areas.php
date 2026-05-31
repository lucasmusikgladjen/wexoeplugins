<?php
/**
 * Entity schema: product_areas (shim)
 *
 * Faltlistan bor pa EXAKT ett stalle: schema/product_areas.json (committad synk-kopia av
 * packages/schema/entities/product_areas.json). Den har filen ar en tunn shim som later
 * Schema::from_json() oversatta JSON-schemat till den array-form
 * SchemaRegistry/Normalizer forvantar sig — read-beteendet ar oforandrat och
 * bevisat byte-identiskt (Normalizer doman-output ===). Samma JSON last av
 * buildern (TS) sa att en faltandring gors pa ett stalle.
 *
 * Lagg till/andra falt i JSON-filen, inte har.
 */

if (!defined('ABSPATH')) exit;

return \Wexoe\Core\Schema::from_json('product_areas');
