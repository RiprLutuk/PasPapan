<script lang="ts">
	import { goto } from '$app/navigation';
	import { currentSession, type CurrentSession } from '../../../lib/api';

	let session = $state<CurrentSession | null>(null);
	let loading = $state(true);
	let expired = $state(false);

	async function loadSession() {
		try { session = await currentSession(); } catch { expired = true; } finally { loading = false; }
	}
	loadSession();
</script>

<svelte:head><title>Hari ini · PasPapan</title></svelte:head>
<main>
	<header><a href="/app/today">PasPapan<span>·</span></a><p>WORKFORCE / HARI INI</p></header>
	{#if loading}<p class="status">Memuat papan kerja…</p>
	{:else if expired}<section class="empty"><h1>Akses perlu diperbarui</h1><p>Sesi Anda sudah berakhir. Silakan masuk kembali.</p><button onclick={() => goto('/login')}>Masuk kembali</button></section>
	{:else if session}<section class="welcome"><p class="eyebrow">{session.company.name} · {session.company.role}</p><h1>Selamat datang, {session.user.full_name.split(' ')[0]}.</h1><p>Modul absensi sedang disambungkan. Hari ini dimulai dari sini.</p></section><section class="cards"><article><small>STATUS</small><strong>Belum check-in</strong><p>Catat kehadiran saat Anda siap bekerja.</p></article><article><small>JADWAL</small><strong>Belum ada jadwal</strong><p>Jadwal dan lokasi akan muncul di sini.</p></article></section>{/if}
</main>
<style>
	main { max-width: 72rem; margin: auto; padding: 1.5rem clamp(1.25rem, 5vw, 4rem) 5rem; }header { display:flex; justify-content:space-between; border-bottom:1px solid #cbd7cb; padding-bottom:1.4rem; }header a { color:#173429; text-decoration:none; font-weight:800; letter-spacing:-.06em; }header span { color:#d78033; }header p, small, .eyebrow { margin:0; color:#64816e; font-size:.67rem; font-weight:780; letter-spacing:.13em; }.welcome { padding:clamp(4rem,10vw,9rem) 0 3rem; max-width:45rem; }h1 { margin:.65rem 0 1.1rem; font-size:clamp(2.8rem,7vw,5.8rem); letter-spacing:-.065em; line-height:.95; }.welcome > p:last-child, article p { color:#5d6a63; line-height:1.6; }.cards { display:grid; grid-template-columns:repeat(2,1fr); gap:1rem; }article { padding:1.5rem; background:#fff; border:1px solid #d7e0d4; border-radius:.55rem; }strong { display:block; margin:.8rem 0; font-size:1.35rem; letter-spacing:-.03em; }.status,.empty { margin-top:5rem; }button { border:0; border-radius:.4rem; padding:.8rem 1rem; background:#173429; color:#fff; font-weight:700; cursor:pointer; }@media(max-width:600px){header p{display:none}.cards{grid-template-columns:1fr}}
</style>
