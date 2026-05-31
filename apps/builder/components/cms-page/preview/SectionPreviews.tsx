'use client';

import type { CSSProperties } from 'react';
import {
  CaseGridSection,
  CatalogSection,
  CompanyDataStripSection,
  ContactFormSection,
  CtaBannerSection,
  FaqSection,
  HeroSection,
  NewsGridSection,
  NewsTextSplitSection,
  PartnerListSection,
  TabsSection,
  TeamGridSection,
  TestimonialSection,
  TextImageSection,
  TextOnlySection,
} from '@/lib/cms-page-types';

// ─── Brand tokens ──────────────────────────────────────────────────────────

const C = {
  navy: '#11325D',
  orange: '#F28C28',
  green: '#10B981',
  paper: '#FAFAF7',
  muted: '#5A6473',
  dark: '#0F0F0F',
  white: '#fff',
  border: 'rgba(15,15,15,0.10)',
  navyFaint: 'rgba(17,50,93,0.06)',
};

const navyGradient = 'linear-gradient(135deg, #11325D 0%, #1a4a7a 55%, #2d6a9f 100%)';
const font = "'DM Sans', sans-serif";
const sectionPad: CSSProperties = { padding: '72px 48px' };
const innerMax: CSSProperties = { maxWidth: 1200, margin: '0 auto' };

// ─── Primitive helpers ──────────────────────────────────────────────────────

function Eyebrow({ text, light = false, bookend = false }: { text: string; light?: boolean; bookend?: boolean }) {
  if (!text) return null;
  const color = light ? 'rgba(255,255,255,0.72)' : C.muted;
  const dashEl = <span style={{ display: 'inline-block', flexShrink: 0, width: 22, height: 1, background: color, verticalAlign: 'middle', margin: '0 10px' }} />;
  return (
    <p style={{ fontFamily: font, fontSize: 11, textTransform: 'uppercase', letterSpacing: '0.14em', color, marginBottom: 16, display: 'flex', alignItems: 'center', justifyContent: bookend ? 'center' : 'flex-start' }}>
      {dashEl}
      {text}
      {bookend && dashEl}
    </p>
  );
}

function H2({ text, light = false }: { text: string; light?: boolean }) {
  if (!text) return null;
  return (
    <h2 style={{ fontFamily: font, fontSize: 'clamp(1.75rem, 3.5vw, 2.4rem)', fontWeight: 700, color: light ? C.white : C.navy, marginBottom: 16, lineHeight: 1.15 }}>
      {text}
    </h2>
  );
}

function Body({ text, light = false }: { text: string; light?: boolean }) {
  if (!text) return null;
  return (
    <p style={{ fontFamily: font, fontSize: 16, color: light ? 'rgba(255,255,255,0.80)' : C.muted, lineHeight: 1.7, whiteSpace: 'pre-line', marginBottom: 16 }}>
      {text}
    </p>
  );
}

function ButtonRow({ cta, cta2, light = false, centered = false }: {
  cta: { text: string; url: string };
  cta2?: { text: string; url: string };
  light?: boolean;
  centered?: boolean;
}) {
  if (!cta.text && !cta2?.text) return null;
  return (
    <div style={{ display: 'flex', flexWrap: 'wrap', gap: 12, marginTop: 24, justifyContent: centered ? 'center' : 'flex-start' }}>
      {cta.text && (
        <span style={{ display: 'inline-block', background: C.orange, color: C.white, fontFamily: font, fontWeight: 600, fontSize: 14, padding: '14px 28px', borderRadius: 2 }}>
          {cta.text}
        </span>
      )}
      {cta2?.text && (
        <span style={{ display: 'inline-block', border: `1.5px solid ${light ? 'rgba(255,255,255,0.50)' : C.navy}`, color: light ? C.white : C.navy, fontFamily: font, fontWeight: 600, fontSize: 14, padding: '12px 27px', borderRadius: 2 }}>
          {cta2.text}
        </span>
      )}
    </div>
  );
}

function ImageBox({ url, label, aspectRatio = '4/3', withShadow = false }: { url: string; label?: string; aspectRatio?: string; withShadow?: boolean }) {
  const shadow = withShadow ? '0 18px 40px rgba(10,26,46,0.12)' : undefined;
  if (url) {
    return (
      // eslint-disable-next-line @next/next/no-img-element
      <img src={url} alt="" style={{ width: '100%', aspectRatio, objectFit: 'cover', borderRadius: 2, boxShadow: shadow, display: 'block' }} />
    );
  }
  return (
    <div style={{ width: '100%', aspectRatio, borderRadius: 2, background: navyGradient, boxShadow: shadow, display: 'grid', placeItems: 'center', color: 'rgba(255,255,255,0.45)', fontSize: 11, textTransform: 'uppercase', letterSpacing: '0.1em' }}>
      {label || 'Bild'}
    </div>
  );
}

