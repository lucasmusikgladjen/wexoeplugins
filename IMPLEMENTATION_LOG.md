# Wexoe enhetlig utvecklingsplan — Implementationslogg

**Branch (båda repon):** `claude/execute-dev-plan-YRjeI`
**Källplan:** `unifiedfeaturesplan.md` (rev 2, 2026-05-13)
**Implementation startad:** 2026-05-13

Detta dokument loggar varje konkret åtgärd som tas under implementationen av planen. Filer som skapas/ändras, Airtable-mutationer, designbeslut som dyker upp på vägen, samt manuella TODO:s som användaren måste utföra (när utökning till runtime krävs).

---

## Justeringar mot ursprungsplan

Efter klargorande från användare:

| Punkt | Plan | Justering |
|---|---|---|
| Fas 1 — radera placeholder cms_*-tabeller | Radera 8 cms_*-tabeller | **Behålls.** De ska migreras senare. Endast initial-data + Hours-fält på core_company. |
| Validering | WP-test, dev-server-test | **Hoppas över.** Skriv koden, dokumentera vad som behöver verifieras efter deploy. |
| Fas 11 | Inkl. Make.com + WP-db-sökning | **Bara kod-del.** Externa steg dokumenteras som manuella TODOs. |
| Audience/Customer types | — | Lämnas som är. Ingen omdöpning. |

---

## Loggens format

Per fas dokumenteras:
- **Filer skapade/ändrade** (med absoluta sökvägar)
- **Airtable-mutationer**
- **Designbeslut på vägen**
- **Manuella TODOs** (saå användare måste göra efteråt)
- **Status:** in-progress / klar

---

## Fas 11 — Polish & deprecation (kod-delen)

**Status:** Klar (kod-delen). Externa steg dokumenterade som TODOs nedan.

**Filer ändrade:**
- `Old plugins/wexoe-contact-form.php` — plugin-header bytt till `[DEPRECATED] Wexoe Contact Form`, version `1.3.0-deprecated`. Lägg admin_notices som varnar admin om plugin är aktivt.

**Avgränsningar (designbeslut för MVP):**
- **SEO-meta retrofit för LP/PA/Audience** — inte implementerat. Skulle kräva nya SEO-fält i Airtable (LP `SEO Title`, `SEO Description`, `OG Image URL`), schema-utökning, och `wp_head`-hook i resp. plugin. cms_unique_pages har redan dessa (Fas 5). Lämnas som senare polish.
- **"Saved sections / page-templates (CMS Save → klona)"** — uppskattat som en mini-feature i sig själv. Inte i denna runda.
- **Audit-länkar i `/globals/*` → "Visa historik i Airtable"** — kräver direkt-deep-link-URL till Airtable record-side. Trivialt att implementera senare när vi vet exakt format.
- **"Vad använder det här SSOT-recordet?"-vy** — kräver reverse-search över Wexoe-basens LP/PA-records. Senare fas.
- **Bulk-operationer i collections** — senare.
- **"Cache rensad i Wexoe Core ✓"-bekräftelse efter save** — `invalidateWexoeCoreCache` loggar redan success men UI:t har ingen bekräftelse. Senare polish.

**Manuella TODOs efter deploy (kräver tillgång till produktion):**

1. **Datamigration av befintliga sidor som använder `[wexoe_contact_form]`-shortcoden:**
   - Sök i WP-databasen: `SELECT ID, post_title FROM wp_posts WHERE post_content LIKE '%[wexoe_contact_form%';`.
   - För varje träff:
     - Identifiera page-typ (är det en LP/PA/Audience eller en stand-alone-sida?).
     - Om LP/PA/Audience: sätt `Show Contact Form=true` i Airtable, fyll i avvikande titel/subtitel om olika från default.
     - Ta bort `[wexoe_contact_form]`-shortcoden från post-innehållet.
     - Ta bort den explicit-döpta "kontakt"-WP-sektionen om sådan finns.
   - För stand-alone-sidor: ersätt med `[wexoe_page slug="..."]` (cms_unique_pages med `Show Contact Form=true`).

