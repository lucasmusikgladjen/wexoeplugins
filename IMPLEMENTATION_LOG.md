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

## Refaktor — Bilder via WP Media URL (2026-05-14)

**Branch:** `claude/airtable-image-to-wp-urls-W1fzm`
**Status:** Klar.

**Syfte:** Byt 12 bild-fält från `multipleAttachments` → `singleLineText` URL i Airtable Wexoe NY. Bilderna pekar nu på WP Media Library (wp-content), inte Airtable-uppladdningar.

**Airtable-mutationer (Wexoe NY `appokKSTaBdCa8YiW`):**
- `core_graphic_profile` (tbl4c4HjiKVCcJI5v): Logo Primary, Logo Dark Background, Favicon → singleLineText.
- `core_customer_types` (tblLsYRMZz6JA6GBK): Icon → singleLineText.
- `core_coworkers` (tblYwMQlW9HFd41pg): Image → singleLineText.
- `core_partners` (tblZ5YIYFelxA0nBm): Logo, Logo Transparent → singleLineText.
- `core_testimonials` (tbl1pe0bWz5zdkqJF): Author Image → singleLineText.
- `cms_unique_pages` (tblpAM1wZWDbrpeai): Hero Image, Text Image A Image, Text Image B Image → singleLineText.

OBS: Airtable MCP-servern saknar `delete_field`-endpoint. Gamla attachment-fält döptes till `[old]` (t.ex. "Logo Primary [old]"). Radera dem manuellt i Airtable UI.

**Filer ändrade i `wexoeplugins/`:**
- `wexoe-core/entities/core_graphic_profile.php` — logo_primary, logo_dark_background, favicon: tog bort `'type' => 'attachment'`, plain string-keys.
- `wexoe-core/entities/core_customer_types.php` — icon: plain string-key.
- `wexoe-core/entities/core_coworkers.php` — image: plain string-key.
- `wexoe-core/entities/core_partners.php` — logo, logo_transparent: plain string-keys.
- `wexoe-core/entities/core_testimonials.php` — author_image: plain string-key.
- `wexoe-core/entities/cms_unique_pages.php` — hero_image_url → hero_image, text_image_a_image_url → text_image_a_image, text_image_b_image_url → text_image_b_image (tog bort _url-suffix + attachment-typ).
- `wexoe-core/write-entities/core_graphic_profile.php` — tog bort attachment_url-entries ur field_types.
- `wexoe-core/write-entities/core_customer_types.php` — tog bort attachment_url-entry ur field_types.
- `wexoe-core/write-entities/core_coworkers.php` — tog bort attachment_url-entry ur field_types.
- `wexoe-core/write-entities/core_partners.php` — tog bort attachment_url-entries ur field_types.
- `wexoe-core/write-entities/core_testimonials.php` — tog bort attachment_url-entry ur field_types.
- `wexoe-core/write-entities/cms_unique_pages.php` — tog bort attachment_url-entries + bytte keys (hero_image_url → hero_image, text_image_a_image_url → text_image_a_image, text_image_b_image_url → text_image_b_image).
- `wexoe-core/src/Renderers/TeamGrid.php` — $img is_array-gren ersatt med `(string) ($c['image'] ?? '')` cast.
- `wexoe-core/src/Renderers/PartnersMarquee.php` — $logo is_array-gren ersatt med `(string) ($p['logo'] ?? '')` cast.
- `wexoe-core/src/Renderers/TestimonialCard.php` — $author_image is_array-gren ersatt med `(string) ($t['author_image'] ?? '')` cast.
- `New plugins/wexoe-pages/wexoe-pages.php` — `wexoe_pages_attachment_url()`-helpern raderad; hero_image/text_image_a_image/text_image_b_image används direkt; contact_person image hämtas nu som plain string.

**Filer ändrade i `wexoebuilder/`:**
- `lib/core/mapper.ts` — `firstAttachmentUrl()` raderad; readGraphicProfile/readCustomerType/readCoworker/readPartner/readTestimonial använder `asString()`; `writeAttachment()` raderad; write-funktioner använder `cleanField()` direkt.
- `lib/unique-page-mapper.ts` — `attachUrl()` + `attachInput()` raderade; Hero Image/Text Image A+B Image läses med `asString()` och skrivs som plain string (null om tom).

