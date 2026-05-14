/**
 * Bidirektional mappning mellan Airtable-records och normaliserade TS-objekt
 * för SSOT-entiteter.
 *
 * Mappar Airtable Title Case-fältnamn → lower_snake_case domain-keys, samt
 * extraherar första attachment-URL ur multipleAttachments-fält.
 */

import { AirtableRecord } from '../airtable';
import { CoreEntityName } from './registry';

type AttachmentLike = { url?: string };

function firstAttachmentUrl(value: unknown): string {
  if (Array.isArray(value) && value.length > 0) {
    const first = value[0] as AttachmentLike;
    if (first && typeof first.url === 'string') return first.url;
  }
  return '';
}

function asString(v: unknown): string {
  if (typeof v === 'string') return v;
  if (typeof v === 'number' || typeof v === 'boolean') return String(v);
  return '';
}

function asNumber(v: unknown): number {
  if (typeof v === 'number') return v;
  if (typeof v === 'string' && v !== '') {
    const n = Number(v);
    if (!Number.isNaN(n)) return n;
  }
  return 0;
}

function asBool(v: unknown): boolean {
  return v === true || v === 'true' || v === 1;
}

function asLinkIds(v: unknown): string[] {
  return Array.isArray(v) ? v.filter((x): x is string => typeof x === 'string') : [];
}

/* --------------------------------------------------------
   Per-entity readers (Airtable record → normalized object)
   -------------------------------------------------------- */

function readCommon(rec: AirtableRecord): { _recordId: string; f: Record<string, unknown> } {
  return { _recordId: rec.id, f: rec.fields };
}

function readCompany(rec: AirtableRecord) {
  const { _recordId, f } = readCommon(rec);
  return {
    _recordId,
    slug: asString(f['Slug']),
    is_default: asBool(f['Is Default']),
    country_ids: asLinkIds(f['Country']),
    tagline: asString(f['Tagline']),
    org_number: asString(f['Org Number']),
    vat_number: asString(f['VAT Number']),
    email: asString(f['Email']),
    email_order: asString(f['Email order']),
    phone: asString(f['Phone']),
    phone_emergency: asString(f['Phone Emergency']),
    address_line_1: asString(f['Address Line 1']),
    address_postal_code: asString(f['Address Postal Code']),
    address_city: asString(f['Address City']),
    linkedin_url: asString(f['LinkedIn URL']),
    facebook_url: asString(f['Facebook URL']),
    instagram_url: asString(f['Instagram URL']),
    youtube_url: asString(f['YouTube URL']),
    hours_mon_thur: asString(f['Hours Mon-Thur']),
    hours_friday: asString(f['Hours Friday']),
    internal_notes: asString(f['Internal Notes']),
  };
}

function readGraphicProfile(rec: AirtableRecord) {
  const { _recordId, f } = readCommon(rec);
  return {
    _recordId,
    slug: asString(f['Slug']),
    is_default: asBool(f['Is Default']),
    logo_primary: firstAttachmentUrl(f['Logo Primary']),
    logo_dark_background: firstAttachmentUrl(f['Logo Dark Background']),
    favicon: firstAttachmentUrl(f['Favicon']),
    color_primary: asString(f['Color Primary']),
    color_secondary: asString(f['Color Secondary']),
    color_accent: asString(f['Color Accent']),
    color_background_light: asString(f['Color Background Light']),
    color_background_dark: asString(f['Color Background Dark']),
    color_text_primary: asString(f['Color Text Primary']),
    color_text_secondary: asString(f['Color Text Secondary']),
    font_heading: asString(f['Font Heading']),
    font_body: asString(f['Font Body']),
    font_css_url: asString(f['Font CSS URL']),
    division_ids: asLinkIds(f['Division']),
  };
}

function readCountry(rec: AirtableRecord) {
  const { _recordId, f } = readCommon(rec);
  return {
    _recordId,
    name: asString(f['Name']),
    code: asString(f['Code']),
    domain: asString(f['Domain']),
    url_prefix: asString(f['URL Prefix']),
    currency: asString(f['Currency']),
    locale: asString(f['Locale']),
    default_language: asString(f['Default Language']),
    order: asNumber(f['Order']),
    active: asBool(f['Active']),
  };
}

function readDivision(rec: AirtableRecord) {
  const { _recordId, f } = readCommon(rec);
  return {
    _recordId,
    name: asString(f['Name']),
    slug: asString(f['Slug']),
    description: asString(f['Description']),
    order: asNumber(f['Order']),
    active: asBool(f['Active']),
    country_ids: asLinkIds(f['Country']),
  };
}

