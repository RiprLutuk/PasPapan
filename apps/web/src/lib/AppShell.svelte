<script lang="ts">
	import { onMount, setContext, type Snippet } from 'svelte';
	import { page } from '$app/state';
	import { goto, afterNavigate } from '$app/navigation';
	import { ApiError, currentSession, request, type CurrentSession } from './api';
	import {
		getCachedSession,
		getCachedUnreadCount,
		sessionKey,
		setCachedSession,
		setCachedUnreadCount
	} from './session';
	import { visibleOperations, requiredPermission } from './access';
	import {
		connectRealtimeStream,
		dismissLiveToast,
		realtimeState,
		syncPwaAppBadge
	} from './realtime.svelte';
	import Icon from './Icon.svelte';
	import Brand from './Brand.svelte';

	let { children, admin = false }: { children: Snippet; admin?: boolean } = $props();
	let session = $state<CurrentSession | null>(getCachedSession());
	let loading = $state(getCachedSession() === null), error = $state(''), expired = $state(false), signingOut = $state(false);
	let sidebarHidden = $state(false);
	let unreadNotificationCount = $state<number | null>(getCachedUnreadCount());
	let notificationError = $state('');
	let notificationRefreshSequence = 0;
	let commandDialog = $state<HTMLDialogElement | null>(null);
	let commandInput = $state<HTMLInputElement | null>(null);
	let commandQuery = $state('');
	let commandCategory = $state('Semua');
	let commandIndex = $state(0);
	let tickerIndex = $state(0);
	let headerHidden = $state(false);
	let headerElement: HTMLElement;

	function clearSession() {
		session = null;
		setCachedSession(null);
		unreadNotificationCount = null;
	}
	setContext(sessionKey, { get current() { return session; }, refresh: refreshAccess, clear: clearSession });

	const primaryLinks = [
		{ href: '/app/today', label: 'Beranda', icon: 'home', hint: 'Presensi & ringkasan hari ini' },
		{ href: '/app/schedule', label: 'Jadwal', icon: 'calendar', hint: 'Kalender shift kerja' },
		{ href: '/app/scan', label: 'Scan', icon: 'qr', hint: 'Check-in & check-out QR lokasi' },
		{ href: '/app/requests', label: 'Pengajuan', icon: 'request', hint: 'Izin, cuti, lembur & klaim' }
	];
	const secondaryDesktopLinks = [
		{ href: '/app/history', label: 'Riwayat', icon: 'clock', hint: 'Catatan kehadiran bulanan' }
	];
	const workspaceModuleLinks = [
		{ href: '/payroll', label: 'Slip Gaji', group: 'Keuangan & Klaim', icon: 'wallet', hint: 'Rincian pendapatan bulanan' },
		{ href: '/cash-advance', label: 'Kasbon', group: 'Keuangan & Klaim', icon: 'banknote', hint: 'Pengajuan pinjaman karyawan' },
		{ href: '/shift-swap', label: 'Tukar Shift', group: 'Kehadiran & Shift', icon: 'swap', hint: 'Delegasi jadwal dengan rekan' },
		{ href: '/wfh', label: 'Izin WFH', group: 'Kehadiran & Shift', icon: 'briefcase', hint: 'Lapor kerja jarak jauh' },
		{ href: '/face-enrollment', label: 'Biometrik', group: 'Kehadiran & Shift', icon: 'face', hint: 'Verifikasi profil wajah' },
		{ href: '/announcements', label: 'Pengumuman', group: 'Komunikasi & SDM', icon: 'megaphone', hint: 'Papan informasi perusahaan' },
		{ href: '/hr-letters', label: 'Surat HR', group: 'Komunikasi & SDM', icon: 'file', hint: 'Surat keterangan & dokumen' },
		{ href: '/assets', label: 'Inventaris', group: 'Komunikasi & SDM', icon: 'box', hint: 'Pinjaman perangkat kerja' },
		{ href: '/kpi', label: 'Evaluasi KPI', group: 'Komunikasi & SDM', icon: 'chart', hint: 'Target & capaian kinerja' },
		{ href: '/offboarding', label: 'Offboarding', group: 'Komunikasi & SDM', icon: 'logout', hint: 'Serah terima & clearance' }
	];
	const adminModuleLinks = [
		{ href: '/admin/dashboard', label: 'Dashboard Admin', icon: 'home', group: 'Operasional Inti', hint: 'Ringkasan kehadiran & antrean' },
		{ href: '/admin/employees', label: 'Karyawan', icon: 'people', group: 'Operasional Inti', hint: 'Direktori & profil anggota' },
		{ href: '/admin/attendance', label: 'Master Shift', icon: 'clock', group: 'Operasional Inti', hint: 'Pengaturan jam kerja' },
		{ href: '/admin/schedules', label: 'Penjadwalan', icon: 'calendar', group: 'Operasional Inti', hint: 'Penugasan shift bulanan' },
		{ href: '/admin/barcodes', label: 'Lokasi & QR', icon: 'pin', group: 'Operasional Inti', hint: 'Titik geofence & kode QR' },
		{ href: '/admin/import-export/attendances', label: 'Log Presensi', icon: 'clock', group: 'SDM & Kehadiran', hint: 'Rekap kehadiran seluruh tim' },
		{ href: '/admin/overtime', label: 'Manajemen Lembur', icon: 'clock', group: 'SDM & Kehadiran', hint: 'Persetujuan jam lembur' },
		{ href: '/admin/leaves/quotas', label: 'Kuota Cuti', icon: 'calendar', group: 'SDM & Kehadiran', hint: 'Jatah cuti tahunan' },
		{ href: '/admin/shift-swaps', label: 'Tukar Shift Tim', icon: 'swap', group: 'SDM & Kehadiran', hint: 'Persetujuan tukar jadwal' },
		{ href: '/admin/payrolls', label: 'Payroll & Slip', icon: 'wallet', group: 'Keuangan & Aset', hint: 'Periode gaji & potongan' },
		{ href: '/admin/reimbursements', label: 'Reimbursement', icon: 'banknote', group: 'Keuangan & Aset', hint: 'Verifikasi klaim biaya' },
		{ href: '/admin/cash-advances', label: 'Persetujuan Kasbon', icon: 'banknote', group: 'Keuangan & Aset', hint: 'Pencairan & cicilan kasbon' },
		{ href: '/admin/assets', label: 'Aset & Inventaris', icon: 'box', group: 'Keuangan & Aset', hint: 'Daftar perangkat kantor' },
		{ href: '/admin/announcements', label: 'Siaran Pengumuman', icon: 'megaphone', group: 'Organisasi & Audit', hint: 'Publikasi info internal' },
		{ href: '/admin/kpi', label: 'Penilaian KPI', icon: 'chart', group: 'Organisasi & Audit', hint: 'Evaluasi performa tim' },
		{ href: '/admin/activity-logs', label: 'Audit Trail', icon: 'file', group: 'Organisasi & Audit', hint: 'Jejak aktivitas sistem' },
		{ href: '/admin/settings', label: 'Storage & Sistem', icon: 'panel', group: 'Keamanan & DB', hint: 'Konfigurasi berkas & objek' },
		{ href: '/admin/system-info', label: 'Monitor Database', icon: 'database', group: 'Keamanan & DB', hint: 'Kesehatan koneksi & pool' }
	];

	let operationLinks = $derived(visibleOperations(session?.capabilities));
	let desktopLinks = $derived([...primaryLinks, ...secondaryDesktopLinks, ...operationLinks]);
	let mobileLinks = $derived(primaryLinks);
	let extendedAdminLinks = $derived(
		session?.capabilities?.includes('admin.dashboard.view')
			? [
					...adminModuleLinks,
					...(session?.capabilities?.includes('admin.access.manage')
						? [
								{ href: '/admin/access', label: 'Peran & Izin', icon: 'shield', group: 'Keamanan & DB', hint: 'Matriks hak akses' },
								{ href: '/admin/api-docs', label: 'API Docs & Postman', icon: 'code', group: 'Keamanan & DB', hint: 'Dokumentasi endpoint & koleksi' }
							]
						: [])
				]
			: []
	);

	let adminModuleGroups = $derived(
		[...new Set(extendedAdminLinks.map((item) => item.group))].map((group) => ({
			group,
			links: extendedAdminLinks.filter((item) => item.group === group)
		}))
	);
	let workspaceModuleGroups = $derived(
		[...new Set(workspaceModuleLinks.map((item) => item.group))].map((group) => ({
			group,
			links: workspaceModuleLinks.filter((item) => item.group === group)
		}))
	);

	let commandItems = $derived([
		...desktopLinks.map((item) => ({
			href: item.href,
			label: item.label,
			category: 'Utama',
			icon: item.icon,
			hint: 'hint' in item && typeof item.hint === 'string' ? item.hint : 'Navigasi utama workspace'
		})),
		...workspaceModuleLinks.map((item) => ({
			href: item.href,
			label: item.label,
			category: 'Layanan SDM',
			icon: item.icon,
			hint: item.hint
		})),
		{ href: '/app/notifications', label: 'Kotak Masuk Notifikasi', category: 'Akun', icon: 'bell', hint: 'Pemberitahuan & pembaruan status' },
		{ href: '/user/profile', label: 'Profil & Keamanan Akun', category: 'Akun', icon: 'profile', hint: 'Data diri, kata sandi & sesi perangkat' },
		...extendedAdminLinks.map((item) => ({
			href: item.href,
			label: item.label,
			category: 'Admin',
			icon: item.icon,
			hint: item.hint
		}))
	]);

	let headerTitle = $derived(commandItems.find((item) => item.href === page.url.pathname)?.label ?? (admin ? 'Admin' : 'Workspace'));
	let availableCategories = $derived([
		'Semua',
		'Utama',
		'Layanan SDM',
		'Akun',
		...(extendedAdminLinks.length ? ['Admin'] : [])
	]);

	let filteredCommands = $derived.by(() => {
		const q = commandQuery.trim().toLocaleLowerCase('id');
		const seen = new Set<string>();
		return commandItems.filter((item) => {
			if (commandCategory !== 'Semua' && item.category !== commandCategory) return false;
			if (!q) return true;
			return `${item.label} ${item.category} ${item.hint} ${item.href}`
				.toLocaleLowerCase('id')
				.includes(q);
		}).filter((item) => {
			if (seen.has(item.href)) return false;
			seen.add(item.href);
			return true;
		});
	});

	$effect(() => {
		void commandQuery;
		void commandCategory;
		commandIndex = 0;
	});

	let isAdminContext = $derived(admin || page.url.pathname.startsWith('/admin'));
	let requiredCapability = $derived(requiredPermission(page.url.pathname, isAdminContext));
	let forbidden = $derived(requiredCapability !== null && !session?.capabilities?.includes(requiredCapability));

	let searchSuggestions = $derived.by(() => {
		const path = page.url.pathname;
		if (path.startsWith('/app/history')) {
			return [
				'Cari koreksi presensi…',
				'Cari jadwal shift kerja…',
				'Cari slip gaji bulanan…',
				'Cari pengajuan izin…'
			];
		}
		if (path.startsWith('/app/schedule')) {
			return [
				'Cari tukar shift rekan…',
				'Cari pengajuan izin WFH…',
				'Cari riwayat kehadiran…',
				'Cari pengajuan cuti…'
			];
		}
		if (path.startsWith('/app/requests')) {
			return [
				'Cari kasbon karyawan…',
				'Cari kuota cuti & izin…',
				'Cari surat keterangan HR…',
				'Cari jadwal kerja…'
			];
		}
		if (path.startsWith('/app/notifications')) {
			return [
				'Cari pengumuman kantor…',
				'Cari persetujuan tim…',
				'Cari profil & keamanan…',
				'Cari riwayat presensi…'
			];
		}
		if (path.startsWith('/user/profile')) {
			return [
				'Cari ubah kata sandi…',
				'Cari sesi perangkat aktif…',
				'Cari biometrik wajah…',
				'Cari slip gaji & THP…'
			];
		}
		if (path.startsWith('/admin')) {
			return [
				'Cari direktori karyawan…',
				'Cari lokasi & kode QR…',
				'Cari penjadwalan shift…',
				'Cari payroll & audit log…'
			];
		}
		return [
			'Cari scan QR presensi…',
			'Cari slip gaji & kasbon…',
			'Cari pengajuan cuti & WFH…',
			'Cari tukar shift kerja…',
			'Cari inventaris & surat HR…'
		];
	});

	let activeSearchPrompt = $derived(
		searchSuggestions[tickerIndex % Math.max(1, searchSuggestions.length)] ?? 'Cari fitur…'
	);

	$effect(() => {
		void page.url.pathname;
		tickerIndex = 0;
	});

	function isActive(href: string) {
		const pathname = page.url.pathname;
		return pathname === href || pathname.startsWith(`${href}/`);
	}

	function openCommandPalette() {
		commandQuery = '';
		commandCategory = 'Semua';
		commandIndex = 0;
		commandDialog?.showModal();
		commandInput?.focus();
	}

	function closeCommandPalette() {
		commandDialog?.close();
	}

	async function jumpTo(href: string) {
		closeCommandPalette();
		closeProfileMenus();
		await goto(href);
	}

	function handleCommandKeydown(event: KeyboardEvent) {
		if (!filteredCommands.length) return;
		if (event.key === 'ArrowDown') {
			event.preventDefault();
			commandIndex = (commandIndex + 1) % filteredCommands.length;
		} else if (event.key === 'ArrowUp') {
			event.preventDefault();
			commandIndex = (commandIndex - 1 + filteredCommands.length) % filteredCommands.length;
		} else if (event.key === 'Enter') {
			const target = filteredCommands[commandIndex];
			if (target) {
				event.preventDefault();
				void jumpTo(target.href);
			}
		}
	}

	async function refreshNotificationCount() {
		if (!session) return;
		const sequence = ++notificationRefreshSequence;
		notificationError = '';
		try {
			const result = await request<{ count: number }>('/v1/notifications/unread-count');
			if (sequence !== notificationRefreshSequence) return;
			unreadNotificationCount = result.count;
			setCachedUnreadCount(result.count);
			syncPwaAppBadge(result.count);
		} catch (cause) {
			if (sequence !== notificationRefreshSequence) return;
			notificationError = cause instanceof Error ? cause.message : 'Jumlah notifikasi tidak dapat dimuat.';
		}
	}

	async function load() {
		const hadCachedSession = session !== null;
		if (!hadCachedSession) loading = true;
		error = ''; expired = false;
		try {
			const fresh = await currentSession();
			session = fresh;
			setCachedSession(fresh);
			void refreshNotificationCount();
		}
		catch (e) {
			clearSession();
			expired = e instanceof ApiError && e.status === 401;
			error = expired ? 'Sesi berakhir. Silakan masuk kembali.' : 'Papan kerja belum dapat dimuat. Periksa koneksi lalu coba lagi.';
		}
		finally { loading = false; }
	}

	async function logout() {
		signingOut = true; error = '';
		try {
			await request('/v1/auth/logout', { method: 'POST' });
			session = null;
			setCachedSession(null);
			unreadNotificationCount = null;
			await goto('/login');
		}
		catch { error = 'Gagal keluar. Coba lagi.'; }
		finally { signingOut = false; }
	}

	function closeProfileMenus() {
		document.querySelectorAll<HTMLDetailsElement>('.profile-menu, .mobile-profile-menu').forEach((menu) => {
			menu.open = false;
		});
	}
	function notificationBadgeLabel() {
		if (!unreadNotificationCount) return '';
		return unreadNotificationCount > 99 ? '99+' : String(unreadNotificationCount);
	}
	async function refreshAccess() {
		if (!session) return;
		try {
			const fresh = await currentSession();
			session = fresh;
			setCachedSession(fresh);
			void refreshNotificationCount();
		} catch {
			notificationRefreshSequence++;
			session = null;
			setCachedSession(null);
			unreadNotificationCount = null;
			error = 'Sesi tidak dapat diverifikasi. Muat ulang atau masuk kembali.';
		}
	}

	afterNavigate(() => { headerHidden = false; void refreshAccess(); closeProfileMenus(); });

	onMount(() => {
		let previous = window.scrollY;
		let frame = 0;
		const scroll = () => {
			if (frame) return;
			frame = requestAnimationFrame(() => {
				frame = 0;
				const current = Math.max(0, window.scrollY);
				if (current < 72 || headerElement?.matches(':focus-within') || headerElement?.querySelector('details[open]') || commandDialog?.open) headerHidden = false;
				else if (Math.abs(current - previous) > 6) headerHidden = current > previous;
				previous = current;
			});
		};
		window.addEventListener('scroll', scroll, { passive: true });
		return () => { window.removeEventListener('scroll', scroll); cancelAnimationFrame(frame); };
	});

	onMount(() => {
		void load();
		const disconnectRealtime = connectRealtimeStream((pulse) => {
			unreadNotificationCount = pulse.unread_notifications;
			setCachedUnreadCount(pulse.unread_notifications);
		});
		const handleKeydown = (event: KeyboardEvent) => {
			if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
				event.preventDefault();
				if (commandDialog?.open) closeCommandPalette();
				else openCommandPalette();
			}
		};
		window.addEventListener('keydown', handleKeydown);
		window.addEventListener('focus', refreshAccess);
		window.addEventListener('paspapan:notifications-updated', refreshNotificationCount);
		const timer = window.setInterval(refreshAccess, 30000);
		const tickerTimer = window.setInterval(() => {
			tickerIndex = (tickerIndex + 1) % Math.max(1, searchSuggestions.length);
		}, 2600);
		return () => {
			disconnectRealtime();
			window.removeEventListener('keydown', handleKeydown);
			window.clearInterval(timer);
			window.clearInterval(tickerTimer);
			window.removeEventListener('focus', refreshAccess);
			window.removeEventListener('paspapan:notifications-updated', refreshNotificationCount);
		};
	});
