import { NormalSection, ProductAreaSectionId } from '@/lib/product-area-types';
import { colorOr, textOn, secondaryTextOn } from '@/lib/color-utils';
import { PreviewSection, MarkdownText, BulletList } from './shared';

interface Props {
  section: NormalSection;
  n: 1 | 2 | 3 | 4;
  active: ProductAreaSectionId | null;
  onSelect: (id: ProductAreaSectionId) => void;
}

export default function NormalSectionPreview({ section, n, active, onSelect }: Props) {
  if (!section.h2.trim() && !section.text.trim()) return null;

  // Default BG alternates: odd N = white, even N = #F8F9FA
  const defaultBg = n % 2 === 0 ? '#F8F9FA' : '#FFFFFF';
  const bg = colorOr(section.bg, defaultBg);
  const color = textOn(bg);
  const colorSecondary = secondaryTextOn(bg);

  return (
    <PreviewSection
      id="content"
      active={active}
      onClick={onSelect}
      style={{ background: bg, color }}
    >
      <div
        style={{
          padding: '70px 40px',
          maxWidth: 1270,
          margin: '0 auto',
          display: 'grid',
          gridTemplateColumns: section.image.trim() ? '1fr 1fr' : '1fr',
          gap: 60,
          alignItems: 'center',
        }}
      >
        {/* Text column */}
        <div style={{ order: section.reversed ? 2 : 1, minWidth: 0 }}>
          {section.h2.trim() && (
            <h2
              style={{
                fontSize: 28,
                fontWeight: 700,
                color,
                margin: '0 0 18px 0',
                lineHeight: 1.35,
              }}
            >
              {section.h2}
            </h2>
          )}
          {section.text.trim() && (
            <div
              style={{
                fontSize: 16,
                lineHeight: 1.7,
                color: colorSecondary,
                margin: '0 0 18px 0',
              }}
            >
              <MarkdownText text={section.text} />
            </div>
          )}
          <BulletList text={section.bullets} textColor={colorSecondary} />
        </div>

        {/* Image column */}
        {section.image.trim() && (
          <div style={{ order: section.reversed ? 1 : 2, minWidth: 0 }}>
            <div style={{ width: '100%', borderRadius: 10, overflow: 'hidden' }}>
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img
                src={section.image}
                alt=""
                style={{ width: '100%', height: 'auto', display: 'block' }}
              />
            </div>
          </div>
        )}
      </div>
    </PreviewSection>
  );
}
