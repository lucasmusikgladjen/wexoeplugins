/**
 * Generisk, DETERMINISTISK state → Airtable-fält (ARKITEKTURPLAN FAS 2).
 *
 * Inversen av `stateFromRecord`. Ersätter Claude-mellanlaget på skrivvägen för
 * scheman som är rena fält-mappningar (t.ex. customer-type). Ingen LLM, ingen
 * latens, inget tredjepartsberoende — och den kan aldrig "hallucinera bort"
 * innehåll.
 *
 * Mode-medveten, speglar de tidigare schema-MD-reglerna:
 *   CREATE — utelämna tomma textfält; booleans inkluderas alltid; tomt
 *            number utelämnas.
 *   UPDATE — inkludera tomma textfält som '' (så Airtable rensar dem); tomt
 *            number skickas som null; booleans alltid.
 *
 * Hoppar över `php_only`-fält (PHP-only), `block`-fält (delade block hanteras
 * av sidtypens egen wrapper, FAS 3) och fält i `opts.omit` (t.ex. read-only
 * länkar som builder aldrig skriver).
 *
 * `link`-fält som ÄR skrivbara emittas som array (UPDATE skickar även tom array
 * så en länkning kan tas bort; CREATE utelämnar tom).
 */

import {
  EntitySchema,
  normalizeFieldDef,
} from './entity-schema';
import { snakeToCamel } from './to-state';

export type WriteMode = 'create' | 'update';

export interface ToFieldsOptions {
  /** Schema-fältnamn (snake_case) som builder aldrig skriver (read-only). */
  omit?: readonly string[];
}

export function toFields(
  state: Record<string, unknown>,
  schema: EntitySchema,
  mode: WriteMode,
  opts: ToFieldsOptions = {},
): Record<string, unknown> {
  const omit = new Set(opts.omit ?? []);
  const out: Record<string, unknown> = {};

  for (const [key, defRaw] of Object.entries(schema.fields)) {
    const def = normalizeFieldDef(defRaw);
    if (def.php_only || def.block || omit.has(key)) continue;

    const source = def.source ?? key;
    const stateKey = snakeToCamel(key);
    const v = state[stateKey];

    // Booleans: alltid (true/false), aldrig utelämnade.
    if (def.type === 'bool') {
      out[source] = v === true;
      continue;
    }

    // Numeriska fält (även de som redigeras som sträng via builder_as).
    if (def.type === 'int' || def.type === 'float') {
      const s = v == null ? '' : String(v).trim();
      if (s === '') {
        if (mode === 'update') out[source] = null; // rensa
        // create: utelämna
      } else {
        const n = Number(s);
        out[source] = Number.isNaN(n) ? (mode === 'update' ? null : undefined) : n;
        if (out[source] === undefined) delete out[source];
      }
      continue;
    }

    // Skrivbara länkar: array av record-ids.
    if (def.type === 'link') {
      const arr = Array.isArray(v) ? v.filter((x) => typeof x === 'string') : [];
      if (mode === 'update' || arr.length > 0) out[source] = arr;
      continue;
    }

    // text | richtext | image | url | lines → sträng.
    const s = v == null ? '' : String(v);
    if (mode === 'update') {
      out[source] = s; // skicka '' för att rensa
    } else if (s !== '') {
      out[source] = s; // create: utelämna tomma
    }
  }

  return out;
}
