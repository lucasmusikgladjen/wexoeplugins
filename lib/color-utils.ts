/**
 * Colour helpers mirroring the PHP plugin's auto-contrast behaviour.
 *
 * The plugin computes whether text on a given background should be light or
 * dark based on YIQ luminance and emits `--*-text` / `--*-text-secondary`
 * CSS variables. We replicate the same logic here so the preview matches the
 * rendered page when users override colours.
 */

function parseHex(hex: string): { r: number; g: number; b: number } | null {
  const v = (hex || '').trim().replace('#', '');
  if (v.length !== 6) return null;
  const n = parseInt(v, 16);
  if (Number.isNaN(n)) return null;
  return {
    r: (n >> 16) & 0xff,
    g: (n >> 8) & 0xff,
    b: n & 0xff,
  };
}

function yiq(hex: string): number | null {
  const rgb = parseHex(hex);
  if (!rgb) return null;
  return (rgb.r * 299 + rgb.g * 587 + rgb.b * 114) / 1000;
}

/** True when the colour is dark enough that light text should be used. */
export function isDark(hex: string): boolean {
  const y = yiq(hex);
  if (y === null) return false;
  return y < 128;
}

/** Primary text colour for a given background. */
export function textOn(bg: string): string {
  return isDark(bg) ? '#FFFFFF' : '#11325D';
}

/** Secondary/muted text colour for a given background. */
export function secondaryTextOn(bg: string): string {
  return isDark(bg) ? 'rgba(255,255,255,0.75)' : '#555555';
}

/** Border colour for a given background. */
export function borderOn(bg: string): string {
  return isDark(bg) ? 'rgba(255,255,255,0.12)' : '#E8E8E8';
}

/** Resolve a user override or fall back to a default. */
export function colorOr(value: string | undefined | null, fallback: string): string {
  const v = (value || '').trim();
  if (!v) return fallback;
  // Accept both "#RRGGBB" and "RRGGBB"
  return v.startsWith('#') ? v : `#${v}`;
}
