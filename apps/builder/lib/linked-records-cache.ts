/**
 * Delat client-side cache för records som ska visas i `LinkedRecords`-pickern
 * och i preview-komponenter som vill rendera linked records (t.ex.
 * `CasePreviewPanel` som visar valda produkter och artiklar, eller
 * `PartnerPreviewPanel` som visar partner-logo + success cases + kategorier).
 *
 * Varför separat modul? `LinkedRecords` är en client-komponent vars modulnivå-
 * cache är lokal — preview kan inte komma åt den. Genom att exponera en
 * fristående `fetchLinkedRecords(source)` får båda samma in-flight-promise
 * och samma cache utan att kopplas till varandra.
 *
 * Källor:
 *   - `core_*` (alla CoreEntityName från `lib/core/registry`).
 *   - CMS-tabeller via `CmsLinkedSourceName` i `lib/linked-sources.ts`
 *     (idag: products, articles, cases, product_areas).
 *
 * Endpoint: en enda generisk route `/api/linked/[source]` som dispatchar
 * mellan core-normaliseringen och CMS-passthrough på serversidan.
 */

import { useEffect, useMemo, useState } from 'react';
import type { LinkedSourceName, CmsLinkedSourceName } from './linked-sources';

/**
 * Bakåtkompatibla typalias som case-editor (och eventuella andra konsumenter
 * som importerade dessa namn direkt) använder. Föredra `LinkedSourceName`
 * och `NormalizedLinkedRecord` framöver — namnen är samma längs båda
 * importvägarna men `LinkedRecordSource` är kvar tills alla callers
 * städats.
 */
export type LinkedRecordSource = LinkedSourceName;
export type CmsLinkSource = CmsLinkedSourceName;

export interface NormalizedLinkedRecord {
  _recordId: string;
  [key: string]: unknown;
}

/** Behåller mitt ursprungliga internnamn som alias så preview-komponenter
 *  som importerade `NormalizedRecord` också kompilerar. */
export type NormalizedRecord = NormalizedLinkedRecord;

const cache = new Map<LinkedRecordSource, Promise<NormalizedLinkedRecord[]>>();

/**
 * Returnerar alla records för en linked-source. Cachas på modulnivå —
 * navigering mellan sidor i editorn triggar inte ny fetch.
 *
 * Vid fel rensas cachen så användaren kan retrya genom att navigera om.
 */
export function fetchLinkedRecords(
  source: LinkedRecordSource,
): Promise<NormalizedLinkedRecord[]> {
  const cached = cache.get(source);
  if (cached) return cached;
  const promise = fetch(`/api/linked/${source}`)
    .then(async (r) => {
      const data = await r.json();
      if (!r.ok || !data.success) {
        throw new Error(data.error || `HTTP ${r.status}`);
      }
      return (data.records ?? []) as NormalizedLinkedRecord[];
    })
    .catch((err) => {
      cache.delete(source);
      throw err;
    });
  cache.set(source, promise);
  return promise;
}

/**
 * Manuell cache-rensning — användbart om en muterande operation
 * (t.ex. en SSOT-edit) just har ändrat datan och vi vill att nästa
 * pickeropp ska hämta fresh.
 */
export function clearLinkedRecordsCache(source?: LinkedRecordSource): void {
  if (source) cache.delete(source);
  else cache.clear();
}

/**
 * Lågnivåshook: hämtar hela record-listan för en source och returnerar
 * en map (recordId → record). Tom map tills datat laddats. Vi lagrar
 * hela mappen i state istället för att kalla setState från unmount-
 * grenar — gör att eslint:s `react-hooks/set-state-in-effect` slipper
 * varna och låter `useLinkedRecord` / `useLinkedRecords` derivera sina
 * resultat synkront från `recordId` resp. `recordIds` props.
 */
function useLinkedRecordMap(
  source: LinkedRecordSource,
): Map<string, NormalizedLinkedRecord> {
  const [records, setRecords] = useState<NormalizedLinkedRecord[]>([]);

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
    const m = new Map<string, NormalizedLinkedRecord>();
    for (const r of records) m.set(r._recordId, r);
    return m;
  }, [records]);
}

/**
 * Slå upp ett specifikt linkat record. Returnerar `null` tills datat
 * laddats (eller om recordet inte hittas).
 */
export function useLinkedRecord(
  source: LinkedRecordSource,
  recordId: string | null | undefined,
): NormalizedLinkedRecord | null {
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
  source: LinkedRecordSource,
  recordIds: readonly string[],
): NormalizedLinkedRecord[] {
  const byId = useLinkedRecordMap(source);
  return useMemo(() => {
    return recordIds
      .map((id) => byId.get(id))
      .filter((r): r is NormalizedLinkedRecord => r !== undefined);
    // recordIds är en array — joinad sträng är stabil deps-jämförelse.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [byId, recordIds.join(',')]);
}
