'use client';

import { PartnerPageState } from '@/lib/partner-types';
import { Field } from '@/components/shared/fields';
import type { SectionEditorProps } from '@/lib/page-types/types';

export default function SettingsEditor({
  state,
  onChange,
}: SectionEditorProps<PartnerPageState>) {
  const set = <K extends keyof PartnerPageState>(key: K, value: PartnerPageState[K]) =>
    onChange({ ...state, [key]: value });

  return (
    <>
      <Field.LinkedRecords
        label="Partner (identitet)"
        source="core_partners"
        value={state.partnerIds}
        onChange={(ids) => set('partnerIds', ids)}
        filter={(r) => r.is_active !== false}
        max={1}
      />

      <Field.LinkedRecords
        label="Länder"
        source="core_countries"
        value={state.countryIds}
        onChange={(ids) => set('countryIds', ids)}
      />

      <div className="pt-2 mt-2 border-t border-gray-100 space-y-3">
        <p className="text-[11px] text-gray-400 uppercase tracking-wider">SEO</p>
        <Field.Text
          label="SEO-titel"
          value={state.seoTitle}
          onChange={(v) => set('seoTitle', v)}
          placeholder="Tom = fallback till H1"
        />
        <Field.Textarea
          label="SEO-beskrivning"
          value={state.seoDescription}
          onChange={(v) => set('seoDescription', v)}
          rows={3}
          placeholder="Meta description för sökmotorer"
        />
        <Field.Text
          label="Open Graph-bild-URL"
          value={state.ogImageUrl}
          onChange={(v) => set('ogImageUrl', v)}
          placeholder="https://..."
        />
      </div>

      <div className="pt-2 mt-2 border-t border-gray-100">
        <Field.Textarea
          label="Interna anteckningar"
          value={state.internalNotes}
          onChange={(v) => set('internalNotes', v)}
          rows={3}
          placeholder="Anteckningar till andra redaktörer. Visas inte publikt."
        />
      </div>
    </>
  );
}
