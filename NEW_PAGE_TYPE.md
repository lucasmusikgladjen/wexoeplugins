# Skapa en ny sidtyp — buildern-sidan (teknisk referens)

> **Är du marknadsförare? Läs `SKAPA-SIDA.md` istället.** Den filen är skriven för dig och guidar dig genom hela flowet. Denna fil är teknisk referens som LLM:er och utvecklare kan dyka i vid behov.

Receptet för att lägga till en helt ny sidtyp i Wexoe-systemet (t.ex. case-sidor, nyheter, leverantörssidor). Denna fil täcker bygget i `wexoebuilder`. Plugin-sidan (PHP-pluginet + Core-schemat + Airtable-tabellerna) täcks av `wexoeplugins/NEW_PAGE_TYPE.md` — pair-läs.

Läs `CLAUDE.md` (denna mapp) först om du inte vet hur page-type-ramverket är uppbyggt.

---

## Flowet i en bild

```
  ┌─────────────────────────────────────────────────────────────────┐
  │ FAS 0 — Skiss + annoterad HTML-prototyp                         │
  │ Mänsklig output: en HTML-fil med villkorskommentarer            │
  └─────────────────────────────────────────────────────────────────┘
                            │
                            ▼
  ┌─────────────────────────────────────────────────────────────────┐
  │ FAS 1 — Schema-design                                            │
  │ Airtable-tabeller skapas + entities/<name>.php skrivs            │
  │ Slutprodukt: en ifyllbar tabell + ett läs-schema i Core          │
  └─────────────────────────────────────────────────────────────────┘
                            │
                            ▼
  ┌──────────────────────────────────┐  ┌──────────────────────────┐
  │ FAS 2 — PHP-plugin                │  │ FAS 3 — Builder-editor   │
  │ Renderar shortcode från Core      │  │ (denna repo)             │
  │ Slutprodukt: zip-bar plugin-mapp  │  │ <type>.server.ts +       │
  │                                   │  │ <type>.ui.tsx +          │
  │                                   │  │ api-route + pages        │
  └──────────────────────────────────┘  └──────────────────────────┘
                                                    │
                                                    ▼
                                       Sidtypen är live i buildern.
                                       Marknadsföraren ser den i listan.
```

Faserna körs i ordning men oftast i separata Claude-sessioner. Mellan varje fas granskar du output (en MÄNSKLIG har sista ordet).

---

## FAS 0 — Annoterad HTML-prototyp

Före något annat: en HTML-fil som visar slutresultatet, med villkorskommentarer som signalerar dynamiken.

### Varför HTML-prototyp?
En LLM som ser bara en Figma-bild gissar. En LLM som ser en HTML-prototyp med tydlig markup *vet* vilka delar som är dynamiska, vilka som är listor, och vilka som är villkorliga.

### Annoterings-konventioner

Använd dessa kommentartaggar konsekvent. LLMer i FAS 1–3 läser dem som spec.

```html
<!-- field: <state-namn> [type] [-- not] -->
   Ett enskilt fält som hämtas från Airtable.
   Type: text | richtext | image | url | color | bool | int | float
   Exempel:
       <h1><!-- field: h1 text -->Vår kompetens</h1>
       <img src="<!-- field: hero_image url -->" alt="<!-- field: hero_alt text -->">

<!-- conditional: <expression> -->
   ... markup som bara renderas om expression är truthy ...
<!-- /conditional -->
   Expression refererar state-fält. Stöd för enkla jämförelser:
       conditional: show_sidebar
       conditional: sidebar_type == "case"
       conditional: stat_number > 0

<!-- repeat: <state-array> -->
   ... markup för EN item, med {{field}}-placeholders ...
<!-- /repeat -->
   Itererar en lista. Items kan ha egna fält:
       <!-- repeat: tabs[] -->
           <div><!-- field: name text --></div>
       <!-- /repeat -->

<!-- section: <id> "<label>" -->
   ... markup för en sektion ...
<!-- /section -->
   Markerar gränser för editor-sektioner. id används som data-section-ankare
   i previewen och som SectionDef.id i ui.tsx.
```

