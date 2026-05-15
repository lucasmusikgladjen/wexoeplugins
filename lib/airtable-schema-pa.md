# Airtable Schema — Wexoe Product Areas

**Base ID:** `appokKSTaBdCa8YiW` (Wexoe NY)

Detta schema används av Claude-middlemannen för att transformera
state-JSON till Airtable-fältnamn när en Produktområdes-sida (Product Area)
skapas eller uppdateras via buildern. Post-migration: snake_case överallt.

PA-familjen består av fyra tabeller:
- `cms_product_pages` — huvudrecorden (PA)
- `cms_product_page_sections` — innehållssektioner (gamla "Normal 1-4"-pseudo-array)
- `cms_products` — produktblock som listas på en PA
- `cms_solutions_mini` — lösnings-/koncept-kort som listas på en PA

Articles (`cms_articles`) är read-only i buildern och rörs aldrig av save-flödet.

---

## Tabell 1: cms_product_pages

**Table ID:** `tbl5PQR7FNHCogeya`

Huvudrecorden. Innehåller ~50 direktfält samt linked-record-fält för
section_ids, product_ids, solution_ids, division_ids, country_ids.

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **slug** | singleLineText | Primary key. URL-slug, lowercase a-z, 0-9, bindestreck. |
| **internal_notes** | multilineText | Redaktörs-notering. Visas inte publikt. |
| **is_active** | checkbox | False = sidan returnerar tom shortcode. |
| **country_ids** | multipleRecordLinks | core_countries-länk. **Hanteras av backend — inkludera ej.** |
| **division_ids** | multipleRecordLinks | core_divisions-länk. **Hanteras av backend — inkludera ej.** |
| **name** | singleLineText | Visningsnamn ("Fibernät", "Frekvensomriktare"). |
| **h1** | singleLineText | Sidans huvudrubrik. |
| **top_bg** | singleLineText | Hex-color för top-banner. |
| **hero_h2** | singleLineText | Underrubrik i hero. |
| **hero_text** | multilineText | Brödtext i hero (markdown). |
| **hero_cta_text** | singleLineText | Primär CTA. |
| **hero_cta_url** | singleLineText | |
| **hero_benefits** | multilineText | En benefit per rad — renderas som checkmark-lista. |
| **hero_image_url** | singleLineText | URL till hero-bakgrundsbild. |
| **hero_bg** | singleLineText | Hex-color. |
| **hero_accent** | singleLineText | Hex-color. |
| **npi_title** | singleLineText | NPI-kortets titel (i hero-högerkolumn). |
| **npi_description** | multilineText | |
| **npi_image_url** | singleLineText | URL till produktbild. |
| **npi_link** | url | URL som NPI-kortet klickar till. |
| **toggle_bg** | singleLineText | Produktsektionens bakgrundsfärg. |
| **toggle_header_bg** | singleLineText | Produktkortens headers-färg. |
| **toggle_accent** | singleLineText | Accent-färg (orange). |
| **solutions_title** | singleLineText | Solutions-griden rubrik, default "Lösningar & koncept". |
| **solutions_bg** | singleLineText | Hex-color. |
| **solutions_card_bg** | singleLineText | Hex-color. |
| **contact_name** | singleLineText | Kontaktperson. |
| **contact_title** | singleLineText | |
| **contact_email** | email | |
| **contact_phone** | phoneNumber | |
| **contact_image_url** | singleLineText | URL. |
| **contact_text** | multilineText | Quote/bio. |
| **contact_bg** | singleLineText | Hex-color. |
| **docs_title** | singleLineText | Rubrik ovanför dokumentations-iframe. |
| **docs_iframe** | url | Iframe src-URL. |
| **docs_bg** | singleLineText | Hex-color. |
| **use_side_menu** | checkbox | Ska ALLTID inkluderas. Togglar sidomeny-layout. |
| **show_request** | checkbox | Ska ALLTID inkluderas. Togglar prisförfrågan-formulär. |
| **default_open** | checkbox | Ska ALLTID inkluderas. |
| **section_ids** | multipleRecordLinks | cms_product_page_sections-länk. **Hanteras av backend — inkludera ej.** |
| **product_ids** | multipleRecordLinks | cms_products-länk. **Hanteras av backend — inkludera ej.** |
| **solution_ids** | multipleRecordLinks | cms_solutions_mini-länk. **Hanteras av backend — inkludera ej.** |

