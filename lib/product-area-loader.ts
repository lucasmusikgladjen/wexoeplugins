import { getRecord, listRecords, AirtableRecord } from './airtable';
import { PA_TABLE_IDS, productAreaStateFromRecords } from './product-area-mapper';
import { ProductAreaState, Division } from './product-area-types';

/** Fetch all Divisions (tiny table — no pagination concerns). */
export async function loadDivisions(apiKey: string): Promise<Division[]> {
  const records = await listRecords(apiKey, PA_TABLE_IDS.divisions, {
    fields: ['Name'],
    sort: [{ field: 'Name', direction: 'asc' }],
  });
  return records.map((r) => ({
    id: r.id,
    name: (r.fields.Name as string) || '',
  }));
}

/** Fetches a Product Area record plus all linked Products, Articles, and
 *  Solutions, and returns a fully-populated ProductAreaState. */
export async function loadProductAreaState(
  apiKey: string,
  recordId: string,
): Promise<ProductAreaState> {
  const pa = await getRecord(apiKey, PA_TABLE_IDS.productAreas, recordId);

  const productIds = (pa.fields['Products'] as string[] | undefined) ?? [];
  const solutionIds = (pa.fields['Solutions'] as string[] | undefined) ?? [];

  let products: AirtableRecord[] = [];
  if (productIds.length > 0) {
    const formula = `OR(${productIds.map((id) => `RECORD_ID()='${id}'`).join(',')})`;
    products = await listRecords(apiKey, PA_TABLE_IDS.products, { filterByFormula: formula });
  }

  const articleIds = new Set<string>();
  for (const p of products) {
    const ids = (p.fields['Articles'] as string[] | undefined) ?? [];
    ids.forEach((id) => articleIds.add(id));
  }

  let articles: AirtableRecord[] = [];
  if (articleIds.size > 0) {
    const formula = `OR(${[...articleIds].map((id) => `RECORD_ID()='${id}'`).join(',')})`;
    articles = await listRecords(apiKey, PA_TABLE_IDS.articles, { filterByFormula: formula });
  }

  let solutions: AirtableRecord[] = [];
  if (solutionIds.length > 0) {
    const formula = `OR(${solutionIds.map((id) => `RECORD_ID()='${id}'`).join(',')})`;
    solutions = await listRecords(apiKey, PA_TABLE_IDS.solutions, { filterByFormula: formula });
  }

  return productAreaStateFromRecords({
    productArea: pa,
    products,
    articles,
    solutions,
  });
}
