'use client';

import { PartnerPageState } from '@/lib/partner-types';
import { Field } from '@/components/shared/fields';
import type { SectionEditorProps } from '@/lib/page-types/types';

/**
 * Hero-sektion. Alltid synlig — schemat har ingen show_*-toggle för hero.
 * Visar partner-logon via partner_ids-länken (väljs i Inställningar) +
 * eyebrow + h1 + tagline + CTA-knappar + hero-bild.
 */
export default function HeroEditor({ state, onChange }: SectionEditorProps<PartnerPageState>) {
  const set = <K extends keyof PartnerPageState>(key: K, value: PartnerPageState[K]) =>
    onChange({ ...state, [key]: value });

  return (
    <>
      <Field.Text
        label="Eyebrow"
        value={state.heroEyebrow}
        onChange={(v) => set('heroEyebrow', v)}
        placeholder="T.ex. Officiell distributör i Sverige"
      />

      <Field.Text
        label="H1"
        value={state.h1}
        onChange={(v) => set('h1', v)}
        placeholder="T.ex. Rockwell Automation"
        description="Visas under partnerns logo i hero. Också publik H1."
      />

      <Field.RichText
        label="Tagline"
        value={state.heroTagline}
        onChange={(v) => set('heroTagline', v)}
        rows={5}
        hint="Markdown inline — **bold**, *italic*, [länk](url)."
        placeholder="Kort beskrivning av partnern och Wexoes roll…"
      />

      <Field.Buttons
        label="Primär CTA"
        segments={[
          { value: state.heroCtaText, onChange: (v) => set('heroCtaText', v), placeholder: 'Text (default: Kontakta oss)' },
          { value: state.heroCtaUrl, onChange: (v) => set('heroCtaUrl', v), placeholder: 'URL (default: #kontakt)' },
        ]}
      />

      <Field.Buttons
        label="Sekundär CTA (visas bara om båda är ifyllda)"
        segments={[
          { value: state.heroCta2Text, onChange: (v) => set('heroCta2Text', v), placeholder: 'Text' },
          { value: state.heroCta2Url, onChange: (v) => set('heroCta2Url', v), placeholder: 'URL' },
        ]}
      />

      <Field.Text
        label="Hero-bild"
        value={state.heroImageUrl}
        onChange={(v) => set('heroImageUrl', v)}
        placeholder="https://..."
        description="Bild som visas till höger om hero-texten."
      />

      <p className="text-[11px] text-gray-400 mt-2 pt-2 border-t border-gray-100">
        Partnerns logo + namn läses från det länkade <strong>core_partners</strong>-recordet —
        välj partnern under <strong>Inställningar</strong>.
      </p>
    </>
  );
}