### Exempel — slimmad audience-hero-prototyp

```html
<!-- section: hero "Hero" -->
<div class="hero">
    <span class="eyebrow"><!-- field: eyebrow text --></span>
    <h1><!-- field: title richtext --></h1>
    <p class="description"><!-- field: description richtext --></p>
    <a href="<!-- field: cta_url url -->" class="cta-primary">
        <!-- field: cta_text text -->
    </a>
    <img src="<!-- field: hero_image url -->" alt="">
    <!-- conditional: stat_number > 0 -->
    <div class="stat">
        <strong><!-- field: stat_number int --></strong>
        <small><!-- field: stat_label text --></small>
    </div>
    <!-- /conditional -->
</div>
<!-- /section -->

<!-- section: value "Värdeproposition" visibility:show_value -->
<!-- conditional: show_value -->
<div class="value">
    <h2><!-- field: value_h2 text --></h2>
    <p><!-- field: value_text_1 richtext --></p>
    <ul>
        <!-- repeat: benefits[] -->
        <li><!-- field: this richtext --></li>
        <!-- /repeat -->
    </ul>
</div>
<!-- /conditional -->
<!-- /section -->

<!-- section: contactForm "Kontaktformulär" visibility:show_contact_form -->
<!-- conditional: show_contact_form -->
    <!-- shared: ContactForm -->
<!-- /conditional -->
<!-- /section -->
```

Notera:
- `visibility:show_value` på `<!-- section -->` = sektionen får en synlig toggle i editorn (`SectionDef.visibilityToggle`).
- `<!-- shared: ContactForm -->` = återanvänd den delade kontaktformulärsmodulen från Core, slipper duplicera markup.
- `repeat: this` inom en `repeat`-block = den enskilda item-värdet (för enkla string-arrayer).

Spara prototypen som `prototype.html` i en arbetskatalog lokalt. Den **committas inte** — den är en arbetsartefakt som följer med från FAS 1 till FAS 3 och slängs när flowet är klart. Källan-of-truth efter att flowet är klart är PHP-pluginet + builder-preview-komponenten.

---

## FAS 3 — Builder-editor (där du är nu)

FAS 1 (schema) och FAS 2 (PHP-plugin) körs först. Denna fas antar att:
- Airtable-tabellen finns med snake_case-fält.
- `wexoe-core/entities/<name>.php` finns och `Core::entity('<name>')` fungerar.
- Du vet baseId och tableId.

### Vad du producerar i FAS 3

| Fil | Innehåll |
|---|---|
| `lib/<type>-types.ts` | TypeScript-state-typer + `empty<Type>State()` |
| `lib/<type>-mapper.ts` | Airtable record ↔ state |
| `lib/page-types/<type>.server.ts` | `PageTypeServerDef` — server-half |
| `lib/page-types/<type>.ui.tsx` | `PageTypeUIDef` — UI-half |
| `components/<type>/editors/*.tsx` | En komponent per editor-sektion |
| `components/<type>/preview/<Type>PreviewPanel.tsx` | Live-preview till höger |
| `app/api/<type>/route.ts` | CRUD-endpoint (oftast en factory-anrop) |
| `app/editor/<type>/page.tsx` | Create-vy (server page) |
| `app/editor/<type>/[recordId]/page.tsx` | Edit-vy (server page) |
| `lib/page-types/registry.ts` | Lägg till entry i `PAGE_TYPES`-arrayen + `copy: { apiType }` |
| `lib/wexoe-cache-entities.ts` | Lägg till `<TYPE>_ENTITIES`-konstant |
| `app/api/copy/route.ts` | Lägg till `copy<Type>`-handler + rad i `COPY_HANDLERS` |

### Trelagsbeslut

