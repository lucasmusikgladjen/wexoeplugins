'use client';

import PageTypeBuilder from '@/components/shared/builder/PageTypeBuilder';
import { productAreaUI } from '@/lib/page-types/product-area.ui';
import { ProductAreaState } from '@/lib/product-area-types';

interface ProductAreaBuilderProps {
  initialState: ProductAreaState;
  mode: 'create' | 'edit';
  recordId?: string;
}

export default function ProductAreaBuilder({ initialState, mode, recordId }: ProductAreaBuilderProps) {
  return (
    <PageTypeBuilder
      uiDef={productAreaUI}
      initialState={initialState}
      mode={mode}
      recordId={recordId}
      apiPath="/api/product-area"
      editPath="/editor/product-area/:recordId"
    />
  );
}
