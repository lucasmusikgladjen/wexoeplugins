/**
 * Server-side loader för partner-page-records.
 *
 * Tunn wrapper — partner-sidan har bara en tabell, så loader behöver inte
 * göra något smart. Filen finns för konsekvens med customer-type-loader
 * och product-area-loader (vilket gör route-factory:n och edit-pages
 * symmetriska över sidtyper).
 */

import { getRecord } from './airtable';
import {
  PARTNER_TABLE_IDS,
  PARTNER_BASE_ID,
  partnerPageStateFromRecord,
} from './partner-mapper';
import { PartnerPageState } from './partner-types';

export async function loadPartnerPageState(
  apiKey: string,
  recordId: string,
): Promise<PartnerPageState> {
  const record = await getRecord(
    apiKey,
    PARTNER_TABLE_IDS.partnerPages,
    recordId,
    PARTNER_BASE_ID,
  );
  return partnerPageStateFromRecord(record);
}
