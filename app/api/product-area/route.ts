import { NextResponse } from 'next/server';
import {
  listRecords,
  getRecord,
  createRecord,
  updateRecord,
  updateRecords,
} from '@/lib/airtable';
import { PA_TABLE_IDS } from '@/lib/product-area-mapper';
import { loadProductAreaState, loadDivisions } from '@/lib/product-area-loader';
import { ProductAreaState } from '@/lib/product-area-types';
import { transformProductArea } from '@/lib/claude-transform';
import { invalidateWexoeCoreCache, PA_ENTITIES } from '@/lib/wexoe-cache';

const ANTHROPIC_API_KEY = process.env.ANTHROPIC_API_KEY;
const AIRTABLE_API_KEY = process.env.AIRTABLE_API_KEY;

// ─── GET: list / get / list-divisions ─────────────────────────────────────

export async function GET(request: Request) {
  if (!AIRTABLE_API_KEY) {
    return NextResponse.json({ error: 'AIRTABLE_API_KEY not configured' }, { status: 500 });
  }

  const { searchParams } = new URL(request.url);
  const action = searchParams.get('action');

  try {
    if (action === 'list') {
      const records = await listRecords(AIRTABLE_API_KEY, PA_TABLE_IDS.productAreas, {
        fields: ['Name', 'Slug', 'H1'],
        sort: [{ field: 'Name', direction: 'asc' }],
      });
      const pages = records.map((r) => ({
        id: r.id,
        name: (r.fields.Name as string) || '',
        slug: (r.fields.Slug as string) || '',
        h1: (r.fields.H1 as string) || '',
      }));
      return NextResponse.json({ pages });
    }

    if (action === 'get') {
      const recordId = searchParams.get('id');
      if (!recordId) {
        return NextResponse.json({ error: 'Missing id parameter' }, { status: 400 });
      }
      const state = await loadProductAreaState(AIRTABLE_API_KEY, recordId);
      return NextResponse.json({ state });
    }

    if (action === 'list-divisions') {
      const divisions = await loadDivisions(AIRTABLE_API_KEY);
      return NextResponse.json({ divisions });
    }

    return NextResponse.json(
      { error: 'Unknown action. Use ?action=list | get&id=... | list-divisions' },
      { status: 400 },
    );
  } catch (err) {
    const message = err instanceof Error ? err.message : 'Unknown error';
    return NextResponse.json({ error: message }, { status: 500 });
  }
}

// ─── POST: save (create + update paths both go via Claude) ────────────────

export async function POST(request: Request) {
  if (!AIRTABLE_API_KEY) {
    return NextResponse.json({ error: 'AIRTABLE_API_KEY not configured' }, { status: 500 });
  }
  if (!ANTHROPIC_API_KEY) {
    return NextResponse.json({ error: 'ANTHROPIC_API_KEY not configured' }, { status: 500 });
  }

  try {
    const state: ProductAreaState = await request.json();

    if (!state.slug?.trim()) {
      return NextResponse.json({ error: 'Slug är obligatoriskt' }, { status: 400 });
    }
    if (!state.h1?.trim()) {
      return NextResponse.json({ error: 'H1 är obligatoriskt' }, { status: 400 });
    }

    if (!state.recordId) {
      return await createProductArea(AIRTABLE_API_KEY, ANTHROPIC_API_KEY, state);
    }
    return await updateProductArea(AIRTABLE_API_KEY, ANTHROPIC_API_KEY, state);
  } catch (err) {
    const message = err instanceof Error ? err.message : 'Okänt fel';
    console.error('[product-area:save] Error:', message);
    return NextResponse.json({ error: message }, { status: 500 });
  }
}

// ─── Create flow: brand-new Product Area ──────────────────────────────────

