/**
 * Product Area — server-side sidtypsdefinition (Lager 3).
 *
 * Product-area passar inte i Lager 2 (declarative relations) eftersom dess
 * Airtable-fält genereras av Claude. Vi använder Lager 3:s `create`/`update`-
 * overrides via factory:n och håller den befintliga Claude+products+solutions-
 * koden i `product-area-actions.ts`.
 *
 * Routen får trots det samma standarder som övriga sidtyper:
 *   - Slug-validering + duplikat-koll
 *   - `{ success, error, code? }`-felformat
 *   - DELETE-endpoint
 *   - Wexoe Core cache-invalidering
 *
 * `emptyState` / `fromRecord` / `stateToFields` lämnas oexponerade här
 * eftersom (a) factory:n behöver dem inte när create/update är override:de,
 * (b) `loadProductAreaState()` är den enda korrekta load-vägen (den hämtar
 * PA + products + articles + solutions i en sweep), och (c) `emptyProductAreaState`
 * importeras direkt av create-page:n.
 */

import { AirtableRecord } from '../airtable';
import {
  productAreaCreate,
  productAreaUpdate,
  type ProductAreaListItem,
} from './product-area-actions';
import { loadProductAreaState } from '../product-area-loader';
import { PA_TABLE_IDS, PA_BASE_ID } from '../product-area-mapper';
import { ProductAreaState } from '../product-area-types';
import { PA_ENTITIES } from '../wexoe-cache';
import type { PageTypeServerDef } from './types';

// stateToFields är obligatorisk i typen även om create/update overridar den.
// Faktor:n anropar den aldrig när override:s är satta — men TS kräver att
// fältet finns. En dedikerad throw klargör om någon framtida ändring råkar
// kalla den.
function unreachableStateToFields(): never {
  throw new Error(
    'product-area: stateToFields ska inte anropas (Lager 3 — create/update är override:de).',
  );
}

// fromRecord och emptyState är optionella i typen och utelämnas eftersom
// product-area inte använder factory:ns ?action=get-väg eller create-default-
// flödet — server-pages hanterar båda direkt.
function unreachableFromRecord(): never {
  throw new Error('product-area: använd loadProductAreaState() istället för fromRecord.');
}

export const productAreaServer: PageTypeServerDef<ProductAreaState, ProductAreaListItem> = {
  id: 'product-area',
  label: 'Produktområde',
  tableId: PA_TABLE_IDS.productAreas,
  baseId: PA_BASE_ID,

  // Lager 3 — hela skriv-vägen är override:d.
  create: productAreaCreate,
  update: productAreaUpdate,

  // Required-by-type-stubs (se kommentarer ovan).
  emptyState: unreachableFromRecord,
  fromRecord: unreachableFromRecord,
  stateToFields: unreachableStateToFields,

  validate: (s) => {
    if (!s.h1?.trim()) return { field: 'h1', message: 'H1 är obligatoriskt.' };
    return null;
  },
  listItemMapper: (r: AirtableRecord): ProductAreaListItem => ({
    id: r.id,
    name: (r.fields.name as string) || '',
    slug: (r.fields.slug as string) || '',
    h1: (r.fields.h1 as string) || '',
    divisionIds: (r.fields.division_ids as string[] | undefined) ?? [],
  }),
  listFields: ['name', 'slug', 'h1', 'division_ids'],
  listSort: [{ field: 'slug', direction: 'asc' }],

  cacheEntities: PA_ENTITIES,

  slug: {
    accessor: (s) => s.slug,
    field: 'slug',
    checkDuplicate: true,
  },
};

export { loadProductAreaState };