</script>

{#snippet profileDropdown()}
	<div class="profile-summary">
		<span class="avatar" aria-hidden="true">{session?.user.full_name.slice(0, 1).toUpperCase()}</span>
		<div class="profile-summary-copy">
			<strong>{session?.user.full_name}</strong>
			<span>{session?.company.role} · {session?.company.name}</span>
		</div>
		<button type="button" class="quiet profile-close-btn" aria-label="Tutup menu" onclick={closeProfileMenus}>
			<Icon name="close" size={14} />
		</button>
	</div>

	<div class="profile-quick-strip">
		<a href="/user/profile" aria-current={isActive('/user/profile') ? 'page' : undefined} onclick={closeProfileMenus}>
			<Icon name="profile" size={16} />
			<span>Akun</span>
		</a>
		<a href="/app/notifications" aria-current={isActive('/app/notifications') ? 'page' : undefined} onclick={closeProfileMenus}>
			<Icon name="bell" size={16} />
			<span>Notifikasi</span>
			{#if unreadNotificationCount}<b class="notification-count">{notificationBadgeLabel()}</b>{/if}
		</a>
		<a href="/app/history" aria-current={isActive('/app/history') ? 'page' : undefined} onclick={closeProfileMenus}>
			<Icon name="clock" size={16} />
			<span>Riwayat</span>
		</a>
	</div>

	<div class="profile-section">
		<p class="profile-menu-label">Layanan Mandiri</p>
		<div class="profile-grid">
			{#each workspaceModuleLinks as item}
				<a href={item.href} aria-current={isActive(item.href) ? 'page' : undefined} onclick={closeProfileMenus}>
					<span class="grid-item-icon"><Icon name={item.icon} size={15} /></span>
					<span class="grid-item-label">{item.label}</span>
				</a>
			{/each}
		</div>
	</div>

	{#if session?.capabilities.includes('requests.approvals.view') || extendedAdminLinks.length}
		<div class="profile-section">
			<p class="profile-menu-label">Operasional &amp; Admin</p>
			<div class="profile-grid">
				{#if session?.capabilities.includes('requests.approvals.view')}
					<a href="/app/approvals" aria-current={isActive('/app/approvals') ? 'page' : undefined} onclick={closeProfileMenus}>
						<span class="grid-item-icon"><Icon name="check" size={15} /></span>
						<span class="grid-item-label">Persetujuan</span>
					</a>
				{/if}
				{#each extendedAdminLinks as item}
					<a href={item.href} aria-current={isActive(item.href) ? 'page' : undefined} onclick={closeProfileMenus}>
						<span class="grid-item-icon"><Icon name={item.icon} size={15} /></span>
						<span class="grid-item-label">{item.label}</span>
					</a>
				{/each}
			</div>
		</div>
	{/if}

	{#if notificationError}<p class="notification-error" role="alert">{notificationError} <button class="quiet" onclick={refreshNotificationCount}>Coba lagi</button></p>{/if}
	<button class="quiet profile-logout" aria-label="Keluar" disabled={signingOut} onclick={logout}>
		<Icon name="logout" size={15} />
		<span>Keluar dari akun</span>
	</button>
{/snippet}

<div class="work-shell">
	<a class="skip-link" href="#main-content">Lewati ke konten</a>
	<header class="topbar" class:header-hidden={headerHidden} bind:this={headerElement} onfocusin={() => headerHidden = false}>
		<div class="topbar-start">
			<button class="desktop-menu-button quiet icon-button" title={sidebarHidden ? 'Tampilkan menu' : 'Sembunyikan menu'} aria-label={sidebarHidden ? 'Tampilkan menu' : 'Sembunyikan menu'} aria-expanded={!sidebarHidden} aria-controls="workspace-sidebar" onclick={() => sidebarHidden = !sidebarHidden}><Icon name="panel" /></button>
			<a class="brand" href="/app/today" aria-label="PasPapan — Beranda"><span class="desktop-brand"><Brand size={28} /></span><img class="mobile-mark" src="/brand/mark.svg" width="28" height="28" alt="" /></a>
			<span class="workspace-label">{headerTitle}</span>
		</div>
		{#if session}
			<div class="topbar-actions">
				<button type="button" class="command-trigger quiet" onclick={openCommandPalette} aria-label="Cari fitur atau halaman (Command+K)" title="Cari fitur atau halaman (⌘K)">
					<Icon name="search" size={14} />
					<span class="command-ticker-viewport" aria-live="off">
						{#key `${page.url.pathname}-${tickerIndex}`}
							<span class="command-trigger-text">{activeSearchPrompt}</span>
						{/key}
					</span>
					<kbd>⌘K</kbd>
				</button>
				<span class="company">{session.company.name}</span>
				<details class="profile-menu">
					<summary aria-label={`Menu profil${unreadNotificationCount ? `, ${unreadNotificationCount} notifikasi belum dibaca` : ''}`} title="Menu profil & modul">
						<span class="avatar-trigger">{session.user.full_name.slice(0, 1).toUpperCase()}</span>
						{#if unreadNotificationCount}<span class="profile-badge" aria-hidden="true">{notificationBadgeLabel()}</span>{/if}
					</summary>
					<div class="profile-dropdown">
						{@render profileDropdown()}
					</div>
				</details>
			</div>
		{/if}
	</header>

	<dialog bind:this={commandDialog} class="command-dialog" aria-label="Pencarian cepat fitur dan halaman" onkeydown={handleCommandKeydown}>
		<div class="command-header">
			<span class="command-search-icon"><Icon name="search" size={17} /></span>
			<input
				bind:this={commandInput}
				type="search"
				bind:value={commandQuery}
				placeholder="Cari fitur, menu, atau halaman (mis. Slip Gaji, Scan QR, Cuti)…"
			/>
			{#if commandQuery}
				<button type="button" class="quiet command-clear" aria-label="Bersihkan pencarian" onclick={() => { commandQuery = ''; commandInput?.focus(); }}>
					<Icon name="close" size={14} />
				</button>
			{/if}
			<button type="button" class="quiet command-close" onclick={closeCommandPalette}>Esc</button>
		</div>

		<div class="command-categories" role="tablist" aria-label="Kategori fitur">
			{#each availableCategories as category}
				<button
					type="button"
					role="tab"
					aria-selected={commandCategory === category}
					class:active={commandCategory === category}
					onclick={() => (commandCategory = category)}
				>
					{category}
				</button>
			{/each}
		</div>

		<div class="command-results">
			{#each filteredCommands as item, idx (item.href)}
				<button
					type="button"
					class="command-item"
					class:selected={idx === commandIndex}
					onmouseenter={() => (commandIndex = idx)}
					onclick={() => jumpTo(item.href)}
				>
					<span class="command-item-icon"><Icon name={item.icon} size={16} /></span>
					<span class="command-item-copy">
						<strong>{item.label}</strong>
						<small>{item.hint}</small>
					</span>
					<span class="command-item-tag">{item.category}</span>
				</button>
			{:else}
				<div class="command-empty">
					<Icon name="search" size={20} />
					<p>Tidak ditemukan hasil untuk <strong>"{commandQuery}"</strong>.</p>
				</div>
			{/each}
		</div>
	</dialog>

	<div class="shell-body" class:sidebar-hidden={sidebarHidden}>
		<aside class="sidebar" id="workspace-sidebar" aria-label="Menu workspace">
			<p class="nav-label">MENU UTAMA</p>
			<nav aria-label="Navigasi utama">{#each desktopLinks as item}<a aria-label={item.label} title={item.label} href={item.href} aria-current={page.url.pathname === item.href ? 'page' : undefined}><Icon name={item.icon} />{item.label}</a>{/each}</nav>
			<p class="nav-label">LAYANAN MANDIRI</p>
			{#each workspaceModuleGroups as group}
				<details class="nav-group" open>
					<summary>{group.group}<span>{group.links.length}</span><Icon name="chevron" size={16} /></summary>
					<nav aria-label={`Menu ${group.group}`}>
						{#each group.links as item}<a href={item.href} aria-current={page.url.pathname === item.href || page.url.pathname.startsWith(`${item.href}/`) ? 'page' : undefined}>{item.label}</a>{/each}
					</nav>
				</details>
			{/each}
			{#if extendedAdminLinks.length}
				<p class="nav-label">MODUL ADMIN</p>
				{#each adminModuleGroups as group}
					<details class="nav-group" open>
						<summary>{group.group}<span>{group.links.length}</span><Icon name="chevron" size={16} /></summary>
						<nav aria-label={`Admin ${group.group}`}>
							{#each group.links as item}
								<a href={item.href} aria-current={page.url.pathname === item.href || page.url.pathname.startsWith(`${item.href}/`) ? 'page' : undefined}>{item.label}</a>
							{/each}
						</nav>
					</details>
				{/each}
			{/if}
		</aside>
		<div class="content-column">
			{#if error}<div class="shell-message" role="alert">{error} {#if expired}<a href="/login">Masuk kembali</a>{:else}<button class="quiet" onclick={load}>Coba lagi</button>{/if}</div>{/if}
			{#if loading}<main id="main-content" class="workspace-page" tabindex="-1"><p role="status">Memuat papan kerja…</p></main>
			{:else if session && forbidden}<main id="main-content" class="workspace-page" tabindex="-1"><section class="panel"><h1>Akses terbatas</h1><p>Peran Anda tidak memiliki akses ke halaman ini.</p><a href="/app/today">Kembali ke beranda</a></section></main>
			{:else if session}{@render children()}{/if}
		</div>
	</div>
	<nav class="bottom-nav" aria-label="Navigasi mobile">
		{#each mobileLinks as item}
			<a class:scan-destination={item.href === '/app/scan'} href={item.href} aria-label={item.label} title={item.label} aria-current={isActive(item.href) ? 'page' : undefined}>
				<span class="nav-icon"><Icon name={item.icon} size={20} /></span>
				<span class="nav-label-text">{item.label}</span>
			</a>
		{/each}
		{#if session}
			<details class="mobile-profile-menu">
				<summary aria-label={`Menu profil${unreadNotificationCount ? `, ${unreadNotificationCount} notifikasi belum dibaca` : ''}`} aria-current={isActive('/user/profile') || isActive('/app/notifications') || isActive('/app/history') || isActive('/app/approvals') ? 'page' : undefined}>
					<span class="nav-icon mobile-profile-icon"><Icon name="profile" size={20} />{#if unreadNotificationCount}<span class="profile-badge" aria-hidden="true">{notificationBadgeLabel()}</span>{/if}</span>
					<span class="nav-label-text">Profil</span>
				</summary>
				<div class="mobile-profile-dropdown">{@render profileDropdown()}</div>
			</details>
		{:else}
			<a href="/user/profile" aria-label="Profil" title="Profil" aria-current={isActive('/user/profile') ? 'page' : undefined}><span class="nav-icon"><Icon name="profile" size={20} /></span><span class="nav-label-text">Profil</span></a>
		{/if}
	</nav>

	{#if realtimeState.toast}
		<aside class="realtime-toast" role="status" aria-live="polite">
			<span class="toast-icon"><Icon name="bell" size={17} /></span>
			<div class="toast-copy">
				<strong>{realtimeState.toast.title}</strong>
				<p>{realtimeState.toast.body}</p>
			</div>
			<div class="toast-actions">
				<a class="toast-open" href={realtimeState.toast.href} onclick={dismissLiveToast}>Buka</a>
				<button type="button" class="quiet toast-close" aria-label="Tutup notifikasi" onclick={dismissLiveToast}>
					<Icon name="close" size={14} />
				</button>
			</div>
		</aside>
	{/if}
</div>

<style>
	.work-shell { min-height: 100svh; background: var(--canvas); }
	.realtime-toast {
		position: fixed;
		right: 18px;
		bottom: 22px;
		z-index: 45;
		display: flex;
		align-items: center;
		gap: 11px;
		width: min(380px, calc(100vw - 24px));
		padding: 12px 14px;
		border: 1px solid #bdd1ba;
		border-radius: var(--radius);
		background: var(--surface);
		box-shadow: 0 18px 40px -20px rgba(20, 38, 22, 0.35);
	}
	.toast-icon {
		display: grid;
		place-items: center;
		width: 36px;
		height: 36px;
		flex: 0 0 36px;
		border-radius: var(--radius-sm);
		background: var(--primary-soft);
		color: var(--primary-strong);
	}
	.toast-copy {
		min-width: 0;
		flex: 1;
		display: grid;
		gap: 2px;
	}
	.toast-copy strong {
		font-size: 12.5px;
		color: var(--ink);
	}
	.toast-copy p {
		margin: 0;
		font-size: 11.5px;
		color: var(--muted);
		line-height: 1.4;
		overflow-wrap: anywhere;
	}
	.toast-actions {
		display: flex;
		align-items: center;
		gap: 6px;
		flex: 0 0 auto;
	}
	.toast-open {
		padding: 6px 10px;
		border-radius: var(--radius-sm);
		background: var(--primary);
		color: #ffffff !important;
		font-size: 11.5px;
		font-weight: 700;
		text-decoration: none;
	}
	.toast-close {
		width: 28px;
		min-height: 28px !important;
		padding: 4px !important;
		display: grid;
		place-items: center;
		border-radius: var(--radius-sm) !important;
	}
	.skip-link { position: fixed; top: -80px; left: 16px; z-index: 40; background: var(--surface); padding: 10px 12px; border: 1px solid var(--line); border-radius: var(--radius); text-decoration: none; color: var(--ink); }
	.skip-link:focus { top: 10px; }
	.topbar {
		transition: transform 180ms ease;
		height: 56px;
		position: sticky;
		top: 0;
		z-index: 25;
		background: color-mix(in srgb, var(--surface) 92%, transparent);
		backdrop-filter: blur(14px);
		border-bottom: 1px solid var(--line);
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 0 18px;
		gap: 12px;
	}
	.topbar-start { display: flex; align-items: center; gap: 10px; min-width: 0; }
	.topbar.header-hidden:not(:focus-within) { transform: translateY(-100%); pointer-events: none; }
	.mobile-mark { display: none; }
	.brand { display: flex; align-items: center; gap: 9px; text-decoration: none; font-size: 16px; font-weight: 800; color: var(--ink); letter-spacing: -.5px; }
	.workspace-label { border-left: 1px solid var(--line); padding-left: 10px; margin-left: 2px; font-size: 11px; color: var(--muted); font-weight: 750; letter-spacing: 0.04em; text-transform: uppercase; }
	.desktop-menu-button { display: inline-flex; align-items: center; justify-content: center; width: 34px; min-height: 34px !important; padding: 6px !important; }
	.desktop-menu-button :global(svg) { width: 16px; height: 16px; }
	.topbar-actions { display: flex; align-items: center; gap: 10px; min-width: 0; }
	.command-trigger {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		min-width: 215px;
		max-width: 270px;
		min-height: 34px !important;
		padding: 5px 11px !important;
		font-size: 12px !important;
		font-weight: 600 !important;
		color: var(--muted) !important;
		background: var(--surface-alt) !important;
		border: 1px solid var(--line) !important;
		border-radius: var(--radius) !important;
		overflow: hidden;
	}
	.command-trigger:hover {
		background: var(--surface) !important;
		border-color: #b7c9b8 !important;
		color: var(--ink) !important;
	}
	.command-ticker-viewport {
		flex: 1;
		min-width: 0;
		height: 18px;
		display: flex;
		align-items: center;
		overflow: hidden;
		position: relative;
	}
	.command-trigger-text {
		display: block;
		width: 100%;
		text-align: start;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		animation: search-ticker-in 320ms cubic-bezier(0.22, 1, 0.36, 1) both;
	}
	@keyframes search-ticker-in {
		from {
			opacity: 0;
			transform: translateY(10px);
		}
		to {
			opacity: 1;
			transform: translateY(0);
		}
	}
	.command-trigger kbd { font: inherit; font-size: 10px; font-weight: 750; padding: 2px 5px; border: 1px solid var(--line); border-radius: var(--radius-sm); background: var(--surface); color: var(--muted); }
	.company { font-size: 12px; font-weight: 650; color: var(--muted); max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
	.avatar, .avatar-trigger { width: 34px; height: 34px; border-radius: var(--radius-sm); background: var(--primary-soft); color: var(--primary-strong); display: grid; place-items: center; font-size: 13px; font-weight: 800; }
	.profile-menu { position: relative; }
	.profile-menu summary { position: relative; display: grid; place-items: center; width: 36px; height: 36px; border: 1px solid var(--line); border-radius: var(--radius); background: var(--surface); color: var(--muted); cursor: pointer; list-style: none; }
	.profile-menu summary::-webkit-details-marker { display: none; }
	.profile-menu[open] summary { border-color: var(--primary); color: var(--primary-strong); }
	.profile-badge { position: absolute; inset-block-start: -4px; inset-inline-end: -4px; display: grid; place-items: center; min-width: 16px; height: 16px; padding-inline: 4px; border: 2px solid var(--surface); border-radius: var(--radius-sm); background: #e11d48; color: #fff; font-size: 9.5px; font-weight: 800; line-height: 1; }

	/* Compact 2-Column Profile & Launcher Sheet */
	.profile-dropdown, .mobile-profile-dropdown {
		display: grid;
		gap: 10px;
		padding: 12px;
		border: 1px solid var(--line);
		border-radius: var(--radius-lg);
		background: var(--surface);
		box-shadow: 0 22px 50px -18px rgba(18, 32, 22, 0.34);
	}
	.profile-dropdown {
		position: absolute;
		inset-block-start: calc(100% + 8px);
		inset-inline-end: 0;
		z-index: 40;
		width: min(340px, calc(100vw - 20px));
		max-height: min(80svh, 620px);
		overflow-y: auto;
	}
	.profile-summary {
		display: flex;
		align-items: center;
		gap: 10px;
		padding-bottom: 10px;
		border-bottom: 1px solid var(--line);
	}
	.profile-summary-copy {
		display: grid;
		gap: 1px;
		min-width: 0;
		flex: 1;
	}
	.profile-summary strong {
		font-size: 13px;
		color: var(--ink);
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}
	.profile-summary span {
		color: var(--muted);
		font-size: 11px;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		text-transform: capitalize;
	}
	.profile-close-btn {
		width: 28px !important;
		min-height: 28px !important;
		padding: 4px !important;
		display: grid !important;
		place-items: center;
		border-radius: var(--radius-sm) !important;
		flex: 0 0 28px;
	}
	.profile-quick-strip {
		display: grid;
		grid-template-columns: repeat(3, minmax(0, 1fr));
		gap: 6px;
	}
	.profile-quick-strip a {
		position: relative;
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 6px;
		min-height: 36px;
		padding: 6px 8px;
		border: 1px solid var(--line);
		border-radius: var(--radius-sm);
		background: var(--surface-alt);
		color: var(--ink);
		font-size: 11.5px;
		font-weight: 700;
		text-decoration: none;
	}
	.profile-quick-strip a:hover,
	.profile-quick-strip a[aria-current] {
		background: var(--primary-soft);
		border-color: #bdd1bd;
		color: var(--primary-strong);
	}
	.profile-section {
		display: grid;
		gap: 5px;
	}
	.profile-menu-label {
		margin: 0 2px;
		color: var(--muted);
		font-size: 9.5px;
		font-weight: 800;
		letter-spacing: 0.07em;
		text-transform: uppercase;
	}
	.profile-grid {
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 5px;
	}
	.profile-grid a {
		display: flex;
		align-items: center;
		gap: 8px;
		min-width: 0;
		min-height: 36px;
		padding: 6px 8px;
		border: 1px solid var(--line);
		border-radius: var(--radius-sm);
		background: var(--surface);
		color: var(--ink);
		font-size: 12px;
		font-weight: 650;
		text-decoration: none;
	}
	.profile-grid a:hover,
	.profile-grid a[aria-current] {
		background: var(--primary-soft);
		border-color: #bdd1bd;
		color: var(--primary-strong);
	}
	.grid-item-icon {
		display: grid;
		place-items: center;
		width: 24px;
		height: 24px;
		flex: 0 0 24px;
		border-radius: var(--radius-sm);
		background: var(--surface-alt);
		color: var(--primary-strong);
	}
	.grid-item-label {
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}
	.notification-count {
		display: inline-grid;
		place-items: center;
		min-width: 16px;
		height: 16px;
		padding-inline: 4px;
		border-radius: var(--radius-sm);
		background: #e11d48;
		color: #fff;
		font-size: 9.5px;
		font-weight: 800;
	}
	.notification-error { margin: 0; color: #9f1239; font-size: 11px; }
	.notification-error button { display: inline; width: auto; min-height: auto; padding: 0; border: 0; color: inherit; font-size: inherit; text-decoration: underline; }
	.profile-logout {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 7px;
		width: 100%;
		min-height: 38px;
		padding: 7px 10px;
		border: 1px solid #ecc8c2 !important;
		border-radius: var(--radius-sm) !important;
		background: #fff6f5 !important;
		color: #962d23 !important;
		font-size: 12px;
		font-weight: 700;
		box-shadow: none !important;
	}
	.profile-logout:hover:not(:disabled) {
		background: #ffece9 !important;
	}

	.command-dialog {
		width: min(560px, calc(100vw - 24px));
		max-height: min(78svh, 540px);
		padding: 0;
		border: 1px solid var(--line);
		border-radius: var(--radius-lg);
		background: var(--surface);
		color: var(--ink);
		box-shadow: 0 28px 64px -24px rgba(15, 25, 20, .4);
	}
	.command-dialog::backdrop { background: rgba(15, 25, 20, .38); backdrop-filter: blur(4px); }
	.command-header { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-bottom: 1px solid var(--line); }
	.command-search-icon { display: grid; place-items: center; color: var(--primary); }
	.command-header input { flex: 1; border: 0; padding: 6px 2px; font: inherit; font-size: 14px; background: transparent; color: var(--ink); outline: none; }
	.command-clear { width: 28px; min-height: 28px !important; padding: 4px !important; display: grid; place-items: center; border-radius: var(--radius-sm) !important; }
	.command-close { min-height: 28px !important; padding: 4px 8px !important; font-size: 11px !important; border-radius: var(--radius-sm) !important; }
	.command-categories {
		display: flex;
		align-items: center;
		gap: 6px;
		padding: 8px 14px;
		border-bottom: 1px solid var(--line);
		background: var(--canvas);
		overflow-x: auto;
	}
	.command-categories button {
		min-height: 28px !important;
		padding: 4px 10px !important;
		border: 1px solid var(--line) !important;
		border-radius: var(--radius-sm) !important;
		background: var(--surface) !important;
		color: var(--muted) !important;
		font-size: 11.5px !important;
		font-weight: 700 !important;
		box-shadow: none !important;
		white-space: nowrap;
	}
	.command-categories button.active {
		background: var(--primary-soft) !important;
		border-color: #b7ceb4 !important;
		color: var(--primary-strong) !important;
	}
	.command-results { display: grid; gap: 3px; padding: 8px; max-height: 360px; overflow-y: auto; }
	.command-item { display: flex; align-items: center; gap: 11px; width: 100%; padding: 9px 11px !important; border: 1px solid transparent !important; border-radius: var(--radius) !important; background: transparent !important; color: var(--ink) !important; text-align: start; box-shadow: none !important; }
	.command-item:hover, .command-item.selected { background: var(--surface-alt) !important; border-color: #cde0cc !important; }
	.command-item-icon { display: grid; place-items: center; width: 34px; height: 34px; flex: 0 0 34px; border-radius: var(--radius-sm); background: var(--primary-soft); color: var(--primary-strong); }
	.command-item-copy { display: grid; gap: 1px; min-width: 0; flex: 1; }
	.command-item-copy strong { font-size: 13px; font-weight: 750; color: var(--ink); }
	.command-item-copy small { font-size: 11px; color: var(--muted); font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
	.command-item-tag { padding: 3px 7px; border-radius: var(--radius-sm); background: var(--surface); border: 1px solid var(--line); color: var(--muted); font-size: 10px; font-weight: 700; white-space: nowrap; }
	.command-empty { display: grid; justify-items: center; gap: 8px; padding: 28px 14px; text-align: center; color: var(--muted); font-size: 12.5px; }
	.command-empty p { margin: 0; }

	.shell-body { display: grid; grid-template-columns: 232px minmax(0, 1fr); min-height: calc(100svh - 56px); }
	.shell-body.sidebar-hidden { grid-template-columns: minmax(0, 1fr); }
	.shell-body.sidebar-hidden .sidebar { display: none; }
	.sidebar {
		position: sticky;
		top: 56px;
		height: calc(100svh - 56px);
		overflow-y: auto;
		padding: 14px 10px 24px;
		border-right: 1px solid var(--line);
		display: flex;
		flex-direction: column;
		background: var(--surface);
	}
	.nav-label { font-size: 9.5px; font-weight: 800; letter-spacing: 1.1px; color: #6e7e73; margin: 0 8px 5px; }
	.nav-label:not(:first-child) { margin-top: 16px; }
	.sidebar nav { display: grid; gap: 2px; }
	.sidebar nav[aria-label="Navigasi utama"] { grid-template-columns: 1fr; }
	.sidebar nav[aria-label="Navigasi utama"] a { justify-content: flex-start; padding: 7px 9px; }
	.nav-group { margin-top: 2px; }
	.nav-group summary {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 8px;
		padding: 6px 9px;
		border-radius: var(--radius-sm);
		color: var(--muted);
		font-size: 11.5px;
		font-weight: 750;
		cursor: pointer;
		list-style: none;
	}
	.nav-group summary::-webkit-details-marker { display: none; }
	.nav-group summary :global(svg) { flex-shrink: 0; transition: transform .15s ease; }
	.nav-group[open] summary :global(svg) { transform: rotate(180deg); }
	.nav-group summary span { margin-inline-start: auto; color: #7c8b81; font-size: 10.5px; }
	.sidebar a {
		display: flex;
		align-items: center;
		gap: 9px;
		padding: 6px 9px;
		min-height: 34px;
		border-radius: var(--radius-sm);
		color: var(--muted);
		text-decoration: none;
		font-size: 12.5px;
		font-weight: 650;
	}
	.sidebar a:hover { background: var(--surface-alt); color: var(--primary-strong); }
	.sidebar a[aria-current] {
		color: var(--primary-strong);
		background: var(--primary-soft);
		font-weight: 750;
	}
	.content-column { min-width: 0; min-height: calc(100svh - 56px); }
	.shell-message { margin: 12px 18px; padding: 11px; border-radius: var(--radius); background: #fff0ed; color: #8e2e26; }
	.bottom-nav { display: none; }
	.mobile-profile-menu { display: none; }

	@media(max-width:1000px){
		.shell-body { display: block; }
		.sidebar { display: none; }
		.desktop-menu-button { display: none; }
		.company { display: none; }
	}

	@media(max-width:760px){
		.realtime-toast {
			right: 12px;
			bottom: 76px;
		}
		.topbar { height: 52px; padding: 0 14px; gap: 10px; }
		.desktop-brand, .command-trigger kbd, .profile-menu { display: none; }
		.mobile-mark { display: block; }
		.workspace-label { display: block; border: 0; padding: 0; margin: 0; font-size: 13px; letter-spacing: 0; text-transform: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
		.topbar-start { flex: 1; }
		.command-trigger {
			min-width: 0;
			max-width: 170px;
			height: 34px !important;
			min-height: 34px !important;
			padding: 0 10px !important;
			border-radius: var(--radius-sm) !important;
			border: 1px solid var(--line) !important;
			background: var(--surface) !important;
			box-shadow: 0 1px 2px rgba(24, 34, 29, 0.04) !important;
		}
		.command-trigger-text { font-size: 11.5px; font-weight: 500; }
		.content-column { min-height: calc(100svh - 52px); padding-bottom: calc(84px + env(safe-area-inset-bottom)); }
		.bottom-nav {
			--dock-radius: var(--radius-lg);
			position: fixed;
			bottom: max(6px, env(safe-area-inset-bottom));
			left: 10px;
			right: 10px;
			z-index: 20;
			display: grid;
			grid-template-columns: repeat(5, minmax(0, 1fr));
			width: calc(100% - 20px);
			max-width: 520px;
			margin-inline: auto;
			gap: 3px;
			padding: 4px;
			border: 1px solid var(--line);
			border-radius: var(--dock-radius);
			background: color-mix(in srgb, var(--surface) 95%, transparent);
			backdrop-filter: blur(16px);
			box-shadow: var(--shadow-soft);
		}
		.bottom-nav > a, .mobile-profile-menu > summary {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			gap: 2px;
			min-width: 0;
			min-height: 52px;
			text-decoration: none;
			color: var(--muted);
			font-size: 10px;
			font-weight: 650;
			line-height: 1.1;
			border-radius: var(--radius-sm);
			cursor: pointer;
			list-style: none;
			box-shadow: none !important;
		}
		.bottom-nav .nav-icon { position: relative; display: grid; place-items: center; width: 32px; height: 28px; border-radius: var(--radius-sm); transition: background-color .14s ease, color .14s ease, transform .14s ease; }
		.bottom-nav :is(a, summary):focus-visible { outline: 2px solid var(--primary); outline-offset: 2px; }
		.command-clear, .command-close, .command-categories button { min-height: 44px !important; }
		.command-clear { width: 44px; }
		.bottom-nav .nav-icon :global(svg) { width: 19px; height: 19px; }
		.mobile-profile-menu > summary::-webkit-details-marker { display: none; }
		.bottom-nav .nav-label-text { display: block; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
		.bottom-nav > a:hover, .mobile-profile-menu > summary:hover { color: var(--primary-strong); }
		.bottom-nav > a[aria-current],
		.mobile-profile-menu > summary[aria-current],
		.mobile-profile-menu[open] > summary {
			color: var(--primary-strong);
			font-weight: 750;
		}
		.bottom-nav > a[aria-current] .nav-icon,
		.mobile-profile-menu > summary[aria-current] .nav-icon,
		.mobile-profile-menu[open] > summary .nav-icon {
			background: var(--primary-soft);
		}
		.bottom-nav > a.scan-destination { color: var(--primary-strong); }
		.bottom-nav > a.scan-destination .nav-icon { width: 38px; height: 36px; margin-top: -8px; background: var(--primary-strong); color: var(--surface); box-shadow: 0 4px 10px color-mix(in srgb, var(--primary-strong) 26%, transparent); }
		.bottom-nav > a.scan-destination[aria-current] .nav-icon { background: var(--primary-strong); color: var(--surface); }
		.mobile-profile-menu { position: relative; display: block; min-width: 0; }
		.mobile-profile-icon { position: relative; }
		.mobile-profile-dropdown {
			position: fixed;
			inset-inline: 10px;
			inset-block-end: calc(70px + env(safe-area-inset-bottom));
			z-index: 40;
			max-width: 500px;
			margin-inline: auto;
			max-height: calc(100svh - 140px);
			overflow-y: auto;
		}
	}
</style>
