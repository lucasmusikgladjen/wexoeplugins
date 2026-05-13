# Feature: Integrerat kontaktformulär per plugin

**Status:** Förslag — ej beslutat
**Branch:** `claude/review-contact-plugins-4jqRS` (wexoeplugins + wexoebuilder)
**Författare:** Claude (genomgång av kodbasen 2026-05-13)
**Berör:** `wexoe-core`, `wexoe-landing-page`, `wexoe-product-area`, `wexoe-audience-hero`, `wexoebuilder`
**Ersätter (på sikt):** `Old plugins/wexoe-contact-form.php`

---

## 1. Sammanfattning

Idag finns ett separat plugin `wexoe-contact-form.php` (`Old plugins/`) som registrerar shortcoden `[wexoe_contact_form]`. Den används genom att en redaktör i WordPress lägger in shortcoden i botten av varje sida och döper själva WP-sektionen till "kontakt" så att `#kontakt`-länkar scrollar dit. Pluginen är hårdkodad: titel, etiketter, dropdown-alternativ och färger är PHP-konstanter, och submissions går till en Make.com-webhook med fast URL.

Målet är att integrera kontaktformuläret direkt i `wexoe-landing-page`, `wexoe-product-area` och `wexoe-audience-hero`, hämta dess innehåll dynamiskt via Airtable (precis som resten av Wexoe-pluginsen), göra det redigerbart i Wexoe Builder och låta utseendet variera per sida — utan att duplicera HTML/CSS/JS i varje plugin.

**Kortrekommendation:** *Lägg renderaren, AJAX-handlern och submissions-skrivningen i `wexoe-core`. Lägg konfigurationsfälten per sida i respektive entity-schema. Låt feature-pluginsen göra ett enda funktionsanrop. Editera via en ny "Kontaktformulär"-sektion i Builderns tre stilar.*

---

## 2. Nuläge

### 2.1 Det gamla `wexoe-contact-form.php`

- Single class `WexoeContactForm` med shortcoden `[wexoe_contact_form]`.
- Hårdkodade defaults: titel "Prata med någon som kan automation", tre trust-signals, sex dropdown-alternativ.
- Inverted/non-inverted-läge via attribut `inverted="true"`.
- AJAX-endpoint `wp_ajax_wexoe_contact_submit` + `_nopriv_` motsvarighet, validerar nonce, sanerar fält, POSTar JSON till en fast webhook (`https://hook.eu1.make.com/sulae2u3lux9g9dqfabtsdngiwz46s6g`).
- Ingen kopplad till Airtable, ingen audit trail i WP, inget revisionerbart utan att redigera koden.
- CSS-prefix `wexoe-contact-*`, scopad via `#wexoe-contact-{uniqid}`.

### 2.2 Vad LP/PA/Audience har idag

Subagent-genomgång bekräftar att **inget av de tre pluginsen renderar ett kontaktformulär idag**. Däremot har två av dem en "kontaktperson"-sektion (visitkort med foto + citat):

| Plugin | Funktion | CSS-prefix | Anchor | Bakgrund | Text genom |
|---|---|---|---|---|---|
| `wexoe-landing-page` v2.1.0 | `wexoe_lp_test_render_contact()` | `wexoe-lp-*` | nej | vit, mobil-flip till `--lp-main` | `esc_html` (plain) |
| `wexoe-product-area` v3.0.1 | `wexoe_pa_test_render_our_guy()` | `wexoe-pa-*` | nej | inline `--contact-bg` per record (full-bredd) | `Markdown::to_inline` |
| `wexoe-audience-hero` v2.0.1 | — | `wah-*` | — | — | — |

Båda kontaktperson-sektionerna saknar `id="kontakt"`. Det betyder att dagens `href="#kontakt"`-knappar fungerar enbart eftersom redaktören har döpt en *yttre* WP-sektion till "kontakt" — sektionen som omsluter `[wexoe_contact_form]`-shortcoden. När vi flyttar in formuläret i pluginsen måste vi själva sätta `id="kontakt"` på vår yttre `<section>`-tagg.

### 2.3 Wexoe Core som datalager

Alla tre feature-plugins är redan migrerade till `\Wexoe\Core\Core::entity('...')` för läsning. Skrivning är också redan stöttad:

