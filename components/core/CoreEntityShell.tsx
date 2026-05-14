'use client';

import { useCallback, useMemo, useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { CoreEntityName, CORE_ENTITIES, CORE_ENTITY_NAMES, isSingleRecordEntity } from '@/lib/core/registry';
import { CORE_ENTITY_FORMS, emptyEntityState } from '@/lib/core/forms';
import CoreEntityForm from './CoreEntityForm';

interface LinkOption {
  recordId: string;
  label: string;
}

interface Props {
  entity: CoreEntityName;
  initialRecords: Array<Record<string, unknown>>;
  linkOptions: Record<string, LinkOption[]>;
}

export default function CoreEntityShell({ entity, initialRecords, linkOptions }: Props) {
  const router = useRouter();
  const def = CORE_ENTITIES[entity];
  const formCfg = CORE_ENTITY_FORMS[entity];
  const singleRecord = isSingleRecordEntity(entity);
  const [records, setRecords] = useState<Array<Record<string, unknown>>>(initialRecords);
  const [selectedId, setSelectedId] = useState<string | null>(initialRecords[0]?._recordId as string | null);
  const [editorState, setEditorState] = useState<Record<string, unknown>>(() => {
    return initialRecords[0] ?? emptyEntityState(entity);
  });
  const [isCreating, setIsCreating] = useState(initialRecords.length === 0);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [justSaved, setJustSaved] = useState(false);

  const selectedRecord = useMemo(() => {
    return records.find((r) => r._recordId === selectedId) ?? null;
  }, [records, selectedId]);

  const handleSelect = useCallback((id: string) => {
    setIsCreating(false);
    setSelectedId(id);
    const rec = records.find((r) => r._recordId === id);
    if (rec) setEditorState(rec);
    setJustSaved(false);
    setError(null);
  }, [records]);

  const handleCreate = useCallback(() => {
    setIsCreating(true);
    setSelectedId(null);
    setEditorState(emptyEntityState(entity));
    setJustSaved(false);
    setError(null);
  }, [entity]);

  async function handleSave() {
    setSaving(true);
    setError(null);
    try {
      const url = isCreating
        ? `/api/core/${entity}`
        : `/api/core/${entity}?id=${selectedId}`;
      const res = await fetch(url, {
        method: isCreating ? 'POST' : 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(editorState),
      });
      const data = await res.json();
      if (!res.ok || !data.success) {
        throw new Error(data.error || `HTTP ${res.status}`);
      }
      const saved = data.record as Record<string, unknown>;
      if (isCreating) {
        setRecords((rs) => [...rs, saved]);
        setSelectedId(saved._recordId as string);
        setIsCreating(false);
      } else {
        setRecords((rs) => rs.map((r) => r._recordId === saved._recordId ? saved : r));
      }
      setEditorState(saved);
      setJustSaved(true);
      router.refresh();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Sparning misslyckades');
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete() {
    if (!selectedId) return;
    if (!confirm('Markera "Aktiv = false" istället för att radera. Är du säker på att du vill radera?')) return;
    setSaving(true);
    setError(null);
    try {
      const res = await fetch(`/api/core/${entity}?id=${selectedId}`, { method: 'DELETE' });
      const data = await res.json();
      if (!res.ok || !data.success) throw new Error(data.error || `HTTP ${res.status}`);
      setRecords((rs) => rs.filter((r) => r._recordId !== selectedId));
      setSelectedId(null);
      setEditorState(emptyEntityState(entity));
      setIsCreating(true);
      router.refresh();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Radering misslyckades');
    } finally {
      setSaving(false);
    }
  }

  return (
    <div className="h-screen flex flex-col" style={{ fontFamily: 'var(--font-dm-sans)' }}>
      <header className="h-14 border-b border-gray-100 bg-white flex items-center px-4 gap-4 flex-shrink-0 z-10">
        <Link
          href="/globals"
          className="flex items-center gap-2 text-gray-500 hover:text-gray-900 transition-colors"
          title="Tillbaka till globaler"
        >
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M19 12H5M12 19l-7-7 7-7" />
          </svg>
          <span className="text-sm">Globaler</span>
        </Link>

        <div className="h-5 w-px bg-gray-200 mx-1" />

        <div className="flex items-center gap-2">
          <span className="text-sm font-medium text-gray-800">{def.label}</span>
        </div>

        <div className="flex-1" />

        {error && <span className="text-xs text-red-500 truncate max-w-xs">{error}</span>}
        {justSaved && !error && <span className="text-xs text-gray-400">Sparat ✓</span>}

        {!singleRecord && !isCreating && selectedId && (
          <button
            onClick={handleDelete}
            disabled={saving}
            className="px-3 py-1.5 text-xs rounded-md text-red-600 hover:bg-red-50 transition-colors disabled:opacity-40"
          >
            Radera
          </button>
        )}

        <button
          onClick={handleSave}
          disabled={saving}
          className="px-4 py-1.5 rounded-md text-sm font-medium text-white transition-opacity hover:opacity-90 disabled:opacity-40"
          style={{ background: '#11325D' }}
        >
          {saving ? 'Sparar…' : isCreating ? 'Skapa' : 'Spara'}
        </button>
      </header>

      <div className="flex-1 flex min-h-0">
        {!singleRecord && (
          <div className="w-[280px] border-r border-gray-100 bg-gray-50 flex flex-col min-h-0">
            <button
              onClick={handleCreate}
              className="m-3 px-3 py-2 text-sm rounded-md border border-dashed border-gray-300 text-gray-600 hover:border-gray-400 hover:text-gray-900 transition-colors"
            >
              + Lägg till {def.label.toLowerCase()}
            </button>
            <div className="flex-1 overflow-y-auto px-3 pb-3 space-y-1">
              {records.length === 0 && (
                <p className="text-xs text-gray-400 py-2">Inga records ännu.</p>
              )}
              {records.map((r) => {
                const id = r._recordId as string;
                const label = String(r[formCfg.listLabelField] ?? '(ingen titel)');
                const meta = formCfg.listMetaField ? String(r[formCfg.listMetaField] ?? '') : '';
                const isDefault = r.is_default === true;
                const isActive = r.active !== false;
                return (
                  <button
                    key={id}
                    onClick={() => handleSelect(id)}
                    className={`w-full text-left px-2 py-1.5 rounded-md text-xs transition-colors ${
                      !isCreating && selectedId === id ? 'bg-white shadow-sm' : 'hover:bg-white'
                    }`}
                  >
                    <div className="flex items-center gap-1.5">
                      <span className={`font-medium truncate ${isActive ? 'text-gray-800' : 'text-gray-400 line-through'}`}>
                        {label || '(tomt)'}
                      </span>
                      {isDefault && (
                        <span className="text-[9px] uppercase tracking-wider bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded">default</span>
                      )}
                    </div>
                    {meta && <div className="text-[10px] text-gray-400 truncate">{meta}</div>}
                  </button>
                );
              })}
            </div>
          </div>
        )}

        <div className="flex-1 overflow-y-auto bg-white">
          <div className="max-w-2xl mx-auto p-6">
            <div className="mb-4 text-xs text-gray-500">{def.description}</div>
            {(isCreating || selectedRecord) && (
              <CoreEntityForm
                entity={entity}
                initialState={editorState}
                state={editorState}
                onChange={setEditorState}
                linkOptions={linkOptions}
              />
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

export { CORE_ENTITY_NAMES };
