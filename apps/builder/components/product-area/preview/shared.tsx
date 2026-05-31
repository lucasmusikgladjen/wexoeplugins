import { ReactNode } from 'react';
import { ProductAreaSectionId } from '@/lib/product-area-types';
import { renderInlineMarkdown, renderMarkdown } from '@/lib/markdown';

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

/** Renders multiline bullet text as a checkmark list. Each line passes
 *  through inline markdown (bold/italic/links) — mirrors the PHP plugin's
 *  per-line `wexoe_pa_md` rendering. Empty input → null. */
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
          <span>{renderInlineMarkdown(item)}</span>
        </li>
      ))}
    </ul>
  );
}

/** Renders prose markdown — paragraphs split on blank lines, single newlines
 *  become <br/>, inline bold/italic/links/code/strike via `wexoe_pa_md`-style
 *  rules. Style is applied to each paragraph. */
export function MarkdownText({ text, style }: { text: string; style?: React.CSSProperties }) {
  if (!text.trim()) return null;
  const paragraphs = text.split(/\n\s*\n/);
  return (
    <>
      {paragraphs.map((p, pi) => {
        const lines = p.split('\n');
        return (
          <p key={pi} style={{ margin: pi === 0 ? 0 : '1em 0 0 0', ...style }}>
            {lines.map((line, li) => (
              <span key={li}>
                {li > 0 && <br />}
                {renderInlineMarkdown(line)}
              </span>
            ))}
          </p>
        );
      })}
    </>
  );
}

// Re-export renderMarkdown so other previews can opt in without importing
// from `@/lib/markdown` directly.
export { renderMarkdown, renderInlineMarkdown };
