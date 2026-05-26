# Airtable Schema — Wexoe Partner Pages

**Base ID:** `appokKSTaBdCa8YiW` (Wexoe NY)

Detta schema används av Claude-middlemannen för att transformera state-JSON
till Airtable-fältnamn när en Partner-sida (leverantörssida) skapas eller
uppdateras via buildern. snake_case överallt — både i Airtable display-namn
och i state-payload-byggaren.

Sidtypen har bara en tabell — `cms_partner_pages`. Inga child-records.
Länkade records (`partner_ids`, `case_ids`, `category_ids`, `country_ids`)
skickas som linked-ID-arrayer.

PHP-pluginet `wexoeplugins/New plugins/wexoe-partner-page` är auktoritativt
för rendering-villkoren — schemat speglar dem en-till-en.

---

## Tabell 1: cms_partner_pages

**Table ID:** `tblQv5E8pSgwxy6wU`

Huvudrecorden. Flatt schema med 9 sektioner: identity, SEO, hero,
quick_facts (4 fasta slots), about, why_wexoe (inkl. cases-länk),
categories, faq, contact_person, contact_form.

### Identity & meta

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **slug** | singleLineText | Primary key. URL-slug. |
| **internal_notes** | multilineText | Redaktörs-notering. |
| **is_active** | checkbox | Ska ALLTID inkluderas, även `false`. |
| **partner_ids** | multipleRecordLinks | core_partners-länk (single-pick i UI; PHP läser första). Skicka som array. |
| **country_ids** | multipleRecordLinks | core_countries-länk. Skicka som array. |

### SEO

| Fältnamn | Typ |
|---|---|
| **seo_title** | singleLineText |
| **seo_description** | multilineText |
| **og_image_url** | singleLineText |

### Hero

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **hero_eyebrow** | singleLineText | Liten label ovanför logo/h1. |
| **h1** | singleLineText | Hero-rubrik. Också publik H1. |
| **hero_tagline** | multilineText | Brödtext i hero. Markdown inline. |
| **hero_cta_text** | singleLineText | Primär CTA-text. |
| **hero_cta_url** | singleLineText | Primär CTA-URL. |
| **hero_cta2_text** | singleLineText | Sekundär CTA — visas bara om BÅDE text och URL är satta. |
| **hero_cta2_url** | singleLineText | Sekundär CTA-URL. |
| **hero_image_url** | singleLineText | Hero-bild (höger). |

### Quick Facts (4 fasta slots)

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **show_quick_facts** | checkbox | Sektionens visibility-toggle. Ska ALLTID inkluderas. |
| **facts_1_icon** | singleLineText | Ikon-key — en av: `calendar`, `users`, `globe`, `shield`, `building`, `factory`, `award`, `package`, `briefcase`, `target`. Tomt = ingen ikon. |
| **facts_1_value** | singleLineText | Stor siffra/text. |
| **facts_1_label** | singleLineText | Label under värdet. |
| **facts_2_icon** / **facts_2_value** / **facts_2_label** | … | Som slot 1. |
| **facts_3_icon** / **facts_3_value** / **facts_3_label** | … | Som slot 1. |
| **facts_4_icon** / **facts_4_value** / **facts_4_label** | … | Som slot 1. |

### About

| Fältnamn | Typ |
|---|---|
| **show_about** | checkbox |
| **about_eyebrow** | singleLineText |
| **about_h2** | singleLineText |
| **about_text** | multilineText (markdown) |
| **about_image_url** | singleLineText |
| **about_badge_value** | singleLineText |
| **about_badge_label** | singleLineText |

### Why Wexoe + benefits + cases

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **show_why_wexoe** | checkbox | |
| **why_h2** | singleLineText | |
| **why_text** | multilineText (markdown inline) | |
| **why_benefits** | multilineText | En benefit per rad — i state är detta en `string[]`, joinas med `\n` här. Tomma rader filtreras bort. |
| **case_ids** | multipleRecordLinks | cms_cases-länk. Pluginet renderar de tre första. |
| **cases_view_all_text** | singleLineText | "Se alla case"-länken visas bara när BÅDE text och URL är satta. |
| **cases_view_all_url** | singleLineText | |

### Categories

| Fältnamn | Typ |
|---|---|
| **show_categories** | checkbox |
| **categories_eyebrow** | singleLineText |
| **categories_h2** | singleLineText |
| **categories_intro** | multilineText (markdown inline) |
| **category_ids** | multipleRecordLinks (cms_product_pages) |

### FAQ

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **show_faq** | checkbox | |
| **faq_h2** | singleLineText | |
| **faqs** | multilineText (JSON-string) | JSON-array `[{"question": "…", "answer": "…"}, …]`. Tomt = `[]` eller `""`. |

### Contact Person (navy strip)

| Fältnamn | Typ |
|---|---|
| **show_contact_person** | checkbox |
| **contact_name** | singleLineText |
| **contact_title** | singleLineText |
| **contact_email** | email |
| **contact_phone** | singleLineText |
| **contact_image_url** | singleLineText |
| **contact_quote** | multilineText |

### Contact Form (delad med ContactForm-renderer)

Identisk uppsättning som customer-type / product-area / cms-page.

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
| `contact_form_trust_signals` | multilineText (`**Bold** \| Resten`, max 3) |
| `contact_form_show_contact_person` | checkbox |

---

## Formateringsregler

1. **Utelämna tomma textfält vid CREATE** (tomma strängar, null). Vid
   UPDATE skickas tomma textfält som `""` så Airtable rensar dem.

2. **Boolean-fält** (`is_active`, alla `show_*`-toggles inkl. contact_form-
   visningar) ska ALLTID inkluderas, även vid värde `false`.

3. **Linked-ID-arrayer** (`partner_ids`, `country_ids`, `case_ids`,
   `category_ids`):
   - Vid CREATE: utelämna om arrayen är tom.
   - Vid UPDATE: skicka **alltid** arrayen (även tom) så marknadsföraren
     kan rensa länkningar från buildern.

4. **Quick-facts-ikon-key** måste vara en av de 10 låsta keys:
   `calendar`, `users`, `globe`, `shield`, `building`, `factory`,
   `award`, `package`, `briefcase`, `target`. Tomt eller okänt → emit
   tomsträng (pluginet utelämnar då ikonen).

5. **`why_benefits`** kommer som `string[]` i input. Joina med `\n`,
   trimma varje rad, filtrera bort tomma. Emit som en multilineText-
   sträng. Vid UPDATE-tomtillstånd: emit `""`.

6. **`faqs`** kommer som array `[{question, answer}, ...]` i input.
   - Filtrera bort poster där `question.trim() === ''`.
   - Emit som JSON-sträng `JSON.stringify([...])`. Tom array → emit
     `"[]"` så pluginets json_decode lyckas men ger 0 items.
   - **Inkludera ALDRIG** `clientId` eller andra meta-fält i den
     emittade JSON:en.

7. **Contact Form-fält:** Alla 15 `contact_form_*`-fält ska ALLTID
   inkluderas vid UPDATE (även tomma eller false) så att ingen data
   tappas vid partial save. `contact_form_layout` = `split` eller
   `centered`. `contact_form_theme` = `dark` eller `light`.
   `contact_form_trust_signals`-format: `**Bold** | Resten` per rad.

8. **Hero CTA2 + cases_view_all**: båda är AND-villkor i PHP-pluginet
   (visas bara när både text och URL är satta). Här finns ingen
   special-logik på Claude-sidan — emit fälten som de är. Pluginet
   sköter visningslogiken.
