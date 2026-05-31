# Wexoe Plugins — utvecklingsguide

> **Vill du skapa en ny sidtyp?** Läs `SKAPA-SIDA.md` (i denna repo) — den guidar dig genom hela flowet steg för steg. Du behöver inte läsa filen du nu har öppen. Den är teknisk referens.

Hela referensen för att jobba med wexoe-core och dess page-plugins. Hur systemet är designat, vilket publikt API som finns, vilka konventioner som gäller och hur en typisk feature-plugin är byggd.

Pair-läs med `wexoebuilder/CLAUDE.md` för bygg-sidan av samma system. För att skapa en *ny* sidtyp, se `SKAPA-SIDA.md`.

---

## 1. Systemöversikt

```
┌──────────────┐     write     ┌──────────┐  read    ┌──────────────┐  → wexoe-landing-page
│ wexoebuilder │ ────────────► │ Airtable │ ◄─────── │  wexoe-core  │  → wexoe-audience-hero
│ (Next.js)    │ ◄──────────── │  (CMS)   │          │  (transients)│  → wexoe-product-area
└──────────────┘    display    └──────────┘          └──────────────┘  → wexoe-pages
                                                            ▲          → wexoe-alb-blocks
                                                            │          → wexoe-contact-page
                                                            │          → automation-pillar
                                                            │          → ... (en per sidtyp)
                                                  ┌─────────┴─────────┐
                                                  │  WordPress site   │
                                                  │  (Enfold theme)   │
                                                  └───────────────────┘
```

### Roller
- **wexoebuilder** — Next.js-app där marknadsförare redigerar sidor. Skriver till Airtable via en deterministisk transform (rena funktioner, ingen Claude på spar-vägen).
- **Airtable** — vår CMS-datakälla. Två basar: `appokKSTaBdCa8YiW` (Wexoe NY, kanonisk) och `appXoUcK68dQwASjF` (Wexoe, legacy under utfasning).
- **wexoe-core** — WordPress-plugin som är ENDA komponenten som pratar med Airtable från WP-sidan. Cachar i transients. Exponerar PHP-API till feature-plugins.
- **Feature-plugins** — en per sidtyp. Renderar shortcode → läser data via Core → producerar HTML. Pratar ALDRIG med Airtable direkt.

### Varför centraliserad Core?
Tidigare hade varje plugin egen Airtable-klient, egen transient-hantering, egna fältnamnskonstanter, egna helpers (markdown, color, youtube). Det innebar kopior av samma kod i sju plugins, sju ställen att byta API-nyckel, sju olika cache-strategier. Core gör datalagret till en infrastrukturdetalj som feature-plugins inte behöver bry sig om.

---

## 2. Naming conventions (lås)

Konventionerna gäller överallt — Airtable display-namn, PHP-fält i schemafiler, TS-state i buildern, kolumnetiketter.

### Tabellprefix
| Prefix | Användning |
|---|---|
| `core_` | SSOT — referensdata och singletons (företag, divisioner, partners, coworkers, ...) |
| `cms_` | CMS-redigerat innehåll för publika sidor (landing pages, product pages, ...) |
| `pim_` | Produktdata speglad från PIM (framtid) |
| `inbox_` | Inkommande events (form-submissions) |
| `ext_` | Speglade externa system (framtid) |

### Format
- **snake_case** överallt — fältnamn, schemanycklar, kod-identifierare.
- **Plural** för kollektioner: `core_partners`, `cms_landing_pages`.
- **Singular** för singletons: `core_company`, `core_graphic_profile`.
- **kebab-case** för slug-VÄRDEN (inte fältnamn).
- **Engelska** överallt.

### Type-suffix
`*_url`, `*_email`, `*_phone`, `*_at` (DateTime), `*_date`, `*_count` (int), `*_id` (single link), `*_ids` (multi link), `*_html`, `*_markdown`, `*_json`.

### Domän-prefix inom tabell
`hero_*`, `seo_*`, `contact_*`, `case_*`, `faq_*`, `cta_banner_*`, etc.

---

## 3. wexoe-core: publikt API

Allt feature-plugins ska behöva. Klassen `\Wexoe\Core\Core` är fasaden; allt annat i `wexoe-core/src/` är implementation.

### 3.1 Läs: entity-repositories

