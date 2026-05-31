'use client';

import { PageState, PageAction, Tab, TabType } from '@/lib/types';
import { FieldInput, FieldSelect, FieldCheckbox, RichTextarea } from './FieldInput';
import EditorSection from './EditorSection';

function ItemCard({ index, onRemove, children }: { index?: number; onRemove: () => void; children: React.ReactNode }) {
  return (
    <div className="bg-white rounded p-2.5 space-y-2">
      <div className="flex items-center justify-between">
        {index !== undefined && (
          <span className="text-[11px] text-gray-400">{index + 1}.</span>
        )}
        <button onClick={onRemove} className="text-[11px] text-gray-300 hover:text-red-400 ml-auto" title="Ta bort">✕</button>
      </div>
      {children}
    </div>
  );
}

interface Props {
  state: PageState;
  dispatch: React.Dispatch<PageAction>;
}

const tabTypeOptions: { value: string; label: string }[] = [
  { value: 'textimage', label: 'Text + Bild' },
  { value: 'fullmedia', label: 'Helbild / Video' },
  { value: 'faq', label: 'FAQ' },
  { value: 'calameo', label: 'Calameo' },
  { value: 'downloads', label: 'Nedladdningar' },
  { value: 'compare', label: 'Jämförelse' },
  { value: 'steps', label: 'Steg-för-steg' },
];

export default function TabsEditor({ state, dispatch }: Props) {
  const set = (field: keyof PageState, value: unknown) =>
    dispatch({ type: 'SET_FIELD', field, value });

  const moveTab = (index: number, direction: -1 | 1) => {
    const newIndex = index + direction;
    if (newIndex < 0 || newIndex >= state.tabs.length) return;
    dispatch({ type: 'REORDER_TABS', fromIndex: index, toIndex: newIndex });
  };

  return (
    <EditorSection
      title="Tabs"
      visible={state.showTabs}
      onToggleVisible={(v) => set('showTabs', v)}
    >
      {state.tabs.map((tab, index) => (
        <TabEditor
          key={tab.id}
          tab={tab}
          index={index}
          total={state.tabs.length}
          dispatch={dispatch}
          onMoveUp={() => moveTab(index, -1)}
          onMoveDown={() => moveTab(index, 1)}
          onRemove={() => dispatch({ type: 'REMOVE_TAB', tabId: tab.id })}
        />
      ))}
      <button
        onClick={() => dispatch({ type: 'ADD_TAB' })}
        className="w-full py-2 text-sm text-gray-300 hover:text-gray-500 transition-colors"
      >
        + Lägg till tab
      </button>
    </EditorSection>
  );
}

function TabEditor({
  tab,
  index,
  total,
  dispatch,
  onMoveUp,
  onMoveDown,
  onRemove,
}: {
  tab: Tab;
  index: number;
  total: number;
  dispatch: React.Dispatch<PageAction>;
  onMoveUp: () => void;
  onMoveDown: () => void;
  onRemove: () => void;
}) {
  const setField = (field: keyof Tab, value: unknown) =>
    dispatch({ type: 'SET_TAB_FIELD', tabId: tab.id, field, value });

  return (
    <div className="bg-gray-50/70 rounded-lg p-3 space-y-3">
      <div className="flex items-center gap-2">
        <span className="text-[11px] text-gray-400 w-4 text-center">{index + 1}</span>
        <input
          value={tab.name}
          onChange={(e) => setField('name', e.target.value)}
          className="flex-1 text-sm bg-transparent outline-none text-gray-700 placeholder:text-gray-300"
          placeholder="Tabnamn..."
        />
        <div className="flex items-center gap-0.5">
          <button onClick={onMoveUp} disabled={index === 0} className="text-gray-300 hover:text-gray-500 disabled:opacity-30 text-[11px] px-0.5" title="Flytta upp">▲</button>
          <button onClick={onMoveDown} disabled={index === total - 1} className="text-gray-300 hover:text-gray-500 disabled:opacity-30 text-[11px] px-0.5" title="Flytta ner">▼</button>
          <button onClick={onRemove} className="text-gray-300 hover:text-red-400 text-[11px] px-0.5 ml-0.5" title="Ta bort">✕</button>
        </div>
      </div>
      <FieldSelect
        label="Typ"
        value={tab.type}
        onChange={(v) => setField('type', v as TabType)}
        options={tabTypeOptions}
      />
      {tab.type === 'textimage' && <TextImageFields tab={tab} setField={setField} />}
      {tab.type === 'fullmedia' && <FullMediaFields tab={tab} setField={setField} />}
      {tab.type === 'faq' && <FaqFields tab={tab} dispatch={dispatch} />}
      {tab.type === 'calameo' && <CalameoFields tab={tab} setField={setField} />}
      {tab.type === 'downloads' && <DownloadFields tab={tab} dispatch={dispatch} />}
      {tab.type === 'compare' && <CompareFields tab={tab} setField={setField} dispatch={dispatch} />}
      {tab.type === 'steps' && <StepsFields tab={tab} setField={setField} dispatch={dispatch} />}
    </div>
  );
}

