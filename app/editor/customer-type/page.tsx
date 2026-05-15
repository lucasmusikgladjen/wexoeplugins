import PageTypeBuilder from '@/components/shared/builder/PageTypeBuilder';
import { customerTypeUI } from '@/lib/page-types/customer-type.ui';
import { emptyCustomerTypePageState } from '@/lib/customer-type-types';

export const dynamic = 'force-dynamic';

export default function CreateCustomerTypePage() {
  return (
    <PageTypeBuilder
      uiDef={customerTypeUI}
      initialState={emptyCustomerTypePageState()}
      mode="create"
    />
  );
}
