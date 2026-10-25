<script lang="ts">
	import type { Snippet } from 'svelte';
	import Icon from '../Icon.svelte';

	let {
		variant = 'error',
		message = '',
		title = '',
		children
	}: {
		variant?: 'error' | 'success' | 'info';
		message?: string;
		title?: string;
		children?: Snippet;
	} = $props();

	const iconName = $derived(variant === 'success' ? 'check' : variant === 'info' ? 'shield' : 'close');
</script>

{#if message || children}
	<div class={`ui-alert ${variant}`} role={variant === 'error' ? 'alert' : 'status'}>
		<span class="alert-icon"><Icon name={iconName} size={15} /></span>
		<div class="alert-copy">
			{#if title}<strong>{title}</strong>{/if}
			{#if message}<p>{message}</p>{/if}
			{#if children}{@render children()}{/if}
		</div>
	</div>
{/if}

<style>
	.ui-alert {
		display: flex;
		align-items: flex-start;
		gap: 10px;
		padding: 10px 12px;
		border-radius: var(--radius-sm);
		font-size: 12px;
		line-height: 1.45;
		border: 1px solid transparent;
	}
	.ui-alert.error {
		background: #fff3f0;
		border-color: #ecc8c2;
		color: #8a2c24;
	}
	.ui-alert.success {
		background: #eff7ed;
		border-color: #c2dec0;
		color: var(--primary-strong);
	}
	.ui-alert.info {
		background: #f3f7fa;
		border-color: #c9dce8;
		color: #1e526e;
	}
	.alert-icon {
		display: grid;
		place-items: center;
		width: 22px;
		height: 22px;
		flex: 0 0 22px;
		border-radius: var(--radius-sm);
		background: rgba(255, 255, 255, 0.72);
	}
	.alert-copy {
		min-width: 0;
		flex: 1;
		display: grid;
		gap: 2px;
	}
	.alert-copy strong {
		font-size: 12px;
		font-weight: 750;
	}
	.alert-copy p {
		margin: 0;
		color: inherit;
		font-size: 12px;
	}
</style>
