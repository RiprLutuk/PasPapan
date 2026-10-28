<script lang="ts">
	import { onMount } from 'svelte';
	import { PageHeader, RefreshButton } from '../../../lib/ui';
	import Icon from '../../../lib/Icon.svelte';
	import { request, employees, type Employee } from '../../../lib/api';
	import { workSession } from '../../../lib/session';
	import { uiText as t } from '../../../lib/i18n';
	const session = workSession();
	let createOpen = $state(false);
	const roleLabel = (role: string) => t(({owner:'Pemilik',admin:'Admin',manager:'Manajer',employee:'Pegawai'} as Record<string,string>)[role] ?? role);
	let canCreate = $derived(session.current?.capabilities.includes('admin.employees.manage') ?? false);
	type DirectoryPerson = Employee & { employment_status: 'active' | 'inactive' | 'resigned' };
	const statusLabels = { active: t('Aktif'), inactive: t('Nonaktif'), resigned: t('Resign') };
	let managers = $state<Employee[]>([]);
	let summary = $state<{total:number;active:number;inactive:number;resigned:number}|null>(null);
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
			const result = await request<{ items: DirectoryPerson[]; total: number; summary: typeof summary }>('/v1/admin/employee-directory?' + query);
			if (attempt !== sequence) return;
			people = result.items; total = result.total; summary = result.summary;
		} catch(e) { if (attempt === sequence) error = e instanceof Error ? e.message : t('Gagal memuat employee.'); }
		finally { if (attempt === sequence) loading = false; }
	}
	function applyFilters() { pageNumber = 1; void load(); }
	async function loadManagers() { try { managers = canCreate ? await employees() : []; } catch { managers = []; } }
	async function submit() {
		if (!canCreate || busy) return;
		busy = true; error = ''; notice = '';
		try {
			await request<Employee>('/v1/admin/employees', { method: 'POST', body: JSON.stringify({ full_name, email, password, employee_code, manager_user_id: manager_user_id || null }) });
			full_name = ''; email = ''; password = ''; employee_code = ''; createOpen = false;
			await load(); await loadManagers();
			notice = t('Employee tersimpan dan dapat login menggunakan kata sandi yang ditetapkan.');
		} catch(e) { error = e instanceof Error ? e.message : t('Gagal menyimpan employee.'); }
		finally { busy = false; }
	}
	onMount(() => { void load(); void loadManagers(); });
