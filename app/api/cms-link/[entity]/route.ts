/**
 * Read-only listing of cms_* entities som kan länkas från sidor i buildern.
 *
 * Skiljer sig från `/api/core/[entity]` på flera sätt:
 *   - Bara GET — cms_*-records redigeras i sin egen sidtyps-editor, inte här.
 *   - Whitelistat till de cms-tabeller som faktiskt länkas från andra sidor
 *     (idag: products + articles, används av case-sidan).
 *   - Returnerar en mager projektion (recordId + namn + bild + ev. extras)
 *     för att hålla payload liten — pickern behöver inte fullständig data.
 *
 * Om en ny sidtyp behöver länka en ny cms-tabell, lägg till tabellen i
 * `CMS_LINK_SOURCES` nedan — då blir den tillgänglig som `source` i
 * `<Field.LinkedRecords />`-komponenten utan ytterligare wiring.
 */

import { NextRequest, NextResponse } from 'next/server';
import { listRecords, BASE_ID } from '@/lib/airtable';
import { str, bool, linkedIds } from '@/lib/airtable-helpers';

interface CmsLinkSourceDef {
  tableId: string;
  /** Fältnamn (Airtable) som ska hämtas. Mindre payload = snabbare picker. */
  fields: readonly string[];
  /** Default sort. */
  sortField: string;
}

const CMS_LINK_SOURCES = {
  products: {
    tableId: 'tblN23V7uAMpeZoO1',
    fields: ['name', 'image_url', 'is_active', 'supplier_ids', 'description'],
    sortField: 'name',
  },
  articles: {
    tableId: 'tblhnz3MQG1JwfKrN',
    fields: ['name', 'image_url', 'article_number', 'is_active', 'supplier_ids', 'description'],
    sortField: 'name',
  },
} as const satisfies Record<string, CmsLinkSourceDef>;

type CmsLinkSource = keyof typeof CMS_LINK_SOURCES;

function isCmsLinkSource(s: string): s is CmsLinkSource {
  return Object.prototype.hasOwnProperty.call(CMS_LINK_SOURCES, s);
}

const apiKey = process.env.AIRTABLE_API_KEY;

export async function GET(
  _req: NextRequest,
  { params }: { params: Promise<{ entity: string }> },
) {
  if (!apiKey) {
    return NextResponse.json(
      { success: false, error: 'AIRTABLE_API_KEY ej konfigurerad.' },
      { status: 500 },
    );
  }

  const { entity } = await params;
  if (!isCmsLinkSource(entity)) {
    return NextResponse.json(
      { success: false, error: `Okänd cms-link-källa: ${entity}` },
      { status: 400 },
    );
  }

  const def = CMS_LINK_SOURCES[entity];
  try {
    const records = await listRecords(apiKey, def.tableId, {
      baseId: BASE_ID,
      fields: [...def.fields],
      sort: [{ field: def.sortField, direction: 'asc' }],
    });
    const normalized = records.map((r) => {
      const f = r.fields;
      const out: Record<string, unknown> = {
        _recordId: r.id,
        name: str(f, 'name'),
        image_url: str(f, 'image_url'),
        is_active: bool(f, 'is_active', true),
        supplier_ids: linkedIds(f, 'supplier_ids'),
        description: str(f, 'description'),
      };
      if (entity === 'articles') {
        out.article_number = str(f, 'article_number');
      }
      return out;
    });
    return NextResponse.json({ success: true, records: normalized });
  } catch (err) {
    return NextResponse.json(
      { success: false, error: err instanceof Error ? err.message : 'Listning misslyckades.' },
      { status: 500 },
    );
  }
}
