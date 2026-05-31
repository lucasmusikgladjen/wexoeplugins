'use client';

import { PageState, PageAction } from '@/lib/types';
import { FieldInput, RichTextarea } from './FieldInput';
import EditorSection from './EditorSection';

interface Props {
  state: PageState;
  dispatch: React.Dispatch<PageAction>;
}

export default function ContentEditor({ state, dispatch }: Props) {
  const set = (field: keyof PageState, value: unknown) =>
    dispatch({ type: 'SET_FIELD', field, value });

  return (
    <EditorSection
      title="Innehåll"
      visible={state.showContent}
      onToggleVisible={(v) => set('showContent', v)}
    >
      <FieldInput label="Underrubrik" value={state.contentH2} onChange={(v) => set('contentH2', v)} placeholder="T.ex. Vad är FTTO?" />
      <RichTextarea label="Brödtext" value={state.contentText} onChange={(v) => set('contentText', v)} rows={10} placeholder="Beskriv produkten/tjänsten..." />
      <RichTextarea label="Benefits" value={state.contentBenefits} onChange={(v) => set('contentBenefits', v)} rows={8} hint="en per rad" placeholder={"Enkel installation\nLägre TCO\nFramtidssäker lösning"} />
    </EditorSection>
  );
}
