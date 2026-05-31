'use client';

/**
 * 15 type-specifika sektion-editorer för cms_page_sections, samlade i en fil
 * för översikt. Varje export tar `{ section, patch }` (`SectionEditorContext`)
 * och renderar bara TYPE-SPECIFIC fält. Common-fälten (layout/theme/padding/
 * anchor_id) renderas av SectionDesign och appendas separat i SectionsEditor.
 *
 * isActive togglas globalt på sektion-korten (RepeaterCard-header).
 * internalLabel hanteras i korthuvudet som titel.
 */

import { Field } from '@/components/shared/fields';
import RepeaterCard from '@/components/shared/RepeaterCard';
import {
  CaseGridSection,
  CatalogSection,
  CfLayout,
  CompanyDataStripSection,
  ContactFormSection,
  CtaBannerSection,
  emptyTabItem,
  FaqSection,
  GridCols,
  HeroSection,
  NewsGridSection,
  NewsTextSplitSection,
  PartnerListSection,
  PlVariant,
  TabItem,
  TabsSection,
  TeamGridSection,
  TestimonialSection,
  TextAlign,
  TextImageSection,
  TextOnlySection,
  TgVariant,
} from '@/lib/cms-page-types';
import { FaqItem, emptyFaqItem } from '@/lib/faq-block';
import { makeTabPatch, SectionEditorContext } from '../SectionFormsCommon';

const TEXT_ALIGN_OPTS: readonly { value: TextAlign; label: string }[] = [
  { value: 'left', label: 'Vänster' },
  { value: 'center', label: 'Centrerat' },
];
const COLUMNS_OPTS: readonly { value: GridCols; label: string }[] = [
  { value: '2', label: '2 kolumner' },
  { value: '3', label: '3 kolumner' },
  { value: '4', label: '4 kolumner' },
];
const TG_VARIANT_OPTS: readonly { value: TgVariant; label: string }[] = [
  { value: 'cards', label: 'Kort' },
  { value: 'rack', label: 'Rack' },
  { value: 'compact', label: 'Kompakt' },
];
const PL_VARIANT_OPTS: readonly { value: PlVariant; label: string }[] = [
  { value: 'grid', label: 'Grid' },
  { value: 'list', label: 'Lista' },
  { value: 'marquee', label: 'Marquee' },
];
const CF_LAYOUT_OPTS: readonly { value: CfLayout; label: string }[] = [
  { value: 'split', label: 'Split (text + form)' },
  { value: 'centered', label: 'Centrerat' },
];

// ─── hero ────────────────────────────────────────────────────────────────────

export function HeroForm({ section, patch }: SectionEditorContext<HeroSection>) {
  return (
    <>
      <Field.Text label="Eyebrow" value={section.eyebrow} onChange={(v) => patch({ eyebrow: v })} placeholder="T.ex. Automation · Sverige" />
      <Field.Text label="H1 (override)" value={section.h1} onChange={(v) => patch({ h1: v })} placeholder="Lämna tom för att använda sidans H1" />
      <Field.Textarea label="Underrubrik" value={section.subtitle} onChange={(v) => patch({ subtitle: v })} rows={3} />
      <Field.Text label="Bild-URL" value={section.imageUrl} onChange={(v) => patch({ imageUrl: v })} placeholder="https://..." />
      <Field.Buttons label="Knapp 1" segments={[
        { value: section.ctaText, onChange: (v) => patch({ ctaText: v }), placeholder: 'Text' },
        { value: section.ctaUrl, onChange: (v) => patch({ ctaUrl: v }), placeholder: 'URL' },
      ]} />
      <Field.Buttons label="Knapp 2" segments={[
        { value: section.cta2Text, onChange: (v) => patch({ cta2Text: v }), placeholder: 'Text' },
        { value: section.cta2Url, onChange: (v) => patch({ cta2Url: v }), placeholder: 'URL' },
      ]} />
    </>
  );
}

// ─── text_image ──────────────────────────────────────────────────────────────

