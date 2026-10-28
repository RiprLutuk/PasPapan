<script lang="ts">
	import { goto } from '$app/navigation';
	import { request } from '../../lib/api';
	import Icon from '../../lib/Icon.svelte';
	import { setCachedSession, setCachedUnreadCount } from '../../lib/session';
	import { AlertBanner, AuthFrame, FormField, PasswordField } from '../../lib/ui';
	import { uiText as t } from '../../lib/i18n';

	const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
	const SLUG_PATTERN = /^[a-z0-9]+(-[a-z0-9]+)*$/;

	let company_name = $state('');
	let company_slug = $state('');
	let slugTouched = $state(false);
	let full_name = $state('');
	let email = $state('');
	let password = $state('');

	let companyNameError = $state('');
	let companySlugError = $state('');
	let fullNameError = $state('');
	let emailError = $state('');
	let passwordError = $state('');
	let error = $state('');
	let submitting = $state(false);

	function slugify(input: string): string {
		return input
			.toLocaleLowerCase('id')
			.replace(/[^a-z0-9]+/g, '-')
			.replace(/^-+|-+$/g, '')
			.slice(0, 63);
	}

	function handleCompanyInput(event: Event & { currentTarget: HTMLInputElement }) {
		if (!slugTouched) {
			company_slug = slugify(event.currentTarget.value);
			if (companySlugError) companySlugError = '';
		}
	}

	function handleSlugInput(event: Event & { currentTarget: HTMLInputElement }) {
		slugTouched = event.currentTarget.value.trim().length > 0;
		company_slug = slugify(event.currentTarget.value);
	}

	function validate(): boolean {
		companyNameError = '';
		companySlugError = '';
		fullNameError = '';
		emailError = '';
		passwordError = '';

		if (!company_name.trim()) {
			companyNameError = t('Nama perusahaan wajib diisi.');
		}
		const slug = company_slug.trim();
		if (!slug) {
			companySlugError = t('Alamat workspace wajib diisi.');
		} else if (slug.length < 3 || !SLUG_PATTERN.test(slug)) {
			companySlugError = t('Gunakan minimal 3 karakter huruf kecil, angka, atau tanda hubung.');
		}
		if (!full_name.trim()) {
			fullNameError = t('Nama pemilik wajib diisi.');
		}
		const trimmedEmail = email.trim();
		if (!trimmedEmail) {
			emailError = t('Email kerja wajib diisi.');
		} else if (!EMAIL_PATTERN.test(trimmedEmail)) {
			emailError = t('Format email kerja belum valid.');
		}
		if (!password) {
			passwordError = t('Kata sandi wajib diisi.');
		} else if (password.length < 12) {
			passwordError = t('Kata sandi minimal 12 karakter.');
		}

		return (
			!companyNameError &&
			!companySlugError &&
			!fullNameError &&
			!emailError &&
			!passwordError
		);
	}

	async function submit(event: SubmitEvent) {
		event.preventDefault();
		if (submitting) return;
		error = '';
		if (!validate()) return;

		submitting = true;
		try {
			setCachedSession(null);
			setCachedUnreadCount(0);
			await request('/v1/auth/register', {
				method: 'POST',
				body: JSON.stringify({
					company_name: company_name.trim(),
					company_slug: company_slug.trim(),
					full_name: full_name.trim(),
					email: email.trim(),
					password
				})
			});
			await goto('/app/today');
		} catch (cause) {
			error = cause instanceof Error ? cause.message : t('Workspace belum dapat dibuat.');
		} finally {
			submitting = false;
		}
	}
</script>

<svelte:head><title>{t('Buat workspace')} · PasPapan</title></svelte:head>

<AuthFrame
	mode="register"
	title={t('Buat workspace')}
	subtitle={t('Daftarkan perusahaan dan akun pemilik untuk mulai menggunakan PasPapan.')}
>
	<form class="auth-form" novalidate data-custom-validation onsubmit={submit}>
		<FormField
			id="company-name"
			label={t('Nama perusahaan')}
			icon="building"
			bind:value={company_name}
			bind:error={companyNameError}
			placeholder="PT Contoh Nusantara"
			autocomplete="organization"
			maxlength={200}
			oninput={handleCompanyInput}
		/>

		<FormField
			id="company-slug"
			label={t('Alamat workspace')}
			prefix="paspapan.app/"
			bind:value={company_slug}
			bind:error={companySlugError}
			placeholder="contoh-nusantara"
			autocomplete="off"
			maxlength={63}
			oninput={handleSlugInput}
		/>

		<div class="two-col">
			<FormField
				id="full-name"
				label={t('Nama lengkap')}
				icon="profile"
				bind:value={full_name}
				bind:error={fullNameError}
				placeholder={t('Nama Anda')}
				autocomplete="name"
				maxlength={200}
			/>

			<FormField
				id="email"
				label={t('Email kerja')}
				type="email"
				icon="mail"
				bind:value={email}
				bind:error={emailError}
				placeholder={t('nama@perusahaan.com')}
				autocomplete="email"
				maxlength={254}
			/>
		</div>

		<PasswordField
			id="password"
			label={t('Kata sandi')}
			bind:value={password}
			bind:error={passwordError}
			autocomplete="new-password"
			minlength={12}
			maxlength={1024}
			hint={t('Minimal 12 karakter.')}
			showStrength={true}
		/>

		<button class="submit-btn" type="submit" disabled={submitting}>
			<span>{submitting ? t('Membuat workspace…') : t('Buat workspace')}</span>
			<Icon name="arrow" size={16} />
		</button>

		<AlertBanner variant="error" message={error} />
	</form>

	{#snippet footer()}
		<div class="footer-row">
			<span>{t('Sudah memiliki akun?')}</span>
			<a href="/login">{t('Masuk')}</a>
		</div>
	{/snippet}
</AuthFrame>

<style>
	.auth-form {
		display: grid;
		gap: 13px;
	}
	.two-col {
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 12px;
	}
	.footer-row {
		display: flex;
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
	@media (max-width: 520px) {
		.two-col {
			grid-template-columns: 1fr;
		}
	}
</style>
