# GAP-analys: Landing page-editor vs partner-editor

## Referens: vad som gör landing page-panelen ren

Landing page-editorpanelen är konsekvent och lätt att skanna:
- Kortfattade labels och placeholders.
- Minimal hjälptrtext.
- Ingen teknisk internjargong (Airtable/plugin/backend).
- Fokus på innehåll, inte implementation.

## GAP i partner-editorerna

1. **För mycket intern teknisk kontext i UI**
   - Flera paneler visade text om plugin-rendering, fallback-logik och datakällor.
2. **Överanvändning av hjälptrtext**
   - Många små texter förklarade regler som redan framgår av fälten.
3. **Ojämt språk i labels/hints**
   - Blandning av redaktionellt språk och systemorienterade uttryck.
4. **Visuell clutter**
   - Extra informationsblock och delare skapade onödigt brus.

## Designprinciper som tillämpades

- Behåll bara text som hjälper redaktören att fatta rätt beslut direkt.
- Ta bort backend-/Airtable-/plugin-referenser ur editor-UI.
- Låt labels och placeholders bära huvuddelen av vägledningen.
- Behåll varningar som förhindrar konkreta fel (exempel: fler case än vad som kan visas).

## Genomförda förbättringar

### 1) Rensning av tekniska kommentarer i kod
- Tog bort interna blockkommentarer i partner-editorer som beskrev datamodell och rendering.

### 2) Förenklad microcopy
- Tog bort markdown-hints och tekniska description-texter där fälten redan är självförklarliga.
- Ersatte lång förklaring i "Benefits" med kort: "En fördel per rad".

### 3) Mindre visuell friktion
- Tog bort separata hjälprader under sektioner (t.ex. fallback-förklaringar och visningslogik).
- Behöll endast relevant varning för case-överskridning.

## Förväntad UX-effekt

- Renare paneler med mindre kognitiv belastning.
- Snabbare redigering eftersom användaren möter färre förklaringsblock.
- Bättre konsekvens med landing page-editorns tonalitet och informationsnivå.
