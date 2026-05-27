# Airtable Schema — Wexoe Cases

**Base ID:** `appokKSTaBdCa8YiW` (Wexoe NY)

Detta schema används av Claude-middlemannen för att transformera state-JSON
till Airtable-fältnamn när ett Case (Customer Case, editorial artikel-format)
skapas eller uppdateras via buildern. snake_case överallt — både i Airtable
display-namn och här.

Sidtypen har bara en tabell — `cms_cases`. Pseudo-arrays (quick_stats,
results, gallery_images) lagras som numrerade fält direkt på recordet.
Linkade records (product_ids, article_ids) är `multipleRecordLinks` till
`cms_products` resp. `cms_articles`.

---

## Tabell 1: cms_cases

**Table ID:** `tblxH3ECSMvDTYrIQ`

### Core / publicering

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **slug** | singleLineText | Primary key. URL-slug, lowercase a-z, 0-9, bindestreck. |
| **internal_notes** | multilineText | Redaktörs-anteckning. ECHO värdet från input — Claude ska inte filtrera bort det. |
| **is_active** | checkbox | Publiceringsflagga. **Ska ALLTID inkluderas**, även `false`. |

### SEO

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **seo_title** | singleLineText | Sidtitel (fallback till `title` om tom — sker i PHP, inte här). |
| **seo_description** | multilineText | Meta-description. |
| **og_image_url** | singleLineText | URL till open-graph-bild. |

### Header

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **industry** | singleLineText | Liten tagg i eyebrow (t.ex. "Livsmedelsindustri"). |
| **title** | singleLineText | H1. |
| **subtitle** | multilineText | Bröd under H1. |
| **customer_name** | singleLineText | T.ex. "Arla Foods AB". |
| **location** | singleLineText | T.ex. "Götene, Sverige". |
| **project_year** | singleLineText | T.ex. "2025". |
| **project_type** | singleLineText | T.ex. "Retrofit / Modernisering". |
| **reading_time** | singleLineText | T.ex. "6 min". |
| **header_logos** | multilineText | Lines-fält: en URL per rad. Skicka som ren sträng — Core's Normalizer expanderar vid läsning. |

### Lead

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **lead_image_url** | singleLineText | Hero-bildens URL. |
| **lead_image_caption** | singleLineText | Bildtext. |
| **lead_paragraph** | multilineText | Ingresstext med markdown. Drop-cap appliceras av PHP-pluginet. |

### Stats strip (toggle)

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **show_stats_strip** | checkbox | **ALLTID inkluderas**. |
| **quick_stat_1_value** … **quick_stat_4_value** | singleLineText | Värde (t.ex. "80 %"). |
| **quick_stat_1_label** … **quick_stat_4_label** | singleLineText | Etikett (t.ex. "snabbare batchbyten"). |

### Challenge

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **challenge_eyebrow** | singleLineText | Default: "Utmaningen". |
| **challenge_title** | singleLineText | H2. |
| **challenge_text** | multilineText | Markdown. |
| **challenge_bullets** | multilineText | Lines-fält: en bullet per rad. |
| **challenge_image_url** | singleLineText | Bild-URL. |
| **challenge_image_caption** | singleLineText | Bildtext. |

### Pullquote (toggle)

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **show_pullquote** | checkbox | **ALLTID inkluderas**. |
| **pullquote_text** | multilineText | Citattexten (markdown inline). |
| **pullquote_attribution** | singleLineText | T.ex. "— Johan Berg, Lead Engineer". |

### Solution

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **solution_eyebrow** | singleLineText | Default: "Lösningen". |
| **solution_title** | singleLineText | H2. |
| **solution_text** | multilineText | Markdown. |
| **solution_image_url** | singleLineText | Arkitekturbild-URL. Renderas av pluginet NEDANFÖR produktlistan. |
| **solution_image_caption** | singleLineText | Bildtext. |

### Products

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **products_title** | singleLineText | Default: "Produkter i lösningen". |
| **products_meta** | singleLineText | T.ex. "Levererat av Wexoe". |
| **product_ids** | multipleRecordLinks | Länk till cms_products. Skicka array av string rec-IDs. |
| **article_ids** | multipleRecordLinks | Länk till cms_articles. Skicka array av string rec-IDs. |

### Results

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **results_eyebrow** | singleLineText | Default: "Resultatet". |
| **results_title** | singleLineText | H2. |
| **results_text** | multilineText | Markdown. |
| **result_1_value** … **result_4_value** | singleLineText | Värde. |
| **result_1_label** … **result_4_label** | singleLineText | Etikett. |

