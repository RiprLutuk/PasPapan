<script lang="ts">
	import { onMount } from 'svelte';
	import Icon from './Icon.svelte';
	import { request } from './api';
	import { realtimeState, requestPwaNotificationPermission } from './realtime.svelte';

	let { mode }: { mode: 'schedule' | 'history' | 'notifications' } = $props();

	type Item = {
		id?: string;
		date?: string;
		name?: string;
		starts_at?: string;
		ends_at?: string;
		crosses_midnight?: boolean;
		checked_in_at?: string | null;
		checked_out_at?: string | null;
		title?: string;
		body?: string;
		read?: boolean;
	};

	const titles = { schedule: 'Jadwal kerja', history: 'Riwayat kehadiran', notifications: 'Notifikasi' };
	const paths = { schedule: '/v1/schedule', history: '/v1/attendance/history', notifications: '/v1/notifications' };
	const statuses: Record<string, string> = {
		pending_manager: 'Menunggu persetujuan manager',
		pending_admin: 'Menunggu persetujuan admin',
		approved: 'Pengajuan disetujui',
		rejected: 'Pengajuan ditolak'
	};
	const monthNames = new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric', timeZone: 'UTC' });
	const dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
	const today = new Date();
	const todayKey = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

	let items = $state<Item[]>([]), loading = $state(true), error = $state(''), busy = $state('');
	let dataReady = $state(false);
	let actionError = $state('');
	let search = $state('');
	let notificationFilter = $state<'all' | 'unread'>('all');
	let selectedMonth = $state(todayKey.slice(0, 7));
	let selectedHistoryId = $state('');

	let visibleItems = $derived.by(() => {
		if (mode === 'history') return items.filter((item) => item.date?.startsWith(selectedMonth));
		if (mode === 'schedule') {
			return [...items].filter((item) => !!item.date && item.date >= todayKey)
				.sort((a, b) => (a.date ?? '').localeCompare(b.date ?? ''));
		}
		return items.filter((item) =>
			(notificationFilter === 'all' || !item.read) &&
			`${item.title ?? ''} ${item.body ?? ''}`.toLocaleLowerCase('id').includes(search.toLocaleLowerCase('id'))
		);
	});

	let calendar = $derived.by(() => {
		const [year, month] = selectedMonth.split('-').map(Number);
		if (!year || !month) return [];
		const offset = new Date(Date.UTC(year, month - 1, 1)).getUTCDay();
		const days = new Date(Date.UTC(year, month, 0)).getUTCDate();
		const records = new Map(items.filter((item) => item.date).map((item) => [item.date!, item]));
		return [
			...Array.from({ length: offset }, () => null),
			...Array.from({ length: days }, (_, index) => {
				const day = index + 1;
				const date = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
				return { date, day, item: records.get(date) };
			})
		];
	});

	let monthLabel = $derived.by(() => {
		const [year, month] = selectedMonth.split('-').map(Number);
		return year && month ? monthNames.format(new Date(Date.UTC(year, month - 1, 1))) : selectedMonth;
	});

	let completeMonthCount = $derived(
		items.filter((item) => item.date?.startsWith(selectedMonth) && Boolean(item.checked_in_at && item.checked_out_at)).length
	);
	let unreadCount = $derived(items.filter((item) => !item.read).length);

	function prevMonth() {
		const [year, month] = selectedMonth.split('-').map(Number);
		if (!year || !month) return;
		const d = new Date(Date.UTC(year, month - 2, 1));
		selectedMonth = `${d.getUTCFullYear()}-${String(d.getUTCMonth() + 1).padStart(2, '0')}`;
	}

	function nextMonth() {
		const [year, month] = selectedMonth.split('-').map(Number);
		if (!year || !month) return;
		const d = new Date(Date.UTC(year, month, 1));
		selectedMonth = `${d.getUTCFullYear()}-${String(d.getUTCMonth() + 1).padStart(2, '0')}`;
	}

	async function load() {
		loading = true; error = ''; actionError = ''; dataReady = false;
		try {
			items = await request<Item[]>(paths[mode]);
			dataReady = true;
			if (mode === 'history') {
				const monthItems = items.filter((item) => item.date?.startsWith(selectedMonth));
				selectedHistoryId = monthItems[0]?.id ?? '';
			}
		}
		catch (e) { error = e instanceof Error ? e.message : 'Gagal memuat data.'; }
		finally { loading = false; }
	}

	$effect(() => {
		if (mode === 'history' && dataReady) {
			const monthItems = items.filter((item) => item.date?.startsWith(selectedMonth));
			if (!monthItems.some((item) => item.id === selectedHistoryId)) {
				selectedHistoryId = monthItems[0]?.id ?? '';
			}
		}
	});

	async function markRead(id: string) {
		busy = id; actionError = '';
		try {
			await request('/v1/notifications/' + id + '/read', { method: 'POST' });
			items = items.map(i => i.id === id ? { ...i, read: true } : i);
			window.dispatchEvent(new Event('paspapan:notifications-updated'));
		}
		catch (e) { actionError = e instanceof Error ? e.message : 'Gagal menyimpan. Coba tandai dibaca lagi.'; }
		finally { busy = ''; }
	}

	function formatDate(value: string, options: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'short', year: 'numeric' }) {
		const [year, month, day] = value.split('-').map(Number);
		if (!year || !month || !day) return value;
		return new Intl.DateTimeFormat('id-ID', { ...options, timeZone: 'UTC' }).format(new Date(Date.UTC(year, month - 1, day)));
	}

	onMount(() => {
		void load();
		const handleRealtimeUpdate = () => {
			if (mode === 'notifications' && !busy) {
				void request<Item[]>(paths.notifications)
					.then((fresh) => {
						items = fresh;
						dataReady = true;
					})
					.catch(() => {});
			}
		};
		window.addEventListener('paspapan:notifications-updated', handleRealtimeUpdate);
		return () => {
			window.removeEventListener('paspapan:notifications-updated', handleRealtimeUpdate);
		};
	});