export function TextImageForm({ section, patch }: SectionEditorContext<TextImageSection>) {
  return (
    <>
      <Field.Text label="Eyebrow" value={section.eyebrow} onChange={(v) => patch({ eyebrow: v })} />
      <Field.Text label="Rubrik (H2)" value={section.h2} onChange={(v) => patch({ h2: v })} />
      <Field.RichText label="Brödtext" value={section.body} onChange={(v) => patch({ body: v })} rows={6} />
      <Field.RichText label="Punktlista" value={section.bullets} onChange={(v) => patch({ bullets: v })} rows={4} hint="en per rad" />
      <Field.Text label="Bild-URL" value={section.imageUrl} onChange={(v) => patch({ imageUrl: v })} placeholder="https://..." />
      <Field.Text label="Bild alt-text" value={section.imageAlt} onChange={(v) => patch({ imageAlt: v })} />
      <Field.Checkbox label="Bild till vänster (omvänd)" checked={section.reversed} onChange={(v) => patch({ reversed: v })} />
      <Field.Buttons label="Knapp 1" segments={[
        { value: section.ctaText, onChange: (v) => patch({ ctaText: v }), placeholder: 'Text' },
        { value: section.ctaUrl, onChange: (v) => patch({ ctaUrl: v }), placeholder: 'URL' },
      ]} />
      <Field.Buttons label="Knapp 2" segments={[
        { value: section.cta2Text, onChange: (v) => patch({ cta2Text: v }), placeholder: 'Text' },
        { value: section.cta2Url, onChange: (v) => patch({ cta2Url: v }), placeholder: 'URL' },
      ]} />
    </>
  );
}

// ─── text_only ───────────────────────────────────────────────────────────────

export function TextOnlyForm({ section, patch }: SectionEditorContext<TextOnlySection>) {
  return (
    <>
      <Field.Text label="Eyebrow" value={section.eyebrow} onChange={(v) => patch({ eyebrow: v })} />
      <Field.Text label="Rubrik (H2)" value={section.h2} onChange={(v) => patch({ h2: v })} />
      <Field.RichText label="Brödtext" value={section.body} onChange={(v) => patch({ body: v })} rows={6} />
      <Field.Select<TextAlign> label="Textjustering" value={section.align} onChange={(v) => patch({ align: v })} options={TEXT_ALIGN_OPTS} />
    </>
  );
}

// ─── company_data_strip ──────────────────────────────────────────────────────

export function CompanyDataStripForm({ section, patch }: SectionEditorContext<CompanyDataStripSection>) {
  return (
    <>
      <Field.Text label="Rubrik (H2)" value={section.h2} onChange={(v) => patch({ h2: v })} />
      <Field.Checkbox label="Använd core_company-singleton" checked={section.useCompanySingleton} onChange={(v) => patch({ useCompanySingleton: v })} />
      <Field.Text label="Land-kod (för singleton-resolution)" value={section.countryCode} onChange={(v) => patch({ countryCode: v })} placeholder="SE / NO / DK / FI" />
      <Field.RichText
        label="Manuella datapunkter"
        value={section.items}
        onChange={(v) => patch({ items: v })}
        rows={4}
        hint="en per rad, format: värde | label"
        placeholder={'90+ | års erfarenhet\n4 | nordiska marknader'}
      />
    </>
  );
}

// ─── news_text_split ─────────────────────────────────────────────────────────

