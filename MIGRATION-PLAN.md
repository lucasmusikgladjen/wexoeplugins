# Wexoe Airtable-migration: kanonisk plan + target-schema

Detta dokument är källan-of-truth för migrationen från `Wexoe` (`appXoUcK68dQwASjF`) till `Wexoe NY` (`appokKSTaBdCa8YiW`). Alla beslut är fattade — ingen diskussion kvarstår.

---

## Naming conventions (lås)

### Prefix
| Prefix | Användning |
|---|---|
| `core_` | SSOT — referensdata och singletons |
| `cms_` | CMS-redigerat innehåll för publika sidor |
| `pim_` | Produktdata speglad från PIM (framtid) |
| `inbox_` | Inkommande events (form-submissions) |
| `ext_` | Speglade externa system (framtid) |

### Format
- **snake_case** överallt — i kod, i Airtable display-namn, i field-keys.
- **Plural** för kollektioner: `core_partners`, `cms_landing_pages`.
- **Singular** för singletons: `core_company`, `core_graphic_profile`.
- **kebab-case** för slug-VÄRDEN (inte fältnamn).
- **Engelska** överallt.

### Type-suffix
`*_url`, `*_email`, `*_phone`, `*_at` (DateTime), `*_date`, `*_count` (int), `*_id` (single link), `*_ids` (multi link), `*_html`, `*_markdown`, `*_json`.

### Domän-prefix inom tabell
`hero_*`, `seo_*`, `contact_*`, `case_*`, `faq_*`, `cta_banner_*`, etc.

### Bool-prefix
`is_*` (tillstånd), `has_*` (relation), `show_*` (render-flagga).

### Standard-fält på alla tabeller
- `slug` (singleLineText) — primary key för entiteter med URL-närvaro
- `internal_notes` (multilineText) — fri redaktörs-text, inte renderad publikt
- `is_active` (checkbox, default true) — soft-delete
- `country_ids` (multipleRecordLinks → `core_countries`) — scope (default SE)

### Bilder
Alla bild-fält är `singleLineText` med URL till WP Media Library. Inga `multipleAttachments`.

---

## Tabell-mappning

### Migreras (med data)
| Gammal | Ny | Volym | Notering |
|---|---|---:|---|
| `Landing Pages` (tbl8KDqGq0Ray1uqS) | `cms_landing_pages` | 9 | Schema-utbyggnad av stub |
| `LP Tabs` (tblvecOh3rAGmw3mw) | `cms_landing_page_tabs` | 24 | Schema-utbyggnad av stub |
| `LP Downloads` (tblbLM827DzjWGjCR) | `cms_landing_page_downloads` | 4 | Schema-utbyggnad av stub |
| `Product Areas` (tblgatNFYFMwF4EcQ) | `cms_product_pages` + `cms_product_page_sections` | 19 | Splitta `Normal 1-4` till sub-records |
| `Customer types` (tblvNf1CqAYEFvTpu) | `cms_customer_type_pages` + `cms_case_pages` | 9 | Splitta case-fält till egen tabell |
| `Products` (tblHafyCEyh7S3Y64) | `cms_products` | ? | Bara CMS-fält; PIM-data lämnas |
| `Articles` (tblb87eWIjnW3ttOL) | `cms_articles` | 59 | Renderings-metadata |
| `Solutions & Concepts` (tblc98m9MJcpbWAVU) | `cms_solutions_mini` | 11 | Omdöpt |
| `Offerings` (tbldQZJu3NHHP5dUh) | `cms_offerings` | 7 | |
| `Product navigation` (tblJa2Kd6QHjFXPJZ) | `cms_product_navigation` | 20 | |
| `Partners` (tblsCOF5BPAxN6nmq) | `core_partners` (befintlig) | 17 | Migrera resterande records till SSOT |
| `Divisions` (tblKam1tUTlR13atl) | `core_divisions` (befintlig) | 4 | Migrera till SSOT |
| `Coworkers` (tblldarIcIpxlZ9GV) | `core_coworkers` (befintlig) | 9 | Migrera till SSOT |

### Befintliga (ingen migration)
`core_company`, `core_countries`, `core_customer_types`, `core_testimonials`, `core_graphic_profile`, `cms_unique_pages`.

