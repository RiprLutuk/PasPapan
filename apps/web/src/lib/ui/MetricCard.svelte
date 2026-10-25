<script lang="ts">
	import Icon from '../Icon.svelte';
	let { label, value, href, loading = false }: {
		label: string; value: string | number; href?: string; loading?: boolean;
	} = $props();
</script>

{#snippet content()}
	<span class="metric-label">{label}</span>
	<strong>{loading ? '—' : value}</strong>
	{#if href}<span class="metric-arrow" aria-hidden="true"><Icon name="arrow" size={16} /></span>{/if}
{/snippet}
{#if href}
	<a class="ui-metric" {href} aria-busy={loading}>{@render content()}</a>
{:else}
	<article class="ui-metric" aria-busy={loading}>{@render content()}</article>
{/if}

<style>
	.ui-metric { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-content: center; gap: var(--space-1) var(--space-2); min-width: 0; min-height: 76px; padding: var(--space-3); border: 1px solid var(--line); border-radius: var(--radius); background: var(--surface); color: var(--ink); text-decoration: none; }
	a.ui-metric:hover { border-color: var(--primary); background: var(--surface-alt); }
	.metric-label { grid-column: 1 / -1; font-size: 12px; line-height: 1.4; color: var(--muted); overflow-wrap: anywhere; }
	strong { font-size: 22px; line-height: 1.15; font-weight: 750; letter-spacing: -.03em; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
	.metric-arrow { align-self: end; color: var(--muted); }
</style>