export function NewsTextSplitForm({ section, patch }: SectionEditorContext<NewsTextSplitSection>) {
  return (
    <>
      <Field.Text label="Eyebrow" value={section.eyebrow} onChange={(v) => patch({ eyebrow: v })} />
      <Field.Text label="Rubrik (H2)" value={section.h2} onChange={(v) => patch({ h2: v })} />
      <Field.RichText label="Brödtext" value={section.body} onChange={(v) => patch({ body: v })} rows={4} />
      <Field.Buttons label="CTA" segments={[
        { value: section.ctaText, onChange: (v) => patch({ ctaText: v }), placeholder: 'Text' },
        { value: section.ctaUrl, onChange: (v) => patch({ ctaUrl: v }), placeholder: 'URL' },
      ]} />
      <Field.Text label="Division-scope" value={section.scopeDivision} onChange={(v) => patch({ scopeDivision: v })} placeholder="ärv från sidan om tom" />
      <Field.Text label="Land-scope" value={section.scopeCountry} onChange={(v) => patch({ scopeCountry: v })} placeholder="ärv från sidan om tom" />
      <Field.Text label="Max antal" type="number" value={String(section.limit || '')} onChange={(v) => patch({ limit: Number(v) || 0 })} placeholder="0 = obegränsat" />
      <p className="text-[10px] text-gray-400">Manuellt valda artiklar visas först, scope fyller på upp till max.</p>
    </>
  );
}

// ─── case_grid ───────────────────────────────────────────────────────────────

export function CaseGridForm({ section, patch }: SectionEditorContext<CaseGridSection>) {
  return (
    <>
      <Field.Text label="Eyebrow" value={section.eyebrow} onChange={(v) => patch({ eyebrow: v })} />
      <Field.Text label="Rubrik (H2)" value={section.h2} onChange={(v) => patch({ h2: v })} />
      <Field.RichText label="Brödtext" value={section.body} onChange={(v) => patch({ body: v })} rows={3} />
      <Field.Text label="Land-scope" value={section.scopeCountry} onChange={(v) => patch({ scopeCountry: v })} placeholder="ärv från sidan om tom" />
      <Field.Text label="Division-scope" value={section.scopeDivision} onChange={(v) => patch({ scopeDivision: v })} placeholder="ärv från sidan om tom" />
      <Field.Text label="Kundtyp-scope" value={section.scopeCustomerType} onChange={(v) => patch({ scopeCustomerType: v })} />
      <Field.Text label="Max antal" type="number" value={String(section.limit || '')} onChange={(v) => patch({ limit: Number(v) || 0 })} placeholder="0 = obegränsat" />
      <Field.Select<GridCols> label="Kolumner" value={section.columns} onChange={(v) => patch({ columns: v })} options={COLUMNS_OPTS} />
    </>
  );
}

// ─── news_grid ───────────────────────────────────────────────────────────────

export function NewsGridForm({ section, patch }: SectionEditorContext<NewsGridSection>) {
  return (
    <>
      <Field.Text label="Eyebrow" value={section.eyebrow} onChange={(v) => patch({ eyebrow: v })} />
      <Field.Text label="Rubrik (H2)" value={section.h2} onChange={(v) => patch({ h2: v })} />
      <Field.Text label="Land-scope" value={section.scopeCountry} onChange={(v) => patch({ scopeCountry: v })} placeholder="ärv från sidan om tom" />
      <Field.Text label="Division-scope" value={section.scopeDivision} onChange={(v) => patch({ scopeDivision: v })} placeholder="ärv från sidan om tom" />
      <Field.Text label="Topic-scope" value={section.scopeTopic} onChange={(v) => patch({ scopeTopic: v })} />
      <Field.Text label="Max antal" type="number" value={String(section.limit || '')} onChange={(v) => patch({ limit: Number(v) || 0 })} placeholder="0 = obegränsat" />
      <Field.Select<GridCols> label="Kolumner" value={section.columns} onChange={(v) => patch({ columns: v })} options={COLUMNS_OPTS} />
    </>
  );
}

// ─── catalog ─────────────────────────────────────────────────────────────────

