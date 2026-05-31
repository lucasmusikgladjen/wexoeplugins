'use client';

import { PartnerPageState, emptyFaqItem } from '@/lib/partner-types';
import { Field } from '@/components/shared/fields';
import RepeaterCard from '@/components/shared/RepeaterCard';
import type { SectionEditorProps } from '@/lib/page-types/types';

export default function FaqEditor({ state, onChange }: SectionEditorProps<PartnerPageState>) {
  const set = <K extends keyof PartnerPageState>(key: K, value: PartnerPageState[K]) =>
    onChange({ ...state, [key]: value });

  const patchFaq = (i: number, patch: Partial<PartnerPageState['faqs'][number]>) => {
    set('faqs', state.faqs.map((f, idx) => (idx === i ? { ...f, ...patch } : f)));
  };

  const moveFaq = (i: number, direction: -1 | 1) => {
    const j = i + direction;
    if (j < 0 || j >= state.faqs.length) return;
    const next = [...state.faqs];
    const [m] = next.splice(i, 1);
    next.splice(j, 0, m);
    set('faqs', next);
  };

  const removeFaq = (i: number) => set('faqs', state.faqs.filter((_, idx) => idx !== i));

  const addFaq = () => set('faqs', [...state.faqs, emptyFaqItem()]);

  return (
    <>
      <Field.Text
        label="H2"
        value={state.faqH2}
        onChange={(v) => set('faqH2', v)}
        placeholder="T.ex. Frågor och svar om Rockwell hos Wexoe"
      />

      <div className="space-y-2 pt-2 mt-2 border-t border-gray-100">
        <p className="text-[11px] text-gray-400">Frågor och svar</p>
        {state.faqs.length === 0 && (
          <p className="text-[11px] text-gray-300 italic">Inga FAQ:er — klicka &quot;Lägg till&quot; nedan.</p>
        )}
        {state.faqs.map((faq, i) => (
          <RepeaterCard
            key={faq.clientId}
            index={i}
            title={faq.question}
            onTitleChange={(v) => patchFaq(i, { question: v })}
            titlePlaceholder="Frågetext…"
            onMoveUp={() => moveFaq(i, -1)}
            onMoveDown={() => moveFaq(i, 1)}
            canMoveUp={i > 0}
            canMoveDown={i < state.faqs.length - 1}
            onRemove={() => removeFaq(i)}
            removeTitle="Ta bort FAQ"
            defaultOpen={!faq.question.trim() && !faq.answer.trim()}
          >
            <Field.RichText
              label="Svar"
              value={faq.answer}
              onChange={(v) => patchFaq(i, { answer: v })}
              rows={5}
              placeholder="Svaret på frågan…"
            />
          </RepeaterCard>
        ))}
        <button
          type="button"
          onClick={addFaq}
          className="w-full py-2 text-sm text-gray-300 hover:text-gray-500 transition-colors"
        >
          + Lägg till FAQ
        </button>
      </div>
    </>
  );
}
