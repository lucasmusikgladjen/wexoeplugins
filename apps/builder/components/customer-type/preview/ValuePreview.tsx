import { CustomerTypePageState, CustomerTypePageSectionId } from '@/lib/customer-type-types';
import { PreviewSection, renderInlineMarkdown } from './shared';

interface Props {
  state: CustomerTypePageState;
  active: CustomerTypePageSectionId | null;
  onSelect: (id: CustomerTypePageSectionId) => void;
}

export default function ValuePreview({ state, active, onSelect }: Props) {
  const hasH2 = !!state.valueH2.trim();
  const hasText1 = !!state.valueText1.trim();
  const hasText2 = !!state.valueText2.trim();
  const benefits = [state.benefit1, state.benefit2, state.benefit3].filter((b) => b.trim());
  const hasBenefits = benefits.length > 0;

  return (
    <PreviewSection
      id="value"
      active={active}
      onClick={onSelect}
      style={{ background: '#f8f9fa', color: '#11325D' }}
    >
      <div
        style={{
          maxWidth: 1270,
          margin: '0 auto',
          padding: '80px 40px',
          display: 'grid',
          gridTemplateColumns: '1fr',
          gap: 60,
          alignItems: 'start',
        }}
      >
        <div>
          <h2
            style={{
              fontSize: 34,
              fontWeight: 700,
              lineHeight: 1.2,
              margin: '0 0 20px 0',
              color: '#11325D',
              opacity: hasH2 ? 1 : 0.45,
            }}
          >
            {hasH2 ? state.valueH2 : 'Din rubrik här'}
          </h2>

          <p
            style={{
              fontSize: 16,
              lineHeight: 1.75,
              color: '#555',
              margin: '0 0 14px 0',
              opacity: hasText1 ? 1 : 0.5,
            }}
          >
            {hasText1
              ? renderInlineMarkdown(state.valueText1)
              : 'Första paragrafen om vad ni levererar och varför det är värdefullt för målgruppen.'}
          </p>

          {(hasText2 || !hasText1) && (
            <p
              style={{
                fontSize: 16,
                lineHeight: 1.75,
                color: '#555',
                margin: '0 0 14px 0',
                opacity: hasText2 ? 1 : 0.5,
              }}
            >
              {hasText2
                ? renderInlineMarkdown(state.valueText2)
                : 'Andra paragrafen (valfri) — fördjupa eller koppla till case.'}
            </p>
          )}

          <ul
            style={{
              listStyle: 'none',
              margin: '24px 0 0 0',
              padding: 0,
              display: 'flex',
              flexDirection: 'column',
              gap: 14,
            }}
          >
            {(hasBenefits ? benefits : ['**Benefit ett** kort beskrivning', '**Benefit två**', '**Benefit tre**']).map(
              (b, i) => (
                <li
                  key={i}
                  style={{
                    display: 'flex',
                    alignItems: 'flex-start',
                    gap: 12,
                    fontSize: 15,
                    color: '#333',
                    opacity: hasBenefits ? 1 : 0.4,
                  }}
                >
                  <span
                    aria-hidden
                    style={{
                      flexShrink: 0,
                      width: 22,
                      height: 22,
                      background: '#10B981',
                      color: '#fff',
                      borderRadius: '50%',
                      display: 'inline-flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      fontSize: 13,
                      fontWeight: 700,
                      marginTop: 1,
                    }}
                  >
                    ✓
                  </span>
                  <span>{renderInlineMarkdown(b)}</span>
                </li>
              ),
            )}
          </ul>
        </div>

      </div>
    </PreviewSection>
  );
}
