# Wexoe Core — utvecklingsguide för plugin-migration

Du ska migrera gamla Wexoe WordPress-plugins till att använda Wexoe Core som datalager. Denna guide innehåller allt du behöver. Du behöver INTE Core:s källkod som kontext.

---

## 1. Vad Core är

Wexoe Core är ett aktivt WordPress-plugin som hanterar all kommunikation med Airtable. Det exponerar ett publikt API via klassen `\Wexoe\Core\Core` och fyra helper-klasser under `\Wexoe\Core\Helpers\*`. Feature-plugins ska aldrig prata med Airtable direkt.

---

## 2. Publikt API

### 2.1 Entity-repository

```php
use Wexoe\Core\Core;

// Hämta repository för en entitet
$repo = Core::entity('landing_pages');  // returnerar EntityRepository|null

// Hitta en post via primärnyckel (definierad i schemat)
$page = Core::entity('landing_pages')->find('fjarraccess');

// Alla poster (valfritt med filter)
$all = Core::entity('partners')->all();
$visible = Core::entity('lp_tabs')->all(['visa' => true]);

// Hitta första post där ett fält har ett visst värde
$tab = Core::entity('lp_tabs')->find_by('tab_type', 'faq');

// Resolva linked records — tar array av Airtable record-IDs,
// returnerar normaliserade poster i samma ordning
$tabs = Core::entity('lp_tabs')->find_by_ids($page['tab_ids']);

// Cache-hantering (används sällan i feature-plugins)
Core::entity('landing_pages')->clear_cache();
Core::entity('landing_pages')->force_refresh();
```

**Viktigt:** `Core::entity()` returnerar `null` om schemat inte finns. Feature-plugins bör kolla detta vid uppstart.

### 2.2 Normaliserat output-format

Alla metoder returnerar associativa arrays med domänfält (aldrig Airtable-fältnamn). Varje post har ett extra `_record_id`-fält med Airtable:s record-ID (`recXXX...`).

```php
$partner = Core::entity('partners')->find('Rockwell');
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

### 2.3 Helpers

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
Color::is_dark('#ffffff');             // false
Color::text_color('#11325D');          // '#ffffff'
Color::text_color('#F5F6F8');          // '#000000'

// YouTube
YouTube::extract_id('https://youtu.be/dQw4w9WgXcQ');              // 'dQw4w9WgXcQ'
YouTube::extract_id('https://youtube.com/watch?v=dQw4w9WgXcQ');   // 'dQw4w9WgXcQ'
YouTube::extract_id('dQw4w9WgXcQ');                                // 'dQw4w9WgXcQ'
YouTube::extract_id('ogiltig');                                    // null
YouTube::render_embed('dQw4w9WgXcQ');   // responsiv iframe (youtube-nocookie.com, lazy loading)
YouTube::thumbnail_url('dQw4w9WgXcQ');  // https://i.ytimg.com/vi/.../hqdefault.jpg

// Lines (multi-line text ↔ array)
Lines::to_array("rad 1\nrad 2\n\nrad 3");  // ['rad 1', 'rad 2', 'rad 3']
Lines::first("rad 1\nrad 2");              // 'rad 1'
Lines::from_array(['a', 'b', 'c']);        // "a\nb\nc"
```

### 2.4 Core-beroendekontroll

Feature-plugins ska kontrollera att Core är aktivt:

```php
function my_plugin_core_ready() {
    return class_exists('\\Wexoe\\Core\\Core')
        && method_exists('\\Wexoe\\Core\\Core', 'entity');
}
```

Anropa i shortcode-funktionen och visa ett tydligt felmeddelande om Core saknas.

---

## 3. Schema-filformat

Scheman bor i `wp-content/plugins/wexoe-core/entities/{namn}.php`. Varje fil returnerar en array:

```php
<?php
if (!defined('ABSPATH')) exit;

return [
    'table_id' => 'tblXXXXXXXXXXXXXXX',     // Airtable table ID
    'primary_key' => 'slug',                   // domänfält för find()
    'cache_ttl' => 86400,                      // sekunder (default 24h)
    'required' => ['slug', 'name'],            // poster utan dessa filtreras bort
    'fields' => [
        // Enkel passthrough: domänfält => Airtable-fältnamn
        'name' => 'Name',
        'slug' => 'Slug',

        // Typat fält
        'visible' => ['source' => 'Visa', 'type' => 'bool'],
        'order' => ['source' => 'Order', 'type' => 'float'],
        'description' => ['source' => 'Description', 'type' => 'string'],

        // Multi-line text → array av strängar
        'benefits' => ['source' => 'Benefits', 'type' => 'lines'],

        // Linked records → array av record-IDs
        'tab_ids' => ['source' => 'LP Tabs', 'type' => 'link', 'entity' => 'lp_tabs'],

        // Attachment (första bilden)
        'hero_image' => ['source' => 'Image', 'type' => 'attachment'],

        // Pseudo-array (numrerade Airtable-fält → array av objekt)
        'sections' => [
            'type' => 'pseudo_array',
            'prefix' => 'Normal',    // fältnamn: "Normal 1 H2", "Normal 2 H2", ...
            'count' => 4,
            'fields' => [
                'h2' => 'H2',       // → "Normal 1 H2", "Normal 2 H2", etc.
                'text' => 'Text',
                'image' => 'Image',
            ],
        ],
    ],
];
```

