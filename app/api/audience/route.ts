import { NextResponse } from 'next/server';
import { listRecords, createRecord, updateRecord } from '@/lib/airtable';
import { AUDIENCE_TABLE_IDS, audienceStateToFields } from '@/lib/audience-mapper';
import { loadAudienceState } from '@/lib/audience-loader';
import { AudienceState } from '@/lib/audience-types';
import { invalidateWexoeCoreCache, AUDIENCE_ENTITIES } from '@/lib/wexoe-cache';

const AIRTABLE_API_KEY = process.env.AIRTABLE_API_KEY;

// ─── GET: list / get ───────────────────────────────────────────────────────

export async function GET(request: Request) {
  if (!AIRTABLE_API_KEY) {
    return NextResponse.json({ error: 'AIRTABLE_API_KEY not configured' }, { status: 500 });
  }

  const { searchParams } = new URL(request.url);
  const action = searchParams.get('action');

  try {
    if (action === 'list') {
      const records = await listRecords(AIRTABLE_API_KEY, AUDIENCE_TABLE_IDS.audienceHeroes, {
        fields: ['Slug', 'Title', 'Eyebrow'],
        sort: [{ field: 'Slug', direction: 'asc' }],
      });
      const pages = records.map((r) => ({
        id: r.id,
        // The page-list expects {name, slug, h1}. Audience records don't have
        // a separate Name field — fall back to Eyebrow + Slug for display, and
        // expose Title as the "H1" equivalent.
        name: (r.fields.Eyebrow as string) || (r.fields.Slug as string) || '',
        slug: (r.fields.Slug as string) || '',
        h1: (r.fields.Title as string) || '',
      }));
      return NextResponse.json({ pages });
    }

    if (action === 'get') {
      const recordId = searchParams.get('id');
      if (!recordId) {
        return NextResponse.json({ error: 'Missing id parameter' }, { status: 400 });
      }
      const state = await loadAudienceState(AIRTABLE_API_KEY, recordId);
      return NextResponse.json({ state });
    }

    return NextResponse.json(
      { error: 'Unknown action. Use ?action=list | get&id=...' },
      { status: 400 },
    );
  } catch (err) {
    const message = err instanceof Error ? err.message : 'Unknown error';
    return NextResponse.json({ error: message }, { status: 500 });
  }
}

// ─── POST: save (create or update) ─────────────────────────────────────────

export async function POST(request: Request) {
  if (!AIRTABLE_API_KEY) {
    return NextResponse.json({ error: 'AIRTABLE_API_KEY not configured' }, { status: 500 });
  }

  try {
    const state: AudienceState = await request.json();

    if (!state.slug?.trim()) {
      return NextResponse.json({ error: 'Slug är obligatoriskt' }, { status: 400 });
    }
    if (!state.title?.trim()) {
      return NextResponse.json({ error: 'Title är obligatoriskt' }, { status: 400 });
    }

    if (!state.recordId) {
      return await createAudience(AIRTABLE_API_KEY, state);
    }
    return await updateAudience(AIRTABLE_API_KEY, state);
  } catch (err) {
    const message = err instanceof Error ? err.message : 'Okänt fel';
    console.error('[audience:save] Error:', message);
    return NextResponse.json({ error: message }, { status: 500 });
  }
}

async function createAudience(airtableKey: string, state: AudienceState) {
  const existing = await listRecords(airtableKey, AUDIENCE_TABLE_IDS.audienceHeroes, {
    fields: ['Slug'],
    filterByFormula: `{Slug} = "${state.slug.replace(/"/g, '\\"')}"`,
  });
  if (existing.length > 0) {
    return NextResponse.json(
      { error: `Slug "${state.slug}" finns redan. Välj ett annat.` },
      { status: 409 },
    );
  }

  const fields = audienceStateToFields(state, 'create');
  const created = await createRecord(airtableKey, AUDIENCE_TABLE_IDS.audienceHeroes, fields);

  await invalidateWexoeCoreCache(AUDIENCE_ENTITIES, 'audience:create');

  return NextResponse.json({
    success: true,
    mode: 'create' as const,
    recordId: created.id,
    slug: state.slug,
  });
}

async function updateAudience(airtableKey: string, state: AudienceState) {
  if (!state.recordId) throw new Error('updateAudience called without recordId');

  const fields = audienceStateToFields(state, 'update');
  await updateRecord(airtableKey, AUDIENCE_TABLE_IDS.audienceHeroes, state.recordId, fields);

  await invalidateWexoeCoreCache(AUDIENCE_ENTITIES, 'audience:update');

  return NextResponse.json({
    success: true,
    mode: 'update' as const,
    recordId: state.recordId,
    slug: state.slug,
  });
}
