<script lang="ts">
	import { goto } from '$app/navigation';
	import { request } from '../../lib/api';
	import Brand from '../../lib/Brand.svelte';

	let email = $state('');
	let password = $state('');
	let error = $state('');
	let submitting = $state(false);
	let showPassword = $state(false);

	async function submit() {
		error = '';
		submitting = true;
		try {
			await request('/v1/auth/login', { method: 'POST', body: JSON.stringify({ email, password }) });
			await goto('/app/today');
		} catch (cause) {
			error = cause instanceof Error ? cause.message : 'Tidak dapat masuk.';
		} finally {
			submitting = false;
		}
	}
</script>

<svelte:head><title>Masuk · PasPapan</title></svelte:head>

<main class="login-shell">
	<div class="login-container">
		<a class="brand" href="/" aria-label="PasPapan — Beranda"><Brand size={44} /></a>
		<section class="login-card" aria-labelledby="login-title">
			<header><p class="eyebrow">WORKSPACE TIM</p><h1 id="login-title">Selamat datang kembali</h1><p>Masuk untuk mencatat kehadiran, melihat jadwal, dan mengelola pengajuan.</p></header>
			<form onsubmit={(event) => { event.preventDefault(); submit(); }}>
				{#if error}<p class="error" role="alert">{error}</p>{/if}
				<label for="email">Email kerja</label>
				<input id="email" bind:value={email} type="email" autocomplete="username" required placeholder="nama@perusahaan.com" />
				<label for="password">Kata sandi</label>
				<div class="password-field"><input id="password" bind:value={password} type={showPassword ? 'text' : 'password'} autocomplete="current-password" required /><button class="show-password" type="button" aria-label={showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'} aria-pressed={showPassword} onclick={() => showPassword = !showPassword}>{showPassword ? 'Sembunyikan' : 'Lihat'}</button></div>
				<button class="submit" disabled={submitting} type="submit">{submitting ? 'Memeriksa akses…' : 'Masuk ke papan kerja'}</button>
			</form>
			<p class="help">Belum memiliki akses? Hubungi admin perusahaan Anda.</p>
		</section>
		<p class="footer">Kehadiran, jadwal, dan kebutuhan kerja dalam satu tempat.</p>
	</div>
</main>

<style>
	.login-shell{min-height:100svh;display:grid;place-items:center;padding:32px 20px;background:var(--canvas)}.login-container{width:min(100%,420px)}.brand{display:flex;align-items:center;justify-content:center;gap:10px;font-size:23px;font-weight:800;color:var(--ink);text-decoration:none;letter-spacing:-.8px;margin-bottom:28px}.login-card{border:1px solid var(--line);background:var(--surface);padding:28px;border-radius:12px}.eyebrow{font-size:10px;font-weight:700;letter-spacing:1.2px;color:var(--primary);margin:0 0 10px}h1{font-size:23px;letter-spacing:-.7px;margin:0;line-height:1.25}header>p:last-child{font-size:13px;line-height:1.6;color:var(--muted);margin:10px 0 24px}label{display:block;font-size:12px;font-weight:650;margin:16px 0 7px}input{width:100%;min-width:0;border:1px solid #bdcbbf;background:white;border-radius:6px;padding:11px 12px;color:var(--ink)}.password-field{display:flex;border:1px solid #bdcbbf;border-radius:6px}.password-field input{border:0}.show-password{border:0;border-radius:6px;padding:0 10px;background:white;color:var(--primary);font-size:11px;font-weight:600;cursor:pointer}.submit{border:0;border-radius:7px;min-height:44px;width:100%;margin-top:22px;padding:12px;background:var(--primary);color:white;font-size:13px;font-weight:650;cursor:pointer}.submit:disabled{opacity:.6;cursor:wait}.error{font-size:13px;background:#fff0ed;color:#8e2e26;padding:12px;border-radius:6px}.help{font-size:12px;line-height:1.6;color:var(--muted);padding-top:18px;margin:20px 0 0;border-top:1px solid var(--line)}.footer{font-size:11px;line-height:1.5;color:var(--muted);text-align:center;margin:18px 0 0}@media(max-width:480px){.login-shell{padding:24px 16px}.login-card{padding:22px}input{font-size:16px}}
</style>