Alla sidtyper använder **Lager 3 + Claude-transform**. Börja alltid där — också för sidtyper vars schema är 1-till-1 mellan state och Airtable. Enhetlig kod över alla sidtyper, schema-ändringar görs i en MD-fil, ingen risk att en handskriven mapper hamnar ur synk.

Lägg till `relations[]` (Lager 2) ovanpå Lager 3 om sidtypen har child-records i separat tabell (t.ex. tabs, downloads, products). Lager 1 (handskriven `stateToFields` utan Claude) stöds av ramverket men används inte i produktionen.

### Claude-transform-checklistan

Varje sidtyp behöver:
- `lib/airtable-schema-<type>.md` — Markdown-spec med komplett fältlista, typer, formateringsregler. Matas in i Claude system prompt.
- Transform-funktion i `lib/claude-transform.ts` — payload-builder + system-prompt-builder + `transform<Type>()`. Kopiera mönstret från `transformCustomerType` (enklast) eller `transformProductArea` (komplex, multi-tabell).
- `create` och `update` override-metoder på `PageTypeServerDef` som anropar transform-funktionen och skriver till Airtable.

Verifiera schema-MD mot live-records innan du anser den klar — fel fältnamn i schema-MD är det vanligaste felscenariot i hela Claude-transform-flödet. Claude returnerar ett korrekt-utseende svar som ändå PATCHar fel fält.

### Checklista (Lager 3, standardflöde)

1. `lib/<type>-types.ts`:
   ```ts
   export interface <Type>State {
     recordId: string | null;
     slug: string;
     // ... domain fields i snake_case eller camelCase, var konsekvent inom sidtypen
   }
   export const empty<Type>State = (): <Type>State => ({ recordId: null, slug: '', ... });
   ```

2. `lib/<type>-mapper.ts`:
   ```ts
   // Bara fromRecord-riktningen. State → Airtable går via Claude-transform i Lager 3.
   export function <type>StateFromRecord(r: AirtableRecord): <Type>State { ... }
   ```

3. `lib/airtable-schema-<type>.md` + transform-funktion i `lib/claude-transform.ts` (se Claude-transform-checklistan ovan).

4. `lib/page-types/<type>.server.ts`: instansiera `PageTypeServerDef<<Type>State, <ListItem>>` med id/label/tableId/fromRecord/listItemMapper/slug och `create`/`update` override:s som anropar `transform<Type>` + skriver via `createRecord`/`updateRecord`.

5. `lib/page-types/<type>.ui.tsx`: instansiera `PageTypeUIDef<<Type>State>` med sections (en per `<!-- section -->` i prototypen), `previewLayout`, `slugInput`.

6. `components/<type>/editors/*` + `components/<type>/preview/*`: en editor-komponent per sektion, en preview-komponent per sektion (eller en monolitisk preview som renderar allt).

7. `app/api/<type>/route.ts`:
   ```ts
   import { createPageRoute, pageTypeToRouteConfig } from '@/lib/route-factory';
   import { <type>Server, load<Type>State } from '@/lib/page-types/<type>.server';
   export const { GET, POST, PATCH, DELETE } = createPageRoute(
     pageTypeToRouteConfig(<type>Server, process.env.AIRTABLE_API_KEY!, load<Type>State),
   );
   ```

8. `app/editor/<type>/page.tsx` + `app/editor/<type>/[recordId]/page.tsx`: server pages som hydrerar state och renderar `<PageTypeBuilder uiDef={<type>UI} initialState={...} />`.

9. `lib/wexoe-cache-entities.ts`: lägg till `export const <TYPE>_ENTITIES = ['<entity_name>', ...] as const;`.

10. `lib/page-types/registry.ts`:
    - Utöka `PageTypeId`-unionen med det nya id:t (`'landing' | 'product' | ... | '<type>'`). Annars rejectar TypeScript ditt nya `id`/`type` innan routen ens kan byggas.
    - Lägg till entry i `PAGE_TYPES` (id, label, description, createPath, editPath, cacheEntities, listUrl, mapList). `mapList` returnerar `PageRow[]` med `type: '<type>'` — samma literal som du la till i unionen.
    - Sätt `copy: { apiType: '<api-type>' }` på entry:n. `apiType`-strängen är vad UI:n och `/api/copy` använder för att para ihop sidtypen med rätt handler — konvention: samma som `id`, förutom `product-area` för historiska skäl.

