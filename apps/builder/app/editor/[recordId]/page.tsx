import { notFound } from 'next/navigation';
import Link from 'next/link';
import PageBuilder from '@/components/PageBuilder';
import { getRecord, listRecords, TABLE_IDS, AirtableRecord } from '@/lib/airtable';
import { pageStateFromRecords } from '@/lib/page-mapper';

export const dynamic = 'force-dynamic';

interface Props {
  params: Promise<{ recordId: string }>;
}

export default async function EditExistingPage({ params }: Props) {
  const { recordId } = await params;
  const apiKey = process.env.AIRTABLE_API_KEY;

  if (!apiKey) {
    return (
      <ErrorScreen
        title="Konfigurationsfel"
        message="AIRTABLE_API_KEY saknas i miljövariablerna."
      />
    );
  }

  let lp: AirtableRecord;
  try {
    lp = await getRecord(apiKey, TABLE_IDS.landingPages, recordId);
  } catch (err) {
    const message = err instanceof Error ? err.message : 'Okänt fel';
    if (/not found|404/i.test(message)) notFound();
    return (
      <ErrorScreen
        title="Kunde inte hämta sidan"
        message={message}
      />
    );
  }

  const tabIds: string[] = (lp.fields['tab_ids'] as string[] | undefined) ?? [];
  let tabs: AirtableRecord[] = [];
  if (tabIds.length > 0) {
    const formula = `OR(${tabIds.map((id) => `RECORD_ID()='${id}'`).join(',')})`;
    tabs = await listRecords(apiKey, TABLE_IDS.landingPageTabs, { filterByFormula: formula });
  }

  const downloadIds = new Set<string>();
  for (const tab of tabs) {
    const ids = (tab.fields['download_ids'] as string[] | undefined) ?? [];
    ids.forEach((id) => downloadIds.add(id));
  }

  let downloads: AirtableRecord[] = [];
  if (downloadIds.size > 0) {
    const formula = `OR(${[...downloadIds].map((id) => `RECORD_ID()='${id}'`).join(',')})`;
    downloads = await listRecords(apiKey, TABLE_IDS.landingPageDownloads, { filterByFormula: formula });
  }

  const downloadsByTabId: Record<string, AirtableRecord[]> = {};
  for (const dl of downloads) {
    const tabRefs = (dl.fields['tab_ids'] as string[] | undefined) ?? [];
    for (const tabId of tabRefs) {
      (downloadsByTabId[tabId] ??= []).push(dl);
    }
  }

  const state = pageStateFromRecords({
    landingPage: lp,
    tabs,
    downloadsByTabId,
  });

  return <PageBuilder loadedState={state} />;
}

function ErrorScreen({ title, message }: { title: string; message: string }) {
  return (
    <div className="min-h-screen flex items-center justify-center bg-white" style={{ fontFamily: 'var(--font-dm-sans)' }}>
      <div className="max-w-md text-center px-8">
        <h1 className="text-xl font-medium text-gray-900 mb-2">{title}</h1>
        <p className="text-sm text-gray-500 mb-6">{message}</p>
        <Link
          href="/"
          className="inline-block text-sm text-gray-400 hover:text-gray-700 transition-colors"
        >
          ← Tillbaka till sidor
        </Link>
      </div>
    </div>
  );
}