- `Core::submission('user_submissions')->create_mapped([...])` skriver till Airtable-tabellen `User data` (`tblxrwMhSysupcDwe`).
- Domänfält finns redan: `email`, `name`, `company`, `phone`, `submission_type`, `submitted_at`, `page_slug`, `page_url`, `source_plugin`, `message`, `newsletter_consent`, `extra` (JSON-spill).
- `submission_type` `singleSelect` har redan värdet `contact` definierat enligt `write-entities/user_submissions.php`.

→ Vi behöver alltså **inte** röra Airtable-skrivlagret. Det räcker att låta den nya renderaren anropa `Core::submission('user_submissions')->create_mapped([...])`.

### 2.4 Wexoe Builder

Builderns kodbas följer redan ett tydligt mönster: en `<Type>Builder.tsx` med rutt-vis state, ett `<Type>PreviewPanel.tsx` med refs per sektion, och `editors/` + `preview/`-mappar per sektion. Alla tre typer (LP, PA, Audience) har redan en `ContactEditor` + `ContactPreview` för kontaktperson-kortet. Lägga till en *ny* "Kontaktformulär"-sektion följer samma mönster. Publish-flödet skickar redan en `entities`-lista till `WEXOE_CORE_WEBHOOK_URL` för att invalidera cachen.

---

## 3. Mål

1. **Maintenance-DRY:** En ändring av kontaktformulärets HTML/CSS/JS ska räcka för att uppdatera alla tre pluginsen.
2. **#kontakt-länkar fortsätter fungera:** Inga sidor får sluta scrolla till formuläret efter migrationen.
3. **Dynamisk innehållskälla:** Titel, underrubrik, trust-signals, dropdown-alternativ och CTA hämtas från Airtable per sida.
4. **Submissions till Airtable, inte Make.com:** Vi äger datat och kan svara redaktören. Make-automationer kan triggers via Airtable-automation eller behållas tills allt fungerar.
5. **Anpassningsbarhet per sida:** Centrerad rubrik vs. split-layout, valfria fält (telefon/företag), valfri kontaktperson bredvid, mörk/ljus variant.
6. **Editerbart i Builder:** Varje page-typ får en ny "Kontaktformulär"-sektion i editorn med live preview.

---

## 4. Föreslagen arkitektur

### 4.1 Översikt

```
┌─────────────────────────────────────────────────────────────────┐
│ wexoe-core (PHP)                                                │
│                                                                 │
│   src/ContactForm/Renderer.php   ← renderar HTML/CSS/JS         │
│   src/ContactForm/Handler.php    ← AJAX-handler, validering     │
│   src/ContactForm/Config.php     ← normaliserad config-DTO      │
│   Core::contact_form()->render($config)                         │
│   Core::contact_form()->handle_ajax()  (registreras vid boot)   │
└─────────────────────────────────────────────────────────────────┘
                ▲
                │ anropas av
                │
┌─────────────────────────────────────────────────────────────────┐
│ wexoe-landing-page  │  wexoe-product-area  │  wexoe-audience-hero │
│                                                                 │
│   Läser contact_form_* från sin egen entity (Core::entity())    │
│   Bygger en $config-array                                       │
│   Skriver  echo \Wexoe\Core\Core::contact_form()->render($cfg); │
│   Wrappar i  <section id="kontakt">  för anchor                 │
└─────────────────────────────────────────────────────────────────┘
                ▲
                │ Airtable-fälten redigeras via
                │
┌─────────────────────────────────────────────────────────────────┐
│ wexoebuilder (Next.js)                                          │
│                                                                 │
│   components/<type>/editors/ContactFormEditor.tsx               │
│   components/<type>/preview/ContactFormPreview.tsx              │
│   <type>-types.ts utökas med contactForm-fält                   │
│   page-mapper / audience-mapper / product-area-mapper utökas    │
└─────────────────────────────────────────────────────────────────┘
```

### 4.2 Lager 1 — Delad PHP-renderare i `wexoe-core`

Ny modul `wexoe-core/src/ContactForm/`:

- **`Renderer.php`** — public statisk metod `render(array $config): string`. Hela HTML+CSS+JS, scopad med `wxcf-{uniqid}`. Eget CSS-prefix `wxcf-*` (Wexoe Contact Form) så att den **inte** ärver eller krockar med `wexoe-lp-*` / `wexoe-pa-*` / `wah-*`. Tar emot redan-normaliserad config.
- **`Handler.php`** — registrerar `wp_ajax_wexoe_contact_submit` + `_nopriv_`, validerar nonce, sanerar input, anropar `Core::submission('user_submissions')->create_mapped()` med `submission_type = 'contact'`. Returnerar JSON (success/error) precis som gamla pluginet, så frontend-JS-kontraktet bevaras.
- **`Config.php`** — DTO/normaliserare som tar en lös array (från entity-data) och fyller i defaults. Centralt ställe för "vad betyder en kontaktforms-konfig".

