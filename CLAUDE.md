# Wexoe Page Builder

> **Vill du skapa en ny sidtyp?** Läs `SKAPA-SIDA.md` (i denna repo) — den guidar dig genom hela flowet steg för steg. Du behöver inte läsa filen du nu har öppen. Den är teknisk referens.

Wexoe Page Builder ("buildern") är vår interna marknadsförar-app för att skapa och redigera sidor på wexoe.com. Den lever här, separat från WordPress, för att vi ska kunna ge marknadsförarna ett kompromisslöst UX-flöde utan att Avia/Enfold står i vägen.

Stack: Next.js (App Router) + TypeScript + Tailwind. Värd på Vercel. Läs från Airtable REST-API direkt; skriv via Claude API (Anthropic) som mellanled.

---

## 1. Vision och intentioner

**Buildern är inte en Airtable-vy.** Det är hela poängen. Airtable är vår datakälla, men Airtable som UI är dåligt för icke-tekniska användare: kryptiska fältnamn, riskabla rich-text-format ("Q==…//A==…" för QnA-element), inga previews, lätt att skriva sönder en post. Buildern översätter den världen till en marknadsförar-vänlig redaktör med live-preview till höger och tydliga sektionsbaserade kontroller till vänster.

**En sidtyp = ett plugin = en editor.** På WordPress-sidan har varje sidtyp ett eget PHP-plugin som renderar frontend. I buildern har varje sidtyp en motsvarande editor. De har samma "form" — samma sektioner, samma villkorslogik — men inte samma kod, och inte samma datamodell. Pluginet läser den råa Airtable-formade datan; buildern visar en putsad version och översätter tillbaka vid spar.

**Inget direkt Airtable-skrivande från klient.** Vi sparar via en server-route som anropar Claude (Anthropic API). Klienten skickar sitt enkla state (t.ex. en array med `{question, answer}`-objekt); Claude returnerar Airtable-redo JSON i exakt det format pluginet förväntar sig (t.ex. en lines-textsträng med `Q: ... A: ...`-prefix). Backenden diffar mot existerande records och PATCHar/CREATEar/DELETEar. Se `lib/claude-transform.ts`.

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
- **CMS-entiteter** (`cms_*` i Airtable) — sidor: landing pages, audience heroes, product pages, unique pages. Redigerbara per record under `/editor/<type>/[recordId]`.
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
    unique-page/        # CRUD-route för unique pages
    read/               # Läs-route för landing pages (legacy)
    publish/            # Cache-invalidering mot WP
    core/               # SSOT-CRUD-routes
    copy/               # AI-textgenerering
components/
  audience/             # Editor-fält + preview-komponenter per sidtyp
  product-area/
  unique-page/
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
  claude-transform.ts   # Klient-state → Airtable-format via Claude API
  airtable.ts           # Tunn fetch-wrapper (read-only, used server-side)
  *-types.ts            # Per-sidtyp state-typer
  *-mapper.ts           # Per-sidtyp record↔state-mappning
  airtable-schema-*.md  # Specs som matas in i Claude system prompt
