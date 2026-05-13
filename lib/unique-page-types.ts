/**
 * State-types för cms_unique_pages-editor i builder.
 */

export type Theme = 'dark' | 'light';
export type TextOnlyAlign = 'left' | 'center';

export interface HeroState {
  eyebrow: string;
  h1Override: string;
  subtitle: string;
  imageUrl: string;
  ctaText: string;
  ctaUrl: string;
  theme: Theme;
}

export interface TextImageState {
  h2: string;
  body: string;
  imageUrl: string;
  reversed: boolean;
  theme: Theme;
}

export interface TextOnlyState {
  h2: string;
  body: string;
  align: TextOnlyAlign;
}

export interface FaqState {
  h2: string;
  items: string; // multiline, en per rad
}

export interface ScopeFilter {
  division: string;
  country: string;
  customerType?: string;
  limit?: number;
}

export interface TeamGridState {
  h2: string;
  scope: ScopeFilter;
}

export interface PartnersMarqueeState {
  h2: string;
  scope: ScopeFilter;
}

export interface TestimonialCardState {
  scope: ScopeFilter;
}

export interface CtaBannerState {
  h2: string;
  body: string;
  ctaText: string;
  ctaUrl: string;
  theme: Theme;
}

export type ContactFormLayout = 'split' | 'centered';
export interface ContactFormState {
  eyebrow: string;
  title: string;
  subtitle: string;
  layout: ContactFormLayout;
  theme: Theme;
  showCompany: boolean;
  showPhone: boolean;
  showDropdown: boolean;
  dropdownLabel: string;
  options: string;
  ctaText: string;
  messageLabel: string;
  trustSignals: string;
  showContactPerson: boolean;
}

export interface UniquePageState {
  mode: 'create' | 'edit';
  recordId?: string;

  // Metadata
  slug: string;
  h1: string;
  seoTitle: string;
  seoDescription: string;
  ogImageUrl: string;
  published: boolean;
  countryIds: string[];
  divisionIds: string[];

  // Sektion-visibility + state
  showHero: boolean;
  hero: HeroState;

  showTextImageA: boolean;
  textImageA: TextImageState;

  showTextImageB: boolean;
  textImageB: TextImageState;

  showTextOnly: boolean;
  textOnly: TextOnlyState;

  showFaq: boolean;
  faq: FaqState;

  showTeamGrid: boolean;
  teamGrid: TeamGridState;

  showPartnersMarquee: boolean;
  partnersMarquee: PartnersMarqueeState;

  showTestimonialCard: boolean;
  testimonialCard: TestimonialCardState;

  showCtaBanner: boolean;
  ctaBanner: CtaBannerState;

  showContactForm: boolean;
  contactForm: ContactFormState;
}

export function emptyHeroState(): HeroState {
  return { eyebrow: '', h1Override: '', subtitle: '', imageUrl: '', ctaText: '', ctaUrl: '', theme: 'dark' };
}
export function emptyTextImageState(): TextImageState {
  return { h2: '', body: '', imageUrl: '', reversed: false, theme: 'light' };
}
export function emptyTextOnlyState(): TextOnlyState {
  return { h2: '', body: '', align: 'left' };
}
export function emptyFaqState(): FaqState {
  return { h2: '', items: '' };
}
export function emptyScopeFilter(): ScopeFilter {
  return { division: '', country: '', customerType: '', limit: 0 };
}
export function emptyCtaBannerState(): CtaBannerState {
  return { h2: '', body: '', ctaText: '', ctaUrl: '', theme: 'dark' };
}
export function emptyContactFormState(): ContactFormState {
  return {
    eyebrow: '',
    title: '',
    subtitle: '',
    layout: 'split',
    theme: 'dark',
    showCompany: true,
    showPhone: true,
    showDropdown: true,
    dropdownLabel: '',
    options: '',
    ctaText: '',
    messageLabel: '',
    trustSignals: '',
    showContactPerson: true,
  };
}

export function emptyUniquePageState(): UniquePageState {
  return {
    mode: 'create',
    slug: '',
    h1: '',
    seoTitle: '',
    seoDescription: '',
    ogImageUrl: '',
    published: false,
    countryIds: [],
    divisionIds: [],

    showHero: false,
    hero: emptyHeroState(),

    showTextImageA: false,
    textImageA: emptyTextImageState(),

    showTextImageB: false,
    textImageB: emptyTextImageState(),

    showTextOnly: false,
    textOnly: emptyTextOnlyState(),

    showFaq: false,
    faq: emptyFaqState(),

    showTeamGrid: false,
    teamGrid: { h2: '', scope: emptyScopeFilter() },

    showPartnersMarquee: false,
    partnersMarquee: { h2: '', scope: emptyScopeFilter() },

    showTestimonialCard: false,
    testimonialCard: { scope: emptyScopeFilter() },

    showCtaBanner: false,
    ctaBanner: emptyCtaBannerState(),

    showContactForm: false,
    contactForm: emptyContactFormState(),
  };
}

export type UniquePageSectionId =
  | 'metadata'
  | 'hero'
  | 'textImageA'
  | 'textImageB'
  | 'textOnly'
  | 'faq'
  | 'teamGrid'
  | 'partnersMarquee'
  | 'testimonialCard'
  | 'ctaBanner'
  | 'contactForm';
