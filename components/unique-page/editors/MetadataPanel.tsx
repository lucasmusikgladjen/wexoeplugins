'use client';

import { UniquePageState } from '@/lib/unique-page-types';
import { Field } from '@/components/shared/fields';
import EditorSection from '@/components/editors/EditorSection';

interface CountryOption { recordId: string; code: string; name: string; }
interface DivisionOption { recordId: string; slug: string; name: string; }

interface Props {
  state: UniquePageState;
  setField: <K extends keyof UniquePageState>(k: K, v: UniquePageState[K]) => void;
  countryOptions: CountryOption[];
  divisionOptions: DivisionOption[];
}

const MULTI_SELECT_CLASS =
  'block w-full px-3 py-2 text-sm rounded bg-gray-100/80 text-gray-700 focus:bg-white focus:ring-1 focus:ring-gray-200 focus:outline-none min-h-[80px]';

export default function MetadataPanel({ state, setField, countryOptions, divisionOptions }: Props) {
  return (
    <EditorSection title="Sidinfo & SEO" defaultOpen>
      <Field.Text label="H1 (huvudrubrik)" placeholder="Om oss" value={state.h1} onChange={(v) => setField('h1', v)} />
      <Field.Text
        label="SEO Title"
        description="Visas i <title>-taggen. Tom = använd H1."
        value={state.seoTitle}
        onChange={(v) => setField('seoTitle', v)}
      />
      <Field.Textarea
        label="SEO Description"
        description="150–160 tecken rekommenderas."
        rows={3}
        value={state.seoDescription}
        onChange={(v) => setField('seoDescription', v)}
      />
      <Field.Text
        label="OG Image URL"
        description="1200×630 rekommenderas."
        placeholder="https://..."
        value={state.ogImageUrl}
        onChange={(v) => setField('ogImageUrl', v)}
      />
      <Field.Group label="Land">
        <select
          multiple
          value={state.countryIds}
          onChange={(e) => setField('countryIds', Array.from(e.target.selectedOptions).map((o) => o.value))}
          className={MULTI_SELECT_CLASS}
        >
          {countryOptions.map((o) => (
            <option key={o.recordId} value={o.recordId}>{o.name} ({o.code})</option>
          ))}
        </select>
      </Field.Group>
      <Field.Group label="Division (valfri)">
        <select
          multiple
          value={state.divisionIds}
          onChange={(e) => setField('divisionIds', Array.from(e.target.selectedOptions).map((o) => o.value))}
          className={MULTI_SELECT_CLASS}
        >
          {divisionOptions.map((o) => (
            <option key={o.recordId} value={o.recordId}>{o.name}</option>
          ))}
        </select>
      </Field.Group>
    </EditorSection>
  );
}
