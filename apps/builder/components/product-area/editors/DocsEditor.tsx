'use client';

import { ProductAreaState } from '@/lib/product-area-types';
import { FieldInput, FieldColor } from '@/components/editors/FieldInput';

interface Props {
  state: ProductAreaState;
  setField: <K extends keyof ProductAreaState>(key: K, value: ProductAreaState[K]) => void;
}

export default function DocsEditor({ state, setField }: Props) {
  return (
    <div className="space-y-3">
      <h3 className="text-xl font-bold text-gray-900">Dokumentation</h3>

      <FieldInput
        label="Rubrik"
        value={state.docsTitle}
        onChange={(v) => setField('docsTitle', v)}
        placeholder="Dokumentation"
      />
      <FieldInput
        label="Iframe-URL"
        value={state.docsIframe}
        onChange={(v) => setField('docsIframe', v)}
        placeholder="https://..."
      />

      <FieldColor
        label="Bakgrundsfärg"
        value={state.docsBg}
        onChange={(v) => setField('docsBg', v)}
        defaultColor="#FFFFFF"
      />
    </div>
  );
}
