/**
 * CRUD-route för cms_unique_pages.
 *
 *   POST   /api/unique-page                 — create
 *   PATCH  /api/unique-page?id=recXXX       — update
 *   DELETE /api/unique-page?id=recXXX       — delete
 *   GET    /api/unique-page?action=list     — list
 *
 * Skriver mot SSOT-basen (Wexoe NY). Invaliderar Wexoe Core cache efter mutation.
 */

import { NextRequest, NextResponse } from 'next/server';
import { createRecord, updateRecord, deleteRecords, listRecords, SSOT_BASE_ID } from '@/lib/airtable';
import { uniquePageStateToFields, UNIQUE_PAGES_TABLE_ID } from '@/lib/unique-page-mapper';
import { UniquePageState } from '@/lib/unique-page-types';
import { invalidateWexoeCoreCache, UNIQUE_PAGES_ENTITIES } from '@/lib/wexoe-cache';
import { isReservedSlug } from '@/lib/core/reserved-slugs';

const apiKey = process.env.AIRTABLE_API_KEY;

function badRequest(message: string) {
  return NextResponse.json({ success: false, error: message }, { status: 400 });
}
function serverError(message: string) {
  return NextResponse.json({ success: false, error: message }, { status: 500 });
}

async function invalidate(context: string) {
  await invalidateWexoeCoreCache(UNIQUE_PAGES_ENTITIES, context);
}

function validateSlug(slug: string): string | null {
  if (!slug) return 'Slug är obligatorisk.';
  if (isReservedSlug(slug)) return `Slug "${slug}" är reserverad och kan inte användas.`;
  if (!/^[a-z0-9][a-z0-9-]*$/.test(slug)) return 'Slug får bara innehålla lower-case bokstäver, siffror och bindestreck.';
  return null;
}

export async function GET(req: NextRequest) {
  if (!apiKey) return serverError('AIRTABLE_API_KEY ej konfigurerad.');
  const url = new URL(req.url);
  const action = url.searchParams.get('action');
  if (action !== 'list') return badRequest('action=list krävs.');
  try {
    const records = await listRecords(apiKey, UNIQUE_PAGES_TABLE_ID, { baseId: SSOT_BASE_ID });
    const pages = records.map((r) => ({
      id: r.id,
      slug: r.fields['Slug'] ?? '',
      h1: r.fields['H1'] ?? '',
      published: r.fields['Published'] === true,
    }));
    return NextResponse.json({ success: true, pages });
  } catch (err) {
    return serverError(err instanceof Error ? err.message : 'List misslyckades.');
  }
}

export async function POST(req: NextRequest) {
  if (!apiKey) return serverError('AIRTABLE_API_KEY ej konfigurerad.');
  let state: UniquePageState;
  try { state = (await req.json()) as UniquePageState; } catch { return badRequest('Ogiltig JSON.'); }

  const slugError = validateSlug(state.slug);
  if (slugError) return badRequest(slugError);

  try {
    // Säkerställ unik slug.
    const existing = await listRecords(apiKey, UNIQUE_PAGES_TABLE_ID, {
      baseId: SSOT_BASE_ID,
      filterByFormula: `{Slug}="${state.slug.replace(/"/g, '\\"')}"`,
    });
    if (existing.length > 0) {
      return NextResponse.json(
        { success: false, code: 'duplicate_slug', error: 'En sida med den slug:en finns redan.' },
        { status: 409 },
      );
    }

    const fields = uniquePageStateToFields(state);
    const created = await createRecord(apiKey, UNIQUE_PAGES_TABLE_ID, fields, SSOT_BASE_ID);
    await invalidate('unique-page/create');
    return NextResponse.json({ success: true, mode: 'create', recordId: created.id }, { status: 201 });
  } catch (err) {
    return serverError(err instanceof Error ? err.message : 'Create misslyckades.');
  }
}

export async function PATCH(req: NextRequest) {
  if (!apiKey) return serverError('AIRTABLE_API_KEY ej konfigurerad.');
  const url = new URL(req.url);
  const recordId = url.searchParams.get('id');
  if (!recordId) return badRequest('Saknar ?id=recXXX.');

  let state: UniquePageState;
  try { state = (await req.json()) as UniquePageState; } catch { return badRequest('Ogiltig JSON.'); }

  const slugError = validateSlug(state.slug);
  if (slugError) return badRequest(slugError);

  try {
    const fields = uniquePageStateToFields(state);
    await updateRecord(apiKey, UNIQUE_PAGES_TABLE_ID, recordId, fields, SSOT_BASE_ID);
    await invalidate('unique-page/update');
    return NextResponse.json({ success: true, mode: 'update' });
  } catch (err) {
    return serverError(err instanceof Error ? err.message : 'Update misslyckades.');
  }
}

export async function DELETE(req: NextRequest) {
  if (!apiKey) return serverError('AIRTABLE_API_KEY ej konfigurerad.');
  const url = new URL(req.url);
  const recordId = url.searchParams.get('id');
  if (!recordId) return badRequest('Saknar ?id=recXXX.');
  try {
    await deleteRecords(apiKey, UNIQUE_PAGES_TABLE_ID, [recordId], SSOT_BASE_ID);
    await invalidate('unique-page/delete');
    return NextResponse.json({ success: true, deleted: true });
  } catch (err) {
    return serverError(err instanceof Error ? err.message : 'Delete misslyckades.');
  }
}
