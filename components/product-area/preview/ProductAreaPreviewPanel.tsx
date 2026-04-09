'use client';

import { ProductAreaState, ProductAreaSectionId, NormalSection } from '@/lib/product-area-types';
import TopBannerPreview from './TopBannerPreview';
import HeroPreview from './HeroPreview';
import NormalSectionPreview from './NormalSectionPreview';
import ProductsPreview from './ProductsPreview';
import SolutionsPreview from './SolutionsPreview';
import ContactPreview from './ContactPreview';
import DocsPreview from './DocsPreview';

interface Props {
  state: ProductAreaState;
  activeSection: ProductAreaSectionId | null;
  onSectionClick: (id: ProductAreaSectionId) => void;
}

/**
 * Renders all PA sections in the order the PHP plugin emits them:
 *
 *   1. Top banner
 *   2. Hero
 *   3. "Early" Normal sections — any with `upp === true` (only Normal 1 in the
 *      current schema, but we read the flag on any section for future-proofing)
 *   4. Products (toggle OR side-menu mode)
 *   5. Solutions grid
 *   6. "Late" Normal sections — the remaining ones without `upp`
 *   7. Contact
 *   8. Docs
 */
export default function ProductAreaPreviewPanel({ state, activeSection, onSectionClick }: Props) {
  const normals: Array<{ n: 1 | 2 | 3 | 4; section: NormalSection }> = [
    { n: 1, section: state.normal1 },
    { n: 2, section: state.normal2 },
    { n: 3, section: state.normal3 },
    { n: 4, section: state.normal4 },
  ];
  const early = normals.filter((x) => x.section.upp);
  const late = normals.filter((x) => !x.section.upp);

  return (
    <div className="h-full overflow-y-auto bg-white editor-panel">
      <div style={{ fontFamily: 'var(--font-dm-sans)', color: '#11325D' }}>
        <TopBannerPreview state={state} active={activeSection} onSelect={onSectionClick} />
        <HeroPreview state={state} active={activeSection} onSelect={onSectionClick} />

        {early.map(({ n, section }) => (
          <NormalSectionPreview
            key={`early-${n}`}
            n={n}
            section={section}
            active={activeSection}
            onSelect={onSectionClick}
          />
        ))}

        <ProductsPreview state={state} active={activeSection} onSelect={onSectionClick} />
        <SolutionsPreview state={state} active={activeSection} onSelect={onSectionClick} />

        {late.map(({ n, section }) => (
          <NormalSectionPreview
            key={`late-${n}`}
            n={n}
            section={section}
            active={activeSection}
            onSelect={onSectionClick}
          />
        ))}

        <ContactPreview state={state} active={activeSection} onSelect={onSectionClick} />
        <DocsPreview state={state} active={activeSection} onSelect={onSectionClick} />

        {/* Spacer so the last section is comfortable to scroll to */}
        <div style={{ height: 80 }} />
      </div>
    </div>
  );
}
