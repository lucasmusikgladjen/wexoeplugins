/**
 * CRUD-route för cms_pages (informationssidor).
 *
 * Drivs av createPageRoute() (Lager 3 — `create`/`update`-overrides bor i
 * `lib/page-types/cms-page-actions.ts`). Routen får standardiserad slug-
 * validering, felformat och cache-invalidering; skrivvägen är deterministisk
 * (rena funktioner, inga Claude-anrop) via cmsPageCreate/Update.
 *
 * Single-record läses server-side via loadCmsPageState i edit-page:n —
 * därför saknas action=get.
 */

import { cmsPageServer, loadCmsPageState } from '@/lib/page-types/cms-page.server';
import { createPageRoute, pageTypeToRouteConfig } from '@/lib/route-factory';

export const { GET, POST, PATCH, DELETE } = createPageRoute(
  pageTypeToRouteConfig(cmsPageServer, process.env.AIRTABLE_API_KEY, loadCmsPageState),
);