### Lämnas i gamla basen (för senare `ops_*`-migration)
`Activities`, `Deliverables`, `Campaigns`.

### Tas inte med
`Customers`, `Leads`, `Webpage campaigns`, `Product news RA` — verifierat ej använt.

### Tom, byggs ny
`inbox_form_submissions` — gamla `User data` var tom.

### Slås in i `cms_unique_pages`
Pillar-sidor (`automation-pillar`-fil-grupp) — befintliga sektioner räcker.

---

## Target-schema per tabell

### `cms_landing_pages` (utbyggnad av stub `tblpPlk17FZIKawXY`)

| Field | Type | Notering |
|---|---|---|
| `slug` | singleLineText | Primary key |
| `internal_notes` | multilineText | |
| `is_active` | checkbox | Default true |
| `country_ids` | multipleRecordLinks → `core_countries` | Default SE |
| `h1` | singleLineText | |
| `seo_title` | singleLineText | |
| `seo_description` | multilineText | |
| `og_image_url` | singleLineText | URL till WP Media |
| `hero_description` | multilineText | |
| `hero_image_url` | singleLineText | URL till WP Media |
| `hero_cta_text` | singleLineText | |
| `hero_cta_url` | singleLineText | |
| `hero_cta2_text` | singleLineText | |
| `hero_cta2_url` | singleLineText | |
| `content_h2` | singleLineText | |
| `content_text` | multilineText | Markdown |
| `content_benefits` | multilineText | En per rad |
| `sidebar_type` | singleSelect | `case`, `calculator`, `event`, `leadmagnet` |
| `case_title` | singleLineText | |
| `case_description` | multilineText | |
| `case_image_url` | singleLineText | |
| `case_outcomes` | multilineText | En per rad |
| `case_cta_text` | singleLineText | |
| `case_cta_url` | singleLineText | |
| `calc_title` | singleLineText | |
| `calc_html` | multilineText | |
| `event_type` | singleLineText | |
| `event_title` | singleLineText | |
| `event_description` | multilineText | |
| `event_date` | singleLineText | |
| `event_location` | singleLineText | |
| `event_webhook` | url | |
| `magnet_title` | singleLineText | |
| `magnet_format` | singleLineText | |
| `magnet_description` | multilineText | |
| `magnet_file_url` | url | |
| `magnet_webhook` | url | |
| `contact_name` | singleLineText | |
| `contact_title` | singleLineText | |
| `contact_email` | email | |
| `contact_phone` | phoneNumber | |
| `contact_image_url` | singleLineText | |
| `contact_quote` | multilineText | |
| `show_content` | checkbox | |
| `show_sidebar` | checkbox | |
| `show_tabs` | checkbox | |
| `show_contact` | checkbox | |
| `color_main` | singleLineText | Hex |
| `color_secondary` | singleLineText | Hex |
| `tab_ids` | multipleRecordLinks → `cms_landing_page_tabs` | |
| `show_contact_form` | checkbox | |
| `contact_form_eyebrow` | singleLineText | |
| `contact_form_title` | singleLineText | |
| `contact_form_subtitle` | multilineText | |
| `contact_form_layout` | singleSelect | `centered`, `split` |
| `contact_form_theme` | singleSelect | `light`, `dark` |
| `contact_form_show_company` | checkbox | |
| `contact_form_show_phone` | checkbox | |
| `contact_form_show_dropdown` | checkbox | |
| `contact_form_dropdown_label` | singleLineText | |
| `contact_form_options` | multilineText | En per rad |
| `contact_form_cta_text` | singleLineText | |
| `contact_form_message_label` | singleLineText | |
| `contact_form_trust_signals` | multilineText | En per rad |
| `contact_form_show_contact_person` | checkbox | |

### `cms_landing_page_tabs` (utbyggnad av stub `tblp8d32aj5BgGMvE`)

