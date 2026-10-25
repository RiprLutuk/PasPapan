<script lang="ts">
	import { PageHeader, MetricCard } from './ui';
	import { onMount } from 'svelte';
	import Icon from './Icon.svelte';
	import {
		request,
		employees,
		shifts,
		systemHealth,
		type Employee,
		type Shift,
		type SystemHealthTelemetry
	} from './api';
	import { realtimeState } from './realtime.svelte';
	import { workSession } from './session';
	import { visibleOperations } from './access';

	const session = workSession();
	let capabilities = $derived(session.current?.capabilities ?? []);
	let baseModules = $derived(visibleOperations(capabilities).filter((link) => link.href !== '/admin/dashboard'));

	let modules = $derived(
		capabilities.includes('admin.dashboard.view')
			? [
					...baseModules,
					{ href: '/admin/attendances', label: 'Rekap kehadiran', icon: 'clock', group: 'Kehadiran' },
					{ href: '/admin/holidays', label: 'Hari libur kerja', icon: 'calendar', group: 'Kehadiran' },
					{ href: '/admin/payrolls', label: 'Penggajian & slip', icon: 'request', group: 'Keuangan' },
					{ href: '/admin/cash-advances', label: 'Persetujuan kasbon', icon: 'check', group: 'Keuangan' },
					{ href: '/admin/kpi', label: 'Penilaian KPI', icon: 'people', group: 'Tim' },
					{ href: '/admin/assets', label: 'Inventaris aset', icon: 'panel', group: 'Operasional' },
					{ href: '/admin/activity-logs', label: 'Log audit sistem', icon: 'clock', group: 'Keamanan & DB' },
					{ href: '/admin/system-info', label: 'Monitor PostgreSQL 18', icon: 'shield', group: 'Keamanan & DB' },
					...(capabilities.includes('admin.access.manage')
						? [{ href: '/admin/api-docs', label: 'API Docs & Postman', icon: 'shield', group: 'Keamanan & DB' }]
						: [])
				]
			: baseModules
	);

	type Approval = { id: string; status: string };
	type OverviewData = {
		employee_count: number;
		today_present_count: number;
		pending_requests_count: number;
		active_locations_count: number;
		recent_attendances: {
			id: string;
			full_name: string;
			date: string;
			checked_in_at?: string | null;
			checked_out_at?: string | null;
			location_name: string;
		}[];
	};

	let people = $state<Employee[]>([]);
	let shiftList = $state<Shift[]>([]);
	let approvals = $state<Approval[]>([]);
	let overview = $state<OverviewData | null>(null);
	let health = $state<SystemHealthTelemetry | null>(null);
	let loading = $state(true);
	let error = $state('');

	let pendingApprovals = $derived(approvals.filter((item) => item.status.startsWith('pending_')).length);
	let stats = $derived(
		[
			{
				label: 'Karyawan aktif',
				value: String(overview?.employee_count ?? people.length),
				href: '/admin/employees',
				permission: 'admin.employees.view'
			},
			{
				label: 'Hadir hari ini',
				value: String(realtimeState.lastPulse?.checked_in_today ?? overview?.today_present_count ?? 0),
				href: '/admin/attendances',
				permission: 'admin.dashboard.view'
			},
			{
				label: 'Master shift',
				value: String(shiftList.length),
				href: '/admin/attendance',
				permission: 'admin.shifts.manage'
			},
			{
				label: 'Menunggu approval',
				value: String(realtimeState.lastPulse?.pending_approvals ?? overview?.pending_requests_count ?? pendingApprovals),
				href: '/app/approvals',
				permission: 'requests.approvals.view'
			}
		].filter((stat) => capabilities.includes(stat.permission))
	);

	async function load() {
		loading = true;
		error = '';
		try {
			[people, shiftList, approvals, overview, health] = await Promise.all([
				capabilities.includes('admin.employees.view') ? employees() : Promise.resolve([]),
				capabilities.includes('admin.shifts.manage') ? shifts() : Promise.resolve([]),
				capabilities.includes('requests.approvals.view') ? request<Approval[]>('/v1/approvals') : Promise.resolve([]),
				capabilities.includes('admin.dashboard.view')
					? request<OverviewData>('/v1/admin/overview').catch(() => null)
					: Promise.resolve(null),
				capabilities.includes('admin.dashboard.view')
					? systemHealth().catch(() => null)
					: Promise.resolve(null)
			]);
		} catch (cause) {
			error = cause instanceof Error ? cause.message : 'Dashboard admin tidak dapat dimuat.';
		} finally {
			loading = false;
		}
	}

	onMount(load);
