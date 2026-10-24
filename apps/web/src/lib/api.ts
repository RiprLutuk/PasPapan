export type CurrentSession = {
	user: { id: string; full_name: string; email: string };
	company: { id: string; name: string; role: string };
};

export type TodayAttendance = {
	date: string;
	status: 'checked_in' | 'checked_out' | 'not_checked_in';
	shift_name: string | null;
	starts_at: string | null;
	ends_at: string | null;
};

export type Employee = { id: string; full_name: string; email: string; role: string; employee_code: string | null };
export type Shift = { id: string; name: string; starts_at: string; ends_at: string; crosses_midnight: boolean };

export class ApiError extends Error {
	constructor(public status: number, message: string) { super(message); this.name = 'ApiError'; }
}

export async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
	const response = await fetch(path, {
		credentials: 'same-origin',
		headers: { 'content-type': 'application/json', ...options.headers },
		...options
	});
	if (!response.ok) {
		const body = (await response.json().catch(() => ({}))) as { error?: string };
		throw new ApiError(response.status, body.error ?? 'Permintaan tidak dapat diproses.');
	}
	if (response.status === 204) return undefined as T;
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

export function checkOut(latitude: number, longitude: number): Promise<TodayAttendance> {
	return request<TodayAttendance>('/v1/attendance/check-out', {
		method: 'POST',
		body: JSON.stringify({ latitude, longitude })
	});
}

export const employees = () => request<Employee[]>('/v1/admin/employees');
export const shifts = () => request<Shift[]>('/v1/admin/shifts');
export const createShift = (body: Omit<Shift, 'id'>) => request<Shift>('/v1/admin/shifts', { method: 'POST', body: JSON.stringify(body) });
export const assignShift = (body: { user_id: string; shift_id: string; work_date: string }) => request<void>('/v1/admin/shift-assignments', { method: 'POST', body: JSON.stringify(body) });
