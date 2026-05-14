'use client';

import { TestimonialCardState } from '@/lib/unique-page-types';
import { Field } from '@/components/shared/fields';
import EditorSection from '@/components/editors/EditorSection';

interface Props {
  state: TestimonialCardState;
  onChange: (s: TestimonialCardState) => void;
  visible: boolean;
  onToggleVisible: (v: boolean) => void;
}

export default function TestimonialCardEditor({ state, onChange, visible, onToggleVisible }: Props) {
  const set = (v: TestimonialCardState) => onChange(v);
  return (
    <EditorSection title="Citat" visible={visible} onToggleVisible={onToggleVisible}>
      <Field.Text
        label="Scope: Kundtyp (slug)"
        value={state.scope.customerType ?? ''}
        onChange={(customerType) => set({ ...state, scope: { ...state.scope, customerType } })}
      />
      <Field.Text
        label="Scope: Division (slug)"
        value={state.scope.division}
        onChange={(division) => set({ ...state, scope: { ...state.scope, division } })}
      />
      <Field.Text
        label="Scope: Land (ISO-kod)"
        value={state.scope.country}
        onChange={(country) => set({ ...state, scope: { ...state.scope, country } })}
      />
    </EditorSection>
  );
}
