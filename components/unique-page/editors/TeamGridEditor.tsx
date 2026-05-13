'use client';

import { TeamGridState } from '@/lib/unique-page-types';
import CollapsibleSection, { FieldRow, TextInput } from './CollapsibleSection';

interface Props {
  state: TeamGridState;
  onChange: (s: TeamGridState) => void;
  visible: boolean;
  onToggleVisible: (v: boolean) => void;
}

export default function TeamGridEditor({ state, onChange, visible, onToggleVisible }: Props) {
  const set = (v: TeamGridState) => onChange(v);

  return (
    <CollapsibleSection title="Team grid" hint="SSOT" visible={visible} onToggleVisible={onToggleVisible}>
      <FieldRow label="H2"><TextInput value={state.h2} onChange={(h2) => set({ ...state, h2 })} placeholder="Vårt team" /></FieldRow>
      <FieldRow label="Scope: Land (ISO-kod)" help="Tomt = använd sidans Country.">
        <TextInput
          value={state.scope.country}
          onChange={(country) => set({ ...state, scope: { ...state.scope, country } })}
          placeholder="SE"
        />
      </FieldRow>
      <FieldRow label="Scope: Division (slug)" help="Tomt = använd sidans Division.">
        <TextInput
          value={state.scope.division}
          onChange={(division) => set({ ...state, scope: { ...state.scope, division } })}
          placeholder="industri"
        />
      </FieldRow>
      <FieldRow label="Max antal" help="0 = obegränsat.">
        <TextInput
          value={String(state.scope.limit ?? 0)}
          onChange={(v) => set({ ...state, scope: { ...state.scope, limit: Number(v) || 0 } })}
        />
      </FieldRow>
    </CollapsibleSection>
  );
}
