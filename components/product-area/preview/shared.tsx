import { ReactNode } from 'react';
import { ProductAreaSectionId } from '@/lib/product-area-types';

interface SectionProps {
  id: ProductAreaSectionId;
  active: ProductAreaSectionId | null;
  onClick: (id: ProductAreaSectionId) => void;
  style?: React.CSSProperties;
  className?: string;
  children: ReactNode;
}

/** Wrapper that provides hover/active outline for preview sections. */
export function PreviewSection({ id, active, onClick, style, className = '', children }: SectionProps) {
  const isActive = active === id;
  return (
    <section
      data-section={id}
      onClick={(e) => {
        e.stopPropagation();
        onClick(id);
      }}
      style={style}
      className={`preview-section ${isActive ? 'active' : ''} ${className}`}
    >
      {children}
    </section>
  );
}

/** Green checkmark matching the PHP plugin. */
export function Check({ color = '#10B981' }: { color?: string }) {
  return (
    <span
      aria-hidden
      style={{
        color,
        fontWeight: 700,
        fontSize: 15,
        flexShrink: 0,
        marginTop: 1,
        display: 'inline-block',
      }}
    >
      ✓
    </span>
  );
}

/** Right-arrow glyph for CTA buttons. */
export function Arrow({ color }: { color?: string }) {
  return (
    <span aria-hidden style={{ color, marginLeft: 2 }}>
      →
    </span>
  );
}

/** Renders multiline bullet text as a checkmark list. Empty input → null. */
export function BulletList({
  text,
  color,
  textColor,
}: {
  text: string;
  color?: string;
  textColor?: string;
}) {
  const items = text.split('\n').map((l) => l.trim()).filter(Boolean);
  if (items.length === 0) return null;
  return (
    <ul style={{ listStyle: 'none', padding: 0, margin: 0 }}>
      {items.map((item, i) => (
        <li
          key={i}
          style={{
            fontSize: 15,
            lineHeight: 1.65,
            color: textColor ?? '#555',
            padding: '4px 0',
            display: 'flex',
            alignItems: 'flex-start',
            gap: 10,
          }}
        >
          <Check color={color} />
          <span>{item}</span>
        </li>
      ))}
    </ul>
  );
}

/** Simple markdown-ish renderer: preserves paragraphs and line breaks. */
export function MarkdownText({ text, style }: { text: string; style?: React.CSSProperties }) {
  if (!text.trim()) return null;
  const paragraphs = text.split(/\n\s*\n/);
  return (
    <>
      {paragraphs.map((p, i) => (
        <p key={i} style={{ margin: i === 0 ? 0 : '1em 0 0 0', whiteSpace: 'pre-wrap', ...style }}>
          {p}
        </p>
      ))}
    </>
  );
}
