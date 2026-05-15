'use client';

import { CustomerTypePageState } from '@/lib/customer-type-types';
import { Field } from '@/components/shared/fields';
import type { SectionEditorProps } from '@/lib/page-types/types';

export default function ValueEditor({ state, onChange }: SectionEditorProps<CustomerTypePageState>) {
  const set = <K extends keyof CustomerTypePageState>(key: K, value: CustomerTypePageState[K]) =>
    onChange({ ...state, [key]: value });

  return (
    <>
      <Field.Text
        label="H2"
        value={state.valueH2}
        onChange={(v) => set('valueH2', v)}
        placeholder="Därför väljer 250+ tillverkare oss"
      />

      <Field.RichText
        label="Brödtext 1"
        value={state.valueText1}
        onChange={(v) => set('valueText1', v)}
        rows={5}
        placeholder="Längre paragraf om värdet…"
      />

      <Field.RichText
        label="Brödtext 2"
        value={state.valueText2}
        onChange={(v) => set('valueText2', v)}
        rows={5}
        placeholder="Andra paragrafen (valfri)…"
      />

      <Field.RichText
        label="Benefit 1"
        value={state.benefit1}
        onChange={(v) => set('benefit1', v)}
        rows={2}
        hint="**ord** för markerad text"
        placeholder="**Snabb leverans** från lokalt lager"
      />
      <Field.RichText
        label="Benefit 2"
        value={state.benefit2}
        onChange={(v) => set('benefit2', v)}
        rows={2}
        hint="**ord** för markerad text"
        placeholder="**Personlig support** av automationsexperter"
      />
      <Field.RichText
        label="Benefit 3"
        value={state.benefit3}
        onChange={(v) => set('benefit3', v)}
        rows={2}
        hint="**ord** för markerad text"
        placeholder="**Lång erfarenhet** av nordisk industri"
      />
    </>
  );
}
