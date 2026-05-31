/**
 * CMS Page — Lager 3 create/update-overrides.
 *
 * cms_pages är multi-tabell: sidans metadata (cms_pages) + polymorfa sektioner
 * (cms_page_sections) + tabs-sektionens sub-records (cms_section_tabs). Vi
 * transformerar state till Airtable-fält per nivå med en deterministisk
 * funktion (`buildCmsPageTransform`, inga Claude-anrop) och orkestrerar
 * create/update/delete för alla tre tabellerna här.
 *
 * Sektioner och tab-sub-records är OWNED av sidan — borttagna från state ⇒
 * raderas i Airtable (samma policy som PA:s product_page_sections och
 * LP:s lp_downloads).
 *
 * Skriv-ordning vid CREATE:
 *   1. Skapa alla section_tabs (innan sektionerna så vi har deras IDs)
 *   2. Skapa alla cms_page_sections (sätt tabs_tab_ids för tabs-sektioner)
 *   3. Skapa cms_pages-record med section_ids
 *
 * Skriv-ordning vid UPDATE:
 *   1. Diff sektioner mot existing section_ids → create new + patch existing
 *      + samla orphans
 *   2. För varje tabs-sektion: diff tabs mot dess existing tabs_tab_ids →
 *      create new + patch existing + samla orphans
 *   3. Patch tabs-sektioner med slutgiltig tabs_tab_ids
 *   4. Patch cms_pages med transform-output + slutgiltig section_ids
 *   5. Radera orphan section_tabs och orphan sections
 */

import {
  createRecord,
  updateRecord,
  deleteRecords,
  getRecord,
  listRecords,
  SSOT_BASE_ID,
} from '../airtable';
import {
  CMS_PAGES_TABLE_ID,
  CMS_PAGE_SECTIONS_TABLE_ID,
  CMS_SECTION_TABS_TABLE_ID,
  CmsPageState,
  TabsSection,
} from '../cms-page-types';
import type {
  CmsPageTransformResult,
  CmsPageTransformTab,
} from '../transform-shared';
import { buildCmsPageTransform } from '../deterministic-transform';
import type { RelationSyncResult } from './types';

type Result = {
  recordId: string;
  relations: Record<string, RelationSyncResult>;
};

function emptyRelationResult(): RelationSyncResult {
  return { created: [], updated: [], deleted: [], unlinked: [], errors: [] };
}

/** Group transform.sectionTabs by parent section's _clientIndex. */
function groupTabsBySection(
  tabs: CmsPageTransformTab[],
): Map<number, CmsPageTransformTab[]> {
  const m = new Map<number, CmsPageTransformTab[]>();
  for (const t of tabs) {
    if (!m.has(t._sectionClientIndex)) m.set(t._sectionClientIndex, []);
    m.get(t._sectionClientIndex)!.push(t);
  }
  // Sortera per sektion på _clientIndex så ordningen bevaras.
  for (const arr of m.values()) {
    arr.sort((a, b) => a._clientIndex - b._clientIndex);
  }
  return m;
}

// ─── Create ────────────────────────────────────────────────────────────────