**Designbeslut:**
- Airtable `delete_field`-API saknas i MCP-servern → gamla attachment-fält döptes till `[old]` istället för radering. Radera manuellt i Airtable UI för att städa upp.
- TS-state-properties behåller `imageUrl`-namn (inte `image`) — camelCase-konvention, matchar `HeroState`/`TextImageState`-interfaces.

**Verifiering:**
- `grep -r 'attachment' wexoe-core/entities/ wexoe-core/write-entities/ wexoe-core/src/Renderers/ 'New plugins/wexoe-pages/'` → 0 träffar.
- `grep -r 'firstAttachmentUrl\|writeAttachment\|attachUrl\|attachInput' lib/` → 0 träffar.
- PHP-renderers: inga `is_array`-grenar för bild-variabler.
- TS: `cleanField(s.logo_primary)` returnerar null för tom sträng — Airtable rensar fältet vid update.

**Manuella TODOs efter deploy:**
1. Radera `[old]`-fälten i Airtable UI (12 st): Logo Primary [old], Logo Dark Background [old], Favicon [old] i core_graphic_profile; Icon [old] i core_customer_types; Image [old] i core_coworkers; Logo [old], Logo Transparent [old] i core_partners; Author Image [old] i core_testimonials; Hero Image [old], Text Image A Image [old], Text Image B Image [old] i cms_unique_pages.
2. Fyll i WP Media Library-URL:er i de nya URL-fälten i Airtable för att aktivera bilder.
3. Verifiera att TeamGrid, PartnersMarquee, TestimonialCard renderar korrekt med en coworker/partner/testimonial som har URL i Image-fältet.

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
- `New plugins/wexoe-landing-page/wexoe-landing-page.php` — anrop till `wexoe_lp_render_contact_form_section()` sist i shortcode-render.
- `New plugins/wexoe-product-area/wexoe-product-area.php` — `wexoe_pa_render_contact_form_section()` följer samma mönster.
- `New plugins/wexoe-audience-hero/wexoe-audience-hero.php` — privat `render_contact_form_section()` på klass-instansen.

**Designbeslut:**
- Alla wrappers gör `class_exists('\\Wexoe\\Core\\Renderers\\ContactForm')` guard.
- `<section id="kontakt">`-wrappar bevaras så befintliga `href="#kontakt"`-länkar fortsätter att fungera.

**Manuella TODOs efter deploy:**
- Verifiera LP med Show Contact Form=true → ny `<section id="kontakt">` renderas.
- Verifiera PA samma.
- Verifiera Audience: kontrollera att hero-marginaler (100vw) inte påverkas.

---

## Fas 7 — Core ContactForm-helper + AJAX-handler

**Status:** Klar.

**Filer skapade i `wexoeplugins/wexoe-core/`:**
- `src/Renderers/ContactForm.php`
- `src/ContactForm/Handler.php`

**Filer skapade i `wexoebuilder/`:**
- `lib/contact-form-types.ts`
- `components/contact-form/ContactFormEditor.tsx`
- `components/contact-form/ContactFormPreview.tsx`

**Filer ändrade:**
- `src/Plugin.php` — registrerar `ContactForm\Handler::register()` på `init`-action.
- `components/UniquePageBuilder.tsx` — lade till "Kontakt" quick-nav-pill + ContactFormEditor-panel.
- `components/unique-page/preview/UniquePagePreview.tsx` — renderar ContactFormPreview när `showContactForm=true`.

**Manuella TODOs efter deploy:**
- Verifiera POST till `/wp-admin/admin-ajax.php?action=wxcf_submit` returnerar 200.
- Verifiera honeypot: skicka med `_hp=anything` → 200 success men ingen rad i Airtable.
- Verifiera rate-limit: 11 submissions → 11:e returnerar 429.

---