```php
use Wexoe\Core\Core;

// Hämta repository för en entitet (entity-namnet = filnamnet utan .php)
$repo = Core::entity('landing_pages');  // returnerar EntityRepository|null

// Hitta en post via primärnyckel (definierad i schemat)
$page = Core::entity('landing_pages')->find('fjarraccess');

// Alla poster (valfritt med filter)
$all = Core::entity('core_partners')->all();
$visible = Core::entity('lp_tabs')->all(['is_active' => true]);

// Hitta första post där ett fält har ett visst värde
$tab = Core::entity('lp_tabs')->find_by('tab_type', 'faq');

// Resolva linked records — tar array av Airtable record-IDs,
// returnerar normaliserade poster i samma ordning
$tabs = Core::entity('lp_tabs')->find_by_ids($page['tab_ids']);

// Cache-hantering (används sällan i feature-plugins)
Core::entity('landing_pages')->clear_cache();
Core::entity('landing_pages')->force_refresh();
```

`Core::entity()` returnerar `null` om schemat inte finns. Feature-plugins SKA null-checka vid uppstart.

**Namnkonvention vs verklighet:** § 2 etablerar `cms_*` / `core_*`-prefix som mål. Migrationen är pågående — `core_*`-entiteter har bytt namn, medan flera `cms_*`-entiteter fortfarande heter t.ex. `landing_pages.php` / `lp_tabs.php` / `audience_heroes.php`. Använd det filnamn som faktiskt finns i `wexoe-core/entities/` (utan `.php`) som argument till `Core::entity()`. Nya entiteter SKA döpas med rätt prefix från start.

### 3.2 Normaliserat output-format

Alla läsmetoder returnerar associativa arrays med **domänfält** (aldrig Airtable-fältnamn). Varje post har ett extra `_record_id`-fält med Airtable:s record-ID (`recXXX...`).

```php
$partner = Core::entity('core_partners')->find('rockwell');
// Returnerar:
// [
//     '_record_id' => 'recABC123...',
//     'name' => 'Rockwell',
//     'logo_url' => 'https://...',
//     'division_ids' => ['recDEF456...', 'recGHI789...'],
// ]
```

Fälttyper i output:

| Schema-typ | PHP-typ i output |
|---|---|
| `'Airtable Field'` (string passthrough) | `string\|null` |
| `['source' => '...', 'type' => 'string']` | `string\|null` |
| `['source' => '...', 'type' => 'int']` | `int\|null` |
| `['source' => '...', 'type' => 'float']` | `float\|null` |
| `['source' => '...', 'type' => 'bool']` | `bool` (aldrig null) |
| `['source' => '...', 'type' => 'lines']` | `string[]` (tom array om tomt) |
| `['source' => '...', 'type' => 'link']` | `string[]` av record-IDs (tom array om tomt) |
| `['source' => '...', 'type' => 'attachment']` | `array\|null` med keys: url, filename, width, height, size, mime_type, thumbnails |
| `['source' => '...', 'type' => 'attachments']` | `array` av attachment-objekt |
| `['type' => 'pseudo_array', ...]` | `array` av objekt (tomma sektioner bortfiltrerade, varje objekt har `_index`) |

### 3.3 Skriv: writers och submissions

För formulär, lead magnets, eventanmälningar — saker som skapar nya records i Airtable från WP-sidan.

```php
// Rå writer — du anger Airtable-fältnamn direkt
$result = Core::writer('tblXXXXXXXXXXXX')->create([
    'Email' => sanitize_email($email),
    'Namn'  => sanitize_text_field($name),
]);

// Schemabaserad writer — domänfält via write-entity-schema
$result = Core::submission('user_submissions')->create_mapped([
    'email'              => sanitize_email($email),
    'submission_type'    => 'leadmagnet',
    'newsletter_consent' => true,
    'extra'              => ['custom_key' => 'value'],
]);
```

Skriv-scheman bor i `wexoe-core/write-entities/{namn}.php` — se § 5 nedan. Skillnaden mot läs-scheman: write-scheman är bara fältmappning (ingen typ-normalisering, ingen cache).

### 3.4 Helpers

