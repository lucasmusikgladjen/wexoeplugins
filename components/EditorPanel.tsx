'use client';

import { useRef, useEffect } from 'react';
import { PageState, PageAction, SectionId } from '@/lib/types';
import HeroEditor from './editors/HeroEditor';
import ContentEditor from './editors/ContentEditor';
import SidebarEditor from './editors/SidebarEditor';
import TabsEditor from './editors/TabsEditor';
import ContactEditor from './editors/ContactEditor';

interface Props {
  state: PageState;
  dispatch: React.Dispatch<PageAction>;
  activeSection: SectionId | null;
  onSectionClick: (section: SectionId) => void;
  onSectionFocus: (section: SectionId) => void;
}

export default function EditorPanel({ state, dispatch, activeSection, onSectionClick, onSectionFocus }: Props) {
  const sectionRefs = useRef<Record<string, HTMLDivElement | null>>({});
  const scrollContainerRef = useRef<HTMLDivElement | null>(null);
  const scrollDetectedRef = useRef(false);
  const isProgrammaticScroll = useRef(false);
  const activeSectionRef = useRef(activeSection);
  activeSectionRef.current = activeSection;

  // Auto-scroll editor to active section (skip when triggered by scroll detection)
  useEffect(() => {
    if (scrollDetectedRef.current) {
      scrollDetectedRef.current = false;
      return;
    }
    if (activeSection && sectionRefs.current[activeSection]) {
      isProgrammaticScroll.current = true;
      sectionRefs.current[activeSection]?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      setTimeout(() => { isProgrammaticScroll.current = false; }, 500);
    }
  }, [activeSection]);

  // Detect which section is visible when user scrolls the editor
  useEffect(() => {
    const container = scrollContainerRef.current;
    if (!container) return;

    const handleScroll = () => {
      if (isProgrammaticScroll.current) return;

      const sectionIds: SectionId[] = ['hero', 'content', 'sidebar', 'tabs', 'contact'];

      // When near bottom, activate the last section
      const nearBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 80;
      if (nearBottom) {
        for (let i = sectionIds.length - 1; i >= 0; i--) {
          if (sectionRefs.current[sectionIds[i]]) {
            if (sectionIds[i] !== activeSectionRef.current) {
              scrollDetectedRef.current = true;
              onSectionClick(sectionIds[i]);
            }
            return;
          }
        }
      }

      const containerTop = container.getBoundingClientRect().top;
      let closestSection: SectionId | null = null;
      let closestDistance = Infinity;

      for (const id of sectionIds) {
        const el = sectionRefs.current[id];
        if (el) {
          const distance = Math.abs(el.getBoundingClientRect().top - containerTop);
          if (distance < closestDistance) {
            closestDistance = distance;
            closestSection = id;
          }
        }
      }

      if (closestSection && closestSection !== activeSectionRef.current) {
        scrollDetectedRef.current = true;
        onSectionClick(closestSection);
      }
    };

    container.addEventListener('scroll', handleScroll, { passive: true });
    return () => container.removeEventListener('scroll', handleScroll);
  }, [onSectionClick]);

  const sections: { id: SectionId; label: string }[] = [
    { id: 'hero', label: 'Hero' },
    { id: 'content', label: 'Innehåll' },
    { id: 'sidebar', label: 'Sidebar' },
    { id: 'tabs', label: 'Tabs' },
    { id: 'contact', label: 'Kontakt' },
  ];

  return (
    <div className="h-full flex flex-col bg-white border-l border-lp-border">
      {/* Section quick-nav */}
      <div className="flex border-b border-lp-border px-2 py-1.5 gap-1 flex-shrink-0 overflow-x-auto">
        {sections.map((s) => (
          <button
            key={s.id}
            onClick={() => onSectionClick(s.id)}
            className={`px-3 py-1 rounded-full text-xs font-medium transition-colors whitespace-nowrap ${
              activeSection === s.id
                ? 'bg-lp-main text-white'
                : 'text-lp-text-light hover:bg-gray-100'
            }`}
          >
            {s.label}
          </button>
        ))}
      </div>

      {/* Editor sections */}
      <div ref={scrollContainerRef} className="flex-1 overflow-y-auto editor-panel p-4 space-y-6">
        <div
          ref={(el) => { sectionRefs.current.hero = el; }}
          className={`p-3 rounded-lg cursor-pointer transition-colors ${activeSection === 'hero' ? 'bg-blue-50 ring-1 ring-lp-main/20' : 'hover:bg-gray-50'}`}
          onClick={() => onSectionClick('hero')}
          onFocusCapture={() => onSectionFocus('hero')}
        >
          <HeroEditor state={state} dispatch={dispatch} />
        </div>

        <div
          ref={(el) => { sectionRefs.current.content = el; }}
          className={`p-3 rounded-lg cursor-pointer transition-colors ${activeSection === 'content' ? 'bg-blue-50 ring-1 ring-lp-main/20' : 'hover:bg-gray-50'}`}
          onClick={() => onSectionClick('content')}
          onFocusCapture={() => onSectionFocus('content')}
        >
          <ContentEditor state={state} dispatch={dispatch} />
        </div>

        <div
          ref={(el) => { sectionRefs.current.sidebar = el; }}
          className={`p-3 rounded-lg cursor-pointer transition-colors ${activeSection === 'sidebar' ? 'bg-blue-50 ring-1 ring-lp-main/20' : 'hover:bg-gray-50'}`}
          onClick={() => onSectionClick('sidebar')}
          onFocusCapture={() => onSectionFocus('sidebar')}
        >
          <SidebarEditor state={state} dispatch={dispatch} />
        </div>

        <div
          ref={(el) => { sectionRefs.current.tabs = el; }}
          className={`p-3 rounded-lg cursor-pointer transition-colors ${activeSection === 'tabs' ? 'bg-blue-50 ring-1 ring-lp-main/20' : 'hover:bg-gray-50'}`}
          onClick={() => onSectionClick('tabs')}
          onFocusCapture={() => onSectionFocus('tabs')}
        >
          <TabsEditor state={state} dispatch={dispatch} />
        </div>

        <div
          ref={(el) => { sectionRefs.current.contact = el; }}
          className={`p-3 rounded-lg cursor-pointer transition-colors ${activeSection === 'contact' ? 'bg-blue-50 ring-1 ring-lp-main/20' : 'hover:bg-gray-50'}`}
          onClick={() => onSectionClick('contact')}
          onFocusCapture={() => onSectionFocus('contact')}
        >
          <ContactEditor state={state} dispatch={dispatch} />
        </div>
      </div>
    </div>
  );
}
