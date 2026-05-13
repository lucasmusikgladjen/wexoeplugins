import { notFound } from 'next/navigation';
import { CORE_ENTITIES, isCoreEntityName, CoreEntityName } from '@/lib/core/registry';
import { CORE_ENTITY_FORMS } from '@/lib/core/forms';
import { loadEntityRecords } from '@/lib/core/loader';
import CoreEntityShell from '@/components/core/CoreEntityShell';

export const dynamic = 'force-dynamic';

interface LinkOption {
  recordId: string;
  label: string;
}

/**
 * Slug-segment använder kebab-case (core-company) men entity-namn underscored
 * (core_company). Konvertera mellan formen för URL- och API-konsistens.
 */
function segmentToEntity(segment: string): CoreEntityName | null {
  const candidate = segment.replace(/-/g, '_');
  return isCoreEntityName(candidate) ? candidate : null;
}

export default async function EntityPage({
  params,
}: {
  params: Promise<{ entity: string }>;
}) {
  const { entity: rawSegment } = await params;
  const entity = segmentToEntity(rawSegment);
  if (entity === null) notFound();

  const apiKey = process.env.AIRTABLE_API_KEY;
  if (!apiKey) {
    return (
      <main className="p-8 text-sm text-red-600">
        AIRTABLE_API_KEY ej konfigurerad.
      </main>
    );
  }

  const records = await loadEntityRecords(entity, apiKey);

  // Hämta länk-options för de fält som är `type: 'link'`.
  const linkedEntities = new Set<CoreEntityName>();
  for (const field of CORE_ENTITY_FORMS[entity].fields) {
    if (field.type === 'link' && field.linkedEntity) {
      linkedEntities.add(field.linkedEntity);
    }
  }

  const linkOptions: Record<string, LinkOption[]> = {};
  await Promise.all(
    [...linkedEntities].map(async (linkedName) => {
      try {
        const recs = await loadEntityRecords(linkedName, apiKey);
        const labelField = CORE_ENTITY_FORMS[linkedName].listLabelField;
        linkOptions[linkedName] = recs.map((r) => ({
          recordId: r._recordId as string,
          label: String(r[labelField] ?? '(tomt)'),
        }));
      } catch {
        linkOptions[linkedName] = [];
      }
    }),
  );

  return (
    <CoreEntityShell
      entity={entity}
      initialRecords={records}
      linkOptions={linkOptions}
    />
  );
}

export function generateMetadata({ params }: { params: Promise<{ entity: string }> }) {
  return params.then(({ entity }) => {
    const ent = segmentToEntity(entity);
    return {
      title: ent ? `${CORE_ENTITIES[ent].label} — Globaler` : 'Globaler',
    };
  });
}
