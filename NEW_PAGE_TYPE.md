# Skapa en ny sidtyp — plugin-sidan (teknisk referens)

> **Är du marknadsförare? Läs `SKAPA-SIDA.md` istället.** Den filen är skriven för dig och guidar dig genom hela flowet. Denna fil är teknisk referens som LLM:er och utvecklare kan dyka i vid behov.

Receptet för att lägga till en helt ny sidtyp i Wexoe-systemet. Denna fil täcker plugin-sidan: Airtable-tabellen, Core-schemat och PHP-pluginet som renderar shortcode på WP. Builder-sidan (Next.js-editorn) täcks av `wexoebuilder/NEW_PAGE_TYPE.md` — pair-läs.

Läs `UTVECKLINGSGUIDE.md` (denna mapp) först om du inte kan Core-API:t från huvudet.

---

## Flowet i en bild

```
  ┌─────────────────────────────────────────────────────────────────┐
  │ FAS 0 — Skiss + annoterad HTML-prototyp                         │
  │ Slutprodukt: prototype.html med villkorskommentarer             │
  └─────────────────────────────────────────────────────────────────┘
                            │
                            ▼
  ┌─────────────────────────────────────────────────────────────────┐
  │ FAS 1 — Schema-design     (denna repo)                          │
  │ Airtable-tabell skapas + entities/<name>.php skrivs              │
  └─────────────────────────────────────────────────────────────────┘
                            │
                            ▼
  ┌──────────────────────────────────┐  ┌──────────────────────────┐
  │ FAS 2 — PHP-plugin                │  │ FAS 3 — Builder-editor   │
  │ (denna repo)                       │  │ (wexoebuilder-repot)     │
  │ Renderar shortcode från Core       │  │                          │
  └──────────────────────────────────┘  └──────────────────────────┘
                                                    │
                                                    ▼
                                       Sidtypen är live.
                                       Marknadsföraren ser den i builder-listan.
                                       Pluginet renderar publika sidor.
```

Faserna körs sekventiellt (1 → 2 → 3) och oftast i separata Claude-sessioner. Mellan varje fas granskar du output.

---

## FAS 0 — Annoterad HTML-prototyp

En statisk HTML-fil som visar slutresultatet, med kommentartaggar som signalerar dynamiken. Driver både PHP-pluginet (FAS 2) och builder-editorn (FAS 3).

### Annoterings-konventioner

```html
<!-- field: <state-namn> [type] -->
   Ett enskilt fält. Type: text | richtext | image | url | color | bool | int | float.

<!-- conditional: <expression> --> ... <!-- /conditional -->
   Markup som bara renderas om expression är truthy.
   Stöd: `bool_field`, `enum_field == "value"`, `int_field > 0`.

<!-- repeat: <state-array> --> ... <!-- /repeat -->
   Itererar en array. Inuti repeat används <!-- field: <name> --> för
   item-fält och <!-- field: this --> för enkla string-arrayer.

<!-- section: <id> "<label>" [visibility:<bool>] --> ... <!-- /section -->
   Markerar editor-sektionsgränser. visibility-toggle gör sektionen
   dölj/visa-bar via en checkbox i editor-headern.

<!-- shared: <Renderer> -->
   Återanvänder en delad Core-renderer (Hero, Faq, TeamGrid, PartnersMarquee,
   TestimonialCard, CtaBanner, ContactForm). Mappar till Core::renderer($type).
```

Se `wexoebuilder/NEW_PAGE_TYPE.md` § FAS 0 för exempel.

### Output
En `prototype.html` i en arbetskatalog. Den är *inte* checkad in i repon — den är en arbetsartefakt som följer med från FAS 1 till FAS 2 till FAS 3.

---

## FAS 1 — Schema-design (denna repo)

Före någon kod: vi designar Airtable-tabellen och skriver Core-läs-schemat.

### Beslut innan tabell skapas

1. **Vilka fält behövs?** Härleds från HTML-prototypen. Varje `<!-- field: -->` blir ett Airtable-fält. Varje `<!-- repeat -->` kräver antingen en separat tabell ELLER ett pseudo-array-mönster (numrerade fält som `Section 1 H2`, `Section 2 H2`, ...).
2. **Vilken bas?** `appokKSTaBdCa8YiW` (Wexoe NY, snake_case-kanonisk). `appXoUcK68dQwASjF` är legacy och fasas ut.
3. **Tabellnamn?** `cms_<plural>` för publika sidor, `core_<plural>` för SSOT, `inbox_<plural>` för formulär-inflöden. Plural för kollektioner.
4. **Primärnyckel?** Nästan alltid `slug`. Måste vara unik per record.
5. **Linked records?** Om sidan har child-records (tabs, downloads, sections) — separat tabell med `*_id` eller `*_ids`-fält. Pseudo-array används bara när antalet är fixerat och litet (`count: 4` eller `count: 3`).

