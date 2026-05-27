import { NextResponse } from 'next/server';
import {
  getRecord,
  listRecords,
  createRecord,
  AirtableRecord,
  SSOT_BASE_ID,
} from '@/lib/airtable';
import { TABLE_IDS as LP_TABLE_IDS } from '@/lib/airtable';
import { PA_TABLE_IDS, PA_BASE_ID } from '@/lib/product-area-mapper';
import {
  CUSTOMER_TYPE_TABLE_IDS,
  CUSTOMER_TYPE_BASE_ID,
} from '@/lib/customer-type-mapper';
import { CASE_TABLE_ID, CASE_BASE_ID } from '@/lib/case-mapper';
import { PARTNER_TABLE_IDS, PARTNER_BASE_ID } from '@/lib/partner-mapper';
import {
  CMS_PAGES_TABLE_ID,
  CMS_PAGE_SECTIONS_TABLE_ID,
  CMS_SECTION_TABS_TABLE_ID,
} from '@/lib/cms-page-types';
import {
  invalidateWexoeCoreCache,
  CUSTOMER_TYPE_PAGE_ENTITIES,
  CASE_ENTITIES,
  CMS_PAGES_ENTITIES,
  PARTNER_ENTITIES,
} from '@/lib/wexoe-cache';

const AIRTABLE_API_KEY = process.env.AIRTABLE_API_KEY;

// Fields we never want to write back as-is — these are linked-record fields
// that point at the source record's owned children. The copy will re-link
// to its own freshly-created children instead.
//
// LP, PA och customer-type-pages är migrerade till Wexoe NY med snake_case-keys.
const LP_FIELDS_TO_DROP = new Set(['tab_ids']);
const TAB_FIELDS_TO_DROP = new Set(['landing_page_ids', 'download_ids']);
const DOWNLOAD_FIELDS_TO_DROP = new Set(['tab_ids']);

const PA_FIELDS_TO_DROP = new Set([
  // Owned section links are rebuilt from freshly copied section records below.
  'section_ids',
]);

const PA_SECTION_FIELDS_TO_DROP = new Set([
  // Managed backlink to cms_product_pages. Copying it would attach cloned
  // sections to the source Product Area and mutate the original relation graph.
  'product_page_ids',

  // Defensive aliases for older experiments/renames; harmless when absent.
  'product_area_ids',
  'product_ids',
  'page_ids',

  // Airtable computed/audit fields must never be written back on create.
  'created_at',
  'updated_at',
  'created_by',
  'last_modified_at',
  'last_modified_by',
]);

// cms_cases-records är flat. Det enda fältet att skippa är backlinken som
// pekar tillbaka från cms_partner_pages — Airtable avvisar PATCH/CREATE som
// skriver till en computed reverse-link.
const CASE_FIELDS_TO_DROP = new Set(['cms_partner_pages']);

// cms_partner_pages är också flat — länkade fält (partner_ids, country_ids,
// case_ids, category_ids) är forward-links som vi vill kopiera oförändrade
// så dubbletten ärver layout + innehåll. Inget att strippa idag.
const PARTNER_FIELDS_TO_DROP = new Set<string>();

// cms_pages är en deep-tree (page → sections → tabs). Page-recordet har
// owned section_ids som vi bygger om från färska sections nedan.
const CMS_PAGE_FIELDS_TO_DROP = new Set(['section_ids']);
const CMS_SECTION_FIELDS_TO_DROP = new Set([
  // Backlink till cms_pages — kopierade sektioner får sin egen page_ids via
  // den nya page:ens section_ids-array.
  'page_ids',
  // Tab-children rebuildas; gamla tabs_tab_ids pekar på källans tabs.
  'tabs_tab_ids',
]);
const CMS_SECTION_TAB_FIELDS_TO_DROP = new Set([
  // Backlink till cms_page_sections — sätts om till den nya parent-sektionen.
  'section_ids',
]);

interface CopyRequest {
  type: string;
  sourceId: string;
  name?: string;
  slug?: string;
}

type CopyHandler = (
  apiKey: string,
  sourceId: string,
  name: string | undefined,
  slug: string | undefined,
) => Promise<Response>;

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

// ─── Product Area (copy owned sections; share Products/Solutions) ─────────
//
// PA family lives in Wexoe NY with snake_case fields. Product/Solution
// relations are shared by reference; owned section records are deep-copied.

