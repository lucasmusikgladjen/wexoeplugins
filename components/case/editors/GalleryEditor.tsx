'use client';

import {
  CaseState,
  CaseGalleryImage,
  CASE_GALLERY_MAX,
} from '@/lib/case-types';
import { Field } from '@/components/shared/fields';
import RepeaterCard from '@/components/shared/RepeaterCard';
import type { SectionEditorProps } from '@/lib/page-types/types';

const emptyImage = (): CaseGalleryImage => ({ url: '', caption: '' });
const hasContent = (i: CaseGalleryImage) => !!i.url.trim();

export default function GalleryEditor({ state, onChange }: SectionEditorProps<CaseState>) {
  const set = <K extends keyof CaseState>(key: K, value: CaseState[K]) =>
    onChange({ ...state, [key]: value });

  const images = state.galleryImages;
  const setImages = (next: CaseGalleryImage[]) => set('galleryImages', next);

  const patch = (i: number, p: Partial<CaseGalleryImage>) =>
    setImages(images.map((img, idx) => (idx === i ? { ...img, ...p } : img)));

  const move = (i: number, dir: -1 | 1) => {
    const next = [...images];
    const j = i + dir;
    if (j < 0 || j >= next.length) return;
    [next[i], next[j]] = [next[j], next[i]];
    setImages(next);
  };

  const remove = (i: number) => setImages(images.filter((_, idx) => idx !== i));
  const add = () => {
    if (images.length >= CASE_GALLERY_MAX) return;
    setImages([...images, emptyImage()]);
  };

  return (
    <>
      <Field.Text
        label="Galleri-rubrik (H3)"
        value={state.galleryTitle}
        onChange={(v) => set('galleryTitle', v)}
        placeholder="T.ex. Från installation till idrifttagning"
      />

      <p className="text-[11px] text-gray-400 mt-2">
        Bilder (max {CASE_GALLERY_MAX}). Var tredje bild renderas i full bredd; övriga visas i 2-kolumner.
      </p>

      {images.map((img, i) => (
        <RepeaterCard
          key={i}
          index={i}
          title={img.caption}
          onTitleChange={(v) => patch(i, { caption: v })}
          titlePlaceholder="Bildtext…"
          onMoveUp={() => move(i, -1)}
          onMoveDown={() => move(i, 1)}
          canMoveUp={i > 0}
          canMoveDown={i < images.length - 1}
          onRemove={() => remove(i)}
          removeTitle="Ta bort bild"
          defaultOpen={!hasContent(img)}
        >
          <Field.Text
            label="Bild (URL)"
            value={img.url}
            onChange={(v) => patch(i, { url: v })}
            placeholder="https://..."
          />
          <Field.Text
            label="Bildtext"
            value={img.caption}
            onChange={(v) => patch(i, { caption: v })}
            placeholder="T.ex. Operatör validerar nya FT Optix-bilder…"
          />
        </RepeaterCard>
      ))}

      {images.length < CASE_GALLERY_MAX && (
        <button
          type="button"
          onClick={add}
          className="w-full py-2 text-sm text-gray-300 hover:text-gray-500 transition-colors"
        >
          + Lägg till bild ({images.length} / {CASE_GALLERY_MAX})
        </button>
      )}
    </>
  );
}
