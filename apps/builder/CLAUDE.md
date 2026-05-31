# Wexoe Page Builder

> **Vill du skapa en ny sidtyp?** Läs `SKAPA-SIDA.md` (i denna repo) — den guidar dig genom hela flowet steg för steg. Du behöver inte läsa filen du nu har öppen. Den är teknisk referens.

Wexoe Page Builder ("buildern") är vår interna marknadsförar-app för att skapa och redigera sidor på wexoe.com. Den lever här, separat från WordPress, för att vi ska kunna ge marknadsförarna ett kompromisslöst UX-flöde utan att Avia/Enfold står i vägen.

Stack: Next.js (App Router) + TypeScript + Tailwind. Värd på Vercel. Läs och skriv direkt mot Airtable REST-API; state→Airtable-fält sker via en **deterministisk transform** (rena funktioner, ingen Claude på spar-vägen).

> **Nyligen ändrat (2026-05-31):** Spar-vägen är deterministisk sedan FAS 2 — `claude-transform.ts` är **borttagen**. Om du hittar en text (kod-kommentar, guide, prompt) som påstår att en sidtyp skriver "via Claude" / "Claude-transform" / "genereras av Claude" på spar, är den **inaktuell** — lita på koden och rätta texten. Claude finns kvar enbart på input/copy (`/api/parse`, `/api/copy`), aldrig på spar. (`ARKITEKTURPLAN.md`-loggen beskriver historik i dåtid — korrekt, rör ej.) Systerrepots feature-plugins-mapp heter numera `plugins/` (ej `New plugins/`).

---

## 1. Vision och intentioner

**Buildern är inte en Airtable-vy.** Det är hela poängen. Airtable är vår datakälla, men Airtable som UI är dåligt för icke-tekniska användare: kryptiska fältnamn, riskabla rich-text-format ("Q==…//A==…" för QnA-element), inga previews, lätt att skriva sönder en post. Buildern översätter den världen till en marknadsförar-vänlig redaktör med live-preview till höger och tydliga sektionsbaserade kontroller till vänster.

**En sidtyp = ett plugin = en editor.** På WordPress-sidan har varje sidtyp ett eget PHP-plugin som renderar frontend. I buildern har varje sidtyp en motsvarande editor. De har samma "form" — samma sektioner, samma villkorslogik — men inte samma kod, och inte samma datamodell. Pluginet läser den råa Airtable-formade datan; buildern visar en putsad version och översätter tillbaka vid spar.

**Inget direkt Airtable-skrivande från klient.** Vi sparar via en server-route som kör en **deterministisk transform** (rena funktioner, inga Claude-anrop). Klienten skickar sitt enkla state (t.ex. en array med `{question, answer}`-objekt); transformen returnerar Airtable-redo fält i exakt det format pluginet förväntar sig (t.ex. en lines-textsträng med `Q: ... A: ...`-prefix). Backenden diffar mot existerande records och PATCHar/CREATEar/DELETEar. Se `lib/deterministic-transform.ts` (per-typ-byggare) och `lib/schema/to-fields.ts` (schema-driven, single-source).

**Vibes:** Spotify-känsla, inte SAP. Direktredigering, generösa whitespace, snabba previews, "spara" som ett självklart icke-moment. Felmeddelanden ska peka på sektionen + fältet, inte bara säga "save failed". Tomma defaults är bättre än krockande placeholders. Marknadsföraren ska aldrig behöva veta att Airtable existerar.

---

## 2. Systemkarta

```
┌──────────────┐     write      ┌──────────┐  read       ┌──────────────┐
│ Web editor   │ ─────────────► │ Airtable │ ◄────────── │ Core Plugin  │ ──► Page Plugins (WP)
│ (denna app)  │ ◄───────────── │   CMS    │             │   Cache      │
└──────────────┘     display    └──────────┘             └──────────────┘
       ▲
       │
       └── Marknadsförare. Ser aldrig Airtable, ser aldrig WP-admin.
```

- **SSOT-entiteter** (`core_*` i Airtable) — referensdata och singletons: företag, divisioner, partners, coworkers, grafisk profil. Globalt redigerbara via buildern under `/globals`.
- **CMS-entiteter** (`cms_*` i Airtable) — sidor: landing pages, audience heroes, product pages, CMS pages (informationssidor). Redigerbara per record under `/editor/<type>/[recordId]`.
- **Cache** — när buildern sparar invalideras motsvarande WP-transients via `/api/cache/clear`. Page-plugins läser nästa gång och hämtar fresh data från Core.

