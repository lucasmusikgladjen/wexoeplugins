/**
 * Delad client-side cache för linkade-records-pickers OCH preview-uppslag.
 *
 * Både `Field.LinkedRecords` (multi-select-pickern) och preview-komponenter
 * som behöver hämta länkad metadata (t.ex. partner-logo från
 * core_partners-recordet) går genom samma cache så vi inte fetcher samma
 * lista två gånger per session.
 *
 * Endpoint: `/api/linked/[source]` — whitelist:ad via `lib/linked-sources.ts`.
 */

import { LinkedSourceName } from './linked-sources';

export interface NormalizedRecord {
  _recordId: string;
  [key: string]: unknown;
}

const cache = new Map<LinkedSourceName, Promise<NormalizedRecord[]>>();

/**
 * Returnerar alla records för en linked-source. Cachas på modulnivå —
 * navigering mellan sidor i editorn triggar inte ny fetch.
 *
 * Vid fel rensas cachen så användaren kan retrya genom att navigera om.
 */
export function fetchLinkedRecords(
  source: LinkedSourceName,
): Promise<NormalizedRecord[]> {
  const cached = cache.get(source);
  if (cached) return cached;
  const promise = fetch(`/api/linked/${source}`)
    .then(async (r) => {
      const data = await r.json();
      if (!r.ok || !data.success) {
        throw new Error(data.error || `HTTP ${r.status}`);
      }
      return (data.records ?? []) as NormalizedRecord[];
    })
    .catch((err) => {
      cache.delete(source);
      throw err;
    });
  cache.set(source, promise);
  return promise;
}

/**
 * Lågnivåshook: hämtar hela record-listan för en source och returnerar
 * en map (recordId → record). Tom map tills datat laddats. Vi lagrar
 * hela mappen i state istället för att kalla setState från unmount-
 * grenar — gör att eslint:s `react-hooks/set-state-in-effect` slipper
 * varna och låter `useLinkedRecord` / `useLinkedRecords` derivera sina
 * resultat synkront från `recordId` resp. `recordIds` props.
 */
import { useEffect, useMemo, useState } from 'react';

function useLinkedRecordMap(source: LinkedSourceName): Map<string, NormalizedRecord> {
  const [records, setRecords] = useState<NormalizedRecord[]>([]);

  useEffect(() => {
    let cancelled = false;
    fetchLinkedRecords(source)
      .then((data) => {
        if (!cancelled) setRecords(data);
      })
      .catch(() => {
        // Tyst — callern visar fallback / tom rendering.
      });
    return () => {
      cancelled = true;
    };
  }, [source]);

  return useMemo(() => {
    const m = new Map<string, NormalizedRecord>();
    for (const r of records) m.set(r._recordId, r);
    return m;
  }, [records]);
}

/**
 * Slå upp ett specifikt linkat record. Returnerar `null` tills datat
 * laddats (eller om recordet inte hittas).
 */
export function useLinkedRecord(
  source: LinkedSourceName,
  recordId: string | null | undefined,
): NormalizedRecord | null {
  const byId = useLinkedRecordMap(source);
  if (!recordId) return null;
  return byId.get(recordId) ?? null;
}

/**
 * Variant för flera record-IDs samtidigt (case-stack, kategori-grid, …).
 * Returnerar records i samma ordning som inputs — bortfiltrerar tysta
 * misses så längden kan vara mindre än inputs.
 */
export function useLinkedRecords(
  source: LinkedSourceName,
  recordIds: readonly string[],
): NormalizedRecord[] {
  const byId = useLinkedRecordMap(source);
  return useMemo(() => {
    return recordIds
      .map((id) => byId.get(id))
      .filter((r): r is NormalizedRecord => r !== undefined);
    // recordIds är en array — joinad sträng är stabil deps-jämförelse.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [byId, recordIds.join(',')]);
}
