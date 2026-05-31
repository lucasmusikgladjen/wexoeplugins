// Customer-type-page state model (tidigare AudienceState).
//
// Speglar Airtable `cms_customer_type_pages` (tblZufoWVNKPuJdMK) i Wexoe NY,
// som drivs av `[wexoe_customer_type slug="..."]`-shortcoden.
//
// Cases är inte längre inline-fält — de länkas via `caseIds` till
// cms_cases. Builder-editorn redigerar bara customer-type-page-fält
// (hero, value, contact-form). Cases hanteras i Airtable tills en separat
// case-editor byggs.

import { ContactFormState, emptyContactFormState } from './contact-form-types';

export interface CustomerTypePageState {
  /** `create` innan recordet persisterats; `edit` när det har Airtable-id. */
  mode: 'edit' | 'create';
  recordId: string;
  slug: string;
  isActive: boolean;

  /** Internt label/display-namn (ex. "Installatör", "OEM"). */
  name: string;

  // Hero
  eyebrow: string;
  title: string;
  description: string;
  ctaText: string;
  ctaUrl: string;
  heroImageUrl: string;
  statNumber: string;
  statLabel: string;

  /** UI-state: visa Värdeproposition-sektionen. Inte persisterat — defaultas
   *  från om något value/benefit-fält har innehåll. */
  showValue: boolean;
  valueH2: string;
  valueText1: string;
  valueText2: string;
  benefit1: string;
  benefit2: string;
  benefit3: string;

  /** Linkade case-records (cms_cases). Read-only i builder denna runda;
   *  visar bara antal i settings-panelen. */
  caseIds: string[];

  // Contact form
  showContactForm: boolean;
  contactForm: ContactFormState;
}

export type CustomerTypePageSectionId =
  | 'hero'
  | 'value'
  | 'cases'
  | 'contactForm'
  | 'settings';

export function emptyCustomerTypePageState(): CustomerTypePageState {
  return {
    mode: 'create',
    recordId: '',
    slug: '',
    isActive: true,
    name: '',

    eyebrow: '',
    title: '',
    description: '',
    ctaText: '',
    ctaUrl: '',
    heroImageUrl: '',
    statNumber: '',
    statLabel: '',

    showValue: false,
    valueH2: '',
    valueText1: '',
    valueText2: '',
    benefit1: '',
    benefit2: '',
    benefit3: '',

    caseIds: [],

    showContactForm: false,
    contactForm: emptyContactFormState(),
  };
}
