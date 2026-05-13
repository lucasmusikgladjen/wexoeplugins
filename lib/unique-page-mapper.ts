/**
 * Bidirektional mappning mellan Airtable cms_unique_pages-records och
 * UniquePageState i builder.
 */

import { AirtableRecord } from './airtable';
import {
  UniquePageState,
  emptyUniquePageState,
  Theme,
  TextOnlyAlign,
  ContactFormLayout,
} from './unique-page-types';

function asString(v: unknown): string {
  return typeof v === 'string' ? v : (v == null ? '' : String(v));
}
function asNumber(v: unknown): number {
  if (typeof v === 'number') return v;
  if (typeof v === 'string' && v !== '') { const n = Number(v); if (!Number.isNaN(n)) return n; }
  return 0;
}
function asBool(v: unknown): boolean { return v === true || v === 'true' || v === 1; }
function asLinkIds(v: unknown): string[] {
  return Array.isArray(v) ? v.filter((x): x is string => typeof x === 'string') : [];
}
function asTheme(v: unknown, def: Theme = 'dark'): Theme {
  return v === 'light' ? 'light' : v === 'dark' ? 'dark' : def;
}
function asAlign(v: unknown): TextOnlyAlign {
  return v === 'center' ? 'center' : 'left';
}
function asLayout(v: unknown): ContactFormLayout {
  return v === 'centered' ? 'centered' : 'split';
}
function attachUrl(v: unknown): string {
  if (Array.isArray(v) && v.length > 0) {
    const first = v[0] as { url?: string };
    if (first && typeof first.url === 'string') return first.url;
  }
  return '';
}

export function uniquePageStateFromRecord(rec: AirtableRecord): UniquePageState {
  const f = rec.fields;
  const state = emptyUniquePageState();
  state.mode = 'edit';
  state.recordId = rec.id;

  state.slug = asString(f['Slug']);
  state.h1 = asString(f['H1']);
  state.seoTitle = asString(f['SEO Title']);
  state.seoDescription = asString(f['SEO Description']);
  state.ogImageUrl = asString(f['OG Image URL']);
  state.published = asBool(f['Published']);
  state.countryIds = asLinkIds(f['Country']);
  state.divisionIds = asLinkIds(f['Division']);

  // Hero
  state.showHero = asBool(f['Show Hero']);
  state.hero = {
    eyebrow: asString(f['Hero Eyebrow']),
    h1Override: asString(f['Hero H1 Override']),
    subtitle: asString(f['Hero Subtitle']),
    imageUrl: attachUrl(f['Hero Image']),
    ctaText: asString(f['Hero CTA Text']),
    ctaUrl: asString(f['Hero CTA URL']),
    theme: asTheme(f['Hero Theme']),
  };

  // Text-image A
  state.showTextImageA = asBool(f['Show Text Image A']);
  state.textImageA = {
    h2: asString(f['Text Image A H2']),
    body: asString(f['Text Image A Body']),
    imageUrl: attachUrl(f['Text Image A Image']),
    reversed: asBool(f['Text Image A Reversed']),
    theme: asTheme(f['Text Image A Theme'], 'light'),
  };

  // Text-image B
  state.showTextImageB = asBool(f['Show Text Image B']);
  state.textImageB = {
    h2: asString(f['Text Image B H2']),
    body: asString(f['Text Image B Body']),
    imageUrl: attachUrl(f['Text Image B Image']),
    reversed: asBool(f['Text Image B Reversed']),
    theme: asTheme(f['Text Image B Theme'], 'light'),
  };

  // Text-only
  state.showTextOnly = asBool(f['Show Text Only']);
  state.textOnly = {
    h2: asString(f['Text Only H2']),
    body: asString(f['Text Only Body']),
    align: asAlign(f['Text Only Align']),
  };

  // FAQ
  state.showFaq = asBool(f['Show FAQ']);
  state.faq = {
    h2: asString(f['FAQ H2']),
    items: asString(f['FAQ Items']),
  };

  // Team Grid
  state.showTeamGrid = asBool(f['Show Team Grid']);
  state.teamGrid = {
    h2: asString(f['Team Grid H2']),
    scope: {
      division: asString(f['Team Grid Scope Division']),
      country: asString(f['Team Grid Scope Country']),
      limit: asNumber(f['Team Grid Limit']),
    },
  };

  // Partners Marquee
  state.showPartnersMarquee = asBool(f['Show Partners Marquee']);
  state.partnersMarquee = {
    h2: asString(f['Partners Marquee H2']),
    scope: {
      division: asString(f['Partners Marquee Scope Division']),
      country: asString(f['Partners Marquee Scope Country']),
    },
  };

  // Testimonial Card
  state.showTestimonialCard = asBool(f['Show Testimonial Card']);
  state.testimonialCard = {
    scope: {
      customerType: asString(f['Testimonial Scope Customer Type']),
      division: asString(f['Testimonial Scope Division']),
      country: asString(f['Testimonial Scope Country']),
    },
  };

  // CTA Banner
  state.showCtaBanner = asBool(f['Show CTA Banner']);
  state.ctaBanner = {
    h2: asString(f['CTA Banner H2']),
    body: asString(f['CTA Banner Body']),
    ctaText: asString(f['CTA Banner CTA Text']),
    ctaUrl: asString(f['CTA Banner CTA URL']),
    theme: asTheme(f['CTA Banner Theme']),
  };

  // Contact Form
  state.showContactForm = asBool(f['Show Contact Form']);
  state.contactForm = {
    eyebrow: asString(f['Contact Form Eyebrow']),
    title: asString(f['Contact Form Title']),
    subtitle: asString(f['Contact Form Subtitle']),
    layout: asLayout(f['Contact Form Layout']),
    theme: asTheme(f['Contact Form Theme']),
    showCompany: asBool(f['Contact Form Show Company']),
    showPhone: asBool(f['Contact Form Show Phone']),
    showDropdown: asBool(f['Contact Form Show Dropdown']),
    dropdownLabel: asString(f['Contact Form Dropdown Label']),
    options: asString(f['Contact Form Options']),
    ctaText: asString(f['Contact Form CTA Text']),
    messageLabel: asString(f['Contact Form Message Label']),
    trustSignals: asString(f['Contact Form Trust Signals']),
    showContactPerson: asBool(f['Contact Form Show Contact Person']),
  };

  return state;
}

