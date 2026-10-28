<script lang="ts">
	import { goto } from '$app/navigation';
	import { onMount } from 'svelte';
	import { visibleOperations } from './access';
	import { request } from './api';
	import { realtimeState } from './realtime.svelte';
	import { notificationCount } from './home';
	import { workSession } from './session';
	import { applyAppearance, storedAppearance, type AppearanceMode } from './appearance';
	import { m } from './paraglide/messages.js';
	import { getLocale, setLocale, type Locale } from './paraglide/runtime.js';
	import Icon from './Icon.svelte';
	import { AlertBanner, FormField, PasswordField } from './ui';
	import { localeTag, uiText as t } from './i18n';

	const session = workSession();
	type ProfileRecord = {
		full_name: string;
		email: string;
		phone: string;
		address: string;
		team: string | null;
		job_title: string | null;
		employee_code: string | null;
		version: number;
	};
	type AccountSession = { id: string; created_at: string; expires_at: string; current: boolean };
	type ProfileTab = 'details' | 'password' | 'security' | 'sessions';

	const tabs: { id: ProfileTab; label: string; description: string; icon: string }[] = [
		{ id: 'details', label: m.tab_details(), description: m.tab_details_description(), icon: 'id-card' },
		{ id: 'password', label: m.tab_password(), description: m.tab_password_description(), icon: 'key' },
		{ id: 'security', label: m.tab_security(), description: m.tab_security_description(), icon: 'shield' },
		{ id: 'sessions', label: m.tab_sessions(), description: m.tab_sessions_description(), icon: 'smartphone' }
	];

	let activeTab = $state<ProfileTab | null>(null);
	let appearance = $state<AppearanceMode>('system');
	let locale = $state<Locale>(getLocale());
	let appearanceNotice = $state('');
	let profile = $state<ProfileRecord | null>(null);
	let sessions = $state<AccountSession[]>([]);
	let companies=$state<{id:string;name:string;role:string;current:boolean}[]>([]);
	let selectedCompany=$state(''),switchingCompany=$state(false),companyError=$state('');
	async function switchCompany(){
		if(!selectedCompany||selectedCompany===current?.company.id)return;
		switchingCompany=true;companyError='';
		try{await request('/v1/account/company',{method:'POST',body:JSON.stringify({company_id:selectedCompany})});session.clear();window.location.assign('/app/today');}
		catch(cause){companyError=cause instanceof Error?cause.message:t('Gagal berpindah perusahaan.');switchingCompany=false;}
	}
	let unreadFromApi = $state(0);
	let loadingProfile = $state(true);
	let loadingSessions = $state(false);
	let savingProfile = $state(false);
	let changingPassword = $state(false);
	let revokingSession = $state(false);
	let signingOut = $state(false);
	let logoutError = $state('');
	let profileError = $state('');
	let profileNotice = $state('');
	let sessionsError = $state('');
	let passwordError = $state('');
	let passwordNotice = $state('');
	let sessionError = $state('');
	let fullName = $state('');
	let phone = $state('');
	let address = $state('');
	let fullNameError = $state('');
	let currentPassword = $state('');
	let newPassword = $state('');
	let confirmPassword = $state('');
	let currentPwError = $state('');
	let newPwError = $state('');
	let confirmPwError = $state('');
	let sessionToRevoke = $state<AccountSession | null>(null);
	let revokePassword = $state('');
	let revokePwError = $state('');
	let revokeDialogElement = $state<HTMLDialogElement>();

	let current = $derived(session.current);
	let teamLinks = $derived(visibleOperations(current?.capabilities));
	let unreadCount = $derived(
		notificationCount(realtimeState.lastPulse?.unread_notifications) ??
		notificationCount(unreadFromApi) ?? 0
	);
	let avatarInitials = $derived(
		(profile?.full_name ?? current?.user.full_name ?? 'DU')
			.split(/\s+/)
			.filter(Boolean)
			.slice(0, 2)
			.map((part) => part[0]?.toLocaleUpperCase('id'))
			.join('') || 'DU'
	);
	let otherSessions = $derived(sessions.filter((entry) => !entry.current));

	$effect(() => {
		if (!revokeDialogElement) return;
		if (sessionToRevoke && !revokeDialogElement.open) revokeDialogElement.showModal();
		else if (!sessionToRevoke && revokeDialogElement.open) revokeDialogElement.close();
	});

	async function loadProfile() {
		loadingProfile = true;
		profileError = '';
		try {
			profile = await request<ProfileRecord>('/v1/account/profile');
			fullName = profile.full_name;
			phone = profile.phone;
			address = profile.address;
		} catch (cause) {
			profileError = cause instanceof Error ? cause.message : t('Informasi profil tidak dapat dimuat.');
		} finally {
			loadingProfile = false;
		}
	}

	async function loadSessions() {
		loadingSessions = true;
		sessionsError = '';
		try {
			sessions = await request<AccountSession[]>('/v1/account/sessions');
		} catch (cause) {
			sessionsError = cause instanceof Error ? cause.message : t('Sesi perangkat tidak dapat dimuat.');
		} finally {
			loadingSessions = false;
		}
	}

	async function saveProfile(event: SubmitEvent) {
		event.preventDefault();
		if (!profile || savingProfile) return;
		fullNameError = '';
		profileError = '';
		profileNotice = '';
		if (!fullName.trim()) {
			fullNameError = t('Nama lengkap wajib diisi.');
			return;
		}
		savingProfile = true;
		try {
			await request<void>('/v1/account/profile', {
				method: 'POST',
				body: JSON.stringify({ full_name: fullName.trim(), phone, address, version: profile.version })
			});
			profileNotice = t('Informasi profil berhasil disimpan.');
			await loadProfile();
			await session.refresh();
		} catch (cause) {
			profileError = cause instanceof Error ? cause.message : t('Profil tidak dapat disimpan.');
		} finally {
			savingProfile = false;
		}
	}

	async function updatePassword(event: SubmitEvent) {
		event.preventDefault();
		if (changingPassword) return;
		currentPwError = '';
		newPwError = '';
		confirmPwError = '';
		passwordError = '';
		passwordNotice = '';
		if (!currentPassword) currentPwError = t('Kata sandi saat ini wajib diisi.');
		if (!newPassword) newPwError = t('Kata sandi baru wajib diisi.');
		else if (newPassword.length < 12) newPwError = t('Kata sandi baru minimal 12 karakter.');
		if (!confirmPassword) confirmPwError = t('Konfirmasi kata sandi wajib diisi.');
		else if (newPassword && newPassword !== confirmPassword) confirmPwError = t('Konfirmasi kata sandi baru belum cocok.');
		if (currentPwError || newPwError || confirmPwError) return;

		changingPassword = true;
		try {
			await request<void>('/v1/account/password', {
				method: 'POST',
				body: JSON.stringify({ current_password: currentPassword, new_password: newPassword })
			});
			currentPassword = '';
			newPassword = '';
			confirmPassword = '';
			passwordNotice = t('Kata sandi diperbarui. Sesi di perangkat lain telah dikeluarkan.');
			await loadSessions();
			await session.refresh();
		} catch (cause) {
			passwordError = cause instanceof Error ? cause.message : t('Kata sandi tidak dapat diperbarui.');
		} finally {
			changingPassword = false;
		}
	}

	async function revokeOtherSession(event: SubmitEvent) {
		event.preventDefault();
		if (!sessionToRevoke || revokingSession) return;
		revokePwError = '';
		sessionError = '';
		if (!revokePassword) {
			revokePwError = t('Kata sandi saat ini wajib diisi.');
			return;
		}
		revokingSession = true;
		try {
			await request<void>(`/v1/account/sessions/${encodeURIComponent(sessionToRevoke.id)}/revoke`, {
				method: 'POST',
				body: JSON.stringify({ current_password: revokePassword })
			});
			sessionToRevoke = null;
			revokePassword = '';
			await loadSessions();
		} catch (cause) {
			sessionError = cause instanceof Error ? cause.message : t('Sesi perangkat tidak dapat dikeluarkan.');
		} finally {
			revokingSession = false;
		}
	}

	async function handleLogout() {
		if (signingOut) return;
		signingOut = true;
		logoutError = '';
		try {
			await request('/v1/auth/logout', { method: 'POST' });
			session.clear();
			await goto('/login');
		} catch {
			logoutError = t('Gagal keluar. Coba lagi.');
		} finally {
			signingOut = false;
		}
	}

	function formatDate(value: string) {
		const date = new Date(value);
		if (Number.isNaN(date.getTime())) return value;
		return new Intl.DateTimeFormat(localeTag(), {
			day: 'numeric',
			month: 'short',
			year: 'numeric',
			hour: '2-digit',
			minute: '2-digit'
		}).format(date);
	}

	function setTab(tab: ProfileTab) {
		activeTab = activeTab === tab ? null : tab;
		if (tab === 'sessions' && !sessions.length && !loadingSessions) void loadSessions();
	}

	function selectAppearance(mode: AppearanceMode) {
		appearance = mode;
		applyAppearance(mode);
		appearanceNotice = t(mode === 'system' ? 'Tema mengikuti perangkat diterapkan.' : mode === 'dark' ? 'Tema gelap diterapkan.' : 'Tema terang diterapkan.');
	}

	function handleTabKeydown(event: KeyboardEvent, index: number) {
		let nextIndex = index;
		if (event.key === 'ArrowRight' || event.key === 'ArrowDown') nextIndex = (index + 1) % tabs.length;
		else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') nextIndex = (index - 1 + tabs.length) % tabs.length;
		else if (event.key === 'Home') nextIndex = 0;
		else if (event.key === 'End') nextIndex = tabs.length - 1;
		else return;
		event.preventDefault();
		const tab = tabs[nextIndex];
		activeTab = tab.id;
		document.getElementById(`tab-${tab.id}`)?.focus();
	}

	function closeRevokeDialogOnEscape(event: KeyboardEvent) {
		if (event.key !== 'Escape') return;
		event.preventDefault();
		event.stopPropagation();
		sessionToRevoke = null;
	}

	function selectLocale(nextLocale: Locale) {
		if (locale === nextLocale) return;
		locale = nextLocale;
		void setLocale(nextLocale);
	}

	onMount(() => {
		appearance = storedAppearance();
		applyAppearance(appearance);
		void loadProfile();
		void request<typeof companies>('/v1/account/companies').then(items=>{companies=items;selectedCompany=items.find(item=>item.current)?.id??'';}).catch(cause=>companyError=cause instanceof Error?cause.message:t('Data tidak dapat dimuat.'));
		void request<{ count: number }>('/v1/notifications/unread-count')
			.then((res) => (unreadFromApi = notificationCount(res.count) ?? 0))
			.catch(() => {});
	});
