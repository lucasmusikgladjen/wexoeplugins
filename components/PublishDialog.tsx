'use client';

import { useState } from 'react';
import { PageState } from '@/lib/types';

interface Props {
  state: PageState;
  onClose: () => void;
}

type PublishStatus = 'idle' | 'validating' | 'publishing' | 'success' | 'error';

interface TokenUsage {
  inputTokens: number;
  outputTokens: number;
  cacheWriteTokens: number;
  cacheReadTokens: number;
  totalTokens: number;
  estimatedCostUsd: number;
}

interface PublishResult {
  recordId?: string;
  slug?: string;
  tabCount?: number;
  tokenUsage?: TokenUsage;
  error?: string;
}

export default function PublishDialog({ state, onClose }: Props) {
  const [status, setStatus] = useState<PublishStatus>('idle');
  const [step, setStep] = useState('');
  const [result, setResult] = useState<PublishResult>({});

  const validationErrors = validate(state);
  const hasErrors = validationErrors.length > 0;

  async function handlePublish() {
    if (hasErrors) return;

    setStatus('publishing');
    setStep('Förbereder data...');

    try {
      setStep('Transformerar och publicerar...');

      const response = await fetch('/api/publish', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(state),
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.error || 'Publicering misslyckades');
      }

      setResult({
        recordId: data.recordId,
        slug: data.slug || state.slug,
        tabCount: data.tabCount || state.tabs.length,
        tokenUsage: data.tokenUsage,
      });
      setStatus('success');
    } catch (err) {
      setResult({ error: err instanceof Error ? err.message : 'Okänt fel' });
      setStatus('error');
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50" onClick={onClose}>
      <div
        className="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Header */}
        <div className="px-6 py-4 border-b border-lp-border flex items-center justify-between">
          <h2 className="text-lg font-bold text-lp-main">
            {status === 'success' ? 'Publicerad!' : 'Publicera sida'}
          </h2>
          <button onClick={onClose} className="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>

        {/* Body */}
        <div className="px-6 py-5">
          {status === 'idle' && (
            <>
              {/* Summary */}
              <div className="mb-4 space-y-2 text-sm">
                <div className="flex justify-between">
                  <span className="text-lp-text-light">Slug:</span>
                  <span className="font-medium">{state.slug || '—'}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-lp-text-light">Rubrik:</span>
                  <span className="font-medium truncate max-w-[250px]">{state.h1 || '—'}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-lp-text-light">Tabs:</span>
                  <span className="font-medium">{state.tabs.length}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-lp-text-light">Sidebar:</span>
                  <span className="font-medium">{state.sidebarType || 'Ingen'}</span>
                </div>
              </div>

              {/* Validation */}
              {hasErrors && (
                <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                  <p className="text-sm font-medium text-red-800 mb-1">Åtgärda innan publicering:</p>
                  <ul className="text-sm text-red-700 space-y-0.5">
                    {validationErrors.map((err, i) => (
                      <li key={i}>• {err}</li>
                    ))}
                  </ul>
                </div>
              )}
            </>
          )}

          {status === 'publishing' && (
            <div className="text-center py-6">
              <div className="inline-block w-8 h-8 border-3 border-lp-main border-t-transparent rounded-full animate-spin mb-3" />
              <p className="text-sm text-lp-text">{step}</p>
              <p className="text-xs text-lp-text-light mt-1">Detta kan ta 10-20 sekunder...</p>
            </div>
          )}

          {status === 'success' && (
            <div className="py-4">
              <div className="flex flex-col items-center mb-4">
                <div className="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center mb-3">
                  <span className="text-green-600 text-xl">✓</span>
                </div>
                <p className="text-sm font-medium text-lp-text mb-1">Sidan har publicerats!</p>
                <p className="text-xs text-lp-text-light">
                  Slug: <strong>{result.slug}</strong> — {result.tabCount} tabs skapade
                </p>
                <p className="text-xs text-lp-text-light mt-2">
                  Sidan visas på wexoe.se inom 5 minuter (Airtable-cache).
                </p>
                {result.recordId && (
                  <p className="text-xs text-gray-400 mt-1">Record ID: {result.recordId}</p>
                )}
              </div>

              {result.tokenUsage && (
                <div className="mt-3 border border-gray-200 rounded-lg overflow-hidden">
                  <div className="bg-gray-50 px-3 py-2 border-b border-gray-200">
                    <p className="text-xs font-semibold text-gray-600 uppercase tracking-wide">Token-användning</p>
                  </div>
                  <div className="px-3 py-2 space-y-1.5 text-xs text-gray-700">
                    <div className="flex justify-between">
                      <span className="text-gray-500">Input-tokens</span>
                      <span className="font-mono">{result.tokenUsage.inputTokens.toLocaleString('sv')}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-500">Output-tokens</span>
                      <span className="font-mono">{result.tokenUsage.outputTokens.toLocaleString('sv')}</span>
                    </div>
                    {result.tokenUsage.cacheReadTokens > 0 && (
                      <div className="flex justify-between">
                        <span className="text-gray-500">Cache-lästa tokens</span>
                        <span className="font-mono">{result.tokenUsage.cacheReadTokens.toLocaleString('sv')}</span>
                      </div>
                    )}
                    <div className="flex justify-between border-t border-gray-100 pt-1.5">
                      <span className="font-medium text-gray-600">Totalt</span>
                      <span className="font-mono font-medium">{result.tokenUsage.totalTokens.toLocaleString('sv')}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="font-medium text-gray-600">Uppskattad kostnad</span>
                      <span className="font-mono font-semibold text-gray-800">
                        ${result.tokenUsage.estimatedCostUsd.toFixed(4)}
                      </span>
                    </div>
                  </div>
                </div>
              )}
            </div>
          )}

          {status === 'error' && (
            <div className="text-center py-4">
              <div className="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-3">
                <span className="text-red-600 text-xl">!</span>
              </div>
              <p className="text-sm font-medium text-red-800 mb-1">Publicering misslyckades</p>
              <p className="text-xs text-red-600">{result.error}</p>
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="px-6 py-3 border-t border-lp-border flex justify-end gap-3">
          {status === 'idle' && (
            <>
              <button onClick={onClose} className="px-4 py-2 text-sm text-lp-text-light hover:text-lp-text">
                Avbryt
              </button>
              <button
                onClick={handlePublish}
                disabled={hasErrors}
                className="px-5 py-2 rounded-lg text-sm font-semibold text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                style={{ background: hasErrors ? '#9ca3af' : '#11325D' }}
              >
                Publicera →
              </button>
            </>
          )}
          {(status === 'success' || status === 'error') && (
            <button
              onClick={onClose}
              className="px-5 py-2 rounded-lg text-sm font-semibold text-white"
              style={{ background: '#11325D' }}
            >
              Stäng
            </button>
          )}
        </div>
      </div>
    </div>
  );
}

function validate(state: PageState): string[] {
  const errors: string[] = [];
  if (!state.slug.trim()) errors.push('Slug är obligatoriskt');
  if (!/^[a-z0-9-]+$/.test(state.slug) && state.slug.trim()) errors.push('Slug får bara innehålla a-z, 0-9 och bindestreck');
  if (!state.h1.trim()) errors.push('H1 (rubrik) är obligatoriskt');
  return errors;
}
