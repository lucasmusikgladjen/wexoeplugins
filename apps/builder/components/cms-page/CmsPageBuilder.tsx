'use client';

/**
 * Klient-sidans wrapper kring `PageTypeBuilder` för cms-page.
 *
 * Server-pages (app/editor/page/...) får inte längre importera `cmsPageUI`
 * direkt — UI-definitionen innehåller arrow-functions för `previewLayout`,
 * `slugInput.accessor/setter/badge`, `toolbarExtras` och `canSave`. När
 * Next 16:s strikta serializer försöker skicka en sådan prop över server→
 * klient-gränsen kraschar render:n med "Functions cannot be passed directly
 * to Client Components".
 *
 * Lösningen: håll uiDef-importen INNANFÖR klient-bundlen. Server-pages
 * skickar bara serialiserbar data (initialState, recordId, mode) som props
 * hit, och vi gör resten lokalt.
 */

import PageTypeBuilder from '@/components/shared/builder/PageTypeBuilder';
import { cmsPageUI } from '@/lib/page-types/cms-page.ui';
import type { CmsPageState } from '@/lib/cms-page-types';

interface Props {
  initialState: CmsPageState;
  mode: 'create' | 'edit';
  recordId?: string;
}

export default function CmsPageBuilder({ initialState, mode, recordId }: Props) {
  return (
    <PageTypeBuilder
      uiDef={cmsPageUI}
      initialState={initialState}
      mode={mode}
      recordId={recordId}
      apiPath="/api/page"
      editPath="/editor/page/:recordId"
    />
  );
}
