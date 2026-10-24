import { getContext } from 'svelte';
import type { CurrentSession } from './api';

export const sessionKey = Symbol('work-session');
export const workSession = () => getContext<{ readonly current: CurrentSession | null }>(sessionKey);
