# Wexoe — Arkitekturplan (modularisering)

> **Denna fil är spegelidentisk i båda repona** (`wexoebuilder` och `wexoeplugins`).
> Den är den enda källan-of-truth för arkitektur-refaktorn. Ändrar du den, spegla
> ändringen till andra repot i samma logiska change-set. Detta är den *enda*
> sanktionerade dupliceringen i hela planen: en delad nordstjärna som ändras
> sällan. Allt annat i planen handlar om att *ta bort* duplicering.
>
> **Ersätter** den tidigare `MIGRATION-PLAN.md` (Airtable-migrationen Wexoe →
> Wexoe NY, nu slutförd; gammalt innehåll finns i git-historiken).

---

## 0. Hur du använder det här dokumentet (läs om du är en LLM)

1. **Läs hela § 1 (Syfte) innan du rör en rad kod.** Syftet är skrivet för att
   inte kunna misstolkas. Om din planerade åtgärd krockar med ett *icke-mål*
   i § 1.3 — stanna och fråga människan. Gör den inte ändå.
2. **Arbeta fas för fas i ordning** (§ 5). Faser har beroenden; hoppa inte.
3. **En fas är klar först när ALLA dess acceptanskriterier (kryssrutor) är
   uppfyllda och verifierade** — inte när koden "ser klar ut".
4. **Bocka av i § 6 (Progress)** och lägg en datinstämplad rad i ändringsloggen.
   Spegla statusändringen till andra repot.
5. **Pilot-först.** Varje fas införs på *en* sidtyp (`customer-type`) hela vägen
   ut, verifieras, och replikeras sedan. Aldrig big-bang över alla sidtyper.
6. **Beteendebevarande.** Slutanvändaren (marknadsföraren) ska inte märka någon
   skillnad i vad som går att göra — bara att det blir snabbare och stabilare.
   Om en ändring tar bort en förmåga: det är en regression, inte en förenkling.

**Statuslegend:** `[ ]` ej påbörjad · `[~]` pågår · `[x]` klar & verifierad ·
`[!]` blockerad (se ändringslogg). Repo-tagg: **[BUILDER]** · **[PLUGINS]** · **[BÅDA]**.

---

## 1. Syfte

### 1.1 Problemet i en mening

Samma fält och samma byggstenar är handskrivna på 6–10 ställen per sidtyp, och en
icke-deterministisk LLM sitter på den kritiska spar-vägen — vilket gör att varje
ny sidtyp eller fältändring är dyr, långsam och riskabel, trots att systemets
vision och flera av dess byggstenar redan är rätt.

### 1.2 Vad vi vill uppnå (och varför)

Målet med Wexoe-systemet är, och förblir, **en smidig page builder där en
marknadsförare ändrar innehåll som lagras i Airtable och projiceras via
WordPress-plugins — utan att någonsin behöva se Airtable eller WP-admin.**

Den här planen ändrar inte det målet. Den ändrar *mekaniken under det* så att
systemet blir:

- **Modulärt** — ett återkommande element (kontaktformulär, hero, FAQ, CTA)
  definieras *en gång* (schema + editor + design) och återanvänds, istället för
  att kopieras in i varje sidtyp.
- **Skalbart** — att lägga till en sidtyp eller ett fält ska vara
  *datadeklaration på ett ställe*, inte parallell handkodning i två repon och
  tio filer.
- **Framtidssäkrat** — spar ska vara deterministiskt, snabbt och utan
  tredjepartsberoende; rendering och preview ska dela källa så de inte driftar
  isär.

### 1.3 Icke-mål (gör INTE detta — det är inte "hjälpsamt", det är fel)

Den här planen är en **inkrementell refaktor inom befintlig stack**, inte en
omskrivning. Följande är uttryckliga icke-mål. En LLM som gör något av detta
"för att det verkade modernare/renare" har brutit mot planen:

