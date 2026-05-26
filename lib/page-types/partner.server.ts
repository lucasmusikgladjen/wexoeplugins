/**
 * Partner-page (leverantörssida) — server-side sidtypsdefinition (Lager 3).
 *
 * Lager 3 + Claude-transform: state → Airtable-fält går alltid via Claude
 * (`transformPartner`), inte en handskriven mapper. Identisk struktur med
 * `customer-type.server.ts` — partner-sidan har också ett flatt schema
 * (en tabell, inga child-records).
 *
 * Linkade fält (`partner_ids`, `country_ids`, `case_ids`, `category_ids`)
 * skrivs som ID-arrayer från Claude:s output. Linked target-records
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
import { transformPartner } from '../claude-transform';
import type { PageTypeServerDef } from './types';

export interface PartnerPageListItem {
  id: string;
  name: string;
  slug: string;
  h1: string;
}

function requireAnthropicKey(): string {
  const key = process.env.ANTHROPIC_API_KEY;
  if (!key) throw new Error('ANTHROPIC_API_KEY ej konfigurerad.');
  return key;
}

async function partnerCreate(
  state: PartnerPageState,
  ctx: { apiKey: string },
): Promise<{ recordId: string }> {
  const anthropicKey = requireAnthropicKey();
  const { partnerPage } = await transformPartner(anthropicKey, state, 'create');
  const created = await createRecord(
    ctx.apiKey,
    PARTNER_TABLE_IDS.partnerPages,
    partnerPage,
    PARTNER_BASE_ID,
  );
  return { recordId: created.id };
}

async function partnerUpdate(
  recordId: string,
  state: PartnerPageState,
  ctx: { apiKey: string },
): Promise<{ relations: Record<string, never> }> {
  const anthropicKey = requireAnthropicKey();
  const { partnerPage } = await transformPartner(anthropicKey, state, 'update');
  await updateRecord(
    ctx.apiKey,
    PARTNER_TABLE_IDS.partnerPages,
    recordId,
    partnerPage,
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

  // Lager 3 — skriv-vägen går via Claude-transform.
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
