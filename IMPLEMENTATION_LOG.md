# Wexoe enhetlig utvecklingsplan — Implementationslogg

**Branch (båda repon):** `claude/execute-dev-plan-YRjeI` (initial), `claude/migrate-wexoe-database-7KILw` (Wexoe → Wexoe NY-migration)
**Källplan:** `unifiedfeaturesplan.md` (rev 2, 2026-05-13), `MIGRATION-PLAN.md` (2026-05-14)
**Implementation startad:** 2026-05-13

Detta dokument loggar varje konkret åtgärd som tas under implementationen av planen. Filer som skapas/ändras, Airtable-mutationer, designbeslut som dyker upp på vägen, samt manuella TODO:s som användaren måste utföra (när utökning till runtime krävs).

---

## PR 3 — LP case-sidebar: inline-kort → länk till `cms_cases` (2026-05-29)

**Branch (båda repon):** `claude/migrate-lp-case-cards` (PR-bas: `claude/ecstatic-heisenberg-IG3SH`, stackad ovanpå PR 2)
**Bakgrund:** `cms_landing_pages` hade ett inline case-kort i sidebaren (`sidebar_type=case`) via 6 fält (`case_title/description/image_url/outcomes/cta_text/cta_url`). Dessa duplicerade case-innehåll som hör hemma i `cms_cases`. Migrerat till en enkellänk `case_id → cms_cases`; renderaren läser kort-lagret (card_*) + permalink från det länkade caset. (`cms_pages` har inget inline case-kort — dess `case_grid`-sektion länkar redan `cms_cases` sedan PR 2.)

### Airtable-mutationer (`Wexoe NY` `appokKSTaBdCa8YiW`)
- Nytt fält `cms_landing_pages.case_id` (fld3rsa8JLWB0cnL0) → `cms_cases`. Reciprok på cms_cases omdöpt till `landing_page_ids` (fldm0ELdfqPFTyYG6).
- Nytt `cms_cases`-record för optix-LP-kortet: **recjm5YI1fZoOlzQc** (slug `hax-optix-stillestand`, "HAX kortade stillestånd med Optix"; card_* + legacy_external_url=wexoe.se/kontakt + country=SE + partner=Rockwell).
- Länkar satta: LP `fjarraccess` (recfiEj26hn3sqUNa) → befintliga `maskinbyggare-fjarraccess` (recnCOqP9AA8iDAHL, identiskt kort); LP `optix` (recWFhYs7lQi66lFE) → nya recjm5YI1fZoOlzQc.
- LP `test123` (recIFNbx7Qjqzb8Ow) + `optixtest` (recScuIRiWq4GyWx4): **hoppade över** (testdata/lorem) — case_id lämnat tomt per användarbeslut.

### Kod — `wexoeplugins`
- `entities/landing_pages.php`: inline `case_*` ersatta med `case_id` (link → cms_cases).
- `wexoe-landing-page.php`: `wexoe_lp_test_render_sidebar_case()` laddar nu caset via `case_id` (ny helper `wexoe_lp_test_load_case`) och renderar card_title/description/result/image + `Permalink::for_record('cms_cases', …)`. `php -l` rent.

### Kod — `wexoebuilder`
- `types.ts`/`state.ts`: 6 case-fält → `caseId`. `page-mapper.ts`: `caseId` ← `linkedIds(f,'case_id')[0]`.
- `claude-transform.ts`: case-payloadblocket borttaget; `LP_SIDEBAR_CASE_FIELDS = []` (case_id är backend-hanterad, ej stale-clearing).
- `app/api/publish/route.ts`: sätter `case_id` direkt vid create/update (samma mönster som `tab_ids`; töms när sidebar ≠ case).
- `airtable-schema-lp.md`: 6 case-rader → en `case_id`-rad (backend-hanterad). `linked-sources.ts`: `cases`-pickern projicerar nu även card_*.
- `SidebarEditor.tsx`: case-panelen är nu en case-picker (`Field.LinkedRecords` source=cases, max 1). `SidebarPreview.tsx`: renderar det länkade caset via `useLinkedRecord('cases', caseId)`.

### ⚠️ Manuell TODO efter merge (MCP saknar `delete_field`)
Radera de 6 nu oanvända inline-kolumnerna på `cms_landing_pages` i Airtable-UI:
`case_title` (fldVBAQjbBQsoX0n9), `case_description` (fld2EPBxqV9JkZt8d), `case_image_url` (fldomUsD2WNctRkRK), `case_outcomes` (fldFuuwQWQnw609pC), `case_cta_text` (fldqGET4LcMftHaBM), `case_cta_url` (fldm1hNUPnH50p9cy).
Koden slutar läsa/skriva dem direkt; de innehåller bara kvarvarande (delvis test-)data tills de raderas. Rensa wexoe-core-cache + re-installera plugin-zip.

---

## PR 2 — Case-konsolidering: `cms_case_pages` + `cases` → `cms_cases` (2026-05-29)

**Branch (båda repon):** `claude/ecstatic-heisenberg-IG3SH`
**Bakgrund:** Case-modellen var triplicerad. Tre läs-entiteter existerade: `case_pages` (tabell `cms_case_pages` `tbl3uMV6IpRIZeucA` — 9 kort-records som länkar ut via `legacy_external_url`), samt `cases` OCH `cms_cases` som *båda* pekade på samma rikare tabell `cms_cases` (`tblxH3ECSMvDTYrIQ`, 4 builder-skapade full-page-records). Buildern och `wexoe-case`-pluginet använder redan `cms_cases`. Beslut: konsolidera allt till den enda kanoniska entiteten **`cms_cases`**, migrera de 9 korten dit, peka om alla konsumenter, och döp om reciprok-fält till snake_case (`*_ids`) nu.

### Airtable-mutationer (`Wexoe NY` `appokKSTaBdCa8YiW`)

**Nya fält på `cms_cases` (`tblxH3ECSMvDTYrIQ`):**
- Kort-lager: `card_title` (fldXRsJ4q67NgNFUs), `card_description` (fldZxBkBJmdkIjDpH), `card_result` (fldRKQsT5Y2M1OGpI), `card_image_url` (fld6tepAeXn0gZ3FJ), `card_cta_text` (fldi0ho3L334R2tPh), `card_industry` (fldAHqax1LumCS7rU), `legacy_external_url` (fldwQN8KY6yisn6Hw), `order` (fldqvumxGdsNskqWT).
- Scope-länkar: `country_ids` (fldUzFvGIVt7DTewe → core_countries), `customer_type_ids` (fldfJX5voIBhC1uHf → core_customer_types), `partner_ids` (fld4AImoSBem8oDwn → core_partners), `customer_type_page_ids` (fldUdo93AjejvKwYx → cms_customer_type_pages).

**9 records migrerade** `cms_case_pages` → `cms_cases` (card_*/legacy/order + kopierade country/partner/customer_type_page-länkar; backlänkar auto-populerade via Airtables symmetriska länkar). Old → new record-id:
| slug | gammalt (cms_case_pages) | nytt (cms_cases) |
|---|---|---|
| m100-motorstartare | rec96OqooK6XuihFx | recwBkq471hLCou3A |
| dtu-datacenter | rec98zlaCJTnlrZmn | recOP3YNgGe4W06J4 |
| slc500-migration | recDTxdd23cUiwQho | recogNVvtA9KBWHzY |
| polarbrod-natverk | recIw9Nr3uHwUEL64 | recFFJMJlJSGWGG7R |
| maskinbyggare-fjarraccess | recKadnINNGr4kLpp | recnCOqP9AA8iDAHL |
| egan-fjarraccess | recPX3JQM0QHs0AGN | rec32ODuwQXYup9Tt |
| installator-rm-certifiering | recRRw9w3AxQ82BSe | recaZU0mEOD9IFbH8 |
| polarbrod-pe-teknik | recmYUmhzoo1wrxrz | recz6jXUDkPrpmc08 |
| pontum-rockwell | rectuKgZveRNG8qJI | rechIDc98ngCqc1qA |

**Reciprok-fält omdöpta till `case_ids` (→ cms_cases), snake_case:**
- `core_countries.case_ids` (fld9hz50RKRdcY6UU), `core_customer_types.case_ids` (fldcPwxY2EraqCaEb), `core_partners.case_ids` (fldKYQEbep8QFbxMS) — nya reciproker av cms_cases-scope-länkarna.
- `cms_customer_type_pages.case_ids` (fldcDeRmGuSBnjYLc) — ny reciprok; **gamla** `case_ids` (→ cms_case_pages) omdöpt till `legacy_case_page_ids` (fldm8RjqJKFbwN1RN) för att frigöra namnet.
- `cms_products.case_ids` (fldheJbRGUnyVwi4E, var auto-namnet "cms_cases") — reciprok av cms_cases.product_ids.
- `cms_articles.case_ids` (fldvMXBIGjNPNwlzY, var "cms_cases") — reciprok av cms_cases.article_ids.
- `cms_page_sections.cg_case_manual_ids` (fldEm4ITFjnjhqN5Y) — **nytt** länkfält → cms_cases (tomt; case-grid manuellt urval); **gamla** (→ cms_case_pages) omdöpt till `legacy_cg_case_manual_ids` (fldbOSQjQmDBsTGWP).

