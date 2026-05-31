import { describe, it, expect } from 'vitest';
// Audit-logiken bor som ett rotverktyg (tools/airtable-audit.mjs). Vi importerar
// de rena funktionerna direkt — modulen kör inte main() vid import.
import { auditEntity, auditSectionTypeEnum } from '../../../../tools/airtable-audit.mjs';

/**
 * Skyddsnät för tools/airtable-audit.mjs. Två regressionsfall kommer direkt från
 * Codex-granskningen av PR #69:
 *   1. `source`-alias måste hedras (annars falskt "saknas" + falsk föräldralös).
 *   2. En schematyp utan TYPE_COMPAT-post ska FELA, inte tyst hoppas över.
 */

type Finding = { level: 'error' | 'warning'; rule: string; message: string };
type AirtableField = { name: string; type: string; options?: unknown };

const errs = (findings: Finding[]) => findings.filter((f) => f.level === 'error');
const warns = (findings: Finding[]) => findings.filter((f) => f.level === 'warning');
const rules = (findings: Finding[]) => findings.map((f) => f.rule);

// En minimal Airtable-tabell (Metadata-API-form: { id, name, fields:[{name,type,options}] }).
const table = (fields: AirtableField[]) => ({ id: 'tblX', name: 'demo', fields });

describe('auditEntity — fält och typer', () => {
  it('grönt när schema-fält matchar Airtable-fält med kompatibel typ', () => {
    const schema = { table: 'demo', table_id: 'tblX', fields: { slug: { type: 'text' } } };
    const f = auditEntity(schema, table([{ name: 'slug', type: 'singleLineText' }]));
    expect(errs(f)).toHaveLength(0);
    expect(warns(f)).toHaveLength(0);
  });

  it('hedrar `source`-alias: aliasad kolumn flaggas varken som saknad eller föräldralös', () => {
    // JSON-nyckeln är 'hero_title' men riktiga Airtable-kolumnen heter 'Title'.
    const schema = {
      table: 'demo', table_id: 'tblX',
      fields: { hero_title: { type: 'text', source: 'Title' } },
    };
    const f = auditEntity(schema, table([{ name: 'Title', type: 'singleLineText' }]));
    expect(errs(f)).toHaveLength(0);   // inte "saknas i Airtable"
    expect(warns(f)).toHaveLength(0);  // 'Title' inte föräldralös
  });

  it('rapporterar saknat fält när varken nyckel eller source finns i Airtable', () => {
    const schema = {
      table: 'demo', table_id: 'tblX',
      fields: { hero_title: { type: 'text', source: 'Title' } },
    };
    const f = auditEntity(schema, table([{ name: 'NågotAnnat', type: 'singleLineText' }]));
    expect(rules(errs(f))).toContain('A-fields');
    // Felet ska nämna source-namnet så det går att felsöka.
    expect(errs(f)[0].message).toContain("source 'Title'");
  });

  it('FELAR på okänd schematyp i stället för att tyst hoppa över (Codex #2)', () => {
    const schema = { table: 'demo', table_id: 'tblX', fields: { pic: { type: 'attachment' } } };
    // attachment finns nu i TYPE_COMPAT → ska vara OK mot multipleAttachments...
    const ok = auditEntity(schema, table([{ name: 'pic', type: 'multipleAttachments' }]));
    expect(errs(ok)).toHaveLength(0);

    // ...men en helt påhittad typ ska ge ett hårt A-types-fel, inte tystnad.
    const bad = auditEntity(
      { table: 'demo', table_id: 'tblX', fields: { x: { type: 'kvantum' } } },
      table([{ name: 'x', type: 'singleLineText' }]),
    );
    expect(rules(errs(bad))).toContain('A-types');
    expect(errs(bad)[0].message).toContain('kvantum');
  });

  it('rapporterar typkrock (text-schema mot number-kolumn)', () => {
    const schema = { table: 'demo', table_id: 'tblX', fields: { count: { type: 'text' } } };
    const f = auditEntity(schema, table([{ name: 'count', type: 'number' }]));
    expect(rules(errs(f))).toContain('A-types');
  });

  it('varnar (inte felar) för Airtable-fält som schemat inte känner', () => {
    const schema = { table: 'demo', table_id: 'tblX', fields: { slug: { type: 'text' } } };
    const f = auditEntity(schema, table([
      { name: 'slug', type: 'singleLineText' },
      { name: 'legacy_kol', type: 'singleLineText' },
    ]));
    expect(errs(f)).toHaveLength(0);
    expect(rules(warns(f))).toContain('A-fields');
  });

  it('täcker alla dokumenterade text-lika typer (richtext/image/url/lines)', () => {
    for (const type of ['richtext', 'image', 'url', 'lines']) {
      const schema = { table: 'demo', table_id: 'tblX', fields: { fld: { type } } };
      const f = auditEntity(schema, table([{ name: 'fld', type: 'multilineText' }]));
      expect(errs(f), `typ '${type}' borde vara kompatibel med multilineText`).toHaveLength(0);
    }
  });
});

describe('auditSectionTypeEnum — section_type-singleSelect', () => {
  const sectionsTable = (choiceNames: string[]) => ({
    id: 'tblSec', name: 'cms_page_sections',
    fields: [{ name: 'section_type', type: 'singleSelect', options: { choices: choiceNames.map((name) => ({ name })) } }],
  });

  it('grönt när enum och Airtable-val matchar', () => {
    const f = auditSectionTypeEnum([sectionsTable(['hero', 'faq'])], ['hero', 'faq']);
    expect(errs(f)).toHaveLength(0);
  });

  it('felar när enum har ett val Airtable saknar', () => {
    const f = auditSectionTypeEnum([sectionsTable(['hero'])], ['hero', 'faq']);
    expect(rules(errs(f))).toContain('A-enum');
    expect(errs(f)[0].message).toContain('faq');
  });

  it('varnar när Airtable har ett extra val', () => {
    const f = auditSectionTypeEnum([sectionsTable(['hero', 'faq', 'extra'])], ['hero', 'faq']);
    expect(errs(f)).toHaveLength(0);
    expect(rules(warns(f))).toContain('A-enum');
  });
});
