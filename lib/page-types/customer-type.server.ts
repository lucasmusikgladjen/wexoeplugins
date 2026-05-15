/**
 * Customer-type-page — server-side sidtypsdefinition.
 *
 * Mappers, validering och list-projektion. Ingen React-kod.
 */

import { AirtableRecord } from '../airtable';
import {
  CUSTOMER_TYPE_TABLE_IDS,
  CUSTOMER_TYPE_BASE_ID,
  customerTypePageStateFromRecord,
  customerTypePageStateToFields,
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
  stateToFields: customerTypePageStateToFields,
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
