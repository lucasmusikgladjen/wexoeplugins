'use client';

import { useEffect, useRef } from 'react';
import { ProductAreaState, ProductAreaSectionId, NormalSection } from '@/lib/product-area-types';
import TopBannerPreview from './TopBannerPreview';
import HeroPreview from './HeroPreview';
import NormalSectionPreview from './NormalSectionPreview';
import ProductsPreview from './ProductsPreview';
import SolutionsPreview from './SolutionsPreview';
import ContactPreview from './ContactPreview';

interface Visibility {
  content: boolean;
  products: boolean;
  solutions: boolean;
  contact: boolean;
}

interface Props {
  state: ProductAreaState;
  activeSection: ProductAreaSectionId | null;
  onSectionClick: (id: ProductAreaSectionId) => void;
  /** Increments whenever the user focuses an editor field — triggers the
   *  preview to scroll so the active section lands in the centre of the
   *  visible area. Mirrors the Landing Page editor's behaviour. */
  scrollTrigger: number;
  /** Client-only per-section visibility. Sections turned off are hidden
   *  from the preview regardless of whether they have content. */
  visibility: Visibility;
}

/**
 * Renders all PA sections in the order the PHP plugin emits them:
 *
 *   1. Top banner
 *   2. Hero
 *   3. "Early" Normal sections — any with `upp === true`
 *   4. Products (toggle OR side-menu mode)
 *   5. Solutions grid
 *   6. "Late" Normal sections — the remaining ones without `upp`
 *   7. Contact
 *
 * Top banner, hero and contact always render (with ghost placeholders when
 * empty) so the create flow shows a meaningful skeleton of the page.
 */
export default function ProductAreaPreviewPanel({
  state,
  activeSection,
  onSectionClick,
  scrollTrigger,
  visibility,
}: Props) {
  const pageRef = useRef<HTMLDivElement>(null);

  // Scroll the active section into the centre of the preview pane whenever
  // the active section or scrollTrigger changes. Uses the `data-section`
  // attribute already applied by PreviewSection, so we don't need to thread
  // refs through every sub-component.
  useEffect(() => {
    if (!activeSection || !pageRef.current) return;
    const el = pageRef.current.querySelector(`[data-section="${activeSection}"]`);
    if (el) {
      (el as HTMLElement).scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }, [activeSection, scrollTrigger]);

  const normals: Array<{ n: 1 | 2 | 3 | 4; section: NormalSection }> = [
    { n: 1, section: state.normal1 },
    { n: 2, section: state.normal2 },
    { n: 3, section: state.normal3 },
    { n: 4, section: state.normal4 },
  ];
  const early = normals.filter((x) => x.section.upp);
  const late = normals.filter((x) => !x.section.upp);

  const isEmpty =
    !state.h1.trim() &&
    !state.heroH2.trim() &&
    !state.heroText.trim() &&
    state.products.length === 0 &&
    state.solutions.length === 0 &&
    !state.contactName.trim() &&
    !normals.some((x) => x.section.h2.trim() || x.section.text.trim());

  return (
    <div className="h-full overflow-y-auto bg-gray-100 hide-scrollbar">
      <div
        ref={pageRef}
        className="max-w-[900px] mx-auto my-4 shadow-lg rounded-lg overflow-hidden bg-white"
      >
        {/* Browser chrome */}
        <div className="bg-gray-50 border-b border-gray-200 px-4 py-1.5 flex items-center gap-2">
          <div className="flex gap-1.5">
            <div className="w-2.5 h-2.5 rounded-full bg-red-400" />
            <div className="w-2.5 h-2.5 rounded-full bg-yellow-400" />
            <div className="w-2.5 h-2.5 rounded-full bg-green-400" />
          </div>
          <span className="text-xs text-gray-400 ml-2">
            wexoe.se/{state.slug || '...'}/ — Preview
          </span>
        </div>

        <div style={{ fontFamily: 'var(--font-dm-sans)', color: '#11325D' }}>
          <TopBannerPreview state={state} active={activeSection} onSelect={onSectionClick} />
          <HeroPreview state={state} active={activeSection} onSelect={onSectionClick} />

          {visibility.content && early.map(({ n, section }) => (
            <NormalSectionPreview
              key={`early-${n}`}
              n={n}
              section={section}
              active={activeSection}
              onSelect={onSectionClick}
            />
          ))}

          {visibility.products && (
            <ProductsPreview state={state} active={activeSection} onSelect={onSectionClick} />
          )}
          {visibility.solutions && (
            <SolutionsPreview state={state} active={activeSection} onSelect={onSectionClick} />
          )}

          {visibility.content && late.map(({ n, section }) => (
            <NormalSectionPreview
              key={`late-${n}`}
              n={n}
              section={section}
              active={activeSection}
              onSelect={onSectionClick}
            />
          ))}

          {visibility.contact && (
            <ContactPreview state={state} active={activeSection} onSelect={onSectionClick} />
          )}
        </div>

        {/* Empty-state hint overlay when the whole state is pristine. */}
        {isEmpty && (
          <div className="px-8 py-10 text-center text-gray-400 border-t border-gray-100">
            <p className="text-sm mb-1">Börja bygga din produktsida</p>
            <p className="text-xs">Fyll i fälten till höger — förhandsvisningen uppdateras direkt.</p>
          </div>
        )}
      </div>
    </div>
  );
}
