/**
 * Generisk record → state, driven av ett entity-schema (ARKITEKTURPLAN FAS 1).
 *
 * Builder-sidans motsvarighet till PHP:s `Normalizer`. Itererar schemats fält
 * och producerar ett platt state-objekt:
 *
 *   - Statenyckel = snake_case → camelCase  (`value_text_1` → `valueText1`).
 *   - `php_only`-fält och `block`-fält hoppas över (block hanteras separat av
 *     sidtypens mapper — FAS 3).
 *   - Värdekonvertering per typ speglar den hand-skrivna mappern:
 *       text/richtext/image/url/lines → string   (lines: rå multiline)
 *       int/float                     → number    (eller string om builder_as)
 *       bool                          → boolean
 *       link                          → string[]
 *
 * Beräknade UI-flaggor (t.ex. `showValue`) och nästlade block (t.ex.
 * `contactForm`) sätts av sidtypens mapper OVANPÅ detta resultat.
 */

import { asString, asBool, asNumber, asLinkIds } from '../airtable-helpers';
import {
  EntitySchema,
  normalizeFieldDef,
} from './entity-schema';

/** snake_case → camelCase. `stat_number` → `statNumber`, `value_text_1` → `valueText1`. */
export function snakeToCamel(s: string): string {
  return s.replace(/_([a-z0-9])/g, (_m, c: string) => c.toUpperCase());
}

export function stateFromRecord(
  record: { fields: Record<string, unknown> },
  schema: EntitySchema,
): Record<string, unknown> {
  const f = record.fields;
  const out: Record<string, unknown> = {};

  for (const [key, defRaw] of Object.entries(schema.fields)) {
    const def = normalizeFieldDef(defRaw);
    if (def.php_only || def.block) continue;

    const source = def.source ?? key;
    const stateKey = snakeToCamel(key);
    const raw = f[source];

    if (def.builder_as === 'string') {
      out[stateKey] = asString(raw);
      continue;
    }

    switch (def.type) {
      case 'bool':
        out[stateKey] = asBool(raw);
        break;
      case 'int':
      case 'float':
        out[stateKey] = asNumber(raw);
        break;
      case 'link':
        out[stateKey] = asLinkIds(raw);
        break;
      // text | richtext | image | url | lines → string
      default:
        out[stateKey] = asString(raw);
    }
  }

  return out;
}
