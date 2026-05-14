'use client';

/**
 * Multi-select field för länkning till core-entiteter.
 *
 * Säkerhet/design:
 *   - `source` är en *whitelist*-key från `lib/core/registry` (CORE_ENTITIES).
 *     Komponenten exponerar INTE ett fritt tableId-API — Airtable-nyckeln
 *     stannar därför på servern.
 *   - Datat hämtas via `/api/core/<source>` som körs server-side.
 *   - Resultaten cachas på modulnivå per source så sessionsnavigation
 *     mellan editorer inte triggar ny fetch.
 *
 * Ergonomi:
 *   - Selected items visas som chips. Klicka ✕ för att avlänka.
 *   - Sökfält filtrerar dropdown:en. Klick på en option lägger till.
 *   - Visar laddnings- och felstate.
 *   - `max`-prop begränsar antalet samtidiga val (lämpligt för t.ex. ett
 *     ensamt land där 0–1 förväntas).
 */

import { useEffect, useId, useMemo, useRef, useState } from 'react';
import type { CoreEntityName } from '@/lib/core/registry';

interface NormalizedRecord {
  _recordId: string;
  [key: string]: unknown;
}

interface Props {
  label: string;
  source: CoreEntityName;
  value: string[];
  onChange: (next: string[]) => void;
  description?: string;
  /** Override default label-formattering (se `defaultLabel` nedan). */
  getLabel?: (record: NormalizedRecord) => string;
  /** Begränsar antalet samtidiga val. Default: obegränsat. */
  max?: number;
  /** Sortera dropdown-options. Default: label A→Z. */
  sort?: (a: NormalizedRecord, b: NormalizedRecord) => number;
  placeholder?: string;
  /** Filtrera vilka records som visas. T.ex. bara `is_active === true`. */
  filter?: (record: NormalizedRecord) => boolean;
}

// ─── Module-level cache ────────────────────────────────────────────────────

const cache = new Map<CoreEntityName, Promise<NormalizedRecord[]>>();

function fetchRecords(source: CoreEntityName): Promise<NormalizedRecord[]> {
  const cached = cache.get(source);
  if (cached) return cached;
  const promise = fetch(`/api/core/${source}`)
    .then(async (r) => {
      const data = await r.json();
      if (!r.ok || !data.success) {
        throw new Error(data.error || `HTTP ${r.status}`);
      }
      return (data.records ?? []) as NormalizedRecord[];
    })
    .catch((err) => {
      // Rensa cachen vid fel så användaren kan retrya genom att navigera om.
      cache.delete(source);
      throw err;
    });
  cache.set(source, promise);
  return promise;
}

// ─── Default labels per entity ─────────────────────────────────────────────

function defaultLabel(source: CoreEntityName, rec: NormalizedRecord): string {
  switch (source) {
    case 'core_countries':
      return `${rec.name as string} (${rec.code as string})`;
    case 'core_divisions':
    case 'core_customer_types':
    case 'core_partners':
      return (rec.name as string) || '';
    case 'core_coworkers':
      return (rec.full_name as string) || '';
    case 'core_testimonials':
      return (rec.internal_name as string) || '';
    case 'core_company':
      return (rec.company_name as string) || '';
    case 'core_graphic_profile':
      return (rec.slug as string) || '';
    default: {
      // Type-exhaustiveness — om CORE_ENTITIES utökas och vi glömmer
      // case:t här fångar TypeScript det vid compile.
      const _exhaustive: never = source;
      void _exhaustive;
      return (rec._recordId as string) || '';
    }
  }
}

// ─── Component ─────────────────────────────────────────────────────────────

