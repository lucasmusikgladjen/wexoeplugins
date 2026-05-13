'use client';

import { TextImageState } from '@/lib/unique-page-types';
import CollapsibleSection, { FieldRow, TextInput, TextareaInput, SelectInput, CheckboxInput } from './CollapsibleSection';

interface Props {
  title: string;
  state: TextImageState;
  onChange: (s: TextImageState) => void;
  visible: boolean;
  onToggleVisible: (v: boolean) => void;
}

export default function TextImageEditor({ title, state, onChange, visible, onToggleVisible }: Props) {
  const set = <K extends keyof TextImageState>(k: K, v: TextImageState[K]) => onChange({ ...state, [k]: v });
  return (
    <CollapsibleSection title={title} visible={visible} onToggleVisible={onToggleVisible}>
      <FieldRow label="H2"><TextInput value={state.h2} onChange={(v) => set('h2', v)} /></FieldRow>
      <FieldRow label="Brödtext" help="Stödjer markdown.">
        <TextareaInput value={state.body} onChange={(v) => set('body', v)} rows={5} />
      </FieldRow>
      <FieldRow label="Bild-URL"><TextInput value={state.imageUrl} onChange={(v) => set('imageUrl', v)} /></FieldRow>
      <FieldRow label="Layout">
        <CheckboxInput
          checked={state.reversed}
          onChange={(v) => set('reversed', v)}
          label="Bild till vänster (annars höger)"
        />
      </FieldRow>
      <FieldRow label="Tema">
        <SelectInput
          value={state.theme}
          onChange={(v) => set('theme', v)}
          options={[{ value: 'light', label: 'Ljust' }, { value: 'dark', label: 'Mörkt' }]}
        />
      </FieldRow>
    </CollapsibleSection>
  );
}
