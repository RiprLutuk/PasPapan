<script lang="ts">
	import { PageHeader, MetricCard } from './ui';
	import { modal } from './ui/modal';
	import Icon from './Icon.svelte';
	import {
		request,
		storageConfig,
		systemHealth,
		uploadFile,
		type StoredFile,
		type SystemHealthTelemetry
	} from './api';
	import { legacyFeature } from './legacy-features';
	import { featureWorkspace, type FeatureRecord } from './legacy-workspaces';
	import { getFeatureFormConfig } from './legacy-forms';
	import { realtimeState } from './realtime.svelte';

	let { pathname }: { pathname: string } = $props();
	let feature = $derived(legacyFeature(pathname));
	let workspace = $derived(featureWorkspace(pathname, feature));
	let formConfig = $derived(getFeatureFormConfig(pathname, feature.title, feature.group));
	let actionButtonLabel = $derived(
		feature.primaryAction &&
		!feature.primaryAction.toLowerCase().includes('buka') &&
		!feature.primaryAction.toLowerCase().includes('lihat') &&
		!feature.primaryAction.toLowerCase().includes('filter')
			? feature.primaryAction
			: formConfig.submitButtonText || 'Tambah data'
	);
	let search = $state('');
	let statusFilter = $state('Semua');
	let modalOpen = $state(false);
	let loading = $state(true);
	let busy = $state(false);
	let uploading = $state(false);
	let error = $state('');
	let notice = $state('');
	let draftTitle = $state('');
	let draftDetail = $state('');
	let draftAmount = $state('');
	let draftStatus = $state('Aktif');
	let uploadedAttachment = $state<StoredFile | null>(null);
	let activeStorageDriver = $state('local');
	let telemetry = $state<SystemHealthTelemetry | null>(null);
	let records = $state<FeatureRecord[]>([]);

	let statuses = $derived(['Semua', ...new Set(records.map((record) => record.status))]);
	let filtered = $derived(
		records.filter(
			(record) =>
				(statusFilter === 'Semua' || record.status === statusFilter) &&
				`${record.title} ${record.detail} ${record.status} ${record.amount ?? ''}`
					.toLocaleLowerCase('id')
					.includes(search.toLocaleLowerCase('id'))
		)
	);

	let summaryCards = $derived([
		{ label: 'Total tercatat', value: loading ? '—' : String(records.length) },
		{
			label: 'Aktif / selesai',
			value: loading
				? '—'
				: String(
						records.filter((record) =>
							/aktif|selesai|disetujui|lengkap|tercatat|published/i.test(record.status)
						).length
					)
		},
		{
			label: 'Perlu tindak lanjut',
			value: loading
				? '—'
				: String(
						records.filter((record) =>
							/menunggu|perlu|draft|diproses|belum/i.test(record.status)
						).length
					)
		}
	]);

	async function loadModule(path: string) {
		loading = true;
		error = '';
		try {
			void storageConfig()
				.then((cfg) => {
					activeStorageDriver = cfg.driver;
				})
				.catch(() => {});
			if (path === '/admin/system-info') {
				const health = await systemHealth();
				telemetry = health;
				records = health.postgres.sessions.map((session) => ({
					id: String(session.pid),
					title: `PID ${session.pid} · ${session.usename}@${session.application_name}`,
					detail: `${session.query_preview || '(idle)'} · Wait: ${session.wait_event_type}`,
					status: session.state === 'active' ? 'Aktif' : session.state,
					updated: `${session.state_duration_ms} ms`,
					amount: `${session.state_duration_ms} ms`
				}));
			} else if (path === '/admin/activity-logs') {
				records = await request<FeatureRecord[]>('/v1/admin/activity-logs');
			} else if (path === '/admin/attendances') {
				const rows = await request<
					{
						id: string;
						full_name: string;
						employee_code?: string | null;
						date: string;
						checked_in_at?: string | null;
						checked_out_at?: string | null;
						location_name: string;
						status: string;
					}[]
				>('/v1/admin/attendances');
				records = rows.map((row) => ({
					id: row.id,
					title: `${row.full_name}${row.employee_code ? ` (${row.employee_code})` : ''}`,
					detail: `${row.location_name} · Masuk ${row.checked_in_at ?? '—'} · Pulang ${row.checked_out_at ?? '—'}`,
					status: row.status,
					updated: row.date
				}));
			} else if (
				path === '/admin/payrolls' ||
				path === '/admin/payrolls/settings' ||
				path === '/payroll'
			) {
				const data = await request<{
					periods: { id: string; period_start: string; period_end: string; status: string }[];
					records: {
						id: string;
						full_name: string;
						period_start: string;
						period_end: string;
						period_status: string;
						gross_amount: number;
						deduction_amount: number;
						net_amount: number;
					}[];
				}>('/v1/payrolls');
				if (data.records.length) {
					records = data.records.map((item) => ({
						id: item.id,
						title: `${item.full_name} · ${item.period_start} s/d ${item.period_end}`,
						detail: `Gaji kotor Rp ${item.gross_amount.toLocaleString('id-ID')} · Potongan Rp ${item.deduction_amount.toLocaleString('id-ID')}`,
						status: item.period_status,
						updated: item.period_end,
						amount: `Rp ${item.net_amount.toLocaleString('id-ID')}`
					}));
				} else {
					records = data.periods.map((period) => ({
						id: period.id,
						title: `Periode ${period.period_start} s/d ${period.period_end}`,
						detail: 'Periode penggajian perusahaan',
						status: period.status,
						updated: period.period_end
					}));
				}
			} else {
				const saved = await request<FeatureRecord[]>(
					`/v1/workspace-records?module=${encodeURIComponent(path)}`
				);
				records = saved;
			}
		} catch (cause) {
			error = cause instanceof Error ? cause.message : 'Gagal memuat data modul.';
			records = [];
		} finally {
			loading = false;
		}
	}

	$effect(() => {
		const path = pathname.replace(/\/+$/, '') || '/';
		search = '';
		statusFilter = 'Semua';
		modalOpen = false;
		notice = '';
		draftStatus = formConfig.defaultStatus;
		void loadModule(path);
	});

	function openCreateModal() {
		draftTitle = '';
		draftDetail = '';
		draftAmount = '';
		draftStatus = formConfig.defaultStatus;
		uploadedAttachment = null;
		modalOpen = true;
	}

	async function handleAttachmentUpload(event: Event) {
		const input = event.currentTarget as HTMLInputElement;
		const file = input.files?.[0];
		if (!file) return;
		uploading = true;
		error = '';
		try {
			const dataUrl = await new Promise<string>((resolve, reject) => {
				const reader = new FileReader();
				reader.onload = () => resolve(String(reader.result ?? ''));
				reader.onerror = () => reject(new Error('Gagal membaca berkas.'));
				reader.readAsDataURL(file);
			});
			uploadedAttachment = await uploadFile({
				file_name: file.name,
				content_type: file.type || 'application/octet-stream',
				data_base64: dataUrl
			});
			if (!draftTitle.trim()) draftTitle = file.name;
		} catch (cause) {
			error = cause instanceof Error ? cause.message : 'Gagal mengunggah berkas.';
		} finally {
			uploading = false;
			input.value = '';
		}
	}

	async function createRecord(event: SubmitEvent) {
		event.preventDefault();
		const title = draftTitle.trim();
		if (!title || busy) return;
		busy = true;
		error = '';
		notice = '';
		const path = pathname.replace(/\/+$/, '') || '/';
		try {
			if (path === '/admin/payrolls' || path === '/admin/payrolls/settings') {
				const today = new Date().toISOString().slice(0, 10);
				await request('/v1/admin/payrolls', {
					method: 'POST',
					body: JSON.stringify({
						period_start: today.slice(0, 8) + '01',
						period_end: today,
						status: 'published'
					})
				});
			} else {
				const baseDetail = draftDetail.trim() || feature.title;
				const fullDetail = uploadedAttachment
					? `${baseDetail} · Berkas (${uploadedAttachment.driver.toUpperCase()}): ${uploadedAttachment.file_name} (${uploadedAttachment.download_url})`
					: baseDetail;
				await request('/v1/workspace-records', {
					method: 'POST',
					body: JSON.stringify({
						module_key: path,
						title,
						detail: fullDetail,
						status: draftStatus,
						amount: draftAmount.trim() || null
					})
				});
			}
			draftTitle = '';
			draftDetail = '';
			draftAmount = '';
			draftStatus = formConfig.defaultStatus;
			uploadedAttachment = null;
			modalOpen = false;
			notice = `${title} berhasil disimpan ke server.`;
			await loadModule(path);
		} catch (cause) {
			error = cause instanceof Error ? cause.message : 'Gagal menyimpan data.';
		} finally {
			busy = false;
		}
	}

	async function cycleStatus(record: FeatureRecord) {
		if (busy || pathname === '/admin/activity-logs' || pathname === '/admin/attendances') return;
		const nextStatus =
			record.status === 'Aktif'
				? 'Selesai'
				: record.status === 'Selesai'
					? 'Disetujui'
					: 'Aktif';
		busy = true;
		error = '';
		try {
			const updated = await request<FeatureRecord>(
				`/v1/workspace-records/${encodeURIComponent(record.id)}/status`,
				{
					method: 'POST',
					body: JSON.stringify({ status: nextStatus })
				}
			);
			records = records.map((item) => (item.id === record.id ? updated : item));
			notice = `Status "${record.title}" diperbarui menjadi ${nextStatus}.`;
		} catch (cause) {
			error = cause instanceof Error ? cause.message : 'Gagal memperbarui status.';
		} finally {
			busy = false;
		}
	}

	async function removeRecord(record: FeatureRecord) {
		if (busy || pathname === '/admin/activity-logs' || pathname === '/admin/attendances') return;
		busy = true;
		error = '';
		try {
			await request(`/v1/workspace-records/${encodeURIComponent(record.id)}/delete`, {
				method: 'POST'
			});
			records = records.filter((item) => item.id !== record.id);
			notice = `"${record.title}" dihapus.`;
		} catch (cause) {
			error = cause instanceof Error ? cause.message : 'Gagal menghapus data.';
		} finally {
			busy = false;
		}
	}

	function exportCsv() {
		const header = ['Judul', 'Detail', 'Status', 'Nilai/Waktu'];
		const rows = filtered.map((r) => [
			`"${r.title.replace(/"/g, '""')}"`,
			`"${r.detail.replace(/"/g, '""')}"`,
			`"${r.status.replace(/"/g, '""')}"`,
			`"${(r.amount ?? r.updated).replace(/"/g, '""')}"`
		]);
		const csv = [header.join(','), ...rows.map((r) => r.join(','))].join('\n');
		const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
		const url = URL.createObjectURL(blob);
		const link = document.createElement('a');
		link.href = url;
		link.download = `${pathname.replace(/\W+/g, '-').replace(/^-|-$/g, '') || 'data'}.csv`;
		link.click();
		URL.revokeObjectURL(url);
		notice = `Berkas CSV (${filtered.length} baris) berhasil diunduh.`;
	}
