'use client';

import { HeroState } from '@/lib/unique-page-types';
import { Field } from '@/components/shared/fields';
import EditorSection from '@/components/editors/EditorSection';

interface Props {
  state: HeroState;
  onChange: (s: HeroState) => void;
  visible: boolean;
  onToggleVisible: (v: boolean) => void;
}

export default function HeroEditor({ state, onChange, visible, onToggleVisible }: Props) {
  const set = <K extends keyof HeroState>(k: K, v: HeroState[K]) => onChange({ ...state, [k]: v });

  return (
    <EditorSection title="Hero" visible={visible} onToggleVisible={onToggleVisible}>
      <Field.Text label="Eyebrow" value={state.eyebrow} onChange={(v) => set('eyebrow', v)} />
      <Field.Text
        label="H1 Override"
        description="Tom = använd top-level H1."
        value={state.h1Override}
        onChange={(v) => set('h1Override', v)}
      />
      <Field.Textarea label="Subtitle" rows={3} value={state.subtitle} onChange={(v) => set('subtitle', v)} />
      <Field.Text label="Bild-URL" placeholder="https://..." value={state.imageUrl} onChange={(v) => set('imageUrl', v)} />
      <Field.Text label="CTA-text" value={state.ctaText} onChange={(v) => set('ctaText', v)} />
      <Field.Text label="CTA-URL" placeholder="/kontakt" value={state.ctaUrl} onChange={(v) => set('ctaUrl', v)} />
      <Field.Select<'dark' | 'light'>
        label="Tema"
        value={state.theme}
        onChange={(v) => set('theme', v)}
        options={[{ value: 'dark', label: 'Mörkt' }, { value: 'light', label: 'Ljust' }]}
      />
    </EditorSection>
  );
}
