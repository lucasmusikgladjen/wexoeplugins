'use client';

import { useState } from 'react';
import { ProductAreaState, LinkedProduct, ProductAreaSectionId } from '@/lib/product-area-types';
import { colorOr, textOn, isDark } from '@/lib/color-utils';
import { PreviewSection, BulletList, MarkdownText, Arrow } from './shared';

interface Props {
  state: ProductAreaState;
  active: ProductAreaSectionId | null;
  onSelect: (id: ProductAreaSectionId) => void;
}

/** Router between toggle accordion and side-menu mode. */
export default function ProductsPreview(props: Props) {
  if (props.state.products.length === 0) return null;
  return props.state.sideMenu ? <SideMenuMode {...props} /> : <ToggleMode {...props} />;
}

// ─── Toggle / Accordion mode ───────────────────────────────────────────────

function ToggleMode({ state, active, onSelect }: Props) {
  const [openIndex, setOpenIndex] = useState(state.defaultOpen ? 0 : -1);

  const bg = colorOr(state.toggleBg, '#11325D');
  const headerBg = colorOr(state.toggleHeaderBg, '#FFFFFF');
  const accent = colorOr(state.toggleAccent, '#F28C28');

  return (
    <PreviewSection
      id="products"
      active={active}
      onClick={onSelect}
      style={{ background: bg }}
    >
      <div style={{ padding: '50px 40px', maxWidth: 1270, margin: '0 auto' }}>
        {state.products.map((product, i) => (
          <ToggleItem
            key={product.clientId}
            product={product}
            isOpen={openIndex === i}
            onToggle={() => setOpenIndex(openIndex === i ? -1 : i)}
            headerBg={headerBg}
            accent={accent}
          />
        ))}
      </div>
    </PreviewSection>
  );
}

