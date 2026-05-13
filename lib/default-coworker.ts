/**
 * Default-coworker-resolver för LP/PA-create-flöden.
 *
 * När en ny LP/PA skapas och alla `contact_*`-fält är tomma, slå upp
 * `core_coworkers` filtrerat på (country + ev. division) och returnera första
 * aktiva coworker. Den används som default kontaktperson på sidan.
 *
 * Server-side only. Anropas från `/api/publish` (LP), `/api/product-area` (PA).
 */

import { listRecords, SSOT_BASE_ID } from './airtable';
import { CORE_ENTITIES } from './core/registry';

export interface DefaultCoworkerFields {
  contactName: string;
  contactTitle: string;
  contactEmail: string;
  contactPhone: string;
  contactImage: string;
}

interface ResolveOpts {
  apiKey: string;
  /** ISO-kod, t.ex. 'SE'. Tom = ingen country-filtrering. */
  countryCode?: string;
  /** Division-slug. Tom = ingen division-filtrering. */
  divisionSlug?: string;
}

/**
 * Slå upp första aktiva coworker enligt scope. Tomt resultat ger tomma strängar
 * — caller använder dem som default-värden och kan därför skriva tomma fält
 * utan extra null-check.
 *
 * Returnerar null om SSOT-läsning misslyckas helt — caller ska då hoppa över
 * default-injektion (annars kan vi skriva över med tomma värden).
 */
export async function resolveDefaultCoworker(opts: ResolveOpts): Promise<DefaultCoworkerFields | null> {
  try {
    let countryRecordId: string | null = null;
    if (opts.countryCode) {
      const countries = await listRecords(opts.apiKey, CORE_ENTITIES.core_countries.tableId, {
        baseId: SSOT_BASE_ID,
        filterByFormula: `UPPER({Code})="${opts.countryCode.toUpperCase()}"`,
      });
      countryRecordId = countries[0]?.id ?? null;
    }

    let divisionRecordId: string | null = null;
    if (opts.divisionSlug) {
      const divisions = await listRecords(opts.apiKey, CORE_ENTITIES.core_divisions.tableId, {
        baseId: SSOT_BASE_ID,
        filterByFormula: `LOWER({Slug})="${opts.divisionSlug.toLowerCase()}"`,
      });
      divisionRecordId = divisions[0]?.id ?? null;
    }

    const coworkers = await listRecords(opts.apiKey, CORE_ENTITIES.core_coworkers.tableId, {
      baseId: SSOT_BASE_ID,
      filterByFormula: '{Active}=TRUE()',
      sort: [{ field: 'Order', direction: 'asc' }],
    });

    type AttachmentLike = { url?: string };
    const firstImageUrl = (v: unknown): string => {
      if (Array.isArray(v) && v.length > 0) {
        const a = v[0] as AttachmentLike;
        if (a && typeof a.url === 'string') return a.url;
      }
      return '';
    };
    const asArr = (v: unknown): string[] => (Array.isArray(v) ? (v.filter((x) => typeof x === 'string') as string[]) : []);

    const match = coworkers.find((c) => {
      const f = c.fields;
      if (countryRecordId) {
        const ids = asArr(f['Country']);
        if (ids.length > 0 && !ids.includes(countryRecordId)) return false;
      }
      if (divisionRecordId) {
        const ids = asArr(f['Division']);
        if (ids.length > 0 && !ids.includes(divisionRecordId)) return false;
      }
      return true;
    }) ?? coworkers[0];

    if (!match) {
      return { contactName: '', contactTitle: '', contactEmail: '', contactPhone: '', contactImage: '' };
    }

    const f = match.fields;
    const str = (k: string) => (typeof f[k] === 'string' ? (f[k] as string) : '');
    return {
      contactName: str('Full Name'),
      contactTitle: str('Title'),
      contactEmail: str('Email'),
      contactPhone: str('Phone'),
      contactImage: firstImageUrl(f['Image']),
    };
  } catch (err) {
    console.warn('[default-coworker] resolveDefaultCoworker misslyckades:', err);
    return null;
  }
}

/**
 * Returnerar `true` om alla contact_*-fält i state är tomma och alltså är
 * kandidater för auto-fyllnad vid create.
 */
export function contactFieldsEmpty(state: {
  contactName?: string;
  contactTitle?: string;
  contactEmail?: string;
  contactPhone?: string;
  contactImage?: string;
}): boolean {
  return !(
    (state.contactName ?? '').trim() ||
    (state.contactTitle ?? '').trim() ||
    (state.contactEmail ?? '').trim() ||
    (state.contactPhone ?? '').trim() ||
    (state.contactImage ?? '').trim()
  );
}
