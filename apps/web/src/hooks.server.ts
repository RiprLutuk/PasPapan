import type { Handle } from '@sveltejs/kit/hooks';
import { paraglideMiddleware } from './lib/paraglide/server.js';
import { getTextDirection } from './lib/paraglide/runtime.js';

const MAX_REQUEST_BODY_BYTES = 15 * 1024 * 1024; // 15 MB boundary protects Node heap from unbounded buffers

export const handle: Handle = async ({ event, resolve }) => {
	const { pathname, search } = event.url;

	if (pathname === '/health' || pathname.startsWith('/v1/')) {
		const envMap = ((event.platform as { env?: Record<string, string> } | undefined)?.env) || {};
		const targetBase =
			envMap.API_PROXY_TARGET ||
			process.env.API_PROXY_TARGET ||
			'http://127.0.0.1:3000';
		const upstreamUrl = new URL(`${pathname}${search}`, targetBase);
		const headers = new Headers();

		// Enforce maximum payload size early via Content-Length header
		const declaredLength = event.request.headers.get('content-length');
		if (declaredLength) {
			const parsedLength = parseInt(declaredLength, 10);
			if (!Number.isNaN(parsedLength) && parsedLength > MAX_REQUEST_BODY_BYTES) {
				return new Response(JSON.stringify({ error: 'payload too large' }), {
					status: 413,
					headers: { 'content-type': 'application/json' }
				});
			}
		}

		for (const name of ['cookie', 'content-type', 'accept', 'user-agent', 'idempotency-key']) {
			const value = event.request.headers.get(name);
			if (value) headers.set(name, value);
		}

		const clientIp =
			(typeof event.getClientAddress === 'function' ? event.getClientAddress() : null) ||
			event.request.headers.get('x-forwarded-for') ||
			event.request.headers.get('x-real-ip');
		if (clientIp) {
			headers.set('x-forwarded-for', clientIp);
		}

		const origin =
			event.request.headers.get('origin') ||
			envMap.ORIGIN ||
			process.env.ORIGIN ||
			event.url.origin;
		if (origin) headers.set('origin', origin);

		const serviceKey = (envMap.API_INTERNAL_KEY || process.env.API_INTERNAL_KEY || '').trim();
		if (serviceKey) {
			headers.set('x-paspapan-service-key', serviceKey);
		}

		const method = event.request.method;
		let body: ArrayBuffer | undefined;

		if (method !== 'GET' && method !== 'HEAD') {
			const buffer = await event.request.arrayBuffer();
			if (buffer.byteLength > MAX_REQUEST_BODY_BYTES) {
				return new Response(JSON.stringify({ error: 'payload too large' }), {
					status: 413,
					headers: { 'content-type': 'application/json' }
				});
			}
			body = buffer;
		}

		let upstreamResponse: Response;
		try {
			upstreamResponse = await fetch(upstreamUrl, {
				method,
				headers,
				body,
				redirect: 'manual',
				signal: event.request.signal
			});
		} catch {
			return new Response(JSON.stringify({ error: 'upstream service unavailable' }), {
				status: 502,
				headers: { 'content-type': 'application/json' }
			});
		}

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

	const response = await paraglideMiddleware(event.request, ({ request, locale }) =>
		resolve(
			{ ...event, request },
			{
				transformPageChunk: ({ html }) =>
					html.replace('%lang%', locale).replace('%dir%', getTextDirection(locale))
			}
		)
	);
	if (event.route?.id) {
		response.headers.set('cache-control', 'private, no-store');
		const vary = response.headers.get('vary')?.split(',').map((value) => value.trim().toLowerCase()) ?? [];
		if (!vary.includes('cookie') && !vary.includes('*')) response.headers.append('vary', 'Cookie');
	}
	return response;
};
