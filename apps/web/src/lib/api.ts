export type SessionLifetime = { id:string; expires_at:number; idle_expires_at:number; idle_timeout_seconds:number; server_time:number };
export type CurrentSession = {
    lifetime?: SessionLifetime;
    capabilities: string[];
	user: { id: string; full_name: string; email: string };
	company: { id: string; name: string; role: string };
};

export type TodayAttendance = {
    work_mode?: 'onsite'|'wfh'|null;
	date: string;
	status: 'checked_in' | 'checked_out' | 'not_checked_in';
	check_in_time: string | null;
	check_out_time: string | null;
	shift_name: string | null;
	starts_at: string | null;
	ends_at: string | null;
};

export type Employee = { id: string; full_name: string; email: string; role: string; employee_code: string | null };
export type Shift = { id: string; name: string; starts_at: string; ends_at: string; crosses_midnight: boolean };
export type ShiftAssignment = {
	id: string;
	user_id: string;
	shift_id: string;
	shift_name: string;
	starts_at: string;
	ends_at: string;
	crosses_midnight: boolean;
	work_date: string;
};
export type AttendanceLocation = {
	id: string;
	name: string;
	qr_token: string;
	qr_expires_at: string;
	latitude: number;
	longitude: number;
	radius_meters: number;
	active: boolean;
};

export type StoredFile = {
	id: string;
	object_key: string;
	file_name: string;
	content_type: string;
	byte_size: number;
	uploaded_by?: string;
	driver: string;
	download_url: string;
	created_at?: string;
};

export type PgStatSession = {
	pid: number;
	usename: string;
	application_name: string;
	state: string;
	wait_event_type: string;
	state_duration_ms: number;
	query_preview: string;
};

export type SystemHealthTelemetry = {
	postgres: {
		server_version: string;
		max_connections: number;
		database_size_bytes: number;
		database_size_pretty: string;
		activity: {
			total_connections: number;
			active_queries: number;
			idle_connections: number;
			idle_in_transaction: number;
			waiting_locks: number;
			longest_query_ms: number;
		};
		sessions: PgStatSession[];
		database_stats: {
			xact_commit: number;
			xact_rollback: number;
			blks_read: number;
			blks_hit: number;
			cache_hit_ratio_pct: number;
			deadlocks: number;
			temp_bytes: number;
			tup_returned: number;
			tup_inserted: number;
			tup_updated: number;
			tup_deleted: number;
		};
		extensions: { name: string; version: string }[];
	};
	sqlx_pool: {
		size: number;
		idle: number;
		active: number;
	};
	storage: {
		driver: string;
		bucket: string;
		region: string;
		endpoint: string;
		force_path_style: boolean;
	};
	mailer?: {
		driver: string;
		from_name: string;
		from_address: string;
		spooled_messages: number;
	};
	tenant: {
		company_id: string;
		stored_files_count: number;
		stored_files_bytes: number;
		audit_events_count: number;
		active_sessions_count: number;
	};
};

export class ApiError extends Error {
	status: number;
    constructor(status: number, message: string) { super(message); this.status = status; this.name = 'ApiError'; }
}

export async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
	const response = await fetch(path, {
		credentials: 'include',
		signal: AbortSignal.timeout(15000),
		headers: { 'content-type': 'application/json', ...options.headers },
		...options
	});
	if (!response.ok) {
		const body = (await response.json().catch(() => ({}))) as { error?: string };
		if (response.status === 401 && body.error === 'session expired or revoked' && path !== '/v1/auth/session' && typeof window !== 'undefined') window.dispatchEvent(new Event('paspapan:session-expired'));
		throw new ApiError(response.status, body.error ?? 'Permintaan tidak dapat diproses.');
	}
	if (path === '/v1/auth/logout' && typeof window !== 'undefined') window.dispatchEvent(new Event('paspapan:logout'));
	if (response.status === 204) return undefined as T;
	const body = await response.text();
	return body.trim() ? JSON.parse(body) as T : undefined as T;
}

export function currentSession(): Promise<CurrentSession> {
	return request<CurrentSession>('/v1/auth/session');
}

export function todayAttendance(): Promise<TodayAttendance> {
	return request<TodayAttendance>('/v1/attendance/today');
}

export function checkIn(latitude: number, longitude: number, qr_token: string, mode='onsite'): Promise<TodayAttendance> {
	return request<TodayAttendance>('/v1/attendance/check-in', {
		method: 'POST',
		body: JSON.stringify({ latitude, longitude, qr_token, mode })
	});
}

export function checkOut(latitude: number, longitude: number, qr_token: string, mode='onsite'): Promise<TodayAttendance> {
	return request<TodayAttendance>('/v1/attendance/check-out', {
		method: 'POST',
		body: JSON.stringify({ latitude, longitude, qr_token, mode })
	});
}

export const employees = () => request<Employee[]>('/v1/admin/employees');
export const shifts = () => request<Shift[]>('/v1/admin/shifts');
export const createShift = (body: Omit<Shift, 'id'>) => request<Shift>('/v1/admin/shifts', { method: 'POST', body: JSON.stringify(body) });
export const shiftAssignments = (user_id: string, month: string) =>
	request<ShiftAssignment[]>(`/v1/admin/shift-assignments?${new URLSearchParams({ user_id, month })}`);
export const assignShift = (body: { user_id: string; shift_id: string; work_date: string }) => request<void>('/v1/admin/shift-assignments', { method: 'POST', body: JSON.stringify(body) });
export const deleteShiftAssignment = (body: { user_id: string; work_date: string }) =>
	request<void>('/v1/admin/shift-assignments/delete', { method: 'POST', body: JSON.stringify(body) });
export const requestPasswordReset = (email: string) =>
	request<{ status: string; message: string } | undefined>('/v1/auth/password-reset', { method: 'POST', body: JSON.stringify({ email }) });
export const confirmPasswordReset = (token: string, new_password: string) =>
	request<void>('/v1/auth/password-reset/confirm', { method: 'POST', body: JSON.stringify({ token, new_password }) });
export const attendanceLocations = () => request<AttendanceLocation[]>('/v1/admin/attendance-locations');
export const refreshAttendanceQr = () => request<AttendanceLocation[]>('/v1/admin/attendance-locations/refresh', { method: 'POST' });
export const createAttendanceLocation = (body: Pick<AttendanceLocation, 'name' | 'latitude' | 'longitude' | 'radius_meters'>) =>
	request<AttendanceLocation>('/v1/admin/attendance-locations', { method: 'POST', body: JSON.stringify(body) });
export const setAttendanceLocationStatus = (id: string, active: boolean) =>
	request<void>(`/v1/admin/attendance-locations/${encodeURIComponent(id)}/status`, { method: 'POST', body: JSON.stringify({ active }) });
export const storageConfig = () =>
	request<{ driver: string; bucket: string; region: string; endpoint: string; force_path_style: boolean }>('/v1/storage/config');
export const listFiles = () => request<StoredFile[]>('/v1/files');
export const uploadFile = (body: { file_name: string; content_type: string; data_base64: string }) =>
	request<StoredFile>('/v1/files', { method: 'POST', body: JSON.stringify(body) });
export const deleteFile = (id: string) =>
	request<void>(`/v1/files/${encodeURIComponent(id)}/delete`, { method: 'POST' });
export const systemHealth = () => request<SystemHealthTelemetry>('/v1/admin/system-health');
