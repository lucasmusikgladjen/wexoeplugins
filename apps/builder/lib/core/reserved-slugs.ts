/**
 * Reserverade slugs som inte får användas för cms_pages.
 *
 * Speglar PHP-konstanten `Wexoe\Core\Constants::RESERVED_SLUGS`.
 * Måste hållas i synk manuellt.
 */

export const RESERVED_SLUGS: readonly string[] = [
  'kontakt',
  'nedladdningar',
  'om-oss-statisk',
];

export function isReservedSlug(slug: string): boolean {
  return RESERVED_SLUGS.includes(slug.toLowerCase().trim());
}
