#!/usr/bin/env node
/**
 * VERIFY — en enda sanning för "är arbetet klart?".
 *
 * Kör SAMMA grindar som CI (.github/workflows/ci.yml), i ordning, och låter
 * INTE körningen avbryta vid första felet — alla steg körs så att en människa
 * (eller en agent) ser hela bilden i en körning istället för att fixa-och-
 * köra-om sju gånger. Grön här = grön i CI. Det var inte sant förut: `npm run
 * check` körde bara väktaren, så tsc/lint/vitest/pest kunde vara röda lokalt
 * ändå.
 *
 * Steg (hoppar elegant över det som inte är installerat i miljön):
 *   1. schema:sync --check     scheman ↔ committade kopior
 *   2. guardian --check        paritet/enum/strängar (sanningsvakten)
 *   3. builder: tsc --noEmit   typecheck
 *   4. builder: lint           eslint
 *   5. builder: vitest         enhetstester (transform, mappers, schema)
 *   6. wordpress: php -l        syntax på alla .php (om php finns)
 *   7. wordpress: pest          beteende-tester (om vendor/bin/pest finns)
 *
 * Airtable-auditen (tools/airtable-audit.mjs) ingår MEDVETET inte här — den
 * kräver en hemlig nyckel + nätverk och körs bara i CI (se docs/AIRTABLE-AUDIT.md).
 * verify ska kunna köras offline, var som helst, utan secrets.
 *
 * Körning:
 *   node tools/verify.mjs            människoläsbar checklista + sammanfattning
 *   node tools/verify.mjs --json     maskinläsbar { ok, steps[] } för en agent
 *   node tools/verify.mjs --quick    hoppa över de tyngsta stegen (tsc/lint) —
 *                                    snabb feedback-loop under utveckling
 *
 * Exit 1 om något obligatoriskt steg failar. Inga beroenden (Node stdlib).
 */
import { spawnSync } from 'node:child_process';
import { existsSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const jsonOut = process.argv.includes('--json');
const quick = process.argv.includes('--quick');

const BUILDER = join(ROOT, 'apps/builder');
const WORDPRESS = join(ROOT, 'apps/wordpress');

const has = (p) => existsSync(join(ROOT, p));
const hasCmd = (cmd) => spawnSync(cmd, ['--version'], { stdio: 'ignore' }).status === 0;

/**
 * Ett steg. `when` (valfri) avgör om steget är relevant i denna miljö; faller
 * det bort markeras steget "skipped" (inte "failed") med en orsak — så att en
 * LLM ser skillnaden mellan "inte kört" och "rött".
 */
const STEPS = [
  {
    id: 'schema-sync',
    label: 'Schema-synk (original ↔ kopior)',
    cmd: ['node', ['tools/schema-sync.mjs', '--check'], ROOT],
  },
  {
    id: 'guardian',
    label: 'Guardian (paritet/enum/strängar)',
    cmd: ['node', ['tools/guardian/guardian.mjs', '--check'], ROOT],
  },
  {
    id: 'builder-typecheck',
    label: 'Builder: tsc --noEmit',
    when: () => (has('apps/builder/node_modules') ? true : 'builder-deps ej installerade (kör npm install i apps/builder)'),
    skipInQuick: true,
    cmd: ['npx', ['tsc', '--noEmit'], BUILDER],
  },
  {
    id: 'builder-lint',
    label: 'Builder: eslint',
    when: () => (has('apps/builder/node_modules') ? true : 'builder-deps ej installerade'),
    skipInQuick: true,
    cmd: ['npm', ['run', 'lint'], BUILDER],
  },
  {
    id: 'builder-test',
    label: 'Builder: vitest',
    when: () => (has('apps/builder/node_modules') ? true : 'builder-deps ej installerade'),
    cmd: ['npm', ['test'], BUILDER],
  },
  {
    id: 'php-lint',
    label: 'WordPress: php -l (alla .php)',
    when: () => (hasCmd('php') ? true : 'php saknas i denna miljö'),
    // php -l kan inte ta flera filer; vi loopar i ett litet shell-snutt.
    cmd: [
      'sh',
      [
        '-c',
        `find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 -P4 php -l > /dev/null && echo "php -l: inga syntaxfel"`,
      ],
      WORDPRESS,
    ],
  },
  {
    id: 'pest',
    label: 'WordPress: pest',
    when: () => (has('apps/wordpress/vendor/bin/pest') ? true : 'pest ej installerat (kör composer install i apps/wordpress)'),
    cmd: ['./vendor/bin/pest', [], WORDPRESS],
  },
];

function runStep(step) {
  if (quick && step.skipInQuick) {
    return { ...meta(step), status: 'skipped', reason: '--quick', durationMs: 0 };
  }
  if (step.when) {
    const verdict = step.when();
    if (verdict !== true) {
      return { ...meta(step), status: 'skipped', reason: verdict, durationMs: 0 };
    }
  }
  const [cmd, args, cwd] = step.cmd;
  const started = Date.now();
  const res = spawnSync(cmd, args, { cwd, encoding: 'utf8' });
  const durationMs = Date.now() - started;
  const ok = res.status === 0;
  return {
    ...meta(step),
    status: ok ? 'passed' : 'failed',
    durationMs,
    // Behåll utdata bara vid fel (och bara svansen) — agenten vill se VARFÖR.
    output: ok ? undefined : tail((res.stdout || '') + (res.stderr || ''), 40),
  };
}

const meta = (s) => ({ id: s.id, label: s.label });
const tail = (s, n) => s.trim().split('\n').slice(-n).join('\n');

const results = [];
for (const step of STEPS) results.push(runStep(step));

const failed = results.filter((r) => r.status === 'failed');
const skipped = results.filter((r) => r.status === 'skipped');
const passed = results.filter((r) => r.status === 'passed');
const ok = failed.length === 0;

if (jsonOut) {
  process.stdout.write(
    JSON.stringify(
      {
        ok,
        summary: { passed: passed.length, failed: failed.length, skipped: skipped.length },
        steps: results,
      },
      null,
      2,
    ) + '\n',
  );
  process.exit(ok ? 0 : 1);
}

// Människoläsbar checklista.
const ICON = { passed: '✓', failed: '✗', skipped: '–' };
console.log('\n  VERIFY — kör alla grindar (samma som CI)\n');
for (const r of results) {
  const t = r.durationMs ? ` (${(r.durationMs / 1000).toFixed(1)}s)` : '';
  let line = `  ${ICON[r.status]} ${r.label}${t}`;
  if (r.status === 'skipped') line += `  — hoppad: ${r.reason}`;
  console.log(line);
  if (r.status === 'failed' && r.output) {
    for (const l of r.output.split('\n')) console.log(`        ${l}`);
  }
}

console.log('');
if (ok) {
  const skipNote = skipped.length ? `, ${skipped.length} hoppade` : '';
  console.log(`  ✓ Allt grönt — ${passed.length} steg passerade${skipNote}.`);
  if (skipped.length) {
    console.log('    (Hoppade steg körs i CI — verifiera där, eller installera deps lokalt.)');
  }
} else {
  console.log(`  ✗ ${failed.length} steg failade: ${failed.map((f) => f.id).join(', ')}`);
  console.log('    Rätta ovan och kör om. Grönt här = grönt i CI.');
}
process.exit(ok ? 0 : 1);
