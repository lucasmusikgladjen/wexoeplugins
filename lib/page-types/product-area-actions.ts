/**
 * Product Area — Lager 3 create/update-overrides.
 *
 * Product-area är inte ren CRUD: state → Airtable-fält transformeras
 * deterministiskt (rena funktioner), och varje "Product" och "Solution" är en
 * separat Airtable-record. Vi kunde inte använda Lager 2 (declarative
 * relations) direkt eftersom fält-transformationen behöver egen ordningslogik.
 *
 * Här lever den tidigare app/api/product-area/route.ts-create/update-koden,
 * lyft ut bakom `PageTypeServerDef.create` / `.update`-hooks. Returvärdet
 * mappas till factory:ns standardshape med `relations.products.created` och
 * `relations.solutions.created` som array av `{ clientId, recordId }` så
 * klient-koden konsumerar samma shape som andra sidtyper med relationer.
 */

import {
  createRecord,
  updateRecord,
  updateRecords,
  deleteRecords,
  getRecord,
  listRecords,
} from '../airtable';
import { PA_TABLE_IDS, PA_BASE_ID } from '../product-area-mapper';
import { NormalSection, ProductAreaState } from '../product-area-types';
import { buildProductAreaTransform } from '../deterministic-transform';
import { contactFieldsEmpty, resolveDefaultCoworker } from '../default-coworker';
import type { RelationSyncResult } from './types';

type Result = {
  recordId: string;
  relations: Record<string, RelationSyncResult>;
};

function emptyRelationResult(): RelationSyncResult {
  return { created: [], updated: [], deleted: [], unlinked: [], errors: [] };
}

function sortByClientIndex<T extends { _clientIndex: number }>(arr: T[]): T[] {
  return [...arr].sort((a, b) => a._clientIndex - b._clientIndex);
}

type SectionSlot = 1 | 2 | 3 | 4;

/** Bygg cms_product_page_sections-fält från en Normal-slot, eller null om
 *  slotet är tomt (ingen H2). Sektioner är PA-ägda — varje skapas/uppdateras
 *  deterministiskt här (ingen Claude-transform behövs). */
function buildSectionFields(
  slug: string,
  slot: SectionSlot,
  sec: NormalSection,
): Record<string, unknown> | null {
  if (!sec.h2?.trim()) return null;
  return {
    name: `${slug}: ${sec.h2}`.slice(0, 255),
    is_active: true,
    order: slot,
    h2: sec.h2,
    text: sec.text,
    bullets: sec.bullets,
    image_url: sec.image,
    bg: sec.bg,
    reversed: !!sec.reversed,
    shown_top: slot === 1 ? !!sec.upp : false,
  };
}

function getSlot(state: ProductAreaState, slot: SectionSlot): NormalSection {
  return state[`normal${slot}` as 'normal1' | 'normal2' | 'normal3' | 'normal4'];
}

async function loadExistingPa(
  apiKey: string,
  recordId: string,
): Promise<{ productIds: string[]; solutionIds: string[]; sectionIds: string[] }> {
  const record = await getRecord(apiKey, PA_TABLE_IDS.productAreas, recordId, PA_BASE_ID);
  return {
    productIds: (record.fields['product_ids'] as string[] | undefined) ?? [],
    solutionIds: (record.fields['solution_ids'] as string[] | undefined) ?? [],
    sectionIds: (record.fields['section_ids'] as string[] | undefined) ?? [],
  };
}

// ─── Create ────────────────────────────────────────────────────────────────

