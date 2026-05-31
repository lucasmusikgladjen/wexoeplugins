#!/usr/bin/env node
/**
 * schema-sync — kopierar schema-ORIGINALET (packages/schema/entities/*.json)
 * till de två deploy-kopiorna som måste vara committade:
 *
 *   - apps/wordpress/wexoe-core/schema/   (WP-pluginet läser härifrån i drift;
 *                                          följer med i en zip av wexoe-core)
 *   - apps/builder/schema/                (buildern importerar via @/schema/*)
 *
 * Originalet i packages/schema/entities/ är det ENDA som redigeras för hand.
 * Kopiorna regenereras härav. Väktaren (tools/guardian) failar om de driftar.
 *
 * Kör: `node tools/schema-sync.mjs`  (eller `npm run schema:sync`)
 *      `node tools/schema-sync.mjs --check`  → exit 1 om kopior är ur synk
 *
 * Ingen kodgenerering — ren filkopia. Inga beroenden (Node stdlib).
 */
import { readdirSync, readFileSync, writeFileSync, mkdirSync, existsSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const SRC = join(ROOT, 'packages/schema/entities');
const TARGETS = [
  join(ROOT, 'apps/wordpress/wexoe-core/schema'),
  join(ROOT, 'apps/builder/schema'),
];

const checkOnly = process.argv.includes('--check');

function listJson(dir) {
  if (!existsSync(dir)) return [];
  return readdirSync(dir).filter((f) => f.endsWith('.json')).sort();
}

const sources = listJson(SRC);
if (sources.length === 0) {
  console.error(`schema-sync: inga *.json i ${SRC}`);
  process.exit(1);
}

let drift = 0;
let copied = 0;

for (const target of TARGETS) {
  if (!checkOnly) mkdirSync(target, { recursive: true });

  // 1. Kopiera/verifiera varje källfil → target.
  for (const file of sources) {
    const srcContent = readFileSync(join(SRC, file), 'utf8');
    const dstPath = join(target, file);
    const dstContent = existsSync(dstPath) ? readFileSync(dstPath, 'utf8') : null;

    if (dstContent !== srcContent) {
      if (checkOnly) {
        console.error(`DRIFT: ${dstPath} skiljer sig från originalet`);
        drift++;
      } else {
        writeFileSync(dstPath, srcContent);
        copied++;
      }
    }
  }

  // 2. Flagga föräldralösa kopior (finns i target men inte i originalet).
  for (const file of listJson(target)) {
    if (!sources.includes(file)) {
      console.error(`FÖRÄLDRALÖS: ${join(target, file)} saknar original i packages/schema/entities/`);
      drift++;
    }
  }
}

if (checkOnly) {
  if (drift > 0) {
    console.error(`\nschema-sync --check: ${drift} avvikelse(r). Kör \`npm run schema:sync\` och committa.`);
    process.exit(1);
  }
  console.log(`schema-sync --check: ${sources.length} entitet(er), kopior i synk ✓`);
} else {
  console.log(`schema-sync: ${sources.length} entitet(er) → ${TARGETS.length} mål, ${copied} fil(er) uppdaterade ✓`);
}
