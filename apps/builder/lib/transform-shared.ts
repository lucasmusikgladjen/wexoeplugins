/**
 * Shared deterministic write-path helpers.
 *
 * Extracted from the former `claude-transform.ts` (removed in FAS 2 — Claude is
 * no longer on the save path). These are the pure, deterministic primitives the
 * write path still relies on:
 *
 *  - the `*Transform*` result types that `deterministic-transform.ts` produces
 *    and the routes correlate against existing Airtable records;
 *  - `sectionToPayload` — maps a cms-page section's state to its snake_case
 *    Airtable fields (reused by the deterministic cms-page builder);
 *  - `clearsForTabType` / `clearsForSidebarType` — the backend, *not* Claude,
 *    handles stale-field clearing on tab-type / sidebar-type switches; these
 *    return empty-string maps that PATCH wipes.
 *
 * No Anthropic calls live here. See `deterministic-transform.ts` for the
 * per-type field builders and `schema/to-fields.ts` for the schema-driven path.
 */

import { PageSection } from './cms-page-types';
import { faqItemsToJson, faqItemsToLines } from './faq-block';

// ─── Result types ──────────────────────────────────────────────────────────

export interface LpTransformTab {
  _clientIndex: number;
  _recordId: string | null;
  fields: Record<string, unknown>;
}

export interface LpTransformDownload {
  _clientIndex: number;
  _tabClientIndex: number;
  _recordId: string | null;
  fields: Record<string, unknown>;
}

export interface LpTransformResult {
  landingPage: Record<string, unknown>;
  tabs: LpTransformTab[];
  downloads: LpTransformDownload[];
}

export interface PaTransformLinked {
  _clientIndex: number;
  _recordId: string | null;
  fields: Record<string, unknown>;
}

export interface PaTransformResult {
  productArea: Record<string, unknown>;
  products: PaTransformLinked[];
  solutions: PaTransformLinked[];
}

export interface CmsPageTransformSection {
  _clientIndex: number;
  _recordId: string | null;
  fields: Record<string, unknown>;
}

export interface CmsPageTransformTab {
  _clientIndex: number;
  _sectionClientIndex: number;
  _recordId: string | null;
  fields: Record<string, unknown>;
}

export interface CmsPageTransformResult {
  page: Record<string, unknown>;
  sections: CmsPageTransformSection[];
  sectionTabs: CmsPageTransformTab[];
}

// ─── Section → Airtable payload (cms-page) ─────────────────────────────────

