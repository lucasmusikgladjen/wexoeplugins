'use client';

import { useReducer, useState, useCallback } from 'react';
import { SectionId } from '@/lib/types';
import { initialState, pageReducer } from '@/lib/state';
import PreviewPanel from '@/components/PreviewPanel';
import EditorPanel from '@/components/EditorPanel';
import PublishDialog from '@/components/PublishDialog';

export default function BuilderPage() {
  const [state, dispatch] = useReducer(pageReducer, initialState);
  const [activeSection, setActiveSection] = useState<SectionId | null>('hero');
  const [scrollTrigger, setScrollTrigger] = useState(0);
  const [showPublish, setShowPublish] = useState(false);

  const handleSectionClick = useCallback((section: SectionId) => {
    setActiveSection(section);
  }, []);

  const handleSectionFocus = useCallback((section: SectionId) => {
    setActiveSection(section);
    setScrollTrigger(prev => prev + 1);
  }, []);

  return (
    <div className="h-screen flex flex-col" style={{ fontFamily: 'var(--font-dm-sans)' }}>
      {/* Toolbar */}
      <header className="h-14 border-b border-lp-border bg-white flex items-center px-4 gap-4 flex-shrink-0 z-10">
        <div className="flex items-center gap-2">
          <div className="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs font-bold" style={{ background: '#11325D' }}>
            W
          </div>
          <span className="font-semibold text-sm text-lp-main">Page Builder</span>
        </div>

        <div className="h-6 w-px bg-lp-border mx-1" />

        {/* Slug input */}
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

        {/* Spacer */}
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

        {/* Publish button */}
        <button
          onClick={() => setShowPublish(true)}
          className="px-4 py-1.5 rounded-lg text-sm font-semibold text-white transition-opacity hover:opacity-90"
          style={{ background: '#11325D' }}
        >
          Publicera →
        </button>
      </header>

      {/* Main layout: Preview + Editor */}
      <div className="flex-1 flex min-h-0">
        {/* Preview panel (65%) */}
        <div className="flex-[65] min-w-0">
          <PreviewPanel
            state={state}
            activeSection={activeSection}
            onSectionClick={handleSectionClick}
            scrollTrigger={scrollTrigger}
          />
        </div>

        {/* Editor panel (35%) */}
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

      {/* Publish dialog */}
      {showPublish && (
        <PublishDialog
          state={state}
          onClose={() => setShowPublish(false)}
        />
      )}
    </div>
  );
}
