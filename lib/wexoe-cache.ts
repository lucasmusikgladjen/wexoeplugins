/**
 * Fire-and-forget cache invalidation against the Wexoe Core WordPress plugin.
 *
 * After a successful publish (landing page or product area), we POST to
 *
 *   ${WEXOE_CORE_WEBHOOK_URL}
 *   header: X-Wexoe-Webhook-Secret: ${WEXOE_CORE_WEBHOOK_SECRET}
 *   body:   { "entities": ["landing_pages", "lp_tabs", ...] }
 *
 * The endpoint clears both the WP transient and the stale-fallback row
 * (wp_options "wexoe_core_stale_entity_*") so the next page load fetches
 * fresh data straight from Airtable.
 *
 * Failures are logged but never thrown — the publish itself already
 * succeeded by the time this runs, and a missing webhook config in dev or
 * a slow WP shouldn't bubble up to the editor.
 */

export {
  CUSTOMER_TYPE_PAGE_ENTITIES,
  LP_ENTITIES,
  PA_ENTITIES,
  SSOT_ENTITIES,
  CMS_PAGES_ENTITIES,
  CASE_ENTITIES,
  PARTNER_ENTITIES,
} from './wexoe-cache-entities';

const WEBHOOK_URL = process.env.WEXOE_CORE_WEBHOOK_URL;
const WEBHOOK_SECRET = process.env.WEXOE_CORE_WEBHOOK_SECRET;
const WEBHOOK_TIMEOUT_MS = 5000;

export async function invalidateWexoeCoreCache(
  entities: readonly string[],
  context: string,
): Promise<void> {
  if (!WEBHOOK_URL || !WEBHOOK_SECRET) {
    // Webhook is opt-in: missing config is normal in local dev. Log once
    // per call so it's traceable but don't fail the publish.
    console.info(
      `[wexoe-cache:${context}] Webhook ej konfigurerad (WEXOE_CORE_WEBHOOK_URL/SECRET) — hoppar över cache-invalidering.`,
    );
    return;
  }

  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), WEBHOOK_TIMEOUT_MS);

  try {
    const res = await fetch(WEBHOOK_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Wexoe-Webhook-Secret': WEBHOOK_SECRET,
      },
      body: JSON.stringify({ entities }),
      signal: controller.signal,
    });

    if (!res.ok) {
      const body = await res.text().catch(() => '');
      console.error(
        `[wexoe-cache:${context}] Webhook svarade ${res.status}:`,
        body.slice(0, 500),
      );
      return;
    }

    const data = (await res.json().catch(() => null)) as
      | { mode?: string; transient_deleted?: number; stale_deleted?: number }
      | null;
    console.info(
      `[wexoe-cache:${context}] Cache rensad`,
      data ? JSON.stringify(data) : '',
    );
  } catch (err) {
    const message = err instanceof Error ? err.message : 'unknown';
    console.error(`[wexoe-cache:${context}] Webhook-anrop misslyckades:`, message);
  } finally {
    clearTimeout(timeout);
  }
}
