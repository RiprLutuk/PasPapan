<script lang="ts">
	import { goto } from '$app/navigation';
	import { onMount } from 'svelte';
	import { request } from '../../lib/api';
	import Icon from '../../lib/Icon.svelte';
	import { setCachedSession, setCachedUnreadCount } from '../../lib/session';
	import { AlertBanner, AuthFrame, FormField, PasswordField } from '../../lib/ui';

	const LAST_EMAIL_KEY = 'paspapan:last-login-email';
	const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

	let email = $state('');
	let password = $state('');
	let emailError = $state('');
	let passwordError = $state('');
	let error = $state('');
	let submitting = $state(false);
	let ready = $state(false);

	onMount(() => {
		try {
			const saved = localStorage.getItem(LAST_EMAIL_KEY);
			if (saved) email = saved;
		} catch {
			// Ignore storage errors
		}
		ready = true;
	});

	function validate(): boolean {
		emailError = '';
		passwordError = '';
		const trimmed = email.trim();
		if (!trimmed) {
			emailError = 'Email kerja wajib diisi.';
		} else if (!EMAIL_PATTERN.test(trimmed)) {
			emailError = 'Format email kerja belum valid.';
		}
		if (!password) {
			passwordError = 'Kata sandi wajib diisi.';
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
			error = cause instanceof Error ? cause.message : 'Email atau kata sandi tidak sesuai.';
		} finally {
			submitting = false;
		}
	}
</script>

<svelte:head><title>Masuk · PasPapan</title></svelte:head>

<AuthFrame
	mode="login"
	title="Masuk"
	subtitle="Gunakan akun kerja Anda."
>
	<form class="auth-form" novalidate onsubmit={submit}>
		<FormField
			id="email"
			label="Email kerja"
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
			label="Kata sandi"
			bind:value={password}
			bind:error={passwordError}
			autocomplete="current-password"
			disabled={!ready || submitting}
		>
			{#snippet labelRight()}
				<a class="inline-link" href="/forgot-password">Lupa kata sandi?</a>
			{/snippet}
		</PasswordField>

		<button class="submit-btn" disabled={!ready || submitting} type="submit">
			<span>{submitting ? 'Memeriksa…' : 'Masuk'}</span>
			<Icon name="arrow" size={16} />
		</button>

		<AlertBanner variant="error" message={error} />
	</form>

	{#snippet footer()}
		<div class="footer-row">
			<span>Belum punya workspace?</span>
			<a href="/register">Buat workspace</a>
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
</style>
