# CLAUDE.md — Wexoe monorepo (ingång)

> Senast verifierad mot kod: 2026-05-31 (monorepo-restrukturering).

Detta är **monorepot** för hela Wexoe-systemet. Den här filen är en router —
den ger bred kontext och pekar vidare. Läs den först, gå sen till rätt app/doc.

## Vad systemet är

Wexoe driver sin publika sajt på WordPress (Enfold) men har flyttat allt
innehåll till Airtable. En Next.js-builder låter marknadsförare redigera sidor
med live-preview och skriver till Airtable; WordPress-plugins läser från Airtable
(via `wexoe-core`) och renderar.

```
apps/builder (Next.js)  →  Airtable  →  apps/wordpress/wexoe-core  →  feature-plugins  →  WP-sidor
   (skriver, deterministiskt)  (CMS)        (enda Airtable-auktoriteten)    (shortcodes)
```

## Monorepo-layout

```
apps/
  builder/        # Next.js/TS-buildern (Vercel; Root Directory = apps/builder). Egen CLAUDE.md.
  wordpress/      # WP-plugins. wexoe-core = datalager; plugins/ = feature-plugins per sidtyp.
packages/
  schema/         # ⭐ SANNINGSKÄLLAN. entities/<table>.json (fält EN gång) + enums/ (section_type m.fl.)
tools/
  schema-sync.mjs        # kopierar packages/schema → committade kopior (apps/builder + wexoe-core)
  guardian/guardian.mjs  # ⭐ VÄKTAREN — validerar paritet/enum/schema/strängar + genererar manifest
docs/             # ARKITEKTURPLAN, MONOREPO-PLAN, NEW_PAGE_TYPE, SKAPA-SIDA, DOCS-MAP (genererad), decisions/
manifest.json     # ⭐ GENERERAD systemkarta (av guardian)
.claude/          # SessionStart-hook + slash-kommandon (/add-section, /add-page-type, /tdd)
```

## Sanningskällor (single source) — så här hänger det ihop

- **Fält** definieras EN gång i `packages/schema/entities/<table>.json`. Kör
  `npm run schema:sync` → kopior i `apps/builder/schema/` (buildern importerar
  `@/schema/*`) och `apps/wordpress/wexoe-core/schema/` (WP-pluginet läser i
  drift; måste vara committad så den följer med i en zip). **Redigera bara
  originalet.** Väktaren failar om kopiorna driftar.
- **`section_type`-menyn** definieras EN gång i
  `packages/schema/enums/section-types.json`. PHP-dispatchern + builderns
  SectionType valideras mot den.
- **"Var bor allt"** → läs `docs/DOCS-MAP.md` (genererad) eller `manifest.json`.
  Leta inte igenom trädet för hand.

## Innan du är klar: kör verify

```
npm run verify     # ⭐ ALLA lokala grindar i en körning (= CI): schema-synk,
                   #   guardian, tsc, lint, vitest, php -l, pest. Grön = klart.
npm run verify:quick  # snabb loop under utveckling (hoppar tsc/lint)
npm run check      # bara sanningsvakten (schema-synk-koll + guardian) — snabbast
npm run guardian   # bygg om manifest.json + docs/DOCS-MAP.md efter strukturändring
```
`npm run verify` är den enda sanningen för "klart": den kör samma grindar som
CI och avbryter INTE vid första felet — du ser hela bilden i en körning. Grönt
lokalt = grönt i CI. En ändring som lägger till/ändrar en sektion eller sidtyp
är inte klar förrän den är grön. (CI: `.github/workflows/ci.yml`.)

**Maskinläsbart för agenter:** `node tools/verify.mjs --json` och
`node tools/guardian/guardian.mjs --json` ger `{ ok, ... }`-JSON så du kan parsa
fel programmatiskt istället för prosa.

**Verifieringsverktyg (alla beroendefria, Node stdlib / PHP):**
| Verktyg | Vad det bevisar | Var |
|---|---|---|
| `tools/verify.mjs` | Kör + samlar alla lokala grindar (en sanning för "klart") | lokalt + CI |
| `tools/guardian/guardian.mjs` | Intern konsistens: paritet/enum/strängar/schema-synk | lokalt + CI |
| `tools/airtable-audit.mjs` | Schemat matchar den FAKTISKA Airtable-basen (fält/typer/section_type-enum) — driften väktaren inte kan se | **bara CI** (kräver `AIRTABLE_API_KEY`-secret; se `docs/AIRTABLE-AUDIT.md`) |

## Hårda regler

1. **Spar är deterministiskt.** state→Airtable sker via rena funktioner
   (`apps/builder/lib/deterministic-transform.ts`), **inga Claude-anrop på spar**.
   Claude finns bara på input/copy (`/api/parse`, `/api/copy`). Påståenden om
   "Claude-transform på spar" i äldre text är inaktuella — rätta dem.
2. **Bara `wexoe-core` pratar med Airtable** från WP-sidan. Feature-plugins går
   via `\Wexoe\Core\Core`. Duplicera aldrig Core-helpers lokalt.
3. **Redigera aldrig schema-kopiorna** (`apps/builder/schema/`,
   `wexoe-core/schema/`) — bara `packages/schema/entities/`. Kör `schema:sync`.
4. **Naming låst:** snake_case, engelska, prefix `core_`/`cms_`/`inbox_`. Se
   `docs/` (UTVECKLINGSGUIDE / ARKITEKTURPLAN).
5. **State-typer heter `<Type>State`** (standardiserat). Inga Airtable-fältnamn i UI-kod.

## Vart pekar resten?

| Vad | Var |
|---|---|
| Bygg-sidan (Next.js): page-type-ramverk, deterministisk save | `apps/builder/CLAUDE.md` |
| Core-API, schemaformat, plugin-anatomi | `apps/wordpress/UTVECKLINGSGUIDE.md` |
| Skapa ny sidtyp (flöde / teknisk referens) | `docs/SKAPA-SIDA.md` · `docs/NEW_PAGE_TYPE.md` · `/add-page-type` |
| Lägg till en sektion | `/add-section` |
| TDD ur kravspec | `/tdd` |
| Arkitektur-refaktorn (modularisering, faser) | `docs/ARKITEKTURPLAN.md` |
| Denna infrastruktur-utrullning (faser, status) | `docs/MONOREPO-PLAN.md` |
| Migrationshistorik / Airtable-datafixar | `docs/IMPLEMENTATION_LOG.md` |

## Verktyg & verifiering i denna miljö

- **Node 22 + PHP 8.x** finns. `npm run check` körs utan beroenden (Node stdlib).
- Builder-deps installeras av SessionStart-hooken (`.claude/hooks/session-start.sh`)
  → `tsc`/`lint`/`vitest` funkar. PHP: `php -l` direkt; Pest/PHPCS via composer (FAS 6).
- **Ingen auto-deploy från WP-sidan.** En push gör inget live — användaren zippar
  plugin-mappen (via online-GitHub-zipper) och laddar upp i WP-admin. Schema-kopian
  i `wexoe-core/schema/` följer med i zippen automatiskt (committad).
