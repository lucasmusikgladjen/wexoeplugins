import PageTypeBuilder from '@/components/shared/builder/PageTypeBuilder';
import { partnerUI } from '@/lib/page-types/partner.ui';
import { emptyPartnerPageState } from '@/lib/partner-types';

export const dynamic = 'force-dynamic';

export default function CreatePartnerPage() {
  return (
    <PageTypeBuilder
      uiDef={partnerUI}
      initialState={emptyPartnerPageState()}
      mode="create"
    />
  );
}
