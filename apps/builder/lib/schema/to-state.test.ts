import { describe, it, expect } from 'vitest';
import { snakeToCamel, stateFromRecord } from './to-state';
import type { EntitySchema } from './entity-schema';

describe('snakeToCamel', () => {
  it('konverterar enkla snake_case-namn', () => {
    expect(snakeToCamel('stat_number')).toBe('statNumber');
    expect(snakeToCamel('value_text_1')).toBe('valueText1');
  });

  it('lämnar redan camelCase/enkelord orört', () => {
    expect(snakeToCamel('slug')).toBe('slug');
    expect(snakeToCamel('title')).toBe('title');
  });

  it('hanterar flera segment', () => {
    expect(snakeToCamel('hero_cta_button_url')).toBe('heroCtaButtonUrl');
  });
});

describe('stateFromRecord', () => {
  const schema: EntitySchema = {
    table: 'demo',
    table_id: 'tblDemo',
    fields: {
      slug: { type: 'text' },
      is_active: { type: 'bool' },
      stat_number: { type: 'int', builder_as: 'string' },
      php_only_field: { type: 'text', php_only: true },
      contact_block: { type: 'text', block: 'contact_form' },
    },
  } as unknown as EntitySchema;

  it('härleder state-nycklar via snake→camel och respekterar typer', () => {
    const out = stateFromRecord(
      { fields: { slug: 'foo', is_active: true, stat_number: 42 } },
      schema,
    );
    expect(out.slug).toBe('foo');
    expect(out.isActive).toBe(true);
    // builder_as:'string' → number serialiseras till sträng
    expect(out.statNumber).toBe('42');
  });

  it('hoppar över php_only- och block-fält (de ägs av andra lager)', () => {
    const out = stateFromRecord(
      { fields: { slug: 'foo', php_only_field: 'x', contact_block: 'y' } },
      schema,
    );
    expect('phpOnlyField' in out).toBe(false);
    expect('contactBlock' in out).toBe(false);
  });

  it('bool är aldrig null (saknat fält → false)', () => {
    const out = stateFromRecord({ fields: { slug: 'foo' } }, schema);
    expect(out.isActive).toBe(false);
  });
});
