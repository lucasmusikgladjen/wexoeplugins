'use client';

import { UniquePageState } from '@/lib/unique-page-types';
import CollapsibleSection, { FieldRow, TextInput, TextareaInput } from './CollapsibleSection';

interface CountryOption { recordId: string; code: string; name: string; }
interface DivisionOption { recordId: string; slug: string; name: string; }

interface Props {
  state: UniquePageState;
  setField: <K extends keyof UniquePageState>(k: K, v: UniquePageState[K]) => void;
  countryOptions: CountryOption[];
  divisionOptions: DivisionOption[];
}

export default function MetadataPanel({ state, setField, countryOptions, divisionOptions }: Props) {
  return (
    <CollapsibleSection title="Sidinfo & SEO" defaultOpen>
      <FieldRow label="H1 (huvudrubrik)">
        <TextInput value={state.h1} onChange={(v) => setField('h1', v)} placeholder="Om oss" />
      </FieldRow>
      <FieldRow label="SEO Title" help="Visas i <title>-taggen. Tom = använd H1.">
        <TextInput value={state.seoTitle} onChange={(v) => setField('seoTitle', v)} />
      </FieldRow>
      <FieldRow label="SEO Description" help="150–160 tecken rekommenderas.">
        <TextareaInput value={state.seoDescription} onChange={(v) => setField('seoDescription', v)} rows={3} />
      </FieldRow>
      <FieldRow label="OG Image URL" help="1200×630 rekommenderas.">
        <TextInput value={state.ogImageUrl} onChange={(v) => setField('ogImageUrl', v)} placeholder="https://..." />
      </FieldRow>
      <FieldRow label="Land">
        <select
          multiple
          value={state.countryIds}
          onChange={(e) => setField('countryIds', Array.from(e.target.selectedOptions).map((o) => o.value))}
          className="w-full px-2 py-1.5 text-sm border border-gray-200 rounded-md min-h-[80px]"
        >
          {countryOptions.map((o) => (
            <option key={o.recordId} value={o.recordId}>{o.name} ({o.code})</option>
          ))}
        </select>
      </FieldRow>
      <FieldRow label="Division (valfri)">
        <select
          multiple
          value={state.divisionIds}
          onChange={(e) => setField('divisionIds', Array.from(e.target.selectedOptions).map((o) => o.value))}
          className="w-full px-2 py-1.5 text-sm border border-gray-200 rounded-md min-h-[80px]"
        >
          {divisionOptions.map((o) => (
            <option key={o.recordId} value={o.recordId}>{o.name}</option>
          ))}
        </select>
      </FieldRow>
    </CollapsibleSection>
  );
}
