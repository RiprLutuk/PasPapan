<script lang="ts">
	import { visibleOperations } from '../../../lib/access';
	import { onMount } from 'svelte';
	import { request, todayAttendance, type TodayAttendance } from '../../../lib/api';
	import { realtimeState } from '../../../lib/realtime.svelte';
	import { workSession } from '../../../lib/session';
	import Icon from '../../../lib/Icon.svelte';

	type RequestItem = {
		id: string;
		request_type?: string;
		title?: string;
		status: string;
		starts_on?: string;
		created_at?: string;
		reason?: string;
	};

	type ScheduleItem = {
		date?: string;
		shift_name?: string;
		starts_at?: string;
		ends_at?: string;
	};

	const session = workSession();
	let attendance = $state<TodayAttendance | null>(null);
	let recentRequests = $state<RequestItem[]>([]);
	let upcomingSchedules = $state<ScheduleItem[]>([]);
	let unreadFromApi = $state(0);
	let loading = $state(true);
	let busy = $state(false);
	let error = $state('');
	let greeting = $state('Selamat siang');
	let moreOpen = $state(false);

	let teamLinks = $derived(visibleOperations(session.current?.capabilities));
	let checkedIn = $derived(Boolean(attendance && attendance.status !== 'not_checked_in'));
	let checkedOut = $derived(attendance?.status === 'checked_out');
	let unreadNotifications = $derived(
		realtimeState.lastPulse?.unread_notifications ?? unreadFromApi
	);
	let pendingRequests = $derived(
		recentRequests.filter(
			(item) => item.status === 'pending_manager' || item.status === 'pending_admin' || item.status === 'pending'
		).length
	);
	let priorityCount = $derived(unreadNotifications + pendingRequests + (!checkedIn ? 1 : 0));

	function greetingForCurrentHour() {
		const hour = new Date().getHours();
		return hour < 11
			? 'Selamat pagi'
			: hour < 15
				? 'Selamat siang'
				: hour < 18
					? 'Selamat sore'
					: 'Selamat malam';
	}

	function initials(name?: string) {
		return (
			(name ?? 'P')
				.split(/\s+/)
				.filter(Boolean)
				.slice(0, 2)
				.map((part) => part[0]?.toUpperCase())
				.join('') || 'P'
		);
	}

	function formatWorkDate(value?: string) {
		if (!value) {
			return new Intl.DateTimeFormat('id-ID', {
				weekday: 'long',
				day: 'numeric',
				month: 'long',
				year: 'numeric'
			}).format(new Date());
		}
		const [year, month, day] = value.split('-').map(Number);
		if (!year || !month || !day) return value;
		return new Intl.DateTimeFormat('id-ID', {
			weekday: 'long',
			day: 'numeric',
			month: 'long',
			year: 'numeric',
			timeZone: 'UTC'
		}).format(new Date(Date.UTC(year, month - 1, day)));
	}

	function formatShortDate(value?: string) {
		if (!value) return 'Baru saja';
		const raw = value.slice(0, 10);
		const [year, month, day] = raw.split('-').map(Number);
		if (!year || !month || !day) return value;
		return new Intl.DateTimeFormat('id-ID', {
			day: '2-digit',
			month: 'short',
			year: 'numeric',
			timeZone: 'UTC'
		}).format(new Date(Date.UTC(year, month - 1, day)));
	}

	function formatRequestType(type?: string, fallbackTitle?: string) {
		if (fallbackTitle) return fallbackTitle;
		switch (type) {
			case 'leave':
			case 'annual_leave':
				return 'Cuti';
			case 'sick':
				return 'Sakit';
			case 'correction':
			case 'attendance_correction':
				return 'Koreksi';
			case 'overtime':
				return 'Lembur';
			case 'claim':
			case 'reimbursement':
				return 'Klaim';
			case 'wfh':
				return 'WFH';
			default:
				return type ? type.replaceAll('_', ' ') : 'Pengajuan';
		}
	}

	function formatStatusLabel(status: string) {
		if (status === 'approved' || status === 'disetujui') return 'DISETUJUI';
		if (status === 'rejected' || status === 'ditolak') return 'DITOLAK';
		return 'MENUNGGU';
	}

	async function load() {
		loading = true;
		error = '';
		try {
			const [today, reqs, schedules, notifCount] = await Promise.all([
				todayAttendance(),
				request<RequestItem[]>('/v1/requests').catch(() => []),
				request<ScheduleItem[]>('/v1/schedule').catch(() => []),
				request<{ unread_count: number }>('/v1/notifications/unread-count').catch(() => ({
					unread_count: 0
				}))
			]);
			attendance = today;
			recentRequests = reqs.slice(0, 4);
			upcomingSchedules = schedules.slice(0, 3);
			unreadFromApi = notifCount.unread_count;
		} catch (e) {
			error = e instanceof Error ? e.message : 'Kehadiran belum dapat dimuat.';
		} finally {
			loading = false;
		}
	}

	onMount(() => {
		greeting = greetingForCurrentHour();
		void load();
	});
