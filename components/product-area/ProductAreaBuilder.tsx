'use client';

import { useState, useCallback, useRef, useEffect } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import {
  ProductAreaState,
  ProductAreaSectionId,
  NormalSection,
  Division,
} from '@/lib/product-area-types';
import ProductAreaPreviewPanel from './preview/ProductAreaPreviewPanel';
import HeroEditor from './editors/HeroEditor';
import ContentBlocksEditor from './editors/ContentBlocksEditor';
import ProductsEditor from './editors/ProductsEditor';
import SolutionsEditor from './editors/SolutionsEditor';
import ContactEditor from './editors/ContactEditor';
import SettingsEditor from './editors/SettingsEditor';

interface Props {
  initialState: ProductAreaState;
  divisions: Division[];
}

const QUICK_NAV: Array<{ id: ProductAreaSectionId; label: string }> = [
  { id: 'hero', label: 'Hero' },
  { id: 'content', label: 'Innehåll' },
  { id: 'products', label: 'Produkter' },
  { id: 'solutions', label: 'Lösningar' },
  { id: 'contact', label: 'Kontakt' },
  { id: 'settings', label: 'Inställningar' },
];

export default function ProductAreaBuilder({ initialState, divisions }: Props) {
  const router = useRouter();
  const [state, setState] = useState<ProductAreaState>(initialState);
  const [activeSection, setActiveSection] = useState<ProductAreaSectionId | null>('hero');
  const [scrollTrigger, setScrollTrigger] = useState(0);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [justSaved, setJustSaved] = useState(false);

  // Client-only per-section visibility for the preview + editor bodies.
  // Not persisted to Airtable — just a UX switch so a fresh page starts
  // with only the Hero visible and everything else collapsed until the
  // user explicitly enables it. For edit mode we default sections "on"
  // if the loaded record already has content in them.
  const [visibility, setVisibility] = useState<{
    content: boolean;
    products: boolean;
    solutions: boolean;
    contact: boolean;
  }>(() => {
    if (initialState.mode === 'create') {
      return { content: false, products: false, solutions: false, contact: false };
    }
    const hasContent = [
      initialState.normal1,
      initialState.normal2,
      initialState.normal3,
      initialState.normal4,
    ].some((n) => n.h2.trim() || n.text.trim());
    return {
      content: hasContent,
      products: initialState.products.length > 0,
      solutions: initialState.solutions.length > 0,
      contact: !!initialState.contactName.trim(),
    };
  });

  const setVisible = useCallback(
    (key: keyof typeof visibility, value: boolean) => {
      setVisibility((v) => ({ ...v, [key]: value }));
    },
    [],
  );

  const isCreate = state.mode === 'create';
  const canSave = !!state.slug.trim() && !!state.h1.trim();

  // ── Generic setters ───────────────────────────────────────────────
  const setField = useCallback(
    <K extends keyof ProductAreaState>(key: K, value: ProductAreaState[K]) => {
      setState((s) => ({ ...s, [key]: value }));
      setJustSaved(false);
    },
    [],
  );

  const setNormal = useCallback(
    (n: 1 | 2 | 3 | 4, patch: Partial<NormalSection>) => {
      const key = `normal${n}` as 'normal1' | 'normal2' | 'normal3' | 'normal4';
      setState((s) => ({ ...s, [key]: { ...s[key], ...patch } }));
      setJustSaved(false);
    },
    [],
  );

  // ── Scroll-sync plumbing (mirrors LP EditorPanel) ────────────────
  const sectionRefs = useRef<Record<string, HTMLDivElement | null>>({});
  const scrollContainerRef = useRef<HTMLDivElement | null>(null);
  const scrollDetectedRef = useRef(false);
  const isProgrammaticScroll = useRef(false);
  const activeSectionRef = useRef(activeSection);
  activeSectionRef.current = activeSection;

  useEffect(() => {
    if (scrollDetectedRef.current) {
      scrollDetectedRef.current = false;
      return;
    }
    if (activeSection && sectionRefs.current[activeSection]) {
      isProgrammaticScroll.current = true;
      sectionRefs.current[activeSection]?.scrollIntoView({
        behavior: 'smooth',
        block: 'nearest',
      });
      setTimeout(() => {
        isProgrammaticScroll.current = false;
      }, 500);
    }
  }, [activeSection]);

  useEffect(() => {
    const container = scrollContainerRef.current;
    if (!container) return;

    const handleScroll = () => {
      if (isProgrammaticScroll.current) return;

      const sectionIds = QUICK_NAV.map((s) => s.id);

      const nearBottom =
        container.scrollHeight - container.scrollTop - container.clientHeight < 80;
      if (nearBottom) {
        for (let i = sectionIds.length - 1; i >= 0; i--) {
          if (sectionRefs.current[sectionIds[i]]) {
            if (sectionIds[i] !== activeSectionRef.current) {
              scrollDetectedRef.current = true;
              setActiveSection(sectionIds[i]);
            }
            return;
          }
        }
      }

      const containerTop = container.getBoundingClientRect().top;
      let closest: ProductAreaSectionId | null = null;
      let closestDistance = Infinity;

      for (const id of sectionIds) {
        const el = sectionRefs.current[id];
        if (el) {
          const distance = Math.abs(el.getBoundingClientRect().top - containerTop);
          if (distance < closestDistance) {
            closestDistance = distance;
            closest = id;
          }
        }
      }

      if (closest && closest !== activeSectionRef.current) {
        scrollDetectedRef.current = true;
        setActiveSection(closest);
      }
    };

    container.addEventListener('scroll', handleScroll, { passive: true });
    return () => container.removeEventListener('scroll', handleScroll);
  }, []);

  const handleSectionClick = useCallback((id: ProductAreaSectionId) => {
    setActiveSection(id);
    setScrollTrigger((prev) => prev + 1);
  }, []);

  // Fired when an editor field inside a section receives focus — mirrors the
  // LP EditorPanel's focus-driven sync so typing into any field centres the
  // corresponding preview section.
  const handleSectionFocus = useCallback((id: ProductAreaSectionId) => {
    setActiveSection(id);
    setScrollTrigger((prev) => prev + 1);
  }, []);

  // ── Save ─────────────────────────────────────────────────────────
  async function handleSave() {
    if (!canSave) return;
    setSaving(true);
    setError(null);
    try {
      const res = await fetch('/api/product-area', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(state),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Sparning misslyckades');

      if (data.mode === 'create' && data.recordId) {
        router.replace(`/editor/product-area/${data.recordId}`);
        return;
      }

      if (data.newProductIds || data.newSolutionIds) {
        setState((s) => ({
          ...s,
          products: applyNewIds(s.products, data.newProductIds ?? {}),
          solutions: applyNewIds(s.solutions, data.newSolutionIds ?? {}),
        }));
      }

      setJustSaved(true);
      router.refresh();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Okänt fel');
    } finally {
      setSaving(false);
    }
  }

  // ── Render ───────────────────────────────────────────────────────
  return (
    <div className="h-screen flex flex-col" style={{ fontFamily: 'var(--font-dm-sans)' }}>
      {/* Toolbar */}
      <header className="h-14 border-b border-gray-100 bg-white flex items-center px-4 gap-4 flex-shrink-0 z-10">
        <Link
          href="/"
          className="flex items-center gap-2 text-gray-500 hover:text-gray-900 transition-colors"
          title="Tillbaka till sidor"
        >
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M19 12H5M12 19l-7-7 7-7" />
          </svg>
          <span className="text-sm">Sidor</span>
        </Link>

        <div className="h-5 w-px bg-gray-200 mx-1" />

        <div className="flex items-center gap-2">
          <span className="text-xs text-gray-400">Slug:</span>
          <input
            type="text"
            value={state.slug}
            onChange={(e) => setField('slug', e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, ''))}
            placeholder="min-sida"
            className="w-44 px-2 py-1 text-sm border border-gray-200 rounded-md focus:border-gray-400 focus:outline-none"
          />
          <span className="text-[10px] uppercase tracking-wider text-gray-300 ml-2">
            {isCreate ? 'Ny produktsida' : 'Produktsida'}
          </span>
        </div>

        <div className="flex-1" />

        {error && <span className="text-xs text-red-500 truncate max-w-xs">{error}</span>}
        {justSaved && !error && <span className="text-xs text-gray-400">Sparat ✓</span>}
        {!canSave && !error && (
          <span className="text-xs text-gray-300">Slug + rubrik krävs</span>
        )}

        <button
          onClick={handleSave}
          disabled={saving || !canSave}
          className="px-4 py-1.5 rounded-md text-sm font-medium text-white transition-opacity hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed"
          style={{ background: '#11325D' }}
        >
          {saving ? (isCreate ? 'Skapar…' : 'Sparar…') : isCreate ? 'Skapa' : 'Spara'}
        </button>
      </header>

      {/* 2-pane layout: preview + editor */}
      <div className="flex-1 flex min-h-0">
        <div className="flex-[65] min-w-0">
          <ProductAreaPreviewPanel
            state={state}
            activeSection={activeSection}
            onSectionClick={handleSectionClick}
            scrollTrigger={scrollTrigger}
            visibility={visibility}
          />
        </div>

        <div className="flex-[35] min-w-[380px] max-w-[520px] h-full flex flex-col bg-white border-l border-gray-100">
          {/* Quick-nav pills — wraps to a second row if the editor pane is
              too narrow to fit all seven labels horizontally. */}
          <div className="flex flex-wrap px-3 py-2 gap-x-0.5 gap-y-1 flex-shrink-0 border-b border-gray-100">
            {QUICK_NAV.map((s) => (
              <button
                key={s.id}
                onClick={() => handleSectionClick(s.id)}
                className={`px-2.5 py-1 rounded-full text-xs transition-colors whitespace-nowrap ${
                  activeSection === s.id
                    ? 'bg-gray-100 text-gray-600'
                    : 'text-gray-400 hover:text-gray-500'
                }`}
              >
                {s.label}
              </button>
            ))}
          </div>

          {/* Scrollable editor sections */}
          <div
            ref={scrollContainerRef}
            className="flex-1 overflow-y-auto editor-panel p-4 space-y-10"
          >
            <div
              ref={(el) => {
                sectionRefs.current.hero = el;
              }}
              className="cursor-pointer"
              onClick={() => handleSectionClick('hero')}
              onFocusCapture={() => handleSectionFocus('hero')}
            >
              <HeroEditor state={state} setField={setField} />
            </div>

            <div
              ref={(el) => {
                sectionRefs.current.content = el;
              }}
              className="cursor-pointer"
              onClick={() => handleSectionClick('content')}
              onFocusCapture={() => handleSectionFocus('content')}
            >
              <ContentBlocksEditor
                state={state}
                setNormal={setNormal}
                visible={visibility.content}
                onToggleVisible={(v) => setVisible('content', v)}
              />
            </div>

            <div
              ref={(el) => {
                sectionRefs.current.products = el;
              }}
              className="cursor-pointer"
              onClick={() => handleSectionClick('products')}
              onFocusCapture={() => handleSectionFocus('products')}
            >
              <ProductsEditor
                state={state}
                setField={setField}
                visible={visibility.products}
                onToggleVisible={(v) => setVisible('products', v)}
              />
            </div>

            <div
              ref={(el) => {
                sectionRefs.current.solutions = el;
              }}
              className="cursor-pointer"
              onClick={() => handleSectionClick('solutions')}
              onFocusCapture={() => handleSectionFocus('solutions')}
            >
              <SolutionsEditor
                state={state}
                setField={setField}
                visible={visibility.solutions}
                onToggleVisible={(v) => setVisible('solutions', v)}
              />
            </div>

            <div
              ref={(el) => {
                sectionRefs.current.contact = el;
              }}
              className="cursor-pointer"
              onClick={() => handleSectionClick('contact')}
              onFocusCapture={() => handleSectionFocus('contact')}
            >
              <ContactEditor
                state={state}
                setField={setField}
                visible={visibility.contact}
                onToggleVisible={(v) => setVisible('contact', v)}
              />
            </div>

            <div
              ref={(el) => {
                sectionRefs.current.settings = el;
              }}
              className="cursor-pointer"
              onClick={() => handleSectionClick('settings')}
              onFocusCapture={() => handleSectionFocus('settings')}
            >
              <SettingsEditor state={state} divisions={divisions} setField={setField} />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

// Merge newly-assigned Airtable record IDs into client-side state. The server
// returns a map of clientId → new recordId after create.
function applyNewIds<T extends { clientId: string; recordId: string }>(
  items: T[],
  newIds: Record<string, string>,
): T[] {
  return items.map((item) => {
    if (item.recordId || !newIds[item.clientId]) return item;
    return { ...item, recordId: newIds[item.clientId] };
  });
}
