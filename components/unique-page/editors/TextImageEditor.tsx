'use client';

import { TextImageState } from '@/lib/unique-page-types';
import { Field } from '@/components/shared/fields';
import EditorSection from '@/components/editors/EditorSection';

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
    <EditorSection title={title} visible={visible} onToggleVisible={onToggleVisible}>
      <Field.Text label="H2" value={state.h2} onChange={(v) => set('h2', v)} />
      <Field.Textarea
        label="Brödtext"
        description="Stödjer markdown."
        rows={5}
        value={state.body}
        onChange={(v) => set('body', v)}
      />
      <Field.Text label="Bild-URL" value={state.imageUrl} onChange={(v) => set('imageUrl', v)} />
      <Field.Checkbox
        label="Bild till vänster (annars höger)"
        checked={state.reversed}
        onChange={(v) => set('reversed', v)}
      />
      <Field.Select<'light' | 'dark'>
        label="Tema"
        value={state.theme}
        onChange={(v) => set('theme', v)}
        options={[{ value: 'light', label: 'Ljust' }, { value: 'dark', label: 'Mörkt' }]}
      />
    </EditorSection>
  );
}
