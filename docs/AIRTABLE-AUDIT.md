# Airtable-audit — så funkar den (och hur du slår på den)

## Vad den gör, i klarspråk

Allt innehåll bor i Airtable. Koden har en egen "ritning" över hur Airtable
ska se ut — vilka kolumner som finns, vad de heter, vilken sorts data de
håller. Den ritningen ligger i `packages/schema/`.

Problemet: **ritningen och verkligheten kan glida isär utan att någon märker
det.** Någon döper om en kolumn i Airtable, eller tar bort en, eller lägger
till ett nytt val i en lista. Koden vet inget om det — förrän en sida slutar
spara eller visar fel, ofta långt senare.

Airtable-auditen är en automatisk kontroll som **jämför ritningen med den
riktiga Airtable-basen** och säger till om de inte stämmer överens. Den läser
bara — den ändrar aldrig något i Airtable.

Den tittar på fyra saker:

- Finns alla tabeller som ritningen förväntar sig?
- Finns alla kolumner — och saknar Airtable någon, eller har Airtable en extra
  som ritningen inte känner till?
- Är varje kolumn av rätt sort (text, siffra, ja/nej, koppling)?
- Stämmer listan av sektionstyper (`section_type`) exakt mellan kod och
  Airtable?

## Du behöver aldrig köra den själv

Den här kontrollen lever **helt inne i GitHub**. Du behöver ingen terminal och
inga kommandon. GitHub kör den åt dig automatiskt:

- **Varje måndag morgon** — så att drift fångas även veckor då ingen rört koden
  (t.ex. om någon pillat direkt i Airtable).
- **När någon ändrar ritningen** (`packages/schema/`) i en pull request — så att
  ändringen genast jämförs mot verkligheten.
- **När du själv vill** — med en knapp inne på GitHub (se längst ner).

Men först måste du göra en **engångsinställning**: ge GitHub en nyckel så att
den får läsa din Airtable-bas. Det tar ett par minuter och beskrivs steg för
steg här under. Det här är enda gången du behöver göra något manuellt.

---

## Engångsinställning (ca 5 minuter)

### Steg 1 — Skapa en läs-nyckel i Airtable

En "nyckel" (Airtable kallar den *personal access token*) är som ett lösenord
som bara får läsa, inget annat.

1. Gå till **https://airtable.com/create/tokens** (logga in om det behövs).
2. Klicka **Create new token**.
3. Ge den ett namn du känner igen, t.ex. `GitHub schema-audit`.
4. Under **Scopes**, klicka *Add a scope* och lägg till exakt dessa två:
   - `schema.bases:read`
   - `data.records:read`
   (De låter krångliga, men betyder helt enkelt "får läsa basens uppbyggnad och
   dess innehåll". Inget mer — den kan inte ändra eller radera något.)
5. Under **Access**, klicka *Add a base* och välj **Wexoe NY**-basen.
6. Klicka **Create token**.
7. Airtable visar nu en lång textsträng som börjar på `pat...`. **Kopiera
   hela.** Du ser den bara den här enda gången — får du bort den, gör bara en
   ny token.

### Steg 2 — Klistra in nyckeln i GitHub

Nu lägger vi nyckeln i GitHub på ett säkert ställe ("secret") där bara
automatiken kan läsa den. Den syns aldrig i klartext för någon efteråt.

1. Gå till repots sida på GitHub.
2. Klicka **Settings** (kugghjulet/fliken högst upp i repot — inte ditt egna
   konto-Settings).
3. I vänstermenyn: **Secrets and variables** → **Actions**.
4. Klicka den gröna knappen **New repository secret**.
5. I fältet **Name**, skriv exakt: `AIRTABLE_API_KEY`
   (versaler och understreck, precis så.)
6. I fältet **Secret**, klistra in `pat...`-strängen från steg 1.
7. Klicka **Add secret**.

Klart. Det var hela inställningen.

> **Behöver jag `AIRTABLE_BASE_ID` också?** Nej, inte normalt. Auditen känner
> redan till standardbasen (Wexoe NY). Du behöver bara lägga till en secret som
> heter `AIRTABLE_BASE_ID` om ni byter till en annan bas i framtiden.

---

## Hur vet jag att det funkar?

Du kan starta kontrollen direkt med en knapp, utan att vänta till måndag:

1. Gå till fliken **Actions** högst upp i repot.
2. Klicka **Airtable-audit** i listan till vänster.
3. Klicka **Run workflow** (knapp till höger) → **Run workflow**.
4. Efter en stund dyker en rad upp med en **grön bock** eller ett **rött kryss**.

- **Grön bock** = ritningen och Airtable stämmer överens. Inget att göra.
- **Rött kryss** = något glappar. Klicka på raden för att läsa exakt vad — den
  skriver ut i klartext vilken tabell/kolumn det gäller, t.ex. *"fält 'cta_url'
  finns i schemat men saknas i Airtable"*. Då vet du precis vad som ska rättas:
  antingen i Airtable, eller i `packages/schema/`.

> **Innan du satt nyckeln** (eller om du tar bort den) blir körningen grön och
> säger bara "hoppad — nyckel saknas". Den blir alltså aldrig falskt röd. Den
> börjar göra verklig nytta först när nyckeln finns på plats.

---

## Vanliga frågor

**Kan den här kontrollen råka ändra eller radera något i Airtable?**
Nej. Nyckeln har bara läsrättigheter, och koden gör enbart läs-anrop.

**Vad händer om nyckeln slutar gälla eller jag vill byta?**
Skapa en ny token (steg 1) och klistra in den i samma secret (steg 2,
`AIRTABLE_API_KEY` — den skrivs då bara över). Inget annat behöver röras.

**Vart vänder jag mig om det rödmarkerar något jag inte förstår?**
Texten i körningen pekar ut tabell + kolumn. Tumregel: har *du* nyss ändrat i
Airtable → rätta i Airtable eller uppdatera `packages/schema/`. Har *koden* nyss
ändrats → kolla att schemaändringen verkligen gjorts i Airtable också.

**Var bor själva kontrollen, om någon vill titta?**
Logiken i `tools/airtable-audit.mjs`, och GitHub-uppsättningen i
`.github/workflows/airtable-audit.yml`. Båda är beroendefria och lästa uppifrån
och ner.
