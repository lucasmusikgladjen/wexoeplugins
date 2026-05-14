'use client';

import { useCallback, useEffect, useLayoutEffect, useRef, useState } from 'react';
import { HexColorPicker, HexColorInput } from 'react-colorful';

/**
 * Field-grupp för custom inputs (multi-select, kombinerade kontroller, etc.)
 * som behöver standardiserad label + description-formatering men inte passar
 * i någon av de namngivna Field.*-primitivema.
 *
 * Använd som sista utväg — föredra Field.Text/Textarea/Select/Checkbox när
 * de räcker.
 */
export function FieldGroup({
  label,
  description,
  children,
}: {
  label: string;
  description?: string;
  children: React.ReactNode;
}) {
  return (
    <label className="block">
      <span className="text-[11px] text-gray-400">{label}</span>
      <div className="mt-0.5">{children}</div>
      {description && <span className="block mt-0.5 text-[10px] text-gray-400">{description}</span>}
    </label>
  );
}

/** Resize the textarea's height to fit its content so callers never need to
 *  scroll — the `rows` prop becomes a minimum, content extends past it. Runs
 *  in a layout effect so the new height is committed before paint. */
function useAutoGrow(
  ref: React.RefObject<HTMLTextAreaElement | null>,
  value: string,
) {
  useLayoutEffect(() => {
    const el = ref.current;
    if (!el) return;
    el.style.height = 'auto';
    el.style.height = `${el.scrollHeight}px`;
  }, [ref, value]);
}

/** Shared classes for our textareas: same colours/typography as inputs, but
 *  resize is disabled (auto-grow handles it) and overflow is hidden so the
 *  scrollbar never appears. */
const TEXTAREA_CLASS =
  'mt-0.5 block w-full rounded bg-gray-100/80 px-3 py-2 text-sm text-gray-700 placeholder:text-gray-300 focus:bg-white focus:ring-1 focus:ring-gray-200 focus:outline-none resize-none overflow-hidden';

interface InputProps {
  label: string;
  value: string;
  onChange: (value: string) => void;
  placeholder?: string;
  type?: string;
  /** Liten hjälptext under fältet, t.ex. "Tom = använd top-level H1". */
  description?: string;
}

export function FieldInput({ label, value, onChange, placeholder, type = 'text', description }: InputProps) {
  return (
    <label className="block">
      <span className="text-[11px] text-gray-400">{label}</span>
      <input
        type={type}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        placeholder={placeholder}
        className="mt-0.5 block w-full rounded bg-gray-100/80 px-3 py-2 text-sm text-gray-700 placeholder:text-gray-300 focus:bg-white focus:ring-1 focus:ring-gray-200 focus:outline-none"
      />
      {description && <span className="block mt-0.5 text-[10px] text-gray-400">{description}</span>}
    </label>
  );
}

interface TextareaProps {
  label: string;
  value: string;
  onChange: (value: string) => void;
  placeholder?: string;
  rows?: number;
  /** Kort inline-tips vid label, t.ex. "(stödjer markdown)". */
  hint?: string;
  /** Längre hjälptext under fältet. */
  description?: string;
}

export function FieldTextarea({ label, value, onChange, placeholder, rows = 8, hint, description }: TextareaProps) {
  const ref = useRef<HTMLTextAreaElement>(null);
  useAutoGrow(ref, value);
  return (
    <label className="block">
      <span className="text-[11px] text-gray-400">{label}</span>
      {hint && <span className="text-[10px] text-gray-300 ml-1">({hint})</span>}
      <textarea
        ref={ref}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        placeholder={placeholder}
        rows={rows}
        className={TEXTAREA_CLASS}
      />
      {description && <span className="block mt-0.5 text-[10px] text-gray-400">{description}</span>}
    </label>
  );
}

interface RichTextareaProps {
  label: string;
  value: string;
  onChange: (value: string) => void;
  placeholder?: string;
  rows?: number;
  hint?: string;
}

/** Long-text editor for fields that support inline markdown
 *  (`**bold**`, `*italic*`, `[text](url)`). Wraps the user's selection on
 *  toolbar click — or inserts a placeholder if there is no selection — and
 *  restores the caret around the new text. */
