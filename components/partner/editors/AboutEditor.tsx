'use client';

import { PartnerPageState } from '@/lib/partner-types';
import { Field } from '@/components/shared/fields';
import type { SectionEditorProps } from '@/lib/page-types/types';

export default function AboutEditor({ state, onChange }: SectionEditorProps<PartnerPageState>) {
  const set = <K extends keyof PartnerPageState>(key: K, value: PartnerPageState[K]) =>
    onChange({ ...state, [key]: value });

  return (
    <>
      <Field.Text
        label="Eyebrow"
        value={state.aboutEyebrow}
        onChange={(v) => set('aboutEyebrow', v)}
        placeholder="T.ex. Om leverantören"
      />

      <Field.Text
        label="H2"
        value={state.aboutH2}
        onChange={(v) => set('aboutH2', v)}
        placeholder="T.ex. Världsledande inom industriell automation"
      />

      <Field.RichText
        label="Brödtext"
        value={state.aboutText}
        onChange={(v) => set('aboutText', v)}
        rows={8}
        hint="Markdown — **bold**, *italic*, [länk](url), - listor."
        placeholder="Berätta om partnerns bakgrund, fokus, position på marknaden…"
      />

      <Field.Text
        label="Bild"
        value={state.aboutImageUrl}
        onChange={(v) => set('aboutImageUrl', v)}
        placeholder="https://..."
      />

      <div className="grid grid-cols-2 gap-2 pt-2 mt-2 border-t border-gray-100">
        <Field.Text
          label="Badge-värde"
          value={state.aboutBadgeValue}
          onChange={(v) => set('aboutBadgeValue', v)}
          placeholder="T.ex. #1"
        />
        <Field.Text
          label="Badge-etikett"
          value={state.aboutBadgeLabel}
          onChange={(v) => set('aboutBadgeLabel', v)}
          placeholder="T.ex. Globalt inom..."
        />
      </div>
      <p className="text-[10px] text-gray-400 -mt-1">
        Badge:n visas över bilden om <strong>värdet</strong> är ifyllt.
      </p>
    </>
  );
}