Boota i `Plugin::boot()`:

```php
add_action('init', [\Wexoe\Core\ContactForm\Handler::class, 'register']);
```

Publik facade utökas:

```php
// src/Core.php
public static function contact_form() {
    return ContactForm\Renderer::class;
}
```

Feature-plugin anropar:

```php
echo \Wexoe\Core\Core::contact_form()::render($config);
```

### 4.3 Lager 2 — Entity-scheman utökas

Lägg till ett gemensamt `contact_form_*`-fältblock i tre scheman. Föreslagen minimum-uppsättning:

| Domänfält | Typ | Airtable-fältnamn | Användning |
|---|---|---|---|
| `contact_form_show` | bool | `Show Contact Form` | Default `false`. Om `true`, rendera sektionen. |
| `contact_form_eyebrow` | string | `Contact Form Eyebrow` | Liten label ovanför H2 (valfri). |
| `contact_form_title` | string | `Contact Form Title` | H2. Default = "Prata med någon som kan automation". |
| `contact_form_subtitle` | string | `Contact Form Subtitle` | Brödtext under H2 (valfri). |
| `contact_form_layout` | string (singleSelect) | `Contact Form Layout` | `split` (default) eller `centered`. |
| `contact_form_theme` | string (singleSelect) | `Contact Form Theme` | `dark` (mörk bg = default) eller `light`. |
| `contact_form_show_company` | bool | `Contact Form Show Company` | Default `true`. |
| `contact_form_show_phone` | bool | `Contact Form Show Phone` | Default `true`. |
| `contact_form_show_dropdown` | bool | `Contact Form Show Dropdown` | Default `true`. |
| `contact_form_dropdown_label` | string | `Contact Form Dropdown Label` | "Vad kan vi hjälpa dig med?" |
| `contact_form_options` | lines | `Contact Form Options` | En option per rad. Tom = fall tillbaka på Core-default. |
| `contact_form_cta_text` | string | `Contact Form CTA Text` | Default "Skicka". |
| `contact_form_message_label` | string | `Contact Form Message Label` | "Berätta mer (valfritt)" |
| `contact_form_trust_signals` | lines | `Contact Form Trust Signals` | Format `**Bold del** \| Resten av meningen`, en per rad. Max 3. |
| `contact_form_show_contact_person` | bool | `Contact Form Show Contact Person` | Om `true` använd sidans befintliga `contact_*`-fält som visitkort bredvid formuläret. |

Detta är **denormaliserat** — vi duplicerar samma kolumner i tre tabeller. Det är medvetet (se avsnitt 8.1 för alternativet).

### 4.4 Lager 3 — Per-plugin integration

Varje feature-plugin lägger till en mycket tunn renderare som mappar entity-fälten till delad config och skriver ut sektionen sist (eller på den plats den hör hemma — i Audience är det första gången sektionen finns över huvud taget, i LP/PA ersätter den eller kompletterar dagens kontaktperson-kort).

Pseudokod (landing-page):

```php
function wexoe_lp_render_contact_form_section($data) {
    if (empty($data['contact_form_show'])) {
        return '';
    }
    $config = [
        'eyebrow' => $data['contact_form_eyebrow'] ?? '',
        'title' => $data['contact_form_title'] ?? 'Prata med någon som kan automation',
        'subtitle' => $data['contact_form_subtitle'] ?? '',
        'layout' => $data['contact_form_layout'] ?: 'split',
        'theme' => $data['contact_form_theme'] ?: 'dark',
        'show_company' => $data['contact_form_show_company'] ?? true,
        'show_phone' => $data['contact_form_show_phone'] ?? true,
        'show_dropdown' => $data['contact_form_show_dropdown'] ?? true,
        'dropdown_label' => $data['contact_form_dropdown_label'] ?? 'Vad kan vi hjälpa dig med?',
        'options' => $data['contact_form_options'] ?? [],
        'cta_text' => $data['contact_form_cta_text'] ?: 'Skicka',
        'message_label' => $data['contact_form_message_label'] ?? 'Berätta mer (valfritt)',
        'trust_signals' => $data['contact_form_trust_signals'] ?? [],
        'colors' => ['main' => $data['color_main'], 'accent' => $data['color_secondary']],
        'source_plugin' => 'wexoe-landing-page',
        'page_slug' => $data['slug'],
        'contact_person' => !empty($data['contact_form_show_contact_person']) ? [
            'name' => $data['contact_name'], 'title' => $data['contact_title'],
            'email' => $data['contact_email'], 'phone' => $data['contact_phone'],
            'image' => $data['contact_image'], 'quote' => $data['contact_quote'],
        ] : null,
    ];
    return '<section id="kontakt">' .
        \Wexoe\Core\Core::contact_form()::render($config) .
        '</section>';
}
```

