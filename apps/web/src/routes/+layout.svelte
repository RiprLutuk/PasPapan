<script lang="ts">
	import favicon from '#lib/assets/favicon.svg';
	import type { LayoutProps } from './$types';

	let { children }: LayoutProps = $props();
</script>

<svelte:head>
	<link rel="icon" href={favicon} />
	<meta name="theme-color" content="#11251f" />
</svelte:head>

{@render children()}

<style>
	:global(*) { box-sizing: border-box; }
	:global(html) { font-family: Inter, ui-sans-serif, system-ui, sans-serif; background: #eef2ec; color: #13221d; }
	:global(body) { min-width: 320px; margin: 0; }
	:global(button), :global(input) { font: inherit; }
	:global(:focus-visible) { outline: 3px solid #f7b75c; outline-offset: 3px; }
</style>