| Field | Type | Notering |
|---|---|---|
| `name` | singleLineText | Primary key (visningsnamn på flik-knapp) |
| `internal_notes` | multilineText | |
| `is_active` | checkbox | (gamla `visa`) |
| `order` | number | |
| `tab_type` | singleSelect | `textimage`, `fullmedia`, `faq`, `calameo`, `downloads`, `compare`, `steps` |
| `ti_h2` | singleLineText | |
| `ti_text` | multilineText | |
| `ti_benefits` | multilineText | En per rad |
| `ti_image_url` | singleLineText | |
| `ti_inverted` | checkbox | |
| `fm_url` | url | |
| `faq_items` | multilineText | Q:/A:-format |
| `calameo_1_title` | singleLineText | |
| `calameo_1_src` | url | |
| `calameo_2_title` | singleLineText | |
| `calameo_2_src` | url | |
| `calameo_3_title` | singleLineText | |
| `calameo_3_src` | url | |
| `download_ids` | multipleRecordLinks → `cms_landing_page_downloads` | |
| `compare_title` | singleLineText | |
| `compare_col_a` | singleLineText | |
| `compare_col_b` | singleLineText | |
| `compare_rows` | multilineText | Label \| A \| B-format |
| `steps_title` | singleLineText | |
| `steps` | multilineText | Title \| Description-format |
| `landing_page_ids` | multipleRecordLinks → `cms_landing_pages` | Back-link |

### `cms_landing_page_downloads` (utbyggnad av stub `tbltAtilGKnQ2wc7I`)

| Field | Type | Notering |
|---|---|---|
| `name` | singleLineText | Primary |
| `internal_notes` | multilineText | |
| `is_active` | checkbox | |
| `order` | number | |
| `description` | singleLineText | |
| `thumbnail_url` | url | |
| `file_url` | url | |
| `button_text` | singleLineText | |
| `tab_ids` | multipleRecordLinks → `cms_landing_page_tabs` | Back-link |

### `cms_product_pages` (utbyggnad av stub `tbl5PQR7FNHCogeya`)

| Field | Type | Notering |
|---|---|---|
| `slug` | singleLineText | Primary |
| `internal_notes` | multilineText | |
| `is_active` | checkbox | |
| `country_ids` | multipleRecordLinks → `core_countries` | |
| `division_ids` | multipleRecordLinks → `core_divisions` | |
| `name` | singleLineText | |
| `h1` | singleLineText | |
| `top_bg` | singleLineText | Hex |
| `hero_h2` | singleLineText | |
| `hero_text` | multilineText | Markdown |
| `hero_cta_text` | singleLineText | |
| `hero_cta_url` | singleLineText | |
| `hero_benefits` | multilineText | En per rad |
| `hero_image_url` | singleLineText | |
| `hero_bg` | singleLineText | Hex |
| `hero_accent` | singleLineText | Hex |
| `npi_title` | singleLineText | |
| `npi_description` | multilineText | |
| `npi_image_url` | singleLineText | |
| `npi_link` | url | |
| `toggle_bg` | singleLineText | Hex |
| `toggle_header_bg` | singleLineText | Hex |
| `toggle_accent` | singleLineText | Hex |
| `solutions_title` | singleLineText | |
| `solutions_bg` | singleLineText | Hex |
| `solutions_card_bg` | singleLineText | Hex |
| `contact_name` | singleLineText | |
| `contact_title` | singleLineText | |
| `contact_email` | email | |
| `contact_phone` | phoneNumber | |
| `contact_image_url` | singleLineText | |
| `contact_text` | multilineText | |
| `contact_bg` | singleLineText | Hex |
| `docs_title` | singleLineText | |
| `docs_iframe` | url | |
| `docs_bg` | singleLineText | Hex |
| `use_side_menu` | checkbox | |
| `show_request` | checkbox | |
| `default_open` | checkbox | |
| `section_ids` | multipleRecordLinks → `cms_product_page_sections` | (Normal 1-4 splittas hit) |
| `product_ids` | multipleRecordLinks → `cms_products` | |
| `solution_ids` | multipleRecordLinks → `cms_solutions_mini` | |
| `show_contact_form` + alla `contact_form_*`-fält | (samma som LP) | |

### `cms_product_page_sections` (ny tabell)

