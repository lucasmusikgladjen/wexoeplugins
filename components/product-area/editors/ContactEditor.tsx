'use client';

import { ProductAreaState } from '@/lib/product-area-types';
import { FieldInput, FieldTextarea, FieldColor, FieldCheckbox } from '@/components/editors/FieldInput';

interface Props {
  state: ProductAreaState;
  setField: <K extends keyof ProductAreaState>(key: K, value: ProductAreaState[K]) => void;
  visible: boolean;
  onToggleVisible: (v: boolean) => void;
}

export default function ContactEditor({ state, setField, visible, onToggleVisible }: Props) {
  return (
    <div className="space-y-3">
      <div className="flex items-center justify-between">
        <h3 className="text-xl font-bold text-gray-900">Kontaktperson</h3>
        <FieldCheckbox label="Visa" checked={visible} onChange={onToggleVisible} />
      </div>

      {visible && (
      <>

      <FieldInput
        label="Namn"
        value={state.contactName}
        onChange={(v) => setField('contactName', v)}
        placeholder="Anna Andersson"
      />
      <FieldInput
        label="Titel"
        value={state.contactTitle}
        onChange={(v) => setField('contactTitle', v)}
        placeholder="Säljare, Industriell Automation"
      />
      <div className="grid grid-cols-2 gap-2">
        <FieldInput
          label="E-post"
          value={state.contactEmail}
          onChange={(v) => setField('contactEmail', v)}
          placeholder="anna@wexoe.se"
        />
        <FieldInput
          label="Telefon"
          value={state.contactPhone}
          onChange={(v) => setField('contactPhone', v)}
          placeholder="+46 70 123 45 67"
        />
      </div>
      <FieldInput
        label="Bild"
        value={state.contactImage}
        onChange={(v) => setField('contactImage', v)}
        placeholder="https://wexoe.se/wp-content/uploads/..."
      />
      <FieldTextarea
        label="Citat"
        value={state.contactText}
        onChange={(v) => setField('contactText', v)}
        rows={3}
        placeholder="Jag hjälper dig gärna att hitta rätt lösning."
      />

      <FieldColor
        label="Bakgrundsfärg"
        value={state.contactBg}
        onChange={(v) => setField('contactBg', v)}
        defaultColor="#11325D"
      />

      </>
      )}
    </div>
  );
}
