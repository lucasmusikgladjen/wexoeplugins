/**
 * Forward and reverse mapping between Airtable Audience Hero records and
 * AudienceState. Direct field-to-field mapping — no Claude transform
 * required because the schema is flat (no linked records).
 */

import { AirtableRecord } from './airtable';
import { AudienceState } from './audience-types';

export const AUDIENCE_TABLE_IDS = {
  audienceHeroes: 'tblvNf1CqAYEFvTpu',
} as const;

type Fields = Record<string, unknown>;

function str(fields: Fields, key: string): string {
  const v = fields[key];
  if (typeof v === 'string') return v;
  if (typeof v === 'number') return String(v);
  return '';
}

function bool(fields: Fields, key: string): boolean {
  return fields[key] === true;
}

export function audienceStateFromRecord(record: AirtableRecord): AudienceState {
  const f = record.fields;
  return {
    mode: 'edit',
    recordId: record.id,
    slug: str(f, 'Slug'),
    active: bool(f, 'Active'),

    eyebrow: str(f, 'Eyebrow'),
    title: str(f, 'Title'),
    description: str(f, 'Description'),
    ctaText: str(f, 'CTA Text'),
    ctaUrl: str(f, 'CTA URL'),
    heroImage: str(f, 'Hero Image'),
    statNumber: str(f, 'Stat Number'),
    statLabel: str(f, 'Stat Label'),

    valueH2: str(f, 'Value H2'),
    valueText1: str(f, 'Value Text 1'),
    valueText2: str(f, 'Value Text 2'),
    benefit1: str(f, 'Benefit 1'),
    benefit2: str(f, 'Benefit 2'),
    benefit3: str(f, 'Benefit 3'),

    caseTitle: str(f, 'Case Title'),
    caseDescription: str(f, 'Case Description'),
    caseResult: str(f, 'Case Result'),
    caseLinkText: str(f, 'Case Link Text'),
    caseLinkUrl: str(f, 'Case Link URL'),
  };
}

/**
 * Build the Airtable fields payload from state. On create, empty strings are
 * dropped so we don't pre-fill blank fields. On update, empties are kept as
 * empty strings so removed values clear out of Airtable.
 */
export function audienceStateToFields(
  state: AudienceState,
  mode: 'create' | 'update',
): Record<string, unknown> {
  const all: Record<string, unknown> = {
    Slug: state.slug,
    Active: state.active,
    Eyebrow: state.eyebrow,
    Title: state.title,
    Description: state.description,
    'CTA Text': state.ctaText,
    'CTA URL': state.ctaUrl,
    'Hero Image': state.heroImage,
    'Stat Label': state.statLabel,
    'Value H2': state.valueH2,
    'Value Text 1': state.valueText1,
    'Value Text 2': state.valueText2,
    'Benefit 1': state.benefit1,
    'Benefit 2': state.benefit2,
    'Benefit 3': state.benefit3,
    'Case Title': state.caseTitle,
    'Case Description': state.caseDescription,
    'Case Result': state.caseResult,
    'Case Link Text': state.caseLinkText,
    'Case Link URL': state.caseLinkUrl,
  };

  // Stat Number is an Airtable number field — coerce, treat blank as null.
  const trimmed = state.statNumber.trim();
  if (trimmed) {
    const n = Number(trimmed);
    if (Number.isFinite(n)) all['Stat Number'] = n;
  } else if (mode === 'update') {
    all['Stat Number'] = null;
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