**Verifiering:** backlänkar bekräftade efter migration — Sverige (`rec7N339jnJ17sfTy`) `case_ids` = alla 9, Rockwell (`recfJUCWX8ycPxjC5`) = 3 (m100/slc500/pontum), panelbyggare (`recejwjfVP9322RLF`) = m100. `cms_product_pages` har ingen case-länk (kontrollerat — ingen missad konsument); `cms_case_pages` hade exakt 6 länkrelationer, alla hanterade.

### Kod — `wexoeplugins`
- `wexoe-core/entities/cms_cases.php`: utbyggd med `order`, scope-länkar (country/customer_type/customer_type_page/partner) och kort-lagret (card_* + legacy_external_url). Docblock: nu kanonisk case-entitet.
- Konsument-entiteter pekar om till `cms_cases`: `customer_type_pages.php` (`case_ids`), `cms_page_sections.php` (`cg_case_manual_ids`), `partner_pages.php` (`case_ids`).
- Feature-plugins: `wexoe-customer-type-page.php`, `wexoe-pages/sections/case-grid.php` (+ title-fallback `card_title ?? title ?? slug`), `wexoe-partner-page/src/Renderer.php` — alla `Core::entity()` / `Permalink::for_record()` → `cms_cases`.
- `wexoe-core/src/Helpers/Permalink.php`: patterns reducerade till enbart `cms_cases`.
- **Raderade filer:** `entities/case_pages.php`, `entities/cases.php`, `write-entities/case_pages.php`.
- `php -l` rent på alla rörda filer.

### Kod — `wexoebuilder`
- `lib/wexoe-cache-entities.ts`: `CUSTOMER_TYPE_PAGE_ENTITIES` och `PARTNER_ENTITIES` `case_pages`/`cases` → `cms_cases` (matchar PHP-entiteterna för cache-invalidering).
- `lib/permalink.ts`: patterns + `caseUrl()` → `cms_cases`.
- Schema-MD uppdaterade (`airtable-schema-cms-page.md`, `-pa.md`, `-customer-type.md`) + kommentarer i `airtable.ts`, `customer-type-{types,mapper}.ts`, customer-type editor/preview.
- **Behållet medvetet:** linked-source-pickern `cases` (i `lib/linked-sources.ts`) — den nyckeln följer picker-konventionen med korta namn (syskon: `products`→cms_products, `articles`→cms_articles, `product_areas`→cms_product_pages), pekar redan client-side på `tblxH3ECSMvDTYrIQ`, och går *inte* via PHP-entiteten. Att döpa om den skulle bryta syskonkonventionen.

### Designbeslut
- **`cms_cases` blev kanonisk** (inte `cases`): cms_-prefix enligt naming-lås, och både buildern (`/api/case`, case-mapper) och `wexoe-case`-pluginet använder redan namnet.
- **Bidirektionell migration:** genom att kopiera *case-sidans* länkfält till de nya recorden auto-populerades alla `core_*`/konsument-backlänkar — inga per-konsument-record-skrivningar behövdes.

### ⚠️ Manuella TODOs efter merge (Airtable MCP saknar `delete_field`/`delete_table`)
1. **Radera tabellen `cms_case_pages` (`tbl3uMV6IpRIZeucA`)** i Airtable-UI. Det tar automatiskt bort dessa nu tomma/legacy länkfält som pekar på den:
   - `core_countries.case_page_ids` (fldpxDOqsY7myVjFA)
   - `core_customer_types.case_page_ids` (fldQkL9YUyoCVMZsy)
   - `core_partners.case_page_ids` (fldbMD2eZuBlf20Ji)
   - `cms_products.case_page_ids` (fldbd7lSPQn8KZi1D)
   - `cms_customer_type_pages.legacy_case_page_ids` (fldm8RjqJKFbwN1RN)
   - `cms_page_sections.legacy_cg_case_manual_ids` (fldbOSQjQmDBsTGWP)
2. Kontrollera att inga Airtable-vyer/automationer refererar `cms_case_pages` innan radering.
3. Rensa `wexoe-core`-cache (eller vänta ut 24h TTL) så frontend läser de nya länkarna.
4. Re-installera plugin-zip i WP (ingen auto-deploy).

---

## Wexoe NY: efter-migration link-rewiring (2026-05-16)

**Branch:** `claude/migrate-airtable-wexoe-nv1T5`
**Bakgrund:** Efter att data kopierats till Wexoe NY upptäcktes att flera linked-record-fält var tomma trots att källdatan i gamla `Wexoe`-basen hade kopplingar. Detta orsakade att Product Area-sidor (t.ex. PLC) renderade utan side-menu/produktlistor och att partner-filter per division gav 0 träffar. Audit-skript jämförde gamla och nya basen post-by-post.

### Fas A — `cms_articles.product_ids` (62 länkar)

**Symptom:** 100 % av nya `cms_articles` (59 records) hade tom `product_ids`. Gamla `Articles.Link to products` (fld7ZUaOseVqIuaZH) hade kopplingar på 16 fiber/koppar-produkter — varje artikel kopplad till 1-2 produkter.

**Åtgärd:** Mappade old article ID → new article ID via primary-name (med 4 manuella overrides för records som omdöptes i nya basen — `Cat7 S/FTP` splittades till `Cat7 S/FTP` + `Cat7 S/FTP Siamese`, `Cat6 U/UTP` splittades till `Dca` + `Fca`). Mappade old product ID → new product ID via name (med 2 manuella overrides för dubbla `Installationskabel`-records — disambiguerade via owning PA: koppar-record → `recXmWh5FeiQ6zthM`, fiber-record → `recLPTNu7jOetSzBP`). Skrev `product_ids` på alla 59 nya artiklar via `update_records_for_table`. Airtable propagerade automatiskt symmetriska länkar till `cms_products.article_ids` — verifierat på 3 stickprov (ODF har nu 7 artiklar, Installationskabel/koppar har 8, Patchkablar SM har 3).

**Resultat:** Sido-menu mode på Product Area (t.ex. Koppar, Fiber) kan nu rendera artikel-tabeller per produkt. Cache i `wexoe-core` måste rensas för att de nya länkarna ska slå igenom på frontend (24 h TTL + 6 h stale grace).

### Fas B — `cms_product_pages.division_ids` (19 länkar)

**Symptom:** 100 % av nya `cms_product_pages` hade tom `division_ids` (fldiGDCr7mHBpb6pL). Gamla `Product Areas.Division` (fldLIbqYkDnM6jB6P) pekade på antingen INDUSTRY eller IT INFRA på alla 19 records.

**Mapping (user-confirmed):**
- INDUSTRY (gamla `rec39zJoKEAbWCMQ1`) → Automation (`recEjCdWID8v09l0S`)
- IT INFRA (gamla `recQhHFAQ0ERXWCJv`) → IT Infra (`recOd94pVsu2GPyGw`)
- POWER, BUILDING INFRASTRUCTURE — *hoppas över* (legacy per användarens beslut)

**Resultat:** 15 PA → Automation, 4 PA → IT Infra (vfd, ibe, robot, hmi, io, onmachine, mjukvara, plc, lagspanning, protokoll, remote, switch, motion, gear, safety → Automation; fiber, koppar, rack, ftto → IT Infra). Skrevs på alla 19 records via en `update_records_for_table`-batch.

### Fas C — `core_partners.division_ids` (14 länkar)

**Symptom:** 100 % av nya `core_partners` (17 records) hade tom `division_ids` (fld5xVFWyWLJZAFFn). Gamla `Partners.Division` (fldaIAV3a79LQVyNv) hade kopplingar på alla 17 partners.

**Mapping:** Samma som Fas B (INDUSTRY → Automation, IT INFRA → IT Infra, POWER/BUILDING INFRASTRUCTURE skippas).

**Resultat per partner:**
- → Automation: ProSoft, IRINOX, HMS, Rockwell Automation, Wittenstein, Spectrum / AMCI (6 st)
- → IT Infra: Ekkosense, Microsens (var IT INFRA + BUILDING INFRA — endast IT Infra migrerad), R&M (samma), LBW, Assetspire, Fibrain (samma), TrendNET, nVent Schroff (8 st)
- → tom (POWER/BUILDING INFRASTRUCTURE-only): Arteche (POWER), Hager (BUILDING), Steinel (BUILDING). 3 records lämnas utan division_ids per användarens beslut att inte migrera dessa divisioner.

