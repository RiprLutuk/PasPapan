<script lang="ts">
	import { onMount } from 'svelte';
	import { PageHeader } from '../../../lib/ui';
	import Icon from '../../../lib/Icon.svelte';
	import { workSession } from '../../../lib/session';
	import { localeTag, uiText as t } from '../../../lib/i18n';
	const session = workSession();
	let capabilities = $derived(session.current?.capabilities ?? []);
	let canManageShifts = $derived(capabilities.includes('admin.shifts.manage'));
	let canAssign = $derived(canManageShifts && capabilities.includes('admin.schedules.manage'));
	let createOpen = $state(false);
	import { assignShift, createShift, request, shifts, type Employee, type Shift } from '../../../lib/api';

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
	let search = $state('');
	let typeFilter = $state<'all' | 'daytime' | 'overnight'>('all');
	let user_id = $state('');
	let shift_id = $state('');
	let work_date = $state(localDateKey());
	let filteredShifts = $derived(shiftList
		.filter((shift) => typeFilter === 'all' || (typeFilter === 'overnight') === shift.crosses_midnight)
		.filter((shift) => shift.name.toLocaleLowerCase(localeTag()).includes(search.toLocaleLowerCase(localeTag())))
		.sort((a, b) => a.starts_at.localeCompare(b.starts_at) || a.name.localeCompare(b.name)));
	let shiftStats = $derived([
		{ label: t('Total shift'), value: shiftList.length },
		{ label: t('Shift harian'), value: shiftList.filter((shift) => !shift.crosses_midnight).length },
		{ label: t('Shift lintas hari'), value: shiftList.filter((shift) => shift.crosses_midnight).length }
	]);

	function localDateKey(date = new Date()) {
		return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
	}

	function durationLabel(shift: Shift) {
		const [startHour, startMinute] = shift.starts_at.split(':').map(Number);
		const [endHour, endMinute] = shift.ends_at.split(':').map(Number);
		const start = startHour * 60 + startMinute;
		let end = endHour * 60 + endMinute;
		if (shift.crosses_midnight || end < start) end += 24 * 60;
		const minutes = end - start;
		if (!Number.isFinite(minutes) || minutes <= 0) return t('Durasi tidak tersedia');
		const hours = Math.floor(minutes / 60);
		const remainingMinutes = minutes % 60;
		return `${hours ? `${hours} ${t('jam')}` : ''}${hours && remainingMinutes ? ' ' : ''}${remainingMinutes ? `${remainingMinutes} ${t('menit')}` : ''}`;
	}

	async function load() {
		loading = true; errorMessage = '';
		try {
			[people, shiftList] = await Promise.all([
				canAssign ? request<{members: Employee[]}>('/v1/admin/scheduling-options').then(data=>data.members) : Promise.resolve([]),
				canManageShifts ? shifts() : Promise.resolve([])
			]);
			user_id ||= people[0]?.id ?? '';
			shift_id ||= shiftList[0]?.id ?? '';
		} catch (error) { errorMessage = error instanceof Error ? error.message : t('CMS tidak dapat dimuat.'); }
		finally { loading = false; }
	}

	async function saveShift() {
		if (busy || !canManageShifts) return;
		busy = true; message = ''; errorMessage = '';
		try { const shift = await createShift({ name, starts_at, ends_at, crosses_midnight }); shiftList = [...shiftList, shift]; shift_id = shift.id; name = ''; createOpen = false; message = t('Shift disimpan.'); }
		catch (error) { errorMessage = error instanceof Error ? error.message : t('Shift tidak dapat disimpan.'); }
		finally { busy = false; }
	}

	async function saveAssignment() {
		if (busy || !canAssign) return;
		busy = true; message = ''; errorMessage = '';
		try { await assignShift({ user_id, shift_id, work_date }); message = t('Jadwal disimpan.'); }
		catch (error) { errorMessage = error instanceof Error ? error.message : t('Jadwal tidak dapat disimpan.'); }
		finally { busy = false; }
	}
	onMount(load);
