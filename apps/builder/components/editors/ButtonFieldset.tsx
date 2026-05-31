'use client';

interface Segment {
  value: string;
  onChange: (value: string) => void;
  placeholder?: string;
}

interface Props {
  /** Label shown above the field, same styling as FieldInput. */
  label: string;
  segments: Segment[];
}

/**
 * Groups two related text inputs under a single label. Visually looks and
 * behaves like a regular FieldInput — same gray background, label style,
 * rounded corners, focus ring — but the inner row is split into segments
 * by thin vertical dividers so the user can tell the inputs belong
 * together without any extra chrome.
 *
 * Each input gets a composed `aria-label` (e.g. "Knapp 1 Text") so screen
 * readers can distinguish them. We deliberately don't use a wrapping
 * `<label>` element because it can only label its first form control,
 * which would leave the second input without an accessible name.
 */
export default function ButtonFieldset({ label, segments }: Props) {
  return (
    <div className="block">
      <span className="text-[11px] text-gray-400">{label}</span>
      <div className="mt-0.5 flex bg-gray-100/80 rounded divide-x divide-gray-200 focus-within:bg-white focus-within:ring-1 focus-within:ring-gray-200">
        {segments.map((seg, i) => (
          <input
            key={i}
            type="text"
            value={seg.value}
            onChange={(e) => seg.onChange(e.target.value)}
            placeholder={seg.placeholder}
            aria-label={seg.placeholder ? `${label} ${seg.placeholder}` : label}
            className="flex-1 min-w-0 px-3 py-2 text-sm text-gray-700 placeholder:text-gray-300 bg-transparent outline-none"
          />
        ))}
      </div>
    </div>
  );
}
