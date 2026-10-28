import { error, type RequestEvent } from '@sveltejs/kit';
import type { CurrentSession } from '../api';

type EntryEvent = Pick<RequestEvent, 'cookies' | 'fetch'>;

/** Check before rendering a public entry page; never cache user identity in a server module. */
export async function getEntrySession({ cookies, fetch }: EntryEvent): Promise<CurrentSession | null> {
 const token = cookies.get('paspapan_session');
 if (!token) return null;
 if (!/^[a-f0-9]{64}$/i.test(token)) {
  cookies.delete('paspapan_session', { path: '/' });
  return null;
 }
 let response: Response;
 try {
  // SvelteKit's scoped fetch forwards this request's cookie through the existing API proxy.
  response = await fetch('/v1/auth/session', { cache: 'no-store', signal: AbortSignal.timeout(8000) });
 } catch {
  error(503, 'Session service unavailable');
 }
 if (response.status === 401) {
  cookies.delete('paspapan_session', { path: '/' });
  return null;
 }
 // A backend outage must not masquerade as a signed-out user or expose the login form.
 if (!response.ok) error(503, 'Session service unavailable');
 try {
  const session = await response.json();
  if (!session?.user?.id || !session?.company?.id || !Array.isArray(session.capabilities)) {
   error(503, 'Invalid session response');
  }
  return session as CurrentSession;
 } catch {
  error(503, 'Session service unavailable');
 }
}
