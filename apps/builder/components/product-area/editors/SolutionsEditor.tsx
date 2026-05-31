'use client';

import { ProductAreaState, LinkedSolution, emptyLinkedSolution } from '@/lib/product-area-types';
import { Field } from '@/components/shared/fields';
import RepeaterCard from '@/components/shared/RepeaterCard';
import type { SectionEditorProps } from '@/lib/page-types/types';

function hasContent(s: LinkedSolution): boolean {
  return !!s.name.trim();
}

export default function SolutionsEditor({ state, onChange }: SectionEditorProps<ProductAreaState>) {
  const set = <K extends keyof ProductAreaState>(key: K, value: ProductAreaState[K]) =>
    onChange({ ...state, [key]: value });

  const patchSolution = (index: number, patch: Partial<LinkedSolution>) => {
    set('solutions', state.solutions.map((s, i) => (i === index ? { ...s, ...patch } : s)));
  };

  const moveSolution = (index: number, direction: -1 | 1) => {
    const newIndex = index + direction;
    if (newIndex < 0 || newIndex >= state.solutions.length) return;
    const next = [...state.solutions];
    const [moved] = next.splice(index, 1);
    next.splice(newIndex, 0, moved);
    set('solutions', next);
  };

  const removeSolution = (index: number) => {
    set('solutions', state.solutions.filter((_, i) => i !== index));
  };

  const addSolution = () => {
    const next = emptyLinkedSolution();
    next.order = state.solutions.length + 1;
    next.name = '';
    set('solutions', [...state.solutions, next]);
  };

  return (
    <>
      <Field.Text label="Rubrik" value={state.solutionsTitle} onChange={(v) => set('solutionsTitle', v)} placeholder="Lösningar & koncept" />

      {state.solutions.map((sol, i) => (
        <RepeaterCard
          key={sol.clientId}
          index={i}
          title={sol.name}
          onTitleChange={(v) => patchSolution(i, { name: v })}
          titlePlaceholder="Lösningens namn…"
          onMoveUp={() => moveSolution(i, -1)}
          onMoveDown={() => moveSolution(i, 1)}
          canMoveUp={i > 0}
          canMoveDown={i < state.solutions.length - 1}
          onRemove={() => removeSolution(i)}
          removeTitle="Ta bort lösning"
          defaultOpen={!hasContent(sol)}
        >
          <Field.Text label="Kategori" value={sol.category} onChange={(v) => patchSolution(i, { category: v })} placeholder="T.ex. Koncept" />
          <Field.RichText label="Beskrivning" value={sol.description} onChange={(v) => patchSolution(i, { description: v })} rows={6} placeholder="Kort beskrivning av lösningen…" />
          <Field.Text label="Bild" value={sol.image} onChange={(v) => patchSolution(i, { image: v })} placeholder="https://..." />
          <div className="grid grid-cols-2 gap-2">
            <Field.Text label="Länk" value={sol.url} onChange={(v) => patchSolution(i, { url: v })} placeholder="/losningar/..." />
            <Field.Text label="Knapptext" value={sol.ctaText} onChange={(v) => patchSolution(i, { ctaText: v })} placeholder="Läs mer" />
          </div>
          <Field.Checkbox label="Visa" checked={sol.visa} onChange={(v) => patchSolution(i, { visa: v })} />
        </RepeaterCard>
      ))}

      <button
        type="button"
        onClick={addSolution}
        className="w-full py-2 text-sm text-gray-300 hover:text-gray-500 transition-colors"
      >
        + Lägg till lösning
      </button>

      <div className="grid grid-cols-2 gap-2">
        <Field.Color label="Bakgrundsfärg" value={state.solutionsBg} onChange={(v) => set('solutionsBg', v)} defaultColor="#FFFFFF" />
        <Field.Color label="Kortbakgrundsfärg" value={state.solutionsCardBg} onChange={(v) => set('solutionsCardBg', v)} defaultColor="#FFFFFF" />
      </div>
    </>
  );
}
