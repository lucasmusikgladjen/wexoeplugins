// Audience hero state model.
//
// Mirrors the Airtable "Audience Heroes" table (tblvNf1CqAYEFvTpu) used by
// the `wexoe-audience-hero` plugin's [wexoe_audience slug="..."] shortcode.
// Flat schema — no linked records.

import { ContactFormState, emptyContactFormState } from './contact-form-types';

export interface AudienceState {
  /** `create` before the record has ever been persisted; `edit` once it has an Airtable id. */
  mode: 'edit' | 'create';
  recordId: string;
  slug: string;
  active: boolean;

  // Hero
  eyebrow: string;
  title: string;
  description: string;
  ctaText: string;
  ctaUrl: string;
  heroImage: string;
  statNumber: string;
  statLabel: string;

  // Value proposition
  valueH2: string;
  valueText1: string;
  valueText2: string;
  benefit1: string;
  benefit2: string;
  benefit3: string;

  // Case card
  caseTitle: string;
  caseDescription: string;
  caseResult: string;
  caseLinkText: string;
  caseLinkUrl: string;

  // Contact form
  showContactForm: boolean;
  contactForm: ContactFormState;
}

export type AudienceSectionId = 'hero' | 'value' | 'case' | 'contactForm' | 'settings';

export function emptyAudienceState(): AudienceState {
  return {
    mode: 'create',
    recordId: '',
    slug: '',
    active: true,

    eyebrow: '',
    title: '',
    description: '',
    ctaText: '',
    ctaUrl: '',
    heroImage: '',
    statNumber: '',
    statLabel: '',

    valueH2: '',
    valueText1: '',
    valueText2: '',
    benefit1: '',
    benefit2: '',
    benefit3: '',

    caseTitle: '',
    caseDescription: '',
    caseResult: '',
    caseLinkText: '',
    caseLinkUrl: '',

    showContactForm: false,
    contactForm: emptyContactFormState(),
  };
}
