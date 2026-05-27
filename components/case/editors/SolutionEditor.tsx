'use client';

import { CaseState } from '@/lib/case-types';
import { Field } from '@/components/shared/fields';
import type { SectionEditorProps } from '@/lib/page-types/types';

export default function SolutionEditor({ state, onChange }: SectionEditorProps<CaseState>) {
  const set = <K extends keyof CaseState>(key: K, value: CaseState[K]) =>
    onChange({ ...state, [key]: value });

  return (
    <>
      <Field.Text
        label="Eyebrow"
        value={state.solutionEyebrow}
        onChange={(v) => set('solutionEyebrow', v)}
        placeholder="Lösningen"
      />

      <Field.Text
        label="Titel (H2)"
        value={state.solutionTitle}
        onChange={(v) => set('solutionTitle', v)}
        placeholder="T.ex. Fasad migration med parallelldrift som säkerhetsnät"
      />

      <Field.RichText
        label="Brödtext"
        value={state.solutionText}
        onChange={(v) => set('solutionText', v)}
        rows={10}
        hint="Markdown stöds. Tom rad = ny paragraf."
        placeholder="Beskriv lösningen i detalj…"
      />

      <Field.Text
        label="Arkitekturbild (URL)"
        value={state.solutionImageUrl}
        onChange={(v) => set('solutionImageUrl', v)}
        placeholder="https://..."
        description="Renderas nedanför produktlistan på den publika sidan."
      />
      <Field.Text
        label="Bildtext"
        value={state.solutionImageCaption}
        onChange={(v) => set('solutionImageCaption', v)}
        placeholder="T.ex. Den nya arkitekturen: ControlLogix för process…"
      />
    </>
  );
}