### 4.5 Lager 4 — Wexoe Builder

Per page-typ:

1. **Utöka types.ts:** Lägg till `contactForm: ContactFormState` i `PageState` / `ProductAreaState` / `AudienceState`. Definiera `ContactFormState` i ny delad fil `lib/contact-form-types.ts` (eftersom strukturen är identisk).
2. **Utöka mapper:** Reverse-mapper läser `Contact Form *`-fält från Airtable-recordet. Forward-mapper (Claude-transform för LP/PA, deterministisk för Audience) får motsvarande regler.
3. **Ny editor:** `components/<type>/editors/ContactFormEditor.tsx` — slug-pris-bil-style med fältinputs, toggles, dropdown för layout/theme, options som textarea, trust-signals som textarea. Återanvänd `FieldInput`/`FieldCheckbox`/`RichTextarea`.
4. **Ny preview:** `components/<type>/preview/ContactFormPreview.tsx` — rendera ungefär samma form-skiss som PHP-pluginet kommer producera. Återanvänd CSS-utilities från Tailwind.
5. **Lägg till section-id:** Utöka `SectionId`-unionen med `'contactForm'`. Lägg sektionen i `QUICK_NAV`-arrayen och i `PreviewPanel`-rendereringen.
6. **Visibility-toggle:** Följ samma mönster som PA/Audience — sektionen visas i preview om `state.contactForm.show` är `true`. Default `false` i create mode.
7. **Cache-invalidering vid publish:** `lib/wexoe-cache.ts` behöver inte ändras — `LP_ENTITIES`/`PA_ENTITIES`/`AUDIENCE_ENTITIES` består av samma entity-namn eftersom contact_form-fälten är *inom* respektive entity.

### 4.6 #kontakt-anchor

`<section id="kontakt">` placeras som yttre wrapper i varje feature-plugin runt `Core::contact_form()::render()`. Detta:

- Bevarar alla existerande `href="#kontakt"`-knappar på sajten.
- Tillåter redaktören att ta bort den explicit-döpta WordPress-sektionen som omsluter `[wexoe_contact_form]` idag. (Som städning, inte brådskande.)
- Per-page är `id="kontakt"` unikt på den sidan så fragment scrollar dit. Om någon sida av misstag har två (gammalt + nytt), tar webbläsaren det första — så vi måste säkerställa via deployment-checklistan att den gamla shortcoden tas bort när det nya aktiveras per sida.

### 4.7 Submissions-flöde

```
Användare submitar form
  → JS POST → admin-ajax.php?action=wexoe_contact_submit
    → Handler::handle()
      → wp_verify_nonce
      → sanera input
      → Core::submission('user_submissions')->create_mapped([
          'email' => ...,
          'name' => ...,
          'company' => ...,
          'phone' => ...,
          'submission_type' => 'contact',
          'submitted_at' => current_time('c'),
          'page_slug' => $_POST['page_slug'],
          'page_url' => $_POST['page_url'],
          'source_plugin' => $_POST['source_plugin'],
          'message' => $msg,
          'newsletter_consent' => isset($_POST['gdpr_consent']),
          'extra' => [
            'behov' => $_POST['behov'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
          ],
        ])
      → returnera JSON success/error
```

Make.com-flöden som behövs (notiser till säljare, CRM-sync) flyttas till en Airtable-automation som triggas på "när ny rad skapas i User data där Submission Type = contact". Det är en engångsmigrering och kräver inte ändrad kod.

---

## 5. Implementationsplan

### Fas 0 — Beslut (denna doc)