11. `app/api/copy/route.ts`:
    - Skriv en `copy<Type>(apiKey, sourceId, name, slug)`-funktion enligt mönstret som de andra typerna följer (slug-uniqueness-kollen, fält-strip av backlinks/computed, defaultCopyName/Slug-fallback, cache-invalidation).
    - Lägg till `'<api-type>': copy<Type>` i `COPY_HANDLERS`-tabellen.
    - Utan steget döljs Kopiera-menyn för den nya typen (registry:n vet inte att stödet finns) — men användarna förväntar sig att alla sidor går att duplicera, så hoppa inte över detta.

12. Testa: `npm run dev`, gå till `/`, klicka "Ny sida" → välj din nya typ → fyll i → spara → ladda om → editera → spara → kolla i Airtable. Hovra över sidan i listan och klicka tre-prickar-menyn → Kopiera → verifiera att kopian dyker upp. Aktivera pluginet på WP och verifiera att en publik sida renderas.

### Vanliga felskott

- **State-objektet innehåller funktioner eller Date-objekt** → server-page kan inte skicka det som JSON till klient. Håll state platt och serialiserbart.
- **Editor-komponenten muterar state** → React ser ingen ändring. Använd alltid `onChange({ ...state, foo: bar })`.
- **Slug skrivs inte till Airtable** → glömt `slug`-policy i `PageTypeServerDef`.
- **Cache rensas inte** → glömt `cacheEntities` i serverDef ELLER glömt entitetsnamnet i `<TYPE>_ENTITIES`.
- **Sidan listas inte på `/`** → glömt registry-entry, eller `mapList` returnerar tom array.
- **Preview re-renderar inte vid edit** → preview-komponenten läser från egen state istället för från props.
- **Kopiera-knappen kraschar för den nya typen** → glömt `copy: { apiType }` i registry, eller glömt en handler i `app/api/copy/route.ts::COPY_HANDLERS`. Se nästa avsnitt.

---

## Copy-flödet (Kopiera-knappen i listan)

Tre-prickar-menyn i sidolistan visar **Kopiera** för varje sidtyp som har `copy: { apiType }` i sin registry-entry. Implementationen är dispatch-baserad — UI:n och API:t paras ihop via `apiType`-strängen.

### Komponenter

| Lager | Fil | Roll |
|---|---|---|
| Registry | `lib/page-types/registry.ts` | Sätter `copy: { apiType }` på varje sidtyp som stöder copy. Saknas fältet → menyn döljs. |
| UI | `app/page.tsx::RowActionsMenu` | Visas bara när `getPageType(page.type).copy` är truthy. |
| UI | `app/page.tsx::CopyPageDialog` | Läser `getPageType(source.type).copy.apiType` och POST:ar mot `/api/copy`. |
| API | `app/api/copy/route.ts::COPY_HANDLERS` | Lookup-tabell: `apiType`-sträng → `copy<Type>`-handler. |
| API | `app/api/copy/route.ts::copy<Type>` | Hämtar source, rensar fält som inte ska kopieras, skapar nya records, invaliderar cache. |

### Konventioner per copy-handler

