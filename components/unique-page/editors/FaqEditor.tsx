'use client';

import { FaqState } from '@/lib/unique-page-types';
import CollapsibleSection, { FieldRow, TextInput, TextareaInput } from './CollapsibleSection';

interface Props {
  state: FaqState;
  onChange: (s: FaqState) => void;
  visible: boolean;
  onToggleVisible: (v: boolean) => void;
}

export default function FaqEditor({ state, onChange, visible, onToggleVisible }: Props) {
  const set = <K extends keyof FaqState>(k: K, v: FaqState[K]) => onChange({ ...state, [k]: v });
  return (
    <CollapsibleSection title="FAQ" visible={visible} onToggleVisible={onToggleVisible}>
      <FieldRow label="H2"><TextInput value={state.h2} onChange={(v) => set('h2', v)} placeholder="Vanliga frågor" /></FieldRow>
      <FieldRow label="FAQ-poster" help="En rad per Q&A. Format: **Fråga** | Svar">
        <TextareaInput
          value={state.items}
          onChange={(v) => set('items', v)}
          rows={8}
          placeholder={'**Vad gör Wexoe?** | Vi är experter på industriell automation.\n**Var finns ni?** | Stockholm och Göteborg.'}
        />
      </FieldRow>
    </CollapsibleSection>
  );
}
