# Airtable Schema — Wexoe Landing Pages

**Base ID:** `appXoUcK68dQwASjF`

Detta schema används av Claude-middlemannen för att transformera
state-JSON till Airtable-fältnamn när en Landing Page skapas eller
uppdateras via buildern.

---

## Tabell 1: Landing Pages

**Table ID:** `tbl8KDqGq0Ray1uqS`

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **Name** | singleLineText | Internt namn. Sätts alltid lika med Slug av buildern. |
| **Slug** | singleLineText | URL-slug, lowercase a-z, 0-9, bindestreck. Matchar WP-shortcode. |
| **H1** | singleLineText | Sidans huvudrubrik i hero-bannern. |
| **Hero Description** | multilineText | Brödtext under H1. |
| **Hero Image** | url | Bild-URL från WP Media. |
| **Hero CTA Text** | singleLineText | Primär CTA-knapp. Default "Kontakta oss". |
| **Hero CTA URL** | url | Länk för primär CTA. |
| **Hero CTA2 Text** | singleLineText | Sekundär CTA — visas bara om ifylld. |
| **Hero CTA2 URL** | url | Länk för sekundär CTA. |
| **Content H2** | singleLineText | Rubrik i content-sektionen. |
| **Content Text** | multilineText | Brödtext (stöder markdown). |
| **Content Benefits** | multilineText | En benefit per rad, `\n`-separerad. |
| **Sidebar Type** | singleSelect | Ett av: `case`, `event`, `leadmagnet`, `calculator`, eller tom sträng. |
| **Case Title** | singleLineText | Endast om `Sidebar Type = case`. |
| **Case Description** | multilineText | Endast om `Sidebar Type = case`. |
| **Case Image** | url | Endast om `Sidebar Type = case`. |
| **Case Outcomes** | multilineText | Endast om `Sidebar Type = case`. En per rad. |
| **Case CTA Text** | singleLineText | Endast om `Sidebar Type = case`. |
| **Case CTA URL** | url | Endast om `Sidebar Type = case`. |
| **Calc Title** | singleLineText | Endast om `Sidebar Type = calculator`. |
| **Calc HTML** | multilineText | Endast om `Sidebar Type = calculator`. HTML/CSS/JS. |
| **Event Type** | singleLineText | Endast om `Sidebar Type = event`. |
| **Event Title** | singleLineText | Endast om `Sidebar Type = event`. |
| **Event Description** | multilineText | Endast om `Sidebar Type = event`. |
| **Event Date** | singleLineText | Endast om `Sidebar Type = event`. Fritext (inte date-typ). |
| **Event Location** | singleLineText | Endast om `Sidebar Type = event`. |
| **Event Webhook** | url | Endast om `Sidebar Type = event`. Make.com-webhook. |
| **Magnet Title** | singleLineText | Endast om `Sidebar Type = leadmagnet`. |
| **Magnet Format** | singleLineText | Endast om `Sidebar Type = leadmagnet`. |
| **Magnet Description** | multilineText | Endast om `Sidebar Type = leadmagnet`. |
| **Magnet File URL** | url | Endast om `Sidebar Type = leadmagnet`. |
| **Magnet Webhook** | url | Endast om `Sidebar Type = leadmagnet`. Make.com-webhook. |
| **Contact Name** | singleLineText | Kontaktpersonens namn. |
| **Contact Title** | singleLineText | Titel. |
| **Contact Email** | email | |
| **Contact Phone** | phoneNumber | |
| **Contact Image** | url | Porträttbild från WP Media. |
| **Contact Quote** | multilineText | Citat/text intill porträttet. |
| **Show Content** | checkbox | Ska ALLTID inkluderas (även när `false`). |
| **Show Sidebar** | checkbox | Ska ALLTID inkluderas. |
| **Show Contact** | checkbox | Ska ALLTID inkluderas. |
| **Show Tabs** | checkbox | Ska ALLTID inkluderas. |
| **Color Main** | singleLineText | Hex-color override, default `#11325D`. |
| **Color Secondary** | singleLineText | Hex-color override, default `#F28C28`. |
| **LP Tabs** | multipleRecordLinks | Länk till LP Tabs-tabellen. **Hanteras av backend, inkludera ej i Claudes output.** |

---

## Tabell 2: LP Tabs

**Table ID:** `tblvecOh3rAGmw3mw`

En LP har 0 eller flera tabs. Tabs är linked records som pekar tillbaka på
Landing Pages via `Landing Page`-fältet.

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **Name** | singleLineText | Tab-rubrik i pill-knappen. |
| **Order** | number | 1-baserat index. Används av WP-pluginet för sortering. |
| **Visa** | checkbox | Ska alltid vara `true`. |
| **Tab Type** | singleSelect | Ett av: `textimage`, `fullmedia`, `faq`, `calameo`, `downloads`, `compare`, `steps`. |
| **Landing Page** | multipleRecordLinks | Back-link till Landing Pages. **Hanteras av backend, inkludera ej.** |
| **LP Downloads** | multipleRecordLinks | Länk till LP Downloads-tabellen. **Hanteras av backend, inkludera ej.** |

### Typ-specifika fält

Inkludera **endast** de fält som hör till tabens `Tab Type`.

**textimage:**

| Fält | Typ | Format |
|---|---|---|
| `TI H2` | singleLineText | |
| `TI Text` | multilineText | |
| `TI Benefits` | multilineText | En benefit per rad, `\n`-separerad. |
| `TI Image` | url | |
| `TI Inverted` | checkbox | Alltid inkludera om tab är textimage. |

