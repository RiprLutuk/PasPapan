export async function handle({
	event,
	resolve
}: {
	event: { url: URL; request: Request };
	resolve: (event: unknown) => Promise<Response>;
}): Promise<Response> {
	const { pathname, search } = event.url;

	if (pathname === '/health' || pathname.startsWith('/v1/')) {
		const targetBase = process.env.API_PROXY_TARGET || 'http://127.0.0.1:3000';
		const upstreamUrl = new URL(`${pathname}${search}`, targetBase);
		const headers = new Headers();

		for (const name of ['cookie', 'content-type', 'accept', 'user-agent']) {
			const value = event.request.headers.get(name);
			if (value) headers.set(name, value);
		}

		const origin =
			event.request.headers.get('origin') ||
			process.env.ORIGIN ||
			event.url.origin;
		if (origin) headers.set('origin', origin);

		const serviceKey = process.env.API_INTERNAL_KEY?.trim();
		if (serviceKey) {
			headers.set('x-paspapan-service-key', serviceKey);
		}

		const method = event.request.method;
		const body =
			method === 'GET' || method === 'HEAD'
				? undefined
				: await event.request.arrayBuffer();

		const upstreamResponse = await fetch(upstreamUrl, {
			method,
			headers,
			body,
			redirect: 'manual'
		});

		const responseHeaders = new Headers();
		for (const [key, value] of upstreamResponse.headers.entries()) {
			if (key.toLowerCase() === 'transfer-encoding') continue;
			responseHeaders.append(key, value);
		}

		return new Response(upstreamResponse.body, {
			status: upstreamResponse.status,
			statusText: upstreamResponse.statusText,
			headers: responseHeaders
		});
	}

	return resolve(event);
}
