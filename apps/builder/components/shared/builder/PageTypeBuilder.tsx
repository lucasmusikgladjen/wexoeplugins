'use client';

/**
 * Generisk builder-shell för alla sidtyper.
 *
 * Drivs av en `PageTypeUIDef<TState>` — säg "vilka sektioner finns, vilken
 * preview-layout, vilka fält är required" — och hanterar resten:
 *
 *   - state-management (`useState<TState>`)
 *   - editor-panel: en `<EditorSection>` per `uiDef.sections[i]` med
 *     standardiserad header, collapse och visibility-toggle. Section-Editor:n
 *     renderar BARA fält; wrappern ägs av buildern så ingen sidtyp kan avvika.
 *   - quickNav (auto-genererad från sections)
 *   - preview-panel (uiDef.previewLayout)
 *   - save-flöde: POST för create, PATCH för update; applicerar
 *     `relations.<id>.created`-mappningar på state så nya child-record-IDs
 *     hamnar i state utan reload
 *
 * Tar INTE emot `serverDef` — server-funktioner får aldrig passera till
 * client i App Router. Server-page kör `serverDef.fromRecord(record)` och
 * skickar `initialState` (plain JSON) hit.
 *
 * Renderar `<PageList>` INTE — den ligger kvar i app/page.tsx (homepage)
 * och i create/edit-routes per Etapp 3-planen.
 */

import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { useRouter } from 'next/navigation';
import BuilderShell from '@/components/BuilderShell';
import SectionEditor from '@/components/shared/SectionEditor';
import { ErrorToast, SaveBar, UnsavedChangesGuard } from './';
import type {
  PageTypeUIDef,
  SectionDef,
  RelationSyncResult,
} from '@/lib/page-types/types';

export interface PageTypeBuilderProps<TState> {
  /** UI-definition från `lib/page-types/<type>.ui.ts`. */
  uiDef: PageTypeUIDef<TState> & {
    canSave?: (state: TState) => boolean;
    canSaveHint?: string;
  };
  /** Hela page-state, normaliserad server-side via `serverDef.fromRecord`. */
  initialState: TState;
  /** Skapande eller redigering. Avgör HTTP-metod och redirect-beteende. */
  mode: 'create' | 'edit';
  /** Befintligt Airtable record-ID — krävs om mode === 'edit'. */
  recordId?: string;
  /** Explicit API endpoint. Needed because UI ids and route slugs can diverge. */
  apiPath?: string;
  /** Edit route template after create, e.g. /editor/audience/:recordId. */
  editPath?: string;
  /**
   * Hook som körs efter lyckad save. Tar emot save-resultet (inkl.
   * `relations` om sidtypen har sådana) och nuvarande state, returnerar
   * ny state som ska behållas i builder:n. Default: ingen state-ändring,
   * bara `router.refresh()`.
   *
   * Sidtyper med relations använder detta för att para clientId → recordId
   * från `result.relations.<id>.created` mot sina nya items.
   */
  onSaved?: (
    result: { mode: 'create' | 'update'; recordId: string; relations: Record<string, RelationSyncResult> },
    state: TState,
  ) => TState | void;
}

interface SaveResponseShape {
  success: boolean;
  mode?: 'create' | 'update';
  recordId?: string;
  relations?: Record<string, RelationSyncResult>;
  error?: string;
}

