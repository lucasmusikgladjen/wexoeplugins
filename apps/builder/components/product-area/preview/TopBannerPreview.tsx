import { ProductAreaState, ProductAreaSectionId } from '@/lib/product-area-types';
import { colorOr, textOn } from '@/lib/color-utils';
import { PreviewSection } from './shared';

interface Props {
  state: ProductAreaState;
  active: ProductAreaSectionId | null;
  onSelect: (id: ProductAreaSectionId) => void;
}

export default function TopBannerPreview({ state, active, onSelect }: Props) {
  const bg = colorOr(state.topBg, '#11325D');
  const color = textOn(bg);
  const hasH1 = !!state.h1.trim();

  return (
    <PreviewSection
      id="hero"
      active={active}
      onClick={onSelect}
      style={{ background: bg, color }}
    >
      <div
        style={{
          padding: '32px 40px',
          minHeight: 90,
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
        }}
      >
        <h1
          style={{
            fontSize: 26,
            fontWeight: 600,
            letterSpacing: '0.08em',
            textTransform: 'uppercase',
            margin: 0,
            textAlign: 'center',
            color,
            opacity: hasH1 ? 1 : 0.35,
          }}
        >
          {hasH1 ? state.h1 : 'Din rubrik här'}
        </h1>
      </div>
    </PreviewSection>
  );
}
