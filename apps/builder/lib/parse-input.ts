/**
 * FAS 7 — Claude på INPUT-lagret (Lager A), ALDRIG på save (Lager B).
 *
 * Tar fri/rörig text (t.ex. ett inklistrat pressmeddelande, en mejltråd, ett
 * gammalt sidinnehåll) och strukturerar den till rena state-fält enligt en
 * entitets schema. Flödet:
 *
 *   fri text → [Claude, här] → ren state → marknadsföraren granskar/redigerar
 *            → [deterministisk toFields, FAS 2] → Airtable
 *
 * Detta är raka motsatsen till det gamla Claude-på-save-mönstret: Claude är
 * här ett OPT-IN importverktyg som producerar ett *förslag* användaren får
 * redigera, inte en obligatorisk, icke-deterministisk del av spar-vägen.
 * Inputens form spelar ingen roll — samma editor-state oavsett källa.
 *
 * Återanvänder FAS 1:s `EntitySchema` för att veta exakt vilka fält som finns
 * och vilken typ de har; resultatet är keyat på camelCase-state-nycklar så att
 * editorn kan göra `setState({ ...state, ...fields })` direkt.
 */

import {
  EntitySchema,
  SchemaFieldDef,
  normalizeFieldDef,
} from './schema/entity-schema';
import { snakeToCamel } from './schema/to-state';

const ANTHROPIC_MODEL = 'claude-sonnet-4-20250514';
const ANTHROPIC_VERSION = '2023-06-01';
const MAX_TOKENS = 2048;

/** Fält-typer Claude rimligen kan extrahera ur fri text. Booleans/länkar/
 *  numeriska utelämnas — de gissas inte tillförlitligt ur prosa. */
const PARSEABLE_TYPES = new Set<string>(['text', 'richtext', 'url', 'image', 'lines']);

interface ParseableField {
  stateKey: string;
  type: string;
}

function parseableFields(schema: EntitySchema): ParseableField[] {
  const out: ParseableField[] = [];
  for (const [key, defRaw] of Object.entries(schema.fields)) {
    const def: SchemaFieldDef = normalizeFieldDef(defRaw);
    if (def.php_only || def.block) continue;
    if (PARSEABLE_TYPES.has(def.type)) {
      out.push({ stateKey: snakeToCamel(key), type: def.type });
    }
  }
  return out;
}

export interface ParseResult {
  /** Delmängd av editor-state, camelCase-nycklar → strängvärden. */
  fields: Record<string, string>;
}

/**
 * Strukturera fri text till ett state-fragment via Claude. Off the save path.
 */
export async function parseFreeformToState(args: {
  text: string;
  schema: EntitySchema;
  apiKey: string;
  /** Valfri extra instruktion, t.ex. "detta är ett kundcase". */
  hint?: string;
}): Promise<ParseResult> {
  const { text, schema, apiKey, hint } = args;
  const fields = parseableFields(schema);
  const allowed = new Set(fields.map((f) => f.stateKey));
  const fieldList = fields.map((f) => `- ${f.stateKey} (${f.type})`).join('\n');

  const system = `Du strukturerar fri text till fält för en publik webbsida (sidtyp: ${schema.table}).

Returnera ENBART giltig JSON: ett objekt med en DELMÄNGD av nycklarna nedan — bara de fält där texten ger tydligt underlag. Hitta INTE på innehåll; utelämna fält du är osäker på. Behåll källtextens språk. Korta, redaktionella värden (inte hela stycken i rubrikfält).

Tillgängliga fält (camelCase-nyckel + typ):
${fieldList}${hint ? `\n\nLedtråd om källan: ${hint}` : ''}`;

  const body = {
    model: ANTHROPIC_MODEL,
    max_tokens: MAX_TOKENS,
    system: [{ type: 'text', text: system, cache_control: { type: 'ephemeral' } }],
    messages: [{ role: 'user', content: `Strukturera denna text:\n\n${text}` }],
  };

  const res = await fetch('https://api.anthropic.com/v1/messages', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'x-api-key': apiKey,
      'anthropic-version': ANTHROPIC_VERSION,
    },
    body: JSON.stringify(body),
  });

  if (!res.ok) {
    const err = await res.json().catch(() => ({}));
    const message =
      (err as { error?: { message?: string } })?.error?.message ||
      `Claude API error: ${res.status}`;
    throw new Error(message);
  }

  const data = (await res.json()) as { content?: Array<{ type?: string; text?: string }> };
  let raw = '';
  if (Array.isArray(data.content)) {
    for (const block of data.content) {
      if (block?.type === 'text' && typeof block.text === 'string') raw += block.text;
    }
  }
  raw = raw.replace(/^```(?:json)?\s*/i, '').replace(/\s*```$/i, '').trim();

  let parsed: unknown;
  try {
    parsed = JSON.parse(raw);
  } catch {
    throw new Error('Claude returnerade ogiltig JSON. Försök igen.');
  }
  if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) {
    throw new Error('Claude returnerade oväntat format.');
  }

  // Filtrera till kända state-nycklar och tvinga sträng. Användaren granskar
  // och redigerar innan spar — vi litar aldrig blint på utdatan.
  const result: Record<string, string> = {};
  for (const [key, value] of Object.entries(parsed as Record<string, unknown>)) {
    if (allowed.has(key) && value != null && typeof value !== 'object') {
      result[key] = String(value);
    }
  }
  return { fields: result };
}
