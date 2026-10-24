<script lang="ts">
	import { onMount, setContext, type Snippet } from 'svelte';
	import { page } from '$app/state';
	import { goto } from '$app/navigation';
	import { ApiError, currentSession, request, type CurrentSession } from './api';
	import { sessionKey } from './session';
	import Icon from './Icon.svelte';
	import Brand from './Brand.svelte';
	let { children, admin = false }: { children: Snippet; admin?: boolean } = $props();
	let session = $state<CurrentSession | null>(null);
	let loading = $state(true), error = $state(''), expired = $state(false), signingOut = $state(false);
	setContext(sessionKey, { get current() { return session; } });
	const links = [
		{ href: '/app/today', label: 'Beranda', icon: 'home' },
		{ href: '/app/schedule', label: 'Jadwal', icon: 'calendar' },
		{ href: '/app/history', label: 'Riwayat', icon: 'clock' },
		{ href: '/app/requests', label: 'Pengajuan', icon: 'request' },
		{ href: '/app/notifications', label: 'Notifikasi', icon: 'bell' }
	];
	let canManage = $derived(session && ['owner', 'admin'].includes(session.company.role));
	let canApprove = $derived(session && ['owner', 'admin', 'manager'].includes(session.company.role));
	let forbidden = $derived((admin && !canManage) || (page.url.pathname === '/app/approvals' && !canApprove));
	async function load() {
		loading = true; error = ''; expired = false; session = null;
		try { session = await currentSession(); }
		catch (e) { expired = e instanceof ApiError && e.status === 401; error = expired ? 'Sesi berakhir. Silakan masuk kembali.' : 'Papan kerja belum dapat dimuat. Periksa koneksi lalu coba lagi.'; }
		finally { loading = false; }
	}
	async function logout() {
		signingOut = true; error = '';
		try { await request('/v1/auth/logout', { method: 'POST' }); await goto('/login'); }
		catch { error = 'Gagal keluar. Coba lagi.'; }
		finally { signingOut = false; }
	}
	onMount(load);
</script>