</script>

<svelte:head><title>{m.profile_page_title()} · PasPapan</title></svelte:head>

<main id="main-content" class="workspace-page profile-page" tabindex="-1">
	<div class="profile-aside">
	{#if current}
		<section class="identity-card panel" aria-label={m.profile_summary()}>
			<div class="identity-main">
				<span class="avatar" aria-hidden="true">{avatarInitials}</span>
				<div class="identity-copy">
					<div class="identity-name"><h2>{profile?.full_name ?? current.user.full_name}</h2><span class="verified-mark" title={m.profile_active_account()}><Icon name="verified" size={16} /></span></div>
					<p class="identity-email">{current.user.email}</p>
					<div class="identity-chips">
						<span>{profile?.employee_code || current.user.id.slice(0, 14)}</span>
						<span>{profile?.job_title || current.company.role}</span>
						<span>{profile?.team || current.company.name}</span>
					</div>
				</div>
				<button type="button" class="logout-icon-button" onclick={handleLogout} disabled={signingOut} aria-label={m.profile_logout()} title={m.profile_logout()}><Icon name="logout" size={17} /></button>
			</div>

			<AlertBanner variant="error" message={logoutError} />
		</section>
	{/if}

	<section class="appearance-card panel" aria-label={m.preferences_title()}>
		{#if companies.length>1}<div class="preference-row"><div class="appearance-copy"><span class="setting-icon"><Icon name="building" size={17}/></span><div><h2>{t('Perusahaan aktif')}</h2><p>{t('Hak akses mengikuti perusahaan yang dipilih.')}</p></div></div><form onsubmit={event=>{event.preventDefault();void switchCompany();}}><label class="sr-only" for="profile-company">{t('Perusahaan aktif')}</label><select id="profile-company" bind:value={selectedCompany} disabled={switchingCompany}>{#each companies as company}<option value={company.id}>{company.name} · {company.role}</option>{/each}</select><button disabled={switchingCompany||selectedCompany===current?.company.id}>{t('Pindah')}</button></form></div>{/if}
		{#if companyError}<p role="alert">{companyError}</p>{/if}
		<div class="preference-row">
			<div class="appearance-copy">
				<span class="setting-icon tone-green"><Icon name="palette" size={17} /></span>
				<div><h2>{m.appearance_title()}</h2><p>{m.appearance_description()}</p></div>
			</div>
			<div class="appearance-options" aria-label={m.appearance_title()}>
				{#each [{ id: 'system', label: m.appearance_system() }, { id: 'light', label: m.appearance_light() }, { id: 'dark', label: m.appearance_dark() }] as option}
					<button type="button" class:active={appearance === option.id} aria-pressed={appearance === option.id} onclick={() => selectAppearance(option.id as AppearanceMode)}>{option.label}</button>
				{/each}
			</div>
		</div>
		<div class="preference-row">
			<div class="appearance-copy">
				<span class="setting-icon"><Icon name="language" size={17} /></span>
				<div><h2>{m.language_title()}</h2><p>{m.language_description()}</p></div>
			</div>
			<div class="language-options" aria-label={m.language_title()}>
				<button type="button" class:active={locale === 'id'} aria-pressed={locale === 'id'} onclick={() => selectLocale('id')}>ID</button>
				<button type="button" class:active={locale === 'en'} aria-pressed={locale === 'en'} onclick={() => selectLocale('en')}>EN</button>
			</div>
		</div>
		<p class="sr-status" aria-live="polite">{appearanceNotice}</p>
	</section>
	</div>

	<section class="account-settings" aria-labelledby="account-settings-title">
		<div class="section-heading-inline"><div><p class="eyebrow">{m.settings_eyebrow()}</p><h2 id="account-settings-title">{m.account_security_title()}</h2></div></div>
	<div class="profile-tabs" aria-label={t('Pengaturan profil')}>
		{#each tabs as tab, index (tab.id)}
			<div class="accordion-item">
				<button
					id={`tab-${tab.id}`}
					type="button"
					class="setting-card setting-trigger"
					class:active={activeTab === tab.id}
					aria-expanded={activeTab === tab.id}
					aria-controls={`panel-${tab.id}`}
					onclick={() => setTab(tab.id)}
					onkeydown={(event) => handleTabKeydown(event, index)}
				>
					<span class="setting-icon"><Icon name={tab.icon} size={19} /></span>
					<span class="setting-copy">
						<strong>{tab.label}</strong>
						<small>{tab.description}</small>
					</span>
					<span class="trigger-chevron" class:open={activeTab === tab.id}><Icon name="chevron" size={16} /></span>
				</button>

				{#if activeTab === tab.id}
					<div id={`panel-${tab.id}`} class="accordion-panel panel" aria-labelledby={`tab-${tab.id}`}>
						{#if tab.id === 'details'}
							{#if loadingProfile}
								<p class="loading" role="status">{m.loading_profile()}</p>
							{:else if profile}
								<form class="profile-form" novalidate onsubmit={saveProfile}>
									<FormField id="profile-full-name" label={m.field_full_name()} icon="profile" bind:value={fullName} bind:error={fullNameError} maxlength={200} autocomplete="name" />
									<FormField id="profile-email" label={m.field_work_email()} type="email" icon="mail" value={profile.email} readonly hint="Email kerja dikelola oleh admin workspace." />
					<FormField id="profile-phone" label={m.field_phone()} type="tel" bind:value={phone} maxlength={64} autocomplete="tel" placeholder={t('Tambahkan nomor telepon')} />
									<label for="profile-address">
										{m.field_address()}
						<textarea id="profile-address" bind:value={address} rows="2" maxlength="500" autocomplete="street-address" placeholder={t('Tambahkan alamat')}></textarea>
									</label>
									<div class="form-actions">
										<button type="submit" disabled={savingProfile || (fullName === profile.full_name && phone === profile.phone && address === profile.address)}>
											{savingProfile ? m.saving() : m.save_changes()}
										</button>
									</div>
									{#if profileError}
										<p class="feedback error" role="alert">
											{profileError}
							<button class="quiet" type="button" onclick={loadProfile} disabled={loadingProfile}>{t('Coba lagi')}</button>
										</p>
									{/if}
									<AlertBanner variant="success" message={profileNotice} />
								</form>
							{/if}
						{:else if tab.id === 'password'}
							<form class="profile-form" novalidate onsubmit={updatePassword}>
								<PasswordField id="current-password" label={m.current_password()} bind:value={currentPassword} bind:error={currentPwError} maxlength={1024} autocomplete="current-password" />
								<PasswordField id="new-password" label={m.new_password()} bind:value={newPassword} bind:error={newPwError} minlength={12} maxlength={1024} autocomplete="new-password" showStrength={true} />
								<PasswordField id="confirm-password" label={m.confirm_password()} bind:value={confirmPassword} bind:error={confirmPwError} minlength={12} maxlength={1024} autocomplete="new-password" />
								<div class="form-actions">
									<button type="submit" disabled={changingPassword}>
									{changingPassword ? m.updating() : m.update_password()}
									</button>
								</div>
								<AlertBanner variant="error" message={passwordError} />
								<AlertBanner variant="success" message={passwordNotice} />
							</form>
						{:else if tab.id === 'security'}
							<div class="security-summary">
							<div class="security-status"><span><Icon name="shield" size={17} /></span><div><strong>{t('Proteksi akun aktif')}</strong><p>{t('Mutasi akun memerlukan origin tepercaya dan konfirmasi kredensial.')}</p></div></div>
							<button type="button" class="security-action" onclick={() => setTab('password')}><span><b>{t('Perbarui kata sandi')}</b><small>{t('Minimal 12 karakter; sesi lain akan dicabut.')}</small></span><Icon name="arrow" size={15} /></button>
							<button type="button" class="security-action" onclick={() => setTab('sessions')}><span><b>{t('Tinjau perangkat aktif')}</b><small>{t('Periksa dan keluarkan sesi yang tidak dikenal.')}</small></span><Icon name="arrow" size={15} /></button>
							</div>
						{:else if tab.id === 'sessions'}
							{#if sessionsError}
								<p class="feedback error" role="alert">
									{sessionsError}
									<button class="quiet" onclick={loadSessions} disabled={loadingSessions}>{t('Coba lagi')}</button>
								</p>
							{/if}
							<AlertBanner variant="error" message={sessionError} />
							{#if loadingSessions}
								<p class="loading" role="status">{t('Memuat sesi perangkat…')}</p>
							{:else if !sessions.length && !sessionsError}
								<p class="loading">{t('Tidak ada sesi aktif.')}</p>
							{:else}
								<ul class="session-list">
									{#each sessions as entry (entry.id)}
										<li class="session-row">
											<span class="device-icon"><Icon name="smartphone" size={18} /></span>
											<div class="session-copy">
												<div class="session-title">
											<strong>{entry.current ? t('Perangkat ini') : t('Sesi perangkat')}</strong>
											{#if entry.current}<span class="current-badge">{t('Saat ini')}</span>{/if}
												</div>
											<small>{t('Masuk')} {formatDate(entry.created_at)} · {t('Berakhir')} {formatDate(entry.expires_at)}</small>
											</div>
											{#if !entry.current}
												<button
													type="button"
													class="revoke-button"
													onclick={() => {
														sessionToRevoke = entry;
														sessionError = '';
														revokePwError = '';
														revokePassword = '';
													}}
												>
													Keluarkan
												</button>
											{/if}
										</li>
									{/each}
								</ul>
								{#if !otherSessions.length}
									<p class="session-footnote">{t('Hanya sesi perangkat ini yang sedang aktif.')}</p>
								{/if}
							{/if}
						{/if}
					</div>
				{/if}
			</div>
		{/each}
	</div>
	</section>

	{#if teamLinks.length > 0}
		<section class="panel admin-shortcuts" aria-label={t('Menu operasional')}>
			<p class="eyebrow">{t('OPERASIONAL & ADMIN')}</p>
			<div class="admin-grid">
				{#each teamLinks as item}
					<a href={item.href}>
						<Icon name={item.icon} size={16} />
						<span>{item.label}</span>
					</a>
				{/each}
				{#if current?.company.role === 'superadmin'}
					<a href="/admin/api-docs">
						<Icon name="code" size={16} />
						<span>API Docs & Postman</span>
					</a>
				{/if}
			</div>
		</section>
	{/if}

	{#if sessionToRevoke}
		<dialog
			bind:this={revokeDialogElement}
			class="revoke-dialog"
			aria-labelledby="revoke-title"
			onkeydown={closeRevokeDialogOnEscape}
			oncancel={(event) => {
				event.preventDefault();
				sessionToRevoke = null;
			}}
			onclose={() => (sessionToRevoke = null)}
		>
			<form novalidate onsubmit={revokeOtherSession}>
				<p class="eyebrow">{t('KEAMANAN AKUN')}</p>
				<h2 id="revoke-title">{t('Keluarkan sesi perangkat?')}</h2>
				<p>{t('Perangkat lain akan diminta masuk kembali. Masukkan kata sandi Anda untuk mengonfirmasi.')}</p>
				<PasswordField id="revoke-password" label={t('Kata sandi saat ini')} bind:value={revokePassword} bind:error={revokePwError} maxlength={1024} autocomplete="current-password" />
				<AlertBanner variant="error" message={sessionError} />
				<div class="dialog-actions">
					<button type="button" class="quiet cancel-button" onclick={() => (sessionToRevoke = null)}>{t('Batal')}</button>
					<button type="submit" disabled={revokingSession}>{revokingSession ? t('Mengeluarkan…') : t('Keluarkan sesi')}</button>
				</div>
			</form>
		</dialog>
	{/if}
</main>

<style>
	.profile-page {
		max-width: 640px;
		display: grid;
		gap: 12px;
		align-content: start;
	}

	.verified-mark {
		display: inline-grid;
		place-items: center;
		color: #10b981;
	}

	/* Identity Card */
	.identity-card {
		display: grid;
		gap: 14px;
		padding: 18px;
		border-radius: var(--radius-lg);
		border: 1px solid #e2ebe3;
		background: var(--surface);
	}
	.identity-main {
		display: flex;
		align-items: center;
		gap: 14px;
	}
	.avatar {
		display: grid;
		place-items: center;
		flex: 0 0 64px;
		width: 64px;
		height: 64px;
		border-radius: 50%;
		background: #f9ebc8;
		color: #7c3f00;
		font-size: 22px;
		font-weight: 800;
	}
	.identity-copy {
		min-width: 0;
		flex: 1;
		display: grid;
		gap: 2px;
	}
	.identity-copy h2 {
		margin: 0;
		font-size: 20px;
		font-weight: 800;
		color: var(--ink);
		overflow-wrap: anywhere;
	}
	.identity-email {
		margin: 0;
		font-size: 13.5px;
		color: #5b6b79;
		overflow-wrap: anywhere;
	}
	.identity-chips {
		display: flex;
		flex-wrap: wrap;
		gap: 6px;
		margin-top: 8px;
	}
	.identity-chips span {
		padding: 4px 10px;
		border-radius: 999em;
		border: 1px solid #dce6ef;
		background: #f8fafc;
		color: #334155;
		font-size: 11.5px;
		font-weight: 750;
		text-transform: capitalize;
	}
	/* Settings Cards Stack */
	.profile-tabs {
		display: grid;
		gap: 10px;
	}
	.accordion-item {
		display: grid;
		gap: 6px;
	}
	.setting-card {
		display: flex;
		align-items: center;
		gap: 13px;
		width: 100%;
		padding: 14px 16px;
		border-radius: var(--radius-lg);
		border: 1px solid #e4ece5;
		background: var(--surface);
		color: var(--ink);
		text-align: start;
		box-shadow: 0 4px 14px -12px rgba(24, 34, 29, 0.14);
	}
	.setting-trigger {
		min-height: 70px;
		cursor: pointer;
	}
	.setting-trigger:hover,
	.setting-trigger.active {
		border-color: #bfd7c0;
		background: #f9fcf9;
	}
	.setting-icon {
		display: grid;
		place-items: center;
		width: 42px;
		height: 42px;
		flex: 0 0 42px;
		border-radius: 50%;
		border: 1px solid #e6eef4;
		background: #f8fafc;
		color: #334155;
	}
	.setting-icon.tone-green {
		border-color: #d3e8d4;
		background: #e6f3e6;
		color: var(--primary-strong);
	}
	.setting-copy {
		display: grid;
		gap: 2px;
		min-width: 0;
		flex: 1;
	}
	.setting-copy strong {
		font-size: 15px;
		font-weight: 800;
		color: var(--ink);
	}
	.setting-copy small {
		font-size: 12.5px;
		font-weight: 500;
		color: #64748b;
	}

	/* Expanded Panel Content */
	.accordion-panel {
		padding: 15px 16px;
		border-radius: var(--radius-lg);
		border: 1px solid #cfe2d0;
		background: var(--surface);
	}
	.profile-form {
		display: grid;
		gap: 12px;
	}
	.profile-form label {
		margin: 0;
	}
	.form-actions {
		display: flex;
		justify-content: flex-end;
	}
	.feedback {
		padding: 9px 11px;
		border-radius: var(--radius-sm);
		font-size: 12px;
	}
	.feedback.error {
		border: 1px solid #edd2ce;
		background: #fff5f3;
		color: #88443d;
	}
	.loading {
		color: var(--muted);
		font-size: 12.5px;
	}

	.security-summary {
		display: grid;
		gap: 10px;
	}
	.session-list {
		display: grid;
		margin: 0;
		padding: 0;
		list-style: none;
	}
	.session-row {
		display: flex;
		align-items: center;
		gap: 10px;
		padding: 10px 0;
	}
	.session-row + .session-row {
		border-top: 1px solid var(--line);
	}
	.device-icon {
		display: grid;
		place-items: center;
		width: 36px;
		height: 36px;
		flex: 0 0 36px;
		border-radius: 50%;
		background: var(--surface-alt);
		color: var(--primary-strong);
	}
	.session-copy {
		min-width: 0;
		flex: 1;
	}
	.session-title {
		display: flex;
		align-items: center;
		gap: 6px;
	}
	.session-title strong {
		font-size: 13px;
	}
	.current-badge {
		padding: 2px 7px;
		border-radius: 999em;
		background: var(--primary-soft);
		color: var(--primary-strong);
		font-size: 10px;
		font-weight: 800;
	}
	.session-copy small {
		display: block;
		margin-top: 2px;
		color: var(--muted);
		font-size: 11.5px;
	}
	.revoke-button {
		min-height: 32px;
		padding: 5px 10px;
		border-radius: var(--radius-sm);
		border: 1px solid #e5c7c3;
		background: #fff8f7;
		color: #87443e;
		font-size: 11px;
		font-weight: 750;
		box-shadow: none;
	}
	.session-footnote {
		margin-top: 8px;
		padding: 8px 10px;
		border-radius: var(--radius-sm);
		background: var(--surface-alt);
		color: var(--muted);
		font-size: 11.5px;
	}

	/* Admin Shortcuts */
	.admin-shortcuts {
		display: grid;
		gap: 8px;
	}
	.admin-grid {
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 7px;
	}
	.admin-grid a {
		display: flex;
		align-items: center;
		gap: 8px;
		padding: 9px 11px;
		border-radius: var(--radius);
		border: 1px solid var(--line);
		background: var(--surface-alt);
		color: var(--ink) !important;
		font-size: 12.5px;
		font-weight: 700;
		text-decoration: none;
	}
	.admin-grid a:hover {
		background: var(--primary-soft);
		color: var(--primary-strong) !important;
	}

	.revoke-dialog {
		position: fixed;
		inset: 50% auto auto 50%;
		width: min(420px, calc(100vw - 32px));
		max-width: 100%;
		margin: 0;
		padding: 20px;
		border-radius: var(--radius-lg);
		transform: translate(-50%, -50%);
		border: 1px solid var(--line);
		background: var(--surface);
		color: var(--ink);
		box-shadow: 0 24px 70px -25px rgba(0, 0, 0, 0.45);
	}
	.revoke-dialog::backdrop {
		background: rgba(20, 32, 24, 0.44);
	}
	.revoke-dialog h2 {
		margin: 0 0 6px;
		font-size: 17px;
	}
	.revoke-dialog form > p:not(.eyebrow) {
		margin: 0 0 14px;
		color: var(--muted);
		font-size: 12px;
	}
	.dialog-actions {
		display: flex;
		justify-content: flex-end;
		gap: 8px;
		margin-top: 14px;
		padding-top: 12px;
		border-top: 1px solid var(--line);
	}
	.cancel-button {
		background: var(--surface) !important;
		color: var(--ink) !important;
		box-shadow: none !important;
	}

	/* Compact account workspace */
	.profile-page {
		max-width: 920px;
		grid-template-columns: minmax(240px, 280px) minmax(0, 1fr);
		gap: 12px;
	}
	.profile-aside { grid-column: 1; grid-row: 1; display: grid; align-content: start; gap: 12px; }
	.identity-card {
		align-self: start;
		padding: 14px;
		background: var(--surface);
		border-color: var(--line);
	}
	.identity-main { align-items: flex-start; gap: 11px; }
	.avatar {
		width: 48px;
		height: 48px;
		flex-basis: 48px;
		border-radius: var(--radius);
		background: var(--primary-soft);
		color: var(--primary-strong);
		font-size: 16px;
	}
	.identity-name { display: flex; align-items: center; gap: 5px; min-width: 0; }
	.identity-name h2 { font-size: 15px; }
	.identity-email { color: var(--muted); font-size: 11.5px; }
	.identity-chips { margin-top: 7px; gap: 4px; }
	.identity-chips span {
		padding: 3px 6px;
		border: 0;
		border-radius: var(--radius-sm);
		background: var(--surface-alt);
		color: var(--muted);
		font-size: 9.5px;
	}
	.logout-icon-button {
		display: grid;
		place-items: center;
		flex: 0 0 34px;
		width: 34px;
		height: 34px;
		min-height: 34px !important;
		padding: 0 !important;
		border: 1px solid #e7c7c4 !important;
		border-radius: var(--radius) !important;
		background: #fff7f6 !important;
		color: #a33a32 !important;
	}
	.logout-icon-button:hover:not(:disabled) { background: #ffebe8 !important; }

	.appearance-card {
		align-self: start;
		display: grid;
		gap: 0;
		padding: 12px !important;
	}
	.preference-row { display: grid; gap: 8px; }
	.preference-row + .preference-row { margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--line); }
	.appearance-copy { display: flex; align-items: center; gap: 9px; }
	.appearance-copy h2 { margin: 0 0 1px; font-size: 13px; }
	.appearance-copy p { color: var(--muted); font-size: 10.5px; line-height: 1.35; }
	.appearance-options {
		display: grid;
		grid-template-columns: repeat(3, minmax(0, 1fr));
		gap: 4px;
		padding: 3px;
		border-radius: var(--radius);
		background: var(--surface-alt);
	}
	.appearance-options button {
		min-height: 30px !important;
		padding: 5px 6px !important;
		border: 1px solid transparent !important;
		border-radius: var(--radius-sm) !important;
		background: transparent !important;
		color: var(--muted) !important;
		font-size: 10.5px !important;
		box-shadow: none !important;
	}
	.appearance-options button:hover { color: var(--ink) !important; }
	.appearance-options button.active {
		border-color: var(--line) !important;
		background: var(--surface) !important;
		color: var(--primary-strong) !important;
		box-shadow: var(--shadow-soft) !important;
	}
	.language-options {
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 4px;
		padding: 3px;
		border-radius: var(--radius);
		background: var(--surface-alt);
	}
	.language-options button {
		min-height: 30px !important;
		padding: 5px 6px !important;
		border: 1px solid transparent !important;
		border-radius: var(--radius-sm) !important;
		background: transparent !important;
		color: var(--muted) !important;
		font-size: 10.5px !important;
		box-shadow: none !important;
	}
	.language-options button.active { background: var(--surface) !important; color: var(--primary-strong) !important; box-shadow: var(--shadow-soft) !important; }
	.sr-status {
		position: absolute;
		width: 1px;
		height: 1px;
		padding: 0;
		margin: -1px;
		overflow: hidden;
		clip: rect(0, 0, 0, 0);
		white-space: nowrap;
		border: 0;
	}

	.account-settings {
		grid-column: 2;
		grid-row: 1;
		padding: 2px 0 0;
		border: 0;
		background: transparent;
	}
	.section-heading-inline { margin-bottom: 10px; }
	.section-heading-inline h2 { margin: 0; font-size: 15px; }
	.profile-tabs { gap: 6px; }
	.accordion-item { gap: 4px; border: 0; border-radius: var(--radius); overflow: visible; background: transparent; }
	button.setting-trigger {
		min-height: 56px;
		padding: 8px 10px;
		border: 0 !important;
		border-radius: var(--radius) !important;
		background: var(--surface) !important;
		color: var(--ink) !important;
		box-shadow: none !important;
	}
	button.setting-trigger:hover,
	button.setting-trigger.active {
		background: var(--surface-alt) !important;
		color: var(--ink) !important;
	}
	button.setting-trigger.active .setting-icon { border-color: var(--primary); background: var(--primary-soft); color: var(--primary-strong); }
	.setting-icon {
		width: 34px;
		height: 34px;
		flex-basis: 34px;
		border-radius: var(--radius-sm);
		border-color: var(--line);
		background: var(--surface-alt);
		color: var(--muted);
	}
	.setting-copy strong { font-size: 12.5px; }
	.setting-copy small { color: var(--muted); font-size: 10.5px; }
	.trigger-chevron { display: grid; place-items: center; color: var(--muted); transition: transform 180ms cubic-bezier(.16, 1, .3, 1); }
	.trigger-chevron.open { transform: rotate(180deg); }
	.accordion-panel {
		padding: 12px;
		border: 0;
		border-top: 0;
		border-radius: var(--radius);
		background: var(--surface);
		box-shadow: var(--panel-shadow);
		animation: reveal-panel 180ms cubic-bezier(.16, 1, .3, 1);
	}
	@keyframes reveal-panel { from { opacity: 0; transform: translateY(-3px); } }
	.security-status { display: flex; align-items: flex-start; gap: 9px; padding: 10px; border-radius: var(--radius); background: var(--primary-soft); }
	.security-status > span { display: grid; place-items: center; color: var(--primary-strong); }
	.security-status strong { display: block; font-size: 12px; }
	.security-status p { margin-top: 2px; color: var(--muted); font-size: 10.5px; }
	.security-action {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 10px;
		width: 100%;
		min-height: 48px !important;
		padding: 8px 10px !important;
		border: 1px solid var(--line) !important;
		border-radius: var(--radius) !important;
		background: var(--surface) !important;
		color: var(--ink) !important;
		text-align: left;
	}
	.security-action:hover { background: var(--surface-alt) !important; border-color: var(--primary) !important; }
	.security-action span { display: grid; gap: 1px; }
	.security-action b { font-size: 11.5px; }
	.security-action small { color: var(--muted); font-size: 9.5px; font-weight: 500; }
	.session-copy small { line-height: 1.35; }
	.admin-shortcuts { grid-column: 1 / -1; }
	:global(:root[data-theme='dark']) .logout-icon-button { border-color: #713f3c !important; background: #362321 !important; color: #ffaaa2 !important; }
	:global(:root[data-theme='dark']) .revoke-button { border-color: #713f3c; background: #362321; color: #ffb0a9; }
	:global(:root[data-theme='dark']) .setting-icon.tone-green { border-color: var(--line); background: var(--surface-alt); color: var(--primary); }
	:global(:root[data-theme='dark']) .identity-card,
	:global(:root[data-theme='dark']) .appearance-card,
	:global(:root[data-theme='dark']) .account-settings {
		border-color: transparent;
		box-shadow: var(--panel-shadow);
	}
	:global(:root[data-theme='dark']) .accordion-item {
		border-color: transparent;
		background: var(--surface-alt);
	}
	:global(:root[data-theme='dark']) button.setting-trigger,
	:global(:root[data-theme='dark']) button.setting-trigger:hover,
	:global(:root[data-theme='dark']) button.setting-trigger.active,
	:global(:root[data-theme='dark']) .accordion-panel {
		background: var(--surface-alt) !important;
	}
	:global(:root[data-theme='dark']) .setting-icon {
		border-color: transparent;
		background: #2b2f30;
	}
	:global(:root[data-theme='dark']) .appearance-options button.active {
		border-color: transparent !important;
	}
	:global(:root[data-theme='dark']) .language-options button.active { border-color: transparent !important; }

	@media (max-width: 760px) {
		.profile-page { grid-template-columns: minmax(0, 1fr); gap: 9px; }
		.profile-aside, .account-settings, .admin-shortcuts { grid-column: 1; grid-row: auto; }
		.account-settings { padding: 2px 0 0; }
		.identity-card { gap: 10px; padding: 10px; }
		.identity-main { gap: 10px; }
		.avatar { width: 44px; height: 44px; flex-basis: 44px; font-size: 15px; }
		.identity-copy h2 { font-size: 16px; }
		.identity-email { font-size: 11.5px; }
		.identity-chips { gap: 4px; margin-top: 5px; }
		.identity-chips span { padding: 2px 7px; font-size: 9.5px; }
		.profile-tabs { gap: 6px; }
		.setting-card { gap: 9px; padding: 9px 10px; }
		.setting-trigger { min-height: 54px; }
		.setting-icon { width: 34px; height: 34px; flex-basis: 34px; }
		.setting-copy strong { font-size: 12.5px; }
		.setting-copy small { font-size: 10.5px; }
	}
</style>