**Regler:**
- Filnamnet = entity-namnet. `landing_pages.php` → `Core::entity('landing_pages')`.
- Bara lowercase `a-z`, `0-9`, `_` i filnamn.
- `primary_key` måste referera till ett fält i `fields`.
- `required`-fält valideras — poster som saknar dem loggas som warning och filtreras bort.
- Linked record-fält behöver `entity`-attributet bara som dokumentation. `find_by_ids()` kräver att du anger entity-namn explicit.

---

## 4. Migrationsrecept

### 4.1 Vad som tas bort

Varje gammalt plugin har typiskt dessa delar som ska bort:

```
✗ Hårdkodade API-nycklar och Base ID (WEXOE_LP_AIRTABLE_API_KEY etc.)
✗ Konstant-definitioner för tabellnamn och cache TTL
✗ Egen airtable_request()-funktion
✗ Egen fetch_page() / fetch_linked()-logik
✗ All transient set/get/delete-kod
✗ Egen pagination-logik (offset-loopar)
✗ function_exists()-guards för delade funktioner
✗ Lokala kopior av: markdown, hex-validering, youtube-id, lines_to_array
```

### 4.2 Vad som behålls

```
✓ Plugin-header (uppdatera version + description)
✓ All CSS (pixelidentisk — kopiera verbatim)
✓ All JavaScript (pixelidentisk)
✓ Shortcode-registrering (add_shortcode)
✓ Section-renderers (HTML-generering)
✓ Parsers som är rendering-specifika (FAQ Q/A-parser, compare-rows, steps)
✓ wexoe_lp_field()-helper (safe getter med default)
```

### 4.3 Vad som ändras

**Fältreferenser:** Alla Airtable-fältnamn (`'Hero CTA Text'`, `'Content Benefits'`) byts till domänfält (`'hero_cta_text'`, `'content_benefits'`). Domänfälten definieras i schemat.

**Datahämtning:** Från `wexoe_lp_fetch_page($slug)` till `Core::entity('landing_pages')->find($slug)`.

**Linked records:** Från `wexoe_lp_fetch_linked($table, $ids, $prefix)` till `Core::entity('lp_tabs')->find_by_ids($ids)`.

**Hjälpfunktioner:**
- `wexoe_lp_md($text)` → `Markdown::to_inline($text)`
- `wexoe_lp_hex($val, $default)` → `Color::normalize_hex($val) ?? $default`
- `wexoe_lp_youtube_id($url)` → `YouTube::extract_id($url)`
- `wexoe_lp_lines_to_array($text)` → schema-typ `lines` gör det automatiskt; för manuell konvertering: `Lines::to_array($text)`

**Cache-rensning:** Från direkt SQL (`DELETE FROM options WHERE option_name LIKE '_transient_wexoe_lp_%'`) till `Core::entity('landing_pages')->clear_cache()`.

**Synlighetsfiltrering och sortering:** Core normaliserar `Visa`-checkbox till `bool` och `Order` till `float`, men filtrering och sortering görs i feature-pluginet:

```php
$all_tabs = Core::entity('lp_tabs')->find_by_ids($page['tab_ids']);

// Filtrera synliga
$tabs = array_filter($all_tabs, function($t) { return !empty($t['visa']); });

// Sortera
usort($tabs, function($a, $b) {
    return ($a['order'] ?? 999) - ($b['order'] ?? 999);
});
$tabs = array_values($tabs);
```

### 4.4 Test-version för parallellkörning

Vid migration, döp om allt för att undvika kollision med det gamla pluginet:

- Plugin Name: `Wexoe Landing Page` → `Wexoe Landing Page TEST`
- Shortcode: `wexoe_landing` → `wexoe_landing_test`
- Alla funktioner: `wexoe_lp_*` → `wexoe_lp_test_*`

