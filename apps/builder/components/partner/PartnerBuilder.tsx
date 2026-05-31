'use client';

/**
 * Klient-sidans wrapper kring `PageTypeBuilder` för partner-page
 * (leverantörssida).
 *
 * Server-pages (app/editor/partner/...) får inte importera `partnerUI`
 * direkt och skicka det som prop — UI-definitionen innehåller arrow-
 * functions (`previewLayout`, `slugInput.accessor/setter/badge`, `canSave`,
 * inline-`Editor`-komponenter). Next 16:s serializer vägrar sådana props
 * över server→klient-gränsen och render:n kraschar med "An error occurred
 * in the Server Components render".
 *
 * Lösningen: håll uiDef-importen INNANFÖR klient-bundlen. Server-pages
 * skickar bara serialiserbar data (initialState, recordId, mode) hit.
 */

import PageTypeBuilder from '@/components/shared/builder/PageTypeBuilder';
import { partnerUI } from '@/lib/page-types/partner.ui';
import { PartnerPageState } from '@/lib/partner-types';

interface PartnerBuilderProps {
  initialState: PartnerPageState;
  mode: 'create' | 'edit';
  recordId?: string;
}

export default function PartnerBuilder({ initialState, mode, recordId }: PartnerBuilderProps) {
  return (
    <PageTypeBuilder
      uiDef={partnerUI}
      initialState={initialState}
      mode={mode}
      recordId={recordId}
      apiPath="/api/partner"
      editPath="/editor/partner/:recordId"
    />
  );
}
