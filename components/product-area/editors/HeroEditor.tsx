'use client';

import { useState } from 'react';
import { ProductAreaState } from '@/lib/product-area-types';
import { FieldInput, FieldTextarea, FieldSelect, RichTextarea } from '@/components/editors/FieldInput';
import ButtonFieldset from '@/components/editors/ButtonFieldset';

interface Props {
  state: ProductAreaState;
  setField: <K extends keyof ProductAreaState>(key: K, value: ProductAreaState[K]) => void;
}

type RightColumnType = 'none' | 'npi' | 'benefits' | 'image';

const rightColumnOptions: { value: string; label: string }[] = [
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

export default function HeroEditor({ state, setField }: Props) {
  // Right column is an "either/or" in the rendered page. Tracked in local
  // component state (not persisted in Airtable) and derived on mount from
  // which fields are populated.
  const [rightType, setRightType] = useState<RightColumnType>(() => deriveRightType(state));

  const changeRightType = (next: RightColumnType) => {
    setRightType(next);
    // Clear fields that no longer belong to the selected mode so the preview
    // (and the saved record) reflect the user's explicit choice.
    if (next !== 'npi') {
      setField('npiTitle', '');
      setField('npiDescription', '');
      setField('npiImage', '');
      setField('npiLink', '');
    }
    if (next !== 'benefits') {
      setField('heroBenefits', '');
    }
    if (next !== 'image') {
      setField('heroImage', '');
    }
  };

  return (
    <div className="space-y-3">
      <h3 className="text-xl font-bold text-gray-900">Hero</h3>

      <FieldInput
        label="H1"
        value={state.h1}
        onChange={(v) => setField('h1', v)}
        placeholder="T.ex. Rockwell Automation PLC"
      />

      <FieldInput
        label="H2"
        value={state.heroH2}
        onChange={(v) => setField('heroH2', v)}
        placeholder="Allen-Bradley PLC för små och stora applikationer"
      />

      <RichTextarea
        label="Brödtext"
        value={state.heroText}
        onChange={(v) => setField('heroText', v)}
        rows={8}
        placeholder="Kort beskrivning av vad sidan handlar om…"
      />

      <ButtonFieldset
        label="Knapp"
        segments={[
          {
            value: state.heroCtaText,
            onChange: (v) => setField('heroCtaText', v),
            placeholder: 'Text',
          },
          {
            value: state.heroCtaUrl,
            onChange: (v) => setField('heroCtaUrl', v),
            placeholder: 'URL',
          },
        ]}
      />

      {/* ── Höger kolumn: either/or NPI / benefits / bild ──────────────── */}
      <div className="pt-2">
        <FieldSelect
          label="Höger kolumn"
          value={rightType}
          onChange={(v) => changeRightType(v as RightColumnType)}
          options={rightColumnOptions}
        />

        {rightType === 'npi' && (
          <div className="mt-3 space-y-2">
            <FieldInput
              label="Titel"
              value={state.npiTitle}
              onChange={(v) => setField('npiTitle', v)}
              placeholder="T.ex. ControlLogix 5590"
            />
            <FieldTextarea
              label="Beskrivning"
              value={state.npiDescription}
              onChange={(v) => setField('npiDescription', v)}
              rows={4}
              placeholder="Ny generation med dubbla scantiden…"
            />
            <FieldInput
              label="Bild"
              value={state.npiImage}
              onChange={(v) => setField('npiImage', v)}
              placeholder="https://..."
            />
            <FieldInput
              label="Länk"
              value={state.npiLink}
              onChange={(v) => setField('npiLink', v)}
              placeholder="/produkter/controllogix/"
            />
          </div>
        )}

        {rightType === 'benefits' && (
          <div className="mt-3">
            <RichTextarea
              label="Benefits"
              value={state.heroBenefits}
              onChange={(v) => setField('heroBenefits', v)}
              rows={8}
              hint="en per rad"
              placeholder={'Från fristående styrning till fabriksövergripande\nFör både process- och diskret tillverkning\nIntegrerad arkitektur'}
            />
          </div>
        )}

        {rightType === 'image' && (
          <div className="mt-3">
            <FieldInput
              label="Bild"
              value={state.heroImage}
              onChange={(v) => setField('heroImage', v)}
              placeholder="https://wexoe.se/wp-content/uploads/..."
            />
          </div>
        )}
      </div>
    </div>
  );
}