2. **Make.com-flöden:**
   - Identifiera alla Make.com-scenarier som hänger på `https://hook.eu1.make.com/sulae2u3lux9g9dqfabtsdngiwz46s6g` (gamla webhook).
   - Skapa motsvarande Airtable-automation: "Trigger när Submission Type = contact i User data".
   - Parallell-kör i 1 vecka. Verifiera att samma data flödar till samma destinations.
   - Inaktivera Make-webhook.

3. **Slutgiltig deprecation:**
   - När WP-databasen visar 0 träffar för `[wexoe_contact_form` → radera `Old plugins/wexoe-contact-form.php`.
   - Tills dess: behåll plugin aktivt men dokumentera att alla nya sidor MÅSTE använda Show Contact Form-checkbox-flödet.

---

## Fas 10 — Globals-driven defaults i LP/PA

**Status:** Klar (med en avgränsning).

**Filer skapade i `wexoebuilder/`:**
- `lib/default-coworker.ts` — `resolveDefaultCoworker()` slår upp första aktiva coworker från SSOT (filtrerad på country, valfritt division). `contactFieldsEmpty()` är guard-funktionen.

**Filer ändrade:**
- `app/api/publish/route.ts` (LP create) — om alla `contact_*`-fält är tomma vid create, kalla `resolveDefaultCoworker({country: 'SE'})` och förfyll innan Claude-transform.
- `app/api/product-area/route.ts` (PA create) — samma mönster, country=SE.

**Designbeslut:**
- **Default-injektion sker bara om ALLA contact_*-fält är tomma.** Manuella värden bevaras alltid.
- **Country är hårdkodad till `SE`** för MVP. Bättre lösning: härled country från sidans URL/division (kräver mer state-modellering). Senare fas.
- **Division-filtrering hoppas över i PA**: Wexoe-basens `Divisions`-tabell (`tblKam1tUTlR13atl`) är INTE samma som SSOT-basens `core_divisions`. För att korsbas-matcha skulle vi behöva slug-mappning. Lämnas som öppen fråga.
- **Default-injektion sker server-side i publish-route**, inte i klienten. Detta säkerställer att Claude-transform alltid får default-värdena.
- **`_contact_source: 'default-coworker' | 'manual'`-flagga** från planen INTE implementerad — onödig komplexitet för MVP. Kan läggas till senare som banner i builder.

**Manuella TODOs efter deploy:**
- Skapa ny LP utan att fylla i contact_*-fält → publishera → kontrollera att Airtable har coworker-data från SSOT.
- Skapa ny LP med manuellt `contactName` → publishera → kontrollera att den manuella datan bevaras.
- Befintliga LP utan ändringar → contact-fält ska vara oförändrade (vi triggar bara default vid CREATE-grenen).
- Skapa coworker för Country=SE i `/globals/coworkers` om ingen finns — annars är default-injektion meningslös.

---

## Fas 9 — Builder ContactFormEditor i LP/PA/Audience

**Status:** Klar.

**Filer ändrade i `wexoebuilder/`:**
- `lib/types.ts` — `PageState` får `showContactForm + contactForm`. SectionId utökad med `'contactForm'`.
- `lib/state.ts` — `initialState` har emptyContactFormState.
- `lib/page-mapper.ts` — `pageStateFromRecords` hämtar nu `Show Contact Form` + Contact Form-fält. Nya helpers `contactFormFromRecord()` + `contactFormToFields()` för delade builder-helpers.
- `lib/audience-types.ts` + `lib/audience-mapper.ts` — samma utökning.
- `lib/product-area-types.ts` + `lib/product-area-mapper.ts` — samma.
- `lib/claude-transform.ts` — buildLpPayload + buildPaPayload skickar nu alla 15 Contact Form-fält i payload. System prompts (LP + PA) anger att Contact Form-fält ALLTID ska emitteras vid UPDATE.
- `lib/airtable-schema-lp.md`, `lib/airtable-schema-pa.md` — dokumenterar de 15 Contact Form-fälten + Formatregel 9 (Contact Form-fält alltid inkluderade).
- `components/EditorPanel.tsx` (LP) — ny quick-nav-pill "Kontaktform" + sektion-panel som visar ContactFormEditor när `showContactForm`.
- `components/audience/AudienceBuilder.tsx` — samma mönster för Audience.
- `components/product-area/ProductAreaBuilder.tsx` — samma mönster för PA.