</script>

<svelte:head><title>Beranda · PasPapan</title></svelte:head>

<main id="main-content" class="workspace-page today-page" tabindex="-1">
	<header class="hero-greeting">
		<div class="hero-copy">
			<p class="greeting-line">{greeting},</p>
			<h1>{session.current?.user.full_name ?? 'Demo User'}</h1>
			<p class="priority-line">{priorityCount} prioritas hari ini</p>
		</div>
		<a class="hero-avatar" href="/user/profile" aria-label="Buka profil">
			{initials(session.current?.user.full_name)}
		</a>
	</header>

	{#if error}
		<p class="today-error" role="alert">
			<span>{error}</span>
			<button class="quiet" onclick={load} disabled={busy}>Coba lagi</button>
		</p>
	{/if}

	<!-- PRESENSI HARI INI -->
	<section class="panel attendance" aria-labelledby="attendance-title" aria-busy={loading}>
		<div class="attendance-top">
			<div>
				<p class="card-eyebrow">PRESENSI</p>
				<h2 id="attendance-title">Hari Ini <span class="sr-only">Presensi hari ini</span></h2>
				<p class="date-subtitle">{formatWorkDate(attendance?.date)}</p>
			</div>
			<span class="live-pill">
				<span class="live-dot"></span>
				<span>Live</span>
			</span>
		</div>

		<div class="shift-banner">
			<span class="shift-icon">
				<Icon name="calendar" size={18} />
			</span>
			<div class="shift-copy">
				<strong>{attendance?.shift_name || 'Shift Pagi'}</strong>
				<p>
					Jam kerja:
					<b>{attendance?.starts_at ?? '07:00:00'} - {attendance?.ends_at ?? '15:00:00'}</b>
					<span>· 8h</span>
				</p>
			</div>
		</div>

		<div class="check-grid">
			<div class="check-card masuk" class:complete={checkedIn}>
				<span class="check-icon masuk">
					<Icon name="login" size={16} />
				</span>
				<div class="timeline-copy">
					<small>Presensi Masuk</small>
					<b>{attendance?.check_in_time ?? '--:--'}</b>
				</div>
			</div>

			<div class="check-card keluar" class:complete={checkedOut}>
				<span class="check-icon keluar">
					<Icon name="logout" size={16} />
				</span>
				<div class="timeline-copy">
					<small>Presensi Keluar</small>
					<b>{attendance?.check_out_time ?? '--:--'}</b>
				</div>
			</div>
		</div>

		<a class="absen-cta" href="/app/scan">
			<span class="absen-cta-icon">
				<Icon name="login" size={17} />
			</span>
			<span>{checkedOut ? 'Absen Selesai' : checkedIn ? 'Absen Keluar' : 'Absen'}</span>
		</a>
	</section>

	<!-- AKSES CEPAT / PINTASAN -->
	<section class="panel shortcuts-panel" aria-labelledby="shortcuts-title">
		<p class="card-eyebrow">AKSES CEPAT</p>
		<h2 id="shortcuts-title">Pintasan</h2>

		<div class="shortcut-grid">
			<a class="shortcut-tile" href="/app/history">
				<span class="shortcut-icon tone-blue"><Icon name="clock" size={19} /></span>
				<b>Riwayat</b>
			</a>
			<a class="shortcut-tile" href="/app/requests">
				<span class="shortcut-icon tone-purple"><Icon name="tasks" size={19} /></span>
				<b>Koreksi</b>
			</a>
			<a class="shortcut-tile" href="/app/requests">
				<span class="shortcut-icon tone-teal"><Icon name="send" size={18} /></span>
				<b>Cuti</b>
			</a>
			<a class="shortcut-tile" href="/app/my-reimbursements">
				<span class="shortcut-icon tone-rose"><Icon name="banknote" size={19} /></span>
				<b>Klaim</b>
			</a>
			<a class="shortcut-tile" href="/app/my-overtimes">
				<span class="shortcut-icon tone-amber"><Icon name="clock" size={19} /></span>
				<b>Lembur</b>
			</a>
			<button
				type="button"
				class="shortcut-tile shortcut-btn"
				aria-expanded={moreOpen}
				onclick={() => (moreOpen = !moreOpen)}
			>
				<span class="shortcut-icon tone-indigo"><Icon name="more" size={19} /></span>
				<b>Lainnya</b>
			</button>
		</div>

		{#if moreOpen}
			<div class="more-drawer">
				<a href="/app/my-payslips"><Icon name="wallet" size={15} /><span>Slip Gaji</span></a>
				<a href="/app/shift-swaps"><Icon name="swap" size={15} /><span>Tukar Shift</span></a>
				<a href="/app/announcements"><Icon name="megaphone" size={15} /><span>Pengumuman</span></a>
				<a href="/app/my-warnings"><Icon name="file" size={15} /><span>Surat Peringatan</span></a>
				<a href="/app/my-assets"><Icon name="box" size={15} /><span>Aset Saya</span></a>
				<a href="/app/my-kpis"><Icon name="chart" size={15} /><span>KPI Saya</span></a>
				{#each teamLinks as link}
					<a href={link.href}><Icon name={link.icon} size={15} /><span>{link.label}</span></a>
				{/each}
			</div>
		{/if}
	</section>

	<!-- PERLU TINDAKAN -->
	<section class="panel action-panel" aria-labelledby="action-title">
		<div class="action-header">
			<div>
				<p class="card-eyebrow">PERLU TINDAKAN</p>
				<h2 id="action-title">Tindak lanjuti tanpa cari-cari menu</h2>
			</div>
			<span class="count-circle">{priorityCount}</span>
		</div>

		<p class="sub-eyebrow">TINDAK LANJUT SAYA</p>
		<div class="followup-row">
			<a class="followup-pill" href="/app/notifications">
				<span class="followup-icon"><Icon name="bell" size={16} /></span>
				<b>Notifikasi</b>
				<span class="followup-badge">{unreadNotifications}</span>
			</a>
			{#if pendingRequests > 0}
				<a class="followup-pill" href="/app/requests">
					<span class="followup-icon"><Icon name="request" size={16} /></span>
					<b>Pengajuan</b>
					<span class="followup-badge">{pendingRequests}</span>
				</a>
			{/if}
		</div>
	</section>

	<!-- AKTIVITAS TERBARU -->
	<section class="panel recent-panel" aria-labelledby="recent-title">
		<p class="card-eyebrow">AKTIVITAS TERBARU</p>
		<h2 id="recent-title">Status pengajuan terbaru</h2>

		{#if recentRequests.length > 0}
			<div class="recent-list">
				{#each recentRequests as item (item.id)}
					<a class="recent-row" href="/app/requests">
						<span
							class="recent-dot"
							class:approved={item.status === 'approved'}
							class:rejected={item.status === 'rejected'}
						></span>
						<div class="recent-copy">
							<strong>{formatRequestType(item.request_type, item.title)}</strong>
							<small>{item.reason ? `${item.reason} · ` : ''}{formatShortDate(item.starts_on ?? item.created_at)}</small>
						</div>
						<span
							class="recent-status"
							class:approved={item.status === 'approved'}
							class:rejected={item.status === 'rejected'}
						>
							{formatStatusLabel(item.status)}
						</span>
					</a>
				{/each}
			</div>
		{:else}
			<div class="recent-list">
				<a class="recent-row" href="/app/my-reimbursements">
					<span class="recent-dot approved"></span>
					<div class="recent-copy">
						<strong>Klaim</strong>
						<small>Operasional · Terverifikasi</small>
					</div>
					<span class="recent-status approved">DISETUJUI</span>
				</a>
				<a class="recent-row" href="/app/requests">
					<span class="recent-dot approved"></span>
					<div class="recent-copy">
						<strong>Koreksi</strong>
						<small>{formatShortDate(attendance?.date)}</small>
					</div>
					<span class="recent-status approved">DISETUJUI</span>
				</a>
			</div>
		{/if}
	</section>

	<!-- SEDANG BERLANGSUNG / ACARA MENDATANG -->
	<section class="ongoing-section" aria-labelledby="ongoing-title">
		<div class="ongoing-header">
			<h2 id="ongoing-title">Sedang Berlangsung</h2>
			<a href="/app/schedule">Lihat Semua</a>
		</div>

		<div class="panel upcoming-panel">
			<div class="upcoming-head">
				<span class="upcoming-icon"><Icon name="calendar" size={16} /></span>
				<span>ACARA MENDATANG</span>
			</div>

			{#if upcomingSchedules.length > 0}
				<div class="upcoming-list">
					{#each upcomingSchedules as item}
						<div class="upcoming-row">
							<strong>{item.shift_name ?? 'Shift Kerja'}</strong>
							<span>{formatShortDate(item.date)} · {item.starts_at ?? '08:00'} – {item.ends_at ?? '17:00'}</span>
						</div>
					{/each}
				</div>
			{:else}
				<div class="upcoming-empty">
					<span class="upcoming-empty-icon"><Icon name="calendar" size={20} /></span>
					<p>Tidak ada acara mendatang.</p>
				</div>
			{/if}
		</div>
	</section>
</main>

<style>
	.sr-only {
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
	.today-page {
		max-width: 640px;
		display: grid;
		gap: 14px;
		align-content: start;
	}

	/* Greeting Header */
	.hero-greeting {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 12px;
		padding: 16px 18px;
		border-radius: var(--radius-lg);
		background: linear-gradient(180deg, #e1efe0 0%, #eef6ee 100%);
		border: 1px solid #d2e4d1;
	}
	.hero-copy {
		display: grid;
		gap: 2px;
	}
	.greeting-line {
		margin: 0;
		font-size: 14px;
		font-weight: 600;
		color: #365043;
	}
	.hero-copy h1 {
		margin: 0;
		font-size: clamp(21px, 2.5vw, 25px);
		font-weight: 800;
		letter-spacing: -0.03em;
		color: #111b15;
	}
	.priority-line {
		margin: 2px 0 0 !important;
		font-size: 12.5px;
		font-weight: 600;
		color: #466153;
	}
	.hero-avatar {
		display: grid;
		place-items: center;
		width: 46px;
		height: 46px;
		flex: 0 0 46px;
		border-radius: 50%;
		background: #f9ebc8;
		border: 2px solid #ffffff;
		color: #7c3f00 !important;
		font-size: 15px;
		font-weight: 800;
		text-decoration: none;
		box-shadow: 0 4px 12px -4px rgba(24, 34, 29, 0.16);
	}

	.today-error {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 10px;
		padding: 10px 12px;
		border-radius: var(--radius);
		background: #fff3f0;
		border: 1px solid #ecc8c2;
		color: #8a2c24;
		font-size: 12.5px;
	}

	/* Shared Card Typography */
	.panel {
		padding: 16px;
		border-radius: var(--radius-lg);
		border: 1px solid #e2ebe3;
		background: #ffffff;
		box-shadow: 0 8px 22px -18px rgba(24, 34, 29, 0.18);
	}
	.card-eyebrow {
		margin: 0 0 3px !important;
		font-size: 10.5px;
		font-weight: 800;
		letter-spacing: 0.14em;
		text-transform: uppercase;
		color: var(--primary);
	}

	/* Presensi Card */
	.attendance-top {
		display: flex;
		align-items: flex-start;
		justify-content: space-between;
		gap: 12px;
		margin-bottom: 12px;
	}
	.attendance-top h2 {
		margin: 0;
		font-size: 18px;
		font-weight: 800;
		color: var(--ink);
	}
	.date-subtitle {
		margin: 2px 0 0 !important;
		font-size: 12.5px;
		color: var(--muted);
	}
	.live-pill {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		padding: 5px 11px;
		border-radius: 999em;
		background: #f2fbf9;
		border: 1px solid #9be3d8;
		color: #1f4d3d;
		font-size: 12px;
		font-weight: 750;
	}
	.live-dot {
		width: 8px;
		height: 8px;
		border-radius: 50%;
		background: #3b8740;
	}

	.shift-banner {
		display: flex;
		align-items: center;
		gap: 11px;
		padding: 10px 12px;
		border-radius: var(--radius);
		border: 1px solid #e4ece5;
		background: #fafcfa;
		margin-bottom: 12px;
	}
	.shift-icon {
		display: grid;
		place-items: center;
		width: 36px;
		height: 36px;
		flex: 0 0 36px;
		border-radius: 50%;
		background: #dfeee0;
		color: var(--primary-strong);
	}
	.shift-copy {
		display: grid;
		gap: 1px;
		min-width: 0;
	}
	.shift-copy strong {
		font-size: 14px;
		font-weight: 800;
		color: var(--ink);
	}
	.shift-copy p {
		margin: 0;
		font-size: 12px;
		color: var(--muted);
	}
	.shift-copy b {
		color: var(--ink);
		font-weight: 750;
		font-variant-numeric: tabular-nums;
	}

	.check-grid {
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 10px;
		margin-bottom: 12px;
	}
	.check-card {
		display: grid;
		gap: 8px;
		padding: 12px 13px;
		border-radius: var(--radius);
		border: 1px solid #e6ede7;
		background: #ffffff;
	}
	.check-card.masuk {
		background: #eef6ec;
		border-color: #b4d4b0;
	}
	.check-icon {
		display: grid;
		place-items: center;
		width: 30px;
		height: 30px;
		border-radius: 50%;
	}
	.check-icon.masuk {
		background: #d8ebd6;
		color: var(--primary-strong);
	}
	.check-icon.keluar {
		background: #fff0df;
		color: #d46b1f;
	}
	.timeline-copy {
		display: grid;
		gap: 4px;
	}
	.timeline-copy small {
		font-size: 12.5px;
		font-weight: 700;
		color: var(--ink);
	}
	.check-card.keluar .timeline-copy small {
		color: #46544b;
	}
	.timeline-copy b {
		font-size: 20px;
		font-weight: 800;
		letter-spacing: 0.03em;
		color: var(--ink);
		font-variant-numeric: tabular-nums;
	}

	.absen-cta {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 9px;
		width: 100%;
		min-height: 46px;
		padding: 10px 16px;
		border-radius: var(--radius);
		background: #426f37;
		color: #ffffff !important;
		font-size: 14.5px;
		font-weight: 800;
		text-decoration: none;
		box-shadow: 0 8px 18px -10px rgba(39, 76, 32, 0.6);
		transition: background-color 140ms ease, transform 140ms ease;
	}
	.absen-cta:hover {
		background: var(--primary-strong);
	}
	.absen-cta:active {
		transform: scale(0.985);
	}
	.absen-cta-icon {
		display: grid;
		place-items: center;
		width: 30px;
		height: 30px;
		border-radius: 50%;
		background: rgba(255, 255, 255, 0.18);
	}

	/* Shortcuts Card */
	.shortcuts-panel h2,
	.action-panel h2,
	.recent-panel h2 {
		margin: 0 0 12px;
		font-size: 16.5px;
		font-weight: 800;
		color: var(--ink);
	}
	.shortcut-grid {
		display: grid;
		grid-template-columns: repeat(3, minmax(0, 1fr));
		gap: 10px;
	}
	.shortcut-tile {
		display: grid;
		justify-items: center;
		gap: 7px;
		padding: 13px 8px;
		border-radius: var(--radius);
		border: 1px solid #e6eee7;
		background: #ffffff;
		color: var(--ink) !important;
		text-decoration: none;
		transition: border-color 140ms ease, background-color 140ms ease, transform 120ms ease;
	}
	.shortcut-btn {
		min-height: 0;
		box-shadow: none;
	}
	.shortcut-tile:hover {
		border-color: #c5d9c6;
		background: #f8fbf8;
	}
	.shortcut-icon {
		display: grid;
		place-items: center;
		width: 38px;
		height: 38px;
		border-radius: 50%;
	}
	.tone-blue {
		background: #dff0fa;
		color: #156594;
	}
	.tone-purple {
		background: #efe5ff;
		color: #6f2dbd;
	}
	.tone-teal {
		background: #dcf7f0;
		color: #0e7c61;
	}
	.tone-rose {
		background: #ffe4e6;
		color: #be123c;
	}
	.tone-amber {
		background: #fef3c7;
		color: #b45309;
	}
	.tone-indigo {
		background: #e0ebff;
		color: #1d4ed8;
	}
	.shortcut-tile b {
		font-size: 12.5px;
		font-weight: 750;
		color: var(--ink);
	}

	.more-drawer {
		margin-top: 10px;
		padding-top: 10px;
		border-top: 1px solid var(--line);
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 6px;
	}
	.more-drawer a {
		display: flex;
		align-items: center;
		gap: 8px;
		padding: 8px 10px;
		border-radius: var(--radius-sm);
		background: var(--surface-alt);
		color: var(--ink);
		font-size: 12px;
		font-weight: 700;
		text-decoration: none;
	}
	.more-drawer a:hover {
		background: var(--primary-soft);
		color: var(--primary-strong);
	}

	/* Perlu Tindakan Card */
	.action-header {
		display: flex;
		align-items: flex-start;
		justify-content: space-between;
		gap: 12px;
	}
	.count-circle {
		display: grid;
		place-items: center;
		width: 36px;
		height: 36px;
		flex: 0 0 36px;
		border-radius: 50%;
		background: #dfeee0;
		border: 1px solid #c3dec4;
		color: #203923;
		font-size: 14px;
		font-weight: 800;
	}
	.sub-eyebrow {
		margin: 2px 0 8px !important;
		font-size: 10px;
		font-weight: 800;
		letter-spacing: 0.12em;
		text-transform: uppercase;
		color: #7c8d9e;
	}
	.followup-row {
		display: flex;
		flex-wrap: wrap;
		gap: 8px;
	}
	.followup-pill {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		padding: 6px 10px 6px 7px;
		border-radius: 999em;
		border: 1px solid #c7e2c8;
		background: #f9fcf9;
		color: var(--ink) !important;
		text-decoration: none;
		font-size: 13px;
	}
	.followup-icon {
		display: grid;
		place-items: center;
		width: 28px;
		height: 28px;
		border-radius: 50%;
		background: #dfeee0;
		color: #233d26;
	}
	.followup-badge {
		display: grid;
		place-items: center;
		min-width: 22px;
		height: 22px;
		padding-inline: 6px;
		border-radius: 999em;
		background: #dfeee0;
		color: #233d26;
		font-size: 11.5px;
		font-weight: 800;
	}

	/* Aktivitas Terbaru Card */
	.recent-list {
		border-radius: var(--radius);
		border: 1px solid #e6eee7;
		overflow: hidden;
	}
	.recent-row {
		display: flex;
		align-items: center;
		gap: 10px;
		padding: 11px 13px;
		background: #ffffff;
		color: var(--ink) !important;
		text-decoration: none;
	}
	.recent-row + .recent-row {
		border-top: 1px solid #edf2ee;
	}
	.recent-row:hover {
		background: #f9fbf9;
	}
	.recent-dot {
		width: 9px;
		height: 9px;
		flex: 0 0 9px;
		border-radius: 50%;
		background: #d97706;
	}
	.recent-dot.approved {
		background: #10b981;
	}
	.recent-dot.rejected {
		background: #ef4444;
	}
	.recent-copy {
		display: grid;
		gap: 1px;
		min-width: 0;
		flex: 1;
	}
	.recent-copy strong {
		font-size: 13.5px;
		font-weight: 800;
		color: var(--ink);
		text-transform: capitalize;
	}
	.recent-copy small {
		font-size: 12px;
		color: #64748b;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}
	.recent-status {
		padding: 3px 9px;
		border-radius: 999em;
		border: 1px solid #fcd34d;
		background: #fffbeb;
		color: #92400e;
		font-size: 10.5px;
		font-weight: 800;
		letter-spacing: 0.04em;
	}
	.recent-status.approved {
		border-color: #a7f3d0;
		background: #ecfdf5;
		color: #065f46;
	}
	.recent-status.rejected {
		border-color: #fecaca;
		background: #fef2f2;
		color: #991b1b;
	}

	/* Sedang Berlangsung / Acara Mendatang */
	.ongoing-section {
		display: grid;
		gap: 8px;
	}
	.ongoing-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 10px;
		padding-inline: 2px;
	}
	.ongoing-header h2 {
		margin: 0;
		font-size: 16px;
		font-weight: 800;
		color: var(--ink);
	}
	.ongoing-header a {
		font-size: 13px;
		font-weight: 700;
		color: var(--primary);
		text-decoration: none;
	}
	.upcoming-panel {
		padding: 0;
		overflow: hidden;
	}
	.upcoming-head {
		display: flex;
		align-items: center;
		gap: 9px;
		padding: 11px 14px;
		border-bottom: 1px solid #edf2ee;
		font-size: 11px;
		font-weight: 800;
		letter-spacing: 0.12em;
		color: #475569;
	}
	.upcoming-icon {
		display: grid;
		place-items: center;
		width: 28px;
		height: 28px;
		border-radius: 50%;
		background: #dfeee0;
		color: var(--primary-strong);
	}
	.upcoming-list {
		display: grid;
	}
	.upcoming-row {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 10px;
		padding: 10px 14px;
		font-size: 12.5px;
	}
	.upcoming-row + .upcoming-row {
		border-top: 1px solid #edf2ee;
	}
	.upcoming-row span {
		color: var(--muted);
		font-size: 12px;
	}
	.upcoming-empty {
		display: grid;
		justify-items: center;
		gap: 8px;
		padding: 24px 16px;
		text-align: center;
	}
	.upcoming-empty-icon {
		display: grid;
		place-items: center;
		width: 40px;
		height: 40px;
		border-radius: 50%;
		background: #f4f7f5;
		color: #94a3b8;
	}
	.upcoming-empty p {
		margin: 0;
		font-size: 12.5px;
		color: #94a3b8;
	}
</style>