- **Slug-unikhet:** kör `isSlugTaken(...)` mot tabellen innan create. Returnera 409 om upptaget — UI:n visar felet inline.
- **Defaultnamn:** kalla `defaultCopyName(sourceName)` och `defaultCopySlug(sourceSlug)` för fallback när användaren inte fyllt i fälten. `defaultCopySlug` hanterar `-copy → -copy-2 → -copy-3` automatiskt.
- **Fält att rensa:** strippa allt som är (a) backlink till parent, (b) ägd child-länk som rebuildas, eller (c) Airtable computed/audit-fält. Se `<TYPE>_FIELDS_TO_DROP`-konstanterna för existerande mönster.
- **Owned vs shared children:** owned (sektioner, tabs, downloads) deep-copy:as. Shared (länkar till partners, produkter, divisions) ärvs by reference. Customer-types `case_ids` och CMS-pages `is_published=false` är medvetna avvikelser — när tvekan: dokumentera valet i en code-kommentar.
- **Cache:** anropa `invalidateWexoeCoreCache(<TYPE>_ENTITIES, '<type>:copy')` efter lyckad create så Core-pluginet inte serverar stale list-data.
- **Respons:** returnera `{ success: true, id, name, slug, type }` — UI:n bumpar `refreshKey` när success är true och hämtar listan igen.

### När en sidtyp avsiktligt INTE ska kunna kopieras

Lämna `copy` undefined i registry-entry:n. UI:n döljer Kopiera-menyn, och API:t returnerar 400 om någon ändå försöker. Lämpligt för system-typer eller singletons.

---

## Kickoff-prompt

Kopiera-och-redigera. En prompt per fas, ny Claude-session per fas så kontextet hålls rent.

### Prompt — FAS 0 (HTML-prototyp)

Körs gärna i en separat Claude-session där du iterar tills du är nöjd. Output: en `prototype.html`-fil.

```
Jag vill bygga en ny sidtyp för Wexoe-systemet: <NAMN — t.ex. "Case-sida">.

Bifogat finns en Figma-skiss av designen.

Producera en statisk HTML-prototyp av sidan med inline CSS. Använd Wexoe:s
annoterings-konventioner för att markera dynamiska delar:

  <!-- field: <state-namn> [type] -->          enskilt fält
  <!-- conditional: <expression> --> ... </>   villkorlig markup
  <!-- repeat: <state-array> --> ... </>        itererad lista
  <!-- section: <id> "<label>" --> ... </>     editor-sektionsgräns

Type kan vara: text, richtext, image, url, color, bool, int, float.

För sektioner som ska kunna döljas via en visibility-toggle i editorn,
lägg till `visibility:<bool-fält>` i section-taggen.

För komponenter som finns delade i Core (Hero, Faq, TeamGrid, PartnersMarquee,
TestimonialCard, CtaBanner, ContactForm), markera med <!-- shared: <Namn> -->
istället för att duplicera markup.

Vi itererar på prototypen tills jag är nöjd — börja med ett första utkast
och vänta på feedback.
```

### Prompt — FAS 1 (Airtable + Core-schema)

Körs i en Claude-session som har Airtable MCP samt tillgång till `wexoeplugins`-repot. Output: en `entities/<name>.php`-fil och en skapad Airtable-tabell.

```
Sidtypen <NAMN> ska få ett eget Airtable-bord + Core-schema.

Här är den annoterade HTML-prototypen:

  <bädda in prototype.html>

Läs `wexoeplugins/UTVECKLINGSGUIDE.md` § 2 (Naming conventions), § 4
(Läs-schemaformat) och `wexoeplugins/NEW_PAGE_TYPE.md`.

Producera:
1. En lista över fält som behövs i Airtable-tabellen, inkl. typ (singleLineText,
   longText, checkbox, multipleAttachments, multipleRecordLinks, etc.) och
   exempel-värden. SHOWA mig listan innan du skapar tabellen.
2. När jag godkänt listan: skapa tabellen i base appokKSTaBdCa8YiW (Wexoe NY)
   via Airtable MCP. Tabellnamn `cms_<plural>`. Snake_case fält-namn.
3. Skriv `wexoeplugins/wexoe-core/entities/<name>.php` enligt schemaformatet.
   Primärnyckel `slug`. Cache TTL 86400. Required `['slug']`.

Skapa INGEN PHP-plugin och ingen builder-editor i denna fas — bara tabell + schema.
```

### Prompt — FAS 2 (PHP-plugin)