function GreenBullets({ raw }: { raw: string }) {
  const lines = raw.split('\n').filter(Boolean);
  if (lines.length === 0) return null;
  return (
    <ul style={{ listStyle: 'none', padding: 0, margin: '0 0 16px', display: 'flex', flexDirection: 'column', gap: 12 }}>
      {lines.map((b, i) => (
        <li key={i} style={{ display: 'flex', alignItems: 'flex-start', gap: 12 }}>
          <span style={{ flexShrink: 0, width: 22, height: 22, borderRadius: '50%', background: C.green, display: 'flex', alignItems: 'center', justifyContent: 'center', color: C.white, fontSize: 12, fontWeight: 700, marginTop: 2 }}>✓</span>
          <span style={{ fontSize: 15, color: C.dark, lineHeight: 1.6 }}>{b}</span>
        </li>
      ))}
    </ul>
  );
}

// ─── Section previews ───────────────────────────────────────────────────────

export function HeroPreview({ section, pageH1 }: { section: HeroSection; pageH1: string }) {
  const hasImage = !!section.imageUrl;
  return (
    <div style={{ background: navyGradient, ...sectionPad, position: 'relative', overflow: 'hidden', fontFamily: font }}>
      <div style={{ position: 'absolute', top: -80, right: -80, width: 320, height: 320, borderRadius: '50%', background: 'rgba(255,255,255,0.04)', pointerEvents: 'none' }} />
      <div style={{ position: 'absolute', bottom: 40, left: -40, width: 220, height: 220, border: '1px solid rgba(255,255,255,0.06)', transform: 'rotate(15deg)', pointerEvents: 'none' }} />
      <div style={{ ...innerMax, display: hasImage ? 'grid' : 'block', gridTemplateColumns: hasImage ? 'minmax(0, 1.05fr) minmax(0, 1fr)' : undefined, gap: hasImage ? 56 : undefined, alignItems: 'center', position: 'relative', zIndex: 1 }}>
        <div style={{ maxWidth: hasImage ? undefined : 640 }}>
          <Eyebrow text={section.eyebrow} light />
          <h1 style={{ fontFamily: font, fontSize: 'clamp(2.25rem, 5vw, 3.5rem)', fontWeight: 700, color: C.white, lineHeight: 1.1, marginBottom: 20 }}>
            {section.h1 || pageH1 || '(ingen rubrik)'}
          </h1>
          {section.subtitle && (
            <p style={{ fontSize: 18, color: 'rgba(255,255,255,0.80)', lineHeight: 1.65, marginBottom: 0 }}>
              {section.subtitle}
            </p>
          )}
          <ButtonRow cta={{ text: section.ctaText, url: section.ctaUrl }} cta2={{ text: section.cta2Text, url: section.cta2Url }} light />
        </div>
        {hasImage && <ImageBox url={section.imageUrl} label="Hero-bild" aspectRatio="4/3" withShadow />}
      </div>
    </div>
  );
}

export function TextImagePreview({ section }: { section: TextImageSection }) {
  return (
    <div style={{ background: C.white, ...sectionPad, fontFamily: font }}>
      <div style={{ ...innerMax, display: 'grid', gridTemplateColumns: 'minmax(0, 1fr) minmax(0, 1fr)', gap: 56, alignItems: 'center' }}>
        <div style={{ order: section.reversed ? 2 : 1 }}>
          <Eyebrow text={section.eyebrow} />
          <H2 text={section.h2} />
          <Body text={section.body} />
          <GreenBullets raw={section.bullets} />
          <ButtonRow cta={{ text: section.ctaText, url: section.ctaUrl }} cta2={{ text: section.cta2Text, url: section.cta2Url }} />
        </div>
        <div style={{ order: section.reversed ? 1 : 2 }}>
          <ImageBox url={section.imageUrl} label="Bild" aspectRatio="4/3" withShadow />
        </div>
      </div>
    </div>
  );
}