export function RichTextarea({
  label,
  value,
  onChange,
  placeholder,
  rows = 8,
  hint,
}: RichTextareaProps) {
  const textareaRef = useRef<HTMLTextAreaElement>(null);
  useAutoGrow(textareaRef, value);

  const apply = useCallback(
    (kind: 'bold' | 'italic' | 'link') => {
      const ta = textareaRef.current;
      if (!ta) return;
      const start = ta.selectionStart;
      const end = ta.selectionEnd;
      const before = value.slice(0, start);
      const selected = value.slice(start, end);
      const after = value.slice(end);

      let inserted: string;
      let cursorStart: number;
      let cursorEnd: number;

      if (kind === 'bold') {
        const inner = selected || 'fet text';
        inserted = `**${inner}**`;
        cursorStart = start + 2;
        cursorEnd = cursorStart + inner.length;
      } else if (kind === 'italic') {
        const inner = selected || 'kursiv text';
        inserted = `*${inner}*`;
        cursorStart = start + 1;
        cursorEnd = cursorStart + inner.length;
      } else {
        const url = window.prompt('URL:', 'https://');
        if (!url) return;
        const trimmed = url.trim();
        if (!trimmed) return;
        const linkText = selected || 'länktext';
        inserted = `[${linkText}](${trimmed})`;
        cursorStart = start + 1;
        cursorEnd = cursorStart + linkText.length;
      }

      const next = before + inserted + after;
      onChange(next);

      // Restore selection on the inner text after React commits the new value.
      requestAnimationFrame(() => {
        const el = textareaRef.current;
        if (!el) return;
        el.focus();
        el.setSelectionRange(cursorStart, cursorEnd);
      });
    },
    [value, onChange],
  );

  const onKeyDown = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
    if (!(e.metaKey || e.ctrlKey)) return;
    const k = e.key.toLowerCase();
    if (k === 'b') {
      e.preventDefault();
      apply('bold');
    } else if (k === 'i') {
      e.preventDefault();
      apply('italic');
    } else if (k === 'k') {
      e.preventDefault();
      apply('link');
    }
  };

  return (
    <label className="block">
      <div className="flex items-center justify-between gap-2">
        <span className="text-[11px] text-gray-400">
          {label}
          {hint && <span className="text-[10px] text-gray-300 ml-1">({hint})</span>}
        </span>
        <div className="flex items-center gap-0.5 -mb-0.5">
          <ToolbarButton
            label="B"
            title="Fet (Cmd/Ctrl+B)"
            onClick={() => apply('bold')}
            bold
          />
          <ToolbarButton
            label="I"
            title="Kursiv (Cmd/Ctrl+I)"
            onClick={() => apply('italic')}
            italic
          />
          <ToolbarButton
            label="🔗"
            title="Länk (Cmd/Ctrl+K)"
            onClick={() => apply('link')}
          />
        </div>
      </div>
      <textarea
        ref={textareaRef}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        onKeyDown={onKeyDown}
        placeholder={placeholder}
        rows={rows}
        className={TEXTAREA_CLASS}
      />
    </label>
  );
}

function ToolbarButton({
  label,
  title,
  onClick,
  bold,
  italic,
}: {
  label: string;
  title: string;
  onClick: () => void;
  bold?: boolean;
  italic?: boolean;
}) {
  return (
    <button
      type="button"
      onClick={onClick}
      title={title}
      className="px-1.5 py-0.5 text-[11px] text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded transition-colors leading-none"
      style={{
        fontWeight: bold ? 700 : undefined,
        fontStyle: italic ? 'italic' : undefined,
        minWidth: 18,
      }}
    >
      {label}
    </button>
  );
}

interface SelectProps<T extends string> {
  label: string;
  value: T;
  onChange: (value: T) => void;
  options: ReadonlyArray<{ value: T; label: string }>;
  description?: string;
}

export function FieldSelect<T extends string = string>({ label, value, onChange, options, description }: SelectProps<T>) {
  return (
    <label className="block">
      <span className="text-[11px] text-gray-400">{label}</span>
      <select
        value={value}
        onChange={(e) => onChange(e.target.value as T)}
        className="mt-0.5 block w-full rounded bg-gray-100/80 px-3 py-2 text-sm text-gray-700 focus:bg-white focus:ring-1 focus:ring-gray-200 focus:outline-none"
      >
        {options.map((opt) => (
          <option key={opt.value} value={opt.value}>{opt.label}</option>
        ))}
      </select>
      {description && <span className="block mt-0.5 text-[10px] text-gray-400">{description}</span>}
    </label>
  );
}

