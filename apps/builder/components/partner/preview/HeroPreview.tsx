'use client';

import { PartnerPageState, PartnerPageSectionId } from '@/lib/partner-types';
import { useLinkedRecord } from '@/lib/linked-records-cache';
import { PreviewSection, renderInlineMarkdown, WEXOE_COLORS } from './shared';

interface Props {
  state: PartnerPageState;
  active: PartnerPageSectionId | null;
  onSelect: (id: PartnerPageSectionId) => void;
}

/**
 * Hero med supplier-lockup: partner-logo (från core_partners-recordet)
 * stackad ovanför H1. Tagline + CTA-knappar + hero-image till höger.
 * Speglar Renderer.php::render_hero.
 */
export default function HeroPreview({ state, active, onSelect }: Props) {
  const partner = useLinkedRecord('core_partners', state.partnerIds[0] ?? null);
  const partnerLogo = typeof partner?.logo_url === 'string' ? partner.logo_url : '';

  const eyebrow = state.heroEyebrow.trim();
  const h1 = state.h1.trim();
  const tagline = state.heroTagline.trim();
  const ctaText = state.heroCtaText.trim() || 'Kontakta oss';
  const cta2Text = state.heroCta2Text.trim();
  const cta2Url = state.heroCta2Url.trim();
  const hasCta2 = cta2Text !== '' && cta2Url !== '';
  const heroImage = state.heroImageUrl.trim();

  return (
    <PreviewSection
      id="hero"
      active={active}
      onClick={onSelect}
      style={{
        background: WEXOE_COLORS.main,
        color: '#fff',
        position: 'relative',
        overflow: 'hidden',
        minHeight: 380,
      }}
    >
      <div
        style={{
          display: 'grid',
          gridTemplateColumns: heroImage ? '1fr 1fr' : '1fr',
          gap: 32,
          alignItems: 'center',
        }}
      >
        <div style={{ padding: '56px 40px' }}>
          {eyebrow && (
            <div
              style={{
                display: 'inline-flex',
                alignItems: 'center',
                gap: 8,
                fontSize: 12,
                fontWeight: 600,
                letterSpacing: 0.5,
                textTransform: 'uppercase',
                padding: '6px 14px',
                borderRadius: 50,
                background: 'rgba(255,255,255,0.1)',
                border: '1px solid rgba(255,255,255,0.15)',
                marginBottom: 22,
              }}
            >
              ✓ {eyebrow}
            </div>
          )}

          <div
            style={{
              background: '#fff',
              padding: '12px 18px',
              borderRadius: 8,
              display: 'inline-block',
              marginBottom: 22,
              minWidth: 120,
              maxWidth: 200,
              height: 56,
              ...(partnerLogo
                ? {
                    backgroundImage: `url("${partnerLogo}")`,
                    backgroundRepeat: 'no-repeat',
                    backgroundPosition: 'center',
                    backgroundSize: 'contain',
                    backgroundOrigin: 'content-box',
                  }
                : {}),
            }}
          >
            {!partnerLogo && (
              <div
                style={{
                  fontSize: 11,
                  color: '#6B7280',
                  fontFamily: 'monospace',
                  textAlign: 'center',
                  paddingTop: 10,
                }}
              >
                {state.partnerIds.length === 0
                  ? 'Välj partner i Inställningar'
                  : 'Logo laddas…'}
              </div>
            )}
          </div>

          <h1
            style={{
              fontSize: 36,
              fontWeight: 700,
              lineHeight: 1.15,
              margin: '0 0 14px 0',
              opacity: h1 ? 1 : 0.5,
            }}
          >
            {h1 || 'Partnerns namn (H1)'}
          </h1>

          {tagline && (
            <p
              style={{
                fontSize: 15,
                lineHeight: 1.6,
                opacity: 0.9,
                margin: '0 0 26px 0',
                maxWidth: 460,
              }}
            >
              {renderInlineMarkdown(tagline)}
            </p>
          )}

          <div style={{ display: 'flex', gap: 10, flexWrap: 'wrap' }}>
            <span
              style={{
                display: 'inline-flex',
                alignItems: 'center',
                gap: 6,
                background: WEXOE_COLORS.secondary,
                color: '#fff',
                padding: '11px 24px',
                borderRadius: 4,
                fontWeight: 600,
                fontSize: 14,
              }}
            >
              {ctaText} →
            </span>
            {hasCta2 && (
              <span
                style={{
                  display: 'inline-flex',
                  alignItems: 'center',
                  gap: 6,
                  background: 'transparent',
                  color: '#fff',
                  padding: '11px 24px',
                  borderRadius: 4,
                  fontWeight: 600,
                  fontSize: 14,
                  border: '2px solid rgba(255,255,255,0.4)',
                }}
              >
                {cta2Text} →
              </span>
            )}
          </div>
        </div>

        {heroImage && (
          <div
            style={{
              height: '100%',
              minHeight: 380,
              background: `center/cover url("${heroImage}")`,
            }}
          />
        )}
      </div>
    </PreviewSection>
  );
}