Allt går genom **wexoe-core** på WP-sidan — page-plugins pratar aldrig med Airtable direkt. Se `wexoeplugins/UTVECKLINGSGUIDE.md` för Core-API och plugin-konventioner.

---

## 3. Repo-layout

```
app/
  editor/               # Per-sidtyps redigeringsvyer (App Router server pages)
  globals/              # SSOT-redigerare (per core_* entity)
  api/
    audience/           # CRUD-route för audience-sidor
    product-area/       # CRUD-route för produktsidor
    page/               # CRUD-route för CMS pages (informationssidor)
    read/               # Läs-route för landing pages (legacy)
    publish/            # Cache-invalidering mot WP
    core/               # SSOT-CRUD-routes
    copy/               # AI-textgenerering
components/
  audience/             # Editor-fält + preview-komponenter per sidtyp
  product-area/
  cms-page/
  editors/              # Legacy LP-editor (migreras till page-types)
  preview/              # Legacy LP-preview
  shared/               # Återanvändbara form-primitives
  contact-form/         # Delad kontaktformulärsmodul (används av flera sidtyper)
lib/
  page-types/           # Page-type ramverk — VIKTIGT, se nedan
    types.ts            #   typdefinitioner (PageTypeServerDef, PageTypeUIDef, relations)
    registry.ts         #   listing över alla sidtyper (klient-säker meta)
    <type>.server.ts    #   server-half: mappers, validering, list
    <type>.ui.tsx       #   UI-half: sektioner, preview-layout
  core/                 # SSOT-ramverk (forms.ts, mapper.ts, loader.ts, registry.ts)
  deterministic-transform.ts # Klient-state → Airtable-fält (rena funktioner, ingen Claude)
  transform-shared.ts   # Delade skriv-primitiver: sectionToPayload, clears*, result-typer
  schema/               # Single-source JSON-schema + to-fields.ts (FAS 1/2-vägen)
  airtable.ts           # Tunn fetch-wrapper (read-only, used server-side)
  *-types.ts            # Per-sidtyp state-typer
  *-mapper.ts           # Per-sidtyp record↔state-mappning
  airtable-schema-*.md  # Specs som matas in i Claude system prompt
  schema/               # Schema-ramverk (vanlig kod, redigeras normalt)
    entity-schema.ts    #   schema-formatet (typer)
    to-state.ts         #   generisk record → state (builderns Normalizer)
schema/                 # ⚠️ AUTO-SPEGLADE *.json från wexoe-core — redigera ALDRIG (se § 3.1)
```

### 3.1 `schema/*.json` är auto-speglade — redigera dem ALDRIG här

> **Om du är en LLM/utvecklare som vill ändra ett fält: gör det INTE i det här
> repot.** `schema/*.json` (och deras kopia under `lib/schema/`s konsumtion) är
> **automatiska spegelkopior** av de kanoniska schemana i
> `wexoeplugins/wexoe-core/schema/`. De är källan (single source of truth).

Ett GitHub Actions-workflow i **wexoeplugins**
(`.github/workflows/sync-schema.yml`) triggar när `wexoe-core/schema/**` ändras
på `main`, speglar `*.json` hit och pushar direkt till builderns `main`. Allt du
ändrar direkt i `schema/`-mappen här **skrivs över vid nästa synk**.

- **Vill du ändra/lägga till ett fält?** Ändra JSON-filen i `wexoe-core/schema/`
  i wexoeplugins-repot, pusha till `main` → ändringen dyker upp här av sig själv.
- **Varför en kopia alls?** Repona deployas separat; buildern kan inte läsa in
  wexoeplugins vid bygget, så en committad kopia krävs. Den hålls dock alltid i
  synk maskinellt — aldrig för hand. Se `schema/README.md` här och
  `wexoeplugins/.github/SCHEMA_SYNC_SETUP.md` för detaljer.
- **`lib/schema/*.ts` (formatet + `to-state.ts`)** är vanlig kod och redigeras
  normalt — det är bara `*.json`-datafilerna som är speglade.

---

## 4. Page-type-ramverket

Hjärtat i buildern. Att lägga till en sidtyp ska vara *datadeklaration*, inte handskriven CRUD-kod.

En sidtyp deklareras som två icke-överlappande filer:

