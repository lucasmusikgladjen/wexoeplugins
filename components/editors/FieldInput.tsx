'use client';

interface InputProps {
  label: string;
  value: string;
  onChange: (value: string) => void;
  placeholder?: string;
  type?: string;
}

export function FieldInput({ label, value, onChange, placeholder, type = 'text' }: InputProps) {
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
    </label>
  );
}

interface TextareaProps {
  label: string;
  value: string;
  onChange: (value: string) => void;
  placeholder?: string;
  rows?: number;
  hint?: string;
}

export function FieldTextarea({ label, value, onChange, placeholder, rows = 4, hint }: TextareaProps) {
  return (
    <label className="block">
      <span className="text-[11px] text-gray-400">{label}</span>
      {hint && <span className="text-[10px] text-gray-300 ml-1">({hint})</span>}
      <textarea
        value={value}
        onChange={(e) => onChange(e.target.value)}
        placeholder={placeholder}
        rows={rows}
        className="mt-0.5 block w-full rounded bg-gray-100/80 px-3 py-2 text-sm text-gray-700 placeholder:text-gray-300 focus:bg-white focus:ring-1 focus:ring-gray-200 focus:outline-none resize-y"
      />
    </label>
  );
}

interface SelectProps {
  label: string;
  value: string;
  onChange: (value: string) => void;
  options: { value: string; label: string }[];
}

export function FieldSelect({ label, value, onChange, options }: SelectProps) {
  return (
    <label className="block">
      <span className="text-[11px] text-gray-400">{label}</span>
      <select
        value={value}
        onChange={(e) => onChange(e.target.value)}
        className="mt-0.5 block w-full rounded bg-gray-100/80 px-3 py-2 text-sm text-gray-700 focus:bg-white focus:ring-1 focus:ring-gray-200 focus:outline-none"
      >
        {options.map((opt) => (
          <option key={opt.value} value={opt.value}>{opt.label}</option>
        ))}
      </select>
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

/** Compact colour picker: swatch + native picker + hex text + clear.
 *  Empty string means "no override" — the swatch shows a checker pattern and
 *  the hex input shows a `default` placeholder. */
export function FieldColor({ label, value, onChange, defaultColor }: ColorProps) {
  const isSet = !!value.trim();
  const swatchColor = isSet ? value : (defaultColor || '#11325D');

  return (
    <label className="block">
      <span className="text-[11px] text-gray-400">{label}</span>
      <div className="mt-0.5 flex items-center gap-2">
        <div className="relative w-8 h-8 flex-shrink-0">
          <input
            type="color"
            value={swatchColor}
            onChange={(e) => onChange(e.target.value)}
            className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
            aria-label={`${label} picker`}
          />
          <div
            className="w-8 h-8 rounded border border-gray-200 pointer-events-none"
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
        </div>
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
