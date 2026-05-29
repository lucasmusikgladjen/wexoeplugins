/**
 * Delade design-tokens (ARKITEKTURPLAN FAS 4) — builder-sidan.
 *
 * Speglar `wexoeplugins/wexoe-core/src/DesignTokens.php` exakt: samma
 * variabelnamn, samma fallback-palett, samma hex-normalisering. Så att
 * live-previewen och den publika WP-sidan ritar med EXAKT samma färger/typsnitt
 * — en källa (`core_graphic_profile`), två konsumenter.
 *
 * Använd `rootStyleFromProfile(profile)` för att injicera ett
 * `<style>:root{…}</style>`-block i preview-shellet; preview-komponenter
 * refererar sedan `var(--wexoe-color-*)` precis som PHP-pluginen kommer göra.
 *
 * Håll i synk med PHP-motsvarigheten (samma princip som schema-JSON-speglingen).
 */

/** Fallback-palett (Wexoe navy/orange) — identisk med DesignTokens::DEFAULTS. */
export const DESIGN_TOKEN_DEFAULTS = {
  '--wexoe-color-primary': '#11325D',
  '--wexoe-color-secondary': '#F28C28',
  '--wexoe-color-accent': '#F28C28',
  '--wexoe-color-bg-light': '#FFFFFF',
  '--wexoe-color-bg-dark': '#11325D',
  '--wexoe-color-text': '#11325D',
  '--wexoe-color-text-secondary': '#5A6B82',
} as const;

/** Delmängd av core_graphic_profile-fälten som driver tokens. */
export interface GraphicProfileTokens {
  color_primary?: string;
  color_secondary?: string;
  color_accent?: string;
  color_background_light?: string;
  color_background_dark?: string;
  color_text_primary?: string;
  color_text_secondary?: string;
  font_heading?: string;
  font_body?: string;
}

/** Normalisera hex (#abc → #aabbcc, ABC123 → #abc123). Ogiltig/tom → null.
 *  Speglar `Color::normalize_hex` i wexoe-core. */
export function normalizeHex(value: string | undefined | null): string | null {
  if (typeof value !== 'string') return null;
  let v = value.trim().replace(/^#/, '');
  if (/^[0-9a-fA-F]{3}$/.test(v)) {
    v = v.split('').map((c) => c + c).join('');
  }
  if (/^[0-9a-fA-F]{6}$/.test(v)) {
    return '#' + v.toLowerCase();
  }
  return null;
}

/** Sanera ett font-family-värde till CSS-säkra tecken. */
function safeFont(value: string | undefined | null): string {
  return String(value ?? '').replace(/[^A-Za-z0-9 ,'"_-]/g, '').trim();
}

/** profil → CSS-variabel-map (med fallbacks för färger). */
export function cssVariablesFromProfile(
  profile: GraphicProfileTokens | null | undefined,
): Record<string, string> {
  const p = profile ?? {};
  const hex = (value: string | undefined, fallback: string): string =>
    normalizeHex(value) ?? fallback;

  const vars: Record<string, string> = {
    '--wexoe-color-primary': hex(p.color_primary, DESIGN_TOKEN_DEFAULTS['--wexoe-color-primary']),
    '--wexoe-color-secondary': hex(p.color_secondary, DESIGN_TOKEN_DEFAULTS['--wexoe-color-secondary']),
    '--wexoe-color-accent': hex(p.color_accent, DESIGN_TOKEN_DEFAULTS['--wexoe-color-accent']),
    '--wexoe-color-bg-light': hex(p.color_background_light, DESIGN_TOKEN_DEFAULTS['--wexoe-color-bg-light']),
    '--wexoe-color-bg-dark': hex(p.color_background_dark, DESIGN_TOKEN_DEFAULTS['--wexoe-color-bg-dark']),
    '--wexoe-color-text': hex(p.color_text_primary, DESIGN_TOKEN_DEFAULTS['--wexoe-color-text']),
    '--wexoe-color-text-secondary': hex(p.color_text_secondary, DESIGN_TOKEN_DEFAULTS['--wexoe-color-text-secondary']),
  };

  const heading = safeFont(p.font_heading);
  const body = safeFont(p.font_body);
  if (heading) vars['--wexoe-font-heading'] = heading;
  if (body) vars['--wexoe-font-body'] = body;
  return vars;
}

/** profil → `:root{…}`-deklarationssträng (utan <style>-wrapper). */
export function rootStyleFromProfile(
  profile: GraphicProfileTokens | null | undefined,
): string {
  const vars = cssVariablesFromProfile(profile);
  const decls = Object.entries(vars)
    .map(([name, value]) => `${name}:${value};`)
    .join('');
  return `:root{${decls}}`;
}
