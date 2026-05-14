'use client';

import { TeamGridState } from '@/lib/unique-page-types';
import { Field } from '@/components/shared/fields';
import EditorSection from '@/components/editors/EditorSection';

interface Props {
  state: TeamGridState;
  onChange: (s: TeamGridState) => void;
  visible: boolean;
  onToggleVisible: (v: boolean) => void;
}

export default function TeamGridEditor({ state, onChange, visible, onToggleVisible }: Props) {
  const set = (v: TeamGridState) => onChange(v);

  return (
    <EditorSection title="Team grid" visible={visible} onToggleVisible={onToggleVisible}>
      <Field.Text label="H2" placeholder="Vårt team" value={state.h2} onChange={(h2) => set({ ...state, h2 })} />
      <Field.Text
        label="Scope: Land (ISO-kod)"
        description="Tomt = använd sidans Country."
        placeholder="SE"
        value={state.scope.country}
        onChange={(country) => set({ ...state, scope: { ...state.scope, country } })}
      />
      <Field.Text
        label="Scope: Division (slug)"
        description="Tomt = använd sidans Division."
        placeholder="industri"
        value={state.scope.division}
        onChange={(division) => set({ ...state, scope: { ...state.scope, division } })}
      />
      <Field.Text
        label="Max antal"
        description="0 = obegränsat."
        value={String(state.scope.limit ?? 0)}
        onChange={(v) => set({ ...state, scope: { ...state.scope, limit: Number(v) || 0 } })}
      />
    </EditorSection>
  );
}
