import { CustomerTypePageState, CustomerTypePageSectionId } from '@/lib/customer-type-types';
import { PreviewSection, renderTitleHighlight } from './shared';

interface Props {
  state: CustomerTypePageState;
  active: CustomerTypePageSectionId | null;
  onSelect: (id: CustomerTypePageSectionId) => void;
}

export default function HeroPreview({ state, active, onSelect }: Props) {
  const hasEyebrow = !!state.eyebrow.trim();
  const hasTitle = !!state.title.trim();
  const hasDescription = !!state.description.trim();
  const hasCta = !!state.ctaText.trim();
  const hasImage = !!state.heroImageUrl.trim();
  const statNumber = parseInt(state.statNumber, 10);
  const hasStat = !!state.statNumber.trim() && !Number.isNaN(statNumber) && statNumber > 0;

  return (
    <PreviewSection
      id="hero"
      active={active}
      onClick={onSelect}
      style={{
        background: 'linear-gradient(135deg, #11325D 0%, #1a4a7a 50%, #2d6a9f 100%)',
        color: '#fff',
        position: 'relative',
        overflow: 'hidden',
      }}
    >
      <div
        style={{
          maxWidth: 1270,
          margin: '0 auto',
          padding: '60px 40px',
          display: 'grid',
          gridTemplateColumns: '1fr 1fr',
          gap: 40,
          alignItems: 'center',
        }}
      >
        <div>
          <div
            style={{
              display: 'inline-flex',
              alignItems: 'center',
              gap: 8,
              fontSize: 13,
              fontWeight: 600,
              textTransform: 'uppercase',
              letterSpacing: 1.4,
              color: '#F28C28',
              marginBottom: 16,
              opacity: hasEyebrow ? 1 : 0.45,
            }}
          >
            <span style={{ display: 'inline-block', width: 22, height: 2, background: '#F28C28' }} />
            {hasEyebrow ? state.eyebrow : 'EYEBROW'}
          </div>

          <h1
            style={{
              fontSize: 44,
              fontWeight: 700,
              lineHeight: 1.15,
              margin: '0 0 20px 0',
              color: '#fff',
              opacity: hasTitle ? 1 : 0.5,
            }}
          >
            {hasTitle ? renderTitleHighlight(state.title) : 'Din *titel* här'}
          </h1>

          <p
            style={{
              fontSize: 16,
              lineHeight: 1.7,
              color: 'rgba(255,255,255,0.85)',
              margin: '0 0 26px 0',
              maxWidth: 460,
              opacity: hasDescription ? 1 : 0.5,
            }}
          >
            {hasDescription
              ? state.description
              : 'Kort beskrivning under titeln som förklarar erbjudandet på ett par meningar.'}
          </p>

          <span
            style={{
              display: 'inline-flex',
              alignItems: 'center',
              gap: 10,
              background: hasCta ? '#F28C28' : 'transparent',
              border: hasCta ? 'none' : '2px dashed rgba(255,255,255,0.4)',
              color: hasCta ? '#fff' : 'rgba(255,255,255,0.6)',
              fontSize: 15,
              fontWeight: 600,
              padding: '14px 28px',
              borderRadius: 6,
            }}
          >
            {hasCta ? state.ctaText : 'Kontakta oss'}
            <span aria-hidden style={{ marginLeft: 2 }}>→</span>
          </span>
        </div>

        <div style={{ position: 'relative' }}>
          <div
            style={{
              borderRadius: 14,
              overflow: 'hidden',
              boxShadow: '0 24px 48px rgba(0,0,0,0.25)',
              aspectRatio: '4 / 3',
              background: hasImage
                ? `center/cover url("${state.heroImageUrl}")`
                : 'rgba(255,255,255,0.06)',
              border: hasImage ? 'none' : '2px dashed rgba(255,255,255,0.2)',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              color: 'rgba(255,255,255,0.4)',
              fontSize: 13,
            }}
          >
            {!hasImage && 'Hero-bild'}
          </div>

          {hasStat && (
            <div
              style={{
                position: 'absolute',
                bottom: -22,
                left: -28,
                background: '#fff',
                padding: '14px 22px',
                borderRadius: 10,
                boxShadow: '0 12px 36px rgba(0,0,0,0.15)',
                color: '#11325D',
              }}
            >
              <div style={{ fontSize: 28, fontWeight: 700, lineHeight: 1 }}>{statNumber}</div>
              <div style={{ fontSize: 12, color: '#666', marginTop: 3 }}>{state.statLabel}</div>
            </div>
          )}
        </div>
      </div>
    </PreviewSection>
  );
}