export async function productAreaCreate(
  state: ProductAreaState,
  ctx: { apiKey: string },
): Promise<Result> {
  const airtableKey = ctx.apiKey;

  // Default-coworker injection: om alla contact_*-fält är tomma, slå upp
  // första aktiva coworker från SSOT och förfyll.
  let workingState = state;
  if (contactFieldsEmpty(workingState)) {
    const defaults = await resolveDefaultCoworker({
      apiKey: airtableKey,
      countryCode: 'SE',
    });
    if (defaults) {
      workingState = {
        ...workingState,
        contactName: defaults.contactName,
        contactTitle: defaults.contactTitle,
        contactEmail: defaults.contactEmail,
        contactPhone: defaults.contactPhone,
        contactImage: defaults.contactImage,
      };
    }
  }

  const transformed = buildProductAreaTransform(workingState, 'create');

  const productsResult = emptyRelationResult();
  const solutionsResult = emptyRelationResult();

  // CREATE products i clientIndex-ordning och tracka {clientId, recordId}.
  const sortedProducts = sortByClientIndex(transformed.products);
  const productIdByClientIndex: Record<number, string> = {};
  for (const product of sortedProducts) {
    const created = await createRecord(airtableKey, PA_TABLE_IDS.products, product.fields, PA_BASE_ID);
    productIdByClientIndex[product._clientIndex] = created.id;
    const clientId = workingState.products[product._clientIndex]?.clientId;
    if (clientId) {
      productsResult.created.push({ clientId, recordId: created.id });
    }
  }

  // CREATE solutions — samma tracking.
  const sortedSolutions = sortByClientIndex(transformed.solutions);
  const solutionIdByClientIndex: Record<number, string> = {};
  for (const solution of sortedSolutions) {
    const created = await createRecord(airtableKey, PA_TABLE_IDS.solutions, solution.fields, PA_BASE_ID);
    solutionIdByClientIndex[solution._clientIndex] = created.id;
    const clientId = workingState.solutions[solution._clientIndex]?.clientId;
    if (clientId) {
      solutionsResult.created.push({ clientId, recordId: created.id });
    }
  }

  // CREATE sections (deterministic — no Claude). Slot-ordningen 1-4 mappar
  // till `order` på sub-records; tomma slots (utan H2) skippas.
  const sectionIds: string[] = [];
  for (const slot of [1, 2, 3, 4] as SectionSlot[]) {
    const fields = buildSectionFields(workingState.slug, slot, getSlot(workingState, slot));
    if (!fields) continue;
    const created = await createRecord(
      airtableKey,
      PA_TABLE_IDS.productPageSections,
      fields,
      PA_BASE_ID,
    );
    sectionIds.push(created.id);
  }

  // CREATE Product Area:n med rätt link-array-ordning. `Name` mirroras
  // slug (basens konvention).
  const productIdOrder = workingState.products
    .map((_, i) => productIdByClientIndex[i])
    .filter((id): id is string => !!id);
  const solutionIdOrder = workingState.solutions
    .map((_, i) => solutionIdByClientIndex[i])
    .filter((id): id is string => !!id);

  const paFields: Record<string, unknown> = {
    name: workingState.slug,
    ...transformed.productArea,
    product_ids: productIdOrder,
    solution_ids: solutionIdOrder,
    section_ids: sectionIds,
  };
  if (workingState.division.length > 0) {
    paFields.division_ids = workingState.division;
  }

  const createdPa = await createRecord(airtableKey, PA_TABLE_IDS.productAreas, paFields, PA_BASE_ID);

  return {
    recordId: createdPa.id,
    relations: {
      products: productsResult,
      solutions: solutionsResult,
    },
  };
}

// ─── Update ────────────────────────────────────────────────────────────────

