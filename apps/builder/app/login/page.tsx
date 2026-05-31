'use client';

import { Suspense, useState } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';

export default function LoginPage() {
  return (
    <Suspense fallback={<LoginShell />}>
      <LoginForm />
    </Suspense>
  );
}

function LoginShell({ children }: { children?: React.ReactNode }) {
  return (
    <div
      className="min-h-screen bg-white flex items-center justify-center px-6"
      style={{ fontFamily: 'var(--font-dm-sans)' }}
    >
      <div className="w-full max-w-sm">
        <div className="mb-10 text-center">
          <h1 className="text-2xl font-medium tracking-tight text-gray-900">Wexoe Page Builder</h1>
          <p className="text-sm text-gray-400 mt-1">Logga in för att fortsätta</p>
        </div>
        {children}
      </div>
    </div>
  );
}

function LoginForm() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const redirectParam = searchParams.get('redirect');
  // Only allow in-app redirects (must start with "/" and not "//") to
  // prevent open-redirect abuse via the ?redirect= query param.
  const redirect =
    redirectParam && redirectParam.startsWith('/') && !redirectParam.startsWith('//')
      ? redirectParam
      : '/';

  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (busy) return;
    setBusy(true);
    setError(null);
    try {
      const res = await fetch('/api/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ password }),
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) {
        throw new Error(data.error || 'Inloggning misslyckades.');
      }
      router.replace(redirect);
      router.refresh();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Okänt fel');
      setPassword('');
      setBusy(false);
    }
  }

  return (
    <LoginShell>
      <form onSubmit={handleSubmit} className="space-y-4">
        <label className="block">
          <span className="text-[11px] text-gray-400">Lösenord</span>
          <input
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            autoFocus
            autoComplete="current-password"
            disabled={busy}
            className="mt-0.5 block w-full rounded bg-gray-100/80 px-3 py-2 text-sm text-gray-700 placeholder:text-gray-300 focus:bg-white focus:ring-1 focus:ring-gray-200 focus:outline-none disabled:opacity-60"
          />
        </label>

        {error && <p className="text-xs text-red-500">{error}</p>}

        <button
          type="submit"
          disabled={busy || password.length === 0}
          className="w-full px-4 py-2 rounded-md text-sm font-medium text-white transition-opacity hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed"
          style={{ background: '#11325D' }}
        >
          {busy ? 'Loggar in…' : 'Logga in'}
        </button>
      </form>
    </LoginShell>
  );
}
