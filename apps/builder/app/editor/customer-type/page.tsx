import CustomerTypeBuilder from '@/components/customer-type/CustomerTypeBuilder';
import { emptyCustomerTypePageState } from '@/lib/customer-type-types';

export const dynamic = 'force-dynamic';

export default function CreateCustomerTypePage() {
  return <CustomerTypeBuilder initialState={emptyCustomerTypePageState()} mode="create" />;
}
