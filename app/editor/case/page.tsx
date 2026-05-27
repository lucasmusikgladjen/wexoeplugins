import PageTypeBuilder from '@/components/shared/builder/PageTypeBuilder';
import { caseUI } from '@/lib/page-types/case.ui';
import { emptyCaseState } from '@/lib/case-types';

export const dynamic = 'force-dynamic';

export default function CreateCasePage() {
  return (
    <PageTypeBuilder
      uiDef={caseUI}
      initialState={emptyCaseState()}
      mode="create"
    />
  );
}