Det möjliggör att båda plugins är aktiva samtidigt. Test-sidan använder `[wexoe_landing_test slug="..."]`, jämförs visuellt med originalet.

---

## 5. Steg-för-steg: migration av ett plugin

1. **Läs det gamla pluginet.** Identifiera: vilka Airtable-tabeller det använder, vilka fält som refereras, vilka hjälpfunktioner som finns, hur cache hanteras.

2. **Kolla om entitets-scheman redan finns i Core.** Befintliga: `partners`, `product_areas`, `landing_pages`, `lp_tabs`, `lp_downloads`. Om schemat saknas — skriv ett nytt (se avsnitt 3). Hämta Airtable-fältnamn och table IDs via Airtable MCP `list_tables_for_base` mot base `appokKSTaBdCa8YiW` (Wexoe NY) eller `appXoUcK68dQwASjF` (Wexoe gammal).

3. **Skriv den nya plugin-filen.** Behåll CSS, JS, och rendering-logik. Byt all datahämtning till Core. Byt alla fältreferenser till domänfält. Byt lokala helpers till Core Helpers.

4. **Döp om till test-version** (ändra plugin-namn, shortcode, funktionsnamn) för parallellkörning.

5. **Syntax-kontrollera** med `php -l`.

6. **Leverera** som zip (mapp med plugin-fil inuti) + separat schema-fil(er) om nya entiteter behövdes.

---

## 6. Vanliga misstag att undvika

**Referera inte till Airtable-fältnamn i feature-plugins.** Aldrig `$data['Hero CTA Text']`. Alltid `$data['hero_cta_text']`. Airtable-fältnamn finns bara i schema-filer.

**Anta inte att `lines`-fält är strängar.** Om schemat definierar typen `lines` returnerar Core en array. Använd `foreach`, inte `Lines::to_array()` igen.

**Glöm inte att `bool`-fält aldrig är null.** De är `true` eller `false`. Gamla plugins kollade ofta `!empty($data['Show Tabs'])` — det fungerar fortfarande, men `$data['show_tabs']` är redan en boolean.

**`find_by_ids()` returnerar bara poster som finns i cachen.** Om en linked record-ID pekar på en post som inte finns (raderad i Airtable), hoppas den över tyst. Kontrollera inte mot ID-listan — iterera över det som returneras.

**CSS ska kopieras byte-för-byte.** Försök inte "förbättra" eller refaktorera CSS:en vid migration. Målet är pixelidentisk output. CSS-förbättringar görs separat.

**Behåll `wexoe_lp_field()` eller motsvarande i feature-pluginet.** Det är en rendering-helper (safe getter med default), inte en Airtable-helper. Den behövs fortfarande.

---

## 7. Referens: existerande scheman

### partners (entities/partners.php)
- Table: `tblsCOF5BPAxN6nmq`
- Primary key: `name`
- Fält: `name`, `logo_url`, `logo_transparent_url`, `division_ids` (link), `campaign_ids` (link), `deliverable_ids` (link), `activity_ids` (link), `article_ids` (link)

### product_areas (entities/product_areas.php)
- Table: `tblgatNFYFMwF4EcQ`
- Primary key: `slug`
- 39 fält inklusive `sections` (pseudo_array med prefix "Normal", count 4)
- Booleans: `use_side_menu`, `show_request`, `default_open`
- Lines: `hero_benefits`
- Links: `product_ids`, `solution_ids`, `division_ids`

### landing_pages (entities/landing_pages.php)
- Table: `tbl8KDqGq0Ray1uqS`
- Primary key: `slug`
- 43 fält: hero, content, sidebar (case/calculator/event/leadmagnet), contact, colors, visibility toggles
- Lines: `content_benefits`, `case_outcomes`
- Booleans: `show_content`, `show_sidebar`, `show_contact`, `show_tabs`
- Links: `tab_ids` → lp_tabs

### lp_tabs (entities/lp_tabs.php)
- Table: `tblvecOh3rAGmw3mw`
- Ingen primary key (uppslag via `find_by_ids`)
- Polymorfa tab-typer: textimage, fullmedia, faq, calameo, downloads, compare, steps
- Pseudo-array: `calameos` (prefix "Calameo", count 3)
- Lines: `ti_benefits`
- Bool: `visa`, `ti_inverted`
- Links: `download_ids` → lp_downloads

### lp_downloads (entities/lp_downloads.php)
- Table: `tblbLM827DzjWGjCR`
- Ingen primary key
- Fält: `name`, `description`, `thumbnail`, `file_url`, `button_text`, `order` (float), `visa` (bool)