export async function productAreaUpdate(
  recordId: string,
  state: ProductAreaState,
  ctx: { apiKey: string },
): Promise<{ relations: Record<string, RelationSyncResult> }> {
  const airtableKey = ctx.apiKey;

  const existingPa = await loadExistingPa(airtableKey, recordId);
  const existingProductIds = new Set(existingPa.productIds);
  const existingSolutionIds = new Set(existingPa.solutionIds);

  const transformed = buildProductAreaTransform(state, 'update');

  // VIKTIGT: products och solutions är *delade* linkade records. Att ta
  // bort från state UN-LINKAR från denna PA (parent-array uppdateras
  // nedan) men RADERAR inte child-record:en. Aldrig delete här.
  const productsResult = emptyRelationResult();
  const productIdByClientIndex: Record<number, string> = {};

  // CREATE nya products först (saknar _recordId eller pekar på stale ID).
  const newProductEntries = transformed.products
    .filter((p) => !p._recordId || !existingProductIds.has(p._recordId))
    .sort((a, b) => a._clientIndex - b._clientIndex);
  for (const product of newProductEntries) {
    const created = await createRecord(airtableKey, PA_TABLE_IDS.products, product.fields, PA_BASE_ID);
    productIdByClientIndex[product._clientIndex] = created.id;
    const clientId = state.products[product._clientIndex]?.clientId;
    if (clientId) {
      productsResult.created.push({ clientId, recordId: created.id });
    }
  }

  // PATCH existerande products (de som fortfarande refereras).
  const productPatchBatch: Array<{ id: string; fields: Record<string, unknown> }> = [];
  for (const product of transformed.products) {
    if (!product._recordId || !existingProductIds.has(product._recordId)) continue;
    productPatchBatch.push({ id: product._recordId, fields: product.fields });
    productIdByClientIndex[product._clientIndex] = product._recordId;
  }
  if (productPatchBatch.length > 0) {
    await updateRecords(airtableKey, PA_TABLE_IDS.products, productPatchBatch, PA_BASE_ID);
    for (const p of productPatchBatch) productsResult.updated.push(p.id);
  }

  // Products som finns i Airtable men inte i state → unlinked (parent-fältet
  // uppdateras nedan så de "försvinner" från denna PA).
  const stateProductIds = new Set(
    transformed.products
      .filter((p) => p._recordId && existingProductIds.has(p._recordId))
      .map((p) => p._recordId as string),
  );
  for (const existingId of existingProductIds) {
    if (!stateProductIds.has(existingId)) {
      productsResult.unlinked.push(existingId);
    }
  }

  // Solutions — samma struktur som products.
  const solutionsResult = emptyRelationResult();
  const solutionIdByClientIndex: Record<number, string> = {};

  const newSolutionEntries = transformed.solutions
    .filter((s) => !s._recordId || !existingSolutionIds.has(s._recordId))
    .sort((a, b) => a._clientIndex - b._clientIndex);
  for (const solution of newSolutionEntries) {
    const created = await createRecord(airtableKey, PA_TABLE_IDS.solutions, solution.fields, PA_BASE_ID);
    solutionIdByClientIndex[solution._clientIndex] = created.id;
    const clientId = state.solutions[solution._clientIndex]?.clientId;
    if (clientId) {
      solutionsResult.created.push({ clientId, recordId: created.id });
    }
  }

  const solutionPatchBatch: Array<{ id: string; fields: Record<string, unknown> }> = [];
  for (const solution of transformed.solutions) {
    if (!solution._recordId || !existingSolutionIds.has(solution._recordId)) continue;
    solutionPatchBatch.push({ id: solution._recordId, fields: solution.fields });
    solutionIdByClientIndex[solution._clientIndex] = solution._recordId;
  }
  if (solutionPatchBatch.length > 0) {
    await updateRecords(airtableKey, PA_TABLE_IDS.solutions, solutionPatchBatch, PA_BASE_ID);
    for (const s of solutionPatchBatch) solutionsResult.updated.push(s.id);
  }

  const stateSolutionIds = new Set(
    transformed.solutions
      .filter((s) => s._recordId && existingSolutionIds.has(s._recordId))
      .map((s) => s._recordId as string),
  );
  for (const existingId of existingSolutionIds) {
    if (!stateSolutionIds.has(existingId)) {
      solutionsResult.unlinked.push(existingId);
    }
  }

  // Sections — sub-records ägda av PA:n. Match:a state-slots mot existerande
  // records via `order`-fältet. Skapa nya, uppdatera matchande, ta bort
  // orphans (sektioner som inte längre har en motsvarande state-slot).
  const existingSectionIds = existingPa.sectionIds;
  let existingSections: Array<{ id: string; order: number }> = [];
  if (existingSectionIds.length > 0) {
    const formula = `OR(${existingSectionIds.map((id) => `RECORD_ID()='${id}'`).join(',')})`;
    const records = await listRecords(airtableKey, PA_TABLE_IDS.productPageSections, {
      filterByFormula: formula,
      baseId: PA_BASE_ID,
      fields: ['order'],
    });
    existingSections = records.map((r) => ({
      id: r.id,
      order: ((r.fields.order as number) ?? 0),
    }));
  }
  const sectionIdByOrder = new Map<number, string>(
    existingSections.map((s) => [s.order, s.id]),
  );
  const usedSectionIds = new Set<string>();
  const sectionIdOrder: string[] = [];
  for (const slot of [1, 2, 3, 4] as SectionSlot[]) {
    const fields = buildSectionFields(state.slug, slot, getSlot(state, slot));
    if (!fields) continue;
    const existingId = sectionIdByOrder.get(slot);
    if (existingId) {
      await updateRecord(
        airtableKey,
        PA_TABLE_IDS.productPageSections,
        existingId,
        fields,
        PA_BASE_ID,
      );
      sectionIdOrder.push(existingId);
      usedSectionIds.add(existingId);
    } else {
      const created = await createRecord(
        airtableKey,
        PA_TABLE_IDS.productPageSections,
        fields,
        PA_BASE_ID,
      );
      sectionIdOrder.push(created.id);
      usedSectionIds.add(created.id);
    }
  }
  const orphanSectionIds = existingSections
    .map((s) => s.id)
    .filter((id) => !usedSectionIds.has(id));
  if (orphanSectionIds.length > 0) {
    await deleteRecords(
      airtableKey,
      PA_TABLE_IDS.productPageSections,
      orphanSectionIds,
      PA_BASE_ID,
    );
  }

  // PATCH Product Area:n med transformens fält + nya link-array-ordningar.
  const productIdOrder = state.products
    .map((_, i) => productIdByClientIndex[i])
    .filter((id): id is string => !!id);
  const solutionIdOrder = state.solutions
    .map((_, i) => solutionIdByClientIndex[i])
    .filter((id): id is string => !!id);

  await updateRecord(airtableKey, PA_TABLE_IDS.productAreas, recordId, {
    ...transformed.productArea,
    product_ids: productIdOrder,
    solution_ids: solutionIdOrder,
    section_ids: sectionIdOrder,
    division_ids: state.division,
  }, PA_BASE_ID);

  return {
    relations: {
      products: productsResult,
      solutions: solutionsResult,
    },
  };
}

// ─── List mapper ──────────────────────────────────────────────────────────

export interface ProductAreaListItem {
  id: string;
  name: string;
  slug: string;
  h1: string;
  divisionIds: string[];
}

export async function listProductAreas(apiKey: string): Promise<ProductAreaListItem[]> {
  const records = await listRecords(apiKey, PA_TABLE_IDS.productAreas, {
    baseId: PA_BASE_ID,
    fields: ['name', 'slug', 'h1', 'division_ids'],
    sort: [{ field: 'slug', direction: 'asc' }],
  });
  return records.map((r) => ({
    id: r.id,
    name: (r.fields.name as string) || '',
    slug: (r.fields.slug as string) || '',
    h1: (r.fields.h1 as string) || '',
    divisionIds: (r.fields.division_ids as string[] | undefined) ?? [],
  }));
}
