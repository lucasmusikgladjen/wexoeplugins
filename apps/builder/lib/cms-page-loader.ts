/**
 * Multi-tabell-load för cms_pages.
 *
 * Hämtar:
 *   1. Page-record (cms_pages)
 *   2. Sektioner (cms_page_sections) i den ordning som page.section_ids anger
 *   3. För varje tabs-sektion: dess child-records (cms_section_tabs) i ordningen
 *      som section.tabs_tab_ids anger
 *
 * Alla läsningar går mot SSOT-basen.
 */

import { getRecord, listRecords, AirtableRecord, SSOT_BASE_ID } from './airtable';
import {
  CmsPageState,
  CMS_PAGES_TABLE_ID,
  CMS_PAGE_SECTIONS_TABLE_ID,
  CMS_SECTION_TABS_TABLE_ID,
} from './cms-page-types';
import { cmsPageStateFromRecords } from './cms-page-mapper';

export async function loadCmsPageState(
  apiKey: string,
  recordId: string,
): Promise<CmsPageState> {
  const page = await getRecord(apiKey, CMS_PAGES_TABLE_ID, recordId, SSOT_BASE_ID);

  const sectionIds = (page.fields['section_ids'] as string[] | undefined) ?? [];

  let sections: AirtableRecord[] = [];
  if (sectionIds.length > 0) {
    const formula = `OR(${sectionIds.map((id) => `RECORD_ID()='${id}'`).join(',')})`;
    sections = await listRecords(apiKey, CMS_PAGE_SECTIONS_TABLE_ID, {
      filterByFormula: formula,
      baseId: SSOT_BASE_ID,
    });
  }

  // Bevara link-ordning från page.section_ids (Airtable returnerar
  // listRecords i intern ordning, inte filterByFormula-ordning).
  const sectionById = new Map(sections.map((r) => [r.id, r]));
  const orderedSections = sectionIds
    .map((id) => sectionById.get(id))
    .filter((r): r is AirtableRecord => r !== undefined);

  // Samla alla tabs_tab_ids från tabs-sektioner och hämta dem i en sweep.
  const allTabIds: string[] = [];
  const tabIdsBySection = new Map<string, string[]>();
  for (const sec of orderedSections) {
    if (sec.fields['section_type'] !== 'tabs') continue;
    const ids = (sec.fields['tabs_tab_ids'] as string[] | undefined) ?? [];
    if (ids.length === 0) continue;
    tabIdsBySection.set(sec.id, ids);
    allTabIds.push(...ids);
  }

  let tabRecords: AirtableRecord[] = [];
  if (allTabIds.length > 0) {
    const formula = `OR(${allTabIds.map((id) => `RECORD_ID()='${id}'`).join(',')})`;
    tabRecords = await listRecords(apiKey, CMS_SECTION_TABS_TABLE_ID, {
      filterByFormula: formula,
      baseId: SSOT_BASE_ID,
    });
  }

  // Bygg map från parent-section-record-ID → tab-records i ordning.
  const tabById = new Map(tabRecords.map((r) => [r.id, r]));
  const tabsByParent = new Map<string, AirtableRecord[]>();
  for (const [parentId, tabIds] of tabIdsBySection.entries()) {
    const ordered = tabIds
      .map((id) => tabById.get(id))
      .filter((r): r is AirtableRecord => r !== undefined);
    tabsByParent.set(parentId, ordered);
  }

  return cmsPageStateFromRecords({
    page,
    orderedSections,
    tabsByParent,
  });
}
