# Airtable Schema — Wexoe Landing Pages

Base ID: `appXoUcK68dQwASjF`

## Tabell 1: Landing Pages (`tbl8KDqGq0Ray1uqS`)

Fält (alla string om inget annat anges):

- **Name** — Samma som Slug
- **Slug** — URL-slug, lowercase a-z, 0-9, bindestreck
- **H1** — Huvudrubrik
- **Hero Description**
- **Hero Image** — URL
- **Hero CTA Text**, **Hero CTA URL**
- **Hero CTA2 Text**, **Hero CTA2 URL**
- **Content H2**
- **Content Text** — Kan vara multi-line
- **Content Benefits** — En benefit per rad, \n-separerad. Om input är en paragraf eller kommaseparerad lista, splitta till en per rad.
- **Sidebar Type** — Ett av: `case`, `event`, `leadmagnet`, `calculator`
- Case-fält (bara om Sidebar Type = case): **Case Title**, **Case Description**, **Case Image** (URL), **Case Outcomes** (\n-separerad, samma regel som Benefits), **Case CTA Text**, **Case CTA URL**
- Event-fält (bara om Sidebar Type = event): **Event Type**, **Event Title**, **Event Description**, **Event Date**, **Event Location**, **Event Webhook** (URL)
- Leadmagnet-fält (bara om Sidebar Type = leadmagnet): **Magnet Title**, **Magnet Format**, **Magnet Description**, **Magnet File URL** (URL), **Magnet Webhook** (URL)
- Calculator-fält (bara om Sidebar Type = calculator): **Calc Title**, **Calc HTML** (HTML-kod)
- **Contact Name**, **Contact Title**, **Contact Email**, **Contact Phone**, **Contact Image** (URL), **Contact Quote**
- **Color Main**, **Color Secondary** — Hex-färgkoder
- **Show Content**, **Show Sidebar**, **Show Tabs**, **Show Contact** — boolean, ska ALLTID inkluderas

## Tabell 2: LP Tabs (`tblvecOh3rAGmw3mw`)

- **Landing Page** — Linked record, array med Landing Page record-ID: `["recXXX"]`
- **Name** — Tab-namn som visas i UI
- **Type** — Ett av: `textimage`, `fullmedia`, `faq`, `calameo`, `downloads`, `compare`, `steps`
- **Order** — number, 1-baserat index
- **Visa** — boolean, alltid `true`

Typ-specifika fält (inkludera BARA de som hör till tabens Type):

- **textimage**: `tiH2`, `tiText`, `tiBenefits` (\n-separerad), `tiImage` (URL), `tiInverted` (boolean)
- **fullmedia**: `fmUrl` (URL)
- **faq**: `faqContent` — Format: `Q: Fråga\nA: Svar\n\nQ: Fråga2\nA: Svar2`
- **calameo**: `calTitle1`, `calUrl1`, `calTitle2`, `calUrl2`, `calTitle3`, `calUrl3`
- **downloads**: Inga tab-fält — downloads skapas i LP Downloads-tabellen
- **compare**: `compareTitle`, `compareColA`, `compareColB`, `compareRows` — Format: `Label | Värde A | Värde B` per rad (\n-separerad)
- **steps**: `stepsTitle`, `stepsRows` — Format: `Rubrik | Beskrivning` per rad (\n-separerad)

## Tabell 3: LP Downloads (`tblbLM827DzjWGjCR`)

- **LP Tab** — Linked record, array med Tab record-ID: `["recXXX"]`
- **Name**
- **Description**
- **File URL** — URL
- **File Type**
- **Visa** — boolean, alltid `true`

## Formateringsregler

1. Utelämna fält med tomt värde — skicka INTE tomma strängar
2. Benefits/Outcomes: Om input ser ut som en paragraf eller kommaseparerad lista → splitta till en per rad (\n)
3. FAQ: Säkerställ Q:/A:-prefix på varje fråga/svar
4. Compare rows: Säkerställ pipe-format `Label | Värde A | Värde B`
5. Steps rows: Säkerställ pipe-format `Rubrik | Beskrivning`
6. Boolean-fält (Show Content, Show Sidebar, Show Tabs, Show Contact, Visa, tiInverted) ska ALLTID inkluderas
