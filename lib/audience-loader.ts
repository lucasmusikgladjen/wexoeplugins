import { getRecord } from './airtable';
import { AUDIENCE_TABLE_IDS, audienceStateFromRecord } from './audience-mapper';
import { AudienceState } from './audience-types';

export async function loadAudienceState(
  apiKey: string,
  recordId: string,
): Promise<AudienceState> {
  const record = await getRecord(apiKey, AUDIENCE_TABLE_IDS.audienceHeroes, recordId);
  return audienceStateFromRecord(record);
}