```ts
// lib/page-types/<type>.server.ts
export const <type>Server: PageTypeServerDef<TState, TListItem> = {
  id, label, tableId, baseId,
  emptyState, fromRecord, validate,
  listItemMapper, listFields, listSort,
  create, update,              // Lager 3 — anropar build<Type> i deterministic-transform.ts
  relations: [...],            // Lager 2: deklarativa child-tabeller (om sidtypen har det)
  cacheEntities, slug,
};

// lib/page-types/<type>.ui.tsx
export const <type>UI: PageTypeUIDef<TState> = {
  id, label,
  sections: [...],              // SectionDef[] — en per editor-panel
  previewLayout: <Component>,   // renderar preview till höger
  slugInput, toolbarExtras,
};
```

Splittringen finns för att React-komponenter inte kan korsa Next:s server/client-gräns som props. Server-pages anropar `serverDef.fromRecord(record)` och skickar resultatet som plain JSON till `<PageTypeBuilder uiDef={…} />`.

**Trelagsmodell för routes** (se `lib/page-types/types.ts` för utförlig kommentar):

- **Lager 1** — vanlig CRUD. Bara `primary` (tableId + fromRecord + stateToFields). Ramverket stöder modellen men det är inte produktionsvalet — alla sidtyper går via den deterministiska transformen i Lager 3. Lager 1 finns kvar som en implementation-detalj.
- **Lager 2** — primary + `relations[]`. Ramverket diffar och synkar varje relation (parent-link-array eller child-backlink-modell). För framtida case-sidor med content-blocks i separat tabell.
- **Lager 3** — full override (`create`/`update`/`delete`) som anropar den deterministiska transformen (`deterministic-transform.ts`, eller den schema-drivna `schema/to-fields.ts` för pilot-typen). Detta är **standardvalet** för alla sidtyper — Customer Type, CMS Page, Product Area, Landing Page, Case och Partner använder alla detta idag.

**Airtable saknar transaktioner.** Ramverket lovar inte atomär rollback. Vid fel: primary skrivs först (eller stoppas hela operationen om primary failar); därefter en relation i taget; fel ackumuleras i `RelationSyncResult.errors`. Klienten visar fel tydligt och låter användaren retrya.

**Copy-flödet är opt-in per sidtyp.** Tre-prickar-menyn "Kopiera" visas bara om registry-entry:n har `copy: { apiType: '...' }`, och `apiType`-strängen måste matcha en handler i `app/api/copy/route.ts::COPY_HANDLERS`. Glöms steget syns ingen Kopiera-knapp — vilket är säkrare än en knapp som kraschar mot fel handler, men användarna förväntar sig att alla sidor kan dupliceras. Se `NEW_PAGE_TYPE.md` § "Copy-flödet" för konventioner per handler (slug-unikhet, fält-strip, owned vs shared children, cache).

Att lägga till en ny sidtyp = följ `NEW_PAGE_TYPE.md`.

---

## 5. Deterministisk skrivväg (state → Airtable)

Det viktigaste mönstret i hela buildern: hur det putsade editor-statet översätts till de råa Airtable-fälten vid spar.

**Problem:** Pluginen på WP-sidan förväntar sig data i ett format som passar PHP-rendering — t.ex. en multi-line text-sträng med `Q: fråga\nA: svar\n\nQ: nästa fråga\nA: nästa svar` för ett FAQ-element. Det är effektivt för rendering men ohanterligt som UI-fält. Marknadsföraren ska få ett vanligt formulär: en lista med `{question, answer}`-poster där hen kan dra-och-släppa, ta bort, lägga till. Det är två olika datamodeller för samma sak.

**Lösning:** Buildern håller den enkla modellen i sitt state. Vid spar kör server-routen en **ren funktion** som översätter state → Airtable-fält, plus metadata (`_clientIndex`, `_recordId`) för att korrelera nya/befintliga records. Backenden diffar och PATCHar/CREATEar/DELETEar. Två varianter:

- **`lib/schema/to-fields.ts`** — schema-driven (single-source). Härleder mappningen ur `schema/<entity>.json`. Pilot: `customer-type`. Dit hela arkitekturen är på väg (se `ARKITEKTURPLAN.md` FAS 1).
- **`lib/deterministic-transform.ts`** — handskrivna per-typ-byggare (`buildLandingTransform`, `buildProductAreaTransform`, `buildCmsPageTransform`, `buildPartnerFields`, `buildCaseFields`) för de typer som ännu inte flyttats till den schema-drivna vägen. Delade primitiver (`sectionToPayload`, stale-field-`clears*`, result-typer) ligger i `lib/transform-shared.ts`.

