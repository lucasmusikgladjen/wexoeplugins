'use client';

import { useEffect, RefObject } from 'react';

/**
 * Scrollar previewpanelens scrollade element ned till den sektion vars
 * `data-section`-attribut matchar `activeSection`.
 *
 * Använder query-baserad lookup mot DOM:en så preview-komponenter slipper
 * tråda refs genom varje undersektion — det räcker att rendera
 * `data-section={sectionId}` på rätt nod.
 *
 * `scrollTrigger` är en räknare som callern bumpar för att triggas om
 * när samma sektion klickas igen (annars triggar useEffect inte på
 * oförändrade deps).
 */
export function useScrollToActiveSection(
  containerRef: RefObject<HTMLElement | null>,
  activeSection: string | null,
  scrollTrigger?: number,
): void {
  useEffect(() => {
    if (!activeSection || !containerRef.current) return;
    const el = containerRef.current.querySelector(`[data-section="${activeSection}"]`);
    if (el) {
      (el as HTMLElement).scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    // containerRef is a stable ref; re-run only when activeSection or scrollTrigger changes.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [activeSection, scrollTrigger]);
}
