/**
 * Field-config per SSOT-entity.
 *
 * Driver `CoreEntityForm`-komponenten: vilka fält som visas, vilken inputtyp,
 * vilken label, eventuella länkfält-relationer.
 */

import { CoreEntityName } from './registry';

export type FormFieldType =
  | 'text'
  | 'textarea'
  | 'email'
  | 'url'
  | 'phone'
  | 'number'
  | 'checkbox'
  | 'image'
  | 'color'
  | 'link';

export interface FormField {
  /** Domain key (matchar state-objektet och Airtable-fältmap). */
  key: string;
  label: string;
  type: FormFieldType;
  /** För `link`-typ: namnet på den entity som länken pekar på. */
  linkedEntity?: CoreEntityName;
  /** Hjälptext under fältet. */
  help?: string;
  /** Pekar ut fältet som primärt (i list-vyn). */
  isPrimary?: boolean;
  /** Bredd på rader i grid: 'full' (default) eller 'half'. */
  width?: 'full' | 'half';
}

export interface CoreEntityFormConfig {
  /** Fältlistan ovanifrån-och-ned. */
  fields: FormField[];
  /** Vilket domain-fält som visas som titel i listvyn. */
  listLabelField: string;
  /** Fält som visas som rad-beskrivning i listvyn (under titeln). */
  listMetaField?: string;
}

const ACTIVE_FIELD: FormField = { key: 'is_active', label: 'Aktiv', type: 'checkbox', help: 'Avaktivera istället för att radera.' };
const ORDER_FIELD: FormField = { key: 'order', label: 'Sorteringsordning', type: 'number', help: 'Lägre värde = visas först. Använd multiplar av 10.' };

