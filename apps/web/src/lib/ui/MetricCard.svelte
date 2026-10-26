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
	.ui-metric {
		display: grid;
		grid-template-columns: minmax(0, 1fr) auto;
		align-content: center;
		gap: 2px 8px;
		min-width: 0;
		min-height: 56px;
		padding: 10px 12px;
		border: 1px solid var(--line);
		border-radius: var(--radius-sm);
		background: var(--surface);
		color: var(--ink);
		text-decoration: none;
		box-shadow: 0 1px 3px rgba(24, 34, 29, 0.04);
	}
	a.ui-metric:hover { border-color: var(--primary); background: var(--surface-alt); }
	.metric-label {
		grid-column: 1 / -1;
		font-size: 11px;
		line-height: 1.3;
		font-weight: 650;
		color: var(--muted);
		overflow-wrap: anywhere;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}
	strong {
		font-size: 18px;
		line-height: 1.2;
		font-weight: 800;
		letter-spacing: -0.03em;
		font-variant-numeric: tabular-nums;
		overflow-wrap: anywhere;
	}
	.metric-arrow { align-self: end; color: var(--muted); }
	@media (max-width: 760px) {
		.ui-metric {
			min-height: 50px;
			padding: 7px 9px;
			gap: 1px 6px;
		}
	}
</style>