export async function cmsPageCreate(
  state: CmsPageState,
  ctx: { apiKey: string },
): Promise<Result> {
  const airtableKey = ctx.apiKey;

  // FAS 2: deterministisk transform — inga Anthropic-anrop.
  const transformed: CmsPageTransformResult = buildCmsPageTransform(state, 'create');

  const sectionsResult = emptyRelationResult();
  const tabsResult = emptyRelationResult();

  // Steg 1: Skapa alla section_tabs (innan sektionerna så vi har deras IDs).
  // Map: parent-section-clientIndex → [tab-record-ID i ordning]
  const tabsBySection = groupTabsBySection(transformed.sectionTabs);
  const tabIdsBySectionIndex = new Map<number, string[]>();
  for (const [sectionIndex, tabs] of tabsBySection.entries()) {
    const tabIds: string[] = [];
    for (const t of tabs) {
      const created = await createRecord(
        airtableKey,
        CMS_SECTION_TABS_TABLE_ID,
        t.fields,
        SSOT_BASE_ID,
      );
      tabIds.push(created.id);
      const parentSection = state.sections[sectionIndex];
      if (parentSection && parentSection.type === 'tabs') {
        const tabsSection = parentSection as TabsSection;
        const stateTab = tabsSection.tabs[t._clientIndex];
        if (stateTab) {
          tabsResult.created.push({ clientId: stateTab.clientId, recordId: created.id });
        }
      }
    }
    tabIdsBySectionIndex.set(sectionIndex, tabIds);
  }

  // Steg 2: Skapa cms_page_sections, sätt tabs_tab_ids för tabs-sektioner.
  // Sortera efter _clientIndex så vi behåller UI-ordningen.
  const sortedSections = [...transformed.sections].sort((a, b) => a._clientIndex - b._clientIndex);
  const sectionIdByClientIndex = new Map<number, string>();
  for (const s of sortedSections) {
    const fields: Record<string, unknown> = { ...s.fields };
    const linkedTabIds = tabIdsBySectionIndex.get(s._clientIndex);
    if (linkedTabIds && linkedTabIds.length > 0) {
      fields['tabs_tab_ids'] = linkedTabIds;
    }
    const created = await createRecord(
      airtableKey,
      CMS_PAGE_SECTIONS_TABLE_ID,
      fields,
      SSOT_BASE_ID,
    );
    sectionIdByClientIndex.set(s._clientIndex, created.id);
    const stateSection = state.sections[s._clientIndex];
    if (stateSection) {
      sectionsResult.created.push({ clientId: stateSection.clientId, recordId: created.id });
    }
  }

  // Steg 3: Skapa cms_pages med section_ids i UI-ordning.
  const sectionIdsOrdered = state.sections
    .map((_, i) => sectionIdByClientIndex.get(i))
    .filter((id): id is string => !!id);

  const pageFields: Record<string, unknown> = {
    ...transformed.page,
    section_ids: sectionIdsOrdered,
  };

  const createdPage = await createRecord(
    airtableKey,
    CMS_PAGES_TABLE_ID,
    pageFields,
    SSOT_BASE_ID,
  );

  return {
    recordId: createdPage.id,
    relations: {
      sections: sectionsResult,
      sectionTabs: tabsResult,
    },
  };
}

// ─── Update ────────────────────────────────────────────────────────────────