| Field | Type | Notering |
|---|---|---|
| `name` | singleLineText | Primary (auto-genererad eller "<page>: <h2>") |
| `internal_notes` | multilineText | |
| `is_active` | checkbox | |
| `order` | number | |
| `product_page_ids` | multipleRecordLinks → `cms_product_pages` | Parent |
| `h2` | singleLineText | |
| `text` | multilineText | Markdown |
| `bullets` | multilineText | En per rad |
| `image_url` | singleLineText | |
| `bg` | singleLineText | Hex |
| `reversed` | checkbox | Bild vänster/text höger |
| `shown_top` | checkbox | Visa över hero (gamla `upp`) |

### `cms_customer_type_pages` (utbyggnad av stub `tblZufoWVNKPuJdMK`)

| Field | Type | Notering |
|---|---|---|
| `slug` | singleLineText | Primary |
| `internal_notes` | multilineText | |
| `is_active` | checkbox | |
| `country_ids` | multipleRecordLinks → `core_countries` | |
| `customer_type_ids` | multipleRecordLinks → `core_customer_types` | |
| `eyebrow` | singleLineText | |
| `title` | singleLineText | |
| `description` | multilineText | |
| `cta_text` | singleLineText | |
| `cta_url` | url | |
| `hero_image_url` | singleLineText | |
| `stat_number` | number | |
| `stat_label` | singleLineText | |
| `value_h2` | singleLineText | |
| `value_text_1` | multilineText | richText |
| `value_text_2` | multilineText | |
| `benefit_1` | singleLineText | |
| `benefit_2` | singleLineText | |
| `benefit_3` | singleLineText | |
| `case_ids` | multipleRecordLinks → `cms_case_pages` | |
| `show_contact_form` + alla `contact_form_*` | (samma som LP) | |

### `cms_case_pages` (utbyggnad av stub `tbl3uMV6IpRIZeucA`)

| Field | Type | Notering |
|---|---|---|
| `slug` | singleLineText | Primary |
| `internal_notes` | multilineText | |
| `is_active` | checkbox | |
| `country_ids` | multipleRecordLinks → `core_countries` | |
| `customer_type_ids` | multipleRecordLinks → `core_customer_types` | |
| `title` | singleLineText | |
| `description` | multilineText | |
| `result` | singleLineText | |
| `link_text` | singleLineText | |
| `link_url` | url | |

### `cms_products` (utbyggnad av stub `tblN23V7uAMpeZoO1`)

| Field | Type | Notering |
|---|---|---|
| `name` | singleLineText | Primary |
| `internal_notes` | multilineText | |
| `is_active` | checkbox | (gamla `visa`) |
| `order` | number | |
| `ecosystem_description` | singleLineText | |
| `description` | multilineText | richText |
| `bullets` | multilineText | En per rad |
| `image_url` | singleLineText | |
| `button_1_text` | singleLineText | |
| `button_1_url` | singleLineText | |
| `button_2_text` | singleLineText | |
| `button_2_url` | singleLineText | |
| `horizontal` | checkbox | |
| `header_side_menu` | singleLineText | |
| `product_page_ids` | multipleRecordLinks → `cms_product_pages` | |
| `article_ids` | multipleRecordLinks → `cms_articles` | |

### `cms_articles` (utbyggnad av stub `tblhnz3MQG1JwfKrN`)

| Field | Type | Notering |
|---|---|---|
| `name` | multilineText | Primary (artikelnamn) |
| `internal_notes` | multilineText | |
| `is_active` | checkbox | Default true |
| `article_number` | singleLineText | (gamla `Artikelnummer`) — framtida PIM-koppling |
| `description` | multilineText | |
| `datasheet_url` | url | (gamla `Datablad`) |
| `webshop_url` | url | (gamla `Länk till webshop`) |
| `image_url` | singleLineText | (gamla `Bild`) |
| `variants` | multilineText | (gamla `Varianter`) |
| `product_ids` | multipleRecordLinks → `cms_products` | (gamla `Link to products`) |
| `supplier_ids` | multipleRecordLinks → `core_partners` | (gamla `Supplier`) |

### `cms_solutions_mini` (utbyggnad av stub `tblxK7ikOgLFuze6m`, namn-byte från `cms_solutions_concepts`)

