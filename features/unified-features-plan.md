# Wexoe enhetlig utvecklingsplan: SSOT, unika sidor och kontaktformulär

**Status:** Beslutad — redo för implementation
**Senast uppdaterad:** 2026-05-13 (rev 2)
**Branch (båda repos):** `claude/plan-wexoe-features-5OxPZ`
**Airtable-baser:** `Wexoe NY` (`appokKSTaBdCa8YiW`) — SSOT/CMS. `Wexoe` (`appXoUcK68dQwASjF`) — sid-data (kvarstår).

Detta dokument är skrivet för att läsas och utföras av en LLM-agent. Varje fas är självstående med beroenden, konkreta artefakter, kodexempel och valideringar. Implementera faserna i ordning. Hoppa inte över valideringssteg.


---

## Innehåll

1. [Leveransvärden per fas](#1-leveransvärden-per-fas)
2. [Designprinciper](#2-designprinciper)
3. [Arkitektur](#3-arkitektur)
4. [Synergier mellan de tre planerna](#4-synergier-mellan-de-tre-planerna)
5. [Datamodell — vad som ändras var](#5-datamodell--vad-som-ändras-var)
6. [Fasplan](#6-fasplan)
   - [Fas 0 — Builder-grund: BuilderShell](#fas-0--builder-grund-buildershell)
   - [Fas 1 — Airtable-städning](#fas-1--airtable-städning)
   - [Fas 2 — Wexoe Core: SSOT-scheman + Helpers](#fas-2--wexoe-core-ssot-scheman--helpers)
   - [Fas 3 — Wexoe Core: REST CRUD för SSOT](#fas-3--wexoe-core-rest-crud-för-ssot)
   - [Fas 4 — Builder: `/globals/*` för SSOT-redigering](#fas-4--builder-globals-för-ssot-redigering)
   - [Fas 5 — `cms_unique_pages` + wexoe-pages-plugin (skelett)](#fas-5--cms_unique_pages--wexoe-pages-plugin-skelett)
   - [Fas 6 — Core render-helpers + sektion-rendering](#fas-6--core-render-helpers--sektion-rendering)
   - [Fas 7 — Core ContactForm-helper + AJAX-handler](#fas-7--core-contactform-helper--ajax-handler)
   - [Fas 8 — Per-plugin: contact-form i LP/PA/Audience](#fas-8--per-plugin-contact-form-i-lppaaudience)
   - [Fas 9 — Builder: ContactFormEditor i LP/PA/Audience](#fas-9--builder-contactformeditor-i-lppaaudience)
   - [Fas 10 — Globals-driven defaults i LP/PA](#fas-10--globals-driven-defaults-i-lppa)
   - [Fas 11 — Polish och deprecation](#fas-11--polish-och-deprecation)
7. [Risker och mitigations](#7-risker-och-mitigations)
8. [Designbeslut tagna](#8-designbeslut-tagna)
9. [Öppna frågor](#9-öppna-frågor)
10. [Bilagor](#10-bilagor)

---

## 1. Leveransvärden per fas

| Efter fas | Vad fungerar |
|---|---|
| Fas 0 | Audience-buildern använder delad shell. Ingen funktionell ändring för redaktör. |
| Fas 2 | Wexoe Core kan läsa alla 8 SSOT-tabeller. PHP-helpers fungerar. |
| Fas 4 | Redaktörer kan redigera alla SSOT-data i builder. Ingen Airtable-direktåtkomst behövs. |
| Fas 5 | Tomma Tier 2-sidor kan skapas och rendrar minimal HTML (bara H1). Infrastruktur klar. |
| Fas 6 | En "om-oss"-sida kan byggas helt i builder med 7+ sektion-typer (varav 3 SSOT-drivna). Sektionerna renderas data-drivet — fält ifyllt → sektion visas. |
| Fas 7 | Kontaktformulär fungerar både på Tier 2-sidor *och* som återanvändbart Core-anrop. Submissions hamnar i `User data`. |
| Fas 9 | LP/PA/Audience har integrerat kontaktformulär. Gamla `[wexoe_contact_form]`-shortcoden kan ersättas. |
| Fas 10 | Nya LP/PA förfylls med default-coworker. |
| Fas 11 | `Old plugins/wexoe-contact-form.php` deprecated. SEO-meta + audit-länkar på plats. |

Varje fas är ett potentiellt deploy-tillfälle. Faser kan inte hoppas över men efterföljande faser kan pausas utan att tidigare leveranser bryts.

---

## 2. Designprinciper

1. **DRY genom Core-helpers, inte plugin-registry.** Återanvändbara UI-byggstenar (kontaktform, team-grid, partners-marquee, testimonial-card, hero, text-image, faq, etc.) bor som klasser i `wexoe-core` och anropas via `\Wexoe\Core\Renderers\<X>::render($config)`. Vilken plugin som helst kan anropa dem. Inga cirkulära plugin-beroenden, ingen separat sektion-arkitektur.
2. **SSOT är data, inte UI.** core-tabeller lagrar fakta. Plugins och renderers *konsumerar* SSOT via Wexoe Core. Builder *redigerar* SSOT via REST.
3. **Tre page-renderer-strategier samexisterar.**
   - **(a) Dedikerade plugins (LP/PA/Audience)** renderar sin egen layout och anropar Core-render-helpers punktvis (t.ex. kontaktform sist).
   - **(b) `wexoe-pages` (Tier 2)** renderar `[wexoe_page slug=]`-shortcoden för one-off-sidor — läser `cms_unique_pages`-record och renderar en fast uppsättning sektioner *i fast ordning* baserat på "om data finns / show-flagga". Detta är samma mönster som LP-tabs och PA-Normals, inte composition.
   - **(c) Tier 1-plugins** (framtida t.ex. migrerad kontakt-sida) hand-rolled men drar SSOT.
4. **Data-driven över composition.** För Tier 2 finns ingen "lägg till sektion"-dialog, ingen drag-drop, ingen sektion-ordning som redaktör väljer. Istället: `cms_unique_pages` har många "kanske"-fält, och renderaren visar varje sektion om dess fält är ifyllda (eller om en `show_*`-flagga är på). Ordningen är fast, schemat är fast. Det är en *opinionated container*, inte en mini-Wix.
5. **Tre roller per SSOT-tabell.** Singleton (1 record per scope + `Is Default`-fallback), Collection (många records med scope-länkar), Taxonomy (referensdata).
6. **Country/Division-scope finns från dag 1.** Även om bara `SE` används initialt — schemat ska ej migreras senare.
7. **Defaults i PHP, override i Airtable.** Tomma SSOT-fält faller tillbaka på vettiga PHP-defaults. Implementationen kräver inte att Airtable är fullt populerat för att fungera.
8. **Soft-delete via `Active`.** Hård-delete inte i builder för SSOT. Records som "tas bort" markeras `Active = false` och döljs i renderingen.
9. **Inga tidsangivelser i denna plan.** Faser har beroenden, inte deadlines.

---

## 3. Arkitektur

```
┌─────────────────────────────────────────┐       ┌──────────────────────────────────────────────┐
│ Web-editor (Next.js, Vercel)            │       │ WordPress                                    │
│                                         │       │                                              │
│  /globals/*       — SSOT-editor         │       │   wexoe-core (bibliotek)                     │
│  /editor/unique   — CMS-editor (Tier 2) │       │      ├── Entity API + cache                  │
│  /editor/...      — LP/PA/Audience      │       │      ├── Helpers (Context/Singletons/...)    │
│                                         │       │      └── Renderers\* (delade UI-byggstenar)  │
└───────────────┬─────────────────────────┘       │              ▲                               │
                │                                 │              │ anropas av                    │
                │ REST writes + reads             │              │                               │
                ▼                                 │   wexoe-landing-page   ──┐                    │
┌─────────────────────────────────────────┐       │   wexoe-product-area   ──┼─► Renderers\*    │
│ Airtable                                │       │   wexoe-audience-hero  ──┘                    │
│                                         │◄──────┤   wexoe-pages    [wexoe_page slug=]  ─► Renderers\*  │
│  Wexoe NY (appokKSTaBdCa8YiW)           │ reads │   wexoe-contact-page (oförändrad, kandidat för Tier 1)│
│   • core_*  (SSOT)                      │       │                                              │
│   • cms_unique_pages (Tier 2, EN tabell)│       │   wxcf_submit AJAX-handler → User data        │
│                                         │       │                                              │
│  Wexoe (appXoUcK68dQwASjF)              │       │                                              │
│   • Landing Pages / Product Areas /     │       │                                              │
│     Audience Heroes / User data         │       │                                              │
└─────────────────────────────────────────┘       └──────────────────────────────────────────────┘
```

Två-bas-modellen är medveten: Wexoe-basen är "live" och innehåller sid-data + submissions. Wexoe NY innehåller plattformsdata (SSOT) + Tier 2-sid-data. Migrationer mellan baser görs *inte* i denna plan.

---

## 4. Synergier mellan de tre planerna

Tre planer slås ihop. Följande dubbelarbete elimineras:

| Dubbelarbete identifierat | Lösning i denna plan |
|---|---|
| Båda planer förespråkar BuilderShell-extraktion | Görs en gång i Fas 0. Alla efterföljande sektioner använder den. |
| Båda planer kräver nya Wexoe Core-hjälpare och boot-ändringar | Konsolideras i Fas 2 (boot rörs en gång). |
| Båda planer kräver Claude-prompt-uppdatering (`airtable-schema-lp.md` + `-pa.md`) | Görs en gång i Fas 9 med alla nya fält samtidigt. |
| Kontaktformulär-renderaren skulle byggas i en separat modul/plugin | Skapas i stället som `\Wexoe\Core\Renderers\ContactForm::render()` i wexoe-core (Fas 7). Anropas både av `wexoe-pages` (Tier 2) och `wexoe-landing-page`/`wexoe-product-area`/`wexoe-audience-hero`. |
| Båda planer rör cache-invalidering | En endpoint, byggs i Fas 3. |
| Båda planer planerar editor-sektioner i builder | Tier 2-builder följer LP-editorns mönster (collapse:able paneler med visibility-toggles). ContactFormEditor återanvänds som komponent mellan UniquePageBuilder och LP/PA/Audience. |
| Båda planer behöver Country/Division-helpers | `Wexoe\Core\Helpers\Context` (Fas 2) används av båda. |

**Konsekvens:** Tier 2 byggs i två steg — först som ren containerplugin med skelett-rendering (Fas 5), sedan med full sektion-uppsättning (Fas 6). Kontaktform-Core-helpern läggs in i Fas 7 och konsumeras både av wexoe-pages (genom att `cms_unique_pages.Show Contact Form` aktiverar den) och av LP/PA/Audience (Fas 8).

---

## 5. Datamodell — vad som ändras var

### 5.1 Airtable `Wexoe NY` (`appokKSTaBdCa8YiW`)

**Befintligt (verifierat):** 8 core-tabeller med korrekta fält men tomma/glesa records, samt 8 cms-placeholders med default-mall-fält som behöver omdesignas.

**Ändringar i denna plan:**

| Tabell | Åtgärd | Fas |
|---|---|---|
| `core_company` | Lägg `Hours Mon-Fri`, `Hours Saturday`, `Hours Sunday`, `Hours Lunch`, `Hours Override`. Populera SE-default-record. | 1 |
| `core_graphic_profile` | Populera default-record (1 record med `Is Default = true`). | 1 |
| `core_countries` | Populera SE-record (`Active=true`, `Domain=wexoe.se`). | 1 |
| `core_divisions` | Populera initiala (industri, automation, kassasystem). | 1 |
| `core_customer_types` | Populera initiala (industri, bygg, offentlig). | 1 |
| `core_coworkers` / `core_partners` / `core_testimonials` | Inga schema-ändringar. | 4 |
| `cms_landing_pages`, `cms_product_pages`, `cms_customer_type_pages`, `cms_partner_pages`, `cms_case_pages`, `cms_pillar_pages`, `cms_products`, `cms_articles` (placeholders) | **Radera alla 8.** Felaktig mental-modell. | 1 |
| `cms_unique_pages` (ny — *enda* CMS-tabellen för Tier 2) | Skapa med fält-uppsättning enligt 5.1.1 nedan. | 5 |

`cms_page_sections` skapas **inte**. Tier 2-sidor är ett record per sida med många fält. Inga sub-records.

#### 5.1.1 Fält i `cms_unique_pages`

**Metadata (alltid):**

| Fält | Typ | Not |
|---|---|---|
| `Slug` | singleLineText (primary, unique) | Reserverade slugs avvisas i builder. |
| `H1` | singleLineText | |
| `SEO Title` | singleLineText | |
| `SEO Description` | multilineText | |
| `OG Image URL` | url | |
| `Published` | checkbox | Default false. |
| `Country` | link → `core_countries` | |
| `Division` | link → `core_divisions` | Valfri. Används som default-scope för SSOT-sektioner. |

**Sektions-fält (data-driven; sektion visas om `Show <X>=true`):**

| Sektion | Fält |
|---|---|
| Hero | `Show Hero` (checkbox), `Hero Eyebrow`, `Hero H1 Override` (om tomt: använd top-level H1), `Hero Subtitle` (multilineText), `Hero Image` (attachment), `Hero CTA Text`, `Hero CTA URL`, `Hero Theme` (singleSelect: dark/light) |
| Text-image A | `Show Text Image A` (checkbox), `Text Image A H2`, `Text Image A Body` (multilineText), `Text Image A Image` (attachment), `Text Image A Reversed` (checkbox), `Text Image A Theme` (dark/light) |
| Text-image B | `Show Text Image B` … (samma mönster) |
| Text-only | `Show Text Only` (checkbox), `Text Only H2`, `Text Only Body` (multilineText), `Text Only Align` (left/center) |
| FAQ | `Show FAQ` (checkbox), `FAQ H2`, `FAQ Items` (multilineText, format `**Fråga** \| Svar`, en per rad) |
| Team grid (SSOT) | `Show Team Grid` (checkbox), `Team Grid H2`, `Team Grid Scope Division` (singleSelect, samma värden som core_divisions slugs), `Team Grid Scope Country` (singleSelect, country codes), `Team Grid Limit` (number) |
| Partners marquee (SSOT) | `Show Partners Marquee` (checkbox), `Partners Marquee H2`, `Partners Marquee Scope Division` (singleSelect), `Partners Marquee Scope Country` (singleSelect) |
| Testimonial card (SSOT) | `Show Testimonial Card` (checkbox), `Testimonial Scope Customer Type` (singleSelect, samma värden som core_customer_types slugs), `Testimonial Scope Division` (singleSelect), `Testimonial Scope Country` (singleSelect) |
| CTA banner | `Show CTA Banner` (checkbox), `CTA Banner H2`, `CTA Banner Body` (multilineText), `CTA Banner CTA Text`, `CTA Banner CTA URL`, `CTA Banner Theme` (dark/light) |
| Contact form | `Show Contact Form` (checkbox) + samma 15 `Contact Form *`-fält som läggs i LP/PA/Audience i 5.2 (delat naming för att Core-helpern kan ta samma config) |

**Konsekvens:** Tabellen blir bred (~60 kolumner) men alla typade Airtable-fält. Inga JSON-fält. Editorn renderas i builder som vertikal lista av collapse:able paneler — varje panel motsvarar en sektion och visar dess fält när öppen. Visibility-toggle vid varje panel-header.

**Skalning:** Om en framtida sektion behöver dynamisk antals-multiplicity (t.ex. "10 FAQ-items") använd `multilineText` med radseparering (samma som `FAQ Items` ovan). Om en sektion behöver verkligt flerval (t.ex. handplockade coworkers) lägg en multi-link senare. *Inga* sub-records tas in i MVP.

### 5.2 Airtable `Wexoe` (`appXoUcK68dQwASjF`)

**Befintligt (verifierat):**
- Landing Pages (`tbl8KDqGq0Ray1uqS`), Product Areas (`tblgatNFYFMwF4EcQ`), Audience Heroes (`tblvNf1CqAYEFvTpu`), User data (`tblxrwMhSysupcDwe`).
- `User data.Submission Type` har redan värdet `contact`. ✓

**Ändringar i denna plan:**

Lägg till följande fält i `Landing Pages`, `Product Areas` och `Audience Heroes` (samma uppsättning, en gång per tabell):

| Airtable-fältnamn | Typ | Default | Not |
|---|---|---|---|
| `Show Contact Form` | checkbox | false | |
| `Contact Form Eyebrow` | singleLineText | — | |
| `Contact Form Title` | singleLineText | — | Tom → PHP-default "Prata med någon som kan automation". |
| `Contact Form Subtitle` | multilineText | — | |
| `Contact Form Layout` | singleSelect (`split`, `centered`) | `split` | |
| `Contact Form Theme` | singleSelect (`dark`, `light`) | `dark` | |
| `Contact Form Show Company` | checkbox | true | |
| `Contact Form Show Phone` | checkbox | true | |
| `Contact Form Show Dropdown` | checkbox | true | |
| `Contact Form Dropdown Label` | singleLineText | — | |
| `Contact Form Options` | multilineText | — | En per rad. Tom → PHP-default. |
| `Contact Form CTA Text` | singleLineText | — | Default "Skicka". |
| `Contact Form Message Label` | singleLineText | — | |
| `Contact Form Trust Signals` | multilineText | — | Format `**Bold** \| Resten`, en per rad, max 3. |
| `Contact Form Show Contact Person` | checkbox | true | |

Skapas i Fas 8 (Airtable-fält först, sedan schema-utökning, sedan plugin-integration).

### 5.3 Wexoe Core-scheman (PHP)

| Schemafil | Åtgärd | Fas |
|---|---|---|
| `wexoe-core/entities/core_company.php` (ny) | Skapa | 2 |
| `wexoe-core/entities/core_graphic_profile.php` (ny) | Skapa | 2 |
| `wexoe-core/entities/core_countries.php` (ny) | Skapa | 2 |
| `wexoe-core/entities/core_divisions.php` (ny) | Skapa | 2 |
| `wexoe-core/entities/core_customer_types.php` (ny) | Skapa | 2 |
| `wexoe-core/entities/core_coworkers.php` (ny) | Skapa | 2 |
| `wexoe-core/entities/core_partners.php` (ny) | Skapa | 2 |
| `wexoe-core/entities/core_testimonials.php` (ny) | Skapa | 2 |
| `wexoe-core/entities/cms_unique_pages.php` (ny) | Skapa | 5 |
| `wexoe-core/entities/landing_pages.php` (utökas) | Lägg `contact_form_*` domain-keys | 8 |
| `wexoe-core/entities/product_areas.php` (utökas) | Samma | 8 |
| `wexoe-core/entities/audience_heroes.php` (utökas) | Samma | 8 |

---

## 6. Fasplan

### Fas 0 — Builder-grund: BuilderShell

**Beroenden:** inga.

**Mål:** Extrahera duplicerad plumbing från LP/PA/Audience till en delad `BuilderShell`. Detta är förberedande arbete som UniquePageBuilder och `/globals/*`-vyer kommer återanvända.

**Konkreta artefakter:**

1. `wexoebuilder/components/BuilderShell.tsx` (ny) — props: `{ toolbar, editorPanel, previewPanel, scrollSync }`. Innehåller layout (split 65/35), publish-knapp, error/saved-banners. Tar emot toolbar och paneler som children.
2. `wexoebuilder/components/audience/AudienceBuilder.tsx` (refaktor) — använd `BuilderShell`. Detta är proof-of-concept (minsta page-typen).

**Vad du *inte* gör i denna fas:**
- LP/PA flyttas inte till `BuilderShell` än. De refaktoreras opportunistiskt senare när vi ändå rör dem.
- Ingen `sections/`-mapp eller `SECTION_REGISTRY` skapas. Det behövs inte i den data-drivna modellen.

**Validering:**
- [ ] `pnpm dev` startar utan typfel.
- [ ] Audience-editorn fungerar likadant som före refaktoringen (skapa ny audience-sida → spara → läs in igen → samma fält).
- [ ] LP/PA-editorerna fungerar oförändrat.

---

### Fas 1 — Airtable-städning

**Beroenden:** inga.

**Mål:** Få Wexoe NY-basen till "ren" status redo för Wexoe Core att läsa.

**Steg (utförs via Airtable MCP eller manuellt):**

1. **Radera de 8 placeholder-cms-tabellerna** (`cms_landing_pages`, `cms_product_pages`, `cms_customer_type_pages`, `cms_partner_pages`, `cms_case_pages`, `cms_pillar_pages`, `cms_products`, `cms_articles`). De har bara default-mall-fält och är fel mental-modell.
2. **Rensa default-mall-fält** (`Notes`, `Assignee`, `Status`, `Attachments`, `Attachment Summary`) från alla `core_*`-tabeller där de finns. Behåll de fält som faktiskt används (se [5.1](#51-airtable-wexoe-ny-appokkstabdca8yiw)).
3. **Lägg `Hours *`-fält i `core_company`** (öppen fråga 9.1 i ursprunglig SSOT-plan besvarad: A — lägg dem i SSOT).
4. **Populera initial-data:**
   - `core_countries`: 1 record `Name=Sweden, Code=SE, Domain=wexoe.se, Active=true`.
   - `core_divisions`: 3 records (`Industri`, `Automation`, `Kassasystem`) länkade till SE-country.
   - `core_customer_types`: 3 records (`Industri-kund`, `Bygg`, `Offentlig sektor`).
   - `core_company`: 1 record `Slug=wexoe-se, Is Default=true, Country=[SE]`, övriga fält tomma.
   - `core_graphic_profile`: 1 record `Slug=default, Is Default=true`, övriga fält tomma.
   - `core_coworkers`/`core_partners`/`core_testimonials`: 0 records — redaktör fyller via builder.
5. **Säkerställ exakt EN `Is Default=true`-record** på `core_company` och `core_graphic_profile`. Hård invariant.
6. **Skriv eller verifiera tabell-beskrivningar** på alla core-tabeller.

**Validering:**
- [ ] `list_tables_for_base baseId=appokKSTaBdCa8YiW` returnerar exakt 8 tabeller (alla `core_*`).
- [ ] `core_company.Slug=wexoe-se` finns och har `Is Default=true`.
- [ ] `core_countries.Code=SE` finns med `Domain=wexoe.se`.
- [ ] Inga `cms_*`-tabeller finns kvar (`cms_unique_pages` skapas i Fas 5).

---

### Fas 2 — Wexoe Core: SSOT-scheman + Helpers

**Beroenden:** Fas 1.

**Mål:** Wexoe Core kan läsa alla 8 SSOT-tabeller via `Core::entity('core_company')->all()` osv. Country/Division-context-detektering fungerar i WP.

**Konkreta artefakter:**

1. **8 entity-schemafiler** i `wexoeplugins/wexoe-core/entities/`. Mall (`core_company.php`):
   ```php
   <?php
   if (!defined('ABSPATH')) exit;
   return [
       'table_id' => 'tblwq9y74ertsNyYG',
       'primary_key' => 'slug',
       'cache_ttl' => 3600,
       'required' => ['slug'],
       'fields' => [
           'slug' => 'Slug',
           'is_default' => ['source' => 'Is Default', 'type' => 'bool'],
           'country_ids' => ['source' => 'Country', 'type' => 'link', 'entity' => 'core_countries'],
           'company_name' => 'Company Name',
           'tagline' => 'Tagline',
           'org_number' => 'Org Number',
           'vat_number' => 'VAT Number',
           'email' => 'Email',
           'phone' => 'Phone',
           'phone_emergency' => 'Phone Emergency',
           'address_line_1' => 'Address Line 1',
           'address_postal_code' => 'Address Postal Code',
           'address_city' => 'Address City',
           'linkedin_url' => 'LinkedIn URL',
           'facebook_url' => 'Facebook URL',
           'instagram_url' => 'Instagram URL',
           'youtube_url' => 'YouTube URL',
           'hours_mon_fri' => 'Hours Mon-Fri',
           'hours_saturday' => 'Hours Saturday',
           'hours_sunday' => 'Hours Sunday',
           'hours_lunch' => 'Hours Lunch',
           'hours_override' => 'Hours Override',
           'internal_notes' => 'Internal Notes',
       ],
   ];
   ```
   Bygg motsvarande för: `core_graphic_profile.php`, `core_countries.php`, `core_divisions.php`, `core_customer_types.php`, `core_coworkers.php`, `core_partners.php`, `core_testimonials.php`. Tabell-ID:n finns i [5.1](#51-airtable-wexoe-ny-appokkstabdca8yiw).

2. **Helpers** i `wexoe-core/src/Helpers/`:
   - `Context.php` — `current_country_record()`, `current_country_code()`, `current_division_slug()`. Resolve-kedja: Domain → URL Prefix → `Is Default` på company → null+log. Per-request-cache.
   - `Singletons.php` — `company_for_country($code)`, `graphic_profile_for_division($slug)`. Matchar mot Country/Division-länk, fall tillbaka till `Is Default=true`.
   - `Collections.php` — `coworkers_for_scope($scope)`, `partners_for_scope($scope)`, `testimonials_for_scope($scope)`. Filtrerar på `active=true`, scope-länkar, sorterar på `order`, respekterar `limit`. Tom scope-länk i record = "globalt synligt".

   (Implementation enligt fullständig kod-skiss i rev 1 av denna plan — bevarad mentalt; bygg ut.)

3. **Boot** i `wexoe-core/src/Plugin.php` — säkerställ PSR-4 autoload för helpers.
4. **Mapper-utvidgning:** entity-mapper exponerar `_record_id` (behövs i Helpers).

**Validering:**
- [ ] WP-test:
  ```php
  $company = \Wexoe\Core\Core::entity('core_company')->all();
  var_dump($company); // 1 record med slug=wexoe-se
  $ctx = \Wexoe\Core\Helpers\Context::current_country_code(); // 'SE'
  $cw = \Wexoe\Core\Helpers\Collections::coworkers_for_scope(['country' => 'SE']); // []
  ```
- [ ] Transienten `wexoe_core_core_company` finns efter första anrop.
- [ ] Cache-TTL är 3600s för core-tabeller.

---

### Fas 3 — Wexoe Core: REST CRUD för SSOT

**Beroenden:** Fas 2.

**Mål:** Buildern kan skapa, läsa, uppdatera och radera SSOT-records via Wexoe Core REST. Cache rensas automatiskt på write.

**Artefakter (alla i `wexoeplugins/wexoe-core/`):**

1. **WriteRegistry-utökning:** write-entities-filer för alla 8 core-tabeller + `cms_unique_pages` (Fas 5). Samma mall som befintliga `write-entities/user_submissions.php`.
2. **REST-route** `wp-json/wexoe-core/v1/entity/{entity}` med GET (list/single via `?slug=x`), POST (create), PATCH (update via `?record_id=rec...`), DELETE. Whitelistas via `CORE_EDITABLE_ENTITIES`-array.
3. **REST-route** `wp-json/wexoe-core/v1/invalidate` (POST) — body `{ entities: string[] }`. Säkras med shared secret.
4. **Singleton-invariant-validering:** Vid PATCH/POST på `core_company` eller `core_graphic_profile`, om `is_default=true` sätts → säkerställ att inget annat record har `is_default=true`. Annars 409.

**Validering:**
- [ ] `curl ... entity/core_company` returnerar JSON-array.
- [ ] PATCH uppdaterar och svar `200`. Direkt efter PATCH: `Core::entity('core_company')->all()` returnerar nytt värde (PATCH-routen anropar invalidate internt).
- [ ] Andra `core_company` med `is_default=true` → `409 Conflict`.
- [ ] PATCH på `landing_pages` via denna route → `403` (whitelist hindrar).

---

### Fas 4 — Builder: `/globals/*` för SSOT-redigering

**Beroenden:** Fas 2, Fas 3.

**Mål:** Redaktörer kan redigera alla 8 SSOT-tabeller direkt i builder. Ingen Airtable-direktåtkomst.

**Artefakter (`wexoebuilder/`):**

1. **`lib/core/`** ny mapp:
   - `types.ts` — TS-interfaces per entity (`CoreCompany`, `CoreCountry`, …).
   - `mapper.ts` — bidirektional Airtable record ↔ TS-objekt.
   - `loader.ts` — server-side fetch per entity (samma mönster som `lib/page-mapper.ts`).
   - `registry.ts` — whitelist + tabell-ID:n:
     ```ts
     export const CORE_ENTITIES = {
       'core_company':         { tableId: 'tblwq9y74ertsNyYG', role: 'singleton', label: 'Företag' },
       'core_graphic_profile': { tableId: 'tbl4c4HjiKVCcJI5v', role: 'singleton', label: 'Grafisk profil' },
       'core_countries':       { tableId: 'tblCZ082jWGUBrUAK', role: 'taxonomy',  label: 'Länder' },
       'core_divisions':       { tableId: 'tblyxs2zsoRBozxQS', role: 'taxonomy',  label: 'Divisioner' },
       'core_customer_types':  { tableId: 'tblLsYRMZz6JA6GBK', role: 'taxonomy',  label: 'Kundtyper' },
       'core_coworkers':       { tableId: 'tblYwMQlW9HFd41pg', role: 'collection',label: 'Medarbetare' },
       'core_partners':        { tableId: 'tblZ5YIYFelxA0nBm', role: 'collection',label: 'Partners' },
       'core_testimonials':    { tableId: 'tbl1pe0bWz5zdkqJF', role: 'collection',label: 'Citat' },
     } as const;
     ```
   - `forms.ts` — field-config per entity (vilka fält visas, inputtyp).
   - `reserved-slugs.ts` — speglar PHP-konstanten (Fas 5).

2. **`app/api/core/[entity]/route.ts`** — generisk route. Validerar mot `CORE_ENTITIES`. GET/POST/PATCH/DELETE. Anropar Airtable direkt + Wexoe Core invalidate efter mutation.

3. **Komponenter:**
   - `components/core/CoreEntityShell.tsx` — generiskt skal: rubrik, list-vy, form-vy, save-banner.
   - `components/core/CoreEntityForm.tsx` — generisk form från `forms.ts`.
   - `components/core/SsotImageField.tsx` — Airtable attachment-uploader.

4. **Routes:**
   ```
   app/globals/page.tsx                  -- entitets-grid
   app/globals/company/page.tsx
   app/globals/graphic-profile/page.tsx
   app/globals/countries/page.tsx
   app/globals/divisions/page.tsx
   app/globals/customer-types/page.tsx
   app/globals/coworkers/page.tsx
   app/globals/partners/page.tsx
   app/globals/testimonials/page.tsx
   ```

5. **Sidlistan i `/`:** lägg "Globaler"-länk i `app/page.tsx`. Auth: återanvänd `lib/auth.ts`.

**Validering:**
- [ ] `/globals` visar 8 entitet-kort.
- [ ] Företag → ändra Phone → spara → ladda om → fältet visar nya värdet.
- [ ] `curl wp/wp-json/wexoe-core/v1/entity/core_company` returnerar nya numret efter invalidate.
- [ ] Ny coworker via `/globals/coworkers` → ny record i Airtable.
- [ ] Försök markera andra `core_company` som default → felmeddelande i UI.

---

### Fas 5 — `cms_unique_pages` + wexoe-pages-plugin (skelett)

**Beroenden:** Fas 0, Fas 2.

**Mål:** En tom Tier 2-sida kan skapas i Airtable och rendreras i WordPress med bara H1 + SEO-meta. Inga sektioner än — det är skelettet.

**Artefakter:**

**Airtable (`Wexoe NY`):**
1. Skapa `cms_unique_pages`-tabell med **alla** fält enligt [5.1.1](#511-fält-i-cms_unique_pages). Hela schemat på en gång — bättre att skapa hela strukturen även om bara `Slug`/`H1`/`SEO *` används initialt.
2. Lägg tabell-beskrivning.
3. Skapa 1 test-record: `Slug=test-page, H1=Testsida, Published=true, Country=[SE]`. Alla `Show *`-flaggor `false`.

**Wexoe Core:**
1. `wexoe-core/entities/cms_unique_pages.php` — entity-schema med alla domain-keys:
   ```php
   return [
       'table_id' => 'tbl...', // sätt efter tabellens skapande i Airtable
       'primary_key' => 'slug',
       'cache_ttl' => 86400,
       'required' => ['slug'],
       'fields' => [
           // Metadata
           'slug' => 'Slug',
           'h1' => 'H1',
           'seo_title' => 'SEO Title',
           'seo_description' => 'SEO Description',
           'og_image_url' => 'OG Image URL',
           'published' => ['source' => 'Published', 'type' => 'bool'],
           'country_ids' => ['source' => 'Country', 'type' => 'link', 'entity' => 'core_countries'],
           'division_ids' => ['source' => 'Division', 'type' => 'link', 'entity' => 'core_divisions'],
           // Hero
           'show_hero' => ['source' => 'Show Hero', 'type' => 'bool'],
           'hero_eyebrow' => 'Hero Eyebrow',
           'hero_h1_override' => 'Hero H1 Override',
           'hero_subtitle' => 'Hero Subtitle',
           'hero_image_url' => ['source' => 'Hero Image', 'type' => 'attachment_url'],
           'hero_cta_text' => 'Hero CTA Text',
           'hero_cta_url' => 'Hero CTA URL',
           'hero_theme' => 'Hero Theme',
           // ... (samma för text-image A, text-image B, text-only, faq, team-grid, partners-marquee, testimonial-card, cta-banner, contact-form)
       ],
   ];
   ```
2. `RESERVED_SLUGS`-konstant i `wexoe-core/src/Constants.php`:
   ```php
   const RESERVED_SLUGS = ['kontakt', 'nedladdningar', 'om-oss-statisk'];
   ```

**Ny plugin `wexoe-pages` (`wexoeplugins/New plugins/wexoe-pages/`):**

1. `wexoe-pages.php`:
   - Header: standard WP-plugin med beroende på `wexoe-core`.
   - Registrerar shortcode `[wexoe_page slug="..."]`.
   - Funktion `wexoe_pages_render($slug)`:
     - Hämtar `cms_unique_pages` via `\Wexoe\Core\Core::entity('cms_unique_pages')->find_by('slug', $slug)`.
     - Om inte hittat → returnera tom sträng (eller debug-kommentar).
     - Om `published=false` → returnera tom sträng.
     - Output buffer: börja, skriv `<article class="wxp-page">`, `<h1>` om h1 finns, plats för sektioner (tom i denna fas), `</article>`, return.
   - Lägg SEO-meta-hooks (`wp_head`-filter som sätter `<title>` och `<meta description>` om sidan matchar nuvarande URL — implementeras enkelt initialt, polish i Fas 11).
2. CSS-prefix `wxp-*` för pluginens egna container-styles.

**Builder (`wexoebuilder/`):**
1. `app/editor/unique/page.tsx` (create) + `app/editor/unique/[recordId]/page.tsx` (edit).
2. `components/UniquePageBuilder.tsx` — använder `BuilderShell`. Toolbar: slug-input, Published-toggle, Country-väljare. EditorPanel: bara metadata-paneler (Slug, H1, SEO). PreviewPanel: visar H1 + meta-info. Inga sektion-paneler i denna fas — de läggs i Fas 6.
3. `app/api/unique-page/route.ts` — POST (create), PATCH (update), DELETE. Cache-invalidate efter mutation.
4. Lägg "Unik sida" i `Ny sida`-dialogen i `app/page.tsx`.

**Validering:**
- [ ] WP: `echo do_shortcode('[wexoe_page slug="test-page"]')` returnerar `<article class="wxp-page"><h1>Testsida</h1></article>`.
- [ ] Skapa sida via builder → ny record i `cms_unique_pages`.
- [ ] Försök skapa sida med slug `kontakt` → felmeddelande i UI.
- [ ] WP: `[wexoe_page slug="finns-inte"]` returnerar tom sträng (eller `<!-- wexoe-pages: not found -->` i debug-läge).
- [ ] `Published=false` → shortcode returnerar tomt.

---

### Fas 6 — Core render-helpers + sektion-rendering

**Beroenden:** Fas 5.

**Mål:** wexoe-pages renderar full uppsättning sektioner (hero, text-image A/B, text-only, faq, team-grid, partners-marquee, testimonial-card, cta-banner) baserat på fält-data. Builder UniquePageBuilder visar alla sektioner som collapse:able paneler med visibility-toggles.

**Sektion-typer i denna fas:**

| Typ | Show-flagga | SSOT? |
|---|---|---|
| Hero | `Show Hero` | Nej |
| Text-image A | `Show Text Image A` | Nej |
| Text-image B | `Show Text Image B` | Nej |
| Text-only | `Show Text Only` | Nej |
| FAQ | `Show FAQ` | Nej |
| Team grid | `Show Team Grid` | Ja → `core_coworkers` |
| Partners marquee | `Show Partners Marquee` | Ja → `core_partners` |
| Testimonial card | `Show Testimonial Card` | Ja → `core_testimonials` |
| CTA banner | `Show CTA Banner` | Nej |

`Contact form` läggs i Fas 7.

**Wexoe Core — render-helpers (`wexoe-core/src/Renderers/`):**

En klass per UI-byggsten. Mall (`TeamGrid.php`):

```php
<?php
namespace Wexoe\Core\Renderers;
if (!defined('ABSPATH')) exit;

class TeamGrid {
    /**
     * @param array{h2?:string, scope?:array, theme?:string} $config
     */
    public static function render(array $config): string {
        $scope = $config['scope'] ?? [];
        $coworkers = \Wexoe\Core\Helpers\Collections::coworkers_for_scope($scope);
        if (empty($coworkers)) return '';
        ob_start();
        ?>
        <section class="wxr-team-grid">
            <?php if (!empty($config['h2'])): ?><h2><?= esc_html($config['h2']) ?></h2><?php endif; ?>
            <div class="wxr-team-grid__list">
                <?php foreach ($coworkers as $c): ?>
                    <div class="wxr-team-grid__item">
                        <?php if (!empty($c['image'])): ?><img src="<?= esc_url($c['image']) ?>" alt=""/><?php endif; ?>
                        <h3><?= esc_html($c['full_name'] ?? '') ?></h3>
                        <p><?= esc_html($c['title'] ?? '') ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <style><?php /* inline scoped CSS, prefix wxr-team-grid__ */ ?></style>
        <?php
        return ob_get_clean();
    }
}
```

Bygg motsvarande för: `Hero`, `TextImage` (parametrar inkluderar `reversed`), `TextOnly`, `Faq` (parsar `**Q** | A`-rader), `PartnersMarquee`, `TestimonialCard`, `CtaBanner`. CSS-prefix `wxr-<type>__` (Wexoe Renderer). Alla returnerar `string` — tom om data otillräcklig.

**Public facade utökning** (valfri sockerlager) i `wexoe-core/src/Core.php`:

```php
public static function renderer(string $type): string {
    $map = [
        'hero' => Renderers\Hero::class,
        'text-image' => Renderers\TextImage::class,
        'text-only' => Renderers\TextOnly::class,
        'faq' => Renderers\Faq::class,
        'team-grid' => Renderers\TeamGrid::class,
        'partners-marquee' => Renderers\PartnersMarquee::class,
        'testimonial-card' => Renderers\TestimonialCard::class,
        'cta-banner' => Renderers\CtaBanner::class,
    ];
    return $map[$type] ?? '';
}
```

Användning: `echo \Wexoe\Core\Renderers\TeamGrid::render($cfg)` (direkt) eller `\Wexoe\Core\Core::renderer('team-grid')::render($cfg)` (via facade).

**wexoe-pages — utöka renderaren:**

I `wexoe-pages.php`:

```php
function wexoe_pages_render($slug) {
    $page = \Wexoe\Core\Core::entity('cms_unique_pages')->find_by('slug', $slug);
    if (!$page || empty($page['published'])) return '';

    ob_start();
    echo '<article class="wxp-page">';
    if (!empty($page['h1'])) echo '<h1>' . esc_html($page['h1']) . '</h1>';

    // Hero
    if (!empty($page['show_hero'])) {
        echo \Wexoe\Core\Renderers\Hero::render([
            'eyebrow' => $page['hero_eyebrow'] ?? '',
            'title' => $page['hero_h1_override'] ?: $page['h1'],
            'subtitle' => $page['hero_subtitle'] ?? '',
            'image_url' => $page['hero_image_url'] ?? '',
            'cta_text' => $page['hero_cta_text'] ?? '',
            'cta_url' => $page['hero_cta_url'] ?? '',
            'theme' => $page['hero_theme'] ?? 'dark',
        ]);
    }
    // Text-image A
    if (!empty($page['show_text_image_a'])) {
        echo \Wexoe\Core\Renderers\TextImage::render([
            'h2' => $page['text_image_a_h2'] ?? '',
            'body' => $page['text_image_a_body'] ?? '',
            'image_url' => $page['text_image_a_image_url'] ?? '',
            'reversed' => !empty($page['text_image_a_reversed']),
            'theme' => $page['text_image_a_theme'] ?? 'light',
        ]);
    }
    // ... text-image B, text-only, faq, team-grid, partners-marquee, testimonial-card, cta-banner
    // Sektion-ordningen här är *fast* — den är planens designval (se Designbeslut).

    echo '</article>';
    return ob_get_clean();
}
```

**Builder — UniquePageBuilder utökning:**

EditorPanel-strukturen följer LP-editorns mönster. Pseudo-struktur:

```
<UniquePageBuilder>
  <BuilderShell toolbar={...}>
    <EditorPanel>
      <MetadataPanel />              (slug, H1, SEO)
      <Section title="Hero" visible={state.showHero} onToggle={...}>
        <HeroEditor state={state.hero} setField={...} />
      </Section>
      <Section title="Text + bild A" visible={state.showTextImageA} ...>
        <TextImageEditor state={state.textImageA} ... />
      </Section>
      <Section title="Text + bild B" ... />
      <Section title="Text" ... />
      <Section title="FAQ" ... />
      <Section title="Team" ... ssotDriven={true} />
      <Section title="Partners" ... ssotDriven />
      <Section title="Citat" ... ssotDriven />
      <Section title="CTA-banner" ... />
    </EditorPanel>
    <PreviewPanel>
      <PageH1 />
      {state.showHero && <HeroPreview ... />}
      {state.showTextImageA && <TextImagePreview ... />}
      {/* ... etc, exakt samma ordning som PHP-renderaren */}
    </PreviewPanel>
  </BuilderShell>
</UniquePageBuilder>
```

**Editor-komponenter:**
- `components/unique-page/editors/HeroEditor.tsx`, `TextImageEditor.tsx`, `TextOnlyEditor.tsx`, `FaqEditor.tsx`, `TeamGridEditor.tsx`, `PartnersMarqueeEditor.tsx`, `TestimonialCardEditor.tsx`, `CtaBannerEditor.tsx`.
- SSOT-drivna editors har `<ScopeFieldset>`-komponent för att välja country/division/customer-type/limit.

**Preview-komponenter:**
- `components/unique-page/preview/<X>Preview.tsx` — visuell skiss per sektion. SSOT-drivna previews fetchar från `/api/core/core_coworkers?country=SE` etc.

**State (`lib/unique-page-types.ts`):**
```ts
export interface UniquePageState {
  recordId?: string;
  slug: string;
  h1: string;
  seoTitle: string;
  seoDescription: string;
  ogImageUrl: string;
  published: boolean;
  countryId?: string;
  divisionId?: string;

  // Sektioner (alla har visibility-flagga)
  showHero: boolean;
  hero: HeroState;
  showTextImageA: boolean;
  textImageA: TextImageState;
  showTextImageB: boolean;
  textImageB: TextImageState;
  showTextOnly: boolean;
  textOnly: TextOnlyState;
  showFaq: boolean;
  faq: FaqState;
  showTeamGrid: boolean;
  teamGrid: { h2: string; scope: ScopeFilter };
  showPartnersMarquee: boolean;
  partnersMarquee: { h2: string; scope: ScopeFilter };
  showTestimonialCard: boolean;
  testimonialCard: { scope: ScopeFilter };
  showCtaBanner: boolean;
  ctaBanner: CtaBannerState;
  // contact-form läggs i Fas 7
}
```

**Mapper (`lib/unique-page-mapper.ts`):**
- `uniquePageStateFromRecord(record)` — plana fält → nested state.
- `uniquePageStateToFields(state)` — omvänt. Skickas direkt till Airtable (ingen Claude-transform — schemat är typat).

**API-route:** `app/api/unique-page/route.ts` — POST/PATCH/DELETE. Cache-invalidate.

**Validering (per sektion):**
- [ ] Toggla `Show Hero=true` + fyll i hero-fält → publish → WP visar hero.
- [ ] Toggla av → WP visar inte hero.
- [ ] Team Grid: scope `{ country: 'SE' }` med 0 coworkers → ingen output (renderaren returnerar tom när data saknas).
- [ ] Lägg en coworker via `/globals/coworkers` → cache-busta → team-grid syns.
- [ ] CSS-prefix `wxr-*` krockar inte med `wxp-*` eller övriga.
- [ ] Builder: alla 8 sektion-paneler i UniquePageBuilder kan öppnas/stängas, fält redigeras, preview uppdateras live.
- [ ] Mapper round-trip: skapa sida, läs in igen → identisk state.

---

### Fas 7 — Core ContactForm-helper + AJAX-handler

**Beroenden:** Fas 6.

**Mål:** `\Wexoe\Core\Renderers\ContactForm::render($cfg)` fungerar. AJAX-handler `wxcf_submit` skriver till `user_submissions`. wexoe-pages renderar kontaktform när `Show Contact Form=true` på unique-page.

**Artefakter:**

**Wexoe Core — ny modul `wexoe-core/src/Renderers/ContactForm.php`:**

```php
<?php
namespace Wexoe\Core\Renderers;
if (!defined('ABSPATH')) exit;

class ContactForm {
    public static function render(array $raw): string {
        $cfg = self::normalize($raw);
        $uniqid = 'wxcf-' . wp_unique_id();
        $nonce = wp_create_nonce('wxcf_submit');
        ob_start();
        // HTML/CSS/JS-skelett kopierat från Old plugins/wexoe-contact-form.php
        // men CSS-prefix bytt till wxcf-*, scopad till #{$uniqid}
        // Honeypot-fält <input name="_hp" style="display:none" tabindex="-1" autocomplete="off">
        // JS POST:ar till admin-ajax.php?action=wxcf_submit
        return ob_get_clean();
    }

    private static function normalize(array $raw): array {
        return [
            'eyebrow'        => $raw['eyebrow'] ?? '',
            'title'          => $raw['title'] ?: 'Prata med någon som kan automation',
            'subtitle'       => $raw['subtitle'] ?? '',
            'layout'         => in_array($raw['layout'] ?? 'split', ['split','centered']) ? $raw['layout'] : 'split',
            'theme'          => in_array($raw['theme'] ?? 'dark', ['dark','light']) ? $raw['theme'] : 'dark',
            'show_company'   => $raw['show_company'] ?? true,
            'show_phone'     => $raw['show_phone'] ?? true,
            'show_dropdown'  => $raw['show_dropdown'] ?? true,
            'dropdown_label' => $raw['dropdown_label'] ?: 'Vad kan vi hjälpa dig med?',
            'options'        => self::parse_lines($raw['options'] ?? null, [
                'Generell fråga','Diskutera ett projekt','Lägga en order',
                'Minska stillestånd','Förbättra OEE','Info om produkt'
            ]),
            'cta_text'       => $raw['cta_text'] ?: 'Skicka',
            'message_label'  => $raw['message_label'] ?: 'Berätta mer (valfritt)',
            'trust_signals'  => self::parse_lines($raw['trust_signals'] ?? null, []),
            'colors'         => $raw['colors'] ?? [],
            'source_plugin'  => $raw['source_plugin'] ?? 'wexoe-pages',
            'page_slug'      => $raw['page_slug'] ?? '',
            'contact_person' => $raw['contact_person'] ?? null,
        ];
    }

    private static function parse_lines($v, array $default): array {
        if (is_array($v)) return $v;
        if (is_string($v) && $v !== '') return array_values(array_filter(array_map('trim', explode("\n", $v))));
        return $default;
    }
}
```

**Wexoe Core — AJAX-handler `wexoe-core/src/ContactForm/Handler.php`:**

```php
<?php
namespace Wexoe\Core\ContactForm;
if (!defined('ABSPATH')) exit;

class Handler {
    public static function register(): void {
        add_action('wp_ajax_wxcf_submit',        [self::class, 'handle']);
        add_action('wp_ajax_nopriv_wxcf_submit', [self::class, 'handle']);
    }
    public static function handle(): void {
        if (!wp_verify_nonce($_POST['_wxcf_nonce'] ?? '', 'wxcf_submit')) {
            wp_send_json(['success' => false, 'error' => 'invalid_nonce'], 403);
        }
        if (!empty($_POST['_hp'])) wp_send_json(['success' => true]); // honeypot: tyst avvisning

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'wxcf_rl_' . md5($ip);
        $count = (int) get_transient($key);
        if ($count >= 10) wp_send_json(['success' => false, 'error' => 'rate_limited'], 429);
        set_transient($key, $count + 1, HOUR_IN_SECONDS);

        $payload = [
            'submission_type'    => 'contact',
            'email'              => sanitize_email($_POST['email'] ?? ''),
            'name'               => sanitize_text_field($_POST['name'] ?? ''),
            'company'            => sanitize_text_field($_POST['company'] ?? ''),
            'phone'              => sanitize_text_field($_POST['phone'] ?? ''),
            'message'            => sanitize_textarea_field($_POST['message'] ?? ''),
            'newsletter_consent' => !empty($_POST['newsletter_consent']),
            'submitted_at'       => current_time('c'),
            'page_slug'          => sanitize_text_field($_POST['page_slug'] ?? ''),
            'page_url'           => esc_url_raw($_POST['page_url'] ?? ''),
            'source_plugin'      => sanitize_text_field($_POST['source_plugin'] ?? ''),
            'extra'              => [
                'behov'      => sanitize_text_field($_POST['behov'] ?? ''),
                'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            ],
        ];
        $res = \Wexoe\Core\Core::submission('user_submissions')->create_mapped($payload);
        if (!empty($res['success'])) wp_send_json(['success' => true]);
        wp_send_json(['success' => false, 'error' => $res['error'] ?? 'unknown'], 500);
    }
}
```

Boot i `wexoe-core/src/Plugin.php`:
```php
add_action('init', [\Wexoe\Core\ContactForm\Handler::class, 'register']);
```

**wexoe-pages — utöka renderaren:**

Lägg sist i `wexoe_pages_render`:

```php
if (!empty($page['show_contact_form'])) {
    echo '<section id="kontakt">';
    echo \Wexoe\Core\Renderers\ContactForm::render([
        'eyebrow' => $page['contact_form_eyebrow'] ?? '',
        'title' => $page['contact_form_title'] ?? '',
        'subtitle' => $page['contact_form_subtitle'] ?? '',
        'layout' => $page['contact_form_layout'] ?? 'split',
        'theme' => $page['contact_form_theme'] ?? 'dark',
        'show_company' => $page['contact_form_show_company'] ?? true,
        'show_phone' => $page['contact_form_show_phone'] ?? true,
        'show_dropdown' => $page['contact_form_show_dropdown'] ?? true,
        'dropdown_label' => $page['contact_form_dropdown_label'] ?? '',
        'options' => $page['contact_form_options'] ?? null,
        'cta_text' => $page['contact_form_cta_text'] ?? '',
        'message_label' => $page['contact_form_message_label'] ?? '',
        'trust_signals' => $page['contact_form_trust_signals'] ?? null,
        'source_plugin' => 'wexoe-pages',
        'page_slug' => $page['slug'] ?? '',
        // contact_person: SSOT-uppslag baserat på sidans country/division om show_contact_form_show_contact_person
        'contact_person' => self::resolve_contact_person($page),
    ]);
    echo '</section>';
}
```

Där `resolve_contact_person` slår upp första aktiva coworker via `Collections::coworkers_for_scope` baserat på sidans country/division.

**Builder — UniquePageBuilder utökning:**

1. `lib/contact-form-types.ts` (delad — används också i Fas 9):
   ```ts
   export type ContactFormLayout = 'split' | 'centered';
   export type ContactFormTheme = 'dark' | 'light';
   export interface ContactFormState {
     eyebrow: string;
     title: string;
     subtitle: string;
     layout: ContactFormLayout;
     theme: ContactFormTheme;
     showCompany: boolean;
     showPhone: boolean;
     showDropdown: boolean;
     dropdownLabel: string;
     options: string;       // multiline
     ctaText: string;
     messageLabel: string;
     trustSignals: string;  // multiline
     showContactPerson: boolean;
   }
   export function emptyContactFormState(): ContactFormState { /* defaults */ }
   ```

2. `components/contact-form/ContactFormEditor.tsx` (delad komponent, neutral):
   - Tar emot `{ state, onChange }`. Visar alla fält. Återanvänds av:
     - UniquePageBuilder (Fas 7)
     - LP / PA / Audience editors (Fas 9)
   - Wrappers per page-typ är tunna (state-mappning).

3. `components/contact-form/ContactFormPreview.tsx` (delad komponent):
   - Visuell skiss. Reflekterar `state.theme` / `state.layout` live.

4. UniquePageBuilder lägger till en ny panel:
   ```tsx
   <Section title="Kontaktformulär" visible={state.showContactForm} onToggle={...}>
     <ContactFormEditor state={state.contactForm} onChange={...} />
   </Section>
   ```
   och PreviewPanel:
   ```tsx
   {state.showContactForm && <ContactFormPreview state={state.contactForm} />}
   ```

5. UniquePageState utökas med `showContactForm: boolean` och `contactForm: ContactFormState`.

6. Mapper utökas att läsa/skriva alla `Contact Form *`-fält på `cms_unique_pages`.

**Validering:**
- [ ] WP: skapa unique-page med `Show Contact Form=true` → `[wexoe_page slug=...]` renderar formuläret med `<section id="kontakt">`.
- [ ] Submit → ny rad i `User data` med `submission_type=contact`, `source_plugin=wexoe-pages`.
- [ ] AJAX går till `?action=wxcf_submit` (inte `wexoe_contact_submit`).
- [ ] 11 submissions från samma IP / 1h → 11:e svar `429`.
- [ ] Honeypot ifyllt → tyst avvisning, ingen rad i Airtable.
- [ ] Builder: ContactFormEditor visar alla fält, preview reflekterar layout/theme live.
- [ ] CSS `wxcf-*` krockar inte med `wxr-*` / `wxp-*`.

---

### Fas 8 — Per-plugin: contact-form i LP/PA/Audience

**Beroenden:** Fas 7.

**Mål:** LP/PA/Audience-sidor får integrerat kontaktformulär utan att duplicera renderer-kod. Befintliga `#kontakt`-anchors fortsätter fungera.

**Airtable (`Wexoe`-basen):**
1. Lägg de 15 `Contact Form *`-fälten i `Landing Pages`, `Product Areas` och `Audience Heroes`. Se [5.2](#52-airtable-wexoe-appxoucks68dqwasjf).
2. Defaults: `Show Contact Form=false`, `Contact Form Layout=split`, `Contact Form Theme=dark`.

**Wexoe Core scheman:**
1. Utöka `entities/landing_pages.php`, `entities/product_areas.php`, `entities/audience_heroes.php` med `contact_form_*` domain-keys mappade mot nya Airtable-fält. Cache-TTL oförändrad.

**Per page-plugin:**

1. `wexoe-landing-page/`: ny helper `wexoe_lp_render_contact_form_section($data): string`:
   ```php
   function wexoe_lp_render_contact_form_section($data) {
       if (empty($data['contact_form_show'])) return '';
       $contact_person = null;
       if (!empty($data['contact_form_show_contact_person'])) {
           $contact_person = [
               'name' => $data['contact_name'] ?? '',
               'title' => $data['contact_title'] ?? '',
               'email' => $data['contact_email'] ?? '',
               'phone' => $data['contact_phone'] ?? '',
               'image' => $data['contact_image'] ?? '',
               'quote' => $data['contact_quote'] ?? '',
           ];
       }
       $html = \Wexoe\Core\Renderers\ContactForm::render([
           'eyebrow' => $data['contact_form_eyebrow'] ?? '',
           'title' => $data['contact_form_title'] ?? '',
           'subtitle' => $data['contact_form_subtitle'] ?? '',
           'layout' => $data['contact_form_layout'] ?? 'split',
           'theme' => $data['contact_form_theme'] ?? 'dark',
           'show_company' => $data['contact_form_show_company'] ?? true,
           'show_phone' => $data['contact_form_show_phone'] ?? true,
           'show_dropdown' => $data['contact_form_show_dropdown'] ?? true,
           'dropdown_label' => $data['contact_form_dropdown_label'] ?? '',
           'options' => $data['contact_form_options'] ?? null,
           'cta_text' => $data['contact_form_cta_text'] ?? '',
           'message_label' => $data['contact_form_message_label'] ?? '',
           'trust_signals' => $data['contact_form_trust_signals'] ?? null,
           'colors' => ['main' => $data['color_main'] ?? '', 'accent' => $data['color_secondary'] ?? ''],
           'source_plugin' => 'wexoe-landing-page',
           'page_slug' => $data['slug'] ?? '',
           'contact_person' => $contact_person,
       ]);
       return '<section id="kontakt">' . $html . '</section>';
   }
   ```
   Anropas sist i LP-shortcode-renderingen.

2. `wexoe-product-area/`: samma mönster. Färgkälla: PA-record's `color_main`/`color_secondary`. `source_plugin=wexoe-product-area`.

3. `wexoe-audience-hero/`: samma mönster, men `show_contact_person=true` faller tillbaka på `Collections::coworkers_for_scope(['country' => $country, 'limit' => 1])` eftersom Audience saknar egna `contact_*`-fält.

4. **Audience marginalvalidering:** Audience använder `100vw`-trick. Säkerställ att `<section id="kontakt">`-wrappern alltid är container, aldrig full-width. Bestäms i denna fas.

**Validering:**
- [ ] Skapa LP, sätt `Show Contact Form=true` → cache-busta → WP visar formulär.
- [ ] `<section id="kontakt">` finns. `href="#kontakt"` scrollar dit.
- [ ] Submit → rad i `User data` med `source_plugin=wexoe-landing-page`.
- [ ] Samma för PA, Audience.
- [ ] CSS-prefix `wxcf-*` krockar inte med `wexoe-lp-*` / `wexoe-pa-*` / `wah-*`.
- [ ] Audience: hero-marginalerna (100vw) påverkas inte.

---

### Fas 9 — Builder: ContactFormEditor i LP/PA/Audience

**Beroenden:** Fas 7, Fas 8.

**Mål:** Redaktörer redigerar contact_form_*-fälten direkt i LP/PA/Audience-editorn med live preview. Återanvänder delade `components/contact-form/ContactFormEditor.tsx` + `ContactFormPreview.tsx` från Fas 7.

**Artefakter (`wexoebuilder/`):**

1. **Typ-utökning:**
   - `lib/types.ts` (LP `PageState`) → lägg `showContactForm: boolean` + `contactForm: ContactFormState`.
   - `lib/product-area-types.ts` → samma.
   - `lib/audience-types.ts` → samma.

2. **Mappers:**
   - `lib/page-mapper.ts`, `lib/product-area-mapper.ts`, `lib/audience-mapper.ts` — läs/skriv `Contact Form *`-fält.

3. **Wrappers per page-typ:**
   - `components/editors/ContactFormEditorWrapper.tsx` (LP) — tar `state.contactForm` + dispatch.
   - `components/audience/editors/ContactFormEditorWrapper.tsx` — samma mönster.
   - `components/product-area/editors/ContactFormEditorWrapper.tsx` — samma.
   - **Ingen duplicering av fält-UI** — alla wrappers använder `components/contact-form/ContactFormEditor.tsx`.

4. **Reducer-actions:**
   - `lib/state.ts` (LP) → `SET_CONTACT_FORM_FIELD` + `TOGGLE_CONTACT_FORM`.
   - Motsvarande i PA och Audience.

5. **EditorPanel / PreviewPanel:**
   - `components/EditorPanel.tsx` → lägg `'contactForm'` i sections-arrayen + quick-nav-pill.
   - `components/PreviewPanel.tsx` → rendera `ContactFormPreview` om `state.showContactForm`.
   - Motsvarande i `AudiencePreviewPanel.tsx` / `AudienceBuilder.tsx` / `ProductAreaBuilder.tsx` / `ProductAreaPreviewPanel.tsx`.

6. **Claude-prompt-uppdatering:**
   - `lib/airtable-schema-lp.md` → lägg de 15 Contact Form-fälten i fältlistan + formateringsregel:
     > 9. **Contact Form-fält:** `Contact Form Layout` ska vara `split` eller `centered`. `Contact Form Theme` ska vara `dark` eller `light`. Trust signals format `**Bold** | Resten`, en per rad. **Alla checkbox-fält** ska ALLTID inkluderas (även `false`).
   - `lib/airtable-schema-pa.md` → samma.
   - Audience använder direct mapper, ingen Claude-prompt.

**Validering:**
- [ ] LP-editor: panel "Kontaktformulär" → fyll i fält → live preview uppdateras.
- [ ] Publish → Airtable uppdateras → cache-bust → WP-sidan visar nya värdena.
- [ ] Stäng av `showContactForm=false` → sektion försvinner från preview och WP.
- [ ] Samma för PA och Audience.
- [ ] Claude-transform tappar inte `Contact Form *`-fält vid UPDATE: ändra annat fält → contact-form-värden bevaras.
- [ ] Befintliga LP utan `Contact Form *`-värden öppnas → `contactForm = emptyContactFormState()`, `showContactForm = false`.

---

### Fas 10 — Globals-driven defaults i LP/PA

**Beroenden:** Fas 4, Fas 9.

**Mål:** Nya LP/PA skapas med förfyllda Contact-fält (kontaktperson) baserat på `core_coworkers`-uppslag.

**Steg:**

1. **Builder create-flöde:** I `app/api/publish/route.ts` (eller pre-create hook): när ny LP/PA skapas och alla `contact_*`-fält är tomma → slå upp `core_coworkers` filtrerat på `country` (LP) eller `country + division` (PA). Välj record med lägst `Order`. Sätt `contact_name`, `contact_title`, `contact_email`, `contact_phone`, `contact_image`.
2. **Respektera manuell input:** Default sätts endast om alla Contact-fält är tomma vid create-tid.
3. **Markera default-sourced (intern flag):** `_contact_source: 'default-coworker' | 'manual'` i state, ej skickad till Airtable. Vid manuell edit → flippa till `manual`. Inget UI-värde nu — möjlig framtida banner.

**Validering:**
- [ ] Ny LP med country=SE → contact-fält förfyllda från första aktiva coworker.
- [ ] Ny LP med manuell `contact_name` → bevaras vid publish.
- [ ] Existerande LP utan ändringar → contact-fält oförändrade.
- [ ] Ny LP utan coworker för country=SE → contact-fält tomma, ingen krasch.

---

### Fas 11 — Polish och deprecation

**Beroenden:** alla tidigare faser.

**Mål:** Avveckla `Old plugins/wexoe-contact-form.php`. Kvalitetshöjningar.

**Steg:**

1. **Datamigration:** För varje sida som idag använder `[wexoe_contact_form]`:
   - Identifiera page-typ (LP / PA / Audience / annat).
   - Sätt `Show Contact Form=true` i Airtable, fyll i avvikande titel/subtitel.
   - Ta bort `[wexoe_contact_form]`-shortcoden och den explicit-döpta "kontakt"-WP-sektionen.
2. **Make.com-flöden:**
   - Identifiera scenarier som hänger på `https://hook.eu1.make.com/...`.
   - Skapa motsvarande Airtable-automation på "ny rad i User data där Submission Type=contact".
   - Parallell-kör i 1 vecka.
   - Inaktivera Make-webhook.
3. **Deprecate Old-pluginen:**
   - `[DEPRECATED]` i plugin-headern.
   - `admin_notices` varnar om pluginet aktivt.
   - När 0 sidor använder shortcoden → radera.
4. **Polish:**
   - SEO-meta retrofit för LP/PA/Audience.
   - Saved sections / page-templates (CMS Save → klona).
   - Audit-länkar i `/globals/*` → "Visa historik i Airtable".
   - "Vad använder det här SSOT-recordet?"-vy.
   - Bulk-operationer i collections.
   - "Cache rensad i Wexoe Core ✓"-bekräftelse efter save.

**Validering:**
- [ ] WP-databas-sökning `[wexoe_contact_form` returnerar 0 träffar.
- [ ] Old plugin inaktivt → kontaktformulär fortsätter fungera.
- [ ] Airtable-automation triggas på nya submissions korrekt.
- [ ] SEO-meta visas korrekt på LP/PA/Audience.

---

## 7. Risker och mitigations

| Risk | Sannolikhet | Påverkan | Mitigation |
|---|---|---|---|
| **`/globals`-redigering bryter publicerade sidor** | Medel | Hög | Soft-delete via `Active`. Audit-vy. Fältvalidering. |
| **Cache-stale efter SSOT-ändring** | Hög | Medel | Auto-invalidate vid POST/PATCH. "Tvinga rensning"-knapp. 1h TTL SSOT vs 24h sid-data. |
| **Land-kontext-detektering felar** | Medel | Hög | Fallback-kedja: Domain → URL Prefix → `Is Default` → SE-hårdkod. Logga vilken fallback som triggade. |
| **`cms_unique_pages`-tabellen växer för bred (60+ kolumner)** | Medel | Låg | Acceptabelt. Airtable klarar 500+ kolumner. Editor renderar collapse:able paneler så bredden är osynlig för redaktör. Alternativ (sub-records) introducerar mer komplexitet än den löser. |
| **Sektion-ordning är fast — sidor som behöver annan ordning kan inte byggas** | Medel | Medel | Acceptabelt i MVP. Om verkliga use cases dyker upp: lägg `Section Order`-overrider-fält (single-line "hero,faq,team-grid,…") senare. Inte premature optimization. |
| **Singleton-fallback ger fel record** | Låg | Hög | Hård invariant: max 1 `Is Default=true` per tabell. REST validerar vid PATCH. PHP loggar warning vid dubblett. |
| **Två `id="kontakt"`-element under migrationen** | Medel | Medel | Migrera en sida i taget. Checklista i Fas 11. |
| **AJAX-handler-krock med Old plugin** | Medel | Medel | Nytt action-namn `wxcf_submit`. |
| **CSS-prefix-kollision** | Låg | Låg | Tre olika prefix: `wxr-*` (renderers), `wxp-*` (wexoe-pages), `wxcf-*` (contact form). Alla scopade med uniqid där relevant. |
| **Audience full-bredd-trick (100vw) krockar med kontaktform** | Medel | Medel | Renderaren alltid container, aldrig 100vw. Bestäms i Fas 8. |
| **Claude-transform tappar Contact Form-fält i UPDATE** | Hög | Medel | Lägg fälten i "always echo"-listan i `airtable-schema-lp.md` / `-pa.md`. Testa explicit i Fas 9. |
| **Spam i kontaktformulär** | Hög | Låg | Honeypot + rate-limit 10/h/IP. reCAPTCHA om eskalerar. |
| **Sektioner växer okontrollerat över tid → "mini-Wix"** | Medel | Medel | Sektion-bibliotek är hård-kodat i `wexoe-pages` + Core. Ny sektion kräver PHP-renderer + Builder-editor + Airtable-fält + Core-helper — fyra ställen. Inte trivialt att lägga till, vilket är **avsiktligt** — det disciplinerar utbyggnad. |
| **Airtable API-rate-limit vid SSOT-edit-vågor** | Låg | Låg | Wexoe Core cache:ar reads. Builder-write är icke-frekvent. |

---

## 8. Designbeslut tagna

Följande beslut är gjorda för att eliminera ambiguity inför implementation:

1. **Render-helpers bor i wexoe-core.** Ingen separat `wexoe-sections`-plugin. `\Wexoe\Core\Renderers\<X>::render($cfg)` anropas direkt av wexoe-pages, wexoe-landing-page, wexoe-product-area, wexoe-audience-hero. Inga cirkulära beroenden.
2. **Tier 2 är data-driven, inte composition.** `cms_unique_pages` är en bred tabell med "kanske"-fält. Renderaren visar sektion om `Show <X>=true` eller om fältet är ifyllt. Ingen `cms_page_sections`-tabell, ingen Data JSON, ingen `Order`-kolumn. Detta är samma mönster som LP-tabs och PA-Normals — välkänt i kodbasen.
3. **Sektion-ordning är fast i schemat.** Bestäms av wexoe-pages PHP-renderare. För meta-sidor (om-oss, karriär, etc.) är detta tillräckligt. Sidor som behöver friare ordning ska vara dedikerade plugins, inte Tier 2.
4. **AJAX-actionnamn är `wxcf_submit`.** Inte `wexoe_contact_submit` — undviker krock med Old-pluginet.
5. **Spam-mitigering: honeypot + rate-limit.** Honeypot `_hp` + transient-baserad 10/h/IP. reCAPTCHA tillkommer endast vid behov.
6. **Defaults bor i PHP, inte Airtable.** Tomma Airtable-fält faller tillbaka på vettiga PHP-defaults.
7. **`show_contact_person=true`:**
   - LP/PA: läs sidans `contact_*`-fält som idag.
   - Audience: `Collections::coworkers_for_scope` med limit 1.
   - wexoe-pages: samma som Audience (SSOT-uppslag).
8. **CMS-tabeller i Wexoe NY skapas från scratch.** De 8 placeholders raderas. Endast `cms_unique_pages` skapas (Fas 5).
9. **Country/Division-scope finns från dag 1** även om bara SE används.
10. **Hours-fält läggs i `core_company`.** Status-logik (öppet/stängt) stannar i PHP.
11. **wexoe-contact-page migreras inte i denna plan.** Separat plan senare. Är en kandidat för Tier 1.
12. **LP/PA Contact-fält pekar inte på `core_coworkers` ännu.** Bara default-förfyllnad vid create (Fas 10).
13. **Audience har inget visitkort.** `show_contact_person=true` → SSOT-uppslag.
14. **Inga drag-drop. Inga draft/publish-stadier.** Allt går live direkt.
15. **wexoe-pages är en plugin, inte ett ramverk.** Tredjepartsutökning av sektion-typer kräver kod i wexoe-pages + Core. Detta är **medvetet** — det disciplinerar mot mishmash.

---

## 9. Öppna frågor

Frågor som *inte* hindrar implementation men bevakas:

1. **Multi-language.** Per-country-records räcker som plattformsfundament. WPML/Polylang för riktig översättning, utanför scope.
2. **Approval status på testimonials.** Lägg `Approval Status` om legal kräver.
3. **AI-orkestrerad sidgenerering.** Senare fas. Med data-driven modell: AI fyller fält på `cms_unique_pages`, samma som redaktör. Inte composition-generering.
4. **Migration av LP/PA till Tier 2.** Stannar som dedikerade. Tier 2 är additivt.
5. **wexoe-contact-page, downloads-sidor, start-sidan**: separata planer.
6. **Section Order-override.** Om praktiska use cases visar att fast ordning är begränsning — lägg `Section Order`-fält på `cms_unique_pages` (csv-string `"hero,faq,team-grid,..."`) som overrider default-ordningen. Inte nu.
7. **Sub-records för multi-instance-sektioner.** Om vi behöver "manuellt valda coworkers" (inte scope-baserade) på en team-grid — lägg multi-link senare. Inte nu.
8. **Reverse-länkar i Airtable**: auto-genererade. Döp om vid behov, ignoreras av Wexoe Core-mapper.

---

## 10. Bilagor

### A. Terminologi

| Term | Definition |
|---|---|
| **SSOT** | Single Source of Truth. `core_*`-tabeller i Wexoe NY. |
| **CMS** | `cms_unique_pages`-tabell i Wexoe NY för Tier 2-sidor. |
| **Tier 1** | Statisk PHP-renderad sida som drar SSOT men inte är layout-redigerbar i builder. |
| **Tier 2** | `cms_unique_pages`-renderad sida via wexoe-pages. Data-driven, fast struktur. |
| **Renderer** | Klass i `\Wexoe\Core\Renderers\*` som returnerar HTML för en UI-byggsten. |
| **Scope** | Filter-uppsättning `{ country?, division?, customer_type? }` som styr vilka SSOT-records som visas. |
| **Singleton** | Tabell med 1 record per scope, fallback till `Is Default = true`. |
| **Collection** | Tabell med många records, filtrerade via scope-länkar. |
| **Taxonomy** | Referensdata-tabell. |
| **Wexoe Core** | WP-plugin med `Core::entity()`-API + Helpers + Renderers. |

### B. Filstruktur efter implementering

**`wexoebuilder/`:**
```
app/
  globals/
    page.tsx                              -- entitets-grid
    {company,graphic-profile,countries,divisions,customer-types,coworkers,partners,testimonials}/page.tsx
  editor/
    unique/
      page.tsx                            -- create
      [recordId]/page.tsx                 -- edit
  api/
    core/[entity]/route.ts                -- SSOT CRUD
    unique-page/route.ts                  -- Tier 2 CRUD
components/
  BuilderShell.tsx                        -- delad plumbing
  UniquePageBuilder.tsx                   -- Tier 2 builder
  unique-page/
    editors/
      HeroEditor.tsx
      TextImageEditor.tsx
      TextOnlyEditor.tsx
      FaqEditor.tsx
      TeamGridEditor.tsx                  -- scope-väljare för SSOT
      PartnersMarqueeEditor.tsx
      TestimonialCardEditor.tsx
      CtaBannerEditor.tsx
    preview/
      HeroPreview.tsx
      TextImagePreview.tsx
      ...
  contact-form/
    ContactFormEditor.tsx                 -- delas av Tier 2 + LP/PA/Audience
    ContactFormPreview.tsx                -- delas
  editors/
    ContactFormEditorWrapper.tsx          -- LP wrapper (state-mappning)
  audience/editors/
    ContactFormEditorWrapper.tsx
  product-area/editors/
    ContactFormEditorWrapper.tsx
  core/
    CoreEntityShell.tsx
    CoreEntityForm.tsx
    SsotImageField.tsx
lib/
  core/
    types.ts, mapper.ts, loader.ts, registry.ts, forms.ts, reserved-slugs.ts
  unique-page-types.ts
  unique-page-mapper.ts
  contact-form-types.ts
```

**`wexoeplugins/wexoe-core/`:**
```
entities/
  core_company.php
  core_graphic_profile.php
  core_countries.php
  core_divisions.php
  core_customer_types.php
  core_coworkers.php
  core_partners.php
  core_testimonials.php
  cms_unique_pages.php
  (utökade) landing_pages.php / product_areas.php / audience_heroes.php
write-entities/
  (samma 9 entiteter)
src/
  Renderers/
    Hero.php
    TextImage.php
    TextOnly.php
    Faq.php
    TeamGrid.php
    PartnersMarquee.php
    TestimonialCard.php
    CtaBanner.php
    ContactForm.php
  ContactForm/
    Handler.php                           -- wxcf_submit AJAX → user_submissions
  Helpers/
    Context.php
    Singletons.php
    Collections.php
  Constants.php                           -- RESERVED_SLUGS
```

**`wexoeplugins/New plugins/`:**
```
wexoe-pages/                              -- [wexoe_page slug="..."] shortcode
  wexoe-pages.php                         -- läser cms_unique_pages, anropar Core\Renderers\*
wexoe-landing-page/                       -- befintlig, utökad med ContactForm-anrop
wexoe-product-area/                       -- befintlig, utökad
wexoe-audience-hero/                      -- befintlig, utökad
wexoe-contact-page/                       -- oförändrad (separat plan)
```

### C. Cache-strategi

| Lager | Cache | TTL | Invalidering |
|---|---|---|---|
| Builder → Airtable read | SWR i React | 5 min | Manual revalidate efter mutation |
| Wexoe Core → Airtable read (SSOT) | WP transient | 1h | `POST /wp-json/wexoe-core/v1/invalidate` |
| Wexoe Core → Airtable read (sid-data) | WP transient | 24h | Samma |
| WP fragment cache | Object cache | Per renderer | Plugin-uppdatering |

Vid `/globals`-save:
1. PATCH Airtable.
2. POST `/wp-json/wexoe-core/v1/invalidate` med entity-namn.
3. Wexoe Core rensar transient.
4. Nästa WP-request → cache-miss → fräsch data.

### D. Snabbreferens — Core-API efter implementation

```php
// Entity API
$company         = \Wexoe\Core\Core::entity('core_company')->find_by('slug', 'wexoe-se');
$unique_page     = \Wexoe\Core\Core::entity('cms_unique_pages')->find_by('slug', 'om-oss');

// Helpers
$profile         = \Wexoe\Core\Helpers\Singletons::graphic_profile_for_division('industri');
$coworkers       = \Wexoe\Core\Helpers\Collections::coworkers_for_scope(['country' => 'SE', 'limit' => 4]);
$country_code    = \Wexoe\Core\Helpers\Context::current_country_code();

// Renderers (samma signatur överallt: render(array $config): string)
echo \Wexoe\Core\Renderers\Hero::render($cfg);
echo \Wexoe\Core\Renderers\TextImage::render($cfg);
echo \Wexoe\Core\Renderers\TeamGrid::render(['h2' => 'Vårt team', 'scope' => ['country' => 'SE']]);
echo \Wexoe\Core\Renderers\ContactForm::render([
    'title' => 'Kontakta oss',
    'theme' => 'dark',
    'source_plugin' => 'wexoe-landing-page',
    'page_slug' => $slug,
]);

// Submissions (befintligt, anropas från ContactForm\Handler)
\Wexoe\Core\Core::submission('user_submissions')->create_mapped([
    'submission_type' => 'contact',
    'email' => $email,
    // ...
]);
```

### E. Anti-mishmash-disciplin

Tier 2 är **inte** en mini-Wix av tre skäl, designade in i arkitekturen:

1. **Begränsad sektion-pool.** 9 sektion-typer max för MVP (8 i Fas 6 + contact-form i Fas 7). Ny typ kräver kod på fyra ställen: Airtable-fält + Wexoe Core schema + Renderer-klass + Builder-editor. Inte trivialt.
2. **Fast ordning.** Sektion-ordningen är hårdkodad i `wexoe-pages.php`. Redaktör väljer *vilka* sektioner, inte *vilken ordning*. Detta eliminerar 90% av "mishmash"-utfall som plagar Wix-sajter.
3. **Tier 2 är inte default.** Säljsidor → LP. Tjänste-sidor → PA. Målgrupps-sidor → Audience. Tier 2 är *bara* för one-off-meta-sidor (om-oss, karriär, etc.). Om en sidtyp visar sig vara återkommande → promotera till dedikerad plugin (egen tabell + egen renderer-plugin).

Punkt 3 är den viktigaste: vi använder inte Tier 2 för "alla sidor utom de tre vi har". Vi använder den för meta-sidor som annars hade varit hårdkodad PHP. LP/PA/Audience kvarstår dedikerade.

---

**Slut på dokument.** 
