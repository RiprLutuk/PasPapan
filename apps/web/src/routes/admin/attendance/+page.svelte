<script lang="ts">
	import { onMount } from 'svelte';
	import Icon from '../../../lib/Icon.svelte';
	import { workSession } from '../../../lib/session';
	import { localeTag, uiText as t } from '../../../lib/i18n';
	const session = workSession();
	let capabilities = $derived(session.current?.capabilities ?? []);
	let canManageShifts = $derived(capabilities.includes('admin.shifts.manage'));
	let canAssign = $derived(canManageShifts && capabilities.includes('admin.schedules.manage') && capabilities.includes('admin.employees.view'));
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
				canAssign ? employees() : Promise.resolve([]),
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
		try { const shift = await createShift({ name, starts_at, ends_at, crosses_midnight }); shiftList = [...shiftList, shift]; shift_id = shift.id; name = ''; message = t('Shift disimpan.'); }
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
<main id="main-content" class="workspace-page" tabindex="-1">
<header class="page-heading">
	<div><a class="back-link" href="/admin/dashboard">← {t('Dashboard admin')}</a><p class="eyebrow">{t('TIM & OPERASIONAL')}</p><h1>{t('Shift & jadwal')}</h1><p>{t('Atur pola jam kerja dan tetapkan shift untuk karyawan.')}</p></div>
	<div class="page-actions">
		{#if canAssign}<a class="calendar-link" href="/admin/schedules"><Icon name="calendar" size={16} /> {t('Kalender bulanan')}</a>{/if}
		{#if canManageShifts}<a class="primary-link" href="#create-shift">{t('Tambah shift')}</a>{/if}
	</div>
</header>
{#if errorMessage}<p role="alert">{errorMessage} <button class="quiet" onclick={load} disabled={busy}>{t('Coba lagi')}</button></p>{/if}
{#if loading}<p class="status" role="status">{t('Memuat data shift…')}</p>{:else if canManageShifts}
	<section class="shift-overview" aria-label="Ringkasan shift" aria-busy={loading}>
		<div class="overview-heading"><div><span class="live-dot" aria-hidden="true"></span><span>{t('Direktori shift')}</span><strong>{t('Pola jam kerja perusahaan')}</strong></div><span>{errorMessage ? t('Data belum tersedia') : `${filteredShifts.length} ${t('dari')} ${shiftList.length} ${t('shift')}`}</span></div>
		<dl>{#each shiftStats as stat}<div><dt>{stat.label}</dt><dd>{errorMessage ? '—' : stat.value}</dd></div>{/each}</dl>
	</section>
	<div class="management-grid">
		<section class="panel shift-directory" aria-label="Daftar shift">
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
					{#each filteredShifts as shift (shift.id)}<tr><td><strong>{shift.name}</strong></td><td><span class="time-range">{shift.starts_at} <span aria-hidden="true">→</span> {shift.ends_at}</span><small>{shift.crosses_midnight ? t('Berakhir pada hari berikutnya') : t('Dalam hari yang sama')}</small></td><td>{durationLabel(shift)}</td><td><span class:overnight={shift.crosses_midnight} class="type-chip">{shift.crosses_midnight ? t('Lintas hari') : t('Harian')}</span></td></tr>{/each}
				</tbody></table></div>
				<div class="shift-cards">{#each filteredShifts as shift (shift.id)}<article class="shift-card"><div class="shift-card-heading"><strong>{shift.name}</strong><span class:overnight={shift.crosses_midnight} class="type-chip">{shift.crosses_midnight ? t('Lintas hari') : t('Harian')}</span></div><div class="shift-card-time"><span>{shift.starts_at}</span><span aria-hidden="true">→</span><span>{shift.ends_at}</span></div><div class="shift-card-foot"><span>{durationLabel(shift)}</span><span>{shift.crosses_midnight ? t('Hari berikutnya') : t('Hari yang sama')}</span></div></article>{/each}</div>
			{/if}
		</section>
		{#if canManageShifts}
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
					<label>Shift<select bind:value={shift_id}>{#each shiftList as shift (shift.id)}<option value={shift.id}>{shift.name} · {shift.starts_at}–{shift.ends_at}</option>{/each}</select></label>
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
.back-link{display:inline-flex;min-height:32px;align-items:center;margin-bottom:8px;color:var(--muted);font-size:12px;font-weight:700;text-decoration:none}.back-link:hover{color:var(--primary-strong)}.page-actions{display:flex;align-items:center;gap:8px}.calendar-link{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:42px;padding:9px 12px;border:1px solid var(--line);background:var(--surface);color:var(--ink)!important;font-size:12px;font-weight:700;text-decoration:none}.calendar-link:hover{background:var(--primary-soft);color:var(--primary-strong)!important}.primary-link{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:9px 14px;background:var(--accent-solid);color:#fff!important;font-size:12px;font-weight:700;text-decoration:none;box-shadow:var(--shadow-soft)}.primary-link:hover{background:var(--accent-solid-hover)}
.shift-overview{margin-bottom:18px;border:1px solid #cfe0cd;background:linear-gradient(110deg,#edf5ea 0%,#f8fbf7 72%);padding:16px 18px}.overview-heading{display:flex;align-items:center;justify-content:space-between;gap:14px}.overview-heading>div{display:flex;align-items:center;gap:8px;flex-wrap:wrap;color:var(--primary-strong);font-size:13px;font-weight:700}.overview-heading strong{color:var(--ink);font-weight:600}.overview-heading>span{color:var(--muted);font-size:12px;white-space:nowrap}.live-dot{width:8px;height:8px;background:#57944a;box-shadow:0 0 0 4px #d8ead5}.shift-overview dl{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;margin:14px 0 0}.shift-overview dl div{display:grid;gap:4px;padding:10px 12px;background:rgba(255,255,255,.82);border:1px solid #d8e5d6}.shift-overview dt{color:var(--primary-strong);font-size:10px;font-weight:750;letter-spacing:.05em;text-transform:uppercase}.shift-overview dd{margin:0;color:var(--ink);font-size:18px;font-weight:750;font-variant-numeric:tabular-nums}
.management-grid{display:grid;grid-template-columns:minmax(0,1.45fr) minmax(270px,.55fr);gap:16px;align-items:start}.shift-directory .section-heading,.create-shift .section-heading,.assignment-panel .section-heading{margin-bottom:14px}.shift-directory .section-heading h2,.create-shift h2,.assignment-panel h2{margin:0;font-size:17px}.filters{display:grid;grid-template-columns:minmax(0,1fr) minmax(160px,.55fr);gap:12px;margin-bottom:15px;padding:13px;background:var(--surface-alt);border:1px solid var(--line)}.filters label{margin:0}.shift-table-wrap{overflow-x:auto;border:1px solid var(--line)}table{width:100%;border-collapse:collapse;text-align:start;font-size:12px}thead{background:#f1f6ef;color:var(--muted)}th{padding:11px 12px;font-size:10px;font-weight:750;letter-spacing:.04em;text-transform:uppercase}td{padding:12px;border-top:1px solid var(--line);vertical-align:middle}td>strong{font-size:13px}td small{display:block;margin-top:4px;color:var(--muted);font-size:10px}.time-range{font-variant-numeric:tabular-nums;font-weight:700}.type-chip{display:inline-flex;width:max-content;padding:4px 7px;background:#eaf3e7;color:var(--primary-strong);font-size:10px;font-weight:700}.type-chip.overnight{background:#f0eefa;color:#68589d}.shift-cards{display:none}.create-shift .form-intro,.assignment-panel .form-intro{margin-bottom:16px;color:var(--muted);font-size:12px;line-height:1.55}.create-shift form{display:grid;gap:12px}.times{display:grid;grid-template-columns:1fr 1fr;gap:10px}.check{display:flex;align-items:center;gap:9px;font-size:12px}.check input{width:17px;height:17px;margin:0}.shift-preview{display:grid;gap:5px;margin-top:17px;padding:12px;background:var(--surface-alt);border-inline-start:3px solid var(--primary)}.shift-preview .eyebrow{margin:0}.shift-preview strong{font-size:13px}.shift-preview>span:last-child{color:var(--muted);font-size:11px;font-variant-numeric:tabular-nums}.assignment-panel{margin-top:16px}.assignment{display:grid;grid-template-columns:minmax(180px,1.1fr) minmax(180px,1fr) minmax(150px,.7fr) auto;align-items:end;gap:12px}.assignment button{margin-bottom:12px}.empty-note,.status{color:var(--muted);font-size:13px}.empty{padding:24px 10px;text-align:center}.empty h2{margin:0 0 6px;font-size:14px}.empty p{margin:0;color:var(--muted);font-size:12px;line-height:1.55}
.no-access h2{font-size:16px}.no-access p{color:var(--muted);font-size:13px}.no-access a{display:inline-flex;margin-top:8px;color:var(--primary-strong);font-size:12px;font-weight:700}
@media(max-width:1100px){.management-grid{grid-template-columns:minmax(0,1fr)}.create-shift{display:grid;grid-template-columns:minmax(0,.8fr) minmax(0,1.2fr);column-gap:18px}.create-shift .section-heading,.create-shift .form-intro{grid-column:1}.create-shift form,.shift-preview{grid-column:2}.create-shift form{grid-row:1/span 2}.shift-preview{margin-top:14px}.assignment{grid-template-columns:repeat(2,minmax(0,1fr))}.assignment button{margin:0}}
@media(max-width:760px){.workspace-page .page-heading{align-items:flex-start}.primary-link{position:fixed;inset-inline-end:16px;inset-block-end:calc(84px + env(safe-area-inset-bottom));z-index:10;box-shadow:0 8px 24px -12px rgba(30,51,26,.55)}.overview-heading{align-items:flex-start;flex-direction:column}.shift-overview dl{grid-template-columns:repeat(3,minmax(0,1fr))}.shift-overview dl div{padding:9px}.shift-overview dt{font-size:9px}.shift-table-wrap{display:none}.shift-cards{display:grid;gap:9px}.shift-card{padding:12px;border:1px solid var(--line);background:var(--surface)}.shift-card-heading{display:flex;align-items:center;justify-content:space-between;gap:8px}.shift-card-heading strong{font-size:13px}.shift-card-time{display:flex;align-items:center;gap:9px;margin:14px 0 10px;color:var(--primary-strong);font-size:19px;font-weight:750;font-variant-numeric:tabular-nums}.shift-card-time span:nth-child(2){font-size:13px;color:var(--muted)}.shift-card-foot{display:flex;justify-content:space-between;gap:8px;color:var(--muted);font-size:10px}.filters{grid-template-columns:minmax(0,1fr)}.create-shift{display:block}.assignment{grid-template-columns:minmax(0,1fr)}.assignment button{margin:0}.assignment-panel{margin-bottom:16px}}
</style>
