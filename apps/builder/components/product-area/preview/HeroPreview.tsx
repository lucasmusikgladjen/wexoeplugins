import { ProductAreaState, ProductAreaSectionId } from '@/lib/product-area-types';
import { colorOr, textOn, secondaryTextOn } from '@/lib/color-utils';
import { PreviewSection, Check, Arrow, MarkdownText } from './shared';

interface Props {
  state: ProductAreaState;
  active: ProductAreaSectionId | null;
  onSelect: (id: ProductAreaSectionId) => void;
}

export default function HeroPreview({ state, active, onSelect }: Props) {
  const bg = colorOr(state.heroBg, '#FFFFFF');
  const accent = colorOr(state.heroAccent, '#F28C28');
  const color = textOn(bg);
  const colorSecondary = secondaryTextOn(bg);

  const hasH2 = !!state.heroH2.trim();
  const hasText = !!state.heroText.trim();
  const hasCta = !!state.heroCtaText.trim();
  const hasNpi = !!state.npiTitle.trim();
  const hasBenefits = !!state.heroBenefits.trim();
  const hasImage = !!state.heroImage.trim();

  return (
    <PreviewSection
      id="hero"
      active={active}
      onClick={onSelect}
      style={{ background: bg, color }}
    >
      <div
        style={{
          padding: '60px 40px',
          display: 'flex',
          gap: 40,
          alignItems: 'flex-start',
          maxWidth: 1270,
          margin: '0 auto',
        }}
      >
        {/* Left column — always rendered */}
        <div style={{ flex: '3 1 0%', minWidth: 0 }}>
          <h2
            style={{
              fontSize: 36,
              fontWeight: 700,
              lineHeight: 1.2,
              margin: '0 0 20px 0',
              color,
              opacity: hasH2 ? 1 : 0.35,
            }}
          >
            {hasH2 ? state.heroH2 : 'Din rubrik här'}
          </h2>

          <div
            style={{
              fontSize: 17,
              lineHeight: 1.7,
              margin: '0 0 28px 0',
              color: colorSecondary,
              opacity: hasText ? 1 : 0.55,
            }}
          >
            {hasText ? (
              <MarkdownText text={state.heroText} />
            ) : (
              <p style={{ margin: 0 }}>
                Beskrivande text som förklarar vad sidan handlar om. Två till tre meningar som fångar besökarens intresse och leder vidare mot din CTA.
              </p>
            )}
          </div>

          <span
            style={{
              display: 'inline-flex',
              alignItems: 'center',
              gap: 10,
              padding: '14px 32px',
              background: hasCta ? accent : 'transparent',
              border: hasCta ? 'none' : `2px dashed ${colorSecondary}`,
              color: hasCta ? '#FFFFFF' : colorSecondary,
              fontSize: 16,
              fontWeight: 600,
              borderRadius: 6,
              opacity: hasCta ? 1 : 0.55,
            }}
          >
            {hasCta ? state.heroCtaText : 'Din CTA'}
            <Arrow color={hasCta ? '#FFFFFF' : colorSecondary} />
          </span>
        </div>

        {/* Right column — three-way conditional with ghost fallback */}
        {hasNpi ? (
          <NpiCard state={state} />
        ) : hasBenefits ? (
          <HeroBenefits state={state} textColor={color} />
        ) : hasImage ? (
          <HeroImage state={state} />
        ) : (
          <GhostBenefits textColor={color} />
        )}
      </div>
    </PreviewSection>
  );
}

