import { NextRequest, NextResponse } from 'next/server';
import {
  COOKIE_MAX_AGE,
  COOKIE_NAME,
  constantTimeEqual,
  createSessionToken,
} from '@/lib/auth';

// Use the Node.js runtime so the in-memory rate-limit map persists across
// requests that hit the same serverless instance. Edge runtime instances are
// shorter-lived and more fragmented, so the map would reset too often.
export const runtime = 'nodejs';

// Anti-brute-force: track failed attempts per IP. After MAX_ATTEMPTS failures
// inside WINDOW_MS, lock the IP out for LOCKOUT_MS. A constant minimum delay
// on every attempt limits the raw attempt rate regardless of lockout state.
//
// This is intentionally simple: it lives in-memory on the serverless
// instance. An attacker that spreads requests across many cold instances
// could bypass the counter, but the MIN_DELAY_MS still caps their throughput
// per request, and the HMAC-signed session cookie means only the real
// password ever grants access.
interface RateLimitEntry {
  attempts: number;
  windowStart: number;
  lockedUntil: number;
}

const rateLimitMap = new Map<string, RateLimitEntry>();

const MAX_ATTEMPTS = 5;
const WINDOW_MS = 15 * 60 * 1000; // 15 minutes
const LOCKOUT_MS = 15 * 60 * 1000; // 15 minutes
const MIN_DELAY_MS = 400;

// Soft cap so a flood of unique IPs can't grow the map without bound.
const MAX_MAP_SIZE = 10_000;

function getClientIp(req: NextRequest): string {
  const fwd = req.headers.get('x-forwarded-for');
  if (fwd) {
    const first = fwd.split(',')[0]?.trim();
    if (first) return first;
  }
  return req.headers.get('x-real-ip') || 'unknown';
}

function checkRateLimit(ip: string): { allowed: boolean; retryAfterSec: number } {
  const now = Date.now();
  const entry = rateLimitMap.get(ip);
  if (!entry) return { allowed: true, retryAfterSec: 0 };

  if (entry.lockedUntil > now) {
    return { allowed: false, retryAfterSec: Math.ceil((entry.lockedUntil - now) / 1000) };
  }

  if (now - entry.windowStart > WINDOW_MS) {
    rateLimitMap.delete(ip);
  }
  return { allowed: true, retryAfterSec: 0 };
}

function recordFailure(ip: string): number {
  const now = Date.now();

  if (rateLimitMap.size > MAX_MAP_SIZE) {
    // Drop the oldest entry to keep the map bounded.
    const oldestKey = rateLimitMap.keys().next().value;
    if (oldestKey !== undefined) rateLimitMap.delete(oldestKey);
  }

  const entry = rateLimitMap.get(ip);
  if (!entry || now - entry.windowStart > WINDOW_MS) {
    rateLimitMap.set(ip, { attempts: 1, windowStart: now, lockedUntil: 0 });
    return MAX_ATTEMPTS - 1;
  }

  entry.attempts += 1;
  if (entry.attempts >= MAX_ATTEMPTS) {
    entry.lockedUntil = now + LOCKOUT_MS;
    return 0;
  }
  return MAX_ATTEMPTS - entry.attempts;
}

function clearRateLimit(ip: string): void {
  rateLimitMap.delete(ip);
}

export async function POST(req: NextRequest) {
  const ip = getClientIp(req);

  const rl = checkRateLimit(ip);
  if (!rl.allowed) {
    const minutes = Math.max(1, Math.ceil(rl.retryAfterSec / 60));
    return NextResponse.json(
      { error: `För många försök. Försök igen om ${minutes} minut${minutes === 1 ? '' : 'er'}.` },
      { status: 429, headers: { 'Retry-After': String(rl.retryAfterSec) } },
    );
  }

  // Constant minimum delay on every attempt (success or failure) — caps the
  // raw attempt rate and reduces timing-based discrimination between failure
  // modes.
  await new Promise((resolve) => setTimeout(resolve, MIN_DELAY_MS));

  const password = process.env.PASSWORD;
  if (!password) {
    return NextResponse.json(
      { error: 'PASSWORD-miljövariabeln är inte konfigurerad.' },
      { status: 500 },
    );
  }

  let body: unknown;
  try {
    body = await req.json();
  } catch {
    recordFailure(ip);
    return NextResponse.json({ error: 'Ogiltig begäran.' }, { status: 400 });
  }

  const submitted =
    body && typeof body === 'object' && 'password' in body && typeof (body as { password: unknown }).password === 'string'
      ? (body as { password: string }).password
      : '';

  if (!constantTimeEqual(submitted, password)) {
    const remaining = recordFailure(ip);
    return NextResponse.json(
      {
        error:
          remaining > 0
            ? `Fel lösenord. ${remaining} försök kvar.`
            : 'Fel lösenord. Ditt konto är låst i 15 minuter.',
      },
      { status: 401 },
    );
  }

  clearRateLimit(ip);

  const token = await createSessionToken(password);
  const res = NextResponse.json({ ok: true });
  res.cookies.set({
    name: COOKIE_NAME,
    value: token,
    httpOnly: true,
    secure: process.env.NODE_ENV === 'production',
    sameSite: 'lax',
    path: '/',
    maxAge: COOKIE_MAX_AGE,
  });
  return res;
}
