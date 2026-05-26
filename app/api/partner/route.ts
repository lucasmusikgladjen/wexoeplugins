/**
 * CRUD-route för partner-page-records (leverantörssidor).
 *
 * Drivs deklarativt av `partnerServer` (lib/page-types/partner.server.ts).
 * Endpoints: GET ?action=list | get&id, POST, PATCH ?id, DELETE ?id.
 *
 * Tabellen ligger i Wexoe NY (cms_partner_pages) — BASE_ID är wired på
 * partnerServer. Cache-invalidering täcker partner_pages + cases +
 * product_areas + core_partners enligt `PARTNER_ENTITIES`.
 */

import {
  partnerServer,
  loadPartnerPageState,
} from '@/lib/page-types/partner.server';
import { createPageRoute, pageTypeToRouteConfig } from '@/lib/route-factory';

export const { GET, POST, PATCH, DELETE } = createPageRoute(
  pageTypeToRouteConfig(partnerServer, process.env.AIRTABLE_API_KEY, loadPartnerPageState),
);
