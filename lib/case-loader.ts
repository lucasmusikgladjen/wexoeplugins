import { getRecord } from './airtable';
import { CASE_TABLE_ID, CASE_BASE_ID, caseStateFromRecord } from './case-mapper';
import { CaseState } from './case-types';

export async function loadCaseState(
  apiKey: string,
  recordId: string,
): Promise<CaseState> {
  const record = await getRecord(apiKey, CASE_TABLE_ID, recordId, CASE_BASE_ID);
  return caseStateFromRecord(record);
}
