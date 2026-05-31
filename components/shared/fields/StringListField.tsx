'use client';

/**
 * StringListField — generisk editor för en lista av strängar.
 *
 * Används för Airtable `type:lines`-fält (en bullet per rad) där state-formen
 * är en `string[]` snarare än en multiline textarea. Bättre UX än textarea
 * eftersom raderna kan flyttas och tas bort individuellt utan att redigera
 * radbrytningar.
 *
 * Konventioner:
 *   - Värdet är `string[]`. Tomma strängar tillåts under redigering men ska
 *     filtreras bort av callern vid spar (typiskt i den deterministiska transformen).
 *   - Add-knappen lägger till en tom rad sist och fokuserar inte automatiskt
 *     (för att inte stjäla fokus från andra fält i samma sektion).
 *   - Flytta upp/ner finns. Drag-and-drop saknas i v1 — pilarna räcker för
 *     korta listor (typiskt 3–8 items).
 *
 * Återanvändbar för alla sidtyper med items-array-fält (benefits, bullets,
 * features, …). Bredare än `Field.Textarea` när items är logiskt separata.
 */

interface Props {
  label: string;
  value: string[];
  onChange: (next: string[]) => void;
  placeholder?: string;
  /** Liten gråtext under listan, t.ex. "Markdown stöds — **bold** för highlight." */
  description?: string;
  /** Knapptext, default "+ Lägg till rad". */
  addLabel?: string;
}

export default function StringListField({
  label,
  value,
  onChange,
  placeholder,
  description,
  addLabel = '+ Lägg till rad',
}: Props) {
  const update = (i: number, next: string) => {
    onChange(value.map((v, idx) => (idx === i ? next : v)));
  };
  const remove = (i: number) => {
    onChange(value.filter((_, idx) => idx !== i));
  };
  const move = (i: number, direction: -1 | 1) => {
    const j = i + direction;
    if (j < 0 || j >= value.length) return;
    const next = [...value];
    const [m] = next.splice(i, 1);
    next.splice(j, 0, m);
    onChange(next);
  };
  const add = () => onChange([...value, '']);

  return (
    <div>
      <span className="text-[11px] text-gray-400">{label}</span>
      <div className="mt-0.5 space-y-1">
        {value.length === 0 && (
          <p className="text-[11px] text-gray-300 italic px-1 py-1.5">Inga rader — klicka &quot;Lägg till&quot; nedan.</p>
        )}
        {value.map((item, i) => (
          <div key={i} className="flex items-center gap-1">
            <input
              type="text"
              value={item}
              onChange={(e) => update(i, e.target.value)}
              placeholder={placeholder}
              className="flex-1 min-w-0 px-3 py-1.5 text-sm rounded bg-gray-100/80 text-gray-700 placeholder:text-gray-300 focus:bg-white focus:ring-1 focus:ring-gray-200 focus:outline-none"
            />
            <button
              type="button"
              onClick={() => move(i, -1)}
              disabled={i === 0}
              className="text-gray-300 hover:text-gray-500 disabled:opacity-30 text-[11px] px-1"
              title="Flytta upp"
            >
              ▲
            </button>
            <button
              type="button"
              onClick={() => move(i, 1)}
              disabled={i === value.length - 1}
              className="text-gray-300 hover:text-gray-500 disabled:opacity-30 text-[11px] px-1"
              title="Flytta ner"
            >
              ▼
            </button>
            <button
              type="button"
              onClick={() => remove(i)}
              className="text-gray-300 hover:text-red-400 text-[11px] px-1"
              title="Ta bort"
            >
              ✕
            </button>
          </div>
        ))}
      </div>
      <button
        type="button"
        onClick={add}
        className="mt-1 text-[11px] text-gray-400 hover:text-gray-700 transition-colors"
      >
        {addLabel}
      </button>
      {description && (
        <p className="mt-0.5 text-[10px] text-gray-400">{description}</p>
      )}
    </div>
  );
}
