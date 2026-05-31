/**
 * Forward mapping mellan Airtable `cms_partner_pages`-records och
 * `PartnerPageState`.
 *
 * Bara `fromRecord`-riktningen — state → Airtable går via den deterministiska
 * skrivvägen (`buildPartnerFields` i `deterministic-transform.ts`).
 * snake_case-konvention både i Airtable och i denna mapper.
 *
 * Special-fält:
 *   - `facts`: 4 fasta slots i Airtable (facts_1..facts_4) packas till en
 *     array av längd 4 i state. Tomma slots blir tomma `PartnerQuickFact`.
 *   - `why_benefits`: lines-typ i Airtable; Core normaliserar redan till en
 *     `string[]`. Vi accepterar både string och string[] för robusthet
 *     mot legacy-records eller direkt Airtable-läsning.
 *   - `faqs`: JSON-string i Airtable. Tomt/ogiltigt JSON → tom array.
 *     `clientId` genereras vid load så React-keys är stabila.
 *   - `show_quick_facts` har default `true` i schemat — om någon fact är
 *     ifylld behåller vi `true`, annars läses raw bool.
 */

import { AirtableRecord, BASE_ID } from './airtable';
import {
  PartnerPageState,
  PartnerQuickFact,
  QuickFactIcon,
  QUICK_FACT_ICON_KEYS,
} from './partner-types';
import { str, bool, linkedIds, asString } from './airtable-helpers';
import { contactFormFromFields } from './contact-form-mapper';
import { faqItemsFromJson } from './faq-block';

export const PARTNER_BASE_ID = BASE_ID;

export const PARTNER_TABLE_IDS = {
  partnerPages: 'tblQv5E8pSgwxy6wU',
  /** För picker-dropdowns. Håll i synk med entities/cases.php. */
  cases: 'tblxH3ECSMvDTYrIQ',
  /** För picker-dropdowns. Håll i synk med entities/product_areas.php. */
  productAreas: 'tbl5PQR7FNHCogeya',
} as const;

const ICON_KEY_SET: ReadonlySet<string> = new Set(QUICK_FACT_ICON_KEYS);

function normalizeIcon(v: unknown): QuickFactIcon {
  const s = asString(v).trim();
  return s !== '' && ICON_KEY_SET.has(s) ? (s as QuickFactIcon) : '';
}

function readQuickFacts(f: Record<string, unknown>): PartnerQuickFact[] {
  const facts: PartnerQuickFact[] = [];
  for (let i = 1; i <= 4; i++) {
    facts.push({
      icon: normalizeIcon(f[`facts_${i}_icon`]),
      value: str(f, `facts_${i}_value`),
      label: str(f, `facts_${i}_label`),
    });
  }
  return facts;
}

/**
 * `why_benefits` är `type:lines` i Core-schemat så den normaliseras
 * normalt till `string[]` av Core. Vi accepterar dock även en rå
 * multilineText-sträng som fallback om en mapper-kund läser direkt
 * från Airtable utan normalisering.
 */
function readBenefits(v: unknown): string[] {
  if (Array.isArray(v)) {
    return v
      .filter((x): x is string => typeof x === 'string')
      .map((x) => x.trim())
      .filter((x) => x !== '');
  }
  if (typeof v === 'string' && v.trim() !== '') {
    return v
      .split(/\r?\n/)
      .map((x) => x.trim())
      .filter((x) => x !== '');
  }
  return [];
}

export function partnerPageStateFromRecord(record: AirtableRecord): PartnerPageState {
  const f = record.fields;

  const facts = readQuickFacts(f);
  const factsHaveContent = facts.some(
    (fact) => fact.icon !== '' || fact.value !== '' || fact.label !== '',
  );
  // show_quick_facts har default true i schemat — om bool saknas men något
  // fact är ifyllt visar vi sektionen, annars följer vi raw-värdet.
  const rawShowQuickFacts = bool(f, 'show_quick_facts', factsHaveContent);

  return {
    recordId: record.id,

    slug: str(f, 'slug'),
    isActive: bool(f, 'is_active'),
    internalNotes: str(f, 'internal_notes'),
    partnerIds: linkedIds(f, 'partner_ids'),
    countryIds: linkedIds(f, 'country_ids'),

    seoTitle: str(f, 'seo_title'),
    seoDescription: str(f, 'seo_description'),
    ogImageUrl: str(f, 'og_image_url'),

    heroEyebrow: str(f, 'hero_eyebrow'),
    h1: str(f, 'h1'),
    heroTagline: str(f, 'hero_tagline'),
    heroCtaText: str(f, 'hero_cta_text'),
    heroCtaUrl: str(f, 'hero_cta_url'),
    heroCta2Text: str(f, 'hero_cta2_text'),
    heroCta2Url: str(f, 'hero_cta2_url'),
    heroImageUrl: str(f, 'hero_image_url'),

    showQuickFacts: rawShowQuickFacts,
    facts,

    showAbout: bool(f, 'show_about'),
    aboutEyebrow: str(f, 'about_eyebrow'),
    aboutH2: str(f, 'about_h2'),
    aboutText: str(f, 'about_text'),
    aboutImageUrl: str(f, 'about_image_url'),
    aboutBadgeValue: str(f, 'about_badge_value'),
    aboutBadgeLabel: str(f, 'about_badge_label'),

    showWhyWexoe: bool(f, 'show_why_wexoe'),
    whyH2: str(f, 'why_h2'),
    whyText: str(f, 'why_text'),
    whyBenefits: readBenefits(f['why_benefits']),
    caseIds: linkedIds(f, 'case_ids'),
    casesViewAllText: str(f, 'cases_view_all_text'),
    casesViewAllUrl: str(f, 'cases_view_all_url'),

    showCategories: bool(f, 'show_categories'),
    categoriesEyebrow: str(f, 'categories_eyebrow'),
    categoriesH2: str(f, 'categories_h2'),
    categoriesIntro: str(f, 'categories_intro'),
    categoryIds: linkedIds(f, 'category_ids'),

    showFaq: bool(f, 'show_faq'),
    faqH2: str(f, 'faq_h2'),
    faqs: faqItemsFromJson(f['faqs']) ?? [],

    showContactPerson: bool(f, 'show_contact_person'),
    contactName: str(f, 'contact_name'),
    contactTitle: str(f, 'contact_title'),
    contactEmail: str(f, 'contact_email'),
    contactPhone: str(f, 'contact_phone'),
    contactImageUrl: str(f, 'contact_image_url'),
    contactQuote: str(f, 'contact_quote'),

    showContactForm: bool(f, 'show_contact_form'),
    contactForm: contactFormFromFields(f, 'snake_case'),
  };
}
