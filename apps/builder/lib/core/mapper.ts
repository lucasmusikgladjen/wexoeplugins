/**
 * Bidirektional mappning mellan Airtable-records och normaliserade TS-objekt
 * för SSOT-entiteter.
 *
 * Post-migration: snake_case överallt — passthrough mellan Airtable display-namn
 * och kod-fält. Bild-fält är singleLineText URL-strängar (wp-content).
 */

import { AirtableRecord } from '../airtable';
import { CoreEntityName } from './registry';

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
    slug: asString(f['slug']),
    is_default: asBool(f['is_default']),
    country_ids: asLinkIds(f['country_ids']),
    company_name: asString(f['company_name']),
    tagline: asString(f['tagline']),
    org_number: asString(f['org_number']),
    vat_number: asString(f['vat_number']),
    email: asString(f['email']),
    email_order: asString(f['email_order']),
    phone: asString(f['phone']),
    phone_emergency: asString(f['phone_emergency']),
    address_line_1: asString(f['address_line_1']),
    address_postal_code: asString(f['address_postal_code']),
    address_city: asString(f['address_city']),
    linkedin_url: asString(f['linkedin_url']),
    facebook_url: asString(f['facebook_url']),
    instagram_url: asString(f['instagram_url']),
    youtube_url: asString(f['youtube_url']),
    hours_mon_thur: asString(f['hours_mon_thur']),
    hours_friday: asString(f['hours_friday']),
    internal_notes: asString(f['internal_notes']),
  };
}

function readGraphicProfile(rec: AirtableRecord) {
  const { _recordId, f } = readCommon(rec);
  return {
    _recordId,
    slug: asString(f['slug']),
    is_default: asBool(f['is_default']),
    logo_primary_url: asString(f['logo_primary_url']),
    logo_dark_url: asString(f['logo_dark_url']),
    favicon_url: asString(f['favicon_url']),
    color_primary: asString(f['color_primary']),
    color_secondary: asString(f['color_secondary']),
    color_accent: asString(f['color_accent']),
    color_background_light: asString(f['color_background_light']),
    color_background_dark: asString(f['color_background_dark']),
    color_text_primary: asString(f['color_text_primary']),
    color_text_secondary: asString(f['color_text_secondary']),
    font_heading: asString(f['font_heading']),
    font_body: asString(f['font_body']),
    font_css_url: asString(f['font_css_url']),
    division_ids: asLinkIds(f['division_ids']),
    internal_notes: asString(f['internal_notes']),
  };
}

function readCountry(rec: AirtableRecord) {
  const { _recordId, f } = readCommon(rec);
  return {
    _recordId,
    name: asString(f['name']),
    code: asString(f['code']),
    domain: asString(f['domain']),
    url_prefix: asString(f['url_prefix']),
    currency: asString(f['currency']),
    locale: asString(f['locale']),
    default_language: asString(f['default_language']),
    order: asNumber(f['order']),
    is_active: asBool(f['is_active']),
    internal_notes: asString(f['internal_notes']),
  };
}

function readDivision(rec: AirtableRecord) {
  const { _recordId, f } = readCommon(rec);
  return {
    _recordId,
    name: asString(f['name']),
    slug: asString(f['slug']),
    description: asString(f['description']),
    order: asNumber(f['order']),
    is_active: asBool(f['is_active']),
    country_ids: asLinkIds(f['country_ids']),
    internal_notes: asString(f['internal_notes']),
  };
}

function readCustomerType(rec: AirtableRecord) {
  const { _recordId, f } = readCommon(rec);
  return {
    _recordId,
    name: asString(f['name']),
    slug: asString(f['slug']),
    description: asString(f['description']),
    icon: asString(f['icon']),
    order: asNumber(f['order']),
    is_active: asBool(f['is_active']),
    internal_notes: asString(f['internal_notes']),
  };
}

function readCoworker(rec: AirtableRecord) {
  const { _recordId, f } = readCommon(rec);
  return {
    _recordId,
    full_name: asString(f['full_name']),
    title: asString(f['title']),
    email: asString(f['email']),
    phone: asString(f['phone']),
    image_url: asString(f['image_url']),
    linkedin_url: asString(f['linkedin_url']),
    bio: asString(f['bio']),
    order: asNumber(f['order']),
    is_active: asBool(f['is_active']),
    division_ids: asLinkIds(f['division_ids']),
    country_ids: asLinkIds(f['country_ids']),
    internal_notes: asString(f['internal_notes']),
  };
}

function readPartner(rec: AirtableRecord) {
  const { _recordId, f } = readCommon(rec);
  return {
    _recordId,
    name: asString(f['name']),
    logo_url: asString(f['logo_url']),
    logo_transparent_url: asString(f['logo_transparent_url']),
    url: asString(f['url']),
    description: asString(f['description']),
    order: asNumber(f['order']),
    is_active: asBool(f['is_active']),
    division_ids: asLinkIds(f['division_ids']),
    country_ids: asLinkIds(f['country_ids']),
    internal_notes: asString(f['internal_notes']),
  };
}

