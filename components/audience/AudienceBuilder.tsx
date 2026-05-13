'use client';

import { useState, useCallback } from 'react';
import { useRouter } from 'next/navigation';
import { AudienceState, AudienceSectionId } from '@/lib/audience-types';
import AudiencePreviewPanel from './preview/AudiencePreviewPanel';
import HeroEditor from './editors/HeroEditor';
import ValueEditor from './editors/ValueEditor';
import CaseEditor from './editors/CaseEditor';
import SettingsEditor from './editors/SettingsEditor';
import BuilderShell from '../BuilderShell';
import ContactFormEditor from '../contact-form/ContactFormEditor';

interface Props {
  initialState: AudienceState;
}

const QUICK_NAV: Array<{ id: AudienceSectionId; label: string }> = [
  { id: 'hero', label: 'Hero' },
  { id: 'value', label: 'Värde' },
  { id: 'case', label: 'Kundcase' },
  { id: 'contactForm', label: 'Kontakt' },
  { id: 'settings', label: 'Inställningar' },
];

export default function AudienceBuilder({ initialState }: Props) {
  const router = useRouter();
  const [state, setState] = useState<AudienceState>(initialState);
  const [activeSection, setActiveSection] = useState<AudienceSectionId | null>('hero');
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [justSaved, setJustSaved] = useState(false);

  // Hero is always on (top of page). Value and Case start hidden in create mode,
  // on in edit mode if the loaded record has content.
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

  const toolbarLeft = (
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
  );

  const toolbarMiddle = (
    <>
      {error && <span className="text-xs text-red-500 truncate max-w-xs">{error}</span>}
      {justSaved && !error && <span className="text-xs text-gray-400">Sparat ✓</span>}
      {!canSave && !error && (
        <span className="text-xs text-gray-300">Slug + titel krävs</span>
      )}
    </>
  );

  const toolbarRight = (
    <button
      onClick={handleSave}
      disabled={saving || !canSave}
      className="px-4 py-1.5 rounded-md text-sm font-medium text-white transition-opacity hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed"
      style={{ background: '#11325D' }}
    >
      {saving ? (isCreate ? 'Skapar…' : 'Sparar…') : isCreate ? 'Skapa' : 'Spara'}
    </button>
  );

  return (
    <BuilderShell
      toolbar={{ left: toolbarLeft, middle: toolbarMiddle, right: toolbarRight }}
      quickNav={QUICK_NAV}
      activeSection={activeSection}
      onActiveSectionChange={(id) => setActiveSection(id as AudienceSectionId)}
      previewPanel={
        <AudiencePreviewPanel
          state={state}
          activeSection={activeSection}
          onSectionClick={(id) => setActiveSection(id)}
          scrollTrigger={0}
          visibility={visibility}
        />
      }
      editorSections={({ sectionRef, onSectionFocus }) => (
        <>
          <div
            ref={sectionRef('hero')}
            className="cursor-pointer"
            onClick={() => onSectionFocus('hero')}
            onFocusCapture={() => onSectionFocus('hero')}
          >
            <HeroEditor state={state} setField={setField} />
          </div>

          <div
            ref={sectionRef('value')}
            className="cursor-pointer"
            onClick={() => onSectionFocus('value')}
            onFocusCapture={() => onSectionFocus('value')}
          >
            <ValueEditor
              state={state}
              setField={setField}
              visible={visibility.value}
              onToggleVisible={(v) => setVisible('value', v)}
            />
          </div>

          <div
            ref={sectionRef('case')}
            className="cursor-pointer"
            onClick={() => onSectionFocus('case')}
            onFocusCapture={() => onSectionFocus('case')}
          >
            <CaseEditor
              state={state}
              setField={setField}
              visible={visibility.case}
              onToggleVisible={(v) => setVisible('case', v)}
            />
          </div>

          <div
            ref={sectionRef('contactForm')}
            className="cursor-pointer"
            onClick={() => onSectionFocus('contactForm')}
            onFocusCapture={() => onSectionFocus('contactForm')}
          >
            <div className="border border-gray-100 rounded-md overflow-hidden">
              <div className="flex items-center gap-2 px-3 py-2 bg-gray-50 border-b border-gray-100">
                <span className="flex-1 text-sm font-medium text-gray-800">Kontaktformulär</span>
                <label className="flex items-center gap-1.5 text-[11px] text-gray-500">
                  <input
                    type="checkbox"
                    checked={state.showContactForm}
                    onChange={(e) => setField('showContactForm', e.target.checked)}
                    className="h-3.5 w-3.5"
                  />
                  Visa
                </label>
              </div>
              {state.showContactForm && (
                <div className="p-3">
                  <ContactFormEditor
                    state={state.contactForm}
                    onChange={(s) => setField('contactForm', s)}
                  />
                </div>
              )}
            </div>
          </div>

          <div
            ref={sectionRef('settings')}
            className="cursor-pointer"
            onClick={() => onSectionFocus('settings')}
            onFocusCapture={() => onSectionFocus('settings')}
          >
            <SettingsEditor state={state} setField={setField} />
          </div>
        </>
      )}
    />
  );
}
