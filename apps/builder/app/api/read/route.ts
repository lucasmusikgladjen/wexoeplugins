import { NextResponse } from 'next/server';
import { getRecord, listRecords, TABLE_IDS, AirtableRecord } from '@/lib/airtable';
import { pageStateFromRecords } from '@/lib/page-mapper';

const AIRTABLE_API_KEY = process.env.AIRTABLE_API_KEY;
const LP_TABLE_ID = TABLE_IDS.landingPages;

export async function GET(request: Request) {
  if (!AIRTABLE_API_KEY) {
    return NextResponse.json({ error: 'AIRTABLE_API_KEY not configured' }, { status: 500 });
  }

  const { searchParams } = new URL(request.url);
  const action = searchParams.get('action');

  try {
    if (action === 'list') {
      // List all landing pages — slug is primary, h1 is the headline.
      // Default base (Wexoe NY) used implicitly by listRecords.
      const records = await listRecords(AIRTABLE_API_KEY, LP_TABLE_ID, {
        fields: ['slug', 'h1', 'internal_notes'],
        sort: [{ field: 'slug', direction: 'asc' }],
      });
      const pages = records.map((r) => ({
        id: r.id,
        // 'name' kept for back-compat with the dashboard's PageRow shape;
        // we surface the slug since cms_landing_pages drops the legacy Name field.
        name: (r.fields?.slug as string) || '',
        slug: (r.fields?.slug as string) || '',
        h1: (r.fields?.h1 as string) || '',
      }));
      return NextResponse.json({ pages });
    }

    if (action === 'get') {
      const recordId = searchParams.get('id');
      if (!recordId) {
        return NextResponse.json({ error: 'Missing id parameter' }, { status: 400 });
      }

      // Fetch LP record + linked tabs + downloads
      const lp = await getRecord(AIRTABLE_API_KEY, TABLE_IDS.landingPages, recordId);

      // Fetch linked tabs by record IDs from the LP record (snake_case post-migration)
      const tabIds = (lp.fields['tab_ids'] as string[] | undefined) ?? [];
      let tabs: AirtableRecord[] = [];
      if (tabIds.length > 0) {
        const formula = `OR(${tabIds.map((id) => `RECORD_ID()='${id}'`).join(',')})`;
        tabs = await listRecords(AIRTABLE_API_KEY, TABLE_IDS.landingPageTabs, {
          filterByFormula: formula,
        });
      }

      // Collect all download IDs across tabs
      const downloadIds = new Set<string>();
      for (const tab of tabs) {
        const ids = (tab.fields['download_ids'] as string[] | undefined) ?? [];
        ids.forEach((id) => downloadIds.add(id));
      }

      let downloads: AirtableRecord[] = [];
      if (downloadIds.size > 0) {
        const formula = `OR(${[...downloadIds].map((id) => `RECORD_ID()='${id}'`).join(',')})`;
        downloads = await listRecords(AIRTABLE_API_KEY, TABLE_IDS.landingPageDownloads, {
          filterByFormula: formula,
        });
      }

      // Group downloads by their parent tab id (back-link is now `tab_ids`)
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

      return NextResponse.json({ state });
    }

    return NextResponse.json({ error: 'Unknown action. Use ?action=list or ?action=get&id=recXXX' }, { status: 400 });
  } catch (err) {
    const message = err instanceof Error ? err.message : 'Unknown error';
    return NextResponse.json({ error: message }, { status: 500 });
  }
}