```php
use Wexoe\Core\Helpers\Markdown;
use Wexoe\Core\Helpers\Color;
use Wexoe\Core\Helpers\YouTube;
use Wexoe\Core\Helpers\Lines;

// Markdown
Markdown::to_html('**bold** and *italic*');     // <p><strong>bold</strong> and <em>italic</em></p>
Markdown::to_inline('**bold** and *italic*');   // <strong>bold</strong> and <em>italic</em>  (utan <p>)
Markdown::strip('**bold** and [link](url)');    // bold and link

// Color
Color::normalize_hex('#abc');          // '#aabbcc'
Color::normalize_hex('ABC123');        // '#abc123'
Color::normalize_hex('ogiltig');       // null
Color::is_dark('#11325D');             // true
Color::text_color('#11325D');          // '#ffffff'  (kontrastfärg)

// YouTube
YouTube::extract_id('https://youtu.be/dQw4w9WgXcQ');   // 'dQw4w9WgXcQ'
YouTube::render_embed('dQw4w9WgXcQ');                   // responsiv iframe
YouTube::thumbnail_url('dQw4w9WgXcQ');                  // hqdefault.jpg URL

// Lines (multi-line text ↔ array)
Lines::to_array("rad 1\nrad 2\n\nrad 3");  // ['rad 1', 'rad 2', 'rad 3']
Lines::first("rad 1\nrad 2");              // 'rad 1'
Lines::from_array(['a', 'b', 'c']);        // "a\nb\nc"
```

Övriga helpers (mindre vanliga): `\Wexoe\Core\Helpers\Collections`, `\Wexoe\Core\Helpers\Context`, `\Wexoe\Core\Helpers\Singletons`.

### 3.5 Renderers

Core innehåller en delad renderer för kontaktformuläret — det är den enda komponent som faktiskt återanvänds av flera plugins (wexoe-landing-page, wexoe-audience-hero, wexoe-product-area, wexoe-pages).

```php
$class = Core::renderer('contact-form');
if ($class !== '') {
    echo $class::render([
        'title'   => 'Kontakta oss',
        'layout'  => 'split',
        'theme'   => 'dark',
    ]);
}
```

Aktuell map (`Core::renderer($type)`):
- `contact-form` → `Renderers\ContactForm`

**När bör en komponent ligga i Core?** När två eller fler feature-plugins behöver exakt samma rendering. Annars håll den i pluginet — varje plugin äger sin egen markup. Att flytta in i Core senare är trivialt; att dela ut är mödosamt.

### 3.6 Logging

```php
Core::log('info', 'Lead magnet downloaded', ['slug' => $slug, 'email' => $email]);
Core::log('warning', 'Missing field in record', ['record_id' => $id]);
Core::log('error', 'Airtable fetch failed', ['error' => $e->getMessage()]);
```

Loggar går till WP error log med prefix `[wexoe-core]`. Inkludera context — det är guld vid debug.

### 3.7 Core-beroendekontroll

Feature-plugins ska kontrollera att Core är aktivt:

```php
function my_plugin_core_ready() {
    return class_exists('\\Wexoe\\Core\\Core')
        && method_exists('\\Wexoe\\Core\\Core', 'entity');
}
```

Anropa i shortcode-funktionen och returnera ett synligt felmeddelande om Core saknas. Aktivera ALDRIG pluginet halvvägs.

---

## 4. Läs-schemaformat (entities/)

Läs-scheman bor i `wexoe-core/entities/{namn}.php`. Varje fil returnerar en array som mappar Airtable-fält till domänfält och deklarerar typer.

```php
<?php
if (!defined('ABSPATH')) exit;

return [
    'table_id'    => 'tblXXXXXXXXXXXXXX',
    'primary_key' => 'slug',          // domänfält som find() söker mot
    'cache_ttl'   => 86400,           // sekunder (default 24h)
    'required'    => ['slug', 'name'], // poster utan dessa filtreras bort och loggas
    'fields' => [
        // Enkel passthrough: domänfält => Airtable-fältnamn
        'name' => 'Name',
        'slug' => 'Slug',

        // Typat fält
        'is_active' => ['source' => 'Is Active', 'type' => 'bool'],
        'order'     => ['source' => 'Order', 'type' => 'float'],

        // Multi-line text → array av strängar
        'benefits' => ['source' => 'Benefits', 'type' => 'lines'],

        // Linked records → array av record-IDs
        'tab_ids' => ['source' => 'Tab Links', 'type' => 'link', 'entity' => 'lp_tabs'],

        // Attachment (första bilden)
        'hero_image' => ['source' => 'Hero Image', 'type' => 'attachment'],

        // Pseudo-array (numrerade Airtable-fält → array av objekt)
        'sections' => [
            'type'   => 'pseudo_array',
            'prefix' => 'Normal',   // fältnamn: "Normal 1 H2", "Normal 2 H2", ...
            'count'  => 4,
            'fields' => [
                'h2'    => 'H2',     // → "Normal 1 H2", "Normal 2 H2", etc.
                'text'  => 'Text',
                'image' => 'Image',
            ],
        ],
    ],
];
```