</script>

<svelte:head><title>{feature.title} · PasPapan</title></svelte:head>

<main id="main-content" class="workspace-page feature-page kind-{workspace.kind}" tabindex="-1">
	<PageHeader
		title={feature.title}
		eyebrow={workspace.domain}
		description={feature.description}
		backHref={pathname.startsWith('/admin/') ? '/admin/dashboard' : '/app/today'}
	>
		{#snippet actions()}<span class="badge">{loading ? 'Memuat…' : `${records.length} data`}</span>{/snippet}
	</PageHeader>

	{#if error}<p class="error-banner" role="alert">{error}</p>{/if}
	{#if notice}<p class="notice" role="status">{notice}</p>{/if}

	<section class="overview-grid" aria-label="Ringkasan {feature.title}" aria-busy={loading}>
		{#each summaryCards as item}
			<MetricCard label={item.label} value={item.value} loading={loading || !!error} />
		{/each}
	</section>

	{#if telemetry && pathname === '/admin/system-info'}
		<section class="panel" aria-label="PostgreSQL 18 & Resource Monitor">
			<div class="section-heading">
				<div>
					<p class="eyebrow">Real-time Telemetry · SSE {realtimeState.connected ? 'Terhubung' : 'Siaga'}</p>
					<h2>PostgreSQL {telemetry.postgres.server_version} &amp; Resource Monitor (`pg_stat_activity`)</h2>
				</div>
				<button type="button" class="quiet" onclick={() => loadModule('/admin/system-info')}>Segarkan</button>
			</div>
			<div class="overview-grid">
				<article class="overview-card">
					<span>Koneksi PG Aktif / Total</span>
					<strong>{telemetry.postgres.activity.active_queries} / {telemetry.postgres.activity.total_connections} (maks {telemetry.postgres.max_connections})</strong>
				</article>
				<article class="overview-card">
					<span>Buffer Cache Hit Ratio</span>
					<strong>{telemetry.postgres.database_stats.cache_hit_ratio_pct}%</strong>
				</article>
				<article class="overview-card">
					<span>Ukuran Database &amp; SQLx Pool</span>
					<strong>{telemetry.postgres.database_size_pretty} · Pool {telemetry.sqlx_pool.active}/{telemetry.sqlx_pool.size}</strong>
				</article>
			</div>
			<p class="muted">
				Ekstensi PostgreSQL 18 aktif:
				{#each telemetry.postgres.extensions as ext, idx}
					<code>{ext.name} ({ext.version})</code>{idx < telemetry.postgres.extensions.length - 1 ? ', ' : ''}
				{/each}
				· Transaksi commit: {telemetry.postgres.database_stats.xact_commit.toLocaleString('id-ID')} · Deadlock: {telemetry.postgres.database_stats.deadlocks}
			</p>
		</section>
	{/if}

	<div class="records-layout">
		<section class="panel records-panel">
			<div class="section-heading">
				<div>
					<p class="eyebrow">{workspace.domain}</p>
					<h2>Daftar · {feature.title}</h2>
				</div>
				<div class="heading-actions">
					{#if records.length > 0}
						<button type="button" class="quiet" onclick={exportCsv}>Ekspor CSV</button>
					{/if}
					{#if pathname !== '/admin/activity-logs' && pathname !== '/admin/attendances'}
						<button type="button" onclick={openCreateModal}>+ {actionButtonLabel}</button>
					{/if}
				</div>
			</div>

			<div class="filters">
				<label class="search">Cari data<input type="search" bind:value={search} placeholder="Ketik untuk mencari…" /></label>
				<label>Status<select bind:value={statusFilter}>{#each statuses as status}<option>{status}</option>{/each}</select></label>
			</div>

			{#if loading}
				<p class="muted" role="status">Memuat data {feature.title.toLocaleLowerCase('id')}…</p>
			{:else if error}
				<div class="empty"><h3>Data belum tersedia</h3><button type="button" class="quiet" onclick={() => loadModule(pathname)}>Coba lagi</button></div>
			{:else if !filtered.length}
				<div class="empty">
					<h3>Belum ada data pada tampilan ini</h3>
					<p>{records.length ? 'Ubah kata pencarian atau filter status.' : 'Tambahkan data pertama untuk modul ini.'}</p>
				</div>
			{:else}
				<!-- Keyboard users need a focusable scroll container to reach off-screen columns. -->
				<!-- svelte-ignore a11y_no_noninteractive_tabindex -->
				<div class="table-wrap" role="region" aria-label={`Tabel ${feature.title}, gulir untuk kolom lainnya`} tabindex="0">
					<table>
						<thead>
							<tr>
								<th>{workspace.columns[0]}</th>
								<th>{workspace.columns[1]}</th>
								<th>{workspace.columns[2]}</th>
								<th>Tindakan</th>
							</tr>
						</thead>
						<tbody>
							{#each filtered as record (record.id)}
								<tr>
									<td>
										<strong>{record.title}</strong>
										<small>{record.detail}</small>
									</td>
									<td>{record.amount ?? record.updated}</td>
									<td><span class="status-chip">{record.status}</span></td>
									<td class="row-actions">
										{#if pathname !== '/admin/activity-logs' && pathname !== '/admin/attendances'}
											<button
												type="button"
												class="quiet row-action"
												disabled={busy}
												onclick={() => cycleStatus(record)}>Ubah status</button
											>
											<button
												type="button"
												class="quiet row-action danger"
												disabled={busy}
												onclick={() => removeRecord(record)}>Hapus</button
											>
										{:else}
											<span class="muted">{record.updated}</span>
										{/if}
									</td>
								</tr>
							{/each}
						</tbody>
					</table>
				</div>
			{/if}
		</section>

		<aside class="panel context-panel">
			<p class="eyebrow">RINGKASAN MODUL</p>
			<h2>{feature.group}</h2>
			<p>{feature.description}</p>
			<div class="context-stat"><span>Tersimpan di database</span><strong>{records.length}</strong></div>
			<div class="context-stat">
				<span>Object storage</span><strong>{activeStorageDriver.toUpperCase()}</strong>
			</div>
			<div class="context-stat">
				<span>Ditampilkan</span><strong>{filtered.length}</strong>
			</div>
			{#if pathname !== '/admin/activity-logs' && pathname !== '/admin/attendances'}
				<button type="button" class="quiet context-action" onclick={openCreateModal}>
					{actionButtonLabel}<Icon name="arrow" />
				</button>
			{/if}
		</aside>
	</div>
</main>

{#if modalOpen}
		<dialog use:modal class="modal ui-dialog" aria-labelledby="modal-title" onclose={() => (modalOpen = false)}>
			<div class="section-heading">
				<div>
					<p class="eyebrow">{workspace.domain}</p>
					<h2 id="modal-title">{actionButtonLabel}</h2>
				</div>
				<button
					type="button"
					class="quiet close"
					aria-label="Tutup"
					title="Tutup"
					onclick={() => {
						modalOpen = false;
					}}><Icon name="close" /></button
				>
			</div>
			{#if formConfig.guideNote}
				<div class="form-guide-banner">
					<Icon name="face" size={16} />
					<p>{formConfig.guideNote}</p>
				</div>
			{/if}
			<form onsubmit={createRecord}>
				<label for="record-name"
					>{formConfig.titleLabel}<input
						id="record-name"
						bind:value={draftTitle}
						maxlength="200"
						required
						placeholder={formConfig.titlePlaceholder}
					/></label
				>
				<label for="record-description"
					>{formConfig.detailLabel}<textarea
						id="record-description"
						rows="3"
						bind:value={draftDetail}
						maxlength="1000"
						placeholder={formConfig.detailPlaceholder}
					></textarea></label
				>
				<div class="date-fields" class:single-field={!formConfig.showAmount}>
					<label for="record-status"
						>Status
						<select id="record-status" bind:value={draftStatus}>
							{#each formConfig.statusOptions as opt}
								<option value={opt}>{opt}</option>
							{/each}
						</select>
					</label>
					{#if formConfig.showAmount}
						<label for="record-amount"
							>{formConfig.amountLabel ?? 'Nilai / target (opsional)'}<input
								id="record-amount"
								bind:value={draftAmount}
								maxlength="80"
								placeholder={formConfig.amountPlaceholder ?? 'Contoh: Rp 1.500.000'}
							/></label
						>
					{/if}
				</div>
				<label for="record-file"
					>{formConfig.attachmentLabel}
					<input
						id="record-file"
						type="file"
						accept={formConfig.attachmentAccept}
						onchange={handleAttachmentUpload}
						disabled={busy || uploading}
					/>
					{#if uploading}
						<small class="muted">Mengunggah berkas…</small>
					{:else if uploadedAttachment}
						<small class="muted"
							>Terunggah: <a href={uploadedAttachment.download_url} target="_blank" rel="noreferrer"
								>{uploadedAttachment.file_name}</a
							></small
						>
					{:else if formConfig.attachmentHint}
						<small class="muted">{formConfig.attachmentHint}</small>
					{/if}
				</label>
				<div class="modal-actions">
					<button
						type="button"
						class="quiet"
						onclick={() => {
							modalOpen = false;
						}}>Batal</button
					>
					<button type="submit" disabled={busy}>{busy ? 'Menyimpan…' : formConfig.submitButtonText}</button>
				</div>
			</form>
		</dialog>
{/if}

<style>
	.feature-page {
		--feature-accent: var(--primary);
		max-width: 680px;
		margin-inline: auto;
		display: grid;
		gap: 12px;
		align-content: start;
	}
	@media (min-width: 1024px) {
		.feature-page { max-width: none; }
	}
	.error-banner { padding: 9px 12px; margin-bottom: 8px; border-radius: var(--radius-sm); background: #fff0ed; color: #8e2e26; font-size: 12px; }
	.overview-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 6px; margin-bottom: 4px; }
	.overview-card { display: grid; gap: 4px; min-height: 52px; padding: 8px 10px; border: 1px solid var(--line); border-radius: var(--radius-sm); background: var(--surface); }
	.overview-card span { color: var(--muted); font-size: 10.5px; font-weight: 650; }
	.overview-card strong { font-size: 16px; letter-spacing: -.03em; font-variant-numeric: tabular-nums; }
	.records-layout { display: grid; grid-template-columns: minmax(0, 1.6fr) minmax(260px, .7fr); gap: 12px; align-items: start; }
	.records-panel { min-width: 0; padding: 12px 14px !important; border-radius: var(--radius) !important; }
	.section-heading h2 { margin: 0; font-size: 14px; font-weight: 750; }
	.heading-actions { display: flex; flex-wrap: wrap; gap: 6px; }
	.heading-actions button { min-height: 32px !important; padding: 4px 10px !important; font-size: 11.5px !important; }
	.filters, .date-fields { display: grid; grid-template-columns: minmax(0, 1fr) minmax(130px, .38fr); gap: 8px; margin: 10px 0; }
	.date-fields { grid-template-columns: repeat(2, minmax(0, 1fr)); }
	.date-fields.single-field { grid-template-columns: minmax(0, 1fr); }
	.form-guide-banner {
		display: flex;
		align-items: flex-start;
		gap: 8px;
		padding: 8px 10px;
		margin-top: 8px;
		background: rgba(30, 77, 43, 0.06);
		border: 1px solid var(--line);
		border-radius: var(--radius-sm);
		font-size: 11.5px;
		line-height: 1.45;
		color: var(--foreground);
	}
	.form-guide-banner p { margin: 0; }
	.form-guide-banner :global(svg) { flex-shrink: 0; margin-top: 1px; color: var(--primary); }
	.filters label, .date-fields label { margin: 0; font-size: 11px; }
	.filters input, .filters select { min-height: 34px !important; padding: 5px 9px !important; font-size: 12px !important; }
	.table-wrap { width: 100%; overflow-x: auto; }
	table { width: 100%; min-width: 540px; border-collapse: collapse; text-align: start; font-size: 12px; }
	th { padding: 8px 6px; border-bottom: 1px solid var(--line); color: var(--muted); font-size: 9.5px; text-transform: uppercase; letter-spacing: .07em; }
	td { padding: 10px 6px; border-bottom: 1px solid var(--line); vertical-align: middle; }
	td strong, td small { display: block; }
	td small { margin-top: 2px; color: var(--muted); font-size: 10.5px; }
	.status-chip { display: inline-flex; border-radius: var(--radius-sm); padding: 3px 6px; background: var(--primary-soft); color: var(--primary-strong); font-size: 9.5px; font-weight: 700; white-space: nowrap; }
	.row-actions { display: flex; gap: 4px; justify-content: flex-end; }
	.row-action { min-height: 28px !important; padding: 3px 8px !important; font-size: 11px !important; }
	.row-action.danger { color: #8e2e26 !important; }
	.context-panel { padding: 12px 14px !important; border-radius: var(--radius) !important; }
	.context-panel h2 { margin: 2px 0 6px; font-size: 14px; font-weight: 750; }
	.context-panel > p:not(.eyebrow) { color: var(--muted); font-size: 11.5px; line-height: 1.5; }
	.context-stat { display: flex; justify-content: space-between; padding: 8px 0; border-top: 1px solid var(--line); font-size: 11.5px; }
	.context-stat:first-of-type { margin-top: 12px; }
	.context-stat span { color: var(--muted); }
	.context-action { display: flex; justify-content: space-between; width: 100%; margin-top: 10px; min-height: 32px !important; font-size: 11.5px !important; }
	.empty { padding: 24px 12px; text-align: center; }
	.empty h3 { margin: 0; font-size: 13.5px; font-weight: 750; }
	.empty p { margin-top: 4px; color: var(--muted); font-size: 11.5px; }
	.modal { width: min(calc(100% - 24px), 480px); padding: 14px 16px; border-radius: var(--radius-lg); }
	.modal form { display: grid; gap: 8px; margin-top: 8px; }
	.modal label { display: grid; gap: 4px; font-size: 11.5px; font-weight: 600; color: var(--foreground); }
	.modal input:not([type="file"]), .modal select, .modal textarea {
		width: 100%;
		font-size: 12px;
		min-height: 34px;
		padding: 6px 10px;
		border: 1px solid var(--line);
		border-radius: var(--radius-sm);
		background: var(--surface);
		color: var(--foreground);
		box-sizing: border-box;
	}
	.modal input[type="file"] { font-size: 11.5px; padding: 2px 0; }
	.modal textarea { min-height: 64px; resize: vertical; }
	.modal small.muted { font-size: 10.5px; color: var(--muted); margin-top: 2px; font-weight: normal; }
	.modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 8px; }
	.modal-actions button { min-height: 32px !important; padding: 4px 12px !important; font-size: 11.5px !important; }
	.close { min-height: 28px !important; width: 28px; padding: 3px !important; border-radius: var(--radius-sm) !important; display: grid !important; place-items: center; }
	@media (max-width: 1100px) { .records-layout { grid-template-columns: minmax(0,1fr); } }
	@media (max-width: 760px) {
		.context-panel { display: none !important; }
		.overview-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 5px; }
		.overview-card { min-height: 50px; padding: 6px 8px; }
		.overview-card strong { font-size: 15px; }
		.overview-card span { font-size: 9.5px; }
		.records-layout { grid-template-columns: minmax(0, 1fr); }
		.filters, .date-fields { grid-template-columns: minmax(0, 1fr); }
	}
</style>
