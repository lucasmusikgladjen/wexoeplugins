/**
 * Partner-page (leverantörssida) — UI-side sidtypsdefinition.
 *
 * 9 sektioner:
 *   1. Hero — alltid synlig (schemat har ingen show_*-toggle för hero)
 *   2. Snabbfakta — visibility:show_quick_facts
 *   3. Om leverantören — visibility:show_about
 *   4. Varför Wexoe + cases — visibility:show_why_wexoe
 *   5. Produktkategorier — visibility:show_categories
 *   6. FAQ — visibility:show_faq
 *   7. Kontaktperson — visibility:show_contact_person
 *   8. Kontaktformulär — visibility:show_contact_form (delad ContactFormEditor)
 *   9. Inställningar — slug/SEO/links (alltid synlig)
 *
 * Sektionsordningen i editorn matchar rendering-ordningen i Renderer.php,
 * med Inställningar sist (samma som customer-type / product-area).
 *
 * Renaming-konvention för visibility-helper: identisk med
 * `product-area.ui.tsx::showToggle` — hade flyttats till ett delat
 * helper-modul men håller den inline här för att inte plumbra typing
 * mellan sidtyperna.
 */

import { PartnerPageState } from '../partner-types';
import HeroEditor from '@/components/partner/editors/HeroEditor';
import QuickFactsEditor from '@/components/partner/editors/QuickFactsEditor';
import AboutEditor from '@/components/partner/editors/AboutEditor';
import WhyWexoeEditor from '@/components/partner/editors/WhyWexoeEditor';
import CategoriesEditor from '@/components/partner/editors/CategoriesEditor';
import FaqEditor from '@/components/partner/editors/FaqEditor';
import ContactPersonEditor from '@/components/partner/editors/ContactPersonEditor';
import SettingsEditor from '@/components/partner/editors/SettingsEditor';
import ContactFormEditor from '@/components/contact-form/ContactFormEditor';
import PartnerPreviewPanel from '@/components/partner/preview/PartnerPreviewPanel';
import type { PageTypeUIDef, SectionDef } from './types';

function showToggle<K extends keyof PartnerPageState>(key: K) {
  return (state: PartnerPageState, setState: (next: PartnerPageState) => void) => ({
    value: Boolean(state[key]),
    onChange: (v: boolean) =>
      setState({ ...state, [key]: v as PartnerPageState[K] }),
  });
}

const sections: SectionDef<PartnerPageState>[] = [
  {
    id: 'hero',
    label: 'Hero',
    Editor: HeroEditor,
  },
  {
    id: 'quickFacts',
    label: 'Snabbfakta',
    Editor: QuickFactsEditor,
    visibilityToggle: showToggle('showQuickFacts'),
  },
  {
    id: 'about',
    label: 'Om leverantören',
    Editor: AboutEditor,
    visibilityToggle: showToggle('showAbout'),
  },
  {
    id: 'whyWexoe',
    label: 'Varför Wexoe + cases',
    Editor: WhyWexoeEditor,
    visibilityToggle: showToggle('showWhyWexoe'),
  },
  {
    id: 'categories',
    label: 'Produktkategorier',
    Editor: CategoriesEditor,
    visibilityToggle: showToggle('showCategories'),
  },
  {
    id: 'faq',
    label: 'FAQ',
    Editor: FaqEditor,
    visibilityToggle: showToggle('showFaq'),
  },
  {
    id: 'contactPerson',
    label: 'Kontaktperson',
    Editor: ContactPersonEditor,
    visibilityToggle: showToggle('showContactPerson'),
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
    visibilityToggle: showToggle('showContactForm'),
  },
  {
    id: 'settings',
    label: 'Inställningar',
    Editor: SettingsEditor,
  },
];

export const partnerUI: PageTypeUIDef<PartnerPageState> & {
  canSave: (state: PartnerPageState) => boolean;
  canSaveHint: string;
} = {
  id: 'partner',
  label: 'Leverantörssida',
  sections,
  previewLayout: ({ state, activeSection, scrollTrigger, onSectionClick }) => (
    <PartnerPreviewPanel
      state={state}
      activeSection={activeSection}
      onSectionClick={onSectionClick}
      scrollTrigger={scrollTrigger}
    />
  ),
  slugInput: {
    accessor: (s) => s.slug,
    setter: (s, slug) => ({ ...s, slug }),
    placeholder: 'rockwell',
    badge: (_s, mode) => (mode === 'create' ? 'Ny leverantörssida' : 'Leverantörssida'),
  },
  canSave: (s) => !!s.slug.trim() && !!s.h1.trim(),
  canSaveHint: 'Slug + H1 krävs',
};
