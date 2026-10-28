import { redirect } from '@sveltejs/kit';
import { getEntrySession } from '../lib/server/entry-session.ts';
import type { PageServerLoad } from './$types';

export const load: PageServerLoad = async (event) => {
 const hadSession = !!event.cookies.get('paspapan_session');
 if (await getEntrySession(event)) redirect(303, '/app/today');
 redirect(303, hadSession ? '/login?reason=expired' : '/login');
};
