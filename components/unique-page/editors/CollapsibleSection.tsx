'use client';

import { ReactNode, useState } from 'react';

interface Props {
  title: string;
  /** True = sektion visas i preview/render. Optional för sektioner som inte har toggle (metadata). */
  visible?: boolean;
  onToggleVisible?: (v: boolean) => void;
  /** Hint som visas till höger i header. */
  hint?: string;
  defaultOpen?: boolean;
  children: ReactNode;
}

export default function CollapsibleSection({
  title,
  visible,
  onToggleVisible,
  hint,
  defaultOpen,
  children,
}: Props) {
  const [open, setOpen] = useState(defaultOpen ?? (visible ?? true));
  const hasToggle = onToggleVisible !== undefined;

  return (
    <div className="border border-gray-100 rounded-md overflow-hidden bg-white">
      <div className="flex items-center gap-2 px-3 py-2 bg-gray-50 border-b border-gray-100">
        <button
          type="button"
          onClick={() => setOpen((o) => !o)}
          className="flex-1 flex items-center gap-2 text-left text-sm font-medium text-gray-800 hover:text-gray-900"
        >
          <svg
            width="10"
            height="10"
            viewBox="0 0 12 12"
            className={`text-gray-400 transition-transform ${open ? 'rotate-90' : ''}`}
          >
            <path d="M4 2 L8 6 L4 10" stroke="currentColor" strokeWidth="1.5" fill="none" strokeLinecap="round" strokeLinejoin="round" />
          </svg>
          {title}
        </button>
        {hint && <span className="text-[10px] uppercase tracking-wider text-gray-300">{hint}</span>}
        {hasToggle && (
          <label className="flex items-center gap-1.5 text-[11px] text-gray-500">
            <input
              type="checkbox"
              checked={!!visible}
              onChange={(e) => onToggleVisible?.(e.target.checked)}
              className="h-3.5 w-3.5"
            />
            Visa
          </label>
        )}
      </div>
      {open && <div className="p-3 space-y-3">{children}</div>}
    </div>
  );
}

/** Liten input-rad-helper för repetitivt mönster. */
export function FieldRow({
  label,
  children,
  help,
}: {
  label: string;
  children: ReactNode;
  help?: string;
}) {
  return (
    <div>
      <label className="block text-xs font-medium text-gray-600 mb-1">{label}</label>
      {children}
      {help && <p className="mt-1 text-[11px] text-gray-400">{help}</p>}
    </div>
  );
}

export function TextInput({ value, onChange, placeholder }: { value: string; onChange: (v: string) => void; placeholder?: string }) {
  return (
    <input
      type="text"
      value={value}
      onChange={(e) => onChange(e.target.value)}
      placeholder={placeholder}
      className="w-full px-2 py-1.5 text-sm border border-gray-200 rounded-md focus:border-gray-400 focus:outline-none"
    />
  );
}

export function TextareaInput({ value, onChange, rows = 4, placeholder }: { value: string; onChange: (v: string) => void; rows?: number; placeholder?: string }) {
  return (
    <textarea
      value={value}
      onChange={(e) => onChange(e.target.value)}
      rows={rows}
      placeholder={placeholder}
      className="w-full px-2 py-1.5 text-sm border border-gray-200 rounded-md focus:border-gray-400 focus:outline-none"
    />
  );
}

export function SelectInput<T extends string>({
  value, onChange, options,
}: {
  value: T;
  onChange: (v: T) => void;
  options: ReadonlyArray<{ value: T; label: string }>;
}) {
  return (
    <select
      value={value}
      onChange={(e) => onChange(e.target.value as T)}
      className="w-full px-2 py-1.5 text-sm border border-gray-200 rounded-md focus:border-gray-400 focus:outline-none"
    >
      {options.map((o) => (
        <option key={o.value} value={o.value}>{o.label}</option>
      ))}
    </select>
  );
}

export function CheckboxInput({ checked, onChange, label }: { checked: boolean; onChange: (v: boolean) => void; label: string }) {
  return (
    <label className="inline-flex items-center gap-2 text-xs text-gray-700">
      <input type="checkbox" checked={checked} onChange={(e) => onChange(e.target.checked)} className="h-4 w-4" />
      {label}
    </label>
  );
}