**Designbeslut:**
- **Delad ContactFormEditor + delad ContactFormPreview** används av alla fyra page-typerna utan duplicering. Wrappers per page-typ är tunna inline-blocks som mappar state.
- **Visibility-toggle inline i header** istället för i CollapsibleSection — alla tre LP/PA/Audience har lite olika visibility-konventioner så det är enklare att hålla helt explicit.
- **Audience har INGEN egen `contact_*`-fält** så `show_contact_person=true` faller tillbaka på SSOT-uppslag i renderaren (`Collections::coworkers_for_scope(['limit'=>1])`). Builder-state har bara `contactForm`-data, inte kontaktperson.
- **Befintliga "Visitkort"-sektioner (LP `contact_*`, PA `Contact *`)** lämnas oförändrade. Kontaktformuläret är ADDITIVT — visa antingen visitkort, kontaktform, eller båda.

**Manuella TODOs efter deploy:**
- Verifiera LP-editor: öppna en LP, gå till "Kontaktform"-panel, fyll i title/subtitle, spara → Airtable uppdaterad. Cache-bust → WP-sidan visar kontaktform med rätt värden.
- Verifiera att Claude-transform tappar inte Contact Form-fält vid UPDATE: ändra ett annat fält → publishera → kontrollera att Contact Form-värdena är oförändrade i Airtable.
- Befintliga LP utan Contact Form-värden → state.contactForm = emptyContactFormState(), showContactForm = false.

---

## Fas 8 — Per-plugin contact-form i LP/PA/Audience

**Status:** Klar.

**Airtable-mutationer (Wexoe-basen):**
- 15 `Contact Form *`-fält tillagda på `Landing Pages` (`tbl8KDqGq0Ray1uqS`).
- 15 fält på `Product Areas` (`tblgatNFYFMwF4EcQ`).
- 15 fält på `Audience Heroes`/`Customer types` (`tblvNf1CqAYEFvTpu`).

**Filer ändrade:**
- `wexoe-core/entities/landing_pages.php` — lade till `contact_form_*` domain-keys.
- `wexoe-core/entities/product_areas.php` — samma.
- `wexoe-core/entities/audience_heroes.php` — samma.
- `New plugins/wexoe-landing-page/wexoe-landing-page.php` — anrop till `wexoe_lp_render_contact_form_section()` sist i shortcode-render. Använder LP:s `color_main`/`color_secondary` som accent + skickar `contact_*` som kontaktperson om `show_contact_person=true`.
- `New plugins/wexoe-product-area/wexoe-product-area.php` — `wexoe_pa_render_contact_form_section()` följer samma mönster med PA:s hero_bg/hero_accent som färgkälla.
- `New plugins/wexoe-audience-hero/wexoe-audience-hero.php` — privat `render_contact_form_section()` på klass-instansen. Eftersom Audience saknar egna `contact_*`-fält faller kontaktperson tillbaka på `Collections::coworkers_for_scope(['limit'=>1])`.

**Designbeslut:**
- Alla wrappers gör `class_exists('\\Wexoe\\Core\\Renderers\\ContactForm')` guard — kontaktform aktiveras bara om Wexoe Core 0.9.0+ med Fas 7-koden är aktiverad. Bryter inte plugins om Core är gammal version.
- `<section id="kontakt">`-wrappar bevaras så befintliga `href="#kontakt"`-länkar fortsätter att fungera.
- Audience-wrappern sätter `style="width:100%"` för att neutralisera 100vw-trickets påverkan på kontaktformulär-sektionen (planens designbeslut).
- Audience-handlern är en metod på klassen (eftersom shortcode-handlern är inom klass), LP/PA är fristående funktioner (matchar deras befintliga mönster).

**Manuella TODOs efter deploy:**
- Verifiera LP med Show Contact Form=true → ny `<section id="kontakt">` renderas. Submit → ny rad i User data med `source_plugin=wexoe-landing-page`.
- Verifiera PA samma.
- Verifiera Audience: kontrollera att hero-marginaler (100vw) inte påverkas av kontaktform-sektion.

---

## Fas 7 — Core ContactForm-helper + AJAX-handler

**Status:** Klar.

