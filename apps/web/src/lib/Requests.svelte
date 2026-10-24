<script lang="ts">
	import { onMount } from 'svelte';
	import { request } from './api';
	let { approvals = false }: { approvals?: boolean } = $props();
	type Entry = { id: string; kind: string; starts_on: string; ends_on: string; reason: string; status: string; full_name?: string };
	const kinds: Record<string, string> = { leave: 'Cuti', overtime: 'Lembur', attendance_correction: 'Koreksi absensi', reimbursement: 'Reimbursement' };
	const statuses: Record<string, string> = { pending_manager: 'Menunggu manager', pending_admin: 'Menunggu admin', approved: 'Disetujui', rejected: 'Ditolak', cancelled: 'Dibatalkan' };
	let entries = $state<Entry[]>([]);
	let loading = $state(true), busy = $state(false), error = $state(''), notice = $state('');
	let kind = $state('leave'), starts_on = $state(''), ends_on = $state(''), reason = $state(''), minutes = $state<number | undefined>();
	let notes = $state<Record<string, string>>({});
	let search = $state(''), status = $state('');
	let filtered = $derived(entries.filter(entry => (!status || entry.status === status) && `${entry.full_name ?? ''} ${entry.reason} ${kinds[entry.kind] ?? entry.kind}`.toLocaleLowerCase('id').includes(search.toLocaleLowerCase('id'))));
	async function load() {
		error = ''; loading = true;
		try { entries = await request<Entry[]>(approvals ? '/v1/approvals' : '/v1/requests'); }
		catch (e) { error = e instanceof Error ? e.message : 'Data tidak dapat dimuat.'; }
		finally { loading = false; }
	}
	async function submit() {
		busy = true; error = ''; notice = '';
		try {
			await request('/v1/requests', { method: 'POST', body: JSON.stringify({ kind, starts_on, ends_on, minutes: minutes ?? null, reason }) });
			reason = ''; notice = 'Pengajuan berhasil dikirim.'; await load();
		} catch (e) { error = e instanceof Error ? e.message : 'Pengajuan gagal dikirim.'; }
		finally { busy = false; }
	}
	async function decide(entry: Entry, decision: string) {
		busy = true; error = ''; notice = '';
		try {
			const stage = entry.status === 'pending_manager' ? 'manager' : 'admin';
			await request('/v1/requests/' + entry.id + '/' + stage + '-decision', { method: 'POST', body: JSON.stringify({ decision, note: notes[entry.id] || null }) });
			notice = 'Keputusan tersimpan.'; await load();
		} catch (e) { error = e instanceof Error ? e.message : 'Keputusan gagal disimpan.'; }
		finally { busy = false; }
	}
	onMount(load);
</script>

<svelte:head><title>{approvals ? 'Persetujuan' : 'Pengajuan'} · PasPapan</title></svelte:head>
<main id="main-content" class="workspace-page" tabindex="-1">
	<header class="page-heading"><div><p class="eyebrow">{approvals ? 'TIM' : 'WORKSPACE'}</p><h1>{approvals ? 'Inbox persetujuan' : 'Pengajuan saya'}</h1><p>{approvals ? 'Periksa alasan dan tanggal sebelum mengambil keputusan.' : 'Ajukan cuti, lembur, koreksi absensi, atau reimbursement.'}</p></div></header>
	{#if error}<aside role="alert">{error} <button onclick={load} disabled={busy}>Coba muat ulang</button></aside>{/if}
	{#if notice}<p class="notice" role="status">{notice}</p>{/if}
	<div class:review={approvals} class="form-grid">
		{#if !approvals}
			<form class="panel" onsubmit={(e) => { e.preventDefault(); void submit(); }}>
				<h2>Pengajuan baru</h2>
				<label>Jenis<select bind:value={kind}>{#each Object.entries(kinds) as [value, label]}<option {value}>{label}</option>{/each}</select></label>
				<label>Tanggal mulai<input type="date" bind:value={starts_on} required /></label>
				<label>Tanggal selesai<input type="date" bind:value={ends_on} min={starts_on} required /></label>
				{#if kind === 'overtime'}<label>Durasi (menit)<input type="number" min="1" step="1" bind:value={minutes} required /></label>{/if}
				<label>Alasan<textarea rows="4" maxlength="4000" bind:value={reason} required></textarea></label>
				<button disabled={busy}>{busy ? 'Menyimpan…' : 'Kirim pengajuan'}</button>
			</form>
		{/if}
		<section aria-label={approvals ? 'Daftar persetujuan' : 'Riwayat pengajuan'} aria-busy={loading}>
			{#if loading}<p>Memuat pengajuan…</p>{:else if !entries.length && !error}<div class="panel empty"><h2>{approvals ? 'Tidak ada pengajuan menunggu' : 'Belum ada pengajuan'}</h2><p>{approvals ? 'Pengajuan yang membutuhkan keputusan Anda akan muncul di sini.' : 'Gunakan form untuk mengirim pengajuan pertama.'}</p></div>{/if}
			<div class="filters"><label>Cari pengajuan<input type="search" bind:value={search} placeholder="Cari alasan atau nama…" /></label><label>Status<select aria-label="Status" bind:value={status}><option value="">Semua status</option>{#each Object.entries(statuses) as [value, label]}<option {value}>{label}</option>{/each}</select></label></div>
			{#if !loading && entries.length && !filtered.length}<div class="panel empty"><h2>Tidak ada hasil</h2><p>Coba kata kunci atau status lain.</p></div>{/if}
			{#each filtered as entry (entry.id)}
				<article class="panel">
					<div class="heading"><h2>{kinds[entry.kind] ?? entry.kind}</h2><span class="badge">{statuses[entry.status] ?? entry.status}</span></div>
					{#if entry.full_name}<b>{entry.full_name}</b>{/if}
					<p class="date">{entry.starts_on} — {entry.ends_on}</p><p class="reason">{entry.reason}</p>
					{#if approvals}
						<label>Catatan keputusan<textarea rows="2" maxlength="4000" bind:value={notes[entry.id]}></textarea></label>
						<div class="actions"><button disabled={busy} onclick={() => decide(entry, 'approved')}>Setujui tahap {entry.status === 'pending_manager' ? 'manager' : 'admin'}</button><button class="reject" disabled={busy} onclick={() => decide(entry, 'rejected')}>Tolak</button></div>
					{/if}
				</article>
			{/each}
		</section>
	</div>
</main>
<style>
	.review{grid-template-columns:1fr!important}article{margin-bottom:10px}.heading{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center}.heading h2{margin:0}.date{font-size:12px;color:var(--muted)}.actions{display:flex;gap:8px;flex-wrap:wrap}.actions .reject{background:#883d35}.reason{white-space:pre-wrap;overflow-wrap:anywhere;font-size:13px}aside button{margin-left:10px}.filters{display:grid;grid-template-columns:minmax(0,1fr) minmax(130px,.6fr);gap:10px;margin-bottom:12px}.filters label{margin-top:0;margin-bottom:0}
</style>