</script>
<svelte:head><title>{t('Employee')} · PasPapan</title></svelte:head>
<main id="main-content" class="workspace-page employees-page" tabindex="-1">
	<PageHeader title={t('Manajemen karyawan')} description={t('Kelola akun kerja, peran, dan hubungan atasan langsung dalam perusahaan.')} backHref="/admin/dashboard">{#snippet actions()}{#if canCreate}<button class:quiet={createOpen} aria-expanded={createOpen} onclick={() => { createOpen = !createOpen; password = ''; }}><Icon name={createOpen ? 'close' : 'plus'} size={15}/>{t(createOpen ? 'Tutup' : 'Tambah karyawan')}</button>{/if}{/snippet}</PageHeader>
	{#if error}<p role="alert">{error} <RefreshButton onclick={load} label={t('Coba lagi')}/></p>{/if}{#if notice}<p class="notice" role="status">{notice}</p>{/if}
	<section class="directory-overview" aria-label={t('Ringkasan direktori')} aria-busy={loading}>
		<dl class="directory-stats">
			{#each [{label:t('Total karyawan'),value:summary?.total},{label:t('Aktif'),value:summary?.active},{label:t('Nonaktif'),value:summary?.inactive},{label:t('Resign'),value:summary?.resigned}] as stat}<div><dt>{stat.label}</dt><dd>{loading || error ? '—' : stat.value ?? '—'}</dd></div>{/each}
		</dl>
	</section>
	<div class="form-grid" class:read-only={!canCreate || !createOpen}>
		{#if canCreate && createOpen}
		<form novalidate id="add-employee" class="panel add-form editor-form" onsubmit={(e) => { e.preventDefault(); void submit(); }}>
			<div class="section-heading"><div><p class="eyebrow">{t('AKUN BARU')}</p><h2>{t('Tambah karyawan')}</h2></div></div>
			<p class="form-intro">{t('Buat akun dengan identitas kerja dan akses awal yang tepat.')}</p>
			<label>{t('Nama lengkap')}<input bind:value={full_name} required maxlength="200" /></label>
			<label>{t('Email kerja')}<input type="email" bind:value={email} required maxlength="254" /></label>
			<label>{t('Kode karyawan')}<input bind:value={employee_code} required maxlength="80" /></label>
			<label>{t('Kata sandi awal')}<input type="password" autocomplete="new-password" bind:value={password} required minlength="12" maxlength="1024" /></label>
			<label>{t('Atasan')}<select bind:value={manager_user_id}><option value="">{t('Belum ditetapkan')}</option>{#each managers as person}<option value={person.id}>{person.full_name}</option>{/each}</select></label>
			<button disabled={busy || loading}>{busy ? t('Menyimpan…') : t('Buat akun karyawan')}</button>
			<p class="form-note">{t('Kata sandi awal harus memiliki minimal 12 karakter.')}</p>
		</form>
		{/if}
		<section class="panel people" aria-label={t('Direktori karyawan')} aria-busy={loading}>
			<div class="section-heading directory-title"><div><p class="eyebrow">{t('ANGGOTA PERUSAHAAN')}</p><h2>{t('Direktori karyawan')}</h2></div><span class="badge">{loading || error ? '—' : total} {t('hasil')}</span></div>
			<form novalidate class="directory-filters editor-form editor-form--embedded" onsubmit={event => { event.preventDefault(); applyFilters(); }}>
				<label class="search">{t('Cari karyawan')}<input type="search" bind:value={search} maxlength="200" placeholder={t('Nama, email, atau kode…')} /></label>
				<label class="role-filter">{t('Peran')}<select bind:value={roleFilter} onchange={applyFilters}><option value="">{t('Semua peran')}</option>{#each roles as role}<option value={role}>{roleLabel(role)}</option>{/each}</select></label>
				<label>{t('Status kerja')}<select bind:value={statusFilter} onchange={applyFilters}><option value="">{t('Semua status')}</option>{#each Object.entries(statusLabels) as [value,label]}<option {value}>{label}</option>{/each}</select></label><button disabled={loading}>{t('Cari')}</button>
			</form>
			{#if loading}<p role="status">{t('Memuat daftar karyawan…')}</p>{:else if !people.length && !error}<div class="empty"><h2>{search || roleFilter || statusFilter ? t('Tidak ada hasil') : t('Belum ada karyawan')}</h2><p>{search || roleFilter || statusFilter ? t('Ubah kata kunci atau filter untuk melihat anggota lain.') : canCreate ? t('Tambahkan karyawan pertama melalui formulir.') : t('Daftar anggota perusahaan akan muncul di sini.')}</p></div>{/if}
			{#if !loading && people.length && !filtered.length}<div class="empty"><h2>{t('Tidak ada hasil')}</h2><p>{t('Coba nama, email, atau kode lain.')}</p></div>{/if}
			{#if filtered.length}
				<div class="people-table-wrap"><table><thead><tr><th scope="col">{t('Karyawan')}</th><th scope="col">{t('Peran')}</th><th scope="col">{t('Identitas & kontak')}</th><th scope="col">{t('Status')}</th></tr></thead><tbody>
					{#each filtered as person (person.id)}<tr><td><div class="table-person"><span class="person-avatar" aria-hidden="true">{person.full_name.slice(0, 1).toUpperCase()}</span><a href={`/admin/employees/${person.id}`}><strong>{person.full_name}</strong></a></div></td><td><span class="role-chip">{roleLabel(person.role)}</span></td><td><span>{person.email}</span><small>{person.employee_code ?? t('Tanpa kode karyawan')}</small></td><td><span class="access-state" class:inactive={person.employment_status !== 'active'}><i aria-hidden="true"></i>{statusLabels[person.employment_status]}</span></td></tr>{/each}
				</tbody></table></div>
				<div class="people-cards">{#each filtered as person (person.id)}<article class="person-card"><div class="person-card-head"><span class="person-avatar" aria-hidden="true">{person.full_name.slice(0, 1).toUpperCase()}</span><div class="person-detail"><h2><a href={`/admin/employees/${person.id}`}>{person.full_name}</a></h2><span class="role-chip">{roleLabel(person.role)}</span></div></div><div class="person-meta"><span>{t('Email kerja')}</span><strong>{person.email}</strong></div><div class="person-meta"><span>{t('Kode karyawan')}</span><strong>{person.employee_code ?? t('Tanpa kode karyawan')}</strong></div><div class="person-card-foot"><span class="access-state" class:inactive={person.employment_status !== 'active'}><i aria-hidden="true"></i>{statusLabels[person.employment_status]}</span></div></article>{/each}</div>
			{/if}
			<nav class="pagination" aria-label={t('Halaman direktori')}><button class="quiet" disabled={loading || pageNumber === 1} onclick={() => { pageNumber--; void load(); }}>{t('Sebelumnya')}</button><span>{t('Halaman')} {pageNumber} / {Math.max(1,Math.ceil(total / 50))}</span><button class="quiet" disabled={loading || pageNumber * 50 >= total} onclick={() => { pageNumber++; void load(); }}>{t('Berikutnya')}</button></nav>
		</section>
	</div>
</main>
<style>
 .employees-page{max-width:none;display:grid;gap:12px;align-content:start}.employees-page .form-grid{display:grid;grid-template-columns:320px minmax(0,1fr);gap:12px;align-items:start}.employees-page .form-grid.read-only{grid-template-columns:minmax(0,1fr)}.employees-page .people,.employees-page .add-form{min-width:0;padding:16px}.directory-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin:0}.directory-stats>div{padding:12px;background:var(--surface);border:1px solid var(--line);border-radius:var(--radius-sm)}.directory-stats dt{font-size:10px;color:var(--muted);font-weight:600}.directory-stats dd{margin:5px 0 0;font-size:21px;font-weight:750;font-variant-numeric:tabular-nums}.section-heading{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:14px}.section-heading h2{font-size:15px;margin:0}.form-intro{color:var(--muted);font-size:12px;margin-bottom:14px!important}.form-note{color:var(--muted);font-size:10px;margin-top:10px!important}.directory-filters{display:grid;grid-template-columns:minmax(0,1fr) repeat(2,minmax(130px,.32fr)) auto;gap:8px;align-items:end;padding:10px;background:var(--surface-alt);border-radius:var(--radius-sm);margin-bottom:12px}.directory-filters label{margin:0}.people-table-wrap{overflow:auto;border:1px solid var(--line);border-radius:var(--radius-sm)}table{width:100%;border-collapse:collapse;text-align:start;font-size:12px}thead{background:var(--surface-alt)}th{padding:10px 12px;font-size:10px;font-weight:650;text-align:start;color:var(--muted)}td{padding:10px 12px;border-top:1px solid var(--line);vertical-align:middle}tbody tr:hover{background:var(--surface-alt)}td>span{display:block;overflow-wrap:anywhere}td small{display:block;margin-top:4px;color:var(--muted);font-size:10px}.table-person{display:flex;align-items:center;gap:10px;min-width:150px}.table-person strong{font-size:12px}.role-chip{display:inline-flex;width:max-content;padding:3px 7px;border-radius:var(--radius-sm);background:var(--surface-alt);color:var(--ink);font-size:10px;font-weight:650}.access-state{display:inline-flex;align-items:center;gap:6px;font-size:10px;font-weight:650;color:var(--primary-strong);white-space:nowrap}.access-state i{width:6px;height:6px;border-radius:50%;background:var(--primary)}.access-state.inactive{color:var(--muted)}.inactive i{background:var(--muted)}.person-avatar{display:grid;place-items:center;flex-shrink:0;width:34px;height:34px;background:var(--surface-alt);color:var(--primary-strong);border-radius:var(--radius-sm);font-size:13px;font-weight:750}.people-cards{display:none}.person-card{padding:12px 0;border-top:1px solid var(--line)}.person-card-head{display:flex;align-items:center;gap:9px}.person-detail{min-width:0;flex:1}.person-detail h2{font-size:13px;margin:0 0 5px}.person-meta{display:grid;gap:3px;margin-top:10px;padding-inline-start:43px}.person-meta span{color:var(--muted);font-size:10px}.person-meta strong{font-size:11px;overflow-wrap:anywhere}.person-card-foot{margin-top:10px;padding-inline-start:43px}.pagination{display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-top:12px;font-size:11px}.empty{padding:24px 0;text-align:center}.empty h2{font-size:14px}.empty p{font-size:12px;color:var(--muted)}@media(max-width:1100px){.employees-page .form-grid{grid-template-columns:minmax(0,1fr)}.directory-filters{grid-template-columns:minmax(0,1fr) minmax(130px,1fr)}}@media(max-width:760px){.directory-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.directory-filters{grid-template-columns:minmax(0,1fr)}.people-table-wrap{display:none}.people-cards{display:block}}
</style>
