/**
 * CRUD-route för case-records (cms_cases).
 *
 * Drivs deklarativt av `caseServer` (lib/page-types/case.server.ts).
 * Endpoints: GET ?action=list | get&id, POST, PATCH ?id, DELETE ?id.
 *
 * Tabellen ligger i Wexoe NY (cms_cases / tblxH3ECSMvDTYrIQ).
 */

import { caseServer, loadCaseState } from '@/lib/page-types/case.server';
import { createPageRoute, pageTypeToRouteConfig } from '@/lib/route-factory';

export const { GET, POST, PATCH, DELETE } = createPageRoute(
  pageTypeToRouteConfig(caseServer, process.env.AIRTABLE_API_KEY, loadCaseState),
);