### Steg

1. **Härled fältlistan.** Skriv ut alla fält du tror behövs, inkl. Airtable-typ (singleLineText, longText, checkbox, multipleAttachments, multipleRecordLinks, ...) och kort beskrivning. Visa mig listan innan tabellen skapas.
2. **Skapa tabellen i Airtable** via MCP (`mcp__d3b2344d-9467-4fc9-b768-696435140bf6__create_table`). Lägg fält i ordning som matchar prototypens sektioner (lättare att navigera i Airtable-UI:t).
3. **Lägg till en exempel-record** så det går att verifiera schemat. Slug `test`, alla obligatoriska fält ifyllda, valfria tomma.
4. **Skriv `wexoe-core/entities/<name>.php`** enligt formatet i `UTVECKLINGSGUIDE.md` § 4. Filnamnet = entity-namnet (utan prefix om du vill — `audience_heroes.php`, inte `cms_audience_heroes.php` — men matcha gärna Airtable-tabellnamnet om det är nytt). Sätt:
   - `table_id` → tableId från Airtable
   - `primary_key` → 'slug'
   - `cache_ttl` → 86400
   - `required` → minst `['slug']`
   - `fields` → mappa varje Airtable-fält till en snake_case domännyckel
5. **Verifiera lokalt.** Aktivera pluginet på en WP-staging-miljö och kör `Core::entity('<name>')->find('test')` i en debug-route eller via en provisorisk shortcode med `debug=true`. Se att output ser rätt ut.

### Vad du INTE gör i FAS 1
- Skriv inga PHP-plugins än.
- Skapa inga builder-editorer än.
- Skriv inget rendering-kod alls.

Att hålla denna fas snäv gör att schemafel upptäcks innan du har 1000 rader rendering att skriva om.

---

## FAS 2 — PHP-plugin (denna repo)

Pluginet renderar shortcoden på WordPress-sidan. Det är ofta den mest CSS-tunga delen av jobbet — själva data-läsningen är trivial via Core.

### Mapp- och filstruktur

```
New plugins/wexoe-<type>/
  wexoe-<type>.php          # All kod i en fil — det är konventionen
```

För större plugins (Product Area, Landing Page) kan filen vara 1000+ rader. Det är OK — sidtypen ÄR komplex, och ett enskilt plugin per fil förenklar zip-distribuering.

### Anatomi (kort)

Se `UTVECKLINGSGUIDE.md` § 6 för fullt exempel. Sammanfattning:

1. Plugin-header
2. `wexoe_<short>_core_ready()`-funktion
3. Klass `Wexoe_<Type>` med constructor som registrerar shortcode
4. `render_shortcode($atts)`-metod som:
   - validerar slug-param
   - checkar Core-readiness
   - hämtar data via `Core::entity('<name>')->find($slug)`
   - filtrerar bort om `is_active` är false (om relevant)
   - returnerar `<pre>print_r($data)</pre>` om `debug=true`
   - producerar HTML via `ob_start()` / `ob_get_clean()`
5. Section-renderer-metoder (en per `<!-- section -->` i prototypen)
6. Format-helpers (markdown via `Markdown::to_inline`, color via `Color::normalize_hex`, ...)

### CSS-scoping

```php
$id = 'wexoe-' . substr($atts['slug'], 0, 8) . '-' . uniqid();
?>
<style>
    #<?php echo $id; ?> .hero { background: <?php echo esc_attr($bg); ?>; }
    #<?php echo $id; ?> .hero h1 { color: <?php echo esc_attr($fg); ?>; }
</style>
<div id="<?php echo $id; ?>">
    ...
</div>
```

Aldrig global CSS. Aldrig `!important`. Två instanser av samma shortcode på samma sida ska inte krocka.

### Polymorfa records

Om någon underliggande tabell har polymorfa records (tab_type, sidebar_type, etc.), branch:a i pluginet:

```php
foreach ($tabs as $tab) {
    switch ($tab['tab_type']) {
        case 'textimage': echo render_textimage($tab); break;
        case 'faq':       echo render_faq($tab);       break;
        // ...
    }
}
```

Buildern hanterar stale-field-clearing vid typbyten. Pluginet kan anta att fält som inte hör till aktuell typ är tomma.

### Delade renderers

Om HTML-prototypen har `<!-- shared: ContactForm -->`, använd:

