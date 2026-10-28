import { redirect } from '@sveltejs/kit';
import { getEntrySession } from '../../lib/server/entry-session.ts';
import { safeReturnPath } from '../../lib/session-policy.ts';
import type { PageServerLoad } from './$types';

export const load: PageServerLoad = async (event) => {
 if (await getEntrySession(event)) redirect(303, safeReturnPath(event.url.searchParams.get('next')));
 return {};
};