### Testimonial (toggle)

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **show_testimonial** | checkbox | **ALLTID inkluderas**. |
| **testimonial_quote** | multilineText | Citatet (markdown inline). |
| **testimonial_photo_url** | singleLineText | Foto-URL. |
| **testimonial_author_name** | singleLineText | Författarens namn. |
| **testimonial_author_title** | singleLineText | Författarens titel. |

### Gallery (toggle)

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **show_gallery** | checkbox | **ALLTID inkluderas**. |
| **gallery_title** | singleLineText | H3. |
| **gallery_image_1_url** … **gallery_image_6_url** | singleLineText | URL till bild. |
| **gallery_image_1_caption** … **gallery_image_6_caption** | singleLineText | Bildtext. |

### About customer (toggle)

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **show_about_customer** | checkbox | **ALLTID inkluderas**. |
| **about_customer_logo_url** | singleLineText | Logo-URL. |
| **about_customer_title** | singleLineText | H3. |
| **about_customer_text** | multilineText | Markdown. |
| **about_customer_link_label** | singleLineText | T.ex. "Läs mer om Arla". |
| **about_customer_url** | singleLineText | T.ex. https://www.arla.se. |

### Glance sidebar (sticky — visas alltid)

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **glance_challenge** | multilineText | Kort sammanfattning, markdown inline. |
| **glance_solution** | multilineText | — || — |
| **glance_result** | multilineText | — || — |

### Contact form (toggle)

Samma 15 fält som i Customer Type / Landing Page / Product Area. Renderas av
`\Wexoe\Core\Renderers\ContactForm` sist på sidan när `show_contact_form` = true.

| Fältnamn | Typ |
|---|---|
| `show_contact_form` | checkbox |
| `contact_form_eyebrow` | singleLineText |
| `contact_form_title` | singleLineText |
| `contact_form_subtitle` | multilineText |
| `contact_form_layout` | singleLineText (`split` / `centered`) |
| `contact_form_theme` | singleLineText (`dark` / `light`) |
| `contact_form_show_company` | checkbox |
| `contact_form_show_phone` | checkbox |
| `contact_form_show_dropdown` | checkbox |
| `contact_form_dropdown_label` | singleLineText |
| `contact_form_options` | multilineText (en per rad) |
| `contact_form_cta_text` | singleLineText |
| `contact_form_message_label` | singleLineText |
| `contact_form_trust_signals` | multilineText (`**Bold** \| Text`, max 3) |
| `contact_form_show_contact_person` | checkbox |

---

## Formateringsregler

1. **CREATE-mode** — utelämna fält med tomt värde (tomma strängar, null). MEN
   inkludera ALLTID boolean-fält (alla `show_*`, `is_active`, alla
   `contact_form_show_*`-checkboxar) även om `false`.

2. **UPDATE-mode** — inkludera ALLA fält från input (även tomma) som `""` så
   Airtable rensar dem. Inkludera ALLTID alla 15 `contact_form_*`-fält och
   alla pseudo-array-fält upp till sin `count` även när items i state är
   färre — slutar arrayen vid 2 items ska `quick_stat_3_value`,
   `quick_stat_3_label`, `quick_stat_4_value`, `quick_stat_4_label`
   skickas som `""`.

3. **Pseudo-arrays expanderas till numrerade fält:**
   - `quick_stats[i]` → `quick_stat_${i+1}_value`, `quick_stat_${i+1}_label` (max 4).
   - `results[i]` → `result_${i+1}_value`, `result_${i+1}_label` (max 4).
   - `gallery_images[i]` → `gallery_image_${i+1}_url`, `gallery_image_${i+1}_caption` (max 6).

4. **Linkade records** (`product_ids`, `article_ids`) skickas som array av
   string rec-IDs (eller tom array `[]` vid UPDATE när användaren tömt
   listan). Backend rör inte fältnamnet — passthrough.

5. **Lines-fält** (`header_logos`, `challenge_bullets`, `contact_form_options`,
   `contact_form_trust_signals`) skickas som RÅ multiline-sträng (en post per
   rad), inte som array. Core's Normalizer expanderar vid läsning.

6. **Layout/theme** är singleLineText i Airtable (inte singleSelect). Skicka
   som ren sträng: `split`/`centered`, `dark`/`light`.

7. **Output ska INTE innehålla:**
   - `cms_partner_pages` (backlink, hanteras inte härifrån).
   - Några andra fältnamn än de som listas ovan.
