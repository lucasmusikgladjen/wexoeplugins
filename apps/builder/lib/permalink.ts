/**
 * Central permalink-resolver (builder-sidan).
 *
 * Speglar `wexoe-core/src/Helpers/Permalink.php` exakt — håll de två i synk.
 * Detta är den ENDA källan till hur en entitets publika URL ser ut i buildern.
 * Editorer ska visa härledda URL:er härifrån i stället för att låta redaktören
 * skriva case-/CTA-URL:er för hand.
 *
 * Multi-country: `urlPrefix` prependas bara när den är ifylld. Default-landet
 * (idag SE) har tom prefix → URL:en lämnas oförändrad.
 */

/** Route-mönster per entitet. {slug} ersätts med en URL-encodad slug. */
const PATTERNS: Record<string, string> = {
  // cms_cases är den kanoniska case-entiteten (aliasen 'cases'/'case_pages'
  // konsoliderades bort i PR 2 — speglar wexoe-core Permalink.php).
  cms_cases: '/case/{slug}/',
};

/** Ren slug → publik path. Tom slug eller okänd entitet → ''. */
export function buildPermalink(entity: string, slug: string, urlPrefix = ''): string {
  const s = (slug ?? '').trim();
  if (s === '') return '';
  const pattern = PATTERNS[entity];
  if (!pattern) return '';
  const path = pattern.replace('{slug}', encodeURIComponent(s));
  const prefix = (urlPrefix ?? '').replace(/^[/\s]+|[/\s]+$/g, '');
  return prefix === '' ? path : `/${prefix}${path}`;
}

/**
 * Publik URL för ett record. Preferensordning:
 *   1. legacyExternalUrl (cases som fortfarande bor på gammal WP/PDF)
 *   2. byggd path från slug
 *   3. '' (inget visningsbart)
 */
export function permalinkForRecord(
  entity: string,
  record: { slug?: string; legacyExternalUrl?: string },
  urlPrefix = '',
): string {
  const legacy = (record.legacyExternalUrl ?? '').trim();
  if (legacy !== '') return legacy;
  return buildPermalink(entity, record.slug ?? '', urlPrefix);
}

/** Bekvämlighet: publik URL för ett case givet dess slug. */
export function caseUrl(slug: string, urlPrefix = ''): string {
  return buildPermalink('cms_cases', slug, urlPrefix);
}
