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
export const UNIQUE_PAGES_ENTITIES = ['cms_unique_pages'] as const;
