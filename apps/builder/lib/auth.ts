// Session cookie helpers. The cookie value is an HMAC of a fixed message
// using the current PASSWORD env var as the key, so:
// - there is no extra secret to configure
// - rotating PASSWORD automatically invalidates every existing session
// - the cookie cannot be forged without knowing PASSWORD

export const COOKIE_NAME = 'wpb_auth';
export const COOKIE_MAX_AGE = 60 * 60 * 24 * 7; // 7 days

async function hmacHex(message: string, secret: string): Promise<string> {
  const enc = new TextEncoder();
  const key = await crypto.subtle.importKey(
    'raw',
    enc.encode(secret),
    { name: 'HMAC', hash: 'SHA-256' },
    false,
    ['sign'],
  );
  const sig = await crypto.subtle.sign('HMAC', key, enc.encode(message));
  const bytes = new Uint8Array(sig);
  let out = '';
  for (let i = 0; i < bytes.length; i++) {
    out += bytes[i].toString(16).padStart(2, '0');
  }
  return out;
}

export async function createSessionToken(password: string): Promise<string> {
  return hmacHex('wpb-authenticated-v1', password);
}

export function constantTimeEqual(a: string, b: string): boolean {
  if (a.length !== b.length) return false;
  let result = 0;
  for (let i = 0; i < a.length; i++) {
    result |= a.charCodeAt(i) ^ b.charCodeAt(i);
  }
  return result === 0;
}

export async function isValidSession(
  token: string | undefined,
  password: string | undefined,
): Promise<boolean> {
  if (!token || !password) return false;
  const expected = await createSessionToken(password);
  return constantTimeEqual(token, expected);
}