- [ ] Lucas läser igenom och godkänner arkitekturskissen, eller pekar ut justeringar.
- [ ] Bestäm var contact-form-sektionen ska sitta visuellt i förhållande till befintliga sektioner per plugin (efter contact-person? istället för? före docs?).

### Fas 1 — Wexoe Core: renderaren + handlern

1. Skapa `wexoe-core/src/ContactForm/Renderer.php` med statisk `render(array $config): string`. Kopiera HTML/CSS/JS-skelettet verbatim från `Old plugins/wexoe-contact-form.php`, byt prefix till `wxcf-*`, parametrisera title/eyebrow/subtitle/layout/theme/options/cta/trust-signals/colors.
2. Skapa `wexoe-core/src/ContactForm/Handler.php`. Registrera AJAX. Byt webhook-anropet till `Core::submission('user_submissions')->create_mapped()`. Bevara samma JSON success/error-svar.
3. Skapa `wexoe-core/src/ContactForm/Config.php`. Defaults + validering.
4. Lägg till `public static function contact_form()` i `Core.php`.
5. Boot i `Plugin::boot()`.
6. **Manuellt test:** Aktivera Core, ring `\Wexoe\Core\Core::contact_form()::render([...])` från ett testskript och submita — verifiera ny rad i Airtable User data.

### Fas 2 — Airtable-fält

1. I Airtable-basen `appXoUcK68dQwASjF`, lägg till alla `Contact Form *`-fält i tabellerna `Landing Pages` (`tbl8KDqGq0Ray1uqS`), `Product Areas` (`tblgatNFYFMwF4EcQ`), `Audience Heroes` (`tblvNf1CqAYEFvTpu`). Sätt defaults där det finns.
2. Uppdatera respektive entity-schema i `wexoe-core/entities/landing_pages.php`, `product_areas.php`, `audience_heroes.php` med de nya domänfälten.
3. Bumpa cache-TTL-rensning via `Core::entity('landing_pages')->clear_cache()` osv. (eller via Verktyg-sidan i WP-admin).

### Fas 3 — Per-plugin integration

Per plugin (gör en åt gången, testa visuellt):

1. Skapa `wexoe_<prefix>_render_contact_form_section($data)`-helper.
2. Anropa den i shortcode-renderloopen sist (eller där det passar designmässigt).
3. **Audience:** Detta är första gången pluginet har en kontakt-sektion alls — testa noggrant för regressioner i hero-marginalerna (full-bredd-trick med `100vw`).
4. **LP:** Bestäm om gamla `wexoe_lp_render_contact()` (visitkortet) ska bytas ut eller stå kvar bredvid. Min rekommendation: behåll men gör det till en *del* av formulärets vänsterkolumn när `contact_form_show_contact_person=true`.
5. **PA:** Samma — `wexoe_pa_render_our_guy()` kan migreras till att bli en del av formulärets vänsterkolumn.

### Fas 4 — Builder integration

Per page-typ:

1. Skapa `lib/contact-form-types.ts` med `ContactFormState`-interfacet och `emptyContactFormState()`.
2. Utöka `lib/types.ts` (LP), `lib/product-area-types.ts`, `lib/audience-types.ts` med `contactForm: ContactFormState`.
3. Utöka mappers (`lib/page-mapper.ts`, `lib/product-area-mapper.ts`, `lib/audience-mapper.ts`).
4. Skapa `components/<type>/editors/ContactFormEditor.tsx` per typ.
5. Skapa `components/<type>/preview/ContactFormPreview.tsx` per typ.
6. Lägg `contactForm` i `SectionId`-unionen, `QUICK_NAV`-arrayen, `PreviewPanel`/`AudiencePreviewPanel`/`ProductAreaPreviewPanel`.
7. Uppdatera Claude-prompten (`lib/airtable-schema-lp.md` / `airtable-schema-pa.md`) med de nya `Contact Form *`-fälten + formateringsregler.

### Fas 5 — Datamigration

1. För varje existerande sida som idag har `[wexoe_contact_form]` på sig, sätt `Show Contact Form = true` i Airtable och fyll i den önskade titeln/subtiteln om de avviker från default.
2. Cache rensas automatiskt via Wexoe Builder publish-webhooken, eller manuellt via Verktyg-sidan.
3. Editorerna tar bort `[wexoe_contact_form]`-shortcoden + den explicit-döpta "kontakt"-sektionen i WordPress-sidan.