function attachInput(url: string): unknown {
  return url ? [{ url }] : [];
}

/**
 * Konvertera UniquePageState → Airtable-fält-map.
 *
 * `mode`:
 *   - 'create' (default): tomma värden tas bort innan POST så Airtable använder
 *     fält-defaults.
 *   - 'update': tomma textfält skickas som tom sträng så Airtable rensar dem.
 *     Annars skulle tomma fält utelämnas → Airtable lämnar gamla värdet.
 *     Bool/checkbox-fält och multi-link-arrayer skickas alltid (de är inte
 *     "tomma" på samma sätt — false respektive []).
 */
export function uniquePageStateToFields(
  state: UniquePageState,
  mode: 'create' | 'update' = 'create',
): Record<string, unknown> {
  const fields: Record<string, unknown> = {
    'Slug': state.slug || null,
    'H1': state.h1 || null,
    'SEO Title': state.seoTitle || null,
    'SEO Description': state.seoDescription || null,
    'OG Image URL': state.ogImageUrl || null,
    'Published': !!state.published,
    'Country': state.countryIds,
    'Division': state.divisionIds,

    'Show Hero': state.showHero,
    'Hero Eyebrow': state.hero.eyebrow || null,
    'Hero H1 Override': state.hero.h1Override || null,
    'Hero Subtitle': state.hero.subtitle || null,
    'Hero Image': attachInput(state.hero.imageUrl),
    'Hero CTA Text': state.hero.ctaText || null,
    'Hero CTA URL': state.hero.ctaUrl || null,
    'Hero Theme': state.hero.theme,

    'Show Text Image A': state.showTextImageA,
    'Text Image A H2': state.textImageA.h2 || null,
    'Text Image A Body': state.textImageA.body || null,
    'Text Image A Image': attachInput(state.textImageA.imageUrl),
    'Text Image A Reversed': state.textImageA.reversed,
    'Text Image A Theme': state.textImageA.theme,

    'Show Text Image B': state.showTextImageB,
    'Text Image B H2': state.textImageB.h2 || null,
    'Text Image B Body': state.textImageB.body || null,
    'Text Image B Image': attachInput(state.textImageB.imageUrl),
    'Text Image B Reversed': state.textImageB.reversed,
    'Text Image B Theme': state.textImageB.theme,

    'Show Text Only': state.showTextOnly,
    'Text Only H2': state.textOnly.h2 || null,
    'Text Only Body': state.textOnly.body || null,
    'Text Only Align': state.textOnly.align,

    'Show FAQ': state.showFaq,
    'FAQ H2': state.faq.h2 || null,
    'FAQ Items': state.faq.items || null,

    'Show Team Grid': state.showTeamGrid,
    'Team Grid H2': state.teamGrid.h2 || null,
    'Team Grid Scope Division': state.teamGrid.scope.division || null,
    'Team Grid Scope Country': state.teamGrid.scope.country || null,
    'Team Grid Limit': state.teamGrid.scope.limit ? Number(state.teamGrid.scope.limit) : null,

    'Show Partners Marquee': state.showPartnersMarquee,
    'Partners Marquee H2': state.partnersMarquee.h2 || null,
    'Partners Marquee Scope Division': state.partnersMarquee.scope.division || null,
    'Partners Marquee Scope Country': state.partnersMarquee.scope.country || null,

    'Show Testimonial Card': state.showTestimonialCard,
    'Testimonial Scope Customer Type': state.testimonialCard.scope.customerType || null,
    'Testimonial Scope Division': state.testimonialCard.scope.division || null,
    'Testimonial Scope Country': state.testimonialCard.scope.country || null,

    'Show CTA Banner': state.showCtaBanner,
    'CTA Banner H2': state.ctaBanner.h2 || null,
    'CTA Banner Body': state.ctaBanner.body || null,
    'CTA Banner CTA Text': state.ctaBanner.ctaText || null,
    'CTA Banner CTA URL': state.ctaBanner.ctaUrl || null,
    'CTA Banner Theme': state.ctaBanner.theme,

    'Show Contact Form': state.showContactForm,
    'Contact Form Eyebrow': state.contactForm.eyebrow || null,
    'Contact Form Title': state.contactForm.title || null,
    'Contact Form Subtitle': state.contactForm.subtitle || null,
    'Contact Form Layout': state.contactForm.layout,
    'Contact Form Theme': state.contactForm.theme,
    'Contact Form Show Company': state.contactForm.showCompany,
    'Contact Form Show Phone': state.contactForm.showPhone,
    'Contact Form Show Dropdown': state.contactForm.showDropdown,
    'Contact Form Dropdown Label': state.contactForm.dropdownLabel || null,
    'Contact Form Options': state.contactForm.options || null,
    'Contact Form CTA Text': state.contactForm.ctaText || null,
    'Contact Form Message Label': state.contactForm.messageLabel || null,
    'Contact Form Trust Signals': state.contactForm.trustSignals || null,
    'Contact Form Show Contact Person': state.contactForm.showContactPerson,
  };

  // Vid CREATE: skala bort tomma värden så Airtable använder fält-defaults
  //   och payloaden inte spammas med null.
  // Vid UPDATE: BEHÅLL null så Airtable rensar fältet. Att utelämna fältet
  //   lämnar det orört (PATCH-semantik), så vi måste skicka explicit null för
  //   att en redaktör ska kunna tömma t.ex. SEO-titel eller FAQ-items.
  //   Airtable accepterar null för text/number/url/select-fält.
  const out: Record<string, unknown> = {};
  for (const [k, v] of Object.entries(fields)) {
    if (v === undefined) continue;
    if (v === null) {
      if (mode === 'update') {
        out[k] = null;
      }
      continue;
    }
    out[k] = v;
  }
  return out;
}

export const UNIQUE_PAGES_TABLE_ID = 'tblpAM1wZWDbrpeai';
