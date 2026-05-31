import { ReactNode } from 'react';
import { PartnerPageSectionId } from '@/lib/partner-types';
import { renderInlineMarkdown } from '@/lib/markdown';

/**
 * Per-sidtyps PreviewSection-wrapper. Identisk struktur som
 * `components/customer-type/preview/shared.tsx::PreviewSection` men typad
 * mot `PartnerPageSectionId`-unionen så TypeScript fångar felstavade
 * sektion-IDs. CSS-klassen `.preview-section` är delad i `globals.css`
 * och styr hover/active-outline.
 */
interface SectionProps {
  id: PartnerPageSectionId;
  active: PartnerPageSectionId | null;
  onClick: (id: PartnerPageSectionId) => void;
  style?: React.CSSProperties;
  className?: string;
  children: ReactNode;
}

export function PreviewSection({
  id,
  active,
  onClick,
  style,
  className = '',
  children,
}: SectionProps) {
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

export { renderInlineMarkdown };

/**
 * Wexoe-färgkonstanter för previews. Samma värden som `:root`-variablerna
 * i prototype_4.html, hårdkodade här eftersom CSS-variabler inte är
 * tillgängliga från PHP-pluginets style.css (cross-repo).
 */
export const WEXOE_COLORS = {
  main: '#11325D',
  secondary: '#F28C28',
  green: '#10B981',
  light: '#F5F6F8',
  border: '#E5E7EB',
  text: '#333',
  textMuted: '#6B7280',
} as const;
