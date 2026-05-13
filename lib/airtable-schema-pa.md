# Airtable Schema — Wexoe Product Areas

**Base ID:** `appXoUcK68dQwASjF`

Detta schema används av Claude-middlemannen för att transformera
state-JSON till Airtable-fältnamn när en Produktsida (Product Area)
skapas eller uppdateras via buildern.

Till skillnad från Landing Pages har Product Areas två typer av
linkade records (Products och Solutions & Concepts) som också kan
skapas, uppdateras och länkas i samma save-flöde. Articles är
read-only i buildern och rörs aldrig av save-flödet.

---

## Tabell 1: Product Areas

**Table ID:** `tblgatNFYFMwF4EcQ`

Huvudrecorden. Innehåller ~60 direktfält samt linked-record-fält för
Products, Solutions och Division.

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **Name** | singleLineText | Internt namn. Buildern sätter det lika med Slug. |
| **Slug** | singleLineText | URL-slug, lowercase a-z, 0-9, bindestreck. |
| **H1** | singleLineText | Sidans huvudrubrik i top-banner. |
| **Top BG** | singleLineText | Hex-color för top-banner-bakgrunden. |
| **Hero H2** | singleLineText | Underrubrik i hero. |
| **Hero Text** | richText | Brödtext i hero (markdown). |
| **Hero CTA Text** | singleLineText | Primär CTA. |
| **Hero CTA URL** | url | |
| **Hero Benefits** | richText | En benefit per rad — renderas som checkmark-lista i hero-högerkolumn. |
| **Hero Image** | multilineText | Bild-URL. |
| **Hero BG** | singleLineText | Hex-color. |
| **Hero Accent** | singleLineText | Hex-color. |
| **NPI Title** | multilineText | NPI-kortets titel (visas i hero-högerkolumn). |
| **NPI Description** | multilineText | |
| **NPI Image** | multilineText | URL. |
| **NPI Link** | multilineText | URL som NPI-kortet klickar till. |
| **Toggle BG** | singleLineText | Hex-color för produktsektionens bakgrund. |
| **Toggle Header BG** | singleLineText | Hex-color för produktkortens headers. |
| **Toggle Accent** | singleLineText | Hex-color för orange accent i produktsektionen. |
| **Solutions Title** | singleLineText | Rubrik på solutions-griden, default "Lösningar & koncept". |
| **Solutions BG** | singleLineText | Hex-color. |
| **Solutions Card BG** | singleLineText | Hex-color. |
| **Normal 1 H2** | singleLineText | Innehållssektion 1 — rubrik. |
| **Normal 1 Text** | richText | Brödtext. |
| **Normal 1 Bullets** | multilineText | En punkt per rad. |
| **Normal 1 Image** | singleLineText | URL. |
| **Normal 1 Reversed** | checkbox | Bild till vänster istället för höger. |
| **Normal 1 upp** | checkbox | Renderar sektion 1 **före** produktlistan istället för efter. |
| **Normal 1 BG** | singleLineText | Hex-color. |
| **Normal 2 H2** / **Normal 2 Text** / **Normal 2 Bullets** / **Normal 2 Image** / **Normal 2 Reversed** / **Normal 2 BG** | samma | Innehållssektion 2. Ingen `upp`-checkbox (renderar alltid efter produkter). |
| **Normal 3 H2** / … / **Normal 3 BG** | samma | Innehållssektion 3. |
| **Normal 4 H2** / **Normal 4 Text** | singleLineText / multilineText | Innehållssektion 4. |
| **Normal 4 Bullets** | multilineText | |
| **Normal 4 Image** | url | OBS — Normal 4 Image är `url`-typ (inte multilineText som 1–3). |
| **Normal 4 Reversed** / **Normal 4 BG** | checkbox / text | |
| **Contact Name** | singleLineText | Kontaktperson. |
| **Contact Title** | singleLineText | |
| **Contact Email** | email | |
| **Contact Phone** | phoneNumber | |
| **Contact Image** | singleLineText | URL. |
| **Contact Text** | multilineText | Quote/bio. |
| **Contact BG** | singleLineText | Hex-color. |
| **Docs Title** | singleLineText | Rubrik ovanför dokumentations-iframe. |
| **Docs Iframe** | url | Iframe src-URL. |
| **Docs BG** | singleLineText | Hex-color. |
| **Side menu** | checkbox | Ska ALLTID inkluderas. Togglar sidomeny-layout. |
| **Request** | checkbox | Ska ALLTID inkluderas. Togglar prisförfrågan-formulär. |
| **Default open** | checkbox | Ska ALLTID inkluderas. |
| **Products** | multipleRecordLinks | Länk till Products-tabellen. **Hanteras av backend — inkludera ej i Claudes output.** |
| **Solutions** | multipleRecordLinks | Länk till Solutions & Concepts-tabellen. **Hanteras av backend — inkludera ej.** |
| **Division** | multipleRecordLinks | Länk till Divisions-tabellen. **Hanteras av backend — inkludera ej.** |

---

## Tabell 2: Products

**Table ID:** `tblHafyCEyh7S3Y64`

