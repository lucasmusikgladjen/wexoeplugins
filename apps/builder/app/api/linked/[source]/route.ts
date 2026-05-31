/**
 * Generisk read-route för linkade-records-pickern.
 *
 * GET /api/linked/[source]
 *
 * Returnerar `{ success, records: Array<{ _recordId, ...fields }> }`
 * — samma shape som `/api/core/[entity]` så `Field.LinkedRecords` kan
 * konsumera båda routes utan klient-side-grenar.
 *
 * Routen är READ-ONLY. Pickers redigerar aldrig target-records — de
 * väljer bara existerande. Skrivning till core_*-tabeller går genom
 * `/api/core/[entity]`; skrivning till cms_*-tabeller går genom respektive
 * sidtyps editor (cms_cases har t.ex. ingen builder-editor ännu — då
 * redigeras posterna i Airtable direkt).
 *
 * Whitelist via `LinkedSourceName` säkerställer att klienten inte kan
 * fritextspecificera ett tableId.
 */

import { NextRequest, NextResponse } from 'next/server';
import { listRecords, SSOT_BASE_ID } from '@/lib/airtable';
import { readEntityRecord } from '@/lib/core/mapper';
import {
  CMS_LINKED_SOURCES,
  isCmsLinkedSource,
  isCoreLinkedSource,
} from '@/lib/linked-sources';
import { CORE_ENTITIES } from '@/lib/core/registry';

const apiKey = process.env.AIRTABLE_API_KEY;

function badRequest(message: string) {
  return NextResponse.json({ success: false, error: message }, { status: 400 });
}

function serverError(message: string) {
  return NextResponse.json({ success: false, error: message }, { status: 500 });
}

export async function GET(
  _req: NextRequest,
  { params }: { params: Promise<{ source: string }> },
) {
  if (!apiKey) return serverError('AIRTABLE_API_KEY ej konfigurerad.');

  const { source } = await params;

  try {
    if (isCoreLinkedSource(source)) {
      const def = CORE_ENTITIES[source];
      const records = await listRecords(apiKey, def.tableId, { baseId: SSOT_BASE_ID });
      const normalized = records.map((r) => readEntityRecord(source, r));
      return NextResponse.json({ success: true, records: normalized });
    }

    if (isCmsLinkedSource(source)) {
      const def = CMS_LINKED_SOURCES[source];
      const records = await listRecords(apiKey, def.tableId, {
        baseId: SSOT_BASE_ID,
        fields: [...def.fields],
      });
      // Spegla shape:n från readEntityRecord — `{ _recordId, ...flat-fields }`.
      const normalized = records.map((r) => ({
        _recordId: r.id,
        ...r.fields,
      }));
      return NextResponse.json({ success: true, records: normalized });
    }

    return badRequest(`Okänd linked source: ${source}`);
  } catch (err) {
    return serverError(err instanceof Error ? err.message : 'Hämtning misslyckades.');
  }
}
