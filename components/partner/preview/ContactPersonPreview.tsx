'use client';

import { PartnerPageState, PartnerPageSectionId } from '@/lib/partner-types';
import { PreviewSection, WEXOE_COLORS } from './shared';

interface Props {
  state: PartnerPageState;
  active: PartnerPageSectionId | null;
  onSelect: (id: PartnerPageSectionId) => void;
}

/**
 * Kontaktperson som fullbredd navy-strip. Pluginet utelämnar sektionen
 * om alla fält (utom titel) är tomma.
 */
export default function ContactPersonPreview({ state, active, onSelect }: Props) {
  const hasContent =
    state.contactName.trim() !== '' ||
    state.contactEmail.trim() !== '' ||
    state.contactPhone.trim() !== '' ||
    state.contactQuote.trim() !== '' ||
    state.contactImageUrl.trim() !== '';

  if (!hasContent) {
    return (
      <PreviewSection
        id="contactPerson"
        active={active}
        onClick={onSelect}
        style={{ background: WEXOE_COLORS.main, color: '#fff', padding: '40px 40px' }}
      >
        <p style={{ fontSize: 13, opacity: 0.7, fontStyle: 'italic', textAlign: 'center', margin: 0 }}>
          Inga kontaktperson-fält ifyllda. Sektionen döljs på publika sidan.
        </p>
      </PreviewSection>
    );
  }

  return (
    <PreviewSection
      id="contactPerson"
      active={active}
      onClick={onSelect}
      style={{ background: WEXOE_COLORS.main, color: '#fff', padding: '44px 40px' }}
    >
      <div
        style={{
          display: 'grid',
          gridTemplateColumns: 'auto 1fr 1.4fr',
          gap: 32,
          alignItems: 'center',
        }}
      >
        <div
          style={{
            width: 92,
            height: 92,
            borderRadius: '50%',
            background: state.contactImageUrl.trim()
              ? `center/cover url("${state.contactImageUrl}")`
              : 'rgba(255,255,255,0.08)',
            border: '3px solid rgba(255,255,255,0.12)',
            flexShrink: 0,
          }}
        />
        <div>
          {state.contactName.trim() && (
            <h3 style={{ fontSize: 18, fontWeight: 700, color: '#fff', margin: '0 0 4px' }}>
              {state.contactName}
            </h3>
          )}
          {state.contactTitle.trim() && (
            <div style={{ fontSize: 13, opacity: 0.7, marginBottom: 12 }}>{state.contactTitle}</div>
          )}
          <div style={{ display: 'flex', gap: 16, flexWrap: 'wrap', fontSize: 12 }}>
            {state.contactEmail.trim() && (
              <span style={{ opacity: 0.85 }}>✉ {state.contactEmail}</span>
            )}
            {state.contactPhone.trim() && (
              <span style={{ opacity: 0.85 }}>☎ {state.contactPhone}</span>
            )}
          </div>
        </div>
        {state.contactQuote.trim() && (
          <div
            style={{
              fontSize: 14,
              fontStyle: 'italic',
              lineHeight: 1.55,
              opacity: 0.92,
              paddingLeft: 22,
              borderLeft: `2px solid ${WEXOE_COLORS.secondary}`,
            }}
          >
            {state.contactQuote}
          </div>
        )}
      </div>
    </PreviewSection>
  );
}
