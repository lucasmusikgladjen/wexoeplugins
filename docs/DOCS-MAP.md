# DOCS-MAP — var bor allt (GENERERAD)

> ⚠️ **Genererad av `tools/guardian/guardian.mjs` — redigera INTE för hand.**
> Kör `npm run guardian` för att uppdatera. Senast: 2026-05-31.

## Sidtyper

| id | server | ui |
|---|---|---|
| `landing` | — | — |
| `product` | apps/builder/lib/page-types/product-area.server.ts | apps/builder/lib/page-types/product-area.ui.tsx |
| `customer-type` | apps/builder/lib/page-types/customer-type.server.ts | apps/builder/lib/page-types/customer-type.ui.tsx |
| `page` | apps/builder/lib/page-types/cms-page.server.ts | apps/builder/lib/page-types/cms-page.ui.tsx |
| `case` | apps/builder/lib/page-types/case.server.ts | apps/builder/lib/page-types/case.ui.tsx |
| `partner` | apps/builder/lib/page-types/partner.server.ts | apps/builder/lib/page-types/partner.ui.tsx |

## Sektioner (cms_page_sections, wexoe-pages-dispatchern)

| section_type | PHP-renderare |
|---|---|
| `hero` | apps/wordpress/plugins/wexoe-pages/sections/hero.php |
| `text_image` | apps/wordpress/plugins/wexoe-pages/sections/text-image.php |
| `text_only` | apps/wordpress/plugins/wexoe-pages/sections/text-only.php |
| `company_data_strip` | apps/wordpress/plugins/wexoe-pages/sections/company-data-strip.php |
| `news_text_split` | apps/wordpress/plugins/wexoe-pages/sections/news-text-split.php |
| `case_grid` | apps/wordpress/plugins/wexoe-pages/sections/case-grid.php |
| `news_grid` | apps/wordpress/plugins/wexoe-pages/sections/news-grid.php |
| `catalog` | apps/wordpress/plugins/wexoe-pages/sections/catalog.php |
| `tabs` | apps/wordpress/plugins/wexoe-pages/sections/tabs.php |
| `team_grid` | apps/wordpress/plugins/wexoe-pages/sections/team-grid.php |
| `partner_list` | apps/wordpress/plugins/wexoe-pages/sections/partner-list.php |
| `faq` | apps/wordpress/plugins/wexoe-pages/sections/faq.php |
| `testimonial` | apps/wordpress/plugins/wexoe-pages/sections/testimonial.php |
| `cta_banner` | apps/wordpress/plugins/wexoe-pages/sections/cta-banner.php |
| `contact_form` | apps/wordpress/plugins/wexoe-pages/sections/contact-form.php |

## Entiteter (packages/schema/entities → synkade kopior)

- `cms_customer_type_pages`

## Copy-handlers (app/api/copy/route.ts)

`landing`, `product-area`, `customer-type`, `case`, `page`, `partner`
