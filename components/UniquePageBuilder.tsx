'use client';

import { useCallback, useState } from 'react';
import { useRouter } from 'next/navigation';
import BuilderShell from './BuilderShell';
import { SaveButton, SaveStatus } from './shared/builder';
import {
  UniquePageState,
  UniquePageSectionId,
} from '@/lib/unique-page-types';
import { isReservedSlug } from '@/lib/core/reserved-slugs';
import MetadataPanel from './unique-page/editors/MetadataPanel';
import HeroEditor from './unique-page/editors/HeroEditor';
import TextImageEditor from './unique-page/editors/TextImageEditor';
import TextOnlyEditor from './unique-page/editors/TextOnlyEditor';
import FaqEditor from './unique-page/editors/FaqEditor';
import TeamGridEditor from './unique-page/editors/TeamGridEditor';
import PartnersMarqueeEditor from './unique-page/editors/PartnersMarqueeEditor';
import TestimonialCardEditor from './unique-page/editors/TestimonialCardEditor';
import CtaBannerEditor from './unique-page/editors/CtaBannerEditor';
import UniquePagePreview from './unique-page/preview/UniquePagePreview';
import ContactFormSection from './contact-form/ContactFormSection';

interface CountryOption { recordId: string; code: string; name: string; }
interface DivisionOption { recordId: string; slug: string; name: string; }

interface Props {
  initialState: UniquePageState;
  countryOptions: CountryOption[];
  divisionOptions: DivisionOption[];
}

const QUICK_NAV: Array<{ id: UniquePageSectionId; label: string }> = [
  { id: 'metadata', label: 'Sidinfo' },
  { id: 'hero', label: 'Hero' },
  { id: 'textImageA', label: 'Text+bild A' },
  { id: 'textImageB', label: 'Text+bild B' },
  { id: 'textOnly', label: 'Text' },
  { id: 'faq', label: 'FAQ' },
  { id: 'teamGrid', label: 'Team' },
  { id: 'partnersMarquee', label: 'Partners' },
  { id: 'testimonialCard', label: 'Citat' },
  { id: 'ctaBanner', label: 'CTA' },
  { id: 'contactForm', label: 'Kontakt' },
];

