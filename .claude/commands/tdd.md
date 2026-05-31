---
description: TDD ur en kravspec — skriv failande test först, implementera tills grönt
---

Kör test-driven utveckling för: `$ARGUMENTS`.

Arbetssätt (följ strikt — testet kommer FÖRE implementationen):

1. **Förstå kravet.** Omformulera `$ARGUMENTS` till en kort, testbar kravlista
   (1–5 punkter). Visa den. Är något oklart — fråga innan du skriver test.

2. **Välj testlager:**
   - Builder-logik (transform, mapper, schema-härledning, utils) → **Vitest** i
     `apps/builder` (`*.test.ts` bredvid koden eller i `__tests__/`).
   - PHP/Core (Normalizer, Schema::from_json, helpers, render) → **Pest** i
     `apps/wordpress` (`tests/`).

3. **Skriv failande test först.** Ett test per kravpunkt. Kör det och visa att
   det är RÖTT av rätt anledning (inte p.g.a. importfel):
   - Builder: `cd apps/builder && npx vitest run <fil>`
   - PHP: `cd apps/wordpress && ./vendor/bin/pest <fil>`

4. **Implementera minsta möjliga** för att gå grönt. Inga extrafunktioner.

5. **Kör tills grönt.** Refaktorera sedan med testerna som skyddsnät.

6. **Helhetskoll:** `npm run check` (väktaren) + relevant testsvit. Allt grönt.

Om testrunnern saknas (Vitest/Pest inte uppsatt än): säg till — det hör till
FAS 6 i `docs/MONOREPO-PLAN.md`, och jag sätter upp den först.

Sammanfatta: kravlista, vilka tester du skrev, röd→grön-resan.
