import { ProductAreaState, ProductAreaSectionId } from '@/lib/product-area-types';
import { colorOr, textOn } from '@/lib/color-utils';
import { PreviewSection } from './shared';

interface Props {
  state: ProductAreaState;
  active: ProductAreaSectionId | null;
  onSelect: (id: ProductAreaSectionId) => void;
}

export default function DocsPreview({ state, active, onSelect }: Props) {
  if (!state.docsIframe.trim()) return null;
  const bg = colorOr(state.docsBg, '#FFFFFF');
  const color = textOn(bg);

  return (
    <PreviewSection
      id="docs"
      active={active}
      onClick={onSelect}
      style={{ background: bg, color }}
    >
      <div
        style={{
          maxWidth: 1270,
          margin: '0 auto',
          padding: '60px 40px',
          textAlign: 'center',
        }}
      >
        <h2
          style={{
            fontSize: 30,
            fontWeight: 700,
            color,
            margin: '0 0 28px 0',
            padding: 0,
          }}
        >
          {state.docsTitle.trim() || 'Dokumentation'}
        </h2>
        <div
          style={{
            width: '100%',
            borderRadius: 8,
            overflow: 'hidden',
            background: '#F8F9FA',
            minHeight: 300,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            padding: 40,
            color: '#999',
            fontSize: 13,
          }}
        >
          <div style={{ textAlign: 'center' }}>
            <div style={{ marginBottom: 6 }}>Iframe-förhandsvisning</div>
            <div style={{ fontFamily: 'monospace', fontSize: 11, color: '#B0B6BF' }}>
              {state.docsIframe}
            </div>
          </div>
        </div>
      </div>
    </PreviewSection>
  );
}
