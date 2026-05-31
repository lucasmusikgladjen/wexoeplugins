---
description: Lägg till en ny cms_page_sections-sektionstyp (alla touchpoints) och verifiera med väktaren
---

Du ska lägga till en ny sektionstyp `$ARGUMENTS` till `cms_page_sections`.
En sektion är "klar" först när ALLA touchpoints finns och väktaren är grön.

Följ stegen i ordning. Visa förslag och invänta godkännande innan du skapar filer.

1. **Enum (källan):** Lägg en rad i `packages/schema/enums/section-types.json`:
   `{ "type": "<snake_case>", "label": "<svensk label>", "render": "<kebab>.php" }`.
   Detta är sanningskällan — PHP och builder härleds/valideras mot den.

2. **PHP-renderare:** Skapa `apps/wordpress/plugins/wexoe-pages/sections/<kebab>.php`.
   Den returnerar en closure `function ($section, $page, $ctx): string`. Studera
   en befintlig (t.ex. `hero.php` eller `faq.php`) för mönstret: escaping
   (`esc_html`/`esc_attr`/`esc_url`), `$ctx`-helpers, CSS-scoping. Använd Core-
   helpers (`Markdown`, `Color`, `Lines`) — duplicera aldrig dem.

3. **Dispatcher:** Lägg `'<snake_case>' => '<kebab>.php'` i
   `wexoe_pages_section_renderers()` i `apps/wordpress/plugins/wexoe-pages/wexoe-pages.php`.

4. **Builder-typ + preview + inspector:** Lägg sektionstypen i
   `apps/builder/lib/cms-page-types.ts` (SectionType-union + variant-interface),
   en preview i `components/cms-page/preview/` och ett formulär i
   `components/cms-page/editors/`. Följ en befintlig sektions mönster.

5. **Airtable:** Påminn användaren att lägga `<prefix>_*`-fälten på
   `cms_page_sections` + ett val i `section_type`-singleSelect (manuellt i Airtable).

6. **Verifiera:** Kör `npm run guardian`. Den ska lista din nya sektion och vara
   grön. Åtgärda allt den flaggar. Kör `npm run check` som slutkoll.

Sammanfatta vad du skapade + vad användaren måste göra manuellt i Airtable.
