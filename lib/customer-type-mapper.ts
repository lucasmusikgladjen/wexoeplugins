/**
 * Airtable `cms_customer_type_pages` record → `CustomerTypePageState`.
 *
 * ARKITEKTURPLAN FAS 1 (pilot): fältlistan bor på ett ställe — schemat
 * `schema/cms_customer_type_pages.json` (kanoniskt i wexoeplugins/wexoe-core/
 * schema/, speglat hit). De skalära fälten + länkar härleds generiskt via
 * `stateFromRecord`; sidtypen lägger bara till det som inte är ren fält-mappning:
 *
 *   - `mode` / `recordId` — meta (inte Airtable-fält).
 *   - `showValue` — beräknad UI-flagga (om något value/benefit-fält har innehåll).
 *   - `contactForm` — nästlat delat block; sätts via `contactFormFromFields`,
 *     som i FAS 3 föredrar JSON-spegeln `contact_form_json` och annars faller
 *     tillbaka på de flata fälten. De 14 `contact_form_*`-fälten + JSON-kolumnen
 *     är `block`/`php_only`-flaggade i schemat och hoppas därför över av
 *     `stateFromRecord` (blocket hanteras separat).
 *
 * `stat_number` lagras som number i Airtable men redigeras som sträng i state —
 * det styrs av `builder_as: "string"` i schemat (ingen specialkod här).
 *
 * Skriv-vägen (state → Airtable) går fortfarande via Claude-transform; den
 * ersätts av en deterministisk `toFields` i FAS 2.
 */

import { AirtableRecord, BASE_ID } from './airtable';
import { CustomerTypePageState } from './customer-type-types';
import { contactFormFromFields, contactFormToFields } from './contact-form-mapper';
import { stateFromRecord } from './schema/to-state';
import { toFields, WriteMode } from './schema/to-fields';
import { EntitySchema } from './schema/entity-schema';
import schemaJson from '@/schema/cms_customer_type_pages.json';

const customerTypeSchema = schemaJson as unknown as EntitySchema;

export const CUSTOMER_TYPE_BASE_ID = BASE_ID;

export const CUSTOMER_TYPE_TABLE_IDS = {
  // Single-sourced ur schemat (samma värde som tidigare hårdkodat).
  customerTypePages: customerTypeSchema.table_id,
  casePages: 'tbl3uMV6IpRIZeucA',
} as const;

export function customerTypePageStateFromRecord(
  record: AirtableRecord,
): CustomerTypePageState {
  const base = stateFromRecord(record, customerTypeSchema);

  const showValue = !!(
    base.valueH2 ||
    base.valueText1 ||
    base.valueText2 ||
    base.benefit1 ||
    base.benefit2 ||
    base.benefit3
  );

  return {
    ...base,
    mode: 'edit',
    recordId: record.id,
    showValue,
    contactForm: contactFormFromFields(record.fields, 'snake_case'),
  } as unknown as CustomerTypePageState;
}

/**
 * state → Airtable-fält, DETERMINISTISKT (FAS 2 — ersätter Claude på save).
 *
 * Skalär-/numeriska fält härleds generiskt ur schemat (mode-medvetet); det
 * delade kontaktformuläret skrivs via `contactFormToFields`. `case_ids`,
 * `country_ids`, `customer_type_ids` och `internal_notes` skrivs aldrig av
 * buildern (read-only/PHP-only) — de utelämnas.
 */
export function customerTypeToFields(
  state: CustomerTypePageState,
  mode: WriteMode,
): Record<string, unknown> {
  const scalar = toFields(
    state as unknown as Record<string, unknown>,
    customerTypeSchema,
    mode,
    { omit: ['case_ids'] }, // länkas i Airtable, read-only i buildern
  );
  const contact = contactFormToFields(state.contactForm, {
    schema: 'snake_case',
    nullForEmpty: mode === 'update',
    emitJson: true, // FAS 3: dual-write JSON-spegeln (flata fält kvar som SoT)
  });
  return { ...scalar, ...contact };
}
