'use client';

import { PartnersMarqueeState } from '@/lib/unique-page-types';
import CollapsibleSection, { FieldRow, TextInput } from './CollapsibleSection';

interface Props {
  state: PartnersMarqueeState;
  onChange: (s: PartnersMarqueeState) => void;
  visible: boolean;
  onToggleVisible: (v: boolean) => void;
}

export default function PartnersMarqueeEditor({ state, onChange, visible, onToggleVisible }: Props) {
  const set = (v: PartnersMarqueeState) => onChange(v);
  return (
    <CollapsibleSection title="Partners (marquee)" hint="SSOT" visible={visible} onToggleVisible={onToggleVisible}>
      <FieldRow label="H2"><TextInput value={state.h2} onChange={(h2) => set({ ...state, h2 })} placeholder="Våra partners" /></FieldRow>
      <FieldRow label="Scope: Land (ISO-kod)" help="Tomt = använd sidans Country.">
        <TextInput
          value={state.scope.country}
          onChange={(country) => set({ ...state, scope: { ...state.scope, country } })}
        />
      </FieldRow>
      <FieldRow label="Scope: Division (slug)">
        <TextInput
          value={state.scope.division}
          onChange={(division) => set({ ...state, scope: { ...state.scope, division } })}
        />
      </FieldRow>
    </CollapsibleSection>
  );
}
