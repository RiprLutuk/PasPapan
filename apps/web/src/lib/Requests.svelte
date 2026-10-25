<script lang="ts">
	import { PageHeader, MetricCard } from './ui';
	import { page } from '$app/state';
	import { onMount } from 'svelte';
	import { request, uploadFile, type StoredFile } from './api';
	import { workSession } from './session';

	const session = workSession();
	function canDecide(status: string) {
		return session.current?.capabilities.includes(status === 'pending_manager' ? 'requests.approvals.manager' : 'requests.approvals.admin') ?? false;
	}

	let { approvals = false }: { approvals?: boolean } = $props();

	type Entry = {
		id: string;
		kind: string;
		starts_on: string;
		ends_on: string;
		minutes?: number | null;
		reason: string;
		status: string;
		full_name?: string;
		decision_note?: string | null;
		decided_by?: string | null;
	};

	const kinds: Record<string, string> = {
		leave: 'Cuti',
		overtime: 'Lembur',
		attendance_correction: 'Koreksi absensi',
		reimbursement: 'Reimbursement'
	};

	const statuses: Record<string, string> = {
		pending_manager: 'Menunggu manager',
		pending_admin: 'Menunggu admin',
		approved: 'Disetujui',
		rejected: 'Ditolak',
		cancelled: 'Dibatalkan'
	};

	let entries = $state<Entry[]>([]);
	let loading = $state(true), busy = $state(false), error = $state(''), notice = $state('');
	let kind = $state(page.url.searchParams.get('kind') ?? 'leave'), starts_on = $state(''), ends_on = $state(''), reason = $state(''), minutes = $state<number | undefined>();
	let attachment = $state<StoredFile | null>(null);
	let uploading = $state(false);
	let notes = $state<Record<string, string>>({});
	let search = $state(''), status = $state('');

	let filtered = $derived(
		entries.filter((entry) =>
			(!status || entry.status === status) &&
			`${entry.full_name ?? ''} ${entry.reason} ${kinds[entry.kind] ?? entry.kind}`.toLocaleLowerCase('id').includes(search.toLocaleLowerCase('id'))
		)
	);

	let summaryCounts = $derived({
		total: entries.length,
		pending: entries.filter((e) => e.status === 'pending_manager' || e.status === 'pending_admin').length,
		approved: entries.filter((e) => e.status === 'approved').length,
		rejected: entries.filter((e) => e.status === 'rejected').length
	});

	function formatMinutes(value: number) {
		const hours = Math.floor(value / 60);
		const rem = value % 60;
		if (!hours) return `${rem} menit`;
		if (!rem) return `${hours} jam (${value} menit)`;
		return `${hours} jam ${rem} menit`;
	}

	async function load() {
		error = ''; loading = true;
		try { entries = await request<Entry[]>(approvals ? '/v1/approvals' : '/v1/requests'); }
		catch (e) { error = e instanceof Error ? e.message : 'Data tidak dapat dimuat.'; }
		finally { loading = false; }
	}

	async function handleFileSelect(event: Event) {
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
			attachment = await uploadFile({
				file_name: file.name,
				content_type: file.type || 'application/octet-stream',
				data_base64: dataUrl
			});
		} catch (e) {
			error = e instanceof Error ? e.message : 'Gagal mengunggah lampiran.';
		} finally {
			uploading = false;
			input.value = '';
		}
	}

	async function submit() {
		busy = true; error = ''; notice = '';
		try {
			const fullReason = attachment
				? `${reason.trim()}\n[Lampiran: ${attachment.file_name} (${attachment.download_url})]`
				: reason;
			await request('/v1/requests', {
				method: 'POST',
				body: JSON.stringify({
					kind,
					starts_on,
					ends_on,
					minutes: kind === 'overtime' ? (minutes ?? null) : null,
					reason: fullReason
				})
			});
			reason = '';
			minutes = undefined;
			attachment = null;
			notice = 'Pengajuan berhasil dikirim.';
			await load();
		} catch (e) { error = e instanceof Error ? e.message : 'Pengajuan gagal dikirim.'; }
		finally { busy = false; }
	}

	async function decide(entry: Entry, decision: string) {
		if (!canDecide(entry.status) || busy) return;
		busy = true; error = ''; notice = '';
		try {
			const stage = entry.status === 'pending_manager' ? 'manager' : 'admin';
			await request('/v1/requests/' + entry.id + '/' + stage + '-decision', { method: 'POST', body: JSON.stringify({ decision, note: notes[entry.id] || null }) });
			notice = 'Keputusan tersimpan.'; await load();
		} catch (e) { error = e instanceof Error ? e.message : 'Keputusan gagal disimpan.'; }
		finally { busy = false; }
	}

	function exportCsv() {
		const header = ['Karyawan', 'Jenis', 'Mulai', 'Selesai', 'Status', 'Alasan'];
		const rows = filtered.map((item) => [
			`"${(item.full_name ?? session.current?.user.full_name ?? '').replace(/"/g, '""')}"`,
			`"${(kinds[item.kind] ?? item.kind).replace(/"/g, '""')}"`,
			`"${item.starts_on}"`,
			`"${item.ends_on}"`,
			`"${statuses[item.status] ?? item.status}"`,
			`"${item.reason.replace(/\r?\n/g, ' ').replace(/"/g, '""')}"`
		]);
		const csv = [header.join(','), ...rows.map((r) => r.join(','))].join('\n');
		const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
		const url = URL.createObjectURL(blob);
		const link = document.createElement('a');
		link.href = url;
		link.download = `${approvals ? 'persetujuan' : 'pengajuan'}.csv`;
		link.click();
		URL.revokeObjectURL(url);
	}

	onMount(load);