</script>

<svelte:head><title>{t('Shift & jadwal')} · PasPapan</title></svelte:head>
<main id="main-content" class="workspace-page shifts-page" tabindex="-1">
<PageHeader title={t('Master Shift')} description={t('Atur pola jam kerja dan tetapkan shift untuk karyawan.')} backHref="/admin/dashboard">{#snippet actions()}{#if canAssign}<a class="calendar-link" href="/admin/schedules"><Icon name="calendar" size={15}/>{t('Kalender bulanan')}</a>{/if}{#if canManageShifts}<button class:quiet={createOpen} aria-expanded={createOpen} onclick={() => createOpen = !createOpen}><Icon name={createOpen ? 'close' : 'plus'} size={15}/>{t(createOpen ? 'Tutup' : 'Tambah shift')}</button>{/if}{/snippet}</PageHeader>
{#if errorMessage}<p role="alert">{errorMessage} <button class="quiet" onclick={load} disabled={busy}>{t('Coba lagi')}</button></p>{/if}
{#if loading}<p class="status" role="status">{t('Memuat data shift…')}</p>{:else if canManageShifts}
	<section class="shift-overview" aria-label={t('Ringkasan shift')} aria-busy={loading}>
		<dl>{#each shiftStats as stat}<div><dt>{stat.label}</dt><dd>{errorMessage ? '—' : stat.value}</dd></div>{/each}</dl>
	</section>
	<div class="management-grid" class:read-only={!createOpen}>
		<section class="panel shift-directory" aria-label={t('Daftar shift')}>
			<div class="section-heading"><div><p class="eyebrow">{t('POLA KERJA')}</p><h2>{t('Daftar shift')}</h2></div><span class="badge">{shiftList.length} {t('shift')}</span></div>
			<div class="filters">
				<label>{t('Cari shift')}<input type="search" bind:value={search} placeholder={t('Cari nama shift…')} /></label>
				<label>{t('Jenis shift')}<select bind:value={typeFilter}><option value="all">{t('Semua shift')}</option><option value="daytime">{t('Shift harian')}</option><option value="overnight">{t('Lintas hari')}</option></select></label>
			</div>
			{#if errorMessage}<div class="empty"><h2>{t('Daftar shift belum tersedia')}</h2><p>{t('Coba muat ulang untuk mengambil data terbaru.')}</p><button class="quiet" onclick={load} disabled={busy}>{t('Coba lagi')}</button></div>
			{:else if !shiftList.length}<div class="empty"><h2>{t('Belum ada shift')}</h2><p>{t('Buat pola jam kerja terlebih dahulu agar dapat digunakan pada jadwal.')}</p></div>
			{:else if !filteredShifts.length}<div class="empty"><h2>{t('Shift tidak ditemukan')}</h2><p>{t('Ubah pencarian atau pilih jenis shift lain.')}</p></div>
			{:else}
				<div class="shift-table-wrap"><table><thead><tr><th scope="col">{t('Nama shift')}</th><th scope="col">{t('Rentang waktu')}</th><th scope="col">{t('Durasi')}</th><th scope="col">{t('Jenis')}</th></tr></thead><tbody>
					{#each filteredShifts as shift (shift.id)}<tr><td><strong>{shift.name}</strong></td><td><span class="time-range">{shift.starts_at.slice(0,5)} <span aria-hidden="true">→</span> {shift.ends_at.slice(0,5)}</span><small>{shift.crosses_midnight ? t('Berakhir pada hari berikutnya') : t('Dalam hari yang sama')}</small></td><td>{durationLabel(shift)}</td><td><span class:overnight={shift.crosses_midnight} class="type-chip">{shift.crosses_midnight ? t('Lintas hari') : t('Harian')}</span></td></tr>{/each}
				</tbody></table></div>
				<div class="shift-cards">{#each filteredShifts as shift (shift.id)}<article class="shift-card"><div class="shift-card-heading"><strong>{shift.name}</strong><span class:overnight={shift.crosses_midnight} class="type-chip">{shift.crosses_midnight ? t('Lintas hari') : t('Harian')}</span></div><div class="shift-card-time"><span>{shift.starts_at.slice(0,5)}</span><span aria-hidden="true">→</span><span>{shift.ends_at.slice(0,5)}</span></div><div class="shift-card-foot"><span>{durationLabel(shift)}</span><span>{shift.crosses_midnight ? t('Hari berikutnya') : t('Hari yang sama')}</span></div></article>{/each}</div>
			{/if}
		</section>
		{#if canManageShifts && createOpen}
			<section id="create-shift" class="panel create-shift">
				<div class="section-heading"><div><p class="eyebrow">{t('POLA BARU')}</p><h2>{t('Buat shift')}</h2></div></div>
				<p class="form-intro">{t('Tentukan nama dan rentang jam yang akan digunakan saat menyusun jadwal.')}</p>
				<form onsubmit={(event) => { event.preventDefault(); void saveShift(); }}>
					<label>{t('Nama shift')}<input bind:value={name} required maxlength="120" placeholder={t('Contoh: Shift pagi')} /></label>
					<div class="times"><label>{t('Mulai')}<input bind:value={starts_at} type="time" required /></label><label>{t('Selesai')}<input bind:value={ends_at} type="time" required /></label></div>
					<label class="check"><input bind:checked={crosses_midnight} type="checkbox" />{t('Shift berakhir pada hari berikutnya')}</label>
					<button type="submit" disabled={busy || loading}>{busy ? t('Menyimpan…') : t('Simpan shift')}</button>
				</form>
				<div class="shift-preview"><span class="eyebrow">{t('PRATINJAU')}</span><strong>{name.trim() || t('Nama shift')}</strong><span>{starts_at} – {ends_at}{crosses_midnight ? ` · ${t('hari berikutnya')}` : ''}</span></div>
			</section>
		{/if}
	</div>
	{#if canAssign}
		<section class="panel assignment-panel">
			<div class="section-heading"><div><p class="eyebrow">{t('PENEMPATAN SHIFT')}</p><h2>{t('Tetapkan jadwal karyawan')}</h2></div></div>
			<p class="form-intro">{t('Pilih karyawan, pola shift, dan tanggal kerja untuk membuat penetapan.')}</p>
			{#if people.length && shiftList.length}
				<form class="assignment" onsubmit={(event) => { event.preventDefault(); void saveAssignment(); }}>
					<label>{t('Karyawan')}<select bind:value={user_id}>{#each people as person (person.id)}<option value={person.id}>{person.full_name} · {person.employee_code ?? person.email}</option>{/each}</select></label>
					<label>Shift<select bind:value={shift_id}>{#each shiftList as shift (shift.id)}<option value={shift.id}>{shift.name} · {shift.starts_at.slice(0,5)}–{shift.ends_at.slice(0,5)}</option>{/each}</select></label>
					<label>{t('Tanggal kerja')}<input bind:value={work_date} type="date" required /></label>
					<button type="submit" disabled={busy || loading}>{busy ? t('Menyimpan…') : t('Tetapkan jadwal')}</button>
				</form>
			{:else}<p class="empty-note">{people.length ? t('Buat shift terlebih dahulu untuk mulai menetapkan jadwal.') : t('Belum ada karyawan yang tersedia untuk dijadwalkan.')}</p>{/if}
		</section>
	{/if}
{:else}
	<section class="panel no-access"><p class="eyebrow">{t('AKSES TERBATAS')}</p><h2>{t('Pengelolaan shift tidak tersedia')}</h2><p>{t('Anda belum memiliki izin untuk melihat atau mengelola pola shift perusahaan.')}</p><a href="/admin/dashboard">{t('Kembali ke dashboard admin')}</a></section>
{/if}
{#if message}<p class="notice" role="status">{message}</p>{/if}
</main>

<style>
 .shifts-page{max-width:none;display:grid;gap:12px;align-content:start}.calendar-link{display:inline-flex;align-items:center;gap:6px;min-height:34px;padding:7px 10px;border:1px solid var(--line);border-radius:var(--radius-sm);color:var(--ink);background:var(--surface);font-size:11px;text-decoration:none}.calendar-link:hover{background:var(--surface-alt)}.shift-overview dl{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin:0}.shift-overview dl>div{padding:12px;background:var(--surface);border:1px solid var(--line);border-radius:var(--radius-sm)}.shift-overview dt{font-size:10px;color:var(--muted);font-weight:600}.shift-overview dd{font-size:21px;font-weight:750;margin:5px 0 0;font-variant-numeric:tabular-nums}.management-grid{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:12px;align-items:start}.management-grid.read-only{grid-template-columns:minmax(0,1fr)}.shifts-page .panel{min-width:0;padding:16px}.section-heading{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:14px}.section-heading h2{margin:0;font-size:15px}.filters{display:grid;grid-template-columns:minmax(0,1fr) minmax(160px,.35fr);gap:8px;margin-bottom:12px;padding:10px;background:var(--surface-alt);border-radius:var(--radius-sm)}.filters label{margin:0}.shift-table-wrap{overflow:auto;border:1px solid var(--line);border-radius:var(--radius-sm)}table{width:100%;border-collapse:collapse;text-align:start;font-size:12px}thead{background:var(--surface-alt)}th{padding:10px 12px;font-size:10px;font-weight:650;color:var(--muted);text-align:start}td{padding:10px 12px;border-top:1px solid var(--line)}td>strong{font-size:12px}td small{display:block;margin-top:4px;color:var(--muted);font-size:10px}.time-range{font-variant-numeric:tabular-nums;font-weight:650}.type-chip{display:inline-flex;padding:3px 7px;background:var(--primary-soft);border-radius:var(--radius-sm);color:var(--primary-strong);font-size:10px;font-weight:650}.type-chip.overnight{background:var(--surface-alt);color:var(--ink)}.shift-cards{display:none}.form-intro{font-size:12px;color:var(--muted);margin-bottom:14px!important}.create-shift form{display:grid;gap:10px}.times{display:grid;grid-template-columns:1fr 1fr;gap:8px}.check{display:flex;align-items:center;gap:8px;font-size:11px}.check input{width:16px;height:16px;margin:0}.shift-preview{display:grid;gap:5px;padding:10px;background:var(--surface-alt);border-radius:var(--radius-sm);margin-top:12px;font-size:12px}.shift-preview>span:last-child{font-size:11px;color:var(--muted)}.assignment{display:grid;grid-template-columns:minmax(150px,1fr) minmax(150px,1fr) minmax(150px,.6fr) auto;gap:10px;align-items:end}.assignment button{margin-bottom:12px}.empty,.empty-note,.status{font-size:12px;color:var(--muted)}.empty{text-align:center;padding:24px 0}.empty h2{font-size:14px}.shift-card{padding:12px;border:1px solid var(--line);border-radius:var(--radius-sm)}.shift-card-heading,.shift-card-time,.shift-card-foot{display:flex;align-items:center;justify-content:space-between;gap:8px}.shift-card-heading{font-size:13px}.shift-card-time{justify-content:start;margin:12px 0;font-size:17px;font-weight:700;font-variant-numeric:tabular-nums}.shift-card-foot{font-size:10px;color:var(--muted)}@media(max-width:1100px){.management-grid{grid-template-columns:minmax(0,1fr)}.assignment{grid-template-columns:repeat(2,minmax(0,1fr))}.assignment button{margin:0}}@media(max-width:760px){.shift-overview dl{gap:6px}.shift-overview dt{font-size:9px}.shift-table-wrap{display:none}.shift-cards{display:grid;gap:8px}.filters,.assignment{grid-template-columns:minmax(0,1fr)}}
</style>