**Manuell TODO för användaren:** Om Arteche, Hager, eller Steinel ska visas i partner-marquees/listor som filtrerar per division måste de få en division manuellt i Airtable UI.

### Fas D — Legacy-flaggning av offerings + product_navigation

**Bakgrund:** Audit hittade att två entiteter pekar på tabeller som inte finns i Wexoe NY:

- `wexoe-core/entities/automation_offerings.php` → `cms_offerings` (saknas)
- `wexoe-core/entities/automation_product_navigation.php` → `cms_product_navigation` (saknas)

Båda har `table_id => null` med kommentaren "Sätts efter MCP-skapande". Konsekvens: `[wexoe_offerings]` och `[wexoe_product_nav]` shortcodes renderar tomma sektioner utan synligt fel (returnerar `[]` från `Core::entity(...)->all()`).

**Användarens beslut:** Lämnas som legacy. Migreras senare (inte i denna omgång).

**Åtgärd:** Lagt prominent `!!! LEGACY — PENDING MIGRATION !!!`-block i:
- `wexoe-core/entities/automation_offerings.php` (top-of-file docblock)
- `wexoe-core/entities/automation_product_navigation.php` (samma)
- `New plugins/automation-pillar/wexoe-offerings-tabs.php` (plugin header + docblock)
- `New plugins/automation-pillar/wexoe-product-nav.php` (samma)

Varje notis pekar på rätt steg för att avsluta migrationen när det blir aktuellt (skapa tabell via MCP enligt `MIGRATION-PLAN.md`, kopiera N records från specifik gammal tabell-ID, fyll i `table_id`).

**Också `automation_team_members`:** Pekar fortfarande på gamla `Coworkers` (`tblldarIcIpxlZ9GV`) i Wexoe-basen. Redan flaggad `@deprecated` i sin entity-fil. Lämnas oförändrad — migration till `core_coworkers` är dokumenterad där.

---

## Wexoe → Wexoe NY-migration (2026-05-14)

**Branch:** `claude/migrate-wexoe-database-7KILw` (båda repon)
**Källplan:** `MIGRATION-PLAN.md` (rotbiblioteket i `wexoeplugins`)
**Mål:** Migrera all aktiv data från `Wexoe` (`appXoUcK68dQwASjF`) till `Wexoe NY` (`appokKSTaBdCa8YiW`) med snake_case-konvention överallt.

### Status översikt

| Fas | Status |
|---|---|
| Naming-conventions etablerade | ✅ Klar (MIGRATION-PLAN.md) |
| `Old plugins/` raderad | ✅ Klar |
| `wexoe-core/entities/*.php` uppdaterade till nya basen + snake_case | ✅ Klar |
| `wexoe-core/write-entities/core_*.php` uppdaterade | ✅ Klar |
| Renaming av befintliga `core_*`-fält i Airtable till snake_case | ✅ Klar (alla 8 core_*-tabeller) |
| `wexoebuilder` `lib/airtable.ts`, `lib/core/*` uppdaterade | ✅ Klar |
| Build-out av `cms_*`-tabellscheman i Airtable | ⚠️ Återstår (se TODO nedan) |
| `cms_unique_pages` fält-rename till snake_case | ⚠️ Återstår |
| Datamigration från gamla basen | ⚠️ Återstår |
| `wexoebuilder` `lib/page-mapper.ts`, `product-area-mapper.ts`, `audience-mapper.ts` snake_case-update | ⚠️ Återstår |
| `wexoebuilder` `lib/claude-transform.ts` + `lib/airtable-schema-*.md` snake_case-update | ⚠️ Återstår |
| `wexoebuilder` `app/api/*` route-handlers BASE_ID + fält-snake_case | ⚠️ Återstår |

### Genomförda ändringar (kod)

**wexoeplugins:**
- `MIGRATION-PLAN.md` — ny fil, kanonisk plan med target-schema per tabell, naming conventions, migrationsordning.
- `Old plugins/` — raderad (12 filer, ~14400 rader). Innehöll endast legacy plugins som inte längre används.
- `wexoe-core/entities/landing_pages.php` — pekar på `cms_landing_pages` (tblpPlk17FZIKawXY) i Wexoe NY, snake_case-passthrough.
- `wexoe-core/entities/lp_tabs.php` — pekar på `cms_landing_page_tabs` (tblp8d32aj5BgGMvE).
- `wexoe-core/entities/lp_downloads.php` — pekar på `cms_landing_page_downloads` (tbltAtilGKnQ2wc7I).
- `wexoe-core/entities/product_areas.php` — pekar på `cms_product_pages` (tbl5PQR7FNHCogeya). `sections` pseudo-array borttagen — sektioner ligger nu i separat `product_page_sections`-entitet.
- `wexoe-core/entities/audience_heroes.php` — pekar på `cms_customer_type_pages` (tblZufoWVNKPuJdMK). Case-fält borttagna — finns nu i separat `case_pages`-entitet.
- `wexoe-core/entities/articles.php` — pekar på `cms_articles` (tblhnz3MQG1JwfKrN).
- `wexoe-core/entities/products.php` — pekar på `cms_products` (tblN23V7uAMpeZoO1).
- `wexoe-core/entities/solutions.php` — pekar på `cms_solutions_mini` (tblxK7ikOgLFuze6m, omdöpt från cms_solutions_concepts).
- `wexoe-core/entities/automation_offerings.php` — pekar på (planerad) `cms_offerings`.
- `wexoe-core/entities/automation_product_navigation.php` — pekar på (planerad) `cms_product_navigation`.
- `wexoe-core/entities/automation_team_members.php` — flaggad `@deprecated`. Pekar fortfarande på gamla `Coworkers` i Wexoe-basen. Migreras till `core_coworkers` när `New plugins/automation-pillar/wexoe-team-rack.php` uppdateras.
- `wexoe-core/entities/cms_unique_pages.php` — snake_case-passthrough.
- `wexoe-core/entities/core_company.php` — snake_case-passthrough. `company_name`-fält tillagt.
- `wexoe-core/entities/core_countries.php` — snake_case-passthrough. `active` → `is_active`.
- `wexoe-core/entities/core_divisions.php` — snake_case-passthrough.
- `wexoe-core/entities/core_customer_types.php` — snake_case-passthrough.
- `wexoe-core/entities/core_coworkers.php` — snake_case-passthrough. `image` → `image_url`.
- `wexoe-core/entities/core_partners.php` — snake_case-passthrough. `logo` → `logo_url`, `logo_transparent` → `logo_transparent_url`.
- `wexoe-core/entities/core_testimonials.php` — snake_case-passthrough. `author_image` → `author_image_url`, `featured` → `is_featured`.
- `wexoe-core/entities/core_graphic_profile.php` — snake_case-passthrough. `logo_primary` → `logo_primary_url`, `logo_dark_background` → `logo_dark_url`, `favicon` → `favicon_url`.
- `wexoe-core/entities/case_pages.php` — **NY**. Schema för cms_case_pages (tbl3uMV6IpRIZeucA).
- `wexoe-core/entities/partner_pages.php` — **NY**. Schema för cms_partner_pages (tblQv5E8pSgwxy6wU).
- `wexoe-core/entities/product_page_sections.php` — **NY**. Schema för cms_product_page_sections (skapas).
- `wexoe-core/entities/inbox_form_submissions.php` — **NY**. Schema för inbox_form_submissions (skapas).
- `wexoe-core/entities/customers.php` — **RADERAD**. Tabellen migreras inte (oanvänd).
- `wexoe-core/entities/partners.php` — **RADERAD**. Duplikat av `core_partners`.
- `wexoe-core/write-entities/core_company.php` — snake_case-passthrough.
- `wexoe-core/write-entities/core_countries.php` — snake_case-passthrough.
- `wexoe-core/write-entities/core_divisions.php` — snake_case-passthrough.
- `wexoe-core/write-entities/core_customer_types.php` — snake_case-passthrough.
- `wexoe-core/write-entities/core_coworkers.php` — snake_case-passthrough.
- `wexoe-core/write-entities/core_partners.php` — snake_case-passthrough.
- `wexoe-core/write-entities/core_testimonials.php` — snake_case-passthrough.
- `wexoe-core/write-entities/core_graphic_profile.php` — snake_case-passthrough.

**wexoebuilder:**
- `lib/airtable.ts` — `BASE_ID` ändrad till `appokKSTaBdCa8YiW`. `SSOT_BASE_ID` är nu alias. `TABLE_IDS` uppdaterade till nya cms_*-tabell-IDs. `createRecords` och `updateRecords` accepterar nu valfri `baseId`.
- `lib/core/mapper.ts` — alla readers och writers använder snake_case fält-keys. Bildfält bytt till `*_url`-konvention.
- `lib/core/types.ts` — TS-interfaces uppdaterade till snake_case. `internal_notes` lagt till på alla. `active` → `is_active`, `featured` → `is_featured`.
- `lib/core/forms.ts` — form-config använder snake_case keys. `image` → `image_url`, `logo` → `logo_url`, etc. `core_company` har nytt `company_name`-fält.

