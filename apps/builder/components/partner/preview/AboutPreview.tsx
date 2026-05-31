'use client';

import { PartnerPageState, PartnerPageSectionId } from '@/lib/partner-types';
import { PreviewSection, renderInlineMarkdown, WEXOE_COLORS } from './shared';

interface Props {
  state: PartnerPageState;
  active: PartnerPageSectionId | null;
  onSelect: (id: PartnerPageSectionId) => void;
}

export default function AboutPreview({ state, active, onSelect }: Props) {
  const eyebrow = state.aboutEyebrow.trim();
  const h2 = state.aboutH2.trim();
  const text = state.aboutText.trim();
  const image = state.aboutImageUrl.trim();
  const badgeValue = state.aboutBadgeValue.trim();
  const badgeLabel = state.aboutBadgeLabel.trim();

  // Renderar text som ren markdown — pluginet använder Markdown::to_html.
  // Preview gör en enkel inline-version som matchar "synligt nog för
  // marknadsföraren" utan att duplicera hela markdown-parsern.
  const paragraphs = text.split(/\r?\n\r?\n/).map((p) => p.trim()).filter(Boolean);

  return (
    <PreviewSection
      id="about"
      active={active}
      onClick={onSelect}
      style={{ background: '#fff', padding: '64px 40px' }}
    >
      <div
        style={{
          display: 'grid',
          gridTemplateColumns: '1.1fr 1fr',
          gap: 56,
          alignItems: 'center',
        }}
      >
        <div>
          {eyebrow && (
            <div
              style={{
                fontSize: 11,
                fontWeight: 700,
                letterSpacing: 1.2,
                textTransform: 'uppercase',
                color: WEXOE_COLORS.secondary,
                marginBottom: 12,
              }}
            >
              {eyebrow}
            </div>
          )}
          {h2 && (
            <h2
              style={{
                fontSize: 28,
                fontWeight: 700,
                color: WEXOE_COLORS.main,
                lineHeight: 1.25,
                margin: '0 0 16px 0',
              }}
            >
              {h2}
            </h2>
          )}
          {paragraphs.length === 0 ? (
            <p style={{ fontSize: 13, color: WEXOE_COLORS.textMuted, fontStyle: 'italic' }}>
              Brödtext saknas.
            </p>
          ) : (
            <div style={{ fontSize: 14, lineHeight: 1.7, color: WEXOE_COLORS.text }}>
              {paragraphs.map((p, i) => (
                <p key={i} style={{ margin: '0 0 12px 0' }}>
                  {renderInlineMarkdown(p)}
                </p>
              ))}
            </div>
          )}
        </div>

        <div style={{ position: 'relative' }}>
          {image ? (
            <div
              style={{
                borderRadius: 8,
                overflow: 'hidden',
                aspectRatio: '4 / 3',
                background: `center/cover url("${image}")`,
              }}
            />
          ) : (
            <div
              style={{
                borderRadius: 8,
                aspectRatio: '4 / 3',
                background: 'rgba(17,50,93,0.04)',
                border: '2px dashed rgba(17,50,93,0.15)',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                color: WEXOE_COLORS.textMuted,
                fontSize: 12,
              }}
            >
              About-bild
            </div>
          )}
          {badgeValue && (
            <div
              style={{
                position: 'absolute',
                bottom: -20,
                left: -20,
                background: WEXOE_COLORS.main,
                color: '#fff',
                padding: '14px 20px',
                borderRadius: 8,
                boxShadow: '0 8px 24px rgba(17,50,93,0.2)',
              }}
            >
              <div style={{ fontSize: 22, fontWeight: 700, lineHeight: 1 }}>{badgeValue}</div>
              {badgeLabel && (
                <div style={{ fontSize: 11, opacity: 0.8, marginTop: 4 }}>{badgeLabel}</div>
              )}
            </div>
          )}
        </div>
      </div>
    </PreviewSection>
  );
}
