# Skapa en ny sida — så här gör du

Den här filen guidar dig — marknadsföraren — genom att lägga till en ny sidtyp i Wexoe-systemet. Du behöver inte kunna programmera. Du driver flowet, LLM:er gör jobbet.

Filen är speglad i båda repon (`wexoeplugins` och `wexoebuilder`) så du hittar den oavsett vilken du råkar öppna. Tekniska detaljer ligger i `NEW_PAGE_TYPE.md` i respektive repo — du behöver INTE läsa dem.

---

## 30 sekunders översikt

Att skapa en ny sidtyp består av **fyra faser**. Varje fas är en separat LLM-session. Mellan faserna granskar du resultatet och bestämmer om du går vidare.

```
   FAS 0          FAS 1            FAS 2          FAS 3
   ──────         ──────           ──────         ──────
   Skiss          Schema           Plugin         Editor
     +              +                +              +
   HTML-      Airtable-           PHP-kod        Builder-UI
   prototyp   tabell + Core       som renderar   med live-preview
              schema              på WP
   ↓               ↓                  ↓              ↓
   ~30-90 min     ~15-30 min        ~30-60 min     ~30-90 min
```

**Total tid:** En arbetsdag för en enkel sidtyp, två-tre för en komplex. Mest tid går åt till FAS 0 (iteration på designen) och FAS 3 (testning av editorn).

---

## Status-markör

Klistra in i början av VARJE LLM-prompt så att LLM:en (och du) direkt ser var i flowet ni är:

```
🚦 SKAPA-SIDA STATUS
   Sidtyp:        <namn — t.ex. "Case-sida">
   Aktuell fas:   FAS X av 4 — <fas-namn>
   Föregående:    <vad du redan har klart, eller "inget än">
   Mål nu:        <vad denna prompt ska producera>
```

Detta är inte dekoration — det är hand-offen. Det är så du och LLM:en undviker att tappa bort var ni är, speciellt om du gör pauser mellan faserna eller låter någon annan ta över.

---

## FAS 0 — Skiss + HTML-prototyp

🎯 **Målet:** En statisk HTML-fil som visar exakt hur den färdiga sidan ska se ut, med kommentarer som markerar vilka delar som är dynamiska.

⏱ **Tid:** 30–90 minuter (iterationer)

🛠 **Verktyg:** Vilken Claude-session som helst — claude.ai, desktop-appen, eller en ny session i en av repona. Spelar ingen roll, ingen kod-koppling än.

📍 **Var det landar:** En `prototype.html`-fil på din dator. Den **committas inte** — det är ett arbetsdokument som slängs när flowet är klart.

### 👤 Vad DU gör
1. Skissa designen i Figma (eller papper, eller skärmdump av en konkurrent — vad som funkar).
2. Öppna en ny Claude-session.
3. Klistra in kickoff-prompten nedan + bifoga skissen.
4. Iterera på resultatet tills du kan peka på prototypen och säga "ja, så här vill jag att sidan ska se ut".

### 🤖 Vad LLM:en gör
Producerar HTML med inline CSS som motsvarar skissen, med Wexoe:s annoteringskonventioner (`<!-- field: -->`, `<!-- conditional: -->`, `<!-- repeat: -->`, `<!-- section: -->`).

### ✅ Klart när
- Du har en `prototype.html` som visuellt matchar din skiss.
- Alla dynamiska delar är annoterade (LLM:en ska inte ha gissat sig till var listor/villkor är).
- Sektionsgränser stämmer med hur du vill att editorn ska delas in.

### 📤 Hand-off till FAS 1
`prototype.html`-filens innehåll. Du kommer klistra in den i FAS 1-prompten.

### Kickoff-prompt

```
🚦 SKAPA-SIDA STATUS
   Sidtyp:        <NAMN — t.ex. "Case-sida">
   Aktuell fas:   FAS 0 av 4 — Skiss + HTML-prototyp
   Föregående:    Inget än
   Mål nu:        Producera prototype.html med annoteringar

Jag vill bygga en ny sidtyp för Wexoe-systemet: <NAMN>.

Bifogat finns en Figma-skiss av designen.

Producera en statisk HTML-prototyp av sidan med inline CSS. Använd Wexoe:s
annoteringskonventioner för att markera dynamiska delar:

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

Vi itererar tills jag är nöjd — börja med ett första utkast och vänta på
feedback.
```

