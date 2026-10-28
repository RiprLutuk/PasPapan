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

export type SessionWarning = { kind: 'idle' | 'absolute'; countdown: string };
export function sessionWarning(lifetime: Deadlines, receivedAt: number, now = Date.now()): SessionWarning | null {
 const remaining = sessionRemaining(lifetime, receivedAt, now);
 if (remaining <= 0 || remaining > 120) return null;
 return {
  kind: lifetime.idle_expires_at < lifetime.expires_at ? 'idle' : 'absolute',
  countdown: `${Math.floor(remaining / 60)}:${String(remaining % 60).padStart(2, '0')}`
 };
}