<div class="work-shell">
	<a class="skip-link" href="#main-content">Lewati ke konten</a>
	<header class="topbar">
		<a class="brand" href="/app/today" aria-label="PasPapan — Beranda"><Brand /><span class="workspace-label">{admin ? 'Admin' : 'Workspace'}</span></a>
		{#if session}<div class="account"><span class="company">{session.company.name}</span><span class="avatar" aria-hidden="true">{session.user.full_name.slice(0, 1).toUpperCase()}</span><span class="identity">{session.user.full_name}<small>{session.company.role}</small></span><button class="quiet icon-button" aria-label="Keluar" title="Keluar" disabled={signingOut} onclick={logout}><Icon name="logout" /></button></div>{/if}
	</header>
	<div class="shell-body">
		<aside class="sidebar" aria-label="Menu workspace">
			<p class="nav-label">WORKSPACE</p>
			<nav aria-label="Navigasi utama">{#each links as item}<a href={item.href} aria-current={page.url.pathname === item.href ? 'page' : undefined}><Icon name={item.icon} />{item.label}</a>{/each}</nav>
			{#if canApprove}<p class="nav-label">TIM & OPERASIONAL</p><nav aria-label="Navigasi tim"><a href="/app/approvals" aria-current={page.url.pathname === '/app/approvals' ? 'page' : undefined}><Icon name="check" />Persetujuan</a>{#if canManage}<a href="/admin/employees" aria-current={page.url.pathname === '/admin/employees' ? 'page' : undefined}><Icon name="people" />Employee</a><a href="/admin/attendance" aria-current={page.url.pathname === '/admin/attendance' ? 'page' : undefined}><Icon name="calendar" />Shift & jadwal</a>{/if}</nav>{/if}
			<p class="sidebar-note">Ruang kerja tim.<br />Satu tempat, setiap hari.</p>
		</aside>
		<div class="content-column">
			{#if canApprove}<nav class="team-mobile" aria-label="Navigasi tim mobile"><a href="/app/approvals" aria-current={page.url.pathname === '/app/approvals' ? 'page' : undefined}>Persetujuan</a>{#if canManage}<a href="/admin/employees" aria-current={page.url.pathname === '/admin/employees' ? 'page' : undefined}>Employee</a><a href="/admin/attendance" aria-current={page.url.pathname === '/admin/attendance' ? 'page' : undefined}>Shift & jadwal</a>{/if}</nav>{/if}
			{#if error}<div class="shell-message" role="alert">{error} {#if expired}<a href="/login">Masuk kembali</a>{:else}<button class="quiet" onclick={load}>Coba lagi</button>{/if}</div>{/if}
			{#if loading}<main id="main-content" class="workspace-page" tabindex="-1"><p role="status">Memuat papan kerja…</p></main>
			{:else if session && forbidden}<main id="main-content" class="workspace-page" tabindex="-1"><section class="panel"><h1>Akses terbatas</h1><p>Peran Anda tidak memiliki akses ke halaman ini.</p><a href="/app/today">Kembali ke beranda</a></section></main>
			{:else if session}{@render children()}{/if}
		</div>
	</div>
	<nav class="bottom-nav" aria-label="Navigasi mobile">{#each links as item}<a href={item.href} aria-current={page.url.pathname === item.href ? 'page' : undefined}><Icon name={item.icon} /><span>{item.label}</span></a>{/each}</nav>
</div>

<style>
	.work-shell{min-height:100svh}.skip-link{position:fixed;top:-80px;left:16px;z-index:30;background:white;padding:12px}.skip-link:focus{top:8px}.topbar{height:64px;position:sticky;top:0;z-index:20;background:var(--surface);border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;padding:0 24px;gap:16px}.brand{display:flex;align-items:center;gap:9px;text-decoration:none;font-size:18px;font-weight:800;color:var(--ink);letter-spacing:-.6px}.workspace-label{border-left:1px solid var(--line);padding-left:12px;margin-left:8px;font-size:12px;color:var(--muted);font-weight:500;letter-spacing:0}.account{display:flex;align-items:center;gap:10px;min-width:0}.company{font-size:12px;color:var(--muted);margin-right:8px;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.avatar{width:32px;height:32px;border-radius:50%;background:var(--primary-soft);color:var(--primary);display:grid;place-items:center;font-size:13px;font-weight:700}.identity{font-size:12px;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.identity small{display:block;color:var(--muted);font-size:11px;text-transform:capitalize}.shell-body{display:grid;grid-template-columns:212px minmax(0,1fr);max-width:1600px;margin:auto}.sidebar{position:sticky;top:64px;height:calc(100svh - 64px);padding:24px 14px;border-right:1px solid var(--line);display:flex;flex-direction:column}.nav-label{font-size:10px;font-weight:700;letter-spacing:1.2px;color:var(--muted);margin:0 12px 10px}.nav-label:not(:first-child){margin-top:28px}.sidebar nav{display:grid;gap:3px}.sidebar a{display:flex;align-items:center;gap:10px;padding:10px 12px;min-height:42px;border-radius:7px;color:var(--muted);text-decoration:none;font-size:13px;font-weight:550}.sidebar a:hover{background:#edf0ed}.sidebar a[aria-current],.team-mobile a[aria-current]{color:var(--primary);background:var(--primary-soft);font-weight:700}.sidebar-note{margin:auto 12px 0;font-size:11px;line-height:1.7;color:var(--muted)}.content-column{min-width:0}.shell-message{margin:16px 24px;padding:12px;border-radius:8px;background:#fff0ed;color:#8e2e26}.bottom-nav,.team-mobile{display:none}
	@media(max-width:760px){.topbar{height:56px;padding:0 16px}.workspace-label,.company,.identity{display:none}.shell-body{display:block}.sidebar{display:none}.content-column{padding-bottom:calc(82px + env(safe-area-inset-bottom))}.bottom-nav{position:fixed;bottom:0;left:0;right:0;z-index:20;display:grid;grid-template-columns:repeat(5,minmax(0,1fr));background:var(--surface);border-top:1px solid var(--line);padding:6px 8px calc(6px + env(safe-area-inset-bottom))}.bottom-nav a{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;min-height:50px;text-decoration:none;color:var(--muted);font-size:10px;border-radius:8px}.bottom-nav a[aria-current]{color:var(--primary);background:var(--primary-soft);font-weight:700}.team-mobile{display:flex;gap:6px;padding:8px 16px;border-bottom:1px solid var(--line);flex-wrap:wrap;background:var(--surface)}.team-mobile a{padding:10px;font-size:12px;text-decoration:none;border-radius:6px;color:var(--muted)}.shell-message{margin:12px 16px}}
</style>
