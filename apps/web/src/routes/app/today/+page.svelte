<script lang="ts">
	import { onMount } from 'svelte';
	import { checkIn, checkOut, todayAttendance, type TodayAttendance } from '../../../lib/api';
	import { workSession } from '../../../lib/session';
	import Icon from '../../../lib/Icon.svelte';
	const session = workSession();
	let attendance = $state<TodayAttendance | null>(null);
	let loading = $state(true), busy = $state(false), error = $state('');
	let canApprove = $derived(session.current && ['owner', 'admin', 'manager'].includes(session.current.company.role));
	let canManage = $derived(session.current && ['owner', 'admin'].includes(session.current.company.role));
	let checkedIn = $derived(attendance && attendance.status !== 'not_checked_in');
	let checkedOut = $derived(attendance?.status === 'checked_out');
	async function load() {
		loading = true; error = '';
		try { attendance = await todayAttendance(); }
		catch (e) { error = e instanceof Error ? e.message : 'Kehadiran belum dapat dimuat.'; }
		finally { loading = false; }
	}
	onMount(load);
	async function submitAttendance(kind: 'check-in' | 'check-out') {
		if (busy) return;
		if (!navigator.geolocation) { error = 'Browser ini tidak mendukung lokasi. Gunakan perangkat dengan GPS aktif.'; return; }
		busy = true; error = '';
		navigator.geolocation.getCurrentPosition(async ({ coords }) => {
			try {
				await (kind === 'check-in' ? checkIn(coords.latitude, coords.longitude) : checkOut(coords.latitude, coords.longitude));
				attendance = await todayAttendance();
			} catch (e) { error = e instanceof Error ? e.message : 'Kehadiran tidak dapat disimpan.'; }
			finally { busy = false; }
		}, () => { error = 'Lokasi diperlukan. Aktifkan izin lokasi lalu coba lagi.'; busy = false; }, { enableHighAccuracy: true, timeout: 15_000, maximumAge: 30_000 });
	}
</script>

