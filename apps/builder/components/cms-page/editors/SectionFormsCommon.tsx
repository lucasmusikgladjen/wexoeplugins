'use client';

/**
 * Gemensamma form-block för en sektion: design-fält (layout/theme/padding/anchor)
 * + helpers för scope-input.
 *
 * En sektion = en discrimineradunion-instans. Alla type-specifika editorer
 * importerar dessa primitives.
 */

import { Field } from '@/components/shared/fields';
import {
  BaseSection,
  Layout,
  Padding,
  SectionTheme,
  PageSection,
  TabItem,
} from '@/lib/cms-page-types';

const LAYOUT_OPTIONS: readonly { value: Layout; label: string }[] = [
  { value: 'contained', label: 'Standard' },
  { value: 'narrow', label: 'Smal' },
  { value: 'full_bleed', label: 'Full bredd' },
];

const THEME_OPTIONS: readonly { value: SectionTheme; label: string }[] = [
  { value: 'inherit', label: 'Ärv från sidan' },
  { value: 'light', label: 'Ljus' },
  { value: 'dark', label: 'Mörk' },
];

const PADDING_OPTIONS: readonly { value: Padding; label: string }[] = [
  { value: 'none', label: 'Ingen' },
  { value: 'sm', label: 'Liten' },
  { value: 'md', label: 'Medel' },
  { value: 'lg', label: 'Stor' },
];

interface SectionDesignProps {
  section: BaseSection;
  patch: (changes: Partial<BaseSection>) => void;
}

export function SectionDesign({ section, patch }: SectionDesignProps) {
  return (
    <div className="grid grid-cols-2 gap-2 pt-2 mt-1 border-t border-gray-200/60">
      <Field.Select<Layout>
        label="Bredd"
        value={section.layout}
        onChange={(v) => patch({ layout: v })}
        options={LAYOUT_OPTIONS}
      />
      <Field.Select<SectionTheme>
        label="Tema"
        value={section.theme}
        onChange={(v) => patch({ theme: v })}
        options={THEME_OPTIONS}
      />
      <Field.Select<Padding>
        label="Padding ovan"
        value={section.topPadding}
        onChange={(v) => patch({ topPadding: v })}
        options={PADDING_OPTIONS}
      />
      <Field.Select<Padding>
        label="Padding nedan"
        value={section.bottomPadding}
        onChange={(v) => patch({ bottomPadding: v })}
        options={PADDING_OPTIONS}
      />
      <div className="col-span-2">
        <Field.Text
          label="Ankar-ID (CSS anchor)"
          value={section.anchorId}
          onChange={(v) => patch({ anchorId: v })}
          placeholder="t.ex. kontakt"
          description="Tom = ingen anchor. Använd kortbokstäver/siffror/bindestreck."
        />
      </div>
    </div>
  );
}

/**
 * Helper för type-specifika editor-komponenter: returnerar `patch()` som
 * uppdaterar staten på exakt rätt sektion-index i page-state.
 *
 * Använder generics för att bevara discriminerad union — TypeScript inferrar
 * korrekt typ från callsite.
 */
export interface SectionEditorContext<S extends PageSection> {
  section: S;
  patch: (changes: Partial<S>) => void;
}

/** Generic generator för { section, patch } när du har en PageSection-array och index. */
export function makeSectionContext<S extends PageSection>(
  sections: PageSection[],
  index: number,
  onSections: (next: PageSection[]) => void,
): SectionEditorContext<S> {
  const section = sections[index] as S;
  const patch = (changes: Partial<S>) => {
    onSections(
      sections.map((s, i) => (i === index ? ({ ...s, ...changes } as PageSection) : s)),
    );
  };
  return { section, patch };
}

/** Patch en TabItem inom en tabs-sektion. */
export function makeTabPatch(
  tabs: TabItem[],
  tabIndex: number,
  onTabs: (next: TabItem[]) => void,
) {
  return (changes: Partial<TabItem>) => {
    onTabs(tabs.map((t, i) => (i === tabIndex ? { ...t, ...changes } : t)));
  };
}
