'use client';

import { ProductAreaState, LinkedProduct, emptyLinkedProduct } from '@/lib/product-area-types';
import { FieldInput, FieldTextarea, FieldCheckbox, FieldColor } from '@/components/editors/FieldInput';
import CollapsibleCard from './CollapsibleCard';
import ButtonFieldset from '@/components/editors/ButtonFieldset';

interface Props {
  state: ProductAreaState;
  setField: <K extends keyof ProductAreaState>(key: K, value: ProductAreaState[K]) => void;
  visible: boolean;
  onToggleVisible: (v: boolean) => void;
}

/** A product is considered "filled" once it has a name — used to decide
 *  whether the card should start collapsed (compact list view). */
function hasContent(p: LinkedProduct): boolean {
  return !!p.name.trim();
}

export default function ProductsEditor({ state, setField, visible, onToggleVisible }: Props) {
  const patchProduct = (index: number, patch: Partial<LinkedProduct>) => {
    setField(
      'products',
      state.products.map((p, i) => (i === index ? { ...p, ...patch } : p)),
    );
  };

  const moveProduct = (index: number, direction: -1 | 1) => {
    const newIndex = index + direction;
    if (newIndex < 0 || newIndex >= state.products.length) return;
    const next = [...state.products];
    const [moved] = next.splice(index, 1);
    next.splice(newIndex, 0, moved);
    setField('products', next);
  };

  const removeProduct = (index: number) => {
    setField(
      'products',
      state.products.filter((_, i) => i !== index),
    );
  };

  const addProduct = () => {
    const next = emptyLinkedProduct();
    next.order = state.products.length + 1;
    next.name = '';
    setField('products', [...state.products, next]);
  };

  return (
    <div className="space-y-3">
      <div className="flex items-center justify-between">
        <h3 className="text-xl font-bold text-gray-900">Produkter</h3>
        <FieldCheckbox label="Visa" checked={visible} onChange={onToggleVisible} />
      </div>

      {visible && (
      <>
      {state.products.map((product, i) => (
        <CollapsibleCard
          key={product.clientId}
          index={i}
          title={product.name}
          onTitleChange={(v) => patchProduct(i, { name: v })}
          titlePlaceholder="Produktnamn…"
          onMoveUp={() => moveProduct(i, -1)}
          onMoveDown={() => moveProduct(i, 1)}
          canMoveUp={i > 0}
          canMoveDown={i < state.products.length - 1}
          onRemove={() => removeProduct(i)}
          removeTitle="Ta bort produkt"
          defaultOpen={!hasContent(product)}
        >
          <FieldInput
            label="Underrubrik"
            value={product.ecosystemDescription}
            onChange={(v) => patchProduct(i, { ecosystemDescription: v })}
            placeholder="T.ex. Integrerad arkitektur"
          />

          <FieldTextarea
            label="Beskrivning"
            value={product.description}
            onChange={(v) => patchProduct(i, { description: v })}
            rows={4}
            placeholder="Kort beskrivning av produkten…"
          />

          <FieldTextarea
            label="Punkter"
            value={product.bullets}
            onChange={(v) => patchProduct(i, { bullets: v })}
            rows={3}
            hint="en per rad"
            placeholder={'Hög tillgänglighet\nKonsoliderad plattform'}
          />

          <FieldInput
            label="Bild"
            value={product.image}
            onChange={(v) => patchProduct(i, { image: v })}
            placeholder="https://..."
          />

          <ButtonFieldset
            label="Knapp 1"
            segments={[
              {
                value: product.button2Text,
                onChange: (v) => patchProduct(i, { button2Text: v }),
                placeholder: 'Text',
              },
              {
                value: product.button2Url,
                onChange: (v) => patchProduct(i, { button2Url: v }),
                placeholder: 'URL',
              },
            ]}
          />

          <ButtonFieldset
            label="Knapp 2"
            segments={[
              {
                value: product.button1Text,
                onChange: (v) => patchProduct(i, { button1Text: v }),
                placeholder: 'Text',
              },
              {
                value: product.button1Url,
                onChange: (v) => patchProduct(i, { button1Url: v }),
                placeholder: 'URL',
              },
            ]}
          />

          {state.sideMenu && (
            <FieldInput
              label="Rubrik i sidomeny"
              value={product.headerSideMenu}
              onChange={(v) => patchProduct(i, { headerSideMenu: v })}
              placeholder="Lämna tom för att använda produktnamnet"
            />
          )}

          <FieldCheckbox
            label="Visa"
            checked={product.visa}
            onChange={(v) => patchProduct(i, { visa: v })}
          />

          {/* Read-only linked articles */}
          {product.articles.length > 0 && (
            <div className="pt-2 mt-1 border-t border-gray-200/60">
              <p className="text-[11px] text-gray-400 mb-1.5">
                Artiklar <span className="text-gray-300">— läsläge</span>
              </p>
              <div className="space-y-1">
                {product.articles.map((a) => (
                  <div key={a.recordId} className="flex items-baseline justify-between gap-3 text-xs">
                    <span className="text-gray-600 truncate">{a.name || '(namnlös)'}</span>
                    <span className="font-mono text-gray-300 whitespace-nowrap">{a.artikelnummer || '—'}</span>
                  </div>
                ))}
              </div>
            </div>
          )}
        </CollapsibleCard>
      ))}

      <button
        type="button"
        onClick={addProduct}
        className="w-full py-2 text-sm text-gray-300 hover:text-gray-500 transition-colors"
      >
        + Lägg till produkt
      </button>

      <div className="grid grid-cols-3 gap-2">
        <FieldColor
          label="Bakgrundsfärg"
          value={state.toggleBg}
          onChange={(v) => setField('toggleBg', v)}
          defaultColor="#11325D"
        />
        <FieldColor
          label="Kortbakgrundsfärg"
          value={state.toggleHeaderBg}
          onChange={(v) => setField('toggleHeaderBg', v)}
          defaultColor="#FFFFFF"
        />
        <FieldColor
          label="Accentfärg"
          value={state.toggleAccent}
          onChange={(v) => setField('toggleAccent', v)}
          defaultColor="#F28C28"
        />
      </div>
      </>
      )}
    </div>
  );
}