## Fas 6 — Core render-helpers + sektion-rendering

**Status:** Klar.

**Filer skapade i `wexoeplugins/wexoe-core/src/Renderers/`:**
- `Hero.php`, `TextImage.php`, `TextOnly.php`, `Faq.php`, `TeamGrid.php`, `PartnersMarquee.php`, `TestimonialCard.php`, `CtaBanner.php`.

**Filer ändrade:**
- `src/Core.php` — ny `Core::renderer($type)`-facade.
- `New plugins/wexoe-pages/wexoe-pages.php` — full sektion-rendering i fast ordning.

**Manuella TODOs efter deploy:**
- Verifiera team-grid med coworker i `/globals/coworkers`.
- Verifiera CSS-prefix-isolering.

---

## Fas 5 — `cms_unique_pages` + wexoe-pages-plugin (skelett)

**Status:** Klar.

**Airtable-mutationer:**
- Skapade tabell `cms_unique_pages` (`tblpAM1wZWDbrpeai`) i Wexoe NY.
- Skapade test-record: slug=`test-page`, H1=Testsida, Published=true, Country=[SE].

**Filer skapade:**
- `wexoe-core/entities/cms_unique_pages.php`
- `wexoe-core/write-entities/cms_unique_pages.php`
- `wexoe-core/src/Constants.php`
- `New plugins/wexoe-pages/wexoe-pages.php`
- `lib/unique-page-types.ts`, `lib/unique-page-mapper.ts`, `components/UniquePageBuilder.tsx` m.fl.

**Manuella TODOs efter deploy:**
- Aktivera `wexoe-pages`-pluginen i WP-admin.
- Lägg `[wexoe_page slug="test-page"]` på en WP-sida för att verifiera shortcoden.

---

## Fas 4 — Builder: `/globals/*` för SSOT-redigering

**Status:** Klar.

**Filer skapade i `wexoebuilder/`:**
- `lib/core/registry.ts`, `lib/core/types.ts`, `lib/core/mapper.ts`, `lib/core/forms.ts`, `lib/core/loader.ts`, `lib/core/reserved-slugs.ts`.
- `components/core/CoreEntityShell.tsx`, `components/core/CoreEntityForm.tsx`.
- `app/api/core/[entity]/route.ts`, `app/globals/page.tsx`, `app/globals/[entity]/page.tsx`.

**Manuella TODOs efter deploy:**
- Verifiera `/globals` listar 8 entitet-kort.
- Verifiera CRUD-cykel på core_company.

---

## Fas 3 — Wexoe Core: REST CRUD för SSOT

**Status:** Klar.

**Filer skapade i `wexoeplugins/wexoe-core/`:**
- write-entities för alla 8 core-entiteter.
- `src/EntityRestApi.php`.

**Filer ändrade:**
- `src/AirtableClient.php`, `src/WriteRepository.php`, `src/Core.php`, `src/Plugin.php`.

**Manuella TODOs efter deploy:**
- Sätt `wexoe_core_webhook_secret`-option i WP.
- Verifiera 401/200/403/409-svar på /entity/-routes.

---

## Fas 2 — Wexoe Core: SSOT-scheman + Helpers

**Status:** Klar.

**Filer skapade i `wexoeplugins/wexoe-core/`:**
- Entities för alla 8 core-entiteter.
- `src/Helpers/Context.php`, `src/Helpers/Singletons.php`, `src/Helpers/Collections.php`.

**Filer ändrade:**
- `src/Plugin.php` — `Plugin::SSOT_BASE_ID`-konstant.
- `src/EntityRepository.php` — `get_base_id()` + base_id-stöd.

**Manuella TODOs efter deploy:**
- Verifiera att SSOT-fetcher fungerar mot Wexoe NY-basen.

---

## Fas 1 — Airtable-städning

**Status:** Klar (justerad scope).

**Airtable-mutationer (`Wexoe NY` `appokKSTaBdCa8YiW`):**
- Hours-fält tillagda på `core_company`.
- Initial-data: core_countries (SE), core_divisions (3 st), core_customer_types (3 st), core_company (default), core_graphic_profile (default).

