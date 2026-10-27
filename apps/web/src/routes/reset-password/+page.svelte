<script lang="ts">
	import { goto } from '$app/navigation';
	import { page } from '$app/state';
	import { onMount } from 'svelte';
	import { confirmPasswordReset } from '../../lib/api';
	import Icon from '../../lib/Icon.svelte';
	import { AlertBanner, AuthFrame, FormField, PasswordField } from '../../lib/ui';
	import { uiText as t } from '../../lib/i18n';

	let token = $state('');
	let newPassword = $state('');
	let confirmPassword = $state('');

	let tokenError = $state('');
	let newPasswordError = $state('');
	let confirmPasswordError = $state('');
	let error = $state('');
	let success = $state('');
	let submitting = $state(false);

	onMount(() => {
		const urlToken = page.url.searchParams.get('token')?.trim() ?? '';
		if (urlToken) token = urlToken;
	});

	function validate(): boolean {
		tokenError = '';
		newPasswordError = '';
		confirmPasswordError = '';

		const cleanToken = token.trim();
		if (!cleanToken) {
			tokenError = t('Token pemulihan wajib diisi.');
		} else if (!/^[a-f0-9]{64}$/i.test(cleanToken)) {
			tokenError = t('Format token pemulihan tidak valid.');
		}
		if (!newPassword) {
			newPasswordError = t('Kata sandi baru wajib diisi.');
		} else if (newPassword.length < 12) {
			newPasswordError = t('Kata sandi baru minimal 12 karakter.');
		}
		if (!confirmPassword) {
			confirmPasswordError = t('Konfirmasi kata sandi wajib diisi.');
		} else if (newPassword && newPassword !== confirmPassword) {
			confirmPasswordError = t('Konfirmasi kata sandi belum cocok.');
		}

		return !tokenError && !newPasswordError && !confirmPasswordError;
	}

	async function submit(event: SubmitEvent) {
		event.preventDefault();
		if (submitting) return;
		error = '';
		success = '';
		if (!validate()) return;

		submitting = true;
		try {
			await confirmPasswordReset(token.trim(), newPassword);
			success = t('Kata sandi berhasil diperbarui. Mengalihkan ke halaman masuk…');
			setTimeout(() => {
				void goto('/login');
			}, 1200);
		} catch (cause) {
			error = cause instanceof Error ? cause.message : t('Kata sandi belum dapat diperbarui.');
		} finally {
			submitting = false;
		}
	}
</script>

<svelte:head><title>{t('Atur ulang kata sandi')} · PasPapan</title></svelte:head>

<AuthFrame
	mode="recovery"
	title={t('Atur ulang kata sandi')}
	subtitle={t('Buat kata sandi baru minimal 12 karakter untuk akun kerja Anda.')}
>
	<form class="auth-form" novalidate onsubmit={submit}>
		{#if !page.url.searchParams.get('token')}
			<FormField
				id="reset-token"
				label={t('Token pemulihan')}
				icon="key"
				bind:value={token}
				bind:error={tokenError}
				placeholder={t('Tempel kode token dari email')}
				autocomplete="off"
				disabled={submitting}
			/>
		{/if}

		<PasswordField
			id="new-password"
			label={t('Kata sandi baru')}
			bind:value={newPassword}
			bind:error={newPasswordError}
			autocomplete="new-password"
			minlength={12}
			maxlength={1024}
			showStrength={true}
			disabled={submitting}
		/>

		<PasswordField
			id="confirm-password"
			label={t('Konfirmasi kata sandi baru')}
			bind:value={confirmPassword}
			bind:error={confirmPasswordError}
			autocomplete="new-password"
			minlength={12}
			maxlength={1024}
			disabled={submitting}
		/>

		<button class="submit-btn" type="submit" disabled={submitting}>
			<span>{submitting ? t('Menyimpan…') : t('Simpan kata sandi baru')}</span>
			<Icon name="arrow" size={16} />
		</button>

		<AlertBanner variant="error" message={error} />
		<AlertBanner variant="success" message={success} />
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
