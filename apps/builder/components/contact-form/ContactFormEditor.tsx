'use client';

import { ContactFormState, ContactFormLayout, ContactFormTheme } from '@/lib/contact-form-types';

interface Props {
  state: ContactFormState;
  onChange: (s: ContactFormState) => void;
}

/**
 * Delad ContactFormEditor — neutral mot page-typ.
 *
 * Används av LP/PA/Audience-editorerna via tunna wrappers, och kan
 * paras med CmsPage:s contact_form-sektionstyp. Renderar inga sektion-
 * headers — det är upp till föräldern att wrappa i CollapsibleSection
 * eller motsvarande.
 */
export default function ContactFormEditor({ state, onChange }: Props) {
  const set = <K extends keyof ContactFormState>(k: K, v: ContactFormState[K]) =>
    onChange({ ...state, [k]: v });

  return (
    <div className="space-y-3">
      <Row label="Eyebrow">
        <Text value={state.eyebrow} onChange={(v) => set('eyebrow', v)} />
      </Row>
      <Row label="Titel" help="Tom = default 'Prata med någon som kan automation'.">
        <Text value={state.title} onChange={(v) => set('title', v)} />
      </Row>
      <Row label="Subtitel">
        <TextArea value={state.subtitle} onChange={(v) => set('subtitle', v)} rows={3} />
      </Row>

      <div className="grid grid-cols-2 gap-3">
        <Row label="Layout">
          <Select
            value={state.layout}
            onChange={(v) => set('layout', v as ContactFormLayout)}
            options={[{ value: 'split', label: 'Split (2 kolumner)' }, { value: 'centered', label: 'Centrerat' }]}
          />
        </Row>
        <Row label="Tema">
          <Select
            value={state.theme}
            onChange={(v) => set('theme', v as ContactFormTheme)}
            options={[{ value: 'dark', label: 'Mörkt' }, { value: 'light', label: 'Ljust' }]}
          />
        </Row>
      </div>

      <fieldset className="border border-gray-100 rounded-md p-3 space-y-2">
        <legend className="text-[10px] uppercase tracking-wider text-gray-400 px-1">Visa fält</legend>
        <Check label="Företag" checked={state.showCompany} onChange={(v) => set('showCompany', v)} />
        <Check label="Telefon" checked={state.showPhone} onChange={(v) => set('showPhone', v)} />
        <Check label="Behov-dropdown" checked={state.showDropdown} onChange={(v) => set('showDropdown', v)} />
        <Check label="Kontaktperson" checked={state.showContactPerson} onChange={(v) => set('showContactPerson', v)} />
      </fieldset>

      {state.showDropdown && (
        <>
          <Row label="Dropdown-label" help="Tom = default 'Vad kan vi hjälpa dig med?'">
            <Text value={state.dropdownLabel} onChange={(v) => set('dropdownLabel', v)} />
          </Row>
          <Row label="Dropdown-alternativ" help="En per rad. Tom = PHP-defaults.">
            <TextArea
              value={state.options}
              onChange={(v) => set('options', v)}
              rows={6}
              placeholder={'Generell fråga\nDiskutera ett projekt\nLägga en order'}
            />
          </Row>
        </>
      )}

      <Row label="CTA-text" help="Default 'Skicka'.">
        <Text value={state.ctaText} onChange={(v) => set('ctaText', v)} />
      </Row>
      <Row label="Meddelande-label" help="Default 'Berätta mer (valfritt)'.">
        <Text value={state.messageLabel} onChange={(v) => set('messageLabel', v)} />
      </Row>
      <Row label="Trust signals" help="Max 3 rader. Format: **Bold** | Resten">
        <TextArea
          value={state.trustSignals}
          onChange={(v) => set('trustSignals', v)}
          rows={4}
          placeholder={'**Svar** | inom 4 timmar på vardagar\n**Inget köptryck** | bara konkret hjälp'}
        />
      </Row>
    </div>
  );
}

function Row({ label, help, children }: { label: string; help?: string; children: React.ReactNode }) {
  return (
    <div>
      <label className="block text-xs font-medium text-gray-600 mb-1">{label}</label>
      {children}
      {help && <p className="mt-1 text-[11px] text-gray-400">{help}</p>}
    </div>
  );
}

function Text({ value, onChange, placeholder }: { value: string; onChange: (v: string) => void; placeholder?: string }) {
  return (
    <input
      type="text"
      value={value}
      onChange={(e) => onChange(e.target.value)}
      placeholder={placeholder}
      className="w-full px-2 py-1.5 text-sm border border-gray-200 rounded-md focus:border-gray-400 focus:outline-none"
    />
  );
}

function TextArea({ value, onChange, rows, placeholder }: { value: string; onChange: (v: string) => void; rows: number; placeholder?: string }) {
  return (
    <textarea
      value={value}
      onChange={(e) => onChange(e.target.value)}
      rows={rows}
      placeholder={placeholder}
      className="w-full px-2 py-1.5 text-sm border border-gray-200 rounded-md focus:border-gray-400 focus:outline-none"
    />
  );
}

function Select({ value, onChange, options }: { value: string; onChange: (v: string) => void; options: Array<{ value: string; label: string }> }) {
  return (
    <select
      value={value}
      onChange={(e) => onChange(e.target.value)}
      className="w-full px-2 py-1.5 text-sm border border-gray-200 rounded-md focus:border-gray-400 focus:outline-none"
    >
      {options.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
    </select>
  );
}

function Check({ checked, onChange, label }: { checked: boolean; onChange: (v: boolean) => void; label: string }) {
  return (
    <label className="flex items-center gap-2 text-xs text-gray-700">
      <input type="checkbox" checked={checked} onChange={(e) => onChange(e.target.checked)} className="h-4 w-4" />
      {label}
    </label>
  );
}