export default function UniquePageBuilder({ initialState, countryOptions, divisionOptions }: Props) {
  const router = useRouter();
  const [state, setState] = useState<UniquePageState>(initialState);
  const [activeSection, setActiveSection] = useState<UniquePageSectionId | null>('metadata');
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [justSaved, setJustSaved] = useState(false);

  const isCreate = state.mode === 'create';
  const slugError = !state.slug ? null
    : isReservedSlug(state.slug) ? `"${state.slug}" är reserverad`
    : !/^[a-z0-9][a-z0-9-]*$/.test(state.slug) ? 'Bara a-z, 0-9, bindestreck'
    : null;
  const canSave = !!state.slug && !!state.h1 && !slugError;

  const setField = useCallback(<K extends keyof UniquePageState>(key: K, value: UniquePageState[K]) => {
    setState((s) => ({ ...s, [key]: value }));
    setJustSaved(false);
  }, []);

  async function handleSave() {
    if (!canSave) return;
    setSaving(true);
    setError(null);
    try {
      const url = isCreate ? '/api/unique-page' : `/api/unique-page?id=${state.recordId}`;
      const method = isCreate ? 'POST' : 'PATCH';
      const res = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(state),
      });
      const data = await res.json();
      if (!res.ok || !data.success) throw new Error(data.error || `HTTP ${res.status}`);

      if (data.mode === 'create' && data.recordId) {
        router.replace(`/editor/unique/${data.recordId}`);
        return;
      }

      setJustSaved(true);
      router.refresh();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Sparning misslyckades');
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
        onChange={(e) => setField('slug', e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, ''))}
        placeholder="om-oss"
        className={`w-44 px-2 py-1 text-sm border rounded-md focus:outline-none ${slugError ? 'border-red-300 focus:border-red-400' : 'border-gray-200 focus:border-gray-400'}`}
      />
      <label className="flex items-center gap-1.5 ml-2 text-xs text-gray-600">
        <input
          type="checkbox"
          checked={state.published}
          onChange={(e) => setField('published', e.target.checked)}
          className="h-3.5 w-3.5"
        />
        Publicerad
      </label>
      <span className="text-[10px] uppercase tracking-wider text-gray-300 ml-2">
        {isCreate ? 'Ny sida' : 'Unik sida'}
      </span>
    </div>
  );

  const toolbarMiddle = (
    <SaveStatus
      error={error || slugError || null}
      justSaved={justSaved}
      hint="Slug + H1 krävs"
      canSave={canSave}
    />
  );

  const toolbarRight = (
    <SaveButton onClick={handleSave} saving={saving} canSave={canSave} isCreate={isCreate} />
  );

  return (
    <BuilderShell
      toolbar={{ left: toolbarLeft, middle: toolbarMiddle, right: toolbarRight }}
      quickNav={QUICK_NAV}
      activeSection={activeSection}
      onActiveSectionChange={(id) => setActiveSection(id as UniquePageSectionId)}
      previewPanel={<UniquePagePreview state={state} />}
      editorSections={({ sectionRef, onSectionFocus }) => (
        <>
          <div ref={sectionRef('metadata')} onClick={() => onSectionFocus('metadata')} onFocusCapture={() => onSectionFocus('metadata')}>
            <MetadataPanel state={state} setField={setField} countryOptions={countryOptions} divisionOptions={divisionOptions} />
          </div>

          <div ref={sectionRef('hero')} onClick={() => onSectionFocus('hero')} onFocusCapture={() => onSectionFocus('hero')}>
            <HeroEditor
              visible={state.showHero}
              onToggleVisible={(v) => setField('showHero', v)}
              state={state.hero}
              onChange={(s) => setField('hero', s)}
            />
          </div>

          <div ref={sectionRef('textImageA')} onClick={() => onSectionFocus('textImageA')} onFocusCapture={() => onSectionFocus('textImageA')}>
            <TextImageEditor
              title="Text + bild A"
              visible={state.showTextImageA}
              onToggleVisible={(v) => setField('showTextImageA', v)}
              state={state.textImageA}
              onChange={(s) => setField('textImageA', s)}
            />
          </div>

          <div ref={sectionRef('textImageB')} onClick={() => onSectionFocus('textImageB')} onFocusCapture={() => onSectionFocus('textImageB')}>
            <TextImageEditor
              title="Text + bild B"
              visible={state.showTextImageB}
              onToggleVisible={(v) => setField('showTextImageB', v)}
              state={state.textImageB}
              onChange={(s) => setField('textImageB', s)}
            />
          </div>

          <div ref={sectionRef('textOnly')} onClick={() => onSectionFocus('textOnly')} onFocusCapture={() => onSectionFocus('textOnly')}>
            <TextOnlyEditor
              visible={state.showTextOnly}
              onToggleVisible={(v) => setField('showTextOnly', v)}
              state={state.textOnly}
              onChange={(s) => setField('textOnly', s)}
            />
          </div>

          <div ref={sectionRef('faq')} onClick={() => onSectionFocus('faq')} onFocusCapture={() => onSectionFocus('faq')}>
            <FaqEditor
              visible={state.showFaq}
              onToggleVisible={(v) => setField('showFaq', v)}
              state={state.faq}
              onChange={(s) => setField('faq', s)}
            />
          </div>

          <div ref={sectionRef('teamGrid')} onClick={() => onSectionFocus('teamGrid')} onFocusCapture={() => onSectionFocus('teamGrid')}>
            <TeamGridEditor
              visible={state.showTeamGrid}
              onToggleVisible={(v) => setField('showTeamGrid', v)}
              state={state.teamGrid}
              onChange={(s) => setField('teamGrid', s)}
            />
          </div>

          <div ref={sectionRef('partnersMarquee')} onClick={() => onSectionFocus('partnersMarquee')} onFocusCapture={() => onSectionFocus('partnersMarquee')}>
            <PartnersMarqueeEditor
              visible={state.showPartnersMarquee}
              onToggleVisible={(v) => setField('showPartnersMarquee', v)}
              state={state.partnersMarquee}
              onChange={(s) => setField('partnersMarquee', s)}
            />
          </div>

          <div ref={sectionRef('testimonialCard')} onClick={() => onSectionFocus('testimonialCard')} onFocusCapture={() => onSectionFocus('testimonialCard')}>
            <TestimonialCardEditor
              visible={state.showTestimonialCard}
              onToggleVisible={(v) => setField('showTestimonialCard', v)}
              state={state.testimonialCard}
              onChange={(s) => setField('testimonialCard', s)}
            />
          </div>

          <div ref={sectionRef('ctaBanner')} onClick={() => onSectionFocus('ctaBanner')} onFocusCapture={() => onSectionFocus('ctaBanner')}>
            <CtaBannerEditor
              visible={state.showCtaBanner}
              onToggleVisible={(v) => setField('showCtaBanner', v)}
              state={state.ctaBanner}
              onChange={(s) => setField('ctaBanner', s)}
            />
          </div>

          <div ref={sectionRef('contactForm')} onClick={() => onSectionFocus('contactForm')} onFocusCapture={() => onSectionFocus('contactForm')}>
            <ContactFormSection
              visible={state.showContactForm}
              onToggleVisible={(v) => setField('showContactForm', v)}
              state={state.contactForm}
              onChange={(s) => setField('contactForm', s)}
            />
          </div>
        </>
      )}
    />
  );
}
