'use client';

import { useState } from 'react';
import { ProductAreaState, NormalSection } from '@/lib/product-area-types';
import { FieldTextarea, FieldInput, FieldCheckbox, FieldColor } from '@/components/editors/FieldInput';
import CollapsibleCard from './CollapsibleCard';

interface Props {
  state: ProductAreaState;
  setNormal: (n: 1 | 2 | 3 | 4, patch: Partial<NormalSection>) => void;
  visible: boolean;
  onToggleVisible: (v: boolean) => void;
}

/** True when a block has *any* content filled in — used to decide whether to
 *  auto-reveal and default-expand it. */
function hasContent(n: NormalSection): boolean {
  return !!(n.h2.trim() || n.text.trim() || n.bullets.trim() || n.image.trim());
}

export default function ContentBlocksEditor({ state, setNormal, visible, onToggleVisible }: Props) {
  const sections = [state.normal1, state.normal2, state.normal3, state.normal4];

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
      h2: '',
      text: '',
      bullets: '',
      image: '',
      reversed: false,
      bg: '',
      upp: false,
    });
    // If the cleared block was the last visible one, collapse the slot away
    // so the user can undo an accidental "+ Lägg till" with one click.
    if (index === visibleCount - 1 && visibleCount > 1) {
      setVisibleCount((v) => v - 1);
    }
  };

  return (
    <div className="space-y-3">
      <div className="flex items-center justify-between">
        <h3 className="text-xl font-bold text-gray-900">Innehåll</h3>
        <FieldCheckbox label="Visa" checked={visible} onChange={onToggleVisible} />
      </div>

      {visible && Array.from({ length: visibleCount }).map((_, i) => {
        const n = (i + 1) as 1 | 2 | 3 | 4;
        const section = sections[i];
        return (
          <CollapsibleCard
            key={n}
            index={i}
            title={section.h2}
            onTitleChange={(v) => setNormal(n, { h2: v })}
            titlePlaceholder="Rubrik på sektionen…"
            onRemove={() => clearBlock(i)}
            removeTitle="Ta bort sektion"
            defaultOpen={!hasContent(section)}
          >
            <FieldTextarea
              label="Brödtext"
              value={section.text}
              onChange={(v) => setNormal(n, { text: v })}
              rows={3}
              placeholder="Beskrivande text för sektionen…"
            />

            <FieldTextarea
              label="Punkter"
              value={section.bullets}
              onChange={(v) => setNormal(n, { bullets: v })}
              rows={3}
              hint="en per rad"
              placeholder={'Första punkten\nAndra punkten'}
            />

            <FieldInput
              label="Bild"
              value={section.image}
              onChange={(v) => setNormal(n, { image: v })}
              placeholder="https://..."
            />

            <div className="flex items-center gap-5 pt-0.5">
              <FieldCheckbox
                label="Bild till vänster"
                checked={section.reversed}
                onChange={(v) => setNormal(n, { reversed: v })}
              />
              <FieldCheckbox
                label="Före produkter"
                checked={section.upp}
                onChange={(v) => setNormal(n, { upp: v })}
              />
            </div>

            <FieldColor
              label="Bakgrundsfärg"
              value={section.bg}
              onChange={(v) => setNormal(n, { bg: v })}
              defaultColor={i % 2 === 1 ? '#F8F9FA' : '#FFFFFF'}
            />
          </CollapsibleCard>
        );
      })}

      {visible && visibleCount < 4 && (
        <button
          type="button"
          onClick={addBlock}
          className="w-full py-2 text-sm text-gray-300 hover:text-gray-500 transition-colors"
        >
          + Lägg till sektion
        </button>
      )}
    </div>
  );
}