```php
$class = \Wexoe\Core\Core::renderer('contact-form');
if ($class !== '') {
    echo $class::render([
        'eyebrow'   => $data['contact_form_eyebrow'],
        'title'     => $data['contact_form_title'],
        // ... mappa state-fält till renderer-config
    ]);
}
```

Aktuella delade renderers (se `UTVECKLINGSGUIDE.md` § 3.5): `hero`, `text-image`, `text-only`, `faq`, `team-grid`, `partners-marquee`, `testimonial-card`, `cta-banner`, `contact-form`.

### Verifiering

1. `php -l` på pluginfilen — syntax-clean.
2. Aktivera pluginet på en staging-WP. Lägg shortcode i ett page med din test-slug. Verifiera att alla sektioner renderar.
3. Toggla checkboxar i Airtable och kör `Core::entity('<name>')->force_refresh()`. Verifiera att villkorlig markup respekteras.
4. Testa edge cases: tomma fält, saknad attachment, raderad linked record. Pluginet ska aldrig krascha — bara visa tomt eller defaulta.

---

## FAS 3 — Builder-editor (annan repo)

Skiftar till `wexoebuilder`. Se `wexoebuilder/NEW_PAGE_TYPE.md` för fullständigt recept.

Sammanfattning av vad som händer där:
1. State-types + mappers (Airtable record ↔ TS-state)
2. `PageTypeServerDef` + `PageTypeUIDef` deklarationer
3. Editor-komponenter (en per section i prototypen)
4. Preview-komponent
5. API-route + server-pages
6. Registry-entry

När FAS 3 är klar är sidtypen live: marknadsföraren ser den i `/`-listan, kan skapa nya records, redigera och spara. Pluginet på WP-sidan plockar upp uppdateringar via cache-invalidering.

---

## Kickoff-prompt

Kopiera-och-redigera. En prompt per fas, ny Claude-session per fas så kontextet hålls rent.

### Prompt — FAS 0 (HTML-prototyp)

Körs gärna i en separat Claude-session (eller direkt i en design-iterations-app). Output: en `prototype.html`-fil.

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

Körs i en Claude-session med Airtable MCP och `wexoeplugins`-repot. Output: en `entities/<name>.php`-fil och en skapad Airtable-tabell.

```
Sidtypen <NAMN> ska få ett eget Airtable-bord + Core-schema.

Här är den annoterade HTML-prototypen:

  <bädda in prototype.html>

Läs `UTVECKLINGSGUIDE.md` § 2 (Naming conventions), § 4 (Läs-schemaformat)
och `NEW_PAGE_TYPE.md` § FAS 1.

Producera:
1. En fält-lista i tabellform: Airtable-fältnamn (snake_case, engelska),
   Airtable-typ (singleLineText, longText, checkbox, multipleAttachments,
   multipleRecordLinks, ...), kort beskrivning, och vilket
   <!-- field: --> i prototypen det motsvarar. SHOWA listan innan du
   skapar tabellen — invänta godkännande.

2. När jag godkänt: skapa tabellen i base appokKSTaBdCa8YiW (Wexoe NY)
   via Airtable MCP. Tabellnamn `cms_<plural>` (eller `core_<plural>` /
   `inbox_<plural>` beroende på syfte). Lägg fälten i ordning som matchar
   prototypens sektioner.

3. Lägg till en exempel-record med slug `test` och tillräckligt med data
   för att alla sektioner ska renderas.

4. Skriv `wexoe-core/entities/<entity_name>.php`:
   - table_id från det nya bordet
   - primary_key = 'slug'
   - cache_ttl = 86400
   - required = ['slug']
   - fields: mappa varje Airtable-fält till snake_case domännyckel,
     med rätt typ (bool/int/float/lines/link/attachment/pseudo_array
     enligt schemaformat-spec).

Skapa INGEN PHP-plugin och ingen builder-editor i denna fas — bara tabell + schema.
```

### Prompt — FAS 2 (PHP-plugin)

Körs i en session med `wexoeplugins`-repot. Output: en zip-bar plugin-mapp under `New plugins/`.