**Regler:**
- Filnamnet = entity-namnet. `landing_pages.php` → `Core::entity('landing_pages')`. Nya entiteter ska namnges med prefix enligt § 2 (`cms_*` / `core_*` / `inbox_*`).
- Bara lowercase `a-z`, `0-9`, `_` i filnamn.
- `primary_key` måste referera till ett fält i `fields`.
- `required`-fält valideras vid load — poster som saknar dem loggas som warning och filtreras bort.
- `entity`-attributet på `type: link` är dokumentation. `find_by_ids()` kräver att du anger entity-namn explicit i anropet.

---

## 5. Skriv-schemaformat (write-entities/)

Skriv-scheman bor i `wexoe-core/write-entities/{namn}.php`. Enklare än läs-scheman — bara fältmappning.

```php
<?php
if (!defined('ABSPATH')) exit;

return [
    'table_id' => 'tblxrwMhSysupcDwe',
    'fields' => [
        // domän-nyckel => Airtable-fältnamn
        'email'              => 'Email',
        'submission_type'    => 'Submission Type',
        'submitted_at'       => 'Submitted At',
        'newsletter_consent' => 'Newsletter Consent',
        'extra'              => 'Extra',     // JSON-spillkolumn
    ],
];
```

**Skalbarhetsmönster — `extra` som spillkolumn:** En enda `user_submissions`-tabell hanterar alla typer av inlämningar (lead magnets, events, kontakt, bokningar). Fält som inte behövs för en viss typ lämnas tomma. Typ-specifik data utan dedikerat fält packas i `extra` som JSON. Nya inlämningstyper kan utöka schemat eller bara använda `extra` — inga Airtable-schemaändringar krävs för MVP.

---

## 6. Anatomi av ett feature-plugin

Typisk struktur för ett page-plugin (`plugins/wexoe-xxx/wexoe-xxx.php`):

```php
<?php
/**
 * Plugin Name: Wexoe Audience Hero
 * Description: Dynamic hero + value section for audience landing pages.
 * Version: 2.x
 * Author: Wexoe
 */

if (!defined('ABSPATH')) exit;

// 1. Core-beroendekontroll
function wexoe_ah_core_ready() {
    return class_exists('\\Wexoe\\Core\\Core')
        && method_exists('\\Wexoe\\Core\\Core', 'entity');
}

class Wexoe_Audience_Hero {

    public function __construct() {
        add_shortcode('wexoe_audience', [$this, 'render_shortcode']);
    }

    public function render_shortcode($atts) {
        // 2. Shortcode-parametrar
        $atts = shortcode_atts(['slug' => '', 'debug' => 'false'], $atts);
        if (empty($atts['slug'])) {
            return '<p style="color:red;">Wexoe Audience Hero: slug required</p>';
        }

        // 3. Core-guard
        if (!wexoe_ah_core_ready()) {
            return '<p style="color:red;">Wexoe Core är inte aktivt.</p>';
        }

        // 4. Hämta data via Core
        $repo = \Wexoe\Core\Core::entity('audience_heroes');
        if (!$repo) {
            return '<p style="color:red;">Schema "audience_heroes" saknas.</p>';
        }

        $data = $repo->find($atts['slug']);
        if ($data && empty($data['is_active'])) {
            $data = null;
        }
        if (!$data) {
            return '<p style="color:red;">Ingen data för slug "' . esc_html($atts['slug']) . '"</p>';
        }

        // 5. Rendera (CSS-scoping, defaults, helpers)
        $id = 'wexoe-ah-' . uniqid();
        ob_start();
        ?>
        <style>
            #<?php echo $id; ?> .hero { background: <?php echo esc_attr($data['hero_bg'] ?? '#fff'); ?>; }
            <?php /* mer scoped CSS här */ ?>
        </style>
        <div id="<?php echo $id; ?>">
            <h1><?php echo esc_html($data['title'] ?? ''); ?></h1>
            <?php if (!empty($data['description'])): ?>
                <p><?php echo \Wexoe\Core\Helpers\Markdown::to_inline($data['description']); ?></p>
            <?php endif; ?>
            <?php /* sektioner med if-statements på checkboxar */ ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

new Wexoe_Audience_Hero();
```

