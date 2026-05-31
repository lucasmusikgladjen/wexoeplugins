# `apps/builder/schema/` — synkad schemakopia (REDIGERA INTE)

> ⚠️ **Detta är en genererad kopia.** Originalet bor i
> `packages/schema/entities/` (monorepo-roten). Ändra fält **där**, kör
> `npm run schema:sync` från roten → den här kopian + WP-kopian
> (`apps/wordpress/wexoe-core/schema/`) uppdateras i samma veva. Väktaren
> (`npm run guardian`) failar om kopiorna driftar från originalet.

Buildern importerar dessa JSON-filer via `@/schema/<entity>.json` (t.ex. i
`lib/customer-type-mapper.ts`) och `lib/schema/to-state.ts` konsumerar dem för
record → state. Kopian måste vara committad eftersom Vercel-bygget läser den ur
builder-appen (`apps/builder` är Vercels Root Directory).

Varför en kopia och inte direktimport från `packages/schema`? För att hålla
builder-importvägen (`@/schema/*`) oförändrad och appen självständigt byggbar på
Vercel. Synken är en ren filkopia (ingen kodgenerering); originalet är det enda
som redigeras för hand.
