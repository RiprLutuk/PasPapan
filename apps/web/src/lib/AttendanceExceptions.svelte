<script lang="ts">
	import { onMount } from 'svelte';
	import { PageHeader, RefreshButton } from './ui';
	import { request } from './api';
	import { uiText as t } from './i18n';

	type Exception = { id:string; kind:'open_checkout'|'late_checkout'|'abnormal_duration'|'pending_correction'; full_name:string; work_date:string; occurred_at:string; detail:string };
	let entries = $state<Exception[]>([]);
	let loading = $state(true), error = $state('');
	const labels: Record<Exception['kind'], string> = {
		open_checkout: 'Checkout tertinggal', late_checkout: 'Checkout terlambat', abnormal_duration: 'Durasi abnormal', pending_correction: 'Koreksi menunggu review'
	};
	async function load() {
		loading = true; error = '';
		try { entries = await request<Exception[]>('/v1/admin/attendance-exceptions'); }
		catch (cause) { error = cause instanceof Error ? cause.message : 'Exception absensi tidak dapat dimuat.'; }
		finally { loading = false; }
	}
	onMount(load);
</script>

<main id="main-content" class="workspace-page" tabindex="-1">
	<PageHeader title="Exception absensi" description="Pantau checkout tertinggal, koreksi, dan durasi kerja yang perlu ditinjau." backHref="/admin/dashboard">{#snippet actions()}<RefreshButton loading={loading} onclick={load}/>{/snippet}</PageHeader>
	{#if error}<p class="error-state" role="alert">{error} <button class="quiet" onclick={load}>Coba lagi</button></p>{/if}
	{#if loading}<p class="loading-state" role="status">Memuat exception…</p>{:else if !entries.length}<section class="panel empty-state"><h2>Tidak ada exception aktif</h2><p>Checkout, koreksi, dan durasi kerja saat ini tidak membutuhkan tindak lanjut.</p></section>{:else}
		<section class="panel"><div class="section-heading"><div><p class="eyebrow">REVIEW ANTRIAN</p><h2>{entries.length} item membutuhkan perhatian</h2></div><a class="quiet-link" href="/app/approvals?kind=attendance_correction">Buka persetujuan</a></div>
			<div class="exception-list">{#each entries as entry (entry.kind + entry.id)}<article class="exception-row"><div><span class="badge">{labels[entry.kind]}</span><h3>{entry.full_name}</h3><p>{entry.work_date} · {entry.detail}</p></div>{#if entry.kind==='open_checkout' || entry.kind==='pending_correction'}<a class="quiet-link" href="/app/approvals?kind=attendance_correction">Tinjau</a>{/if}</article>{/each}</div>
		</section>
	{/if}
</main>

<style>
	.exception-list{display:grid;gap:10px}.exception-row{display:flex;justify-content:space-between;gap:16px;padding:14px 0;border-top:1px solid var(--border)}.exception-row:first-child{border-top:0}.exception-row h3{margin:8px 0 3px}.exception-row p{margin:0;color:var(--muted)}.badge{font-size:.75rem}.empty-state{text-align:center;padding:42px 24px}@media(max-width:640px){.exception-row{align-items:flex-start;flex-direction:column}}
</style>
