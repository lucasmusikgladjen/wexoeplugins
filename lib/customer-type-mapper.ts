/**
 * Forward and reverse mapping between Airtable `cms_customer_type_pages`
 * records och `CustomerTypePageState`.
 *
 * Schema är flat (en linked-record-länk till cms_case_pages, ingen Claude-
 * transform). snake_case-konvention både i Airtable och här.
 */

import { AirtableRecord, BASE_ID } from './airtable';
import { CustomerTypePageState } from './customer-type-types';
import { str, bool } from './airtable-helpers';
import { contactFormFromFields, contactFormToFields } from './contact-form-mapper';

export const CUSTOMER_TYPE_BASE_ID = BASE_ID;

export const CUSTOMER_TYPE_TABLE_IDS = {
  customerTypePages: 'tblZufoWVNKPuJdMK',
  casePages: 'tbl3uMV6IpRIZeucA',
} as const;

function strArray(fields: Record<string, unknown>, key: string): string[] {
  const v = fields[key];
  return Array.isArray(v) ? (v.filter((x) => typeof x === 'string') as string[]) : [];
}

export function customerTypePageStateFromRecord(
  record: AirtableRecord,
): CustomerTypePageState {
  const f = record.fields;
  const valueH2 = str(f, 'value_h2');
  const valueText1 = str(f, 'value_text_1');
  const valueText2 = str(f, 'value_text_2');
  const benefit1 = str(f, 'benefit_1');
  const benefit2 = str(f, 'benefit_2');
  const benefit3 = str(f, 'benefit_3');

  const statNumberRaw = f['stat_number'];
  const statNumber =
    typeof statNumberRaw === 'number'
      ? String(statNumberRaw)
      : typeof statNumberRaw === 'string'
        ? statNumberRaw
        : '';

  return {
    mode: 'edit',
    recordId: record.id,
    slug: str(f, 'slug'),
    isActive: bool(f, 'is_active'),
    name: str(f, 'name'),

    eyebrow: str(f, 'eyebrow'),
    title: str(f, 'title'),
    description: str(f, 'description'),
    ctaText: str(f, 'cta_text'),
    ctaUrl: str(f, 'cta_url'),
    heroImageUrl: str(f, 'hero_image_url'),
    statNumber,
    statLabel: str(f, 'stat_label'),

    showValue: !!(valueH2 || valueText1 || valueText2 || benefit1 || benefit2 || benefit3),
    valueH2,
    valueText1,
    valueText2,
    benefit1,
    benefit2,
    benefit3,

    caseIds: strArray(f, 'case_ids'),

    showContactForm: bool(f, 'show_contact_form'),
    contactForm: contactFormFromFields(f, 'snake_case'),
  };
}

/**
 * Build Airtable fields payload from state. On create, empty strings dropped;
 * on update, kept as empty string so removed values clear i Airtable.
 *
 * Linked record `case_ids` skickas inte (builder redigerar inte case-länkning
 * denna runda — hanteras i Airtable UI).
 */
export function customerTypePageStateToFields(
  state: CustomerTypePageState,
  mode: 'create' | 'update',
): Record<string, unknown> {
  const all: Record<string, unknown> = {
    slug: state.slug,
    is_active: state.isActive,
    name: state.name,
    eyebrow: state.eyebrow,
    title: state.title,
    description: state.description,
    cta_text: state.ctaText,
    cta_url: state.ctaUrl,
    hero_image_url: state.heroImageUrl,
    stat_label: state.statLabel,
    value_h2: state.valueH2,
    value_text_1: state.valueText1,
    value_text_2: state.valueText2,
    benefit_1: state.benefit1,
    benefit_2: state.benefit2,
    benefit_3: state.benefit3,

    show_contact_form: state.showContactForm,
    ...contactFormToFields(state.contactForm, { schema: 'snake_case' }),
  };

  // stat_number — Airtable number-fält. Tom sträng = null vid update.
  const trimmed = state.statNumber.trim();
  if (trimmed) {
    const n = Number(trimmed);
    if (Number.isFinite(n)) all.stat_number = n;
  } else if (mode === 'update') {
    all.stat_number = null;
  }

  if (mode === 'create') {
    const out: Record<string, unknown> = {};
    for (const [k, v] of Object.entries(all)) {
      if (v === '' || v === undefined) continue;
      out[k] = v;
    }
    return out;
  }
  return all;
}