| Field | Type | Notering |
|---|---|---|
| `name` | singleLineText | Primary |
| `internal_notes` | multilineText | |
| `is_active` | checkbox | (gamla `visa`) |
| `order` | number | |
| `category` | singleLineText | |
| `image_url` | singleLineText | |
| `url` | url | |
| `description` | multilineText | |
| `cta_text` | singleLineText | |
| `product_page_ids` | multipleRecordLinks → `cms_product_pages` | (gamla `Product Areas`) |

### `cms_offerings` (ny)

| Field | Type | Notering |
|---|---|---|
| `name` | singleLineText | Primary |
| `internal_notes` | multilineText | |
| `is_active` | checkbox | |
| `order` | number | |
| `division` | singleSelect | (legacy fält — overlapping med core_divisions, behåll som enkel select) |
| `heading` | singleLineText | |
| `description` | multilineText | richText |
| `image_url` | singleLineText | |
| `benefit_1` | singleLineText | |
| `benefit_2` | singleLineText | |
| `benefit_3` | singleLineText | |
| `benefit_4` | singleLineText | |
| `benefit_5` | singleLineText | |
| `cta_text` | singleLineText | |
| `cta_url` | url | |

### `cms_product_navigation` (ny)

| Field | Type | Notering |
|---|---|---|
| `name` | singleLineText | Primary |
| `internal_notes` | multilineText | |
| `is_active` | checkbox | (gamla `Active`) |
| `order` | number | |
| `type` | singleSelect | `Nav`, `Event`, `Case`, `Campaign` |
| `url` | singleLineText | |
| `icon` | multilineText | SVG-kod |
| `description` | multilineText | |
| `button_text` | singleLineText | |
| `benefit_1` | singleLineText | |
| `benefit_2` | singleLineText | |
| `division_ids` | multipleRecordLinks → `core_divisions` | |

### `inbox_form_submissions` (ny, ersätter `User data`)

| Field | Type | Notering |
|---|---|---|
| `submission_id` | singleLineText | Primary (UUID eller composite) |
| `submitted_at` | dateTime | |
| `submission_type` | singleSelect | `contact`, `magnet`, `event`, `calculator` |
| `source_plugin` | singleLineText | |
| `page_slug` | singleLineText | |
| `email` | email | |
| `name` | singleLineText | |
| `company` | singleLineText | |
| `phone` | phoneNumber | |
| `message` | multilineText | |
| `newsletter_consent` | checkbox | |
| `magnet_name` | singleLineText | |
| `event_title` | singleLineText | |
| `calculator_data` | multilineText | JSON |
| `extra` | multilineText | JSON för plugin-specifika fält |
| `sent_to_crm` | checkbox | |

### `cms_partner_pages` (utbyggnad av stub `tblQv5E8pSgwxy6wU`)

Dedikerade publika partner-sidor. Innehåll hämtas via scope från `core_partners` + denna tabells egna fält.

| Field | Type | Notering |
|---|---|---|
| `slug` | singleLineText | Primary |
| `internal_notes` | multilineText | |
| `is_active` | checkbox | |
| `country_ids` | multipleRecordLinks → `core_countries` | |
| `partner_ids` | multipleRecordLinks → `core_partners` | Vilken partner sidan handlar om |
| `h1` | singleLineText | |
| `hero_description` | multilineText | |
| `hero_image_url` | singleLineText | |
| `hero_cta_text` | singleLineText | |
| `hero_cta_url` | url | |
| `body_markdown` | multilineText | Markdown |
| `show_contact_form` + alla `contact_form_*` | (samma som LP) | |

---

## Befintliga `core_*`-fält — renaming till snake_case

Befintliga `core_*`-tabeller har idag fältnamn i Title Case. De ska döpas om till snake_case i Airtable display för att matcha konventionen.

### `core_company`
| Gammalt | Nytt |
|---|---|
| Slug | slug |
| Internal Notes | internal_notes |
| Is Default | is_default |
| Company Name | company_name |
| Tagline | tagline |
| Org Number | org_number |
| VAT Number | vat_number |
| Email | email |
| Email order | email_order |
| Phone | phone |
| Phone Emergency | phone_emergency |
| Address Line 1 | address_line_1 |
| Address Postal Code | address_postal_code |
| Address City | address_city |
| LinkedIn URL | linkedin_url |
| Facebook URL | facebook_url |
| Instagram URL | instagram_url |
| YouTube URL | youtube_url |
| Hours Mon-Thur | hours_mon_thur |
| Hours Friday | hours_friday |
| Country | country_ids |