export function CatalogForm({ section, patch }: SectionEditorContext<CatalogSection>) {
  return (
    <>
      <Field.Text label="Eyebrow" value={section.eyebrow} onChange={(v) => patch({ eyebrow: v })} />
      <Field.Text label="Rubrik (H2)" value={section.h2} onChange={(v) => patch({ h2: v })} />
      <Field.RichText label="Intro-text" value={section.introBody} onChange={(v) => patch({ introBody: v })} rows={3} />
      <div className="flex gap-4">
        <Field.Checkbox label="Inkludera produkter" checked={section.includeProducts} onChange={(v) => patch({ includeProducts: v })} />
        <Field.Checkbox label="Inkludera artiklar" checked={section.includeArticles} onChange={(v) => patch({ includeArticles: v })} />
      </div>
      <Field.Text label="Division-scope" value={section.scopeDivision} onChange={(v) => patch({ scopeDivision: v })} placeholder="ärv från sidan om tom" />
      <Field.Text label="Land-scope" value={section.scopeCountry} onChange={(v) => patch({ scopeCountry: v })} placeholder="ärv från sidan om tom" />
      <Field.RichText label="Facet-fält" value={section.facetFields} onChange={(v) => patch({ facetFields: v })} rows={3} hint="en per rad" placeholder={'supplier\ncategory'} />
      <Field.Text label="Sök-placeholder" value={section.placeholder} onChange={(v) => patch({ placeholder: v })} placeholder="Sök i katalogen…" />
      <Field.Text label="Empty state-text" value={section.emptyText} onChange={(v) => patch({ emptyText: v })} placeholder="Inga träffar." />
    </>
  );
}

// ─── tabs ────────────────────────────────────────────────────────────────────

interface TabsFormProps {
  section: TabsSection;
  patch: (changes: Partial<TabsSection>) => void;
}

export function TabsForm({ section, patch }: TabsFormProps) {
  const setTabs = (next: TabItem[]) => patch({ tabs: next });
  const addTab = () => setTabs([...section.tabs, emptyTabItem()]);
  const removeTab = (i: number) => setTabs(section.tabs.filter((_, j) => j !== i));
  const moveTab = (i: number, dir: -1 | 1) => {
    const j = i + dir;
    if (j < 0 || j >= section.tabs.length) return;
    const next = [...section.tabs];
    const [m] = next.splice(i, 1);
    next.splice(j, 0, m);
    setTabs(next);
  };

  return (
    <>
      <Field.Text label="Eyebrow" value={section.eyebrow} onChange={(v) => patch({ eyebrow: v })} />
      <Field.Text label="Rubrik (H2)" value={section.h2} onChange={(v) => patch({ h2: v })} />
      <Field.RichText label="Intro-text" value={section.introBody} onChange={(v) => patch({ introBody: v })} rows={3} />

      <div className="pt-2 mt-2 border-t border-gray-200/60">
        <p className="text-[11px] text-gray-400 mb-2">Flikar</p>
        <div className="space-y-2">
          {section.tabs.map((tab, i) => {
            const tabPatch = makeTabPatch(section.tabs, i, setTabs);
            return (
              <RepeaterCard
                key={tab.clientId}
                index={i}
                title={tab.name}
                onTitleChange={(v) => tabPatch({ name: v })}
                titlePlaceholder="Flik-namn…"
                onMoveUp={() => moveTab(i, -1)}
                onMoveDown={() => moveTab(i, 1)}
                canMoveUp={i > 0}
                canMoveDown={i < section.tabs.length - 1}
                onRemove={() => removeTab(i)}
                removeTitle="Ta bort flik"
                defaultOpen={!tab.name.trim() && !tab.h2.trim()}
              >
                <Field.Text label="Eyebrow" value={tab.eyebrow} onChange={(v) => tabPatch({ eyebrow: v })} />
                <Field.Text label="Rubrik (H2)" value={tab.h2} onChange={(v) => tabPatch({ h2: v })} />
                <Field.RichText label="Brödtext" value={tab.body} onChange={(v) => tabPatch({ body: v })} rows={4} />
                <Field.RichText label="Punktlista" value={tab.bullets} onChange={(v) => tabPatch({ bullets: v })} rows={3} hint="en per rad" />
                <Field.Text label="Bild-URL" value={tab.imageUrl} onChange={(v) => tabPatch({ imageUrl: v })} placeholder="https://..." />
                <Field.Text label="Bild alt" value={tab.imageAlt} onChange={(v) => tabPatch({ imageAlt: v })} />
                <Field.Buttons label="Knapp 1" segments={[
                  { value: tab.ctaText, onChange: (v) => tabPatch({ ctaText: v }), placeholder: 'Text' },
                  { value: tab.ctaUrl, onChange: (v) => tabPatch({ ctaUrl: v }), placeholder: 'URL' },
                ]} />
                <Field.Buttons label="Knapp 2" segments={[
                  { value: tab.cta2Text, onChange: (v) => tabPatch({ cta2Text: v }), placeholder: 'Text' },
                  { value: tab.cta2Url, onChange: (v) => tabPatch({ cta2Url: v }), placeholder: 'URL' },
                ]} />
                <Field.Checkbox label="Aktiv" checked={tab.isActive} onChange={(v) => tabPatch({ isActive: v })} />
              </RepeaterCard>
            );
          })}
        </div>
        <button
          type="button"
          onClick={addTab}
          className="w-full mt-2 py-2 text-sm text-gray-300 hover:text-gray-500 transition-colors"
        >
          + Lägg till flik
        </button>
      </div>
    </>
  );
}

