<script lang="ts">
	import type { Snippet } from 'svelte';
	import Icon from '../Icon.svelte';

	let {
		id,
		label,
		value = $bindable(''),
		error = $bindable(''),
		type = 'text',
		placeholder = '',
		autocomplete = 'off',
		icon = '',
		prefix = '',
		hint = '',
		required = false,
		readonly = false,
		disabled = false,
		minlength,
		maxlength,
		pattern,
		oninput,
		labelRight
	}: {
		id: string;
		label: string;
		value?: string;
		error?: string;
		type?: 'text' | 'email' | 'tel' | 'url' | 'search';
		placeholder?: string;
		autocomplete?: AutoFill;
		icon?: string;
		prefix?: string;
		hint?: string;
		required?: boolean;
		readonly?: boolean;
		disabled?: boolean;
		minlength?: number;
		maxlength?: number;
		pattern?: string;
		oninput?: (event: Event & { currentTarget: HTMLInputElement }) => void;
		labelRight?: Snippet;
	} = $props();

	function handleInput(event: Event & { currentTarget: HTMLInputElement }) {
		if (error) error = '';
		oninput?.(event);
	}
</script>

<div class="ui-field">
	<div class="ui-field-head">
		<label for={id}>{label}</label>
		{#if labelRight}
			<div class="ui-field-extra">{@render labelRight()}</div>
		{/if}
	</div>
	<div
		class="ui-input-shell"
		class:has-icon={Boolean(icon)}
		class:has-prefix={Boolean(prefix)}
		class:has-error={Boolean(error)}
		class:is-readonly={readonly}
		class:is-disabled={disabled}
	>
		{#if icon}
			<span class="ui-input-icon"><Icon name={icon} size={16} /></span>
		{/if}
		{#if prefix}
			<span class="ui-input-prefix">{prefix}</span>
		{/if}
		<input
			{id}
			bind:value
			{type}
			{placeholder}
			{autocomplete}
			{required}
			{readonly}
			{disabled}
			{minlength}
			{maxlength}
			{pattern}
			aria-invalid={Boolean(error)}
			aria-describedby={error ? `${id}-error` : undefined}
			oninput={handleInput}
		/>
	</div>
	{#if error}
		<p id={`${id}-error`} class="ui-field-error" role="alert">
			<Icon name="close" size={12} />
			<span>{error}</span>
		</p>
	{:else if hint}
		<small class="ui-field-hint">{hint}</small>
	{/if}
</div>

<style>
	.ui-field {
		display: grid;
		gap: 5px;
	}
	.ui-field-head {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 8px;
	}
	.ui-field-head label {
		margin: 0;
		font-size: 12.5px;
		font-weight: 700;
		color: var(--ink);
	}
	.ui-field-extra {
		font-size: 11.5px;
		font-weight: 650;
	}
	.ui-input-shell {
		display: flex;
		align-items: center;
		min-height: 42px;
		border: 1px solid #c6d4c7;
		border-radius: var(--radius-sm);
		background: #ffffff;
		transition: border-color 140ms ease, box-shadow 140ms ease, background-color 140ms ease;
		overflow: hidden;
	}
	.ui-input-shell:focus-within {
		border-color: var(--primary);
		box-shadow: 0 0 0 3px rgba(59, 110, 49, 0.13);
	}
	.ui-input-shell.has-error {
		border-color: #d0483e;
		background: #fffafa;
	}
	.ui-input-shell.has-error:focus-within {
		border-color: #c2382e;
		box-shadow: 0 0 0 3px rgba(208, 72, 62, 0.14);
	}
	.ui-input-shell.is-readonly,
	.ui-input-shell.is-disabled {
		background: var(--surface-alt);
		opacity: 0.85;
	}
	.ui-input-icon {
		display: grid;
		place-items: center;
		padding-inline-start: 11px;
		color: var(--muted);
		flex: 0 0 auto;
	}
	.ui-input-shell.has-error .ui-input-icon {
		color: #c2382e;
	}
	.ui-input-prefix {
		padding-inline-start: 11px;
		color: var(--muted);
		font-size: 12.5px;
		font-weight: 650;
		white-space: nowrap;
		user-select: none;
	}
	.ui-input-shell input {
		width: 100%;
		min-width: 0;
		min-height: 40px;
		border: 0 !important;
		border-radius: 0 !important;
		background: transparent !important;
		box-shadow: none !important;
		padding: 9px 12px;
		color: var(--ink);
		font: inherit;
		font-size: 13.5px;
		outline: none;
	}
	.ui-input-shell.has-icon input {
		padding-inline-start: 8px;
	}
	.ui-input-shell.has-prefix input {
		padding-inline-start: 4px;
	}
	.ui-field-hint {
		color: var(--muted);
		font-size: 11px;
		line-height: 1.4;
	}
	.ui-field-error {
		margin: 1px 0 0;
		display: inline-flex;
		align-items: center;
		gap: 5px;
		color: #b42e24;
		font-size: 11.5px;
		font-weight: 650;
		line-height: 1.35;
	}
	.ui-field-error :global(svg) {
		flex: 0 0 auto;
	}
	@media (max-width: 760px) {
		.ui-field-head label { font-size: 11.5px; }
		.ui-field-extra { font-size: 11px; }
		.ui-input-shell { min-height: 38px; }
		.ui-input-icon { padding-inline-start: 9px; }
		.ui-input-shell input {
			min-height: 36px;
			padding-block: 6px;
			font-size: 13px;
		}
	}
</style>