### Airtable-mutationer

**Renaming till snake_case (alla via MCP):**

Tabell-rename:
- `cms_solutions_concepts` → `cms_solutions_mini` (tblxK7ikOgLFuze6m)

Fält-rename per tabell:
- `core_company` (tblwq9y74ertsNyYG): 19 fält omdöpta (Slug → slug, Internal Notes → internal_notes, Is Default → is_default, Company Name → company_name, etc.)
- `core_countries` (tblCZ082jWGUBrUAK): 9 fält omdöpta (Name → name, Code → code, Domain → domain, URL Prefix → url_prefix, Currency → currency, Locale → locale, Default Language → default_language, Order → order, Active → is_active)
- `core_divisions` (tblyxs2zsoRBozxQS): 6 fält omdöpta
- `core_customer_types` (tblLsYRMZz6JA6GBK): 6 fält omdöpta
- `core_coworkers` (tblYwMQlW9HFd41pg): 11 fält omdöpta (inkl Image → image_url, Division → division_ids, Country → country_ids)
- `core_partners` (tblZ5YIYFelxA0nBm): 9 fält omdöpta (inkl Logo → logo_url, Logo Transparent → logo_transparent_url)
- `core_testimonials` (tbl1pe0bWz5zdkqJF): 11 fält omdöpta (inkl Author Image → author_image_url, Featured → is_featured)
- `core_graphic_profile` (tbl4c4HjiKVCcJI5v): 16 fält omdöpta (inkl Logo Primary → logo_primary_url, Logo Dark Background → logo_dark_url, Favicon → favicon_url)

**Totalt: 87 fält omdöpta.**

**Datamigration:**

- `core_partners`: 17 records skapade från gamla Partners-tabellen (Rockwell Automation, HMS, Wittenstein, Spectrum / AMCI, ProSoft, IRINOX, R&M, Fibrain, Microsens, Ekkosense, LBW, nVent Schroff, Assetspire, TrendNET, Hager, Steinel, Arteche). Inkluderar logo_url där tillgängligt (R&M, LBW). Country = SE. Division-länkar lämnade tomma — gamla basens divisioner (INDUSTRY, IT INFRA, POWER, BUILDING INFRASTRUCTURE) matchar inte nya basens (Automation, Industri, Kassasystem). Användaren måste mappa manuellt.

- `core_coworkers`: 8 unika records skapade från gamla Coworkers-tabellen (Joakim Knutson, Niclas Fransson, Jari Turja, Jan Hildeby, Andreas Grünerwald, Edis Residovski, Tony Annell, David Eriksson). Inkluderar full_name, title, email, phone, image_url, bio. Country = SE. Division-länkar lämnade tomma av samma skäl som ovan.

OBS: Tre tomma stub-records ligger fortfarande i `core_partners` och tre i `core_divisions` från initial bootstrap. Kan raderas manuellt i Airtable UI.

### LP-familj fullt migrerad (2026-05-14, fortsättning)

**Schemas byggda i Wexoe NY via MCP:**

- `cms_landing_page_downloads` (tbltAtilGKnQ2wc7I): name (primary, renamed från Name), internal_notes, is_active, order, description, thumbnail_url, file_url, button_text, tab_ids (auto-skapad back-link, omdöpt från default).
- `cms_landing_page_tabs` (tblp8d32aj5BgGMvE): name (primary, renamed), internal_notes, is_active, order, tab_type (singleSelect: textimage/fullmedia/faq/calameo/downloads/compare/steps), 5 textimage-fält, fm_url, faq_items, 6 calameo-fält (3 slots), download_ids (länk → downloads), 4 compare-fält, 2 steps-fält, landing_page_ids (auto back-link omdöpt). Totalt 25 fält.
- `cms_landing_pages` (tblpPlk17FZIKawXY): slug (primary, renamed), internal_notes, is_active, country_ids, h1, 3 SEO-fält, 6 hero-fält, 3 content-fält, sidebar_type, 6 case-fält, 2 calc-fält, 6 event-fält, 5 magnet-fält, 6 contact-fält, 4 show-flaggor, 2 color-fält, tab_ids, 15 contact_form-fält. Totalt ~60 fält.

**Datamigration (37 records):**

- 4 downloads: Datablad, ControlLogix 5580 Datablad, Whitepaper ROI, Migrationsguide PLC-5
- 24 tabs (alla typer representerade): textimage (7), faq (4), compare (5), steps (4), fullmedia (2), downloads (2), calameo (1)
- 9 LPs: test123, ftto-webinar, optixtest, test-event, plc5-migration, fjarraccess, optix, test-calc, ftto

Alla länk-fält (tab_ids på LPs, download_ids på downloads-tabs) kopplade korrekt. Country=SE på alla LPs.

### Articles fullt migrerat (2026-05-14, fortsättning)

Alla **59 articles** migrerade från gamla `Articles`-tabellen (`tblb87eWIjnW3ttOL`) till `cms_articles` (`tblhnz3MQG1JwfKrN`) i Wexoe NY.

**Schema byggt i cms_articles:**
- `name` (primary, fanns redan) — artikelnamn
- `internal_notes`, `is_active` — standard-konvention
- `article_number` — leverantörs-artikelnummer (R&M R-prefix, Fibrain 79.74.x)
- `description`, `datasheet_url`, `webshop_url`, `image_url`, `variants`
- `supplier_ids` (link → core_partners) — automatisk back-link `article_ids` skapad och omdöpt på core_partners

**Supplier-mappning gamla → nya partners:**
- R&M: `recMmERnDdYBcrWTc` → `recZLKKd0QejsMilJ`
- Fibrain: `recownOIUhe24Jw44` → `recrl80IrxgD0Fo25`
- LBW: `recOMlO0NS73wa3yr` → `recaOEsWnO1gq7Opp`

**Volym per leverantör:**
- R&M: ~30 records (Cat6/Cat7 kabel, RJ45-jack, Uttag, etc.)
- Fibrain: ~25 records (Pigtails, Patchkablar, Adaptrar, ODF, Blåsfiber, etc.)
- LBW: ~5 records (ODF-system)
- Utan supplier: 1 record (ODF DIN-skena)

OBS: Articles-entiteten i `wexoe-core/entities/articles.php` pekar fortfarande på gamla basen som fallback (eftersom PA-renderern läser articles via PA → product → article-länken, och PA-data inte är migrerat). När PA migreras byts entitetens table_id till `tblhnz3MQG1JwfKrN`.

### Codex review-fix (2026-05-14, fortsättning)

PR#25 (wexoeplugins) och PR#42 (wexoebuilder) review:ades av Codex och fyra typer av buggar identifierades:

1. **`landing_pages.php` — hero_image vs hero_image_url, contact_form_show vs show_contact_form.** LP renderer i `wexoe-landing-page.php` läste fortfarande de gamla nycklarna. Eftersom LP är fullt migrerat denna session uppdaterades renderern till nya snake_case_url-namnen.

2. **`product_areas.php` — section_ids vs sections-pseudo-array.** PA-renderern läste `$row['sections']` (gammalt pseudo-array-mönster) men entiteten exposed bara `section_ids`-länkar. Eftersom PA-data inte är migrerat till `cms_product_pages` än, reverterades PA-entiteten till att peka på gamla basen med pseudo-array intakt.

3. **PA + Audience-entiteter pekade på tomma cms_*-stubbar.** Liknande problem — entitet uppdaterad till nya basen i föregående PR utan att data migrerats. Reverterade till gamla basen.

4. **`lib/airtable.ts` — default BASE_ID byttes utan att uppdatera unmigrated routes.** Product Area och Audience routes använder fortfarande `PA_TABLE_IDS` / `AUDIENCE_TABLE_IDS` som pekar på gamla basen. Lade till `LEGACY_BASE_ID`-export och uppdaterade alla PA/audience-anrop att explicit pass:a `baseId: PA_BASE_ID` / `AUDIENCE_BASE_ID`.

5. **Hardcoded `appXoUcK68dQwASjF` i route handlers.** `app/api/read/route.ts`, `publish/route.ts`, `copy/route.ts` hade gamla bas-ID hårdkodat. Fixade alla att använda `BASE_ID` / `LEGACY_BASE_ID` korrekt och konverterade LP-route handlers att läsa snake_case-fält.

6. **`{Is Default}=TRUE()` formula i core/[entity]/route.ts.** Duplicate-default-guarden för core_graphic_profile filtrerade på den gamla PascalCase-fältnamnet. Bytt till `{is_default}=TRUE()`.

