export type CurrentSession = {
	user: { id: string; full_name: string; email: string };
	company: { id: string; name: string; role: string };
};

export type TodayAttendance = {
	date: string;
	status: 'checked_in' | 'not_checked_in';
	shift_name: string | null;
	starts_at: string | null;
	ends_at: string | null;
};

export async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
	const response = await fetch(path, {
		credentials: 'same-origin',
		headers: { 'content-type': 'application/json', ...options.headers },
		...options
	});
	if (!response.ok) {
		const body = (await response.json().catch(() => ({}))) as { error?: string };
		throw new Error(body.error ?? 'Permintaan tidak dapat diproses.');
	}
	return response.json() as Promise<T>;
}

export function currentSession(): Promise<CurrentSession> {
	return request<CurrentSession>('/v1/auth/session');
}

export function todayAttendance(): Promise<TodayAttendance> {
	return request<TodayAttendance>('/v1/attendance/today');
}

export function checkIn(latitude: number, longitude: number): Promise<TodayAttendance> {
	return request<TodayAttendance>('/v1/attendance/check-in', {
		method: 'POST',
		body: JSON.stringify({ latitude, longitude })
	});
}
