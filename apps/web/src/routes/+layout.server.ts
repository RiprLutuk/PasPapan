import { error, redirect } from '@sveltejs/kit';
import { getEntrySession } from '../lib/server/entry-session.ts';
import { requiredPermission } from '../lib/access.ts';
import { isLegacyRoute } from '../lib/legacy-features.ts';
import { legacyDestination } from '../lib/legacy-destinations.ts';
import type { LayoutServerLoad } from './$types';

const publicPages = new Set(['/', '/login', '/register', '/forgot-password', '/reset-password', '/two-factor-challenge']);
const catchallRoutes = new Set(['/[...path]', '/app/[...path]', '/admin/[...path]']);

export const load: LayoutServerLoad = async (event) => {
 const path = event.url.pathname.replace(/\/+$/, '') || '/';
 if (!event.route.id || publicPages.has(path)) return { authSession: null };
 const destination = legacyDestination(path);
 // Leave unknown paths to their 404 loader, including signed-out requests.
 if (catchallRoutes.has(event.route.id) && !isLegacyRoute(path) && !destination) return { authSession: null };
 if (event.route.id === '/admin/employees/[id]' && !/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(event.params.id ?? '')) return { authSession: null };
 // Resolve old entry URLs on the server, without showing a redirect screen.
 if (destination) {
  const target = new URL(destination.destination, event.url);
  for (const [key, value] of event.url.searchParams) if (!target.searchParams.has(key)) target.searchParams.append(key, value);
  redirect(303, target.pathname + target.search);
 }
 const hadSession = !!event.cookies.get('paspapan_session');
 const authSession = await getEntrySession(event);
 if (!authSession) {
  const query = new URLSearchParams({ next: path + event.url.search });
  if (hadSession) query.set('reason', 'expired');
  redirect(303, '/login?' + query);
 }
 const permission = requiredPermission(path, path === '/admin' || path.startsWith('/admin/'));
 if (permission && !authSession.capabilities.includes(permission)) error(403, 'Permission denied');
 return { authSession };
};
