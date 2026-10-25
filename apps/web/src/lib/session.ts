import { getContext } from 'svelte';
import type { CurrentSession } from './api';

export const sessionKey = Symbol('work-session');
export const workSession = () => getContext<{ readonly current: CurrentSession | null; refresh: () => Promise<void>; clear: () => void }>(sessionKey);

let cachedSession: CurrentSession | null = null;
let cachedUnreadCount: number | null = null;

export function getCachedSession(): CurrentSession | null {
	return cachedSession;
}

export function setCachedSession(session: CurrentSession | null): void {
	cachedSession = session;
	if (!session) cachedUnreadCount = null;
}

export function getCachedUnreadCount(): number | null {
	return cachedUnreadCount;
}

export function setCachedUnreadCount(count: number | null): void {
	cachedUnreadCount = count;
}