async function createProductArea(
  airtableKey: string,
  anthropicKey: string,
  state: ProductAreaState,
) {
  // 1. Reject duplicate slugs up-front so the user gets a clean error
  //    rather than Airtable's generic 422.
  const existing = await listRecords(airtableKey, PA_TABLE_IDS.productAreas, {
    fields: ['Slug'],
    filterByFormula: `{Slug} = "${state.slug.replace(/"/g, '\\"')}"`,
  });
  if (existing.length > 0) {
    return NextResponse.json(
      { error: `Slug "${state.slug}" finns redan. Välj ett annat.` },
      { status: 409 },
    );
  }

  // 2. Claude transforms state → Airtable-ready fields
  const transformed = await transformProductArea(anthropicKey, state, 'create');

  // 3. CREATE any new linked products first so we have their IDs to link.
  //    Sort by _clientIndex defensively in case Claude reordered. Track
  //    new IDs by both client position (for the link-array ordering
  //    below) and by clientId (returned to the client so the editor can
  //    promote empty `recordId: ''` rows in its local state and avoid
  //    duplicate creation on the next save).
  const sortedProducts = sortByClientIndex(transformed.products);
  const productIdByClientIndex: Record<number, string> = {};
  const newProductIds: Record<string, string> = {};
  for (const product of sortedProducts) {
    const created = await createRecord(
      airtableKey,
      PA_TABLE_IDS.products,
      product.fields,
    );
    productIdByClientIndex[product._clientIndex] = created.id;
    const clientId = state.products[product._clientIndex]?.clientId;
    if (clientId) newProductIds[clientId] = created.id;
  }

  // 4. CREATE any new linked solutions — same tracking as products.
  const sortedSolutions = sortByClientIndex(transformed.solutions);
  const solutionIdByClientIndex: Record<number, string> = {};
  const newSolutionIds: Record<string, string> = {};
  for (const solution of sortedSolutions) {
    const created = await createRecord(
      airtableKey,
      PA_TABLE_IDS.solutions,
      solution.fields,
    );
    solutionIdByClientIndex[solution._clientIndex] = created.id;
    const clientId = state.solutions[solution._clientIndex]?.clientId;
    if (clientId) newSolutionIds[clientId] = created.id;
  }

  // 5. CREATE the Product Area record with direct fields + linked record
  //    arrays in the right order. `Name` mirrors the slug (base convention).
  //    Division, Products, Solutions are backend-managed per schema.
  const productIdOrder = state.products.map(
    (_, i) => productIdByClientIndex[i],
  ).filter((id): id is string => !!id);
  const solutionIdOrder = state.solutions.map(
    (_, i) => solutionIdByClientIndex[i],
  ).filter((id): id is string => !!id);

  const paFields: Record<string, unknown> = {
    Name: state.slug,
    ...transformed.productArea,
    Products: productIdOrder,
    Solutions: solutionIdOrder,
  };
  if (state.division.length > 0) {
    paFields.Division = state.division;
  }

  const createdPa = await createRecord(
    airtableKey,
    PA_TABLE_IDS.productAreas,
    paFields,
  );

  // Drop the matching Wexoe Core entity caches in WordPress so the live
  // site reflects this new product area on the next request.
  await invalidateWexoeCoreCache(PA_ENTITIES, 'product-area:create');

  return NextResponse.json({
    success: true,
    mode: 'create' as const,
    recordId: createdPa.id,
    slug: state.slug,
    productsCreated: sortedProducts.length,
    solutionsCreated: sortedSolutions.length,
    newProductIds,
    newSolutionIds,
  });
}

// ─── Update flow: existing Product Area ───────────────────────────────────