**Manuella TODOs efter deploy:**
- Fyll i `core_company.wexoe-se` med faktiska värden.
- Fyll i `core_graphic_profile.default` med färger, loggor, typsnitt.

---

## Fas 0 — BuilderShell-extraktion

**Status:** Klar.

**Filer skapade/ändrade i `wexoebuilder/`:**
- `components/BuilderShell.tsx` (ny).
- `components/audience/AudienceBuilder.tsx` (refaktor).

**Manuella TODOs efter deploy:**
- Verifiera att Audience-editor fungerar likadant som före refaktor.

---

## Wexoe ALB Blocks — Avia Layout Builder-element (2026-05-14)

**Branch:** `claude/alb-blocks-development-TKF1u`
**Plan:** `UTVECKLINGSPLAN-ALB-BLOCKS.md`
**Status:** Klar (fas 1, alt. A: förladdad lista + JS-filter).
**Testat mot Enfold-version:** Ej verifierat i WP — pinna versionsnummer efter första deploy.

**Syfte:** Ersätta manuella `[wexoe_page slug="..."]`-shortcodes i Code Block med ett riktigt ALB-element ("Wexoe Content") som har två dropdowns: innehållstyp + post.

**Filer skapade i `New plugins/wexoe-alb-blocks/`:**
- `wexoe-alb-blocks.php` — bootstrap, dependency checks (`wexoe-core` + Enfold), `avia_register_shortcodes`-hook, frontend shortcode-fallback, `admin_enqueue_scripts`.
- `includes/content-types.php` — filtrerbar `wexoe_alb_content_types()`-registry, generisk `wexoe_alb_list_by_slug()`, render-dispatch `wexoe_alb_render()`, fyra render-wrappers.
- `includes/class-wexoe-content-block.php` — `Wexoe_Content_Block extends aviaShortcodeTemplate` (popup_elements, editor_element, shortcode_handler).
- `assets/builder.js` — delegerad change-lyssnare + MutationObserver för att filtrera `content_id`-options på vald `content_type`. Optionernas value har formatet `{type}:{slug}`.
- `assets/builder-icon.svg` — enkel ikon för builder-modalen.

**Designbeslut på vägen:**
- **Scope fas 1:** fyra entiteter med slug-primärnyckel och befintlig render-funktion: `cms_unique_pages`, `landing_pages`, `audience_heroes`, `product_areas`. `automation_pillar` (samling av fem shortcodes utan egen entitet) och `partners`/`contact_page` (saknar slug-baserad render) hoppas över i denna fas.
- **Wrap-funktioner i registry:** varje typ har en egen render-callback i `content-types.php` som anpassar API-skillnader (rått slug vs `['slug' => $slug]`-array vs do_shortcode för klassmetoder).
- **Dropdown-strategi:** alt. A enligt plan (alla optioner förladdade + JS-filter). Migration till alt. B (AJAX) först om totala antalet poster överstiger ~500.
- **Prefix-format `{type}:{raw_id}`:** löser slug-kollisioner mellan entiteter. `shortcode_handler` strippar prefixet före render — råa IDs fungerar fortfarande (bakåtkompat).
- **Frontend-shortcode-fallback:** `[wexoe_content]` registreras alltid via `init`-hook, även när Enfolds builder-pipeline inte är aktiv (t.ex. AMP, REST-render).

**Manuella TODOs efter deploy:**
- Aktivera pluginet i WP-admin på sajt med Enfold + wexoe-core.
- Verifiera att "Wexoe Content"-ikonen visas under "Content Elements" i ALB.
- Klicka "Update Avia Templates" i Enfold → Theme Options om modalen inte visar nya fälten (Avia cachar element-struktur).
- Testa att båda dropdowns fungerar och att `content_id`-listan filtreras när `content_type` byts.
- Skapa testsida som renderar varje entitetstyp och jämför HTML mot motsvarande manuella shortcode.
- Pinna faktisk Enfold-version här i loggen efter första deploy.

---