</script>

<svelte:head><title>{titles[mode]} · PasPapan</title></svelte:head>

<main id="main-content" class="workspace-page activity-page" tabindex="-1">
	<header class="compact-header">
		<div class="header-left">
			<a class="back-btn" href="/app/today" aria-label="Kembali ke Beranda" title="Beranda">
				<Icon name="back" size={16} />
			</a>
			<h1>{titles[mode]}</h1>
		</div>

		{#if dataReady}
			<div class="header-meta">
				{#if mode === 'history'}
					<span class="meta-pill">{visibleItems.length} hadir</span>
					<span class="meta-pill strong">{completeMonthCount} lengkap</span>
				{:else if mode === 'schedule'}
					<span class="meta-pill strong">{visibleItems.length} shift</span>
					<a class="action-chip" href="/app/requests?kind=leave&new=1"><Icon name="plus" size={13} /> Izin</a>
				{:else}
					<span class="meta-pill strong">{unreadCount} baru</span>
				{/if}
			</div>
		{/if}
	</header>

	{#if error}
		<p class="alert-row" role="alert">{error} <button class="quiet" onclick={load}>Coba lagi</button></p>
	{/if}
	{#if actionError}
		<p class="alert-row" role="alert">{actionError}</p>
	{/if}

	{#if mode === 'history'}
		<section class="panel calendar-panel" aria-label="Kalender kehadiran">
			<div class="calendar-toolbar">
				<div class="month-nav-group">
					<button type="button" class="month-nav-btn" onclick={prevMonth} aria-label="Bulan sebelumnya" title="Bulan sebelumnya">
						<Icon name="back" size={13} />
					</button>
					<h2 class="month-heading">{monthLabel}</h2>
					<button type="button" class="month-nav-btn" onclick={nextMonth} aria-label="Bulan berikutnya" title="Bulan berikutnya">
						<Icon name="arrow" size={13} />
					</button>
				</div>
				<div class="calendar-legend">
					<span><i></i>Hadir</span>
					<span><b></b>Hari ini</span>
				</div>
			</div>

			<div class="calendar-grid day-labels">{#each dayNames as day}<span>{day}</span>{/each}</div>
			<div class="calendar-grid dates-grid" aria-busy={loading}>
				{#each calendar as cell, index (`${selectedMonth}-${index}`)}
					{#if cell}
						<button
							type="button"
							class:has-record={!!cell.item}
							class:selected={cell.item?.id === selectedHistoryId}
							class:today={cell.date === todayKey}
							class="date-cell"
							aria-label={`${formatDate(cell.date, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}${cell.item ? ', kehadiran tercatat' : ', tidak ada catatan kehadiran'}`}
							disabled={!cell.item || loading}
							onclick={() => (selectedHistoryId = cell.item?.id ?? '')}
						>
							<span>{cell.day}</span>
							{#if cell.item}<i aria-hidden="true"></i>{/if}
						</button>
					{:else}
						<span class="calendar-blank" aria-hidden="true"></span>
					{/if}
				{/each}
			</div>

			{#if dataReady && selectedHistoryId && items.find((item) => item.id === selectedHistoryId)}
				{@const selected = items.find((item) => item.id === selectedHistoryId)}
				<div class="selected-strip">
					<span class="selected-date">{formatDate(selected?.date ?? '', { weekday: 'short', day: 'numeric', month: 'short' })}</span>
					<div class="selected-times">
						<span>Masuk <strong>{selected?.checked_in_at ?? '—:—'}</strong></span>
						<span class="sep">·</span>
						<span>Pulang <strong>{selected?.checked_out_at ?? '—:—'}</strong></span>
					</div>
				</div>
			{/if}
		</section>

		{#if loading}
			<p class="status-line" role="status">Memuat riwayat…</p>
		{:else if dataReady && !visibleItems.length}
			<section class="panel empty-compact">
				<Icon name="clock" size={20} />
				<p>Belum ada catatan kehadiran pada bulan ini.</p>
			</section>
		{:else if dataReady}
			<section class="record-list" aria-label="Daftar catatan bulan ini">
				{#each visibleItems as record (record.id ?? record.date)}
					<button
						type="button"
						class="record-row"
						class:active={record.id === selectedHistoryId}
						onclick={() => (selectedHistoryId = record.id ?? '')}
					>
						<div class="date-badge">
							<small>{formatDate(record.date ?? '', { month: 'short' })}</small>
							<strong>{record.date?.slice(-2)}</strong>
						</div>
						<div class="record-main">
							<div class="record-top">
								<strong>{formatDate(record.date ?? '', { weekday: 'long' })}</strong>
								<span class="mini-tag" class:done={Boolean(record.checked_in_at && record.checked_out_at)}>
									{record.checked_in_at && record.checked_out_at ? 'Lengkap' : 'Masuk'}
								</span>
							</div>
							<p>
								<span>Masuk {record.checked_in_at ?? '—:—'}</span>
								<Icon name="arrow" size={12} />
								<span>Pulang {record.checked_out_at ?? '—:—'}</span>
							</p>
						</div>
					</button>
				{/each}
			</section>
		{/if}
	{:else if mode === 'schedule'}
		<section class="record-list" aria-label="Jadwal mendatang" aria-busy={loading}>
			{#if loading}
				<p class="status-line" role="status">Memuat jadwal…</p>
			{:else if dataReady && !visibleItems.length}
				<div class="panel empty-compact">
					<Icon name="calendar" size={20} />
					<p>Belum ada shift kerja mendatang yang terjadwal.</p>
				</div>
			{/if}
			{#each visibleItems as item (item.id ?? item.date)}
				<article class="record-row static">
					<div class="date-badge">
						<small>{formatDate(item.date ?? '', { month: 'short' })}</small>
						<strong>{item.date?.slice(-2)}</strong>
					</div>
					<div class="record-main">
						<div class="record-top">
							<strong>{item.name ?? 'Shift kerja'}</strong>
							{#if item.date === todayKey}<span class="mini-tag done">Hari ini</span>{/if}
						</div>
						<p>
							<span>{formatDate(item.date ?? '', { weekday: 'short' })} · {item.starts_at ?? '—'} – {item.ends_at ?? '—'}</span>
							{#if item.crosses_midnight}<small>(Lintas hari)</small>{/if}
						</p>
					</div>
					<a class="row-action" href="/app/requests">Izin</a>
				</article>
			{/each}
		</section>
	{:else}
		<section class="notification-section" aria-label="Daftar notifikasi" aria-busy={loading}>
			<div class="notif-toolbar">
				<div class="notif-search-box">
					<Icon name="search" size={14} />
					<input type="search" aria-label="Cari notifikasi" bind:value={search} placeholder="Cari notifikasi…" />
				</div>
				<div class="notif-pills">
					<button type="button" class:active={notificationFilter === 'all'} onclick={() => (notificationFilter = 'all')}>
						Semua ({items.length})
					</button>
					<button type="button" class:active={notificationFilter === 'unread'} onclick={() => (notificationFilter = 'unread')}>
						Belum dibaca ({unreadCount})
					</button>
					{#if dataReady && unreadCount > 0}
						<button
							type="button"
							class="mark-all-btn"
							disabled={!!busy}
							onclick={async () => {
								for (const item of items.filter((entry) => !entry.read && entry.id)) await markRead(item.id!);
							}}
						>
							Baca semua
						</button>
					{/if}
					{#if realtimeState.permission === 'default'}
						<button type="button" class="mark-all-btn" onclick={() => void requestPwaNotificationPermission()}>
							<Icon name="bell" size={13} /> Notif perangkat
						</button>
					{/if}
				</div>
			</div>

			{#if loading}
				<p class="status-line" role="status">Memuat notifikasi…</p>
			{:else if dataReady && !visibleItems.length}
				<div class="panel empty-compact">
					<Icon name="bell" size={20} />
					<p>{items.length ? 'Tidak ada notifikasi yang cocok.' : 'Belum ada notifikasi baru.'}</p>
				</div>
			{/if}

			<div class="record-list">
				{#each visibleItems as item (item.id)}
					<article class="notif-row" class:unread={!item.read}>
						<span class="notif-dot" aria-hidden="true"></span>
						<div class="notif-copy">
							<div class="record-top">
								<strong>{item.title ?? 'Notifikasi'}</strong>
								{#if !item.read}<span class="mini-tag done">Baru</span>{/if}
							</div>
							<p>{statuses[item.body ?? ''] ?? item.body}</p>
						</div>
						{#if !item.read}
							<button
								type="button"
								class="quiet notif-read-btn"
								disabled={busy === item.id || !!busy}
								onclick={() => item.id && markRead(item.id)}
							>
								{busy === item.id ? '…' : 'Dibaca'}
							</button>
						{/if}
					</article>
				{/each}
			</div>
		</section>
	{/if}
</main>

<style>
	.activity-page {
		max-width: 680px;
		display: grid;
		gap: 12px;
		align-content: start;
	}
	@media (min-width: 1024px) {
		.activity-page { max-width: none; }
	}
	.compact-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 10px;
		flex-wrap: wrap;
	}
	.header-left {
		display: flex;
		align-items: center;
		gap: 9px;
	}
	.back-btn {
		display: grid;
		place-items: center;
		width: 32px;
		height: 32px;
		border: 1px solid var(--line);
		border-radius: var(--radius-sm);
		background: var(--surface);
		color: var(--ink) !important;
		text-decoration: none;
	}
	.back-btn:hover {
		background: var(--primary-soft);
		color: var(--primary-strong) !important;
	}
	.compact-header h1 {
		margin: 0;
		font-size: 18px;
		font-weight: 800;
		letter-spacing: -0.025em;
	}
	.header-meta {
		display: flex;
		align-items: center;
		gap: 6px;
	}
	.meta-pill {
		padding: 3px 8px;
		border-radius: var(--radius-sm);
		background: var(--surface);
		border: 1px solid var(--line);
		color: var(--muted);
		font-size: 11px;
		font-weight: 700;
		font-variant-numeric: tabular-nums;
	}
	.meta-pill.strong {
		background: var(--primary-soft);
		border-color: #c8dec5;
		color: var(--primary-strong);
	}
	.action-chip {
		display: inline-flex;
		align-items: center;
		gap: 5px;
		padding: 4px 9px;
		border-radius: var(--radius-sm);
		background: var(--primary);
		color: #ffffff !important;
		font-size: 11px;
		font-weight: 700;
		text-decoration: none;
	}
	.alert-row {
		padding: 9px 11px;
		border-radius: var(--radius-sm);
		background: #fff3f0;
		border: 1px solid #ecc8c2;
		color: #8a2c24;
		font-size: 12px;
	}

	/* Compact Calendar Card */
	.calendar-panel {
		padding: 12px 14px;
		border-radius: var(--radius-lg);
		border: 1px solid var(--line);
		background: var(--surface);
		box-shadow: var(--shadow-soft);
	}
	.calendar-toolbar {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 8px;
		margin-bottom: 10px;
	}
	.month-nav-group {
		display: flex;
		align-items: center;
		gap: 6px;
	}
	.month-heading {
		margin: 0;
		font-size: 14.5px;
		font-weight: 800;
		text-transform: capitalize;
		color: var(--ink);
	}
	.month-nav-btn {
		display: grid !important;
		place-items: center !important;
		width: 26px !important;
		height: 26px !important;
		min-height: 26px !important;
		padding: 0 !important;
		border: 1px solid var(--line) !important;
		border-radius: var(--radius-sm) !important;
		background: var(--surface-alt) !important;
		color: var(--ink) !important;
		box-shadow: none !important;
	}
	.month-nav-btn:hover {
		background: var(--primary-soft) !important;
		color: var(--primary-strong) !important;
	}
	.calendar-legend {
		display: flex;
		align-items: center;
		gap: 8px;
		color: var(--muted);
		font-size: 10.5px;
		font-weight: 650;
	}
	.calendar-legend span {
		display: inline-flex;
		align-items: center;
		gap: 4px;
	}
	.calendar-legend i,
	.calendar-legend b {
		width: 6px;
		height: 6px;
		border-radius: 50%;
		display: inline-block;
	}
	.calendar-legend i {
		background: var(--primary);
	}
	.calendar-legend b {
		border: 1.5px solid var(--primary);
		background: var(--surface);
	}
	.calendar-grid {
		display: grid;
		grid-template-columns: repeat(7, minmax(0, 1fr));
		gap: 3px;
	}
	.day-labels {
		margin-bottom: 3px;
		color: var(--muted);
		font-size: 10px;
		font-weight: 700;
		text-align: center;
	}
	.day-labels span:first-child {
		color: #9e4238;
	}
	.dates-grid {
		grid-auto-rows: 32px;
	}
	.date-cell {
		position: relative;
		display: grid;
		place-items: center;
		min-width: 0;
		min-height: 32px !important;
		padding: 1px !important;
		border: 1px solid var(--line) !important;
		border-radius: var(--radius-sm) !important;
		background: var(--surface) !important;
		color: var(--ink) !important;
		box-shadow: none !important;
		font-size: 11.5px !important;
		font-weight: 650 !important;
	}
	.date-cell:disabled {
		opacity: 0.45 !important;
		cursor: default !important;
	}
	.date-cell.has-record {
		background: var(--primary-soft) !important;
		border-color: #cbe0c8 !important;
		color: var(--primary-strong) !important;
		font-weight: 800 !important;
	}
	.date-cell.selected,
	.date-cell.today {
		border-color: var(--primary) !important;
		box-shadow: inset 0 0 0 1px var(--primary) !important;
	}
	.date-cell i {
		position: absolute;
		inset-block-end: 3px;
		width: 4px;
		height: 4px;
		border-radius: 50%;
		background: var(--primary);
	}
	.calendar-blank {
		min-height: 34px;
	}
	.selected-strip {
		margin-top: 10px;
		padding: 8px 10px;
		border-radius: var(--radius-sm);
		background: var(--surface-alt);
		border: 1px solid var(--line);
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 8px;
		flex-wrap: wrap;
		font-size: 12px;
	}
	.selected-date {
		font-weight: 750;
		color: var(--ink);
		text-transform: capitalize;
	}
	.selected-times {
		display: flex;
		align-items: center;
		gap: 6px;
		color: var(--muted);
		font-variant-numeric: tabular-nums;
	}
	.selected-times strong {
		color: var(--primary-strong);
	}
	.sep {
		color: var(--line);
	}

	/* Compact Lists (History, Schedule, Notifications) */
	.record-list {
		display: grid;
		gap: 7px;
	}
	.record-row {
		display: flex;
		align-items: center;
		gap: 11px;
		width: 100%;
		padding: 10px 12px !important;
		border: 1px solid var(--line) !important;
		border-radius: var(--radius) !important;
		background: var(--surface) !important;
		color: var(--ink) !important;
		text-align: start;
		box-shadow: none !important;
	}
	.record-row:not(.static):hover,
	.record-row.active {
		border-color: var(--primary) !important;
		background: #fafdf9 !important;
	}
	.date-badge {
		display: grid;
		place-content: center;
		justify-items: center;
		width: 42px;
		height: 42px;
		flex: 0 0 42px;
		border-radius: var(--radius-sm);
		background: var(--surface-alt);
		border: 1px solid var(--line);
	}
	.date-badge small {
		font-size: 9px;
		font-weight: 800;
		color: var(--primary);
		text-transform: uppercase;
		line-height: 1;
	}
	.date-badge strong {
		font-size: 15px;
		font-weight: 800;
		color: var(--ink);
		line-height: 1.1;
		font-variant-numeric: tabular-nums;
	}
	.record-main {
		min-width: 0;
		flex: 1;
		display: grid;
		gap: 2px;
	}
	.record-top {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 8px;
	}
	.record-top strong {
		font-size: 13px;
		font-weight: 750;
		color: var(--ink);
		text-transform: capitalize;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}
	.mini-tag {
		padding: 2px 7px;
		border-radius: var(--radius-sm);
		background: var(--surface-alt);
		color: var(--muted);
		font-size: 10px;
		font-weight: 750;
		white-space: nowrap;
	}
	.mini-tag.done {
		background: var(--primary-soft);
		color: var(--primary-strong);
	}
	.record-main p {
		margin: 0;
		display: flex;
		align-items: center;
		gap: 6px;
		color: var(--muted);
		font-size: 11.5px;
		font-weight: 600;
		font-variant-numeric: tabular-nums;
	}
	.row-action {
		padding: 5px 10px;
		border-radius: var(--radius-sm);
		border: 1px solid var(--line);
		background: var(--surface-alt);
		color: var(--ink) !important;
		font-size: 11px;
		font-weight: 700;
		text-decoration: none;
	}

	/* Compact Notifications */
	.notification-section {
		display: grid;
		gap: 10px;
	}
	.notif-toolbar {
		display: grid;
		gap: 8px;
		padding: 10px 12px;
		border: 1px solid var(--line);
		border-radius: var(--radius);
		background: var(--surface);
	}
	.notif-search-box {
		display: flex;
		align-items: center;
		gap: 8px;
		padding: 0 10px;
		min-height: 34px;
		border: 1px solid var(--line);
		border-radius: var(--radius-sm);
		background: var(--surface-alt);
		color: var(--muted);
	}
	.notif-search-box input {
		width: 100%;
		min-height: 32px !important;
		border: 0 !important;
		padding: 4px 0 !important;
		background: transparent !important;
		box-shadow: none !important;
		font-size: 12.5px !important;
		outline: none;
	}
	.notif-pills {
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		gap: 6px;
	}
	.notif-pills button {
		min-height: 28px !important;
		padding: 4px 9px !important;
		border: 1px solid var(--line) !important;
		border-radius: var(--radius-sm) !important;
		background: var(--surface) !important;
		color: var(--muted) !important;
		font-size: 11px !important;
		font-weight: 700 !important;
		box-shadow: none !important;
	}
	.notif-pills button.active {
		border-color: #b7ceb4 !important;
		background: var(--primary-soft) !important;
		color: var(--primary-strong) !important;
	}
	.notif-pills .mark-all-btn {
		margin-inline-start: auto;
		display: inline-flex;
		align-items: center;
		gap: 4px;
		color: var(--primary-strong) !important;
	}
	.notif-row {
		display: flex;
		align-items: flex-start;
		gap: 10px;
		padding: 11px 12px;
		border: 1px solid var(--line);
		border-radius: var(--radius);
		background: var(--surface);
	}
	.notif-row.unread {
		border-color: #c2dac0;
		background: #fafdf9;
	}
	.notif-dot {
		width: 8px;
		height: 8px;
		margin-top: 5px;
		flex: 0 0 8px;
		border-radius: 50%;
		background: var(--line);
	}
	.notif-row.unread .notif-dot {
		background: var(--primary);
	}
	.notif-copy {
		min-width: 0;
		flex: 1;
		display: grid;
		gap: 2px;
	}
	.notif-copy p {
		margin: 0;
		font-size: 12px;
		color: var(--muted);
		line-height: 1.4;
		overflow-wrap: anywhere;
	}
	.notif-read-btn {
		min-height: 28px !important;
		padding: 4px 8px !important;
		font-size: 11px !important;
		border-radius: var(--radius-sm) !important;
	}

	.status-line {
		color: var(--muted);
		font-size: 12px;
		margin: 0;
	}
	.empty-compact {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 8px;
		padding: 20px 14px;
		border-radius: var(--radius);
		color: var(--muted);
		font-size: 12.5px;
	}
	.empty-compact p {
		margin: 0;
	}
	@media (max-width: 767px), (pointer: coarse) {
		.dates-grid { grid-auto-rows: 34px; }
		.date-cell { min-height: 34px !important; }
		.notif-pills button { min-height: 30px !important; font-size: 11px !important; }
		.notif-read-btn { min-height: 28px !important; font-size: 11px !important; }
		.notif-search-box input { min-height: 34px !important; font-size: 12.5px !important; }
		.action-chip { min-height: 30px; font-size: 11px; }
	}
</style>