async function copyProductArea(
  apiKey: string,
  sourceId: string,
  name: string | undefined,
  slug: string | undefined,
) {
  const source = await getRecord(apiKey, PA_TABLE_IDS.productAreas, sourceId, PA_BASE_ID);
  const sourceName = (source.fields.name as string) || '';
  const sourceSlug = (source.fields.slug as string) || '';

  const newName = name?.trim() || defaultCopyName(sourceName);
  const newSlug = slug?.trim() || defaultCopySlug(sourceSlug);

  if (await isSlugTaken(apiKey, PA_TABLE_IDS.productAreas, newSlug, 'slug', PA_BASE_ID)) {
    return NextResponse.json(
      { error: `Slug "${newSlug}" finns redan. Välj ett annat.` },
      { status: 409 },
    );
  }

  const sourceSectionIds = (source.fields['section_ids'] as string[] | undefined) ?? [];
  let sourceSections: AirtableRecord[] = [];
  if (sourceSectionIds.length > 0) {
    const formula = `OR(${sourceSectionIds.map((id) => `RECORD_ID()='${id}'`).join(',')})`;
    sourceSections = await listRecords(apiKey, PA_TABLE_IDS.productPageSections, {
      filterByFormula: formula,
      baseId: PA_BASE_ID,
    });
  }

  const sourceSectionsById = new Map(sourceSections.map((section) => [section.id, section]));
  const newSectionIds: string[] = [];
  for (const sectionId of sourceSectionIds) {
    const section = sourceSectionsById.get(sectionId);
    if (!section) continue;

    const sectionFields = strip(section.fields, PA_SECTION_FIELDS_TO_DROP);

    const createdSection = await createRecord(
      apiKey,
      PA_TABLE_IDS.productPageSections,
      sectionFields,
      PA_BASE_ID,
    );
    newSectionIds.push(createdSection.id);
  }

  // Linked Products and Solutions are intentionally shared with the original
  // by reference for v1. Owned sections are rebuilt from the freshly-created
  // section records above.
  const fields = strip(source.fields, PA_FIELDS_TO_DROP);
  fields.name = newName;
  fields.slug = newSlug;
  fields.section_ids = newSectionIds;

  const newPa = await createRecord(apiKey, PA_TABLE_IDS.productAreas, fields, PA_BASE_ID);

  return NextResponse.json({
    success: true,
    id: newPa.id,
    name: newName,
    slug: newSlug,
    type: 'product-area' as const,
    sectionsCopied: newSectionIds.length,
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

// ─── Case (flat copy — product_ids/article_ids shared by reference) ───────
//
// cms_cases är flat: pseudo-arrayer (quick_stat_N_*, result_N_*,
// gallery_image_N_*) är bara fält på recordet och följer med automatiskt.
// product_ids + article_ids delas med källan — flera case-sidor kan länka
// samma cms_products/cms_articles utan att skapa duplicerade records.

async function copyCase(
  apiKey: string,
  sourceId: string,
  name: string | undefined,
  slug: string | undefined,
) {
  const source = await getRecord(apiKey, CASE_TABLE_ID, sourceId, CASE_BASE_ID);
  const sourceTitle = (source.fields.title as string) || '';
  const sourceSlug = (source.fields.slug as string) || '';

  const newName = name?.trim() || defaultCopyName(sourceTitle);
  const newSlug = slug?.trim() || defaultCopySlug(sourceSlug);

  if (await isSlugTaken(apiKey, CASE_TABLE_ID, newSlug, 'slug', CASE_BASE_ID)) {
    return NextResponse.json(
      { error: `Slug "${newSlug}" finns redan. Välj ett annat.` },
      { status: 409 },
    );
  }

  const fields = strip(source.fields, CASE_FIELDS_TO_DROP);
  fields.slug = newSlug;
  // `title` är H1 på den publika sidan och case-sidans "namn" i listan
  // (listItemMapper plockar title som name). Kopiera rubriken så list-vyn
  // inte visar två identiska poster.
  fields.title = newName;

  const created = await createRecord(apiKey, CASE_TABLE_ID, fields, CASE_BASE_ID);
  await invalidateWexoeCoreCache(CASE_ENTITIES, 'case:copy');

  return NextResponse.json({
    success: true,
    id: created.id,
    name: newName,
    slug: newSlug,
    type: 'case' as const,
  });
}

// ─── Partner Page (flat copy — single record, no owned children) ──────────
//
// cms_partner_pages är ett flat record-schema. Länkade fält
// (partner_ids, country_ids, case_ids, category_ids) kopieras oförändrade
// så dubbletten ärver hela layouten + content-strukturen. h1-fältet är
// listans visningsnamn — vi sätter `${h1} COPY` så list-vyn skiljer på
// original och dubblett. `is_active` ärvs som false-default? Nej, vi
// behåller källans tillstånd — en dubblett av en publicerad partner-sida
// förväntas vara redo att redigeras och publiceras direkt.

async function copyPartner(
  apiKey: string,
  sourceId: string,
  name: string | undefined,
  slug: string | undefined,
) {
  const source = await getRecord(
    apiKey,
    PARTNER_TABLE_IDS.partnerPages,
    sourceId,
    PARTNER_BASE_ID,
  );
  const sourceH1 = (source.fields.h1 as string) || '';
  const sourceSlug = (source.fields.slug as string) || '';

  const newName = name?.trim() || defaultCopyName(sourceH1);
  const newSlug = slug?.trim() || defaultCopySlug(sourceSlug);

  if (
    await isSlugTaken(
      apiKey,
      PARTNER_TABLE_IDS.partnerPages,
      newSlug,
      'slug',
      PARTNER_BASE_ID,
    )
  ) {
    return NextResponse.json(
      { error: `Slug "${newSlug}" finns redan. Välj ett annat.` },
      { status: 409 },
    );
  }

  const fields = strip(source.fields, PARTNER_FIELDS_TO_DROP);
  fields.slug = newSlug;
  fields.h1 = newName;

  const created = await createRecord(
    apiKey,
    PARTNER_TABLE_IDS.partnerPages,
    fields,
    PARTNER_BASE_ID,
  );
  await invalidateWexoeCoreCache(PARTNER_ENTITIES, 'partner:copy');

  return NextResponse.json({
    success: true,
    id: created.id,
    name: newName,
    slug: newSlug,
    type: 'partner' as const,
  });
}

// ─── CMS Page (deep copy — page + owned sections + tabs sub-records) ─────
//
// cms_pages-familjen är multi-tabell precis som LP men med polymorfa sektioner.
// Skriv-ordning vid copy:
//   1. CREATE alla cms_section_tabs (innan sektionerna så vi har deras IDs)
//   2. CREATE alla cms_page_sections (sätt tabs_tab_ids = nya tab-IDs där relevant)
//   3. CREATE cms_pages med section_ids = nya section-IDs (i källans ordning)

async function copyCmsPage(
  apiKey: string,
  sourceId: string,
  name: string | undefined,
  slug: string | undefined,
) {
  const source = await getRecord(apiKey, CMS_PAGES_TABLE_ID, sourceId, SSOT_BASE_ID);
  const sourceSlug = (source.fields.slug as string) || '';
  const sourceLabel =
    (source.fields.internal_label as string) ||
    (source.fields.h1 as string) ||
    sourceSlug;

  // `name`-fältet från UI:n hamnar på `internal_label` (redaktörsetiketten).
  // CMS-pages har ingen separat `name`-kolumn.
  const newInternalLabel = name?.trim() || defaultCopyName(sourceLabel);
  const newSlug = slug?.trim() || defaultCopySlug(sourceSlug);

  if (await isSlugTaken(apiKey, CMS_PAGES_TABLE_ID, newSlug, 'slug', SSOT_BASE_ID)) {
    return NextResponse.json(
      { error: `Slug "${newSlug}" finns redan. Välj ett annat.` },
      { status: 409 },
    );
  }

  // 1. Läs alla source-sektioner i deras source-ordning.
  const sourceSectionIds = (source.fields['section_ids'] as string[] | undefined) ?? [];
  let sourceSections: AirtableRecord[] = [];
  if (sourceSectionIds.length > 0) {
    const formula = `OR(${sourceSectionIds.map((id) => `RECORD_ID()='${id}'`).join(',')})`;
    sourceSections = await listRecords(apiKey, CMS_PAGE_SECTIONS_TABLE_ID, {
      filterByFormula: formula,
      baseId: SSOT_BASE_ID,
    });
  }
  const sourceSectionsById = new Map(sourceSections.map((s) => [s.id, s]));

  // 2. Samla alla tab-IDs som hör till tabs-sektioner.
  const tabsBySourceSection = new Map<string, string[]>();
  const allSourceTabIds = new Set<string>();
  for (const sec of sourceSections) {
    const tabIds = (sec.fields['tabs_tab_ids'] as string[] | undefined) ?? [];
    if (tabIds.length > 0) {
      tabsBySourceSection.set(sec.id, tabIds);
      tabIds.forEach((id) => allSourceTabIds.add(id));
    }
  }

  // 3. Läs alla source-tabs.
  let sourceTabs: AirtableRecord[] = [];
  if (allSourceTabIds.size > 0) {
    const ids = [...allSourceTabIds];
    const formula = `OR(${ids.map((id) => `RECORD_ID()='${id}'`).join(',')})`;
    sourceTabs = await listRecords(apiKey, CMS_SECTION_TABS_TABLE_ID, {
      filterByFormula: formula,
      baseId: SSOT_BASE_ID,
    });
  }
  const sourceTabsById = new Map(sourceTabs.map((t) => [t.id, t]));

  // 4. CREATE nya tabs (utan section_ids backlink). Map: gammal → ny tab-ID.
  const tabIdMap = new Map<string, string>();
  for (const sourceTab of sourceTabs) {
    const tabFields = strip(sourceTab.fields, CMS_SECTION_TAB_FIELDS_TO_DROP);
    const created = await createRecord(
      apiKey,
      CMS_SECTION_TABS_TABLE_ID,
      tabFields,
      SSOT_BASE_ID,
    );
    tabIdMap.set(sourceTab.id, created.id);
  }

  // 5. CREATE nya sections i källans ordning. Sätt tabs_tab_ids = nya tab-IDs
  //    där sektionen är en tabs-typ. Map: gammal → ny section-ID.
  const sectionIdMap = new Map<string, string>();
  const newSectionIdsOrdered: string[] = [];
  for (const sourceSectionId of sourceSectionIds) {
    const sourceSection = sourceSectionsById.get(sourceSectionId);
    if (!sourceSection) continue;

    const sectionFields = strip(sourceSection.fields, CMS_SECTION_FIELDS_TO_DROP);
    const oldTabIds = tabsBySourceSection.get(sourceSectionId);
    if (oldTabIds && oldTabIds.length > 0) {
      const newTabIds = oldTabIds
        .map((id) => tabIdMap.get(id))
        .filter((id): id is string => !!id);
      sectionFields['tabs_tab_ids'] = newTabIds;
    }

    const createdSection = await createRecord(
      apiKey,
      CMS_PAGE_SECTIONS_TABLE_ID,
      sectionFields,
      SSOT_BASE_ID,
    );
    sectionIdMap.set(sourceSectionId, createdSection.id);
    newSectionIdsOrdered.push(createdSection.id);
    // Notera: vi rensar inte upp ifall en sub-step failar — Airtable saknar
    // transaktioner. Orphan-records kan tas bort manuellt eller via en
    // framtida cleanup-route. Matchar copyLandingPage:s semantik.
    void sourceTabsById;
  }

  // 6. CREATE cms_pages med section_ids = nya sektioners IDs.
  const pageFields = strip(source.fields, CMS_PAGE_FIELDS_TO_DROP);
  pageFields.slug = newSlug;
  pageFields.internal_label = newInternalLabel;
  pageFields.section_ids = newSectionIdsOrdered;
  // `is_published` ärvs INTE — en kopia ska aldrig auto-publiceras. Användaren
  // får aktivt sätta toggle:n efter att ha justerat content. Defensivt mot
  // att en redaktör råkar publicera en halvfärdig duplikat.
  pageFields.is_published = false;

  const created = await createRecord(apiKey, CMS_PAGES_TABLE_ID, pageFields, SSOT_BASE_ID);
  await invalidateWexoeCoreCache(CMS_PAGES_ENTITIES, 'page:copy');

  return NextResponse.json({
    success: true,
    id: created.id,
    name: newInternalLabel,
    slug: newSlug,
    type: 'page' as const,
    sectionsCopied: newSectionIdsOrdered.length,
    tabsCopied: tabIdMap.size,
  });
}

// ─── Dispatch ──────────────────────────────────────────────────────────────
//
// När en ny sidtyp läggs till:
//   1. Skriv copy<Type>-funktionen ovan.
//   2. Lägg till en rad i COPY_HANDLERS nedan med samma `apiType`-sträng
//      som `copy: { apiType }` i `lib/page-types/registry.ts`.
//
// UI:n (CopyPageDialog + RowActionsMenu) läser från registry:n och dyker
// upp så snart steg 2 är gjort. Tappas en handler hamnar typen i "Ogiltig
// typ"-grenen nedan — bättre att returnera 400 explicit än att kraschar
// fel handler.

const COPY_HANDLERS: Record<string, CopyHandler> = {
  'landing': copyLandingPage,
  'product-area': copyProductArea,
  'customer-type': copyCustomerType,
  'case': copyCase,
  'page': copyCmsPage,
  'partner': copyPartner,
};

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

    const handler = COPY_HANDLERS[body.type];
    if (!handler) {
      return NextResponse.json(
        { error: `Ogiltig typ: ${body.type}` },
        { status: 400 },
      );
    }

    return await handler(AIRTABLE_API_KEY, body.sourceId, body.name, body.slug);
  } catch (err) {
    const message = err instanceof Error ? err.message : 'Okänt fel';
    console.error('[copy] Error:', message);
    return NextResponse.json({ error: message }, { status: 500 });
  }
}