### Konventioner i feature-plugins

- **CSS-scoping via uniq ID.** Varje render får en unik `wexoe-xxx-{uniqid}`-wrapper. CSS:en byggs i `<style>` med den ID-prefixen så att två instanser av samma shortcode på samma sida inte krockar.
- **Defaults vid läsning.** Använd `$data['field'] ?? 'default'` i stället för att anta att fältet finns. Tomma valfria fält är `null` eller tom sträng beroende på typ.
- **Boolean checkboxar är aldrig null.** `$data['show_sidebar']` är `true` eller `false` — säkert att använda direkt i `if`-statements.
- **Linked records itereras, inte indexeras.** `find_by_ids()` hoppar tyst över raderade records. Iterera över returnerade arrayen, kolla inte mot ID-listan.
- **Filtrering och sortering görs i pluginet, inte i schemat.** Core normaliserar `is_active` till bool och `order` till float; det är feature-pluginets ansvar att filtrera bort osynliga och sortera.
- **`debug=true`-läge.** Lägg till en `debug`-shortcode-parameter som dumpar `print_r($data)` så att det går att inspektera vad Core returnerar utan att gräva i transients.
- **Behåll en `wexoe_xxx_field()`-helper** (safe getter med default) i pluginet om det förenklar rendering. Det är en *rendering*-helper, inte en Airtable-helper.

### Filtrera + sortera linked records

```php
$all_tabs = Core::entity('lp_tabs')->find_by_ids($page['tab_ids']);

$visible = array_filter($all_tabs, fn($t) => !empty($t['is_active']));
usort($visible, fn($a, $b) => ($a['order'] ?? 999) - ($b['order'] ?? 999));
$tabs = array_values($visible);
```

### Polymorfa records (tab-typer, sidebar-typer)

Vissa entiteter har en `type`-kolumn som styr vilka fält som är meningsfulla. Pluginet branchar på den:

```php
foreach ($tabs as $tab) {
    switch ($tab['tab_type']) {
        case 'textimage':
            echo render_textimage($tab);
            break;
        case 'faq':
            echo render_faq($tab);
            break;
        case 'compare':
            echo render_compare($tab);
            break;
        // ...
    }
}
```

Stale-field-clearing vid typbyten hanteras av buildern (deterministisk transform). Pluginet ska inte oroa sig — fält som inte är relevanta för den aktuella typen är tomma.

---

## 7. Mappstruktur (wexoeplugins-repot)

```
wexoe-core/
  wexoe-core.php           # Plugin-bootstrap
  src/
    Core.php               # Publikt API (fasaden)
    AirtableClient.php     # HTTP mot Airtable REST
    Cache.php              # Transient-wrapper
    EntityRepository.php   # Läs-API
    WriteRepository.php    # Skriv-API
    SchemaRegistry.php     # Schema-loader
    Normalizer.php         # Airtable-record → domänobjekt
    Logger.php
    Helpers/               # Markdown, Color, YouTube, Lines, ...
    Renderers/             # Delade renderers (Hero, Faq, TeamGrid, ...)
    Admin/                 # WP-admin (settings page, debug tools)
    ContactForm/           # Delad kontaktformulärsmodul
    RestApi.php            # Cache-clear endpoint (anropas från buildern)
    EntityRestApi.php
  entities/                # LÄS-scheman, en fil per entitet
    landing_pages.php        # (cms_-prefix kommer vid migration)
    lp_tabs.php
    audience_heroes.php
    cms_pages.php            # One-off-sidor (start, om-oss, pillar)
    cms_page_sections.php    # Polymorfa sektioner för cms_pages
    cms_section_tabs.php     # Sub-records för tabs-sektionen
    core_partners.php
    ...
  write-entities/          # SKRIV-scheman, en fil per write-target
    user_submissions.php
    core_partners.php
    ...

plugins/
  wexoe-landing-page/      # En mapp per feature-plugin
    wexoe-landing-page.php
  wexoe-audience-hero/
    wexoe-audience-hero.php
  wexoe-product-area/      # Plugin-basename behålls (active_plugins-kontrakt);
    wexoe-product-area.php #   sidtypen heter product-page i builder/schema/entitet

  wexoe-pages/             # One-off-sidor — dispatcher + sections/
    wexoe-pages.php          # Bootstrap, shortcode, dispatcher, SEO
    sections/                # En fil per section_type-renderer
      hero.php  text-image.php  text-only.php  faq.php  ...
  wexoe-alb-blocks/        # Avia Layout Builder-integrering
  wexoe-contact-page/
  automation-pillar/       # @deprecated — utfasas till wexoe-pages-sektioner
```

