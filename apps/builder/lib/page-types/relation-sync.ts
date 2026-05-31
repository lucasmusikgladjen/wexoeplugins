/**
 * Lager 2 — Relation-synkningsmotorn.
 *
 * Diff:ar state mot Airtable för en eller flera `RelationDef` och utför
 * create/update/delete/unlink per item. Returnerar `RelationSyncResult`
 * per relation — bl.a. `created[]` med clientId→recordId-mappning så UI
 * kan uppdatera state efter create utan reload.
 *
 * Ansvarsfördelning gentemot route-factory:
 *   - Den här filen synkar BARA child-records (skapar/uppdaterar/raderar
 *     records i relation.tableId).
 *   - Route-factory:n är ansvarig för parent-record:en, inkl. att skriva
 *     parent.parentField-arrayen efter att sync är klar. Vi exponerar
 *     `computeFinalParentArrays()` som returnerar de slutgiltiga
 *     ID-arrayerna route-factory ska skriva.
 *   - Anledningen: parent-tabellen finns inte i RelationDef — den ligger
 *     i PageTypeServerDef. Route-factory har båda, sync-motorn bara den
 *     ena.
 *
 * Failure-semantik:
 *   - Airtable saknar transaktioner; vi lovar ingen rollback.
 *   - Fel per item samlas i `RelationSyncResult.errors` och övriga items
 *     fortsätter köras.
 *   - Om `onStaleId: 'error'` och stale-IDs hittas: vi vägrar skriva
 *     någonting för relationen och returnerar bara errors.
 *
 * Se `lib/page-types/types.ts` för detaljerad doc om semantik och fält.
 */

import {
  AirtableRecord,
  createRecord,
  updateRecord,
  updateRecords,
  deleteRecords,
  listRecords,
} from '../airtable';
import type { RelationDef, RelationSyncResult } from './types';

// ─── Public API ────────────────────────────────────────────────────────────

export async function syncRelations<TState>(
  apiKey: string,
  parentRecordId: string,
  parentRecord: AirtableRecord,
  state: TState,
  relations: ReadonlyArray<RelationDef<TState, unknown>>,
): Promise<Record<string, RelationSyncResult>> {
  const out: Record<string, RelationSyncResult> = {};
  for (const relation of relations) {
    out[relation.id] = await syncSingleRelation(
      apiKey,
      parentRecordId,
      parentRecord,
      state,
      relation,
    );
  }
  return out;
}

/**
 * Tas explicit av DELETE: tar bort owned child-records innan parent
 * raderas. Shared- och ignore-relationer rörs inte.
 *
 * `parentRecord` krävs för parentLinkArray-relationer eftersom child-IDs
 * läses från parent.parentField. För childBacklink-relationer hämtas
 * children direkt via filterByFormula.
 */
export async function deleteRelationChildren<TState>(
  apiKey: string,
  parentRecordId: string,
  parentRecord: AirtableRecord,
  relations: ReadonlyArray<RelationDef<TState, unknown>>,
): Promise<void> {
  for (const relation of relations) {
    const ownership = relation.ownership ?? 'owned';
    if (ownership !== 'owned') continue;
    const existing = await fetchExistingChildIds(
      apiKey,
      parentRecordId,
      parentRecord,
      relation,
    );
    if (existing.length > 0) {
      await deleteRecords(apiKey, relation.tableId, existing, relation.baseId);
    }
  }
}

/**
 * Beräknar slutgiltiga ID-arrayer per `parentLinkArray`-relation som
 * route-factory ska skriva på parent-record:en efter sync. Anropas EFTER
 * `syncRelations()` med dess resultat.
 *
 * Returnerar `{ [parentField]: string[] }` — bara fält som faktiskt
 * ändrats jämfört med parent-record:en behöver inte filtreras här,
 * route-factory:n kan jämföra mot parent.fields[parentField] om den vill.
 */
export function computeFinalParentArrays<TState>(
  state: TState,
  relations: ReadonlyArray<RelationDef<TState, unknown>>,
  syncResults: Record<string, RelationSyncResult>,
): Record<string, string[]> {
  const out: Record<string, string[]> = {};
  for (const relation of relations) {
    if (relation.kind !== 'parentLinkArray') continue;
    const items = relation.select(state);
    const result = syncResults[relation.id];
    if (!result) continue;

    const createdByClientId = new Map(result.created.map((c) => [c.clientId, c.recordId]));
    const failedClientIds = new Set(
      result.errors.filter((e) => e.clientId).map((e) => e.clientId as string),
    );

    let ids: string[] = [];
    for (const item of items) {
      const existing = relation.identity(item);
      if (existing !== null) {
        // Items vars update failade ligger fortfarande kvar i Airtable —
        // ta med dem i arrayen så vi inte unlinkar oavsiktligt.
        ids.push(existing);
        continue;
      }
      const clientId = relation.clientIdentity(item);
      if (failedClientIds.has(clientId)) continue;
      const created = createdByClientId.get(clientId);
      if (created) ids.push(created);
    }

    const ordered = relation.ordered ?? true;
    if (!ordered) ids = [...ids].sort();
    out[relation.parentField] = ids;
  }
  return out;
}

// ─── Per-relation sync ─────────────────────────────────────────────────────

