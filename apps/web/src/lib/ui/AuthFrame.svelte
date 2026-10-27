<script lang="ts">
	import type { Snippet } from 'svelte';
	import Brand from '../Brand.svelte';

	type AuthMode = 'login' | 'register' | 'recovery';

	let {
		mode = 'login',
		title,
		subtitle,
		children,
		footer
	}: {
		mode?: AuthMode;
		eyebrow?: string;
		title: string;
		subtitle?: string;
		children: Snippet;
		footer?: Snippet;
	} = $props();
</script>

<main class="auth-shell">
	<section class="auth-card" class:wide={mode === 'register'} aria-labelledby="auth-heading">
		<header class="auth-header">
			<a class="brand-link" href="/login" aria-label="PasPapan">
				<Brand size={30} />
			</a>
			<div class="heading-copy">
				<h1 id="auth-heading">{title}</h1>
				{#if subtitle}
					<p class="subtitle">{subtitle}</p>
				{/if}
			</div>
		</header>

		<div class="auth-body">
			{@render children()}
		</div>

		{#if footer}
			<footer class="auth-footer">
				{@render footer()}
			</footer>
		{/if}
	</section>
</main>

<style>
	.auth-shell {
		min-height: 100svh;
		display: grid;
		place-items: center;
		padding: 20px 16px;
		background: var(--canvas);
		font-family: 'Plus Jakarta Sans', sans-serif;
		color: var(--ink);
	}
	.auth-card {
		width: min(100%, 380px);
		display: grid;
		gap: 18px;
		padding: 24px;
		border-radius: var(--radius-lg);
		border: 1px solid var(--line);
		background: var(--surface);
		box-shadow: 0 18px 42px -28px rgba(24, 34, 29, 0.18), 0 1px 3px rgba(24, 34, 29, 0.04);
	}
	.auth-card.wide {
		width: min(100%, 460px);
	}
	.auth-header {
		display: grid;
		gap: 14px;
	}
	.brand-link {
		display: inline-flex;
		align-items: center;
		justify-self: start;
		text-decoration: none;
	}
	.heading-copy {
		display: grid;
		gap: 4px;
	}
	.heading-copy h1 {
		margin: 0;
		font-size: 22px;
		font-weight: 800;
		line-height: 1.15;
		letter-spacing: -0.035em;
		color: var(--ink);
	}
	.heading-copy .subtitle {
		margin: 0;
		color: var(--muted);
		font-size: 13px;
		line-height: 1.45;
	}
	.auth-body {
		display: grid;
		gap: 14px;
	}
	.auth-body :global(.submit-btn) {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		gap: 8px;
		width: 100%;
		min-height: 44px;
		margin-top: 2px;
		padding: 10px 16px;
		border: 1px solid transparent;
		border-radius: var(--radius-sm);
		background: var(--accent-solid);
		color: #ffffff;
		font: inherit;
		font-size: 13.5px;
		font-weight: 700;
		cursor: pointer;
		box-shadow: 0 6px 16px -8px rgba(39, 76, 32, 0.55);
		transition: background-color 140ms ease, transform 140ms ease, opacity 140ms ease;
	}
	.auth-body :global(.submit-btn:hover:not(:disabled)) {
		background: var(--accent-solid-hover);
	}
	.auth-body :global(.submit-btn:active:not(:disabled)) {
		transform: scale(0.985);
	}
	.auth-body :global(.submit-btn:disabled) {
		opacity: 0.6;
		cursor: wait;
	}
	.auth-footer {
		padding-top: 14px;
		border-top: 1px solid var(--line);
		font-size: 12.5px;
		color: var(--muted);
	}
	:global(:root[data-theme='dark']) .auth-card {
		border-color: transparent;
		box-shadow: var(--panel-shadow);
	}
	@media (max-height: 600px) {
		.auth-shell { align-items: start; }
	}
	@media (max-width: 480px) {
		.auth-shell {
			padding: 14px 16px;
		}
		.auth-card,
		.auth-card.wide {
			width: min(100%, 340px);
			padding: 18px 16px;
			gap: 14px;
		}
		.auth-header { gap: 11px; }
		.brand-link { font-size: 14px; }
		.brand-link :global(.identity) { gap: 7px; }
		.brand-link :global(.identity img) { width: 26px; height: 26px; }
		.heading-copy { gap: 3px; }
		.heading-copy h1 { font-size: 18px; }
		.heading-copy .subtitle { font-size: 12px; }
		.auth-body { gap: 10px; }
		.auth-body :global(.submit-btn) {
			min-height: 38px;
			padding: 7px 12px;
			font-size: 12.5px;
		}
		.auth-footer { padding-top: 11px; font-size: 11.5px; }
	}
</style>