---

## FAS 1 — Airtable-tabell + Core-schema

🎯 **Målet:** En Airtable-tabell att fylla med innehåll + ett Core-schema som lär WP-systemet hur tabellen ska läsas.

⏱ **Tid:** 15–30 minuter

🛠 **Verktyg:** Claude Code-session med **wexoeplugins**-repot öppet + Airtable MCP aktivt.

📍 **Var det landar:**
- Ny tabell i Airtable-basen `Wexoe NY` (`appokKSTaBdCa8YiW`).
- Ny fil: `wexoeplugins/wexoe-core/entities/<entity_name>.php` (committas).

### 👤 Vad DU gör
1. Öppna en ny Claude Code-session mot `wexoeplugins`-repot.
2. Klistra in kickoff-prompten nedan + hela innehållet i prototype.html.
3. **Vänta på fält-listan.** LLM:en SKA visa dig en tabell med föreslagna Airtable-fält INNAN tabellen skapas. Granska den.
4. Säg "ja, skapa tabellen" eller justera (t.ex. "döp om field X till Y", "lägg till en checkbox för Z").
5. När tabellen är skapad + schema-filen finns, säg till LLM:en att committa och pusha.

### 🤖 Vad LLM:en gör
- Föreslår fält-lista i tabellform (Airtable-namn, typ, beskrivning, koppling till `<!-- field -->` i prototypen).
- Efter ditt OK: skapar Airtable-tabellen via MCP, lägger till en exempel-record med slug `test`.
- Skriver `entities/<entity_name>.php`.
- Committar och pushar på din befintliga branch.

### ✅ Klart när
- Du ser den nya tabellen i Airtable.
- En `test`-record finns och har data i alla viktiga fält.
- `entities/<entity_name>.php` är committad i `wexoeplugins`-repot.
- LLM:en har INTE påbörjat något plugin eller någon editor (det är nästa fas).

### 📤 Hand-off till FAS 2
- `prototype.html`-innehållet (samma som FAS 1)
- Filen `entities/<entity_name>.php` (LLM:en kan läsa den från repot)

### Kickoff-prompt

```
🚦 SKAPA-SIDA STATUS
   Sidtyp:        <NAMN>
   Aktuell fas:   FAS 1 av 4 — Airtable-tabell + Core-schema
   Föregående:    prototype.html är klar
   Mål nu:        Skapa Airtable-tabell + entities/<entity_name>.php

Här är den annoterade HTML-prototypen:

  <bädda in hela prototype.html>

Läs `UTVECKLINGSGUIDE.md` § 2 (Naming conventions), § 4 (Läs-schemaformat)
och `NEW_PAGE_TYPE.md` § FAS 1.

Producera:
1. En fält-lista i tabellform: Airtable-fältnamn (snake_case, engelska),
   Airtable-typ, kort beskrivning, och vilket <!-- field --> i prototypen
   det motsvarar. Visa listan innan du skapar tabellen och invänta godkännande.

2. När jag godkänt: skapa tabellen i base appokKSTaBdCa8YiW (Wexoe NY)
   via Airtable MCP. Tabellnamn `cms_<plural>`.

3. Lägg till en exempel-record med slug `test`.

4. Skriv `wexoe-core/entities/<entity_name>.php` enligt schemaformatet.
   Primärnyckel `slug`, cache_ttl 86400, required ['slug'].

Skapa INGEN PHP-plugin och ingen builder-editor i denna fas — bara
tabell + schema. Commita och pusha när det är klart.
```

---

## FAS 2 — PHP-plugin (rendering på WP)

🎯 **Målet:** Ett WordPress-plugin som tar din Airtable-data och renderar publika sidor med shortcode `[wexoe_<typ> slug="..."]`.

⏱ **Tid:** 30–60 minuter

🛠 **Verktyg:** Claude Code-session med **wexoeplugins**-repot. Behöver INTE Airtable MCP.

📍 **Var det landar:**
- Ny mapp: `wexoeplugins/New plugins/wexoe-<type>/wexoe-<type>.php` (committas).