### Fas 6 — Deprecation

1. Markera `Old plugins/wexoe-contact-form.php` som *deprecated* i plugin-headern (`Description: [DEPRECATED — använd integrerade kontaktformulär]`). Aktiverat plugin kan stå kvar i månader, men shortcoden kan svara med en `<!-- wexoe-contact-form: deprecated -->`-kommentar.
2. När alla sidor är migrerade: kör en sökning i WP-databasen efter `[wexoe_contact_form` och bekräfta att det är 0 träffar. Sedan kan pluginet avaktiveras och tas bort.
3. Den befintliga Make.com-webhooken kan avlyssnas på Make-sidan och flyttas in i en Airtable-automation som svarar på nya rader i User data.

---

## 6. Risker

| Risk | Konsekvens | Mitigering |
|---|---|---|
| Två `id="kontakt"`-element på samma sida under migrationen | Anchor scrollar till fel ställe | Migrera en sida i taget; under övergången sätt `Show Contact Form = true` *samtidigt* som redaktören tar bort den gamla shortcoden. Skapa en städnings-checklista. |
| AJAX-handler-kollision om gamla pluginet är aktivt parallellt | Submission går två gånger eller blandas | Båda registrerar samma action-namn (`wexoe_contact_submit`) — sista vinner enligt WP. Säkrare: byt namn på nya handler till `wxcf_submit` så de inte krockar. |
| CSS-prefix-kollision om någon LP/PA-style råkar matcha en `wxcf-*`-selector | Visuell bug | Det nya prefixet är unikt och scopad till `#wxcf-{uniqid}`. Risk ≈ noll. |
| Tre denormaliserade fält-set i Airtable | Schema-drift om man bara uppdaterar en | Kommentar i scheman ("synka manuellt med övriga två") + lägg till en CI-check senare. Se avsnitt 8.1 för alternativet. |
| Audience-pluginen får en sektion den aldrig haft → marginal-/breddregressioner | Visuell bug på audience-sidor | Skriv CSS som inte förlitar sig på Audiences `100vw`-trick. Renderaren ska klara att leva både innanför och utanför en `100vw`-container. |
| Make.com-webhooken stoppar gå när vi migrerar | Notiser till säljare uteblir | Sätt upp Airtable-automation *innan* deprecation av gamla pluginet. Behåll båda flödena parallellt i 1 vecka för dubbel-leverans. |
| Spam | Trasig inbox | Lägg honeypot-fält (`<input name="_hp" style="display:none">`), avvisa om ifyllt. Vid behov: lägg till rate-limit per IP som LP-pluginet har idag (10/timme transient). |
| GDPR | Data lagras på fel plats | User data-tabellen är redan godkänd som lagringsplats för leads. Bekräfta retention-policy och radera-på-begäran-flödet (separat fråga). |
| Claude-transform tappar fält i UPDATE mode för LP/PA | Sparas tomma och kontaktformulär försvinner | Lägg `Contact Form *`-fälten i listan med fält som ska *alltid* echos även när tomma. Uppdatera `airtable-schema-lp.md` / `airtable-schema-pa.md` system-prompts. |

---

## 7. Möjligheter (bonus från refactorn)

- **Audit trail.** Varje contact-submission hamnar i Airtable, daterad och kopplad till `page_slug` + `source_plugin`. Lättare att analysera vilka sidor som konverterar.
- **A/B-test av formulärtext.** Lucas kan byta titel/subtitle/CTA per sida i Airtable utan deploy.
- **Centraliserade trust-signals.** Om alla sidor delar samma trust-signals idag är det 1 ändring; med per-page kan vi tona dem efter målgrupp.
- **Wexoe Builder copy-flöde** (`/api/copy`) kommer automatiskt kopiera contact-form-fälten när redaktören klonar en sida.
- **Bottenplatta för framtida block-system.** Konfig-pattern (`render($config)` i Core) är samma som behövs för ev. "blocks"-baserad arkitektur i v3.

---

## 8. Alternativa approacher (utvärderade)

### 8.1 ALT: En delad `contact_form_variants`-entity

Istället för att duplicera ~15 fält i tre tabeller: skapa en separat Airtable-tabell `Contact Form Variants` med rader som `default`, `automation`, `it-infra`. Lägg ett enda `contact_form_variant` (string FK) på LP/PA/Audience-entities. Pluginen läser variant och slår upp konfigen.

