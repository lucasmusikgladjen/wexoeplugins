# CLAUDE.md — orientering för en ny LLM-session

Du är i `wexoeplugins`. Den här filen är *bara* ingången — den ger bred kontext och pekar vidare.

> **Nyligen ändrat (2026-05-31):**
> - Feature-plugins-mappen heter numera **`plugins/`** (tidigare `New plugins/` — mellanslaget togs bort). Sökvägar i äldre commits/loggar kan visa det gamla namnet.
> - Spar-vägen i buildern är **deterministisk sedan FAS 2** (rena funktioner, inga Claude-anrop). Om du hittar en text — i kod-kommentar, guide eller prompt — som påstår att buildern skriver till Airtable "via Claude"/"Claude-transform", är den **inaktuell**: lita på koden och rätta texten. (`IMPLEMENTATION_LOG.md` och `ARKITEKTURPLAN.md`-loggen beskriver historik i dåtid — det är korrekt och ska inte ändras.)

---

## 1. Vad systemet är

Wexoe driver sin publika sajt på WordPress (Enfold-tema) men har flyttat allt innehåll ut ur WP. Datan ligger i Airtable. WP-plugins läser från Airtable och renderar via shortcodes.

Två separata repon utgör systemet:

| Repo | Roll |
|---|---|
| **wexoeplugins** (denna) | WordPress-pluginen. `wexoe-core` är datalager + helpers. Feature-plugins per sidtyp renderar HTML. |
| **wexoebuilder** (separat repo, samma org) | Next.js-app där marknadsförare redigerar sidor med live-preview. Skriver till Airtable. |

```
wexoebuilder  →  Airtable  →  wexoe-core  →  feature-plugins  →  WP-sidor
  (Next.js)     (SSOT+CMS)    (transients)    (shortcodes)
```

Du jobbar bara i denna repo. Buildern lever sitt eget liv i `wexoebuilder/` med egen CLAUDE.md.

---

## 2. wexoe-core: hjärtat

**Endast** `wexoe-core` får prata med Airtable. Feature-plugins ringer alltid via fasaden `\Wexoe\Core\Core`:

```php
$page = Core::entity('cms_landing_pages')->find('fjarraccess');   // läs
$result = Core::submission('user_submissions')->create_mapped([…]); // skriv
$class = Core::renderer('contact-form');                            // delad markup
Core::log('warning', '…', ['context' => …]);
```

Detaljer (alla metoder, schemaformat, helpers, cache-flow) finns i `UTVECKLINGSGUIDE.md` § 3–8. Öppna den när du faktiskt skriver kod mot Core.

Två slags scheman:
- `wexoe-core/entities/*.php` — **läs**-scheman (mappning + typer + cache-TTL)
- `wexoe-core/write-entities/*.php` — **skriv**-scheman (bara fältmappning)

---

## 3. Airtable-baser

| Bas | ID | Status |
|---|---|---|
| Wexoe NY | `appokKSTaBdCa8YiW` | **Kanonisk.** All ny data, alla nya tabeller skrivs hit. |
| Wexoe (legacy) | `appXoUcK68dQwASjF` | **Fasas ut.** Skriv aldrig hit. Läs bara om du måste audita migration. |

SSOT-tabeller (`core_*` / `cms_*`) sätter `'base_id' => Plugin::SSOT_BASE_ID` i schemat för att tvinga Wexoe NY även när plugin-konfigen pekar mot en annan bas.

**Migrationsstatus:** slutförd (legacy-basen utfasad). Datat är på plats. *Vissa* entity-filer heter fortfarande sina legacy-namn (`landing_pages.php`, `lp_tabs.php`, `audience_heroes.php`) trots att Airtable-tabellen bytt namn. Det är medvetet — döp inte om filer reflexmässigt; fråga om det är oklart. `IMPLEMENTATION_LOG.md` har migrationshistoriken; den framåtblickande arkitekturplanen ligger i `ARKITEKTURPLAN.md`. Lita på koden/Airtable först om något skiljer sig.

---

## 4. Naming (låst — diskutera inte alternativ)

- **snake_case**, **engelska**, överallt — Airtable display-namn, PHP-fält, TS-state.
- **Prefix:** `core_` SSOT, `cms_` redigerbart innehåll, `inbox_` formulär-inflöden, `pim_` / `ext_` framtid.
- **Plural** för kollektioner (`core_partners`), **singular** för singletons (`core_company`).
- **Type-suffix:** `*_url`, `*_email`, `*_at` (datetime), `*_count` (int), `*_id` / `*_ids` (link), `*_html`, `*_markdown`, `*_json`.
- **Bool-prefix:** `is_*`, `has_*`, `show_*`.
- **kebab-case** bara i slug-*värden*, aldrig fältnamn.

Fullständig tabell i `UTVECKLINGSGUIDE.md` § 2.

---

## 5. Hårda regler (gör aldrig)

