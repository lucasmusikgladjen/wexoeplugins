#!/usr/bin/env node
/**
 * AIRTABLE-AUDIT — jämför sanningskällan (packages/schema) mot den FAKTISKA
 * Airtable-basen. Den check väktaren inte kan göra: guardian validerar att
 * koden är internt konsistent, men inte att schemat matchar verkligheten i
 * Airtable. Det är den läskigaste driften — schemat säger att ett fält finns,
 * Airtable saknar det, och spar failar tyst i produktion.
 *
 * Vad den kollar (läs-bara, muterar ALDRIG Airtable):
 *   A-table    varje schema-entitets table_id finns i basen
 *   A-fields   varje schema-fält finns som ett Airtable-fält (och vice versa →
 *              varning för fält i Airtable som schemat inte känner till)
 *   A-types    Airtable-fälttypen är kompatibel med schema-typen
 *              (text/bool/int/link)
 *   A-enum     section_type-singleSelect i cms_page_sections matchar
 *              packages/schema/enums/section-types.json exakt
 *
 * KÖRS I CI, inte för hand. GitHub kör den åt dig (schemalagt + vid
 * schemaändringar) — se .github/workflows/airtable-audit.yml och den pedagogiska
 * guiden docs/AIRTABLE-AUDIT.md. Den ingår MEDVETET inte i `npm run verify`,
 * eftersom verify ska kunna köras offline utan hemligheter.
 *
 * Kräver en Airtable PAT med schema-läs-scope (data.records:read +
 * schema.bases:read), satt som GitHub-secret:
 *   AIRTABLE_API_KEY=pat...   (samma sorts nyckel som buildern använder)
 * Använder Airtables Metadata-API: GET /v0/meta/bases/{baseId}/tables
 *
 * UTAN nyckel: skippar elegant (exit 0, status "skipped"). Så workflowen blir
 * inte röd för någon som saknar secret:en — den aktiveras tyst när secret:en
 * finns. Inga npm-beroenden (Node stdlib + global fetch i Node 18+).
 *
 * (Går att köra för hand med AIRTABLE_API_KEY=... node tools/airtable-audit.mjs,
 *  men det är inte tänkt arbetssätt — CI äger den här grinden.)
 */
