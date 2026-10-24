<script lang="ts">
	import type { LayoutProps } from './$types';
	import '../lib/workspace.css';
	import { onMount } from 'svelte';
	import { page } from '$app/state';

	let { children }: LayoutProps = $props();
	onMount(() => {
		if (import.meta.env.PROD && 'serviceWorker' in navigator) {
			void navigator.serviceWorker.register('/sw.js', { scope: '/', updateViaCache: 'none' })
				.catch(() => { /* Installation can be retried on the next online visit. */ });
		}
	});
</script>

<svelte:head>
	<link rel="icon" type="image/svg+xml" href="/brand/favicon.svg" />
	<link rel="icon" type="image/png" sizes="32x32" href="/brand/favicon-32.png" />
	<meta name="theme-color" content="#44733a" />
	<link rel="manifest" href="/manifest.webmanifest" />
	<link rel="apple-touch-icon" sizes="180x180" href="/brand/apple-touch-icon.png" />
	<meta name="apple-mobile-web-app-title" content="PasPapan" />
	<meta property="og:site_name" content="PasPapan" />
	<meta property="og:title" content="PasPapan Workspace" />
	<meta property="og:type" content="website" />
	<meta property="og:image" content={new URL('/brand/social-card.png', page.url.origin).href} />
	<meta property="og:image:width" content="1200" />
	<meta property="og:image:height" content="630" />
	<meta property="og:image:alt" content="PasPapan — Hadir. Terhubung. Tertata." />
</svelte:head>

{@render children()}

<style>
	:global(*) { box-sizing: border-box; }
	:global(html) { font-family: Inter, ui-sans-serif, system-ui, sans-serif; background: #f6f8f6; color: #17231d; }
	:global(body) { min-width: 320px; margin: 0; }
	:global(button), :global(input) { font: inherit; }
	:global(:focus-visible) { outline: 3px solid #f7b75c; outline-offset: 3px; }
</style>
