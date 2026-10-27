<script lang="ts">
	import { onMount } from 'svelte';
	import { request, employees, type Employee } from '../../../lib/api';
	import { workSession } from '../../../lib/session';
	const session = workSession();
	let canCreate = $derived(session.current?.capabilities.includes('admin.employees.manage') ?? false);
	type DirectoryPerson = Employee & { employment_status: 'active' | 'inactive' | 'resigned' };
	const statusLabels = { active: 'Aktif', inactive: 'Nonaktif', resigned: 'Resign' };
	let managers = $state<Employee[]>([]);
	let total = $state(0), pageNumber = $state(1), statusFilter = $state('');
	let sequence = 0;
	let people = $state<DirectoryPerson[]>([]), error = $state(''), notice = $state(''), busy = $state(false), loading = $state(true);
	let full_name = $state(''), email = $state(''), password = $state(''), employee_code = $state(''), manager_user_id = $state('');
	let search = $state('');
	let roleFilter = $state('');
	let filtered = $derived(people);
	const roles = ['owner', 'admin', 'manager', 'employee'];
	async function load() {
		const attempt = ++sequence;
		loading = true; error = ''; people = [];
		try {
			const query = new URLSearchParams({ q: search, role: roleFilter, status: statusFilter, page: String(pageNumber) });
			const result = await request<{ items: DirectoryPerson[]; total: number }>('/v1/admin/employee-directory?' + query);
			if (attempt !== sequence) return;
			people = result.items; total = result.total;
		} catch(e) { if (attempt === sequence) error = e instanceof Error ? e.message : 'Gagal memuat employee.'; }
		finally { if (attempt === sequence) loading = false; }
	}
	function applyFilters() { pageNumber = 1; void load(); }
	async function loadManagers() { try { managers = canCreate ? await employees() : []; } catch { managers = []; } }
	async function submit() {
		if (!canCreate || busy) return;
		busy = true; error = ''; notice = '';
		try {
			await request<Employee>('/v1/admin/employees', { method: 'POST', body: JSON.stringify({ full_name, email, password, employee_code, manager_user_id: manager_user_id || null }) });
			full_name = ''; email = ''; password = ''; employee_code = '';
			await load(); await loadManagers();
			notice = 'Employee tersimpan dan dapat login menggunakan kata sandi yang ditetapkan.';
		} catch(e) { error = e instanceof Error ? e.message : 'Gagal menyimpan employee.'; }
		finally { busy = false; }
	}
	onMount(() => { void load(); void loadManagers(); });
