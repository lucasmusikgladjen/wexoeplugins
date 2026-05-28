'use client';

import { PartnerPageState } from '@/lib/partner-types';
import { Field } from '@/components/shared/fields';
import type { SectionEditorProps } from '@/lib/page-types/types';

export default function CategoriesEditor({
  state,
  onChange,
}: SectionEditorProps<PartnerPageState>) {
  const set = <K extends keyof PartnerPageState>(key: K, value: PartnerPageState[K]) =>
    onChange({ ...state, [key]: value });

  return (
    <>
      <Field.Text
        label="Eyebrow"
        value={state.categoriesEyebrow}
        onChange={(v) => set('categoriesEyebrow', v)}
        placeholder="T.ex. Produktportfölj"
      />

      <Field.Text
        label="H2"
        value={state.categoriesH2}
        onChange={(v) => set('categoriesH2', v)}
        placeholder="T.ex. Det här erbjuder vi från Rockwell Automation"
      />

      <Field.RichText
        label="Intro-text"
        value={state.categoriesIntro}
        onChange={(v) => set('categoriesIntro', v)}
        rows={3}
        placeholder="Kort introduktion ovanför korten…"
      />

      <div className="pt-2 mt-2 border-t border-gray-100">
        <Field.LinkedRecords
          label="Produktområden"
          source="product_areas"
          value={state.categoryIds}
          onChange={(ids) => set('categoryIds', ids)}
          filter={(r) => r.is_active !== false}
        />
      </div>
    </>
  );
}