**Filer ändrade i wexoeplugins (fix-PR `claude/fix-codex-review-and-continue-migration`):**
- `New plugins/wexoe-landing-page/wexoe-landing-page.php` — hero_image_url, case_image_url, contact_image_url, show_contact_form
- `wexoe-core/entities/{audience_heroes,product_areas,products,solutions,articles}.php` — reverted till OLD base + PascalCase sources

**Filer ändrade i wexoebuilder (fix-PR `claude/fix-codex-review-base-id-and-formula`):**
- `lib/airtable.ts` — LEGACY_BASE_ID export
- `lib/audience-mapper.ts`, `lib/product-area-mapper.ts` — AUDIENCE_BASE_ID, PA_BASE_ID export
- `lib/audience-loader.ts`, `lib/product-area-loader.ts` — pass baseId
- `lib/page-mapper.ts` — full snake_case conversion för LP-fält + Contact Form
- `app/api/read/route.ts` — snake_case + remove hardcoded BASE_ID
- `app/api/publish/route.ts` — snake_case + use TABLE_IDS.landingPageTabs/Downloads
- `app/api/copy/route.ts` — split LP (NEW base, snake_case) + PA/Audience (legacy)
- `app/api/audience/route.ts`, `app/api/product-area/route.ts` — pass legacy baseId
- `app/api/core/[entity]/route.ts` — `{Is Default}=TRUE()` → `{is_default}=TRUE()`

### Återstående arbete (manuella TODOs eller follow-up-session)

Allt nedan följer mönstret som etablerats. Kan automatiseras i en uppföljande session — kräver främst ytterligare MCP-anrop.

#### A. Bygg ut cms_*-tabellscheman i Airtable

Stub-tabellerna (`cms_landing_pages`, `cms_landing_page_tabs`, `cms_landing_page_downloads`, `cms_product_pages`, `cms_customer_type_pages`, `cms_case_pages`, `cms_partner_pages`, `cms_products`, `cms_articles`, `cms_solutions_mini`) har idag bara `Name` + `Notes` + `Assignee` + `Status` + `Attachments` + `Attachment Summary`-fält. De behöver utökas med fält enligt `MIGRATION-PLAN.md` (avsnitt "Target-schema per tabell").

Per tabell:
1. Rename `Name` → primary key från spec (`slug` eller `name`)
2. Rename `Notes` → `internal_notes`
3. Rename `Assignee`, `Status`, `Attachments`, `Attachment Summary` → `__deprecated_*` (MCP saknar delete_field)
4. Skapa alla övriga fält enligt spec via `create_field`. Linked records sist (efter att alla tabeller finns).

Nya tabeller att skapa (via `create_table`):
- `cms_product_page_sections`
- `cms_offerings`
- `cms_product_navigation`
- `inbox_form_submissions`

#### B. Rename `cms_unique_pages` fält till snake_case

`cms_unique_pages` (tblpAM1wZWDbrpeai) har idag ~50 fält i PascalCase. Måste döpas om till snake_case enligt sektionen "core_*-fält — renaming till snake_case" i MIGRATION-PLAN.md (samma mönster).

#### C. Migrera data från gamla basen

Per tabell, läs records från gamla basen, transformera fält, skriv till nya basen via MCP. Volymerna är hanterbara:

| Källa | Mål | Volym |
|---|---|---|
| Partners (tblsCOF5BPAxN6nmq) | core_partners | 17 records |
| Divisions (tblKam1tUTlR13atl) | core_divisions | 4 records (men nya basen har redan 3 namngivna + 3 tomma; behöver konsolideras) |
| Coworkers (tblldarIcIpxlZ9GV) | core_coworkers | 9 records |
| Landing Pages (tbl8KDqGq0Ray1uqS) | cms_landing_pages | 9 records |
| LP Tabs (tblvecOh3rAGmw3mw) | cms_landing_page_tabs | 24 records |
| LP Downloads (tblbLM827DzjWGjCR) | cms_landing_page_downloads | 4 records |
| Product Areas (tblgatNFYFMwF4EcQ) | cms_product_pages + cms_product_page_sections | 19 records (sektion-splittas) |
| Customer types (tblvNf1CqAYEFvTpu) | cms_customer_type_pages + cms_case_pages | 9 records (case-splittas) |
| Products (tblHafyCEyh7S3Y64) | cms_products | ~100 records (uppskattat) |
| Articles (tblb87eWIjnW3ttOL) | cms_articles | 59 records |
| Solutions & Concepts (tblc98m9MJcpbWAVU) | cms_solutions_mini | 11 records |
| Offerings (tbldQZJu3NHHP5dUh) | cms_offerings | 7 records |
| Product navigation (tblJa2Kd6QHjFXPJZ) | cms_product_navigation | 20 records |

**Division-mappning:** Gamla basens divisioner (INDUSTRY, IT INFRA, POWER, BUILDING INFRASTRUCTURE) matchar inte nya basens (Automation, Industri, Kassasystem). Användaren behöver besluta mappning innan division-länkar kan migreras. Förslag: skapa 4 extra divisions i nya basen (eller mappa INDUSTRY→Automation, övriga→nya divisioner som ska skapas).

**Linked-record-rewiring:** Efter all data är kopierad, gå igenom varje länk-fält och ersätt gamla record-IDs med nya enligt en `gammalt_record_id → nytt_record_id`-mappning.

#### D. Uppdatera wexoebuilder-mappers för cms_*-tabeller

Följande filer i `wexoebuilder/lib/` har hårdkodade PascalCase-fältnamn som måste uppdateras till snake_case efter att Airtable-renames är gjorda:

- `lib/page-mapper.ts` — LP record → state
- `lib/product-area-mapper.ts` — PA record → state
- `lib/audience-mapper.ts` — Audience record → state
- `lib/unique-page-mapper.ts` — kontrollera om fältreferenser behöver uppdateras

Plus Claude-prompt-filer:
- `lib/claude-transform.ts`
- `lib/airtable-schema-lp.md`
- `lib/airtable-schema-pa.md`

Plus API routes:
- `app/api/read/route.ts` — hårdkodat `appXoUcK68dQwASjF` på rad 6 → byt till `BASE_ID`-import
- `app/api/publish/route.ts` — hårdkodat `appXoUcK68dQwASjF` på rad 236
- `app/api/copy/route.ts`
- `app/api/product-area/route.ts`
- `app/api/audience/route.ts`

#### E. Migrera `automation_team_members` till `core_coworkers`

Plugin `New plugins/automation-pillar/wexoe-team-rack.php` använder fortfarande `Core::entity('automation_team_members')` som pekar på gamla Wexoe-basen. För att kunna släcka gamla basen:

1. Lägg till team-rack-specifika fält på `core_coworkers` om de behövs publikt (`team_rack_tag`, `module_color`, `module_id`)
2. Uppdatera `wexoe-team-rack.php` att kalla `Core::entity('core_coworkers')` med nya fältnamn
3. Radera `wexoe-core/entities/automation_team_members.php`

#### F. Rensa gamla basen

Efter att alla data är migrerade och produktion verifierad:
1. Exportera gamla basen till CSV som backup
2. Sätt gamla basen till read-only i Airtable UI
3. Vänta minst 2 veckor utan trafik mot den
4. Slutgiltigt: radera basen

### Designbeslut

- **snake_case ÖVERALLT.** Airtable display-namn och kod-fält är identiska. Detta gör att mapper-koden blir trivial passthrough.
- **kebab-case för slug-VÄRDEN** (inte fältnamn). Slugs är web-URLs.
- **`is_*`-prefix för booleans** (is_active, is_default, is_featured). Tidigare `active`/`featured` byttes ut.
- **`*_url`-suffix för WP Media-URL-fält** (image_url, logo_url, author_image_url, etc.). Tidigare osuffigerade.
- **`internal_notes`-fält på alla entiteter** för redaktörs-noteringar. Inte renderat publikt.
- **Domain keys i wexoe-core matchar Airtable-fältnamn 1:1.** Eliminerar översättnings-lager.
- **Sektioner (Normal 1-4) bryts ut till egen tabell `cms_product_page_sections`.** Variabelt antal av samma typ ger renare schema än pseudo-array.
- **Case-fält bryts ut till egen tabell `cms_case_pages`.** Återanvändbara cases istället för inline på audience-sidor.
- **Pillar-sidor migreras inte separat — slås ihop med `cms_unique_pages`.** Samma data-driven-mönster fungerar för båda.
- **`Customers`, `Leads`, `Webpage campaigns`, `Product news RA` migreras inte.** Verifierat oanvända.
- **Make.com är out-of-scope.** Användaren har lagt om submission-flödet att gå direkt till Airtable.

### Verifiering efter deploy

