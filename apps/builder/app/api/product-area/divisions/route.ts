/**
 * Legacy product-area divisions list.
 *
 * Product-area-records lever fortfarande i legacy-basen, så Division-länken
 * pekar på legacy-basens divisions-tabell. SSOT:s `core_divisions` är en
 * separat tabell i Wexoe NY och är inte direkt utbytbar.
 *
 * Den här endpointen serverar legacy-divisions så SettingsEditor kan
 * dropdown:a dem client-side när PageTypeBuilder driver builder:n.
 * Tas bort när product-area-familjen migreras till SSOT.
 */

import { NextResponse } from 'next/server';
import { loadDivisions } from '@/lib/product-area-loader';

export async function GET() {
  const apiKey = process.env.AIRTABLE_API_KEY;
  if (!apiKey) {
    return NextResponse.json({ success: false, error: 'AIRTABLE_API_KEY ej konfigurerad.' }, { status: 500 });
  }
  try {
    const divisions = await loadDivisions(apiKey);
    return NextResponse.json({ success: true, divisions });
  } catch (err) {
    const message = err instanceof Error ? err.message : 'Hämtning misslyckades.';
    return NextResponse.json({ success: false, error: message }, { status: 500 });
  }
}
