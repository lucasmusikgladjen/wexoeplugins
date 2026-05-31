'use client';

import PageTypeBuilder from '@/components/shared/builder/PageTypeBuilder';
import { customerTypeUI } from '@/lib/page-types/customer-type.ui';
import { CustomerTypePageState } from '@/lib/customer-type-types';

interface CustomerTypeBuilderProps {
  initialState: CustomerTypePageState;
  mode: 'create' | 'edit';
  recordId?: string;
}

export default function CustomerTypeBuilder({ initialState, mode, recordId }: CustomerTypeBuilderProps) {
  return (
    <PageTypeBuilder
      uiDef={customerTypeUI}
      initialState={initialState}
      mode={mode}
      recordId={recordId}
    />
  );
}
