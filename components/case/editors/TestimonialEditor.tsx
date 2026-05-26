'use client';

import { CaseState } from '@/lib/case-types';
import { Field } from '@/components/shared/fields';
import type { SectionEditorProps } from '@/lib/page-types/types';

export default function TestimonialEditor({ state, onChange }: SectionEditorProps<CaseState>) {
  const set = <K extends keyof CaseState>(key: K, value: CaseState[K]) =>
    onChange({ ...state, [key]: value });

  return (
    <>
      <Field.RichText
        label="Citat"
        value={state.testimonialQuote}
        onChange={(v) => set('testimonialQuote', v)}
        rows={5}
        hint="Markdown inline (**bold**, *italic*, [länk](url))."
        placeholder="T.ex. Vi hade gått i tankarna kring en migration i åtta år men aldrig sett en väg framåt…"
      />

      <Field.Text
        label="Författarens foto (URL)"
        value={state.testimonialPhotoUrl}
        onChange={(v) => set('testimonialPhotoUrl', v)}
        placeholder="https://..."
      />

      <div className="grid grid-cols-2 gap-2">
        <Field.Text
          label="Namn"
          value={state.testimonialAuthorName}
          onChange={(v) => set('testimonialAuthorName', v)}
          placeholder="Karin Lindberg"
        />
        <Field.Text
          label="Titel"
          value={state.testimonialAuthorTitle}
          onChange={(v) => set('testimonialAuthorTitle', v)}
          placeholder="Underhållschef, Arla Götene"
        />
      </div>
    </>
  );
}
