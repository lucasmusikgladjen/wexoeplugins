import { ReactNode } from 'react';
import { CustomerTypePageSectionId } from '@/lib/customer-type-types';
import { renderInlineMarkdown } from '@/lib/markdown';

interface SectionProps {
  id: CustomerTypePageSectionId;
  active: CustomerTypePageSectionId | null;
  onClick: (id: CustomerTypePageSectionId) => void;
  style?: React.CSSProperties;
  className?: string;
  children: ReactNode;
}

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

/**
 * Render the customer-type-page title with *highlight* spans mapped to an
 * orange accent — mirrors the PHP plugin's `parse_title_formatting`.
 */
export function renderTitleHighlight(text: string): ReactNode {
  if (!text) return null;
  const parts = text.split(/(\*[^*]+\*)/g);
  return parts.map((part, i) => {
    if (/^\*[^*]+\*$/.test(part)) {
      return (
        <em key={i} style={{ fontStyle: 'normal', color: '#F28C28' }}>
          {part.slice(1, -1)}
        </em>
      );
    }
    return <span key={i}>{part}</span>;
  });
}

export { renderInlineMarkdown };
