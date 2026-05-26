/**
 * Case-page — server-side sidtypsdefinition (Lager 3).
 *
 * Lager 3 + Claude-transform: state → Airtable-fält går alltid via
 * `transformCase`, inte en handskriven mapper. Matchar konventionen för alla
 * sidtyper i buildern.
 *
 * Schemat är flatt (en tabell, inga child-records). Pseudo-arrays
 * (quick_stats, results, gallery_images) och linked-records (product_ids,
 * article_ids) hanteras inom samma record — Claude expanderar pseudo-arrays
 * och passar genom linked-record-arrayer.
 */

import { AirtableRecord, createRecord, updateRecord } from '../airtable';
import {
  CASE_TABLE_ID,
  CASE_BASE_ID,
  caseStateFromRecord,
} from '../case-mapper';
import { loadCaseState } from '../case-loader';
import { CaseState, emptyCaseState } from '../case-types';
import { CASE_ENTITIES } from '../wexoe-cache';
import { transformCase } from '../claude-transform';
import type { PageTypeServerDef } from './types';

export interface CaseListItem {
  id: string;
  name: string;
  slug: string;
  h1: string;
  customerName: string;
}

function requireAnthropicKey(): string {
  const key = process.env.ANTHROPIC_API_KEY;
  if (!key) throw new Error('ANTHROPIC_API_KEY ej konfigurerad.');
  return key;
}

async function caseCreate(
  state: CaseState,
  ctx: { apiKey: string },
): Promise<{ recordId: string }> {
  const anthropicKey = requireAnthropicKey();
  const { case: fields } = await transformCase(anthropicKey, state, 'create');
  const created = await createRecord(ctx.apiKey, CASE_TABLE_ID, fields, CASE_BASE_ID);
  return { recordId: created.id };
}

async function caseUpdate(
  recordId: string,
  state: CaseState,
  ctx: { apiKey: string },
): Promise<{ relations: Record<string, never> }> {
  const anthropicKey = requireAnthropicKey();
  const { case: fields } = await transformCase(anthropicKey, state, 'update');
  await updateRecord(ctx.apiKey, CASE_TABLE_ID, recordId, fields, CASE_BASE_ID);
  return { relations: {} };
}

export const caseServer: PageTypeServerDef<CaseState, CaseListItem> = {
  id: 'case',
  label: 'Case',
  tableId: CASE_TABLE_ID,
  baseId: CASE_BASE_ID,
  emptyState: emptyCaseState,
  fromRecord: caseStateFromRecord,

  // Lager 3 — skriv-vägen går via Claude-transform.
  create: caseCreate,
  update: caseUpdate,

  validate: (s) => {
    if (!s.title?.trim()) return { field: 'title', message: 'Titel är obligatoriskt.' };
    return null;
  },
  listItemMapper: (r: AirtableRecord): CaseListItem => ({
    id: r.id,
    name: (r.fields.title as string) || (r.fields.slug as string) || '',
    slug: (r.fields.slug as string) || '',
    h1: (r.fields.title as string) || '',
    customerName: (r.fields.customer_name as string) || '',
  }),
  listFields: ['slug', 'title', 'customer_name', 'industry', 'is_active'],
  listSort: [{ field: 'slug', direction: 'asc' }],
  cacheEntities: CASE_ENTITIES,
  slug: {
    accessor: (s) => s.slug,
    field: 'slug',
    checkDuplicate: true,
  },
};

export { loadCaseState };
