/**
 * Product Area — UI-side sidtypsdefinition.
 *
 * 7 sektioner: Hero, Innehåll, Produkter, Lösningar, Visitkort,
 * Kontaktformulär, Inställningar. Innehåll/Produkter/Lösningar/Visitkort
 * är toggle-bara via showContent/showProducts/showSolutions/showContact
 * på staten (computed i fromRecord från innehåll, ej persisterade).
 */

import { ProductAreaState } from '../product-area-types';
import HeroEditor from '@/components/product-area/editors/HeroEditor';
import ContentBlocksEditor from '@/components/product-area/editors/ContentBlocksEditor';
import ProductsEditor from '@/components/product-area/editors/ProductsEditor';
import SolutionsEditor from '@/components/product-area/editors/SolutionsEditor';
import ContactEditor from '@/components/product-area/editors/ContactEditor';
import SettingsEditor from '@/components/product-area/editors/SettingsEditor';
import ContactFormEditor from '@/components/contact-form/ContactFormEditor';
import ProductAreaPreviewPanel from '@/components/product-area/preview/ProductAreaPreviewPanel';
import type { PageTypeUIDef, SectionDef } from './types';

function showToggle<K extends keyof ProductAreaState>(key: K) {
  return (state: ProductAreaState, setState: (next: ProductAreaState) => void) => ({
    value: Boolean(state[key]),
    onChange: (v: boolean) =>
      setState({ ...state, [key]: v as ProductAreaState[K] }),
  });
}

const sections: SectionDef<ProductAreaState>[] = [
  {
    id: 'hero',
    label: 'Hero',
    Editor: HeroEditor,
  },
  {
    id: 'content',
    label: 'Innehåll',
    Editor: ContentBlocksEditor,
    visibilityToggle: showToggle('showContent'),
  },
  {
    id: 'products',
    label: 'Produkter',
    Editor: ProductsEditor,
    visibilityToggle: showToggle('showProducts'),
  },
  {
    id: 'solutions',
    label: 'Lösningar',
    Editor: SolutionsEditor,
    visibilityToggle: showToggle('showSolutions'),
  },
  {
    id: 'contact',
    label: 'Visitkort',
    Editor: ContactEditor,
    visibilityToggle: showToggle('showContact'),
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

export const productAreaUI: PageTypeUIDef<ProductAreaState> & {
  canSave: (state: ProductAreaState) => boolean;
  canSaveHint: string;
} = {
  id: 'product-area',
  label: 'Produktområde',
  sections,
  previewLayout: ({ state, activeSection, scrollTrigger, onSectionClick }) => (
    <ProductAreaPreviewPanel
      state={state}
      activeSection={activeSection}
      onSectionClick={onSectionClick}
      scrollTrigger={scrollTrigger}
    />
  ),
  slugInput: {
    accessor: (s) => s.slug,
    setter: (s, slug) => ({ ...s, slug }),
    placeholder: 'min-sida',
    badge: (_s, mode) => (mode === 'create' ? 'Ny produktsida' : 'Produktsida'),
  },
  canSave: (s) => !!s.slug.trim() && !!s.h1.trim(),
  canSaveHint: 'Slug + rubrik krävs',
};