interface ColorProps {
  label: string;
  value: string;
  onChange: (value: string) => void;
  /** Shown as the fallback the PHP plugin would use when value is empty. */
  defaultColor?: string;
}

/** Compact colour field: swatch + HEX text input + clear button, with a
 *  popover HEX-only picker (no RGB/HSL tabs — react-colorful's
 *  HexColorPicker is pure HEX). Empty string means "no override"; the
 *  swatch shows a checker pattern and the hex input shows a `default`
 *  placeholder. */
export function FieldColor({ label, value, onChange, defaultColor }: ColorProps) {
  const isSet = !!value.trim();
  const [open, setOpen] = useState(false);
  const rootRef = useRef<HTMLDivElement>(null);

  // Close the popover on outside click.
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

  // The picker needs a valid 6-digit hex to render meaningfully. Fall back
  // to the plugin default so the picker opens on the effective colour.
  const pickerValue = isSet && /^#[0-9a-fA-F]{6}$/.test(value.trim())
    ? value.trim()
    : (defaultColor || '#11325D');

  return (
    <label className="block">
      <span className="text-[11px] text-gray-400">{label}</span>
      <div ref={rootRef} className="relative mt-0.5 flex items-center gap-2">
        {/* Swatch — click toggles the HEX popover picker */}
        <button
          type="button"
          onClick={() => setOpen((v) => !v)}
          className="w-8 h-8 rounded border border-gray-200 flex-shrink-0 cursor-pointer"
          aria-label={`${label} picker`}
          style={
            isSet
              ? { background: value }
              : {
                  backgroundColor: '#f9fafb',
                  backgroundImage:
                    'linear-gradient(45deg, #e5e7eb 25%, transparent 25%), linear-gradient(-45deg, #e5e7eb 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #e5e7eb 75%), linear-gradient(-45deg, transparent 75%, #e5e7eb 75%)',
                  backgroundSize: '8px 8px',
                  backgroundPosition: '0 0, 0 4px, 4px -4px, -4px 0',
                }
          }
        />

        <input
          type="text"
          value={value}
          onChange={(e) => onChange(e.target.value)}
          placeholder="default"
          className="flex-1 min-w-0 px-2 py-1.5 text-xs font-mono bg-gray-100/80 rounded text-gray-700 placeholder:text-gray-300 focus:bg-white focus:ring-1 focus:ring-gray-200 focus:outline-none"
        />

        {isSet && (
          <button
            type="button"
            onClick={() => onChange('')}
            className="text-gray-300 hover:text-red-400 text-sm leading-none px-1 flex-shrink-0"
            title="Rensa (använd default)"
          >
            ×
          </button>
        )}

        {/* Popover: HEX-only picker + HEX input */}
        {open && (
          <div className="absolute top-full left-0 z-30 mt-2 p-3 bg-white rounded-lg shadow-xl border border-gray-200 w-[220px]">
            <HexColorPicker
              color={pickerValue}
              onChange={(next) => onChange(next)}
              style={{ width: '100%', height: 160 }}
            />
            <div className="mt-3 flex items-center gap-2">
              <span className="text-[10px] uppercase tracking-wider text-gray-400">Hex</span>
              <HexColorInput
                color={pickerValue}
                onChange={(next) => onChange(next)}
                prefixed
                className="flex-1 px-2 py-1 text-xs font-mono bg-gray-100/80 rounded text-gray-700 focus:bg-white focus:ring-1 focus:ring-gray-200 focus:outline-none uppercase"
              />
            </div>
          </div>
        )}
      </div>
    </label>
  );
}

interface CheckboxProps {
  label: string;
  checked: boolean;
  onChange: (checked: boolean) => void;
}

export function FieldCheckbox({ label, checked, onChange }: CheckboxProps) {
  return (
    <label className="flex items-center gap-1.5 cursor-pointer">
      <input
        type="checkbox"
        checked={checked}
        onChange={(e) => onChange(e.target.checked)}
        className="rounded border-gray-300 text-gray-500 focus:ring-0 h-3.5 w-3.5"
      />
      <span className="text-[11px] text-gray-400">{label}</span>
    </label>
  );
}