- **Flytta INTE rendering från WordPress till Next.js / headless.** WordPress
  (Enfold) förblir renderaren. PHP-pluginen blir *tunnare*, inte borttagna.
- **Byt INTE ut Airtable som datalager** i den här planen. Airtable förblir
  CMS:et och wexoe-core förblir den enda komponenten som pratar med Airtable.
- **Ta INTE bort gränsen "en sidtyp = ett plugin = en editor".** Den är ett
  medvetet val för scope-isolering (oberoende aktivering, liten blast radius).
  Vi delar *byggstenarna* mellan sidtyper — vi river inte gränsen.
- **Ta INTE bort Claude helt.** Claude *flyttas* från spar-vägen till
  input-lagret (§ 1.4). Det är en omplacering, inte en avveckling.
- **Gör INGEN big-bang-migrering.** Allt går pilot-först, en sidtyp i taget,
  beteendebevarande.
- **Döp INTE om filer/fält reflexmässigt.** Naming-konventionerna och vissa
  legacy-filnamn är låsta (se `UTVECKLINGSGUIDE.md` § 2 / `CLAUDE.md`). Fråga.

### 1.4 Den bärande principen om Claude: två lager

Detta är den vanligaste missuppfattningen, så den slås fast här.

Det finns två steg mellan en marknadsförares input och Airtable:

- **Lager A — Input → ren, strukturerad state.** Att tolka fri/luddig/multimodal
  input ("Q=…/A=…", en tabellrad, inklistrad text) till strukturerad data
  (`{question, answer}`). Detta är *omdöme*. **Här är Claude värdefullt och får
  finnas kvar** — som en explicit, opt-in "tolka/importera"-åtgärd.
- **Lager B — ren state → Airtable-fält.** Att döpa om `statNumber` →
  `stat_number` och serialisera en lista till Airtable-format. Detta är
  *mekaniskt och deterministiskt*. **Här ska Claude bort.** En ren funktion gör
  jobbet snabbare, gratis och utan risk att hallucinera bort innehåll.

Idag kör systemet Claude på **Lager B** (spar) — fel lager. Editorn producerar
redan ren state innan Claude anropas, så Claude tillför ingen frihet där; den
tillför bara latens, kostnad och icke-determinism. Friheten i § 1.2 bor i
React-editorn (och valfritt i Lager A), aldrig på spar-knappen.

### 1.5 Den andra bärande principen: behåll gränsen, dela tegelstenarna

Scope-isolering (en plugin/editor per sidtyp) och kod-duplicering (varje plugin
skriver om hero/contact/CSS) är **två olika saker**. Vi behåller det första och
tar bort det andra. En sidtyp förblir sin egen plugin/editor — men byggd av
delade block, inte egna kopior. Att en delad renderer *finns tillgänglig* räcker
inte (kontaktformuläret är idag implementerat på 3 ställen trots
`Core::renderer('contact-form')`); återanvändning måste vara default, inte ett val.

---

## 2. Bakgrund (nuläget)

Två repon utgör systemet (se `CLAUDE.md` i respektive repo för full karta):

```
wexoebuilder  →  Airtable  →  wexoe-core  →  feature-plugins  →  WP-sidor
  (Next.js)     (Wexoe NY)    (transients)    (shortcodes)
```

Airtable-migrationen (Wexoe → Wexoe NY, `appokKSTaBdCa8YiW`) är **slutförd**;
legacy-basen är utfasad (0 referenser kvar i `wexoe-core`). Den historiska
loggen finns i `IMPLEMENTATION_LOG.md`.

### 2.1 Vad som är bra och MÅSTE bevaras

- **`route-factory.ts`** (builder) — generisk CRUD-factory (Lager 1/2/3),
  slug-validering, cache-invalidering, felhantering.
- **`PageTypeBuilder.tsx`** (builder) — det generiska editor-skalet (state,
  quickNav, live-preview med scroll-sync, dirty-guard, enhetligt save-flöde).
