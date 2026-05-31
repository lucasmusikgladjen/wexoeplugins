/**
 * FAS 7 — input-parser-endpoint (Claude på Lager A, aldrig på save).
 *
 * POST { entity, text, hint? } → { success, fields }  där `fields` är ett
 * camelCase-state-fragment editorn kan merga in (`setState({ ...state, ...fields })`)
 * och sedan låta användaren redigera. Sparet är fortfarande deterministiskt
 * (FAS 2) — den här routen rör ALDRIG Airtable.
 *
 * Pilot: customer-type (den enda sidtypen med JSON-schema än). Lägg till fler
 * i SCHEMAS-mappen när de migrerats till FAS 1-schema.
 *
 * UI-adoption (en "Importera från text"-knapp i editorn som POST:ar hit och
 * mergar svaret) är nästa, additiva steg — endpointen är grunden.
 */

import { NextRequest, NextResponse } from 'next/server';
import { parseFreeformToState } from '@/lib/parse-input';
import { EntitySchema } from '@/lib/schema/entity-schema';
import customerTypeSchema from '@/schema/cms_customer_type_pages.json';

const ANTHROPIC_API_KEY = process.env.ANTHROPIC_API_KEY;

/** Sidtyp-id → FAS 1-schema. Utöka när fler sidtyper får JSON-schema. */
const SCHEMAS: Record<string, EntitySchema> = {
  'customer-type': customerTypeSchema as unknown as EntitySchema,
};

interface ParseRequestBody {
  entity?: string;
  text?: string;
  hint?: string;
}

export async function POST(req: NextRequest): Promise<NextResponse> {
  if (!ANTHROPIC_API_KEY) {
    return NextResponse.json(
      { success: false, error: 'ANTHROPIC_API_KEY ej konfigurerad.' },
      { status: 500 },
    );
  }

  let body: ParseRequestBody;
  try {
    body = (await req.json()) as ParseRequestBody;
  } catch {
    return NextResponse.json({ success: false, error: 'Ogiltig JSON.' }, { status: 400 });
  }

  const entity = (body.entity ?? '').trim();
  const text = (body.text ?? '').trim();
  if (!text) {
    return NextResponse.json({ success: false, error: 'Fältet "text" krävs.' }, { status: 400 });
  }

  const schema = SCHEMAS[entity];
  if (!schema) {
    return NextResponse.json(
      { success: false, error: `Ingen input-parser för sidtyp "${entity}".` },
      { status: 400 },
    );
  }

  try {
    const { fields } = await parseFreeformToState({
      text,
      schema,
      apiKey: ANTHROPIC_API_KEY,
      hint: body.hint,
    });
    return NextResponse.json({ success: true, fields });
  } catch (err) {
    return NextResponse.json(
      { success: false, error: err instanceof Error ? err.message : 'Parse misslyckades.' },
      { status: 500 },
    );
  }
}
