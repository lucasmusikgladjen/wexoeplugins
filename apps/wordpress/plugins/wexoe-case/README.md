# Wexoe Case

WordPress-plugin som renderar kundcase i editorial artikel-format. En enhetlig artikelspalt med sticky "Caset i korthet"-sidebar genom hela sidan. Data hämtas från Airtable-tabellen `cms_cases` (Wexoe NY) via `wexoe-core`.

## Krav

- `wexoe-core` ≥ 0.9.0 måste vara aktivt (annars visas felmeddelande).
- Entity-schema `cms_cases` måste finnas i `wexoe-core/entities/`.

## Användning

Skapa en WP-sida och lägg in shortcode:

```
[wexoe_case slug="test"]
```

### Parametrar

| Parameter | Default | Beskrivning |
|---|---|---|
| `slug`  | *(krävs)* | Slug-värdet på `cms_cases`-recordet. |
| `debug` | `false`   | `true` → dumpar `$data`-arrayen via `print_r` istället för normal rendering. Bra för att inspektera vad Core levererar. |

### Exempel

```html
<!-- Normal rendering -->
[wexoe_case slug="arla-gotene"]

<!-- Debug-läge -->
[wexoe_case slug="arla-gotene" debug="true"]
```

## Fält → sektion

Alla fält bor på `cms_cases` (`tblxH3ECSMvDTYrIQ`) i Wexoe NY. Schemat finns i `wexoe-core/entities/cms_cases.php` — använd det som auktoritativ fältlista.

| Sektion i prototypen | Styrande fält i `cms_cases` |
|---|---|
| Header (mörk full-bleed) | `industry`, `title`, `subtitle`, `customer_name`, `location`, `project_year`, `project_type`, `reading_time`, `header_logos` |
| Lead | `lead_image_url`, `lead_image_caption`, `lead_paragraph` |
| Snabba siffror (stats strip) | `show_stats_strip`, `quick_stats[]` (pseudo-array, count: 4) |
| Utmaningen | `challenge_eyebrow`, `challenge_title`, `challenge_text`, `challenge_bullets`, `challenge_image_url`, `challenge_image_caption` |
| Pull quote | `show_pullquote`, `pullquote_text`, `pullquote_attribution` |
| Lösningen (text) | `solution_eyebrow`, `solution_title`, `solution_text` |
| Produkter i lösningen | `products_title`, `products_meta`, `product_ids` (cms_products), `article_ids` (cms_articles) |
| Lösningen (arkitekturbild) | `solution_image_url`, `solution_image_caption` |
| Resultatet | `results_eyebrow`, `results_title`, `results_text`, `results[]` (pseudo-array, count: 4) |
| Kundens röst | `show_testimonial`, `testimonial_quote`, `testimonial_photo_url`, `testimonial_author_name`, `testimonial_author_title` |
| Bildberättelse | `show_gallery`, `gallery_title`, `gallery_images[]` (pseudo-array, count: 6) |
| Om kunden | `show_about_customer`, `about_customer_logo_url`, `about_customer_title`, `about_customer_text`, `about_customer_link_label`, `about_customer_url` |
| Caset i korthet (sticky sidebar) | `glance_challenge`, `glance_solution`, `glance_result` |
| Kontaktformulär | `show_contact_form` + alla `contact_form_*`-fält (mappas till `Core::renderer('contact-form')`) |

### Synlighetstoggles

Sektioner är dolda när motsvarande boolean är `false`:

- `show_stats_strip` — döljer även stats-strippen om `quick_stats` är tom.
- `show_pullquote`
- `show_testimonial`
- `show_gallery` — döljer även galleriet om `gallery_images` är tom.
- `show_about_customer`
- `show_contact_form`

`is_active` på själva recordet styr hela sidan: när det är `false` returnerar shortcoden felmeddelande och inget innehåll renderas.

### Polymorf produktlista

`product_ids` och `article_ids` renderas i samma `.case-products-grid`. Posterna hämtas via `Core::entity('products')` respektive `Core::entity('articles')`. Inaktiva poster (`is_active === false`) filtreras bort.

Per linkad post:
- **Bild** ← `image_url`
- **Brand** ← supplier-namnet via `core_partners` (`name`-fältet på första `supplier_ids`)
- **Namn** ← `name`
- **Roll/beskrivning** ← `description`, trunkerad till ~120 tecken
- **Artikelnummer** (bara cms_articles) ← visas som secondary label
- **Länk** ← `button_1_url` (products) eller `webshop_url` (articles); `#` om saknad

Visuellt:
- 1 produkt → 1 cell, full bredd inom boxen
- 2+ produkter → 2-kolumns grid (på mobil: 1 kolumn)
- Hairlines mellan celler via `gap: 1px` + grid-bg-trick (ingen villkorlig CSS)

## SEO

Tre fält styr metadata: `seo_title`, `seo_description`, `og_image_url`.

- `<title>` sätts via `document_title_parts`-filtret.
- `<meta name="description">`, `og:title`, `og:description`, `og:image` skrivs via `wp_head`.
- Om `seo_title` är tom används `title` som fallback.

Pluginet letar efter `[wexoe_case slug="..."]` i WP-postens content för att avgöra om SEO-metadata ska skrivas — så fungerar det också om shortcoden är inbäddad inuti annan markup.

## Tekniska detaljer

- CSS scopas via en unik wrapper `#wexoe-case-XXXXXXXX` (genererad per render). Två instanser av shortcoden på samma sida krockar inte.
- Inget `!important`. Ingen global CSS.
- Markdown-rendering via `Wexoe\Core\Helpers\Markdown` — `to_html` för längre brödtext (lead, challenge, solution, results, about), `to_inline` för pull quote, testimonial, glance-block.
- Lines-fält (`header_logos`, `challenge_bullets`) splittras via `Wexoe\Core\Helpers\Lines`.
- Saknade länkade poster hoppas tyst över (`find_by_ids` returnerar bara existerande).
- Partner-namn cacheas per request — samma supplier i flera produkter triggar bara en lookup.

## Verifiering

1. Aktivera pluginet i WP-admin (efter `wexoe-core`).
2. Skapa en testsida med shortcode `[wexoe_case slug="test"]`.
3. Kontrollera att alla sektioner renderar enligt prototypen.
4. Toggla `show_pullquote` av i Airtable, kör cache-clear via `Core::entity('cms_cases')->force_refresh()`, ladda om — pull quote ska försvinna.
5. Toggla `is_active` av → shortcoden ska visa felmeddelande.
6. Lägg två `[wexoe_case slug="..."]` på samma sida med olika slugs — båda ska rendera utan CSS-krock.

## Filer

- `wexoe-case.php` — hela pluginet (shortcode, rendering, scoped CSS, SEO).
- `README.md` — den här filen.

## Vidare

- **FAS 1** (klar): Airtable-tabell + `wexoe-core/entities/cms_cases.php`.
- **FAS 2** (detta plugin): PHP-rendering på WP.
- **FAS 3** (separat repo `wexoebuilder`): Editor-UI i Next.js.

Se `SKAPA-SIDA.md` i wexoeplugins-repots root för hela flödet.
