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
import { loadProductAreaState } from '@/lib/product-area-loader';
import { ProductAreaState } from '@/lib/product-area-types';

const AIRTABLE_API_KEY = process.env.AIRTABLE_API_KEY;

// ─── GET: list or get ──────────────────────────────────────────────────────

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

    return NextResponse.json(
      { error: 'Unknown action. Use ?action=list or ?action=get&id=recXXX' },
      { status: 400 },
    );
  } catch (err) {
    const message = err instanceof Error ? err.message : 'Unknown error';
    return NextResponse.json({ error: message }, { status: 500 });
  }
}

// ─── POST: save (update + create new linked records) ──────────────────────

export async function POST(request: Request) {
  if (!AIRTABLE_API_KEY) {
    return NextResponse.json({ error: 'AIRTABLE_API_KEY not configured' }, { status: 500 });
  }

  try {
    const state: ProductAreaState = await request.json();

    if (!state.recordId) {
      return NextResponse.json(
        { error: 'Product Area save kräver ett existerande recordId (ingen create i v1).' },
        { status: 400 },
      );
    }
    if (!state.slug?.trim()) {
      return NextResponse.json({ error: 'Slug är obligatoriskt' }, { status: 400 });
    }

    // ── 1. CREATE any new products (no recordId yet) ──────────────────
    const newProductIds: Record<string, string> = {};
    for (const product of state.products) {
      if (product.recordId) continue;
      const created = await createRecord(
        AIRTABLE_API_KEY,
        PA_TABLE_IDS.products,
        productPatchFields(product),
      );
      newProductIds[product.clientId] = created.id;
    }

    // ── 2. CREATE any new solutions ───────────────────────────────────
    const newSolutionIds: Record<string, string> = {};
    for (const solution of state.solutions) {
      if (solution.recordId) continue;
      const created = await createRecord(
        AIRTABLE_API_KEY,
        PA_TABLE_IDS.solutions,
        solutionPatchFields(solution),
      );
      newSolutionIds[solution.clientId] = created.id;
    }

    // ── 3. Build full ordered ID arrays (existing + newly created) ───
    const productIdOrder = state.products.map(
      (p) => p.recordId || newProductIds[p.clientId],
    );
    const solutionIdOrder = state.solutions.map(
      (s) => s.recordId || newSolutionIds[s.clientId],
    );

    // ── 4. PATCH the Product Area record itself — including updated
    //       Products / Solutions link arrays in case the ordering changed
    //       or new records were added.
    await updateRecord(
      AIRTABLE_API_KEY,
      PA_TABLE_IDS.productAreas,
      state.recordId,
      {
        ...productAreaPatchFields(state),
        Products: productIdOrder,
        Solutions: solutionIdOrder,
      },
    );

    // ── 5. PATCH existing linked products ─────────────────────────────
    const productUpdates = state.products
      .filter((p) => p.recordId)
      .map((p) => ({
        id: p.recordId,
        fields: productPatchFields(p),
      }));
    if (productUpdates.length > 0) {
      await updateRecords(AIRTABLE_API_KEY, PA_TABLE_IDS.products, productUpdates);
    }

    // ── 6. PATCH existing linked solutions ────────────────────────────
    const solutionUpdates = state.solutions
      .filter((s) => s.recordId)
      .map((s) => ({
        id: s.recordId,
        fields: solutionPatchFields(s),
      }));
    if (solutionUpdates.length > 0) {
      await updateRecords(AIRTABLE_API_KEY, PA_TABLE_IDS.solutions, solutionUpdates);
    }

    return NextResponse.json({
      success: true,
      recordId: state.recordId,
      slug: state.slug,
      productsCreated: Object.keys(newProductIds).length,
      productsUpdated: productUpdates.length,
      solutionsCreated: Object.keys(newSolutionIds).length,
      solutionsUpdated: solutionUpdates.length,
      newProductIds,
      newSolutionIds,
    });
  } catch (err) {
    const message = err instanceof Error ? err.message : 'Okänt fel';
    console.error('[product-area:save] Error:', message);
    return NextResponse.json({ error: message }, { status: 500 });
  }
}
