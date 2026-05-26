/**
 * Delat client-side cache för records som ska visas i `LinkedRecords`-pickern
 * och i preview-komponenter som vill renderar linked records (t.ex.
 * `CasePreviewPanel` som visar valda produkter och artiklar).
 *
 * Varför separat modul? `LinkedRecords` är en client-komponent vars modulnivå-
 * cache är lokal — preview kan inte komma åt den. Genom att exponera en
 * fristående `fetchLinkedRecords(source)` får båda samma in-flight-promise
 * och samma cache utan att kopplas till varandra.
 *
 * Källor som stöds:
 *   - `core_*` (alla CoreEntityName från lib/core/registry) — hämtas från
 *     /api/core/<source>.
 *   - `products` / `articles` — hämtas från /api/cms-link/<source>.
 */

import type { CoreEntityName } from './core/registry';

export type CmsLinkSource = 'products' | 'articles';
export type LinkedRecordSource = CoreEntityName | CmsLinkSource;

export interface NormalizedLinkedRecord {
  _recordId: string;
  [key: string]: unknown;
}

function isCmsLinkSource(source: LinkedRecordSource): source is CmsLinkSource {
  return source === 'products' || source === 'articles';
}

function fetchUrl(source: LinkedRecordSource): string {
  return isCmsLinkSource(source) ? `/api/cms-link/${source}` : `/api/core/${source}`;
}

const cache = new Map<LinkedRecordSource, Promise<NormalizedLinkedRecord[]>>();

export function fetchLinkedRecords(
  source: LinkedRecordSource,
): Promise<NormalizedLinkedRecord[]> {
  const cached = cache.get(source);
  if (cached) return cached;
  const promise = fetch(fetchUrl(source))
    .then(async (r) => {
      const data = await r.json();
      if (!r.ok || !data.success) {
        throw new Error(data.error || `HTTP ${r.status}`);
      }
      return (data.records ?? []) as NormalizedLinkedRecord[];
    })
    .catch((err) => {
      // Rensa cachen vid fel så användaren kan retrya genom att navigera om.
      cache.delete(source);
      throw err;
    });
  cache.set(source, promise);
  return promise;
}

export function clearLinkedRecordsCache(source?: LinkedRecordSource): void {
  if (source) cache.delete(source);
  else cache.clear();
}