**Filer skapade i `wexoeplugins/wexoe-core/`:**
- `src/Renderers/ContactForm.php` — full HTML/CSS/JS-renderare, CSS-prefix `wxcf-*`, scopad till uniqid. Split/centered-layout, dark/light-tema, kontaktperson-stöd, trust signals.
- `src/ContactForm/Handler.php` — `wxcf_submit` AJAX-handler: nonce-verifiering, honeypot (silent success), rate-limit (10/h/IP via transient), validering, anrop till `Core::submission('user_submissions')`.

**Filer skapade i `wexoebuilder/`:**
- `lib/contact-form-types.ts` — delade typer (`ContactFormState`, `emptyContactFormState()`).
- `components/contact-form/ContactFormEditor.tsx` — delad neutral editor med alla fält (kontekstabel via wrapper i LP/PA/Audience).
- `components/contact-form/ContactFormPreview.tsx` — delad visual skiss.

**Filer ändrade:**
- `src/Plugin.php` — registrerar `ContactForm\Handler::register()` på `init`-action.
- `components/UniquePageBuilder.tsx` — lade till "Kontakt" quick-nav-pill + ContactFormEditor-panel (wrappad i CollapsibleSection eftersom delade editorn är neutral).
- `components/unique-page/preview/UniquePagePreview.tsx` — renderar ContactFormPreview när `showContactForm=true`.

**Designbeslut:**
- AJAX-action heter `wxcf_submit` — explicit skild från legacy `wexoe_contact_submit` så Old-pluginen kan samexistera under migration.
- Honeypot returnerar `{success: true}` (silent success) — botar ska tro att de lyckats.
- Rate-limit-key inkluderar md5(IP) för att inte exponera IP i wp_options-namn.
- Email-validering: tom email eller is_email()=false → 422. Andra validerings-fel skickas inte detaljerat till klient (säkerhet).
- `WEXOE_TRUSTED_PROXY`-konstant aktiverar proxy-header-läsning (X-Forwarded-For, Cloudflare etc.) — annars REMOTE_ADDR.
- Inline `<style>`-block scopas till `#wxcf-<uniqid>` så stilarna inte läcker.
- ContactForm-renderaren accepterar både array (from lines-typ schema) och string (raw multilinetext) som värde — flexibel mot olika config-källor.
- wexoe-pages renderar redan kontaktformuläret (via class_exists-guard från Fas 6) — så Fas 7-kompletteringen automatiskt aktiverar Tier 2-kontaktform.

**Manuella TODOs efter deploy:**
- Verifiera POST till `/wp-admin/admin-ajax.php?action=wxcf_submit` returnerar 200 + ny rad i User data med `submission_type=contact`.
- Verifiera honeypot: skicka med `_hp=anything` → 200 success men ingen rad i Airtable.
- Verifiera rate-limit: skicka 11 submissions från samma IP → 11:e returnerar 429.
- Verifiera dubbla `<style>`-block om två kontaktform på samma sida (`wp_unique_id` vs `wp_generate_password` — använder password så det blir unika ID:n).

---

## Fas 6 — Core render-helpers + sektion-rendering

**Status:** Klar.

**Filer skapade i `wexoeplugins/wexoe-core/src/Renderers/`:**
- `Hero.php` — hero med eyebrow/h1/subtitle/CTA, dark/light-tema, bakgrundsbild.
- `TextImage.php` — text + bild-block, reversed-flagga för spegling, dark/light.
- `TextOnly.php` — ren text, left/center-justering.
- `Faq.php` — parsar `**Q** | A`-format, renderar `<details>`-list.
- `TeamGrid.php` — anropar Collections::coworkers_for_scope, fallback initials om ingen bild.
- `PartnersMarquee.php` — logo-rad, hover-effekt.
- `TestimonialCard.php` — featured-first-strategy, fallback till första matchande.
- `CtaBanner.php` — H2 + body + CTA-knapp.

Alla renderers följer samma kontrakt: `public static function render(array $config): string`. Returnerar tom sträng om data är otillräcklig (t.ex. tom title, tom items, tom Collections-resultat).

