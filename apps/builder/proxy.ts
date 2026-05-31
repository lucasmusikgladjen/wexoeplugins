import { NextRequest, NextResponse } from 'next/server';
import { COOKIE_NAME, isValidSession } from './lib/auth';

const PUBLIC_PATHS = new Set(['/login', '/api/login']);

export async function proxy(req: NextRequest) {
  const { pathname } = req.nextUrl;

  if (PUBLIC_PATHS.has(pathname)) {
    return NextResponse.next();
  }

  const password = process.env.PASSWORD;
  const token = req.cookies.get(COOKIE_NAME)?.value;
  const valid = await isValidSession(token, password);

  if (valid) {
    return NextResponse.next();
  }

  // API calls get a JSON 401 so client code can handle it. Everything else
  // (page loads, RSC navigations) gets redirected to /login with a
  // redirect-back param so the user lands where they were headed.
  if (pathname.startsWith('/api/')) {
    return NextResponse.json({ error: 'Not authenticated' }, { status: 401 });
  }

  const loginUrl = new URL('/login', req.url);
  if (pathname !== '/') {
    loginUrl.searchParams.set('redirect', pathname + (req.nextUrl.search || ''));
  }
  return NextResponse.redirect(loginUrl);
}

export const config = {
  // Run on every path except Next.js internals, the favicon, and common
  // static asset extensions. The proxy itself allowlists /login and
  // /api/login.
  matcher: [
    '/((?!_next/static|_next/image|_next/data|favicon\\.ico|.*\\.(?:svg|png|jpg|jpeg|gif|webp|ico|txt|xml|js\\.map|css\\.map)$).*)',
  ],
};
