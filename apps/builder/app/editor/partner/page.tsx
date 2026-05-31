import PartnerBuilder from '@/components/partner/PartnerBuilder';
import { emptyPartnerPageState } from '@/lib/partner-types';

export const dynamic = 'force-dynamic';

export default function CreatePartnerPage() {
  return <PartnerBuilder initialState={emptyPartnerPageState()} mode="create" />;
}
