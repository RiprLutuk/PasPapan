<script lang="ts">
	import type { Snippet } from 'svelte';
	let { title, description, eyebrow, actions }: {
		title: string; description?: string; eyebrow?: string; actions?: Snippet;
	} = $props();
</script>

<header class="page-heading ui-page-header">
	<div class="header-copy">
		{#if eyebrow}<p class="eyebrow">{eyebrow}</p>{/if}
		<h1>{title}</h1>
		{#if description}<p>{description}</p>{/if}
	</div>
	{#if actions}<div class="header-actions">{@render actions()}</div>{/if}
</header>

<style>
	.ui-page-header { align-items: flex-start; flex-wrap: wrap; gap: var(--space-3); }
	.header-copy { flex: 1 1 240px; min-width: 0; }
	.header-actions { display: flex; flex-wrap: wrap; align-items: center; gap: var(--space-2); max-width: 100%; }
	.header-actions :global(a) { display: inline-flex; align-items: center; min-height: var(--control-height); }
	.header-copy p:not(.eyebrow) { max-width: 64ch; }
</style>
