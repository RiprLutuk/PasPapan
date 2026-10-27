<script lang="ts">
	import { requestPasswordReset } from '../../lib/api';
	import Icon from '../../lib/Icon.svelte';
	import { AlertBanner, AuthFrame, FormField } from '../../lib/ui';
	import { uiText as t } from '../../lib/i18n';

	const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

	let email = $state('');
	let emailError = $state('');
	let pending = $state(false);
	let error = $state('');
	let message = $state('');

	function validate(): boolean {
		emailError = '';
		const trimmed = email.trim();
		if (!trimmed) {
			emailError = t('Email kerja wajib diisi.');
			return false;
		}
		if (!EMAIL_PATTERN.test(trimmed)) {
			emailError = t('Format email kerja belum valid.');
			return false;
		}
		return true;
	}

	async function submit(event: SubmitEvent) {
		event.preventDefault();
		if (pending) return;
		error = '';
		message = '';
		if (!validate()) return;

		pending = true;
		try {
			const response = await requestPasswordReset(email.trim());
			message = response?.message || t('Jika email terdaftar, instruksi pemulihan akan dikirim.');
		} catch (cause) {
			error = cause instanceof Error ? cause.message : t('Pemulihan kata sandi belum tersedia.');
		} finally {
			pending = false;
		}
	}
</script>

<svelte:head><title>{t('Pulihkan akses')} · PasPapan</title></svelte:head>

<AuthFrame
	mode="recovery"
	title={t('Lupa kata sandi?')}
	subtitle={t('Masukkan email kerja Anda untuk menerima instruksi pemulihan.')}
>
	<form class="auth-form" novalidate onsubmit={submit}>
		<FormField
			id="recovery-email"
			label={t('Email kerja')}
			type="email"
			icon="mail"
			bind:value={email}
			bind:error={emailError}
			placeholder={t('nama@perusahaan.com')}
			autocomplete="email"
			disabled={pending}
		/>

		<button class="submit-btn" type="submit" disabled={pending}>
			<span>{pending ? t('Mengirim…') : t('Kirim instruksi')}</span>
			<Icon name="arrow" size={16} />
		</button>

		<AlertBanner variant="error" message={error} />
		<AlertBanner variant="success" message={message} />
	</form>

	{#snippet footer()}
		<div class="footer-row">
			<a href="/login">{t('Kembali ke halaman masuk')}</a>
		</div>
	{/snippet}
</AuthFrame>

<style>
	.auth-form {
		display: grid;
		gap: 14px;
	}
	.footer-row {
		display: flex;
		align-items: center;
		justify-content: center;
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
