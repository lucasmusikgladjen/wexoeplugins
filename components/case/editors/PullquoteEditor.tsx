'use client';

import { CaseState } from '@/lib/case-types';
import { Field } from '@/components/shared/fields';
import type { SectionEditorProps } from '@/lib/page-types/types';

export default function PullquoteEditor({ state, onChange }: SectionEditorProps<CaseState>) {
  const set = <K extends keyof CaseState>(key: K, value: CaseState[K]) =>
    onChange({ ...state, [key]: value });

  return (
    <>
      <Field.RichText
        label="Citat"
        value={state.pullquoteText}
        onChange={(v) => set('pullquoteText', v)}
        rows={4}
        hint="Markdown inline (**bold**, *italic*, [länk](url))."
        placeholder="T.ex. Det finns ingen heroisk lösning på ett 22 år gammalt system…"
      />
      <Field.Text
        label="Attribution"
        value={state.pullquoteAttribution}
        onChange={(v) => set('pullquoteAttribution', v)}
        placeholder="— Johan Berg, Lead Engineer Wexoe Industry"
      />
    </>
  );
}
