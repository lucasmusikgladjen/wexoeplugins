'use client';

import { PageState, PageAction } from '@/lib/types';
import { FieldInput, FieldTextarea } from './FieldInput';

interface Props {
  state: PageState;
  dispatch: React.Dispatch<PageAction>;
}

export default function HeroEditor({ state, dispatch }: Props) {
  const set = (field: keyof PageState, value: string) =>
    dispatch({ type: 'SET_FIELD', field, value });

  return (
    <div className="space-y-4">
      <h3 className="text-sm font-medium text-lp-text">Hero</h3>
      <FieldInput label="H1 — Rubrik" value={state.h1} onChange={(v) => set('h1', v)} placeholder="T.ex. Framtidens nätverkslösning" />
      <FieldTextarea label="Beskrivning" value={state.heroDescription} onChange={(v) => set('heroDescription', v)} rows={3} placeholder="Kort beskrivning under rubriken..." />
      <FieldInput label="Hero-bild (URL)" value={state.heroImage} onChange={(v) => set('heroImage', v)} placeholder="https://wexoe.se/wp-content/uploads/..." />
      <div>
        <p className="text-xs font-medium text-lp-text mb-1">Primär CTA</p>
        <div className="grid grid-cols-2 gap-2">
          <FieldInput label="Text" value={state.heroCta1Text} onChange={(v) => set('heroCta1Text', v)} placeholder="Kontakta oss" />
          <FieldInput label="URL" value={state.heroCta1Url} onChange={(v) => set('heroCta1Url', v)} placeholder="/kontakt/" />
        </div>
      </div>
      <div>
        <p className="text-xs font-medium text-lp-text mb-1">Sekundär CTA (valfritt)</p>
        <div className="grid grid-cols-2 gap-2">
          <FieldInput label="Text" value={state.heroCta2Text} onChange={(v) => set('heroCta2Text', v)} placeholder="Läs mer" />
          <FieldInput label="URL" value={state.heroCta2Url} onChange={(v) => set('heroCta2Url', v)} placeholder="/produkt/" />
        </div>
      </div>
    </div>
  );
}