</script>

<svelte:head><title>Dashboard admin · PasPapan</title></svelte:head>

<main id="main-content" class="workspace-page admin-dashboard" tabindex="-1">
	<PageHeader title="Dashboard admin" eyebrow={session.current?.company.name} description="Kehadiran, pengajuan, dan operasional tim.">{#snippet actions()}
			{#if capabilities.includes('admin.barcodes.manage')}
				<a class="quiet-link" href="/admin/barcodes"><Icon name="qr" size={16} />Layar QR</a>
			{/if}
			{#if capabilities.includes('admin.employees.view')}
				<a class="quiet-link" href="/admin/employees"><Icon name="people" size={16} />Karyawan</a>
			{/if}
		{/snippet}</PageHeader>

	{#if error}
		<p role="alert">{error} <button class="quiet" onclick={load}>Coba lagi</button></p>
	{/if}

	<section class="metrics" aria-label="Ringkasan admin" aria-busy={loading}>
		{#each stats as stat}
			<MetricCard label={stat.label} value={stat.value} href={stat.href} loading={loading || !!error} />
		{/each}
	</section>

	<div class="dashboard-grid">
		<section class="panel">
			<div class="section-heading">
				<div>
					<p class="eyebrow">NAVIGASI OPERASIONAL</p>
					<h2>Modul SDM, kehadiran &amp; keuangan</h2>
				</div>
				<span class="badge">{modules.length} modul</span>
			</div>
			<div class="module-grid">
				{#each modules as module}
					<a href={module.href}>
						<span class="module-icon"><Icon name={module.icon} size={17} /></span>
						<span class="module-copy">
							<strong>{module.label}</strong>
							<small>{module.group}</small>
						</span>
						<Icon name="arrow" size={14} />
					</a>
				{/each}
				{#if !modules.length}
					<p class="muted">Belum ada akses operasional. Hubungi owner perusahaan.</p>
				{/if}
			</div>
		</section>

		<div class="side-stack">
			<aside class="panel health">
				<div class="health-top">
					<div class="health-mark" aria-hidden="true"><Icon name={loading || error ? 'clock' : 'shield'} size={18} /></div>
					<span class="badge">{realtimeState.connected ? 'SSE Live' : 'Siaga'}</span>
				</div>
				<p class="eyebrow">RESOURCE &amp; DATABASE MONITOR</p>
				<h2>{health ? `PostgreSQL ${health.postgres.server_version}` : loading ? 'Menghubungkan…' : 'Semua siap'}</h2>
				{#if health}
					<dl class="telemetry-list">
						<div><dt>pg_stat_activity</dt><dd>{health.postgres.activity.active_queries} aktif / {health.postgres.activity.total_connections} koneksi</dd></div>
						<div><dt>Cache Hit Ratio</dt><dd>{health.postgres.database_stats.cache_hit_ratio_pct}%</dd></div>
						<div><dt>Ukuran DB &amp; Pool</dt><dd>{health.postgres.database_size_pretty} · SQLx {health.sqlx_pool.active}/{health.sqlx_pool.size}</dd></div>
						<div><dt>Object Storage</dt><dd>{health.storage.driver.toUpperCase()} ({health.tenant.stored_files_count} berkas)</dd></div>
					</dl>
					<a class="telemetry-cta" href="/admin/system-info">Buka detail `pg_stat_activity` <Icon name="arrow" size={14} /></a>
				{:else}
					<p>{loading ? 'Memuat telemetri server.' : 'Data tim dan koneksi database berjalan normal.'}</p>
				{/if}
			</aside>

			{#if overview?.recent_attendances?.length}
				<section class="panel live-feed" aria-label="Kehadiran terbaru">
					<div class="section-heading">
						<div>
							<p class="eyebrow">AKTIVITAS PRESENSI</p>
							<h2>Kehadiran terbaru</h2>
						</div>
						<a class="feed-more" href="/admin/attendances">Semua</a>
					</div>
					<ul class="feed-list">
						{#each overview.recent_attendances.slice(0, 5) as item}
							<li>
								<div class="feed-copy">
									<strong>{item.full_name}</strong>
									<small>{item.location_name} · {item.date}</small>
								</div>
								<span class="badge">{item.checked_in_at ?? '—'} → {item.checked_out_at ?? 'Aktif'}</span>
							</li>
						{/each}
					</ul>
				</section>
			{/if}
		</div>
	</div>
</main>

<style>
	.quiet-link { display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 36px; flex-shrink: 0; padding: 7px 11px; border: 1px solid var(--line); border-radius: var(--radius-sm); background: var(--surface); color: var(--ink) !important; font-size: 12px; font-weight: 750; text-decoration: none; box-shadow: var(--shadow-soft); transition: background-color 140ms ease, border-color 140ms ease, color 140ms ease; }
	.quiet-link:hover { background: var(--primary-soft); border-color: #bfd3bd; color: var(--primary-strong) !important; }

	.metrics { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; margin-bottom: 14px; }

	.dashboard-grid { display: grid; grid-template-columns: minmax(0, 1.35fr) minmax(280px, .65fr); gap: 12px; align-items: start; }
	.module-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 7px; }
	.module-grid a { display: flex; align-items: center; gap: 9px; min-width: 0; min-height: 48px; padding: 8px 10px; border: 1px solid var(--line); border-radius: var(--radius-sm); background: var(--surface); color: var(--ink); text-decoration: none; transition: border-color 140ms ease, background-color 140ms ease, transform 140ms ease; }
	.module-grid a:hover { border-color: #bdd1bd; background: var(--surface-alt); transform: translateY(-1px); }
	.module-icon { display: grid; place-items: center; width: 32px; height: 32px; flex: 0 0 32px; border-radius: var(--radius-sm); background: var(--primary-soft); color: var(--primary-strong); }
	.module-copy { display: grid; min-width: 0; line-height: 1.2; }
	.module-copy strong { min-width: 0; font-size: 12.5px; font-weight: 750; color: var(--ink); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
	.module-copy small { font-size: 10.5px; color: var(--muted); }
	.module-grid a > :global(svg:last-child) { margin-inline-start: auto; flex: 0 0 auto; color: var(--muted); }

	.side-stack { display: grid; gap: 12px; }
	.health-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
	.health-mark { display: grid; place-items: center; width: 34px; height: 34px; border-radius: var(--radius-sm); background: var(--primary-soft); color: var(--primary-strong); }
	.health h2 { margin-bottom: 8px; font-size: 15.5px; }
	.telemetry-list { display: grid; gap: 6px; margin: 10px 0 12px; padding: 0; }
	.telemetry-list div { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding-bottom: 5px; border-bottom: 1px dashed var(--line); font-size: 11.5px; }
	.telemetry-list dt { color: var(--muted); font-weight: 600; }
	.telemetry-list dd { margin: 0; font-weight: 750; color: var(--ink); font-variant-numeric: tabular-nums; }
	.telemetry-cta { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 750; text-decoration: none; color: var(--primary-strong); }

	.feed-more { font-size: 11.5px; font-weight: 750; text-decoration: none; }
	.feed-list { list-style: none; margin: 0; padding: 0; display: grid; gap: 7px; }
	.feed-list li { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 7px 9px; border: 1px solid var(--line); border-radius: var(--radius-sm); background: var(--surface-alt); }
	.feed-copy { display: grid; min-width: 0; }
	.feed-copy strong { font-size: 12px; font-weight: 750; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
	.feed-copy small { font-size: 10.5px; color: var(--muted); }

	@media (max-width: 960px) {
		.metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); }
		.dashboard-grid { grid-template-columns: minmax(0, 1fr); }
	}
	@media (max-width: 760px) {
		.metrics { gap: 8px; }
	}
	@media (max-width: 420px) {
		.module-grid { grid-template-columns: 1fr; }
	}
</style>
