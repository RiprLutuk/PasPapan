<script lang="ts">
	import { visibleOperations } from '../../../lib/access';
	import { onMount } from 'svelte';
	import { request, todayAttendance, type TodayAttendance } from '../../../lib/api';
	import { formatClock, notificationCount } from '../../../lib/home';
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
		notificationCount(realtimeState.lastPulse?.unread_notifications) ??
		notificationCount(unreadFromApi) ?? 0
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
			unreadFromApi = notifCount.unread_count ?? 0;
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
	<!-- HEADER PROFIL & SAPAAN -->
	<header class="hero-greeting">
		<div class="hero-copy">
			<div class="hero-meta">
				<span class="greeting-line">{greeting},</span>
				{#if session.current?.company.role}
					<span class="role-badge">{session.current.company.role}</span>
				{/if}
			</div>
			<h1 class="user-name">{session.current?.user.full_name ?? 'Karyawan'}</h1>
			<div class="priority-chip" class:all-done={priorityCount === 0}>
				<span class="priority-dot" class:pulse={priorityCount > 0}></span>
				<p class="priority-line">{priorityCount} prioritas hari ini</p>
			</div>
		</div>
		<a class="hero-avatar" href="/user/profile" aria-label="Buka profil">
			<span class="avatar-text">{initials(session.current?.user.full_name)}</span>
		</a>
	</header>

	{#if error}
		<div class="today-error" role="alert">
			<span class="error-text">{error}</span>
			<button class="quiet retry-btn" onclick={load} disabled={busy}>Coba lagi</button>
		</div>
	{/if}

	<!-- PRESENSI HARI INI -->
	<section class="panel attendance" aria-labelledby="attendance-title" aria-busy={loading}>
		<div class="attendance-top">
			<div class="attendance-title-group">
				<p class="card-eyebrow">PRESENSI</p>
				<h2 id="attendance-title">Hari Ini <span class="sr-only">Presensi hari ini</span></h2>
				<p class="date-subtitle">{formatWorkDate(attendance?.date)}</p>
			</div>
			<div class="live-pill" aria-label="Status live stream">
				<span class="live-dot"></span>
				<span class="live-text">Live</span>
			</div>
		</div>

		<!-- SHIFT INFO CHIP -->
		<div class="shift-banner">
			<span class="shift-icon">
				<Icon name="calendar" size={17} />
			</span>
			<div class="shift-copy">
				<div class="shift-head">
					<strong class="shift-name">{attendance?.shift_name || 'Shift Pagi'}</strong>
					<span class="shift-duration">8 jam</span>
				</div>
				<p class="shift-hours">
					Jam kerja: <b>{formatClock(attendance?.starts_at) !== '—' ? formatClock(attendance?.starts_at) : '07:00'} – {formatClock(attendance?.ends_at) !== '—' ? formatClock(attendance?.ends_at) : '15:00'}</b>
				</p>
			</div>
		</div>

		<!-- DUAL CHECK-IN / CHECK-OUT CARDS -->
		<div class="check-grid">
			<div class="check-card masuk" class:is-done={checkedIn}>
				<div class="check-card-header">
					<span class="check-icon masuk">
						<Icon name="login" size={15} />
					</span>
					<span class="check-state-badge" class:done={checkedIn}>
						{checkedIn ? 'Tercatat' : 'Belum'}
					</span>
				</div>
				<div class="check-metrics">
					<b class="time-display">{attendance?.check_in_time ? formatClock(attendance.check_in_time) : '--:--'}</b>
					<span class="timeline-copy">
						<small>Presensi Masuk</small>
					</span>
				</div>
			</div>

			<div class="check-card keluar" class:is-done={checkedOut} class:is-active={checkedIn && !checkedOut}>
				<div class="check-card-header">
					<span class="check-icon keluar">
						<Icon name="logout" size={15} />
					</span>
					<span class="check-state-badge" class:done={checkedOut} class:active={checkedIn && !checkedOut}>
						{checkedOut ? 'Selesai' : checkedIn ? 'Menunggu' : 'Belum'}
					</span>
				</div>
				<div class="check-metrics">
					<b class="time-display">{attendance?.check_out_time ? formatClock(attendance.check_out_time) : '--:--'}</b>
					<span class="timeline-copy">
						<small>Presensi Keluar</small>
					</span>
				</div>
			</div>
		</div>

		<!-- CTA ACTION BUTTON -->
		<a class="absen-cta" class:is-complete={checkedOut} href="/app/scan">
			<span class="absen-cta-icon">
				<Icon name="qr" size={18} />
			</span>
			<span class="absen-cta-label">
				{checkedOut ? 'Presensi Selesai' : checkedIn ? 'Absen Keluar via QR' : 'Absen Masuk via QR'}
			</span>
		</a>
	</section>

	<!-- AKSES CEPAT / PINTASAN -->
	<section class="panel shortcuts-panel" aria-labelledby="shortcuts-title">
		<div class="section-title-wrap">
			<p class="card-eyebrow">AKSES CEPAT</p>
			<h2 id="shortcuts-title">Pintasan Layanan</h2>
		</div>

		<div class="shortcut-grid">
			<a class="shortcut-tile" href="/app/history">
				<span class="shortcut-icon tone-blue"><Icon name="clock" size={20} /></span>
				<b class="shortcut-label">Riwayat</b>
			</a>
			<a class="shortcut-tile" href="/app/requests">
				<span class="shortcut-icon tone-purple"><Icon name="tasks" size={20} /></span>
				<b class="shortcut-label">Koreksi</b>
			</a>
			<a class="shortcut-tile" href="/app/requests">
				<span class="shortcut-icon tone-emerald"><Icon name="send" size={19} /></span>
				<b class="shortcut-label">Cuti</b>
			</a>
			<a class="shortcut-tile" href="/app/my-reimbursements">
				<span class="shortcut-icon tone-amber"><Icon name="banknote" size={20} /></span>
				<b class="shortcut-label">Klaim</b>
			</a>
			<a class="shortcut-tile" href="/app/my-overtimes">
				<span class="shortcut-icon tone-sky"><Icon name="clock" size={20} /></span>
				<b class="shortcut-label">Lembur</b>
			</a>
			<button
				type="button"
				class="shortcut-tile shortcut-btn"
				class:active={moreOpen}
				aria-expanded={moreOpen}
				onclick={() => (moreOpen = !moreOpen)}
			>
				<span class="shortcut-icon tone-neutral"><Icon name="more" size={20} /></span>
				<b class="shortcut-label">{moreOpen ? 'Tutup' : 'Lainnya'}</b>
			</button>
		</div>

		{#if moreOpen}
			<div class="more-drawer">
				<a href="/app/my-payslips" class="more-item"><Icon name="wallet" size={16} /><span>Slip Gaji</span></a>
				<a href="/app/shift-swaps" class="more-item"><Icon name="swap" size={16} /><span>Tukar Shift</span></a>
				<a href="/app/announcements" class="more-item"><Icon name="megaphone" size={16} /><span>Pengumuman</span></a>
				<a href="/app/my-warnings" class="more-item"><Icon name="file" size={16} /><span>Surat HR</span></a>
				<a href="/app/my-assets" class="more-item"><Icon name="box" size={16} /><span>Inventaris</span></a>
				<a href="/app/my-kpis" class="more-item"><Icon name="chart" size={16} /><span>Capaian KPI</span></a>
				{#each teamLinks as link}
					<a href={link.href} class="more-item team-link"><Icon name={link.icon} size={16} /><span>{link.label}</span></a>
				{/each}
			</div>
		{/if}
	</section>

	<!-- PERLU TINDAKAN -->
	{#if priorityCount > 0}
		<section class="panel action-panel" aria-labelledby="action-title">
			<div class="action-header">
				<div>
					<p class="card-eyebrow">PERLU TINDAKAN</p>
					<h2 id="action-title">Agenda belum selesai</h2>
				</div>
				<span class="count-badge">{priorityCount}</span>
			</div>

			<div class="followup-row">
				<a class="followup-pill" href="/app/notifications">
					<span class="followup-icon"><Icon name="bell" size={15} /></span>
					<span class="followup-label">Notifikasi</span>
					<span class="followup-count">{unreadNotifications}</span>
				</a>
				{#if pendingRequests > 0}
					<a class="followup-pill" href="/app/requests">
						<span class="followup-icon"><Icon name="request" size={15} /></span>
						<span class="followup-label">Pengajuan Pending</span>
						<span class="followup-count">{pendingRequests}</span>
					</a>
				{/if}
				{#if !checkedIn}
					<a class="followup-pill urgent" href="/app/scan">
						<span class="followup-icon"><Icon name="login" size={15} /></span>
						<span class="followup-label">Belum Presensi Masuk</span>
					</a>
				{/if}
			</div>
		</section>
	{/if}

	<!-- AKTIVITAS TERBARU -->
	<section class="panel recent-panel" aria-labelledby="recent-title">
		<div class="section-title-wrap">
			<p class="card-eyebrow">AKTIVITAS TERBARU</p>
			<h2 id="recent-title">Status Pengajuan</h2>
		</div>

		{#if recentRequests.length > 0}
			<div class="recent-list">
				{#each recentRequests as item (item.id)}
					<a class="recent-row" href="/app/requests">
						<span
							class="recent-dot"
							class:approved={item.status === 'approved' || item.status === 'disetujui'}
							class:rejected={item.status === 'rejected' || item.status === 'ditolak'}
						></span>
						<div class="recent-copy">
							<strong>{formatRequestType(item.request_type, item.title)}</strong>
							<small>{item.reason ? `${item.reason} · ` : ''}{formatShortDate(item.starts_on ?? item.created_at)}</small>
						</div>
						<span
							class="recent-status"
							class:approved={item.status === 'approved' || item.status === 'disetujui'}
							class:rejected={item.status === 'rejected' || item.status === 'ditolak'}
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
						<strong>Klaim Operasional</strong>
						<small>Terverifikasi & diproses</small>
					</div>
					<span class="recent-status approved">DISETUJUI</span>
				</a>
				<a class="recent-row" href="/app/requests">
					<span class="recent-dot approved"></span>
					<div class="recent-copy">
						<strong>Koreksi Presensi</strong>
						<small>{formatShortDate(attendance?.date)}</small>
					</div>
					<span class="recent-status approved">DISETUJUI</span>
				</a>
			</div>
		{/if}
	</section>

	<!-- ACARA MENDATANG -->
	<section class="ongoing-section" aria-labelledby="ongoing-title">
		<div class="ongoing-header">
			<h2 id="ongoing-title">Jadwal Shift Berikutnya</h2>
			<a class="view-all-link" href="/app/schedule">Lihat Kalender</a>
		</div>

		<div class="panel upcoming-panel">
			{#if upcomingSchedules.length > 0}
				<div class="upcoming-list">
					{#each upcomingSchedules as item}
						<div class="upcoming-row">
							<div class="upcoming-copy">
								<strong>{item.shift_name ?? 'Shift Kerja'}</strong>
								<span class="upcoming-time">{formatShortDate(item.date)}</span>
							</div>
							<span class="upcoming-hours">
								{formatClock(item.starts_at) !== '—' ? formatClock(item.starts_at) : '08:00'} – {formatClock(item.ends_at) !== '—' ? formatClock(item.ends_at) : '17:00'}
							</span>
						</div>
					{/each}
				</div>
			{:else}
				<div class="upcoming-empty">
					<span class="upcoming-empty-icon"><Icon name="calendar" size={20} /></span>
					<p>Belum ada jadwal shift tambahan terdaftar.</p>
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
		max-width: 600px;
		margin-inline: auto;
		display: grid;
		gap: 16px;
		align-content: start;
	}

	/* Shared Card Surface */
	.panel {
		padding: 18px;
		border-radius: var(--radius-lg);
		border: 1px solid var(--line);
		background: var(--surface);
		box-shadow: 0 4px 18px -12px rgba(24, 34, 29, 0.08);
	}

	.card-eyebrow {
		margin: 0 0 4px !important;
		font-size: 10.5px;
		font-weight: 800;
		letter-spacing: 0.12em;
		text-transform: uppercase;
		color: var(--primary);
	}

	.section-title-wrap h2 {
		margin: 0 0 14px;
		font-size: 16.5px;
		font-weight: 800;
		color: var(--ink);
		letter-spacing: -0.01em;
	}

	/* Header Profil & Sapaan */
	.hero-greeting {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 16px;
		padding: 18px 20px;
		border-radius: var(--radius-lg);
		background: var(--surface);
		border: 1px solid var(--line);
		box-shadow: 0 4px 16px -10px rgba(24, 34, 29, 0.06);
	}
	.hero-copy {
		display: grid;
		gap: 4px;
		min-width: 0;
	}
	.hero-meta {
		display: flex;
		align-items: center;
		gap: 8px;
	}
	.greeting-line {
		margin: 0;
		font-size: 13px;
		font-weight: 600;
		color: var(--muted);
	}
	.role-badge {
		display: inline-block;
		padding: 2px 7px;
		border-radius: 999em;
		font-size: 10.5px;
		font-weight: 750;
		letter-spacing: 0.02em;
		text-transform: uppercase;
		background: var(--surface-alt);
		color: var(--primary-strong);
		border: 1px solid var(--line);
	}
	.user-name {
		margin: 0;
		font-size: clamp(20px, 3vw, 24px);
		font-weight: 800;
		letter-spacing: -0.03em;
		color: var(--ink);
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}
	.priority-chip {
		display: inline-flex;
		align-items: center;
		gap: 7px;
		margin-top: 2px;
	}
	.priority-dot {
		width: 7px;
		height: 7px;
		border-radius: 50%;
		background: #d97706;
	}
	.priority-dot.pulse {
		background: #059669;
		box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.2);
	}
	.priority-chip.all-done .priority-dot {
		background: #059669;
	}
	.priority-line {
		margin: 0 !important;
		font-size: 12px;
		font-weight: 650;
		color: var(--muted);
	}

	.hero-avatar {
		display: grid;
		place-items: center;
		width: 48px;
		height: 48px;
		flex: 0 0 48px;
		border-radius: 50%;
		background: linear-gradient(135deg, #eaf3e9 0%, #d5e8d3 100%);
		border: 2px solid var(--surface);
		box-shadow: 0 4px 14px -4px rgba(39, 76, 32, 0.22);
		text-decoration: none;
		transition: transform 140ms ease;
	}
	.hero-avatar:hover {
		transform: scale(1.04);
	}
	.avatar-text {
		font-size: 16px;
		font-weight: 800;
		color: var(--primary-strong);
		letter-spacing: -0.02em;
	}

	.today-error {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 12px;
		padding: 11px 14px;
		border-radius: var(--radius);
		background: #fff5f5;
		border: 1px solid #fed7d7;
		color: #991b1b;
		font-size: 13px;
	}
	.error-text {
		flex: 1;
		font-weight: 600;
	}
	.retry-btn {
		min-height: 32px !important;
		padding: 4px 10px !important;
		font-size: 12px !important;
	}

	/* Card Presensi */
	.attendance-top {
		display: flex;
		align-items: flex-start;
		justify-content: space-between;
		gap: 12px;
		margin-bottom: 14px;
	}
	.attendance-title-group h2 {
		margin: 0;
		font-size: 18px;
		font-weight: 800;
		color: var(--ink);
		letter-spacing: -0.02em;
	}
	.date-subtitle {
		margin: 2px 0 0 !important;
		font-size: 12.5px;
		font-weight: 550;
		color: var(--muted);
	}
	.live-pill {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		padding: 5px 10px;
		border-radius: 999em;
		background: #ecfdf5;
		border: 1px solid #a7f3d0;
		color: #065f46;
		font-size: 11.5px;
		font-weight: 750;
	}
	.live-dot {
		width: 7px;
		height: 7px;
		border-radius: 50%;
		background: #059669;
		animation: live-glow 2s infinite ease-in-out;
	}
	@keyframes live-glow {
		0%, 100% { opacity: 1; transform: scale(1); }
		50% { opacity: 0.5; transform: scale(1.3); }
	}

	/* Shift Info */
	.shift-banner {
		display: flex;
		align-items: center;
		gap: 12px;
		padding: 11px 14px;
		border-radius: var(--radius);
		border: 1px solid var(--line);
		background: var(--surface-alt);
		margin-bottom: 14px;
	}
	.shift-icon {
		display: grid;
		place-items: center;
		width: 36px;
		height: 36px;
		flex: 0 0 36px;
		border-radius: var(--radius-sm);
		background: var(--surface);
		border: 1px solid var(--line);
		color: var(--primary);
	}
	.shift-copy {
		display: grid;
		gap: 2px;
		min-width: 0;
		flex: 1;
	}
	.shift-head {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 8px;
	}
	.shift-name {
		font-size: 13.5px;
		font-weight: 800;
		color: var(--ink);
	}
	.shift-duration {
		font-size: 11px;
		font-weight: 700;
		color: var(--muted);
		background: var(--surface);
		padding: 1px 7px;
		border-radius: 999em;
		border: 1px solid var(--line);
	}
	.shift-hours {
		margin: 0;
		font-size: 12px;
		color: var(--muted);
	}
	.shift-hours b {
		color: var(--ink);
		font-weight: 750;
	}

	/* Dual Check-in / Out Grid */
	.check-grid {
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 12px;
		margin-bottom: 14px;
	}
	.check-card {
		display: grid;
		gap: 10px;
		padding: 14px;
		border-radius: var(--radius);
		border: 1px solid var(--line);
		background: var(--surface);
		transition: border-color 140ms ease, box-shadow 140ms ease;
	}
	.check-card.masuk.is-done {
		border-color: #bbf7d0;
		background: #f7fdf9;
	}
	.check-card.keluar.is-done {
		border-color: #bbf7d0;
		background: #f7fdf9;
	}
	.check-card.keluar.is-active {
		border-color: #fed7aa;
		background: #fffdf9;
	}
	.check-card-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 6px;
	}
	.check-icon {
		display: grid;
		place-items: center;
		width: 28px;
		height: 28px;
		border-radius: var(--radius-sm);
	}
	.check-icon.masuk {
		background: #dcfce7;
		color: #15803d;
	}
	.check-icon.keluar {
		background: #ffedd5;
		color: #c2410c;
	}
	.check-state-badge {
		font-size: 10.5px;
		font-weight: 750;
		padding: 2px 7px;
		border-radius: 999em;
		background: var(--surface-alt);
		color: var(--muted);
		border: 1px solid var(--line);
	}
	.check-state-badge.done {
		background: #dcfce7;
		color: #166534;
		border-color: #86efac;
	}
	.check-state-badge.active {
		background: #fef3c7;
		color: #92400e;
		border-color: #fde68a;
	}
	.check-metrics {
		display: grid;
		gap: 3px;
	}
	.time-display {
		font-size: 24px;
		font-weight: 850;
		letter-spacing: -0.02em;
		color: var(--ink);
		font-variant-numeric: tabular-nums;
		line-height: 1.1;
	}
	.timeline-copy small {
		font-size: 11.5px;
		font-weight: 650;
		color: var(--muted);
	}

	/* Primary Absen CTA */
	.absen-cta {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 10px;
		width: 100%;
		min-height: 48px;
		padding: 12px 20px;
		border-radius: var(--radius);
		background: linear-gradient(135deg, #2b6127 0%, #1e451b 100%);
		color: #ffffff !important;
		font-size: 14.5px;
		font-weight: 800;
		text-decoration: none;
		letter-spacing: 0.01em;
		box-shadow: 0 6px 18px -6px rgba(30, 69, 27, 0.45);
		transition: filter 140ms ease, transform 140ms ease;
	}
	.absen-cta:hover {
		filter: brightness(1.08);
	}
	.absen-cta:active {
		transform: scale(0.985);
	}
	.absen-cta.is-complete {
		background: #3f5545;
		box-shadow: none;
	}
	.absen-cta-icon {
		display: grid;
		place-items: center;
		width: 26px;
		height: 26px;
		border-radius: var(--radius-sm);
		background: rgba(255, 255, 255, 0.18);
	}

	/* Shortcuts Grid */
	.shortcut-grid {
		display: grid;
		grid-template-columns: repeat(3, minmax(0, 1fr));
		gap: 10px;
	}
	.shortcut-tile {
		display: grid;
		justify-items: center;
		gap: 8px;
		padding: 14px 10px;
		border-radius: var(--radius);
		border: 1px solid var(--line);
		background: var(--surface);
		color: var(--ink) !important;
		text-decoration: none;
		box-shadow: 0 1px 3px rgba(24, 34, 29, 0.03);
		transition: border-color 140ms ease, background-color 140ms ease, transform 120ms ease;
	}
	.shortcut-btn {
		min-height: 0;
		box-shadow: 0 1px 3px rgba(24, 34, 29, 0.03);
	}
	.shortcut-tile:hover {
		border-color: #b7ceb8;
		background: var(--surface-alt);
	}
	.shortcut-tile:active {
		transform: scale(0.975);
	}
	.shortcut-icon {
		display: grid;
		place-items: center;
		width: 42px;
		height: 42px;
		border-radius: var(--radius-sm);
		transition: transform 140ms ease;
	}
	.shortcut-tile:hover .shortcut-icon {
		transform: translateY(-1px);
	}
	.tone-blue {
		background: #eff6ff;
		color: #1d4ed8;
	}
	.tone-purple {
		background: #f5f3ff;
		color: #6d28d9;
	}
	.tone-emerald {
		background: #ecfdf5;
		color: #047857;
	}
	.tone-amber {
		background: #fffbeb;
		color: #b45309;
	}
	.tone-sky {
		background: #f0f9ff;
		color: #0369a1;
	}
	.tone-neutral {
		background: #f1f5f9;
		color: #334155;
	}
	.shortcut-label {
		font-size: 12.5px;
		font-weight: 750;
		color: var(--ink);
	}

	.more-drawer {
		margin-top: 14px;
		padding-top: 14px;
		border-top: 1px solid var(--line);
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 8px;
	}
	.more-item {
		display: flex;
		align-items: center;
		gap: 9px;
		padding: 9px 12px;
		border-radius: var(--radius-sm);
		background: var(--surface-alt);
		border: 1px solid transparent;
		color: var(--ink) !important;
		font-size: 12px;
		font-weight: 700;
		text-decoration: none;
		transition: border-color 140ms ease, background-color 140ms ease;
	}
	.more-item:hover {
		background: var(--primary-soft);
		border-color: #b9d8b7;
		color: var(--primary-strong) !important;
	}

	/* Action Needed Card */
	.action-header {
		display: flex;
		align-items: flex-start;
		justify-content: space-between;
		gap: 12px;
		margin-bottom: 12px;
	}
	.action-header h2 {
		margin: 0;
		font-size: 16px;
		font-weight: 800;
		color: var(--ink);
	}
	.count-badge {
		display: grid;
		place-items: center;
		min-width: 26px;
		height: 26px;
		padding-inline: 7px;
		border-radius: 999em;
		background: #fef3c7;
		border: 1px solid #fde68a;
		color: #92400e;
		font-size: 12px;
		font-weight: 850;
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
		padding: 7px 12px 7px 8px;
		border-radius: 999em;
		border: 1px solid var(--line);
		background: var(--surface-alt);
		color: var(--ink) !important;
		text-decoration: none;
		font-size: 12.5px;
		font-weight: 650;
		transition: background-color 140ms ease, border-color 140ms ease;
	}
	.followup-pill:hover {
		background: var(--surface);
		border-color: #b8ccb9;
	}
	.followup-pill.urgent {
		background: #fff7ed;
		border-color: #fed7aa;
		color: #9a3412 !important;
	}
	.followup-icon {
		display: grid;
		place-items: center;
		width: 26px;
		height: 26px;
		border-radius: 50%;
		background: var(--surface);
		color: var(--primary);
	}
	.followup-pill.urgent .followup-icon {
		background: #ffedd5;
		color: #c2410c;
	}
	.followup-count {
		display: grid;
		place-items: center;
		min-width: 20px;
		height: 20px;
		padding-inline: 5px;
		border-radius: 999em;
		background: var(--primary-soft);
		color: var(--primary-strong);
		font-size: 11px;
		font-weight: 800;
	}

	/* Recent Activity List */
	.recent-list {
		border-radius: var(--radius);
		border: 1px solid var(--line);
		overflow: hidden;
	}
	.recent-row {
		display: flex;
		align-items: center;
		gap: 12px;
		padding: 12px 14px;
		background: var(--surface);
		color: var(--ink) !important;
		text-decoration: none;
		transition: background-color 120ms ease;
	}
	.recent-row + .recent-row {
		border-top: 1px solid var(--line);
	}
	.recent-row:hover {
		background: var(--surface-alt);
	}
	.recent-dot {
		width: 8px;
		height: 8px;
		flex: 0 0 8px;
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
		gap: 2px;
		min-width: 0;
		flex: 1;
	}
	.recent-copy strong {
		font-size: 13.5px;
		font-weight: 750;
		color: var(--ink);
	}
	.recent-copy small {
		font-size: 11.5px;
		color: var(--muted);
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}
	.recent-status {
		padding: 3px 9px;
		border-radius: 999em;
		border: 1px solid #fde68a;
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

	/* Upcoming Section */
	.ongoing-section {
		display: grid;
		gap: 10px;
	}
	.ongoing-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 10px;
		padding-inline: 4px;
	}
	.ongoing-header h2 {
		margin: 0;
		font-size: 16px;
		font-weight: 800;
		color: var(--ink);
	}
	.view-all-link {
		font-size: 12.5px;
		font-weight: 700;
		color: var(--primary);
		text-decoration: none;
	}
	.upcoming-panel {
		padding: 0;
		overflow: hidden;
	}
	.upcoming-list {
		display: grid;
	}
	.upcoming-row {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 12px;
		padding: 12px 16px;
	}
	.upcoming-row + .upcoming-row {
		border-top: 1px solid var(--line);
	}
	.upcoming-copy {
		display: grid;
		gap: 2px;
	}
	.upcoming-copy strong {
		font-size: 13.5px;
		font-weight: 750;
		color: var(--ink);
	}
	.upcoming-time {
		font-size: 11.5px;
		color: var(--muted);
	}
	.upcoming-hours {
		font-size: 12.5px;
		font-weight: 700;
		color: var(--ink);
		font-variant-numeric: tabular-nums;
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
		width: 38px;
		height: 38px;
		border-radius: 50%;
		background: var(--surface-alt);
		color: var(--muted);
	}
	.upcoming-empty p {
		margin: 0;
		font-size: 12.5px;
		color: var(--muted);
	}

	@media (max-width: 480px) {
		.today-page {
			gap: 12px;
		}
		.hero-greeting {
			padding: 14px 16px;
		}
		.panel {
			padding: 14px;
		}
		.time-display {
			font-size: 21px;
		}
		.shortcut-tile {
			padding: 11px 6px;
		}
		.shortcut-icon {
			width: 36px;
			height: 36px;
		}
	}
</style>
