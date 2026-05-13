/**
 * Reverse mapping from Airtable records → ProductAreaState, used by the
 * loader to hydrate the builder when editing an existing Product Area.
 *
 * Forward mapping (state → Airtable fields) now lives entirely in Claude
 * via `lib/claude-transform.ts` — there are no deterministic forward
 * mappers in this file anymore.
 */

import {
  ProductAreaState,
  NormalSection,
  LinkedProduct,
  LinkedSolution,
  LinkedArticleReadonly,
  generateClientId,
} from './product-area-types';
import { AirtableRecord } from './airtable';
import { ContactFormState, ContactFormLayout, ContactFormTheme, emptyContactFormState } from './contact-form-types';

type Fields = Record<string, unknown>;

// ─── Table IDs (Product Area family) ───────────────────────────────────────

export const PA_TABLE_IDS = {
  productAreas: 'tblgatNFYFMwF4EcQ',
  products: 'tblHafyCEyh7S3Y64',
  articles: 'tblb87eWIjnW3ttOL',
  solutions: 'tblc98m9MJcpbWAVU',
  divisions: 'tblKam1tUTlR13atl',
} as const;

// ─── Helpers ───────────────────────────────────────────────────────────────

function str(fields: Fields, key: string): string {
  const v = fields[key];
  return typeof v === 'string' ? v : '';
}
function bool(fields: Fields, key: string): boolean {
  return fields[key] === true;
}
function num(fields: Fields, key: string, fallback = 0): number {
  const v = fields[key];
  return typeof v === 'number' ? v : fallback;
}

// ─── Reverse: Airtable → state ─────────────────────────────────────────────

function normalFromFields(fields: Fields, n: 1 | 2 | 3 | 4): NormalSection {
  return {
    h2: str(fields, `Normal ${n} H2`),
    text: str(fields, `Normal ${n} Text`),
    bullets: str(fields, `Normal ${n} Bullets`),
    image: str(fields, `Normal ${n} Image`),
    reversed: bool(fields, `Normal ${n} Reversed`),
    bg: str(fields, `Normal ${n} BG`),
    upp: n === 1 ? bool(fields, 'Normal 1 upp') : false,
  };
}

function articleFromRecord(r: AirtableRecord): LinkedArticleReadonly {
  return {
    recordId: r.id,
    name: str(r.fields, 'Name'),
    artikelnummer: str(r.fields, 'Artikelnummer'),
    description: str(r.fields, 'Description'),
    bild: str(r.fields, 'Bild'),
    datablad: str(r.fields, 'Datablad'),
    lankTillWebshop: str(r.fields, 'Länk till webshop'),
    varianter: str(r.fields, 'Varianter'),
  };
}

function productFromRecord(
  r: AirtableRecord,
  articlesById: Record<string, AirtableRecord>,
): LinkedProduct {
  const articleIds = (r.fields['Articles'] as string[] | undefined) ?? [];
  const articles = articleIds
    .map((id) => articlesById[id])
    .filter((rec): rec is AirtableRecord => !!rec)
    .map(articleFromRecord);

  return {
    clientId: generateClientId('loaded-product'),
    recordId: r.id,
    name: str(r.fields, 'Name'),
    headerSideMenu: str(r.fields, 'Header side menu'),
    description: str(r.fields, 'Description'),
    ecosystemDescription: str(r.fields, 'Ecosystem Description'),
    bullets: str(r.fields, 'Bullets'),
    image: str(r.fields, 'Image'),
    button1Text: str(r.fields, 'Button 1 Text'),
    button1Url: str(r.fields, 'Button 1 URL'),
    button2Text: str(r.fields, 'Button 2 Text'),
    button2Url: str(r.fields, 'Button 2 URL'),
    horizontal: bool(r.fields, 'Horizontal'),
    order: num(r.fields, 'Order'),
    visa: bool(r.fields, 'Visa'),
    articles,
  };
}

function solutionFromRecord(r: AirtableRecord): LinkedSolution {
  return {
    clientId: generateClientId('loaded-solution'),
    recordId: r.id,
    name: str(r.fields, 'Name'),
    image: str(r.fields, 'Image'),
    url: str(r.fields, 'URL'),
    description: str(r.fields, 'Description'),
    category: str(r.fields, 'Category'),
    ctaText: str(r.fields, 'CTA Text'),
    order: num(r.fields, 'Order'),
    visa: bool(r.fields, 'Visa'),
  };
}