### Plugin med många sektion-typer (wexoe-pages-mönstret)

Standard-plugin-mönstret (§6) räcker för sidor med 1–5 sektioner. För
informationssidor med 10+ heterogena sektioner använder vi en
**dispatcher**-variant:

- Sidan är en `cms_pages`-record med en länkad lista `section_ids` →
  `cms_page_sections` (polymorfa records med `section_type`-fält som
  diskriminerar vilka prefixade fält som är meningsfulla — samma mönster som
  `lp_tabs`).
- Plugin-pluginfilen är en tunn dispatcher: läser sidan + sektionerna, loopar
  och delegerar till `sections/<type>.php` per record. Varje section-fil
  returnerar en closure `function ($section, $page, $ctx): string`.
- Section-filer require:as lazy via en cachad loader-helper (en gång per
  request, oavsett hur många instanser av samma typ).
- Buildern rensar fält vid `section_type`-byte (samma stale-clearing-mönster
  som `lp_tabs`).

Lägga till en ny sektionstyp:
1. Lägg fält `<prefix>_*` på `cms_page_sections` i Airtable (snake_case).
2. Lägg fältmappningar i `entities/cms_page_sections.php`.
3. Lägg ett val i `section_type`-singleSelect.
4. Skapa `sections/<type>.php` som returnerar en closure.
5. Mappa typ → fil i `wexoe_pages_section_renderers()` i huvudfilen.

---

## 8. Cachestrategi

Core cachar varje entitet i en WP-transient med default-TTL 24h (konfigurerbar per schema). Cache invalideras explicit:

1. **Vid spar från buildern** — `/api/publish` på buildern anropar `/wp-json/wexoe-core/v1/cache/clear` med en lista över entitetsnamn. Routen är skyddad med `WP_CACHE_CLEAR_SECRET`.
2. **Manuellt via Core::entity('xxx')->clear_cache()** — om något script behöver tvinga refresh.
3. **TTL-utgång** — bakgrundsrefresh; första requesten efter expiry är långsam.

`force_refresh()` skiljer sig från `clear_cache()` så: clear tömmer transient och nästa läsning hämtar fresh; force_refresh hämtar fresh OCH lagrar omedelbart. Använd force när du *vet* att du strax kommer att läsa.

---

## 9. Felsökning

**Plugin renderar tomt eller fel:**
1. Aktivera `[wexoe_xxx slug="..." debug="true"]` — dumpa `$data` och se vad Core returnerar.
2. Kolla WP error log för `[wexoe-core]`-rader — `required`-validering och Airtable-fel loggas där.
3. `Core::entity('xxx')->force_refresh()` om data ändrats i Airtable men cache är stale.

**Schema-fel:**
- "Entity X not found" → schema-fil saknas eller filnamn matchar inte entity-namnet.
- "Required field 'slug' missing" → posten i Airtable saknar slug. Antingen fix posten eller ta bort fältet ur `required`.

**Airtable-fält syns inte i output:**
- Är fältet listat i `fields`?
- Stavning av `source` exakt som Airtable display-namnet (inkl. mellanslag, versaler)?
- Är fält-typen rätt? En lines-typ förväntar multi-line text, en attachment förväntar single attachment-fält.

---

## 10. Vart pekar resten?

- `NEW_PAGE_TYPE.md` (denna mapp) — receptet för att skapa en helt ny sidtyp (plugin + schema + builder-editor). Pair-läs med samma fil i `wexoebuilder/`.
- `wexoebuilder/CLAUDE.md` — bygg-sidan av samma system. Page-type-ramverket, Claude-transform-mellanlaget, SSOT-redigerare.
- `ARKITEKTURPLAN.md` — kanonisk, framåtblickande arkitekturplan (modularisering: single-source-schema, deterministisk save, delade block). Spegelidentisk i båda repona. (Ersätter den slutförda `MIGRATION-PLAN.md`; migrationshistoriken finns i `IMPLEMENTATION_LOG.md`.)
- `IMPLEMENTATION_LOG.md` — löpande logg av migrationsåtgärder. Historik, inte API.
- `wexoe-core/src/Core.php` — koden själv. Doc-kommentarerna är auktoritativa när dokumentation och kod skiljer sig åt.
