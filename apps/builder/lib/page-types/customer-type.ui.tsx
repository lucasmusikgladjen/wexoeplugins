/**
 * Customer-type-page — UI-side sidtypsdefinition.
 */

import { CustomerTypePageState } from '../customer-type-types';
import HeroEditor from '@/components/customer-type/editors/HeroEditor';
import ValueEditor from '@/components/customer-type/editors/ValueEditor';
import SettingsEditor from '@/components/customer-type/editors/SettingsEditor';
import ContactFormEditor from '@/components/contact-form/ContactFormEditor';
import CustomerTypePagePreviewPanel from '@/components/customer-type/preview/CustomerTypePagePreviewPanel';
import type { PageTypeUIDef, SectionDef } from './types';

const sections: SectionDef<CustomerTypePageState>[] = [
  {
    id: 'hero',
    label: 'Hero',
    Editor: HeroEditor,
  },
  {
    id: 'value',
    label: 'Värdeproposition',
    Editor: ValueEditor,
    visibilityToggle: (state, setState) => ({
      value: state.showValue,
      onChange: (v) => setState({ ...state, showValue: v }),
    }),
  },
  {
    id: 'contactForm',
    label: 'Kontaktformulär',
    Editor: ({ state, onChange }) => (
      <ContactFormEditor
        state={state.contactForm}
        onChange={(cf) => onChange({ ...state, contactForm: cf })}
      />
    ),
    visibilityToggle: (state, setState) => ({
      value: state.showContactForm,
      onChange: (v) => setState({ ...state, showContactForm: v }),
    }),
  },
  {
    id: 'settings',
    label: 'Inställningar',
    Editor: SettingsEditor,
  },
];

export const customerTypeUI: PageTypeUIDef<CustomerTypePageState> & {
  canSave: (state: CustomerTypePageState) => boolean;
  canSaveHint: string;
} = {
  id: 'customer-type',
  label: 'Kundtyp',
  sections,
  previewLayout: ({ state, activeSection, scrollTrigger, onSectionClick }) => (
    <CustomerTypePagePreviewPanel
      state={state}
      activeSection={activeSection}
      onSectionClick={onSectionClick}
      scrollTrigger={scrollTrigger}
    />
  ),
  slugInput: {
    accessor: (s) => s.slug,
    setter: (s, slug) => ({ ...s, slug }),
    placeholder: 'min-kundtyp',
    badge: (_s, mode) => (mode === 'create' ? 'Ny kundtyp-sida' : 'Kundtyp-sida'),
  },
  canSave: (s) => !!s.slug.trim() && !!s.title.trim(),
  canSaveHint: 'Slug + titel krävs',
};
