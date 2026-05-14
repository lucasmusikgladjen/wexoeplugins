import Link from 'next/link';
import { CORE_ENTITIES, CORE_ENTITY_NAMES } from '@/lib/core/registry';
import { loadEntityCounts } from '@/lib/core/loader';

export const dynamic = 'force-dynamic';

export default async function GlobalsPage() {
  const apiKey = process.env.AIRTABLE_API_KEY;
  let counts: Record<string, number> = {};
  let countError: string | null = null;
  if (apiKey) {
    try {
      counts = await loadEntityCounts(apiKey);
    } catch (err) {
      countError = err instanceof Error ? err.message : 'okänt fel';
    }
  }

  const grouped = {
    grunddata: CORE_ENTITY_NAMES.filter((n) => CORE_ENTITIES[n].role === 'singleton'),
    taxonomy: CORE_ENTITY_NAMES.filter((n) => CORE_ENTITIES[n].role === 'taxonomy'),
    collection: CORE_ENTITY_NAMES.filter((n) => CORE_ENTITIES[n].role === 'collection'),
  };

  return (
    <div className="min-h-screen bg-gray-50" style={{ fontFamily: 'var(--font-dm-sans)' }}>
      <header className="h-14 border-b border-gray-100 bg-white flex items-center px-4 gap-4">
        <Link href="/" className="flex items-center gap-2 text-gray-500 hover:text-gray-900">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M19 12H5M12 19l-7-7 7-7" />
          </svg>
          <span className="text-sm">Sidor</span>
        </Link>
        <div className="h-5 w-px bg-gray-200 mx-1" />
        <h1 className="text-sm font-medium text-gray-800">Globaler (SSOT)</h1>
      </header>

      <main className="max-w-5xl mx-auto px-6 py-10">
        {countError && (
          <div className="mb-6 text-sm text-amber-700 bg-amber-50 border border-amber-100 rounded-md px-3 py-2">
            Kunde inte hämta records-räkning: {countError}
          </div>
        )}
        {!apiKey && (
          <div className="mb-6 text-sm text-red-700 bg-red-50 border border-red-100 rounded-md px-3 py-2">
            AIRTABLE_API_KEY ej konfigurerad — list-vyn fungerar men records syns inte.
          </div>
        )}

        <Section title="Grunddata" description="Övergripande inställningar och profil.">
          {grouped.grunddata.map((name) => (
            <EntityCard key={name} name={name} count={counts[name] ?? 0} />
          ))}
        </Section>

        <Section title="Taxonomi" description="Referensdata som andra entiteter länkar till.">
          {grouped.taxonomy.map((name) => (
            <EntityCard key={name} name={name} count={counts[name] ?? 0} />
          ))}
        </Section>

        <Section title="Kollektion" description="Många records med scope-filtrering.">
          {grouped.collection.map((name) => (
            <EntityCard key={name} name={name} count={counts[name] ?? 0} />
          ))}
        </Section>
      </main>
    </div>
  );
}

function Section({ title, description, children }: { title: string; description: string; children: React.ReactNode }) {
  return (
    <section className="mb-10">
      <h2 className="text-xs uppercase tracking-wider text-gray-500 mb-1">{title}</h2>
      <p className="text-xs text-gray-400 mb-3">{description}</p>
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">{children}</div>
    </section>
  );
}

function EntityCard({ name, count }: { name: keyof typeof CORE_ENTITIES; count: number }) {
  const def = CORE_ENTITIES[name];
  const segment = name.replace(/_/g, '-');
  return (
    <Link
      href={`/globals/${segment}`}
      className="block bg-white rounded-md border border-gray-100 hover:border-gray-300 hover:shadow-sm transition-all p-4"
    >
      <div className="flex items-center justify-between mb-1">
        <span className="text-sm font-medium text-gray-800">{def.label}</span>
        <span className="text-[10px] text-gray-300 uppercase tracking-wider">{count}</span>
      </div>
      <p className="text-xs text-gray-500 line-clamp-2">{def.description}</p>
    </Link>
  );
}