1. Trigga `wexoe-core`-cache-rensning för alla migrerade entiteter (via REST-endpoint eller WP-admin).
2. Verifiera produktion:
   - Öppna en LP, jämför mot screenshot från före migration
   - Öppna en Product Area, jämför
   - Öppna en Audience-sida, jämför
   - Verifiera team-grids, partners-marquee, testimonials
3. Verifiera wexoebuilder:
   - Öppna `/globals/company`, `/globals/coworkers`, `/globals/partners`, etc. — bör nu visa data från Wexoe NY
   - Skapa ett test-record, publicera, verifiera Airtable-sidan
   - Redigera ett befintligt record, publicera, verifiera ändringen

---

## Justeringar mot ursprungsplan

Efter klargorande från användare:

| Punkt | Plan | Justering |
|---|---|---|
| Fas 1 — radera placeholder cms_*-tabeller | Radera 8 cms_*-tabeller | **Behålls.** De ska migreras senare. Endast initial-data + Hours-fält på core_company. |
| Validering | WP-test, dev-server-test | **Hoppas över.** Skriv koden, dokumentera vad som behöver verifieras efter deploy. |
| Fas 11 | Inkl. Make.com + WP-db-sökning | **Bara kod-del.** Externa steg dokumenteras som manuella TODOs. |
| Audience/Customer types | — | Lämnas som är. Ingen omdöpning. |

---

## Loggens format

Per fas dokumenteras:
- **Filer skapade/ändrade** (med absoluta sökvägar)
- **Airtable-mutationer**
- **Designbeslut på vägen**
- **Manuella TODOs** (saå användare måste göra efteråt)
- **Status:** in-progress / klar

---

## Refaktor — Bilder via WP Media URL (2026-05-14)

**Branch:** `claude/airtable-image-to-wp-urls-W1fzm`
**Status:** Klar.

**Syfte:** Byt 12 bild-fält från `multipleAttachments` → `singleLineText` URL i Airtable Wexoe NY. Bilderna pekar nu på WP Media Library (wp-content), inte Airtable-uppladdningar.

**Airtable-mutationer (Wexoe NY `appokKSTaBdCa8YiW`):**
- `core_graphic_profile` (tbl4c4HjiKVCcJI5v): Logo Primary, Logo Dark Background, Favicon → singleLineText.
- `core_customer_types` (tblLsYRMZz6JA6GBK): Icon → singleLineText.
- `core_coworkers` (tblYwMQlW9HFd41pg): Image → singleLineText.
- `core_partners` (tblZ5YIYFelxA0nBm): Logo, Logo Transparent → singleLineText.
- `core_testimonials` (tbl1pe0bWz5zdkqJF): Author Image → singleLineText.
- `cms_unique_pages` (tblpAM1wZWDbrpeai): Hero Image, Text Image A Image, Text Image B Image → singleLineText.

OBS: Airtable MCP-servern saknar `delete_field`-endpoint. Gamla attachment-fält döptes till `[old]` (t.ex. "Logo Primary [old]"). Radera dem manuellt i Airtable UI.

**Filer ändrade i `wexoeplugins/`:**
- `wexoe-core/entities/core_graphic_profile.php` — logo_primary, logo_dark_background, favicon: tog bort `'type' => 'attachment'`, plain string-keys.
- `wexoe-core/entities/core_customer_types.php` — icon: plain string-key.
- `wexoe-core/entities/core_coworkers.php` — image: plain string-key.
- `wexoe-core/entities/core_partners.php` — logo, logo_transparent: plain string-keys.
- `wexoe-core/entities/core_testimonials.php` — author_image: plain string-key.
- `wexoe-core/entities/cms_unique_pages.php` — hero_image_url → hero_image, text_image_a_image_url → text_image_a_image, text_image_b_image_url → text_image_b_image (tog bort _url-suffix + attachment-typ).
- `wexoe-core/write-entities/core_graphic_profile.php` — tog bort attachment_url-entries ur field_types.
- `wexoe-core/write-entities/core_customer_types.php` — tog bort attachment_url-entry ur field_types.
- `wexoe-core/write-entities/core_coworkers.php` — tog bort attachment_url-entry ur field_types.
- `wexoe-core/write-entities/core_partners.php` — tog bort attachment_url-entries ur field_types.
- `wexoe-core/write-entities/core_testimonials.php` — tog bort attachment_url-entry ur field_types.
- `wexoe-core/write-entities/cms_unique_pages.php` — tog bort attachment_url-entries + bytte keys (hero_image_url → hero_image, text_image_a_image_url → text_image_a_image, text_image_b_image_url → text_image_b_image).
- `wexoe-core/src/Renderers/TeamGrid.php` — $img is_array-gren ersatt med `(string) ($c['image'] ?? '')` cast.
- `wexoe-core/src/Renderers/PartnersMarquee.php` — $logo is_array-gren ersatt med `(string) ($p['logo'] ?? '')` cast.
- `wexoe-core/src/Renderers/TestimonialCard.php` — $author_image is_array-gren ersatt med `(string) ($t['author_image'] ?? '')` cast.
- `New plugins/wexoe-pages/wexoe-pages.php` — `wexoe_pages_attachment_url()`-helpern raderad; hero_image/text_image_a_image/text_image_b_image används direkt; contact_person image hämtas nu som plain string.

**Filer ändrade i `wexoebuilder/`:**
- `lib/core/mapper.ts` — `firstAttachmentUrl()` raderad; readGraphicProfile/readCustomerType/readCoworker/readPartner/readTestimonial använder `asString()`; `writeAttachment()` raderad; write-funktioner använder `cleanField()` direkt.
- `lib/unique-page-mapper.ts` — `attachUrl()` + `attachInput()` raderade; Hero Image/Text Image A+B Image läses med `asString()` och skrivs som plain string (null om tom).

**Designbeslut:**
- Airtable `delete_field`-API saknas i MCP-servern → gamla attachment-fält döptes till `[old]` istället för radering. Radera manuellt i Airtable UI för att städa upp.
- TS-state-properties behåller `imageUrl`-namn (inte `image`) — camelCase-konvention, matchar `HeroState`/`TextImageState`-interfaces.

**Verifiering:**
- `grep -r 'attachment' wexoe-core/entities/ wexoe-core/write-entities/ wexoe-core/src/Renderers/ 'New plugins/wexoe-pages/'` → 0 träffar.
- `grep -r 'firstAttachmentUrl\|writeAttachment\|attachUrl\|attachInput' lib/` → 0 träffar.
- PHP-renderers: inga `is_array`-grenar för bild-variabler.
- TS: `cleanField(s.logo_primary)` returnerar null för tom sträng — Airtable rensar fältet vid update.

**Manuella TODOs efter deploy:**
1. Radera `[old]`-fälten i Airtable UI (12 st): Logo Primary [old], Logo Dark Background [old], Favicon [old] i core_graphic_profile; Icon [old] i core_customer_types; Image [old] i core_coworkers; Logo [old], Logo Transparent [old] i core_partners; Author Image [old] i core_testimonials; Hero Image [old], Text Image A Image [old], Text Image B Image [old] i cms_unique_pages.
2. Fyll i WP Media Library-URL:er i de nya URL-fälten i Airtable för att aktivera bilder.
3. Verifiera att TeamGrid, PartnersMarquee, TestimonialCard renderar korrekt med en coworker/partner/testimonial som har URL i Image-fältet.

---

## Fas 11 — Polish & deprecation (kod-delen)

**Status:** Klar (kod-delen). Externa steg dokumenterade som TODOs nedan.

**Filer ändrade:**
- `Old plugins/wexoe-contact-form.php` — plugin-header bytt till `[DEPRECATED] Wexoe Contact Form`, version `1.3.0-deprecated`. Lägg admin_notices som varnar admin om plugin är aktivt.

**Avgränsningar (designbeslut för MVP):**
- **SEO-meta retrofit för LP/PA/Audience** — inte implementerat. Skulle kräva nya SEO-fält i Airtable (LP `SEO Title`, `SEO Description`, `OG Image URL`), schema-utökning, och `wp_head`-hook i resp. plugin. cms_unique_pages har redan dessa (Fas 5). Lämnas som senare polish.
- **"Saved sections / page-templates (CMS Save → klona)"** — uppskattat som en mini-feature i sig själv. Inte i denna runda.
- **Audit-länkar i `/globals/*` → "Visa historik i Airtable"** — kräver direkt-deep-link-URL till Airtable record-side. Trivialt att implementera senare när vi vet exakt format.
- **"Vad använder det här SSOT-recordet?"-vy** — kräver reverse-search över Wexoe-basens LP/PA-records. Senare fas.
- **Bulk-operationer i collections** — senare.
- **"Cache rensad i Wexoe Core ✓"-bekräftelse efter save** — `invalidateWexoeCoreCache` loggar redan success men UI:t har ingen bekräftelse. Senare polish.

