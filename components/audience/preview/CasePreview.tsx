import { AudienceState, AudienceSectionId } from '@/lib/audience-types';

interface Props {
  state: AudienceState;
  active: AudienceSectionId | null;
  onSelect: (id: AudienceSectionId) => void;
}

/**
 * Renders the case card. Composed inside the value section's right column
 * by `AudiencePreviewPanel` to match the live page's two-column layout.
 */
export default function CasePreview({ state, active, onSelect }: Props) {
  const isActive = active === 'case';
  const hasTitle = !!state.caseTitle.trim();
  const hasDescription = !!state.caseDescription.trim();
  const hasResult = !!state.caseResult.trim();
  const hasLink = !!state.caseLinkUrl.trim() && state.caseLinkUrl !== '#';
  return (
    <div
      data-section="case"
      onClick={(e) => {
        e.stopPropagation();
        onSelect('case');
      }}
      className={`preview-section ${isActive ? 'active' : ''}`}
      style={{
        background: '#fff',
        borderRadius: 16,
        padding: 28,
        boxShadow: '0 4px 20px rgba(0,0,0,0.06)',
        cursor: 'pointer',
      }}
    >
      <div
        style={{
          fontSize: 11,
          fontWeight: 700,
          textTransform: 'uppercase',
          letterSpacing: 1,
          color: '#F28C28',
          marginBottom: 10,
        }}
      >
        Kundcase
      </div>
      <div
        style={{
          fontSize: 17,
          fontWeight: 600,
          color: '#11325D',
          lineHeight: 1.4,
          marginBottom: 10,
          opacity: hasTitle ? 1 : 0.5,
        }}
      >
        {hasTitle ? state.caseTitle : 'Din case-titel'}
      </div>
      <p
        style={{
          fontSize: 14,
          color: '#666',
          lineHeight: 1.6,
          margin: '0 0 14px 0',
          opacity: hasDescription ? 1 : 0.5,
        }}
      >
        {hasDescription
          ? state.caseDescription
          : 'Kort beskrivning av caset — vad de gjorde, hur ni hjälpte, och resultatet.'}
      </p>
      {hasResult && (
        <div
          style={{
            display: 'flex',
            alignItems: 'center',
            gap: 8,
            fontSize: 14,
            fontWeight: 600,
            color: '#10B981',
            marginBottom: 8,
          }}
        >
          <span aria-hidden>✓</span>
          {state.caseResult}
        </div>
      )}
      {hasLink && (
        <div
          style={{
            marginTop: 12,
            display: 'inline-flex',
            alignItems: 'center',
            gap: 6,
            fontSize: 14,
            fontWeight: 600,
            color: '#11325D',
          }}
        >
          {state.caseLinkText || 'Läs mer'}
          <span aria-hidden>→</span>
        </div>
      )}
    </div>
  );
}