export const CORE_ENTITY_FORMS: Record<CoreEntityName, CoreEntityFormConfig> = {
  core_company: {
    listLabelField: 'slug',
    fields: [
      { key: 'slug', label: 'Slug (intern nyckel)', type: 'text', isPrimary: true },
      { key: 'company_name', label: 'Företagsnamn', type: 'text' },
      { key: 'country_ids', label: 'Land', type: 'link', linkedEntity: 'core_countries' },
      { key: 'tagline', label: 'Tagline', type: 'text' },
      { key: 'org_number', label: 'Organisationsnummer', type: 'text', width: 'half' },
      { key: 'vat_number', label: 'VAT-nummer', type: 'text', width: 'half' },
      { key: 'email', label: 'E-post', type: 'email', width: 'half' },
      { key: 'email_order', label: 'E-post order', type: 'email', width: 'half' },
      { key: 'phone', label: 'Telefon', type: 'phone', width: 'half' },
      { key: 'phone_emergency', label: 'Akut-/jour-telefon', type: 'phone', width: 'half' },
      { key: 'address_line_1', label: 'Adressrad', type: 'text' },
      { key: 'address_postal_code', label: 'Postnummer', type: 'text', width: 'half' },
      { key: 'address_city', label: 'Ort', type: 'text', width: 'half' },
      { key: 'linkedin_url', label: 'LinkedIn', type: 'url', width: 'half' },
      { key: 'facebook_url', label: 'Facebook', type: 'url', width: 'half' },
      { key: 'instagram_url', label: 'Instagram', type: 'url', width: 'half' },
      { key: 'youtube_url', label: 'YouTube', type: 'url', width: 'half' },
      { key: 'hours_mon_thur', label: 'Öppettider mån-tors', type: 'text', width: 'half' },
      { key: 'hours_friday', label: 'Öppettider fredag', type: 'text', width: 'half' },
      { key: 'internal_notes', label: 'Interna noteringar', type: 'textarea', help: 'Visas inte publikt.' },
    ],
  },
  core_graphic_profile: {
    listLabelField: 'slug',
    fields: [
      { key: 'slug', label: 'Slug', type: 'text', isPrimary: true },
      { key: 'is_default', label: 'Är default', type: 'checkbox' },
      { key: 'logo_primary_url', label: 'Logo primär', type: 'image' },
      { key: 'logo_dark_url', label: 'Logo mörk bakgrund', type: 'image' },
      { key: 'favicon_url', label: 'Favicon', type: 'image' },
      { key: 'color_primary', label: 'Primärfärg', type: 'color', width: 'half' },
      { key: 'color_secondary', label: 'Sekundärfärg', type: 'color', width: 'half' },
      { key: 'color_accent', label: 'Accentfärg', type: 'color', width: 'half' },
      { key: 'color_background_light', label: 'Bakgrund ljus', type: 'color', width: 'half' },
      { key: 'color_background_dark', label: 'Bakgrund mörk', type: 'color', width: 'half' },
      { key: 'color_text_primary', label: 'Text primär', type: 'color', width: 'half' },
      { key: 'color_text_secondary', label: 'Text sekundär', type: 'color', width: 'half' },
      { key: 'font_heading', label: 'Typsnitt rubriker', type: 'text' },
      { key: 'font_body', label: 'Typsnitt brödtext', type: 'text' },
      { key: 'font_css_url', label: 'Font CSS URL', type: 'url' },
      { key: 'division_ids', label: 'Division', type: 'link', linkedEntity: 'core_divisions' },
    ],
  },
  core_countries: {
    listLabelField: 'name',
    listMetaField: 'code',
    fields: [
      { key: 'name', label: 'Namn', type: 'text', isPrimary: true },
      { key: 'code', label: 'Kod (ISO 3166-1 alpha-2)', type: 'text', width: 'half', help: 'Versaler, 2 tecken (SE, NO, DK).' },
      { key: 'domain', label: 'Domän', type: 'text', width: 'half', help: 'wexoe.se, wexoe.no etc.' },
      { key: 'url_prefix', label: 'URL-prefix', type: 'text', width: 'half', help: 'Om flera länder delar domän.' },
      { key: 'currency', label: 'Valuta', type: 'text', width: 'half', help: 'ISO 4217, t.ex. SEK.' },
      { key: 'locale', label: 'Locale', type: 'text', width: 'half', help: 'BCP 47, t.ex. sv-SE.' },
      { key: 'default_language', label: 'Standardspråk', type: 'text', width: 'half', help: 'ISO 639-1, t.ex. sv.' },
      ORDER_FIELD,
      ACTIVE_FIELD,
    ],
  },
  core_divisions: {
    listLabelField: 'name',
    listMetaField: 'slug',
    fields: [
      { key: 'name', label: 'Namn', type: 'text', isPrimary: true },
      { key: 'slug', label: 'Slug', type: 'text' },
      { key: 'description', label: 'Beskrivning', type: 'textarea' },
      { key: 'country_ids', label: 'Länder', type: 'link', linkedEntity: 'core_countries' },
      ORDER_FIELD,
      ACTIVE_FIELD,
    ],
  },
  core_customer_types: {
    listLabelField: 'name',
    listMetaField: 'slug',
    fields: [
      { key: 'name', label: 'Namn', type: 'text', isPrimary: true },
      { key: 'slug', label: 'Slug', type: 'text' },
      { key: 'description', label: 'Beskrivning', type: 'textarea' },
      { key: 'icon', label: 'Ikon', type: 'image' },
      ORDER_FIELD,
      ACTIVE_FIELD,
    ],
  },
  core_coworkers: {
    listLabelField: 'full_name',
    listMetaField: 'title',
    fields: [
      { key: 'full_name', label: 'Fullständigt namn', type: 'text', isPrimary: true },
      { key: 'title', label: 'Titel', type: 'text' },
      { key: 'email', label: 'E-post', type: 'email', width: 'half' },
      { key: 'phone', label: 'Telefon', type: 'phone', width: 'half' },
      { key: 'image_url', label: 'Porträttbild', type: 'image' },
      { key: 'linkedin_url', label: 'LinkedIn', type: 'url' },
      { key: 'bio', label: 'Bio', type: 'textarea' },
      { key: 'division_ids', label: 'Divisioner', type: 'link', linkedEntity: 'core_divisions' },
      { key: 'country_ids', label: 'Länder', type: 'link', linkedEntity: 'core_countries' },
      ORDER_FIELD,
      ACTIVE_FIELD,
    ],
  },
  core_partners: {
    listLabelField: 'name',
    fields: [
      { key: 'name', label: 'Partnernamn', type: 'text', isPrimary: true },
      { key: 'logo_url', label: 'Logo (ljus bakgrund)', type: 'image' },
      { key: 'logo_transparent_url', label: 'Logo (mörk bakgrund)', type: 'image' },
      { key: 'url', label: 'Hemsida', type: 'url' },
      { key: 'description', label: 'Beskrivning', type: 'textarea' },
      { key: 'division_ids', label: 'Divisioner', type: 'link', linkedEntity: 'core_divisions' },
      { key: 'country_ids', label: 'Länder', type: 'link', linkedEntity: 'core_countries' },
      ORDER_FIELD,
      ACTIVE_FIELD,
    ],
  },
  core_testimonials: {
    listLabelField: 'internal_name',
    listMetaField: 'author_name',
    fields: [
      { key: 'internal_name', label: 'Internt namn (visas inte publikt)', type: 'text', isPrimary: true },
      { key: 'quote', label: 'Citat', type: 'textarea' },
      { key: 'author_name', label: 'Författarens namn', type: 'text', width: 'half' },
      { key: 'author_title', label: 'Författarens titel', type: 'text', width: 'half' },
      { key: 'author_image_url', label: 'Porträttbild', type: 'image' },
      { key: 'is_featured', label: 'Featured', type: 'checkbox', help: 'Framhäv i carouseller.' },
      { key: 'customer_type_ids', label: 'Kundtyper', type: 'link', linkedEntity: 'core_customer_types' },
      { key: 'division_ids', label: 'Divisioner', type: 'link', linkedEntity: 'core_divisions' },
      { key: 'country_ids', label: 'Länder', type: 'link', linkedEntity: 'core_countries' },
      ORDER_FIELD,
      ACTIVE_FIELD,
    ],
  },
};

/** Tomt state per entity, för "skapa nytt"-formen. */
export function emptyEntityState(entity: CoreEntityName): Record<string, unknown> {
  const cfg = CORE_ENTITY_FORMS[entity];
  const state: Record<string, unknown> = {};
  for (const field of cfg.fields) {
    switch (field.type) {
      case 'checkbox': state[field.key] = false; break;
      case 'number': state[field.key] = 0; break;
      case 'link': state[field.key] = []; break;
      default: state[field.key] = '';
    }
  }
  return state;
}
