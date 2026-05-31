'use client';

import { ProductAreaState } from '@/lib/product-area-types';
import { Field } from '@/components/shared/fields';
import type { SectionEditorProps } from '@/lib/page-types/types';

export default function ContactEditor({ state, onChange }: SectionEditorProps<ProductAreaState>) {
  const set = <K extends keyof ProductAreaState>(key: K, value: ProductAreaState[K]) =>
    onChange({ ...state, [key]: value });

  return (
    <>
      <Field.Text label="Namn" value={state.contactName} onChange={(v) => set('contactName', v)} placeholder="Anna Andersson" />
      <Field.Text label="Titel" value={state.contactTitle} onChange={(v) => set('contactTitle', v)} placeholder="Säljare, Industriell Automation" />
      <div className="grid grid-cols-2 gap-2">
        <Field.Text label="E-post" value={state.contactEmail} onChange={(v) => set('contactEmail', v)} placeholder="anna@wexoe.se" />
        <Field.Text label="Telefon" value={state.contactPhone} onChange={(v) => set('contactPhone', v)} placeholder="+46 70 123 45 67" />
      </div>
      <Field.Text label="Bild" value={state.contactImage} onChange={(v) => set('contactImage', v)} placeholder="https://wexoe.se/wp-content/uploads/..." />
      <Field.RichText label="Citat" value={state.contactText} onChange={(v) => set('contactText', v)} rows={6} placeholder="Jag hjälper dig gärna att hitta rätt lösning." />
      <Field.Color label="Bakgrundsfärg" value={state.contactBg} onChange={(v) => set('contactBg', v)} defaultColor="#11325D" />
    </>
  );
}
