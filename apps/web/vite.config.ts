import nodeAdapter from '@sveltejs/adapter-node';
import cloudflareAdapter from '@sveltejs/adapter-cloudflare';
import { sveltekit } from '@sveltejs/kit/vite';
import { paraglideVitePlugin } from '@inlang/paraglide-js';
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

	const useCloudflare =
		env.ADAPTER === 'cloudflare' ||
		process.env.ADAPTER === 'cloudflare' ||
		Boolean(process.env.CF_PAGES);

	const selectedAdapter = useCloudflare ? cloudflareAdapter() : nodeAdapter();

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

				// Portable adapter: uses @sveltejs/adapter-cloudflare on Cloudflare Pages, otherwise @sveltejs/adapter-node
				adapter: selectedAdapter
			}),
			paraglideVitePlugin({
				project: './project.inlang',
				outdir: './src/lib/paraglide',
				emitTsDeclarations: true,
				strategy: ['cookie', 'preferredLanguage', 'baseLocale']
			})
		]
	};
});