function readCustomerType(rec: AirtableRecord) {
  const { _recordId, f } = readCommon(rec);
  return {
    _recordId,
    name: asString(f['Name']),
    slug: asString(f['Slug']),
    description: asString(f['Description']),
    icon: firstAttachmentUrl(f['Icon']),
    order: asNumber(f['Order']),
    active: asBool(f['Active']),
  };
}

function readCoworker(rec: AirtableRecord) {
  const { _recordId, f } = readCommon(rec);
  return {
    _recordId,
    full_name: asString(f['Full Name']),
    title: asString(f['Title']),
    email: asString(f['Email']),
    phone: asString(f['Phone']),
    image: firstAttachmentUrl(f['Image']),
    linkedin_url: asString(f['LinkedIn URL']),
    bio: asString(f['Bio']),
    order: asNumber(f['Order']),
    active: asBool(f['Active']),
    division_ids: asLinkIds(f['Division']),
    country_ids: asLinkIds(f['Country']),
  };
}

function readPartner(rec: AirtableRecord) {
  const { _recordId, f } = readCommon(rec);
  return {
    _recordId,
    name: asString(f['Name']),
    logo: firstAttachmentUrl(f['Logo']),
    logo_transparent: firstAttachmentUrl(f['Logo Transparent']),
    url: asString(f['URL']),
    description: asString(f['Description']),
    order: asNumber(f['Order']),
    active: asBool(f['Active']),
    division_ids: asLinkIds(f['Division']),
    country_ids: asLinkIds(f['Country']),
  };
}

function readTestimonial(rec: AirtableRecord) {
  const { _recordId, f } = readCommon(rec);
  return {
    _recordId,
    internal_name: asString(f['Internal Name']),
    quote: asString(f['Quote']),
    author_name: asString(f['Author Name']),
    author_title: asString(f['Author Title']),
    author_image: firstAttachmentUrl(f['Author Image']),
    order: asNumber(f['Order']),
    active: asBool(f['Active']),
    featured: asBool(f['Featured']),
    customer_type_ids: asLinkIds(f['Customer Type']),
    division_ids: asLinkIds(f['Division']),
    country_ids: asLinkIds(f['Country']),
  };
}

export function readEntityRecord(entity: CoreEntityName, rec: AirtableRecord): Record<string, unknown> {
  switch (entity) {
    case 'core_company': return readCompany(rec);
    case 'core_graphic_profile': return readGraphicProfile(rec);
    case 'core_countries': return readCountry(rec);
    case 'core_divisions': return readDivision(rec);
    case 'core_customer_types': return readCustomerType(rec);
    case 'core_coworkers': return readCoworker(rec);
    case 'core_partners': return readPartner(rec);
    case 'core_testimonials': return readTestimonial(rec);
  }
}

/* --------------------------------------------------------
   Domain → Airtable fields (för write)
   -------------------------------------------------------- */

function writeAttachment(url: string): unknown {
  return url ? [{ url }] : [];
}

function cleanField(v: unknown): unknown {
  if (v === undefined || v === '') return null;
  return v;
}

function writeCompany(s: Record<string, unknown>): Record<string, unknown> {
  return {
    'Slug': cleanField(s.slug),
    // Singleton: bara ett record finns och det är alltid default.
    'Is Default': true,
    'Country': asLinkIds(s.country_ids),
    'Tagline': cleanField(s.tagline),
    'Org Number': cleanField(s.org_number),
    'VAT Number': cleanField(s.vat_number),
    'Email': cleanField(s.email),
    'Email order': cleanField(s.email_order),
    'Phone': cleanField(s.phone),
    'Phone Emergency': cleanField(s.phone_emergency),
    'Address Line 1': cleanField(s.address_line_1),
    'Address Postal Code': cleanField(s.address_postal_code),
    'Address City': cleanField(s.address_city),
    'LinkedIn URL': cleanField(s.linkedin_url),
    'Facebook URL': cleanField(s.facebook_url),
    'Instagram URL': cleanField(s.instagram_url),
    'YouTube URL': cleanField(s.youtube_url),
    'Hours Mon-Thur': cleanField(s.hours_mon_thur),
    'Hours Friday': cleanField(s.hours_friday),
    'Internal Notes': cleanField(s.internal_notes),
  };
}

function writeGraphicProfile(s: Record<string, unknown>): Record<string, unknown> {
  return {
    'Slug': cleanField(s.slug),
    'Is Default': !!s.is_default,
    'Logo Primary': writeAttachment(asString(s.logo_primary)),
    'Logo Dark Background': writeAttachment(asString(s.logo_dark_background)),
    'Favicon': writeAttachment(asString(s.favicon)),
    'Color Primary': cleanField(s.color_primary),
    'Color Secondary': cleanField(s.color_secondary),
    'Color Accent': cleanField(s.color_accent),
    'Color Background Light': cleanField(s.color_background_light),
    'Color Background Dark': cleanField(s.color_background_dark),
    'Color Text Primary': cleanField(s.color_text_primary),
    'Color Text Secondary': cleanField(s.color_text_secondary),
    'Font Heading': cleanField(s.font_heading),
    'Font Body': cleanField(s.font_body),
    'Font CSS URL': cleanField(s.font_css_url),
    'Division': asLinkIds(s.division_ids),
  };
}