function TextImageFields({ tab, setField }: { tab: Tab; setField: (f: keyof Tab, v: unknown) => void }) {
  return (
    <>
      <FieldInput label="Rubrik" value={tab.tiH2} onChange={(v) => setField('tiH2', v)} placeholder="Rubrik för tabben" />
      <RichTextarea label="Text" value={tab.tiText} onChange={(v) => setField('tiText', v)} rows={6} />
      <RichTextarea label="Benefits" value={tab.tiBenefits} onChange={(v) => setField('tiBenefits', v)} rows={6} hint="en per rad" placeholder={"Fördel 1\nFördel 2\nFördel 3"} />
      <FieldInput label="Bild" value={tab.tiImage} onChange={(v) => setField('tiImage', v)} placeholder="https://..." />
      <FieldCheckbox label="Inverterad layout (bild till vänster)" checked={tab.tiInverted} onChange={(v) => setField('tiInverted', v)} />
    </>
  );
}

function FullMediaFields({ tab, setField }: { tab: Tab; setField: (f: keyof Tab, v: unknown) => void }) {
  return (
    <FieldInput label="Media" value={tab.fmUrl} onChange={(v) => setField('fmUrl', v)} placeholder="YouTube-länk eller bildlänk" />
  );
}

function FaqFields({ tab, dispatch }: { tab: Tab; dispatch: React.Dispatch<PageAction> }) {
  return (
    <div className="space-y-2">
      {tab.faqItems.map((item, i) => (
        <ItemCard key={item.id} index={i} onRemove={() => dispatch({ type: 'REMOVE_FAQ_ITEM', tabId: tab.id, itemId: item.id })}>
          <FieldInput
            label="Fråga"
            value={item.question}
            onChange={(v) => dispatch({ type: 'SET_FAQ_ITEM_FIELD', tabId: tab.id, itemId: item.id, field: 'question', value: v })}
            placeholder="T.ex. Vad är FTTO?"
          />
          <RichTextarea
            label="Svar"
            value={item.answer}
            onChange={(v) => dispatch({ type: 'SET_FAQ_ITEM_FIELD', tabId: tab.id, itemId: item.id, field: 'answer', value: v })}
            rows={4}
            placeholder="Svaret på frågan..."
          />
        </ItemCard>
      ))}
      <button
        onClick={() => dispatch({ type: 'ADD_FAQ_ITEM', tabId: tab.id })}
        className="w-full py-1.5 text-xs text-gray-300 hover:text-gray-500 transition-colors"
      >
        + Lägg till fråga
      </button>
    </div>
  );
}

function CalameoFields({ tab, setField }: { tab: Tab; setField: (f: keyof Tab, v: unknown) => void }) {
  return (
    <div className="space-y-2">
      {[1, 2, 3].map((n) => (
        <div key={n} className="grid grid-cols-[1fr_2fr] gap-2">
          <FieldInput
            label={`Titel ${n}`}
            value={tab[`calTitle${n}` as keyof Tab] as string}
            onChange={(v) => setField(`calTitle${n}` as keyof Tab, v)}
            placeholder={`Dokument ${n}`}
          />
          <FieldInput
            label={`URL ${n}`}
            value={tab[`calUrl${n}` as keyof Tab] as string}
            onChange={(v) => setField(`calUrl${n}` as keyof Tab, v)}
            placeholder="https://v.calameo.com/..."
          />
        </div>
      ))}
    </div>
  );
}

function DownloadFields({ tab, dispatch }: { tab: Tab; dispatch: React.Dispatch<PageAction> }) {
  return (
    <div className="space-y-2">
      {tab.downloads.map((dl) => (
        <div key={dl.id} className="bg-white rounded p-2.5 space-y-2">
          <div className="flex justify-between items-center">
            <span className="text-[11px] text-gray-400">Nedladdning</span>
            <button
              onClick={() => dispatch({ type: 'REMOVE_DOWNLOAD', tabId: tab.id, downloadId: dl.id })}
              className="text-[11px] text-gray-300 hover:text-red-400"
            >
              ✕
            </button>
          </div>
          <FieldInput label="Namn" value={dl.name} onChange={(v) => dispatch({ type: 'SET_DOWNLOAD_FIELD', tabId: tab.id, downloadId: dl.id, field: 'name', value: v })} placeholder="Produktblad FTTO" />
          <FieldInput label="Beskrivning" value={dl.description} onChange={(v) => dispatch({ type: 'SET_DOWNLOAD_FIELD', tabId: tab.id, downloadId: dl.id, field: 'description', value: v })} placeholder="Teknisk specifikation..." />
          <div className="grid grid-cols-2 gap-2">
            <FieldInput label="Fillänk" value={dl.fileUrl} onChange={(v) => dispatch({ type: 'SET_DOWNLOAD_FIELD', tabId: tab.id, downloadId: dl.id, field: 'fileUrl', value: v })} placeholder="https://..." />
            <FieldInput label="Filtyp" value={dl.fileType} onChange={(v) => dispatch({ type: 'SET_DOWNLOAD_FIELD', tabId: tab.id, downloadId: dl.id, field: 'fileType', value: v })} placeholder="PDF" />
          </div>
        </div>
      ))}
      <button
        onClick={() => dispatch({ type: 'ADD_DOWNLOAD', tabId: tab.id })}
        className="w-full py-1.5 text-xs text-gray-300 hover:text-gray-500 transition-colors"
      >
        + Lägg till nedladdning
      </button>
    </div>
  );
}

