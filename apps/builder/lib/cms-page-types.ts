/**
 * State-typer för cms_pages-editorn (informationssidor: start, om-oss, pillar).
 *
 * Sidtypen skiljer sig från övriga genom att ha en POLYMORF lista av
 * sektioner — 0..N records ur cms_page_sections där `section_type` är
 * diskriminator. En av typerna (`tabs`) har sina egna sub-records i
 * cms_section_tabs.
 *
 * Statet håller:
 *   - Page-metadata (cms_pages-fält)
 *   - sections: PageSection[] — diskriminerad union, en variant per typ
 *
 * Editorns 2 paneler (metadata + sections) speglar inte sektionerna 1:1.
 * Sections-panelen renderar listan dynamiskt via RepeaterCard-mönstret;
 * varje korts inre editor är typ-medveten.
 */

import { FaqItem } from './faq-block';

// ─── Singleselect-värden (matchar Airtable-choices exakt) ─────────────────

export type SectionType =
  | 'hero'
  | 'text_image'
  | 'text_only'
  | 'company_data_strip'
  | 'news_text_split'
  | 'case_grid'
  | 'news_grid'
  | 'catalog'
  | 'tabs'
  | 'team_grid'
  | 'partner_list'
  | 'faq'
  | 'testimonial'
  | 'cta_banner'
  | 'contact_form';

export type PageTheme = 'light' | 'dark';
export type SectionTheme = 'light' | 'dark' | 'inherit';
export type MaxWidth = 'narrow' | 'normal' | 'wide' | 'full';
export type Layout = 'contained' | 'full_bleed' | 'narrow';
export type Padding = 'none' | 'sm' | 'md' | 'lg';
export type TextAlign = 'left' | 'center';
export type GridCols = '2' | '3' | '4';
export type TgVariant = 'cards' | 'rack' | 'compact';
export type PlVariant = 'marquee' | 'grid' | 'list';
export type CfLayout = 'split' | 'centered';

export const SECTION_TYPES: readonly SectionType[] = [
  'hero',
  'text_image',
  'text_only',
  'company_data_strip',
  'news_text_split',
  'case_grid',
  'news_grid',
  'catalog',
  'tabs',
  'team_grid',
  'partner_list',
  'faq',
  'testimonial',
  'cta_banner',
  'contact_form',
] as const;

export const SECTION_TYPE_LABELS: Record<SectionType, string> = {
  hero: 'Hero',
  text_image: 'Text + bild',
  text_only: 'Endast text',
  company_data_strip: 'Företagsdata',
  news_text_split: 'Nyheter + text',
  case_grid: 'Case-grid',
  news_grid: 'Nyhets-grid',
  catalog: 'Katalog',
  tabs: 'Tabs',
  team_grid: 'Team-grid',
  partner_list: 'Partner-lista',
  faq: 'FAQ',
  testimonial: 'Citat',
  cta_banner: 'CTA-banner',
  contact_form: 'Kontaktformulär',
};

// ─── Universella fält per sektion ──────────────────────────────────────────

export interface BaseSection {
  /** Stabil React-key som överlever spar (recordId byts från '' till rec...). */
  clientId: string;
  /** Tom för osparade sektioner; Airtable rec... efter create. */
  recordId: string;
  /** Discriminator. */
  type: SectionType;
  internalLabel: string;
  internalNotes: string;
  isActive: boolean;
  anchorId: string;
  layout: Layout;
  theme: SectionTheme;
  topPadding: Padding;
  bottomPadding: Padding;
}

// ─── Type-specifika sektioner ──────────────────────────────────────────────

export interface HeroSection extends BaseSection {
  type: 'hero';
  eyebrow: string;
  h1: string;
  subtitle: string;
  imageUrl: string;
  ctaText: string;
  ctaUrl: string;
  cta2Text: string;
  cta2Url: string;
}

export interface TextImageSection extends BaseSection {
  type: 'text_image';
  eyebrow: string;
  h2: string;
  body: string;
  bullets: string;
  imageUrl: string;
  imageAlt: string;
  reversed: boolean;
  ctaText: string;
  ctaUrl: string;
  cta2Text: string;
  cta2Url: string;
}

export interface TextOnlySection extends BaseSection {
  type: 'text_only';
  eyebrow: string;
  h2: string;
  body: string;
  align: TextAlign;
}

export interface CompanyDataStripSection extends BaseSection {
  type: 'company_data_strip';
  h2: string;
  useCompanySingleton: boolean;
  countryCode: string;
  items: string;
}

export interface NewsTextSplitSection extends BaseSection {
  type: 'news_text_split';
  eyebrow: string;
  h2: string;
  body: string;
  ctaText: string;
  ctaUrl: string;
  newsManualIds: string[];
  scopeDivision: string;
  scopeCountry: string;
  limit: number;
}

