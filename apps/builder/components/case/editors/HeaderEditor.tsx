'use client';

import { CaseState } from '@/lib/case-types';
import { Field } from '@/components/shared/fields';
import type { SectionEditorProps } from '@/lib/page-types/types';

export default function HeaderEditor({ state, onChange }: SectionEditorProps<CaseState>) {
  const set = <K extends keyof CaseState>(key: K, value: CaseState[K]) =>
    onChange({ ...state, [key]: value });

  return (
    <>
      <Field.Text
        label="Bransch / industri"
        value={state.industry}
        onChange={(v) => set('industry', v)}
        placeholder="T.ex. Livsmedelsindustri"
      />

      <Field.Textarea
        label="Titel (H1)"
        value={state.title}
        onChange={(v) => set('title', v)}
        rows={2}
        placeholder="T.ex. Från manuell omställning till 80 % snabbare batchbyten på Arla Götene"
      />

      <Field.Textarea
        label="Underrubrik"
        value={state.subtitle}
        onChange={(v) => set('subtitle', v)}
        rows={3}
        placeholder="Kort beskrivning under titeln…"
      />

      <div className="grid grid-cols-2 gap-2">
        <Field.Text
          label="Kund"
          value={state.customerName}
          onChange={(v) => set('customerName', v)}
          placeholder="Arla Foods AB"
        />
        <Field.Text
          label="Plats"
          value={state.location}
          onChange={(v) => set('location', v)}
          placeholder="Götene, Sverige"
        />
      </div>

      <div className="grid grid-cols-3 gap-2">
        <Field.Text
          label="År"
          value={state.projectYear}
          onChange={(v) => set('projectYear', v)}
          placeholder="2025"
        />
        <Field.Text
          label="Projekttyp"
          value={state.projectType}
          onChange={(v) => set('projectType', v)}
          placeholder="Retrofit"
        />
        <Field.Text
          label="Lästid"
          value={state.readingTime}
          onChange={(v) => set('readingTime', v)}
          placeholder="6 min"
        />
      </div>

      <Field.Textarea
        label="Header-loggor"
        value={state.headerLogos}
        onChange={(v) => set('headerLogos', v)}
        rows={4}
        hint="En URL per rad. Renderas i vita pill-bakgrunder på den mörka headern."
        placeholder={'https://wexoe.se/wp-content/uploads/arla-logo.png\nhttps://wexoe.se/wp-content/uploads/wexoe-logo.png'}
      />
    </>
  );
}
