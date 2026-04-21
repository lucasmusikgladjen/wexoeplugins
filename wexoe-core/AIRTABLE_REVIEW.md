# Airtable-granskning (Wexoe Core)

Datum: 2026-04-21

## Uppdaterad bedömning (efter originalutvecklarens sammanfattning)

Efter att ha vägt in originalutvecklarens målbild för Core (stabil, central adapter med förutsägbara schema-typer och inkrementella förbättringar) är bedömningen:

- **Arkitekturen är sund och medvetet avgränsad.**
- **Största riskerna ligger främst i driftresiliens**, inte i att Normalizer saknar fler generiska typer.
- **Det är viktigare att hålla Core deterministisk för nuvarande migrationer** än att bredda typsystemet proaktivt.

Detta innebär att rekommendationen justeras från "utöka normalisering brett nu" till "prioritera robusthet i fetch/cache-felbanor och schemahygien".

## Verifierade styrkor i nuvarande implementation

1. En tydlig femlagers-arkitektur med ren ansvarsfördelning (client/cache/schema/normalizer/repository).
2. Schema-driven normalisering som redan täcker de faktiska typerna ni använder i migrationerna (`text`, `string`, `int`, `float`, `bool`, `lines`, `link`, `attachment`, `pseudo_array`).
3. Lång cache-TTL + jitter (24h ±5%) vilket är rimligt för att minska Airtable-tryck.
4. Bra observability för ett WP-plugin (logger + admin-diagnostik + testknappar).

## Potentiella brister (prioriterade)

1. **429/5xx hanteras utan retry/backoff**
   - Fel klassificeras korrekt, men inga återförsök med `Retry-After` eller exponential backoff.

2. **"Stale fallback" i praktiken saknas när transient har löpt ut**
   - Kommentarer nämner stale-beteende, men WordPress transients returnerar inte expired value.

3. **Schema och Airtable metadata valideras inte mot varandra**
   - Bra lokal schema-validering finns, men ingen automatisk kontroll att Airtable-fälttyp matchar schema-antaganden.

4. **Passthrough-fält kan ge typdrift över tid**
   - Om Airtable-fält byter typ (t.ex. URL → attachment) kan feature-plugins påverkas tyst.

## Rekommenderad ordning framåt

1. Lägg till begränsad retry-strategi för 429/5xx (max 2–3 försök, jitter, respekt för `Retry-After`).
2. Implementera riktig stale-on-error / stale-while-revalidate (soft/hard TTL) i repository-lagret.
3. Lägg till en admin-"schema health check" mot Airtable metadata (`/meta/bases/{base}/tables`).
4. Behåll nuvarande Normalizer-typuppsättning tills ett konkret schema kräver fler typer.
