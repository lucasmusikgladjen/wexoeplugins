'use client';

import { PartnerPageState, PartnerPageSectionId } from '@/lib/partner-types';
import { useLinkedRecords } from '@/lib/linked-records-cache';
import { PreviewSection, renderInlineMarkdown, WEXOE_COLORS } from './shared';

interface Props {
  state: PartnerPageState;
  active: PartnerPageSectionId | null;
  onSelect: (id: PartnerPageSectionId) => void;
}

/**
 * "Varför Wexoe"-sektionen — vänster: case-stack (max 3) eller
 * contact-fallback. Höger: copy + benefits-lista.
 *
 * Pluginet slicear case-listan till 3 i Renderer.php — vi gör samma här
 * så preview matchar produktionssidan.
 */
export default function WhyWexoePreview({ state, active, onSelect }: Props) {
  const cases = useLinkedRecords('cases', state.caseIds)
    .filter((r) => r.is_active !== false)
    .slice(0, 3);

  const benefits = state.whyBenefits
    .map((b) => b.trim())
    .filter((b) => b !== '');

  const viewAllText = state.casesViewAllText.trim();
  const viewAllUrl = state.casesViewAllUrl.trim();
  const showViewAll = viewAllText !== '' && viewAllUrl !== '';

  const fallback = cases.length === 0;

  return (
    <PreviewSection
      id="whyWexoe"
      active={active}
      onClick={onSelect}
      style={{ background: WEXOE_COLORS.light, padding: '64px 40px' }}
    >
      <div
        style={{
          display: 'grid',
          gridTemplateColumns: '1fr 1fr',
          gap: 48,
          alignItems: 'start',
        }}
      >
        <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
          {fallback ? (
            <ContactFallback state={state} />
          ) : (
            <>
              {cases.map((c, i) => (
                <CaseCard
                  key={(c._recordId as string) || i}
                  image={typeof c.lead_image_url === 'string' ? c.lead_image_url : ''}
                  title={typeof c.title === 'string' ? c.title : ''}
                  description={typeof c.subtitle === 'string' ? c.subtitle : ''}
                />
              ))}
              {showViewAll && (
                <span
                  style={{
                    display: 'inline-flex',
                    alignItems: 'center',
                    gap: 5,
                    fontSize: 13,
                    fontWeight: 600,
                    color: WEXOE_COLORS.main,
                    marginTop: 8,
                    paddingBottom: 1,
                    borderBottom: '2px solid transparent',
                    alignSelf: 'flex-start',
                  }}
                >
                  {viewAllText} →
                </span>
              )}
            </>
          )}
        </div>

        <div>
          {state.whyH2.trim() && (
            <h2
              style={{
                fontSize: 28,
                fontWeight: 700,
                color: WEXOE_COLORS.main,
                lineHeight: 1.25,
                margin: '0 0 16px 0',
              }}
            >
              {state.whyH2}
            </h2>
          )}
          {state.whyText.trim() && (
            <p
              style={{
                fontSize: 14,
                lineHeight: 1.7,
                color: WEXOE_COLORS.textMuted,
                margin: '0 0 22px 0',
              }}
            >
              {renderInlineMarkdown(state.whyText)}
            </p>
          )}
          {benefits.length > 0 && (
            <ul style={{ listStyle: 'none', padding: 0, margin: 0 }}>
              {benefits.map((b, i) => (
                <li
                  key={i}
                  style={{
                    position: 'relative',
                    paddingLeft: 30,
                    marginBottom: 12,
                    fontSize: 14,
                    fontWeight: 500,
                  }}
                >
                  <span
                    style={{
                      position: 'absolute',
                      left: 0,
                      top: 1,
                      width: 18,
                      height: 18,
                      background: WEXOE_COLORS.green,
                      borderRadius: '50%',
                      opacity: 0.15,
                    }}
                  />
                  <span
                    style={{
                      position: 'absolute',
                      left: 3,
                      top: 4,
                      color: WEXOE_COLORS.green,
                      fontSize: 11,
                      fontWeight: 700,
                    }}
                  >
                    ✓
                  </span>
                  {renderInlineMarkdown(b)}
                </li>
              ))}
            </ul>
          )}
        </div>
      </div>
    </PreviewSection>
  );
}

function CaseCard({
  image,
  title,
  description,
}: {
  image: string;
  title: string;
  description: string;
}) {
  return (
    <div
      style={{
        display: 'grid',
        gridTemplateColumns: 'auto 1fr',
        background: '#fff',
        borderRadius: 10,
        border: `1px solid ${WEXOE_COLORS.border}`,
        overflow: 'hidden',
      }}
    >
      <div style={{ padding: '14px 0 14px 14px' }}>
        <div
          style={{
            width: 80,
            height: 80,
            background: image ? `center/cover url("${image}")` : WEXOE_COLORS.light,
            borderRadius: 6,
          }}
        />
      </div>
      <div style={{ padding: '14px 14px 14px 14px', display: 'flex', flexDirection: 'column', justifyContent: 'center', minWidth: 0 }}>
        <div
          style={{
            fontSize: 13,
            fontWeight: 700,
            color: WEXOE_COLORS.main,
            lineHeight: 1.35,
            marginBottom: 4,
          }}
        >
          {title || '(case utan titel)'}
        </div>
        {description && (
          <div
            style={{
              fontSize: 11.5,
              color: WEXOE_COLORS.textMuted,
              lineHeight: 1.5,
              overflow: 'hidden',
              display: '-webkit-box',
              WebkitLineClamp: 2,
              WebkitBoxOrient: 'vertical' as const,
            }}
          >
            {description}
          </div>
        )}
      </div>
    </div>
  );
}

function ContactFallback({ state }: { state: PartnerPageState }) {
  const hasContent =
    state.contactName.trim() ||
    state.contactEmail.trim() ||
    state.contactPhone.trim() ||
    state.contactQuote.trim();
  if (!hasContent) {
    return (
      <div
        style={{
          background: '#fff',
          border: `1px dashed ${WEXOE_COLORS.border}`,
          borderRadius: 10,
          padding: 24,
          fontSize: 12,
          color: WEXOE_COLORS.textMuted,
          fontStyle: 'italic',
        }}
      >
        Inga case valda. Fyll i Kontaktperson så visas dess kort som
        fallback här på publika sidan.
      </div>
    );
  }
  return (
    <div
      style={{
        background: '#fff',
        border: `1px solid ${WEXOE_COLORS.border}`,
        borderRadius: 10,
        padding: 24,
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'flex-start',
        gap: 12,
      }}
    >
      {state.contactImageUrl.trim() && (
        <div
          style={{
            width: 72,
            height: 72,
            borderRadius: '50%',
            background: `center/cover url("${state.contactImageUrl}")`,
          }}
        />
      )}
      {state.contactName.trim() && (
        <h3 style={{ fontSize: 17, fontWeight: 700, color: WEXOE_COLORS.main, margin: 0 }}>
          {state.contactName}
        </h3>
      )}
      {state.contactTitle.trim() && (
        <div style={{ fontSize: 13, color: WEXOE_COLORS.textMuted, marginTop: -8 }}>
          {state.contactTitle}
        </div>
      )}
      {state.contactQuote.trim() && (
        <div
          style={{
            fontSize: 13,
            fontStyle: 'italic',
            lineHeight: 1.55,
            color: WEXOE_COLORS.main,
            paddingLeft: 14,
            borderLeft: `2px solid ${WEXOE_COLORS.secondary}`,
          }}
        >
          {state.contactQuote}
        </div>
      )}
    </div>
  );
}
