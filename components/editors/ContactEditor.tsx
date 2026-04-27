'use client';

import { PageState, PageAction } from '@/lib/types';
import { FieldInput, RichTextarea, FieldCheckbox } from './FieldInput';

interface Props {
  state: PageState;
  dispatch: React.Dispatch<PageAction>;
}

export default function ContactEditor({ state, dispatch }: Props) {
  const set = (field: keyof PageState, value: unknown) =>
    dispatch({ type: 'SET_FIELD', field, value });

  return (
    <div className="space-y-3">
      <div className="flex items-center justify-between">
        <h3 className="text-xl font-bold text-gray-900">Kontaktperson</h3>
        <FieldCheckbox label="Visa" checked={state.showContact} onChange={(v) => set('showContact', v)} />
      </div>
      {state.showContact && (
        <>
          <FieldInput label="Namn" value={state.contactName} onChange={(v) => set('contactName', v)} placeholder="Anna Andersson" />
          <FieldInput label="Titel" value={state.contactTitle} onChange={(v) => set('contactTitle', v)} placeholder="Säljare, Industriell Automation" />
          <div className="grid grid-cols-2 gap-2">
            <FieldInput label="E-post" value={state.contactEmail} onChange={(v) => set('contactEmail', v)} placeholder="anna@wexoe.se" />
            <FieldInput label="Telefon" value={state.contactPhone} onChange={(v) => set('contactPhone', v)} placeholder="+46 70 123 45 67" />
          </div>
          <FieldInput label="Bild" value={state.contactImage} onChange={(v) => set('contactImage', v)} placeholder="https://wexoe.se/wp-content/uploads/..." />
          <RichTextarea label="Citat" value={state.contactQuote} onChange={(v) => set('contactQuote', v)} rows={4} placeholder="Jag hjälper dig gärna att hitta rätt lösning." />
        </>
      )}
    </div>
  );
}