async function updateProductArea(
  airtableKey: string,
  anthropicKey: string,
  state: ProductAreaState,
) {
  if (!state.recordId) throw new Error('updateProductArea called without recordId');

  // 1. Fetch existing PA so we know which linked products and solutions
  //    currently live under it — needed for the diff (PATCH vs CREATE vs
  //    DELETE). Only the linked-record IDs matter; field values come from
  //    Claude's transformation of current state.
  const existingPa = await loadExistingPa(airtableKey, state.recordId);
  const existingProductIds = new Set(existingPa.productIds);
  const existingSolutionIds = new Set(existingPa.solutionIds);

  // 2. Claude transforms state → Airtable-ready fields
  const transformed = await transformProductArea(anthropicKey, state, 'update');

  // 3. Diff + apply products. Claude echoes _clientIndex / _recordId.
  //
  //    IMPORTANT: products (and solutions) are *shared* linked records in
  //    Airtable — the same product row might be linked to multiple Product
  //    Areas. The builder's v1 update contract is that removing a product
  //    from state UN-LINKS it from this PA (by leaving it out of the PA's
  //    `Products` array patch below) but never DELETES the underlying
  //    product record. Only CREATE new + PATCH existing here.
  //
  //    We also track freshly-created IDs keyed by the client-side
  //    `clientId` so the editor can promote its empty `recordId: ''`
  //    rows after a successful save and avoid creating the same rows
  //    again on the next save.
  const productIdByClientIndex: Record<number, string> = {};
  const newProductIds: Record<string, string> = {};

  // Create new products (no _recordId, or stale _recordId not in existing).
  // Must happen first so we can include their IDs in the PA's Products
  // link array below.
  const newProductEntries = transformed.products
    .filter((p) => !p._recordId || !existingProductIds.has(p._recordId))
    .sort((a, b) => a._clientIndex - b._clientIndex);
  for (const product of newProductEntries) {
    const created = await createRecord(
      airtableKey,
      PA_TABLE_IDS.products,
      product.fields,
    );
    productIdByClientIndex[product._clientIndex] = created.id;
    const clientId = state.products[product._clientIndex]?.clientId;
    if (clientId) newProductIds[clientId] = created.id;
  }

  // Patch existing products that are still referenced in state.
  const productPatchBatch: Array<{ id: string; fields: Record<string, unknown> }> = [];
  for (const product of transformed.products) {
    if (!product._recordId || !existingProductIds.has(product._recordId)) continue;
    productPatchBatch.push({ id: product._recordId, fields: product.fields });
    productIdByClientIndex[product._clientIndex] = product._recordId;
  }
  if (productPatchBatch.length > 0) {
    await updateRecords(airtableKey, PA_TABLE_IDS.products, productPatchBatch);
  }

  // 4. Same diff for solutions — also shared, also never deleted, only
  //    unlinked via the PA patch below.
  const solutionIdByClientIndex: Record<number, string> = {};
  const newSolutionIds: Record<string, string> = {};

  const newSolutionEntries = transformed.solutions
    .filter((s) => !s._recordId || !existingSolutionIds.has(s._recordId))
    .sort((a, b) => a._clientIndex - b._clientIndex);
  for (const solution of newSolutionEntries) {
    const created = await createRecord(
      airtableKey,
      PA_TABLE_IDS.solutions,
      solution.fields,
    );
    solutionIdByClientIndex[solution._clientIndex] = created.id;
    const clientId = state.solutions[solution._clientIndex]?.clientId;
    if (clientId) newSolutionIds[clientId] = created.id;
  }

  const solutionPatchBatch: Array<{ id: string; fields: Record<string, unknown> }> = [];
  for (const solution of transformed.solutions) {
    if (!solution._recordId || !existingSolutionIds.has(solution._recordId)) continue;
    solutionPatchBatch.push({ id: solution._recordId, fields: solution.fields });
    solutionIdByClientIndex[solution._clientIndex] = solution._recordId;
  }
  if (solutionPatchBatch.length > 0) {
    await updateRecords(airtableKey, PA_TABLE_IDS.solutions, solutionPatchBatch);
  }

  // 5. PATCH the Product Area record itself with Claude's transformed fields
  //    plus the fresh linked-record arrays (using state order). Removed
  //    products/solutions drop out of these arrays here — that's how the
  //    "unlink from PA, don't delete the row" rule is enforced.
  const productIdOrder = state.products
    .map((_, i) => productIdByClientIndex[i])
    .filter((id): id is string => !!id);
  const solutionIdOrder = state.solutions
    .map((_, i) => solutionIdByClientIndex[i])
    .filter((id): id is string => !!id);

  await updateRecord(airtableKey, PA_TABLE_IDS.productAreas, state.recordId, {
    ...transformed.productArea,
    Products: productIdOrder,
    Solutions: solutionIdOrder,
    Division: state.division,
  });

  // Cache-bust Wexoe Core after a successful update so live edits aren't
  // hidden behind the 24h transient TTL on the WordPress side.
  await invalidateWexoeCoreCache(PA_ENTITIES, 'product-area:update');

  return NextResponse.json({
    success: true,
    mode: 'update' as const,
    recordId: state.recordId,
    slug: state.slug,
    productsCreated: newProductEntries.length,
    productsUpdated: productPatchBatch.length,
    solutionsCreated: newSolutionEntries.length,
    solutionsUpdated: solutionPatchBatch.length,
    newProductIds,
    newSolutionIds,
  });
}

// ─── Helpers ───────────────────────────────────────────────────────────────

function sortByClientIndex<T extends { _clientIndex: number }>(arr: T[]): T[] {
  return [...arr].sort((a, b) => a._clientIndex - b._clientIndex);
}

/** Read the existing PA record and return the linked product/solution IDs
 *  so the update path can diff against them. Full records aren't needed
 *  here — the transformation comes from Claude, not the stored data. */
async function loadExistingPa(
  apiKey: string,
  recordId: string,
): Promise<{ productIds: string[]; solutionIds: string[] }> {
  const record = await getRecord(apiKey, PA_TABLE_IDS.productAreas, recordId);
  return {
    productIds: (record.fields['Products'] as string[] | undefined) ?? [],
    solutionIds: (record.fields['Solutions'] as string[] | undefined) ?? [],
  };
}
