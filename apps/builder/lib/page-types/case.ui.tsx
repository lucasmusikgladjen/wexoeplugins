/**
 * Case-page — UI-side sidtypsdefinition.
 *
 * Sektionerna matchar editor-listan i SKAPA-SIDA-promptens FAS 3-spec, vilken
 * i sin tur speglar `<!-- section -->`-strukturen i prototype.html och
 * render-ordningen i wexoe-case.php.
 *
 * Visibility-toggles följer befintligt mönster (`visibilityToggle`-callback
 * på SectionDef) — editorn renderar fältet alltid, men sektionen i preview
 * är dold när togglen är `false`.
 */

import { CaseState } from '../case-types';
import HeaderEditor from '@/components/case/editors/HeaderEditor';
import LeadEditor from '@/components/case/editors/LeadEditor';
import StatsStripEditor from '@/components/case/editors/StatsStripEditor';
import ChallengeEditor from '@/components/case/editors/ChallengeEditor';
import PullquoteEditor from '@/components/case/editors/PullquoteEditor';
import SolutionEditor from '@/components/case/editors/SolutionEditor';
import ProductsEditor from '@/components/case/editors/ProductsEditor';
import ResultsEditor from '@/components/case/editors/ResultsEditor';
import TestimonialEditor from '@/components/case/editors/TestimonialEditor';
import GalleryEditor from '@/components/case/editors/GalleryEditor';
import AboutCustomerEditor from '@/components/case/editors/AboutCustomerEditor';
import GlanceEditor from '@/components/case/editors/GlanceEditor';
import SeoEditor from '@/components/case/editors/SeoEditor';
import SettingsEditor from '@/components/case/editors/SettingsEditor';
import ContactFormEditor from '@/components/contact-form/ContactFormEditor';
import CasePreviewPanel from '@/components/case/preview/CasePreviewPanel';
import type { PageTypeUIDef, SectionDef } from './types';

const sections: SectionDef<CaseState>[] = [
  {
    id: 'header',
    label: 'Header',
    description: 'Industri, titel, byline, kund-loggor.',
    Editor: HeaderEditor,
  },
  {
    id: 'lead',
    label: 'Lead (bild + ingress)',
    Editor: LeadEditor,
  },
  {
    id: 'statsStrip',
    label: 'Snabba siffror',
    description: 'Rad med 1–4 nyckeltal under hero-bilden.',
    Editor: StatsStripEditor,
    visibilityToggle: (state, setState) => ({
      value: state.showStatsStrip,
      onChange: (v) => setState({ ...state, showStatsStrip: v }),
    }),
  },
  {
    id: 'challenge',
    label: 'Utmaningen',
    Editor: ChallengeEditor,
  },
  {
    id: 'pullquote',
    label: 'Pull quote',
    description: 'Inline citat-block mellan challenge och solution.',
    Editor: PullquoteEditor,
    visibilityToggle: (state, setState) => ({
      value: state.showPullquote,
      onChange: (v) => setState({ ...state, showPullquote: v }),
    }),
  },
  {
    id: 'solution',
    label: 'Lösningen',
    Editor: SolutionEditor,
  },
  {
    id: 'products',
    label: 'Produkter i lösningen',
    description: 'Multi-select från cms_products + cms_articles.',
    Editor: ProductsEditor,
  },
  {
    id: 'results',
    label: 'Resultatet',
    Editor: ResultsEditor,
  },
  {
    id: 'testimonial',
    label: 'Kundens röst',
    description: 'Citat från en namngiven person hos kunden.',
    Editor: TestimonialEditor,
    visibilityToggle: (state, setState) => ({
      value: state.showTestimonial,
      onChange: (v) => setState({ ...state, showTestimonial: v }),
    }),
  },
  {
    id: 'gallery',
    label: 'Bildberättelse',
    description: '1–6 bilder. Var tredje bild renderas i full bredd.',
    Editor: GalleryEditor,
    visibilityToggle: (state, setState) => ({
      value: state.showGallery,
      onChange: (v) => setState({ ...state, showGallery: v }),
    }),
  },
  {
    id: 'aboutCustomer',
    label: 'Om kunden',
    Editor: AboutCustomerEditor,
    visibilityToggle: (state, setState) => ({
      value: state.showAboutCustomer,
      onChange: (v) => setState({ ...state, showAboutCustomer: v }),
    }),
  },
  {
    id: 'glance',
    label: 'Caset i korthet (sidebar)',
    description: 'Sticky sidebar med Utmaning / Lösning / Resultat. Visas alltid.',
    Editor: GlanceEditor,
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
    id: 'seo',
    label: 'SEO',
    description: 'Sidtitel, meta-beskrivning, OG-bild. Faller tillbaka till case-titeln.',
    Editor: SeoEditor,
    defaultCollapsed: true,
  },
  {
    id: 'settings',
    label: 'Publicering & intern notering',
    Editor: SettingsEditor,
  },
];

export const caseUI: PageTypeUIDef<CaseState> & {
  canSave: (state: CaseState) => boolean;
  canSaveHint: string;
} = {
  id: 'case',
  label: 'Case',
  sections,
  previewLayout: ({ state, activeSection, scrollTrigger, onSectionClick }) => (
    <CasePreviewPanel
      state={state}
      activeSection={activeSection}
      onSectionClick={onSectionClick}
      scrollTrigger={scrollTrigger}
    />
  ),
  slugInput: {
    accessor: (s) => s.slug,
    setter: (s, slug) => ({ ...s, slug }),
    placeholder: 'arla-gotene',
    badge: (_s, mode) => (mode === 'create' ? 'Nytt case' : 'Case'),
  },
  canSave: (s) => !!s.slug.trim() && !!s.title.trim(),
  canSaveHint: 'Slug + titel krävs',
};
