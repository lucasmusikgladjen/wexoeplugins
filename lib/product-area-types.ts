// ─── Product Area state model ──────────────────────────────────────────────
//
// Mirrors the Airtable "Product Areas" table plus enough of the linked
// "Products" and "Solutions & Concepts" records for in-place editing, and a
// read-only projection of linked "Articles" beneath each product.

export interface NormalSection {
  h2: string;
  text: string;
  bullets: string;
  image: string;
  reversed: boolean;
  bg: string;
  /** Only used on section 1 in the current schema, but modelled uniformly. */
  upp: boolean;
}

/** Read-only view of a linked Article record. */
export interface LinkedArticleReadonly {
  recordId: string;
  name: string;
  artikelnummer: string;
  description: string;
  bild: string;
  datablad: string;
  lankTillWebshop: string;
  varianter: string;
}

/** Editable projection of a linked Product record.
 *  `recordId` is empty for products that have been added client-side but not
 *  yet saved; `clientId` is a stable React key that survives saves. */
export interface LinkedProduct {
  clientId: string;
  recordId: string;
  name: string;
  headerSideMenu: string;
  description: string;
  ecosystemDescription: string;
  bullets: string;
  image: string;
  button1Text: string;
  button1Url: string;
  button2Text: string;
  button2Url: string;
  horizontal: boolean;
  order: number;
  visa: boolean;
  /** Linked Articles — read-only in v1. */
  articles: LinkedArticleReadonly[];
}

/** Editable projection of a linked Solutions & Concepts record.
 *  Same `clientId` convention as LinkedProduct. */
export interface LinkedSolution {
  clientId: string;
  recordId: string;
  name: string;
  image: string;
  url: string;
  description: string;
  category: string;
  ctaText: string;
  order: number;
  visa: boolean;
}

export interface ProductAreaState {
  /** `create` before the record has ever been persisted; `edit` once it has an Airtable id. */
  mode: 'edit' | 'create';
  recordId: string;
  slug: string;

  // Top banner
  h1: string;
  topBg: string;

  // Hero
  heroH2: string;
  heroText: string;
  heroCtaText: string;
  heroCtaUrl: string;
  heroBenefits: string;
  heroImage: string;
  heroBg: string;
  heroAccent: string;

  // NPI card
  npiTitle: string;
  npiDescription: string;
  npiImage: string;
  npiLink: string;

  // Toggle / side-menu colors
  toggleBg: string;
  toggleHeaderBg: string;
  toggleAccent: string;

  // Solutions grid settings
  solutionsTitle: string;
  solutionsBg: string;
  solutionsCardBg: string;

  // Normal 1–4
  normal1: NormalSection;
  normal2: NormalSection;
  normal3: NormalSection;
  normal4: NormalSection;

  // Contact ("our guy")
  contactName: string;
  contactTitle: string;
  contactEmail: string;
  contactPhone: string;
  contactImage: string;
  contactText: string;
  contactBg: string;

  // Docs iframe
  docsTitle: string;
  docsIframe: string;
  docsBg: string;

  // Feature flags
  sideMenu: boolean;
  request: boolean;
  defaultOpen: boolean;

  // Linked records
  products: LinkedProduct[];
  solutions: LinkedSolution[];

  /** Division linked-record IDs (multipleRecordLinks — currently the UI
   *  only exposes a single pick, but the state mirrors Airtable shape). */
  division: string[];
}

/** Lightweight projection of a Division record for the picker UI. */
export interface Division {
  id: string;
  name: string;
}

export type ProductAreaSectionId =
  | 'hero'
  | 'npi'
  | 'normal1'
  | 'normal2'
  | 'normal3'
  | 'normal4'
  | 'products'
  | 'solutions'
  | 'contact'
  | 'docs'
  | 'settings';

export function emptyNormalSection(): NormalSection {
  return {
    h2: '',
    text: '',
    bullets: '',
    image: '',
    reversed: false,
    bg: '',
    upp: false,
  };
}

let clientIdCounter = 0;
/** Generate a stable, unique client-side ID (not an Airtable record ID). */
export function generateClientId(prefix: string): string {
  clientIdCounter += 1;
  return `${prefix}-${Date.now()}-${clientIdCounter}`;
}

export function emptyLinkedProduct(): LinkedProduct {
  return {
    clientId: generateClientId('new-product'),
    recordId: '',
    name: '',
    headerSideMenu: '',
    description: '',
    ecosystemDescription: '',
    bullets: '',
    image: '',
    button1Text: '',
    button1Url: '',
    button2Text: '',
    button2Url: '',
    horizontal: false,
    order: 0,
    visa: true,
    articles: [],
  };
}

export function emptyLinkedSolution(): LinkedSolution {
  return {
    clientId: generateClientId('new-solution'),
    recordId: '',
    name: '',
    image: '',
    url: '',
    description: '',
    category: '',
    ctaText: '',
    order: 0,
    visa: true,
  };
}

/** Initial state for a brand-new Product Area. All colour fields start empty
 *  so the PHP plugin's built-in defaults (navy / orange / alternating grey)
 *  render through `colorOr()` in the preview. */
export function emptyProductAreaState(): ProductAreaState {
  return {
    mode: 'create',
    recordId: '',
    slug: '',

    h1: '',
    topBg: '',

    heroH2: '',
    heroText: '',
    heroCtaText: '',
    heroCtaUrl: '',
    heroBenefits: '',
    heroImage: '',
    heroBg: '',
    heroAccent: '',

    npiTitle: '',
    npiDescription: '',
    npiImage: '',
    npiLink: '',

    toggleBg: '',
    toggleHeaderBg: '',
    toggleAccent: '',

    solutionsTitle: '',
    solutionsBg: '',
    solutionsCardBg: '',

    normal1: emptyNormalSection(),
    normal2: emptyNormalSection(),
    normal3: emptyNormalSection(),
    normal4: emptyNormalSection(),

    contactName: '',
    contactTitle: '',
    contactEmail: '',
    contactPhone: '',
    contactImage: '',
    contactText: '',
    contactBg: '',

    docsTitle: '',
    docsIframe: '',
    docsBg: '',

    sideMenu: false,
    request: false,
    defaultOpen: false,

    products: [],
    solutions: [],

    division: [],
  };
}
