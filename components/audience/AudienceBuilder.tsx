'use client';

import { useState, useCallback, useRef, useEffect } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { AudienceState, AudienceSectionId } from '@/lib/audience-types';
import AudiencePreviewPanel from './preview/AudiencePreviewPanel';
import HeroEditor from './editors/HeroEditor';
import ValueEditor from './editors/ValueEditor';
import CaseEditor from './editors/CaseEditor';
import SettingsEditor from './editors/SettingsEditor';

interface Props {
  initialState: AudienceState;
}

const QUICK_NAV: Array<{ id: AudienceSectionId; label: string }> = [
  { id: 'hero', label: 'Hero' },
  { id: 'value', label: 'Värde' },
  { id: 'case', label: 'Kundcase' },
  { id: 'settings', label: 'Inställningar' },
];

export default function AudienceBuilder({ initialState }: Props) {
  const router = useRouter();
  const [state, setState] = useState<AudienceState>(initialState);
  const [activeSection, setActiveSection] = useState<AudienceSectionId | null>('hero');
  const [scrollTrigger, setScrollTrigger] = useState(0);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [justSaved, setJustSaved] = useState(false);

  // Client-only per-section visibility for the preview + editor bodies.
  // Hero is always on (top of page). Value and Case start hidden in create
  // mode, on in edit mode if the loaded record has content.
  const [visibility, setVisibility] = useState<{ value: boolean; case: boolean }>(() => {
    if (initialState.mode === 'create') {
      return { value: false, case: false };
    }
    return {
      value: !!(
        initialState.valueH2.trim() ||
        initialState.valueText1.trim() ||
        initialState.valueText2.trim() ||
        initialState.benefit1.trim() ||
        initialState.benefit2.trim() ||
        initialState.benefit3.trim()
      ),
      case: !!initialState.caseTitle.trim(),
    };
  });

  const setVisible = useCallback(
    (key: keyof typeof visibility, value: boolean) => {
      setVisibility((v) => ({ ...v, [key]: value }));
    },
    [],
  );

  const isCreate = state.mode === 'create';
  const canSave = !!state.slug.trim() && !!state.title.trim();

  const setField = useCallback(
    <K extends keyof AudienceState>(key: K, value: AudienceState[K]) => {
      setState((s) => ({ ...s, [key]: value }));
      setJustSaved(false);
    },
    [],
  );

  // ── Scroll-sync plumbing (mirrors PA / LP) ─────────────────────────
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
      let closest: AudienceSectionId | null = null;
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

  const handleSectionClick = useCallback((id: AudienceSectionId) => {
    setActiveSection(id);
    setScrollTrigger((prev) => prev + 1);
  }, []);

  const handleSectionFocus = useCallback((id: AudienceSectionId) => {
    setActiveSection(id);
    setScrollTrigger((prev) => prev + 1);
  }, []);

  async function handleSave() {
    if (!canSave) return;
    setSaving(true);
    setError(null);
    try {
      const res = await fetch('/api/audience', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(state),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Sparning misslyckades');

      if (data.mode === 'create' && data.recordId) {
        router.replace(`/editor/audience/${data.recordId}`);
        return;
      }

      setJustSaved(true);
      router.refresh();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Okänt fel');
    } finally {
      setSaving(false);
    }
  }

  return (
    <div className="h-screen flex flex-col" style={{ fontFamily: 'var(--font-dm-sans)' }}>
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
            onChange={(e) =>
              setField('slug', e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, ''))
            }
            placeholder="min-sida"
            className="w-44 px-2 py-1 text-sm border border-gray-200 rounded-md focus:border-gray-400 focus:outline-none"
          />
          <span className="text-[10px] uppercase tracking-wider text-gray-300 ml-2">
            {isCreate ? 'Ny audience-sida' : 'Audience-sida'}
          </span>
        </div>

        <div className="flex-1" />

        {error && <span className="text-xs text-red-500 truncate max-w-xs">{error}</span>}
        {justSaved && !error && <span className="text-xs text-gray-400">Sparat ✓</span>}
        {!canSave && !error && (
          <span className="text-xs text-gray-300">Slug + titel krävs</span>
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

      <div className="flex-1 flex min-h-0">
        <div className="flex-[65] min-w-0">
          <AudiencePreviewPanel
            state={state}
            activeSection={activeSection}
            onSectionClick={handleSectionClick}
            scrollTrigger={scrollTrigger}
            visibility={visibility}
          />
        </div>

        <div className="flex-[35] min-w-[380px] max-w-[520px] h-full flex flex-col bg-white border-l border-gray-100">
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
                sectionRefs.current.value = el;
              }}
              className="cursor-pointer"
              onClick={() => handleSectionClick('value')}
              onFocusCapture={() => handleSectionFocus('value')}
            >
              <ValueEditor
                state={state}
                setField={setField}
                visible={visibility.value}
                onToggleVisible={(v) => setVisible('value', v)}
              />
            </div>

            <div
              ref={(el) => {
                sectionRefs.current.case = el;
              }}
              className="cursor-pointer"
              onClick={() => handleSectionClick('case')}
              onFocusCapture={() => handleSectionFocus('case')}
            >
              <CaseEditor
                state={state}
                setField={setField}
                visible={visibility.case}
                onToggleVisible={(v) => setVisible('case', v)}
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
              <SettingsEditor state={state} setField={setField} />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
