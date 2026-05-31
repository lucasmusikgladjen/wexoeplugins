import CmsPageBuilder from '@/components/cms-page/CmsPageBuilder';
import { emptyCmsPageState } from '@/lib/cms-page-types';

export const dynamic = 'force-dynamic';

export default function CreateCmsPage() {
  return <CmsPageBuilder initialState={emptyCmsPageState()} mode="create" />;
}
