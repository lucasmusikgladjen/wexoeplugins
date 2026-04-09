import { NextResponse } from 'next/server';
import {
  listRecords,
  createRecord,
  updateRecord,
  updateRecords,
} from '@/lib/airtable';
import {
  PA_TABLE_IDS,
  productAreaPatchFields,
  productPatchFields,
  solutionPatchFields,
} from '@/lib/product-area-mapper';
import { loadProductAreaState, loadDivisions } from '@/lib/product-area-loader';
import { ProductAreaState } from '@/lib/product-area-types';

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

// ─── POST: save — branches on presence of recordId ────────────────────────

export async function POST(request: Request) {
  if (!AIRTABLE_API_KEY) {
    return NextResponse.json({ error: 'AIRTABLE_API_KEY not configured' }, { status: 500 });
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
      return await createProductArea(AIRTABLE_API_KEY, state);
    }
    return await updateProductArea(AIRTABLE_API_KEY, state);
  } catch (err) {
    const message = err instanceof Error ? err.message : 'Okänt fel';
    console.error('[product-area:save] Error:', message);
    return NextResponse.json({ error: message }, { status: 500 });
  }
}

// ─── Create flow: brand-new Product Area ──────────────────────────────────

async function createProductArea(apiKey: string, state: ProductAreaState) {
  // Defensive: reject duplicate slugs up-front with a clear error.
  const existing = await listRecords(apiKey, PA_TABLE_IDS.productAreas, {
    fields: ['Slug'],
    filterByFormula: `{Slug} = "${state.slug.replace(/"/g, '\\"')}"`,
  });
  if (existing.length > 0) {
    return NextResponse.json(
      { error: `Slug "${state.slug}" finns redan. Välj ett annat.` },
      { status: 409 },
    );
  }

  // 1. CREATE any new linked products first so we have their IDs.
  const newProductIds: Record<string, string> = {};
  for (const product of state.products) {
    const created = await createRecord(
      apiKey,
      PA_TABLE_IDS.products,
      productPatchFields(product),
    );
    newProductIds[product.clientId] = created.id;
  }

  // 2. CREATE any new linked solutions.
  const newSolutionIds: Record<string, string> = {};
  for (const solution of state.solutions) {
    const created = await createRecord(
      apiKey,
      PA_TABLE_IDS.solutions,
      solutionPatchFields(solution),
    );
    newSolutionIds[solution.clientId] = created.id;
  }

  // 3. CREATE the Product Area record itself with all direct fields plus
  //    the freshly minted linked-record arrays. `Name` mirrors the slug
  //    (same convention the existing records in the base use).
  const productIdOrder = state.products.map(
    (p) => p.recordId || newProductIds[p.clientId],
  );
  const solutionIdOrder = state.solutions.map(
    (s) => s.recordId || newSolutionIds[s.clientId],
  );

  const created = await createRecord(apiKey, PA_TABLE_IDS.productAreas, {
    Name: state.slug,
    ...productAreaPatchFields(state),
    Products: productIdOrder,
    Solutions: solutionIdOrder,
  });

  return NextResponse.json({
    success: true,
    mode: 'create' as const,
    recordId: created.id,
    slug: state.slug,
    productsCreated: Object.keys(newProductIds).length,
    solutionsCreated: Object.keys(newSolutionIds).length,
    newProductIds,
    newSolutionIds,
  });
}

// ─── Update flow: existing Product Area ───────────────────────────────────

async function updateProductArea(apiKey: string, state: ProductAreaState) {
  // 1. CREATE any new products (no recordId yet).
  const newProductIds: Record<string, string> = {};
  for (const product of state.products) {
    if (product.recordId) continue;
    const created = await createRecord(
      apiKey,
      PA_TABLE_IDS.products,
      productPatchFields(product),
    );
    newProductIds[product.clientId] = created.id;
  }

  // 2. CREATE any new solutions.
  const newSolutionIds: Record<string, string> = {};
  for (const solution of state.solutions) {
    if (solution.recordId) continue;
    const created = await createRecord(
      apiKey,
      PA_TABLE_IDS.solutions,
      solutionPatchFields(solution),
    );
    newSolutionIds[solution.clientId] = created.id;
  }

  // 3. Build full ordered ID arrays (existing + newly created).
  const productIdOrder = state.products.map(
    (p) => p.recordId || newProductIds[p.clientId],
  );
  const solutionIdOrder = state.solutions.map(
    (s) => s.recordId || newSolutionIds[s.clientId],
  );

  // 4. PATCH the Product Area record itself — including updated
  //    Products / Solutions link arrays in case the ordering changed
  //    or new records were added.
  await updateRecord(
    apiKey,
    PA_TABLE_IDS.productAreas,
    state.recordId,
    {
      ...productAreaPatchFields(state),
      Products: productIdOrder,
      Solutions: solutionIdOrder,
    },
  );

  // 5. PATCH existing linked products.
  const productUpdates = state.products
    .filter((p) => p.recordId)
    .map((p) => ({
      id: p.recordId,
      fields: productPatchFields(p),
    }));
  if (productUpdates.length > 0) {
    await updateRecords(apiKey, PA_TABLE_IDS.products, productUpdates);
  }

  // 6. PATCH existing linked solutions.
  const solutionUpdates = state.solutions
    .filter((s) => s.recordId)
    .map((s) => ({
      id: s.recordId,
      fields: solutionPatchFields(s),
    }));
  if (solutionUpdates.length > 0) {
    await updateRecords(apiKey, PA_TABLE_IDS.solutions, solutionUpdates);
  }

  return NextResponse.json({
    success: true,
    mode: 'update' as const,
    recordId: state.recordId,
    slug: state.slug,
    productsCreated: Object.keys(newProductIds).length,
    productsUpdated: productUpdates.length,
    solutionsCreated: Object.keys(newSolutionIds).length,
    solutionsUpdated: solutionUpdates.length,
    newProductIds,
    newSolutionIds,
  });
}
