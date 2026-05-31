'use client';

import { useState } from 'react';
import { ProductAreaState, NormalSection } from '@/lib/product-area-types';
import { Field } from '@/components/shared/fields';
import RepeaterCard from '@/components/shared/RepeaterCard';
import type { SectionEditorProps } from '@/lib/page-types/types';

/** True when a block has *any* content filled in — used to decide whether to
 *  auto-reveal and default-expand it. */
function hasContent(n: NormalSection): boolean {
  return !!(n.h2.trim() || n.text.trim() || n.bullets.trim() || n.image.trim());
}

export default function ContentBlocksEditor({ state, onChange }: SectionEditorProps<ProductAreaState>) {
  const sections = [state.normal1, state.normal2, state.normal3, state.normal4];

  const setNormal = (n: 1 | 2 | 3 | 4, patch: Partial<NormalSection>) => {
    const key = `normal${n}` as 'normal1' | 'normal2' | 'normal3' | 'normal4';
    onChange({ ...state, [key]: { ...state[key], ...patch } });
  };

  // Auto-reveal enough slots on mount to cover all filled blocks — even if
  // some middle blocks are empty (mirrors how the PHP plugin skips empty
  // Normal sections individually). After mount, visibility is user-driven.
  const [visibleCount, setVisibleCount] = useState(() => {
    const highestFilled = sections.reduce(
      (max, s, i) => (hasContent(s) ? i : max),
      -1,
    );
    return Math.max(1, highestFilled + 1);
  });

  const addBlock = () => setVisibleCount((v) => Math.min(4, v + 1));

  const clearBlock = (index: number) => {
    const n = (index + 1) as 1 | 2 | 3 | 4;
    setNormal(n, {
      h2: '', text: '', bullets: '', image: '', reversed: false, bg: '', upp: false,
    });
    if (index === visibleCount - 1 && visibleCount > 1) {
      setVisibleCount((v) => v - 1);
    }
  };

  return (
    <>
      {Array.from({ length: visibleCount }).map((_, i) => {
        const n = (i + 1) as 1 | 2 | 3 | 4;
        const section = sections[i];
        return (
          <RepeaterCard
            key={n}
            index={i}
            title={section.h2}
            onTitleChange={(v) => setNormal(n, { h2: v })}
            titlePlaceholder="Rubrik på sektionen…"
            onRemove={() => clearBlock(i)}
            removeTitle="Ta bort sektion"
            defaultOpen={!hasContent(section)}
          >
            <Field.RichText
              label="Brödtext"
              value={section.text}
              onChange={(v) => setNormal(n, { text: v })}
              rows={6}
              placeholder="Beskrivande text för sektionen…"
            />
            <Field.RichText
              label="Punkter"
              value={section.bullets}
              onChange={(v) => setNormal(n, { bullets: v })}
              rows={6}
              hint="en per rad"
              placeholder={'Första punkten\nAndra punkten'}
            />
            <Field.Text
              label="Bild"
              value={section.image}
              onChange={(v) => setNormal(n, { image: v })}
              placeholder="https://..."
            />
            <div className="flex items-center gap-5 pt-0.5">
              <Field.Checkbox
                label="Bild till vänster"
                checked={section.reversed}
                onChange={(v) => setNormal(n, { reversed: v })}
              />
              <Field.Checkbox
                label="Före produkter"
                checked={section.upp}
                onChange={(v) => setNormal(n, { upp: v })}
              />
            </div>
            <Field.Color
              label="Bakgrundsfärg"
              value={section.bg}
              onChange={(v) => setNormal(n, { bg: v })}
              defaultColor={i % 2 === 1 ? '#F8F9FA' : '#FFFFFF'}
            />
          </RepeaterCard>
        );
      })}

      {visibleCount < 4 && (
        <button
          type="button"
          onClick={addBlock}
          className="w-full py-2 text-sm text-gray-300 hover:text-gray-500 transition-colors"
        >
          + Lägg till sektion
        </button>
      )}
    </>
  );
}
