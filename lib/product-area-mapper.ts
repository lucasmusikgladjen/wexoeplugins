/**
 * Bidirectional mapping between Airtable records and ProductAreaState.
 *
 * Reverse: pageStateFromProductAreaRecords — builds a ProductAreaState from
 * an LP record + linked products + each product's linked articles + linked
 * solutions.
 *
 * Forward: productAreaPatchFields / productPatchFields / solutionPatchFields
 * build Airtable-ready field maps for PATCH requests. Fields are cleared
 * explicitly rather than omitted so the schema matches state exactly.
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
  };
}

// ─── Forward: state → Airtable fields (PATCH) ──────────────────────────────

function normalPatchFields(section: NormalSection, n: 1 | 2 | 3 | 4): Fields {
  const fields: Fields = {
    [`Normal ${n} H2`]: section.h2,
    [`Normal ${n} Text`]: section.text,
    [`Normal ${n} Bullets`]: section.bullets,
    [`Normal ${n} Image`]: section.image,
    [`Normal ${n} Reversed`]: section.reversed,
    [`Normal ${n} BG`]: section.bg,
  };
  if (n === 1) fields['Normal 1 upp'] = section.upp;
  return fields;
}

/** Build the PATCH payload for the Product Areas record itself. */
export function productAreaPatchFields(state: ProductAreaState): Fields {
  return {
    Slug: state.slug,
    H1: state.h1,
    'Top BG': state.topBg,

    'Hero H2': state.heroH2,
    'Hero Text': state.heroText,
    'Hero CTA Text': state.heroCtaText,
    'Hero CTA URL': state.heroCtaUrl,
    'Hero Benefits': state.heroBenefits,
    'Hero Image': state.heroImage,
    'Hero BG': state.heroBg,
    'Hero Accent': state.heroAccent,

    'NPI Title': state.npiTitle,
    'NPI Description': state.npiDescription,
    'NPI Image': state.npiImage,
    'NPI Link': state.npiLink,

    'Toggle BG': state.toggleBg,
    'Toggle Header BG': state.toggleHeaderBg,
    'Toggle Accent': state.toggleAccent,

    'Solutions Title': state.solutionsTitle,
    'Solutions BG': state.solutionsBg,
    'Solutions Card BG': state.solutionsCardBg,

    ...normalPatchFields(state.normal1, 1),
    ...normalPatchFields(state.normal2, 2),
    ...normalPatchFields(state.normal3, 3),
    ...normalPatchFields(state.normal4, 4),

    'Contact Name': state.contactName,
    'Contact Title': state.contactTitle,
    'Contact Email': state.contactEmail,
    'Contact Phone': state.contactPhone,
    'Contact Image': state.contactImage,
    'Contact Text': state.contactText,
    'Contact BG': state.contactBg,

    'Docs Title': state.docsTitle,
    'Docs Iframe': state.docsIframe,
    'Docs BG': state.docsBg,

    'Side menu': state.sideMenu,
    Request: state.request,
    'Default open': state.defaultOpen,

    Division: state.division,
  };
}

/** PATCH payload for a linked Product record. */
export function productPatchFields(product: LinkedProduct): Fields {
  return {
    Name: product.name,
    'Header side menu': product.headerSideMenu,
    Description: product.description,
    'Ecosystem Description': product.ecosystemDescription,
    Bullets: product.bullets,
    Image: product.image,
    'Button 1 Text': product.button1Text,
    'Button 1 URL': product.button1Url,
    'Button 2 Text': product.button2Text,
    'Button 2 URL': product.button2Url,
    Horizontal: product.horizontal,
    Order: product.order,
    Visa: product.visa,
  };
}

/** PATCH payload for a linked Solution record. */
export function solutionPatchFields(solution: LinkedSolution): Fields {
  return {
    Name: solution.name,
    Image: solution.image,
    URL: solution.url,
    Description: solution.description,
    Category: solution.category,
    'CTA Text': solution.ctaText,
    Order: solution.order,
    Visa: solution.visa,
  };
}
