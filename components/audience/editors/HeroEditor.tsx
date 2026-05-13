'use client';

import { AudienceState } from '@/lib/audience-types';
import { FieldInput, FieldTextarea, RichTextarea } from '@/components/editors/FieldInput';
import ButtonFieldset from '@/components/editors/ButtonFieldset';

interface Props {
  state: AudienceState;
  setField: <K extends keyof AudienceState>(key: K, value: AudienceState[K]) => void;
}

export default function HeroEditor({ state, setField }: Props) {
  return (
    <div className="space-y-3">
      <h3 className="text-xl font-bold text-gray-900">Hero</h3>

      <FieldInput
        label="Eyebrow"
        value={state.eyebrow}
        onChange={(v) => setField('eyebrow', v)}
        placeholder="T.ex. AUTOMATION FÖR TILLVERKARE"
      />

      <RichTextarea
        label="Titel"
        value={state.title}
        onChange={(v) => setField('title', v)}
        rows={3}
        hint="*ord* för orange highlight"
        placeholder="Framtidens *automation* för din fabrik"
      />

      <FieldTextarea
        label="Beskrivning"
        value={state.description}
        onChange={(v) => setField('description', v)}
        rows={4}
        placeholder="Kort beskrivning under titeln…"
      />

      <ButtonFieldset
        label="CTA-knapp"
        segments={[
          {
            value: state.ctaText,
            onChange: (v) => setField('ctaText', v),
            placeholder: 'Text',
          },
          {
            value: state.ctaUrl,
            onChange: (v) => setField('ctaUrl', v),
            placeholder: 'URL',
          },
        ]}
      />

      <FieldInput
        label="Hero-bild"
        value={state.heroImage}
        onChange={(v) => setField('heroImage', v)}
        placeholder="https://wexoe.se/wp-content/uploads/..."
      />

      <div className="grid grid-cols-2 gap-2">
        <FieldInput
          label="Stat-nummer"
          value={state.statNumber}
          onChange={(v) => setField('statNumber', v.replace(/[^0-9]/g, ''))}
          placeholder="250"
        />
        <FieldInput
          label="Stat-etikett"
          value={state.statLabel}
          onChange={(v) => setField('statLabel', v)}
          placeholder="kunder i Norden"
        />
      </div>
    </div>
  );
}