**Pros:**

- Verkligt DRY även i datat. En ändring av "default" uppdaterar alla sidor som inte överskridit.
- Lägre Airtable-skrivkostnad — färre kolumner.
- Lättare att standardisera.

**Cons:**

- Ett extra repository-anrop per page-load. Cachen tar det.
- Mer abstrakt för redaktör — kräver att de förstår begreppet "variant".
- Override-fält per sida behövs ändå (titel/subtitle/CTA varierar per kampanj), så vi får både variant + override → mer komplext.
- Större initial implementation.

**Rekommendation:** Skip för v1. Lägg in som möjlig v2 om vi ser att 80%+ av sidor använder identisk config.

### 8.2 ALT: Behåll separat `wexoe-contact-form`-plugin, men gör IT dynamiskt

Lägg slug-attribut på shortcoden: `[wexoe_contact_form slug="kontakt-automation"]`. Skapa en `contact_forms`-entity. Pluginet läser config från Airtable men existerar fortfarande som separat shortcode.

**Pros:**

- Minimal refactor i feature-pluginsen.
- Lösare koppling mellan typer.

**Cons:**

- **Löser inte** maintenance-problemet (gamla pluginen har redan en separat instans av all kod).
- Löser inte #kontakt-anchor-problemet — redaktören måste fortfarande wrappa shortcoden i en namngiven WP-sektion.
- Blockerar inte den naturliga vägen att integrera formuläret med kontaktperson-kortet i LP/PA.
- Kräver att Wexoe Builder lär sig hantera ytterligare en page-typ ("contact-form-variants") som existerar oberoende.

**Rekommendation:** Avvisas. Den löser några problem men inte de viktigaste.

### 8.3 ALT: Generisk "page footer blocks"-arkitektur

Ge varje page-typ en `bottom_blocks`-array (pseudo-array som `Normal N` i PA): block-typer som `contact_form`, `kontaktperson`, `downloads`, `faq`, etc. Redaktören väljer block och ordningsstapel.

**Pros:**

- Maximal flexibilitet.
- Skalar till alla framtida sektioner, inte bara kontakt.

**Cons:**

- Stor refactor. Stort schema-skifte. Stor Builder-refactor.
- Risk för premature abstraction — vi har idag bara *ett* nytt block att lägga till. Tre månader senare har vi kanske två.
- Förskjuter denna feature med veckor.

**Rekommendation:** Spara som långsiktig vision. Inte v1.

---

## 9. Min värdering och invändningar

### 9.1 Vad jag tycker om upplägget i grunden

Rätt instinkt att integrera istället för att hålla en separat plugin. Det matchar resten av Wexoe-arkitekturen: en plugin = en sidtyp, all data i Airtable, all rendering scopad. Att lägga delningen i `wexoe-core` är arkitekturellt korrekt: Core är redan den enda platsen där alla feature-pluggar har gemensamt språk.

### 9.2 Där jag tror du tänker fel

1. **"contact-form ligger i en separat shortcode under de andra pluginsen"** — i koden idag är `[wexoe_contact_form]` *helt* separat från LP/PA/Audience. Den vet inte vilken sida den står på, vilka färger som ska användas, eller vad redaktören skrivit. Den hårdkodar allt. Det betyder att "integrera fullt" i praktiken är att **bygga om hela formuläret från grunden** med en ny arkitektur — inte att flytta kod. Det är inte mer jobb, men det är en annan mental modell. (Faktum är, vi kan i princip slänga den gamla pluginen och börja från ett tomt papper i `wexoe-core` — gamla pluginen är nyttigt som referens för CSS/HTML, ingenting annat.)
2. **"contact section" i LP/PA är inte ett formulär idag.** De är kontaktperson-visitkort (`render_contact()` / `render_our_guy()`). Du nämnde "många knappar går till #kontakt och så har jag döpt wp section som contact forms shortcode ligger i till kontakt" — det betyder att #kontakt scrollar till en WP-sektion som *omsluter* `[wexoe_contact_form]`, INTE till själva pluginens contact-sektion. Det är två olika element på sidan. Var explicit i din egen mental-modell om detta så att du inte råkar ta bort fel sak när migrationen sker.
3. **"jag vill att de ska kodas så att man kan ändra hur de ser ut"** — den gamla pluginen har redan `inverted="true"`-attributet för en variant. Det skalar inte. Min plan med `layout` (split/centered) + `theme` (dark/light) + `show_*`-toggles ger 8 visuella varianter utan att man behöver lägga till CSS-klasser per ny variant. Lägga till en variant senare = en ny enum-entry, inte ny kod.
4. **Vi behöver inte separat write-entity.** `user_submissions` finns redan och är generisk (`submission_type` är en `singleSelect`). Det vore overengineering att skapa `contact_submissions` separat.

