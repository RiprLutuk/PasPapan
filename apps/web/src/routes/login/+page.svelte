<script lang="ts">
	import { goto } from '$app/navigation';
	import { onMount } from 'svelte';
	import { currentSession, request } from '../../lib/api';
	import Icon from '../../lib/Icon.svelte';
	import { setCachedSession, setCachedUnreadCount } from '../../lib/session';
	import { AlertBanner, AuthFrame, FormField, PasswordField } from '../../lib/ui';
	import { m } from '../../lib/paraglide/messages.js';
	import { uiText as t } from '../../lib/i18n';

	const LAST_EMAIL_KEY = 'paspapan:last-login-email';
	const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

	let email = $state('');
	let password = $state('');
	let emailError = $state('');
	let passwordError = $state('');
	let error = $state('');
	let submitting = $state(false);
	let ready = $state(false);

	async function initializeLogin() {
		try {
			const saved = localStorage.getItem(LAST_EMAIL_KEY);
			if (saved) email = saved;
		} catch {
			// Ignore storage errors
		}

		try {
			const session = await currentSession();
			setCachedSession(session);
			await goto('/app/today', { replaceState: true });
			return;
		} catch {
			// An absent, expired, or temporarily unreachable session keeps this public route usable.
			setCachedSession(null);
			ready = true;
		}
	}

	onMount(() => {
		void initializeLogin();
	});

	function validate(): boolean {
		emailError = '';
		passwordError = '';
		const trimmed = email.trim();
		if (!trimmed) {
			emailError = t('Email kerja wajib diisi.');
		} else if (!EMAIL_PATTERN.test(trimmed)) {
			emailError = t('Format email kerja belum valid.');
		}
		if (!password) {
			passwordError = t('Kata sandi wajib diisi.');
		}
		return !emailError && !passwordError;
	}

	async function submit(event: SubmitEvent) {
		event.preventDefault();
		if (!ready || submitting) return;
		error = '';
		if (!validate()) return;

		submitting = true;
		try {
			setCachedSession(null);
			setCachedUnreadCount(0);
			await request('/v1/auth/login', {
				method: 'POST',
				body: JSON.stringify({ email: email.trim(), password })
			});
			try {
				if (email.trim()) localStorage.setItem(LAST_EMAIL_KEY, email.trim());
			} catch {
				// Ignore storage errors
			}
			await goto('/app/today');
		} catch (cause) {
			error = cause instanceof Error ? cause.message : t('Email atau kata sandi tidak sesuai.');
		} finally {
			submitting = false;
		}
	}
</script>

<svelte:head><title>{m.login_title()} · PasPapan</title></svelte:head>

<AuthFrame
	mode="login"
	title={m.login_title()}
	subtitle={m.login_subtitle()}
>
	<form class="auth-form" novalidate onsubmit={submit}>
		<FormField
			id="email"
			label={m.field_work_email()}
			type="email"
			icon="mail"
			bind:value={email}
			bind:error={emailError}
			autocomplete="username"
			placeholder="nama@perusahaan.com"
			disabled={!ready || submitting}
		/>

		<PasswordField
			id="password"
			label={m.login_password()}
			bind:value={password}
			bind:error={passwordError}
			autocomplete="current-password"
			disabled={!ready || submitting}
		>
			{#snippet labelRight()}
				<a class="inline-link" href="/forgot-password">{m.login_forgot_password()}</a>
			{/snippet}
		</PasswordField>

		<button class="submit-btn" disabled={!ready || submitting} type="submit">
			<span>{submitting ? m.login_checking() : m.login_title()}</span>
			<Icon name="arrow" size={16} />
		</button>

		<AlertBanner variant="error" message={error} />
	</form>

	{#snippet footer()}
		<div class="footer-row">
			<span>{m.login_no_workspace()}</span>
			<a href="/register">{m.login_create_workspace()}</a>
		</div>
	{/snippet}
</AuthFrame>

<style>
	.auth-form {
		display: grid;
		gap: 14px;
	}
	.inline-link {
		color: var(--primary);
		font-size: 11.5px;
		font-weight: 700;
		text-decoration: none;
	}
	.inline-link:hover {
		text-decoration: underline;
	}
	.footer-row {
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		justify-content: space-between;
		gap: 8px;
	}
	.footer-row a {
		color: var(--primary-strong);
		font-weight: 700;
		text-decoration: none;
	}
	.footer-row a:hover {
		text-decoration: underline;
	}
	@media (max-width: 480px) {
		.auth-form { gap: 10px; }
		.inline-link { font-size: 11px; }
	}
</style>
