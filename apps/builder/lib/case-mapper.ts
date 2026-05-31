/**
 * Forward mapping från Airtable `cms_cases`-records till `CaseState`.
 *
 * Bara fromRecord-riktningen — state → Airtable går via den deterministiska
 * skrivvägen (`buildCaseFields` i `deterministic-transform.ts`). Schemat är flatt på Airtable-sidan
 * men pseudo-arrayerna (quick_stat_N_*, result_N_*, gallery_image_N_*) packas
 * upp till riktiga arrays här så editorerna får jobba med items istället för
 * numrerade kolumner.
 *
 * snake_case både i Airtable och som källnycklar.
 */

import { AirtableRecord, BASE_ID } from './airtable';
import { str, bool, linkedIds, AirtableFields } from './airtable-helpers';
import { contactFormFromFields } from './contact-form-mapper';
import {
  CaseState,
  CaseQuickStat,
  CaseResult,
  CaseGalleryImage,
  CASE_QUICK_STATS_MAX,
  CASE_RESULTS_MAX,
  CASE_GALLERY_MAX,
} from './case-types';

export const CASE_BASE_ID = BASE_ID;
export const CASE_TABLE_ID = 'tblxH3ECSMvDTYrIQ';

/** Packa upp ett pseudo-array som `<prefix>_<N>_<field>` till items. Hoppar
 *  över items där alla sub-fält är tomma — matchar hur Normalizer:n
 *  filtrerar bort tomma sektioner vid läsning. */
function readPseudoArray<T>(
  fields: AirtableFields,
  prefix: string,
  count: number,
  subfields: ReadonlyArray<keyof T & string>,
): T[] {
  const items: T[] = [];
  for (let i = 1; i <= count; i++) {
    const item: Record<string, string> = {};
    let hasContent = false;
    for (const subfield of subfields) {
      const v = str(fields, `${prefix}_${i}_${subfield}`);
      item[subfield] = v;
      if (v !== '') hasContent = true;
    }
    if (hasContent) items.push(item as T);
  }
  return items;
}

export function caseStateFromRecord(record: AirtableRecord): CaseState {
  const f = record.fields;

  const quickStats = readPseudoArray<CaseQuickStat>(
    f,
    'quick_stat',
    CASE_QUICK_STATS_MAX,
    ['value', 'label'],
  );
  const results = readPseudoArray<CaseResult>(
    f,
    'result',
    CASE_RESULTS_MAX,
    ['value', 'label'],
  );
  const galleryImages = readPseudoArray<CaseGalleryImage>(
    f,
    'gallery_image',
    CASE_GALLERY_MAX,
    ['url', 'caption'],
  );

  // Defaults för eyebrows: om Airtable har tomt fält, visa schemats default
  // så editorn inte ser ut som en bugg. PHP-pluginet gör samma med field()-defaulten.
  const challengeEyebrow = str(f, 'challenge_eyebrow') || 'Utmaningen';
  const solutionEyebrow = str(f, 'solution_eyebrow') || 'Lösningen';
  const resultsEyebrow = str(f, 'results_eyebrow') || 'Resultatet';
  const productsTitle = str(f, 'products_title') || 'Produkter i lösningen';

  return {
    mode: 'edit',
    recordId: record.id,

    slug: str(f, 'slug'),
    isActive: bool(f, 'is_active'),
    internalNotes: str(f, 'internal_notes'),

    seoTitle: str(f, 'seo_title'),
    seoDescription: str(f, 'seo_description'),
    ogImageUrl: str(f, 'og_image_url'),

    industry: str(f, 'industry'),
    title: str(f, 'title'),
    subtitle: str(f, 'subtitle'),
    customerName: str(f, 'customer_name'),
    location: str(f, 'location'),
    projectYear: str(f, 'project_year'),
    projectType: str(f, 'project_type'),
    readingTime: str(f, 'reading_time'),
    headerLogos: str(f, 'header_logos'),

    leadImageUrl: str(f, 'lead_image_url'),
    leadImageCaption: str(f, 'lead_image_caption'),
    leadParagraph: str(f, 'lead_paragraph'),

    showStatsStrip: bool(f, 'show_stats_strip'),
    quickStats,

    challengeEyebrow,
    challengeTitle: str(f, 'challenge_title'),
    challengeText: str(f, 'challenge_text'),
    challengeBullets: str(f, 'challenge_bullets'),
    challengeImageUrl: str(f, 'challenge_image_url'),
    challengeImageCaption: str(f, 'challenge_image_caption'),

    showPullquote: bool(f, 'show_pullquote'),
    pullquoteText: str(f, 'pullquote_text'),
    pullquoteAttribution: str(f, 'pullquote_attribution'),

    solutionEyebrow,
    solutionTitle: str(f, 'solution_title'),
    solutionText: str(f, 'solution_text'),
    solutionImageUrl: str(f, 'solution_image_url'),
    solutionImageCaption: str(f, 'solution_image_caption'),

    productsTitle,
    productsMeta: str(f, 'products_meta'),
    productIds: linkedIds(f, 'product_ids'),
    articleIds: linkedIds(f, 'article_ids'),

    resultsEyebrow,
    resultsTitle: str(f, 'results_title'),
    resultsText: str(f, 'results_text'),
    results,

    showTestimonial: bool(f, 'show_testimonial'),
    testimonialQuote: str(f, 'testimonial_quote'),
    testimonialPhotoUrl: str(f, 'testimonial_photo_url'),
    testimonialAuthorName: str(f, 'testimonial_author_name'),
    testimonialAuthorTitle: str(f, 'testimonial_author_title'),

    showGallery: bool(f, 'show_gallery'),
    galleryTitle: str(f, 'gallery_title'),
    galleryImages,

    showAboutCustomer: bool(f, 'show_about_customer'),
    aboutCustomerLogoUrl: str(f, 'about_customer_logo_url'),
    aboutCustomerTitle: str(f, 'about_customer_title'),
    aboutCustomerText: str(f, 'about_customer_text'),
    aboutCustomerLinkLabel: str(f, 'about_customer_link_label'),
    aboutCustomerUrl: str(f, 'about_customer_url'),

    glanceChallenge: str(f, 'glance_challenge'),
    glanceSolution: str(f, 'glance_solution'),
    glanceResult: str(f, 'glance_result'),

    showContactForm: bool(f, 'show_contact_form'),
    contactForm: contactFormFromFields(f, 'snake_case'),
  };
}