- **`Normalizer.php`** (plugins) — schemadriven läs-mappning. *Mönstret hela
  planen generaliserar.*
- **`lib/core/` (registry + forms)** (builder) — SSOT-entiteter renderas
  generiskt ur en fält-config utan bespoke-komponenter. *Beviset på att § 4
  redan fungerar i huset.*
- **`wexoe-core`-fasaden + cache** (plugins) — enda Airtable-auktoriteten;
  stale-while-revalidate; AirtableClient med retry/backoff. Produktionsklass.

### 2.2 Den tekniska skulden vi adresserar (med siffror)

- **Schemat definieras 6–10 gånger per sidtyp**: Airtable + `entities/*.php`
  (läs) + `write-entities/*.php` (skriv, *oanvänt på sidvägen*) +
  `lib/<type>-types.ts` + `lib/<type>-mapper.ts` + `lib/airtable-schema-*.md` +
  `claude-transform.ts`-payload + editor- + preview-komponenter + PHP-render.
  Koden bär "håll i synk med …"-kommentarer på ≥5 ställen.
- **Claude på spar-vägen**: `claude-transform.ts` (~1 570 rader) +
  6 schema-MD (~1 255 rader). ~1–3 s latens/spar, tokenkostnad, defensiva guards
  mot att tom array raderar allt innehåll.
- **Render-duplicering** (plugins, ~26 200 rader PHP): 14/14 plugins har egen
  inline-scoped CSS (~28 KB duplicerat), 6/15 sektionstyper implementerade på
  flera ställen, hero ×3, contact-form ×3.
- **Datamodell tvingad platt av Airtable**: `contact_form_*` (15 fält) ×6
  tabeller; pseudo-array-kolumner (`quick_stat_1_…`, `gallery_image_1_…`);
  dubbla case-modeller (`cases.php` / `case_pages.php` / `cms_cases.php`,
  två levande case-tabeller).
- **Builder-sprawl** (~25 900 rader TS): ~15 000 är per-sidtyps-bespoke som
  växer linjärt med antal sidtyper.

---

## 3. Mål (definition av "färdigt")

Systemet anses moderniserat när allt nedan är sant och verifierat:

- [ ] Ett fält i en sidtyp definieras på **exakt ett ställe** (ett delat schema);
      typ, mapper, editor-fält och PHP-läsning *härleds* därifrån.
- [ ] **Spar gör 0 Anthropic-anrop.** `claude-transform.ts` är borttagen.
- [ ] Återkommande element (contact_form, hero, faq, cta, testimonial)
      definieras **en gång** (schema + editor + render) och återanvänds.
- [ ] Marknadsföraren kan fortfarande ändra contact-form-text **per sida** utan
      att schemat lever i flera tabeller.
- [ ] Preview (builder) och render (plugin) delar **en källa** för
      block-struktur och för design-tokens (färg/typografi).
- [ ] **0 sektionstyper** implementerade på fler än ett ställe; `automation-pillar`
      migrerad till `wexoe-pages`-sektioner.
- [ ] **0 oanvända scheman** (`write-entities`-sidscheman borttagna eller
      faktiskt använda), **en** case-modell, **0** legacy-base-referenser.
- [ ] Claude finns kvar **enbart** på explicita input-/copy-åtgärder, aldrig
      på spar.

---

## 4. Principer (så att även olistade beslut blir rätt)

1. **Single source of truth.** Definiera en sak en gång; härled resten. Om du
   råkar skriva samma fältnamn i två filer — stanna, det är en regression.
2. **Deterministiskt på kritisk väg.** Spar/läs får aldrig bero på en
   probabilistisk eller extern tjänst.
3. **Behåll gränsen, dela tegelstenarna** (§ 1.5).
4. **Claude på Lager A, aldrig Lager B** (§ 1.4).
5. **Beteendebevarande & pilot-först.** En sidtyp i taget, verifiera, replikera.
6. **wexoe-core är enda Airtable-auktoriteten.** Inget feature-plugin och ingen
   build-process pratar med Airtable förbi Core (undantag: buildern, som äger
   skriv-vägen och redan går direkt mot Airtable REST).
