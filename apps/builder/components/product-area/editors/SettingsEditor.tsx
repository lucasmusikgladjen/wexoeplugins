'use client';

import { useEffect, useState } from 'react';
import { ProductAreaState, Division } from '@/lib/product-area-types';
import { Field } from '@/components/shared/fields';
import type { SectionEditorProps } from '@/lib/page-types/types';

// Modul-cache så navigering mellan sektioner inte triggar ny fetch.
let divisionsCache: Promise<Division[]> | null = null;

function fetchDivisions(): Promise<Division[]> {
  if (divisionsCache) return divisionsCache;
  divisionsCache = fetch('/api/product-area/divisions')
    .then(async (r) => {
      const data = await r.json();
      if (!r.ok || !data.success) throw new Error(data.error || `HTTP ${r.status}`);
      return (data.divisions ?? []) as Division[];
    })
    .catch((err) => {
      divisionsCache = null;
      throw err;
    });
  return divisionsCache;
}

export default function SettingsEditor({ state, onChange }: SectionEditorProps<ProductAreaState>) {
  const set = <K extends keyof ProductAreaState>(key: K, value: ProductAreaState[K]) =>
    onChange({ ...state, [key]: value });

  const [divisions, setDivisions] = useState<Division[] | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;
    fetchDivisions()
      .then((d) => { if (!cancelled) setDivisions(d); })
      .catch((err) => { if (!cancelled) setError(err instanceof Error ? err.message : 'Hämtning misslyckades.'); });
    return () => { cancelled = true; };
  }, []);

  return (
    <>
      <div className="space-y-2">
        <Field.Checkbox
          label="Sidomeny istället för toggle"
          checked={state.sideMenu}
          onChange={(v) => set('sideMenu', v)}
        />
        <Field.Checkbox
          label="Första produkten öppen som default"
          checked={state.defaultOpen}
          onChange={(v) => set('defaultOpen', v)}
        />
      </div>

      <div className="pt-2">
        <label className="block">
          <span className="text-[11px] text-gray-400">Division</span>
          <select
            value={state.division[0] ?? ''}
            onChange={(e) => set('division', e.target.value ? [e.target.value] : [])}
            className="mt-0.5 block w-full rounded bg-gray-100/80 px-3 py-2 text-sm text-gray-700 focus:bg-white focus:ring-1 focus:ring-gray-200 focus:outline-none"
            disabled={divisions === null && !error}
          >
            <option value="">— Ingen —</option>
            {(divisions ?? []).map((d) => (
              <option key={d.id} value={d.id}>{d.name}</option>
            ))}
          </select>
          {divisions === null && !error && (
            <span className="block mt-0.5 text-[10px] text-gray-300">Laddar divisioner…</span>
          )}
          {error && (
            <span className="block mt-0.5 text-[10px] text-red-400">{error}</span>
          )}
        </label>
      </div>
    </>
  );
}
