/**
 * Linkade-records-källor — registry för `Field.LinkedRecords`-pickern.
 *
 * En "källa" är en Airtable-tabell som kan visas i en multi-select-picker.
 * Innan denna fil hade vi bara core_*-källor via `lib/core/registry.ts` +
 * `/api/core/[entity]`. Den här filen utvidgar mängden så CMS-tabeller
 * (cms_cases, cms_product_pages) också kan väljas — utan att blanda in
 * SSOT-editor-logiken (which is what CORE_ENTITIES is for).
 *
 * Filosofi:
 *   - core_*-källor delegeras till befintlig `/api/core/[entity]`-route så
 *     normaliseringen i `lib/core/mapper.ts` återanvänds (det är samma data
 *     som SSOT-editorn ser).
 *   - cms_*-källor läses direkt mot Airtable via en ny generisk route
 *     `/api/linked/[source]` som returnerar `{ _recordId, ...fields }`.
 *
 * När en sidtyp behöver en ny picker-källa: lägg till entry här (eller
 * skapa en ny `kind`-gren om datat ligger någon annanstans). Editorn
 * importerar bara `LinkedSourceName`-strängen — ingen sidtyp tar i routes
 * eller table-IDs direkt.
 */

import { CORE_ENTITIES, type CoreEntityName } from './core/registry';

/** CMS-tabeller som finns som linkbara källor (utöver core_*-mängden). */
export const CMS_LINKED_SOURCES = {
  cases: {
    /** cms_cases — kundcase-stories. */
    tableId: 'tblxH3ECSMvDTYrIQ',
    /** Fält att hämta från Airtable. Håll listan minimal för snabba pickers. */
    fields: [
      'slug',
      'title',
      'subtitle',
      'customer_name',
      'industry',
      'lead_image_url',
      'is_active',
    ] as const,
  },
  product_areas: {
    /** cms_product_pages — produktområdessidor. */
    tableId: 'tbl5PQR7FNHCogeya',
    fields: [
      'slug',
      'name',
      'h1',
      'card_image_url',
      'card_description',
      'is_active',
    ] as const,
  },
} as const;

export type CmsLinkedSourceName = keyof typeof CMS_LINKED_SOURCES;
export type LinkedSourceName = CoreEntityName | CmsLinkedSourceName;

export function isCmsLinkedSource(name: string): name is CmsLinkedSourceName {
  return name in CMS_LINKED_SOURCES;
}

export function isCoreLinkedSource(name: string): name is CoreEntityName {
  return name in CORE_ENTITIES;
}

export function isLinkedSourceName(name: string): name is LinkedSourceName {
  return isCmsLinkedSource(name) || isCoreLinkedSource(name);
}