// ─── team_grid ───────────────────────────────────────────────────────────────

export function TeamGridForm({ section, patch }: SectionEditorContext<TeamGridSection>) {
  return (
    <>
      <Field.Text label="Eyebrow" value={section.eyebrow} onChange={(v) => patch({ eyebrow: v })} />
      <Field.Text label="Rubrik (H2)" value={section.h2} onChange={(v) => patch({ h2: v })} />
      <Field.RichText label="Brödtext" value={section.body} onChange={(v) => patch({ body: v })} rows={3} />
      <Field.Select<TgVariant> label="Variant" value={section.variant} onChange={(v) => patch({ variant: v })} options={TG_VARIANT_OPTS} />
      <Field.Text label="Land-scope" value={section.scopeCountry} onChange={(v) => patch({ scopeCountry: v })} placeholder="ärv från sidan om tom" />
      <Field.Text label="Division-scope" value={section.scopeDivision} onChange={(v) => patch({ scopeDivision: v })} placeholder="ärv från sidan om tom" />
      <Field.Text label="Max antal" type="number" value={String(section.limit || '')} onChange={(v) => patch({ limit: Number(v) || 0 })} placeholder="0 = obegränsat" />
      <p className="text-[10px] text-gray-400">Manuellt valda coworkers (från core_coworkers) visas först.</p>
    </>
  );
}

// ─── partner_list ────────────────────────────────────────────────────────────

export function PartnerListForm({ section, patch }: SectionEditorContext<PartnerListSection>) {
  return (
    <>
      <Field.Text label="Eyebrow" value={section.eyebrow} onChange={(v) => patch({ eyebrow: v })} />
      <Field.Text label="Rubrik (H2)" value={section.h2} onChange={(v) => patch({ h2: v })} />
      <Field.RichText label="Brödtext" value={section.body} onChange={(v) => patch({ body: v })} rows={3} />
      <Field.Select<PlVariant> label="Variant" value={section.variant} onChange={(v) => patch({ variant: v })} options={PL_VARIANT_OPTS} />
      <Field.Text label="Division-scope" value={section.scopeDivision} onChange={(v) => patch({ scopeDivision: v })} placeholder="ärv från sidan om tom" />
      <Field.Text label="Land-scope" value={section.scopeCountry} onChange={(v) => patch({ scopeCountry: v })} placeholder="ärv från sidan om tom" />
      <Field.Text label="Max antal" type="number" value={String(section.limit || '')} onChange={(v) => patch({ limit: Number(v) || 0 })} placeholder="0 = obegränsat" />
      <p className="text-[10px] text-gray-400">Manuellt valda partners (från core_partners) visas först.</p>
    </>
  );
}

// ─── faq ─────────────────────────────────────────────────────────────────────

