/**
 * Server-side loader för SSOT-entities.
 *
 * Anropas från `app/globals/[entity]/page.tsx` för att hämta records.
 */

import { listRecords, SSOT_BASE_ID } from '../airtable';
import { CORE_ENTITIES, CoreEntityName } from './registry';
import { readEntityRecord } from './mapper';

export async function loadEntityRecords(
  entity: CoreEntityName,
  apiKey: string,
): Promise<Array<Record<string, unknown>>> {
  const def = CORE_ENTITIES[entity];
  const raw = await listRecords(apiKey, def.tableId, { baseId: SSOT_BASE_ID });
  return raw.map((r) => readEntityRecord(entity, r));
}

/**
 * Hämta records för alla SSOT-entiteter parallellt — används av landningssidan
 * `/globals` för att räkna records per entity.
 */
export async function loadEntityCounts(apiKey: string): Promise<Record<CoreEntityName, number>> {
  const entries = (Object.keys(CORE_ENTITIES) as CoreEntityName[]).map(async (name) => {
    try {
      const recs = await loadEntityRecords(name, apiKey);
      return [name, recs.length] as const;
    } catch {
      return [name, 0] as const;
    }
  });
  const results = await Promise.all(entries);
  return Object.fromEntries(results) as Record<CoreEntityName, number>;
}
