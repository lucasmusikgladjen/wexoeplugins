import CaseBuilder from '@/components/case/CaseBuilder';
import { emptyCaseState } from '@/lib/case-types';

export const dynamic = 'force-dynamic';

export default function CreateCasePage() {
  return <CaseBuilder initialState={emptyCaseState()} mode="create" />;
}
