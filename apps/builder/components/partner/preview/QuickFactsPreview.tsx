'use client';

import { PartnerPageState, PartnerPageSectionId } from '@/lib/partner-types';
import { PreviewSection, WEXOE_COLORS } from './shared';

interface Props {
  state: PartnerPageState;
  active: PartnerPageSectionId | null;
  onSelect: (id: PartnerPageSectionId) => void;
}

/**
 * Quick-facts som en slim ljus strip. Återger samma sektion-villkor som
 * Renderer.php::render_quick_facts — tomma slots (alla tre fält tomma)
 * filtreras bort tyst.
 */
export default function QuickFactsPreview({ state, active, onSelect }: Props) {
  const visibleFacts = state.facts.filter(
    (f) => f.icon !== '' || f.value.trim() !== '' || f.label.trim() !== '',
  );
  if (visibleFacts.length === 0) {
    return (
      <PreviewSection
        id="quickFacts"
        active={active}
        onClick={onSelect}
        style={{
          background: WEXOE_COLORS.light,
          padding: '24px 40px',
          borderBottom: `1px solid ${WEXOE_COLORS.border}`,
        }}
      >
        <p style={{ fontSize: 12, color: WEXOE_COLORS.textMuted, fontStyle: 'italic', margin: 0 }}>
          Inga snabbfakta ifyllda — sektionen döljs på publika sidan.
        </p>
      </PreviewSection>
    );
  }

  return (
    <PreviewSection
      id="quickFacts"
      active={active}
      onClick={onSelect}
      style={{
        background: WEXOE_COLORS.light,
        padding: '24px 40px',
        borderBottom: `1px solid ${WEXOE_COLORS.border}`,
      }}
    >
      <div
        style={{
          display: 'grid',
          gridTemplateColumns: `repeat(${Math.min(visibleFacts.length, 4)}, 1fr)`,
          gap: 24,
        }}
      >
        {visibleFacts.map((fact, i) => (
          <div key={i} style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
            <div
              style={{
                width: 22,
                height: 22,
                opacity: 0.35,
                color: WEXOE_COLORS.main,
                fontSize: 11,
                fontFamily: 'monospace',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                background: 'rgba(17,50,93,0.06)',
                borderRadius: 4,
                flexShrink: 0,
              }}
              title={`Ikon: ${fact.icon || '(ingen)'}`}
            >
              {fact.icon ? fact.icon.slice(0, 3) : '—'}
            </div>
            <div>
              {fact.value && (
                <div
                  style={{
                    fontSize: 20,
                    fontWeight: 700,
                    color: WEXOE_COLORS.main,
                    lineHeight: 1,
                    marginBottom: 4,
                  }}
                >
                  {fact.value}
                </div>
              )}
              {fact.label && (
                <div style={{ fontSize: 12, color: WEXOE_COLORS.textMuted }}>{fact.label}</div>
              )}
            </div>
          </div>
        ))}
      </div>
    </PreviewSection>
  );
}
