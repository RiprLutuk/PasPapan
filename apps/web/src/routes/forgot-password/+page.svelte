<script lang="ts">
	import { requestPasswordReset } from '../../lib/api';
	import Icon from '../../lib/Icon.svelte';
	import { AlertBanner, AuthFrame, FormField } from '../../lib/ui';

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
			emailError = 'Email kerja wajib diisi.';
			return false;
		}
		if (!EMAIL_PATTERN.test(trimmed)) {
			emailError = 'Format email kerja belum valid.';
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
			message = response?.message || 'Jika email terdaftar, instruksi pemulihan akan dikirim.';
		} catch (cause) {
			error = cause instanceof Error ? cause.message : 'Pemulihan kata sandi belum tersedia.';
		} finally {
			pending = false;
		}
	}
</script>

<svelte:head><title>Pulihkan akses · PasPapan</title></svelte:head>

<AuthFrame
	mode="recovery"
	title="Lupa kata sandi?"
	subtitle="Masukkan email kerja Anda untuk menerima instruksi pemulihan."
>
	<form class="auth-form" novalidate onsubmit={submit}>
		<FormField
			id="recovery-email"
			label="Email kerja"
			type="email"
			icon="mail"
			bind:value={email}
			bind:error={emailError}
			placeholder="nama@perusahaan.com"
			autocomplete="email"
			disabled={pending}
		/>

		<button class="submit-btn" type="submit" disabled={pending}>
			<span>{pending ? 'Mengirim…' : 'Kirim instruksi'}</span>
			<Icon name="arrow" size={16} />
		</button>

		<AlertBanner variant="error" message={error} />
		<AlertBanner variant="success" message={message} />
	</form>

	{#snippet footer()}
		<div class="footer-row">
			<a href="/login">Kembali ke halaman masuk</a>
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
