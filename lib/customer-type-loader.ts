import { getRecord } from './airtable';
import {
  CUSTOMER_TYPE_TABLE_IDS,
  CUSTOMER_TYPE_BASE_ID,
  customerTypePageStateFromRecord,
} from './customer-type-mapper';
import { CustomerTypePageState } from './customer-type-types';

export async function loadCustomerTypePageState(
  apiKey: string,
  recordId: string,
): Promise<CustomerTypePageState> {
  const record = await getRecord(
    apiKey,
    CUSTOMER_TYPE_TABLE_IDS.customerTypePages,
    recordId,
    CUSTOMER_TYPE_BASE_ID,
  );
  return customerTypePageStateFromRecord(record);
}
