const BASE_ID = 'appXoUcK68dQwASjF';

export const TABLE_IDS = {
  landingPages: 'tbl8KDqGq0Ray1uqS',
  tabs: 'tblvecOh3rAGmw3mw',
  downloads: 'tblbLM827DzjWGjCR',
} as const;

interface AirtableRecord {
  id: string;
  fields: Record<string, unknown>;
}

/** Create a single record in an Airtable table. */
export async function createRecord(
  apiKey: string,
  tableId: string,
  fields: Record<string, unknown>,
): Promise<AirtableRecord> {
  const res = await fetch(
    `https://api.airtable.com/v0/${BASE_ID}/${tableId}`,
    {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${apiKey}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ fields }),
    },
  );

  if (!res.ok) {
    const err = await res.json().catch(() => ({}));
    throw new Error(
      err.error?.message || `Airtable error ${res.status}: ${tableId}`,
    );
  }

  return res.json();
}

/** Batch-create records (max 10 per Airtable request). */
export async function createRecords(
  apiKey: string,
  tableId: string,
  records: Array<{ fields: Record<string, unknown> }>,
): Promise<AirtableRecord[]> {
  if (records.length === 0) return [];

  const results: AirtableRecord[] = [];

  for (let i = 0; i < records.length; i += 10) {
    const batch = records.slice(i, i + 10);
    const res = await fetch(
      `https://api.airtable.com/v0/${BASE_ID}/${tableId}`,
      {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${apiKey}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ records: batch }),
      },
    );

    if (!res.ok) {
      const err = await res.json().catch(() => ({}));
      throw new Error(
        err.error?.message || `Airtable batch error ${res.status}: ${tableId}`,
      );
    }

    const data = await res.json();
    results.push(...data.records);
  }

  return results;
}
