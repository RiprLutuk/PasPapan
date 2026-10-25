import adapter from '@sveltejs/adapter-node';
import { sveltekit } from '@sveltejs/kit/vite';
import { defineConfig, loadEnv } from 'vite';

export default defineConfig(({ mode }) => {
	const env = loadEnv(mode, process.cwd(), '');
	const host = env.WEB_HOST || '127.0.0.1';
	const port = Number(env.WEB_PORT || 4173);
	const apiProxyTarget = env.API_PROXY_TARGET || 'http://127.0.0.1:3000';
	const serviceKey = env.API_INTERNAL_KEY?.trim();
	const proxyOptions = {
		target: apiProxyTarget,
		changeOrigin: false,
		...(serviceKey ? { headers: { 'x-paspapan-service-key': serviceKey } } : {})
	};

	return {
		server: {
			host,
			port,
			strictPort: true,
			proxy: {
				'/v1': proxyOptions,
				'/health': proxyOptions
			}
		},
		preview: {
			host,
			port,
			strictPort: true,
			proxy: {
				'/v1': proxyOptions,
				'/health': proxyOptions
			}
		},
		plugins: [
			sveltekit({
				compilerOptions: {
					// Force runes mode for the project, except for libraries. Can be removed in svelte 6.
					runes: ({ filename }) =>
						filename.split(/[/\\]/).includes('node_modules') ? undefined : true
				},

				// Portable Node frontend; route /v1 to Rust via API_PROXY_TARGET + API_INTERNAL_KEY.
				adapter: adapter()
			})
		]
	};
});