export async function cmsPageUpdate(
  recordId: string,
  state: CmsPageState,
  ctx: { apiKey: string },
): Promise<{ relations: Record<string, RelationSyncResult> }> {
  const airtableKey = ctx.apiKey;

  // 0) Läs existerande page (för section_ids) + existerande sektioner
  //    (för deras tabs_tab_ids).
  const existingPage = await getRecord(
    airtableKey,
    CMS_PAGES_TABLE_ID,
    recordId,
    SSOT_BASE_ID,
  );
  const existingSectionIds: string[] =
    (existingPage.fields['section_ids'] as string[] | undefined) ?? [];
  const existingSectionIdSet = new Set(existingSectionIds);

  // Läs sektioner för att få deras tabs_tab_ids (behövs för diffen vid tabs).
  let existingSections: { id: string; tabsTabIds: string[] }[] = [];
  if (existingSectionIds.length > 0) {
    const formula = `OR(${existingSectionIds.map((id) => `RECORD_ID()='${id}'`).join(',')})`;
    const records = await listRecords(airtableKey, CMS_PAGE_SECTIONS_TABLE_ID, {
      filterByFormula: formula,
      baseId: SSOT_BASE_ID,
      fields: ['tabs_tab_ids', 'section_type'],
    });
    existingSections = records.map((r) => ({
      id: r.id,
      tabsTabIds: (r.fields['tabs_tab_ids'] as string[] | undefined) ?? [],
    }));
  }
  const existingTabIdsBySection = new Map(
    existingSections.map((s) => [s.id, s.tabsTabIds]),
  );

  // FAS 2: deterministisk transform — inga Anthropic-anrop.
  const transformed: CmsPageTransformResult = buildCmsPageTransform(state, 'update');

  const sectionsResult = emptyRelationResult();
  const tabsResult = emptyRelationResult();

  // 1) Diff sectionTabs PER PARENT-SEKTION.
  //    Vi måste veta vilka tabs-records hör till vilken parent-sektion.
  //    Map: state-section-clientIndex → state-section-recordId (om existing)
  //
  //    Iterera över transformed.sections för att hitta de som är tabs-typer
  //    OCH _recordId är truthy (existerande sektion).
  const tabsTransformBySection = groupTabsBySection(transformed.sectionTabs);

  // Map: parent-section-clientIndex → [tab-record-ID i ordning] (slutgiltigt)
  const finalTabIdsBySectionIndex = new Map<number, string[]>();

  for (const [sectionClientIndex, tabsInSection] of tabsTransformBySection.entries()) {
    const parentTransformedSection = transformed.sections.find(
      (s) => s._clientIndex === sectionClientIndex,
    );
    if (!parentTransformedSection) continue;
    const parentRecordId = parentTransformedSection._recordId;
    const existingTabIds = parentRecordId
      ? new Set(existingTabIdsBySection.get(parentRecordId) ?? [])
      : new Set<string>();

    const finalTabIds: string[] = [];
    for (const t of tabsInSection) {
      if (t._recordId && existingTabIds.has(t._recordId)) {
        // PATCH
        await updateRecord(
          airtableKey,
          CMS_SECTION_TABS_TABLE_ID,
          t._recordId,
          t.fields,
          SSOT_BASE_ID,
        );
        finalTabIds.push(t._recordId);
        tabsResult.updated.push(t._recordId);
      } else {
        // CREATE
        const created = await createRecord(
          airtableKey,
          CMS_SECTION_TABS_TABLE_ID,
          t.fields,
          SSOT_BASE_ID,
        );
        finalTabIds.push(created.id);
        const stateSection = state.sections[sectionClientIndex];
        if (stateSection && stateSection.type === 'tabs') {
          const stateTab = stateSection.tabs[t._clientIndex];
          if (stateTab) {
            tabsResult.created.push({ clientId: stateTab.clientId, recordId: created.id });
          }
        }
      }
    }
    finalTabIdsBySectionIndex.set(sectionClientIndex, finalTabIds);
  }

  // 2) Diff sektioner mot existing section_ids.
  //    CREATE där _recordId saknas eller pekar på orphan; PATCH där matchar.
  //    Samla nya recordIds för att bygga final section_ids-array.
  const finalSectionIdByClientIndex = new Map<number, string>();

  // Sortera så att hela CREATE-batchen körs i UI-ordning (vi tar inget batchAPI;
  // hellre deterministisk loop).
  const sortedSections = [...transformed.sections].sort((a, b) => a._clientIndex - b._clientIndex);

  for (const s of sortedSections) {
    const linkedTabIds = finalTabIdsBySectionIndex.get(s._clientIndex);
    const fields: Record<string, unknown> = { ...s.fields };
    // För tabs-sektioner: skriv aktuell tabs_tab_ids (även tom array
    // för att rensa bort).
    if (linkedTabIds !== undefined) {
      fields['tabs_tab_ids'] = linkedTabIds;
    } else {
      // Sektion är inte tabs eller har inga tabs → rensa tabs_tab_ids om den
      // tidigare hade några (annars no-op).
      // Vi sätter den till [] bara för existing tabs-sektioner som tömts;
      // för andra typer låter vi vara (Airtable bevarar oseparerat fält).
      // Hjälper Airtable hålla tabs_tab_ids ren när användaren tar bort alla tabs.
      if (s._recordId && existingTabIdsBySection.has(s._recordId)) {
        const had = existingTabIdsBySection.get(s._recordId) ?? [];
        if (had.length > 0) {
          fields['tabs_tab_ids'] = [];
        }
      }
    }

    if (s._recordId && existingSectionIdSet.has(s._recordId)) {
      await updateRecord(
        airtableKey,
        CMS_PAGE_SECTIONS_TABLE_ID,
        s._recordId,
        fields,
        SSOT_BASE_ID,
      );
      finalSectionIdByClientIndex.set(s._clientIndex, s._recordId);
      sectionsResult.updated.push(s._recordId);
    } else {
      const created = await createRecord(
        airtableKey,
        CMS_PAGE_SECTIONS_TABLE_ID,
        fields,
        SSOT_BASE_ID,
      );
      finalSectionIdByClientIndex.set(s._clientIndex, created.id);
      const stateSection = state.sections[s._clientIndex];
      if (stateSection) {
        sectionsResult.created.push({ clientId: stateSection.clientId, recordId: created.id });
      }
    }
  }

  // 3) Patch cms_pages med transform-output + slutgiltig section_ids-array (UI-ordning).
  const finalSectionIdsOrdered = state.sections
    .map((_, i) => finalSectionIdByClientIndex.get(i))
    .filter((id): id is string => !!id);

  await updateRecord(
    airtableKey,
    CMS_PAGES_TABLE_ID,
    recordId,
    {
      ...transformed.page,
      section_ids: finalSectionIdsOrdered,
    },
    SSOT_BASE_ID,
  );

  // 4) Radera orphan sektioner (existerade i Airtable men inte längre i state).
  const finalSectionIdSet = new Set(finalSectionIdsOrdered);
  const orphanSectionIds = existingSectionIds.filter((id) => !finalSectionIdSet.has(id));
  if (orphanSectionIds.length > 0) {
    await deleteRecords(
      airtableKey,
      CMS_PAGE_SECTIONS_TABLE_ID,
      orphanSectionIds,
      SSOT_BASE_ID,
    );
    for (const id of orphanSectionIds) sectionsResult.deleted.push(id);
  }

  // 5) Radera orphan tabs.
  //    Vi har existerande tabs-IDs per parent-sektion (existingTabIdsBySection).
  //    De som inte finns i finalTabIdsBySectionIndex för motsvarande sektion =
  //    orphans. Plus: ALLA tabs som tillhörde en raderad parent-sektion är orphans.
  const allFinalTabIds = new Set<string>();
  for (const ids of finalTabIdsBySectionIndex.values()) {
    for (const id of ids) allFinalTabIds.add(id);
  }
  const orphanTabIds: string[] = [];
  for (const [, ids] of existingTabIdsBySection.entries()) {
    for (const id of ids) {
      if (!allFinalTabIds.has(id)) orphanTabIds.push(id);
    }
  }
  if (orphanTabIds.length > 0) {
    await deleteRecords(
      airtableKey,
      CMS_SECTION_TABS_TABLE_ID,
      orphanTabIds,
      SSOT_BASE_ID,
    );
    for (const id of orphanTabIds) tabsResult.deleted.push(id);
  }

  return {
    relations: {
      sections: sectionsResult,
      sectionTabs: tabsResult,
    },
  };
}

