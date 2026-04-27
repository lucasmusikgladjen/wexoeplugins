'use client';

import { PageState, PageAction } from '@/lib/types';
import { FieldInput, RichTextarea, FieldCheckbox } from './FieldInput';

interface Props {
  state: PageState;
  dispatch: React.Dispatch<PageAction>;
}

export default function ContentEditor({ state, dispatch }: Props) {
  const set = (field: keyof PageState, value: unknown) =>
    dispatch({ type: 'SET_FIELD', field, value });

  return (
    <div className="space-y-3">
      <div className="flex items-center justify-between">
        <h3 className="text-xl font-bold text-gray-900">Innehåll</h3>
        <FieldCheckbox label="Visa" checked={state.showContent} onChange={(v) => set('showContent', v)} />
      </div>
      {state.showContent && (
        <>
          <FieldInput label="Underrubrik" value={state.contentH2} onChange={(v) => set('contentH2', v)} placeholder="T.ex. Vad är FTTO?" />
          <RichTextarea label="Brödtext" value={state.contentText} onChange={(v) => set('contentText', v)} rows={10} placeholder="Beskriv produkten/tjänsten..." />
          <RichTextarea label="Benefits" value={state.contentBenefits} onChange={(v) => set('contentBenefits', v)} rows={8} hint="en per rad" placeholder={"Enkel installation\nLägre TCO\nFramtidssäker lösning"} />
        </>
      )}
    </div>
  );
}