</script>
<svelte:head><title>Employee · PasPapan</title></svelte:head>
<main id="main-content" class="workspace-page employees-page" tabindex="-1">
	<header class="page-heading">
		<div><a class="back-link" href="/admin/dashboard"><span aria-hidden="true">←</span> Dashboard admin</a><p class="eyebrow">TIM & AKSES</p><h1>Manajemen karyawan</h1><p>Kelola akun kerja, peran, dan hubungan atasan langsung dalam perusahaan.</p></div>
		{#if canCreate}<a class="add-person" href="#add-employee">Tambah karyawan</a>{/if}
	</header>
	{#if error}<p role="alert">{error} <button class="quiet" onclick={load}>Coba lagi</button></p>{/if}{#if notice}<p class="notice" role="status">{notice}</p>{/if}
	<section class="directory-overview" aria-label="Ringkasan direktori" aria-busy={loading}>
		<div class="overview-heading"><div><span class="live-dot" aria-hidden="true"></span><span>Direktori karyawan</span><strong>Daftar anggota yang dapat Anda akses</strong></div><span>{loading ? 'Memuat…' : `${filtered.length} dari ${total} hasil`}</span></div>
		<dl class="directory-stats">
			<div><dt>Total hasil</dt><dd>{loading || error ? '—' : total}</dd></div>
			<div><dt>Ditampilkan</dt><dd>{loading || error ? '—' : filtered.length}</dd></div>
			<div><dt>Peran di halaman</dt><dd>{loading || error ? '—' : new Set(people.map(p => p.role)).size}</dd></div>
			<div><dt>Filter aktif</dt><dd>{(search.trim() ? 1 : 0) + (roleFilter ? 1 : 0) + (statusFilter ? 1 : 0)}</dd></div>
		</dl>
	</section>
	<div class="form-grid" class:read-only={!canCreate}>
		{#if canCreate}
		<form id="add-employee" class="panel add-form" onsubmit={(e) => { e.preventDefault(); void submit(); }}>
			<div class="section-heading"><div><p class="eyebrow">AKUN BARU</p><h2>Tambah karyawan</h2></div></div>
			<p class="form-intro">Buat akun dengan identitas kerja dan akses awal yang tepat.</p>
			<label>Nama lengkap<input bind:value={full_name} required maxlength="200" /></label>
			<label>Email kerja<input type="email" bind:value={email} required maxlength="254" /></label>
			<label>Kode karyawan<input bind:value={employee_code} required maxlength="80" /></label>
			<label>Kata sandi awal<input type="password" autocomplete="new-password" bind:value={password} required minlength="12" maxlength="1024" /></label>
			<label>Atasan<select bind:value={manager_user_id}><option value="">Belum ditetapkan</option>{#each managers as person}<option value={person.id}>{person.full_name}</option>{/each}</select></label>
			<button disabled={busy || loading}>{busy ? 'Menyimpan…' : 'Buat akun karyawan'}</button>
			<p class="form-note">Kata sandi awal harus memiliki minimal 12 karakter.</p>
		</form>
		{/if}
		<section class="panel people" aria-label="Daftar employee" aria-busy={loading}>
			<div class="section-heading directory-title"><div><p class="eyebrow">ANGGOTA PERUSAHAAN</p><h2>Direktori karyawan</h2></div><span class="badge">{loading || error ? '—' : total} hasil</span></div>
			<form class="directory-filters" onsubmit={event => { event.preventDefault(); applyFilters(); }}>
				<label class="search">Cari karyawan<input type="search" bind:value={search} maxlength="200" placeholder="Nama, email, atau kode…" /></label>
				<label class="role-filter">Peran<select bind:value={roleFilter} onchange={applyFilters}><option value="">Semua peran</option>{#each roles as role}<option value={role}>{role}</option>{/each}</select></label>
				<label>Status kerja<select bind:value={statusFilter} onchange={applyFilters}><option value="">Semua status</option>{#each Object.entries(statusLabels) as [value,label]}<option {value}>{label}</option>{/each}</select></label><button disabled={loading}>Cari</button>
			</form>
			{#if loading}<p role="status">Memuat daftar karyawan…</p>{:else if !people.length && !error}<div class="empty"><h2>{search || roleFilter || statusFilter ? 'Tidak ada hasil' : 'Belum ada karyawan'}</h2><p>{search || roleFilter || statusFilter ? 'Ubah kata kunci atau filter untuk melihat anggota lain.' : canCreate ? 'Tambahkan karyawan pertama melalui formulir.' : 'Daftar anggota perusahaan akan muncul di sini.'}</p></div>{/if}
			{#if !loading && people.length && !filtered.length}<div class="empty"><h2>Tidak ada hasil</h2><p>Coba nama, email, atau kode lain.</p></div>{/if}
			{#if filtered.length}
				<div class="people-table-wrap"><table><thead><tr><th scope="col">Karyawan</th><th scope="col">Peran</th><th scope="col">Identitas & kontak</th><th scope="col">Status</th></tr></thead><tbody>
					{#each filtered as person (person.id)}<tr><td><div class="table-person"><span class="person-avatar" aria-hidden="true">{person.full_name.slice(0, 1).toUpperCase()}</span><a href={`/admin/employees/${person.id}`}><strong>{person.full_name}</strong></a></div></td><td><span class="role-chip">{person.role}</span></td><td><span>{person.email}</span><small>{person.employee_code ?? 'Tanpa kode karyawan'}</small></td><td><span class="access-state" class:inactive={person.employment_status !== 'active'}><i aria-hidden="true"></i>{statusLabels[person.employment_status]}</span></td></tr>{/each}
				</tbody></table></div>
				<div class="people-cards">{#each filtered as person (person.id)}<article class="person-card"><div class="person-card-head"><span class="person-avatar" aria-hidden="true">{person.full_name.slice(0, 1).toUpperCase()}</span><div class="person-detail"><h2><a href={`/admin/employees/${person.id}`}>{person.full_name}</a></h2><span class="role-chip">{person.role}</span></div></div><div class="person-meta"><span>Email kerja</span><strong>{person.email}</strong></div><div class="person-meta"><span>Kode karyawan</span><strong>{person.employee_code ?? 'Tanpa kode karyawan'}</strong></div><div class="person-card-foot"><span class="access-state" class:inactive={person.employment_status !== 'active'}><i aria-hidden="true"></i>{statusLabels[person.employment_status]}</span></div></article>{/each}</div>
			{/if}
			<nav class="pagination" aria-label="Halaman direktori"><button class="quiet" disabled={loading || pageNumber === 1} onclick={() => { pageNumber--; void load(); }}>Sebelumnya</button><span>Halaman {pageNumber} / {Math.max(1,Math.ceil(total / 50))}</span><button class="quiet" disabled={loading || pageNumber * 50 >= total} onclick={() => { pageNumber++; void load(); }}>Berikutnya</button></nav>
		</section>
	</div>
</main>
<style>
	.pagination{display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-top:12px;font-size:12px}.inactive{color:var(--muted)!important}.inactive i{background:var(--muted)!important}

	.read-only{grid-template-columns:1fr!important}.employees-page .back-link{display:inline-flex;align-items:center;gap:7px;min-height:32px;margin-bottom:8px;color:var(--muted);font-size:12px;font-weight:700;text-decoration:none}.employees-page .back-link:hover{color:var(--primary-strong)}.add-person{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:9px 14px;background:var(--accent-solid);color:#fff!important;font-size:13px;font-weight:700;text-decoration:none;box-shadow:var(--shadow-soft)}.add-person:hover{background:var(--accent-solid-hover)}
	.directory-overview{margin-bottom:18px;border:1px solid #cfe0cd;background:linear-gradient(110deg,#edf5ea 0%,#f8fbf7 72%);padding:16px 18px}.overview-heading{display:flex;align-items:center;justify-content:space-between;gap:14px}.overview-heading>div{display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-size:13px;font-weight:700;color:var(--primary-strong)}.overview-heading strong{color:var(--ink);font-weight:600}.overview-heading>span{color:var(--muted);font-size:12px;white-space:nowrap}.live-dot{width:8px;height:8px;background:#57944a;box-shadow:0 0 0 4px #d8ead5}.directory-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;margin:14px 0 0}.directory-stats>div{display:grid;gap:4px;padding:10px 12px;background:rgba(255,255,255,.82);border:1px solid #d8e5d6}.directory-stats dt{color:var(--primary-strong);font-size:10px;font-weight:750;letter-spacing:.05em;text-transform:uppercase}.directory-stats dd{margin:0;color:var(--ink);font-size:18px;font-weight:750;font-variant-numeric:tabular-nums}
	.add-form .section-heading{margin-bottom:6px}.add-form h2{margin:0}.form-intro{margin-bottom:18px!important;color:var(--muted);font-size:13px;line-height:1.55}.form-note{margin-top:10px!important;color:var(--muted);font-size:11px;line-height:1.5}.directory-title{margin-bottom:14px}.directory-title .eyebrow{margin-bottom:5px}.directory-title h2{margin:0;font-size:17px}.directory-filters{display:grid;grid-template-columns:minmax(0,1fr) minmax(160px,.36fr);gap:12px;margin-bottom:16px;padding:14px;background:var(--surface-alt);border:1px solid var(--line)}.directory-filters label{margin:0}.people-table-wrap{overflow-x:auto;border:1px solid var(--line)}table{width:100%;border-collapse:collapse;text-align:start;font-size:13px}thead{background:#eff6ed;color:var(--muted)}th{padding:12px 14px;font-size:11px;font-weight:750;letter-spacing:.04em;text-transform:uppercase}td{padding:13px 14px;border-top:1px solid var(--line);vertical-align:middle}tbody tr:hover{background:#fafcf9}td>span{display:block;overflow-wrap:anywhere}td small{display:block;margin-top:4px;color:var(--muted);font-size:11px}.table-person{display:flex;align-items:center;gap:10px;min-width:200px}.table-person strong{font-size:13px}.role-chip{display:inline-flex!important;width:max-content;max-width:100%;padding:4px 8px;background:var(--primary-soft);color:var(--primary-strong);font-size:11px;font-weight:700;text-transform:capitalize}.access-state{display:inline-flex!important;align-items:center;gap:7px;color:var(--primary-strong);font-size:11px;font-weight:700;white-space:nowrap}.access-state i{width:7px;height:7px;background:#57944a}.people-cards{display:none}.person-avatar{display:grid;place-items:center;flex-shrink:0;width:40px;height:40px;background:var(--primary-soft);color:var(--primary-strong);font-size:15px;font-weight:750}.person-detail{min-width:0;flex:1}.person-detail h2{font-size:14px;margin:0 0 6px;overflow-wrap:anywhere}.person-card{padding:14px 0;border-top:1px solid var(--line)}.person-card-head{display:flex;align-items:center;gap:10px}.person-meta{display:grid;gap:3px;margin:12px 0 0;padding-inline-start:50px}.person-meta span{color:var(--muted);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.04em}.person-meta strong{overflow-wrap:anywhere;font-size:12px}.person-card-foot{margin-top:12px;padding-inline-start:50px}
	@media(max-width:1000px){.form-grid{grid-template-columns:minmax(0,1fr)!important}.add-form{order:2}.people{order:1}}
	@media(max-width:760px){.employees-page .page-heading{flex-direction:row}.employees-page .add-person{position:fixed;inset-inline-end:16px;inset-block-end:calc(84px + env(safe-area-inset-bottom));z-index:10;box-shadow:0 8px 24px -12px rgba(30,51,26,.55)}.directory-overview{padding:14px}.overview-heading{align-items:flex-start;flex-direction:column}.directory-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.directory-filters{grid-template-columns:1fr}.people-table-wrap{display:none}.people-cards{display:block}}
</style>