7. **Naming & filnamn är låsta.** Se `UTVECKLINGSGUIDE.md` § 2 / `CLAUDE.md`.

---

## 5. Plan (faser i ordning)

> Pilot-sidtyp genomgående: **`customer-type`** (`cms_customer_type_pages`) — den
> enklaste (flat, en tabell). Den mest komplexa referensen att stresstesta mot
> efter pilot: **`product-area`** (multi-tabell, child-records).

### FAS 0 — Fundament & format-beslut · **[BÅDA]**

- [ ] Besluta källformat för delat schema: **neutral JSON** i
      `wexoe-core/schema/<entity>.json`, läst av både wexoe-core (PHP) och
      buildern (TS). Motiv: båda repona kan läsa JSON utan codegen-steg; PHP är
      inte importerbart i TS och tvärtom.
- [ ] Skriv en kort `wexoe-core/schema/README.md` som definierar schema-formatet
      (fält-typer: text, richtext, int, float, bool, image, url, link, lines,
      block-referens) — superset av dagens `Normalizer`-typer.
- [ ] Den här planen committad i båda repona.

**Klar när:** schema-formatet är dokumenterat och godkänt av människa.

### FAS 1 — Single source schema (pilot) · **[BÅDA]** · *kräver FAS 0*

- [ ] **[PLUGINS]** Skriv `wexoe-core/schema/cms_customer_type_pages.json` —
      fältlistan en gång.
- [ ] **[PLUGINS]** Låt `SchemaRegistry`/`Normalizer` läsa JSON-schemat för
      pilot-entiteten (adapter som producerar samma interna form som
      `entities/*.php` ger idag). Läs-beteendet ska vara identiskt.
- [ ] **[BUILDER]** Skriv en generisk `toState(record, schema)` (motsvarar
      `Normalizer` i TS) och härled `CustomerTypePageState`-typen ur schemat.
- [ ] **[BUILDER]** Härled editorns fältlista ur schemat (samma mönster som
      `lib/core/forms.ts` redan använder för SSOT).
- [ ] **[BUILDER]** Ta bort `customer-type-mapper.ts`-handmappningen,
      `airtable-schema-customer-type.md` och custom state-typen — allt härlett.

**Klar när:** `customer-type`-fält definieras på exakt ett ställe (JSON);
inga "håll i synk"-kommentarer kvar för typen; läs + editor fungerar oförändrat.

### FAS 2 — Deterministisk save (Claude bort från Lager B) · **[BUILDER]** · *kräver FAS 1*

- [ ] Implementera generisk `toFields(state, schema, mode)` (ren funktion;
      camelCase→snake_case, lines-join, pseudo-array-expansion, block-serialisering).
- [ ] **Shadow-mode:** kör `toFields` parallellt med `transformCustomerType`,
      jämför output vid varje spar, logga avvikelser. Flippa pilot till
      deterministisk väg efter ≥50 saves utan avvikelse.
- [ ] Replikera `toFields` + shadow-flip för övriga sidtyper (`product-area`,
      `cms-page`, `case`, `partner`, `landing`).
- [ ] Radera `claude-transform.ts` och de 6 `airtable-schema-*.md` när alla
      typer är flippade.

**Klar när:** spar gör 0 Anthropic-anrop; tom-array-guards behövs inte längre
(deterministiskt); p95 save-latens väsentligt lägre.

### FAS 3 — Delade block (contact_form först) · **[BÅDA]** · *gynnas av FAS 1*

- [ ] Besluta lagringsform för per-sida block-innehåll: **child-tabell**
      (`cms_contact_blocks`, en rad per sida) **eller JSON-kolumn**
      (`contact_form_json`). Krav: redaktör kan ändra text per sida; schemat
      definieras en gång; de 15 kolumnerna ×6 tabeller försvinner.