export function FaqForm({ section, patch }: SectionEditorContext<FaqSection>) {
  const setItems = (next: FaqItem[]) => patch({ items: next });
  const patchItem = (i: number, p: Partial<FaqItem>) =>
    setItems(section.items.map((it, j) => (j === i ? { ...it, ...p } : it)));
  const addItem = () => setItems([...section.items, emptyFaqItem()]);
  const removeItem = (i: number) => setItems(section.items.filter((_, j) => j !== i));
  const moveItem = (i: number, dir: -1 | 1) => {
    const j = i + dir;
    if (j < 0 || j >= section.items.length) return;
    const next = [...section.items];
    const [m] = next.splice(i, 1);
    next.splice(j, 0, m);
    setItems(next);
  };

  return (
    <>
      <Field.Text label="Eyebrow" value={section.eyebrow} onChange={(v) => patch({ eyebrow: v })} />
      <Field.Text label="Rubrik (H2)" value={section.h2} onChange={(v) => patch({ h2: v })} />
      <Field.RichText label="Brödtext" value={section.body} onChange={(v) => patch({ body: v })} rows={3} />

      <div className="pt-2 mt-2 border-t border-gray-200/60">
        <p className="text-[11px] text-gray-400 mb-2">Frågor och svar</p>
        {section.items.length === 0 && (
          <p className="text-[11px] text-gray-300 italic mb-2">Inga FAQ:er — klicka &quot;Lägg till&quot; nedan.</p>
        )}
        <div className="space-y-2">
          {section.items.map((item, i) => (
            <RepeaterCard
              key={item.clientId}
              index={i}
              title={item.question}
              onTitleChange={(v) => patchItem(i, { question: v })}
              titlePlaceholder="Frågetext…"
              onMoveUp={() => moveItem(i, -1)}
              onMoveDown={() => moveItem(i, 1)}
              canMoveUp={i > 0}
              canMoveDown={i < section.items.length - 1}
              onRemove={() => removeItem(i)}
              removeTitle="Ta bort FAQ"
              defaultOpen={!item.question.trim() && !item.answer.trim()}
            >
              <Field.RichText
                label="Svar"
                value={item.answer}
                onChange={(v) => patchItem(i, { answer: v })}
                rows={4}
                placeholder="Svaret på frågan…"
              />
            </RepeaterCard>
          ))}
        </div>
        <button
          type="button"
          onClick={addItem}
          className="w-full py-2 text-sm text-gray-300 hover:text-gray-500 transition-colors"
        >
          + Lägg till FAQ
        </button>
      </div>
    </>
  );
}

// ─── testimonial ─────────────────────────────────────────────────────────────

export function TestimonialForm({ section, patch }: SectionEditorContext<TestimonialSection>) {
  return (
    <>
      <Field.Text label="Eyebrow" value={section.eyebrow} onChange={(v) => patch({ eyebrow: v })} />
      <Field.RichText label="Citat" value={section.quote} onChange={(v) => patch({ quote: v })} rows={4} />
      <Field.Text label="Författarnamn" value={section.authorName} onChange={(v) => patch({ authorName: v })} />
      <Field.Text label="Författartitel" value={section.authorTitle} onChange={(v) => patch({ authorTitle: v })} />
      <Field.Text label="Författarbild-URL" value={section.authorImageUrl} onChange={(v) => patch({ authorImageUrl: v })} placeholder="https://..." />
      <Field.Checkbox label="Endast featured-citat (från SSOT)" checked={section.featuredOnly} onChange={(v) => patch({ featuredOnly: v })} />
      <Field.Text label="Land-scope" value={section.scopeCountry} onChange={(v) => patch({ scopeCountry: v })} placeholder="ärv från sidan om tom" />
      <Field.Text label="Division-scope" value={section.scopeDivision} onChange={(v) => patch({ scopeDivision: v })} placeholder="ärv från sidan om tom" />
      <Field.Text label="Kundtyp-scope" value={section.scopeCustomerType} onChange={(v) => patch({ scopeCustomerType: v })} />
      <p className="text-[10px] text-gray-400">Manuella val: skickas via t_testimonial_manual_ids (UI för manuell länkning saknas i v1).</p>
    </>
  );
}

