'use client';

import { useState } from 'react';
import { ProductAreaState } from '@/lib/product-area-types';
import { Field } from '@/components/shared/fields';
import type { SectionEditorProps } from '@/lib/page-types/types';

type RightColumnType = 'none' | 'npi' | 'benefits' | 'image';

const rightColumnOptions: ReadonlyArray<{ value: RightColumnType; label: string }> = [
  { value: 'none', label: '— Ingen —' },
  { value: 'npi', label: 'NPI-kort' },
  { value: 'benefits', label: 'Benefits' },
  { value: 'image', label: 'Bild' },
];

/** Derive the right-column mode from existing state using the PHP plugin's
 *  priority order (npi > benefits > image > none), so loading an existing
 *  record selects whichever variant the record actually uses. */
function deriveRightType(state: ProductAreaState): RightColumnType {
  if (state.npiTitle.trim()) return 'npi';
  if (state.heroBenefits.trim()) return 'benefits';
  if (state.heroImage.trim()) return 'image';
  return 'none';
}

export default function HeroEditor({ state, onChange }: SectionEditorProps<ProductAreaState>) {
  const set = <K extends keyof ProductAreaState>(key: K, value: ProductAreaState[K]) =>
    onChange({ ...state, [key]: value });

  // Right column is an "either/or" in the rendered page. Tracked in local
  // component state (not persisted in Airtable) and derived on mount from
  // which fields are populated.
  const [rightType, setRightType] = useState<RightColumnType>(() => deriveRightType(state));

  const changeRightType = (next: RightColumnType) => {
    setRightType(next);
    // Clear fields that no longer belong to the selected mode så preview
    // och saved record reflekterar användarens explicita val.
    const patch: Partial<ProductAreaState> = {};
    if (next !== 'npi') {
      patch.npiTitle = '';
      patch.npiDescription = '';
      patch.npiImage = '';
      patch.npiLink = '';
    }
    if (next !== 'benefits') patch.heroBenefits = '';
    if (next !== 'image') patch.heroImage = '';
    if (Object.keys(patch).length > 0) onChange({ ...state, ...patch });
  };

  return (
    <>
      <Field.Text
        label="H1"
        value={state.h1}
        onChange={(v) => set('h1', v)}
        placeholder="T.ex. Rockwell Automation PLC"
      />
      <Field.Text
        label="H2"
        value={state.heroH2}
        onChange={(v) => set('heroH2', v)}
        placeholder="Allen-Bradley PLC för små och stora applikationer"
      />
      <Field.RichText
        label="Brödtext"
        value={state.heroText}
        onChange={(v) => set('heroText', v)}
        rows={8}
        placeholder="Kort beskrivning av vad sidan handlar om…"
      />
      <Field.Buttons
        label="Knapp"
        segments={[
          { value: state.heroCtaText, onChange: (v) => set('heroCtaText', v), placeholder: 'Text' },
          { value: state.heroCtaUrl, onChange: (v) => set('heroCtaUrl', v), placeholder: 'URL' },
        ]}
      />

      {/* ── Höger kolumn: either/or NPI / benefits / bild ──────────────── */}
      <div className="pt-2">
        <Field.Select<RightColumnType>
          label="Höger kolumn"
          value={rightType}
          onChange={changeRightType}
          options={rightColumnOptions}
        />

        {rightType === 'npi' && (
          <div className="mt-3 space-y-2">
            <Field.Text label="Titel" value={state.npiTitle} onChange={(v) => set('npiTitle', v)} placeholder="T.ex. ControlLogix 5590" />
            <Field.Textarea label="Beskrivning" value={state.npiDescription} onChange={(v) => set('npiDescription', v)} rows={4} placeholder="Ny generation med dubbla scantiden…" />
            <Field.Text label="Bild" value={state.npiImage} onChange={(v) => set('npiImage', v)} placeholder="https://..." />
            <Field.Text label="Länk" value={state.npiLink} onChange={(v) => set('npiLink', v)} placeholder="/produkter/controllogix/" />
          </div>
        )}

        {rightType === 'benefits' && (
          <div className="mt-3">
            <Field.RichText
              label="Benefits"
              value={state.heroBenefits}
              onChange={(v) => set('heroBenefits', v)}
              rows={8}
              hint="en per rad"
              placeholder={'Från fristående styrning till fabriksövergripande\nFör både process- och diskret tillverkning\nIntegrerad arkitektur'}
            />
          </div>
        )}

        {rightType === 'image' && (
          <div className="mt-3">
            <Field.Text label="Bild" value={state.heroImage} onChange={(v) => set('heroImage', v)} placeholder="https://wexoe.se/wp-content/uploads/..." />
          </div>
        )}
      </div>
    </>
  );
}