- [ ] Definiera `contact_form` som **ett block**: schema (en gång) + editor-
      komponent (en gång) + Core-renderer (utöka befintlig `ContactForm`).
- [ ] Migrera data för de 6 sidtyperna till blocket; uppdatera läs/skriv/editor/render.
- [ ] Upprepa mönstret för `faq`/QnA, `hero`, `cta`, `testimonial`.

**Klar när:** contact_form-schemat finns på ett ställe; 0 `contact_form_*`-kolumner
kvar i de 6 sidtabellerna (eller exakt 1 JSON-kolumn); text fortfarande
redigerbar per sida.

### FAS 4 — Delad CSS & design-tokens · **[BÅDA]** · *gynnas av FAS 3*

- [ ] Generera CSS-variabler ur `core_graphic_profile` (färg/typografi); en
      delad stylesheet/token-källa.
- [ ] Bryt ut bas-CSS (box-model, form-reset) till ett ställe; ta bort
      duplicerade inline-`<style>`-block.
- [ ] Buildernes preview och PHP-pluginen läser samma tokens.

**Klar när:** box-model/form-reset finns på ett ställe; ~28 KB duplicerad CSS
borta; preview och render delar färg/typografi-källa.

### FAS 5 — Plugin-konsolidering · **[PLUGINS]** · *gynnas av FAS 4*

- [ ] Migrera `automation-pillar`-shortcodes till `wexoe-pages`-sektioner.
- [ ] Dedupe hero/cta/testimonial/team-grid till delade sektioner/Core-renderers.

**Klar när:** 0 sektionstyper på >1 ställe; `automation-pillar` borttaget.

### FAS 6 — Död kod & legacy-städning · **[BÅDA]** · *parallellt, låg risk*

- [ ] Verifiera och ta bort oanvända `write-entities`-sidscheman
      (`customer_type_pages`, `case_pages`, `cms_pages`, `cms_page_sections`,
      `cms_section_tabs`) — behåll bara faktiskt använda (`user_submissions` m.fl.).
- [ ] Konsolidera dubbla case-modeller till **en** (`cases.php` /
      `case_pages.php` / `cms_cases.php` + de två case-tabellerna).
- [ ] Ta bort sista `LEGACY_BASE_ID`-referensen i buildern (efter FAS 5).

**Klar när:** 0 oanvända scheman; en case-modell; 0 legacy-base-referenser.

### FAS 7 — Claude på input-lagret (valfritt/framtid) · **[BUILDER]** · *kräver FAS 2*

- [ ] Implementera valfri "tolka/importera"-åtgärd (freeform/multimodal input →
      ren state) som producerar samma state-form som editorn.

**Klar när:** Claude finns kvar enbart på explicit input/copy-åtgärd, aldrig på spar.

---

## 6. Progress

> Uppdatera kryssrutorna i § 3 och § 5. För repo-specifika uppgifter är ägar-repots
> kryssruta auktoritativ; spegla statusraden hit i båda repona.

| Fas | Status | Repo | Kort notis |
|---|---|---|---|
| 0 — Fundament | [ ] | BÅDA | — |
| 1 — Single source schema (pilot) | [ ] | BÅDA | — |
| 2 — Deterministisk save | [ ] | BUILDER | — |
| 3 — Delade block | [ ] | BÅDA | — |
| 4 — Delad CSS/tokens | [ ] | BÅDA | — |
| 5 — Plugin-konsolidering | [ ] | PLUGINS | — |
| 6 — Död kod/legacy | [ ] | BÅDA | — |
| 7 — Claude på input (valfritt) | [ ] | BUILDER | — |

### Ändringslogg

Lägg nyaste överst. Format: `YYYY-MM-DD — [repo] — vad gjordes / vad återstår`.

- 2026-05-29 — [BÅDA] — Plan skapad, ersätter `MIGRATION-PLAN.md`. Inga
  kodändringar ännu.