**fullmedia:**

| Fält | Typ | Format |
|---|---|---|
| `FM URL` | url | YouTube-länk eller bild-URL (auto-detect av WP). |

**faq:**

| Fält | Typ | Format |
|---|---|---|
| `FAQ Items` | multilineText | `Q: Fråga\nA: Svar\n\nQ: Fråga 2\nA: Svar 2` (tom rad separerar). |

**calameo:**

| Fält | Typ | Format |
|---|---|---|
| `Calameo 1 Title` / `Calameo 1 Src` | text / url | Upp till 3 dokument. |
| `Calameo 2 Title` / `Calameo 2 Src` | text / url | |
| `Calameo 3 Title` / `Calameo 3 Src` | text / url | |

**downloads:**

Inga tab-fält — nedladdningar skapas som separata records i LP Downloads-tabellen
och linkas till taben via `Tab`-fältet där.

**compare:**

| Fält | Typ | Format |
|---|---|---|
| `Compare Title` | singleLineText | |
| `Compare Col A` | singleLineText | Rubrik på kolumn A. |
| `Compare Col B` | singleLineText | Rubrik på kolumn B. |
| `Compare Rows` | multilineText | `Label \| Värde A \| Värde B` per rad (pipe-separerat), `\n`-separerat mellan rader. |

**steps:**

| Fält | Typ | Format |
|---|---|---|
| `Steps Title` | singleLineText | |
| `Steps` | multilineText | `Rubrik \| Beskrivning` per rad (pipe-separerat), `\n`-separerat mellan rader. |

---

## Tabell 3: LP Downloads

**Table ID:** `tblbLM827DzjWGjCR`

Downloads är linked records som hör till en tab via `Tab`-fältet.

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **Name** | singleLineText | Filnamn eller titel. |
| **Description** | singleLineText | Kort beskrivning. |
| **Thumbnail** | url | Valfri thumbnail-URL. |
| **File URL** | url | Länk till filen (WP Media). |
| **Button Text** | singleLineText | Knapp-text, oftast filtyp (ex. "PDF"). |
| **Order** | number | 1-baserat index inom taben. |
| **Visa** | checkbox | Ska alltid vara `true`. |
| **Tab** | multipleRecordLinks | Back-link till LP Tabs. **Hanteras av backend, inkludera ej.** |

---

## Formateringsregler

Claude ska **tolka** och **städa** user-edited data innan den går till Airtable:

1. **Utelämna tomma fält** (tomma strängar, null) vid CREATE. Vid UPDATE kan
   backend sätta explicit `""` för fält som ska rensas — men Claude behöver inte
   bry sig om det.

2. **Benefits / Case Outcomes / TI Benefits:** Om input ser ut som en paragraf
   eller kommaseparerad lista, splitta till en per rad (`\n`).

3. **FAQ:** Säkerställ att varje fråga har `Q: `-prefix och varje svar `A: `-prefix.
   Separera QA-par med en blank rad.

4. **Compare Rows:** Säkerställ pipe-format `Label | Värde A | Värde B` per rad.

5. **Steps:** Säkerställ pipe-format `Rubrik | Beskrivning` per rad.

6. **Boolean-fält** (`Show Content`, `Show Sidebar`, `Show Tabs`, `Show Contact`,
   `Show Contact Form`, `Contact Form Show *`-checkboxes, `Visa`, `TI Inverted`)
   ska ALLTID inkluderas i outputen, även när `false`.

7. **Sidebar-typ-fält:** Inkludera endast fält som hör till den aktiva
   `Sidebar Type`. Backend rensar stale fält från andra typer vid UPDATE.

8. **Tab-typ-fält:** Inkludera endast fält som hör till tabens `Tab Type`.
   Backend rensar stale fält vid UPDATE.

9. **Contact Form-fält:** Alla 15 `Contact Form *`-fält ska ALLTID inkluderas
   i `landingPage.fields` vid UPDATE (även tomma eller false) så att ingen
   data tappas i en delvis publish. `Contact Form Layout` = `split` eller
   `centered`. `Contact Form Theme` = `dark` eller `light`. `Contact Form Trust
   Signals` format: `**Bold** | Resten` per rad, max 3.

---

## Contact Form-fält (Landing Pages-tabellen)

Renderas av `\Wexoe\Core\Renderers\ContactForm` sist på LP-sidan när
`Show Contact Form` = true. Samma 15 fält finns på Product Areas och
Audience Heroes.

| Fältnamn | Typ | Kommentar |
|---|---|---|
| `Show Contact Form` | checkbox | True = rendera kontaktformulär sist. |
| `Contact Form Eyebrow` | singleLineText | |
| `Contact Form Title` | singleLineText | Tom = PHP-default. |
| `Contact Form Subtitle` | multilineText | |
| `Contact Form Layout` | singleSelect | `split` eller `centered`. |
| `Contact Form Theme` | singleSelect | `dark` eller `light`. |
| `Contact Form Show Company` | checkbox | |
| `Contact Form Show Phone` | checkbox | |
| `Contact Form Show Dropdown` | checkbox | |
| `Contact Form Dropdown Label` | singleLineText | Tom = PHP-default. |
| `Contact Form Options` | multilineText | En option per rad. |
| `Contact Form CTA Text` | singleLineText | Tom = "Skicka". |
| `Contact Form Message Label` | singleLineText | |
| `Contact Form Trust Signals` | multilineText | `**Bold** \| Text` per rad, max 3. |
| `Contact Form Show Contact Person` | checkbox | Visa kontaktperson-kort. |
