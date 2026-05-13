import { NextResponse } from 'next/server';
import {
  getRecord,
  listRecords,
  createRecord,
  AirtableRecord,
} from '@/lib/airtable';
import { TABLE_IDS as LP_TABLE_IDS } from '@/lib/airtable';
import { PA_TABLE_IDS } from '@/lib/product-area-mapper';
import { AUDIENCE_TABLE_IDS } from '@/lib/audience-mapper';

const AIRTABLE_API_KEY = process.env.AIRTABLE_API_KEY;

// Fields we never want to write back as-is — these are linked-record fields
// that point at the source record's owned children. The copy will re-link
// to its own freshly-created children instead.
const LP_FIELDS_TO_DROP = new Set(['LP Tabs']);
const TAB_FIELDS_TO_DROP = new Set(['Landing Page', 'LP Downloads']);
const DOWNLOAD_FIELDS_TO_DROP = new Set(['Tab']);

interface CopyRequest {
  type: 'landing' | 'product-area' | 'audience';
  sourceId: string;
  name?: string;
  slug?: string;
}

function strip(fields: Record<string, unknown>, drop: Set<string>): Record<string, unknown> {
  const out: Record<string, unknown> = {};
  for (const [k, v] of Object.entries(fields)) {
    if (drop.has(k)) continue;
    out[k] = v;
  }
  return out;
}

function defaultCopySlug(slug: string): string {
  if (!slug) return 'copy';
  // If the slug already ends with -copy or -copy-N, bump the number.
  const m = slug.match(/^(.*)-copy(?:-(\d+))?$/);
  if (m) {
    const base = m[1];
    const n = m[2] ? parseInt(m[2], 10) + 1 : 2;
    return `${base}-copy-${n}`;
  }
  return `${slug}-copy`;
}

function defaultCopyName(name: string): string {
  if (!name) return 'COPY';
  return `${name} COPY`;
}