### 👤 Vad DU gör
1. Öppna en ny Claude Code-session mot `wexoeplugins`-repot.
2. Klistra in kickoff-prompten + prototype.html.
3. Vänta på leverans. LLM:en bygger pluginet.
4. Ladda ner / zip:a plugin-mappen.
5. Aktivera på WordPress-staging (eller direkt på prod om du är modig och har backup).
6. Skapa en WP-sida med shortcode `[wexoe_<type> slug="test"]` och se att det renderar din test-record.
7. Testa även `[wexoe_<type> slug="test" debug="true"]` — den dumpar rådatan från Core. Om något ser knasigt ut, peka tillbaka till LLM:en.

### 🤖 Vad LLM:en gör
- Studerar ett existerande plugin som referens (Audience är enklast, Landing Page är mest komplex).
- Skriver `wexoe-<type>.php` med plugin-header, shortcode-registrering, render-funktion som läser via Core, och rendering som matchar prototypens annoteringar.
- Använder Core-helpers (Markdown, Color, YouTube, Lines) och delade renderers där prototypen säger så.
- Kör `php -l` för syntax-koll.
- Committar och pushar.

### ✅ Klart när
- WP-sidan med shortcoden renderar din test-data.
- Den ser ut som prototypen (åtminstone strukturellt — pixel-perfekt CSS kan kräva en runda till).
- `debug=true`-läget fungerar.
- Tom-värden kraschar inte (testa genom att tömma valfria fält i Airtable och köra `force_refresh()`).

### 📤 Hand-off till FAS 3
- `prototype.html`-innehållet
- Filen `entities/<entity_name>.php`

### Kickoff-prompt

```
🚦 SKAPA-SIDA STATUS
   Sidtyp:        <NAMN>
   Aktuell fas:   FAS 2 av 4 — PHP-plugin
   Föregående:    Airtable-tabell + Core-schema är klart
   Mål nu:        Skapa New plugins/wexoe-<type>/wexoe-<type>.php

Här är den annoterade HTML-prototypen:

  <bädda in hela prototype.html>

Här är Core-schemat (`wexoe-core/entities/<entity_name>.php`):

  <bädda in schemafilen>

Läs `UTVECKLINGSGUIDE.md` § 3 (Core publikt API), § 6 (Anatomi av ett
feature-plugin) och `NEW_PAGE_TYPE.md` § FAS 2.

Studera `New plugins/wexoe-audience-hero/wexoe-audience-hero.php` som
referens för enkla sidtyper, eller `wexoe-landing-page/wexoe-landing-page.php`
för komplexa.

Producera `New plugins/wexoe-<type>/wexoe-<type>.php`:
- Plugin-header
- Core-readiness-check
- Shortcode [wexoe_<type> slug="..." debug="false"]
- Rendering som matchar prototypens annoteringar exakt
- CSS scoped via wexoe-<short>-{uniqid()}-wrapper
- Använd Core-helpers (Markdown, Color, YouTube, Lines) där det passar
- För <!-- shared: --> använd Core::renderer($type)

php -l ska gå ren. Commita och pusha när det är klart.
```

---

## FAS 3 — Builder-editor

🎯 **Målet:** En editor i Wexoe-buildern där du (och andra marknadsförare) kan skapa och redigera sidor av denna typ utan att se Airtable.

⏱ **Tid:** 30–90 minuter

🛠 **Verktyg:** Claude Code-session med **wexoebuilder**-repot.

📍 **Var det landar:** En handfull filer i `wexoebuilder` (server.ts, ui.tsx, editors, preview, route, pages) — LLM:en hanterar listan. Allt committas.

### 👤 Vad DU gör
1. Öppna en ny Claude Code-session mot `wexoebuilder`-repot.
2. Klistra in kickoff-prompten + prototype.html + schema-filen.
3. **Vänta på förslag.** LLM:en ska föreslå state-struktur och sektionsuppdelning INNAN den implementerar.
4. Godkänn eller justera. Sen kör LLM:en.
5. När det är klart: `npm run dev`, gå till `/`, klicka "Ny sida" → välj din nya typ → fyll i → spara.
6. Verifiera att sidan dök upp i Airtable. Ladda om WordPress-sidan med din slug och se att den renderar (cachen rensas automatiskt vid spar).

