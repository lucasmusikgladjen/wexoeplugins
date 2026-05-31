import { ProductAreaState, ProductAreaSectionId } from '@/lib/product-area-types';
import { colorOr, textOn, isDark } from '@/lib/color-utils';
import { PreviewSection, MarkdownText } from './shared';

interface Props {
  state: ProductAreaState;
  active: ProductAreaSectionId | null;
  onSelect: (id: ProductAreaSectionId) => void;
}

export default function ContactPreview({ state, active, onSelect }: Props) {
  const bg = colorOr(state.contactBg, '#11325D');
  const color = textOn(bg);
  const dark = isDark(bg);
  const borderColor = dark ? 'rgba(255,255,255,0.2)' : '#E8E8E8';
  const avatarBg = dark ? 'rgba(255,255,255,0.08)' : '#F1F5F9';

  const hasName = !!state.contactName.trim();
  const hasTitle = !!state.contactTitle.trim();
  const hasEmail = !!state.contactEmail.trim();
  const hasPhone = !!state.contactPhone.trim();
  const hasImage = !!state.contactImage.trim();
  const hasQuote = !!state.contactText.trim();

  return (
    <PreviewSection
      id="contact"
      active={active}
      onClick={onSelect}
      style={{ background: bg, color }}
    >
      <div
        style={{
          maxWidth: 800,
          margin: '0 auto',
          padding: '50px 40px',
          display: 'flex',
          gap: 40,
          alignItems: 'center',
          flexWrap: 'wrap',
        }}
      >
        {/* Left: avatar + info */}
        <div
          style={{
            display: 'flex',
            alignItems: 'center',
            gap: 24,
            flexShrink: 0,
          }}
        >
          {hasImage ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img
              src={state.contactImage}
              alt=""
              style={{
                width: 110,
                height: 110,
                borderRadius: '50%',
                objectFit: 'cover',
                border: `3px solid ${borderColor}`,
                flexShrink: 0,
              }}
            />
          ) : (
            <div
              aria-hidden
              style={{
                width: 110,
                height: 110,
                borderRadius: '50%',
                border: `3px solid ${borderColor}`,
                background: avatarBg,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                color,
                opacity: 0.4,
                flexShrink: 0,
              }}
            >
              {/* Minimal person silhouette */}
              <svg width="44" height="44" viewBox="0 0 44 44" fill="none">
                <circle cx="22" cy="16" r="7" stroke="currentColor" strokeWidth="2" />
                <path
                  d="M8 36c0-7 6-12 14-12s14 5 14 12"
                  stroke="currentColor"
                  strokeWidth="2"
                  strokeLinecap="round"
                />
              </svg>
            </div>
          )}

          <div style={{ flexShrink: 0 }}>
            <div
              style={{
                fontSize: 19,
                fontWeight: 700,
                color,
                marginBottom: 2,
                opacity: hasName ? 1 : 0.4,
              }}
            >
              {hasName ? state.contactName : 'Kontaktperson'}
            </div>
            <div
              style={{
                fontSize: 14,
                color,
                marginBottom: 10,
                opacity: hasTitle ? 0.85 : 0.4,
              }}
            >
              {hasTitle ? state.contactTitle : 'Titel'}
            </div>
            <div
              style={{
                fontSize: 14,
                marginBottom: 3,
                color,
                textDecoration: 'underline',
                textUnderlineOffset: 2,
                opacity: hasEmail ? 0.85 : 0.4,
              }}
            >
              {hasEmail ? state.contactEmail : 'namn@wexoe.se'}
            </div>
            <div
              style={{
                fontSize: 14,
                marginBottom: 3,
                color,
                textDecoration: 'underline',
                textUnderlineOffset: 2,
                opacity: hasPhone ? 0.85 : 0.4,
              }}
            >
              {hasPhone ? state.contactPhone : '+46 00 000 00 00'}
            </div>
          </div>
        </div>

        {/* Right: quote */}
        <div
          style={{
            flex: 1,
            minWidth: 240,
            position: 'relative',
            paddingLeft: 20,
            borderLeft: `2px solid ${borderColor}`,
          }}
        >
          <QuoteMark open color={borderColor} />
          <div
            style={{
              fontSize: 16,
              lineHeight: 1.7,
              color,
              fontStyle: 'italic',
              position: 'relative',
              zIndex: 1,
              opacity: hasQuote ? 1 : 0.4,
            }}
          >
            {hasQuote ? (
              <MarkdownText text={state.contactText} />
            ) : (
              <p style={{ margin: 0 }}>
                Här kan du lägga en kort quote från kontaktpersonen som bygger förtroende för sidan.
              </p>
            )}
          </div>
          <QuoteMark color={borderColor} />
        </div>
      </div>
    </PreviewSection>
  );
}

function QuoteMark({ open = false, color }: { open?: boolean; color: string }) {
  return (
    <svg
      width="32"
      height="24"
      viewBox="0 0 32 24"
      style={{
        display: 'block',
        marginBottom: open ? 6 : 0,
        marginTop: open ? 0 : 6,
        marginLeft: open ? 0 : 'auto',
        opacity: 0.35,
      }}
      aria-hidden
    >
      <path
        d="M0 24V12C0 5.4 5.4 0 12 0v4c-4.4 0-8 3.6-8 8h8v12H0zm20 0V12c0-6.6 5.4-12 12-12v4c-4.4 0-8 3.6-8 8h8v12H20z"
        fill={color}
        transform={open ? 'rotate(180, 16, 12)' : undefined}
      />
    </svg>
  );
}
