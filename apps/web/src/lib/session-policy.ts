type Deadlines = { expires_at: number; idle_expires_at: number; server_time: number };

export function sessionRemaining(lifetime: Deadlines, receivedAt: number, now = Date.now()): number {
 const elapsed = Math.max(0, (now - receivedAt) / 1000);
 const deadline = Math.min(lifetime.expires_at, lifetime.idle_expires_at);
 return Math.max(0, Math.ceil(deadline - lifetime.server_time - elapsed));
}

export function safeReturnPath(value: string | null): string {
 const fallback = '/app/today';
 if (!value || !value.startsWith('/') || value.startsWith('//') || value.includes('\\') || /[\u0000-\u001f]/.test(value)) return fallback;
 try {
  const url = new URL(value, 'https://paspapan.invalid');
  const path = url.pathname.replace(/\/+$/, '') || '/';
  const publicAuthPaths = ['/login', '/register', '/forgot-password', '/reset-password'];
  return url.origin === 'https://paspapan.invalid' && !publicAuthPaths.includes(path)
   ? url.pathname + url.search
   : fallback;
 } catch {
  return fallback;
 }
}
