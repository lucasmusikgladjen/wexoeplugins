# Wexoe — Monorepo & Infrastruktur-plan (exekverbar)

> **Detta är ett exekveringsdokument**, skrivet för att en LLM-session ska kunna
> följa det fas för fas och göra alla ändringar. Det ersätter inte
> `ARKITEKTURPLAN.md` (modulariserings-refaktorn) — det *levererar fundamentet*
> som gör den planen enkel: ett repo, en schema-källa, en väktare, en
> teststege och repo-egna LLM-verktyg.
>
> **Beslutat av människa (2026-05-31):**
> 1. Monorepo — **nytt repo, bevarad historik** (git subtree från båda).
> 2. Schemakälla — **`packages/schema`** (delad, neutral).
> 3. Codegen — **skippas.** Väktaren (inte en kodgenerator) garanterar synk.
> 4. Väktaren — **slår brett från dag 1** (paritet + enum + schema↔typ + magiska strängar).
> 5. State-typ — **standardiseras** till `<Type>State`.
> 6. Minimal CI nu; **full TDD-uppställning** (Vitest + Pest + `/tdd`-skill).
> 7. `New plugins/` → `plugins/` (klart, PR #65).
> 8. Golden files — **senare** (per plugin, vid polish).
> 9. **Ingen hänsyn till "legacy ska funka parallellt".** Ett system. Migrera en gång.

**Statuslegend:** `[ ]` ej påbörjad · `[~]` pågår · `[x]` klar & verifierad · `[!]` blockerad.

---

## 0. Den hårda blockeraren (läs först)

Monorepo-skapandet kan **inte** göras av en Claude-session i den nuvarande
miljön: GitHub-verktygen + git-push är låsta till de två befintliga repona, och
containern är ephemeral (inget överlever utan push till ett existerande remote).

**Därför kräver FAS 0 en människa.** Tre vägar (i ökande Claude-andel):
- **(A) Nytt tomt repo via terminal + subtree** (§0.1). Du kör ~5 git-steg, ger
  Claude scope mot nya repot, Claude kör FAS 1–8. Renast namn, mest terminal.
- **(B) Claude bygger om ETT befintligt repo till monorepot.** Claude *får* pusha
  till `wexoeplugins`/`wexoebuilder`. Claude flyttar in plugins-koden i
  `apps/wordpress/`, drar in den andra kodbasen i `apps/builder/` (historik
  bevarad), bygger `packages/` m.m. Du gör bara: **(1)** GitHub Settings → Rename
  → `wexoe` (gammal länk redirectar), **(2)** peka om Vercel till `apps/builder`.
  **Noll terminal, noll token-strul.** Rekommenderas givet online-arbetsflöde.
- **(C) Du kör git-stegen lokalt** utifrån scripten här; Claude skriver all kod.

Allt från FAS 1 och framåt förutsätter att monorepot existerar och Claude har scope mot det.

---

## 1. Målarkitektur

```
wexoe/                              # nytt repo (namn TBD av människa)
├── apps/
│   ├── builder/                    # = gamla wexoebuilder (Next.js/TS), via subtree
│   └── wordpress/                  # = gamla wexoeplugins, via subtree
│       ├── wexoe-core/             #   datalager + helpers (enda Airtable-auktoriteten)
│       │   └── schema/             #   committad spegelkopia av packages/schema (synk vid push, väktarskyddad — följer med i WP-zip)
│       └── plugins/                #   feature-plugins (en mapp per sidtyp)
├── packages/
│   └── schema/                     # ⭐ SANNINGSKÄLLAN — delad, neutral
│       ├── entities/<table>.json   #   en fil per entitet (fältlistan EN gång)
│       ├── enums/<name>.json       #   section_type m.fl. (menyn EN gång)
│       ├── index.ts                #   typed export för buildern (@wexoe/schema)
│       └── README.md               #   formatspec (superset av Normalizer-typerna)
├── tools/
│   ├── guardian/                   # ⭐ VÄKTAREN — bygger manifest + validerar paritet
│   │   ├── guardian.ts
│   │   ├── rules/                  #   en regel per check (paritet/enum/schema/strängar)
│   │   └── manifest.schema.json
│   └── schema-sync.ts              # kopierar packages/schema/entities → wexoe-core/schema (committat)
├── docs/
│   ├── ARKITEKTURPLAN.md           # flyttad hit (en kopia, inte två)
│   ├── MONOREPO-PLAN.md            # denna fil
│   ├── DOCS-MAP.md                 # ⭐ GENERERAD av guardian — "var bor allt"
│   └── decisions/                  # ADR:er (korta "varför"-filer)
├── manifest.json                   # ⭐ GENERERAD av guardian — maskinläsbar systemkarta
├── .github/workflows/ci.yml        # minimal CI: tsc/lint/vitest + php -l/PHPCS/pest + guardian
├── .claude/
│   ├── commands/                   # /add-section /add-page-type /tdd
│   ├── settings.json               # SessionStart-hook (deps install + sanity)
│   └── skills/                     # ev. längre skills
├── pnpm-workspace.yaml | (npm workspaces i root package.json)
├── package.json                    # root scripts: check / test / guardian / package
├── composer.json                   # (i apps/wordpress) Pest + PHPCS
└── CLAUDE.md                       # monorepo-router (ersätter de två gamla)
```

**Två hårda deploy-sanningar som planen respekterar:**
1. **WP-pluginet körs isolerat på WordPress-servern** — `packages/schema` finns
   inte där. Pluginet måste vara självförsörjande, så schema-JSON måste ligga
   *committat inuti* `wexoe-core/schema/` (den följer då automatiskt med när
   mappen laddas ner/zippas via en online-GitHub-zipper — inget script körs vid
   zip). `packages/schema/entities/` är **originalet** (redigeras för hand);
   `wexoe-core/schema/` är en **committad spegelkopia** som uppdateras av
   `npm run schema:sync` **vid kodändring/push**, aldrig vid zip. Väktaren (FAS 4)
   failar rött om kopian inte matchar originalet → kan aldrig drifta osett.
   Detta är samma modell som dagens `sync-schema.yml`, fast inom ett repo och
   väktarskyddad. (Inte codegen: en LLM redigerar bara originalet; synken är en
   ren filkopia.)
2. **Buildern (Vercel)** importerar `@wexoe/schema` som workspace-paket → samma
   filsystem, ingen kopia. Vercel: Root Directory `apps/builder`, install körs i
   monorepo-roten så workspace-länken finns; "Ignored Build Step" så att rena
   PHP-ändringar inte triggar builder-deploy.

---

## FAS 0 — Monorepo-fundament · **[människa kör]**

**Mål:** ett repo med båda kodbasernas historik bevarad, byggbart, deploybart.

### 0.1 Skapa repot + dra in historik (git subtree)
```bash
# 0. Skapa tomt repo på GitHub (t.ex. lucasmusikgladjen/wexoe), ingen README.
NEW=git@github.com:lucasmusikgladjen/wexoe.git

mkdir wexoe && cd wexoe && git init -b main
git commit --allow-empty -m "chore: init monorepo"

git remote add builder git@github.com:lucasmusikgladjen/wexoebuilder.git
git remote add wp      git@github.com:lucasmusikgladjen/wexoeplugins.git
git fetch builder && git fetch wp

# Bevarar full commit-historik under prefixen:
git subtree add --prefix=apps/builder    builder claude/wexoe-page-builder-setup-DmUOd
git subtree add --prefix=apps/wordpress  wp      main

git remote add origin $NEW && git push -u origin main
```
> Subtree (inte submodule) → en enda historik, inga extra kloningssteg, en LLM
> ser allt. PR:er i de gamla repona stängs; arbetet fortsätter här.

### 0.2 Workspace-skelett
- Root `package.json` med `"workspaces": ["apps/builder", "packages/*"]`
  (`apps/wordpress` står utanför npm-workspace — det är PHP).
- Root-scripts: `check` (guardian), `test`, `package`, `dev` (proxar builder).
- Flytta `apps/builder/package.json`-deps orört; lägg `@wexoe/schema` som dep.
- `packages/schema/package.json` (`"name": "@wexoe/schema"`, exporterar `index.ts` + JSON).
- `apps/wordpress/composer.json` med `pestphp/pest`, `squizlabs/php_codesniffer`, `wp-coding-standards/wpcs` (dev).

### 0.3 Vercel
- Nytt Vercel-projekt mot monorepot, **Root Directory = `apps/builder`**.
- Build Command default; säkerställ att install sker i roten (workspace-länk).
- **Ignored Build Step:** `git diff --quiet HEAD^ HEAD -- apps/builder packages/schema`
  (bygg bara om builder eller schema ändrats).
- Sätt env-varianter (`AIRTABLE_*`, `ANTHROPIC_API_KEY`, `AUTH_*`, `WEXOE_CORE_WEBHOOK_*`).

### 0.4 Acceptans
- [ ] `apps/builder` bygger på Vercel; `apps/wordpress/...` orört i drift.
- [ ] `git log apps/builder` och `git log apps/wordpress` visar gammal historik.
- [ ] En ren PHP-ändring triggar **inte** builder-deploy.

---

## FAS 1 — Delad schemakälla + migrera ALLA entiteter · **[BÅDA]** · kräver FAS 0

> Idag: 1/26 entiteter i JSON. Mål: **alla** i `packages/schema`, en källa.
> Detta gör "FAS 1" i `ARKITEKTURPLAN.md` klar på riktigt (inte pilot).

### 1.1 Sätt upp källan
- `packages/schema/entities/` = **originalet** (flytta dit dagens
  `cms_customer_type_pages.json` + alla nya i FAS 1.3).
- `packages/schema/README.md` = schema-format-spec (flyttas hit).
- `apps/wordpress/wexoe-core/schema/` förblir **committad** (INTE gitignored) —
  WP-zippen måste innehålla den. Den hålls i synk av `npm run schema:sync`.

### 1.2 Peka om läsarna + synk-scriptet
- **`tools/schema-sync.ts`** + `npm run schema:sync`: kopierar
  `packages/schema/entities/*.json` → `apps/wordpress/wexoe-core/schema/`.
  Körs vid varje schemaändring; båda committas ihop. (Kan även köras av
  SessionStart-hook + valideras av väktaren.)
- **PHP:** `wexoe-core/src/Schema.php::from_json()` läser oförändrat ur den
  committade `wexoe-core/schema/` — ingen kodändring behövs där.
- **Builder:** `apps/builder/lib/schema/to-state.ts` importerar via `@wexoe/schema`.
- Radera gamla `.github/workflows/sync-schema.yml` (cross-repo-speglingen onödig i monorepo).

### 1.3 Migrera de 25 kvarvarande entiteterna (mekaniskt, en i taget)
För varje `wexoe-core/entities/<t>.php` som är en handskriven array:
1. Skriv `packages/schema/entities/<t>.json` (samma fält, format per README).
2. Kör `npm run schema:sync` (kopierar till `wexoe-core/schema/`).
3. Ersätt PHP-filen med shim: `return \Wexoe\Core\Schema::from_json('<t>');`.
4. Kör väktaren (FAS 4) → grön betyder PHP-läsning oförändrad.
> Ordning: börja med de enklaste flata (`core_*`), sist multi-fält
> (`cms_page_sections`, `landing_pages`). `php -l` + guardian efter varje.

### 1.4 Acceptans
- [ ] `packages/schema/entities/` innehåller alla entiteter (originalet); `entities/*.php` är shims.
- [ ] `wexoe-core/schema/` är committad och bit-identisk med originalet (väktaren grön).
- [ ] Builder-read och PHP-read identiska före/efter (guardian + manuell spot-check).
- [ ] 0 `write-entities/`-filer kvar som inte används (verifiera mot kod; radera oanvända).

---

## FAS 2 — Enum-källa · **[BÅDA]** · kräver FAS 1

**Mål:** `section_type` (och andra menyer) definieras en gång, i kod.

- `packages/schema/enums/section-types.json` — lista över de 15 sektionstyperna
  (slug + label + ev. vilka fält-prefix som hör till typen).
- **PHP-dispatcher** (`plugins/wexoe-pages/wexoe-pages.php`): `wexoe_pages_section_renderers()`
  härleds ur enum-filen (typ → `sections/<slug>.php`).
- **Builder:** `cms-page-types.ts` `SectionType`-unionen + sektionslistan läser/valideras mot enum.
- Airtable speglar listan (manuell singleSelect) — väktaren varnar vid avvikelse.

- [ ] En ny sektionstyp = en rad i `section-types.json` + en renderer + en preview (väktaren listar exakt vad).

---

## FAS 3 — Standardisera state-typ · **[BUILDER]** · kräver FAS 0

**Mål:** ett namnmönster — `<Type>State`.

| Idag | → |
|---|---|
| `PartnerPageState` | `PartnerState` |
| `CustomerTypePageState` | `CustomerTypeState` |
| `CaseState`, `ProductAreaState`, `CmsPageState` | oförändrade (följer redan mönstret) |

- Rename via sök/ersätt över `apps/builder`. **Verifiera med `tsc --noEmit`** (kräver `node_modules`).
- Dokumentera mönstret i `CLAUDE.md` § konventioner + i `/add-page-type`-skillen.
- ⚠️ Risk: ren rename, men många referenser; måste tsc-verifieras (se Riskregister R3).

- [ ] `tsc --noEmit` grön; inga `*PageState` kvar utom där "Page" är domänen (t.ex. `CmsPageState`).

---

## FAS 4 — Väktaren + manifest · **[tools]** · kräver FAS 1–3 (bör byggas tidigt, växer med dem)

**Mål:** ett verktyg som (a) **genererar** systemkartan och (b) **validerar** att
sanningen håller. En LLM kör `npm run check` och vet om arbetet är komplett.

### 4.1 Manifest (genereras, #4 i diskussionen)
`tools/guardian` scannar repot och skriver `manifest.json` + läsbar `docs/DOCS-MAP.md`.
Per sidtyp och per sektion/block, alla touchpoints:
```jsonc
{ "pageTypes": [ { "id": "customer-type",
    "schema": "packages/schema/entities/cms_customer_type_pages.json",
    "stateType": "apps/builder/lib/customer-type-types.ts#CustomerTypeState",
    "server": "apps/builder/lib/page-types/customer-type.server.ts",
    "ui": "apps/builder/lib/page-types/customer-type.ui.tsx",
    "transform": "apps/builder/lib/deterministic-transform.ts#buildCustomerType",
    "phpRender": "apps/wordpress/plugins/wexoe-customer-type-page/...",
    "cacheEntities": ["cms_customer_type_pages", ...] } ],
  "sections": [ { "type": "hero",
    "phpRender": "apps/wordpress/plugins/wexoe-pages/sections/hero.php",
    "reactPreview": "apps/builder/components/cms-page/preview/...",
    "inspector": "apps/builder/components/cms-page/editors/...",
    "parity": "ok" } ] }
```

### 4.2 Checkar (slår brett — beslut #4)
- **R-paritet:** varje sidtyp/sektion i manifestet har *alla* förväntade
  touchpoints (server+ui+transform+phpRender+stateType / phpRender+preview+inspector).
- **R-enum:** varje `section-types.json`-slug har en renderer + preview; inga föräldralösa renderers.
- **R-schema↔typ:** fält i `packages/schema/entities/<t>.json` matchar
  state-typens fält (camelCase-härledning) — flaggar fält som finns i ena men inte andra.
- **R-strängar:** `registry.copy.apiType` matchar en handler i `app/api/copy/route.ts::COPY_HANDLERS`;
  `cacheEntities` matchar kända entiteter; `Core::renderer()`-nycklar finns. *(Lättviktig — beslut #8: hellre tidigt larm än tyst fel.)*

### 4.3 Acceptans
- [ ] `npm run check` failar rött när en touchpoint saknas, grönt annars.
- [ ] `manifest.json` + `docs/DOCS-MAP.md` regenereras av `npm run guardian`.
- [ ] CI kör `check` (FAS 5).

---

## FAS 5 — Minimal CI · **[.github]** · kräver FAS 0

`.github/workflows/ci.yml`, ett jobb per stege:
- **builder:** `npm ci` → `tsc --noEmit` → `eslint` → `vitest run`.
- **wordpress:** `composer install` → `php -l` (alla rörda) → `phpcs` (WP-standard) → `pest`.
- **guardian:** `npm run check`.

- [ ] PR mot monorepot kör alla tre; rött blockerar.

---

## FAS 6 — TDD-uppställning · **[BÅDA]** · kräver FAS 5

**Mål:** arbetssättet "kravspec → failande test → implementera tills grönt".

- **Builder:** Vitest. Första tester: `deterministic-transform` (state→fält per
  sidtyp), `to-state` (record→state), schema↔typ-helpers.
- **WordPress:** Pest via composer. Första tester: `Normalizer` (varje fälttyp),
  `Schema::from_json`, helpers (`Markdown`, `Color`, `Lines`).
- Etablera mönstret: ett test speglar ett krav; röd-grön-refaktor.

- [ ] `vitest` + `pest` kör lokalt och i CI; minst en meningsfull svit i varje.

---

## FAS 7 — Repo-egna LLM-verktyg (SY5) · **[.claude]** · kräver FAS 1–6

**Mål:** recept blir kommandon; varje session börjar i känt läge; TDD inbyggt.

- **`.claude/commands/add-section.md`** — `/add-section <slug>`: lägg i enum →
  scaffolda `sections/<slug>.php` + React-preview + inspector ur mallar → kör guardian.
- **`.claude/commands/add-page-type.md`** — `/add-page-type <id>`: schema-JSON +
  `<id>.server.ts` + `<id>.ui.tsx` + state-typ (`<Type>State`) + transform-byggare
  + cacheEntities + (ev.) PHP-plugin → guardian.
- **`.claude/commands/tdd.md`** — `/tdd <kravspec>`: skriv failande Vitest/Pest-test
  ur kravet → implementera tills grönt → guardian → sammanfatta.
- **`.claude/settings.json` SessionStart-hook** — installera deps
  (`npm ci` i roten + `composer install` i apps/wordpress) + `schema:materialize`
  + snabb sanity (`tsc --noEmit`, `php -l` på ändrade) så Claude alltid kan köra & testa.

- [ ] `/add-section`, `/add-page-type`, `/tdd` fungerar end-to-end och slutar med grön guardian.
- [ ] Ny session kan köra `npm run check` och `pest` utan manuell setup.

---

## FAS 8 — CLAUDE.md + docs-konsolidering · **[docs]** · kräver FAS 0–7

- En `CLAUDE.md` i roten = router; pekar på `docs/DOCS-MAP.md` (genererad).
- Flytta `ARKITEKTURPLAN.md` till `docs/` (en kopia). De gamla repo-dubbletterna
  försvinner med monorepot.
- Slå ihop `NEW_PAGE_TYPE.md` (builder+plugins) → ett dokument med två avsnitt.
- Splittra `IMPLEMENTATION_LOG.md` (D4): auto-changelog + `docs/decisions/` (ADR)
  + behåll **bara** Airtable-datamutationer som manuell logg.
- Lägg färskhetsstämpel överst i varje doc: `> Senast verifierad mot kod: <commit>`.

- [ ] En router-CLAUDE.md; inga byte-identiska dok-dubbletter; DOCS-MAP genererad.

---

## Riskregister (flaggas live när de inträffar)

| # | Risk | Hantering |
|---|---|---|
| R1 | **Repo-skapande kan ej göras av Claude** (verktyg låsta, ephemeral container). | FAS 0 = människa. Resten kräver scope mot nya repot eller handoff. |
| R2 | **WP-deploy-isolering:** `packages/schema` finns ej på WP-servern; användaren zippar via online-GitHub-zipper (inget script vid zip). | `wexoe-core/schema/` är **committad** spegelkopia, synkad vid push (`npm run schema:sync`), väktarskyddad. Zippen får den gratis. Inget triggas vid zip. |
| R3 | **Ingen `node_modules`/composer i Claude-containern** → kan ej köra tsc/vitest/pest/php här. | Verifiering sker i CI (FAS 5) + SessionStart-hook. State-typ-rename (FAS 3) är blind tills CI — gör den efter CI finns. |
| R4 | **Vercel monorepo-bygg** hittar ej workspace-paketet. | Root Directory `apps/builder` + install i roten; verifiera @wexoe/schema-länk i första deployen. |
| R5 | **Subtree-historik** stökig om gamla branches behövs. | Subtree bevarar `main`/default-historiken; öppna feature-branches i gamla repon arkiveras, inte migreras. |
| R6 | **Schema-migrering (25 st)** introducerar läs-drift. | En i taget, guardian + `php -l` + spot-check efter varje; aldrig big-bang. |

---

## Exekveringsordning (kritisk väg)

```
FAS 0 (människa) ─► FAS 1 ─► FAS 2
                     │         │
                     ├─► FAS 3 (builder, efter FAS 5/CI)
                     ▼
                   FAS 4 (väktare) ◄── byggs tidigt, växer med 1–3
                     │
                   FAS 5 (CI) ─► FAS 6 (TDD) ─► FAS 7 (skills) ─► FAS 8 (docs)
```

Praktiskt: **FAS 0 → 1 → 4(skelett) → 5 → 2/3 → 6 → 7 → 8.** Väktaren och CI
tidigt så allt därefter verifieras automatiskt.

---

## Progress

| Fas | Status | Notis |
|---|---|---|
| 0 — Monorepo-fundament | [ ] | Blockerad på repo-skapande (människa). |
| 1 — Delad schema + migrera alla | [ ] | |
| 2 — Enum-källa | [ ] | |
| 3 — State-typ-standard | [ ] | Efter CI (R3). |
| 4 — Väktare + manifest | [ ] | Bygg skelett tidigt. |
| 5 — Minimal CI | [ ] | |
| 6 — TDD-uppställning | [ ] | |
| 7 — LLM-verktyg (.claude) | [ ] | |
| 8 — CLAUDE.md + docs | [ ] | |