**Filer ändrade:**
- `src/Core.php` — ny `Core::renderer($type)`-facade returnerar fully-qualified-class-name. Inkluderar `contact-form` (förbereds för Fas 7).
- `New plugins/wexoe-pages/wexoe-pages.php` — full sektion-rendering i fast ordning: Hero → Text-image A → Text-image B → Text-only → FAQ → Team grid → Partners marquee → Testimonial card → CTA banner → Contact form (sista placeholder för Fas 7).
- `wexoe-pages.php` har också nya helpers: `wexoe_pages_resolve_scope()` (faller tillbaka på sidans Country/Division), `wexoe_pages_resolve_contact_person()`, `wexoe_pages_build_contact_form_config()`, `wexoe_pages_attachment_url()`.

**Designbeslut:**
- CSS injekteras inline med `<style>` per renderer — enkel kapsling, fungerar utan WP enqueue-flöde, kan flyttas till enqueue senare. CSS-prefix `wxr-<type>__` (Wexoe Renderer).
- Top-level H1 på sidan visas bara om Hero AV — undviker dubbel H1.
- Scope-fält fallback: tomt scope-fält → använd sidans Country/Division-länkar (via record_id → kod/slug-uppslag).
- TestimonialCard har featured-first-fallback: först försök med `featured_only=true`, om inget hittas → fall tillbaka på vanlig scope-matchning.
- ContactForm-rendering hoppas över i Fas 6 men anrops-koden finns redan i wexoe-pages.php (med `class_exists`-guard) — Fas 7 lägger till klassen så fungerar det automatiskt.
- Markdown-rendering via `\Wexoe\Core\Helpers\Markdown` om klassen finns (den finns sedan tidigare i Core).

**Manuella TODOs efter deploy:**
- Skapa team-grid-test: lägg coworker i `/globals/coworkers` med Country=SE, aktivera "Show Team Grid" på test-page med scope.country=SE → verifiera att grid visas.
- Verifiera CSS-prefix-isolering: `wxr-*` ska inte krocka med befintliga `wexoe-lp-*`, `wxp-*`.

---

## Fas 5 — `cms_unique_pages` + wexoe-pages-plugin (skelett)

**Status:** Klar.

**Airtable-mutationer:**
- Skapade tabell `cms_unique_pages` (`tblpAM1wZWDbrpeai`) i Wexoe NY med ~60 fält i grupper:
  - Metadata: Slug, H1, SEO Title, SEO Description, OG Image URL, Published, Country, Division.
  - Hero (8 fält): Show Hero + Eyebrow/H1 Override/Subtitle/Image/CTA Text/CTA URL/Theme.
  - Text-image A (6 fält), Text-image B (6 fält).
  - Text-only (4 fält): Show + H2/Body/Align.
  - FAQ (3 fält): Show + H2/Items.
  - Team grid (5 fält, SSOT-scope), Partners marquee (4 fält), Testimonial card (4 fält).
  - CTA banner (6 fält), Contact form (16 fält — speglar planens [5.2]).
- Skapade test-record: slug=`test-page`, H1=Testsida, Published=true, Country=[SE].

**Filer skapade i `wexoeplugins/`:**
- `wexoe-core/entities/cms_unique_pages.php` — read-entity-schema.
- `wexoe-core/write-entities/cms_unique_pages.php` — write-entity-schema.
- `wexoe-core/src/Constants.php` — `RESERVED_SLUGS` (`kontakt`, `nedladdningar`, `om-oss-statisk`).
- `New plugins/wexoe-pages/wexoe-pages.php` — skelett-plugin med `[wexoe_page slug="..."]`-shortcode + `wp_head`-SEO-meta-injektion.

**Filer skapade i `wexoebuilder/`:**
- `lib/unique-page-types.ts` — UniquePageState + alla sektion-state-typer.
- `lib/unique-page-mapper.ts` — bidirektional Airtable ↔ state mappning.
- `components/UniquePageBuilder.tsx` — använder BuilderShell, navi-pills för alla sektioner.
- `components/unique-page/editors/CollapsibleSection.tsx` — delad expand/toggle + inputs.
- `components/unique-page/editors/MetadataPanel.tsx` — Slug/H1/SEO/Country/Division.
- `components/unique-page/editors/HeroEditor.tsx`, `TextImageEditor.tsx`, `TextOnlyEditor.tsx`, `FaqEditor.tsx`, `TeamGridEditor.tsx`, `PartnersMarqueeEditor.tsx`, `TestimonialCardEditor.tsx`, `CtaBannerEditor.tsx`.
- `components/unique-page/preview/UniquePagePreview.tsx` — visuell skiss per sektion (inte pixelidentisk).
- `app/api/unique-page/route.ts` — CRUD + list, slug-validering, cache-invalidate.
- `app/editor/unique/page.tsx` — create.
- `app/editor/unique/[recordId]/page.tsx` — edit.

