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
 * Kategorier — grid av product-area-kort. Bild/namn/beskrivning kommer
 * från cms_product_pages-recordet (card_image_url, name, card_description).
 * Pluginet returnerar ingenting om category_ids är tom → vi visar då en
 * tom state-notis i preview.
 */
export default function CategoriesPreview({ state, active, onSelect }: Props) {
  const categories = useLinkedRecords('product_areas', state.categoryIds).filter(
    (r) => r.is_active !== false,
  );

  const eyebrow = state.categoriesEyebrow.trim();
  const h2 = state.categoriesH2.trim();
  const intro = state.categoriesIntro.trim();

  if (categories.length === 0) {
    return (
      <PreviewSection
        id="categories"
        active={active}
        onClick={onSelect}
        style={{ background: '#fff', padding: '64px 40px' }}
      >
        <p style={{ fontSize: 13, color: WEXOE_COLORS.textMuted, fontStyle: 'italic', textAlign: 'center', margin: 0 }}>
          Inga produktområden valda. Sektionen döljs helt på publika sidan tills minst ett område läggs till.
        </p>
      </PreviewSection>
    );
  }

  return (
    <PreviewSection
      id="categories"
      active={active}
      onClick={onSelect}
      style={{ background: '#fff', padding: '64px 40px' }}
    >
      <div style={{ textAlign: 'center', maxWidth: 720, margin: '0 auto 40px' }}>
        {eyebrow && (
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
            {eyebrow}
          </div>
        )}
        {h2 && (
          <h2
            style={{
              fontSize: 26,
              fontWeight: 700,
              color: WEXOE_COLORS.main,
              lineHeight: 1.25,
              margin: '0 0 12px 0',
            }}
          >
            {h2}
          </h2>
        )}
        {intro && (
          <p style={{ fontSize: 14, lineHeight: 1.7, color: WEXOE_COLORS.textMuted, margin: 0 }}>
            {renderInlineMarkdown(intro)}
          </p>
        )}
      </div>

      <div
        style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(4, 1fr)',
          gap: 14,
        }}
      >
        {categories.map((cat) => {
          const image = typeof cat.card_image_url === 'string' ? cat.card_image_url : '';
          const name = typeof cat.name === 'string' ? cat.name : (cat.slug as string) || '(namnlös)';
          const desc = typeof cat.card_description === 'string' ? cat.card_description : '';
          return (
            <div
              key={cat._recordId as string}
              style={{
                background: '#fff',
                borderRadius: 8,
                overflow: 'hidden',
                border: `1px solid ${WEXOE_COLORS.border}`,
                display: 'flex',
                flexDirection: 'column',
              }}
            >
              <div
                style={{
                  width: '100%',
                  height: 100,
                  background: image ? `center/cover url("${image}")` : WEXOE_COLORS.light,
                }}
              />
              <div style={{ padding: 12, display: 'flex', flexDirection: 'column', flex: 1 }}>
                <div
                  style={{
                    fontSize: 13,
                    fontWeight: 700,
                    color: WEXOE_COLORS.main,
                    marginBottom: 5,
                    lineHeight: 1.3,
                  }}
                >
                  {name}
                </div>
                {desc && (
                  <div
                    style={{
                      fontSize: 11,
                      color: WEXOE_COLORS.textMuted,
                      lineHeight: 1.45,
                      marginBottom: 8,
                      flex: 1,
                    }}
                  >
                    {desc}
                  </div>
                )}
                <span
                  style={{
                    fontSize: 11,
                    fontWeight: 600,
                    color: WEXOE_COLORS.main,
                    alignSelf: 'flex-start',
                  }}
                >
                  Se produkter →
                </span>
              </div>
            </div>
          );
        })}
      </div>
    </PreviewSection>
  );
}
