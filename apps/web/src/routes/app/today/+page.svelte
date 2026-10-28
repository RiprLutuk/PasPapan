<script lang="ts">
	import { visibleOperations } from '../../../lib/access';
	import { onMount } from 'svelte';
	import { request, todayAttendance, type TodayAttendance } from '../../../lib/api';
	import { formatClock, notificationCount } from '../../../lib/home';
	import { realtimeState } from '../../../lib/realtime.svelte';
	import { workSession } from '../../../lib/session';
	import Icon from '../../../lib/Icon.svelte';
	import { m } from '../../../lib/paraglide/messages.js';
	import { getLocale } from '../../../lib/paraglide/runtime.js';

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
	let greeting = $state(m.home_greeting_afternoon());
	let servicesOpen = $state(false);
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
			? m.home_greeting_morning()
			: hour < 15
				? m.home_greeting_afternoon()
				: hour < 18
					? m.home_greeting_evening()
					: m.home_greeting_night();
	}

	function dateLocale() {
		return getLocale() === 'en' ? 'en-US' : 'id-ID';
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
			return new Intl.DateTimeFormat(dateLocale(), {
				weekday: 'long',
				day: 'numeric',
				month: 'long',
				year: 'numeric'
			}).format(new Date());
		}
		const [year, month, day] = value.split('-').map(Number);
		if (!year || !month || !day) return value;
		return new Intl.DateTimeFormat(dateLocale(), {
			weekday: 'long',
			day: 'numeric',
			month: 'long',
			year: 'numeric',
			timeZone: 'UTC'
		}).format(new Date(Date.UTC(year, month - 1, day)));
	}

	function formatShortDate(value?: string) {
		if (!value) return m.just_now();
		const raw = value.slice(0, 10);
		const [year, month, day] = raw.split('-').map(Number);
		if (!year || !month || !day) return value;
		return new Intl.DateTimeFormat(dateLocale(), {
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
				return m.leave();
			case 'sick':
				return m.sick();
			case 'correction':
			case 'attendance_correction':
				return m.correction();
			case 'overtime':
				return m.overtime();
			case 'claim':
			case 'reimbursement':
				return m.claim();
			case 'wfh':
				return 'WFH';
			default:
				return type ? type.replaceAll('_', ' ') : m.request();
		}
	}

	function formatShiftName(name?: string | null) {
		switch (name?.trim().toLocaleLowerCase()) {
			case 'shift pagi': return m.morning_shift();
			case 'shift siang': return m.afternoon_shift();
			case 'shift malam': return m.night_shift();
			default: return name || m.morning_shift();
		}
	}

	function formatStatusLabel(status: string) {
		if (status === 'approved' || status === 'disetujui') return m.approved();
		if (status === 'rejected' || status === 'ditolak') return m.rejected();
		return m.pending();
	}

	async function load() {
		loading = true;
		error = '';
		try {
			const [today, reqs, schedules, notifCount] = await Promise.all([
				todayAttendance(),
				request<RequestItem[]>('/v1/requests').catch(() => []),
				request<ScheduleItem[]>('/v1/schedule').catch(() => []),
				request<{ count: number }>('/v1/notifications/unread-count').catch(() => ({
					count: 0
				}))
			]);
			attendance = today;
			recentRequests = reqs.slice(0, 4);
			upcomingSchedules = schedules.slice(0, 3);
			unreadFromApi = notificationCount(notifCount.count) ?? 0;
		} catch (e) {
			error = e instanceof Error ? e.message : m.attendance_unavailable();
		} finally {
			loading = false;
		}
	}

	onMount(() => {
		greeting = greetingForCurrentHour();
		void load();
	});
</script>

<svelte:head><title>{m.home_title()} · PasPapan</title></svelte:head>
<svelte:window onkeydown={(event) => { if (event.key === 'Escape') servicesOpen = false; }} />

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
			<h1 class="user-name">{session.current?.user.full_name ?? m.home_employee_fallback()}</h1>
			<div class="priority-chip" class:all-done={priorityCount === 0}>
				<span class="priority-dot" class:pulse={priorityCount > 0}></span>
				<p class="priority-line">{priorityCount} {m.home_priority_today()}</p>
			</div>
		</div>
		<a class="hero-avatar" href="/user/profile" aria-label={m.home_open_profile()}>
			<span class="avatar-text">{initials(session.current?.user.full_name)}</span>
		</a>
	</header>

	{#if error}
		<div class="today-error" role="alert">
			<span class="error-text">{error}</span>
			<button class="quiet retry-btn" onclick={load} disabled={busy}>{m.retry()}</button>
		</div>
	{/if}

	<!-- PRESENSI HARI INI -->
	<section class="panel attendance" aria-labelledby="attendance-title" aria-busy={loading}>
		<div class="attendance-top">
			<div class="attendance-title-group">
				<p class="card-eyebrow">{m.attendance_eyebrow()}</p>
				<h2 id="attendance-title">{m.today()} <span class="sr-only">{m.attendance_today()}</span></h2>
				<p class="date-subtitle">{formatWorkDate(attendance?.date)}</p>
			</div>
			<div class="live-pill" aria-label={m.live_status()}>
				<span class="live-dot"></span>
				<span class="live-text">{m.live()}</span>
			</div>
		</div>

		<!-- SHIFT INFO CHIP -->
		<div class="shift-banner">
			<span class="shift-icon">
				<Icon name="calendar" size={17} />
			</span>
			<div class="shift-copy">
				<div class="shift-head">
					<strong class="shift-name">{formatShiftName(attendance?.shift_name)}</strong>
					<span class="shift-duration">8 {m.hours_short()}</span>
				</div>
				<p class="shift-hours">
					{m.working_hours()}: <b>{formatClock(attendance?.starts_at) !== '—' ? formatClock(attendance?.starts_at) : '07:00'} – {formatClock(attendance?.ends_at) !== '—' ? formatClock(attendance?.ends_at) : '15:00'}</b>
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
						{checkedIn ? m.recorded() : m.not_yet()}
					</span>
				</div>
				<div class="check-metrics">
					<b class="time-display">{attendance?.check_in_time ? formatClock(attendance.check_in_time) : '--:--'}</b>
					<span class="timeline-copy">
						<small>{m.attendance_in()}</small>
					</span>
				</div>
			</div>

			<div class="check-card keluar" class:is-done={checkedOut} class:is-active={checkedIn && !checkedOut}>
				<div class="check-card-header">
					<span class="check-icon keluar">
						<Icon name="logout" size={15} />
					</span>
					<span class="check-state-badge" class:done={checkedOut} class:active={checkedIn && !checkedOut}>
						{checkedOut ? m.completed() : checkedIn ? m.waiting() : m.not_yet()}
					</span>
				</div>
				<div class="check-metrics">
					<b class="time-display">{attendance?.check_out_time ? formatClock(attendance.check_out_time) : '--:--'}</b>
					<span class="timeline-copy">
						<small>{m.attendance_out()}</small>
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
				{checkedOut ? m.attendance_complete() : checkedIn ? m.check_out_qr() : m.check_in_qr()}
			</span>
		</a>
	</section>

	<!-- AKSES CEPAT / PINTASAN -->
	<section class="panel shortcuts-panel" aria-labelledby="shortcuts-title">
		<div class="section-title-wrap">
			<p class="card-eyebrow">{m.quick_access()}</p>
			<h2 id="shortcuts-title">{m.services()}</h2>
		</div>

		<div class="shortcut-grid">
			<a class="shortcut-tile" href="/app/history">
				<span class="shortcut-icon tone-blue"><Icon name="clock" size={20} /></span>
				<b class="shortcut-label">{m.nav_history()}</b>
			</a>
			<a class="shortcut-tile" href="/app/requests">
				<span class="shortcut-icon tone-purple"><Icon name="tasks" size={20} /></span>
				<b class="shortcut-label">{m.correction()}</b>
			</a>
			<a class="shortcut-tile" href="/app/requests">
				<span class="shortcut-icon tone-emerald"><Icon name="send" size={19} /></span>
				<b class="shortcut-label">{m.leave()}</b>
			</a>
			<a class="shortcut-tile" href="/app/my-reimbursements">
				<span class="shortcut-icon tone-amber"><Icon name="banknote" size={20} /></span>
				<b class="shortcut-label">{m.claim()}</b>
			</a>
			<a class="shortcut-tile" href="/app/my-overtimes">
				<span class="shortcut-icon tone-sky"><Icon name="clock" size={20} /></span>
				<b class="shortcut-label">{m.overtime()}</b>
			</a>
			<a class="shortcut-tile" href="/app/my-payslips">
				<span class="shortcut-icon tone-green"><Icon name="wallet" size={20} /></span>
				<b class="shortcut-label">{m.payslip()}</b>
			</a>
		</div>
		<button class="services-trigger" type="button" onclick={() => (servicesOpen = true)}>
			<span class="services-trigger-icon"><Icon name="more" size={16} /></span>
			<span>{m.all_services()}</span>
			<small>{5 + teamLinks.length}</small>
			<Icon name="arrow" size={15} />
		</button>
	</section>

	{#if servicesOpen}
		<div class="services-backdrop" role="presentation" onclick={(event) => { if (event.target === event.currentTarget) servicesOpen = false; }}>
			<div class="services-drawer" role="dialog" aria-modal="true" aria-labelledby="services-title" tabindex="-1">
				<header class="services-drawer-header">
					<div><p class="card-eyebrow">{m.navigation()}</p><h2 id="services-title">{m.all_services()}</h2></div>
					<button class="services-close" type="button" aria-label={m.close_all_services()} onclick={() => (servicesOpen = false)}><Icon name="close" size={18} /></button>
				</header>
				<nav class="services-list" aria-label={m.work_services()}>
					<a href="/app/shift-swaps"><span><Icon name="swap" size={17} /></span><b>{m.shift_swap()}</b><Icon name="arrow" size={14} /></a>
					<a href="/app/announcements"><span><Icon name="megaphone" size={17} /></span><b>{m.announcements()}</b><Icon name="arrow" size={14} /></a>
					<a href="/app/my-warnings"><span><Icon name="file" size={17} /></span><b>{m.hr_letter()}</b><Icon name="arrow" size={14} /></a>
					<a href="/app/my-assets"><span><Icon name="box" size={17} /></span><b>{m.inventory()}</b><Icon name="arrow" size={14} /></a>
					<a href="/app/my-kpis"><span><Icon name="chart" size={17} /></span><b>{m.kpi_achievement()}</b><Icon name="arrow" size={14} /></a>
					{#each teamLinks as link}
						<a href={link.href}><span><Icon name={link.icon} size={17} /></span><b>{link.label}</b><Icon name="arrow" size={14} /></a>
					{/each}
				</nav>
			</div>
		</div>
	{/if}

	<!-- PERLU TINDAKAN -->
	{#if priorityCount > 0}
		<section class="panel action-panel" aria-labelledby="action-title">
			<div class="action-header">
				<div>
					<p class="card-eyebrow">{m.agenda()}</p>
					<h2 id="action-title">{m.needs_completion()}</h2>
				</div>
				<span class="count-badge">{priorityCount} {m.actions_count()}</span>
			</div>

			<div class="followup-row">
				{#if unreadNotifications > 0}
					<a class="followup-pill" href="/app/notifications">
						<span class="followup-icon"><Icon name="bell" size={15} /></span>
						<span class="followup-label">{m.notifications()}</span>
						<span class="followup-count">{unreadNotifications}</span>
					</a>
				{/if}
				{#if pendingRequests > 0}
					<a class="followup-pill" href="/app/requests">
						<span class="followup-icon"><Icon name="request" size={15} /></span>
						<span class="followup-label">{m.pending_requests()}</span>
						<span class="followup-count">{pendingRequests}</span>
					</a>
				{/if}
				{#if !checkedIn}
					<a class="followup-pill urgent" href="/app/scan">
						<span class="followup-icon"><Icon name="login" size={15} /></span>
						<span class="followup-label">{m.check_in_missing()}</span>
					</a>
				{/if}
			</div>
		</section>
	{/if}

	<!-- AKTIVITAS TERBARU -->
	<section class="panel recent-panel" aria-labelledby="recent-title">
		<div class="section-title-wrap">
			<p class="card-eyebrow">{m.recent_activity()}</p>
			<h2 id="recent-title">{m.request_status()}</h2>
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
						<strong>{m.operational_claim()}</strong>
						<small>{m.verified_processing()}</small>
					</div>
					<span class="recent-status approved">{m.approved()}</span>
				</a>
				<a class="recent-row" href="/app/requests">
					<span class="recent-dot approved"></span>
					<div class="recent-copy">
						<strong>{m.attendance_correction()}</strong>
						<small>{formatShortDate(attendance?.date)}</small>
					</div>
					<span class="recent-status approved">{m.approved()}</span>
				</a>
			</div>
		{/if}
	</section>

	<!-- ACARA MENDATANG -->
	<section class="panel ongoing-section" aria-labelledby="ongoing-title">
		<div class="ongoing-header">
			<h2 id="ongoing-title">{m.next_shift_schedule()}</h2>
			<a class="view-all-link" href="/app/schedule">{m.view_calendar()}</a>
		</div>

		<div class="upcoming-panel">
			{#if upcomingSchedules.length > 0}
				<div class="upcoming-list">
					{#each upcomingSchedules as item}
						<div class="upcoming-row">
							<div class="upcoming-copy">
								<strong>{item.shift_name ?? m.work_shift()}</strong>
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
					<p>{m.no_additional_shift()}</p>
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
		grid-template-columns: repeat(4, minmax(0, 1fr));
		gap: 4px;
	}
	.shortcut-tile {
		display: grid;
		align-items: center;
		justify-items: center;
		align-content: center;
		gap: 3px;
		min-width: 0;
		min-height: 52px;
		padding: 5px 2px;
		border-radius: var(--radius-sm);
		border: 1px solid transparent;
		background: transparent !important;
		color: var(--ink) !important;
		text-decoration: none;
		box-shadow: none;
		transition: border-color 140ms ease, background-color 140ms ease, transform 120ms ease, box-shadow 140ms ease;
	}
	.shortcut-tile:hover {
		border-color: #d7e4d5 !important;
		background: var(--surface-alt) !important;
		color: var(--primary-strong) !important;
		box-shadow: none !important;
	}
	.shortcut-tile:active {
		transform: scale(0.96);
	}
	.shortcut-icon {
		display: grid;
		place-items: center;
		width: 27px;
		height: 27px;
		border-radius: var(--radius-sm);
		background: var(--primary-soft);
		color: var(--primary-strong);
		transition: transform 140ms ease;
	}
	.shortcut-tile:hover .shortcut-icon {
		transform: translateY(-1px);
	}
	.shortcut-label {
		min-width: 0;
		max-width: 100%;
		font-size: 9px;
		line-height: 1.15;
		font-weight: 750;
		color: var(--ink);
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}
	.services-trigger {
		display: grid !important;
		grid-template-columns: auto minmax(0, 1fr) auto auto;
		align-items: center;
		gap: 8px;
		width: 100%;
		min-height: 38px !important;
		margin-top: 8px;
		padding: 5px 7px !important;
		border: 0 !important;
		border-top: 1px solid var(--line) !important;
		border-radius: 0 !important;
		background: transparent !important;
		box-shadow: none !important;
		color: var(--ink) !important;
		font: inherit;
		font-size: 10.5px !important;
		font-weight: 750;
		text-align: left;
		cursor: pointer;
	}
	.services-trigger:hover {
		color: var(--primary-strong) !important;
	}
	.services-trigger-icon {
		display: grid;
		place-items: center;
		width: 26px;
		height: 26px;
		border-radius: var(--radius-sm);
		background: var(--primary-soft);
		color: var(--primary-strong);
	}
	.services-trigger small {
		color: var(--muted);
		font-size: 9.5px;
		font-weight: 750;
	}
	.services-backdrop {
		position: fixed;
		inset: 0;
		z-index: 120;
		display: flex;
		justify-content: flex-end;
		background: rgba(24, 34, 29, 0.28);
		backdrop-filter: blur(2px);
	}
	.services-drawer {
		width: min(360px, calc(100vw - 18px));
		height: 100dvh;
		padding: 16px;
		overflow-y: auto;
		background: var(--surface);
		border-inline-start: 1px solid var(--line);
		box-shadow: -16px 0 44px -28px rgba(24, 34, 29, 0.42);
	}
	.services-drawer-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 12px;
		padding-bottom: 12px;
		border-bottom: 1px solid var(--line);
	}
	.services-drawer-header h2 {
		margin: 0;
		font-size: 17px;
	}
	.services-close {
		display: grid !important;
		place-items: center;
		width: 34px;
		min-height: 34px !important;
		padding: 0 !important;
		border: 1px solid var(--line) !important;
		border-radius: var(--radius-sm) !important;
		background: transparent !important;
		color: var(--ink) !important;
		box-shadow: none !important;
		cursor: pointer;
	}
	.services-list {
		display: grid;
		gap: 3px;
		padding-top: 8px;
	}
	.services-list a {
		display: grid;
		grid-template-columns: auto minmax(0, 1fr) auto;
		align-items: center;
		gap: 9px;
		min-height: 44px;
		padding: 6px;
		border-radius: var(--radius-sm);
		color: var(--ink) !important;
		text-decoration: none;
	}
	.services-list a:hover {
		background: var(--surface-alt);
		color: var(--primary-strong) !important;
	}
	.services-list a > span {
		display: grid;
		place-items: center;
		width: 30px;
		height: 30px;
		border-radius: var(--radius-sm);
		background: var(--primary-soft);
		color: var(--primary-strong);
	}
	.services-list b {
		font-size: 11.5px;
		font-weight: 750;
	}

	/* Action Needed Card */
	.action-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 12px;
		margin-bottom: 7px;
	}
	.action-header h2 {
		margin: 0;
		font-size: 14px;
		font-weight: 800;
		color: var(--ink);
	}
	.count-badge {
		display: inline-flex;
		align-items: center;
		height: 24px;
		padding-inline: 6px;
		border-radius: var(--radius-sm);
		background: #fef3c7;
		border: 1px solid #fde68a;
		color: #92400e;
		font-size: 10.5px;
		font-weight: 850;
	}
	.followup-row {
		display: flex;
		flex-wrap: wrap;
		gap: 5px;
	}
	.followup-pill {
		display: inline-flex;
		align-items: center;
		gap: 7px;
		min-height: 34px;
		padding: 4px 8px 4px 5px;
		border-radius: var(--radius-sm);
		border: 1px solid transparent;
		background: var(--surface-alt);
		color: var(--ink) !important;
		text-decoration: none;
		font-size: 11px;
		font-weight: 650;
		transition: background-color 140ms ease, border-color 140ms ease;
	}
	.followup-pill:hover {
		background: var(--surface);
		border-color: #b8ccb9;
	}
	.followup-pill.urgent {
		background: #fff7ed;
		border-color: #ffdfbd;
		color: #9a3412 !important;
	}
	.followup-icon {
		display: grid;
		place-items: center;
		width: 24px;
		height: 24px;
		border-radius: var(--radius-sm);
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
		min-width: 18px;
		height: 18px;
		padding-inline: 5px;
		border-radius: 999em;
		background: var(--primary-soft);
		color: var(--primary-strong);
		font-size: 9.5px;
		font-weight: 800;
	}

	/* Recent Activity List */
	.recent-panel .section-title-wrap h2 {
		margin-bottom: 5px;
	}
	.recent-list {
		display: grid;
	}
	.recent-row {
		display: flex;
		align-items: center;
		gap: 9px;
		min-height: 40px;
		padding: 5px 2px;
		background: transparent;
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
		width: 6px;
		height: 6px;
		flex: 0 0 6px;
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
		font-size: 11.5px;
		font-weight: 750;
		color: var(--ink);
	}
	.recent-copy small {
		font-size: 10px;
		color: var(--muted);
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}
	.recent-status {
		padding: 2px 6px;
		border-radius: var(--radius-sm);
		border: 0;
		background: #fff8e7;
		color: #92400e;
		font-size: 9px;
		font-weight: 800;
		letter-spacing: 0.02em;
	}
	.recent-status.approved {
		background: #ecf8f0;
		color: #065f46;
	}
	.recent-status.rejected {
		background: #fef2f2;
		color: #991b1b;
	}

	/* Upcoming Section */
	.ongoing-section {
		display: grid;
		grid-template-rows: auto minmax(0, 1fr);
		gap: 7px;
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
		font-size: 14px;
		font-weight: 800;
		color: var(--ink);
	}
	.view-all-link {
		font-size: 10.5px;
		font-weight: 700;
		color: var(--primary);
		text-decoration: none;
	}
	.upcoming-panel {
		display: grid;
		align-content: start;
		padding: 0;
		overflow: hidden;
	}
	.upcoming-panel:has(.upcoming-empty) { align-content: center; }
	.upcoming-list {
		display: grid;
	}
	.upcoming-row {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 12px;
		padding: 7px 2px;
	}
	.upcoming-row + .upcoming-row {
		border-top: 1px solid var(--line);
	}
	.upcoming-copy {
		display: grid;
		gap: 2px;
	}
	.upcoming-copy strong {
		font-size: 11.5px;
		font-weight: 750;
		color: var(--ink);
	}
	.upcoming-time {
		font-size: 10px;
		color: var(--muted);
	}
	.upcoming-hours {
		font-size: 10.5px;
		font-weight: 700;
		color: var(--ink);
		font-variant-numeric: tabular-nums;
	}
	.upcoming-empty {
		display: flex;
		align-items: center;
		gap: 8px;
		padding: 2px;
		text-align: left;
	}
	.upcoming-empty-icon {
		display: grid;
		place-items: center;
		width: 30px;
		height: 30px;
		flex: 0 0 30px;
		border-radius: var(--radius-sm);
		background: var(--surface-alt);
		color: var(--muted);
	}
	.upcoming-empty p {
		margin: 0;
		font-size: 10.5px;
		color: var(--muted);
	}

	:global(:root[data-theme='dark']) .panel,
	:global(:root[data-theme='dark']) .hero-greeting {
		border-color: transparent;
		box-shadow: var(--panel-shadow);
	}
	:global(:root[data-theme='dark']) .shift-banner,
	:global(:root[data-theme='dark']) .check-card {
		border-color: transparent;
		background: var(--surface-alt);
	}
	:global(:root[data-theme='dark']) .shift-icon,
	:global(:root[data-theme='dark']) .shift-duration,
	:global(:root[data-theme='dark']) .check-state-badge {
		border-color: transparent;
		background: #2b2f30;
	}
	:global(:root[data-theme='dark']) .services-drawer {
		border-color: transparent;
		box-shadow: -18px 0 48px -24px rgba(0, 0, 0, .92);
	}

	@media (min-width: 1024px) {
		.today-page {
			max-width: none;
			grid-template-columns: repeat(2, minmax(0, 1fr));
			align-items: stretch;
		}
		.today-page > * { min-width: 0; }
		.shortcuts-panel { display: flex; flex-direction: column; }
		.shortcuts-panel .services-trigger { margin-top: auto; }
		.hero-greeting, .today-error, .action-panel { grid-column: 1 / -1; }
	}

	@media (max-width: 480px) {
		.today-page {
			gap: 9px;
		}
		.hero-greeting {
			gap: 10px;
			padding: 10px 12px;
		}
		.panel {
			padding: 11px;
		}
		.hero-copy { gap: 2px; }
		.hero-meta { gap: 6px; }
		.greeting-line { font-size: 11px; }
		.role-badge { padding: 1px 6px; font-size: 9px; }
		.user-name { font-size: 18px; }
		.priority-chip { gap: 5px; }
		.priority-line { font-size: 10.5px; }
		.hero-avatar { width: 40px; height: 40px; flex-basis: 40px; }
		.avatar-text { font-size: 13px; }
		.card-eyebrow { margin-bottom: 2px !important; font-size: 9px; }
		.attendance-top { margin-bottom: 10px; }
		.attendance-title-group h2 { font-size: 15px; }
		.date-subtitle { font-size: 11px; }
		.live-pill { gap: 4px; padding: 3px 7px; font-size: 9.5px; }
		.live-dot { width: 5px; height: 5px; }
		.shift-banner { gap: 8px; padding: 8px 10px; margin-bottom: 9px; }
		.shift-icon { width: 30px; height: 30px; flex-basis: 30px; }
		.shift-name { font-size: 11.5px; }
		.shift-duration { font-size: 9.5px; padding-inline: 5px; }
		.shift-hours { font-size: 10.5px; }
		.check-grid { gap: 8px; margin-bottom: 9px; }
		.check-card { gap: 7px; padding: 9px; }
		.check-icon { width: 24px; height: 24px; }
		.check-state-badge { padding: 1px 5px; font-size: 9px; }
		.time-display { font-size: 19px; }
		.timeline-copy small { font-size: 10px; }
		.absen-cta { gap: 7px; min-height: 38px; padding: 7px 12px; font-size: 12.5px; }
		.absen-cta-icon { width: 22px; height: 22px; }
		.section-title-wrap h2 { margin-bottom: 9px; font-size: 14px; }
		.shortcut-grid {
			grid-template-columns: repeat(3, minmax(0, 1fr));
			gap: 4px;
		}
		.shortcut-tile {
			gap: 4px;
			min-height: 60px;
			padding: 6px 3px;
		}
		.shortcut-icon {
			width: 32px;
			height: 32px;
		}
		.shortcut-icon :global(svg) { width: 17px; height: 17px; }
		.shortcut-label { font-size: 10.5px; }
		.services-drawer {
			align-self: flex-end;
			width: calc(100% - 20px);
			height: auto;
			max-height: calc(100dvh - 140px - env(safe-area-inset-bottom));
			margin: 10px;
			padding: 14px 12px;
			border: 1px solid var(--line);
			border-radius: var(--radius-lg);
			box-shadow: 0 -18px 44px -28px rgba(24, 34, 29, 0.42);
		}
		.services-backdrop {
			inset-block-end: calc(62px + env(safe-area-inset-bottom));
		}
		.action-panel,
		.recent-panel { padding-block: 10px; }
		.recent-panel .section-title-wrap h2 { margin-bottom: 3px; }
		.followup-row { display: grid; grid-template-columns: minmax(0, 1fr); }
	}
</style>
