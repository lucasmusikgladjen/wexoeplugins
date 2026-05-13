import { listRecords, SSOT_BASE_ID } from '@/lib/airtable';
import UniquePageBuilder from '@/components/UniquePageBuilder';
import { emptyUniquePageState } from '@/lib/unique-page-types';

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

export default async function CreateUniquePage() {
  const apiKey = process.env.AIRTABLE_API_KEY;
  if (!apiKey) {
    return <main className="p-8 text-sm text-red-600">AIRTABLE_API_KEY ej konfigurerad.</main>;
  }
  const { countries, divisions } = await loadOptions(apiKey);
  return (
    <UniquePageBuilder
      initialState={emptyUniquePageState()}
      countryOptions={countries}
      divisionOptions={divisions}
    />
  );
}
