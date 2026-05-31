'use client';

import { useReducer, useState, useCallback, useEffect } from 'react';
import Link from 'next/link';
import { PageState, SectionId } from '@/lib/types';
import { initialState, pageReducer } from '@/lib/state';
import PreviewPanel from '@/components/PreviewPanel';
import EditorPanel from '@/components/EditorPanel';
import PublishDialog from '@/components/PublishDialog';

interface Props {
  /** When provided, the editor opens in edit mode pre-filled with this state. */
  loadedState?: PageState;
}

export default function PageBuilder({ loadedState }: Props) {
  const [state, dispatch] = useReducer(
    pageReducer,
    loadedState ?? initialState,
  );
  const [activeSection, setActiveSection] = useState<SectionId | null>('hero');
  const [scrollTrigger, setScrollTrigger] = useState(0);
  const [showPublish, setShowPublish] = useState(false);

  // If the loaded state changes after mount (e.g. from a parent fetch), sync it.
  useEffect(() => {
    if (loadedState) {
      dispatch({ type: 'LOAD_STATE', state: loadedState });
    }
  }, [loadedState]);

  const handleSectionClick = useCallback((section: SectionId) => {
    setActiveSection(section);
  }, []);

  const handleSectionFocus = useCallback((section: SectionId) => {
    setActiveSection(section);
    setScrollTrigger((prev) => prev + 1);
  }, []);

  const isEdit = state.mode === 'edit';

  return (
    <div className="h-screen flex flex-col" style={{ fontFamily: 'var(--font-dm-sans)' }}>
      {/* Toolbar */}
      <header className="h-14 border-b border-lp-border bg-white flex items-center px-4 gap-4 flex-shrink-0 z-10">
        <Link
          href="/"
          className="flex items-center gap-2 text-gray-500 hover:text-lp-main transition-colors"
          title="Tillbaka till sidöversikt"
        >
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M19 12H5M12 19l-7-7 7-7" />
          </svg>
          <span className="text-sm">Sidor</span>
        </Link>

        <div className="h-6 w-px bg-lp-border mx-1" />

        <div className="flex items-center gap-2">
          <span className="text-xs text-lp-text-light">Slug:</span>
          <input
            type="text"
            value={state.slug}
            onChange={(e) =>
              dispatch({
                type: 'SET_FIELD',
                field: 'slug',
                value: e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, ''),
              })
            }
            placeholder="min-sida"
            className="w-40 px-2 py-1 text-sm border border-lp-border rounded-md focus:border-lp-main focus:outline-none focus:ring-1 focus:ring-lp-main"
          />
        </div>

        <div className="flex-1" />

        {/* Color pickers */}
        <div className="flex items-center gap-2">
          <label className="flex items-center gap-1 text-xs text-lp-text-light">
            Primär:
            <input
              type="color"
              value={state.colorMain}
              onChange={(e) => dispatch({ type: 'SET_FIELD', field: 'colorMain', value: e.target.value })}
              className="w-6 h-6 rounded border border-lp-border cursor-pointer"
            />
          </label>
          <label className="flex items-center gap-1 text-xs text-lp-text-light">
            Accent:
            <input
              type="color"
              value={state.colorSecondary}
              onChange={(e) => dispatch({ type: 'SET_FIELD', field: 'colorSecondary', value: e.target.value })}
              className="w-6 h-6 rounded border border-lp-border cursor-pointer"
            />
          </label>
        </div>

        <div className="h-6 w-px bg-lp-border mx-1" />

        <button
          onClick={() => setShowPublish(true)}
          className="px-4 py-1.5 rounded-lg text-sm font-semibold text-white transition-opacity hover:opacity-90"
          style={{ background: '#11325D' }}
        >
          {isEdit ? 'Spara' : 'Publicera →'}
        </button>
      </header>

      {/* Main layout: Preview + Editor */}
      <div className="flex-1 flex min-h-0">
        <div className="flex-[65] min-w-0">
          <PreviewPanel
            state={state}
            activeSection={activeSection}
            onSectionClick={handleSectionClick}
            scrollTrigger={scrollTrigger}
          />
        </div>
        <div className="flex-[35] min-w-[360px] max-w-[480px]">
          <EditorPanel
            state={state}
            dispatch={dispatch}
            activeSection={activeSection}
            onSectionClick={handleSectionClick}
            onSectionFocus={handleSectionFocus}
          />
        </div>
      </div>

      {showPublish && (
        <PublishDialog
          state={state}
          onClose={() => setShowPublish(false)}
        />
      )}
    </div>
  );
}
