'use client';

import { ReactNode, useState } from 'react';
import { FieldCheckbox } from './FieldInput';

interface Props {
  /** Header text (renders at h3-size to match the existing editor visual). */
  title: string;
  /**
   * Preview-visibility. When provided, a "Visa" checkbox is rendered to the
   * right of the title. Independent from the collapse state — toggling Visa
   * affects the live preview, not the editor body.
   */
  visible?: boolean;
  onToggleVisible?: (v: boolean) => void;
  /**
   * Initial collapse state. Defaults to expanded when `visible` is true (or
   * absent) and collapsed when `visible` is false — mirrors today's "hidden
   * sections sit out of the way" behaviour without coupling collapse to
   * preview visibility.
   */
  defaultOpen?: boolean;
  children: ReactNode;
}

/**
 * Shared section wrapper for every right-pane editor. Renders the h3 header,
 * a small chevron to indicate collapsibility, and an optional preview-visa
 * toggle. The whole title row is clickable — typing on the chevron or the
 * empty space beside the title toggles the body.
 *
 * The collapse is purely visual: hiding the body doesn't disable the section
 * in the preview. That stays under the caller's control via `visible`.
 */
export default function EditorSection({
  title,
  visible,
  onToggleVisible,
  defaultOpen,
  children,
}: Props) {
  const [open, setOpen] = useState(defaultOpen ?? (visible ?? true));
  const hasToggle = onToggleVisible !== undefined;

  return (
    <div className="space-y-3">
      <div className="flex items-center gap-3">
        <button
          type="button"
          onClick={() => setOpen((o) => !o)}
          aria-expanded={open}
          className="flex-1 flex items-center gap-2 text-left group"
        >
          <h3 className="text-xl font-bold text-gray-900">{title}</h3>
          <svg
            width="12"
            height="12"
            viewBox="0 0 12 12"
            className={`text-gray-300 group-hover:text-gray-500 transition-transform ${
              open ? 'rotate-90' : ''
            }`}
            aria-hidden
          >
            <path
              d="M4 2 L8 6 L4 10"
              stroke="currentColor"
              strokeWidth="1.5"
              fill="none"
              strokeLinecap="round"
              strokeLinejoin="round"
            />
          </svg>
        </button>
        {hasToggle && (
          <FieldCheckbox label="Visa" checked={!!visible} onChange={onToggleVisible} />
        )}
      </div>
      {open && <div className="space-y-3">{children}</div>}
    </div>
  );
}