// ─── cta_banner ──────────────────────────────────────────────────────────────

export function CtaBannerForm({ section, patch }: SectionEditorContext<CtaBannerSection>) {
  return (
    <>
      <Field.Text label="Eyebrow" value={section.eyebrow} onChange={(v) => patch({ eyebrow: v })} />
      <Field.Text label="Rubrik (H2)" value={section.h2} onChange={(v) => patch({ h2: v })} />
      <Field.RichText label="Brödtext" value={section.body} onChange={(v) => patch({ body: v })} rows={4} />
      <Field.Text label="Bild-URL" value={section.imageUrl} onChange={(v) => patch({ imageUrl: v })} placeholder="https://..." />
      <Field.Buttons label="Knapp 1" segments={[
        { value: section.ctaText, onChange: (v) => patch({ ctaText: v }), placeholder: 'Text' },
        { value: section.ctaUrl, onChange: (v) => patch({ ctaUrl: v }), placeholder: 'URL' },
      ]} />
      <Field.Buttons label="Knapp 2" segments={[
        { value: section.cta2Text, onChange: (v) => patch({ cta2Text: v }), placeholder: 'Text' },
        { value: section.cta2Url, onChange: (v) => patch({ cta2Url: v }), placeholder: 'URL' },
      ]} />
    </>
  );
}

// ─── contact_form ────────────────────────────────────────────────────────────

export function ContactFormForm({ section, patch }: SectionEditorContext<ContactFormSection>) {
  return (
    <>
      <Field.Text label="Eyebrow" value={section.eyebrow} onChange={(v) => patch({ eyebrow: v })} />
      <Field.Text label="Titel" value={section.title} onChange={(v) => patch({ title: v })} />
      <Field.Textarea label="Underrubrik" value={section.subtitle} onChange={(v) => patch({ subtitle: v })} rows={3} />
      <Field.Select<CfLayout> label="Layout" value={section.cfLayout} onChange={(v) => patch({ cfLayout: v })} options={CF_LAYOUT_OPTS} />
      <div className="flex flex-wrap gap-4">
        <Field.Checkbox label="Visa företagsfält" checked={section.showCompany} onChange={(v) => patch({ showCompany: v })} />
        <Field.Checkbox label="Visa telefonfält" checked={section.showPhone} onChange={(v) => patch({ showPhone: v })} />
        <Field.Checkbox label="Visa dropdown" checked={section.showDropdown} onChange={(v) => patch({ showDropdown: v })} />
        <Field.Checkbox label="Visa kontaktperson" checked={section.showContactPerson} onChange={(v) => patch({ showContactPerson: v })} />
      </div>
      <Field.Text label="Dropdown-label" value={section.dropdownLabel} onChange={(v) => patch({ dropdownLabel: v })} />
      <Field.Textarea label="Dropdown-alternativ" value={section.options} onChange={(v) => patch({ options: v })} rows={4} hint="en per rad" />
      <Field.Text label="Skicka-knapp-text" value={section.ctaText} onChange={(v) => patch({ ctaText: v })} placeholder="Skicka förfrågan" />
      <Field.Text label="Meddelande-label" value={section.messageLabel} onChange={(v) => patch({ messageLabel: v })} placeholder="Meddelande" />
      <Field.Textarea
        label="Trust signals"
        value={section.trustSignals}
        onChange={(v) => patch({ trustSignals: v })}
        rows={3}
        hint="**Bold** | Resten, max 3 rader"
        placeholder={'**Svar inom 24h** | i normalfallet\n**Lokal teknisk support** | i Norden'}
      />
      <Field.Text label="Land-scope för kontaktperson" value={section.contactScopeCountry} onChange={(v) => patch({ contactScopeCountry: v })} placeholder="ärv från sidan om tom" />
      <Field.Text label="Division-scope för kontaktperson" value={section.contactScopeDivision} onChange={(v) => patch({ contactScopeDivision: v })} placeholder="ärv från sidan om tom" />
    </>
  );
}
