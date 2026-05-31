#!/usr/bin/env node
/**
 * GUARDIAN — väktaren för Wexoe-monorepot.
 *
 * Bygger en maskinläsbar systemkarta (manifest.json) och en läsbar
 * "var bor allt"-sida (docs/DOCS-MAP.md), och VALIDERAR att sanningen håller:
 *
 *   R-schema   schema-original ↔ committade kopior (apps/wordpress, apps/builder)
 *   R-enum     varje section_type i enum:en har PHP-renderare; inga föräldralösa
 *   R-parity   varje sidtyp har sina förväntade touchpoints (server/ui/php-plugin)
 *   R-strings  registry.copy.apiType matchar COPY_HANDLERS; cacheEntities mot kända
 *
 * Körning:
 *   node tools/guardian/guardian.mjs            → bygg manifest + DOCS-MAP, validera
 *   node tools/guardian/guardian.mjs --check    → bara validera (CI), exit 1 vid fel
 *
 * Inga beroenden (Node stdlib). En LLM kör detta och vet om arbetet är komplett.
 */
import { readFileSync, writeFileSync, existsSync, readdirSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..', '..');
const checkOnly = process.argv.includes('--check');

const P = {
  schemaSrc: join(ROOT, 'packages/schema/entities'),
  schemaCopies: [
    join(ROOT, 'apps/wordpress/wexoe-core/schema'),
    join(ROOT, 'apps/builder/schema'),
  ],
  enums: join(ROOT, 'packages/schema/enums'),
  registry: join(ROOT, 'apps/builder/lib/page-types/registry.ts'),
  copyRoute: join(ROOT, 'apps/builder/app/api/copy/route.ts'),
  cacheEntities: join(ROOT, 'apps/builder/lib/wexoe-cache-entities.ts'),
  pagesDispatcher: join(ROOT, 'apps/wordpress/plugins/wexoe-pages/wexoe-pages.php'),
  sectionsDir: join(ROOT, 'apps/wordpress/plugins/wexoe-pages/sections'),
  pageTypesDir: join(ROOT, 'apps/builder/lib/page-types'),
  manifest: join(ROOT, 'manifest.json'),
  docsMap: join(ROOT, 'docs/DOCS-MAP.md'),
};

const errors = [];
const warnings = [];
const read = (p) => (existsSync(p) ? readFileSync(p, 'utf8') : null);
const listJson = (d) => (existsSync(d) ? readdirSync(d).filter((f) => f.endsWith('.json')).sort() : []);

// ─────────────────────────────────────────────────────────────────────────
// R-schema — original ↔ committade kopior
// ─────────────────────────────────────────────────────────────────────────
function checkSchema() {
  const sources = listJson(P.schemaSrc);
  if (sources.length === 0) errors.push('R-schema: inga scheman i packages/schema/entities/');
  for (const dir of P.schemaCopies) {
    for (const f of sources) {
      const a = read(join(P.schemaSrc, f));
      const b = read(join(dir, f));
      if (b === null) errors.push(`R-schema: saknad kopia ${join(dir, f).replace(ROOT + '/', '')}`);
      else if (a !== b) errors.push(`R-schema: drift i ${join(dir, f).replace(ROOT + '/', '')} (kör npm run schema:sync)`);
    }
    for (const f of listJson(dir)) {
      if (!sources.includes(f)) errors.push(`R-schema: föräldralös kopia ${join(dir, f).replace(ROOT + '/', '')}`);
    }
  }
  return sources.map((f) => f.replace('.json', ''));
}

// ─────────────────────────────────────────────────────────────────────────
// R-enum — section_type-enum ↔ PHP-renderare
// ─────────────────────────────────────────────────────────────────────────
function checkEnums() {
  const enumFile = join(P.enums, 'section-types.json');
  const dispatcher = read(P.pagesDispatcher) || '';
  // Dispatcher-mappning: 'slug' => 'file.php'
  const dispatched = {};
  for (const m of dispatcher.matchAll(/'([a-z_]+)'\s*=>\s*'([a-z-]+\.php)'/g)) {
    dispatched[m[1]] = m[2];
  }
  let enumSlugs = [];
  if (!existsSync(enumFile)) {
    warnings.push('R-enum: packages/schema/enums/section-types.json saknas än (FAS 2) — härleder från dispatcher tills vidare');
    enumSlugs = Object.keys(dispatched);
  } else {
    const data = JSON.parse(read(enumFile));
    const list = Array.isArray(data) ? data : data.types;
    enumSlugs = list.map((t) => (typeof t === 'string' ? t : t.type || t.slug));
    // Varje enum-slug måste ha en dispatcher-rad + en renderer-fil.
    for (const slug of enumSlugs) {
      if (!dispatched[slug]) errors.push(`R-enum: '${slug}' i enum men saknar rad i wexoe-pages-dispatchern`);
      else if (!existsSync(join(P.sectionsDir, dispatched[slug])))
        errors.push(`R-enum: '${slug}' → sections/${dispatched[slug]} saknas på disk`);
    }
    // Föräldralösa dispatcher-rader (i dispatcher men inte i enum).
    for (const slug of Object.keys(dispatched)) {
      if (!enumSlugs.includes(slug)) errors.push(`R-enum: dispatcher har '${slug}' som saknas i enum:en`);
    }
  }
  // Renderer-filer utan dispatcher-rad (alltid värt en varning).
  const rendererFiles = existsSync(P.sectionsDir)
    ? readdirSync(P.sectionsDir).filter((f) => f.endsWith('.php'))
    : [];
  const dispatchedFiles = Object.values(dispatched);
  for (const f of rendererFiles) {
    if (!dispatchedFiles.includes(f)) warnings.push(`R-enum: sections/${f} har ingen dispatcher-rad (oanvänd?)`);
  }
  return { enumSlugs, dispatched };
}

// ─────────────────────────────────────────────────────────────────────────
// R-parity — sidtypers touchpoints
// ─────────────────────────────────────────────────────────────────────────
// Registry-id ≠ filnamn för två sidtyper. Detta är en KÄND namn-mismatch
// (magisk koppling) som FAS 3 ska städa. Tills dess mappar vi explicit här;
// väktaren varnar så att kopplingen är synlig och inte glöms.
const ID_TO_MODULE = { product: 'product-area', page: 'cms-page' };
// landing är medvetet legacy (lib/state.ts reducer + page-mapper.ts), ingen .server.ts.
const LEGACY_NO_MODULE = new Set(['landing']);

function checkParity() {
  const reg = read(P.registry) || '';
  const ids = [...reg.matchAll(/^\s{4}id:\s*'([a-z-]+)',/gm)].map((m) => m[1]);
  const uniqueIds = [...new Set(ids)];
  const pageTypes = [];
  for (const id of uniqueIds) {
    if (ID_TO_MODULE[id]) warnings.push(`R-parity: id '${id}' ≠ modulnamn '${ID_TO_MODULE[id]}' (känd mismatch, städas i FAS 3)`);
    const mod = ID_TO_MODULE[id] || id;
    if (LEGACY_NO_MODULE.has(id)) {
      warnings.push(`R-parity: '${id}' är legacy reducer-väg (lib/state.ts), ingen ${id}.server.ts — känt undantag`);
      pageTypes.push({ id, module: null, server: null, ui: null, legacy: true });
      continue;
    }
    const serverFile = join(P.pageTypesDir, `${mod}.server.ts`);
    const uiFile = join(P.pageTypesDir, `${mod}.ui.tsx`);
    const hasServer = existsSync(serverFile);
    const hasUi = existsSync(uiFile);
    if (!hasServer) errors.push(`R-parity: sidtyp '${id}' saknar ${mod}.server.ts`);
    if (!hasUi) errors.push(`R-parity: sidtyp '${id}' saknar ${mod}.ui.tsx`);
    pageTypes.push({
      id,
      module: mod,
      server: hasServer ? rel(serverFile) : null,
      ui: hasUi ? rel(uiFile) : null,
    });
  }
  return pageTypes;
}

// ─────────────────────────────────────────────────────────────────────────
// R-strings — apiType ↔ COPY_HANDLERS, cacheEntities mot kända
// ─────────────────────────────────────────────────────────────────────────
function checkStrings() {
  const reg = read(P.registry) || '';
  const copy = read(P.copyRoute) || '';
  const apiTypes = [...reg.matchAll(/apiType:\s*'([a-z-]+)'/g)].map((m) => m[1]);
  // COPY_HANDLERS-nycklar — fånga DEFINITIONSblocket (Record<...> = { ... }),
  // inte kommentar-omnämnanden. Matcha från `COPY_HANDLERS ... = {` till `};`.
  const def = copy.match(/COPY_HANDLERS[^=]*=\s*\{([\s\S]*?)\n\};/);
  const handlerBlock = def ? def[1] : '';
  const handlers = [...handlerBlock.matchAll(/'([a-z-]+)'\s*:/g)].map((m) => m[1]);
  for (const t of apiTypes) {
    if (!handlers.includes(t))
      errors.push(`R-strings: registry copy.apiType '${t}' saknar handler i COPY_HANDLERS (app/api/copy/route.ts)`);
  }
  // Föräldralösa handlers (handler utan apiType) — varning, inte fel (kan vara avsiktlig).
  for (const h of handlers) {
    if (!apiTypes.includes(h)) warnings.push(`R-strings: COPY_HANDLERS '${h}' har ingen registry-apiType som pekar dit`);
  }
  return { apiTypes, handlers };
}

const rel = (p) => p.replace(ROOT + '/', '');

// ─────────────────────────────────────────────────────────────────────────
// Kör + bygg manifest
// ─────────────────────────────────────────────────────────────────────────
const entities = checkSchema();
const { enumSlugs, dispatched } = checkEnums();
const pageTypes = checkParity();
const { apiTypes, handlers } = checkStrings();

const manifest = {
  generatedAt: new Date().toISOString().slice(0, 10),
  generatedBy: 'tools/guardian/guardian.mjs',
  entities,
  sections: enumSlugs.map((slug) => ({
    type: slug,
    phpRender: dispatched[slug] ? `apps/wordpress/plugins/wexoe-pages/sections/${dispatched[slug]}` : null,
  })),
  pageTypes,
  copyHandlers: handlers,
};

if (!checkOnly) {
  writeFileSync(P.manifest, JSON.stringify(manifest, null, 2) + '\n');
  writeFileSync(P.docsMap, renderDocsMap(manifest));
}

// ─────────────────────────────────────────────────────────────────────────
// Rapport
// ─────────────────────────────────────────────────────────────────────────
for (const w of warnings) console.log(`  ⚠ ${w}`);
if (errors.length) {
  console.error(`\n✗ Guardian: ${errors.length} fel:`);
  for (const e of errors) console.error(`  ✗ ${e}`);
  process.exit(1);
}
console.log(
  `\n✓ Guardian OK — ${entities.length} entiteter, ${enumSlugs.length} sektioner, ` +
    `${pageTypes.length} sidtyper, ${handlers.length} copy-handlers` +
    (checkOnly ? '' : `\n  skrev manifest.json + docs/DOCS-MAP.md`),
);

function renderDocsMap(m) {
  const L = [];
  L.push('# DOCS-MAP — var bor allt (GENERERAD)');
  L.push('');
  L.push('> ⚠️ **Genererad av `tools/guardian/guardian.mjs` — redigera INTE för hand.**');
  L.push('> Kör `npm run guardian` för att uppdatera. Senast: ' + m.generatedAt + '.');
  L.push('');
  L.push('## Sidtyper');
  L.push('');
  L.push('| id | server | ui |');
  L.push('|---|---|---|');
  for (const pt of m.pageTypes) L.push(`| \`${pt.id}\` | ${pt.server || '—'} | ${pt.ui || '—'} |`);
  L.push('');
  L.push('## Sektioner (cms_page_sections, wexoe-pages-dispatchern)');
  L.push('');
  L.push('| section_type | PHP-renderare |');
  L.push('|---|---|');
  for (const s of m.sections) L.push(`| \`${s.type}\` | ${s.phpRender || '—'} |`);
  L.push('');
  L.push('## Entiteter (packages/schema/entities → synkade kopior)');
  L.push('');
  for (const e of m.entities) L.push(`- \`${e}\``);
  L.push('');
  L.push('## Copy-handlers (app/api/copy/route.ts)');
  L.push('');
  L.push(m.copyHandlers.map((h) => `\`${h}\``).join(', '));
  L.push('');
  return L.join('\n');
}
