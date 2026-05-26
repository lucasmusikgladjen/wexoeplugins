'use client';

import { CaseState } from '@/lib/case-types';
import { Field } from '@/components/shared/fields';
import type { SectionEditorProps } from '@/lib/page-types/types';

export default function GlanceEditor({ state, onChange }: SectionEditorProps<CaseState>) {
  const set = <K extends keyof CaseState>(key: K, value: CaseState[K]) =>
    onChange({ ...state, [key]: value });

  return (
    <>
      <p className="text-[11px] text-gray-400">
        Den sticky sidebaren som följer läsaren genom hela artikeln. Korta meningar — markdown inline stöds.
      </p>

      <Field.Textarea
        label="Utmaning"
        value={state.glanceChallenge}
        onChange={(v) => set('glanceChallenge', v)}
        rows={3}
        placeholder="T.ex. 22 år gammalt SLC 500-system, end-of-life sedan 2017…"
      />

      <Field.Textarea
        label="Lösning"
        value={state.glanceSolution}
        onChange={(v) => set('glanceSolution', v)}
        rows={3}
        placeholder="T.ex. Fasad ControlLogix-migration med shadow-PLC-parallelldrift…"
      />

      <Field.Textarea
        label="Resultat"
        value={state.glanceResult}
        onChange={(v) => set('glanceResult', v)}
        rows={3}
        placeholder="T.ex. 80 % snabbare batchbyten, noll missade skift…"
      />
    </>
  );
}
