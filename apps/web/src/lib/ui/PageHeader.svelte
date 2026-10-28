<script lang="ts">
	import type { Snippet } from 'svelte';
	import Icon from '../Icon.svelte';
	import { uiText as t } from '../i18n';
	let { title, description, eyebrow, actions, backHref }: {
		title: string; description?: string; eyebrow?: string; actions?: Snippet; backHref?: string;
	} = $props();
</script>

<header class="page-heading ui-page-header">
	<div class="header-left-wrap">
		{#if backHref}
			<a class="back-btn" href={backHref} aria-label={t('Kembali')} title={t('Kembali')}>
				<Icon name="back" size={16} />
			</a>
		{/if}
		<div class="header-copy">
			{#if eyebrow}<p class="eyebrow">{eyebrow}</p>{/if}
			<h1>{title}</h1>
			{#if description}<p class="header-desc">{description}</p>{/if}
		</div>
	</div>
	{#if actions}<div class="header-actions">{@render actions()}</div>{/if}
</header>

<style>
	.ui-page-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 12px;
		margin-bottom: 14px;
		flex-wrap: wrap;
	}
	.header-left-wrap {
		display: flex;
		align-items: center;
		gap: 10px;
		min-width: 0;
		flex: 1 1 auto;
	}
	.back-btn {
		display: grid;
		place-items: center;
		width: 32px;
		height: 32px;
		flex: 0 0 32px;
		border: 1px solid var(--line);
		border-radius: var(--radius-sm);
		background: var(--surface);
		color: var(--ink) !important;
		text-decoration: none;
		transition: background-color 140ms ease, color 140ms ease;
	}
	.back-btn:hover {
		background: var(--primary-soft);
		color: var(--primary-strong) !important;
	}
	.header-copy {
		min-width: 0;
		flex: 1;
	}
	.eyebrow {
		margin: 0 0 2px !important;
		font-size: 10px;
		font-weight: 800;
		letter-spacing: 0.12em;
		text-transform: uppercase;
		color: var(--primary);
	}
	h1 {
		margin: 0;
		font-size: 18px;
		font-weight: 800;
		letter-spacing: -0.025em;
		color: var(--ink);
		line-height: 1.25;
	}
	.header-desc {
		margin: 2px 0 0 !important;
		font-size: 12px;
		color: var(--muted);
		line-height: 1.4;
	}
	.header-actions {
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		gap: 8px;
	}
	.header-actions :global(a), .header-actions :global(button) {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		gap: 8px;
		min-height: 34px;
		font-size: 12px;
	}
</style>
