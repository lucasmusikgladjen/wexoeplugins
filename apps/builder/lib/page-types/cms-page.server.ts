/**
 * CMS Page — server-side sidtypsdefinition (Lager 3).
 *
 * Multi-tabell sidtyp som inte passar i Lager 2 (declarative relations):
 *   - cms_pages (primary)
 *   - cms_page_sections (polymorf — 15 typer)
 *   - cms_section_tabs (sub-records för tabs-typen)
 *
 * Skriv-vägen är override:d i `cms-page-actions.ts` och transformerar
 * deterministiskt (rena funktioner, inga Claude-anrop). Slug-validering,
 * validate-hooken och cache-invalidering körs fortfarande av factory:n.
 *
 * `emptyState`/`fromRecord` är required-by-type-stubs — `loadCmsPageState()`
 * är den enda korrekta load-vägen (hämtar page + sektioner + tabs i en sweep).
 */

import { AirtableRecord, SSOT_BASE_ID } from '../airtable';
import {
  cmsPageCreate,
  cmsPageUpdate,
  cmsPageDelete,
  type CmsPageListItem,
} from './cms-page-actions';
import { loadCmsPageState } from '../cms-page-loader';
import { CMS_PAGES_TABLE_ID, CmsPageState } from '../cms-page-types';
import { CMS_PAGES_ENTITIES } from '../wexoe-cache';
import type { PageTypeServerDef } from './types';

function unreachable(): never {
  throw new Error('cms-page: använd loadCmsPageState() istället för fromRecord/emptyState.');
}

export const cmsPageServer: PageTypeServerDef<CmsPageState, CmsPageListItem> = {
  id: 'cms-page',
  label: 'Sida',
  tableId: CMS_PAGES_TABLE_ID,
  baseId: SSOT_BASE_ID,

  // Lager 3 — hela skriv-vägen är override:d. Delete behöver också override
  // eftersom defaultDelete bara cascade:ar via `relations[]` (tom här).
  create: cmsPageCreate,
  update: cmsPageUpdate,
  delete: cmsPageDelete,

  // Required-by-type-stubs (se kommentar ovan).
  emptyState: unreachable,
  fromRecord: unreachable,

  validate: (s) => {
    if (!s.slug?.trim()) return { field: 'slug', message: 'Slug är obligatorisk.' };
    return null;
  },
  listItemMapper: (r: AirtableRecord): CmsPageListItem => ({
    id: r.id,
    slug: (r.fields['slug'] as string) ?? '',
    h1: (r.fields['h1'] as string) ?? '',
    internalLabel: (r.fields['internal_label'] as string) ?? '',
    isPublished: r.fields['is_published'] === true,
    divisionIds: (r.fields['division_ids'] as string[] | undefined) ?? [],
    countryIds: (r.fields['country_ids'] as string[] | undefined) ?? [],
  }),
  listFields: ['slug', 'h1', 'internal_label', 'is_published', 'division_ids', 'country_ids'],
  listSort: [{ field: 'slug', direction: 'asc' }],

  cacheEntities: CMS_PAGES_ENTITIES,

  slug: {
    accessor: (s) => s.slug,
    field: 'slug',
    validateFormat: true,
    checkReserved: true,
    checkDuplicate: true,
  },
};

export { loadCmsPageState };