function CompareFields({ tab, setField, dispatch }: { tab: Tab; setField: (f: keyof Tab, v: unknown) => void; dispatch: React.Dispatch<PageAction> }) {
  return (
    <>
      <FieldInput label="Titel" value={tab.compareTitle} onChange={(v) => setField('compareTitle', v)} placeholder="Jämför lösningar" />
      <div className="grid grid-cols-2 gap-2">
        <FieldInput label="Kolumn A" value={tab.compareColA} onChange={(v) => setField('compareColA', v)} placeholder="FTTO" />
        <FieldInput label="Kolumn B" value={tab.compareColB} onChange={(v) => setField('compareColB', v)} placeholder="Traditionellt" />
      </div>
      {tab.compareRows.length > 0 && (
        <div className="space-y-1.5">
          <div className="grid grid-cols-[1fr_1fr_1fr_24px] gap-1.5 text-[11px] text-gray-400 px-0.5">
            <span>Egenskap</span>
            <span>{tab.compareColA || 'Kolumn A'}</span>
            <span>{tab.compareColB || 'Kolumn B'}</span>
            <span></span>
          </div>
          {tab.compareRows.map((row) => (
            <div key={row.id} className="grid grid-cols-[1fr_1fr_1fr_24px] gap-1.5 items-center">
              <input
                value={row.label}
                onChange={(e) => dispatch({ type: 'SET_COMPARE_ROW_FIELD', tabId: tab.id, rowId: row.id, field: 'label', value: e.target.value })}
                placeholder="Egenskap"
                className="w-full px-2 py-1.5 text-sm rounded bg-gray-100/80 focus:bg-white focus:ring-1 focus:ring-gray-200 focus:outline-none"
              />
              <input
                value={row.valueA}
                onChange={(e) => dispatch({ type: 'SET_COMPARE_ROW_FIELD', tabId: tab.id, rowId: row.id, field: 'valueA', value: e.target.value })}
                placeholder="Värde"
                className="w-full px-2 py-1.5 text-sm rounded bg-gray-100/80 focus:bg-white focus:ring-1 focus:ring-gray-200 focus:outline-none"
              />
              <input
                value={row.valueB}
                onChange={(e) => dispatch({ type: 'SET_COMPARE_ROW_FIELD', tabId: tab.id, rowId: row.id, field: 'valueB', value: e.target.value })}
                placeholder="Värde"
                className="w-full px-2 py-1.5 text-sm rounded bg-gray-100/80 focus:bg-white focus:ring-1 focus:ring-gray-200 focus:outline-none"
              />
              <button
                onClick={() => dispatch({ type: 'REMOVE_COMPARE_ROW', tabId: tab.id, rowId: row.id })}
                className="text-[11px] text-gray-300 hover:text-red-400 text-center"
                title="Ta bort rad"
              >✕</button>
            </div>
          ))}
        </div>
      )}
      <button
        onClick={() => dispatch({ type: 'ADD_COMPARE_ROW', tabId: tab.id })}
        className="w-full py-1.5 text-xs text-gray-300 hover:text-gray-500 transition-colors"
      >
        + Lägg till rad
      </button>
    </>
  );
}

function StepsFields({ tab, setField, dispatch }: { tab: Tab; setField: (f: keyof Tab, v: unknown) => void; dispatch: React.Dispatch<PageAction> }) {
  return (
    <>
      <FieldInput label="Titel" value={tab.stepsTitle} onChange={(v) => setField('stepsTitle', v)} placeholder="Så här kommer du igång" />
      <div className="space-y-2">
        {tab.stepsItems.map((item, i) => (
          <ItemCard key={item.id} index={i} onRemove={() => dispatch({ type: 'REMOVE_STEP_ITEM', tabId: tab.id, itemId: item.id })}>
            <FieldInput
              label="Rubrik"
              value={item.title}
              onChange={(v) => dispatch({ type: 'SET_STEP_ITEM_FIELD', tabId: tab.id, itemId: item.id, field: 'title', value: v })}
              placeholder={`Steg ${i + 1}`}
            />
            <RichTextarea
              label="Beskrivning"
              value={item.description}
              onChange={(v) => dispatch({ type: 'SET_STEP_ITEM_FIELD', tabId: tab.id, itemId: item.id, field: 'description', value: v })}
              rows={4}
              placeholder="Vad händer i detta steg..."
            />
          </ItemCard>
        ))}
        <button
          onClick={() => dispatch({ type: 'ADD_STEP_ITEM', tabId: tab.id })}
          className="w-full py-1.5 text-xs text-gray-300 hover:text-gray-500 transition-colors"
        >
          + Lägg till steg
        </button>
      </div>
    </>
  );
}
