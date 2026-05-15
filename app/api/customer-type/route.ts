/**
 * CRUD-route för customer-type-page-records.
 *
 * Drivs deklarativt av `customerTypeServer`
 * (lib/page-types/customer-type.server.ts). Endpoints: GET ?action=list |
 * get&id, POST, PATCH ?id, DELETE ?id.
 *
 * Tabellen ligger i Wexoe NY (cms_customer_type_pages) — BASE_ID är wired
 * på customerTypeServer.
 */

import {
  customerTypeServer,
  loadCustomerTypePageState,
} from '@/lib/page-types/customer-type.server';
import { createPageRoute, pageTypeToRouteConfig } from '@/lib/route-factory';

export const { GET, POST, PATCH, DELETE } = createPageRoute(
  pageTypeToRouteConfig(customerTypeServer, process.env.AIRTABLE_API_KEY, loadCustomerTypePageState),
);
