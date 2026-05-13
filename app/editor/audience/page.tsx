import AudienceBuilder from '@/components/audience/AudienceBuilder';
import { emptyAudienceState } from '@/lib/audience-types';

export const dynamic = 'force-dynamic';

export default function CreateAudiencePage() {
  return <AudienceBuilder initialState={emptyAudienceState()} />;
}
