'use client';

import { CaseState } from '@/lib/case-types';
import { Field } from '@/components/shared/fields';
import type { SectionEditorProps } from '@/lib/page-types/types';

export default function AboutCustomerEditor({ state, onChange }: SectionEditorProps<CaseState>) {
  const set = <K extends keyof CaseState>(key: K, value: CaseState[K]) =>
    onChange({ ...state, [key]: value });

  return (
    <>
      <Field.Text
        label="Logo (URL)"
        value={state.aboutCustomerLogoUrl}
        onChange={(v) => set('aboutCustomerLogoUrl', v)}
        placeholder="https://..."
      />
      <Field.Text
        label="Titel (H3)"
        value={state.aboutCustomerTitle}
        onChange={(v) => set('aboutCustomerTitle', v)}
        placeholder="T.ex. Om Arla Foods"
      />
      <Field.RichText
        label="Brödtext"
        value={state.aboutCustomerText}
        onChange={(v) => set('aboutCustomerText', v)}
        rows={6}
        hint="Markdown stöds."
        placeholder="Kort företagspresentation…"
      />

      <Field.Buttons
        label="Länk"
        segments={[
          {
            value: state.aboutCustomerLinkLabel,
            onChange: (v) => set('aboutCustomerLinkLabel', v),
            placeholder: 'T.ex. Läs mer om Arla',
          },
          {
            value: state.aboutCustomerUrl,
            onChange: (v) => set('aboutCustomerUrl', v),
            placeholder: 'https://www.arla.se',
          },
        ]}
      />
    </>
  );
}
