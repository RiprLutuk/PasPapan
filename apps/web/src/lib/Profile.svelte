<script lang="ts">
	import { goto } from '$app/navigation';
	import { onMount } from 'svelte';
	import { visibleOperations } from './access';
	import { request } from './api';
	import { realtimeState } from './realtime.svelte';
	import { workSession } from './session';
	import Icon from './Icon.svelte';
	import { AlertBanner, FormField, PasswordField } from './ui';

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
		{ id: 'details', label: 'Detail', description: 'Perbarui informasi profil Anda', icon: 'id-card' },
		{ id: 'password', label: 'Kata Sandi', description: 'Ubah dan perkuat kata sandi Anda', icon: 'key' },
		{ id: 'security', label: 'Keamanan', description: 'Kelola verifikasi dan keamanan akun', icon: 'shield' },
		{ id: 'sessions', label: 'Sesi', description: 'Tinjau & keluarkan perangkat aktif', icon: 'smartphone' }
	];

	let activeTab = $state<ProfileTab | null>(null);
	let lang = $state<'ID' | 'EN'>('ID');
	let darkMode = $state(false);
	let profile = $state<ProfileRecord | null>(null);
	let sessions = $state<AccountSession[]>([]);
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
	let unreadCount = $derived(realtimeState.lastPulse?.unread_notifications ?? unreadFromApi);
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
			profileError = cause instanceof Error ? cause.message : 'Informasi profil tidak dapat dimuat.';
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
			sessionsError = cause instanceof Error ? cause.message : 'Sesi perangkat tidak dapat dimuat.';
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
			fullNameError = 'Nama lengkap wajib diisi.';
			return;
		}
		savingProfile = true;
		try {
			await request<void>('/v1/account/profile', {
				method: 'POST',
				body: JSON.stringify({ full_name: fullName.trim(), phone, address, version: profile.version })
			});
			profileNotice = 'Informasi profil berhasil disimpan.';
			await loadProfile();
			await session.refresh();
		} catch (cause) {
			profileError = cause instanceof Error ? cause.message : 'Profil tidak dapat disimpan.';
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
		if (!currentPassword) currentPwError = 'Kata sandi saat ini wajib diisi.';
		if (!newPassword) newPwError = 'Kata sandi baru wajib diisi.';
		else if (newPassword.length < 12) newPwError = 'Kata sandi baru minimal 12 karakter.';
		if (!confirmPassword) confirmPwError = 'Konfirmasi kata sandi wajib diisi.';
		else if (newPassword && newPassword !== confirmPassword) confirmPwError = 'Konfirmasi kata sandi baru belum cocok.';
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
			passwordNotice = 'Kata sandi diperbarui. Sesi di perangkat lain telah dikeluarkan.';
			await loadSessions();
			await session.refresh();
		} catch (cause) {
			passwordError = cause instanceof Error ? cause.message : 'Kata sandi tidak dapat diperbarui.';
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
			revokePwError = 'Kata sandi saat ini wajib diisi.';
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
			sessionError = cause instanceof Error ? cause.message : 'Sesi perangkat tidak dapat dikeluarkan.';
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
			logoutError = 'Gagal keluar. Coba lagi.';
		} finally {
			signingOut = false;
		}
	}

	function formatDate(value: string) {
		const date = new Date(value);
		if (Number.isNaN(date.getTime())) return value;
		return new Intl.DateTimeFormat('id-ID', {
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

	onMount(() => {
		void loadProfile();
		void loadSessions();
		void request<{ unread_count: number }>('/v1/notifications/unread-count')
			.then((res) => (unreadFromApi = res.unread_count))
			.catch(() => {});
	});
</script>

<svelte:head><title>Profil · PasPapan</title></svelte:head>

<main id="main-content" class="workspace-page profile-page" tabindex="-1">
	<!-- Soft Green Top Header -->
	<header class="profile-topbar">
		<div class="topbar-left">
			<a class="circle-btn" href="/app/today" aria-label="Kembali ke Beranda">
				<Icon name="back" size={18} />
			</a>
			<div class="topbar-title">
				<h1>Profil</h1>
				<span class="verified-mark" title="Akun terverifikasi">
					<Icon name="verified" size={18} />
				</span>
			</div>
		</div>

		<a class="circle-btn notif-btn" href="/app/notifications" aria-label="Notifikasi">
			<Icon name="bell" size={18} />
			{#if unreadCount > 0}
				<span class="notif-badge">{unreadCount > 99 ? '99+' : unreadCount}</span>
			{/if}
		</a>
	</header>

	<!-- Identity Card -->
	{#if current}
		<section class="identity-card panel" aria-label="Ringkasan akun">
			<div class="identity-main">
				<span class="avatar" aria-hidden="true">{avatarInitials}</span>
				<div class="identity-copy">
					<p class="eyebrow">MASUK SEBAGAI</p>
					<h2>{profile?.full_name ?? current.user.full_name}</h2>
					<p class="identity-email">{current.user.email}</p>
					<div class="identity-chips">
						<span>{profile?.employee_code || current.user.id.slice(0, 14)}</span>
						<span>{profile?.team || current.company.name}</span>
						<span>{profile?.job_title || current.company.role}</span>
					</div>
				</div>
			</div>

			<AlertBanner variant="error" message={logoutError} />
			<button type="button" class="logout-banner-btn" onclick={handleLogout} disabled={signingOut}>
				<Icon name="logout" size={16} />
				<span>{signingOut ? 'Keluar…' : 'Keluar'}</span>
			</button>
		</section>
	{/if}

	<!-- Quick Preferences (Bahasa & Tampilan) -->
	<section class="settings-stack" aria-label="Preferensi aplikasi">
		<div class="setting-card">
			<span class="setting-icon tone-green"><Icon name="language" size={19} /></span>
			<div class="setting-copy">
				<strong>Bahasa</strong>
				<small>Ganti bahasa (Indonesia/Inggris)</small>
			</div>
			<button
				type="button"
				class="lang-pill"
				onclick={() => (lang = lang === 'ID' ? 'EN' : 'ID')}
				aria-label="Ganti bahasa"
			>
				<span class="flag-circle" aria-hidden="true">🇮🇩</span>
				<b>{lang === 'ID' ? 'EN' : 'ID'}</b>
			</button>
		</div>

		<div class="setting-card">
			<span class="setting-icon tone-green"><Icon name="palette" size={19} /></span>
			<div class="setting-copy">
				<strong>Tampilan</strong>
				<small>Mode terang/gelap</small>
			</div>
			<button
				type="button"
				class="theme-btn"
				onclick={() => (darkMode = !darkMode)}
				aria-label="Ubah mode tampilan"
			>
				<Icon name={darkMode ? 'moon' : 'sun'} size={17} />
			</button>
		</div>
	</section>

	<!-- Account Settings Accordion Cards (role="tablist") -->
	<div class="profile-tabs" role="tablist" aria-label="Pengaturan profil" aria-orientation="vertical">
		{#each tabs as tab, index (tab.id)}
			<div class="accordion-item">
				<button
					id={`tab-${tab.id}`}
					type="button"
					class="setting-card setting-trigger"
					class:active={activeTab === tab.id}
					role="tab"
					aria-selected={activeTab === tab.id}
					aria-controls={`panel-${tab.id}`}
					tabindex={activeTab === tab.id || (activeTab === null && index === 0) ? 0 : -1}
					onclick={() => setTab(tab.id)}
					onkeydown={(event) => handleTabKeydown(event, index)}
				>
					<span class="setting-icon"><Icon name={tab.icon} size={19} /></span>
					<span class="setting-copy">
						<strong>{tab.label}</strong>
						<small>{tab.description}</small>
					</span>
				</button>

				{#if activeTab === tab.id}
					<div id={`panel-${tab.id}`} class="accordion-panel panel" role="tabpanel" aria-labelledby={`tab-${tab.id}`}>
						{#if tab.id === 'details'}
							{#if loadingProfile}
								<p class="loading" role="status">Memuat informasi profil…</p>
							{:else if profile}
								<form class="profile-form" novalidate onsubmit={saveProfile}>
									<FormField id="profile-full-name" label="Nama lengkap" icon="profile" bind:value={fullName} bind:error={fullNameError} maxlength={200} autocomplete="name" />
									<FormField id="profile-email" label="Email kerja" type="email" icon="mail" value={profile.email} readonly hint="Email kerja dikelola oleh admin workspace." />
									<FormField id="profile-phone" label="Nomor telepon" type="tel" bind:value={phone} maxlength={64} autocomplete="tel" placeholder="Tambahkan nomor telepon" />
									<label for="profile-address">
										Alamat
										<textarea id="profile-address" bind:value={address} rows="2" maxlength="500" autocomplete="street-address" placeholder="Tambahkan alamat"></textarea>
									</label>
									<div class="form-actions">
										<button type="submit" disabled={savingProfile || (fullName === profile.full_name && phone === profile.phone && address === profile.address)}>
											{savingProfile ? 'Menyimpan…' : 'Simpan perubahan'}
										</button>
									</div>
									{#if profileError}
										<p class="feedback error" role="alert">
											{profileError}
											<button class="quiet" type="button" onclick={loadProfile} disabled={loadingProfile}>Coba lagi</button>
										</p>
									{/if}
									<AlertBanner variant="success" message={profileNotice} />
								</form>
							{/if}
						{:else if tab.id === 'password'}
							<form class="profile-form" novalidate onsubmit={updatePassword}>
								<PasswordField id="current-password" label="Kata sandi saat ini" bind:value={currentPassword} bind:error={currentPwError} maxlength={1024} autocomplete="current-password" />
								<PasswordField id="new-password" label="Kata sandi baru" bind:value={newPassword} bind:error={newPwError} minlength={12} maxlength={1024} autocomplete="new-password" showStrength={true} />
								<PasswordField id="confirm-password" label="Konfirmasi kata sandi baru" bind:value={confirmPassword} bind:error={confirmPwError} minlength={12} maxlength={1024} autocomplete="new-password" />
								<div class="form-actions">
									<button type="submit" disabled={changingPassword}>
										{changingPassword ? 'Memperbarui…' : 'Perbarui kata sandi'}
									</button>
								</div>
								<AlertBanner variant="error" message={passwordError} />
								<AlertBanner variant="success" message={passwordNotice} />
							</form>
						{:else if tab.id === 'security'}
							<div class="security-summary">
								<div class="security-row">
									<span class="security-badge">Aktif</span>
									<div>
										<strong>Verifikasi Sesi & Kredensial</strong>
										<p>Akun Anda dilindungi dengan cookie sesi terenkripsi dan validasi perangkat.</p>
									</div>
								</div>
								<div class="security-row">
									<span class="security-badge">{sessions.length || 1} perangkat</span>
									<div>
										<strong>Perlindungan Kata Sandi</strong>
										<p>Mengubah kata sandi akan otomatis mencabut sesi perangkat lain.</p>
									</div>
								</div>
							</div>
						{:else if tab.id === 'sessions'}
							{#if sessionsError}
								<p class="feedback error" role="alert">
									{sessionsError}
									<button class="quiet" onclick={loadSessions} disabled={loadingSessions}>Coba lagi</button>
								</p>
							{/if}
							<AlertBanner variant="error" message={sessionError} />
							{#if loadingSessions}
								<p class="loading" role="status">Memuat sesi perangkat…</p>
							{:else if !sessions.length && !sessionsError}
								<p class="loading">Tidak ada sesi aktif.</p>
							{:else}
								<ul class="session-list">
									{#each sessions as entry (entry.id)}
										<li class="session-row">
											<span class="device-icon"><Icon name="smartphone" size={18} /></span>
											<div class="session-copy">
												<div class="session-title">
													<strong>{entry.current ? 'Perangkat ini' : 'Sesi perangkat'}</strong>
													{#if entry.current}<span class="current-badge">Saat ini</span>{/if}
												</div>
												<small>Dimulai {formatDate(entry.created_at)}</small>
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
									<p class="session-footnote">Hanya sesi perangkat ini yang sedang aktif.</p>
								{/if}
							{/if}
						{/if}
					</div>
				{/if}
			</div>
		{/each}
	</div>

	{#if teamLinks.length > 0}
		<section class="panel admin-shortcuts" aria-label="Menu operasional">
			<p class="eyebrow">OPERASIONAL & ADMIN</p>
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
				<p class="eyebrow">KEAMANAN AKUN</p>
				<h2 id="revoke-title">Keluarkan sesi perangkat?</h2>
				<p>Perangkat lain akan diminta masuk kembali. Masukkan kata sandi Anda untuk mengonfirmasi.</p>
				<PasswordField id="revoke-password" label="Kata sandi saat ini" bind:value={revokePassword} bind:error={revokePwError} maxlength={1024} autocomplete="current-password" />
				<AlertBanner variant="error" message={sessionError} />
				<div class="dialog-actions">
					<button type="button" class="quiet cancel-button" onclick={() => (sessionToRevoke = null)}>Batal</button>
					<button type="submit" disabled={revokingSession}>{revokingSession ? 'Mengeluarkan…' : 'Keluarkan sesi'}</button>
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

	/* Topbar */
	.profile-topbar {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 12px;
		padding: 12px 14px;
		border-radius: var(--radius-lg);
		background: linear-gradient(180deg, #e1efe0 0%, #eef6ee 100%);
		border: 1px solid #d2e4d1;
	}
	.topbar-left {
		display: flex;
		align-items: center;
		gap: 12px;
	}
	.circle-btn {
		position: relative;
		display: grid;
		place-items: center;
		width: 40px;
		height: 40px;
		border-radius: 50%;
		background: #ffffff;
		border: 1px solid #dce6dd;
		color: var(--ink) !important;
		text-decoration: none;
		box-shadow: 0 2px 8px -4px rgba(24, 34, 29, 0.14);
	}
	.topbar-title {
		display: flex;
		align-items: center;
		gap: 6px;
	}
	.topbar-title h1 {
		margin: 0;
		font-size: 20px;
		font-weight: 800;
		color: var(--ink);
	}
	.verified-mark {
		display: inline-grid;
		place-items: center;
		color: #10b981;
	}
	.notif-badge {
		position: absolute;
		top: -3px;
		right: -3px;
		min-width: 19px;
		height: 19px;
		padding-inline: 5px;
		border-radius: 999em;
		background: #e11d48;
		border: 2px solid #ffffff;
		color: #ffffff;
		font-size: 10px;
		font-weight: 800;
		display: grid;
		place-items: center;
	}

	/* Identity Card */
	.identity-card {
		display: grid;
		gap: 14px;
		padding: 18px;
		border-radius: var(--radius-lg);
		border: 1px solid #e2ebe3;
		background: #ffffff;
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
	.identity-copy .eyebrow {
		margin: 0;
		font-size: 10.5px;
		font-weight: 800;
		letter-spacing: 0.12em;
		color: var(--primary);
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
	.logout-banner-btn {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 8px;
		width: 100%;
		min-height: 44px;
		padding: 10px 16px;
		border-radius: 999em;
		border: 1px solid #f5c2c7;
		background: #fff5f5;
		color: #c9182b;
		font-size: 14px;
		font-weight: 800;
		box-shadow: none;
	}
	.logout-banner-btn:hover:not(:disabled) {
		background: #ffe8e8;
		border-color: #f1aeb5;
	}

	/* Settings Cards Stack */
	.settings-stack,
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
		background: #ffffff;
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

	.lang-pill {
		display: inline-flex;
		align-items: center;
		gap: 7px;
		min-height: 38px;
		padding: 4px 12px 4px 6px;
		border-radius: 999em;
		border: 1px solid #dce4ec;
		background: #f8fafc;
		color: var(--ink);
		font-size: 12.5px;
		font-weight: 800;
		box-shadow: none;
	}
	.lang-pill:hover {
		background: #eff4f8 !important;
	}
	.flag-circle {
		display: grid;
		place-items: center;
		width: 28px;
		height: 28px;
		border-radius: 50%;
		background: #428542;
		font-size: 14px;
	}
	.theme-btn {
		display: grid;
		place-items: center;
		width: 40px;
		height: 40px;
		min-height: 40px;
		padding: 0;
		border-radius: var(--radius);
		border: 1px solid #dce4ec;
		background: #ffffff;
		color: var(--ink);
		box-shadow: none;
	}
	.theme-btn:hover {
		background: #f8fafc !important;
	}

	/* Expanded Panel Content */
	.accordion-panel {
		padding: 15px 16px;
		border-radius: var(--radius-lg);
		border: 1px solid #cfe2d0;
		background: #ffffff;
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
	.security-row {
		display: flex;
		align-items: flex-start;
		gap: 10px;
		padding: 10px 12px;
		border-radius: var(--radius);
		background: var(--surface-alt);
	}
	.security-badge {
		padding: 3px 8px;
		border-radius: 999em;
		background: var(--primary-soft);
		color: var(--primary-strong);
		font-size: 11px;
		font-weight: 800;
		white-space: nowrap;
	}
	.security-row strong {
		display: block;
		font-size: 13px;
		color: var(--ink);
	}
	.security-row p {
		margin: 2px 0 0;
		font-size: 12px;
		color: var(--muted);
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
</style>