**Filer ändrade i `wexoebuilder/`:**
- `app/page.tsx` — "Unik sida" i AddPageDialog, fetch:ar `/api/unique-page?action=list`, ny `unique`-PageType, ny `editPath`.

**Designbeslut:**
- TextImageEditor återanvänds för både A och B (samma struktur, olika titel/state).
- CollapsibleSection-komponenten är en delad byggsten (header + expand + visibility-toggle) som alla sektion-editorer använder — undviker duplicering.
- Contact-form-panelen läggs i Fas 7 (kommer återanvända delad ContactFormEditor från Fas 7).
- wexoe-pages-pluginen är *skelett*: den läser cms_unique_pages, renderar `<article class="wxp-page"><h1>...</h1></article>` och kallar `do_action('wexoe_pages_render_sections', $page)` — sektion-rendering läggs i Fas 6 (via filter eller direkt utbyggnad).
- Slug-validering sker både i builder-route och CollapsibleSection-toolbar (regex + reserved-slugs).
- SEO-meta-injektion via `wp_head`-action upptäcker `[wexoe_page slug=]`-shortcode i `$post->post_content`. Polish i Fas 11 om bättre detektion behövs.

**Manuella TODOs efter deploy:**
- Aktivera `wexoe-pages`-pluginen i WP-admin.
- Lägg `[wexoe_page slug="test-page"]` på en WP-sida för att verifiera shortcoden returnerar `<article class="wxp-page"><h1>Testsida</h1></article>`.
- Verifiera att `kontakt` (reserved) avvisas i builder.
- Verifiera 409 vid duplicerad slug.

---

## Fas 4 — Builder: `/globals/*` för SSOT-redigering

**Status:** Klar.

**Filer skapade i `wexoebuilder/`:**
- `lib/core/registry.ts` — whitelist + tabell-IDs + roller per entity.
- `lib/core/types.ts` — TS-interfaces per entity.
- `lib/core/mapper.ts` — bidirektional Airtable record ↔ TS-objekt.
- `lib/core/forms.ts` — field-config per entity (driver UI).
- `lib/core/loader.ts` — server-side fetch.
- `lib/core/reserved-slugs.ts` — reserverade slugs (för Fas 5).
- `components/core/CoreEntityShell.tsx` — generisk list/edit-shell (header, list-vy, form-vy, save/delete).
- `components/core/CoreEntityForm.tsx` — generisk form från forms.ts (alla input-typer).
- `app/api/core/[entity]/route.ts` — generisk REST CRUD (GET/POST/PATCH/DELETE) som anropar Airtable direkt + invaliderar Wexoe Core cache efter mutation.
- `app/globals/page.tsx` — landningssida med 8 entity-kort grupperade på roll.
- `app/globals/[entity]/page.tsx` — dynamic route, server-fetchar records + link-options.

**Filer ändrade i `wexoebuilder/`:**
- `lib/airtable.ts` — alla helpers tar nu optional `baseId`-parameter (bakåtkompatibelt). Exporterar `SSOT_BASE_ID`.
- `lib/wexoe-cache.ts` — lade till `SSOT_ENTITIES` och `UNIQUE_PAGES_ENTITIES`-konstanter.
- `app/page.tsx` — "Globaler"-knapp i header.

**Designbeslut:**
- En `[entity]`-dynamic route istället för 8 statiska sidor — minskar duplicering. URL använder kebab-case (`/globals/core-company`) men entity-namn är `core_company` (segment-konvertering vid route-load).
- Generisk `CoreEntityForm` med field-config-driven rendering — adderar nya entiteter genom att uppdatera `forms.ts`, inte React-komponenter.
- Singleton-invariant valideras BÅDE i route (`/api/core/...`) OCH i Wexoe Core REST (Fas 3). Builder-routen anropar Airtable direkt, så core REST skyddar inte builder-write — därför dubbel validering.
- Bilder hanteras som URL i attachment-fält (Airtable attachment med `{url}`). MVP — uppgradera till uppladdning via WP Media senare.
- Auth hanteras av befintligt `proxy.ts` middleware — ingen extra check behövs i route.

