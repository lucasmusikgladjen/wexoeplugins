'use client';

import PageTypeBuilder from '@/components/shared/builder/PageTypeBuilder';
import { caseUI } from '@/lib/page-types/case.ui';
import { CaseState } from '@/lib/case-types';

interface CaseBuilderProps {
  initialState: CaseState;
  mode: 'create' | 'edit';
  recordId?: string;
}

export default function CaseBuilder({ initialState, mode, recordId }: CaseBuilderProps) {
  return (
    <PageTypeBuilder
      uiDef={caseUI}
      initialState={initialState}
      mode={mode}
      recordId={recordId}
    />
  );
}
