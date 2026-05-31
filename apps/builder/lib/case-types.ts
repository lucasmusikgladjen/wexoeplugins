/**
 * Case-page state model.
 *
 * Speglar Airtable `cms_cases` (tblxH3ECSMvDTYrIQ) i Wexoe NY, som drivs av
 * `[wexoe_case slug="..."]`-shortcoden (FAS 2-pluginet).
 *
 * Pseudo-arrays (quick_stats, results, gallery_images) lagras i Airtable
 * som numrerade fält (`quick_stat_1_value`, `quick_stat_1_label`, ...) upp
 * till N items. Här i state hålls de som riktiga arrayer; mappern expanderar
 * och den deterministiska transformen omvänder vid spar.
 *
 * Linkade records (product_ids, article_ids) är `multipleRecordLinks` till
 * cms_products resp. cms_articles. Brand/role/article_number läses inte här
 * — PHP-pluginet och builder-preview slår upp dem via Core::entity vid render.
 */

import { ContactFormState, emptyContactFormState } from './contact-form-types';

export interface CaseQuickStat {
  value: string;
  label: string;
}

export interface CaseResult {
  value: string;
  label: string;
}

export interface CaseGalleryImage {
  url: string;
  caption: string;
}

/** Antal items pseudo-arrayerna kan hålla — matchar `count` i
 *  wexoe-core/entities/cms_cases.php. Editorerna använder dessa för att
 *  dölja "+ Lägg till"-knappen vid max. */
export const CASE_QUICK_STATS_MAX = 4;
export const CASE_RESULTS_MAX = 4;
export const CASE_GALLERY_MAX = 6;

export interface CaseState {
  /** `create` innan recordet persisterats; `edit` när det har Airtable-id. */
  mode: 'create' | 'edit';
  recordId: string;

  // Core / publicering
  slug: string;
  isActive: boolean;
  internalNotes: string;

  // SEO
  seoTitle: string;
  seoDescription: string;
  ogImageUrl: string;

  // Header
  industry: string;
  title: string;
  subtitle: string;
  customerName: string;
  location: string;
  projectYear: string;
  projectType: string;
  readingTime: string;
  /** Lines-fält: en URL per rad. */
  headerLogos: string;

  // Lead
  leadImageUrl: string;
  leadImageCaption: string;
  /** Markdown — drop-cap appliceras av PHP-pluginet på första tecknet. */
  leadParagraph: string;

  // Stats strip
  showStatsStrip: boolean;
  quickStats: CaseQuickStat[];

  // Challenge
  challengeEyebrow: string;
  challengeTitle: string;
  challengeText: string;
  /** Lines-fält: en bullet per rad. */
  challengeBullets: string;
  challengeImageUrl: string;
  challengeImageCaption: string;

  // Pullquote
  showPullquote: boolean;
  pullquoteText: string;
  pullquoteAttribution: string;

  // Solution
  solutionEyebrow: string;
  solutionTitle: string;
  solutionText: string;
  solutionImageUrl: string;
  solutionImageCaption: string;

  // Products
  productsTitle: string;
  productsMeta: string;
  productIds: string[];
  articleIds: string[];

  // Results
  resultsEyebrow: string;
  resultsTitle: string;
  resultsText: string;
  results: CaseResult[];

  // Testimonial
  showTestimonial: boolean;
  testimonialQuote: string;
  testimonialPhotoUrl: string;
  testimonialAuthorName: string;
  testimonialAuthorTitle: string;

  // Gallery
  showGallery: boolean;
  galleryTitle: string;
  galleryImages: CaseGalleryImage[];

  // About customer
  showAboutCustomer: boolean;
  aboutCustomerLogoUrl: string;
  aboutCustomerTitle: string;
  aboutCustomerText: string;
  aboutCustomerLinkLabel: string;
  aboutCustomerUrl: string;

  // Glance sidebar — visas alltid på den publika sidan
  glanceChallenge: string;
  glanceSolution: string;
  glanceResult: string;

  // Contact form
  showContactForm: boolean;
  contactForm: ContactFormState;
}

export type CaseSectionId =
  | 'header'
  | 'lead'
  | 'statsStrip'
  | 'challenge'
  | 'pullquote'
  | 'solution'
  | 'products'
  | 'results'
  | 'testimonial'
  | 'gallery'
  | 'aboutCustomer'
  | 'glance'
  | 'contactForm'
  | 'seo'
  | 'settings';

export function emptyCaseState(): CaseState {
  return {
    mode: 'create',
    recordId: '',

    slug: '',
    isActive: true,
    internalNotes: '',

    seoTitle: '',
    seoDescription: '',
    ogImageUrl: '',

    industry: '',
    title: '',
    subtitle: '',
    customerName: '',
    location: '',
    projectYear: '',
    projectType: '',
    readingTime: '',
    headerLogos: '',

    leadImageUrl: '',
    leadImageCaption: '',
    leadParagraph: '',

    showStatsStrip: false,
    quickStats: [],

    challengeEyebrow: 'Utmaningen',
    challengeTitle: '',
    challengeText: '',
    challengeBullets: '',
    challengeImageUrl: '',
    challengeImageCaption: '',

    showPullquote: false,
    pullquoteText: '',
    pullquoteAttribution: '',

    solutionEyebrow: 'Lösningen',
    solutionTitle: '',
    solutionText: '',
    solutionImageUrl: '',
    solutionImageCaption: '',

    productsTitle: 'Produkter i lösningen',
    productsMeta: '',
    productIds: [],
    articleIds: [],

    resultsEyebrow: 'Resultatet',
    resultsTitle: '',
    resultsText: '',
    results: [],

    showTestimonial: false,
    testimonialQuote: '',
    testimonialPhotoUrl: '',
    testimonialAuthorName: '',
    testimonialAuthorTitle: '',

    showGallery: false,
    galleryTitle: '',
    galleryImages: [],

    showAboutCustomer: false,
    aboutCustomerLogoUrl: '',
    aboutCustomerTitle: '',
    aboutCustomerText: '',
    aboutCustomerLinkLabel: '',
    aboutCustomerUrl: '',

    glanceChallenge: '',
    glanceSolution: '',
    glanceResult: '',

    showContactForm: false,
    contactForm: emptyContactFormState(),
  };
}