---

## Tabell 2: cms_product_page_sections

**Table ID:** `tbl1r3T3ukIPJ0S3N`

Innehållssektioner (gamla "Normal 1-4"). Buildern hanterar fortfarande
fyra slots i state (`normal1` … `normal4`); backend extraherar och skapar
upp till fyra sub-records per save.

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **name** | singleLineText | Internt namn, t.ex. "<page slug>: <h2>". |
| **internal_notes** | multilineText | |
| **is_active** | checkbox | False = sektionen visas inte. |
| **order** | number | Renderingsordning. Lägre = visas först. |
| **h2** | singleLineText | Sektionens rubrik. |
| **text** | multilineText | Brödtext, markdown. |
| **bullets** | multilineText | En punkt per rad. |
| **image_url** | singleLineText | URL till bild. |
| **bg** | singleLineText | Hex-färg. |
| **reversed** | checkbox | Bild vänster, text höger. |
| **shown_top** | checkbox | Sektion visas över hero/toggle (gamla "upp"). |
| **product_page_ids** | multipleRecordLinks | Back-link till cms_product_pages. **Hanteras av backend.** |

---

## Tabell 3: cms_products

**Table ID:** `tblN23V7uAMpeZoO1`

Products är linkade records som representerar produkter på en PA.
En produkt kan listas på flera PA:n (delas via product_ids).

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **name** | singleLineText | Produktnamn. |
| **internal_notes** | multilineText | |
| **is_active** | checkbox | Ska ALLTID inkluderas. `false` döljer produkten. |
| **order** | number | 1-baserat. PHP-pluginet sorterar produkter på detta fält. |
| **ecosystem_description** | singleLineText | Kort etikett bredvid produktnamnet. |
| **description** | multilineText | Brödtext, markdown. |
| **bullets** | multilineText | En punkt per rad. |
| **image_url** | singleLineText | URL. |
| **button_1_text** | singleLineText | Sekundär knapps text. |
| **button_1_url** | singleLineText | |
| **button_2_text** | singleLineText | Primär knapps text. |
| **button_2_url** | singleLineText | |
| **horizontal** | checkbox | Ska ALLTID inkluderas. Togglar horisontell layout. |
| **header_side_menu** | singleLineText | Sidomeny-rubrik. Fallback: `name`. |
| **product_page_ids** | multipleRecordLinks | Back-link till PA. **Hanteras av backend.** |
| **article_ids** | multipleRecordLinks | Länk till cms_articles. **Read-only i buildern — inkludera ej.** |
| **supplier_ids** | multipleRecordLinks | core_partners-länk (valfritt). |

---

## Tabell 4: cms_solutions_mini

**Table ID:** `tblxK7ikOgLFuze6m`

Solutions är kort som renderas i en grid nedanför produktsektionen.

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **name** | singleLineText | Lösningens titel. |
| **internal_notes** | multilineText | |
| **is_active** | checkbox | Ska ALLTID inkluderas. |
| **order** | number | 1-baserat, för sortering. |
| **category** | singleLineText | Visas som uppercase-label ovanför titeln. |
| **image_url** | singleLineText | URL. |
| **url** | url | Hela kortets klick-länk. |
| **description** | multilineText | Kort beskrivning. |
| **cta_text** | singleLineText | Knapptext, default "Läs mer". |
| **product_page_ids** | multipleRecordLinks | Back-link till PA. **Hanteras av backend.** |

