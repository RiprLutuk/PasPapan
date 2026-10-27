<script lang="ts">
	import { onMount } from 'svelte';
	import Icon from '../../../lib/Icon.svelte';
	import {
		assignShift,
		deleteShiftAssignment,
		employees,
		shiftAssignments,
		shifts,
		type Employee,
		type Shift,
		type ShiftAssignment
	} from '../../../lib/api';
	import { workSession } from '../../../lib/session';
	import { localeTag, uiText as t } from '../../../lib/i18n';

	type CalendarDay = {
		date: string;
		day: number;
		inMonth: boolean;
		today: boolean;
		assignment?: ShiftAssignment;
	};

	const session = workSession();
	let capabilities = $derived(session.current?.capabilities ?? []);
	let canManageSchedules = $derived(
		capabilities.includes('admin.schedules.manage') &&
			capabilities.includes('admin.shifts.manage') &&
			capabilities.includes('admin.employees.view')
	);

	const dayNames = Array.from({ length: 7 }, (_, day) => new Intl.DateTimeFormat(localeTag(), { weekday: 'short' }).format(new Date(2026, 10, 1 + day)));
	const today = new Date();
	const todayKey = dateKey(today);
	const monthFormatter = new Intl.DateTimeFormat(localeTag(), { month: 'long', year: 'numeric' });
	const dateFormatter = new Intl.DateTimeFormat(localeTag(), {
		weekday: 'long',
		day: 'numeric',
		month: 'long',
		year: 'numeric'
	});

	let employeeList = $state<Employee[]>([]);
	let shiftList = $state<Shift[]>([]);
	let assignments = $state<Record<string, ShiftAssignment>>({});
	let selectedMonth = $state(todayKey.slice(0, 7));
	let selectedEmployeeId = $state('');
	let selectedDate = $state('');
	let selectedShiftId = $state('');
	let selectedClear = $state(false);
	let showDialog = $state(false);
	let loading = $state(true);
	let loadingSchedule = $state(false);
	let saving = $state(false);
	let error = $state('');
	let feedback = $state('');
	let dialogElement = $state<HTMLDialogElement>();
	let scheduleSequence = 0;

	let [year, month] = $derived(selectedMonth.split('-').map(Number));
	let monthLabel = $derived(
		year && month ? monthFormatter.format(new Date(year, month - 1, 1)) : selectedMonth
	);
	let calendar = $derived.by((): CalendarDay[] => {
		if (!year || !month) return [];
		const firstWeekday = new Date(year, month - 1, 1).getDay();
		return Array.from({ length: 42 }, (_, index) => {
			const date = new Date(year, month - 1, index - firstWeekday + 1);
			const key = dateKey(date);
			return {
				date: key,
				day: date.getDate(),
				inMonth: date.getMonth() === month - 1,
				today: key === todayKey,
				assignment: assignments[key]
			};
		});
	});
	let selectedEmployee = $derived(
		employeeList.find((employee) => employee.id === selectedEmployeeId)
	);
	let selectedDateLabel = $derived(
		selectedDate ? dateFormatter.format(dateFromKey(selectedDate)) : ''
	);
	let assignedCount = $derived(Object.keys(assignments).length);

	$effect(() => {
		if (!dialogElement) return;
		if (showDialog && !dialogElement.open) dialogElement.showModal();
		else if (!showDialog && dialogElement.open) dialogElement.close();
	});

	function dateKey(date: Date) {
		return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
	}

	function dateFromKey(value: string) {
		const [year, month, day] = value.split('-').map(Number);
		return new Date(year, month - 1, day);
	}

	async function loadInitial() {
		loading = true;
		error = '';
		try {
			const [loadedEmployees, loadedShifts] = await Promise.all([
				capabilities.includes('admin.employees.view') ? employees() : Promise.resolve([]),
				capabilities.includes('admin.shifts.manage') ? shifts() : Promise.resolve([])
			]);
			employeeList = loadedEmployees;
			shiftList = loadedShifts;
			if (!selectedEmployeeId && loadedEmployees.length) {
				selectedEmployeeId = loadedEmployees[0].id;
			}
			if (selectedEmployeeId) {
				await loadMonthAssignments(selectedEmployeeId, selectedMonth);
			}
		} catch (cause) {
			error = cause instanceof Error ? cause.message : t('Data penjadwalan tidak dapat dimuat.');
		} finally {
			loading = false;
		}
	}

	async function loadMonthAssignments(userId: string, monthKey: string) {
		if (!userId || !monthKey || !capabilities.includes('admin.schedules.manage')) {
			assignments = {};
			return;
		}
		const attempt = ++scheduleSequence;
		loadingSchedule = true;
		error = '';
		try {
			const list = await shiftAssignments(userId, monthKey);
			if (attempt !== scheduleSequence) return;
			assignments = Object.fromEntries(list.map((entry) => [entry.work_date, entry]));
		} catch (cause) {
			if (attempt !== scheduleSequence) return;
			error = cause instanceof Error ? cause.message : t('Jadwal bulanan tidak dapat dimuat.');
		} finally {
			if (attempt === scheduleSequence) loadingSchedule = false;
		}
	}

	function openDay(day: CalendarDay) {
		if (!canManageSchedules || !selectedEmployeeId) return;
		selectedDate = day.date;
		const existing = assignments[day.date];
		selectedShiftId = existing?.shift_id ?? shiftList[0]?.id ?? '';
		selectedClear = false;
		feedback = '';
		error = '';
		showDialog = true;
	}

	function changeMonth(offset: number) {
		const date = new Date(year, month - 1 + offset, 1);
		selectedMonth = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
		feedback = '';
		void loadMonthAssignments(selectedEmployeeId, selectedMonth);
	}

	async function saveSchedule() {
		if (!selectedDate || !selectedEmployeeId || saving || !canManageSchedules) return;
		saving = true;
		error = '';
		feedback = '';
		try {
			if (selectedClear || !selectedShiftId) {
				await deleteShiftAssignment({
					user_id: selectedEmployeeId,
					work_date: selectedDate
				});
				feedback = `${t('Penugasan shift')} ${selectedEmployee?.full_name ?? t('karyawan')} ${t('pada')} ${selectedDateLabel} ${t('dihapus.')}`;
			} else {
				await assignShift({
					user_id: selectedEmployeeId,
					shift_id: selectedShiftId,
					work_date: selectedDate
				});
				feedback = `${t('Jadwal')} ${selectedEmployee?.full_name ?? t('karyawan')} ${t('pada')} ${selectedDateLabel} ${t('berhasil disimpan.')}`;
			}
			showDialog = false;
			await loadMonthAssignments(selectedEmployeeId, selectedMonth);
		} catch (cause) {
			error = cause instanceof Error ? cause.message : t('Perubahan jadwal gagal disimpan.');
		} finally {
			saving = false;
		}
	}

	onMount(() => {
		void loadInitial();
	});
