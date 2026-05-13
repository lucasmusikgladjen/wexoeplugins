'use client';

import { AudienceState } from '@/lib/audience-types';
import { FieldInput, RichTextarea, FieldCheckbox } from '@/components/editors/FieldInput';

interface Props {
  state: AudienceState;
  setField: <K extends keyof AudienceState>(key: K, value: AudienceState[K]) => void;
  visible: boolean;
  onToggleVisible: (v: boolean) => void;
}

export default function ValueEditor({ state, setField, visible, onToggleVisible }: Props) {
  return (
    <div className="space-y-3">
      <div className="flex items-center justify-between">
        <h3 className="text-xl font-bold text-gray-900">Värdeproposition</h3>
        <FieldCheckbox label="Visa" checked={visible} onChange={onToggleVisible} />
      </div>

      {visible && (
        <>
          <FieldInput
            label="H2"
            value={state.valueH2}
            onChange={(v) => setField('valueH2', v)}
            placeholder="Därför väljer 250+ tillverkare oss"
          />

          <RichTextarea
            label="Brödtext 1"
            value={state.valueText1}
            onChange={(v) => setField('valueText1', v)}
            rows={5}
            placeholder="Längre paragraf om värdet…"
          />

          <RichTextarea
            label="Brödtext 2"
            value={state.valueText2}
            onChange={(v) => setField('valueText2', v)}
            rows={5}
            placeholder="Andra paragrafen (valfri)…"
          />

          <RichTextarea
            label="Benefit 1"
            value={state.benefit1}
            onChange={(v) => setField('benefit1', v)}
            rows={2}
            hint="**ord** för markerad text"
            placeholder="**Snabb leverans** från lokalt lager"
          />
          <RichTextarea
            label="Benefit 2"
            value={state.benefit2}
            onChange={(v) => setField('benefit2', v)}
            rows={2}
            hint="**ord** för markerad text"
            placeholder="**Personlig support** av automationsexperter"
          />
          <RichTextarea
            label="Benefit 3"
            value={state.benefit3}
            onChange={(v) => setField('benefit3', v)}
            rows={2}
            hint="**ord** för markerad text"
            placeholder="**Lång erfarenhet** av nordisk industri"
          />
        </>
      )}
    </div>
  );
}
