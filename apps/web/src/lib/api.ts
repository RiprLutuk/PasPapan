export type CurrentSession = {
	user: { id: string; full_name: string; email: string };
	company: { id: string; name: string; role: string };
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
