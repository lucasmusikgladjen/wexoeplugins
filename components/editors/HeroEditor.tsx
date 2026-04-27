'use client';

import { PageState, PageAction } from '@/lib/types';
import { FieldInput, RichTextarea } from './FieldInput';
import ButtonFieldset from './ButtonFieldset';

interface Props {
  state: PageState;
  dispatch: React.Dispatch<PageAction>;
}

export default function HeroEditor({ state, dispatch }: Props) {
  const set = (field: keyof PageState, value: string) =>
    dispatch({ type: 'SET_FIELD', field, value });

  return (
    <div className="space-y-3">
      <h3 className="text-xl font-bold text-gray-900">Hero</h3>
      <FieldInput label="Rubrik" value={state.h1} onChange={(v) => set('h1', v)} placeholder="T.ex. Framtidens nätverkslösning" />
      <RichTextarea label="Beskrivning" value={state.heroDescription} onChange={(v) => set('heroDescription', v)} rows={6} placeholder="Kort beskrivning under rubriken..." />
      <FieldInput label="Bakgrundsbild" value={state.heroImage} onChange={(v) => set('heroImage', v)} placeholder="https://wexoe.se/wp-content/uploads/..." />

      <ButtonFieldset
        label="Knapp 1"
        segments={[
          {
            value: state.heroCta1Text,
            onChange: (v) => set('heroCta1Text', v),
            placeholder: 'Text',
          },
          {
            value: state.heroCta1Url,
            onChange: (v) => set('heroCta1Url', v),
            placeholder: 'URL',
          },
        ]}
      />

      <ButtonFieldset
        label="Knapp 2"
        segments={[
          {
            value: state.heroCta2Text,
            onChange: (v) => set('heroCta2Text', v),
            placeholder: 'Text',
          },
          {
            value: state.heroCta2Url,
            onChange: (v) => set('heroCta2Url', v),
            placeholder: 'URL',
          },
        ]}
      />
    </div>
  );
}
