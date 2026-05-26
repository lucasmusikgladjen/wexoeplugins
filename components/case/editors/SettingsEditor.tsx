'use client';

import { CaseState } from '@/lib/case-types';
import { Field } from '@/components/shared/fields';
import type { SectionEditorProps } from '@/lib/page-types/types';

export default function SettingsEditor({ state, onChange }: SectionEditorProps<CaseState>) {
  const set = <K extends keyof CaseState>(key: K, value: CaseState[K]) =>
    onChange({ ...state, [key]: value });

  return (
    <>
      <Field.Checkbox
        label="Aktiv (publicerad)"
        checked={state.isActive}
        onChange={(v) => set('isActive', v)}
      />

      <Field.Textarea
        label="Intern anteckning"
        value={state.internalNotes}
        onChange={(v) => set('internalNotes', v)}
        rows={4}
        hint="Visas aldrig publikt. Använd för redaktionella notater (status, källor, etc.)."
        placeholder="T.ex. Bilderna är från fotograf X, juli 2025…"
      />
    </>
  );
}