1. **Aldrig** kalla Airtable från ett feature-plugin direkt. Gå via `Core::entity()` / `Core::submission()` / `Core::writer()`.
2. **Aldrig** skriva till legacy-basen `appXoUcK68dQwASjF`.
3. **Aldrig** ändra naming-konventionerna i § 4 — om något känns fel, fråga.
4. **Aldrig** aktivera ett plugin halvvägs — kolla `class_exists('\\Wexoe\\Core\\Core')` i shortcode-funktionen och returnera synligt fel om Core saknas (mönster i `UTVECKLINGSGUIDE.md` § 3.7 och § 6).
5. **Aldrig** duplicera Core-funktionalitet (markdown, color, youtube, contact-form-rendering) lokalt i ett feature-plugin. Använd helpern.

---

## 6. Verktyg i den här miljön

- **Airtable MCP** är tillgänglig och får användas fritt — läsa scheman, skapa/uppdatera tabeller och records i Wexoe NY. Skriv aldrig till legacy-basen. Servrarnas instruktioner längst upp i prompten beskriver flowet (`search_bases` → `list_tables_for_base` → `get_table_schema` → `list_records_for_table` / `update_records_for_table`).
- **Ingen CI, inga test-svit, inga linters** i denna repo idag. `php -l` är vettigt på filer du rört. Faktisk verifiering sker manuellt: användaren zippar plugin-mappen och laddar upp via WP-admin.
- **Ingen automatisk deploy.** En commit/push gör *inget* live — användaren måste manuellt re-installera pluginet. Du behöver inte vara extra försiktig "för att det går till prod", men du måste vara extra tydlig om vad användaren behöver göra efter merge.

---

## 7. Vanliga uppgifter (icke uttömmande)

- Lägga till/justera fält i `wexoe-core/entities/*.php` när Airtable-tabellen ändrats.
- Bygga eller ändra ett feature-plugin under `plugins/wexoe-*/`.
- Skapa en helt ny sidtyp — följ flowet i **`SKAPA-SIDA.md`** (marknadsförar-guide) eller den tekniska referensen i **`NEW_PAGE_TYPE.md`**.
- Bugfix i `wexoe-core/src/` (Normalizer, Cache, AirtableClient, …).
- Datafixar i Airtable via MCP (typ link-rewiring efter migration — se `IMPLEMENTATION_LOG.md` för precedens).

För större migrations- eller datafix-åtgärder: **appenda till `IMPLEMENTATION_LOG.md`** med symptom + åtgärd + resultat. Vanliga feature-PRs behöver inte loggas där.

---

## 8. Mappstruktur (kort)

```
wexoe-core/
  wexoe-core.php             # bootstrap
  src/                       # Core, AirtableClient, Cache, EntityRepository,
                             #   WriteRepository, Normalizer, SchemaRegistry,
                             #   Helpers/, Renderers/, ContactForm/, Admin/
  entities/                  # läs-scheman (en fil per entitet)
  write-entities/            # skriv-scheman

plugins/
  wexoe-landing-page/        # en mapp per feature-plugin
  wexoe-pages/               # dispatcher-mönster: cms_pages + cms_page_sections
    sections/                # en fil per section_type
  wexoe-alb-blocks/          # Avia Layout Builder-integrering
  wexoe-audience-hero/  wexoe-customer-type-page/  wexoe-contact-page/
  wexoe-product-area/  automation-pillar/  (med flera)
```

Full anatomi av ett plugin: `UTVECKLINGSGUIDE.md` § 6. Dispatcher-mönstret för sidor med många sektioner: § 7.

---

## 9. Var fortsätter jag läsa?

| Fil | Använd när |
|---|---|
| `UTVECKLINGSGUIDE.md` | Du ska skriva kod mot Core eller bygga/ändra ett feature-plugin. Auktoritativ teknisk referens. |
| `SKAPA-SIDA.md` | Användaren vill skapa en ny sidtyp och behöver flow-instruktioner (4 faser, en LLM-session per fas). |
| `NEW_PAGE_TYPE.md` | Du är LLM:en i en av faserna i SKAPA-SIDA-flowet och behöver tekniska detaljer. |
| `ARKITEKTURPLAN.md` | Den kanoniska, framåtblickande arkitektur-refaktorn (modularisering). Spegelidentisk i båda repona. Arbeta mot den och bocka av progress. |
| `IMPLEMENTATION_LOG.md` | Du vill veta om en datafix redan gjorts, eller du själv ska logga en migration. |
| `wexoe-core/src/Core.php` | Doc-kommentarerna är auktoritativa när dokumentation och kod skiljer sig åt. |

`wexoebuilder/CLAUDE.md` finns i det *andra* repot — du har inte access till det härifrån, men nämn det för användaren om en uppgift kräver builder-ändringar.

---

## 10. Git

Du jobbar på branchen som user/harness anger (typiskt `claude/<task>-XXXX`). Commit-meddelanden på svenska eller engelska följer existerande stil: `feat(wexoe-pages): …`, `fix(airtable): …`, `chore: …`, `docs: …`, `refactor(...)`.