**Manuella TODOs efter deploy (kräver tillgång till produktion):**

1. **Datamigration av befintliga sidor som använder `[wexoe_contact_form]`-shortcoden:**
   - Sök i WP-databasen: `SELECT ID, post_title FROM wp_posts WHERE post_content LIKE '%[wexoe_contact_form%';`.
   - För varje träff:
     - Identifiera page-typ (är det en LP/PA/Audience eller en stand-alone-sida?).
     - Om LP/PA/Audience: sätt `Show Contact Form=true` i Airtable, fyll i avvikande titel/subtitel om olika från default.
     - Ta bort `[wexoe_contact_form]`-shortcoden från post-innehållet.
     - Ta bort den explicit-döpta "kontakt"-WP-sektionen om sådan finns.
   - För stand-alone-sidor: ersätt med `[wexoe_page slug="..."]` (cms_unique_pages med `Show Contact Form=true`).

2. **Make.com-flöden:**
   - Identifiera alla Make.com-scenarier som hänger på `https://hook.eu1.make.com/sulae2u3lux9g9dqfabtsdngiwz46s6g` (gamla webhook).
   - Skapa motsvarande Airtable-automation: "Trigger när Submission Type = contact i User data".
   - Parallell-kör i 1 vecka. Verifiera att samma data flödar till samma destinations.
   - Inaktivera Make-webhook.

3. **Slutgiltig deprecation:**
   - När WP-databasen visar 0 träffar för `[wexoe_contact_form` → radera `Old plugins/wexoe-contact-form.php`.
   - Tills dess: behåll plugin aktivt men dokumentera att alla nya sidor MÅSTE använda Show Contact Form-checkbox-flödet.

---

## Fas 10 — Globals-driven defaults i LP/PA

**Status:** Klar (med en avgränsning).

**Filer skapade i `wexoebuilder/`:**
- `lib/default-coworker.ts` — `resolveDefaultCoworker()` slår upp första aktiva coworker från SSOT (filtrerad på country, valfritt division). `contactFieldsEmpty()` är guard-funktionen.

**Filer ändrade:**
- `app/api/publish/route.ts` (LP create) — om alla `contact_*`-fält är tomma vid create, kalla `resolveDefaultCoworker({country: 'SE'})` och förfyll innan Claude-transform.
- `app/api/product-area/route.ts` (PA create) — samma mönster, country=SE.

**Designbeslut:**
- **Default-injektion sker bara om ALLA contact_*-fält är tomma.** Manuella värden bevaras alltid.
- **Country är hårdkodad till `SE`** för MVP. Bättre lösning: härled country från sidans URL/division (kräver mer state-modellering). Senare fas.
- **Division-filtrering hoppas över i PA**: Wexoe-basens `Divisions`-tabell (`tblKam1tUTlR13atl`) är INTE samma som SSOT-basens `core_divisions`. För att korsbas-matcha skulle vi behöva slug-mappning. Lämnas som öppen fråga.
- **Default-injektion sker server-side i publish-route**, inte i klienten. Detta säkerställer att Claude-transform alltid får default-värdena.
- **`_contact_source: 'default-coworker' | 'manual'`-flagga** från planen INTE implementerad — onödig komplexitet för MVP. Kan läggas till senare som banner i builder.

**Manuella TODOs efter deploy:**
- Skapa ny LP utan att fylla i contact_*-fält → publishera → kontrollera att Airtable har coworker-data från SSOT.
- Skapa ny LP med manuellt `contactName` → publishera → kontrollera att den manuella datan bevaras.
- Befintliga LP utan ändringar → contact-fält ska vara oförändrade (vi triggar bara default vid CREATE-grenen).
- Skapa coworker för Country=SE i `/globals/coworkers` om ingen finns — annars är default-injektion meningslös.

---

## Fas 9 — Builder ContactFormEditor i LP/PA/Audience

**Status:** Klar.

**Filer ändrade i `wexoebuilder/`:**
- `lib/types.ts` — `PageState` får `showContactForm + contactForm`. SectionId utökad med `'contactForm'`.
- `lib/state.ts` — `initialState` har emptyContactFormState.
- `lib/page-mapper.ts` — `pageStateFromRecords` hämtar nu `Show Contact Form` + Contact Form-fält. Nya helpers `contactFormFromRecord()` + `contactFormToFields()` för delade builder-helpers.
- `lib/audience-types.ts` + `lib/audience-mapper.ts` — samma utökning.
- `lib/product-area-types.ts` + `lib/product-area-mapper.ts` — samma.
- `lib/claude-transform.ts` — buildLpPayload + buildPaPayload skickar nu alla 15 Contact Form-fält i payload. System prompts (LP + PA) anger att Contact Form-fält ALLTID ska emitteras vid UPDATE.
- `lib/airtable-schema-lp.md`, `lib/airtable-schema-pa.md` — dokumenterar de 15 Contact Form-fälten + Formatregel 9 (Contact Form-fält alltid inkluderade).
- `components/EditorPanel.tsx` (LP) — ny quick-nav-pill "Kontaktform" + sektion-panel som visar ContactFormEditor när `showContactForm`.
- `components/audience/AudienceBuilder.tsx` — samma mönster för Audience.
- `components/product-area/ProductAreaBuilder.tsx` — samma mönster för PA.

**Designbeslut:**
- **Delad ContactFormEditor + delad ContactFormPreview** används av alla fyra page-typerna utan duplicering. Wrappers per page-typ är tunna inline-blocks som mappar state.
- **Visibility-toggle inline i header** istället för i CollapsibleSection — alla tre LP/PA/Audience har lite olika visibility-konventioner så det är enklare att hålla helt explicit.
- **Audience har INGEN egen `contact_*`-fält** så `show_contact_person=true` faller tillbaka på SSOT-uppslag i renderaren (`Collections::coworkers_for_scope(['limit'=>1])`). Builder-state har bara `contactForm`-data, inte kontaktperson.
- **Befintliga "Visitkort"-sektioner (LP `contact_*`, PA `Contact *`)** lämnas oförändrade. Kontaktformuläret är ADDITIVT — visa antingen visitkort, kontaktform, eller båda.

**Manuella TODOs efter deploy:**
- Verifiera LP-editor: öppna en LP, gå till "Kontaktform"-panel, fyll i title/subtitle, spara → Airtable uppdaterad. Cache-bust → WP-sidan visar kontaktform med rätt värden.
- Verifiera att Claude-transform tappar inte Contact Form-fält vid UPDATE: ändra ett annat fält → publishera → kontrollera att Contact Form-värdena är oförändrade i Airtable.
- Befintliga LP utan Contact Form-värden → state.contactForm = emptyContactFormState(), showContactForm = false.

---

## Fas 8 — Per-plugin contact-form i LP/PA/Audience

**Status:** Klar.

**Airtable-mutationer (Wexoe-basen):**
- 15 `Contact Form *`-fält tillagda på `Landing Pages` (`tbl8KDqGq0Ray1uqS`).
- 15 fält på `Product Areas` (`tblgatNFYFMwF4EcQ`).
- 15 fält på `Audience Heroes`/`Customer types` (`tblvNf1CqAYEFvTpu`).

**Filer ändrade:**
- `wexoe-core/entities/landing_pages.php` — lade till `contact_form_*` domain-keys.
- `wexoe-core/entities/product_areas.php` — samma.
- `wexoe-core/entities/audience_heroes.php` — samma.
- `New plugins/wexoe-landing-page/wexoe-landing-page.php` — anrop till `wexoe_lp_render_contact_form_section()` sist i shortcode-render.
- `New plugins/wexoe-product-area/wexoe-product-area.php` — `wexoe_pa_render_contact_form_section()` följer samma mönster.
- `New plugins/wexoe-audience-hero/wexoe-audience-hero.php` — privat `render_contact_form_section()` på klass-instansen.

**Designbeslut:**
- Alla wrappers gör `class_exists('\\Wexoe\\Core\\Renderers\\ContactForm')` guard.
- `<section id="kontakt">`-wrappar bevaras så befintliga `href="#kontakt"`-länkar fortsätter att fungera.

**Manuella TODOs efter deploy:**
- Verifiera LP med Show Contact Form=true → ny `<section id="kontakt">` renderas.
- Verifiera PA samma.
- Verifiera Audience: kontrollera att hero-marginaler (100vw) inte påverkas.

---

## Fas 7 — Core ContactForm-helper + AJAX-handler

**Status:** Klar.

**Filer skapade i `wexoeplugins/wexoe-core/`:**
- `src/Renderers/ContactForm.php`
- `src/ContactForm/Handler.php`

**Filer skapade i `wexoebuilder/`:**
- `lib/contact-form-types.ts`
- `components/contact-form/ContactFormEditor.tsx`
- `components/contact-form/ContactFormPreview.tsx`