<svelte:head><title>Hari ini · PasPapan</title></svelte:head>
<main id="main-content" class="workspace-page" tabindex="-1">
	<header class="page-heading"><div><p class="eyebrow">{session.current?.company.name}</p><h1>Halo, {session.current?.user.full_name.split(' ')[0]}.</h1><p>Jadwal, kehadiran, dan kebutuhan kerja Anda hari ini.</p></div><a class="history-link" href="/app/history">Lihat riwayat <Icon name="arrow" /></a></header>
	{#if error}<p role="alert">{error} <button class="quiet" onclick={load} disabled={busy}>Coba lagi</button></p>{/if}
	<div class="home-grid">
		<section class="panel attendance" aria-labelledby="attendance-title" aria-busy={loading}>
			<div class="section-heading"><div><p class="eyebrow">ABSENSI</p><h2 id="attendance-title">Hari ini</h2></div>{#if attendance}<span class="badge">{attendance.date}</span>{/if}</div>
			{#if loading}<p role="status">Memuat kehadiran…</p>{:else if attendance}
				<div class="shift-strip"><Icon name="calendar" /><div><b>{attendance.shift_name ?? 'Belum ada jadwal'}</b><p>{attendance.starts_at && attendance.ends_at ? `${attendance.starts_at} – ${attendance.ends_at}` : 'Hubungi admin untuk penetapan shift.'}</p></div></div>
				<ol class="timeline">
					<li class:complete={checkedIn}><span class="step"><Icon name={checkedIn ? 'check' : 'arrow'} /></span><div><b>Check-in</b><p>{checkedIn ? 'Kehadiran sudah tercatat' : 'Mulai hari kerja Anda'}</p></div><span class="step-status">{checkedIn ? 'Tercatat' : 'Belum'}</span></li>
					<li class:complete={checkedOut}><span class="step"><Icon name={checkedOut ? 'check' : 'logout'} /></span><div><b>Check-out</b><p>{checkedOut ? 'Hari kerja sudah ditutup' : checkedIn ? 'Selesaikan setelah bekerja' : 'Tersedia setelah check-in'}</p></div><span class="step-status">{checkedOut ? 'Tercatat' : 'Belum'}</span></li>
				</ol>
				{#if !checkedOut}<button class="attendance-action" disabled={busy} onclick={() => submitAttendance(checkedIn ? 'check-out' : 'check-in')}><Icon name="pin" />{busy ? 'Membaca lokasi…' : checkedIn ? 'Check-out sekarang' : 'Check-in sekarang'}<Icon name="arrow" /></button><p class="location-note">Lokasi perangkat digunakan saat mencatat kehadiran.</p>{:else}<p class="done" role="status"><Icon name="check" />Kehadiran hari ini lengkap. Terima kasih!</p>{/if}
			{/if}
		</section>
		<section class="panel quick-access" aria-labelledby="quick-title"><div class="section-heading"><h2 id="quick-title">Akses cepat</h2><span class="muted">Kebutuhan harian</span></div><div class="quick-grid">
			<a href="/app/schedule"><span><Icon name="calendar" /></span><b>Jadwal kerja</b><small>Lihat shift Anda</small></a>
			<a href="/app/requests"><span><Icon name="request" /></span><b>Buat pengajuan</b><small>Cuti, lembur, dan lainnya</small></a>
			<a href="/app/history"><span><Icon name="clock" /></span><b>Riwayat absensi</b><small>Catatan masuk & pulang</small></a>
			<a href="/app/notifications"><span><Icon name="bell" /></span><b>Notifikasi</b><small>Perubahan pengajuan</small></a>
		</div></section>
		{#if canApprove}<section class="panel team-panel"><div><p class="eyebrow">TIM & OPERASIONAL</p><h2>Kelola pekerjaan tim</h2><p class="muted">Tinjau pengajuan{canManage ? ', employee, dan jadwal perusahaan' : ' anggota tim'}.</p></div><div class="team-actions"><a href="/app/approvals"><Icon name="check" />Persetujuan<Icon name="arrow" /></a>{#if canManage}<a href="/admin/employees"><Icon name="people" />Employee<Icon name="arrow" /></a><a href="/admin/attendance"><Icon name="calendar" />Shift & jadwal<Icon name="arrow" /></a>{/if}</div></section>{/if}
	</div>
</main>
<style>
	.history-link{display:flex;align-items:center;gap:7px;font-size:12px;text-decoration:none;white-space:nowrap}.home-grid{display:grid;grid-template-columns:1.1fr 1fr;gap:16px;align-items:start}.attendance h2{font-size:18px}.shift-strip{display:flex;gap:10px;align-items:center;padding:12px;background:var(--canvas);border-radius:7px;font-size:12px}.shift-strip p{color:var(--muted);margin:3px 0 0}.timeline{list-style:none;padding:0;margin:12px 0}.timeline li{display:flex;align-items:center;gap:10px;padding:14px 0}.timeline li+li{border-top:1px solid var(--line)}.step{width:34px;height:34px;display:grid;place-items:center;border-radius:50%;background:#f0f2f0;color:var(--muted);flex-shrink:0}.complete .step{background:var(--primary-soft);color:var(--primary)}.timeline b{font-size:13px}.timeline p{margin:2px 0 0;font-size:11px;color:var(--muted)}.step-status{margin-left:auto;font-size:11px;color:var(--muted)}.complete .step-status{color:var(--primary);font-weight:600}.attendance-action{display:flex;gap:10px;align-items:center;justify-content:center;width:100%;border-radius:24px!important;min-height:46px!important}.location-note{font-size:11px;text-align:center;margin:10px 0 0;color:var(--muted)}.quick-access .muted{font-size:11px}.quick-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}.quick-grid a{display:flex;flex-direction:column;align-items:flex-start;padding:14px 12px;border:1px solid var(--line);border-radius:8px;text-decoration:none;color:var(--ink)}.quick-grid a:hover{background:var(--canvas);border-color:var(--primary)}.quick-grid a>span{color:var(--primary);background:var(--primary-soft);display:grid;place-items:center;width:34px;height:34px;border-radius:8px;margin-bottom:10px}.quick-grid b{font-size:12px}.quick-grid small{font-size:11px;color:var(--muted);margin-top:3px}.team-panel{grid-column:1/-1;display:flex;align-items:center;justify-content:space-between;gap:16px}.team-panel h2{margin:0}.team-panel p.muted{font-size:12px;margin:5px 0 0}.team-actions{display:flex;gap:8px;flex-wrap:wrap}.team-actions a{display:flex;gap:8px;align-items:center;text-decoration:none;border:1px solid var(--line);border-radius:7px;padding:11px;font-size:12px}.done{display:flex;align-items:center;gap:8px;padding:12px;background:var(--primary-soft);color:var(--primary);border-radius:8px;font-size:12px}
	@media(max-width:1100px){.team-panel{align-items:flex-start;flex-direction:column}}
	@media(max-width:900px){.home-grid{grid-template-columns:1fr}.team-panel{grid-column:auto}}
	@media(max-width:760px){.history-link{display:none}.quick-grid a{padding:12px}.team-actions{flex-direction:column;width:100%}.team-actions a{justify-content:space-between}}
</style>
