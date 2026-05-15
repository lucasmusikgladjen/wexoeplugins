import { NextResponse } from 'next/server';
import {
  getRecord,
  listRecords,
  createRecord,
  AirtableRecord,
} from '@/lib/airtable';
import { TABLE_IDS as LP_TABLE_IDS } from '@/lib/airtable';
import { PA_TABLE_IDS, PA_BASE_ID } from '@/lib/product-area-mapper';
import {
  CUSTOMER_TYPE_TABLE_IDS,
  CUSTOMER_TYPE_BASE_ID,
} from '@/lib/customer-type-mapper';
import { invalidateWexoeCoreCache, CUSTOMER_TYPE_PAGE_ENTITIES } from '@/lib/wexoe-cache';

const AIRTABLE_API_KEY = process.env.AIRTABLE_API_KEY;

// Fields we never want to write back as-is — these are linked-record fields
// that point at the source record's owned children. The copy will re-link
// to its own freshly-created children instead.
//
// LP, PA och customer-type-pages är migrerade till Wexoe NY med snake_case-keys.
// use legacy PascalCase keys until those tables migrate.
const LP_FIELDS_TO_DROP = new Set(['tab_ids']);
const TAB_FIELDS_TO_DROP = new Set(['landing_page_ids', 'download_ids']);
const DOWNLOAD_FIELDS_TO_DROP = new Set(['tab_ids']);