function writeCountry(s: Record<string, unknown>): Record<string, unknown> {
  return {
    'Name': cleanField(s.name),
    'Code': cleanField(s.code),
    'Domain': cleanField(s.domain),
    'URL Prefix': cleanField(s.url_prefix),
    'Currency': cleanField(s.currency),
    'Locale': cleanField(s.locale),
    'Default Language': cleanField(s.default_language),
    'Order': s.order === '' ? null : Number(s.order ?? 0),
    'Active': !!s.active,
  };
}

function writeDivision(s: Record<string, unknown>): Record<string, unknown> {
  return {
    'Name': cleanField(s.name),
    'Slug': cleanField(s.slug),
    'Description': cleanField(s.description),
    'Order': s.order === '' ? null : Number(s.order ?? 0),
    'Active': !!s.active,
    'Country': asLinkIds(s.country_ids),
  };
}

function writeCustomerType(s: Record<string, unknown>): Record<string, unknown> {
  return {
    'Name': cleanField(s.name),
    'Slug': cleanField(s.slug),
    'Description': cleanField(s.description),
    'Icon': writeAttachment(asString(s.icon)),
    'Order': s.order === '' ? null : Number(s.order ?? 0),
    'Active': !!s.active,
  };
}

function writeCoworker(s: Record<string, unknown>): Record<string, unknown> {
  return {
    'Full Name': cleanField(s.full_name),
    'Title': cleanField(s.title),
    'Email': cleanField(s.email),
    'Phone': cleanField(s.phone),
    'Image': writeAttachment(asString(s.image)),
    'LinkedIn URL': cleanField(s.linkedin_url),
    'Bio': cleanField(s.bio),
    'Order': s.order === '' ? null : Number(s.order ?? 0),
    'Active': !!s.active,
    'Division': asLinkIds(s.division_ids),
    'Country': asLinkIds(s.country_ids),
  };
}

function writePartner(s: Record<string, unknown>): Record<string, unknown> {
  return {
    'Name': cleanField(s.name),
    'Logo': writeAttachment(asString(s.logo)),
    'Logo Transparent': writeAttachment(asString(s.logo_transparent)),
    'URL': cleanField(s.url),
    'Description': cleanField(s.description),
    'Order': s.order === '' ? null : Number(s.order ?? 0),
    'Active': !!s.active,
    'Division': asLinkIds(s.division_ids),
    'Country': asLinkIds(s.country_ids),
  };
}

function writeTestimonial(s: Record<string, unknown>): Record<string, unknown> {
  return {
    'Internal Name': cleanField(s.internal_name),
    'Quote': cleanField(s.quote),
    'Author Name': cleanField(s.author_name),
    'Author Title': cleanField(s.author_title),
    'Author Image': writeAttachment(asString(s.author_image)),
    'Order': s.order === '' ? null : Number(s.order ?? 0),
    'Active': !!s.active,
    'Featured': !!s.featured,
    'Customer Type': asLinkIds(s.customer_type_ids),
    'Division': asLinkIds(s.division_ids),
    'Country': asLinkIds(s.country_ids),
  };
}

/**
 * Mappa core-entity-state → Airtable-fält-map.
 *
 * `mode`:
 *   - 'create' (default): tomma värden tas bort så Airtable använder defaults.
 *   - 'update': null behålls så Airtable rensar fältet (utelämnade fält lämnas
 *     orörda av PATCH-semantiken).
 */
export function writeEntityFields(
  entity: CoreEntityName,
  state: Record<string, unknown>,
  mode: 'create' | 'update' = 'create',
): Record<string, unknown> {
  const raw = (() => {
    switch (entity) {
      case 'core_company': return writeCompany(state);
      case 'core_graphic_profile': return writeGraphicProfile(state);
      case 'core_countries': return writeCountry(state);
      case 'core_divisions': return writeDivision(state);
      case 'core_customer_types': return writeCustomerType(state);
      case 'core_coworkers': return writeCoworker(state);
      case 'core_partners': return writePartner(state);
      case 'core_testimonials': return writeTestimonial(state);
    }
  })();
  const out: Record<string, unknown> = {};
  for (const [k, v] of Object.entries(raw)) {
    if (v === undefined) continue;
    if (v === null) {
      if (mode === 'update') {
        out[k] = null;
      }
      continue;
    }
    out[k] = v;
  }
  return out;
}
