'use client';

import { TextOnlyState } from '@/lib/unique-page-types';
import { Field } from '@/components/shared/fields';
import EditorSection from '@/components/editors/EditorSection';

interface Props {
  state: TextOnlyState;
  onChange: (s: TextOnlyState) => void;
  visible: boolean;
  onToggleVisible: (v: boolean) => void;
}

export default function TextOnlyEditor({ state, onChange, visible, onToggleVisible }: Props) {
  const set = <K extends keyof TextOnlyState>(k: K, v: TextOnlyState[K]) => onChange({ ...state, [k]: v });
  return (
    <EditorSection title="Text" visible={visible} onToggleVisible={onToggleVisible}>
      <Field.Text label="H2" value={state.h2} onChange={(v) => set('h2', v)} />
      <Field.Textarea label="Brödtext" rows={5} value={state.body} onChange={(v) => set('body', v)} />
      <Field.Select<'left' | 'center'>
        label="Justering"
        value={state.align}
        onChange={(v) => set('align', v)}
        options={[{ value: 'left', label: 'Vänster' }, { value: 'center', label: 'Centrerat' }]}
      />
    </EditorSection>
  );
}