export interface CaseGridSection extends BaseSection {
  type: 'case_grid';
  eyebrow: string;
  h2: string;
  body: string;
  caseManualIds: string[];
  scopeCountry: string;
  scopeDivision: string;
  scopeCustomerType: string;
  limit: number;
  columns: GridCols;
}

export interface NewsGridSection extends BaseSection {
  type: 'news_grid';
  eyebrow: string;
  h2: string;
  articleManualIds: string[];
  scopeCountry: string;
  scopeDivision: string;
  scopeTopic: string;
  limit: number;
  columns: GridCols;
}

export interface CatalogSection extends BaseSection {
  type: 'catalog';
  eyebrow: string;
  h2: string;
  introBody: string;
  includeProducts: boolean;
  includeArticles: boolean;
  scopeDivision: string;
  scopeCountry: string;
  facetFields: string;
  placeholder: string;
  emptyText: string;
}

/** Sub-record av cms_section_tabs (parent: TabsSection). */
export interface TabItem {
  clientId: string;
  recordId: string;
  name: string;
  internalNotes: string;
  isActive: boolean;
  eyebrow: string;
  h2: string;
  body: string;
  bullets: string;
  imageUrl: string;
  imageAlt: string;
  ctaText: string;
  ctaUrl: string;
  cta2Text: string;
  cta2Url: string;
}

export interface TabsSection extends BaseSection {
  type: 'tabs';
  eyebrow: string;
  h2: string;
  introBody: string;
  tabs: TabItem[];
}

export interface TeamGridSection extends BaseSection {
  type: 'team_grid';
  eyebrow: string;
  h2: string;
  body: string;
  variant: TgVariant;
  coworkerManualIds: string[];
  scopeCountry: string;
  scopeDivision: string;
  limit: number;
}

export interface PartnerListSection extends BaseSection {
  type: 'partner_list';
  eyebrow: string;
  h2: string;
  body: string;
  variant: PlVariant;
  partnerManualIds: string[];
  scopeDivision: string;
  scopeCountry: string;
  limit: number;
}

export interface FaqSection extends BaseSection {
  type: 'faq';
  eyebrow: string;
  h2: string;
  body: string;
  /** FAS 3: strukturerad delad FAQ-modell (tidigare en rå Q:/A:-sträng). */
  items: FaqItem[];
}

export interface TestimonialSection extends BaseSection {
  type: 'testimonial';
  eyebrow: string;
  quote: string;
  authorName: string;
  authorTitle: string;
  authorImageUrl: string;
  testimonialManualIds: string[];
  scopeCountry: string;
  scopeDivision: string;
  scopeCustomerType: string;
  featuredOnly: boolean;
}

export interface CtaBannerSection extends BaseSection {
  type: 'cta_banner';
  eyebrow: string;
  h2: string;
  body: string;
  ctaText: string;
  ctaUrl: string;
  cta2Text: string;
  cta2Url: string;
  imageUrl: string;
}

export interface ContactFormSection extends BaseSection {
  type: 'contact_form';
  eyebrow: string;
  title: string;
  subtitle: string;
  cfLayout: CfLayout;
  showCompany: boolean;
  showPhone: boolean;
  showDropdown: boolean;
  dropdownLabel: string;
  options: string;
  ctaText: string;
  messageLabel: string;
  trustSignals: string;
  showContactPerson: boolean;
  contactScopeCountry: string;
  contactScopeDivision: string;
}

export type PageSection =
  | HeroSection
  | TextImageSection
  | TextOnlySection
  | CompanyDataStripSection
  | NewsTextSplitSection
  | CaseGridSection
  | NewsGridSection
  | CatalogSection
  | TabsSection
  | TeamGridSection
  | PartnerListSection
  | FaqSection
  | TestimonialSection
  | CtaBannerSection
  | ContactFormSection;

// ─── Page-state ────────────────────────────────────────────────────────────

export interface CmsPageState {
  mode: 'create' | 'edit';
  recordId: string;

  // Metadata
  slug: string;
  internalLabel: string;
  internalNotes: string;
  h1: string;
  seoTitle: string;
  seoDescription: string;
  ogImageUrl: string;
  isPublished: boolean;
  countryIds: string[];
  divisionIds: string[];
  pageTheme: PageTheme;
  maxWidth: MaxWidth;

  // Polymorf sektion-lista
  sections: PageSection[];
}

// ─── ClientId-generator ────────────────────────────────────────────────────

let clientIdCounter = 0;
export function generateClientId(prefix: string): string {
  clientIdCounter += 1;
  return `${prefix}-${Date.now()}-${clientIdCounter}`;
}

// ─── Empty-factories ───────────────────────────────────────────────────────

function emptyBase(type: SectionType): BaseSection {
  return {
    clientId: generateClientId(`section-${type}`),
    recordId: '',
    type,
    internalLabel: '',
    internalNotes: '',
    isActive: true,
    anchorId: '',
    layout: 'contained',
    theme: 'inherit',
    topPadding: 'md',
    bottomPadding: 'md',
  };
}