export function sectionToPayload(sec: PageSection, index: number): Record<string, unknown> {
  // Universella fält + typ-diskriminator.
  const base: Record<string, unknown> = {
    _clientIndex: index,
    _recordId: sec.recordId || null,
    type: sec.type,
    internal_label: sec.internalLabel,
    is_active: sec.isActive,
    anchor_id: sec.anchorId,
    layout: sec.layout,
    theme: sec.theme,
    top_padding: sec.topPadding,
    bottom_padding: sec.bottomPadding,
  };

  switch (sec.type) {
    case 'hero':
      return { ...base,
        hero_eyebrow: sec.eyebrow, hero_h1: sec.h1, hero_subtitle: sec.subtitle,
        hero_image_url: sec.imageUrl,
        hero_cta_text: sec.ctaText, hero_cta_url: sec.ctaUrl,
        hero_cta2_text: sec.cta2Text, hero_cta2_url: sec.cta2Url };
    case 'text_image':
      return { ...base,
        ti_eyebrow: sec.eyebrow, ti_h2: sec.h2, ti_body: sec.body, ti_bullets: sec.bullets,
        ti_image_url: sec.imageUrl, ti_image_alt: sec.imageAlt, ti_reversed: sec.reversed,
        ti_cta_text: sec.ctaText, ti_cta_url: sec.ctaUrl,
        ti_cta2_text: sec.cta2Text, ti_cta2_url: sec.cta2Url };
    case 'text_only':
      return { ...base,
        to_eyebrow: sec.eyebrow, to_h2: sec.h2, to_body: sec.body, to_align: sec.align };
    case 'company_data_strip':
      return { ...base,
        cds_h2: sec.h2, cds_use_company_singleton: sec.useCompanySingleton,
        cds_country_code: sec.countryCode, cds_items: sec.items };
    case 'news_text_split':
      return { ...base,
        nts_eyebrow: sec.eyebrow, nts_h2: sec.h2, nts_body: sec.body,
        nts_cta_text: sec.ctaText, nts_cta_url: sec.ctaUrl,
        nts_news_manual_ids: sec.newsManualIds,
        nts_scope_division: sec.scopeDivision, nts_scope_country: sec.scopeCountry,
        nts_limit: sec.limit };
    case 'case_grid':
      return { ...base,
        cg_eyebrow: sec.eyebrow, cg_h2: sec.h2, cg_body: sec.body,
        cg_case_manual_ids: sec.caseManualIds,
        cg_scope_country: sec.scopeCountry, cg_scope_division: sec.scopeDivision,
        cg_scope_customer_type: sec.scopeCustomerType,
        cg_limit: sec.limit, cg_columns: sec.columns };
    case 'news_grid':
      return { ...base,
        ng_eyebrow: sec.eyebrow, ng_h2: sec.h2,
        ng_article_manual_ids: sec.articleManualIds,
        ng_scope_country: sec.scopeCountry, ng_scope_division: sec.scopeDivision,
        ng_scope_topic: sec.scopeTopic,
        ng_limit: sec.limit, ng_columns: sec.columns };
    case 'catalog':
      return { ...base,
        cat_eyebrow: sec.eyebrow, cat_h2: sec.h2, cat_intro_body: sec.introBody,
        cat_include_products: sec.includeProducts, cat_include_articles: sec.includeArticles,
        cat_scope_division: sec.scopeDivision, cat_scope_country: sec.scopeCountry,
        cat_facet_fields: sec.facetFields,
        cat_placeholder: sec.placeholder, cat_empty_text: sec.emptyText };
    case 'tabs':
      // Tabs sub-records skickas separat (sectionTabs) — endast container-fälten här.
      return { ...base,
        tabs_eyebrow: sec.eyebrow, tabs_h2: sec.h2, tabs_intro_body: sec.introBody };
    case 'team_grid':
      return { ...base,
        tg_eyebrow: sec.eyebrow, tg_h2: sec.h2, tg_body: sec.body, tg_variant: sec.variant,
        tg_coworker_manual_ids: sec.coworkerManualIds,
        tg_scope_country: sec.scopeCountry, tg_scope_division: sec.scopeDivision,
        tg_limit: sec.limit };
    case 'partner_list':
      return { ...base,
        pl_eyebrow: sec.eyebrow, pl_h2: sec.h2, pl_body: sec.body, pl_variant: sec.variant,
        pl_partner_manual_ids: sec.partnerManualIds,
        pl_scope_division: sec.scopeDivision, pl_scope_country: sec.scopeCountry,
        pl_limit: sec.limit };
    case 'faq':
      // FAS 3: dual-write — Q:/A:-fältet (PHP renderar från det) + JSON-spegeln.
      return { ...base,
        faq_eyebrow: sec.eyebrow, faq_h2: sec.h2, faq_body: sec.body,
        faq_items: faqItemsToLines(sec.items), faq_json: faqItemsToJson(sec.items) };
    case 'testimonial':
      return { ...base,
        t_eyebrow: sec.eyebrow, t_quote: sec.quote,
        t_author_name: sec.authorName, t_author_title: sec.authorTitle,
        t_author_image_url: sec.authorImageUrl,
        t_testimonial_manual_ids: sec.testimonialManualIds,
        t_scope_country: sec.scopeCountry, t_scope_division: sec.scopeDivision,
        t_scope_customer_type: sec.scopeCustomerType,
        t_featured_only: sec.featuredOnly };
    case 'cta_banner':
      return { ...base,
        cta_eyebrow: sec.eyebrow, cta_h2: sec.h2, cta_body: sec.body,
        cta_cta_text: sec.ctaText, cta_cta_url: sec.ctaUrl,
        cta_cta2_text: sec.cta2Text, cta_cta2_url: sec.cta2Url,
        cta_image_url: sec.imageUrl };
    case 'contact_form':
      return { ...base,
        cf_eyebrow: sec.eyebrow, cf_title: sec.title, cf_subtitle: sec.subtitle,
        cf_layout: sec.cfLayout,
        cf_show_company: sec.showCompany, cf_show_phone: sec.showPhone,
        cf_show_dropdown: sec.showDropdown, cf_dropdown_label: sec.dropdownLabel,
        cf_options: sec.options, cf_cta_text: sec.ctaText, cf_message_label: sec.messageLabel,
        cf_trust_signals: sec.trustSignals, cf_show_contact_person: sec.showContactPerson,
        cf_contact_scope_country: sec.contactScopeCountry,
        cf_contact_scope_division: sec.contactScopeDivision };
  }
}

// ─── Stale-field clearing on type switches ─────────────────────────────────
// These are the authoritative lists of type-specific Airtable fields. Used
// by the update paths to explicitly PATCH removed fields to empty strings
// (or `false` for the one checkbox) so switching a tab from `textimage` to
// `faq` — or an LP sidebar from `case` to `event` — wipes the old data.

