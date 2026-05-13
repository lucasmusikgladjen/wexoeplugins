'use client';

import { ContactFormState } from '@/lib/contact-form-types';

interface Props { state: ContactFormState; }

/**
 * Visual skiss av kontaktformuläret. Reflekterar layout/theme live men är
 * inte pixelidentisk med PHP-renderaren — målet är att redaktören ska se
 * vilka fält som visas och tema-valet.
 */
export default function ContactFormPreview({ state }: Props) {
  const dark = state.theme === 'dark';
  const isSplit = state.layout === 'split';
  return (
    <div className={`p-6 ${dark ? 'bg-gray-900 text-white' : 'bg-gray-50 text-gray-900'}`}>
      <div className={`max-w-4xl mx-auto ${isSplit ? 'grid grid-cols-2 gap-6 items-start' : 'max-w-xl text-center'}`}>
        <div>
          {state.eyebrow && <p className="text-[10px] uppercase tracking-wider opacity-75 mb-2">{state.eyebrow}</p>}
          <h2 className="text-xl font-medium mb-2">{state.title || 'Prata med någon som kan automation'}</h2>
          {state.subtitle && <p className="text-sm opacity-80 mb-3 whitespace-pre-line">{state.subtitle}</p>}

          {state.trustSignals && (
            <ul className="space-y-1 mt-3">
              {state.trustSignals.split('\n').filter(Boolean).slice(0, 3).map((line, i) => {
                const m = line.match(/^\s*\*\*(.+?)\*\*\s*\|?\s*(.*)$/);
                return (
                  <li key={i} className="text-xs flex items-start gap-1.5">
                    <span className="text-orange-400">✓</span>
                    {m ? (<><strong>{m[1]}</strong> <span className="opacity-80">{m[2]}</span></>) : line}
                  </li>
                );
              })}
            </ul>
          )}

          {state.showContactPerson && (
            <div className="mt-4 flex items-center gap-2 text-xs opacity-75">
              <div className="w-8 h-8 rounded-full bg-white/10" />
              <div>
                <div className="text-xs">Kontaktperson</div>
                <div className="text-[10px] opacity-70">visas från SSOT vid render</div>
              </div>
            </div>
          )}
        </div>

        <div className={`p-4 rounded-md ${dark ? 'bg-white/5' : 'bg-white shadow-sm'}`}>
          <div className={`grid ${state.showCompany ? 'grid-cols-2' : 'grid-cols-1'} gap-2 mb-2`}>
            <Field label="Namn *" />
            {state.showCompany && <Field label="Företag *" />}
          </div>
          <div className={`grid ${state.showPhone ? 'grid-cols-2' : 'grid-cols-1'} gap-2 mb-2`}>
            <Field label="E-post *" />
            {state.showPhone && <Field label="Telefon" />}
          </div>
          {state.showDropdown && <Field label={state.dropdownLabel || 'Vad kan vi hjälpa dig med?'} />}
          <Field label={state.messageLabel || 'Berätta mer (valfritt)'} multiline />
          <div className="text-[10px] opacity-60 mt-2">☐ Nyhetsbrev-samtycke</div>
          <button className="mt-3 px-3 py-1.5 rounded-md text-xs font-medium bg-orange-500 text-white">
            {state.ctaText || 'Skicka'}
          </button>
        </div>
      </div>
    </div>
  );
}

function Field({ label, multiline }: { label: string; multiline?: boolean }) {
  return (
    <div className="mb-2">
      <div className="text-[10px] opacity-60 mb-1">{label}</div>
      <div className={`bg-white/10 rounded ${multiline ? 'h-12' : 'h-6'}`} />
    </div>
  );
}
