'use client';

import {
  CaseState,
  CaseQuickStat,
  CASE_QUICK_STATS_MAX,
} from '@/lib/case-types';
import { Field } from '@/components/shared/fields';
import RepeaterCard from '@/components/shared/RepeaterCard';
import type { SectionEditorProps } from '@/lib/page-types/types';

const emptyStat = (): CaseQuickStat => ({ value: '', label: '' });
const hasContent = (s: CaseQuickStat) => !!s.value.trim() || !!s.label.trim();

export default function StatsStripEditor({ state, onChange }: SectionEditorProps<CaseState>) {
  const stats = state.quickStats;
  const setStats = (next: CaseQuickStat[]) => onChange({ ...state, quickStats: next });

  const patch = (i: number, p: Partial<CaseQuickStat>) =>
    setStats(stats.map((s, idx) => (idx === i ? { ...s, ...p } : s)));

  const move = (i: number, dir: -1 | 1) => {
    const next = [...stats];
    const j = i + dir;
    if (j < 0 || j >= next.length) return;
    [next[i], next[j]] = [next[j], next[i]];
    setStats(next);
  };

  const remove = (i: number) => setStats(stats.filter((_, idx) => idx !== i));
  const add = () => {
    if (stats.length >= CASE_QUICK_STATS_MAX) return;
    setStats([...stats, emptyStat()]);
  };

  return (
    <>
      <p className="text-[11px] text-gray-400">
        Rad med snabba siffror under hero-bilden. Max {CASE_QUICK_STATS_MAX} items.
        Visas bara om togglen ovan är på och minst ett item är ifyllt.
      </p>

      {stats.map((s, i) => (
        <RepeaterCard
          key={i}
          index={i}
          title={s.value}
          onTitleChange={(v) => patch(i, { value: v })}
          titlePlaceholder="80 %"
          onMoveUp={() => move(i, -1)}
          onMoveDown={() => move(i, 1)}
          canMoveUp={i > 0}
          canMoveDown={i < stats.length - 1}
          onRemove={() => remove(i)}
          removeTitle="Ta bort siffra"
          defaultOpen={!hasContent(s)}
        >
          <Field.Text
            label="Värde"
            value={s.value}
            onChange={(v) => patch(i, { value: v })}
            placeholder="80 %"
          />
          <Field.Text
            label="Etikett"
            value={s.label}
            onChange={(v) => patch(i, { label: v })}
            placeholder="snabbare batchbyten"
          />
        </RepeaterCard>
      ))}

      {stats.length < CASE_QUICK_STATS_MAX && (
        <button
          type="button"
          onClick={add}
          className="w-full py-2 text-sm text-gray-300 hover:text-gray-500 transition-colors"
        >
          + Lägg till siffra ({stats.length} / {CASE_QUICK_STATS_MAX})
        </button>
      )}
    </>
  );
}
