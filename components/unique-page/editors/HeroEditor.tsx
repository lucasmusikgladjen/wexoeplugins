'use client';

import { HeroState } from '@/lib/unique-page-types';
import CollapsibleSection, { FieldRow, TextInput, TextareaInput, SelectInput } from './CollapsibleSection';

interface Props {
  state: HeroState;
  onChange: (s: HeroState) => void;
  visible: boolean;
  onToggleVisible: (v: boolean) => void;
}

export default function HeroEditor({ state, onChange, visible, onToggleVisible }: Props) {
  const set = <K extends keyof HeroState>(k: K, v: HeroState[K]) => onChange({ ...state, [k]: v });

  return (
    <CollapsibleSection title="Hero" visible={visible} onToggleVisible={onToggleVisible}>
      <FieldRow label="Eyebrow"><TextInput value={state.eyebrow} onChange={(v) => set('eyebrow', v)} /></FieldRow>
      <FieldRow label="H1 Override" help="Tom = använd top-level H1.">
        <TextInput value={state.h1Override} onChange={(v) => set('h1Override', v)} />
      </FieldRow>
      <FieldRow label="Subtitle"><TextareaInput value={state.subtitle} onChange={(v) => set('subtitle', v)} rows={3} /></FieldRow>
      <FieldRow label="Bild-URL"><TextInput value={state.imageUrl} onChange={(v) => set('imageUrl', v)} placeholder="https://..." /></FieldRow>
      <FieldRow label="CTA-text"><TextInput value={state.ctaText} onChange={(v) => set('ctaText', v)} /></FieldRow>
      <FieldRow label="CTA-URL"><TextInput value={state.ctaUrl} onChange={(v) => set('ctaUrl', v)} placeholder="/kontakt" /></FieldRow>
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