export function TextOnlyPreview({ section }: { section: TextOnlySection }) {
  const centered = section.align === 'center';
  return (
    <div style={{ background: C.white, ...sectionPad, fontFamily: font }}>
      <div style={{ maxWidth: 720, margin: '0 auto', textAlign: centered ? 'center' : 'left' }}>
        <Eyebrow text={section.eyebrow} />
        <H2 text={section.h2} />
        <Body text={section.body} />
      </div>
    </div>
  );
}

export function CompanyDataStripPreview({ section }: { section: CompanyDataStripSection }) {
  const items = section.items.split('\n').filter(Boolean).map((line) => {
    const [val, lbl, suffix] = line.split('|');
    return { value: (val || '').trim(), label: (lbl || '').trim(), suffix: (suffix || '').trim() };
  });
  const hasHeader = !!section.h2;
  const numStyle: CSSProperties = { fontSize: 'clamp(2rem, 3.4vw, 2.75rem)', fontWeight: 700, color: C.white, lineHeight: 1 };
  const lblStyle: CSSProperties = { fontSize: 14, color: 'rgba(255,255,255,0.74)', marginTop: 6 };

  return (
    <div style={{ background: C.navy, ...sectionPad, fontFamily: font }}>
      <div style={innerMax}>
        {hasHeader ? (
          <div style={{ display: 'grid', gridTemplateColumns: '1fr auto 3fr', gap: 48, alignItems: 'center' }}>
            <h2 style={{ fontFamily: font, fontSize: 'clamp(1.5rem, 2.5vw, 2rem)', fontWeight: 700, color: C.white, lineHeight: 1.2 }}>
              {section.h2}
            </h2>
            <div style={{ width: 1, height: 96, background: 'rgba(255,255,255,0.22)' }} />
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(160px, 1fr))', gap: 24 }}>
              {items.map((it, i) => (
                <div key={i}>
                  <div style={numStyle}>{it.value || '–'}{it.suffix}</div>
                  <div style={lblStyle}>{it.label}</div>
                </div>
              ))}
            </div>
          </div>
        ) : (
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(200px, 1fr))', gap: 32 }}>
            {items.length === 0
              ? <p style={{ color: 'rgba(255,255,255,0.40)', fontSize: 13 }}>Inga datapunkter</p>
              : items.map((it, i) => (
                <div key={i} style={{ textAlign: 'center' }}>
                  <div style={numStyle}>{it.value || '–'}{it.suffix}</div>
                  <div style={lblStyle}>{it.label}</div>
                </div>
              ))}
          </div>
        )}
      </div>
    </div>
  );
}

