import { ProductAreaState, ProductAreaSectionId } from '@/lib/product-area-types';
import { colorOr, textOn, isDark } from '@/lib/color-utils';
import { PreviewSection, MarkdownText } from './shared';

interface Props {
  state: ProductAreaState;
  active: ProductAreaSectionId | null;
  onSelect: (id: ProductAreaSectionId) => void;
}

export default function ContactPreview({ state, active, onSelect }: Props) {
  if (!state.contactName.trim()) return null;

  const bg = colorOr(state.contactBg, '#11325D');
  const color = textOn(bg);
  const dark = isDark(bg);
  const borderColor = dark ? 'rgba(255,255,255,0.2)' : '#E8E8E8';

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
          {state.contactImage.trim() && (
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
          )}
          <div style={{ flexShrink: 0 }}>
            <div style={{ fontSize: 19, fontWeight: 700, color, marginBottom: 2 }}>
              {state.contactName}
            </div>
            {state.contactTitle.trim() && (
              <div
                style={{
                  fontSize: 14,
                  color,
                  marginBottom: 10,
                  opacity: 0.85,
                }}
              >
                {state.contactTitle}
              </div>
            )}
            {state.contactEmail.trim() && (
              <div style={{ fontSize: 14, marginBottom: 3 }}>
                <span
                  style={{
                    color,
                    textDecoration: 'underline',
                    textUnderlineOffset: 2,
                    opacity: 0.85,
                  }}
                >
                  {state.contactEmail}
                </span>
              </div>
            )}
            {state.contactPhone.trim() && (
              <div style={{ fontSize: 14, marginBottom: 3 }}>
                <span
                  style={{
                    color,
                    textDecoration: 'underline',
                    textUnderlineOffset: 2,
                    opacity: 0.85,
                  }}
                >
                  {state.contactPhone}
                </span>
              </div>
            )}
          </div>
        </div>

        {/* Right: quote */}
        {state.contactText.trim() && (
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
              }}
            >
              <MarkdownText text={state.contactText} />
            </div>
            <QuoteMark color={borderColor} />
          </div>
        )}
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