Körs i en session med `wexoeplugins`-repot. Output: en zip-bar plugin-mapp under `New plugins/`.

```
Tabellen och Core-schemat för <NAMN> är klart (`entities/<name>.php`).
Nu bygger vi PHP-pluginet som renderar shortcode på WP.

Här är den annoterade HTML-prototypen:

  <bädda in prototype.html>

Läs `wexoeplugins/UTVECKLINGSGUIDE.md` § 3 (Core publikt API), § 6 (Anatomi
av ett feature-plugin) och `wexoeplugins/NEW_PAGE_TYPE.md`.

Producera under `New plugins/wexoe-<type>/wexoe-<type>.php`:
1. Plugin-header med korrekt namn/version.
2. Core-readiness-check.
3. Shortcode `[wexoe_<type> slug="..." debug="false"]` som läser via
   `Core::entity('<entity_name>')->find($slug)`.
4. Rendering som matchar HTML-prototypen — alla `<!-- field -->`,
   `<!-- conditional -->`, `<!-- repeat -->` ska respekteras.
5. CSS scoped via `wexoe-<short>-{uniqid()}`-wrapper.
6. Använd Core-helpers: Markdown, Color, YouTube, Lines där det passar.
7. För <!-- shared: <Namn> --> — använd `Core::renderer($type)`.
8. Boolean-checkboxar från Airtable är redan bool — behöver inte `!empty()`.
9. `debug=true` ska dumpa `<pre>print_r($data)</pre>`.

`php -l` ska gå ren. Leverera som en zip-bar mapp.
```

### Prompt — FAS 3 (Builder-editor)

Körs i en session med `wexoebuilder`-repot. Output: alla filer enligt checklistan ovan.

```
PHP-pluginet och Core-schemat för <NAMN> är klart. Nu bygger vi builder-editorn.

Här är den annoterade HTML-prototypen:

  <bädda in prototype.html>

Här är Core-schemat (`wexoeplugins/wexoe-core/entities/<name>.php`):

  <bädda in schemafilen>

Läs `wexoebuilder/CLAUDE.md` och `wexoebuilder/NEW_PAGE_TYPE.md`. Studera
också en existerande sidtyp som referens — Customer Type
(`lib/page-types/customer-type.*`, `components/audience/`) är minsta Lager 3-referensen;
Product Area är komplex Lager 3 med child-records.

Producera alla filer enligt checklistan i NEW_PAGE_TYPE.md. Alla sidtyper
använder Lager 3 + Claude-transform — börja med att skriva schema-MD och
transform-funktion. Om sidtypen har child-records i separat tabell, lägg
till `relations[]` (Lager 2) ovanpå.

Krav:
- snake_case på Airtable-fält i mappers; konsekvent stil i state.
- State serialiserbar (inga funktioner/Date-objekt).
- Editor-sektioner matchar `<!-- section -->`-strukturen i prototypen.
- visibility-toggles där prototypen anger `visibility:<bool>`.
- Preview-komponenten ska rendera ungefär samma struktur som PHP-pluginet.
- registry.ts uppdaterad så sidtypen syns i homepage-listan.

Visa förslag på state-struktur + section-uppdelning innan du implementerar.
```

---

## Snabbreferenser

- `lib/page-types/types.ts` — den definitiva spec:en för ramverket
- `lib/page-types/registry.ts` — toppkommentar med kort checklista
- `lib/page-types/customer-type.{server,ui}.ts` — minsta Lager 3-referens (en tabell, ingen child-records)
- `lib/page-types/product-area.{server,ui}.ts` — komplex Lager 3-referens (multi-tabell, child-records)
- `lib/claude-transform.ts` — Claude-mellanlaget; alla `transform<Type>` lever här
- `lib/airtable-schema-*.md` — en per sidtyp; specifikationen som matas in i Claude
- `lib/route-factory.ts` — `createPageRoute()` factory
- `components/audience/` — kanonisk editor + preview-uppdelning
