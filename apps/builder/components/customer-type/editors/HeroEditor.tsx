'use client';

import { CustomerTypePageState } from '@/lib/customer-type-types';
import { Field } from '@/components/shared/fields';
import type { SectionEditorProps } from '@/lib/page-types/types';

export default function HeroEditor({ state, onChange }: SectionEditorProps<CustomerTypePageState>) {
  const set = <K extends keyof CustomerTypePageState>(key: K, value: CustomerTypePageState[K]) =>
    onChange({ ...state, [key]: value });

  return (
    <>
      <Field.Text
        label="Internt namn"
        value={state.name}
        onChange={(v) => set('name', v)}
        placeholder="T.ex. Installatör, OEM"
        description="Visas inte publikt — används som etikett i listor."
      />

      <Field.Text
        label="Eyebrow"
        value={state.eyebrow}
        onChange={(v) => set('eyebrow', v)}
        placeholder="T.ex. För installatörer"
      />

      <Field.RichText
        label="Titel"
        value={state.title}
        onChange={(v) => set('title', v)}
        rows={3}
        hint="*ord* för orange highlight"
        placeholder="Framtidens *automation* för din fabrik"
      />

      <Field.Textarea
        label="Beskrivning"
        value={state.description}
        onChange={(v) => set('description', v)}
        rows={4}
        placeholder="Kort beskrivning under titeln…"
      />

      <Field.Buttons
        label="CTA-knapp"
        segments={[
          { value: state.ctaText, onChange: (v) => set('ctaText', v), placeholder: 'Text' },
          { value: state.ctaUrl, onChange: (v) => set('ctaUrl', v), placeholder: 'URL' },
        ]}
      />

      <Field.Text
        label="Hero-bild"
        value={state.heroImageUrl}
        onChange={(v) => set('heroImageUrl', v)}
        placeholder="https://wexoe.se/wp-content/uploads/..."
      />

      <div className="grid grid-cols-2 gap-2">
        <Field.Text
          label="Stat-nummer"
          value={state.statNumber}
          onChange={(v) => set('statNumber', v.replace(/[^0-9]/g, ''))}
          placeholder="250"
        />
        <Field.Text
          label="Stat-etikett"
          value={state.statLabel}
          onChange={(v) => set('statLabel', v)}
          placeholder="kunder i Norden"
        />
      </div>
    </>
  );
}