interface CopyRequest {
  type: 'landing' | 'product-area' | 'customer-type';
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

/** Slug uniqueness check. `slugField` differs by family: LP/PA use the
 *  primary `slug` (snake_case in NY base, PascalCase `Slug` in legacy). */
async function isSlugTaken(
  apiKey: string,
  tableId: string,
  slug: string,
  slugField: string,
  baseId?: string,
): Promise<boolean> {
  const safeSlug = slug.replace(/"/g, '\\"');
  const matches = await listRecords(apiKey, tableId, {
    fields: [slugField],
    filterByFormula: `{${slugField}} = "${safeSlug}"`,
    baseId,
  });
  return matches.length > 0;
}

// ─── Landing Page (deep copy: LP + tabs + downloads) ───────────────────────
//
// LP family now lives in Wexoe NY with snake_case fields. Default base from
// `lib/airtable.ts` already points there — no explicit baseId needed.

async function copyLandingPage(
  apiKey: string,
  sourceId: string,
  name: string | undefined,
  slug: string | undefined,
) {
  const source = await getRecord(apiKey, LP_TABLE_IDS.landingPages, sourceId);
  const sourceSlug = (source.fields.slug as string) || '';

  // LP records dropped the standalone Name field — fall back to slug for
  // the COPY label so the dialog still has something sensible to show.
  const newName = name?.trim() || defaultCopyName(sourceSlug);
  const newSlug = slug?.trim() || defaultCopySlug(sourceSlug);

  if (await isSlugTaken(apiKey, LP_TABLE_IDS.landingPages, newSlug, 'slug')) {
    return NextResponse.json(
      { error: `Slug "${newSlug}" finns redan. Välj ett annat.` },
      { status: 409 },
    );
  }

  // 1. CREATE the new LP record. Strip the tab_ids link — the new tabs we
  //    create below will set their own back-link to this record.
  const lpFields = strip(source.fields, LP_FIELDS_TO_DROP);
  lpFields.slug = newSlug;

  const newLp = await createRecord(apiKey, LP_TABLE_IDS.landingPages, lpFields);

  // 2. Read all source tabs and downloads in two queries.
  const tabIds = (source.fields['tab_ids'] as string[] | undefined) ?? [];
  let sourceTabs: AirtableRecord[] = [];
  if (tabIds.length > 0) {
    const formula = `OR(${tabIds.map((id) => `RECORD_ID()='${id}'`).join(',')})`;
    sourceTabs = await listRecords(apiKey, LP_TABLE_IDS.landingPageTabs, { filterByFormula: formula });
  }

  // Sort by order so creation order matches what the live page shows.
  sourceTabs.sort((a, b) => {
    const oa = (a.fields.order as number) ?? 0;
    const ob = (b.fields.order as number) ?? 0;
    return oa - ob;
  });

  const downloadIds = new Set<string>();
  for (const tab of sourceTabs) {
    const ids = (tab.fields['download_ids'] as string[] | undefined) ?? [];
    ids.forEach((id) => downloadIds.add(id));
  }
  let sourceDownloads: AirtableRecord[] = [];
  if (downloadIds.size > 0) {
    const formula = `OR(${[...downloadIds].map((id) => `RECORD_ID()='${id}'`).join(',')})`;
    sourceDownloads = await listRecords(apiKey, LP_TABLE_IDS.landingPageDownloads, { filterByFormula: formula });
  }

  // 3. CREATE new tabs linked to the new LP. Map oldTabId → newTabId so we
  //    can rewire downloads in the next step.
  const tabIdMap: Record<string, string> = {};
  for (const sourceTab of sourceTabs) {
    const tabFields = strip(sourceTab.fields, TAB_FIELDS_TO_DROP);
    tabFields['landing_page_ids'] = [newLp.id];
    const newTab = await createRecord(apiKey, LP_TABLE_IDS.landingPageTabs, tabFields);
    tabIdMap[sourceTab.id] = newTab.id;
  }

  // 4. CREATE new downloads, each linked back to the *new* tab id.
  for (const sourceDl of sourceDownloads) {
    const oldTabRefs = (sourceDl.fields.tab_ids as string[] | undefined) ?? [];
    const newTabRefs = oldTabRefs
      .map((id) => tabIdMap[id])
      .filter((id): id is string => !!id);
    if (newTabRefs.length === 0) continue;

    const dlFields = strip(sourceDl.fields, DOWNLOAD_FIELDS_TO_DROP);
    dlFields.tab_ids = newTabRefs;
    await createRecord(apiKey, LP_TABLE_IDS.landingPageDownloads, dlFields);
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
//
// Still on legacy Wexoe base — pass PA_BASE_ID explicitly to all helpers.

async function copyProductArea(
  apiKey: string,
  sourceId: string,
  name: string | undefined,
  slug: string | undefined,
) {
  const source = await getRecord(apiKey, PA_TABLE_IDS.productAreas, sourceId, PA_BASE_ID);
  const sourceName = (source.fields.Name as string) || '';
  const sourceSlug = (source.fields.Slug as string) || '';

  const newName = name?.trim() || defaultCopyName(sourceName);
  const newSlug = slug?.trim() || defaultCopySlug(sourceSlug);

  if (await isSlugTaken(apiKey, PA_TABLE_IDS.productAreas, newSlug, 'Slug', PA_BASE_ID)) {
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

  const newPa = await createRecord(apiKey, PA_TABLE_IDS.productAreas, fields, PA_BASE_ID);

  return NextResponse.json({
    success: true,
    id: newPa.id,
    name: newName,
    slug: newSlug,
    type: 'product-area' as const,
  });
}

// ─── Customer-type page (flat copy — single record, case_ids skip:as) ─────
//
// cms_customer_type_pages ligger i Wexoe NY. `case_ids`-länken kopieras INTE
// — kopian börjar utan länkade case för att undvika oavsiktlig delning av
// case-cards mellan källrecord och kopia.

async function copyCustomerType(
  apiKey: string,
  sourceId: string,
  name: string | undefined,
  slug: string | undefined,
) {
  const source = await getRecord(
    apiKey,
    CUSTOMER_TYPE_TABLE_IDS.customerTypePages,
    sourceId,
    CUSTOMER_TYPE_BASE_ID,
  );
  const sourceSlug = (source.fields.slug as string) || '';

  const newSlug = slug?.trim() || defaultCopySlug(sourceSlug);
  const newName = name?.trim() || defaultCopyName(sourceSlug);

  if (
    await isSlugTaken(
      apiKey,
      CUSTOMER_TYPE_TABLE_IDS.customerTypePages,
      newSlug,
      'slug',
      CUSTOMER_TYPE_BASE_ID,
    )
  ) {
    return NextResponse.json(
      { error: `Slug "${newSlug}" finns redan. Välj ett annat.` },
      { status: 409 },
    );
  }

  const fields: Record<string, unknown> = { ...source.fields };
  fields.slug = newSlug;
  // Skriv alltid newName till fields.name — annars ärver kopian källans namn
  // medan svaret/UI rapporterar default-copy-namnet, och list-vyn slutar
  // visa två identiska "Installatör"-rader när användaren lämnar Name tomt.
  fields.name = newName;
  // case_ids ärvs inte — kopian börjar utan länkade cases.
  delete fields.case_ids;

  const created = await createRecord(
    apiKey,
    CUSTOMER_TYPE_TABLE_IDS.customerTypePages,
    fields,
    CUSTOMER_TYPE_BASE_ID,
  );

  await invalidateWexoeCoreCache(CUSTOMER_TYPE_PAGE_ENTITIES, 'customer-type:copy');

  return NextResponse.json({
    success: true,
    id: created.id,
    name: newName,
    slug: newSlug,
    type: 'customer-type' as const,
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
    if (body.type === 'customer-type') {
      return await copyCustomerType(AIRTABLE_API_KEY, body.sourceId, body.name, body.slug);
    }
    return NextResponse.json({ error: 'Ogiltig typ.' }, { status: 400 });
  } catch (err) {
    const message = err instanceof Error ? err.message : 'Okänt fel';
    console.error('[copy] Error:', message);
    return NextResponse.json({ error: message }, { status: 500 });
  }
}