// ─── List mapper ──────────────────────────────────────────────────────────

export interface CmsPageListItem {
  id: string;
  slug: string;
  h1: string;
  internalLabel: string;
  isPublished: boolean;
  divisionIds: string[];
  countryIds: string[];
}

// ─── Delete (cascade) ──────────────────────────────────────────────────────

/**
 * Multi-tabell cascade delete: page → sektioner → tabs.
 *
 * Lager 3 deklarerar inga `relations[]`, så factory:ns `defaultDelete`
 * skulle bara radera cms_pages-recorden och lämna sektion- och
 * tab-records orphana. Vi äger orkestreringen själva.
 *
 * Ordning: tabs först (djupast nästade), sen sektioner, sen page. Om en
 * radering misslyckas avbryter felet kedjan — Airtable saknar transaktioner
 * så orphans kan finnas kvar; användaren kan retrya delete (det är
 * idempotent: redan-borttagna records ger inget fel via batch-DELETE).
 */
export async function cmsPageDelete(
  recordId: string,
  ctx: { apiKey: string },
): Promise<void> {
  const apiKey = ctx.apiKey;

  // 1) Läs page för section_ids.
  const page = await getRecord(apiKey, CMS_PAGES_TABLE_ID, recordId, SSOT_BASE_ID);
  const sectionIds: string[] =
    (page.fields['section_ids'] as string[] | undefined) ?? [];

  // 2) Läs sektioner för deras tabs_tab_ids.
  const allTabIds: string[] = [];
  if (sectionIds.length > 0) {
    const formula = `OR(${sectionIds.map((id) => `RECORD_ID()='${id}'`).join(',')})`;
    const sections = await listRecords(apiKey, CMS_PAGE_SECTIONS_TABLE_ID, {
      filterByFormula: formula,
      baseId: SSOT_BASE_ID,
      fields: ['tabs_tab_ids'],
    });
    for (const sec of sections) {
      const ids = (sec.fields['tabs_tab_ids'] as string[] | undefined) ?? [];
      allTabIds.push(...ids);
    }
  }

  // 3) Radera tabs först (djupast nästade).
  if (allTabIds.length > 0) {
    await deleteRecords(apiKey, CMS_SECTION_TABS_TABLE_ID, allTabIds, SSOT_BASE_ID);
  }

  // 4) Radera sektioner.
  if (sectionIds.length > 0) {
    await deleteRecords(apiKey, CMS_PAGE_SECTIONS_TABLE_ID, sectionIds, SSOT_BASE_ID);
  }

  // 5) Radera page-recorden.
  await deleteRecords(apiKey, CMS_PAGES_TABLE_ID, [recordId], SSOT_BASE_ID);
}