---

## Tabell 5: core_divisions (endast read/link)

**Table ID:** `tblyxs2zsoRBozxQS`

SSOT-tabellen med Wexoes affärsområden. Buildern **skapar aldrig** nya
divisioner — användaren väljer en befintlig.

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **name** | singleLineText | Divisionsnamn. |

---

## Tabell 6: cms_articles (read-only)

**Table ID:** `tblhnz3MQG1JwfKrN`

Articles är artiklar (produktvarianter) som hör till en produkt. Buildern
visar dem som en read-only lista — aldrig editerbar och aldrig skriven av
save-flödet. Dokumenteras för fullständighet.

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **name** | singleLineText | Artikelnamn. |
| **article_number** | singleLineText | Unikt artikelnummer. |
| **description** | multilineText | |
| **datasheet_url** | url | URL till datablad. |
| **webshop_url** | url | URL till webshop. |
| **image_url** | singleLineText | URL. |
| **variants** | multilineText | Variant-definition. |
| **product_ids** | multipleRecordLinks | Back-link till cms_products. |
| **supplier_ids** | multipleRecordLinks | core_partners-länk. |

---

## Formateringsregler

Claude ska **tolka** och **städa** user-edited data innan den går till Airtable:

1. **Utelämna tomma fält** (tomma strängar, null) vid CREATE. Vid UPDATE kan
   backend sätta explicit `""` för fält som ska rensas — Claude behöver inte
   bry sig om det.

2. **bullets, hero_benefits, sections.bullets:** Om input ser ut som en paragraf
   eller kommaseparerad lista, splitta till en per rad (`\n`).

3. **Boolean-fält** (`use_side_menu`, `show_request`, `default_open`, `is_active`,
   `horizontal`, `reversed`, `shown_top`, `show_contact_form`, alla
   `contact_form_show_*`-checkboxes) ska ALLTID inkluderas, även `false`.

4. **Hex-färger** (alla `*_bg`, `*_accent`, `top_bg`, etc.): om användaren har
   satt en färg, skicka den som `#RRGGBB`. Om tom, utelämna vid CREATE.

5. **Linkade records** (`product_ids`, `solution_ids`, `section_ids`,
   `division_ids`, `country_ids`, `product_page_ids`, `article_ids`,
   `supplier_ids`): **Inkludera ALDRIG** i Claudes output. Backend
   hanterar all linked-record-logik.

6. **Contact Form-fält:** Alla 15 `contact_form_*`-fält ska ALLTID inkluderas
   i `productArea.fields` vid UPDATE (även tomma eller false). `contact_form_layout`
   = `split` eller `centered`. `contact_form_theme` = `dark` eller `light`.
   Trust signals format: `**Bold** | Resten` per rad, max 3.

---

## Contact Form-fält (cms_product_pages-tabellen)

Samma 15 fält som i Landing Pages, renderas av `\Wexoe\Core\Renderers\ContactForm`
sist på PA-sidan när `show_contact_form` = true.

| Fältnamn | Typ |
|---|---|
| `show_contact_form` | checkbox |
| `contact_form_eyebrow` | singleLineText |
| `contact_form_title` | singleLineText |
| `contact_form_subtitle` | multilineText |
| `contact_form_layout` | singleSelect (`split` / `centered`) |
| `contact_form_theme` | singleSelect (`dark` / `light`) |
| `contact_form_show_company` | checkbox |
| `contact_form_show_phone` | checkbox |
| `contact_form_show_dropdown` | checkbox |
| `contact_form_dropdown_label` | singleLineText |
| `contact_form_options` | multilineText (en per rad) |
| `contact_form_cta_text` | singleLineText |
| `contact_form_message_label` | singleLineText |
| `contact_form_trust_signals` | multilineText (`**Bold** \| Text`, max 3) |
| `contact_form_show_contact_person` | checkbox |
