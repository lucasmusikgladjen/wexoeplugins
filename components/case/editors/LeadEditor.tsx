'use client';

import { CaseState } from '@/lib/case-types';
import { Field } from '@/components/shared/fields';
import type { SectionEditorProps } from '@/lib/page-types/types';

export default function LeadEditor({ state, onChange }: SectionEditorProps<CaseState>) {
  const set = <K extends keyof CaseState>(key: K, value: CaseState[K]) =>
    onChange({ ...state, [key]: value });

  return (
    <>
      <Field.Text
        label="Hero-bild (URL)"
        value={state.leadImageUrl}
        onChange={(v) => set('leadImageUrl', v)}
        placeholder="https://wexoe.se/wp-content/uploads/..."
      />
      <Field.Text
        label="Bildtext"
        value={state.leadImageCaption}
        onChange={(v) => set('leadImageCaption', v)}
        placeholder="T.ex. Produktionslinjen efter modernisering, oktober 2025."
      />
      <Field.RichText
        label="Ingresstext"
        value={state.leadParagraph}
        onChange={(v) => set('leadParagraph', v)}
        rows={8}
        hint="Markdown stöds. Drop-cap appliceras automatiskt på första tecknet av PHP-pluginet."
        placeholder="När Arlas produktionsanläggning i Götene stod inför ett systembyte…"
      />
    </>
  );
}
