export function notificationCount(value: unknown): number | null {
	return typeof value === 'number' && Number.isFinite(value) && value >= 0
		? Math.floor(value) : null;
}

export function formatClock(value?: string | null): string {
	return value && /^([01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/.test(value) ? value.slice(0, 5) : '—';
}