function ToggleItem({
  product,
  isOpen,
  onToggle,
  headerBg,
  accent,
}: {
  product: LinkedProduct;
  isOpen: boolean;
  onToggle: () => void;
  headerBg: string;
  accent: string;
}) {
  return (
    <div
      style={{
        background: headerBg,
        borderRadius: 8,
        marginBottom: 10,
        overflow: 'hidden',
      }}
    >
      <button
        type="button"
        onClick={(e) => {
          e.stopPropagation();
          onToggle();
        }}
        style={{
          all: 'unset',
          display: 'flex',
          alignItems: 'center',
          width: '100%',
          padding: '22px 28px',
          cursor: 'pointer',
          boxSizing: 'border-box',
        }}
      >
        <div
          style={{
            flex: 1,
            minWidth: 0,
            display: 'flex',
            alignItems: 'baseline',
            gap: 8,
            flexWrap: 'wrap',
            marginRight: 16,
          }}
        >
          <span style={{ fontSize: 17, fontWeight: 600, color: '#11325D' }}>
            {product.name || '(namnlös produkt)'}
          </span>
          {product.ecosystemDescription.trim() && (
            <span style={{ fontSize: 14, fontWeight: 400, color: '#999' }}>
              — {product.ecosystemDescription}
            </span>
          )}
        </div>
        <svg width="16" height="16" viewBox="0 0 16 16" style={{ flexShrink: 0 }}>
          <line
            x1="2"
            y1="8"
            x2="14"
            y2="8"
            stroke="#11325D"
            strokeWidth="2"
            strokeLinecap="round"
          />
          {!isOpen && (
            <line
              x1="8"
              y1="2"
              x2="8"
              y2="14"
              stroke="#11325D"
              strokeWidth="2"
              strokeLinecap="round"
            />
          )}
        </svg>
      </button>

      {isOpen && (
        <div
          style={{
            display: 'flex',
            gap: 36,
            padding: '8px 28px 28px 28px',
            flexWrap: 'wrap',
          }}
        >
          <div style={{ flex: '1 1 55%', minWidth: 0 }}>
            {product.description.trim() && (
              <div
                style={{
                  fontSize: 15,
                  lineHeight: 1.7,
                  color: '#555',
                  margin: '0 0 16px 0',
                }}
              >
                <MarkdownText text={product.description} />
              </div>
            )}
            <BulletList text={product.bullets} textColor="#555" />
            {(product.button1Text.trim() || product.button2Text.trim()) && (
              <div
                style={{
                  display: 'flex',
                  gap: 10,
                  marginTop: 20,
                  flexWrap: 'wrap',
                }}
              >
                {product.button2Text.trim() && (
                  <span
                    style={{
                      display: 'inline-flex',
                      alignItems: 'center',
                      gap: 8,
                      padding: '12px 22px',
                      borderRadius: 6,
                      fontSize: 14,
                      fontWeight: 600,
                      background: accent,
                      color: '#FFFFFF',
                      minWidth: 180,
                      justifyContent: 'center',
                    }}
                  >
                    {product.button2Text} <Arrow color="#FFFFFF" />
                  </span>
                )}
                {product.button1Text.trim() && (
                  <span
                    style={{
                      display: 'inline-flex',
                      alignItems: 'center',
                      gap: 8,
                      padding: '12px 22px',
                      borderRadius: 6,
                      fontSize: 14,
                      fontWeight: 600,
                      background: 'transparent',
                      color: '#11325D',
                      border: '2px solid #11325D',
                      minWidth: 180,
                      justifyContent: 'center',
                    }}
                  >
                    {product.button1Text} <Arrow color="#11325D" />
                  </span>
                )}
              </div>
            )}
          </div>

          {product.image.trim() && (
            <div style={{ flex: '0 0 260px' }}>
              <div
                style={{
                  width: 260,
                  height: 200,
                  overflow: 'hidden',
                  borderRadius: 8,
                  background: '#FFFFFF',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                }}
              >
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img
                  src={product.image}
                  alt=""
                  style={{
                    maxWidth: '100%',
                    maxHeight: '100%',
                    width: 'auto',
                    height: 'auto',
                    objectFit: 'contain',
                    display: 'block',
                  }}
                />
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  );
}

// ─── Side-menu mode ────────────────────────────────────────────────────────

function SideMenuMode({ state, active, onSelect }: Props) {
  const [activeIndex, setActiveIndex] = useState(0);
  const bg = colorOr(state.toggleBg, '#11325D');
  const accent = colorOr(state.toggleAccent, '#F28C28');
  const dark = isDark(bg);
  const textColor = textOn(bg);
  const mutedColor = dark ? 'rgba(255,255,255,0.85)' : '#555';

  const current = state.products[activeIndex];
  if (!current) return null;

  return (
    <PreviewSection
      id="products"
      active={active}
      onClick={onSelect}
      style={{ background: bg, color: textColor }}
    >
      <div
        style={{
          display: 'flex',
          gap: 36,
          padding: '60px 40px',
          maxWidth: 1270,
          margin: '0 auto',
          flexWrap: 'wrap',
        }}
      >
        {/* Sidebar nav */}
        <nav style={{ flex: '0 0 200px', minWidth: 180 }}>
          {state.products.map((p, i) => {
            const isActive = i === activeIndex;
            return (
              <button
                key={p.clientId}
                type="button"
                onClick={(e) => {
                  e.stopPropagation();
                  setActiveIndex(i);
                }}
                style={{
                  all: 'unset',
                  display: 'block',
                  width: '100%',
                  padding: '12px 16px',
                  fontSize: 16,
                  fontWeight: isActive ? 600 : 500,
                  color: isActive ? accent : mutedColor,
                  borderLeft: `3px solid ${isActive ? accent : 'transparent'}`,
                  cursor: 'pointer',
                  boxSizing: 'border-box',
                }}
              >
                {p.name || '(namnlös)'}
              </button>
            );
          })}
        </nav>

        {/* Content */}
        <div style={{ flex: '1 1 60%', minWidth: 0 }}>
          <h2 style={{ fontSize: 28, fontWeight: 700, margin: '0 0 16px 0', color: textColor }}>
            {current.headerSideMenu.trim() || current.name || '(namnlös)'}
          </h2>
          {current.description.trim() && (
            <div
              style={{
                fontSize: 15,
                lineHeight: 1.7,
                color: mutedColor,
                margin: '0 0 16px 0',
              }}
            >
              <MarkdownText text={current.description} />
            </div>
          )}
          <BulletList text={current.bullets} textColor={mutedColor} />

          {(current.button1Text.trim() || current.button2Text.trim()) && (
            <div style={{ display: 'flex', gap: 10, margin: '20px 0', flexWrap: 'wrap' }}>
              {current.button2Text.trim() && (
                <span
                  style={{
                    padding: '12px 22px',
                    borderRadius: 6,
                    fontSize: 14,
                    fontWeight: 600,
                    background: accent,
                    color: '#FFFFFF',
                  }}
                >
                  {current.button2Text} <Arrow color="#FFFFFF" />
                </span>
              )}
              {current.button1Text.trim() && (
                <span
                  style={{
                    padding: '12px 22px',
                    borderRadius: 6,
                    fontSize: 14,
                    fontWeight: 600,
                    background: 'transparent',
                    color: textColor,
                    border: `2px solid ${textColor}`,
                  }}
                >
                  {current.button1Text} <Arrow color={textColor} />
                </span>
              )}
            </div>
          )}

          {/* Article cards — read-only, only visible in side-menu mode */}
          {current.articles.length > 0 && (
            <div
              style={{
                marginTop: 32,
                paddingTop: 28,
                borderTop: `1px solid ${dark ? 'rgba(255,255,255,0.1)' : '#E8E8E8'}`,
                display: 'grid',
                gridTemplateColumns: 'repeat(auto-fill, minmax(200px, 1fr))',
                gap: 20,
              }}
            >
              {current.articles.map((a) => (
                <div
                  key={a.recordId}
                  style={{
                    background: dark ? 'rgba(255,255,255,0.06)' : '#F8F9FA',
                    borderRadius: 8,
                    overflow: 'hidden',
                    border: `1px solid ${dark ? 'rgba(255,255,255,0.08)' : '#E8E8E8'}`,
                    display: 'flex',
                    flexDirection: 'column',
                  }}
                >
                  {a.bild.trim() && (
                    // eslint-disable-next-line @next/next/no-img-element
                    <img
                      src={a.bild}
                      alt=""
                      style={{
                        width: '100%',
                        aspectRatio: '4 / 3',
                        objectFit: 'contain',
                        background: '#FFFFFF',
                      }}
                    />
                  )}
                  <div style={{ padding: 12 }}>
                    <div
                      style={{
                        fontSize: 15,
                        fontWeight: 600,
                        color: textColor,
                        marginBottom: 4,
                      }}
                    >
                      {a.name}
                    </div>
                    {a.artikelnummer && (
                      <div style={{ fontSize: 13, color: mutedColor, fontFamily: 'monospace' }}>
                        {a.artikelnummer}
                      </div>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </PreviewSection>
  );
}

