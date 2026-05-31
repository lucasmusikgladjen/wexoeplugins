'use client';

/**
 * Polymorf sektion-lista editor. En sektion = ett RepeaterCard som
 * dispatchar till rätt type-form i AllSectionForms.tsx.
 *
 * Lägg till-knappen visar en type-picker (alla 15 typer).
 *
 * Per-kort scroll-sync: när ett kort fokuseras (öppnas eller titel-fokuseras)
 * tipsar vi previewen via `data-section={section.clientId}`.
 */

import { useState } from 'react';
import RepeaterCard from '@/components/shared/RepeaterCard';
import {
  CmsPageState,
  emptySection,
  PageSection,
  SectionType,
  SECTION_TYPE_LABELS,
  SECTION_TYPES,
  TabsSection,
} from '@/lib/cms-page-types';
import { Field } from '@/components/shared/fields';
import type { SectionEditorProps } from '@/lib/page-types/types';
import { SectionDesign } from './SectionFormsCommon';
import {
  CaseGridForm,
  CatalogForm,
  CompanyDataStripForm,
  ContactFormForm,
  CtaBannerForm,
  FaqForm,
  HeroForm,
  NewsGridForm,
  NewsTextSplitForm,
  PartnerListForm,
  TabsForm,
  TeamGridForm,
  TestimonialForm,
  TextImageForm,
  TextOnlyForm,
} from './sections/AllSectionForms';

interface PickerProps {
  onPick: (type: SectionType) => void;
  onCancel: () => void;
}

function TypePicker({ onPick, onCancel }: PickerProps) {
  return (
    <div className="rounded-lg border border-gray-200 bg-white p-3 shadow-sm">
      <div className="flex items-center justify-between mb-2">
        <p className="text-xs text-gray-500">Välj sektionstyp:</p>
        <button
          type="button"
          onClick={onCancel}
          className="text-gray-300 hover:text-gray-500 text-sm leading-none"
          aria-label="Avbryt"
        >
          ×
        </button>
      </div>
      <div className="grid grid-cols-2 gap-1.5">
        {SECTION_TYPES.map((t) => (
          <button
            key={t}
            type="button"
            onClick={() => onPick(t)}
            className="text-left text-xs px-2 py-1.5 rounded bg-gray-50 hover:bg-orange-50 hover:text-orange-700 transition-colors"
          >
            {SECTION_TYPE_LABELS[t]}
          </button>
        ))}
      </div>
    </div>
  );
}

/**
 * Renderar en sektion via rätt type-form. Genericen säkrar att tabs-fallet
 * får TabsSection-shape (där tabs-array hanteras).
 */
function renderSectionFields(section: PageSection, patch: (changes: Partial<PageSection>) => void) {
  switch (section.type) {
    case 'hero':
      return <HeroForm section={section} patch={patch as (c: Partial<typeof section>) => void} />;
    case 'text_image':
      return <TextImageForm section={section} patch={patch as (c: Partial<typeof section>) => void} />;
    case 'text_only':
      return <TextOnlyForm section={section} patch={patch as (c: Partial<typeof section>) => void} />;
    case 'company_data_strip':
      return <CompanyDataStripForm section={section} patch={patch as (c: Partial<typeof section>) => void} />;
    case 'news_text_split':
      return <NewsTextSplitForm section={section} patch={patch as (c: Partial<typeof section>) => void} />;
    case 'case_grid':
      return <CaseGridForm section={section} patch={patch as (c: Partial<typeof section>) => void} />;
    case 'news_grid':
      return <NewsGridForm section={section} patch={patch as (c: Partial<typeof section>) => void} />;
    case 'catalog':
      return <CatalogForm section={section} patch={patch as (c: Partial<typeof section>) => void} />;
    case 'tabs':
      return <TabsForm section={section as TabsSection} patch={patch as (c: Partial<TabsSection>) => void} />;
    case 'team_grid':
      return <TeamGridForm section={section} patch={patch as (c: Partial<typeof section>) => void} />;
    case 'partner_list':
      return <PartnerListForm section={section} patch={patch as (c: Partial<typeof section>) => void} />;
    case 'faq':
      return <FaqForm section={section} patch={patch as (c: Partial<typeof section>) => void} />;
    case 'testimonial':
      return <TestimonialForm section={section} patch={patch as (c: Partial<typeof section>) => void} />;
    case 'cta_banner':
      return <CtaBannerForm section={section} patch={patch as (c: Partial<typeof section>) => void} />;
    case 'contact_form':
      return <ContactFormForm section={section} patch={patch as (c: Partial<typeof section>) => void} />;
  }
}

