'use client';

import { CtaBannerState } from '@/lib/unique-page-types';
import CollapsibleSection, { FieldRow, TextInput, TextareaInput, SelectInput } from './CollapsibleSection';

interface Props {
  state: CtaBannerState;
  onChange: (s: CtaBannerState) => void;
  visible: boolean;
  onToggleVisible: (v: boolean) => void;
}

export default function CtaBannerEditor({ state, onChange, visible, onToggleVisible }: Props) {
  const set = <K extends keyof CtaBannerState>(k: K, v: CtaBannerState[K]) => onChange({ ...state, [k]: v });
  return (
    <CollapsibleSection title="CTA-banner" visible={visible} onToggleVisible={onToggleVisible}>
      <FieldRow label="H2"><TextInput value={state.h2} onChange={(v) => set('h2', v)} /></FieldRow>
      <FieldRow label="Brödtext"><TextareaInput value={state.body} onChange={(v) => set('body', v)} rows={3} /></FieldRow>
      <FieldRow label="CTA-text"><TextInput value={state.ctaText} onChange={(v) => set('ctaText', v)} /></FieldRow>
      <FieldRow label="CTA-URL"><TextInput value={state.ctaUrl} onChange={(v) => set('ctaUrl', v)} /></FieldRow>
      <FieldRow label="Tema">
        <SelectInput
          value={state.theme}
          onChange={(v) => set('theme', v)}
          options={[{ value: 'dark', label: 'Mörkt' }, { value: 'light', label: 'Ljust' }]}
        />
      </FieldRow>
    </CollapsibleSection>
  );
}
