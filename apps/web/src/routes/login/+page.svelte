<script lang="ts">
	import { goto } from '$app/navigation';
	import { request } from '../../lib/api';

	let email = $state('');
	let password = $state('');
	let error = $state('');
	let submitting = $state(false);

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
	<section class="story">
		<a class="brand" href="/">PasPapan<span>·</span></a>
		<div>
			<p class="eyebrow">HARI KERJA, TERLIHAT JELAS</p>
			<h1>Mulai hari dengan satu catatan yang benar.</h1>
			<p class="copy">Absensi, jadwal, dan pengajuan tim dalam satu papan kerja yang tenang.</p>
		</div>
		<p class="mark"><b>08</b> JAM KERJA<br />HARI INI</p>
	</section>
	<section class="form-panel" aria-labelledby="login-title">
		<form onsubmit={(event) => { event.preventDefault(); submit(); }}>
			<p class="eyebrow">AKSES TIM</p>
			<h2 id="login-title">Masuk ke PasPapan</h2>
			<p class="intro">Gunakan email kerja dan kata sandi Anda.</p>
			{#if error}<p class="error" role="alert">{error}</p>{/if}
			<label>Email kerja<input bind:value={email} type="email" autocomplete="email" required placeholder="nama@perusahaan.com" /></label>
			<label>Kata sandi<input bind:value={password} type="password" autocomplete="current-password" required minlength="12" /></label>
			<button disabled={submitting} type="submit">{submitting ? 'Memeriksa akses…' : 'Masuk ke papan kerja'}</button>
		</form>
	</section>
</main>

<style>
	.login-shell { min-height: 100svh; display: grid; grid-template-columns: 1.15fr .85fr; }
	.story { min-height: 100%; padding: clamp(2rem, 6vw, 6rem); color: #f4f8ef; background: #11251f; display: flex; flex-direction: column; justify-content: space-between; position: relative; overflow: hidden; }
	.story::after { content: ''; width: min(48vw, 630px); aspect-ratio: 1; border: 1px solid #9fb49c; border-radius: 50%; position: absolute; right: -20%; bottom: -37%; opacity: .35; }
	.brand { color: inherit; text-decoration: none; font-weight: 750; letter-spacing: -.06em; z-index: 1; }.brand span, .mark b { color: #f7b75c; }
	.eyebrow { margin: 0 0 1rem; color: #719986; font-size: .68rem; font-weight: 760; letter-spacing: .14em; }.story div { z-index: 1; }
	h1, h2 { letter-spacing: -.06em; line-height: .98; } h1 { margin: 0; max-width: 11ch; font-size: clamp(3rem, 6vw, 6.3rem); }.copy { max-width: 29rem; color: #c8d4c5; line-height: 1.6; }.mark { z-index: 1; color: #9fb49c; font-size: .62rem; font-weight: 700; letter-spacing: .12em; }.mark b { margin-right: .8rem; font: 700 2.6rem ui-monospace, monospace; letter-spacing: -.12em; }
	.form-panel { display: grid; place-items: center; padding: 2rem; background: #f7f9f4; } form { width: min(100%, 25rem); } h2 { margin: 0; font-size: 2.4rem; }.intro { margin: .9rem 0 2.3rem; color: #64716b; } label { display: grid; gap: .48rem; margin: 1.05rem 0; font-size: .82rem; font-weight: 680; } input { width: 100%; border: 1px solid #becbc1; border-radius: .4rem; padding: .85rem .9rem; background: #fff; } button { width: 100%; margin-top: 1rem; border: 0; border-radius: .4rem; padding: .95rem; background: #d78033; color: white; font-weight: 750; cursor: pointer; } button:disabled { opacity: .65; cursor: wait; }.error { padding: .8rem; border-left: 3px solid #b73f34; background: #fff0ed; color: #8e2e26; font-size: .86rem; }
	@media (max-width: 760px) { .login-shell { grid-template-columns: 1fr; }.story { min-height: 43svh; padding: 2rem; } h1 { font-size: clamp(2.7rem, 11vw, 4rem); }.form-panel { min-height: 57svh; padding: 2.5rem 1.5rem; } }
</style>
