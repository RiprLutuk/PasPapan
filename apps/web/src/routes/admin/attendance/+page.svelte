<script lang="ts">
	import { onMount } from 'svelte';
	import { assignShift, createShift, employees, shifts, type Employee, type Shift } from '../../../lib/api';

	let people = $state<Employee[]>([]);
	let shiftList = $state<Shift[]>([]);
	let loading = $state(true);
	let message = $state('');
	let errorMessage = $state('');
	let busy = $state(false);
	let name = $state('');
	let starts_at = $state('08:00');
	let ends_at = $state('17:00');
	let crosses_midnight = $state(false);
	let user_id = $state('');
	let shift_id = $state('');
	let work_date = $state(new Date().toISOString().slice(0, 10));

	async function load() {
		loading = true; errorMessage = '';
		try {
			[people, shiftList] = await Promise.all([employees(), shifts()]);
			user_id ||= people[0]?.id ?? '';
			shift_id ||= shiftList[0]?.id ?? '';
		} catch (error) { errorMessage = error instanceof Error ? error.message : 'CMS tidak dapat dimuat.'; }
		finally { loading = false; }
	}

	async function saveShift() {
		if (busy) return;
		busy = true; message = ''; errorMessage = '';
		try { const shift = await createShift({ name, starts_at, ends_at, crosses_midnight }); shiftList = [...shiftList, shift]; shift_id = shift.id; name = ''; message = 'Shift disimpan.'; }
		catch (error) { errorMessage = error instanceof Error ? error.message : 'Shift tidak dapat disimpan.'; }
		finally { busy = false; }
	}

	async function saveAssignment() {
		if (busy) return;
		busy = true; message = ''; errorMessage = '';
		try { await assignShift({ user_id, shift_id, work_date }); message = 'Jadwal disimpan.'; }
		catch (error) { errorMessage = error instanceof Error ? error.message : 'Jadwal tidak dapat disimpan.'; }
		finally { busy = false; }
	}
	onMount(load);
</script>

<svelte:head><title>Attendance CMS · PasPapan</title></svelte:head>
<main id="main-content" class="workspace-page" tabindex="-1">
	<header class="page-heading"><div><p class="eyebrow">TIM & OPERASIONAL</p><h1>Shift & jadwal</h1><p>Buat jam kerja, lalu tetapkan shift untuk employee.</p></div><span class="badge">{shiftList.length} shift</span></header>
	{#if errorMessage}<p role="alert">{errorMessage} <button class="quiet" onclick={load} disabled={busy}>Coba lagi</button></p>{/if}
	{#if loading}<p class="status">Memuat CMS…</p>{:else}<div class="grid"><section class="panel"><h2>Daftar shift</h2>{#if shiftList.length}<ul>{#each shiftList as shift}<li><b>{shift.name}</b><span>{shift.starts_at} – {shift.ends_at}{shift.crosses_midnight ? ' (+1 hari)' : ''}</span></li>{/each}</ul>{:else if !errorMessage}<p class="muted">Belum ada shift. Tambahkan jam kerja melalui form.</p>{/if}</section><section class="panel"><h2>Buat shift</h2><form onsubmit={(event) => { event.preventDefault(); saveShift(); }}><label>Nama shift<input bind:value={name} required placeholder="Shift pagi" /></label><div class="times"><label>Mulai<input bind:value={starts_at} type="time" required /></label><label>Selesai<input bind:value={ends_at} type="time" required /></label></div><label class="check"><input bind:checked={crosses_midnight} type="checkbox" />Lewat tengah malam</label><button type="submit" disabled={busy || loading}>Simpan shift</button></form></section><section class="panel wide"><h2>Tetapkan jadwal</h2>{#if people.length && shiftList.length}<form class="assignment" onsubmit={(event) => { event.preventDefault(); saveAssignment(); }}><label>Employee<select bind:value={user_id}>{#each people as person}<option value={person.id}>{person.full_name}</option>{/each}</select></label><label>Shift<select bind:value={shift_id}>{#each shiftList as shift}<option value={shift.id}>{shift.name} · {shift.starts_at}—{shift.ends_at}</option>{/each}</select></label><label>Tanggal<input bind:value={work_date} type="date" required /></label><button type="submit" disabled={busy || loading}>Tetapkan</button></form>{:else}<p>Buat shift dan pastikan ada employee terlebih dahulu.</p>{/if}</section></div>{/if}
	{#if message}<p class="notice" role="status">{message}</p>{/if}
</main>

<style>
	.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;align-items:start}.wide{grid-column:1/-1}ul{list-style:none;padding:0;margin:0}li{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 0;border-top:1px solid var(--line);font-size:13px}li span{color:var(--muted);font-size:12px;white-space:nowrap}.times{display:grid;grid-template-columns:1fr 1fr;gap:12px}.assignment{display:grid;grid-template-columns:1.2fr 1.2fr 1fr auto;align-items:end;gap:12px}.assignment button{margin-bottom:12px}.check{display:flex;align-items:center;gap:8px}.check input{width:18px;height:18px}.status{color:var(--muted)}@media(max-width:1100px){.assignment{grid-template-columns:1fr 1fr}}@media(max-width:760px){.grid,.assignment{grid-template-columns:minmax(0,1fr)}.wide{grid-column:auto}li{flex-wrap:wrap}}
</style>
