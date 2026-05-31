'use client';

import { PartnerPageState } from '@/lib/partner-types';
import { Field } from '@/components/shared/fields';
import type { SectionEditorProps } from '@/lib/page-types/types';

export default function ContactPersonEditor({
  state,
  onChange,
}: SectionEditorProps<PartnerPageState>) {
  const set = <K extends keyof PartnerPageState>(key: K, value: PartnerPageState[K]) =>
    onChange({ ...state, [key]: value });

  return (
    <>
      <Field.Text
        label="Bild"
        value={state.contactImageUrl}
        onChange={(v) => set('contactImageUrl', v)}
        placeholder="https://..."
      />

      <div className="grid grid-cols-2 gap-2">
        <Field.Text
          label="Namn"
          value={state.contactName}
          onChange={(v) => set('contactName', v)}
          placeholder="Anders Andersson"
        />
        <Field.Text
          label="Titel"
          value={state.contactTitle}
          onChange={(v) => set('contactTitle', v)}
          placeholder="T.ex. Rockwell Brand Specialist"
        />
      </div>

      <div className="grid grid-cols-2 gap-2">
        <Field.Text
          label="E-post"
          value={state.contactEmail}
          onChange={(v) => set('contactEmail', v)}
          placeholder="anders@wexoe.se"
        />
        <Field.Text
          label="Telefon"
          value={state.contactPhone}
          onChange={(v) => set('contactPhone', v)}
          placeholder="+46 70 123 45 67"
        />
      </div>

      <Field.RichText
        label="Citat (valfritt)"
        value={state.contactQuote}
        onChange={(v) => set('contactQuote', v)}
        rows={3}
        placeholder="T.ex. Rockwell är ett komplext ekosystem — min uppgift är att översätta ditt behov…"
      />
    </>
  );
}
