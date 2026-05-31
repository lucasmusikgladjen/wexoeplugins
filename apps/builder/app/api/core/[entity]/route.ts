/**
 * Generic CRUD route for SSOT entities.
 *
 * Endpoints:
 *   GET    /api/core/[entity]            — list records
 *   POST   /api/core/[entity]            — create
 *   PATCH  /api/core/[entity]?id=recXXX  — update
 *   DELETE /api/core/[entity]?id=recXXX  — delete
 *
 * Anropar Airtable direkt (Wexoe NY-basen) och invaliderar Wexoe Core
 * cachen efter varje mutation.
 */

import { NextRequest, NextResponse } from 'next/server';
import { createRecord, updateRecord, deleteRecords, listRecords, SSOT_BASE_ID } from '@/lib/airtable';
import { CORE_ENTITIES, isCoreEntityName, isSingleRecordEntity, CoreEntityName } from '@/lib/core/registry';
import { readEntityRecord, writeEntityFields } from '@/lib/core/mapper';
import { invalidateWexoeCoreCache } from '@/lib/wexoe-cache';

const apiKey = process.env.AIRTABLE_API_KEY;

function badRequest(message: string) {
  return NextResponse.json({ success: false, error: message }, { status: 400 });
}

function serverError(message: string) {
  return NextResponse.json({ success: false, error: message }, { status: 500 });
}

function entityFromParams(entity: string): CoreEntityName | null {
  return isCoreEntityName(entity) ? entity : null;
}

async function invalidate(entity: CoreEntityName, context: string) {
  await invalidateWexoeCoreCache([entity], context);
}

export async function GET(
  _req: NextRequest,
  { params }: { params: Promise<{ entity: string }> },
) {
  if (!apiKey) return serverError('AIRTABLE_API_KEY ej konfigurerad.');

  const { entity: raw } = await params;
  const entity = entityFromParams(raw);
  if (!entity) return badRequest(`Okänd entity: ${raw}`);

  const def = CORE_ENTITIES[entity];
  try {
    const records = await listRecords(apiKey, def.tableId, { baseId: SSOT_BASE_ID });
    const normalized = records.map((r) => readEntityRecord(entity, r));
    return NextResponse.json({ success: true, records: normalized });
  } catch (err) {
    return serverError(err instanceof Error ? err.message : 'Listning misslyckades.');
  }
}

export async function POST(
  req: NextRequest,
  { params }: { params: Promise<{ entity: string }> },
) {
  if (!apiKey) return serverError('AIRTABLE_API_KEY ej konfigurerad.');

  const { entity: raw } = await params;
  const entity = entityFromParams(raw);
  if (!entity) return badRequest(`Okänd entity: ${raw}`);

  let state: Record<string, unknown>;
  try {
    state = (await req.json()) as Record<string, unknown>;
  } catch {
    return badRequest('Ogiltig JSON-body.');
  }

  if (isSingleRecordEntity(entity)) {
    try {
      const existing = await listRecords(apiKey, CORE_ENTITIES[entity].tableId, {
        baseId: SSOT_BASE_ID,
      });
      if (existing.length > 0) {
        return NextResponse.json(
          { success: false, code: 'already_exists', error: `${CORE_ENTITIES[entity].label} finns redan — bara ett record tillåts.` },
          { status: 409 },
        );
      }
    } catch (err) {
      console.warn('Kunde inte verifiera single-record-invariant:', err);
    }
  } else if (entity === 'core_graphic_profile' && state.is_default === true) {
    try {
      const existing = await listRecords(apiKey, CORE_ENTITIES[entity].tableId, {
        baseId: SSOT_BASE_ID,
        filterByFormula: '{is_default}=TRUE()',
      });
      if (existing.length > 0) {
        return NextResponse.json(
          { success: false, code: 'duplicate_default', error: 'Ett annat record har redan is_default=true. Avmarkera det först.' },
          { status: 409 },
        );
      }
    } catch (err) {
      console.warn('Kunde inte verifiera default-invariant:', err);
    }
  }

  try {
    const fields = writeEntityFields(entity, state, 'create');
    const created = await createRecord(apiKey, CORE_ENTITIES[entity].tableId, fields, SSOT_BASE_ID);
    await invalidate(entity, `core/${entity}/create`);
    return NextResponse.json({ success: true, record: readEntityRecord(entity, created) }, { status: 201 });
  } catch (err) {
    return serverError(err instanceof Error ? err.message : 'Create misslyckades.');
  }
}

export async function PATCH(
  req: NextRequest,
  { params }: { params: Promise<{ entity: string }> },
) {
  if (!apiKey) return serverError('AIRTABLE_API_KEY ej konfigurerad.');

  const { entity: raw } = await params;
  const entity = entityFromParams(raw);
  if (!entity) return badRequest(`Okänd entity: ${raw}`);

  const url = new URL(req.url);
  const recordId = url.searchParams.get('id');
  if (!recordId) return badRequest('Saknar query-param ?id=recXXX.');

  let state: Record<string, unknown>;
  try {
    state = (await req.json()) as Record<string, unknown>;
  } catch {
    return badRequest('Ogiltig JSON-body.');
  }

  if (entity === 'core_graphic_profile' && state.is_default === true) {
    try {
      const existing = await listRecords(apiKey, CORE_ENTITIES[entity].tableId, {
        baseId: SSOT_BASE_ID,
        filterByFormula: '{is_default}=TRUE()',
      });
      const other = existing.find((r) => r.id !== recordId);
      if (other) {
        return NextResponse.json(
          { success: false, code: 'duplicate_default', error: 'Ett annat record har redan is_default=true. Avmarkera det först.' },
          { status: 409 },
        );
      }
    } catch (err) {
      console.warn('Kunde inte verifiera default-invariant:', err);
    }
  }

  try {
    const fields = writeEntityFields(entity, state, 'update');
    const updated = await updateRecord(apiKey, CORE_ENTITIES[entity].tableId, recordId, fields, SSOT_BASE_ID);
    await invalidate(entity, `core/${entity}/update`);
    return NextResponse.json({ success: true, record: readEntityRecord(entity, updated) });
  } catch (err) {
    return serverError(err instanceof Error ? err.message : 'Update misslyckades.');
  }
}

export async function DELETE(
  req: NextRequest,
  { params }: { params: Promise<{ entity: string }> },
) {
  if (!apiKey) return serverError('AIRTABLE_API_KEY ej konfigurerad.');

  const { entity: raw } = await params;
  const entity = entityFromParams(raw);
  if (!entity) return badRequest(`Okänd entity: ${raw}`);

  const url = new URL(req.url);
  const recordId = url.searchParams.get('id');
  if (!recordId) return badRequest('Saknar query-param ?id=recXXX.');

  if (isSingleRecordEntity(entity)) {
    return NextResponse.json(
      { success: false, code: 'cannot_delete', error: `${CORE_ENTITIES[entity].label} kan inte raderas — bara redigeras.` },
      { status: 409 },
    );
  }

  try {
    await deleteRecords(apiKey, CORE_ENTITIES[entity].tableId, [recordId], SSOT_BASE_ID);
    await invalidate(entity, `core/${entity}/delete`);
    return NextResponse.json({ success: true, deleted: true });
  } catch (err) {
    return serverError(err instanceof Error ? err.message : 'Delete misslyckades.');
  }
}