### 9.3 Skalbarhetsfrågor

- **Vad händer när vi får en fjärde page-typ?** Med embedded-fält-approachen blir det fjärde gången vi lägger till 15 fält i ett schema. Då börjar variants-approachen (8.1) bli värd den extra komplexiteten. Bygg in en kommentar i scheman som påminner om att samma fält finns på tre ställen.
- **CSS-överlägg.** `wxcf-*`-prefix är unik. Men inom Audience-pluginet finns ett `100vw`-trick som flyttar elementet utanför container. Renderaren måste antingen alltid göra samma trick, eller alltid inte. Bestäm tidigt.
- **Cachning.** Contact-form-config läses som del av page-entity. Den ärver page-entityns cache-TTL (24h). En ändring av contact-form-titeln i Airtable syns inte direkt. Bygger Wexoe Builder publish webhook redan idag för LP/PA/Audience? **Ja** — så detta är redan löst.
- **Submissions-volym.** Om vi får 100 submissions/dag är det 36 500/år — Airtable Pro klarar ~100K rader. Vi har gott om utrymme. Migrera senare om volymen kräver det.

### 9.4 Vad jag *inte* säkert vet och du behöver verifiera

- Vilka WP-sidor använder `[wexoe_contact_form]` idag? En `wp db search` eller en `grep` i WP-content kan svara.
- Är det OK att flytta från Make-webhook till Airtable direkt? Vilka Make-scenarier hänger på `https://hook.eu1.make.com/sulae2u3lux9g9dqfabtsdngiwz46s6g`? Kolla i Make.
- Finns det rate-limit-behov? LP-pluginet har 10/timme/IP. PA-pluginet har inget. Bestäm en standard.
- Förmodade rättigheter på Airtable: skapande av rader i User data ska redan fungera via Core-write — bekräfta att nuvarande API-token tillåter det.

---

## 10. Öppna beslut före kod

1. **Position i layout:** Var ska formuläret placeras i förhållande till kontaktperson-kortet i LP och PA? (Mitt förslag: ersätt det. Lägg in kontaktpersonen som vänsterkolumn i formuläret när `show_contact_person=true`.)
2. **Nytt AJAX-actionnamn:** `wexoe_contact_submit` (krockar med gamla) eller `wxcf_submit` (säker)? Förslag: `wxcf_submit`.
3. **Spam-mitigering:** Honeypot räcker, eller vill vi lägga reCAPTCHA / hCaptcha? Förslag: honeypot v1, eskalera om vi får spam.
4. **Rate-limit:** 0, 10/h/IP eller 5/h/IP? Förslag: 10/h/IP, identisk med LP-pluginet.
5. **Default-värden:** Ska defaults bo i PHP-renderaren eller alltid hämtas från Airtable? Förslag: PHP — gör att tomma fält faller tillbaka på vettiga defaults utan att kräva att Airtable-raden är fullt ifylld.

---

## 11. Estimat

Grov totaltid om allt går utan friktion:

| Fas | Tid |
|---|---|
| 0 — Beslut, godkänna spec | ½ dag |
| 1 — Core renderer + handler | 1 dag |
| 2 — Airtable-fält + scheman | ½ dag |
| 3 — Per-plugin integration (3 plugins) | 1 dag |
| 4 — Builder editor + preview per typ (3 ggr) | 2 dagar |
| 5 — Datamigration + testning | ½ dag |
| 6 — Deprecation + cleanup | ½ dag |
| **Totalt** | **~6 dagar** |

Realistiskt med kontextbyten och oförutsedda upptäckter: 1.5–2 veckors arbete.

---

## 12. Nästa steg

1. Lucas läser denna doc.
2. Vi besvarar de fem öppna besluten i §10.
3. Vi skapar en konkret task-uppdelning (1 PR per fas).
4. Fas 1 startar i `wexoe-core` på en följd-branch (`claude/contact-form-core-renderer`).

---

*Slut.*
