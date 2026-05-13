'use client';

import { TextOnlyState } from '@/lib/unique-page-types';
import CollapsibleSection, { FieldRow, TextInput, TextareaInput, SelectInput } from './CollapsibleSection';

interface Props {
  state: TextOnlyState;
  onChange: (s: TextOnlyState) => void;
  visible: boolean;
  onToggleVisible: (v: boolean) => void;
}

export default function TextOnlyEditor({ state, onChange, visible, onToggleVisible }: Props) {
  const set = <K extends keyof TextOnlyState>(k: K, v: TextOnlyState[K]) => onChange({ ...state, [k]: v });
  return (
    <CollapsibleSection title="Text" visible={visible} onToggleVisible={onToggleVisible}>
      <FieldRow label="H2"><TextInput value={state.h2} onChange={(v) => set('h2', v)} /></FieldRow>
      <FieldRow label="Brödtext"><TextareaInput value={state.body} onChange={(v) => set('body', v)} rows={5} /></FieldRow>
      <FieldRow label="Justering">
        <SelectInput
          value={state.align}
          onChange={(v) => set('align', v)}
          options={[{ value: 'left', label: 'Vänster' }, { value: 'center', label: 'Centrerat' }]}
        />
      </FieldRow>
    </CollapsibleSection>
  );
}
