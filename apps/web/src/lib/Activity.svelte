<script lang="ts">
	import { onMount } from 'svelte';
	import { request } from './api';
	let { mode }: { mode: 'schedule' | 'history' | 'notifications' } = $props();
	type Item = { id?: string; date?: string; name?: string; starts_at?: string; ends_at?: string; crosses_midnight?: boolean; checked_in_at?: string | null; checked_out_at?: string | null; title?: string; body?: string; read?: boolean };
	const titles = { schedule: 'Jadwal kerja', history: 'Riwayat kehadiran', notifications: 'Notifikasi' };
	const paths = { schedule: '/v1/schedule', history: '/v1/attendance/history', notifications: '/v1/notifications' };
	const statuses: Record<string, string> = { pending_admin: 'Menunggu persetujuan admin', approved: 'Pengajuan disetujui', rejected: 'Pengajuan ditolak' };
	let items = $state<Item[]>([]), loading = $state(true), error = $state(''), busy = $state('');
	async function load() {
		loading = true; error = '';
		try { items = await request<Item[]>(paths[mode]); }
		catch (e) { error = e instanceof Error ? e.message : 'Gagal memuat data.'; }
		finally { loading = false; }
	}
	async function markRead(id: string) {
		busy = id;
		try { await request('/v1/notifications/' + id + '/read', { method: 'POST' }); items = items.map(i => i.id === id ? { ...i, read: true } : i); }
		catch (e) { error = e instanceof Error ? e.message : 'Gagal menyimpan.'; }
		finally { busy = ''; }
	}
	onMount(load);
</script>
<svelte:head><title>{titles[mode]} · PasPapan</title></svelte:head>
<main id="main-content" class="workspace-page" tabindex="-1">
	<header class="page-heading"><div><p class="eyebrow">WORKSPACE</p><h1>{titles[mode]}</h1><p>Menampilkan maksimal 100 catatan terbaru milik Anda.</p></div><span class="badge">{items.length} catatan</span></header>
	{#if error}<p role="alert">{error} <button onclick={load}>Coba lagi</button></p>{/if}
	{#if loading}<p>Memuat…</p>{:else if !items.length && !error}<section class="panel empty"><h2>Belum ada catatan</h2><p>{mode === 'schedule' ? 'Jadwal yang ditetapkan admin akan tampil di sini.' : mode === 'history' ? 'Check-in Anda akan muncul setelah tersimpan.' : 'Perubahan status pengajuan akan tampil di sini.'}</p></section>{/if}
	{#each items as item}
		<section class="panel record" class:unread={mode === 'notifications' && !item.read}>
			{#if mode === 'notifications'}
				<h2>{item.title}</h2><p>{statuses[item.body ?? ''] ?? item.body}</p>
				{#if !item.read}<button disabled={!!busy} onclick={() => markRead(item.id!)}>Tandai dibaca</button>{:else}<small>Sudah dibaca</small>{/if}
			{:else if mode === 'schedule'}
				<h2>{item.name}</h2><p>{item.date} · {item.starts_at} – {item.ends_at}{item.crosses_midnight ? ' (+1 hari)' : ''}</p>
			{:else}
				<h2>{item.date}</h2><dl><div><dt>Masuk</dt><dd>{item.checked_in_at ?? 'Belum tercatat'}</dd></div><div><dt>Pulang</dt><dd>{item.checked_out_at ?? 'Belum tercatat'}</dd></div></dl>
			{/if}
		</section>
	{/each}
</main>
<style>
	.record{margin-bottom:10px}.record h2{margin-bottom:4px}.record p{font-size:13px;color:var(--muted);margin:4px 0 10px;overflow-wrap:anywhere}.record small{color:var(--muted);font-size:11px}.unread{border-left:3px solid var(--primary)!important}dl{display:flex;gap:32px;margin:12px 0 0;flex-wrap:wrap}dt{font-size:11px;color:var(--muted)}dd{margin:4px 0;font-size:13px;font-variant-numeric:tabular-nums}
</style>
