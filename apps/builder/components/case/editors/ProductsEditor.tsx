'use client';

import { CaseState } from '@/lib/case-types';
import { Field } from '@/components/shared/fields';
import type { SectionEditorProps } from '@/lib/page-types/types';
import type { NormalizedLinkedRecord } from '@/lib/linked-records-cache';

const filterActive = (rec: NormalizedLinkedRecord) => rec.is_active !== false;

export default function ProductsEditor({ state, onChange }: SectionEditorProps<CaseState>) {
  const set = <K extends keyof CaseState>(key: K, value: CaseState[K]) =>
    onChange({ ...state, [key]: value });

  return (
    <>
      <Field.Text
        label="Sektionsrubrik"
        value={state.productsTitle}
        onChange={(v) => set('productsTitle', v)}
        placeholder="Produkter i lösningen"
      />
      <Field.Text
        label="Meta-text"
        value={state.productsMeta}
        onChange={(v) => set('productsMeta', v)}
        placeholder="T.ex. Levererat av Wexoe"
      />

      <Field.LinkedRecords
        label="Produkter"
        source="products"
        value={state.productIds}
        onChange={(v) => set('productIds', v)}
        description="Multi-select från cms_products. Brand och beskrivning hämtas från den länkade posten vid rendering."
        filter={filterActive}
        placeholder="Sök produkt…"
      />

      <Field.LinkedRecords
        label="Artiklar"
        source="articles"
        value={state.articleIds}
        onChange={(v) => set('articleIds', v)}
        description="Multi-select från cms_articles. Renderas EFTER produkterna i samma box."
        filter={filterActive}
        placeholder="Sök artikel…"
      />
    </>
  );
}