Products är linkade records som representerar produkter på en Product Area.
En produkt kan i teorin vara linkad till flera Product Areas, men i praktiken
äger varje PA sina egna produkter.

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **Name** | singleLineText | Produktnamn. |
| **Header side menu** | singleLineText | Rubrik som används i sidomeny-läget. Fallback: `Name`. |
| **Ecosystem Description** | singleLineText | Kort underrubrik som visas bredvid produktnamnet i toggle-headers. |
| **Description** | richText | Brödtext (markdown). |
| **Bullets** | multilineText | En punkt per rad. |
| **Image** | singleLineText | URL. |
| **Button 1 Text** | singleLineText | Sekundär knapps text. |
| **Button 1 URL** | singleLineText | Sekundär knapps URL. |
| **Button 2 Text** | singleLineText | Primär knapps text. |
| **Button 2 URL** | singleLineText | Primär knapps URL. |
| **Order** | number | 1-baserat. PHP-pluginet sorterar produkter på detta fält. |
| **Visa** | checkbox | Ska ALLTID inkluderas. `false` döljer produkten. |
| **Horizontal** | checkbox | Ska ALLTID inkluderas. Togglar horisontell layout för artikelkort. |
| **Product Area** | multipleRecordLinks | Back-link. **Hanteras av backend — inkludera ej.** |
| **Articles** | multipleRecordLinks | Länk till Articles. **Read-only i buildern — inkludera ej.** |

---

## Tabell 3: Solutions & Concepts

**Table ID:** `tblc98m9MJcpbWAVU`

Solutions är kort som renderas i en grid nedanför produktsektionen på en PA.

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **Name** | singleLineText | Lösningens titel. |
| **Image** | singleLineText | URL. |
| **URL** | singleLineText | Länk som hela kortet klickar till. |
| **Order** | number | 1-baserat, används för sortering. |
| **Visa** | checkbox | Ska ALLTID inkluderas. |
| **Product Areas** | multipleRecordLinks | Back-link till PA. **Hanteras av backend — inkludera ej.** |
| **Description** | multilineText | Kort beskrivning. |
| **Category** | singleLineText | Visas som uppercase-label ovanför titeln. |
| **CTA Text** | singleLineText | Knapptext, default "Läs mer". |

---

## Tabell 4: Divisions (endast read/link)

**Table ID:** `tblKam1tUTlR13atl`

Divisions är en liten lookup-tabell med Wexoes affärsområden (Automation,
IT Infra etc.). Buildern **skapar aldrig** nya divisions — användaren väljer
en befintlig i Inställningar. Linkas till PA via `Division`-fältet.

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **Name** | singleLineText | Divisionsnamn. |

---

## Tabell 5: Articles (read-only)

**Table ID:** `tblb87eWIjnW3ttOL`

Articles är artiklar (produktvarianter) som hör till en produkt. Buildern
visar dem som en read-only lista under varje produktkort — aldrig editerbar
och aldrig skriven av save-flödet. Dokumenteras här för fullständighet.

| Fältnamn | Typ | Kommentar |
|---|---|---|
| **Name** | multilineText | Artikelnamn. |
| **Artikelnummer** | singleLineText | Unikt artikelnummer. |
| **Description** | multilineText | |
| **Datablad** | singleLineText | URL till datablad. |
| **Länk till webshop** | singleLineText | URL till webshop. |
| **Bild** | singleLineText | URL. |
| **Link to products** | multipleRecordLinks | Back-link till Products. |
| **Varianter** | multilineText | Variant-definition (se variant-format i wexoe-product-area.php). |
| **Supplier** | multipleRecordLinks | Länk till Partners. |

---

## Formateringsregler

Claude ska **tolka** och **städa** user-edited data innan den går till Airtable:

1. **Utelämna tomma fält** (tomma strängar, null) vid CREATE. Vid UPDATE kan
   backend sätta explicit `""` för fält som ska rensas — Claude behöver inte
   bry sig om det.

2. **Bullets, Hero Benefits, Normal N Bullets:** Om input ser ut som en paragraf
   eller kommaseparerad lista, splitta till en per rad (`\n`).

3. **Boolean-fält** (`Side menu`, `Request`, `Default open`, `Visa`, `Horizontal`,
   `Normal N Reversed`, `Normal N upp`, `Show Contact Form`, alla
   `Contact Form Show *`-checkboxes) ska ALLTID inkluderas, även `false`.

4. **Hex-färger** (alla `*BG`, `*Accent`, `Top BG`, etc.): om användaren har
   satt en färg, skicka den som `#RRGGBB`. Om tom, utelämna vid CREATE.

5. **Linkade records** (`Products`, `Solutions`, `Division`, `Product Area`,
   `Articles`, `LP Downloads`): **Inkludera ALDRIG** i Claudes output. Backend
   hanterar all linked-record-logik.

6. **Contact Form-fält:** Alla 15 `Contact Form *`-fält ska ALLTID inkluderas
   i `productArea.fields` vid UPDATE (även tomma eller false). `Contact Form
   Layout` = `split` eller `centered`. `Contact Form Theme` = `dark` eller
   `light`. Trust signals format: `**Bold** | Resten` per rad, max 3.

---

## Contact Form-fält (Product Areas-tabellen)

Samma 15 fält som i Landing Pages, renderas av `\Wexoe\Core\Renderers\ContactForm`
sist på PA-sidan när `Show Contact Form` = true.

| Fältnamn | Typ |
|---|---|
| `Show Contact Form` | checkbox |
| `Contact Form Eyebrow` | singleLineText |
| `Contact Form Title` | singleLineText |
| `Contact Form Subtitle` | multilineText |
| `Contact Form Layout` | singleSelect (`split` / `centered`) |
| `Contact Form Theme` | singleSelect (`dark` / `light`) |
| `Contact Form Show Company` | checkbox |
| `Contact Form Show Phone` | checkbox |
| `Contact Form Show Dropdown` | checkbox |
| `Contact Form Dropdown Label` | singleLineText |
| `Contact Form Options` | multilineText (en per rad) |
| `Contact Form CTA Text` | singleLineText |
| `Contact Form Message Label` | singleLineText |
| `Contact Form Trust Signals` | multilineText (`**Bold** \| Text`, max 3) |
| `Contact Form Show Contact Person` | checkbox |
