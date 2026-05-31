'use client';

import { useState, useEffect, useRef, ReactNode } from 'react';

interface Props {
  /** 1-based index shown on the left of the header row. */
  index: number;
  /** The inline title input shown in the header — stays editable while collapsed. */
  title: string;
  onTitleChange: (value: string) => void;
  titlePlaceholder?: string;
  onMoveUp?: () => void;
  onMoveDown?: () => void;
  canMoveUp?: boolean;
  canMoveDown?: boolean;
  onRemove: () => void;
  removeTitle?: string;
  /** Whether the body starts expanded. Usually: empty cards open, filled cards closed. */
  defaultOpen?: boolean;
  children: ReactNode;
}

/**
 * Compact card shell used by ContentBlocksEditor, ProductsEditor and
 * SolutionsEditor. The header row stays visible and interactive when the
 * card is collapsed so users can rename, reorder and remove items without
 * expanding the body.
 *
 * Click targets for toggling (roughly in order of prominence):
 *   1. Chevron + number button on the left
 *   2. Blank flex-1 area between the title input and the ▲▼✕ buttons
 *   3. Focusing the title input (opens if currently closed)
 *
 * The title input itself stays fully editable — typing never toggles. The
 * ▲▼ and ✕ buttons stopPropagation so they never toggle either.
 *
 * Auto-closes on document-level mousedown outside the card root so opening
 * another card or clicking any top-level field collapses this one.
 */
export default function CollapsibleCard({
  index,
  title,
  onTitleChange,
  titlePlaceholder,
  onMoveUp,
  onMoveDown,
  canMoveUp = false,
  canMoveDown = false,
  onRemove,
  removeTitle = 'Ta bort',
  defaultOpen = false,
  children,
}: Props) {
  const [open, setOpen] = useState(defaultOpen);
  const rootRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!open) return;
    const handler = (e: MouseEvent) => {
      if (!rootRef.current) return;
      if (rootRef.current.contains(e.target as Node)) return;
      setOpen(false);
    };
    document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, [open]);

  const toggle = () => setOpen((v) => !v);

  return (
    <div ref={rootRef} className="bg-gray-50/70 rounded-lg">
      {/* Header row — always visible */}
      <div className="flex items-stretch gap-1 px-2 py-1">
        {/* Chevron + number toggle button */}
        <button
          type="button"
          onClick={toggle}
          aria-label={open ? 'Kollapsa' : 'Expandera'}
          className="flex items-center gap-1.5 px-2 py-2 rounded text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors flex-shrink-0"
        >
          <svg
            width="14"
            height="14"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2.5"
            strokeLinecap="round"
            strokeLinejoin="round"
            className={`transition-transform ${open ? 'rotate-90' : ''}`}
          >
            <path d="M9 6l6 6-6 6" />
          </svg>
          <span className="text-[11px] tabular-nums">{index + 1}</span>
        </button>

        {/* Constrained title input — focusing opens, typing doesn't toggle */}
        <input
          value={title}
          onChange={(e) => onTitleChange(e.target.value)}
          onFocus={() => {
            if (!open) setOpen(true);
          }}
          className="w-44 flex-shrink-0 text-sm bg-transparent outline-none text-gray-700 placeholder:text-gray-300 px-1"
          placeholder={titlePlaceholder}
        />

        {/* Blank flex-1 area — clicking anywhere here toggles the card */}
        <button
          type="button"
          onClick={toggle}
          aria-label={open ? 'Kollapsa' : 'Expandera'}
          className="flex-1 min-w-0 cursor-pointer"
        />

        {(onMoveUp || onMoveDown) && (
          <div className="flex items-center gap-0.5 flex-shrink-0">
            {onMoveUp && (
              <button
                type="button"
                onClick={(e) => {
                  e.stopPropagation();
                  onMoveUp();
                }}
                disabled={!canMoveUp}
                className="text-gray-300 hover:text-gray-500 disabled:opacity-30 text-[11px] px-0.5"
                title="Flytta upp"
              >
                ▲
              </button>
            )}
            {onMoveDown && (
              <button
                type="button"
                onClick={(e) => {
                  e.stopPropagation();
                  onMoveDown();
                }}
                disabled={!canMoveDown}
                className="text-gray-300 hover:text-gray-500 disabled:opacity-30 text-[11px] px-0.5"
                title="Flytta ner"
              >
                ▼
              </button>
            )}
          </div>
        )}

        <button
          type="button"
          onClick={(e) => {
            e.stopPropagation();
            onRemove();
          }}
          className="text-gray-300 hover:text-red-400 text-[11px] px-0.5 ml-0.5 flex-shrink-0"
          title={removeTitle}
        >
          ✕
        </button>
      </div>

      {/* Body — collapsible */}
      {open && <div className="px-3 pb-3 space-y-3">{children}</div>}
    </div>
  );
}
