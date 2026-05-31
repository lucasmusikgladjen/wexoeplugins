'use client';

import { CustomerTypePageState } from '@/lib/customer-type-types';
import { Field } from '@/components/shared/fields';
import type { SectionEditorProps } from '@/lib/page-types/types';

export default function SettingsEditor({ state, onChange }: SectionEditorProps<CustomerTypePageState>) {
  const caseCount = state.caseIds.length;

  return (
    <>
      <Field.Checkbox
        label="Aktiv (publicerad)"
        checked={state.isActive}
        onChange={(v) => onChange({ ...state, isActive: v })}
      />

      <div className="rounded border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-600">
        <div className="font-medium text-gray-800">
          Länkade kundcase: {caseCount}
        </div>
        <p className="mt-1 text-xs text-gray-500">
          Cases hanteras i Airtable (cms_cases) — varje case är en
          self-contained record som kan länkas från flera kundtyp-sidor. Dedikerad
          case-editor kommer i en senare task.
        </p>
      </div>
    </>
  );
}