export function emptyTabItem(): TabItem {
  return {
    clientId: generateClientId('tab'),
    recordId: '',
    name: '',
    internalNotes: '',
    isActive: true,
    eyebrow: '',
    h2: '',
    body: '',
    bullets: '',
    imageUrl: '',
    imageAlt: '',
    ctaText: '',
    ctaUrl: '',
    cta2Text: '',
    cta2Url: '',
  };
}

export function emptySection(type: SectionType): PageSection {
  const base = emptyBase(type);
  switch (type) {
    case 'hero':
      return { ...base, type: 'hero', eyebrow: '', h1: '', subtitle: '', imageUrl: '',
        ctaText: '', ctaUrl: '', cta2Text: '', cta2Url: '' };
    case 'text_image':
      return { ...base, type: 'text_image', eyebrow: '', h2: '', body: '', bullets: '',
        imageUrl: '', imageAlt: '', reversed: false,
        ctaText: '', ctaUrl: '', cta2Text: '', cta2Url: '' };
    case 'text_only':
      return { ...base, type: 'text_only', eyebrow: '', h2: '', body: '', align: 'left' };
    case 'company_data_strip':
      return { ...base, type: 'company_data_strip', h2: '',
        useCompanySingleton: false, countryCode: '', items: '' };
    case 'news_text_split':
      return { ...base, type: 'news_text_split', eyebrow: '', h2: '', body: '',
        ctaText: '', ctaUrl: '',
        newsManualIds: [], scopeDivision: '', scopeCountry: '', limit: 0 };
    case 'case_grid':
      return { ...base, type: 'case_grid', eyebrow: '', h2: '', body: '',
        caseManualIds: [], scopeCountry: '', scopeDivision: '',
        scopeCustomerType: '', limit: 0, columns: '3' };
    case 'news_grid':
      return { ...base, type: 'news_grid', eyebrow: '', h2: '',
        articleManualIds: [], scopeCountry: '', scopeDivision: '',
        scopeTopic: '', limit: 0, columns: '3' };
    case 'catalog':
      return { ...base, type: 'catalog', eyebrow: '', h2: '', introBody: '',
        includeProducts: true, includeArticles: false,
        scopeDivision: '', scopeCountry: '',
        facetFields: '', placeholder: '', emptyText: '' };
    case 'tabs':
      return { ...base, type: 'tabs', eyebrow: '', h2: '', introBody: '', tabs: [] };
    case 'team_grid':
      return { ...base, type: 'team_grid', eyebrow: '', h2: '', body: '',
        variant: 'cards', coworkerManualIds: [],
        scopeCountry: '', scopeDivision: '', limit: 0 };
    case 'partner_list':
      return { ...base, type: 'partner_list', eyebrow: '', h2: '', body: '',
        variant: 'grid', partnerManualIds: [],
        scopeDivision: '', scopeCountry: '', limit: 0 };
    case 'faq':
      return { ...base, type: 'faq', eyebrow: '', h2: '', body: '', items: [] };
    case 'testimonial':
      return { ...base, type: 'testimonial', eyebrow: '', quote: '',
        authorName: '', authorTitle: '', authorImageUrl: '',
        testimonialManualIds: [],
        scopeCountry: '', scopeDivision: '', scopeCustomerType: '',
        featuredOnly: false };
    case 'cta_banner':
      return { ...base, type: 'cta_banner', eyebrow: '', h2: '', body: '',
        ctaText: '', ctaUrl: '', cta2Text: '', cta2Url: '', imageUrl: '' };
    case 'contact_form':
      return { ...base, type: 'contact_form', eyebrow: '', title: '', subtitle: '',
        cfLayout: 'split',
        showCompany: true, showPhone: true, showDropdown: true,
        dropdownLabel: '', options: '', ctaText: '', messageLabel: '',
        trustSignals: '', showContactPerson: true,
        contactScopeCountry: '', contactScopeDivision: '' };
  }
}

export function emptyCmsPageState(): CmsPageState {
  return {
    mode: 'create',
    recordId: '',
    slug: '',
    internalLabel: '',
    internalNotes: '',
    h1: '',
    seoTitle: '',
    seoDescription: '',
    ogImageUrl: '',
    isPublished: false,
    countryIds: [],
    divisionIds: [],
    pageTheme: 'light',
    maxWidth: 'normal',
    sections: [],
  };
}

// ─── Section ID type for navigation/preview ────────────────────────────────

export type CmsPageSectionPanelId = 'metadata' | 'sections';

// ─── Tabell-konstanter ──────────────────────────────────────────────────────

export const CMS_PAGES_TABLE_ID = 'tblglNKHehRWy3lEM';
export const CMS_PAGE_SECTIONS_TABLE_ID = 'tblWDvAe3s45P2Nok';
export const CMS_SECTION_TABS_TABLE_ID = 'tblxEtcLO4N9k83rn';