</script>

<svelte:head><title>{t('Penjadwalan tim')} · PasPapan</title></svelte:head>

<main id="main-content" class="workspace-page schedule-page" tabindex="-1">
	<header class="page-heading">
		<div>
			<a class="back-link" href="/admin/attendance"><Icon name="back" size={16} /> {t('Shift & pola kerja')}</a>
			<p class="eyebrow">{t('TIM & OPERASIONAL')}</p>
			<h1>{t('Penjadwalan tim')}</h1>
			<p>{t('Atur shift kerja dan hari tanpa penugasan karyawan dalam kalender bulanan.')}</p>
		</div>
		<a class="quiet-link" href="/admin/attendance"><Icon name="calendar" size={17} /> {t('Kelola pola shift')}</a>
	</header>

	{#if error}
		<p role="alert">{error} <button class="quiet" onclick={loadInitial} disabled={loading || saving}>{t('Coba lagi')}</button></p>
	{/if}
	{#if feedback}<p class="notice" role="status">{feedback}</p>{/if}

	{#if loading}
		<p role="status">{t('Memuat kalender penjadwalan…')}</p>
	{:else if !employeeList.length}
		<section class="panel empty">
			<h2>{t('Belum ada karyawan aktif')}</h2>
			<p>{t('Tambahkan karyawan terlebih dahulu sebelum menyusun jadwal bulanan.')}</p>
			<a class="quiet-link" href="/admin/employees">{t('Buka direktori karyawan')}</a>
		</section>
	{:else}
		<section class="calendar-toolbar panel" aria-label={t('Pilihan kalender')}>
			<label>
				{t('Karyawan')}
				<select
					bind:value={selectedEmployeeId}
					onchange={() => void loadMonthAssignments(selectedEmployeeId, selectedMonth)}
				>
					{#each employeeList as employee (employee.id)}
						<option value={employee.id}>{employee.full_name} · {employee.employee_code ?? employee.email}</option>
					{/each}
				</select>
			</label>
			<label>
				{t('Bulan')}
				<input
					type="month"
					bind:value={selectedMonth}
					onchange={() => void loadMonthAssignments(selectedEmployeeId, selectedMonth)}
				/>
			</label>
			<div class="month-controls" aria-label={t('Navigasi bulan')}>
				<button class="quiet icon-button" aria-label={t('Bulan sebelumnya')} onclick={() => changeMonth(-1)}><Icon name="back" size={17} /></button>
				<button class="quiet current-month" onclick={() => { selectedMonth = todayKey.slice(0, 7); void loadMonthAssignments(selectedEmployeeId, selectedMonth); }}>{t('Bulan ini')}</button>
				<button class="quiet icon-button" aria-label={t('Bulan berikutnya')} onclick={() => changeMonth(1)}><Icon name="arrow" size={17} /></button>
			</div>
		</section>

		<section class="panel calendar-panel" aria-label={`Jadwal ${selectedEmployee?.full_name ?? ''}, ${monthLabel}`} aria-busy={loadingSchedule}>
			<div class="calendar-title">
				<div>
					<p class="eyebrow">{t('KALENDER KERJA')}</p>
					<h2>{monthLabel}</h2>
					<p>{selectedEmployee?.full_name ?? t('Karyawan')} · {loadingSchedule ? t('Memuat jadwal…') : `${assignedCount} ${t('hari terjadwal')}`}</p>
				</div>
			<span class="badge">{assignedCount} {t('shift')}</span>
			</div>
			<div class="week-grid day-headings">{#each dayNames as day, index}<span class:weekend={index === 0 || index === 6}>{day}</span>{/each}</div>
			<div class="week-grid calendar-days">
				{#each calendar as day (day.date)}
					<button
						class="calendar-day"
						class:outside={!day.inMonth}
						class:today={day.today}
						class:has-shift={!!day.assignment}
						disabled={!canManageSchedules || loadingSchedule}
						aria-label={`${t('Atur jadwal')} ${dateFormatter.format(dateFromKey(day.date))}${day.assignment ? `, ${day.assignment.shift_name}` : `, ${t('belum dijadwalkan')}`}`}
						onclick={() => openDay(day)}
					>
						<span class="day-number">{day.day}</span>
						{#if day.assignment}
							<span class="day-schedule">
								<strong>{day.assignment.shift_name}</strong>
								<small>{day.assignment.starts_at.slice(0, 5)}–{day.assignment.ends_at.slice(0, 5)}</small>
							</span>
						{:else if day.inMonth}
							<span class="auto-label">{t('Kosong')}</span>
						{/if}
						{#if canManageSchedules}<span class="edit-hint">{t('Atur')}</span>{/if}
					</button>
				{/each}
			</div>
			<div class="calendar-legend">
				<span><i class="legend-shift"></i>{t('Shift ditetapkan')}</span>
				<span><i class="legend-auto"></i>{t('Belum dijadwalkan')}</span>
				<span><b></b>{t('Hari ini')}</span>
			</div>
		</section>
	{/if}

	{#if showDialog}
		<dialog bind:this={dialogElement} class="schedule-dialog" aria-labelledby="dialog-title" onclose={() => showDialog = false}>
			<div class="dialog-heading">
				<div>
					<p class="eyebrow">{t('PENEMPATAN SHIFT')}</p>
					<h2 id="dialog-title">{t('Atur jadwal')}</h2>
					<p>{selectedEmployee?.full_name} · {selectedDateLabel}</p>
				</div>
				<button class="quiet icon-button" aria-label={t('Tutup dialog')} onclick={() => showDialog = false}><Icon name="close" size={18} /></button>
			</div>
			{#if !shiftList.length && !selectedClear}
				<p class="dialog-note">{t('Belum ada pola shift tersedia. Buat pola shift terlebih dahulu di halaman Shift & jadwal.')}</p>
			{/if}
			<label>
				Shift
				<select bind:value={selectedShiftId} disabled={selectedClear || !shiftList.length}>
					<option value="">{t('Kosongkan jadwal hari ini')}</option>
					{#each shiftList as shift (shift.id)}
						<option value={shift.id}>{shift.name} · {shift.starts_at}–{shift.ends_at}</option>
					{/each}
				</select>
			</label>
			<label class="day-off-toggle">
				<input type="checkbox" bind:checked={selectedClear} /> {t('Kosongkan penugasan shift (hari libur / tanpa shift)')}
			</label>
			<div class="dialog-actions">
				<button class="quiet cancel-button" onclick={() => showDialog = false} disabled={saving}>{t('Batal')}</button>
				<button onclick={() => void saveSchedule()} disabled={saving || (!selectedClear && !selectedShiftId && !assignments[selectedDate])}>
					{saving ? t('Menyimpan…') : t('Simpan jadwal')}
				</button>
			</div>
		</dialog>
	{/if}
</main>

<style>
	.schedule-page .back-link{display:inline-flex;align-items:center;gap:7px;min-height:32px;margin-bottom:8px;color:var(--muted);font-size:12px;font-weight:700;text-decoration:none}.schedule-page .back-link:hover{color:var(--primary-strong)}.quiet-link{display:inline-flex;align-items:center;gap:8px;min-height:42px;padding:9px 13px;border:1px solid var(--line);background:var(--surface);color:var(--ink)!important;font-size:12px;font-weight:700;text-decoration:none;box-shadow:var(--shadow-soft)}.quiet-link:hover{background:var(--primary-soft);color:var(--primary-strong)!important}
	.calendar-toolbar{display:grid;grid-template-columns:minmax(200px,1fr) minmax(170px,.7fr) auto;align-items:end;gap:12px;margin-bottom:16px}.calendar-toolbar label{margin:0}.month-controls{display:flex;align-items:center;gap:6px}.month-controls .icon-button{display:grid;place-items:center;width:40px;height:40px;padding:0!important}.current-month{min-height:40px}
	.calendar-title{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:18px}.calendar-title .eyebrow{margin-bottom:5px}.calendar-title h2{margin:0;font-size:19px;text-transform:capitalize}.calendar-title p:not(.eyebrow){margin-top:5px;color:var(--muted);font-size:12px}
	.week-grid{display:grid;grid-template-columns:repeat(7,minmax(0,1fr))}.day-headings{border:1px solid var(--line);border-bottom:0;background:var(--surface-alt)}.day-headings span{padding:10px 8px;text-align:center;color:var(--muted);font-size:10px;font-weight:750;letter-spacing:.06em;text-transform:uppercase}.day-headings .weekend{color:#a0554c}.calendar-days{border-top:1px solid var(--line);border-left:1px solid var(--line)}.calendar-day{position:relative;display:flex;min-width:0;min-height:105px;flex-direction:column;align-items:stretch;gap:8px;padding:9px!important;border:0!important;border-right:1px solid var(--line)!important;border-bottom:1px solid var(--line)!important;background:var(--surface)!important;color:var(--ink)!important;text-align:start;box-shadow:none!important}.calendar-day:hover:not(:disabled){background:var(--surface-alt)!important}.calendar-day.outside{background:var(--surface-alt)!important;color:var(--muted)!important}.day-number{display:grid;place-items:center;width:27px;height:27px;font-size:12px;font-weight:700}.calendar-day.today .day-number{background:var(--accent-solid);color:#fff}.day-schedule{display:grid;gap:3px;padding:6px;background:var(--primary-soft);color:var(--primary-strong)}.day-schedule strong{overflow:hidden;font-size:10px;text-overflow:ellipsis;white-space:nowrap}.day-schedule small{font-size:9px;font-variant-numeric:tabular-nums}.auto-label{color:var(--muted);font-size:9px;font-style:italic}.edit-hint{position:absolute;inset-block-start:10px;inset-inline-end:9px;opacity:0;color:var(--primary-strong);font-size:9px;font-weight:700}.calendar-day:hover .edit-hint,.calendar-day:focus-visible .edit-hint{opacity:1}.calendar-legend{display:flex;flex-wrap:wrap;gap:14px;margin-top:16px;color:var(--muted);font-size:10px}.calendar-legend span{display:inline-flex;align-items:center;gap:6px}.calendar-legend i,.calendar-legend b{width:9px;height:9px;border:1px solid transparent}.legend-shift{background:var(--primary-soft)}.legend-auto{background:var(--surface-alt);border-color:var(--line)!important}.calendar-legend b{background:var(--accent-solid)}
	.schedule-dialog{position:fixed;inset:50% auto auto 50%;width:min(460px,calc(100vw - 36px));max-width:100%;margin:0;padding:22px;transform:translate(-50%,-50%);border:1px solid var(--line);background:var(--surface);color:var(--ink);box-shadow:0 24px 70px -25px rgba(0,0,0,.45)}.schedule-dialog::backdrop{background:rgba(20,32,24,.44)}.dialog-heading{display:flex;justify-content:space-between;gap:12px;margin-bottom:20px}.dialog-heading .eyebrow{margin-bottom:5px}.dialog-heading h2{margin:0;font-size:19px}.dialog-heading p:last-child{margin:5px 0 0;color:var(--muted);font-size:12px}.day-off-toggle{display:flex;align-items:center;gap:9px;margin-top:14px}.day-off-toggle input{width:17px;height:17px;margin:0}.dialog-note{margin:16px 0;color:var(--muted);font-size:11px;line-height:1.55}.dialog-actions{display:flex;justify-content:flex-end;gap:8px;padding-top:14px;border-top:1px solid var(--line)}.dialog-actions button{min-width:118px}.cancel-button{background:var(--surface)!important;color:var(--ink)!important;box-shadow:none!important}
	@media(max-width:800px){.calendar-toolbar{grid-template-columns:minmax(0,1fr) minmax(0,1fr)}.month-controls{grid-column:1/-1;justify-content:flex-end}.calendar-day{min-height:85px;padding:7px!important}.day-schedule{padding:5px}}
	@media(max-width:560px){.schedule-page .page-heading{align-items:flex-start}.quiet-link{display:none}.calendar-toolbar{grid-template-columns:minmax(0,1fr)}.month-controls{grid-column:auto;justify-content:space-between}.calendar-panel{padding:12px!important}.calendar-title{align-items:flex-start}.day-headings span{padding:8px 2px}.calendar-day{min-height:67px;gap:5px;padding:5px 3px!important}.day-number{width:22px;height:22px}.day-schedule{padding:3px}.edit-hint{display:none}.calendar-legend{gap:9px}.schedule-dialog{padding:17px}}
</style>