export default function PageTypeBuilder<TState>({
  uiDef,
  initialState,
  mode,
  recordId,
  apiPath,
  editPath,
  onSaved,
}: PageTypeBuilderProps<TState>) {
  const router = useRouter();
  const [state, setState] = useState<TState>(initialState);
  const resolvedSections = useMemo(
    () => (uiDef.resolveSections ? uiDef.resolveSections(state) : uiDef.sections),
    [uiDef, state],
  );
  const [activeSection, setActiveSection] = useState<string | null>(
    resolvedSections[0]?.id ?? null,
  );
  const [scrollTrigger, setScrollTrigger] = useState(0);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [justSaved, setJustSaved] = useState(false);
  const savedSnapshotRef = useRef(JSON.stringify(initialState));
  const currentSnapshot = useMemo(() => JSON.stringify(state), [state]);
  const isDirty = currentSnapshot !== savedSnapshotRef.current;

  const isCreate = mode === 'create';
  const canSave = uiDef.canSave ? uiDef.canSave(state) : true;

  useEffect(() => {
    if (!resolvedSections.length) {
      setActiveSection(null);
      return;
    }
    if (!activeSection || !resolvedSections.some((s) => s.id === activeSection)) {
      setActiveSection(resolvedSections[0].id);
    }
  }, [activeSection, resolvedSections]);

  const update = useCallback((next: TState) => {
    setState(next);
    setJustSaved(false);
  }, []);

  const handleSectionFocus = useCallback((id: string) => {
    setActiveSection(id);
    setScrollTrigger((n) => n + 1);
  }, []);

  async function handleSave() {
    if (!canSave || saving) return;
    setSaving(true);
    setError(null);

    const endpoint = apiPath ?? `/api/${uiDef.id}`;
    const url = isCreate ? endpoint : `${endpoint}?id=${recordId}`;
    const method = isCreate ? 'POST' : 'PATCH';

    try {
      const res = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(state),
      });
      const data: SaveResponseShape = await res.json();
      if (!res.ok || !data.success || !data.mode || !data.recordId) {
        throw new Error(data.error || `HTTP ${res.status}`);
      }

      if (data.mode === 'create') {
        // Redirecta så URL:en matchar den nya record:en. State hydreras om
        // från servern via fromRecord på edit-routen — inga relations-IDs
        // behöver smyga in på state här.
        savedSnapshotRef.current = JSON.stringify(state);
        setJustSaved(true);
        router.replace((editPath ?? `/editor/${uiDef.id}/:recordId`).replace(':recordId', data.recordId));
        return;
      }

      // Update: låt sidtypen själv applicera relations.created om den vill,
      // annars bara markera sparad och refresha.
      const next = onSaved
        ? onSaved(
            { mode: data.mode, recordId: data.recordId, relations: data.relations ?? {} },
            state,
          )
        : undefined;
      const savedState = next !== undefined ? next : state;
      if (next !== undefined) setState(next);
      savedSnapshotRef.current = JSON.stringify(savedState);
      setJustSaved(true);
      if (!onSaved) router.refresh();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Sparning misslyckades.');
    } finally {
      setSaving(false);
    }
  }

  const quickNav = resolvedSections.map((s) => ({ id: s.id, label: s.label }));

  const slugConfig = uiDef.slugInput;
  const slug = slugConfig ? slugConfig.accessor(state) : '';
  const slugBadge = slugConfig?.badge?.(state, mode);

  const ToolbarExtras = uiDef.toolbarExtras;
  const toolbarLeft = slugConfig || ToolbarExtras ? (
    <div className="flex items-center gap-2">
      {slugConfig && (
        <>
          <span className="text-xs text-lp-text-light">Slug:</span>
          <input
            type="text"
            value={slug}
            onChange={(e) => {
              const cleaned = e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, '');
              update(slugConfig.setter(state, cleaned));
            }}
            placeholder={slugConfig.placeholder ?? 'min-sida'}
            className="w-40 px-2 py-1 text-sm border border-lp-border rounded-md focus:border-lp-main focus:outline-none focus:ring-1 focus:ring-lp-main"
          />
        </>
      )}
      {ToolbarExtras && <ToolbarExtras state={state} setState={update} />}
      {slugBadge && (
        <span className="text-[10px] uppercase tracking-wider text-lp-text-light ml-2">
          {slugBadge}
        </span>
      )}
    </div>
  ) : undefined;

  const toolbarMiddle = error ? (
    <span className="text-xs text-red-500 truncate max-w-xs">{error}</span>
  ) : undefined;

  const toolbarRight = (
    <SaveBar
      dirty={isDirty}
      saving={saving}
      canSave={canSave}
      justSaved={justSaved}
      isCreate={isCreate}
      onSave={handleSave}
      hint={uiDef.canSaveHint}
    />
  );

  const PreviewLayout = uiDef.previewLayout;

  return (
    <>
      <UnsavedChangesGuard active={isDirty && !saving} />
      <ErrorToast message={error} onClose={() => setError(null)} />
      <BuilderShell
        toolbar={{ left: toolbarLeft, middle: toolbarMiddle, right: toolbarRight }}
        quickNav={quickNav}
        activeSection={activeSection}
        onActiveSectionChange={(id) => setActiveSection(id)}
        previewPanel={
          <PreviewLayout
            state={state}
            activeSection={activeSection}
            scrollTrigger={scrollTrigger}
            onSectionClick={(id) => setActiveSection(id)}
          />
        }
        editorSections={({ sectionRef, onSectionFocus }) =>
          resolvedSections.map((section) => (
            <SectionWrapper
              key={section.id}
              section={section}
              state={state}
              setState={update}
              sectionRef={sectionRef(section.id)}
              onFocus={() => {
                onSectionFocus(section.id);
                handleSectionFocus(section.id);
              }}
            />
          ))
        }
      />
    </>
  );
}

// ─── Per-section wrapper ───────────────────────────────────────────────────

interface SectionWrapperProps<TState> {
  section: SectionDef<TState>;
  state: TState;
  setState: (next: TState) => void;
  sectionRef: (el: HTMLDivElement | null) => void;
  onFocus: () => void;
}

function SectionWrapper<TState>({
  section,
  state,
  setState,
  sectionRef,
  onFocus,
}: SectionWrapperProps<TState>) {
  const Editor = section.Editor;
  const toggle = section.visibilityToggle?.(state, setState);

  return (
    <div
      ref={sectionRef}
      className="cursor-pointer"
      onClick={onFocus}
      onFocusCapture={onFocus}
    >
      <SectionEditor
        id={section.id}
        title={section.label}
        description={section.description}
        visibilityToggle={toggle}
        defaultOpen={!section.defaultCollapsed}
        filledIndicator={section.isFilled?.(state)}
      >
        <Editor state={state} onChange={setState} />
      </SectionEditor>
    </div>
  );
}
