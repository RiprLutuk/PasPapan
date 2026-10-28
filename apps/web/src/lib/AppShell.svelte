<script lang="ts">
	import { onMount, setContext, untrack, type Snippet } from 'svelte';
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
	import ErrorState from './ErrorState.svelte';
	import { sessionRemaining, sessionWarning, type SessionWarning } from './session-policy';
	import type { SessionLifetime } from './api';
	import { m } from './paraglide/messages.js';
	import { uiText as t } from './i18n';
	import { legacyDestination } from './legacy-destinations';

	let { children, admin = false }: { children: Snippet; admin?: boolean } = $props();
	const initialSession = untrack(() => page.data.authSession ?? null);
	let session = $state<CurrentSession | null>(initialSession ?? getCachedSession());
	let loading = $state(!initialSession), error = $state(''), signingOut = $state(false);
	let sessionVerified = $state(!!initialSession);
	let failureStatus = $state(0), warning = $state<SessionWarning|null>(null), activityError = $state('');
	let receivedAt = Date.now(), pendingActivity = true, lastHeartbeat = 0, ending = false, lastVerification = 0;
	let heartbeatBusy = $state(false);
	let verifying: Promise<void>|null = null;
	let authChannel: BroadcastChannel|null = null;
	let stopRealtime = () => {};
	let sidebarHidden = $state(false);
	let navigationDialog = $state<HTMLDialogElement | null>(null);
	let mobileProfileOpen = $state(false);
	let unreadNotificationCount = $state<number | null>(getCachedUnreadCount());
	let notificationError = $state('');
	let notificationRefreshSequence = 0;
	let commandDialog = $state<HTMLDialogElement | null>(null);
	let commandInput = $state<HTMLInputElement | null>(null);
	let commandQuery = $state('');
	let commandCategory = $state(t('Semua'));
	let commandIndex = $state(0);
	let tickerIndex = $state(0);
	let headerHidden = $state(false);
	let headerElement: HTMLElement;
	const translated = (values: string[]) => values.map((value) => t(value));

	function clearSession() {
		session = null;
		setCachedSession(null);
		unreadNotificationCount = null;
	}
	setContext(sessionKey, { get current() { return session; }, refresh: refreshAccess, clear: clearSession });

	const primaryLinks = [
		{ href: '/app/today', label: m.nav_home(), icon: 'home', hint: t('Presensi & ringkasan hari ini') },
		{ href: '/app/schedule', label: m.nav_schedule(), icon: 'calendar', hint: t('Kalender shift kerja') },
		{ href: '/app/scan', label: m.nav_scan(), icon: 'qr', hint: t('Check-in & check-out QR lokasi') },
		{ href: '/app/requests', label: m.nav_requests(), icon: 'request', hint: t('Izin, cuti, lembur & klaim') }
	];
	const secondaryDesktopLinks = [
		{ href: '/app/history', label: m.nav_history(), icon: 'clock', hint: t('Catatan kehadiran bulanan') }
	];
	const workspaceModuleLinks = [
		{ href: '/payroll', label: t('Slip Gaji'), group: t('Keuangan & Klaim'), icon: 'wallet', hint: t('Rincian pendapatan bulanan') },
		{ href: '/cash-advance', label: t('Kasbon'), group: t('Keuangan & Klaim'), icon: 'banknote', hint: t('Pengajuan pinjaman karyawan') },
		{ href: '/shift-swap', label: t('Tukar Shift'), group: t('Kehadiran & Shift'), icon: 'swap', hint: t('Delegasi jadwal dengan rekan') },
		{ href: '/wfh', label: t('Izin WFH'), group: t('Kehadiran & Shift'), icon: 'briefcase', hint: t('Lapor kerja jarak jauh') },
		{ href: '/face-enrollment', label: t('Biometrik'), group: t('Kehadiran & Shift'), icon: 'face', hint: t('Verifikasi profil wajah') },
		{ href: '/announcements', label: t('Pengumuman'), group: t('Komunikasi & SDM'), icon: 'megaphone', hint: t('Papan informasi perusahaan') },
		{ href: '/hr-letters', label: t('Surat HR'), group: t('Komunikasi & SDM'), icon: 'file', hint: t('Surat keterangan & dokumen') },
		{ href: '/assets', label: t('Inventaris'), group: t('Komunikasi & SDM'), icon: 'box', hint: t('Pinjaman perangkat kerja') },
		{ href: '/kpi', label: t('Evaluasi KPI'), group: t('Komunikasi & SDM'), icon: 'chart', hint: t('Target & capaian kinerja') },
		{ href: '/offboarding', label: t('Offboarding'), group: t('Komunikasi & SDM'), icon: 'logout', hint: t('Serah terima & clearance') }
	];
	const adminModuleLinks = [
		{ href: '/admin/hr-checklists', label: 'Checklist HR', icon: 'tasks', group: 'Organisasi & Audit', hint: 'Onboarding & offboarding', permission: 'admin.checklists.manage' },
		{ href: '/admin/hr-documents', label: 'Surat HR', icon: 'file', group: 'Organisasi & Audit', hint: 'Permintaan & dokumen resmi', permission: 'admin.documents.manage' },
		{ href: '/admin/organization', label: 'Master data organisasi', icon: 'building', group: 'Organisasi & Audit', hint: 'Divisi, jabatan, cuti & hari libur', permission: 'admin.organization.manage' },
		{ href: '/admin/dashboard', label: 'Dashboard Admin', icon: 'home', group: 'Operasional Inti', hint: 'Ringkasan kehadiran & antrean', permission: 'admin.dashboard.view' },
		{ href: '/admin/employees', label: 'Karyawan', icon: 'people', group: 'Operasional Inti', hint: 'Direktori & profil anggota', permission: 'admin.employees.view' },
		{ href: '/admin/attendance', label: 'Master Shift', icon: 'clock', group: 'Operasional Inti', hint: 'Pengaturan jam kerja', permission: 'admin.shifts.manage' },
		{ href: '/admin/schedules', label: 'Penjadwalan', icon: 'calendar', group: 'Operasional Inti', hint: 'Penugasan shift bulanan', permission: 'admin.schedules.manage' },
		{ href: '/admin/barcodes', label: 'Lokasi & QR', icon: 'pin', group: 'Operasional Inti', hint: 'Titik geofence & kode QR', permission: 'admin.barcodes.manage' },
		{ href: '/admin/import-export/attendances', label: 'Log Presensi', icon: 'clock', group: 'SDM & Kehadiran', hint: 'Rekap kehadiran seluruh tim', permission: 'admin.attendances.view' },
		{ href: '/admin/overtime', label: 'Manajemen Lembur', icon: 'clock', group: 'SDM & Kehadiran', hint: 'Persetujuan jam lembur', permission: 'requests.approvals.view' },
		{ href: '/admin/leave-entitlements', label: 'Kuota Cuti', icon: 'calendar', group: 'SDM & Kehadiran', hint: 'Jatah cuti tahunan', permission: 'admin.organization.manage' },
		{ href: '/admin/shift-swaps', label: 'Tukar Shift Tim', icon: 'swap', group: 'SDM & Kehadiran', hint: 'Persetujuan tukar jadwal', permission: 'requests.approvals.view' },
		{ href: '/admin/payrolls', label: 'Payroll & Slip', icon: 'wallet', group: 'Keuangan & Aset', hint: 'Periode gaji & potongan', permission: 'admin.payroll.view' },
		{ href: '/admin/reimbursements', label: 'Reimbursement', icon: 'banknote', group: 'Keuangan & Aset', hint: 'Verifikasi klaim biaya', permission: 'requests.approvals.view' },
		{ href: '/admin/cash-advances', label: 'Persetujuan Kasbon', icon: 'banknote', group: 'Keuangan & Aset', hint: 'Pencairan & cicilan kasbon', permission: 'requests.approvals.view' },
		{ href: '/admin/assets', label: 'Aset & Inventaris', icon: 'box', group: 'Keuangan & Aset', hint: 'Daftar perangkat kantor', permission: 'admin.assets.manage' },
		{ href: '/admin/announcements', label: 'Siaran Pengumuman', icon: 'megaphone', group: 'Organisasi & Audit', hint: 'Publikasi info internal', permission: 'admin.announcements.manage' },
		{ href: '/admin/kpi', label: 'Penilaian KPI', icon: 'chart', group: 'Organisasi & Audit', hint: 'Evaluasi performa tim', permission: 'admin.performance.manage' },
		{ href: '/admin/activity-logs', label: 'Audit Trail', icon: 'file', group: 'Organisasi & Audit', hint: 'Jejak aktivitas sistem', permission: 'admin.audit.view' },
		{ href: '/admin/settings', label: 'Storage & Sistem', icon: 'panel', group: 'Keamanan & DB', hint: 'Konfigurasi berkas & objek', permission: 'admin.system.view' },
		{ href: '/admin/integrations', label: 'Integrasi layanan', icon: 'shield', group: 'Keamanan & DB', hint: 'Provider dan kredensial perusahaan', permission: 'admin.access.manage' },
		{ href: '/admin/system-info', label: 'Monitor Database', icon: 'database', group: 'Keamanan & DB', hint: 'Kesehatan koneksi & pool', permission: 'admin.system.view' }
	].map((item) => ({ ...item, label: t(item.label), group: t(item.group), hint: t(item.hint) }));

	let operationLinks = $derived(visibleOperations(session?.capabilities).map((item) => ({ ...item, label: t(item.label), group: t(item.group) })));
	let desktopLinks = $derived([...primaryLinks, ...secondaryDesktopLinks, ...operationLinks.filter(item => !extendedAdminLinks.some(admin => admin.href === item.href))]);
	let mobileLinks = $derived(primaryLinks);
	let extendedAdminLinks = $derived(
		session
			? [
						...adminModuleLinks.filter((item) => session?.capabilities?.includes(item.permission)),
					...(session?.capabilities?.includes('admin.access.manage')
						? [
								{ href: '/admin/access', label: t('Peran & Izin'), icon: 'shield', group: t('Keamanan & DB'), hint: t('Matriks hak akses') },
								{ href: '/admin/api-docs', label: 'API Docs & Postman', icon: 'code', group: t('Keamanan & DB'), hint: t('Dokumentasi endpoint & koleksi') }
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
			category: t('Utama'),
			icon: item.icon,
			hint: 'hint' in item && typeof item.hint === 'string' ? item.hint : t('Navigasi utama workspace')
		})),
		...workspaceModuleLinks.map((item) => ({
			href: item.href,
			label: item.label,
			category: t('Layanan SDM'),
			icon: item.icon,
			hint: item.hint
		})),
		{ href: '/app/notifications', label: t('Kotak Masuk Notifikasi'), category: t('Akun'), icon: 'bell', hint: t('Pemberitahuan & pembaruan status') },
		{ href: '/user/profile', label: m.profile_page_title(), category: m.profile_account(), icon: 'profile', hint: m.tab_sessions_description() },
		...extendedAdminLinks.map((item) => ({
			href: item.href,
			label: item.label,
			category: t('Admin'),
			icon: item.icon,
			hint: item.hint
		}))
	]);

	let headerTitle = $derived(commandItems.find((item) => item.href === page.url.pathname)?.label ?? (admin ? t('Admin') : t('Workspace')));
	let availableCategories = $derived([
		t('Semua'),
		t('Utama'),
		t('Layanan SDM'),
		t('Akun'),
		...(extendedAdminLinks.length ? [t('Admin')] : [])
	]);

	let filteredCommands = $derived.by(() => {
		const q = commandQuery.trim().toLocaleLowerCase('id');
		const seen = new Set<string>();
		return commandItems.filter((item) => {
			if (commandCategory !== t('Semua') && item.category !== commandCategory) return false;
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
	let selectedArea = $derived(isAdminContext && extendedAdminLinks.length ? 'admin' : 'workspace');
	let adminLanding = $derived(extendedAdminLinks.find(item => item.href === '/admin/dashboard')?.href ?? extendedAdminLinks[0]?.href);
	let requiredCapability = $derived(requiredPermission(page.url.pathname, isAdminContext));
	let forbidden = $derived(requiredCapability !== null && !session?.capabilities?.includes(requiredCapability));

	let searchSuggestions = $derived.by(() => {
		const path = page.url.pathname;
		if (path.startsWith('/app/history')) {
			return translated([
				'Cari koreksi presensi…',
				'Cari jadwal shift kerja…',
				'Cari slip gaji bulanan…',
				'Cari pengajuan izin…'
			]);
		}
		if (path.startsWith('/app/schedule')) {
			return translated([
				'Cari tukar shift rekan…',
				'Cari pengajuan izin WFH…',
				'Cari riwayat kehadiran…',
				'Cari pengajuan cuti…'
			]);
		}
		if (path.startsWith('/app/requests')) {
			return translated([
				'Cari kasbon karyawan…',
				'Cari kuota cuti & izin…',
				'Cari surat keterangan HR…',
				'Cari jadwal kerja…'
			]);
		}
		if (path.startsWith('/app/notifications')) {
			return translated([
				'Cari pengumuman kantor…',
				'Cari persetujuan tim…',
				'Cari profil & keamanan…',
				'Cari riwayat presensi…'
			]);
		}
		if (path.startsWith('/user/profile')) {
			return translated([
				'Cari ubah kata sandi…',
				'Cari sesi perangkat aktif…',
				'Cari biometrik wajah…',
				'Cari slip gaji & THP…'
			]);
		}
		if (path.startsWith('/admin')) {
			return translated([
				'Cari direktori karyawan…',
				'Cari lokasi & kode QR…',
				'Cari penjadwalan shift…',
				'Cari payroll & audit log…'
			]);
		}
		return translated([
			'Cari scan QR presensi…',
			'Cari slip gaji & kasbon…',
			'Cari pengajuan cuti & WFH…',
			'Cari tukar shift kerja…',
			'Cari inventaris & surat HR…'
		]);
	});

	let activeSearchPrompt = $derived(
		searchSuggestions[tickerIndex % Math.max(1, searchSuggestions.length)] ?? t('Cari fitur…')
	);

	$effect(() => {
		void page.url.pathname;
		tickerIndex = 0;
	});

	function isActive(href: string) {
		const pathname = page.url.pathname;
		const target = legacyDestination(href)?.destination ?? href;
		const [route, query] = target.split('?');
		return (pathname === route || pathname.startsWith(`${route}/`)) && (!query || [...new URLSearchParams(query)].every(([key, value]) => page.url.searchParams.get(key) === value));
	}

	function openCommandPalette() {
		commandQuery = '';
		commandCategory = t('Semua');
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
			notificationError = cause instanceof Error ? cause.message : t('Jumlah notifikasi tidak dapat dimuat.');
		}
	}

	async function endSession(reason='expired', broadcast=true) {
		if(ending)return;ending=true;stopRealtime();dismissLiveToast();realtimeState.lastPulse=null;syncPwaAppBadge(0);notificationRefreshSequence++;clearSession();
		if(broadcast)authChannel?.postMessage({type:'logout'});
		const next=page.url.pathname+page.url.search;
		await goto(`/login?reason=${reason}&next=${encodeURIComponent(next)}`,{replaceState:true});
	}
	async function performLoad() {
		if(!session)loading=true;
		try {
			const fresh=await currentSession();
			if(ending)return;
			session=fresh;sessionVerified=true;receivedAt=Date.now();warning=fresh.lifetime?sessionWarning(fresh.lifetime,receivedAt):null;setCachedSession(fresh);error='';failureStatus=0;
			void refreshNotificationCount();
		} catch(cause) {
			if(cause instanceof ApiError&&cause.status===401){await endSession();}
			else {failureStatus=cause instanceof ApiError&&cause.status>=500?cause.status:503;error=t('Sesi belum dapat diverifikasi. Periksa koneksi lalu coba lagi.');}
		} finally {loading=false;}
	}
	async function load() {
		if(verifying)return verifying;
		lastVerification=Date.now();verifying=performLoad();try{await verifying;}finally{verifying=null;}
	}
	async function recordActivity() {
		if(!session||ending||heartbeatBusy)return;
		heartbeatBusy=true;activityError='';lastHeartbeat=Date.now();
		try {
			const lifetime=await request<SessionLifetime>('/v1/auth/activity',{method:'POST',body:'{}'});
			if(!session||ending)return;
			session={...session,lifetime};receivedAt=Date.now();warning=sessionWarning(lifetime,receivedAt);setCachedSession(session);lastHeartbeat=Date.now();pendingActivity=false;
		} catch(cause){if(cause instanceof ApiError&&cause.status===401)await load();else activityError=t('Sesi belum dapat diperpanjang. Coba lagi.');}
		finally{heartbeatBusy=false;}
	}

	async function logout() {
		signingOut = true; error = '';
		try {
			await request('/v1/auth/logout', { method: 'POST' });
			await endSession('signed-out');
		}
		catch { error = t('Gagal keluar. Coba lagi.'); }
		finally { signingOut = false; }
	}

	function closeProfileMenus() {
		mobileProfileOpen = false;
		document.querySelectorAll<HTMLDetailsElement>('.profile-menu, .mobile-profile-menu').forEach((menu) => {
			menu.open = false;
		});
	}
	function notificationBadgeLabel() {
		if (!unreadNotificationCount) return '';
		return unreadNotificationCount > 99 ? '99+' : String(unreadNotificationCount);
	}
	async function refreshAccess() { await load(); }

	afterNavigate(() => { headerHidden = false; navigationDialog?.close(); void refreshAccess(); closeProfileMenus(); });

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
		stopRealtime=disconnectRealtime;
		try { authChannel=new BroadcastChannel('paspapan-auth');authChannel.onmessage=()=>{void load();}; } catch { /* API polling remains the fallback. */ }
		const activity=()=>{pendingActivity=true;if(warning?.kind==='idle'&&!heartbeatBusy&&Date.now()-lastHeartbeat>=5000)void recordActivity();};
		const expiredEvent=()=>{void load();};
		const loggedOut=()=>{void endSession('signed-out');};
		for(const name of ['pointerdown','keydown','touchstart','wheel'])window.addEventListener(name,activity,{passive:true});
		window.addEventListener('paspapan:session-expired',expiredEvent);
		window.addEventListener('paspapan:logout',loggedOut);
		const idleTimer=window.setInterval(()=>{
			if(!session||ending)return;
			if(session.lifetime){
				warning=sessionWarning(session.lifetime,receivedAt);
				if(sessionRemaining(session.lifetime,receivedAt)===0&&!verifying&&Date.now()-lastVerification>=15000){void load();return;}
			}
			if(pendingActivity&&Date.now()-lastHeartbeat>=60000&&document.visibilityState==='visible')void recordActivity();
		},1000);
		const handleKeydown = (event: KeyboardEvent) => {
			if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
				event.preventDefault();
				if (commandDialog?.open) closeCommandPalette();
				else openCommandPalette();
			} else if (event.key === 'Escape' && mobileProfileOpen) {
				closeProfileMenus();
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
			authChannel?.close();window.clearInterval(idleTimer);
			for(const name of ['pointerdown','keydown','touchstart','wheel'])window.removeEventListener(name,activity);
			window.removeEventListener('paspapan:session-expired',expiredEvent);window.removeEventListener('paspapan:logout',loggedOut);
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
		<button type="button" class="quiet profile-close-btn" aria-label={t('Tutup menu')} onclick={closeProfileMenus}>
			<Icon name="close" size={14} />
		</button>
	</div>

	<div class="profile-quick-strip">
		<a href="/user/profile" aria-current={isActive('/user/profile') ? 'page' : undefined} onclick={closeProfileMenus}>
			<Icon name="profile" size={16} />
			<span>{t('Akun')}</span>
		</a>
		<a href="/app/notifications" aria-current={isActive('/app/notifications') ? 'page' : undefined} onclick={closeProfileMenus}>
			<Icon name="bell" size={16} />
			<span>{t('Notifikasi')}</span>
			{#if unreadNotificationCount}<b class="notification-count">{notificationBadgeLabel()}</b>{/if}
		</a>
	</div>

	{#if notificationError}<p class="notification-error" role="alert">{notificationError} <button class="quiet" onclick={refreshNotificationCount}>{t('Coba lagi')}</button></p>{/if}
	<button class="quiet profile-logout" aria-label={t('Keluar')} disabled={signingOut} onclick={logout}>
		<Icon name="logout" size={15} />
		<span>{t('Keluar dari akun')}</span>
	</button>
{/snippet}

{#snippet navigation()}
	<div class="navigation-context"><span class="workspace-symbol"><Icon name="building" size={18}/></span><div><strong>{session?.company.name ?? 'PasPapan'}</strong><small>{selectedArea === 'admin' ? t('Operasional & Admin') : t('Layanan Mandiri')}</small></div></div>
	{#if adminLanding}<nav class="navigation-switch" aria-label={t('Navigasi')}><a href="/app/today" aria-current={selectedArea === 'workspace' ? 'true' : undefined}><Icon name="home" size={14}/>{t('Workspace')}</a><a href={adminLanding} aria-current={selectedArea === 'admin' ? 'true' : undefined}><Icon name="building" size={14}/>{t('Admin')}</a></nav>{/if}
	{#if selectedArea === 'workspace'}
		<p class="nav-label">{m.main_menu()}</p>
		<nav aria-label={m.main_menu()}>{#each desktopLinks as item}<a href={item.href} aria-current={isActive(item.href) ? 'page' : undefined}><Icon name={item.icon} size={17}/><span>{item.label}</span>{#if isActive(item.href)}<i class="active-dot" aria-hidden="true"></i>{/if}</a>{/each}</nav>
	{/if}
	{#each (selectedArea === 'admin' ? adminModuleGroups : workspaceModuleGroups) as group (group.group)}
		<details class="nav-group" open={group.links.some(item => isActive(item.href))}>
			<summary><span>{group.group}</span><Icon name="chevron" size={13}/></summary>
			<nav aria-label={group.group}>{#each group.links as item}<a href={item.href} title={item.label} aria-current={isActive(item.href) ? 'page' : undefined}><Icon name={item.icon} size={17}/><span>{item.label}</span>{#if isActive(item.href)}<i class="active-dot" aria-hidden="true"></i>{/if}</a>{/each}</nav>
		</details>
	{/each}
{/snippet}

<div class="work-shell" class:sidebar-collapsed={sidebarHidden}>
	<a class="skip-link" href={failureStatus ? "#session-error" : "#main-content"}>{t('Lewati ke konten')}</a>
	<header class="topbar" class:header-hidden={headerHidden} bind:this={headerElement} onfocusin={() => headerHidden = false}>
		<div class="topbar-start">
			<button class="mobile-navigation-button quiet icon-button" aria-label={t('Tampilkan menu')} aria-haspopup="dialog" onclick={() => { closeProfileMenus(); navigationDialog?.showModal(); }}><Icon name="panel" size={18}/></button>
			<button class="desktop-menu-button quiet icon-button" title={sidebarHidden ? t('Tampilkan menu') : t('Sembunyikan menu')} aria-label={sidebarHidden ? t('Tampilkan menu') : t('Sembunyikan menu')} aria-expanded={!sidebarHidden} aria-controls="workspace-sidebar" onclick={() => sidebarHidden = !sidebarHidden}><Icon name="panel" /></button>
			<span class="workspace-label">{headerTitle}</span>
		</div>
		{#if session}
			<div class="topbar-actions">
				<button type="button" class="command-trigger quiet" onclick={openCommandPalette} aria-label={`${t('Cari fitur atau halaman')} (Command+K)`} title={`${t('Cari fitur atau halaman')} (⌘K)`}>
					<Icon name="search" size={14} />
					<span class="command-ticker-viewport" aria-live="off">
						{#key `${page.url.pathname}-${tickerIndex}`}
							<span class="command-trigger-text">{activeSearchPrompt}</span>
						{/key}
					</span>
					<span class="mobile-search-text">{m.search_short()}</span>
					<kbd>⌘K</kbd>
				</button>
				<a class="header-notifications" href="/app/notifications" aria-label={t('Notifikasi')} title={t('Notifikasi')}><Icon name="bell" size={18}/>{#if unreadNotificationCount}<span class="profile-badge">{notificationBadgeLabel()}</span>{/if}</a>
				<details class="profile-menu">
					<summary aria-label={`Menu profil${unreadNotificationCount ? `, ${unreadNotificationCount} notifikasi belum dibaca` : ''}`} title={t('Akun')}>
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

	<dialog bind:this={navigationDialog} class="navigation-dialog" aria-label={t('Navigasi')} onclick={event => { if(event.target === navigationDialog) navigationDialog?.close(); }} onkeydown={event => { if(event.key === 'Escape') navigationDialog?.close(); }}>
		<div class="navigation-panel"><div class="navigation-mobile-heading"><a href="/app/today" aria-label={`PasPapan — ${t('Beranda')}`}><Brand size={26}/></a><button class="quiet icon-button" aria-label={t('Tutup menu')} onclick={() => navigationDialog?.close()}><Icon name="close" size={18}/></button></div>{@render navigation()}</div>
	</dialog>
	<dialog bind:this={commandDialog} class="command-dialog" aria-label="Pencarian cepat fitur dan halaman" onkeydown={handleCommandKeydown}>
		<div class="command-header">
			<span class="command-search-icon"><Icon name="search" size={16} /></span>
			<input
				bind:this={commandInput}
				type="search"
				bind:value={commandQuery}
					placeholder={t('Cari menu atau fitur…')}
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
					<p>{t('Tidak ditemukan hasil untuk')} <strong>"{commandQuery}"</strong>.</p>
				</div>
			{/each}
		</div>
	</dialog>

	<div class="shell-body" class:sidebar-hidden={sidebarHidden}>
		<aside class="sidebar" id="workspace-sidebar" aria-label="Menu workspace">
			<a class="sidebar-brand" href="/app/today" aria-label={`PasPapan — ${t('Beranda')}`}><Brand size={28}/></a>
			{@render navigation()}
		</aside>
		<div class="content-column">
			{#if failureStatus}<main id="session-error" tabindex="-1"><ErrorState status={failureStatus} retry={()=>void load()}/></main>{/if}
			{#if !failureStatus && loading}<main id="main-content" class="workspace-page" tabindex="-1"><p role="status">{t('Memuat papan kerja…')}</p></main>
			{:else if !failureStatus && session && forbidden}<main id="main-content" tabindex="-1"><ErrorState status={403}/></main>
			{:else if session && sessionVerified && !forbidden}
				<div hidden={failureStatus>0}>
				{#if warning}
					<div class="idle-warning" role="status">
						<div><strong>{t(warning.kind==='idle'?'Tidak ada aktivitas':'Batas waktu sesi hampir habis')}</strong><span>{t(warning.kind==='idle'?'Lanjutkan aktivitas agar tetap masuk.':'Simpan pekerjaan Anda, lalu masuk ulang.')} <time>{warning.countdown}</time></span>{#if activityError}<small role="alert">{activityError}</small>{/if}</div>
						{#if warning.kind==='idle'}<button class="quiet" disabled={heartbeatBusy} onclick={()=>void recordActivity()}>{t(heartbeatBusy?'Memperpanjang…':'Lanjutkan sesi')}</button>
						{:else}<button class="quiet" disabled={signingOut} onclick={()=>void logout()}>{t('Masuk ulang')}</button>{/if}
					</div>
				{/if}
				{@render children()}
				</div>
			{/if}
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
			<details class="mobile-profile-menu" bind:open={mobileProfileOpen}>
				<summary aria-label={`Menu profil${unreadNotificationCount ? `, ${unreadNotificationCount} notifikasi belum dibaca` : ''}`} aria-current={isActive('/user/profile') || isActive('/app/notifications') || mobileProfileOpen ? 'page' : undefined}>
					<span class="nav-icon mobile-profile-icon"><Icon name="profile" size={20} />{#if unreadNotificationCount}<span class="profile-badge" aria-hidden="true">{notificationBadgeLabel()}</span>{/if}</span>
					<span class="nav-label-text">{m.nav_profile()}</span>
				</summary>
			</details>
		{:else}
			<a href="/user/profile" aria-label={m.nav_profile()} title={m.nav_profile()} aria-current={isActive('/user/profile') ? 'page' : undefined}><span class="nav-icon"><Icon name="profile" size={20} /></span><span class="nav-label-text">{m.nav_profile()}</span></a>
		{/if}
	</nav>

	{#if mobileProfileOpen && session}
		<button
			type="button"
			tabindex="-1"
			class="mobile-profile-backdrop"
			aria-label={t('Tutup menu profil')}
			onclick={closeProfileMenus}
		></button>
		<div class="mobile-profile-dropdown" role="dialog" aria-modal="true" aria-label={t('Menu profil dan layanan')}>
			{@render profileDropdown()}
		</div>
	{/if}

	{#if realtimeState.toast}
		<aside class="realtime-toast" role="status" aria-live="polite">
			<span class="toast-icon"><Icon name="bell" size={17} /></span>
			<div class="toast-copy">
				<strong>{realtimeState.toast.title}</strong>
				<p>{realtimeState.toast.body}</p>
			</div>
			<div class="toast-actions">
				<a class="toast-open" href={realtimeState.toast.href} onclick={dismissLiveToast}>{t('Buka')}</a>
				<button type="button" class="quiet toast-close" aria-label={t('Tutup notifikasi')} onclick={dismissLiveToast}>
					<Icon name="close" size={14} />
				</button>
			</div>
		</aside>
	{/if}
</div>

<style>
	.work-shell { display:grid; grid-template-columns:248px minmax(0,1fr); grid-template-rows:56px minmax(0,1fr); min-height: 100svh; background: var(--canvas); }
	.work-shell.sidebar-collapsed { grid-template-columns:minmax(0,1fr); }
	.work-shell.sidebar-collapsed .topbar, .work-shell.sidebar-collapsed .content-column { grid-column:1; }
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
		background: var(--accent-solid);
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
		grid-column:2;
		grid-row:1;
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
	.sidebar-brand, .navigation-mobile-heading a { display:flex; align-items:center; text-decoration:none; color:var(--ink); font-size:18px; }
	.sidebar-brand { min-height:56px; padding:0 10px; margin:-14px 0 16px; flex-shrink:0; }
	.workspace-label { min-width:0; font-size:13px; color:var(--ink); font-weight:650; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
	.desktop-menu-button { display: inline-flex; align-items: center; justify-content: center; width: 30px; min-height: 30px !important; padding: 5px !important; border:0; background:transparent; box-shadow:none; flex-shrink:0; }
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
	.mobile-search-text { display: none; }
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
	.header-notifications { position:relative; display:grid; place-items:center; width:32px; height:32px; flex-shrink:0; color:var(--muted); border-radius:var(--radius-sm); }
	.header-notifications:hover { background:var(--surface-alt); color:var(--ink); }
	.avatar, .avatar-trigger { width: 34px; height: 34px; border-radius: var(--radius-sm); background: var(--primary-soft); color: var(--primary-strong); display: grid; place-items: center; font-size: 13px; font-weight: 800; }
	.profile-menu { position: relative; }
	.profile-menu summary { position: relative; display: grid; place-items: center; width: 36px; height: 36px; border: 1px solid var(--line); border-radius: var(--radius); background: var(--surface); color: var(--muted); cursor: pointer; list-style: none; transition: background-color .14s ease, border-color .14s ease, color .14s ease; }
	.profile-menu summary::-webkit-details-marker { display: none; }
	.profile-menu summary:hover { background: var(--surface-alt); color: var(--ink); }
	.profile-menu[open] summary { border-color: var(--primary); background: var(--primary-soft); color: var(--primary-strong); }
	.profile-badge { position: absolute; inset-block-start: -4px; inset-inline-end: -4px; display: grid; place-items: center; min-width: 16px; height: 16px; padding-inline: 4px; border: 2px solid var(--surface); border-radius: var(--radius-sm); background: #e11d48; color: #fff; font-size: 9.5px; font-weight: 800; line-height: 1; }

	/* Account-only menu */
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
		width: min(288px, calc(100vw - 20px));
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
		grid-template-columns: 1fr;
		gap: 4px;
	}
	.profile-quick-strip a {
		position: relative;
		display: flex;
		align-items: center;
		justify-content: flex-start;
		gap: 9px;
		min-height: 40px;
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
		border: 0 !important;
		border-radius: var(--radius-sm) !important;
		background: color-mix(in srgb, var(--danger, #b64032) 8%, var(--surface)) !important;
		color: var(--danger, #b64032) !important;
		font-size: 12px;
		font-weight: 700;
		box-shadow: none !important;
	}
	.profile-logout:hover:not(:disabled) {
		background: color-mix(in srgb, var(--danger, #b64032) 14%, var(--surface)) !important;
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

	.shell-body { display:contents; }
	.shell-body.sidebar-hidden .sidebar { display: none; }
	.sidebar {
		grid-column:1;
		grid-row:1 / span 2;
		position: sticky;
		top: 0;
		height: 100svh;
		overflow-y: auto;
		padding: 14px 10px 24px;
		border-right: 1px solid var(--line);
		display: flex;
		flex-direction: column;
		background: var(--surface);
	}
	.navigation-context { display:flex; align-items:center; gap:10px; padding:4px 8px 14px; }
	.navigation-context > div { display:grid; gap:3px; min-width:0; }
	.navigation-context strong { font-size:12px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
	.navigation-context small { font-size:10px; color:var(--muted); }
	.workspace-symbol { display:grid; place-items:center; width:34px; height:34px; border-radius:var(--radius); background:var(--surface-alt); color:var(--muted); flex-shrink:0; }
	.navigation-switch { display:flex; gap:4px; padding:4px; background:var(--surface-alt); border-radius:var(--radius); margin-bottom:16px; }
	.navigation-switch a { display:flex; align-items:center; justify-content:center; gap:6px; flex:1; border:0; text-decoration:none; box-shadow:none; min-height:32px; padding:5px 8px; font-size:11px; font-weight:650; color:var(--muted); background:transparent; border-radius:var(--radius-sm); }
	.navigation-switch a[aria-current] { background:var(--surface); color:var(--ink); box-shadow:0 1px 3px #00000012; }
	.nav-label { font-size:9px; font-weight:750; letter-spacing:.08em; text-transform:uppercase; color:var(--muted); margin:0 10px 8px; }
	.sidebar nav:not(.navigation-switch), .navigation-panel nav:not(.navigation-switch) { display:grid; gap:3px; }
	.nav-group { margin-top:12px; }
	.nav-group summary { display:flex; align-items:center; justify-content:space-between; gap:8px; padding:8px 10px; border-radius:var(--radius-sm); color:var(--muted); font-size:10px; font-weight:700; cursor:pointer; list-style:none; }
	.nav-group summary::-webkit-details-marker { display:none; }
	.nav-group summary:hover { background:var(--surface-alt); color:var(--ink); }
	.nav-group summary > :global(svg) { flex-shrink:0; transition:transform .15s ease; transform:rotate(-90deg); }
	.nav-group[open] summary > :global(svg) { transform:rotate(0); }
	.sidebar nav:not(.navigation-switch) a, .navigation-panel nav:not(.navigation-switch) a { position:relative; display:flex; align-items:center; gap:10px; min-height:38px; padding:8px 10px; border-radius:var(--radius-sm); color:var(--muted); text-decoration:none; font-size:12px; font-weight:550; background-clip:padding-box; transition:background-color .14s ease,color .14s ease; }
	.sidebar nav:not(.navigation-switch) a > span, .navigation-panel nav:not(.navigation-switch) a > span { flex:1; min-width:0; }
	.sidebar nav:not(.navigation-switch) a > :global(svg), .navigation-panel nav:not(.navigation-switch) a > :global(svg) { flex-shrink:0; opacity:.8; }
	.sidebar nav:not(.navigation-switch) a:hover, .navigation-panel nav:not(.navigation-switch) a:hover { background:var(--surface-alt); color:var(--ink); }
	.sidebar nav:not(.navigation-switch) a[aria-current], .navigation-panel nav:not(.navigation-switch) a[aria-current] { color:var(--primary-strong); background:var(--primary-soft); font-weight:700; }
	.active-dot { width:5px; height:5px; border-radius:50%; background:currentColor; flex-shrink:0; }
	.mobile-navigation-button { display:none; }
	.navigation-dialog { padding:0; margin:8px; width:min(300px,calc(100vw - 32px)); height:calc(100svh - 88px); max-height:none; border:1px solid var(--line); border-radius:var(--radius-lg); background:var(--surface); color:var(--ink); }
	.navigation-dialog::backdrop { background:#00000055; }
	.navigation-panel { padding:14px 10px 20px; }
	.navigation-mobile-heading { display:flex; justify-content:space-between; align-items:center; margin:0 6px 18px; font-size:15px; }

	.content-column { grid-column:2; grid-row:2; min-width: 0; min-height: calc(100svh - 56px); }
	.idle-warning {display:flex;align-items:center;justify-content:space-between;gap:12px;margin:12px 18px;padding:12px;background:var(--surface-alt);border-radius:var(--radius);font-size:12px;flex-wrap:wrap;}
	.idle-warning > div {display:grid;gap:4px;flex:1;min-width:180px}.idle-warning strong{font-size:12px}.idle-warning span,.idle-warning small{font-size:11px;color:var(--muted)}.idle-warning time{font-variant-numeric:tabular-nums;font-weight:700;white-space:nowrap}.idle-warning button{white-space:nowrap}
	.bottom-nav { display: none; }
	.mobile-profile-menu { display: none; }
	.mobile-profile-backdrop { display: none; }

	@media(max-width:1000px){
		.work-shell { display:block; }
		.shell-body { display: block; }
		.sidebar { display: none; }
		.desktop-menu-button { display: none; }
		.mobile-navigation-button { display:inline-grid; place-items:center; width:32px; min-height:32px; padding:5px; }
	}

	@media(max-width:760px){
		.realtime-toast {
			right: 12px;
			bottom: 76px;
		}
		.topbar { height: 46px; padding: 0 10px; gap: 8px; }
		.command-trigger kbd, .profile-menu { display: none; }
		.workspace-label { display: block; border: 0; padding: 0; margin: 0; font-size: 12px; letter-spacing: 0; text-transform: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
		.topbar-start { flex: 1; }
		.command-ticker-viewport { display: none; }
		.mobile-search-text { display: inline; font-size: 10.5px; font-weight: 600; color: var(--muted); }
		.command-trigger {
			min-width: 0;
			max-width: 80px;
			height: 30px !important;
			min-height: 30px !important;
			padding: 0 8px !important;
			gap: 5px;
			border-radius: var(--radius-sm) !important;
			border: 1px solid var(--line) !important;
			background: var(--surface) !important;
			box-shadow: 0 1px 2px rgba(24, 34, 29, 0.04) !important;
		}
		.command-dialog {
			width: calc(100vw - 20px);
			max-height: 75svh;
			margin-top: 14px;
			border-radius: var(--radius);
		}
		.command-header { padding: 9px 12px; }
		.command-header input { font-size: 13.5px; }
		.command-categories { padding: 6px 12px; gap: 5px; }
		.command-categories button {
			min-height: 28px !important;
			padding: 3px 10px !important;
			font-size: 11px !important;
		}
		.command-close {
			min-height: 26px !important;
			padding: 2px 8px !important;
			font-size: 11px !important;
		}
		.content-column { min-height: calc(100svh - 46px); padding-bottom: calc(72px + env(safe-area-inset-bottom)); }
		.bottom-nav {
			--dock-radius: var(--radius-lg);
			position: fixed;
			bottom: max(6px, env(safe-area-inset-bottom));
			left: 8px;
			right: 8px;
			z-index: 39;
			display: grid;
			grid-template-columns: repeat(5, minmax(0, 1fr));
			width: calc(100% - 16px);
			max-width: 520px;
			margin-inline: auto;
			gap: 3px;
			padding: 3px;
			border: 1px solid var(--line);
			border-radius: var(--dock-radius);
			background: color-mix(in srgb, var(--surface) 95%, transparent);
			backdrop-filter: blur(16px);
			box-shadow: var(--shadow-soft);
		}
		:global(:root[data-theme='dark']) .bottom-nav {
			border-color: transparent;
			box-shadow: inset 0 1px rgba(255, 255, 255, .04), 0 16px 36px -18px rgba(0, 0, 0, .9);
		}
		.bottom-nav > a, .mobile-profile-menu > summary {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			gap: 1px;
			min-width: 0;
			min-height: 46px;
			text-decoration: none;
			color: var(--muted);
			font-size: 9.5px;
			font-weight: 650;
			line-height: 1.1;
			border-radius: var(--radius-sm);
			cursor: pointer;
			list-style: none;
			box-shadow: none !important;
			background: transparent;
			background-clip: padding-box;
			transition: background-color .14s ease, color .14s ease;
		}
		.bottom-nav .nav-icon { position: relative; display: grid; place-items: center; width: 28px; height: 24px; border-radius: var(--radius-sm); transition: background-color .14s ease, color .14s ease, transform .14s ease; }
		.bottom-nav :is(a, summary):focus-visible { outline: 2px solid var(--primary); outline-offset: 2px; }
		.command-clear { min-height: 28px !important; width: 28px; }
		.bottom-nav .nav-icon :global(svg) { width: 17px; height: 17px; }
		.mobile-profile-menu > summary::-webkit-details-marker { display: none; }
		.bottom-nav .nav-label-text { display: block; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
		.bottom-nav > a:hover,
		.mobile-profile-menu > summary:hover {
			background: var(--surface-alt);
			color: var(--primary-strong);
		}
		.bottom-nav > a[aria-current],
		.mobile-profile-menu > summary[aria-current],
		.mobile-profile-menu[open] > summary {
			background: var(--primary-soft);
			color: var(--primary-strong);
			font-weight: 750;
		}
		.bottom-nav > a:active,
		.mobile-profile-menu > summary:active {
			background: var(--primary-soft);
			scale: .96;
		}
		.bottom-nav > a[aria-current] .nav-icon,
		.mobile-profile-menu > summary[aria-current] .nav-icon,
		.mobile-profile-menu[open] > summary .nav-icon {
			background: var(--primary-soft);
		}
		.bottom-nav > a.scan-destination { color: var(--primary-strong); }
		.bottom-nav > a.scan-destination .nav-icon { width: 34px; height: 32px; margin-top: -6px; background: var(--accent-solid); color: #fff; box-shadow: 0 4px 10px color-mix(in srgb, var(--accent-solid) 26%, transparent); }
		.bottom-nav > a.scan-destination[aria-current] .nav-icon { background: var(--accent-solid); color: #fff; }
		.mobile-profile-menu { position: relative; display: block; min-width: 0; }
		.mobile-profile-icon { position: relative; }
		.mobile-profile-backdrop {
			display: block;
			position: fixed;
			inset: 0;
			z-index: 45;
			background: rgba(15, 23, 18, 0.52);
			backdrop-filter: blur(24px);
			-webkit-backdrop-filter: blur(24px);
			border: 0;
			padding: 0;
			margin: 0;
			width: 100vw;
			height: 100vh;
			cursor: default;
			animation: fadeInBackdrop 160ms cubic-bezier(0.16, 1, 0.3, 1);
		}
		@keyframes fadeInBackdrop {
			from {
				opacity: 0;
				backdrop-filter: blur(0px);
				-webkit-backdrop-filter: blur(0px);
			}
			to {
				opacity: 1;
				backdrop-filter: blur(24px);
				-webkit-backdrop-filter: blur(24px);
			}
		}
		.mobile-profile-dropdown {
			position: fixed;
			inset-inline: 10px;
			inset-block-end: calc(70px + env(safe-area-inset-bottom));
			z-index: 48;
			max-width: 500px;
			margin-inline: auto;
			max-height: calc(100svh - 140px);
			overflow-y: auto;
			background: var(--surface);
			box-shadow: 0 24px 60px -16px rgba(10, 25, 15, 0.45);
			animation: slideUpSheet 180ms cubic-bezier(0.16, 1, 0.3, 1);
		}
		@keyframes slideUpSheet {
			from {
				opacity: 0;
				transform: translateY(12px) scale(0.98);
			}
			to {
				opacity: 1;
				transform: translateY(0) scale(1);
			}
		}
	}
</style>