/**
 * Notify preview att hovra/öppna en specifik sektion. Vi dispatcherar
 * en custom event på dokumentet — previewen lyssnar och scrollar.
 *
 * Alternativet vore att tråda en callback genom 5 lager komponenter; för
 * 1 callback-typ är event-bus billigare.
 */
function focusPreviewSection(clientId: string) {
  if (typeof window === 'undefined') return;
  window.dispatchEvent(new CustomEvent('cms-page-focus-section', { detail: clientId }));
}

export default function SectionsEditor({ state, onChange }: SectionEditorProps<CmsPageState>) {
  const [adding, setAdding] = useState(false);
  const setSections = (next: PageSection[]) => onChange({ ...state, sections: next });

  const patchSection = (index: number, changes: Partial<PageSection>) => {
    setSections(
      state.sections.map((s, i) =>
        i === index ? ({ ...s, ...changes } as PageSection) : s,
      ),
    );
  };

  const moveSection = (index: number, dir: -1 | 1) => {
    const j = index + dir;
    if (j < 0 || j >= state.sections.length) return;
    const next = [...state.sections];
    const [m] = next.splice(index, 1);
    next.splice(j, 0, m);
    setSections(next);
  };

  const removeSection = (index: number) => {
    setSections(state.sections.filter((_, i) => i !== index));
  };

  const addSection = (type: SectionType) => {
    setSections([...state.sections, emptySection(type)]);
    setAdding(false);
  };

  return (
    <>
      {state.sections.length === 0 && !adding && (
        <p className="text-xs text-gray-400 italic">
          Inga sektioner än. Klicka &quot;Lägg till sektion&quot; för att börja.
        </p>
      )}

      {state.sections.map((section, i) => {
        const patch = (changes: Partial<PageSection>) => patchSection(i, changes);
        const titleFallback = `${SECTION_TYPE_LABELS[section.type]} #${i + 1}`;
        return (
          <div
            key={section.clientId}
            onFocusCapture={() => focusPreviewSection(section.clientId)}
            onClick={(e) => {
              // Bara fokusera previewen när användaren INTERAGERAR — inte vid
              // ren render av nästade RepeaterCard:s onClick.
              e.stopPropagation();
              focusPreviewSection(section.clientId);
            }}
          >
            <RepeaterCard
              index={i}
              title={section.internalLabel || titleFallback}
              onTitleChange={(v) => patch({ internalLabel: v })}
              titlePlaceholder={titleFallback}
              onMoveUp={() => moveSection(i, -1)}
              onMoveDown={() => moveSection(i, 1)}
              canMoveUp={i > 0}
              canMoveDown={i < state.sections.length - 1}
              onRemove={() => removeSection(i)}
              removeTitle="Ta bort sektion"
              defaultOpen={!section.internalLabel.trim()}
            >
              <p className="text-[10px] uppercase tracking-wider text-gray-300 -mt-1">
                {SECTION_TYPE_LABELS[section.type]}
              </p>
              <Field.Checkbox
                label="Aktiv"
                checked={section.isActive}
                onChange={(v) => patch({ isActive: v })}
              />
              {renderSectionFields(section, patch)}
              <SectionDesign
                section={section}
                patch={(changes) => patch(changes)}
              />
            </RepeaterCard>
          </div>
        );
      })}

      {adding ? (
        <TypePicker onPick={addSection} onCancel={() => setAdding(false)} />
      ) : (
        <button
          type="button"
          onClick={() => setAdding(true)}
          className="w-full py-2 text-sm text-gray-300 hover:text-gray-500 transition-colors border border-dashed border-gray-200 rounded-lg"
        >
          + Lägg till sektion
        </button>
      )}
    </>
  );
}