**Manuella TODOs efter deploy:**
- Verifiera att `/globals` listar 8 entitet-kort med korrekta records-räkningar.
- Verifiera CRUD-cykel: ändra `core_company.wexoe-se` Phone, spara, läs in igen.
- Verifiera 409-fel vid två is_default på core_company.
- Polish: replace multi-select med Combobox-komponent för link-fält senare.

---

## Fas 3 — Wexoe Core: REST CRUD för SSOT

**Status:** Klar.

**Filer skapade i `wexoeplugins/wexoe-core/`:**
- `write-entities/core_company.php`
- `write-entities/core_graphic_profile.php`
- `write-entities/core_countries.php`
- `write-entities/core_divisions.php`
- `write-entities/core_customer_types.php`
- `write-entities/core_coworkers.php`
- `write-entities/core_partners.php`
- `write-entities/core_testimonials.php`
- `src/EntityRestApi.php` — generisk REST CRUD med whitelist + singleton-validering.

**Filer ändrade:**
- `src/AirtableClient.php` — ny `delete_record($table_id, $record_id, $base_id)` med retry-logik.
- `src/WriteRepository.php` — ny `delete($record_id)`-metod.
- `src/Core.php` — `Core::submission()` läser nu `base_id` ur write-entity-config och passerar till WriteRepository.
- `src/Plugin.php` — registrerar EntityRestApi-routes på `rest_api_init`.

**Designbeslut:**
- Återanvänder befintlig `RestApi::check_secret()` som permission_callback — samma webhook-secret skyddar både `/cache/clear` och `/entity/*`.
- Whitelist `CORE_EDITABLE_ENTITIES` hindrar att man av misstag PATCH:ar `landing_pages` etc. via denna route — sid-tabeller skrivs via egna routes.
- Singleton-invariant valideras vid POST/PATCH: om `is_default=true` försöks sättas → kollar att inget annat record redan har det och returnerar 409 Conflict om så är fallet.
- Cache invalideras automatiskt efter mutation (`$repo->clear_cache()` + `Context::reset()` när country/division/company ändras).
- `cms_unique_pages` är förinkluderad i whitelist trots att schemat skapas i Fas 5 — route-handlern returnerar 404 för entiteten tills schemat finns. Detta gör att Fas 5 kan piggyback på samma route.

**Manuella TODOs efter deploy:**
- Sätt `wexoe_core_webhook_secret`-option i WP (Verktyg → Webhook) om inte redan satt.
- Verifiera 401 på `/entity/core_company` utan secret, 200 med rätt secret.
- Verifiera 403 på `/entity/landing_pages` (inte i whitelist).
- Verifiera 409 vid två `core_company` med `is_default=true`.

---

## Fas 2 — Wexoe Core: SSOT-scheman + Helpers

**Status:** Klar.

**Filer skapade i `wexoeplugins/wexoe-core/`:**
- `entities/core_company.php` — singleton, primary_key=slug, base_id=SSOT_BASE_ID, inkluderar Hours-fält.
- `entities/core_graphic_profile.php` — singleton, primary_key=slug.
- `entities/core_countries.php` — taxonomi, primary_key=code.
- `entities/core_divisions.php` — taxonomi, primary_key=slug.
- `entities/core_customer_types.php` — taxonomi, primary_key=slug.
- `entities/core_coworkers.php` — collection, primary_key=full_name.
- `entities/core_partners.php` — collection, primary_key=name.
- `entities/core_testimonials.php` — collection, primary_key=internal_name.
- `src/Helpers/Context.php` — country-detection (Domain → URL Prefix → default).
- `src/Helpers/Singletons.php` — company_for_country, graphic_profile_for_division.
- `src/Helpers/Collections.php` — coworkers/partners/testimonials_for_scope.

**Filer ändrade i `wexoeplugins/wexoe-core/`:**
- `src/Plugin.php` — lade till `Plugin::SSOT_BASE_ID = 'appokKSTaBdCa8YiW'`-konstant.
- `src/EntityRepository.php` — ny `get_base_id()`-metod + skickar base_id till AirtableClient i `fetch_from_airtable_and_cache()`. Bakåtkompatibelt: scheman utan `base_id` använder fortfarande `Plugin::get_base_id()`.