</script>

<svelte:head><title>{approvals ? 'Persetujuan' : 'Pengajuan'} · PasPapan</title></svelte:head>

<main id="main-content" class="workspace-page" tabindex="-1">
	<PageHeader title={approvals ? 'Persetujuan tim' : 'Pengajuan & izin'} description={approvals ? 'Tinjau dan putuskan pengajuan anggota tim.' : 'Cuti, lembur, koreksi absensi, dan reimbursement.'}>
{#snippet actions()}{#if filtered.length > 0}<button type="button" class="quiet" onclick={exportCsv}>Ekspor CSV</button>{/if}{/snippet}
</PageHeader>

	{#if error}
		<aside role="alert">{error} <button onclick={load} disabled={busy}>Coba lagi</button></aside>
	{/if}
	{#if notice}
		<p class="notice" role="status">{notice}</p>
	{/if}

	<section class="request-kpi-strip" aria-label="Ringkasan pengajuan">
		<MetricCard label="Total" value={summaryCounts.total} loading={loading || !!error} />
		<MetricCard label="Menunggu" value={summaryCounts.pending} loading={loading || !!error} />
		<MetricCard label="Disetujui" value={summaryCounts.approved} loading={loading || !!error} />
		<MetricCard label="Ditolak" value={summaryCounts.rejected} loading={loading || !!error} />
	</section>

	<div class:review={approvals} class="form-grid">
		{#if !approvals}
			<form class="panel" onsubmit={(e) => { e.preventDefault(); void submit(); }}>
				<div class="section-heading compact">
					<h2>Pengajuan baru</h2>
					<span class="badge">Lampiran opsional</span>
				</div>
				<label>Jenis
					<select bind:value={kind} onchange={() => { if (kind !== 'overtime') minutes = undefined; }}>
						{#each Object.entries(kinds) as [value, label]}
							<option {value}>{label}</option>
						{/each}
					</select>
				</label>
				<div class="date-fields">
					<label>Mulai<input type="date" bind:value={starts_on} onchange={() => { if (starts_on && (!ends_on || ends_on < starts_on)) ends_on = starts_on; }} required /></label>
					<label>Selesai<input type="date" bind:value={ends_on} min={starts_on} required /></label>
				</div>
				{#if kind === 'overtime'}
					<label>Durasi (menit)<input type="number" min="1" step="1" bind:value={minutes} required /></label>
				{/if}
				<label>Alasan<textarea rows="3" maxlength="4000" bind:value={reason} placeholder="Jelaskan alasan atau rincian biaya…" required></textarea></label>
				<label>Lampiran berkas / bukti (opsional)
					<input type="file" onchange={handleFileSelect} disabled={busy || uploading} />
					{#if uploading}
						<small>Mengunggah berkas…</small>
					{:else if attachment}
						<small>Terunggah: <a href={attachment.download_url} target="_blank" rel="noreferrer">{attachment.file_name}</a></small>
					{/if}
				</label>
				<button disabled={busy || uploading}>{busy ? 'Menyimpan…' : 'Kirim pengajuan'}</button>
			</form>
		{/if}

		<section aria-label={approvals ? 'Daftar persetujuan' : 'Riwayat pengajuan'} aria-busy={loading}>
			<div class="filters">
				<label>
					Cari pengajuan
					<input type="search" bind:value={search} placeholder="Cari alasan, jenis, atau nama…" />
				</label>
				<label>
					Status
					<select aria-label="Status" bind:value={status}>
						<option value="">Semua status</option>
						{#each Object.entries(statuses) as [value, label]}
							<option {value}>{label}</option>
						{/each}
					</select>
				</label>
			</div>

			{#if loading}
				<p>Memuat pengajuan…</p>
			{:else if !entries.length && !error}
				<div class="panel empty">
					<h2>{approvals ? 'Tidak ada pengajuan menunggu' : 'Belum ada pengajuan'}</h2>
					<p>{approvals ? 'Pengajuan yang membutuhkan keputusan Anda akan muncul di sini.' : 'Gunakan form untuk mengirim pengajuan pertama.'}</p>
				</div>
			{:else if !filtered.length && !error}
				<div class="panel empty">
					<h2>Tidak ada hasil</h2>
					<p>Coba kata kunci atau status lain.</p>
				</div>
			{/if}

			{#each filtered as entry (entry.id)}
				<article class="panel record-card">
					<div class="heading">
						<div>
							<h2>{kinds[entry.kind] ?? entry.kind}</h2>
							{#if entry.full_name}<b class="requester-name">{entry.full_name}</b>{/if}
						</div>
						<span class="badge">{statuses[entry.status] ?? entry.status}</span>
					</div>
					<p class="date">{entry.starts_on} — {entry.ends_on}{#if entry.minutes} · Durasi {formatMinutes(entry.minutes)}{/if}</p>
					<p class="reason">{entry.reason}</p>
					{#if entry.decision_note || entry.decided_by}
						<p class="decision-meta">
							{#if entry.decided_by}<span>Ditinjau oleh <strong>{entry.decided_by}</strong></span>{/if}
							{#if entry.decision_note}<span>Catatan: {entry.decision_note}</span>{/if}
						</p>
					{/if}
					{#if approvals && canDecide(entry.status)}
						<label>Catatan keputusan
							<textarea rows="2" maxlength="4000" bind:value={notes[entry.id]} placeholder="Tambahkan catatan persetujuan/penolakan…"></textarea>
						</label>
						<div class="actions">
							<button disabled={busy} onclick={() => decide(entry, 'approved')}>
								Setujui tahap {entry.status === 'pending_manager' ? 'manager' : 'admin'}
							</button>
							<button class="reject" disabled={busy} onclick={() => decide(entry, 'rejected')}>Tolak</button>
						</div>
					{/if}
				</article>
			{/each}
		</section>
	</div>
</main>

<style>
	.request-kpi-strip { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; margin-bottom: 12px; }
	.date-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
	.date-fields label { min-width: 0; }
	@media (min-width: 761px) and (max-width: 1000px), (max-width: 360px) { .date-fields { grid-template-columns: 1fr; gap: 0; } }
	.review { grid-template-columns: 1fr !important; }
	.compact { margin-bottom: 8px; }
	.record-card { margin-bottom: 8px; }
	.heading { display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap; align-items: flex-start; margin-bottom: 4px; }
	.heading h2 { margin: 0; font-size: 14.5px; }
	.requester-name { font-size: 12px; color: var(--primary-strong); }
	.date { font-size: 11.5px; color: var(--muted); font-variant-numeric: tabular-nums; }
	.actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
	.actions .reject { background: #883d35; }
	.reason { margin-top: 6px; white-space: pre-wrap; overflow-wrap: anywhere; font-size: 12.5px; }
	.decision-meta { display: grid; gap: 2px; margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--line); color: var(--muted); font-size: 11.5px; }
	aside button { margin-left: 10px; }
	.filters { display: grid; grid-template-columns: minmax(0,1fr) minmax(130px,.55fr); gap: 10px; margin-bottom: 10px; }
	.filters label { margin-top: 0; margin-bottom: 0; }
	@media (max-width: 760px) {
		.request-kpi-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); }
		.filters { grid-template-columns: 1fr; gap: 8px; }
	}
</style>
