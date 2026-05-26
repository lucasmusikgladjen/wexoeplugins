/**
 * Partner-page state model.
 *
 * Speglar Airtable `cms_partner_pages` (tblQv5E8pSgwxy6wU) i Wexoe NY,
 * som drivs av `[wexoe_partner slug="..."]`-shortcoden via
 * `wexoeplugins/New plugins/wexoe-partner-page`.
 *
 * Sektion-ordning matchar `wexoe-partner-page/src/Renderer.php`-rendringen,
 * inte editor-quicknav-ordningen (där `Inställningar` ligger sist enligt
 * konventionen från customer-type/product-area).
 *
 * Schemat har tre länkade fält:
 *   - `partnerIds`  → core_partners (single-pick, pluginet använder första)
 *   - `caseIds`     → cms_cases (max 3 renderas — varning i editorn vid fler)
 *   - `categoryIds` → cms_product_pages (multi-pick, ingen gräns)
 *
 * Quick-facts har 4 fasta slots i Airtable (facts_1_icon..facts_4_label).
 * State exponerar dem som en array av längd 4 för cleaner editor-kod;
 * mapper/Claude-transform packar tillbaka till slot-fält vid spar.
 *
 * `faqs` lagras i Airtable som JSON-sträng `[{question, answer}, ...]` —
 * state har en strukturerad array med `clientId` för React-keys; Claude-
 * transformen serialiserar bort `clientId` vid spar.
 *
 * `whyBenefits` lagras som en lines-textsträng i Airtable (en bullet per
 * rad). State är en `string[]` så editorn kan ha add/remove/move; Claude-
 * transformen joinar med `\n` vid spar.
 */

import { ContactFormState, emptyContactFormState } from './contact-form-types';

/**
 * Quick-facts ikon-keys. Måste hållas i synk med
 * `wexoeplugins/New plugins/wexoe-partner-page/src/Renderer.php::$icons`.
 * Pluginet utelämnar ikonen tyst vid okänd key — editorn ska bara
 * exponera dessa val plus tomsträng (= ingen ikon).
 */
export const QUICK_FACT_ICON_KEYS = [
  'calendar',
  'users',
  'globe',
  'shield',
  'building',
  'factory',
  'award',
  'package',
  'briefcase',
  'target',
] as const;

export type QuickFactIcon = (typeof QUICK_FACT_ICON_KEYS)[number] | '';

export interface PartnerQuickFact {
  icon: QuickFactIcon;
  value: string;
  label: string;
}

export interface PartnerFaqItem {
  /** Stabilt React-key. Genereras lokalt — skickas aldrig till Airtable. */
  clientId: string;
  question: string;
  answer: string;
}

export interface PartnerPageState {
  recordId: string;

  // Identity & meta
  slug: string;
  isActive: boolean;
  internalNotes: string;
  /** core_partners (single-pick, men lagras som array för konsistent picker). */
  partnerIds: string[];
  /** core_countries (multi). */
  countryIds: string[];

  // SEO
  seoTitle: string;
  seoDescription: string;
  ogImageUrl: string;

  // Hero (alltid synlig — ingen show_*-toggle i schemat)
  heroEyebrow: string;
  h1: string;
  heroTagline: string;
  heroCtaText: string;
  heroCtaUrl: string;
  heroCta2Text: string;
  heroCta2Url: string;
  heroImageUrl: string;

  // Quick Facts
  showQuickFacts: boolean;
  /** Längd alltid 4 — fasta slots. Tomma slots utelämnas i rendering. */
  facts: PartnerQuickFact[];

  // About
  showAbout: boolean;
  aboutEyebrow: string;
  aboutH2: string;
  aboutText: string;
  aboutImageUrl: string;
  aboutBadgeValue: string;
  aboutBadgeLabel: string;

  // Why Wexoe + benefits + cases
  showWhyWexoe: boolean;
  whyH2: string;
  whyText: string;
  whyBenefits: string[];
  /** cms_cases-länkar. Pluginet renderar bara de tre första — varning i UI. */
  caseIds: string[];
  casesViewAllText: string;
  casesViewAllUrl: string;

  // Categories
  showCategories: boolean;
  categoriesEyebrow: string;
  categoriesH2: string;
  categoriesIntro: string;
  /** cms_product_pages-länkar. */
  categoryIds: string[];

  // FAQ
  showFaq: boolean;
  faqH2: string;
  faqs: PartnerFaqItem[];

  // Contact Person (navy strip)
  showContactPerson: boolean;
  contactName: string;
  contactTitle: string;
  contactEmail: string;
  contactPhone: string;
  contactImageUrl: string;
  contactQuote: string;

  // Contact Form (shared module)
  showContactForm: boolean;
  contactForm: ContactFormState;
}

export type PartnerPageSectionId =
  | 'hero'
  | 'quickFacts'
  | 'about'
  | 'whyWexoe'
  | 'categories'
  | 'faq'
  | 'contactPerson'
  | 'contactForm'
  | 'settings';

export function emptyQuickFact(): PartnerQuickFact {
  return { icon: '', value: '', label: '' };
}

/** Genererar ett tillräckligt stabilt klient-side ID för React-keys. */
export function newFaqClientId(): string {
  return `faq_${Date.now().toString(36)}_${Math.random().toString(36).slice(2, 8)}`;
}

export function emptyFaqItem(): PartnerFaqItem {
  return { clientId: newFaqClientId(), question: '', answer: '' };
}

export function emptyPartnerPageState(): PartnerPageState {
  return {
    recordId: '',

    slug: '',
    isActive: true,
    internalNotes: '',
    partnerIds: [],
    countryIds: [],

    seoTitle: '',
    seoDescription: '',
    ogImageUrl: '',

    heroEyebrow: '',
    h1: '',
    heroTagline: '',
    heroCtaText: '',
    heroCtaUrl: '',
    heroCta2Text: '',
    heroCta2Url: '',
    heroImageUrl: '',

    showQuickFacts: false,
    facts: [emptyQuickFact(), emptyQuickFact(), emptyQuickFact(), emptyQuickFact()],

    showAbout: false,
    aboutEyebrow: '',
    aboutH2: '',
    aboutText: '',
    aboutImageUrl: '',
    aboutBadgeValue: '',
    aboutBadgeLabel: '',

    showWhyWexoe: false,
    whyH2: '',
    whyText: '',
    whyBenefits: [],
    caseIds: [],
    casesViewAllText: '',
    casesViewAllUrl: '',

    showCategories: false,
    categoriesEyebrow: '',
    categoriesH2: '',
    categoriesIntro: '',
    categoryIds: [],

    showFaq: false,
    faqH2: '',
    faqs: [],

    showContactPerson: false,
    contactName: '',
    contactTitle: '',
    contactEmail: '',
    contactPhone: '',
    contactImageUrl: '',
    contactQuote: '',

    showContactForm: false,
    contactForm: emptyContactFormState(),
  };
}
