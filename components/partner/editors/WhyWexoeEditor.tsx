'use client';

import { PartnerPageState } from '@/lib/partner-types';
import { Field } from '@/components/shared/fields';
import type { SectionEditorProps } from '@/lib/page-types/types';

/**
 * "Varför Wexoe + [partner]"-sektionen kombinerar:
 *   - Vänsterkolumn: stack av success-case-kort (case_ids → cms_cases).
 *   - Högerkolumn: copy (h2 + text) + benefits-lista (lines).
 *   - Optional "Se alla case"-länk under stacken (visas bara om båda
 *     casesViewAllText OCH casesViewAllUrl är satta).
 *
 * Pluginet renderar bara de tre första casen — vi visar en varning om
 * fler är valda.
 */
export default function WhyWexoeEditor({
  state,
  onChange,
}: SectionEditorProps<PartnerPageState>) {
  const set = <K extends keyof PartnerPageState>(key: K, value: PartnerPageState[K]) =>
    onChange({ ...state, [key]: value });

  const caseOverflow = state.caseIds.length > 3;

  return (
    <>
      <Field.Text
        label="H2"
        value={state.whyH2}
        onChange={(v) => set('whyH2', v)}
        placeholder="T.ex. Varför köpa Rockwell genom Wexoe?"
      />

      <Field.RichText
        label="Brödtext"
        value={state.whyText}
        onChange={(v) => set('whyText', v)}
        rows={4}
        hint="Markdown inline."
        placeholder="Kort om varför kunder väljer Wexoe + denna partner…"
      />

      <Field.StringList
        label="Benefits"
        value={state.whyBenefits}
        onChange={(v) => set('whyBenefits', v)}
        placeholder="T.ex. Lokalt lager i Sverige — kortare leveranstider"
        description="En benefit per rad. Markdown stöds inline (**bold** för highlight)."
        addLabel="+ Lägg till benefit"
      />

      <div className="pt-3 mt-3 border-t border-gray-100">
        <Field.LinkedRecords
          label="Success cases"
          source="cases"
          value={state.caseIds}
          onChange={(ids) => set('caseIds', ids)}
          filter={(r) => r.is_active !== false}
          description="Pluginet renderar de tre första. Tomt → kontaktperson-fallback visas i stället."
        />
        {caseOverflow && (
          <p className="mt-1 text-[11px] text-amber-600">
            ⚠ {state.caseIds.length} case valda — bara de 3 första renderas på den
            publika sidan.
          </p>
        )}
      </div>

      <div className="pt-3 mt-2 border-t border-gray-100">
        <Field.Buttons
          label='"Se alla case"-länk'
          segments={[
            { value: state.casesViewAllText, onChange: (v) => set('casesViewAllText', v), placeholder: 'Text (t.ex. Se alla case)' },
            { value: state.casesViewAllUrl, onChange: (v) => set('casesViewAllUrl', v), placeholder: 'URL' },
          ]}
        />
        <p className="mt-1 text-[10px] text-gray-400">
          Länken visas bara om <strong>båda</strong> text och URL är ifyllda.
        </p>
      </div>
    </>
  );
}
