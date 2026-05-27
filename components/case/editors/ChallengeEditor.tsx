'use client';

import { CaseState } from '@/lib/case-types';
import { Field } from '@/components/shared/fields';
import type { SectionEditorProps } from '@/lib/page-types/types';

export default function ChallengeEditor({ state, onChange }: SectionEditorProps<CaseState>) {
  const set = <K extends keyof CaseState>(key: K, value: CaseState[K]) =>
    onChange({ ...state, [key]: value });

  return (
    <>
      <Field.Text
        label="Eyebrow"
        value={state.challengeEyebrow}
        onChange={(v) => set('challengeEyebrow', v)}
        placeholder="Utmaningen"
      />

      <Field.Text
        label="Titel (H2)"
        value={state.challengeTitle}
        onChange={(v) => set('challengeTitle', v)}
        placeholder="T.ex. 22 år av lapptäcken — och inga reservdelar kvar"
      />

      <Field.RichText
        label="Brödtext"
        value={state.challengeText}
        onChange={(v) => set('challengeText', v)}
        rows={8}
        hint="Markdown stöds. Tom rad = ny paragraf."
        placeholder="Beskriv utmaningen kunden stod inför…"
      />

      <Field.Textarea
        label="Punktlista"
        value={state.challengeBullets}
        onChange={(v) => set('challengeBullets', v)}
        rows={5}
        hint="En punkt per rad. Renderas med pil-prefix."
        placeholder={'SLC 500-CPU:er över rekommenderad livslängd\nRecipe-data låst i proprietärt format'}
      />

      <Field.Text
        label="Bild (URL)"
        value={state.challengeImageUrl}
        onChange={(v) => set('challengeImageUrl', v)}
        placeholder="https://..."
      />
      <Field.Text
        label="Bildtext"
        value={state.challengeImageCaption}
        onChange={(v) => set('challengeImageCaption', v)}
        placeholder="T.ex. Det ursprungliga styrskåpet från 2003."
      />
    </>
  );
}
