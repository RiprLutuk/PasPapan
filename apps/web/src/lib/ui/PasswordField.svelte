<script lang="ts">
	import type { Snippet } from 'svelte';
	import Icon from '../Icon.svelte';

	let {
		id,
		label = 'Kata sandi',
		value = $bindable(''),
		error = $bindable(''),
		placeholder = 'Masukkan kata sandi',
		autocomplete = 'current-password',
		required = true,
		disabled = false,
		minlength,
		maxlength = 1024,
		hint = '',
		showStrength = false,
		labelRight
	}: {
		id: string;
		label?: string;
		value?: string;
		error?: string;
		placeholder?: string;
		autocomplete?: AutoFill;
		required?: boolean;
		disabled?: boolean;
		minlength?: number;
		maxlength?: number;
		hint?: string;
		showStrength?: boolean;
		labelRight?: Snippet;
	} = $props();

	let revealed = $state(false);

	let strengthScore = $derived.by(() => {
		if (!value) return 0;
		let score = 0;
		if (value.length >= 8) score += 1;
		if (value.length >= 12) score += 1;
		if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score += 1;
		if (/\d/.test(value) || /[^A-Za-z0-9]/.test(value)) score += 1;
		return score;
	});

	let strengthLabel = $derived.by(() => {
		if (!value) return 'Minimal 12 karakter';
		if (minlength && value.length < minlength) return `${minlength - value.length} karakter lagi`;
		if (strengthScore <= 1) return 'Lemah';
		if (strengthScore === 2) return 'Cukup';
		if (strengthScore === 3) return 'Kuat';
		return 'Sangat kuat';
	});

	function handleInput() {
		if (error) error = '';
	}
</script>

<div class="ui-pw-field">
	<div class="ui-pw-head">
		<label for={id}>{label}</label>
		{#if labelRight}
			<div class="ui-pw-extra">{@render labelRight()}</div>
		{/if}
	</div>

	<div class="ui-pw-shell" class:has-error={Boolean(error)} class:is-disabled={disabled}>
		<span class="ui-pw-icon"><Icon name="lock" size={16} /></span>
		<input
			{id}
			bind:value
			type={revealed ? 'text' : 'password'}
			{placeholder}
			{autocomplete}
			{required}
			{disabled}
			{minlength}
			{maxlength}
			aria-invalid={Boolean(error)}
			aria-describedby={error ? `${id}-error` : undefined}
			oninput={handleInput}
		/>
		<button
			type="button"
			class="ui-pw-toggle"
			title={revealed ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}
			aria-label={revealed ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}
			aria-pressed={revealed}
			disabled={disabled}
			onclick={() => (revealed = !revealed)}
		>
			<Icon name={revealed ? 'eye-off' : 'eye'} size={16} />
		</button>
	</div>

	{#if error}
		<p id={`${id}-error`} class="ui-pw-error" role="alert">
			<Icon name="close" size={12} />
			<span>{error}</span>
		</p>
	{:else if showStrength && value.length > 0}
		<div class="ui-pw-meter" aria-live="polite">
			<div class="meter-bars" data-score={strengthScore}>
				<span class:filled={strengthScore >= 1}></span>
				<span class:filled={strengthScore >= 2}></span>
				<span class:filled={strengthScore >= 3}></span>
				<span class:filled={strengthScore >= 4}></span>
			</div>
			<small class:ok={!minlength || value.length >= minlength}>{strengthLabel}</small>
		</div>
	{:else if hint}
		<small class="ui-pw-hint">{hint}</small>
	{/if}
</div>

<style>
	.ui-pw-field {
		display: grid;
		gap: 5px;
	}
	.ui-pw-head {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 8px;
	}
	.ui-pw-head label {
		margin: 0;
		font-size: 12.5px;
		font-weight: 700;
		color: var(--ink);
	}
	.ui-pw-extra {
		font-size: 11.5px;
		font-weight: 650;
	}
	.ui-pw-shell {
		display: flex;
		align-items: center;
		min-height: 42px;
		border: 1px solid var(--control-border);
		border-radius: var(--radius-sm);
		background: var(--surface);
		transition: border-color 140ms ease, box-shadow 140ms ease, background-color 140ms ease;
		overflow: hidden;
	}
	.ui-pw-shell:focus-within {
		border-color: var(--primary);
		box-shadow: 0 0 0 3px rgba(59, 110, 49, 0.13);
	}
	.ui-pw-shell.has-error {
		border-color: #d0483e;
		background: #fffafa;
	}
	.ui-pw-shell.has-error:focus-within {
		border-color: #c2382e;
		box-shadow: 0 0 0 3px rgba(208, 72, 62, 0.14);
	}
	.ui-pw-shell.is-disabled {
		background: var(--surface-alt);
		opacity: 0.85;
	}
	.ui-pw-icon {
		display: grid;
		place-items: center;
		padding-inline-start: 11px;
		color: var(--muted);
		flex: 0 0 auto;
	}
	.ui-pw-shell.has-error .ui-pw-icon {
		color: #c2382e;
	}
	.ui-pw-shell input {
		width: 100%;
		min-width: 0;
		min-height: 40px;
		border: 0 !important;
		border-radius: 0 !important;
		background: transparent !important;
		box-shadow: none !important;
		padding: 9px 8px;
		color: var(--ink);
		font: inherit;
		font-size: 13.5px;
		outline: none;
	}
	.ui-pw-toggle {
		display: grid;
		place-items: center;
		width: 34px;
		height: 34px;
		margin-inline-end: 4px;
		padding: 0;
		border: 0;
		border-radius: var(--radius-sm);
		background: transparent;
		color: var(--muted);
		box-shadow: none;
		cursor: pointer;
	}
	.ui-pw-toggle:hover:not(:disabled) {
		background: var(--surface-alt);
		color: var(--ink);
	}
	.ui-pw-meter {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 10px;
		margin-top: 2px;
	}
	.meter-bars {
		display: grid;
		grid-template-columns: repeat(4, 1fr);
		gap: 4px;
		flex: 1;
		max-width: 130px;
	}
	.meter-bars span {
		height: 4px;
		border-radius: 2px;
		background: #dde6dc;
		transition: background-color 150ms ease;
	}
	.meter-bars span.filled {
		background: var(--accent-solid);
	}
	.ui-pw-meter small,
	.ui-pw-hint {
		color: var(--muted);
		font-size: 11px;
		line-height: 1.4;
	}
	.ui-pw-meter small.ok {
		color: var(--primary-strong);
		font-weight: 700;
	}
	.ui-pw-error {
		margin: 1px 0 0;
		display: inline-flex;
		align-items: center;
		gap: 5px;
		color: #b42e24;
		font-size: 11.5px;
		font-weight: 650;
		line-height: 1.35;
	}
	.ui-pw-error :global(svg) {
		flex: 0 0 auto;
	}
	@media (max-width: 760px) {
		.ui-pw-head label { font-size: 11.5px; }
		.ui-pw-extra { font-size: 11px; }
		.ui-pw-shell { min-height: 38px; }
		.ui-pw-icon { padding-inline-start: 9px; }
		.ui-pw-toggle { width: 36px; height: 36px; flex: 0 0 36px; }
		.ui-pw-shell input {
			min-height: 36px;
			padding-block: 6px;
			font-size: 13px;
		}
	}
</style>
