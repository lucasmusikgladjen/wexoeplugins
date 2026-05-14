'use client';

import { PartnersMarqueeState } from '@/lib/unique-page-types';
import { Field } from '@/components/shared/fields';
import EditorSection from '@/components/editors/EditorSection';

interface Props {
  state: PartnersMarqueeState;
  onChange: (s: PartnersMarqueeState) => void;
  visible: boolean;
  onToggleVisible: (v: boolean) => void;
}

export default function PartnersMarqueeEditor({ state, onChange, visible, onToggleVisible }: Props) {
  const set = (v: PartnersMarqueeState) => onChange(v);
  return (
    <EditorSection title="Partners (marquee)" visible={visible} onToggleVisible={onToggleVisible}>
      <Field.Text label="H2" placeholder="Våra partners" value={state.h2} onChange={(h2) => set({ ...state, h2 })} />
      <Field.Text
        label="Scope: Land (ISO-kod)"
        description="Tomt = använd sidans Country."
        value={state.scope.country}
        onChange={(country) => set({ ...state, scope: { ...state.scope, country } })}
      />
      <Field.Text
        label="Scope: Division (slug)"
        value={state.scope.division}
        onChange={(division) => set({ ...state, scope: { ...state.scope, division } })}
      />
    </EditorSection>
  );
}
