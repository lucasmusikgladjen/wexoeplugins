'use client';

import { CaseState } from '@/lib/case-types';
import { Field } from '@/components/shared/fields';
import type { SectionEditorProps } from '@/lib/page-types/types';

export default function SeoEditor({ state, onChange }: SectionEditorProps<CaseState>) {
  const set = <K extends keyof CaseState>(key: K, value: CaseState[K]) =>
    onChange({ ...state, [key]: value });

  return (
    <>
      <Field.Text
        label="SEO-titel"
        value={state.seoTitle}
        onChange={(v) => set('seoTitle', v)}
        placeholder="Lämna tom för att använda case-titeln"
        description="Faller tillbaka till H1 om tom."
      />
      <Field.Textarea
        label="SEO-beskrivning"
        value={state.seoDescription}
        onChange={(v) => set('seoDescription', v)}
        rows={3}
        placeholder="Meta-description som visas i sökresultat och social-shares."
      />
      <Field.Text
        label="Open Graph-bild (URL)"
        value={state.ogImageUrl}
        onChange={(v) => set('ogImageUrl', v)}
        placeholder="https://wexoe.se/wp-content/uploads/..."
        description="Visas när sidan delas på Facebook/LinkedIn. 1200×630px rekommenderas."
      />
    </>
  );
}
