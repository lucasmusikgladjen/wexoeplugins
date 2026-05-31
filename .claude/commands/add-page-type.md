---
description: Skapa en ny sidtyp (schema + builder-editor + PHP-plugin) och verifiera med väktaren
---

Du ska skapa en ny sidtyp `$ARGUMENTS`. Pair-läs `docs/NEW_PAGE_TYPE.md` och
`docs/SKAPA-SIDA.md` — de beskriver hela flödet. En sidtyp är "klar" först när
alla touchpoints finns och väktaren är grön.

Visa förslag på state-struktur + sektionsuppdelning INNAN du implementerar.
Invänta godkännande. Sätt INTE en Claude-transform på spar-vägen — spar är
deterministiskt (rena funktioner).

1. **Schema (källan):** Skapa `packages/schema/entities/<table>.json` enligt
   formatspecen i `packages/schema/README.md`. Kör `npm run schema:sync` så
   kopiorna (wexoe-core + builder) uppdateras. PHP-läs-schemat blir en shim:
   `return \Wexoe\Core\Schema::from_json('<table>');` i `apps/wordpress/wexoe-core/entities/<table>.php`.

2. **Builder server-half:** `apps/builder/lib/page-types/<id>.server.ts` —
   `id`, `label`, `tableId`, `emptyState`, `fromRecord`, `validate`, list-fält,
   `create`/`update` (Lager 3, anropar en deterministisk byggare i
   `deterministic-transform.ts`), `cacheEntities`, `slug`.

3. **Builder UI-half:** `apps/builder/lib/page-types/<id>.ui.tsx` — `sections`,
   `previewLayout`. State-typen heter `<Type>State` (standardiserat namnmönster).

4. **Registry + copy:** Lägg entry i `apps/builder/lib/page-types/registry.ts`.
   Om sidan ska kunna kopieras: sätt `copy: { apiType: '<x>' }` OCH lägg en
   handler med samma nyckel i `app/api/copy/route.ts::COPY_HANDLERS`.
   (Väktaren R-strings failar om de inte matchar.) Håll `id` == modulnamn för
   att undvika id≠modul-mismatchen — undvik den fällan.

5. **PHP-plugin:** `apps/wordpress/plugins/wexoe-<id>/wexoe-<id>.php` — shortcode,
   Core-guard (`class_exists('\\Wexoe\\Core\\Core')`), läs via `Core::entity()`,
   rendera med escaping + Core-helpers. Mönster i `docs/UTVECKLINGSGUIDE.md` § 6.

6. **Verifiera:** `npm run check` (schema-synk + väktare) + `cd apps/builder &&
   npx tsc --noEmit`. Om tester finns: skriv ett för transformen (`/tdd`).

Sammanfatta touchpoints + manuella Airtable-steg (skapa tabellen/fälten).
