'use client';

import { TestimonialCardState } from '@/lib/unique-page-types';
import CollapsibleSection, { FieldRow, TextInput } from './CollapsibleSection';

interface Props {
  state: TestimonialCardState;
  onChange: (s: TestimonialCardState) => void;
  visible: boolean;
  onToggleVisible: (v: boolean) => void;
}

export default function TestimonialCardEditor({ state, onChange, visible, onToggleVisible }: Props) {
  const set = (v: TestimonialCardState) => onChange(v);
  return (
    <CollapsibleSection title="Citat" hint="SSOT" visible={visible} onToggleVisible={onToggleVisible}>
      <FieldRow label="Scope: Kundtyp (slug)">
        <TextInput
          value={state.scope.customerType ?? ''}
          onChange={(customerType) => set({ ...state, scope: { ...state.scope, customerType } })}
        />
      </FieldRow>
      <FieldRow label="Scope: Division (slug)">
        <TextInput
          value={state.scope.division}
          onChange={(division) => set({ ...state, scope: { ...state.scope, division } })}
        />
      </FieldRow>
      <FieldRow label="Scope: Land (ISO-kod)">
        <TextInput
          value={state.scope.country}
          onChange={(country) => set({ ...state, scope: { ...state.scope, country } })}
        />
      </FieldRow>
    </CollapsibleSection>
  );
}
