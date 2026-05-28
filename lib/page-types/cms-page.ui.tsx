/**
 * CMS Page — UI-side sidtypsdefinition.
 *
 * 2 fasta editor-paneler (metadata + sections). Sektioner är polymorfa och
 * hanteras dynamiskt inom "sections"-panelen via SectionsEditor.
 *
 * Publicerad-toggle ligger i toolbarExtras så marknadsföraren ser
 * publiceringsstatus i toolbaren oberoende av vilken panel som är öppen.
 */

import { CmsPageState } from '../cms-page-types';
import MetadataPanel from '@/components/cms-page/editors/MetadataPanel';
import AddSectionEditor from '@/components/cms-page/editors/AddSectionEditor';
import CmsPageSectionEditor from '@/components/cms-page/editors/CmsPageSectionEditor';
import CmsPagePreview from '@/components/cms-page/preview/CmsPagePreview';
import type { PageTypeUIDef, SectionDef, SectionEditorProps } from './types';

const sections: SectionDef<CmsPageState>[] = [
  {
    id: 'metadata',
    label: 'Sidinfo & SEO',
    Editor: MetadataPanel,
  },
  {
    id: 'add-section',
    label: 'Lägg till sektion',
    Editor: AddSectionEditor,
  },
];

export const cmsPageUI: PageTypeUIDef<CmsPageState> & {
  canSave: (state: CmsPageState) => boolean;
  canSaveHint: string;
} = {
  id: 'cms-page',
  label: 'Sida',
  sections,
  resolveSections: (state): SectionDef<CmsPageState>[] => [
    sections[0],
    ...state.sections.map((section, index) => ({
      id: section.clientId,
      label: section.internalLabel?.trim() || `Sektion ${index + 1}`,
      description: undefined,
      Editor: ({ state: s, onChange }: SectionEditorProps<CmsPageState>) => (
        <CmsPageSectionEditor state={s} onChange={onChange} index={index} />
      ),
    })),
    sections[1],
  ],
  previewLayout: ({ state, activeSection, scrollTrigger, onSectionClick }) => (
    <CmsPagePreview
      state={state}
      activeSection={activeSection}
      scrollTrigger={scrollTrigger}
      onSectionClick={onSectionClick}
    />
  ),
  slugInput: {
    accessor: (s) => s.slug,
    setter: (s, slug) => ({ ...s, slug }),
    placeholder: 'om-oss',
    badge: (_s, mode) => (mode === 'create' ? 'Ny sida' : 'Sida'),
  },
  toolbarExtras: ({ state, setState }) => (
    <label className="flex items-center gap-1.5 ml-2 text-xs text-gray-600 cursor-pointer">
      <input
        type="checkbox"
        checked={state.isPublished}
        onChange={(e) => setState({ ...state, isPublished: e.target.checked })}
        className="h-3.5 w-3.5"
      />
      Publicerad
    </label>
  ),
  canSave: (s) => !!s.slug.trim(),
  canSaveHint: 'Slug krävs',
};
