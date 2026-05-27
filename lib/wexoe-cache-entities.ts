// Entity names exactly match the schema files in
// wexoeplugins/wexoe-core/entities/*.php — do NOT translate.
// Kept in a side-effect-free module so client-safe metadata (the page-type
// registry) can reference cache entity names without importing webhook code.
export const LP_ENTITIES = ['landing_pages', 'lp_tabs', 'lp_downloads'] as const;
export const PA_ENTITIES = ['product_areas', 'products', 'solutions'] as const;
// Customer-type-pages (tidigare audience_heroes) migrerades till Wexoe NY
// och länkar nu case_pages — båda entiteterna invalideras tillsammans när
// en kundtyp-sida muteras.
export const CUSTOMER_TYPE_PAGE_ENTITIES = ['customer_type_pages', 'case_pages'] as const;
export const SSOT_ENTITIES = [
  'core_company',
  'core_graphic_profile',
  'core_countries',
  'core_divisions',
  'core_customer_types',
  'core_coworkers',
  'core_partners',
  'core_testimonials',
] as const;
// CMS-pages-familjen — informationssidor (start, om-oss, pillar) byggda av
// polymorfa cms_page_sections. Tabs-sektionstypen har egna sub-records i
// cms_section_tabs som invalideras tillsammans.
export const CMS_PAGES_ENTITIES = ['cms_pages', 'cms_page_sections', 'cms_section_tabs'] as const;
// Case-sidor (editorial artikel-format). Bara en tabell — produkter/artiklar
// som länkas cachas av sina egna sidtyper (PA m.fl.), så ingen anledning att
// invalidiera dem härifrån.
export const CASE_ENTITIES = ['cms_cases'] as const;
// Partner-sidor (leverantörssidor som Rockwell, HMS, Wittenstein, …).
// Sidan länkar in cms_cases (success cases) och product_areas (kategorier)
// + core_partners (identitet). Alla fyra invalideras tillsammans när en
// partner-sida muteras så att ändringar i linked records syns omedelbart.
export const PARTNER_ENTITIES = [
  'partner_pages',
  'cases',
  'product_areas',
  'core_partners',
] as const;