**Varför rena funktioner (och inte Claude)?** Spar är *mekaniskt och deterministiskt* — döpa om `statNumber` → `stat_number`, serialisera en lista till lines-format. En ren funktion gör det snabbare, gratis och utan risk att hallucinera bort innehåll. (Historik: skrivvägen gick tidigare via en Claude-transform med ett MD-schema per sidtyp; den togs bort i FAS 2 eftersom editorn redan producerar rent state innan spar — Claude tillförde bara latens, kostnad och icke-determinism. Claude finns kvar på input-/copy-lagret, aldrig på spar. Se `ARKITEKTURPLAN.md` § 1.4.)

Svåra fall transformen hanterar:
- Polymorfa items (en tab kan vara textimage/faq/compare/...) som ska radas till olika fält
- Pipe/prefix-baserade text-format som Airtable-fältet kräver
- Multi-tabell-records med ordning som behöver korreleras

---

## 6. SSOT-redigerare (`/globals`)

För `core_*`-entiteter (företag, divisioner, partners, ...). Inte sidor — referensdata.

Drivs av `lib/core/`-ramverket:
- `registry.ts` — listar alla SSOT-entiteter med tableId
- `forms.ts` — fältkonfiguration per entitet (label, type, link-targets, list-display)
- `mapper.ts` — bidirektional record↔plain-object-mappning
- `loader.ts` — list/load-flöden

Att lägga till en SSOT-entitet är *bara* att registrera den i `registry.ts` + skriva en `forms.ts`-entry. Inga editor-komponenter krävs — `CoreEntityForm` renderar generiskt utifrån fältkonfigen.

För riktiga sidor är detta för fattigt — då går vi via page-types-ramverket.

---

## 7. Konventioner

- **snake_case** överallt i state, mappers, scheman och Airtable display-namn. Buildern speglar Core-konventionen. Se `wexoeplugins/UTVECKLINGSGUIDE.md` § Naming.
- **Inga Airtable-fältnamn i UI-kod.** State är snake_case domänfält; Airtable-namn finns bara i mappers, transform-byggarna och `schema/`.
- **Per-sidtyp typer i `lib/<type>-types.ts`.** State-typen ska kunna serialiseras (skickas server→client som JSON utan funktioner/Date-objekt).
- **State-uppdateringar är immutabla.** Editor-komponenter får `state` + `onChange(next)`. Använd spread `{ ...state, foo: bar }`.
- **Server-pages hydrerar state.** Server-page hämtar från Airtable, kör `fromRecord`, skickar `initialState` som prop till client-builder. Klienten skickar aldrig egna Airtable-anrop.
- **Cache-invalidering är obligatorisk.** Varje muterande route kör `clearWexoeCache(cacheEntities)` efter lyckad skrivning. Annars stale page-plugins.

---

## 8. Miljövariabler

| Variabel | Källa | Användning |
|---|---|---|
| `AIRTABLE_API_KEY` | Personal access token (`pat...`) | Läs/skriv mot Airtable REST |
| `AIRTABLE_BASE_ID` | Wexoe NY: `appokKSTaBdCa8YiW` | Default-base för läsning |
| `ANTHROPIC_API_KEY` | Claude API-nyckel | `/api/copy` (textgenerering) + `/api/parse` (valfri input-tolkning, FAS 7). **Inte** spar-vägen. |
| `AUTH_USERNAME` / `AUTH_PASSWORD` | Basic auth | `/login`-gate (single-tenant) |
| `WEXOE_CORE_WEBHOOK_URL` / `WEXOE_CORE_WEBHOOK_SECRET` | WP-endpoint + delad secret | Cache-invalidering via webhook (`lib/wexoe-cache.ts`). Utan dem skippas invalidering tyst. |

---

## 9. Vart pekar resten?

- `ARKITEKTURPLAN.md` (denna mapp) — den kanoniska, framåtblickande arkitektur-refaktorn (modularisering: single-source-schema, deterministisk save, delade block). Spegelidentisk i `wexoeplugins/`. Arbeta mot den och bocka av progress.
- `NEW_PAGE_TYPE.md` (denna mapp) — recept för att lägga till en ny sidtyp i buildern. Pair-läs med samma fil i `wexoeplugins/`.
- `wexoeplugins/UTVECKLINGSGUIDE.md` — fullständig referens för Core-API, schemaformat, plugin-konventioner.
- `wexoeplugins/NEW_PAGE_TYPE.md` — plugin-sidans recept för samma flöde.
- `lib/page-types/types.ts` — den definitiva specifikationen på ramverket. Läs kommentarerna; de är inte boilerplate.
- `lib/page-types/registry.ts` — toppkommentar har en kortare checklista för "lägg till sidtyp".
