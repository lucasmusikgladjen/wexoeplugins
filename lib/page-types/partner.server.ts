/**
 * Partner-page (leverantörssida) — server-side sidtypsdefinition (Lager 3).
 *
 * FAS 2: skrivvägen är DETERMINISTISK — `buildPartnerFields(state, mode)`
 * producerar Airtable-fälten direkt (facts flattas till slot-fält, faqs
 * serialiseras till JSON, why_benefits joinas till lines, ikoner valideras).
 * Inga Anthropic-anrop. Flatt schema, inga child-records.
 *
 * Linkade fält (`partner_ids`, `country_ids`, `case_ids`, `category_ids`)
 * skrivs som ID-arrayer av transformen. Linked target-records
 * (cms_cases, cms_product_pages, core_partners) redigeras aldrig härifrån
 * — pickers väljer bara existerande poster.
 */

import { AirtableRecord, createRecord, updateRecord } from '../airtable';
import {
  PARTNER_TABLE_IDS,
  PARTNER_BASE_ID,
  partnerPageStateFromRecord,
} from '../partner-mapper';
import { loadPartnerPageState } from '../partner-loader';
import { PartnerPageState, emptyPartnerPageState } from '../partner-types';
import { PARTNER_ENTITIES } from '../wexoe-cache';
import { buildPartnerFields } from '../deterministic-transform';
import type { PageTypeServerDef } from './types';

export interface PartnerPageListItem {
  id: string;
  name: string;
  slug: string;
  h1: string;
}

// FAS 2: deterministisk skrivväg — inga Anthropic-anrop.
async function partnerCreate(
  state: PartnerPageState,
  ctx: { apiKey: string },
): Promise<{ recordId: string }> {
  const fields = buildPartnerFields(state, 'create');
  const created = await createRecord(
    ctx.apiKey,
    PARTNER_TABLE_IDS.partnerPages,
    fields,
    PARTNER_BASE_ID,
  );
  return { recordId: created.id };
}

async function partnerUpdate(
  recordId: string,
  state: PartnerPageState,
  ctx: { apiKey: string },
): Promise<{ relations: Record<string, never> }> {
  const fields = buildPartnerFields(state, 'update');
  await updateRecord(
    ctx.apiKey,
    PARTNER_TABLE_IDS.partnerPages,
    recordId,
    fields,
    PARTNER_BASE_ID,
  );
  return { relations: {} };
}

export const partnerServer: PageTypeServerDef<PartnerPageState, PartnerPageListItem> = {
  id: 'partner',
  label: 'Leverantörssida',
  tableId: PARTNER_TABLE_IDS.partnerPages,
  baseId: PARTNER_BASE_ID,
  emptyState: emptyPartnerPageState,
  fromRecord: partnerPageStateFromRecord,

  // Lager 3 — skriv-vägen går via deterministisk transform (ingen Claude).
  create: partnerCreate,
  update: partnerUpdate,

  validate: (s) => {
    if (!s.h1?.trim()) return { field: 'h1', message: 'H1 är obligatoriskt.' };
    return null;
  },
  listItemMapper: (r: AirtableRecord): PartnerPageListItem => ({
    id: r.id,
    name:
      (r.fields.h1 as string) ||
      (r.fields.slug as string) ||
      '',
    slug: (r.fields.slug as string) || '',
    h1: (r.fields.h1 as string) || '',
  }),
  listFields: ['slug', 'h1', 'is_active'],
  listSort: [{ field: 'slug', direction: 'asc' }],
  cacheEntities: PARTNER_ENTITIES,
  slug: {
    accessor: (s) => s.slug,
    field: 'slug',
    validateFormat: true,
    checkDuplicate: true,
  },
};

export { loadPartnerPageState };