function readTestimonial(rec: AirtableRecord) {
  const { _recordId, f } = readCommon(rec);
  return {
    _recordId,
    internal_name: asString(f['internal_name']),
    quote: asString(f['quote']),
    author_name: asString(f['author_name']),
    author_title: asString(f['author_title']),
    author_image_url: asString(f['author_image_url']),
    order: asNumber(f['order']),
    is_active: asBool(f['is_active']),
    is_featured: asBool(f['is_featured']),
    customer_type_ids: asLinkIds(f['customer_type_ids']),
    division_ids: asLinkIds(f['division_ids']),
    country_ids: asLinkIds(f['country_ids']),
    internal_notes: asString(f['internal_notes']),
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

function cleanField(v: unknown): unknown {
  if (v === undefined || v === '') return null;
  return v;
}

function writeCompany(s: Record<string, unknown>): Record<string, unknown> {
  return {
    'slug': cleanField(s.slug),
    'is_default': true, // Singleton: bara ett record finns och det är alltid default.
    'country_ids': asLinkIds(s.country_ids),
    'company_name': cleanField(s.company_name),
    'tagline': cleanField(s.tagline),
    'org_number': cleanField(s.org_number),
    'vat_number': cleanField(s.vat_number),
    'email': cleanField(s.email),
    'email_order': cleanField(s.email_order),
    'phone': cleanField(s.phone),
    'phone_emergency': cleanField(s.phone_emergency),
    'address_line_1': cleanField(s.address_line_1),
    'address_postal_code': cleanField(s.address_postal_code),
    'address_city': cleanField(s.address_city),
    'linkedin_url': cleanField(s.linkedin_url),
    'facebook_url': cleanField(s.facebook_url),
    'instagram_url': cleanField(s.instagram_url),
    'youtube_url': cleanField(s.youtube_url),
    'hours_mon_thur': cleanField(s.hours_mon_thur),
    'hours_friday': cleanField(s.hours_friday),
    'internal_notes': cleanField(s.internal_notes),
  };
}

function writeGraphicProfile(s: Record<string, unknown>): Record<string, unknown> {
  return {
    'slug': cleanField(s.slug),
    'is_default': !!s.is_default,
    'logo_primary_url': cleanField(s.logo_primary_url),
    'logo_dark_url': cleanField(s.logo_dark_url),
    'favicon_url': cleanField(s.favicon_url),
    'color_primary': cleanField(s.color_primary),
    'color_secondary': cleanField(s.color_secondary),
    'color_accent': cleanField(s.color_accent),
    'color_background_light': cleanField(s.color_background_light),
    'color_background_dark': cleanField(s.color_background_dark),
    'color_text_primary': cleanField(s.color_text_primary),
    'color_text_secondary': cleanField(s.color_text_secondary),
    'font_heading': cleanField(s.font_heading),
    'font_body': cleanField(s.font_body),
    'font_css_url': cleanField(s.font_css_url),
    'division_ids': asLinkIds(s.division_ids),
    'internal_notes': cleanField(s.internal_notes),
  };
}

function writeCountry(s: Record<string, unknown>): Record<string, unknown> {
  return {
    'name': cleanField(s.name),
    'code': cleanField(s.code),
    'domain': cleanField(s.domain),
    'url_prefix': cleanField(s.url_prefix),
    'currency': cleanField(s.currency),
    'locale': cleanField(s.locale),
    'default_language': cleanField(s.default_language),
    'order': s.order === '' ? null : Number(s.order ?? 0),
    'is_active': !!s.is_active,
    'internal_notes': cleanField(s.internal_notes),
  };
}

function writeDivision(s: Record<string, unknown>): Record<string, unknown> {
  return {
    'name': cleanField(s.name),
    'slug': cleanField(s.slug),
    'description': cleanField(s.description),
    'order': s.order === '' ? null : Number(s.order ?? 0),
    'is_active': !!s.is_active,
    'country_ids': asLinkIds(s.country_ids),
    'internal_notes': cleanField(s.internal_notes),
  };
}

function writeCustomerType(s: Record<string, unknown>): Record<string, unknown> {
  return {
    'name': cleanField(s.name),
    'slug': cleanField(s.slug),
    'description': cleanField(s.description),
    'icon': cleanField(s.icon),
    'order': s.order === '' ? null : Number(s.order ?? 0),
    'is_active': !!s.is_active,
    'internal_notes': cleanField(s.internal_notes),
  };
}

function writeCoworker(s: Record<string, unknown>): Record<string, unknown> {
  return {
    'full_name': cleanField(s.full_name),
    'title': cleanField(s.title),
    'email': cleanField(s.email),
    'phone': cleanField(s.phone),
    'image_url': cleanField(s.image_url),
    'linkedin_url': cleanField(s.linkedin_url),
    'bio': cleanField(s.bio),
    'order': s.order === '' ? null : Number(s.order ?? 0),
    'is_active': !!s.is_active,
    'division_ids': asLinkIds(s.division_ids),
    'country_ids': asLinkIds(s.country_ids),
    'internal_notes': cleanField(s.internal_notes),
  };
}

function writePartner(s: Record<string, unknown>): Record<string, unknown> {
  return {
    'name': cleanField(s.name),
    'logo_url': cleanField(s.logo_url),
    'logo_transparent_url': cleanField(s.logo_transparent_url),
    'url': cleanField(s.url),
    'description': cleanField(s.description),
    'order': s.order === '' ? null : Number(s.order ?? 0),
    'is_active': !!s.is_active,
    'division_ids': asLinkIds(s.division_ids),
    'country_ids': asLinkIds(s.country_ids),
    'internal_notes': cleanField(s.internal_notes),
  };
}

function writeTestimonial(s: Record<string, unknown>): Record<string, unknown> {
  return {
    'internal_name': cleanField(s.internal_name),
    'quote': cleanField(s.quote),
    'author_name': cleanField(s.author_name),
    'author_title': cleanField(s.author_title),
    'author_image_url': cleanField(s.author_image_url),
    'order': s.order === '' ? null : Number(s.order ?? 0),
    'is_active': !!s.is_active,
    'is_featured': !!s.is_featured,
    'customer_type_ids': asLinkIds(s.customer_type_ids),
    'division_ids': asLinkIds(s.division_ids),
    'country_ids': asLinkIds(s.country_ids),
    'internal_notes': cleanField(s.internal_notes),
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