### `core_countries`
| Gammalt | Nytt |
|---|---|
| Name | name |
| Code | code |
| Domain | domain |
| URL Prefix | url_prefix |
| Currency | currency |
| Locale | locale |
| Default Language | default_language |
| Order | order |
| Active | is_active |
| (alla `core_*`-backlinks) | (osynliga, omdöpta automatiskt vid skapande av övriga) |

### `core_divisions`
| Gammalt | Nytt |
|---|---|
| Name | name |
| Slug | slug |
| Description | description |
| Order | order |
| Active | is_active |
| Country | country_ids |

### `core_customer_types`
| Gammalt | Nytt |
|---|---|
| Name | name |
| Slug | slug |
| Description | description |
| Icon | icon |
| Order | order |
| Active | is_active |

### `core_coworkers`
| Gammalt | Nytt |
|---|---|
| Full Name | full_name |
| Title | title |
| Email | email |
| Phone | phone |
| Image | image_url |
| LinkedIn URL | linkedin_url |
| Bio | bio |
| Order | order |
| Active | is_active |
| Division | division_ids |
| Country | country_ids |

### `core_partners`
| Gammalt | Nytt |
|---|---|
| Name | name |
| Logo | logo_url |
| Logo Transparent | logo_transparent_url |
| URL | url |
| Description | description |
| Order | order |
| Active | is_active |
| Division | division_ids |
| Country | country_ids |

### `core_testimonials`
| Gammalt | Nytt |
|---|---|
| Internal Name | internal_name |
| Quote | quote |
| Author Name | author_name |
| Author Title | author_title |
| Author Image | author_image_url |
| Order | order |
| Active | is_active |
| Featured | is_featured |
| Customer Type | customer_type_ids |
| Division | division_ids |
| Country | country_ids |

### `core_graphic_profile`
| Gammalt | Nytt |
|---|---|
| Slug | slug |
| Is Default | is_default |
| Logo Primary | logo_primary_url |
| Logo Dark Background | logo_dark_url |
| Favicon | favicon_url |
| Color Primary | color_primary |
| Color Secondary | color_secondary |
| Color Accent | color_accent |
| Color Background Light | color_background_light |
| Color Background Dark | color_background_dark |
| Color Text Primary | color_text_primary |
| Color Text Secondary | color_text_secondary |
| Font Heading | font_heading |
| Font Body | font_body |
| Font CSS URL | font_css_url |
| Division | division_ids |

### `cms_unique_pages`
Befintlig tabell — döper om alla fält från PascalCase till snake_case enligt samma princip.

---

## Migrationsordning (data-flytt)

1. SSOT-resterande data: `Partners` → `core_partners`, `Divisions` → `core_divisions`, `Coworkers` → `core_coworkers`
2. Leaf-tabeller: `cms_articles`, `cms_landing_page_downloads`, `cms_solutions_mini`, `cms_offerings`, `cms_product_navigation`
3. Mid-level: `cms_products` (länkar till articles), `cms_landing_page_tabs` (länkar till downloads), `cms_case_pages`
4. Parent-tabeller: `cms_landing_pages` (länkar till tabs), `cms_product_pages` (länkar till sections, products, solutions), `cms_customer_type_pages` (länkar till cases)
5. Linked-record-rewiring: ersätt gamla record-IDs med nya enligt mappning

---

## Definition of Done

- [ ] Alla `cms_*`-tabeller fullt utbyggda med field descriptions
- [ ] All aktiv data kopierad från gamla basen
- [ ] Länk-fält pekar på rätt records i nya basen
- [ ] `wexoe-core/entities/*.php` pekar uteslutande på nya basen
- [ ] `wexoebuilder` mappers + Claude-prompts uppdaterade
- [ ] `Old plugins/` raderad
- [ ] 0 träffar för `appXoUcK68dQwASjF` i kod
- [ ] 0 hårdkodade tabell-IDs/namn utanför entity-scheman och `lib/core/registry.ts`
