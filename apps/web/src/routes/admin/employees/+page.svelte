<script lang="ts">
	import { onMount } from 'svelte';
	import { request, employees, type Employee } from '../../../lib/api';
	let people = $state<Employee[]>([]), error = $state(''), notice = $state(''), busy = $state(false), loading = $state(true);
	let full_name = $state(''), email = $state(''), password = $state(''), employee_code = $state(''), manager_user_id = $state('');
	let search = $state('');
	let filtered = $derived(people.filter(person => `${person.full_name} ${person.email} ${person.employee_code ?? ''}`.toLocaleLowerCase('id').includes(search.toLocaleLowerCase('id'))));
	async function load() {
		loading = true; error = '';
		try { people = await employees(); }
		catch(e) { error = e instanceof Error ? e.message : 'Gagal memuat employee.'; }
		finally { loading = false; }
	}
	async function submit() {
		busy = true; error = ''; notice = '';
		try {
			const person = await request<Employee>('/v1/admin/employees', { method: 'POST', body: JSON.stringify({ full_name, email, password, employee_code, manager_user_id: manager_user_id || null }) });
			people = [...people, person]; full_name = ''; email = ''; password = ''; employee_code = '';
			notice = 'Employee tersimpan dan dapat login menggunakan kata sandi yang ditetapkan.';
		} catch(e) { error = e instanceof Error ? e.message : 'Gagal menyimpan employee.'; }
		finally { busy = false; }
	}
	onMount(load);
</script>
<svelte:head><title>Employee · PasPapan</title></svelte:head>
<main id="main-content" class="workspace-page" tabindex="-1">
	<header class="page-heading"><div><p class="eyebrow">TIM & OPERASIONAL</p><h1>Employee</h1><p>Kelola akun kerja dan atasan langsung dalam perusahaan Anda.</p></div><span class="badge">{people.length} anggota</span></header>
	{#if error}<p role="alert">{error} <button class="quiet" onclick={load}>Coba lagi</button></p>{/if}{#if notice}<p class="notice" role="status">{notice}</p>{/if}
	<div class="form-grid">
		<form class="panel" onsubmit={(e) => { e.preventDefault(); void submit(); }}>
			<h2>Tambah employee</h2>
			<label>Nama lengkap<input bind:value={full_name} required maxlength="200" /></label>
			<label>Email kerja<input type="email" bind:value={email} required maxlength="254" /></label>
			<label>Kode employee<input bind:value={employee_code} required maxlength="80" /></label>
			<label>Kata sandi awal<input type="password" autocomplete="new-password" bind:value={password} required minlength="12" maxlength="1024" /></label>
			<label>Atasan<select bind:value={manager_user_id}><option value="">Belum ditetapkan</option>{#each people.filter(p => ['owner','admin','manager'].includes(p.role)) as person}<option value={person.id}>{person.full_name}</option>{/each}</select></label>
			<button disabled={busy || loading}>{busy ? 'Menyimpan…' : 'Tambah employee'}</button>
		</form>
		<section class="panel people" aria-label="Daftar employee" aria-busy={loading}>
			<label class="search">Cari employee<input type="search" bind:value={search} placeholder="Nama, email, atau kode…" /></label>
			{#if loading}<p>Memuat employee…</p>{:else if !people.length && !error}<p>Belum ada employee.</p>{/if}
			{#if !loading && people.length && !filtered.length}<div class="empty"><h2>Tidak ada hasil</h2><p>Coba nama, email, atau kode lain.</p></div>{/if}
			{#each filtered as person (person.id)}<article><span class="person-avatar" aria-hidden="true">{person.full_name.slice(0, 1).toUpperCase()}</span><div class="person-detail"><h2>{person.full_name}</h2><p>{person.email}</p><small>{person.employee_code ?? 'Tanpa kode'}</small></div><span class="badge">{person.role}</span></article>{/each}
		</section>
	</div>
</main>
<style>
	.search{margin:0 0 16px}article{display:flex;align-items:center;gap:10px;padding:12px 0;border-top:1px solid var(--line);overflow-wrap:anywhere}article h2{font-size:13px;margin:0}article p{margin:3px 0;font-size:12px;color:var(--muted)}article small{font-size:11px;color:var(--muted)}.person-detail{min-width:0;flex:1}.person-avatar{display:grid;place-items:center;flex-shrink:0;width:32px;height:32px;border-radius:50%;background:var(--primary-soft);color:var(--primary);font-size:12px;font-weight:700}
</style>