async function isSlugTaken(apiKey: string, tableId: string, slug: string): Promise<boolean> {
  const safeSlug = slug.replace(/"/g, '\\"');
  const matches = await listRecords(apiKey, tableId, {
    fields: ['Slug'],
    filterByFormula: `{Slug} = "${safeSlug}"`,
  });
  return matches.length > 0;
}

// ─── Landing Page (deep copy: LP + tabs + downloads) ───────────────────────

async function copyLandingPage(
  apiKey: string,
  sourceId: string,
  name: string | undefined,
  slug: string | undefined,
) {
  const source = await getRecord(apiKey, LP_TABLE_IDS.landingPages, sourceId);
  const sourceName = (source.fields.Name as string) || '';
  const sourceSlug = (source.fields.Slug as string) || '';

  const newName = name?.trim() || defaultCopyName(sourceName);
  const newSlug = slug?.trim() || defaultCopySlug(sourceSlug);

  if (await isSlugTaken(apiKey, LP_TABLE_IDS.landingPages, newSlug)) {
    return NextResponse.json(
      { error: `Slug "${newSlug}" finns redan. Välj ett annat.` },
      { status: 409 },
    );
  }

  // 1. CREATE the new LP record. Strip the LP Tabs link — the new tabs we
  //    create below will set their own back-link to this record.
  const lpFields = strip(source.fields, LP_FIELDS_TO_DROP);
  lpFields.Name = newName;
  lpFields.Slug = newSlug;

  const newLp = await createRecord(apiKey, LP_TABLE_IDS.landingPages, lpFields);

  // 2. Read all source tabs and downloads in two queries.
  const tabIds = (source.fields['LP Tabs'] as string[] | undefined) ?? [];
  let sourceTabs: AirtableRecord[] = [];
  if (tabIds.length > 0) {
    const formula = `OR(${tabIds.map((id) => `RECORD_ID()='${id}'`).join(',')})`;
    sourceTabs = await listRecords(apiKey, LP_TABLE_IDS.tabs, { filterByFormula: formula });
  }

  // Sort by Order so creation order matches what the live page shows.
  sourceTabs.sort((a, b) => {
    const oa = (a.fields.Order as number) ?? 0;
    const ob = (b.fields.Order as number) ?? 0;
    return oa - ob;
  });

  const downloadIds = new Set<string>();
  for (const tab of sourceTabs) {
    const ids = (tab.fields['LP Downloads'] as string[] | undefined) ?? [];
    ids.forEach((id) => downloadIds.add(id));
  }
  let sourceDownloads: AirtableRecord[] = [];
  if (downloadIds.size > 0) {
    const formula = `OR(${[...downloadIds].map((id) => `RECORD_ID()='${id}'`).join(',')})`;
    sourceDownloads = await listRecords(apiKey, LP_TABLE_IDS.downloads, { filterByFormula: formula });
  }

  // 3. CREATE new tabs linked to the new LP. Map oldTabId → newTabId so we
  //    can rewire downloads in the next step.
  const tabIdMap: Record<string, string> = {};
  for (const sourceTab of sourceTabs) {
    const tabFields = strip(sourceTab.fields, TAB_FIELDS_TO_DROP);
    tabFields['Landing Page'] = [newLp.id];
    const newTab = await createRecord(apiKey, LP_TABLE_IDS.tabs, tabFields);
    tabIdMap[sourceTab.id] = newTab.id;
  }

  // 4. CREATE new downloads, each linked back to the *new* tab id.
  for (const sourceDl of sourceDownloads) {
    const oldTabRefs = (sourceDl.fields.Tab as string[] | undefined) ?? [];
    const newTabRefs = oldTabRefs
      .map((id) => tabIdMap[id])
      .filter((id): id is string => !!id);
    if (newTabRefs.length === 0) continue;

    const dlFields = strip(sourceDl.fields, DOWNLOAD_FIELDS_TO_DROP);
    dlFields.Tab = newTabRefs;
    await createRecord(apiKey, LP_TABLE_IDS.downloads, dlFields);
  }

  return NextResponse.json({
    success: true,
    id: newLp.id,
    name: newName,
    slug: newSlug,
    type: 'landing' as const,
    tabsCopied: sourceTabs.length,
    downloadsCopied: sourceDownloads.length,
  });
}

// ─── Product Area (shallow copy — share linked Products/Solutions) ─────────

async function copyProductArea(
  apiKey: string,
  sourceId: string,
  name: string | undefined,
  slug: string | undefined,
) {
  const source = await getRecord(apiKey, PA_TABLE_IDS.productAreas, sourceId);
  const sourceName = (source.fields.Name as string) || '';
  const sourceSlug = (source.fields.Slug as string) || '';

  const newName = name?.trim() || defaultCopyName(sourceName);
  const newSlug = slug?.trim() || defaultCopySlug(sourceSlug);

  if (await isSlugTaken(apiKey, PA_TABLE_IDS.productAreas, newSlug)) {
    return NextResponse.json(
      { error: `Slug "${newSlug}" finns redan. Välj ett annat.` },
      { status: 409 },
    );
  }

  // PA copy is shallow: linked Products and Solutions are *shared* with
  // the original. Editing one product still affects both pages — that's the
  // expected behaviour for v1, since linked records in Airtable are always
  // shared by reference.
  const fields: Record<string, unknown> = { ...source.fields };
  fields.Name = newName;
  fields.Slug = newSlug;

  const newPa = await createRecord(apiKey, PA_TABLE_IDS.productAreas, fields);

  return NextResponse.json({
    success: true,
    id: newPa.id,
    name: newName,
    slug: newSlug,
    type: 'product-area' as const,
  });
}

// ─── Audience (flat copy — single record, no children) ────────────────────

async function copyAudience(
  apiKey: string,
  sourceId: string,
  name: string | undefined,
  slug: string | undefined,
) {
  const source = await getRecord(apiKey, AUDIENCE_TABLE_IDS.audienceHeroes, sourceId);
  const sourceSlug = (source.fields.Slug as string) || '';

  // Audience records have no Name field — fall back to slug-based defaults.
  const newSlug = slug?.trim() || defaultCopySlug(sourceSlug);
  // `name` is accepted from the dialog for parity with other types but
  // there's nowhere to write it on the audience record; preserve it for
  // the response only.
  const newName = name?.trim() || defaultCopyName(sourceSlug);

  if (await isSlugTaken(apiKey, AUDIENCE_TABLE_IDS.audienceHeroes, newSlug)) {
    return NextResponse.json(
      { error: `Slug "${newSlug}" finns redan. Välj ett annat.` },
      { status: 409 },
    );
  }

  const fields: Record<string, unknown> = { ...source.fields };
  fields.Slug = newSlug;

  const created = await createRecord(apiKey, AUDIENCE_TABLE_IDS.audienceHeroes, fields);

  return NextResponse.json({
    success: true,
    id: created.id,
    name: newName,
    slug: newSlug,
    type: 'audience' as const,
  });
}

// ─── Handler ───────────────────────────────────────────────────────────────

export async function POST(request: Request) {
  if (!AIRTABLE_API_KEY) {
    return NextResponse.json({ error: 'AIRTABLE_API_KEY not configured' }, { status: 500 });
  }

  try {
    const body: CopyRequest = await request.json();
    if (!body.sourceId) {
      return NextResponse.json({ error: 'sourceId krävs' }, { status: 400 });
    }

    if (body.type === 'landing') {
      return await copyLandingPage(AIRTABLE_API_KEY, body.sourceId, body.name, body.slug);
    }
    if (body.type === 'product-area') {
      return await copyProductArea(AIRTABLE_API_KEY, body.sourceId, body.name, body.slug);
    }
    if (body.type === 'audience') {
      return await copyAudience(AIRTABLE_API_KEY, body.sourceId, body.name, body.slug);
    }
    return NextResponse.json({ error: 'Ogiltig typ.' }, { status: 400 });
  } catch (err) {
    const message = err instanceof Error ? err.message : 'Okänt fel';
    console.error('[copy] Error:', message);
    return NextResponse.json({ error: message }, { status: 500 });
  }
}