```

---

## 4. Page-type-ramverket

Hjärtat i buildern. Att lägga till en sidtyp ska vara *datadeklaration*, inte handskriven CRUD-kod.

En sidtyp deklareras som två icke-överlappande filer:

```ts
// lib/page-types/<type>.server.ts
export const <type>Server: PageTypeServerDef<TState, TListItem> = {
  id, label, tableId, baseId,
  emptyState, fromRecord, stateToFields, validate,
  listItemMapper, listFields, listSort,
  relations: [...],            // Lager 2: deklarativa child-tabeller
  // create/update/delete:     // Lager 3: bara om Lager 1/2 inte räcker
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

- **Lager 1** — vanlig CRUD. Bara `primary` (tableId + fromRecord + stateToFields). Audience och Unique Pages använder detta idag.
- **Lager 2** — primary + `relations[]`. Ramverket diffar och synkar varje relation (parent-link-array eller child-backlink-modell). För framtida case-sidor med content-blocks i separat tabell.
- **Lager 3** — full override (`create`/`update`/`delete`). Bara när logik är så udda att det inte passar i ramverket. Product Area använder detta idag (multi-tabell-create i specifik ordning via Claude-transform).

**Airtable saknar transaktioner.** Ramverket lovar inte atomär rollback. Vid fel: primary skrivs först (eller stoppas hela operationen om primary failar); därefter en relation i taget; fel ackumuleras i `RelationSyncResult.errors`. Klienten visar fel tydligt och låter användaren retrya.

Att lägga till en ny sidtyp = följ `NEW_PAGE_TYPE.md`.

---

## 5. Claude-transform-mellanlaget

Filosofiskt det viktigaste mönstret i hela buildern.

**Problem:** Pluginen på WP-sidan förväntar sig data i ett format som passar PHP-rendering — t.ex. en multi-line text-sträng med `Q: fråga\nA: svar\n\nQ: nästa fråga\nA: nästa svar` för ett FAQ-element. Det är effektivt för rendering men ohanterligt som UI-fält. Marknadsföraren ska få ett vanligt formulär: en lista med `{question, answer}`-poster där hen kan dra-och-släppa, ta bort, lägga till. Det är två olika datamodeller för samma sak.

**Lösning:** Buildern håller den enkla modellen i sitt state. Vid spar bygger `lib/claude-transform.ts` en JSON-payload av staten, plus metadata (`_clientIndex`, `_recordId`) för att korrelera nya/befintliga records, och skickar till Claude med en system prompt som innehåller hela Airtable-schemat (från `lib/airtable-schema-{lp,pa}.md`). Claude returnerar färdiga Airtable-fält. Backenden diffar och PATCHar/CREATEar/DELETEar.

**Varför Claude och inte ren kod?**
1. Formatteringsreglerna är många och växer — bullets-splitting, FAQ-prefix, pipe-format för compare-rows, etc. Att hålla en hand-kodad transformer i synk med schemat blir lätt fel.
2. När en ny sidtyp läggs till räcker det att skriva ett nytt schema-MD plus en payload-builder. Logiken för "tom-rensning vid CREATE", "behåll-array i UPDATE", "echo metadata" osv. återanvänds.
3. Det är robust mot små schemaändringar — fält som byter namn eller får nya regler kräver bara att MD-schemat uppdateras.

**Trade-off:** Claude måste vara igång, sparet kostar ~1-3 sek + några öre i tokens, och vi har defensiva guards i `transform*` mot tomma arrayer i UPDATE-läget (annars skulle Claude oavsiktligt kunna unlinka allt). Värt det för utvecklingsekvansen.

**När ska Claude-transform användas?** När datamodellen i editorn skiljer sig icke-trivialt från Airtable-formatet, t.ex. när vi har:
- Polymorfa items (en tab kan vara textimage/faq/compare/...) som ska radas till olika fält
- Pipe/prefix-baserade text-format som Airtable-fältet kräver
- Multi-tabell-records med ordning som behöver korreleras

För enkla sidtyper (alla fält 1-till-1 mellan state och Airtable) skip Claude och använd Lager 1 med direkt `stateToFields`. Audience och Unique Pages gör så.

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

- **snake_case** överallt i state, mappers, schema-MD och Airtable display-namn. Buildern speglar Core-konventionen. Se `wexoeplugins/UTVECKLINGSGUIDE.md` § Naming.
- **Inga Airtable-fältnamn i UI-kod.** State är snake_case domänfält; Airtable-namn finns bara i mappers och schema-MD.
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
| `ANTHROPIC_API_KEY` | Claude API-nyckel | `claude-transform.ts` + `/api/copy` |
| `AUTH_USERNAME` / `AUTH_PASSWORD` | Basic auth | `/login`-gate (single-tenant) |
| `WEXOE_CORE_WEBHOOK_URL` / `WEXOE_CORE_WEBHOOK_SECRET` | WP-endpoint + delad secret | Cache-invalidering via webhook (`lib/wexoe-cache.ts`). Utan dem skippas invalidering tyst. |

---

## 9. Vart pekar resten?

- `NEW_PAGE_TYPE.md` (denna mapp) — recept för att lägga till en ny sidtyp i buildern. Pair-läs med samma fil i `wexoeplugins/`.
- `wexoeplugins/UTVECKLINGSGUIDE.md` — fullständig referens för Core-API, schemaformat, plugin-konventioner.
- `wexoeplugins/NEW_PAGE_TYPE.md` — plugin-sidans recept för samma flöde.
- `lib/page-types/types.ts` — den definitiva specifikationen på ramverket. Läs kommentarerna; de är inte boilerplate.
- `lib/page-types/registry.ts` — toppkommentar har en kortare checklista för "lägg till sidtyp".
