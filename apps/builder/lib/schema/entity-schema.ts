/**
 * Entity-schema-format (ARKITEKTURPLAN FAS 0–1).
 *
 * Speglar JSON-formatet i `wexoe-core/schema/*.json` (kanoniskt där). Buildern
 * läser en kopia under `wexoebuilder/schema/` och härleder record → state via
 * `lib/schema/to-state.ts`. Samma fältlista driver PHP-read (Normalizer) och
 * builder-read — definierad på ett ställe.
 */

export type SchemaFieldType =
  | 'text'
  | 'richtext'
  | 'image'
  | 'url'
  | 'int'
  | 'float'
  | 'bool'
  | 'lines'
  | 'link';

export interface SchemaFieldDef {
  type: SchemaFieldType;
  /** Airtable-fältnamn; default = nyckeln. */
  source?: string;
  /** För `type: 'link'` — måltabell (dokumentation). */
  entity?: string;
  /** Fält som bara läses av PHP — buildern hoppar över det. */
  php_only?: boolean;
  /** Fält som hör till ett delat block (FAS 3) — buildern hoppar över top-level. */
  block?: string;
  /** Builderns representation avviker från read-typen (t.ex. int redigeras som sträng). */
  builder_as?: 'string';
}

export interface EntitySchema {
  /** Tabellnamn (dokumentation). */
  table: string;
  /** Airtable table-id. */
  table_id: string;
  /** `'ssot'` | `'legacy'` | en explicit `app...`-bas. */
  base?: string;
  primary_key?: string;
  cache_ttl?: number;
  required?: string[];
  /** Fältdefinitioner, keyade på Airtable-fältnamn. Kort-form: bara typsträng. */
  fields: Record<string, SchemaFieldDef | SchemaFieldType>;
}

export function normalizeFieldDef(def: SchemaFieldDef | SchemaFieldType): SchemaFieldDef {
  return typeof def === 'string' ? { type: def } : def;
}
