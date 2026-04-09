import { ProductAreaState, ProductAreaSectionId } from '@/lib/product-area-types';
import { colorOr, textOn } from '@/lib/color-utils';
import { PreviewSection, Arrow } from './shared';

interface Props {
  state: ProductAreaState;
  active: ProductAreaSectionId | null;
  onSelect: (id: ProductAreaSectionId) => void;
}

export default function SolutionsPreview({ state, active, onSelect }: Props) {
  if (state.solutions.length === 0) return null;
  const bg = colorOr(state.solutionsBg, '#FFFFFF');
  const cardBg = colorOr(state.solutionsCardBg, '#FFFFFF');
  const color = textOn(bg);

  return (
    <PreviewSection
      id="solutions"
      active={active}
      onClick={onSelect}
      style={{ background: bg, color }}
    >
      <div style={{ padding: '60px 40px', maxWidth: 1270, margin: '0 auto' }}>
        <h2
          style={{
            fontSize: 30,
            fontWeight: 700,
            color,
            margin: '0 0 32px 0',
            textAlign: 'center',
          }}
        >
          {state.solutionsTitle.trim() || 'Lösningar & koncept'}
        </h2>

        <div
          style={{
            display: 'grid',
            gridTemplateColumns: 'repeat(auto-fit, minmax(240px, 280px))',
            gap: 24,
            justifyContent: 'center',
          }}
        >
          {state.solutions.map((sol) => (
            <div
              key={sol.clientId}
              style={{
                background: cardBg,
                borderRadius: 10,
                overflow: 'hidden',
                boxShadow: '0 1px 6px rgba(0,0,0,0.07)',
                border: '1px solid rgba(0,0,0,0.04)',
                display: 'flex',
                flexDirection: 'column',
              }}
            >
              <div
                style={{
                  aspectRatio: '16 / 9',
                  overflow: 'hidden',
                  background: '#FFFFFF',
                  flexShrink: 0,
                }}
              >
                {sol.image.trim() ? (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img
                    src={sol.image}
                    alt=""
                    style={{
                      width: '100%',
                      height: '100%',
                      objectFit: 'contain',
                      objectPosition: 'center',
                      display: 'block',
                    }}
                  />
                ) : (
                  <div
                    style={{
                      width: '100%',
                      height: '100%',
                      background: '#F8F9FA',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      color: '#D0D4DA',
                      fontSize: 11,
                      letterSpacing: '0.1em',
                      textTransform: 'uppercase',
                    }}
                  >
                    Ingen bild
                  </div>
                )}
              </div>
              <div
                style={{
                  padding: '20px 22px 24px',
                  display: 'flex',
                  flexDirection: 'column',
                  flex: 1,
                }}
              >
                {sol.category.trim() && (
                  <span
                    style={{
                      fontSize: 11,
                      fontWeight: 600,
                      letterSpacing: '0.08em',
                      color: '#777',
                      marginBottom: 6,
                      display: 'block',
                      textTransform: 'uppercase',
                    }}
                  >
                    {sol.category}
                  </span>
                )}
                <div
                  style={{
                    fontSize: 18,
                    fontWeight: 700,
                    color: '#11325D',
                    marginBottom: 10,
                    lineHeight: 1.3,
                  }}
                >
                  {sol.name || '(namnlös)'}
                </div>
                {sol.description.trim() && (
                  <p
                    style={{
                      fontSize: 14,
                      lineHeight: 1.6,
                      color: '#555',
                      margin: '0 0 16px 0',
                      padding: 0,
                      flex: 1,
                    }}
                  >
                    {sol.description}
                  </p>
                )}
                <span
                  style={{
                    fontSize: 14,
                    fontWeight: 600,
                    color: '#F28C28',
                    marginTop: 'auto',
                    display: 'inline-flex',
                    alignItems: 'center',
                    gap: 4,
                  }}
                >
                  {sol.ctaText.trim() || 'Läs mer'} <Arrow color="#F28C28" />
                </span>
              </div>
            </div>
          ))}
        </div>
      </div>
    </PreviewSection>
  );
}