```
Tabellen och Core-schemat för <NAMN> är klart (entities/<entity_name>.php).
Nu bygger vi PHP-pluginet som renderar shortcode på WP.

Här är den annoterade HTML-prototypen:

  <bädda in prototype.html>

Här är Core-schemat (`wexoe-core/entities/<entity_name>.php`):

  <bädda in schemafilen>

Läs `UTVECKLINGSGUIDE.md` § 3 (Core publikt API), § 6 (Anatomi av ett
feature-plugin) och `NEW_PAGE_TYPE.md` § FAS 2.

Studera ett existerande plugin som referens — `wexoe-audience-hero/` är
en bra "enkel" referens; `wexoe-landing-page/` är mer komplex.

Producera under `New plugins/wexoe-<type>/wexoe-<type>.php`:

1. Plugin-header (Name, Description, Version, Author).
2. `wexoe_<short>_core_ready()`-funktion.
3. Klass `Wexoe_<Type>` med shortcode-registrering i konstruktorn.
4. `render_shortcode($atts)` som:
   - validerar slug
   - checkar Core-readiness och returnerar felmeddelande om Core saknas
   - hämtar data via Core::entity('<entity_name>')->find($atts['slug'])
   - filtrerar bort om is_active === false (om sidtypen har den toggle:n)
   - returnerar print_r($data) om debug=true
   - producerar HTML via ob_start()/ob_get_clean()
5. Rendering som matchar prototypen:
   - varje <!-- field: --> mappas till motsvarande $data['<key>']
   - varje <!-- conditional: --> branch:as i PHP
   - varje <!-- repeat: --> itereras med foreach
   - varje <!-- shared: <Type> --> renderas via Core::renderer($type)
6. CSS scoped via `wexoe-<short>-{uniqid()}`-wrapper. Aldrig global CSS,
   aldrig !important.
7. Använd Core-helpers där det passar (Markdown, Color, YouTube, Lines).
8. Boolean-fält är redan bool — använd direkt i if-statements.
9. Linked records: filtrera + sortera i pluginet (Core gör inte det åt dig).

Krav:
- `php -l` ska gå ren.
- Leverera som en zip-bar mapp under `New plugins/`.
- Inkludera en kort README.md i mappen som visar shortcode-användning.
```

### Prompt — FAS 3 (Builder-editor)

Körs i en session med `wexoebuilder`-repot. Output: alla filer enligt checklistan i `wexoebuilder/NEW_PAGE_TYPE.md`.

```
PHP-pluginet och Core-schemat för <NAMN> är klart. Nu bygger vi builder-editorn.

Här är den annoterade HTML-prototypen:

  <bädda in prototype.html>

Här är Core-schemat (`wexoeplugins/wexoe-core/entities/<entity_name>.php`):

  <bädda in schemafilen>

Läs `CLAUDE.md` och `NEW_PAGE_TYPE.md` i denna repo. Studera Audience
(`lib/page-types/audience.*`, `components/audience/`) för Lager 1-referens
eller Product Area för Lager 3-referens.

Avgör först:
- Lager 1 (alla fält 1-till-1, ingen child-tabell) — default
- Lager 2 (separat child-tabell, t.ex. tabs/products) — använd relations[]
- Lager 3 (full override, Claude-transform behövs) — bara om något i
  prototypen kräver det (polymorfa items, pipe-format, multi-tabell-skrivning
  med specifik ordning)

Producera alla filer enligt checklistan i NEW_PAGE_TYPE.md:
- lib/<type>-types.ts
- lib/<type>-mapper.ts
- lib/page-types/<type>.server.ts
- lib/page-types/<type>.ui.tsx
- components/<type>/editors/*.tsx (en per <!-- section --> i prototypen)
- components/<type>/preview/<Type>PreviewPanel.tsx
- app/api/<type>/route.ts
- app/editor/<type>/page.tsx + app/editor/<type>/[recordId]/page.tsx
- lib/wexoe-cache-entities.ts (lägg till <TYPE>_ENTITIES)
- lib/page-types/registry.ts (lägg till entry i PAGE_TYPES)

Krav:
- snake_case på Airtable-fält i mappers; konsekvent stil i state.
- State serialiserbar (inga funktioner/Date-objekt).
- Editor-sektioner matchar <!-- section -->-strukturen i prototypen.
- Visibility-toggles där prototypen anger `visibility:<bool>`.
- Preview-komponenten renderar ungefär samma struktur som PHP-pluginet.

Visa förslag på state-struktur + section-uppdelning innan implementation.
```

---

## Snabbreferenser

- `UTVECKLINGSGUIDE.md` — Core-API, schemaformat, plugin-anatomi
- `wexoe-core/src/Core.php` — auktoritativ kod-dokumentation
- `wexoe-core/entities/audience_heroes.php` — minsta läs-schema-referens
- `wexoe-core/entities/landing_pages.php` — komplext schema med många typer
- `wexoe-core/write-entities/user_submissions.php` — write-schema-referens
- `New plugins/wexoe-audience-hero/wexoe-audience-hero.php` — enklaste plugin
- `New plugins/wexoe-landing-page/wexoe-landing-page.php` — komplext plugin med polymorfa tabs
- `wexoebuilder/NEW_PAGE_TYPE.md` — bygger-sidan av samma flöde