**Filer ändrade:**
- `src/Plugin.php` — registrerar `ContactForm\Handler::register()` på `init`-action.
- `components/UniquePageBuilder.tsx` — lade till "Kontakt" quick-nav-pill + ContactFormEditor-panel.
- `components/unique-page/preview/UniquePagePreview.tsx` — renderar ContactFormPreview när `showContactForm=true`.

**Manuella TODOs efter deploy:**
- Verifiera POST till `/wp-admin/admin-ajax.php?action=wxcf_submit` returnerar 200.
- Verifiera honeypot: skicka med `_hp=anything` → 200 success men ingen rad i Airtable.
- Verifiera rate-limit: 11 submissions → 11:e returnerar 429.

---

## Fas 6 — Core render-helpers + sektion-rendering

**Status:** Klar.

**Filer skapade i `wexoeplugins/wexoe-core/src/Renderers/`:**
- `Hero.php`, `TextImage.php`, `TextOnly.php`, `Faq.php`, `TeamGrid.php`, `PartnersMarquee.php`, `TestimonialCard.php`, `CtaBanner.php`.

**Filer ändrade:**
- `src/Core.php` — ny `Core::renderer($type)`-facade.
- `New plugins/wexoe-pages/wexoe-pages.php` — full sektion-rendering i fast ordning.

**Manuella TODOs efter deploy:**
- Verifiera team-grid med coworker i `/globals/coworkers`.
- Verifiera CSS-prefix-isolering.

---

## Fas 5 — `cms_unique_pages` + wexoe-pages-plugin (skelett)

**Status:** Klar.

**Airtable-mutationer:**
- Skapade tabell `cms_unique_pages` (`tblpAM1wZWDbrpeai`) i Wexoe NY.
- Skapade test-record: slug=`test-page`, H1=Testsida, Published=true, Country=[SE].

**Filer skapade:**
- `wexoe-core/entities/cms_unique_pages.php`
- `wexoe-core/write-entities/cms_unique_pages.php`
- `wexoe-core/src/Constants.php`
- `New plugins/wexoe-pages/wexoe-pages.php`
- `lib/unique-page-types.ts`, `lib/unique-page-mapper.ts`, `components/UniquePageBuilder.tsx` m.fl.

**Manuella TODOs efter deploy:**
- Aktivera `wexoe-pages`-pluginen i WP-admin.
- Lägg `[wexoe_page slug="test-page"]` på en WP-sida för att verifiera shortcoden.

---

## Fas 4 — Builder: `/globals/*` för SSOT-redigering

**Status:** Klar.

**Filer skapade i `wexoebuilder/`:**
- `lib/core/registry.ts`, `lib/core/types.ts`, `lib/core/mapper.ts`, `lib/core/forms.ts`, `lib/core/loader.ts`, `lib/core/reserved-slugs.ts`.
- `components/core/CoreEntityShell.tsx`, `components/core/CoreEntityForm.tsx`.
- `app/api/core/[entity]/route.ts`, `app/globals/page.tsx`, `app/globals/[entity]/page.tsx`.

**Manuella TODOs efter deploy:**
- Verifiera `/globals` listar 8 entitet-kort.
- Verifiera CRUD-cykel på core_company.

---

## Fas 3 — Wexoe Core: REST CRUD för SSOT

**Status:** Klar.

**Filer skapade i `wexoeplugins/wexoe-core/`:**
- write-entities för alla 8 core-entiteter.
- `src/EntityRestApi.php`.

**Filer ändrade:**
- `src/AirtableClient.php`, `src/WriteRepository.php`, `src/Core.php`, `src/Plugin.php`.

**Manuella TODOs efter deploy:**
- Sätt `wexoe_core_webhook_secret`-option i WP.
- Verifiera 401/200/403/409-svar på /entity/-routes.

---

## Fas 2 — Wexoe Core: SSOT-scheman + Helpers

**Status:** Klar.

**Filer skapade i `wexoeplugins/wexoe-core/`:**
- Entities för alla 8 core-entiteter.
- `src/Helpers/Context.php`, `src/Helpers/Singletons.php`, `src/Helpers/Collections.php`.

**Filer ändrade:**
- `src/Plugin.php` — `Plugin::SSOT_BASE_ID`-konstant.
- `src/EntityRepository.php` — `get_base_id()` + base_id-stöd.

**Manuella TODOs efter deploy:**
- Verifiera att SSOT-fetcher fungerar mot Wexoe NY-basen.

---

## Fas 1 — Airtable-städning

**Status:** Klar (justerad scope).

**Airtable-mutationer (`Wexoe NY` `appokKSTaBdCa8YiW`):**
- Hours-fält tillagda på `core_company`.
- Initial-data: core_countries (SE), core_divisions (3 st), core_customer_types (3 st), core_company (default), core_graphic_profile (default).

**Manuella TODOs efter deploy:**
- Fyll i `core_company.wexoe-se` med faktiska värden.
- Fyll i `core_graphic_profile.default` med färger, loggor, typsnitt.

---

## Fas 0 — BuilderShell-extraktion

**Status:** Klar.

**Filer skapade/ändrade i `wexoebuilder/`:**
- `components/BuilderShell.tsx` (ny).
- `components/audience/AudienceBuilder.tsx` (refaktor).

**Manuella TODOs efter deploy:**
- Verifiera att Audience-editor fungerar likadant som före refaktor.

---

## Wexoe ALB Blocks — Avia Layout Builder-element (2026-05-14)

**Branch:** `claude/alb-blocks-development-TKF1u`
**Plan:** `UTVECKLINGSPLAN-ALB-BLOCKS.md`
**Status:** Klar (fas 1, alt. A: förladdad lista + JS-filter).
**Testat mot Enfold-version:** Ej verifierat i WP — pinna versionsnummer efter första deploy.

**Syfte:** Ersätta manuella `[wexoe_page slug="..."]`-shortcodes i Code Block med ett riktigt ALB-element ("Wexoe Content") som har två dropdowns: innehållstyp + post.

**Filer skapade i `New plugins/wexoe-alb-blocks/`:**
- `wexoe-alb-blocks.php` — bootstrap, dependency checks (`wexoe-core` + Enfold), `avia_load_shortcodes`-hook som pekar Enfold på vår `shortcodes/`-katalog, frontend shortcode-fallback, `admin_enqueue_scripts`.
- `includes/content-types.php` — filtrerbar `wexoe_alb_content_types()`-registry, generisk `wexoe_alb_list_by_slug()`, render-dispatch `wexoe_alb_render()` (med core_ready-guard), fyra render-wrappers.
- `shortcodes/wexoe_content/wexoe_content.php` — `Wexoe_Content_Block extends aviaShortcodeTemplate` (popup_elements, editor_element, shortcode_handler). Sökväg/filnamn följer Enfolds discovery-konvention.
- `assets/builder.js` — delegerad change-lyssnare + MutationObserver för att filtrera `content_id`-options på vald `content_type`. Optionernas value har formatet `{type}:{slug}`.
- `assets/builder-icon.svg` — enkel ikon för builder-modalen.

**Designbeslut på vägen:**
- **Scope fas 1:** fyra entiteter med slug-primärnyckel och befintlig render-funktion: `cms_unique_pages`, `landing_pages`, `audience_heroes`, `product_areas`. `automation_pillar` (samling av fem shortcodes utan egen entitet) och `partners`/`contact_page` (saknar slug-baserad render) hoppas över i denna fas.
- **Wrap-funktioner i registry:** varje typ har en egen render-callback i `content-types.php` som anpassar API-skillnader (rått slug vs `['slug' => $slug]`-array vs do_shortcode för klassmetoder).
- **Dropdown-strategi:** alt. A enligt plan (alla optioner förladdade + JS-filter). Migration till alt. B (AJAX) först om totala antalet poster överstiger ~500.
- **Prefix-format `{type}:{raw_id}`:** löser slug-kollisioner mellan entiteter. `shortcode_handler` strippar prefixet före render — råa IDs fungerar fortfarande (bakåtkompat).
- **Frontend-shortcode-fallback:** `[wexoe_content]` registreras alltid via `init`-hook, även när Enfolds builder-pipeline inte är aktiv (t.ex. AMP, REST-render).

**Manuella TODOs efter deploy:**
- Aktivera pluginet i WP-admin på sajt med Enfold + wexoe-core.
- Verifiera att "Wexoe Content"-ikonen visas under "Content Elements" i ALB.
- Klicka "Update Avia Templates" i Enfold → Theme Options om modalen inte visar nya fälten (Avia cachar element-struktur).
- Testa att båda dropdowns fungerar och att `content_id`-listan filtreras när `content_type` byts.
- Skapa testsida som renderar varje entitetstyp och jämför HTML mot motsvarande manuella shortcode.
- Pinna faktisk Enfold-version här i loggen efter första deploy.

---