async function syncSingleRelation<TState, TItem>(
  apiKey: string,
  parentRecordId: string,
  parentRecord: AirtableRecord,
  state: TState,
  relation: RelationDef<TState, TItem>,
): Promise<RelationSyncResult> {
  const result: RelationSyncResult = {
    created: [],
    updated: [],
    deleted: [],
    unlinked: [],
    errors: [],
  };

  const stateItems = relation.select(state);
  const existingIds = await fetchExistingChildIds(
    apiKey,
    parentRecordId,
    parentRecord,
    relation,
  );

  // ─── Diff ──────────────────────────────────────────────────────────────
  const stateKeptIds = new Set<string>();
  const staleItems: TItem[] = [];
  type Plan =
    | { item: TItem; index: number; kind: 'create' }
    | { item: TItem; index: number; kind: 'update'; recordId: string };
  const plans: Plan[] = [];

  for (let i = 0; i < stateItems.length; i++) {
    const item = stateItems[i];
    const id = relation.identity(item);
    if (id === null) {
      plans.push({ item, index: i, kind: 'create' });
    } else if (existingIds.includes(id)) {
      stateKeptIds.add(id);
      plans.push({ item, index: i, kind: 'update', recordId: id });
    } else {
      staleItems.push(item);
    }
  }

  // ─── Stale-IDs ────────────────────────────────────────────────────────
  const onStaleId = relation.onStaleId ?? 'error';
  if (staleItems.length > 0) {
    if (onStaleId === 'error') {
      for (const item of staleItems) {
        const id = relation.identity(item);
        result.errors.push({
          recordId: id ?? undefined,
          message: `Record ${id} finns i state men inte i Airtable. onStaleId='error' (default) — refuserar att skriva relation '${relation.id}' för att inte korrumpera data.`,
        });
      }
      return result;
    }
    if (onStaleId === 'create') {
      for (const item of staleItems) {
        plans.push({
          item,
          index: plans.length,
          kind: 'create',
        });
      }
    }
    // 'skip' — gör inget
  }

  const missingIds = existingIds.filter((id) => !stateKeptIds.has(id));
  const onMissing =
    relation.onMissing
    ?? ((relation.ownership ?? 'owned') === 'owned' ? 'delete' : 'unlink');

  // ─── Create ────────────────────────────────────────────────────────────
  for (const plan of plans) {
    if (plan.kind !== 'create') continue;
    const clientId = relation.clientIdentity(plan.item);
    try {
      const baseFields = relation.toFields(plan.item, {
        parentRecordId,
        index: plan.index,
      });
      // childBacklink: sätt parent-länken redan vid create.
      // parentLinkArray: child:en behöver inte veta om parent — parent's
      //                  parentField sätts av route-factory efteråt.
      const fields =
        relation.kind === 'childBacklink'
          ? { ...baseFields, [relation.childField]: [parentRecordId] }
          : baseFields;
      const created = await createRecord(
        apiKey,
        relation.tableId,
        fields,
        relation.baseId,
      );
      result.created.push({ clientId, recordId: created.id });
    } catch (err) {
      result.errors.push({
        clientId,
        message: err instanceof Error ? err.message : 'Create misslyckades.',
      });
    }
  }

  // ─── Update ────────────────────────────────────────────────────────────
  for (const plan of plans) {
    if (plan.kind !== 'update') continue;
    try {
      const fields = relation.toFields(plan.item, {
        parentRecordId,
        index: plan.index,
      });
      await updateRecord(
        apiKey,
        relation.tableId,
        plan.recordId,
        fields,
        relation.baseId,
      );
      result.updated.push(plan.recordId);
    } catch (err) {
      result.errors.push({
        recordId: plan.recordId,
        message: err instanceof Error ? err.message : 'Update misslyckades.',
      });
    }
  }

  // ─── Missing items (delete / unlink / ignore) ──────────────────────────
  if (missingIds.length > 0 && onMissing !== 'ignore') {
    if (onMissing === 'delete') {
      try {
        await deleteRecords(apiKey, relation.tableId, missingIds, relation.baseId);
        result.deleted.push(...missingIds);
      } catch (err) {
        for (const id of missingIds) {
          result.errors.push({
            recordId: id,
            message: err instanceof Error ? err.message : 'Delete misslyckades.',
          });
        }
      }
    } else if (onMissing === 'unlink') {
      if (relation.kind === 'parentLinkArray') {
        // Avlänkning sker via computeFinalParentArrays() — child-record:en
        // får ligga kvar, parent's parentField uppdateras av route-factory.
        result.unlinked.push(...missingIds);
      } else {
        // childBacklink: ta bort parent-länken från children:s childField.
        try {
          const fetched = await listRecords(apiKey, relation.tableId, {
            baseId: relation.baseId,
            filterByFormula:
              `OR(${missingIds.map((id) => `RECORD_ID()="${id}"`).join(',')})`,
          });
          const updates = fetched.map((r) => {
            const current = (r.fields[relation.childField] as string[] | undefined) ?? [];
            return {
              id: r.id,
              fields: {
                [relation.childField]: current.filter((x) => x !== parentRecordId),
              },
            };
          });
          if (updates.length > 0) {
            await updateRecords(apiKey, relation.tableId, updates, relation.baseId);
          }
          result.unlinked.push(...missingIds);
        } catch (err) {
          for (const id of missingIds) {
            result.errors.push({
              recordId: id,
              message: err instanceof Error ? err.message : 'Unlink misslyckades.',
            });
          }
        }
      }
    }
  }

  return result;
}

// ─── Helpers ───────────────────────────────────────────────────────────────

async function fetchExistingChildIds<TState, TItem>(
  apiKey: string,
  parentRecordId: string,
  parentRecord: AirtableRecord,
  relation: RelationDef<TState, TItem>,
): Promise<string[]> {
  if (relation.kind === 'parentLinkArray') {
    return (parentRecord.fields[relation.parentField] as string[] | undefined) ?? [];
  }
  // childBacklink — filtrera child-tabellen på childField som innehåller parent.
  const records = await listRecords(apiKey, relation.tableId, {
    baseId: relation.baseId,
    filterByFormula: `FIND("${parentRecordId}", ARRAYJOIN({${relation.childField}}))`,
  });
  return records.map((r) => r.id);
}