import { readFileSync, readdirSync, existsSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const jsonOut = process.argv.includes('--json');

const SCHEMA_DIR = join(ROOT, 'packages/schema/entities');
const ENUM_FILE = join(ROOT, 'packages/schema/enums/section-types.json');

// Base-alias → Airtable base-id. Speglar apps/builder/lib/airtable.ts.
// (Override via env om en entitet pekar på en annan bas.)
const BASE_IDS = {
  ssot: process.env.AIRTABLE_BASE_ID || 'appokKSTaBdCa8YiW',
  legacy: 'appXoUcK68dQwASjF',
};

// Schema-typ → mängd kompatibla Airtable-fälttyper (Metadata-API:ts namn).
const TYPE_COMPAT = {
  text: new Set([
    'singleLineText', 'multilineText', 'richText', 'url', 'email',
    'phoneNumber', 'singleSelect', 'multipleSelects', 'date', 'dateTime',
    'barcode', 'formula', 'rollup', 'lookup', 'multipleLookupValues',
  ]),
  int: new Set(['number', 'autoNumber', 'rating', 'count', 'currency', 'percent', 'duration', 'formula', 'rollup']),
  bool: new Set(['checkbox', 'formula']),
  link: new Set(['multipleRecordLinks']),
};

const findings = []; // { level: 'error'|'warning', rule, message }
const err = (rule, message) => findings.push({ level: 'error', rule, message });
const warn = (rule, message) => findings.push({ level: 'warning', rule, message });

function loadSchemas() {
  if (!existsSync(SCHEMA_DIR)) return [];
  return readdirSync(SCHEMA_DIR)
    .filter((f) => f.endsWith('.json'))
    .map((f) => JSON.parse(readFileSync(join(SCHEMA_DIR, f), 'utf8')));
}

async function fetchBaseTables(baseId, apiKey) {
  const res = await fetch(`https://api.airtable.com/v0/meta/bases/${baseId}/tables`, {
    headers: { Authorization: `Bearer ${apiKey}` },
  });
  if (!res.ok) {
    const body = await res.text().catch(() => '');
    throw new Error(`Metadata-API ${res.status} för bas ${baseId}: ${body.slice(0, 200)}`);
  }
  const data = await res.json();
  return data.tables; // [{ id, name, fields: [{ id, name, type, options }], ... }]
}

function auditEntity(schema, table) {
  const tableLabel = `${schema.table} (${schema.table_id})`;

  // A-fields + A-types — varje schema-fält ska finnas med kompatibel typ.
  const airtableFields = new Map(table.fields.map((f) => [f.name, f]));
  for (const [fieldName, def] of Object.entries(schema.fields || {})) {
    const af = airtableFields.get(fieldName);
    if (!af) {
      err('A-fields', `${tableLabel}: fält '${fieldName}' finns i schemat men saknas i Airtable`);
      continue;
    }
    const compat = TYPE_COMPAT[def.type];
    if (compat && !compat.has(af.type)) {
      err(
        'A-types',
        `${tableLabel}: fält '${fieldName}' är '${af.type}' i Airtable men schemat säger '${def.type}'`,
      );
    }
  }

  // Föräldralösa Airtable-fält (finns i Airtable men inte i schemat) — varning,
  // inte fel: kan vara legacy-kolumner eller fält bara WP/automation läser.
  const schemaFieldNames = new Set(Object.keys(schema.fields || {}));
  for (const af of table.fields) {
    if (!schemaFieldNames.has(af.name)) {
      warn('A-fields', `${tableLabel}: Airtable-fält '${af.name}' (${af.type}) finns inte i schemat (legacy? eller saknas i packages/schema)`);
    }
  }
}

function auditSectionTypeEnum(tables) {
  if (!existsSync(ENUM_FILE)) return;
  const enumData = JSON.parse(readFileSync(ENUM_FILE, 'utf8'));
  const enumSlugs = (enumData.types || []).map((t) => t.type).sort();

  // Hitta cms_page_sections-tabellen och dess section_type-singleSelect.
  const table = tables.find((t) => t.name === 'cms_page_sections');
  if (!table) {
    warn('A-enum', "cms_page_sections-tabellen hittades inte i basen — hoppar enum-jämförelse (är section_type-fältet i en annan bas?)");
    return;
  }
  const field = table.fields.find((f) => f.name === 'section_type');
  if (!field) {
    warn('A-enum', "cms_page_sections saknar fältet 'section_type' i Airtable");
    return;
  }
  if (field.type !== 'singleSelect') {
    warn('A-enum', `cms_page_sections.section_type är '${field.type}' i Airtable, väntade 'singleSelect'`);
    return;
  }
  const airtableChoices = (field.options?.choices || []).map((c) => c.name).sort();
  const missingInAirtable = enumSlugs.filter((s) => !airtableChoices.includes(s));
  const extraInAirtable = airtableChoices.filter((s) => !enumSlugs.includes(s));
  for (const s of missingInAirtable) {
    err('A-enum', `section_type '${s}' finns i enum:en men inte som val i Airtable-singleSelect`);
  }
  for (const s of extraInAirtable) {
    warn('A-enum', `section_type '${s}' är ett val i Airtable men saknas i packages/schema/enums/section-types.json`);
  }
}

function report(status, extra = {}) {
  if (jsonOut) {
    const ok = status !== 'failed';
    process.stdout.write(JSON.stringify({ ok, status, findings, ...extra }, null, 2) + '\n');
    process.exit(ok ? 0 : 1);
  }
  if (status === 'skipped') {
    console.log(`  – Airtable-audit hoppad: ${extra.reason}`);
    process.exit(0);
  }
  const errors = findings.filter((f) => f.level === 'error');
  const warnings = findings.filter((f) => f.level === 'warning');
  for (const w of warnings) console.log(`  ⚠ ${w.rule}: ${w.message}`);
  if (errors.length) {
    console.error(`\n✗ Airtable-audit: ${errors.length} avvikelse(r) mot basen:`);
    for (const e of errors) console.error(`  ✗ ${e.rule}: ${e.message}`);
    process.exit(1);
  }
  console.log(`\n✓ Airtable-audit OK — schema matchar basen (${extra.entities} entiteter, ${warnings.length} varningar).`);
  process.exit(0);
}

// ── Main ────────────────────────────────────────────────────────────────
const apiKey = process.env.AIRTABLE_API_KEY;
if (!apiKey) {
  report('skipped', { reason: 'AIRTABLE_API_KEY saknas — sätt den som GitHub-secret för att aktivera (se docs/AIRTABLE-AUDIT.md)' });
}
if (typeof fetch !== 'function') {
  report('skipped', { reason: 'global fetch saknas (kräver Node 18+)' });
}

const schemas = loadSchemas();
if (schemas.length === 0) report('failed', {});

try {
  // Hämta varje unik bas en gång.
  const neededBases = [...new Set(schemas.map((s) => BASE_IDS[s.base] || BASE_IDS.ssot))];
  const tablesByBase = {};
  for (const baseId of neededBases) {
    tablesByBase[baseId] = await fetchBaseTables(baseId, apiKey);
  }

  for (const schema of schemas) {
    const baseId = BASE_IDS[schema.base] || BASE_IDS.ssot;
    const tables = tablesByBase[baseId];
    const table = tables.find((t) => t.id === schema.table_id);
    if (!table) {
      err('A-table', `${schema.table}: table_id '${schema.table_id}' finns inte i bas ${baseId}`);
      continue;
    }
    auditEntity(schema, table);
  }

  // Enum-jämförelsen lever i ssot-basen (där cms_page_sections bor).
  auditSectionTypeEnum(tablesByBase[BASE_IDS.ssot] || []);

  const hasErrors = findings.some((f) => f.level === 'error');
  report(hasErrors ? 'failed' : 'passed', { entities: schemas.length });
} catch (e) {
  // Nätverks-/auth-fel är inte en schema-avvikelse → rapportera men exit 1 så
  // en trasig nyckel inte tyst maskeras.
  if (jsonOut) {
    process.stdout.write(JSON.stringify({ ok: false, status: 'error', error: String(e.message || e), findings }, null, 2) + '\n');
  } else {
    console.error(`\n✗ Airtable-audit kunde inte köras: ${e.message || e}`);
  }
  process.exit(1);
}