export function NewsTextSplitPreview({ section }: { section: NewsTextSplitSection }) {
  return (
    <div style={{ background: C.paper, ...sectionPad, fontFamily: font }}>
      <div style={{ ...innerMax, display: 'grid', gridTemplateColumns: 'minmax(0, 1.3fr) minmax(0, 1fr)', gap: 56, alignItems: 'start' }}>
        <div>
          <Eyebrow text={section.eyebrow} />
          <H2 text={section.h2} />
          <Body text={section.body} />
          <ButtonRow cta={{ text: section.ctaText, url: section.ctaUrl }} />
        </div>
        <div style={{ borderLeft: `3px solid ${C.navy}`, padding: '24px 24px 24px 28px', background: C.white }}>
          <p style={{ fontSize: 12, textTransform: 'uppercase', letterSpacing: '0.1em', fontWeight: 700, color: C.navy, marginBottom: 16 }}>Senaste nytt</p>
          {[1, 2, 3].map((i) => (
            <div key={i} style={{ borderBottom: `1px solid rgba(17,50,93,0.08)`, padding: '14px 0' }}>
              <p style={{ fontWeight: 600, fontSize: 14, color: C.navy, lineHeight: 1.3, marginBottom: 4 }}>Artikelrubrik #{i}</p>
              <p style={{ fontSize: 12, color: C.muted }}>Kort ingress till artikeln…</p>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

function CaseCard({ index }: { index: number }) {
  return (
    <div style={{ background: C.white, border: `1px solid ${C.border}`, borderRadius: 2, overflow: 'hidden', display: 'flex', flexDirection: 'column' }}>
      <div style={{ aspectRatio: '4/3', position: 'relative', background: navyGradient }}>
        <span style={{ position: 'absolute', top: 14, left: 14, fontSize: 10, textTransform: 'uppercase', letterSpacing: '0.08em', background: 'rgba(250,250,247,0.95)', color: C.navy, fontWeight: 700, padding: '4px 8px', borderRadius: 2 }}>
          Bransch
        </span>
      </div>
      <div style={{ padding: '16px 20px', height: 116, overflow: 'hidden' }}>
        <p style={{ fontWeight: 700, fontSize: 15, color: C.navy, marginBottom: 6, lineHeight: 1.3 }}>Case-titel #{index}</p>
        <p style={{ fontSize: 13, color: C.muted, lineHeight: 1.5 }}>Kort beskrivning av uppdraget och lösningen som levererades.</p>
      </div>
      <div style={{ padding: '12px 20px', borderTop: `1px solid ${C.border}`, display: 'flex', alignItems: 'center', gap: 10 }}>
        <span style={{ flexShrink: 0, width: 18, height: 18, borderRadius: '50%', background: '#16A34A', display: 'flex', alignItems: 'center', justifyContent: 'center', color: C.white, fontSize: 10, fontWeight: 700 }}>✓</span>
        <span style={{ fontSize: 13, color: C.dark }}>Resultat: <strong style={{ color: C.navy }}>+35%</strong> effektivitet</span>
      </div>
      <div style={{ padding: '14px 20px 16px', marginTop: 'auto', display: 'flex', justifyContent: 'space-between', alignItems: 'center', borderTop: `1px solid ${C.border}` }}>
        <span style={{ fontSize: 11, fontWeight: 700, textTransform: 'uppercase', color: C.navy }}>Partnernamn</span>
        <span style={{ fontSize: 16, color: C.muted }}>→</span>
      </div>
    </div>
  );
}

export function CaseGridPreview({ section }: { section: CaseGridSection }) {
  const cols = Number(section.columns) || 3;
  return (
    <div style={{ background: C.paper, ...sectionPad, fontFamily: font }}>
      <div style={innerMax}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', paddingBottom: 24, marginBottom: 32, borderBottom: `1px solid ${C.border}` }}>
          <div>
            <Eyebrow text={section.eyebrow} />
            <H2 text={section.h2 || 'Case'} />
            {section.body && <p style={{ fontSize: 14, color: C.muted }}>{section.body}</p>}
          </div>
        </div>
        <div style={{ display: 'grid', gridTemplateColumns: `repeat(${cols}, minmax(0, 1fr))`, gap: 24 }}>
          {Array.from({ length: cols }).map((_, i) => <CaseCard key={i} index={i + 1} />)}
        </div>
      </div>
    </div>
  );
}

export function NewsGridPreview({ section }: { section: NewsGridSection }) {
  const cols = Number(section.columns) || 3;
  return (
    <div style={{ background: C.white, ...sectionPad, fontFamily: font }}>
      <div style={innerMax}>
        <Eyebrow text={section.eyebrow} />
        <H2 text={section.h2} />
        <div style={{ display: 'grid', gridTemplateColumns: `repeat(${cols}, minmax(0, 1fr))`, gap: 24, marginTop: section.h2 ? 32 : 0 }}>
          {Array.from({ length: cols }).map((_, i) => (
            <div key={i} style={{ background: C.white, border: `1px solid ${C.border}`, borderRadius: 2, overflow: 'hidden' }}>
              <div style={{ aspectRatio: '16/10', background: navyGradient, position: 'relative' }}>
                <span style={{ position: 'absolute', top: 12, left: 12, fontSize: 10, background: 'rgba(17,50,93,0.08)', color: C.navy, textTransform: 'uppercase', fontWeight: 700, padding: '3px 8px', borderRadius: 2 }}>
                  Kategori
                </span>
              </div>
              <div style={{ padding: '16px 18px 20px' }}>
                <p style={{ fontWeight: 700, fontSize: 17, color: C.navy, lineHeight: 1.3, marginBottom: 8 }}>Nyhetsrubrik #{i + 1}</p>
                <p style={{ fontSize: 13, color: C.muted, marginBottom: 12 }}>Ingress till artikeln…</p>
                <p style={{ fontSize: 13, color: C.orange, fontWeight: 600 }}>Läs mer →</p>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

export function CatalogPreview({ section }: { section: CatalogSection }) {
  return (
    <div style={{ background: C.paper, ...sectionPad, fontFamily: font }}>
      <div style={innerMax}>
        <Eyebrow text={section.eyebrow} />
        <H2 text={section.h2} />
        {section.introBody && <Body text={section.introBody} />}
        <div style={{ background: C.white, border: `1px solid ${C.border}`, borderRadius: 2, padding: '40px 32px', textAlign: 'center' }}>
          <p style={{ fontSize: 14, color: C.muted, marginBottom: 8 }}>Katalog med sök och filter renderas i frontend-pluginet.</p>
          <p style={{ fontSize: 11, color: C.muted, opacity: 0.7 }}>
            Källor: {[section.includeProducts && 'produkter', section.includeArticles && 'artiklar'].filter(Boolean).join(', ') || '(ingen vald)'}
            {section.facetFields ? ` · Facets: ${section.facetFields.split('\n').filter(Boolean).join(', ')}` : ''}
          </p>
        </div>
      </div>
    </div>
  );
}

export function TabsPreview({ section }: { section: TabsSection }) {
  const activeTab = section.tabs[0];
  return (
    <div style={{ background: C.white, ...sectionPad, fontFamily: font }}>
      <div style={innerMax}>
        <Eyebrow text={section.eyebrow} />
        <H2 text={section.h2} />
        {section.introBody && <Body text={section.introBody} />}
        <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8, marginBottom: 40 }}>
          {section.tabs.length === 0
            ? <p style={{ fontSize: 13, color: C.muted, fontStyle: 'italic' }}>Inga flikar</p>
            : section.tabs.map((t, i) => (
              <span key={t.clientId} style={{ padding: '9px 18px', borderRadius: 999, border: `2px solid ${C.navy}`, background: i === 0 ? C.navy : 'transparent', color: i === 0 ? C.white : C.navy, fontSize: 14, fontWeight: 600, cursor: 'default' }}>
                {t.name || `Flik ${i + 1}`}
              </span>
            ))}
        </div>
        {activeTab && (
          <div style={{ display: 'grid', gridTemplateColumns: 'minmax(0, 1.05fr) minmax(0, 1fr)', gap: 56, alignItems: 'center' }}>
            <div>
              <Eyebrow text={activeTab.eyebrow} />
              <H2 text={activeTab.h2} />
              <Body text={activeTab.body} />
              <GreenBullets raw={activeTab.bullets} />
              <ButtonRow cta={{ text: activeTab.ctaText, url: activeTab.ctaUrl }} cta2={{ text: activeTab.cta2Text, url: activeTab.cta2Url }} />
            </div>
            <ImageBox url={activeTab.imageUrl} label="Flik-bild" aspectRatio="4/3" withShadow />
          </div>
        )}
      </div>
    </div>
  );
}

function TeamCard({ index }: { index: number }) {
  return (
    <div style={{ background: C.white, border: `1px solid ${C.border}`, borderRadius: 2, padding: '28px 20px', position: 'relative', overflow: 'hidden', textAlign: 'center' }}>
      <div style={{ position: 'absolute', top: 0, left: 0, right: 0, height: 3, background: C.orange }} />
      <div style={{ width: 110, height: 110, borderRadius: '50%', background: navyGradient, margin: '8px auto 16px' }} />
      <p style={{ fontWeight: 700, fontSize: 15, color: C.navy, marginBottom: 4 }}>Medarbetare {index}</p>
      <p style={{ fontSize: 13, color: C.muted, marginBottom: 16 }}>Titel</p>
      <div style={{ borderTop: `1px solid ${C.border}`, paddingTop: 14 }}>
        <p style={{ fontSize: 12, color: C.muted, marginBottom: 4 }}>person@wexoe.com</p>
        <p style={{ fontSize: 12, color: C.muted }}>+46 70 000 00 00</p>
      </div>
    </div>
  );
}

function TeamRackRow({ index }: { index: number }) {
  return (
    <div style={{ display: 'flex', alignItems: 'center', gap: 16, padding: '14px 20px', background: C.white, border: `1px solid ${C.border}`, borderRadius: 2 }}>
      <div style={{ width: 64, height: 64, borderRadius: '50%', flexShrink: 0, background: navyGradient }} />
      <div>
        <p style={{ fontWeight: 700, fontSize: 14, color: C.navy, marginBottom: 2 }}>Medarbetare {index}</p>
        <p style={{ fontSize: 13, color: C.muted, marginBottom: 4 }}>Titel</p>
        <p style={{ fontSize: 12, color: C.muted }}>person@wexoe.com</p>
      </div>
    </div>
  );
}

function TeamCompactPill({ index }: { index: number }) {
  return (
    <div style={{ display: 'flex', alignItems: 'center', gap: 10, padding: '8px 14px', background: C.navyFaint, borderRadius: 999 }}>
      <div style={{ width: 32, height: 32, borderRadius: '50%', flexShrink: 0, background: navyGradient }} />
      <div>
        <p style={{ fontSize: 13, fontWeight: 600, color: C.navy, lineHeight: 1.2, marginBottom: 1 }}>Medarbetare {index}</p>
        <p style={{ fontSize: 11, color: C.muted }}>Titel</p>
      </div>
    </div>
  );
}

export function TeamGridPreview({ section }: { section: TeamGridSection }) {
  const variant = section.variant || 'cards';
  return (
    <div style={{ background: C.white, ...sectionPad, fontFamily: font }}>
      <div style={innerMax}>
        <Eyebrow text={section.eyebrow} />
        <H2 text={section.h2} />
        {section.body && <Body text={section.body} />}
        {variant === 'cards' && (
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(240px, 1fr))', gap: 24 }}>
            {[1, 2, 3, 4].map(i => <TeamCard key={i} index={i} />)}
          </div>
        )}
        {variant === 'rack' && (
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(320px, 1fr))', gap: 16 }}>
            {[1, 2, 3, 4].map(i => <TeamRackRow key={i} index={i} />)}
          </div>
        )}
        {variant === 'compact' && (
          <div style={{ display: 'flex', flexWrap: 'wrap', gap: 10 }}>
            {[1, 2, 3, 4, 5, 6].map(i => <TeamCompactPill key={i} index={i} />)}
          </div>
        )}
      </div>
    </div>
  );
}

export function PartnerListPreview({ section }: { section: PartnerListSection }) {
  const variant = section.variant || 'grid';
  return (
    <div style={{ background: C.paper, ...sectionPad, fontFamily: font }}>
      <div style={innerMax}>
        <Eyebrow text={section.eyebrow} />
        <H2 text={section.h2} />
        {section.body && <Body text={section.body} />}
        {variant === 'marquee' && (
          <div style={{ display: 'flex', flexWrap: 'wrap', gap: 20, alignItems: 'center' }}>
            {[1, 2, 3, 4, 5, 6].map(i => (
              <div key={i} style={{ width: 120, height: 44, background: C.border, borderRadius: 2, opacity: 0.65, filter: 'grayscale(1)' }} />
            ))}
          </div>
        )}
        {variant === 'grid' && (
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(170px, 1fr))', gap: 16 }}>
            {[1, 2, 3, 4, 5, 6].map(i => (
              <div key={i} style={{ background: C.white, border: `1px solid ${C.border}`, borderRadius: 2, aspectRatio: '3/2', display: 'flex', alignItems: 'center', justifyContent: 'center', opacity: 0.65, filter: 'grayscale(1)' }}>
                <p style={{ fontSize: 11, color: C.muted, textTransform: 'uppercase', letterSpacing: '0.1em' }}>Logo {i}</p>
              </div>
            ))}
          </div>
        )}
        {variant === 'list' && (
          <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
            {[1, 2, 3].map(i => (
              <div key={i} style={{ display: 'grid', gridTemplateColumns: '120px 1fr auto', gap: 24, alignItems: 'center', padding: '16px 20px', background: C.white, border: `1px solid ${C.border}`, borderRadius: 2 }}>
                <div style={{ height: 36, background: C.border, borderRadius: 2, opacity: 0.5 }} />
                <div>
                  <p style={{ fontWeight: 600, fontSize: 14, color: C.navy, marginBottom: 2 }}>Partnernamn {i}</p>
                  <p style={{ fontSize: 12, color: C.muted }}>Beskrivning av partnern</p>
                </div>
                <span style={{ fontSize: 12, color: C.orange, fontWeight: 600, whiteSpace: 'nowrap' }}>Läs mer →</span>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}

export function FaqPreview({ section }: { section: FaqSection }) {
  // FAS 3: items är nu en strukturerad FaqItem[] (tidigare en rå Q:/A:-sträng).
  const items = section.items.map((it) => ({ q: it.question, a: it.answer }));

  return (
    <div style={{ background: C.paper, ...sectionPad, fontFamily: font }}>
      <div style={{ maxWidth: 800, margin: '0 auto' }}>
        <Eyebrow text={section.eyebrow} />
        <H2 text={section.h2} />
        {section.body && <Body text={section.body} />}
        <div style={{ display: 'flex', flexDirection: 'column', gap: 8, marginTop: section.h2 ? 32 : 0 }}>
          {items.length === 0
            ? <p style={{ fontSize: 13, color: C.muted, fontStyle: 'italic' }}>Inga frågor</p>
            : items.map((it, i) => (
              <div key={i} style={{ background: C.white, border: `1px solid ${C.border}`, borderRadius: 2, overflow: 'hidden' }}>
                <div style={{ padding: '18px 22px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                  <p style={{ fontWeight: 600, fontSize: 16, color: C.navy, lineHeight: 1.35 }}>{it.q || '(fråga saknas)'}</p>
                  <span style={{ flexShrink: 0, marginLeft: 16, color: C.navy, fontWeight: 700, fontSize: 20, lineHeight: 1 }}>
                    {i === 0 ? '−' : '+'}
                  </span>
                </div>
                {i === 0 && (
                  <div style={{ padding: '0 22px 18px', borderTop: `1px solid ${C.border}` }}>
                    <p style={{ fontSize: 15, color: C.muted, lineHeight: 1.7 }}>{it.a || '(svar saknas)'}</p>
                  </div>
                )}
              </div>
            ))}
        </div>
      </div>
    </div>
  );
}

export function TestimonialPreview({ section }: { section: TestimonialSection }) {
  return (
    <div style={{ background: C.paper, ...sectionPad, fontFamily: font }}>
      <div style={{ maxWidth: 760, margin: '0 auto', textAlign: 'center' }}>
        <Eyebrow text={section.eyebrow} bookend />
        <blockquote style={{ fontSize: 'clamp(1.375rem, 2.4vw, 1.75rem)', fontWeight: 500, color: C.navy, lineHeight: 1.5, maxWidth: '36ch', margin: '0 auto 28px', whiteSpace: 'pre-line' }}>
          &ldquo;{section.quote || '(citat saknas)'}&rdquo;
        </blockquote>
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 12 }}>
          {section.authorImageUrl
            ? (
              // eslint-disable-next-line @next/next/no-img-element
              <img src={section.authorImageUrl} alt="" style={{ width: 48, height: 48, borderRadius: '50%', objectFit: 'cover', flexShrink: 0 }} />
            )
            : <div style={{ width: 48, height: 48, borderRadius: '50%', background: navyGradient, flexShrink: 0 }} />
          }
          <div style={{ textAlign: 'left' }}>
            <p style={{ fontSize: 15, fontWeight: 700, color: C.navy }}>{section.authorName || '(namn)'}</p>
            <p style={{ fontSize: 13, color: C.muted }}>{section.authorTitle}</p>
          </div>
        </div>
      </div>
    </div>
  );
}

export function CtaBannerPreview({ section }: { section: CtaBannerSection }) {
  const hasImage = !!section.imageUrl;
  return (
    <div style={{ background: C.paper, ...sectionPad, fontFamily: font }}>
      <div style={innerMax}>
        <div style={{ background: navyGradient, padding: '56px 48px', borderRadius: 2, boxShadow: '0 24px 60px rgba(10,26,46,0.18)', position: 'relative', overflow: 'hidden', textAlign: hasImage ? 'left' : 'center' }}>
          <div style={{ position: 'absolute', top: '50%', left: '50%', transform: 'translate(-50%, -50%)', width: 700, height: 700, borderRadius: '50%', background: 'radial-gradient(circle, rgba(255,255,255,0.04) 0%, transparent 65%)', pointerEvents: 'none' }} />
          <div style={{ position: 'relative', zIndex: 1, display: hasImage ? 'grid' : 'block', gridTemplateColumns: hasImage ? 'minmax(0, 1fr) minmax(0, 1fr)' : undefined, gap: hasImage ? 56 : undefined, alignItems: 'center' }}>
            <div>
              <Eyebrow text={section.eyebrow} light />
              <h2 style={{ fontFamily: font, fontSize: 'clamp(1.75rem, 3.5vw, 2.4rem)', fontWeight: 700, color: C.white, marginBottom: 16, lineHeight: 1.15 }}>
                {section.h2 || '(CTA-rubrik)'}
              </h2>
              {section.body && (
                <p style={{ fontSize: 16, color: 'rgba(255,255,255,0.80)', lineHeight: 1.7, maxWidth: hasImage ? undefined : 560, margin: hasImage ? undefined : '0 auto' }}>
                  {section.body}
                </p>
              )}
              <ButtonRow cta={{ text: section.ctaText, url: section.ctaUrl }} cta2={{ text: section.cta2Text, url: section.cta2Url }} light centered={!hasImage} />
            </div>
            {hasImage && <ImageBox url={section.imageUrl} label="CTA-bild" aspectRatio="4/3" withShadow />}
          </div>
        </div>
      </div>
    </div>
  );
}

function ContactTextCol({ section }: { section: ContactFormSection }) {
  return (
    <div>
      <Eyebrow text={section.eyebrow} light />
      {section.title && (
        <h2 style={{ fontFamily: font, fontSize: 'clamp(1.75rem, 3.5vw, 2.4rem)', fontWeight: 700, color: C.white, marginBottom: 16, lineHeight: 1.15 }}>
          {section.title}
        </h2>
      )}
      {section.subtitle && (
        <p style={{ fontSize: 16, color: 'rgba(255,255,255,0.80)', lineHeight: 1.7 }}>
          {section.subtitle}
        </p>
      )}
      {section.showContactPerson && (
        <div style={{ marginTop: 28, display: 'flex', alignItems: 'center', gap: 14 }}>
          <div style={{ width: 52, height: 52, borderRadius: '50%', background: 'rgba(255,255,255,0.10)', flexShrink: 0 }} />
          <div>
            <p style={{ fontSize: 14, fontWeight: 600, color: C.white, marginBottom: 2 }}>Kontaktperson</p>
            <p style={{ fontSize: 12, color: 'rgba(255,255,255,0.60)' }}>Hämtas automatiskt</p>
          </div>
        </div>
      )}
    </div>
  );
}

function ContactFormFields({ section }: { section: ContactFormSection }) {
  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
        <div style={{ background: 'rgba(255,255,255,0.08)', borderRadius: 2, padding: '12px 14px', fontSize: 13, color: 'rgba(255,255,255,0.40)' }}>Namn</div>
        <div style={{ background: 'rgba(255,255,255,0.08)', borderRadius: 2, padding: '12px 14px', fontSize: 13, color: 'rgba(255,255,255,0.40)' }}>E-post</div>
        {section.showCompany && <div style={{ background: 'rgba(255,255,255,0.08)', borderRadius: 2, padding: '12px 14px', fontSize: 13, color: 'rgba(255,255,255,0.40)' }}>Företag</div>}
        {section.showPhone && <div style={{ background: 'rgba(255,255,255,0.08)', borderRadius: 2, padding: '12px 14px', fontSize: 13, color: 'rgba(255,255,255,0.40)' }}>Telefon</div>}
      </div>
      {section.showDropdown && (
        <div style={{ background: 'rgba(255,255,255,0.08)', borderRadius: 2, padding: '12px 14px', fontSize: 13, color: 'rgba(255,255,255,0.40)' }}>
          {section.dropdownLabel || 'Ärende'}
        </div>
      )}
      <div style={{ background: 'rgba(255,255,255,0.08)', borderRadius: 2, padding: '12px 14px', fontSize: 13, color: 'rgba(255,255,255,0.40)', minHeight: 80 }}>
        {section.messageLabel || 'Meddelande'}
      </div>
      {section.trustSignals && (
        <p style={{ fontSize: 11, color: 'rgba(255,255,255,0.40)' }}>
          {section.trustSignals.split('\n').filter(Boolean).join(' · ')}
        </p>
      )}
      <div>
        <span style={{ display: 'inline-block', background: C.orange, color: C.white, fontSize: 14, fontWeight: 600, padding: '14px 28px', borderRadius: 2 }}>
          {section.ctaText || 'Skicka'}
        </span>
      </div>
    </div>
  );
}

export function ContactFormPreview({ section }: { section: ContactFormSection }) {
  const split = section.cfLayout === 'split';

  return (
    <div style={{ background: C.navy, ...sectionPad, fontFamily: font }}>
      {split ? (
        <div style={{ ...innerMax, display: 'grid', gridTemplateColumns: 'minmax(0,1fr) minmax(0,1fr)', gap: 56 }}>
          <ContactTextCol section={section} />
          <ContactFormFields section={section} />
        </div>
      ) : (
        <div style={{ maxWidth: 640, margin: '0 auto' }}>
          <ContactTextCol section={section} />
          <div style={{ marginTop: 32 }}>
            <ContactFormFields section={section} />
          </div>
        </div>
      )}
    </div>
  );
}
