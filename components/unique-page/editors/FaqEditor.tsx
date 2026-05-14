'use client';

import { FaqState } from '@/lib/unique-page-types';
import { Field } from '@/components/shared/fields';
import EditorSection from '@/components/editors/EditorSection';

interface Props {
  state: FaqState;
  onChange: (s: FaqState) => void;
  visible: boolean;
  onToggleVisible: (v: boolean) => void;
}

export default function FaqEditor({ state, onChange, visible, onToggleVisible }: Props) {
  const set = <K extends keyof FaqState>(k: K, v: FaqState[K]) => onChange({ ...state, [k]: v });
  return (
    <EditorSection title="FAQ" visible={visible} onToggleVisible={onToggleVisible}>
      <Field.Text label="H2" placeholder="Vanliga frågor" value={state.h2} onChange={(v) => set('h2', v)} />
      <Field.Textarea
        label="FAQ-poster"
        description="En rad per Q&A. Format: **Fråga** | Svar"
        rows={8}
        value={state.items}
        onChange={(v) => set('items', v)}
        placeholder={'**Vad gör Wexoe?** | Vi är experter på industriell automation.\n**Var finns ni?** | Stockholm och Göteborg.'}
      />
    </EditorSection>
  );
}
