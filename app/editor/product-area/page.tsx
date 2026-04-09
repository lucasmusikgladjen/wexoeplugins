import Link from 'next/link';
import ProductAreaBuilder from '@/components/product-area/ProductAreaBuilder';
import { loadDivisions } from '@/lib/product-area-loader';
import { emptyProductAreaState, Division } from '@/lib/product-area-types';

export const dynamic = 'force-dynamic';

export default async function CreateProductAreaPage() {
  const apiKey = process.env.AIRTABLE_API_KEY;

  if (!apiKey) {
    return (
      <ErrorScreen
        title="Konfigurationsfel"
        message="AIRTABLE_API_KEY saknas i miljövariablerna."
      />
    );
  }

  let divisions: Division[] = [];
  try {
    divisions = await loadDivisions(apiKey);
  } catch (err) {
    // Non-fatal — user can still create without picking a division.
    console.error('[create-product-area] Could not load divisions:', err);
  }

  return (
    <ProductAreaBuilder
      initialState={emptyProductAreaState()}
      divisions={divisions}
    />
  );
}

function ErrorScreen({ title, message }: { title: string; message: string }) {
  return (
    <div
      className="min-h-screen flex items-center justify-center bg-white"
      style={{ fontFamily: 'var(--font-dm-sans)' }}
    >
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