function NpiCard({ state }: { state: ProductAreaState }) {
  return (
    <div
      style={{
        flex: '2 1 0%',
        minWidth: 0,
        maxWidth: 420,
        background: '#FFFFFF',
        borderRadius: 10,
        overflow: 'hidden',
        boxShadow: '0 2px 12px rgba(0,0,0,0.08)',
        display: 'flex',
        flexDirection: 'column',
        alignSelf: 'center',
      }}
    >
      {state.npiImage.trim() && (
        // eslint-disable-next-line @next/next/no-img-element
        <img
          src={state.npiImage}
          alt=""
          style={{
            width: '100%',
            aspectRatio: '16 / 9',
            objectFit: 'cover',
            background: '#F5F5F5',
            display: 'block',
          }}
        />
      )}
      <div style={{ padding: '16px 18px 20px' }}>
        <span
          style={{
            display: 'inline-block',
            background: '#11325D',
            color: '#FFFFFF',
            fontSize: 11,
            fontWeight: 700,
            letterSpacing: '0.08em',
            padding: '3px 10px',
            borderRadius: 4,
            marginBottom: 10,
          }}
        >
          NYHET
        </span>
        <div
          style={{
            fontSize: 18,
            fontWeight: 700,
            color: '#11325D',
            marginBottom: 6,
            lineHeight: 1.3,
          }}
        >
          {state.npiTitle}
        </div>
        {state.npiDescription.trim() && (
          <p
            style={{
              fontSize: 14,
              lineHeight: 1.6,
              color: '#555',
              margin: '0 0 14px 0',
            }}
          >
            {state.npiDescription}
          </p>
        )}
        <span
          style={{
            fontSize: 14,
            fontWeight: 600,
            color: '#11325D',
            display: 'inline-flex',
            alignItems: 'center',
            gap: 4,
          }}
        >
          Läs mer <Arrow color="#11325D" />
        </span>
      </div>
    </div>
  );
}

function HeroBenefits({ state, textColor }: { state: ProductAreaState; textColor: string }) {
  const items = state.heroBenefits.split('\n').map((l) => l.trim()).filter(Boolean);
  if (items.length === 0) return null;
  return (
    <div
      style={{
        flex: '2 1 0%',
        minWidth: 0,
        background: '#F8F9FA',
        border: '1px solid #E8E8E8',
        borderRadius: 10,
        padding: '30px 28px',
        alignSelf: 'flex-start',
      }}
    >
      <ul style={{ listStyle: 'none', padding: 0, margin: 0 }}>
        {items.map((item, i) => (
          <li
            key={i}
            style={{
              fontSize: 15,
              lineHeight: 1.6,
              color: textColor,
              padding: '8px 0',
              display: 'flex',
              alignItems: 'flex-start',
              gap: 12,
              borderTop: i > 0 ? '1px solid #E8E8E8' : 'none',
            }}
          >
            <Check />
            <span>{item}</span>
          </li>
        ))}
      </ul>
    </div>
  );
}

function HeroImage({ state }: { state: ProductAreaState }) {
  return (
    <div style={{ flex: '2 1 0%', minWidth: 0, alignSelf: 'center' }}>
      {/* eslint-disable-next-line @next/next/no-img-element */}
      <img
        src={state.heroImage}
        alt=""
        style={{
          width: '100%',
          height: 'auto',
          borderRadius: 10,
          display: 'block',
          objectFit: 'contain',
        }}
      />
    </div>
  );
}

/** Placeholder shown when no NPI card, benefits or image are set.
 *  Mimics the benefits box shape so users see the structural split. */
function GhostBenefits({ textColor }: { textColor: string }) {
  const ghostItems = ['Punkt ett', 'Punkt två', 'Punkt tre'];
  return (
    <div
      style={{
        flex: '2 1 0%',
        minWidth: 0,
        background: '#F8F9FA',
        border: '1px dashed #D1D5DB',
        borderRadius: 10,
        padding: '30px 28px',
        alignSelf: 'flex-start',
        opacity: 0.6,
      }}
    >
      <ul style={{ listStyle: 'none', padding: 0, margin: 0 }}>
        {ghostItems.map((item, i) => (
          <li
            key={i}
            style={{
              fontSize: 15,
              lineHeight: 1.6,
              color: textColor,
              padding: '8px 0',
              display: 'flex',
              alignItems: 'flex-start',
              gap: 12,
              borderTop: i > 0 ? '1px solid #E8E8E8' : 'none',
            }}
          >
            <Check color="#C0C4CC" />
            <span>{item}</span>
          </li>
        ))}
      </ul>
    </div>
  );
}
