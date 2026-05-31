'use client';

/**
 * Sidans metadata + SEO + målgruppslänkar + sidvisning.
 *
 * page_theme och max_width är page-globala men ärvs bara av sektioner som
 * sätter theme='inherit' resp. uses default layout. De flesta sektioner
 * överrider lokalt, så fälten ligger som "avancerade" inställningar längst ner.
 */

import { useState } from 'react';
import { Field } from '@/components/shared/fields';
import type { SectionEditorProps } from '@/lib/page-types/types';
import { CmsPageState, MaxWidth, PageTheme } from '@/lib/cms-page-types';

const THEME_OPTIONS: readonly { value: PageTheme; label: string }[] = [
  { value: 'light', label: 'Ljus' },
  { value: 'dark', label: 'Mörk' },
];

const MAX_WIDTH_OPTIONS: readonly { value: MaxWidth; label: string }[] = [
  { value: 'narrow', label: 'Smal (760 px)' },
  { value: 'normal', label: 'Normal (1100 px)' },
  { value: 'wide', label: 'Bred' },
  { value: 'full', label: 'Full bredd' },
];

export default function MetadataPanel({ state, onChange }: SectionEditorProps<CmsPageState>) {
  const [showAdvanced, setShowAdvanced] = useState(false);
  const set = <K extends keyof CmsPageState>(key: K, value: CmsPageState[K]) =>
    onChange({ ...state, [key]: value });

  return (
    <>
      <Field.Text
        label="Internt namn"
        value={state.internalLabel}
        onChange={(v) => set('internalLabel', v)}
        placeholder="T.ex. Startsida SE"
        description="Marknadsförarens egna namn. Visas inte publikt."
      />

      <Field.Text
        label="Rubrik (H1)"
        value={state.h1}
        onChange={(v) => set('h1', v)}
        placeholder="Sidans huvudrubrik"
        description="Visas överst om ingen Hero-sektion finns. Hero-sektionen kan override:a."
      />

      <Field.Text
        label="SEO-titel"
        value={state.seoTitle}
        onChange={(v) => set('seoTitle', v)}
        placeholder="Lämna tom för att fallback till H1"
      />
      <Field.Textarea
        label="SEO-beskrivning"
        value={state.seoDescription}
        onChange={(v) => set('seoDescription', v)}
        rows={3}
        placeholder="Meta description för sökmotorer"
      />
      <Field.Text
        label="Open Graph-bild-URL"
        value={state.ogImageUrl}
        onChange={(v) => set('ogImageUrl', v)}
        placeholder="https://..."
      />

      <Field.LinkedRecords
        label="Länder"
        source="core_countries"
        value={state.countryIds}
        onChange={(ids) => set('countryIds', ids)}
        description="Bestämmer scope för sektioner utan eget land-filter."
      />
      <Field.LinkedRecords
        label="Divisioner"
        source="core_divisions"
        value={state.divisionIds}
        onChange={(ids) => set('divisionIds', ids)}
        description="Bestämmer scope för sektioner utan eget division-filter."
      />

      <Field.Textarea
        label="Interna anteckningar"
        value={state.internalNotes}
        onChange={(v) => set('internalNotes', v)}
        rows={3}
        placeholder="Anteckningar till andra redaktörer. Visas inte publikt."
      />

      <div className="pt-2 mt-2 border-t border-gray-100">
        <button
          type="button"
          onClick={() => setShowAdvanced((v) => !v)}
          className="text-[11px] text-gray-400 hover:text-gray-700 transition-colors"
        >
          {showAdvanced ? '▾' : '▸'} Sidövergripande utseende
        </button>
        {showAdvanced && (
          <div className="mt-3 space-y-3">
            <Field.Select<PageTheme>
              label="Sidtema"
              value={state.pageTheme}
              onChange={(v) => set('pageTheme', v)}
              options={THEME_OPTIONS}
              description="Sektioner med tema=ärv använder detta."
            />
            <Field.Select<MaxWidth>
              label="Standard-bredd"
              value={state.maxWidth}
              onChange={(v) => set('maxWidth', v)}
              options={MAX_WIDTH_OPTIONS}
              description="Sektioner använder sin egen layout-inställning — detta är bara informativt."
            />
          </div>
        )}
      </div>
    </>
  );
}
