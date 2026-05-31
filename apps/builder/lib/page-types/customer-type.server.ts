/**
 * Customer-type-page — server-side sidtypsdefinition (Lager 3).
 *
 * FAS 2: skrivvägen är DETERMINISTISK — `customerTypeToFields(state, mode)`
 * producerar Airtable-fälten direkt ur state + JSON-schemat. Inga
 * Anthropic-anrop, ingen latens, ingen risk att tappa innehåll.
 * Schemat är flatt (en tabell, inga child-records); read och write delar
 * samma `schema/cms_customer_type_pages.json`.
 */

import { AirtableRecord } from '../airtable';
import { createRecord, updateRecord } from '../airtable';
import {
  CUSTOMER_TYPE_TABLE_IDS,
  CUSTOMER_TYPE_BASE_ID,
  customerTypePageStateFromRecord,
  customerTypeToFields,
} from '../customer-type-mapper';
import { loadCustomerTypePageState } from '../customer-type-loader';
import { CustomerTypePageState, emptyCustomerTypePageState } from '../customer-type-types';
import { CUSTOMER_TYPE_PAGE_ENTITIES } from '../wexoe-cache';
import type { PageTypeServerDef } from './types';

export interface CustomerTypePageListItem {
  id: string;
  name: string;
  slug: string;
  h1: string;
}

// FAS 2: deterministisk skrivväg — inga Anthropic-anrop. `customerTypeToFields`
// producerar Airtable-fälten direkt ur state + schema.
async function customerTypeCreate(
  state: CustomerTypePageState,
  ctx: { apiKey: string },
): Promise<{ recordId: string }> {
  const fields = customerTypeToFields(state, 'create');
  const created = await createRecord(
    ctx.apiKey,
    CUSTOMER_TYPE_TABLE_IDS.customerTypePages,
    fields,
    CUSTOMER_TYPE_BASE_ID,
  );
  return { recordId: created.id };
}

async function customerTypeUpdate(
  recordId: string,
  state: CustomerTypePageState,
  ctx: { apiKey: string },
): Promise<{ relations: Record<string, never> }> {
  const fields = customerTypeToFields(state, 'update');
  await updateRecord(
    ctx.apiKey,
    CUSTOMER_TYPE_TABLE_IDS.customerTypePages,
    recordId,
    fields,
    CUSTOMER_TYPE_BASE_ID,
  );
  return { relations: {} };
}

export const customerTypeServer: PageTypeServerDef<
  CustomerTypePageState,
  CustomerTypePageListItem
> = {
  id: 'customer-type',
  label: 'Kundtyp',
  tableId: CUSTOMER_TYPE_TABLE_IDS.customerTypePages,
  baseId: CUSTOMER_TYPE_BASE_ID,
  emptyState: emptyCustomerTypePageState,
  fromRecord: customerTypePageStateFromRecord,

  // Lager 3 — skriv-vägen går via deterministisk transform (ingen Claude).
  create: customerTypeCreate,
  update: customerTypeUpdate,

  validate: (s) => {
    if (!s.title?.trim()) return { field: 'title', message: 'Title är obligatoriskt.' };
    return null;
  },
  listItemMapper: (r: AirtableRecord): CustomerTypePageListItem => ({
    id: r.id,
    name: (r.fields.name as string) || (r.fields.slug as string) || '',
    slug: (r.fields.slug as string) || '',
    h1: (r.fields.title as string) || '',
  }),
  listFields: ['slug', 'title', 'name', 'eyebrow'],
  listSort: [{ field: 'slug', direction: 'asc' }],
  cacheEntities: CUSTOMER_TYPE_PAGE_ENTITIES,
  slug: {
    accessor: (s) => s.slug,
    field: 'slug',
    checkDuplicate: true,
  },
};

export { loadCustomerTypePageState };
