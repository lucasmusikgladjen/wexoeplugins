'use client';

import {
  PartnerPageState,
  PartnerQuickFact,
  QuickFactIcon,
  QUICK_FACT_ICON_KEYS,
} from '@/lib/partner-types';
import { Field } from '@/components/shared/fields';
import type { SectionEditorProps } from '@/lib/page-types/types';

/**
 * Quick-facts-sektionen har 4 fasta slots — schemat lagrar dem som
 * `facts_1_icon`..`facts_4_label`. Pluginet hoppar över slots där alla
 * tre fält är tomma, så marknadsföraren kan lämna luckor.
 *
 * Ikon-keyn är en dropdown med 10 låsta val + tomt (= ingen ikon). Listan
 * speglar `wexoe-partner-page/src/Renderer.php::$icons` — uppdatera båda
 * vid utökning.
 */

const ICON_OPTIONS: readonly { value: QuickFactIcon; label: string }[] = [
  { value: '', label: '— Ingen ikon —' },
  ...QUICK_FACT_ICON_KEYS.map((k) => ({ value: k, label: k })),
];

export default function QuickFactsEditor({
  state,
  onChange,
}: SectionEditorProps<PartnerPageState>) {
  const patchFact = (index: number, patch: Partial<PartnerQuickFact>) => {
    onChange({
      ...state,
      facts: state.facts.map((f, i) => (i === index ? { ...f, ...patch } : f)),
    });
  };

  return (
    <div className="space-y-4">
      <p className="text-[11px] text-gray-400">
        4 fasta slots. Tomma slots döljs i renderingen — fyll bara i de du behöver.
      </p>

      {state.facts.map((fact, i) => (
        <div
          key={i}
          className="border border-gray-100 rounded-md p-3 space-y-2 bg-gray-50/40"
        >
          <div className="text-[11px] uppercase tracking-wider text-gray-400">
            Slot {i + 1}
          </div>

          <Field.Select<QuickFactIcon>
            label="Ikon"
            value={fact.icon}
            onChange={(v) => patchFact(i, { icon: v })}
            options={ICON_OPTIONS}
          />

          <div className="grid grid-cols-2 gap-2">
            <Field.Text
              label="Värde"
              value={fact.value}
              onChange={(v) => patchFact(i, { value: v })}
              placeholder="T.ex. 1903"
            />
            <Field.Text
              label="Etikett"
              value={fact.label}
              onChange={(v) => patchFact(i, { label: v })}
              placeholder="T.ex. Grundat"
            />
          </div>
        </div>
      ))}
    </div>
  );
}