**Designbeslut:**
- `base_id`-fält per entity-schema istället för att lägga `core_*`-tabellerna i samma bas som sid-data — speglar planens två-bas-modell. Bakåtkompatibelt eftersom default-fallback bevarats.
- `Singletons::company_for_country()` accepterar code (`'SE'`), record_id (`recXXX`), eller null (auto-resolve via Context).
- `Collections::*` filtrerar `active=true` automatiskt och behandlar tom scope-länk som "global synlighet".
- `Context::current_division_slug()` returnerar null tills URL-regler är beslutade — placeholder.

**Manuella TODOs efter deploy:**
- Verifiera att SSOT-fetcher fungerar mot Wexoe NY-basen efter att WP-options `wexoe_core_airtable_api_key` har access till båda baser (samma PAT/Bot om Personal Access Token är scopad rätt). Annars: ge token access till båda.

---

## Fas 1 — Airtable-städning

**Status:** Klar (justerad scope).

**Airtable-mutationer (`Wexoe NY` `appokKSTaBdCa8YiW`):**

Fält tillagda på `core_company` (`tblwq9y74ertsNyYG`):
- `Hours Mon-Fri` (`fldBmusOTUKSUiZvc`) — singleLineText
- `Hours Saturday` (`fldfN3rr2ys4ZY9sO`) — singleLineText
- `Hours Sunday` (`fldAILS1dpw4ZMXKe`) — singleLineText
- `Hours Lunch` (`fldy9ZjV5tEniBheW`) — singleLineText
- `Hours Override` (`fld3ytF1qACOVtbSR`) — multilineText

Initial-data skapad:
- `core_countries`: SE-record `rec7N339jnJ17sfTy` (Sverige, Code=SE, Domain=wexoe.se, Active=true).
- `core_divisions`: Industri `recN7qh6VJ8vqxTjJ`, Automation `recEjCdWID8v09l0S`, Kassasystem `recOd94pVsu2GPyGw` — alla länkade till SE.
- `core_customer_types`: Industri `recbUXwpxAPBv5z4N`, Bygg `rec1ykIq5VQpQmGAu`, Offentlig sektor `recN5Wu5W0AEYEozy`.
- `core_company`: default-record `recXm5P5hMCA3Z4ye` (slug=wexoe-se, Is Default=true, Country=[SE]).
- `core_graphic_profile`: default-record `recA2iukzv8uFbsPk` (slug=default, Is Default=true).

**Hoppade över:**
- Radering av cms_*-placeholder-tabeller (per användarens önskemål — de migreras senare).
- Cleanup av default-mall-fält i core_*-tabeller (kollade — de finns redan inte i nuvarande schema).

**Manuella TODOs efter deploy:**
- Fyll i `core_company.wexoe-se` med faktiska värden: Company Name, Email, Phone, Org Number, VAT Number, adress, sociala medier, hours.
- Fyll i `core_graphic_profile.default` med färger, loggor, typsnitt.

---

## Fas 0 — BuilderShell-extraktion

**Status:** Klar.

**Filer skapade/ändrade i `wexoebuilder/`:**
- `components/BuilderShell.tsx` (ny) — delad shell-komponent med toolbar, quick-nav, split-layout, scroll-sync.
- `components/audience/AudienceBuilder.tsx` (refaktor) — använder BuilderShell istället för inline-plumbing.

**Designbeslut:**
- BuilderShell tar emot `toolbar.left/middle/right` som slots istället för att ha hårdkodat slug-input. Detta gör shellen återanvändbar för `/globals/*` (där toolbar är annorlunda — ingen slug, bara titel).
- `editorSections` prop tar emot en render-funktion som ger sectionRef-setter; subreps får använda samma ref-mekanism för scroll-sync utan att duplicera plumbing.
- LP och PA refaktoreras inte i denna fas (opportunistiskt senare när vi ändå rör dem). Bara Audience (minsta) görs som proof-of-concept.

**Manuella TODOs efter deploy:**
- Verifiera att Audience-editor fungerar likadant som före refaktor: skapa/läs in audience-sida, alla 4 paneler kan navigeras via quick-nav, scroll-sync fungerar.

---
