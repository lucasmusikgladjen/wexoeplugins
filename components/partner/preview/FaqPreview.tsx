'use client';

import { PartnerPageState, PartnerPageSectionId } from '@/lib/partner-types';
import { PreviewSection, WEXOE_COLORS } from './shared';

interface Props {
  state: PartnerPageState;
  active: PartnerPageSectionId | null;
  onSelect: (id: PartnerPageSectionId) => void;
}

/**
 * FAQ-sektion. Pluginet utelämnar sektionen helt om inga giltiga FAQ:er
 * finns (`question` tom = filtreras bort). Preview matchar denna logik.
 *
 * Markdown-svar visas som plain text i preview för enkelhet — pluginet
 * kör Markdown::to_html på live-sidan.
 */
export default function FaqPreview({ state, active, onSelect }: Props) {
  const validFaqs = state.faqs.filter((f) => f.question.trim() !== '');

  if (validFaqs.length === 0) {
    return (
      <PreviewSection
        id="faq"
        active={active}
        onClick={onSelect}
        style={{ background: WEXOE_COLORS.light, padding: '64px 40px' }}
      >
        <p style={{ fontSize: 13, color: WEXOE_COLORS.textMuted, fontStyle: 'italic', textAlign: 'center', margin: 0 }}>
          Inga FAQ:er. Sektionen döljs på publika sidan tills minst en fråga har innehåll.
        </p>
      </PreviewSection>
    );
  }

  return (
    <PreviewSection
      id="faq"
      active={active}
      onClick={onSelect}
      style={{ background: WEXOE_COLORS.light, padding: '64px 40px' }}
    >
      <div style={{ textAlign: 'center', maxWidth: 720, margin: '0 auto 32px' }}>
        <div
          style={{
            fontSize: 11,
            fontWeight: 700,
            letterSpacing: 1.2,
            textTransform: 'uppercase',
            color: WEXOE_COLORS.secondary,
            marginBottom: 10,
          }}
        >
          Vanliga frågor
        </div>
        {state.faqH2.trim() && (
          <h2
            style={{
              fontSize: 26,
              fontWeight: 700,
              color: WEXOE_COLORS.main,
              lineHeight: 1.25,
              margin: 0,
            }}
          >
            {state.faqH2}
          </h2>
        )}
      </div>

      <ul style={{ listStyle: 'none', padding: 0, margin: 0, maxWidth: 760, marginInline: 'auto' }}>
        {validFaqs.map((faq, i) => (
          <li
            key={faq.clientId}
            style={{
              background: '#fff',
              border: `1px solid ${WEXOE_COLORS.border}`,
              borderRadius: 8,
              padding: '14px 18px',
              marginBottom: 8,
            }}
          >
            <div
              style={{
                fontSize: 14,
                fontWeight: 600,
                color: WEXOE_COLORS.main,
                display: 'flex',
                justifyContent: 'space-between',
                gap: 12,
              }}
            >
              <span>{faq.question}</span>
              <span style={{ color: WEXOE_COLORS.textMuted, fontSize: 12 }}>
                {i === 0 ? '▾' : '▸'}
              </span>
            </div>
            {i === 0 && faq.answer.trim() && (
              <div
                style={{
                  marginTop: 10,
                  paddingTop: 10,
                  borderTop: `1px solid ${WEXOE_COLORS.border}`,
                  fontSize: 13,
                  lineHeight: 1.6,
                  color: WEXOE_COLORS.text,
                  whiteSpace: 'pre-wrap',
                }}
              >
                {faq.answer}
              </div>
            )}
          </li>
        ))}
      </ul>
    </PreviewSection>
  );
}
