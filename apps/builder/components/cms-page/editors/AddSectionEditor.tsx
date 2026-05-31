'use client';

import { useState } from 'react';
import { CmsPageState, emptySection, SECTION_TYPE_LABELS, SECTION_TYPES, SectionType } from '@/lib/cms-page-types';
import type { SectionEditorProps } from '@/lib/page-types/types';

function TypePicker({ onPick, onCancel }: { onPick: (type: SectionType) => void; onCancel: () => void }) {
  return (
    <div className="rounded-lg border border-gray-200 bg-white p-3 shadow-sm">
      <div className="flex items-center justify-between mb-2">
        <p className="text-xs text-gray-500">Välj sektionstyp:</p>
        <button type="button" onClick={onCancel} className="text-gray-300 hover:text-gray-500 text-sm leading-none" aria-label="Avbryt">×</button>
      </div>
      <div className="grid grid-cols-2 gap-1.5">
        {SECTION_TYPES.map((t) => (
          <button key={t} type="button" onClick={() => onPick(t)} className="text-left text-xs px-2 py-1.5 rounded bg-gray-50 hover:bg-orange-50 hover:text-orange-700 transition-colors">{SECTION_TYPE_LABELS[t]}</button>
        ))}
      </div>
    </div>
  );
}

export default function AddSectionEditor({ state, onChange }: SectionEditorProps<CmsPageState>) {
  const [adding, setAdding] = useState(false);
  const addSection = (type: SectionType) => {
    onChange({ ...state, sections: [...state.sections, emptySection(type)] });
    setAdding(false);
  };

  return adding ? (
    <TypePicker onPick={addSection} onCancel={() => setAdding(false)} />
  ) : (
    <button type="button" onClick={() => setAdding(true)} className="w-full py-2 text-sm text-gray-300 hover:text-gray-500 transition-colors border border-dashed border-gray-200 rounded-lg">+ Lägg till sektion</button>
  );
}
