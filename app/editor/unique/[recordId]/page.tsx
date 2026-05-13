import { notFound } from 'next/navigation';
import { getRecord, listRecords, SSOT_BASE_ID } from '@/lib/airtable';
import UniquePageBuilder from '@/components/UniquePageBuilder';
import { uniquePageStateFromRecord, UNIQUE_PAGES_TABLE_ID } from '@/lib/unique-page-mapper';

export const dynamic = 'force-dynamic';

interface CountryOption { recordId: string; code: string; name: string; }
interface DivisionOption { recordId: string; slug: string; name: string; }

async function loadOptions(apiKey: string): Promise<{ countries: CountryOption[]; divisions: DivisionOption[] }> {
  const [countries, divisions] = await Promise.all([
    listRecords(apiKey, 'tblCZ082jWGUBrUAK', { baseId: SSOT_BASE_ID }),
    listRecords(apiKey, 'tblyxs2zsoRBozxQS', { baseId: SSOT_BASE_ID }),
  ]);
  return {
    countries: countries.map((r) => ({
      recordId: r.id,
      code: String(r.fields['Code'] ?? ''),
      name: String(r.fields['Name'] ?? ''),
    })),
    divisions: divisions.map((r) => ({
      recordId: r.id,
      slug: String(r.fields['Slug'] ?? ''),
      name: String(r.fields['Name'] ?? ''),
    })),
  };
}

export default async function EditUniquePage({
  params,
}: {
  params: Promise<{ recordId: string }>;
}) {
  const { recordId } = await params;
  const apiKey = process.env.AIRTABLE_API_KEY;
  if (!apiKey) {
    return <main className="p-8 text-sm text-red-600">AIRTABLE_API_KEY ej konfigurerad.</main>;
  }
  let rec;
  try {
    rec = await getRecord(apiKey, UNIQUE_PAGES_TABLE_ID, recordId, SSOT_BASE_ID);
  } catch {
    notFound();
  }
  if (!rec) notFound();
  const state = uniquePageStateFromRecord(rec);
  const { countries, divisions } = await loadOptions(apiKey);
  return (
    <UniquePageBuilder
      initialState={state}
      countryOptions={countries}
      divisionOptions={divisions}
    />
  );
}