export default function LinkedRecords({
  label,
  source,
  value,
  onChange,
  description,
  getLabel,
  max,
  sort,
  placeholder = 'Sök…',
  filter,
}: Props) {
  const inputId = useId();
  const [records, setRecords] = useState<NormalizedRecord[] | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [search, setSearch] = useState('');
  const [open, setOpen] = useState(false);
  const rootRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    let cancelled = false;
    fetchRecords(source)
      .then((data) => {
        if (!cancelled) setRecords(data);
      })
      .catch((err) => {
        if (!cancelled) setError(err instanceof Error ? err.message : 'Hämtning misslyckades.');
      });
    return () => {
      cancelled = true;
    };
  }, [source]);

  // Stäng dropdown vid klick utanför.
  useEffect(() => {
    if (!open) return;
    const handler = (e: MouseEvent) => {
      if (!rootRef.current) return;
      if (rootRef.current.contains(e.target as Node)) return;
      setOpen(false);
    };
    document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, [open]);

  const labelOf = (rec: NormalizedRecord) =>
    getLabel ? getLabel(rec) : defaultLabel(source, rec);

  const byId = useMemo(() => {
    const m = new Map<string, NormalizedRecord>();
    for (const r of records ?? []) m.set(r._recordId, r);
    return m;
  }, [records]);

  // Valda items i value-ordning så användaren ser samma sekvens som
  // arrayen i state.
  const selected = useMemo(
    () =>
      value
        .map((id) => byId.get(id))
        .filter((r): r is NormalizedRecord => r !== undefined),
    [value, byId],
  );

  // Visade options = (alla — valda — filtrerade) sorterade.
  const options = useMemo(() => {
    if (!records) return [];
    const valueSet = new Set(value);
    const q = search.trim().toLowerCase();
    let list = records.filter((r) => !valueSet.has(r._recordId));
    if (filter) list = list.filter(filter);
    if (q) list = list.filter((r) => labelOf(r).toLowerCase().includes(q));
    if (sort) {
      list = [...list].sort(sort);
    } else {
      list = [...list].sort((a, b) => labelOf(a).localeCompare(labelOf(b), 'sv'));
    }
    return list;
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [records, value, search, filter, sort, source, getLabel]);

  const canAddMore = max === undefined || value.length < max;

  const add = (id: string) => {
    if (!canAddMore) return;
    onChange([...value, id]);
    setSearch('');
  };
  const remove = (id: string) => {
    onChange(value.filter((x) => x !== id));
  };

  return (
    <label htmlFor={inputId} className="block">
      <span className="text-[11px] text-gray-400">{label}</span>
      <div
        ref={rootRef}
        className="mt-0.5 relative rounded bg-gray-100/80 focus-within:bg-white focus-within:ring-1 focus-within:ring-gray-200"
      >
        <div className="flex flex-wrap items-center gap-1 px-2 py-1.5 min-h-[36px]">
          {selected.map((rec) => (
            <span
              key={rec._recordId}
              className="inline-flex items-center gap-1 rounded bg-white px-1.5 py-0.5 text-xs text-gray-700 border border-gray-200"
            >
              {labelOf(rec)}
              <button
                type="button"
                onClick={(e) => {
                  e.preventDefault();
                  remove(rec._recordId);
                }}
                className="text-gray-300 hover:text-red-400 leading-none"
                aria-label={`Ta bort ${labelOf(rec)}`}
              >
                ×
              </button>
            </span>
          ))}

          {canAddMore && (
            <input
              id={inputId}
              type="text"
              value={search}
              onChange={(e) => {
                setSearch(e.target.value);
                setOpen(true);
              }}
              onFocus={() => setOpen(true)}
              placeholder={selected.length === 0 ? placeholder : ''}
              className="flex-1 min-w-[60px] bg-transparent text-sm text-gray-700 placeholder:text-gray-300 focus:outline-none"
            />
          )}
        </div>

        {open && records && options.length > 0 && (
          <div className="absolute top-full left-0 right-0 z-30 mt-1 max-h-56 overflow-y-auto rounded bg-white shadow-lg border border-gray-200">
            {options.map((rec) => (
              <button
                key={rec._recordId}
                type="button"
                onMouseDown={(e) => {
                  // mousedown så input:en inte tappar fokus innan onClick körts.
                  e.preventDefault();
                  add(rec._recordId);
                }}
                className="block w-full text-left px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-100"
              >
                {labelOf(rec)}
              </button>
            ))}
          </div>
        )}

        {open && records && options.length === 0 && search.trim() && (
          <div className="absolute top-full left-0 right-0 z-30 mt-1 rounded bg-white shadow-lg border border-gray-200 px-3 py-2 text-xs text-gray-400">
            Inga träffar.
          </div>
        )}
      </div>

      {records === null && !error && (
        <span className="block mt-0.5 text-[10px] text-gray-300">Laddar…</span>
      )}
      {error && (
        <span className="block mt-0.5 text-[10px] text-red-400">{error}</span>
      )}
      {description && !error && records !== null && (
        <span className="block mt-0.5 text-[10px] text-gray-400">{description}</span>
      )}
    </label>
  );
}
