'use client';

import {
  CaseState,
  CaseResult,
  CASE_RESULTS_MAX,
} from '@/lib/case-types';
import { Field } from '@/components/shared/fields';
import RepeaterCard from '@/components/shared/RepeaterCard';
import type { SectionEditorProps } from '@/lib/page-types/types';

const emptyResult = (): CaseResult => ({ value: '', label: '' });
const hasContent = (r: CaseResult) => !!r.value.trim() || !!r.label.trim();

export default function ResultsEditor({ state, onChange }: SectionEditorProps<CaseState>) {
  const set = <K extends keyof CaseState>(key: K, value: CaseState[K]) =>
    onChange({ ...state, [key]: value });

  const results = state.results;
  const setResults = (next: CaseResult[]) => set('results', next);

  const patch = (i: number, p: Partial<CaseResult>) =>
    setResults(results.map((r, idx) => (idx === i ? { ...r, ...p } : r)));

  const move = (i: number, dir: -1 | 1) => {
    const next = [...results];
    const j = i + dir;
    if (j < 0 || j >= next.length) return;
    [next[i], next[j]] = [next[j], next[i]];
    setResults(next);
  };

  const remove = (i: number) => setResults(results.filter((_, idx) => idx !== i));
  const add = () => {
    if (results.length >= CASE_RESULTS_MAX) return;
    setResults([...results, emptyResult()]);
  };

  return (
    <>
      <Field.Text
        label="Eyebrow"
        value={state.resultsEyebrow}
        onChange={(v) => set('resultsEyebrow', v)}
        placeholder="Resultatet"
      />

      <Field.Text
        label="Titel (H2)"
        value={state.resultsTitle}
        onChange={(v) => set('resultsTitle', v)}
        placeholder="T.ex. En produktionslinje som är snabbare, säkrare och spårbar i realtid"
      />

      <Field.RichText
        label="Brödtext"
        value={state.resultsText}
        onChange={(v) => set('resultsText', v)}
        rows={6}
        hint="Markdown stöds."
        placeholder="Beskriv resultaten…"
      />

      <p className="text-[11px] text-gray-400 mt-2">
        Resultat-siffror (max {CASE_RESULTS_MAX}). Visas större än stats-strippen i headern.
      </p>

      {results.map((r, i) => (
        <RepeaterCard
          key={i}
          index={i}
          title={r.value}
          onTitleChange={(v) => patch(i, { value: v })}
          titlePlaceholder="+80 %"
          onMoveUp={() => move(i, -1)}
          onMoveDown={() => move(i, 1)}
          canMoveUp={i > 0}
          canMoveDown={i < results.length - 1}
          onRemove={() => remove(i)}
          removeTitle="Ta bort resultat"
          defaultOpen={!hasContent(r)}
        >
          <Field.Text
            label="Värde"
            value={r.value}
            onChange={(v) => patch(i, { value: v })}
            placeholder="+80 %"
          />
          <Field.Text
            label="Etikett"
            value={r.label}
            onChange={(v) => patch(i, { label: v })}
            placeholder="Snabbare batchbyten"
          />
        </RepeaterCard>
      ))}

      {results.length < CASE_RESULTS_MAX && (
        <button
          type="button"
          onClick={add}
          className="w-full py-2 text-sm text-gray-300 hover:text-gray-500 transition-colors"
        >
          + Lägg till resultat ({results.length} / {CASE_RESULTS_MAX})
        </button>
      )}
    </>
  );
}