export const LP_SIDEBAR_CASE_FIELDS = [
  'case_title',
  'case_description',
  'case_image_url',
  'case_outcomes',
  'case_cta_text',
  'case_cta_url',
];
export const LP_SIDEBAR_EVENT_FIELDS = [
  'event_type',
  'event_title',
  'event_description',
  'event_date',
  'event_location',
  'event_webhook',
];
export const LP_SIDEBAR_LEADMAGNET_FIELDS = [
  'magnet_title',
  'magnet_format',
  'magnet_description',
  'magnet_file_url',
  'magnet_webhook',
];
export const LP_SIDEBAR_CALC_FIELDS = ['calc_title', 'calc_html'];

const ALL_LP_SIDEBAR_FIELDS = [
  ...LP_SIDEBAR_CASE_FIELDS,
  ...LP_SIDEBAR_EVENT_FIELDS,
  ...LP_SIDEBAR_LEADMAGNET_FIELDS,
  ...LP_SIDEBAR_CALC_FIELDS,
];

export const LP_TAB_TEXTIMAGE_FIELDS = [
  'ti_h2',
  'ti_text',
  'ti_benefits',
  'ti_image_url',
  'ti_inverted',
];
export const LP_TAB_FULLMEDIA_FIELDS = ['fm_url'];
export const LP_TAB_FAQ_FIELDS = ['faq_items'];
export const LP_TAB_CALAMEO_FIELDS = [
  'calameo_1_title',
  'calameo_1_src',
  'calameo_2_title',
  'calameo_2_src',
  'calameo_3_title',
  'calameo_3_src',
];
export const LP_TAB_COMPARE_FIELDS = [
  'compare_title',
  'compare_col_a',
  'compare_col_b',
  'compare_rows',
];
export const LP_TAB_STEPS_FIELDS = ['steps_title', 'steps'];

const ALL_LP_TAB_FIELDS = [
  ...LP_TAB_TEXTIMAGE_FIELDS,
  ...LP_TAB_FULLMEDIA_FIELDS,
  ...LP_TAB_FAQ_FIELDS,
  ...LP_TAB_CALAMEO_FIELDS,
  ...LP_TAB_COMPARE_FIELDS,
  ...LP_TAB_STEPS_FIELDS,
];

/** Fields belonging to the given LP tab type. Empty set for unknown types. */
function fieldsForTabType(type: string): Set<string> {
  switch (type) {
    case 'textimage':
      return new Set(LP_TAB_TEXTIMAGE_FIELDS);
    case 'fullmedia':
      return new Set(LP_TAB_FULLMEDIA_FIELDS);
    case 'faq':
      return new Set(LP_TAB_FAQ_FIELDS);
    case 'calameo':
      return new Set(LP_TAB_CALAMEO_FIELDS);
    case 'compare':
      return new Set(LP_TAB_COMPARE_FIELDS);
    case 'steps':
      return new Set(LP_TAB_STEPS_FIELDS);
    default:
      return new Set();
  }
}

/** For a tab being updated to `newType`, returns `""` (or `false` for the
 *  TI Inverted checkbox) for every type-specific field that does NOT belong
 *  to the new type. Merge this into the returned fields before PATCH
 *  so a type switch wipes stale data from the previous type. */
export function clearsForTabType(newType: string): Record<string, unknown> {
  const keep = fieldsForTabType(newType);
  const out: Record<string, unknown> = {};
  for (const field of ALL_LP_TAB_FIELDS) {
    if (!keep.has(field)) {
      out[field] = field === 'ti_inverted' ? false : '';
    }
  }
  return out;
}

/** Same idea for an LP's `Sidebar Type` — clears fields belonging to the
 *  previous sidebar variant. Empty `newType` (the "— Ingen —" option) clears
 *  every sidebar field. */
export function clearsForSidebarType(newType: string): Record<string, unknown> {
  const keep = new Set<string>();
  if (newType === 'case') LP_SIDEBAR_CASE_FIELDS.forEach((f) => keep.add(f));
  else if (newType === 'event') LP_SIDEBAR_EVENT_FIELDS.forEach((f) => keep.add(f));
  else if (newType === 'leadmagnet') LP_SIDEBAR_LEADMAGNET_FIELDS.forEach((f) => keep.add(f));
  else if (newType === 'calculator') LP_SIDEBAR_CALC_FIELDS.forEach((f) => keep.add(f));

  const out: Record<string, unknown> = {};
  for (const field of ALL_LP_SIDEBAR_FIELDS) {
    if (!keep.has(field)) out[field] = '';
  }
  return out;
}