### 🤖 Vad LLM:en gör
- Avgör vilket "lager" sidtypen behöver (1, 2 eller 3 — teknisk detalj du inte behöver bry dig om).
- Skriver state-typer, mappers, server.ts, ui.tsx, editor-komponenter, preview-komponent, API-route, server-pages.
- Lägger till entry i registry så sidtypen syns på `/`.
- Committar och pushar.

### ✅ Klart när
- Du kan skapa, redigera och spara en sida av den nya typen via buildern.
- Live-preview matchar pluginet (åtminstone strukturellt).
- WP-sidan uppdateras när du sparar.
- Pluginet och buildern visar samma sak (det är hela poängen).

### 📤 Hand-off
Inga fler faser. Du är klar.

### Kickoff-prompt

```
🚦 SKAPA-SIDA STATUS
   Sidtyp:        <NAMN>
   Aktuell fas:   FAS 3 av 4 — Builder-editor
   Föregående:    PHP-plugin + Core-schema är klart
   Mål nu:        Bygga builder-editor i wexoebuilder-repot

Här är den annoterade HTML-prototypen:

  <bädda in hela prototype.html>

Här är Core-schemat (`wexoeplugins/wexoe-core/entities/<entity_name>.php`):

  <bädda in schemafilen>

Läs `CLAUDE.md` och `NEW_PAGE_TYPE.md` i denna repo. Studera Audience
(`lib/page-types/audience.*`, `components/audience/`) som referens för
enkla sidtyper, eller Product Area för komplexa.

Avgör först vilket lager sidtypen behöver (1, 2 eller 3) baserat på
prototypen. Motivera valet.

Visa förslag på state-struktur + section-uppdelning INNAN du implementerar.
Invänta godkännande.

Sedan: producera alla filer enligt checklistan i NEW_PAGE_TYPE.md. Commita
och pusha när det är klart.
```

---

## När det går fel

**LLM:en hoppar över ett steg eller missförstår vad fas vi är i.**
Kolla att du klistrade in status-markören och hela prototype.html. LLM:en utan kontext gissar.

**Schema-fältet finns inte i Airtable.**
LLM:en kanske skapade tabellen men glömde fältet, eller schemat refererar fel namn. Be om en diff mellan schema-filen och Airtable-tabellen.

**Sidan i WP visar "ingen data".**
Är pluginet aktivt? Stämmer slug? Kör `[shortcode slug="..." debug="true"]` för att se vad Core returnerar. Om det är `null` — antingen finns posten inte, eller är `is_active` false, eller cachen är stale (vänta 24h eller kör `Core::entity('xxx')->force_refresh()`).

**Live-preview i buildern ser inte ut som pluginet.**
Preview-komponenten och PHP-renderingen är två kopior av samma layout. Be FAS 3-LLM:en att jämföra dem sida vid sida med prototypen som spec.

**Du tappade bort var i flowet du är.**
Öppna senaste git-commit i en av repona. Commit-meddelandet visar vilken fas som senast levererades. Status-markören i din nästa prompt ska peka på nästa fas.

---

## När du är klar — städning

1. **Radera `prototype.html`** lokalt om du har den kvar. Den är inte längre källan-of-truth — pluginet + buildern är.
2. **Radera test-record:en** i Airtable (eller behåll om du vill ha den som visuell sanity-check).
3. **Skapa "riktig" content.** Skapa de första riktiga sidorna av denna typ via buildern.
4. **Senare ändringar:** Om du vill ändra hur sidan ser ut, gå direkt till PHP-pluginet eller builder-preview-komponenten — du behöver inte återskapa prototypen.

---

## Var jag tar vägen om jag vill veta mer

- `NEW_PAGE_TYPE.md` (i denna repo) — teknisk referens om du eller en LLM behöver detaljer per fas
- `UTVECKLINGSGUIDE.md` (i `wexoeplugins`) — fullständig referens för Core-API och plugin-konventioner
- `CLAUDE.md` (i `wexoebuilder`) — fullständig arkitektur-översikt över buildern

Men om du är marknadsförare: ovanstående räcker. Du behöver inte läsa de tekniska filerna.
