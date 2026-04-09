'use client';

import { useState, useCallback } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import {
  ProductAreaState,
  ProductAreaSectionId,
  NormalSection,
  LinkedProduct,
  LinkedSolution,
  Division,
  emptyLinkedProduct,
  emptyLinkedSolution,
} from '@/lib/product-area-types';
import { FieldInput, FieldTextarea, FieldCheckbox, FieldColor } from '@/components/editors/FieldInput';
import Collapsible from './Collapsible';
import ProductAreaPreviewPanel from './preview/ProductAreaPreviewPanel';

interface Props {
  initialState: ProductAreaState;
  divisions: Division[];
}

export default function ProductAreaBuilder({ initialState, divisions }: Props) {
  const router = useRouter();
  const [state, setState] = useState<ProductAreaState>(initialState);
  const [activeSection, setActiveSection] = useState<ProductAreaSectionId | null>('hero');
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [justSaved, setJustSaved] = useState(false);

  const isCreate = state.mode === 'create';
  const canSave = !!state.slug.trim() && !!state.h1.trim();

  // ── Setters ─────────────────────────────────────────────────────────
  function setField<K extends keyof ProductAreaState>(key: K, value: ProductAreaState[K]) {
    setState((s) => ({ ...s, [key]: value }));
    setJustSaved(false);
  }

  function setNormal(n: 1 | 2 | 3 | 4, patch: Partial<NormalSection>) {
    const key = (`normal${n}` as 'normal1' | 'normal2' | 'normal3' | 'normal4');
    setState((s) => ({ ...s, [key]: { ...s[key], ...patch } }));
    setJustSaved(false);
  }

  function patchProduct(index: number, patch: Partial<LinkedProduct>) {
    setState((s) => ({
      ...s,
      products: s.products.map((p, i) => (i === index ? { ...p, ...patch } : p)),
    }));
    setJustSaved(false);
  }

  function patchSolution(index: number, patch: Partial<LinkedSolution>) {
    setState((s) => ({
      ...s,
      solutions: s.solutions.map((sol, i) => (i === index ? { ...sol, ...patch } : sol)),
    }));
    setJustSaved(false);
  }

  function addProduct() {
    const newProduct = emptyLinkedProduct();
    newProduct.order = state.products.length + 1;
    newProduct.name = 'Ny produkt';
    setState((s) => ({ ...s, products: [...s.products, newProduct] }));
    setActiveSection('products');
    setJustSaved(false);
  }

  function addSolution() {
    const newSolution = emptyLinkedSolution();
    newSolution.order = state.solutions.length + 1;
    newSolution.name = 'Ny lösning';
    setState((s) => ({ ...s, solutions: [...s.solutions, newSolution] }));
    setActiveSection('solutions');
    setJustSaved(false);
  }

  const handleSectionClick = useCallback((id: ProductAreaSectionId) => {
    setActiveSection(id);
  }, []);

  // ── Save ─────────────────────────────────────────────────────────────
  async function handleSave() {
    if (!canSave) return;
    setSaving(true);
    setError(null);
    try {
      const res = await fetch('/api/product-area', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(state),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Sparning misslyckades');

      // Create mode: we now have a real recordId — redirect to the edit URL
      // so reloading the page works and subsequent saves hit the update path.
      if (data.mode === 'create' && data.recordId) {
        router.replace(`/editor/product-area/${data.recordId}`);
        return;
      }

      // Update mode: merge back any newly-created record IDs returned by the
      // server so the next save PATCHes them in place instead of creating again.
      if (data.newProductIds || data.newSolutionIds) {
        setState((s) => ({
          ...s,
          products: applyNewIds(s.products, data.newProductIds ?? {}),
          solutions: applyNewIds(s.solutions, data.newSolutionIds ?? {}),
        }));
      }

      setJustSaved(true);
      router.refresh();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Okänt fel');
    } finally {
      setSaving(false);
    }
  }

  // ── Render ───────────────────────────────────────────────────────────
  return (
    <div className="h-screen flex flex-col" style={{ fontFamily: 'var(--font-dm-sans)' }}>
      {/* Toolbar */}
      <header className="h-14 border-b border-gray-100 bg-white flex items-center px-4 gap-4 flex-shrink-0 z-10">
        <Link
          href="/"
          className="flex items-center gap-2 text-gray-500 hover:text-gray-900 transition-colors"
          title="Tillbaka till sidor"
        >
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M19 12H5M12 19l-7-7 7-7" />
          </svg>
          <span className="text-sm">Sidor</span>
        </Link>

        <div className="h-5 w-px bg-gray-200 mx-1" />

        <div className="flex items-center gap-2">
          <span className="text-xs text-gray-400">Slug:</span>
          <input
            type="text"
            value={state.slug}
            onChange={(e) => setField('slug', e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, ''))}
            placeholder="min-sida"
            className="w-44 px-2 py-1 text-sm border border-gray-200 rounded-md focus:border-gray-400 focus:outline-none"
          />
          <span className="text-[10px] uppercase tracking-wider text-gray-300 ml-2">
            {isCreate ? 'Ny produktsida' : 'Produktsida'}
          </span>
        </div>

        <div className="flex-1" />

        {error && <span className="text-xs text-red-500 truncate max-w-xs">{error}</span>}
        {justSaved && !error && <span className="text-xs text-gray-400">Sparat ✓</span>}
        {!canSave && !error && (
          <span className="text-xs text-gray-300">Slug + H1 krävs</span>
        )}

        <button
          onClick={handleSave}
          disabled={saving || !canSave}
          className="px-4 py-1.5 rounded-md text-sm font-medium text-white transition-opacity hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed"
          style={{ background: '#11325D' }}
        >
          {saving ? (isCreate ? 'Skapar…' : 'Sparar…') : isCreate ? 'Skapa' : 'Spara'}
        </button>
      </header>

      {/* 2-pane layout: preview + editor */}
      <div className="flex-1 flex min-h-0">
        <div className="flex-[65] min-w-0">
          <ProductAreaPreviewPanel
            state={state}
            activeSection={activeSection}
            onSectionClick={handleSectionClick}
          />
        </div>

        <div className="flex-[35] min-w-[380px] max-w-[520px] border-l border-gray-100 bg-white overflow-y-auto editor-panel">
          <div className="px-6 py-6">
            {/* ── Hero ─────────────────────────────────────────────── */}
            <Collapsible title="Hero" meta={state.heroH2 || state.h1 || 'tom'} defaultOpen>
              <div className="space-y-3">
                <FieldInput label="H1 (topbanner)" value={state.h1} onChange={(v) => setField('h1', v)} placeholder="Sidans huvudrubrik" />
                <FieldColor label="Top BG" value={state.topBg} onChange={(v) => setField('topBg', v)} defaultColor="#11325D" />

                <div className="h-px bg-gray-100 my-4" />

                <FieldInput label="Hero H2" value={state.heroH2} onChange={(v) => setField('heroH2', v)} />
                <FieldTextarea label="Hero Text" value={state.heroText} onChange={(v) => setField('heroText', v)} rows={4} hint="markdown" />
                <FieldTextarea label="Hero Benefits" value={state.heroBenefits} onChange={(v) => setField('heroBenefits', v)} rows={3} hint="en per rad" />
                <FieldInput label="Hero Image URL" value={state.heroImage} onChange={(v) => setField('heroImage', v)} />

                <div className="grid grid-cols-2 gap-3">
                  <FieldInput label="CTA Text" value={state.heroCtaText} onChange={(v) => setField('heroCtaText', v)} />
                  <FieldInput label="CTA URL" value={state.heroCtaUrl} onChange={(v) => setField('heroCtaUrl', v)} />
                </div>

                <div className="grid grid-cols-2 gap-3">
                  <FieldColor label="Hero BG" value={state.heroBg} onChange={(v) => setField('heroBg', v)} defaultColor="#FFFFFF" />
                  <FieldColor label="Hero Accent" value={state.heroAccent} onChange={(v) => setField('heroAccent', v)} defaultColor="#F28C28" />
                </div>
              </div>
            </Collapsible>

            {/* ── NPI ─────────────────────────────────────────────── */}
            <Collapsible title="NPI-kort" meta={state.npiTitle || 'tom'}>
              <div className="space-y-3">
                <FieldInput label="NPI Title" value={state.npiTitle} onChange={(v) => setField('npiTitle', v)} placeholder="Visas bara om ifylld" />
                <FieldTextarea label="NPI Description" value={state.npiDescription} onChange={(v) => setField('npiDescription', v)} rows={3} />
                <FieldInput label="NPI Image URL" value={state.npiImage} onChange={(v) => setField('npiImage', v)} />
                <FieldInput label="NPI Link URL" value={state.npiLink} onChange={(v) => setField('npiLink', v)} />
              </div>
            </Collapsible>

            {/* ── Normal 1–4 ───────────────────────────────────────── */}
            {[1, 2, 3, 4].map((n) => {
              const section = state[`normal${n}` as 'normal1' | 'normal2' | 'normal3' | 'normal4'];
              const metaParts = [
                section.h2 || 'tom',
                section.upp ? 'upp' : null,
                section.reversed ? 'reversed' : null,
              ].filter(Boolean).join(' · ');
              return (
                <Collapsible key={n} title={`Innehåll ${n}`} meta={metaParts}>
                  <div className="space-y-3">
                    <FieldInput label="H2" value={section.h2} onChange={(v) => setNormal(n as 1 | 2 | 3 | 4, { h2: v })} />
                    <FieldTextarea label="Text" value={section.text} onChange={(v) => setNormal(n as 1 | 2 | 3 | 4, { text: v })} rows={4} hint="markdown" />
                    <FieldTextarea label="Bullets" value={section.bullets} onChange={(v) => setNormal(n as 1 | 2 | 3 | 4, { bullets: v })} rows={3} hint="en per rad" />
                    <FieldInput label="Image URL" value={section.image} onChange={(v) => setNormal(n as 1 | 2 | 3 | 4, { image: v })} />
                    <FieldColor label="Background" value={section.bg} onChange={(v) => setNormal(n as 1 | 2 | 3 | 4, { bg: v })} defaultColor={n % 2 === 0 ? '#F8F9FA' : '#FFFFFF'} />
                    <div className="flex items-center gap-6 pt-1">
                      <FieldCheckbox
                        label="Reversed (bild till vänster)"
                        checked={section.reversed}
                        onChange={(v) => setNormal(n as 1 | 2 | 3 | 4, { reversed: v })}
                      />
                      <FieldCheckbox
                        label="Rendera före produkter"
                        checked={section.upp}
                        onChange={(v) => setNormal(n as 1 | 2 | 3 | 4, { upp: v })}
                      />
                    </div>
                  </div>
                </Collapsible>
              );
            })}

            {/* ── Products ─────────────────────────────────────────── */}
            <Collapsible
              title="Produkter"
              meta={`${state.products.length} länkade`}
              defaultOpen={state.products.length > 0 && state.products.length <= 3}
            >
              <div className="mb-3 space-y-3">
                <FieldColor label="Toggle BG" value={state.toggleBg} onChange={(v) => setField('toggleBg', v)} defaultColor="#11325D" />
                <FieldColor label="Header BG" value={state.toggleHeaderBg} onChange={(v) => setField('toggleHeaderBg', v)} defaultColor="#FFFFFF" />
                <FieldColor label="Accent" value={state.toggleAccent} onChange={(v) => setField('toggleAccent', v)} defaultColor="#F28C28" />
              </div>

              <div className="space-y-2">
                {state.products.map((product, i) => (
                  <Collapsible
                    key={product.clientId}
                    title={product.name || '(namnlös produkt)'}
                    meta={`${product.articles.length} artiklar${product.horizontal ? ' · horisontell' : ''}${!product.visa ? ' · dold' : ''}${!product.recordId ? ' · ny' : ''}`}
                  >
                    <div className="space-y-3">
                      <FieldInput label="Namn" value={product.name} onChange={(v) => patchProduct(i, { name: v })} />
                      <FieldInput label="Header side menu (fallback till namn)" value={product.headerSideMenu} onChange={(v) => patchProduct(i, { headerSideMenu: v })} />
                      <FieldInput label="Ecosystem Description" value={product.ecosystemDescription} onChange={(v) => patchProduct(i, { ecosystemDescription: v })} />
                      <FieldTextarea label="Description" value={product.description} onChange={(v) => patchProduct(i, { description: v })} rows={4} hint="markdown" />
                      <FieldTextarea label="Bullets" value={product.bullets} onChange={(v) => patchProduct(i, { bullets: v })} rows={3} hint="en per rad" />
                      <FieldInput label="Image URL" value={product.image} onChange={(v) => patchProduct(i, { image: v })} />

                      <div className="grid grid-cols-2 gap-3">
                        <FieldInput label="Button 1 Text" value={product.button1Text} onChange={(v) => patchProduct(i, { button1Text: v })} />
                        <FieldInput label="Button 1 URL" value={product.button1Url} onChange={(v) => patchProduct(i, { button1Url: v })} />
                        <FieldInput label="Button 2 Text" value={product.button2Text} onChange={(v) => patchProduct(i, { button2Text: v })} />
                        <FieldInput label="Button 2 URL" value={product.button2Url} onChange={(v) => patchProduct(i, { button2Url: v })} />
                      </div>

                      <div className="grid grid-cols-3 gap-3 items-center">
                        <FieldInput label="Order" type="number" value={String(product.order)} onChange={(v) => patchProduct(i, { order: parseInt(v, 10) || 0 })} />
                        <FieldCheckbox label="Visa" checked={product.visa} onChange={(v) => patchProduct(i, { visa: v })} />
                        <FieldCheckbox label="Horizontal" checked={product.horizontal} onChange={(v) => patchProduct(i, { horizontal: v })} />
                      </div>

                      {product.articles.length > 0 && (
                        <div className="mt-4 pt-3 border-t border-gray-100">
                          <p className="text-[11px] uppercase tracking-wider text-gray-300 mb-2">
                            Artiklar ({product.articles.length}) — läsläge
                          </p>
                          <div className="space-y-1">
                            {product.articles.map((a) => (
                              <div key={a.recordId} className="flex items-baseline justify-between gap-3 text-xs">
                                <span className="text-gray-700 truncate">{a.name || '(namnlös)'}</span>
                                <span className="font-mono text-gray-300 whitespace-nowrap">{a.artikelnummer || '—'}</span>
                              </div>
                            ))}
                          </div>
                        </div>
                      )}
                    </div>
                  </Collapsible>
                ))}
              </div>

              <button
                type="button"
                onClick={addProduct}
                className="mt-3 w-full py-2 text-xs text-gray-400 hover:text-gray-700 border border-dashed border-gray-200 rounded-md hover:border-gray-300 transition-colors"
              >
                + Ny produkt
              </button>
            </Collapsible>

            {/* ── Solutions ────────────────────────────────────────── */}
            <Collapsible title="Lösningar" meta={`${state.solutions.length} länkade`}>
              <div className="mb-3 space-y-3">
                <FieldInput label="Solutions Title" value={state.solutionsTitle} onChange={(v) => setField('solutionsTitle', v)} placeholder="Lösningar & koncept" />
                <FieldColor label="Solutions BG" value={state.solutionsBg} onChange={(v) => setField('solutionsBg', v)} defaultColor="#FFFFFF" />
                <FieldColor label="Card BG" value={state.solutionsCardBg} onChange={(v) => setField('solutionsCardBg', v)} defaultColor="#FFFFFF" />
              </div>

              <div className="space-y-2">
                {state.solutions.map((sol, i) => (
                  <Collapsible
                    key={sol.clientId}
                    title={sol.name || '(namnlös lösning)'}
                    meta={[sol.category, !sol.recordId && 'ny'].filter(Boolean).join(' · ') || undefined}
                  >
                    <div className="space-y-3">
                      <FieldInput label="Namn" value={sol.name} onChange={(v) => patchSolution(i, { name: v })} />
                      <FieldInput label="Kategori" value={sol.category} onChange={(v) => patchSolution(i, { category: v })} />
                      <FieldTextarea label="Beskrivning" value={sol.description} onChange={(v) => patchSolution(i, { description: v })} rows={3} />
                      <FieldInput label="Image URL" value={sol.image} onChange={(v) => patchSolution(i, { image: v })} />
                      <FieldInput label="Länk-URL" value={sol.url} onChange={(v) => patchSolution(i, { url: v })} />
                      <FieldInput label="CTA Text" value={sol.ctaText} onChange={(v) => patchSolution(i, { ctaText: v })} placeholder="Läs mer" />

                      <div className="grid grid-cols-2 gap-3 items-center">
                        <FieldInput label="Order" type="number" value={String(sol.order)} onChange={(v) => patchSolution(i, { order: parseInt(v, 10) || 0 })} />
                        <FieldCheckbox label="Visa" checked={sol.visa} onChange={(v) => patchSolution(i, { visa: v })} />
                      </div>
                    </div>
                  </Collapsible>
                ))}
              </div>

              <button
                type="button"
                onClick={addSolution}
                className="mt-3 w-full py-2 text-xs text-gray-400 hover:text-gray-700 border border-dashed border-gray-200 rounded-md hover:border-gray-300 transition-colors"
              >
                + Ny lösning
              </button>
            </Collapsible>

            {/* ── Contact ──────────────────────────────────────────── */}
            <Collapsible title="Kontaktperson" meta={state.contactName || 'tom'}>
              <div className="space-y-3">
                <FieldInput label="Namn" value={state.contactName} onChange={(v) => setField('contactName', v)} />
                <FieldInput label="Titel" value={state.contactTitle} onChange={(v) => setField('contactTitle', v)} />
                <div className="grid grid-cols-2 gap-3">
                  <FieldInput label="Email" value={state.contactEmail} onChange={(v) => setField('contactEmail', v)} />
                  <FieldInput label="Telefon" value={state.contactPhone} onChange={(v) => setField('contactPhone', v)} />
                </div>
                <FieldInput label="Bild-URL" value={state.contactImage} onChange={(v) => setField('contactImage', v)} />
                <FieldTextarea label="Citat/text" value={state.contactText} onChange={(v) => setField('contactText', v)} rows={3} />
                <FieldColor label="Background" value={state.contactBg} onChange={(v) => setField('contactBg', v)} defaultColor="#11325D" />
              </div>
            </Collapsible>

            {/* ── Docs ─────────────────────────────────────────────── */}
            <Collapsible title="Dokumentation" meta={state.docsIframe ? 'aktiv' : 'tom'}>
              <div className="space-y-3">
                <FieldInput label="Docs Title" value={state.docsTitle} onChange={(v) => setField('docsTitle', v)} placeholder="Dokumentation" />
                <FieldInput label="Docs Iframe URL" value={state.docsIframe} onChange={(v) => setField('docsIframe', v)} />
                <FieldColor label="Background" value={state.docsBg} onChange={(v) => setField('docsBg', v)} defaultColor="#FFFFFF" />
              </div>
            </Collapsible>

            {/* ── Settings ─────────────────────────────────────────── */}
            <Collapsible
              title="Inställningar"
              meta={
                [state.sideMenu && 'sidomeny', state.request && 'prisförfrågan', divisionName(state.division, divisions)]
                  .filter(Boolean)
                  .join(' · ') || 'standard'
              }
              defaultOpen={isCreate}
            >
              <div className="space-y-4">
                <FieldCheckbox label="Sidomeny-läge (istället för togglelista)" checked={state.sideMenu} onChange={(v) => setField('sideMenu', v)} />
                <FieldCheckbox label="Prisförfrågan-formulär" checked={state.request} onChange={(v) => setField('request', v)} />
                <FieldCheckbox label="Default open (första produkten öppen)" checked={state.defaultOpen} onChange={(v) => setField('defaultOpen', v)} />

                <div className="pt-3 border-t border-gray-100">
                  <label className="block">
                    <span className="text-[11px] text-gray-400">Division</span>
                    <select
                      value={state.division[0] ?? ''}
                      onChange={(e) =>
                        setField('division', e.target.value ? [e.target.value] : [])
                      }
                      className="mt-0.5 block w-full rounded bg-gray-100/80 px-3 py-2 text-sm text-gray-700 focus:bg-white focus:ring-1 focus:ring-gray-200 focus:outline-none"
                    >
                      <option value="">— Ingen —</option>
                      {divisions.map((d) => (
                        <option key={d.id} value={d.id}>
                          {d.name}
                        </option>
                      ))}
                    </select>
                  </label>
                </div>
              </div>
            </Collapsible>

            {state.recordId && (
              <div className="mt-10 text-center">
                <p className="text-[11px] text-gray-300 font-mono">rec: {state.recordId}</p>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

// Merge newly-assigned Airtable record IDs into client-side state. The server
// returns a map of clientId → new recordId after create.
function applyNewIds<T extends { clientId: string; recordId: string }>(
  items: T[],
  newIds: Record<string, string>,
): T[] {
  return items.map((item) => {
    if (item.recordId || !newIds[item.clientId]) return item;
    return { ...item, recordId: newIds[item.clientId] };
  });
}

/** Resolve a division record-ID array to a display label for the Settings
 *  collapsible meta line. */
function divisionName(ids: string[], divisions: Division[]): string {
  if (ids.length === 0) return '';
  const match = divisions.find((d) => d.id === ids[0]);
  return match?.name ?? '';
}