export function productAreaStateFromRecords(args: {
  productArea: AirtableRecord;
  products: AirtableRecord[];
  articles: AirtableRecord[];
  solutions: AirtableRecord[];
}): ProductAreaState {
  const { productArea, products, articles, solutions } = args;
  const f = productArea.fields;

  const articlesById: Record<string, AirtableRecord> = {};
  for (const a of articles) articlesById[a.id] = a;

  // Preserve the order of the linked Products field on the record so editing
  // matches what renders on the page.
  const productLinkOrder = (f['Products'] as string[] | undefined) ?? [];
  const productsById: Record<string, AirtableRecord> = {};
  for (const p of products) productsById[p.id] = p;

  const orderedProducts = productLinkOrder
    .map((id) => productsById[id])
    .filter((r): r is AirtableRecord => !!r)
    .map((r) => productFromRecord(r, articlesById));

  const solutionLinkOrder = (f['Solutions'] as string[] | undefined) ?? [];
  const solutionsById: Record<string, AirtableRecord> = {};
  for (const s of solutions) solutionsById[s.id] = s;

  const orderedSolutions = solutionLinkOrder
    .map((id) => solutionsById[id])
    .filter((r): r is AirtableRecord => !!r)
    .map(solutionFromRecord);

  return {
    mode: 'edit',
    recordId: productArea.id,
    slug: str(f, 'Slug'),

    h1: str(f, 'H1'),
    topBg: str(f, 'Top BG'),

    heroH2: str(f, 'Hero H2'),
    heroText: str(f, 'Hero Text'),
    heroCtaText: str(f, 'Hero CTA Text'),
    heroCtaUrl: str(f, 'Hero CTA URL'),
    heroBenefits: str(f, 'Hero Benefits'),
    heroImage: str(f, 'Hero Image'),
    heroBg: str(f, 'Hero BG'),
    heroAccent: str(f, 'Hero Accent'),

    npiTitle: str(f, 'NPI Title'),
    npiDescription: str(f, 'NPI Description'),
    npiImage: str(f, 'NPI Image'),
    npiLink: str(f, 'NPI Link'),

    toggleBg: str(f, 'Toggle BG'),
    toggleHeaderBg: str(f, 'Toggle Header BG'),
    toggleAccent: str(f, 'Toggle Accent'),

    solutionsTitle: str(f, 'Solutions Title'),
    solutionsBg: str(f, 'Solutions BG'),
    solutionsCardBg: str(f, 'Solutions Card BG'),

    normal1: normalFromFields(f, 1),
    normal2: normalFromFields(f, 2),
    normal3: normalFromFields(f, 3),
    normal4: normalFromFields(f, 4),

    contactName: str(f, 'Contact Name'),
    contactTitle: str(f, 'Contact Title'),
    contactEmail: str(f, 'Contact Email'),
    contactPhone: str(f, 'Contact Phone'),
    contactImage: str(f, 'Contact Image'),
    contactText: str(f, 'Contact Text'),
    contactBg: str(f, 'Contact BG'),

    docsTitle: str(f, 'Docs Title'),
    docsIframe: str(f, 'Docs Iframe'),
    docsBg: str(f, 'Docs BG'),

    sideMenu: bool(f, 'Side menu'),
    request: bool(f, 'Request'),
    defaultOpen: bool(f, 'Default open'),

    products: orderedProducts,
    solutions: orderedSolutions,

    division: ((f['Division'] as string[] | undefined) ?? []).slice(),

    showContactForm: f['Show Contact Form'] === true,
    contactForm: contactFormFromRecord(productArea),
  };
}

function contactFormFromRecord(record: AirtableRecord): ContactFormState {
  const f = record.fields;
  const empty = emptyContactFormState();
  const str = (k: string) => (typeof f[k] === 'string' ? (f[k] as string) : '');
  const boolOr = (k: string, fallback: boolean) =>
    f[k] === true ? true : f[k] === false ? false : fallback;
  const layoutRaw = str('Contact Form Layout');
  const themeRaw = str('Contact Form Theme');
  return {
    eyebrow: str('Contact Form Eyebrow'),
    title: str('Contact Form Title'),
    subtitle: str('Contact Form Subtitle'),
    layout: (layoutRaw === 'centered' ? 'centered' : 'split') as ContactFormLayout,
    theme: (themeRaw === 'light' ? 'light' : 'dark') as ContactFormTheme,
    showCompany: boolOr('Contact Form Show Company', empty.showCompany),
    showPhone: boolOr('Contact Form Show Phone', empty.showPhone),
    showDropdown: boolOr('Contact Form Show Dropdown', empty.showDropdown),
    dropdownLabel: str('Contact Form Dropdown Label'),
    options: str('Contact Form Options'),
    ctaText: str('Contact Form CTA Text'),
    messageLabel: str('Contact Form Message Label'),
    trustSignals: str('Contact Form Trust Signals'),
    showContactPerson: boolOr('Contact Form Show Contact Person', empty.showContactPerson),
  };
}
