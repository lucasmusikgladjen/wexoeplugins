'use client';

import { CmsPageState, PageSection, SECTION_TYPE_LABELS, TabsSection } from '@/lib/cms-page-types';
import { Field } from '@/components/shared/fields';
import { SectionDesign } from './SectionFormsCommon';
import {
  CaseGridForm,
  CatalogForm,
  CompanyDataStripForm,
  ContactFormForm,
  CtaBannerForm,
  FaqForm,
  HeroForm,
  NewsGridForm,
  NewsTextSplitForm,
  PartnerListForm,
  TabsForm,
  TeamGridForm,
  TestimonialForm,
  TextImageForm,
  TextOnlyForm,
} from './sections/AllSectionForms';

function renderSectionFields(section: PageSection, patch: (changes: Partial<PageSection>) => void) {
  switch (section.type) {
    case 'hero': return <HeroForm section={section} patch={patch as (c: Partial<typeof section>) => void} />;
    case 'text_image': return <TextImageForm section={section} patch={patch as (c: Partial<typeof section>) => void} />;
    case 'text_only': return <TextOnlyForm section={section} patch={patch as (c: Partial<typeof section>) => void} />;
    case 'company_data_strip': return <CompanyDataStripForm section={section} patch={patch as (c: Partial<typeof section>) => void} />;
    case 'news_text_split': return <NewsTextSplitForm section={section} patch={patch as (c: Partial<typeof section>) => void} />;
    case 'case_grid': return <CaseGridForm section={section} patch={patch as (c: Partial<typeof section>) => void} />;
    case 'news_grid': return <NewsGridForm section={section} patch={patch as (c: Partial<typeof section>) => void} />;
    case 'catalog': return <CatalogForm section={section} patch={patch as (c: Partial<typeof section>) => void} />;
    case 'tabs': return <TabsForm section={section as TabsSection} patch={patch as (c: Partial<TabsSection>) => void} />;
    case 'team_grid': return <TeamGridForm section={section} patch={patch as (c: Partial<typeof section>) => void} />;
    case 'partner_list': return <PartnerListForm section={section} patch={patch as (c: Partial<typeof section>) => void} />;
    case 'faq': return <FaqForm section={section} patch={patch as (c: Partial<typeof section>) => void} />;
    case 'testimonial': return <TestimonialForm section={section} patch={patch as (c: Partial<typeof section>) => void} />;
    case 'cta_banner': return <CtaBannerForm section={section} patch={patch as (c: Partial<typeof section>) => void} />;
    case 'contact_form': return <ContactFormForm section={section} patch={patch as (c: Partial<typeof section>) => void} />;
  }
}

interface Props {
  state: CmsPageState;
  onChange: (next: CmsPageState) => void;
  index: number;
}

export default function CmsPageSectionEditor({ state, onChange, index }: Props) {
  const section = state.sections[index];
  if (!section) return null;

  const patch = (changes: Partial<PageSection>) => {
    onChange({
      ...state,
      sections: state.sections.map((s, i) => (i === index ? ({ ...s, ...changes } as PageSection) : s)),
    });
  };

  const move = (dir: -1 | 1) => {
    const j = index + dir;
    if (j < 0 || j >= state.sections.length) return;
    const next = [...state.sections];
    const [m] = next.splice(index, 1);
    next.splice(j, 0, m);
    onChange({ ...state, sections: next });
  };

  const remove = () => onChange({ ...state, sections: state.sections.filter((_, i) => i !== index) });

  const titleFallback = `${SECTION_TYPE_LABELS[section.type]} #${index + 1}`;

  return (
    <div className="space-y-3">
      <div className="grid grid-cols-2 gap-2">
        <button type="button" onClick={() => move(-1)} disabled={index === 0} className="rounded border px-2 py-1 text-xs disabled:opacity-40">Flytta upp</button>
        <button type="button" onClick={() => move(1)} disabled={index === state.sections.length - 1} className="rounded border px-2 py-1 text-xs disabled:opacity-40">Flytta ned</button>
      </div>
      <button type="button" onClick={remove} className="w-full rounded border border-red-200 text-red-600 px-2 py-1 text-xs">Ta bort sektion</button>
      <Field.Text label="Internt namn" value={section.internalLabel} onChange={(v) => patch({ internalLabel: v })} placeholder={titleFallback} />
      <p className="text-[10px] uppercase tracking-wider text-gray-300">{SECTION_TYPE_LABELS[section.type]}</p>
      <Field.Checkbox label="Aktiv" checked={section.isActive} onChange={(v) => patch({ isActive: v })} />
      {renderSectionFields(section, patch)}
      <SectionDesign section={section} patch={(changes) => patch(changes)} />
    </div>
  );
}
