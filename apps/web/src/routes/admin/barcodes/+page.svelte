<script lang="ts">
	import { onMount } from 'svelte';
	import QRCode from 'qrcode';
	import Icon from '../../../lib/Icon.svelte';
	import { refreshAttendanceQr, createAttendanceLocation, setAttendanceLocationStatus, type AttendanceLocation } from '../../../lib/api';
	import { workSession } from '../../../lib/session';

	const session = workSession();
	let locations = $state<AttendanceLocation[]>([]);
	let qrImages = $state<Record<string, string>>({});
	let loading = $state(true);
	let busy = $state(false);
	let error = $state('');
	let notice = $state('');
	let now = $state(Date.now());
	let refreshing = false;
	let displayId = $state<string | null>(null);
	let display = $derived(locations.find((location) => location.id === displayId));
	function openDisplay(node: HTMLDialogElement) { node.showModal(); }
	function secondsLeft(location: AttendanceLocation) {
		return Math.min(30, Math.max(0, Math.ceil((Date.parse(location.qr_expires_at) - now) / 1000)));
	}
	async function renew() {
		if (refreshing || busy || !canManage) return;
		refreshing = true;
		try {
			const updated = await refreshAttendanceQr();
			await addQrImages(updated);
			now = Date.now();
			locations = updated;
			error = '';
		} catch (cause) {
			error = cause instanceof Error ? cause.message : 'QR gagal diperbarui. Periksa koneksi.';
		} finally { refreshing = false; }
	}
	let name = $state('');
	let latitude = $state<number | null>(null);
	let longitude = $state<number | null>(null);
	let radiusMeters = $state(100);
	let locating = $state(false);
	let locationError = $state('');
	let accuracy = $state<number | null>(null);
	let mounted = false;

	async function detectLocation() {
		if (locating) return;
		locationError = '';
		if (!window.isSecureContext || !navigator.geolocation) {
			locationError = 'Deteksi lokasi membutuhkan HTTPS atau localhost dan browser yang mendukung lokasi.';
			return;
		}
		locating = true;
		try {
			const position = await new Promise<GeolocationPosition>((resolve, reject) =>
				navigator.geolocation.getCurrentPosition(resolve, reject, { enableHighAccuracy: true, maximumAge: 0, timeout: 15000 })
			);
			if (!mounted) return;
			latitude = position.coords.latitude;
			longitude = position.coords.longitude;
			accuracy = Math.ceil(position.coords.accuracy);
		} catch (cause) {
			if (!mounted) return;
			const code = (cause as GeolocationPositionError).code;
			locationError = code === 1 ? 'Izin lokasi ditolak. Izinkan lokasi di browser, lalu coba lagi.'
				: code === 3 ? 'Pencarian lokasi melewati batas waktu. Coba lagi di area terbuka.'
				: 'Lokasi belum tersedia. Aktifkan layanan lokasi atau isi koordinat manual.';
		} finally {
			locating = false;
		}
	}
	let canManage = $derived(session.current?.capabilities.includes('admin.barcodes.manage') ?? false);

	async function addQrImages(items: AttendanceLocation[]) {
		const pairs = await Promise.all(items.map(async (location) => [
			location.id,
			await QRCode.toDataURL(location.qr_token, {
				errorCorrectionLevel: 'M',
				margin: 4,
				width: 480,
				color: { dark: '#17351f', light: '#ffffff' }
			})
		] as const));
		qrImages = Object.fromEntries(pairs);
	}

	async function load() {
		loading = true;
		error = '';
		try {
			locations = await refreshAttendanceQr();
			await addQrImages(locations);
		} catch (cause) {
			error = cause instanceof Error ? cause.message : 'Lokasi absensi tidak dapat dimuat.';
		} finally {
			loading = false;
		}
	}

	async function createLocation(event: SubmitEvent) {
		event.preventDefault();
		if (busy || !canManage || latitude === null || longitude === null) return;
		busy = true;
		error = '';
		notice = '';
		try {
			const created = await createAttendanceLocation({
				name,
				latitude,
				longitude,
				radius_meters: radiusMeters
			});
			locations = [...locations, created];
			await addQrImages(locations);
			name = '';
			notice = 'Lokasi dan QR absensi berhasil dibuat.';
		} catch (cause) {
			error = cause instanceof Error ? cause.message : 'Lokasi absensi tidak dapat dibuat.';
		} finally {
			busy = false;
		}
	}

	async function toggleLocation(location: AttendanceLocation) {
		if (busy || !canManage) return;
		const action = location.active ? 'menonaktifkan' : 'mengaktifkan';
		if (!window.confirm(`Yakin ingin ${action} QR ${location.name}?`)) return;
		busy = true;
		error = '';
		notice = '';
		try {
			await setAttendanceLocationStatus(location.id, !location.active);
			locations = await refreshAttendanceQr();
			await addQrImages(locations);
			notice = location.active ? 'QR dinonaktifkan.' : 'QR diaktifkan.';
		} catch (cause) {
			error = cause instanceof Error ? cause.message : 'Status QR tidak dapat diperbarui.';
		} finally {
			busy = false;
		}
	}

	onMount(() => {
		mounted = true;
		void load();
		const timer = window.setInterval(() => {
			now = Date.now();
			if (!document.hidden && locations.some((item) => item.active && secondsLeft(item) === 0)) void renew();
		}, 1000);
		// Reuse permission already granted, without prompting unexpectedly on page load.
		void navigator.permissions?.query({ name: 'geolocation' }).then((permission) => {
			if (mounted && permission.state === 'granted' && latitude === null && longitude === null) void detectLocation();
		}).catch(() => {});
		return () => { mounted = false; window.clearInterval(timer); };
	});
</script>

<svelte:head><title>QR & lokasi absensi · PasPapan</title></svelte:head>

<main id="main-content" class="workspace-page" tabindex="-1">
	<header class="page-heading">
		<div>
			<p class="eyebrow">ABSENSI · LOKASI</p>
			<h1>QR & lokasi absensi</h1>
			<p>Buat QR lokasi kerja dan tentukan radius yang berlaku untuk check-in dan check-out.</p>
		</div>
		<a href="/app/today">Kembali ke beranda</a>
	</header>

	<aside class="notice security-note" role="note">
		<Icon name="pin" size={18} />
		<p>QR berganti setiap 30 detik. Tampilkan di layar lokasi kerja; screenshot kedaluwarsa ditolak. Berbagi QR secara langsung dan pemalsuan GPS masih memerlukan kontrol tambahan.</p>
	</aside>

	{#if error}<p class="error-message" role="alert">{error} <button class="quiet" onclick={load} disabled={loading || busy}>Coba lagi</button></p>{/if}
	{#if notice}<p class="notice" role="status">{notice}</p>{/if}

	<div class="locations-layout">
		<section class="panel locations-panel" aria-labelledby="locations-title" aria-busy={loading}>
			<div class="section-heading">
				<div><p class="eyebrow">TITIK TERDAFTAR</p><h2 id="locations-title">Lokasi kerja</h2></div>
				<span class="badge">{locations.length} lokasi</span>
			</div>
			{#if loading}
				<p role="status">Memuat lokasi absensi…</p>
			{:else if !locations.length}
				<div class="empty"><h3>Belum ada lokasi QR</h3><p>Tambahkan titik kerja dan radius sebelum karyawan dapat mencatat absensi dengan QR.</p></div>
			{:else}
				<ul class="location-list">
					{#each locations as location (location.id)}
						<li class:inactive={!location.active} class="location-card">
							{#if location.active && secondsLeft(location) > 0 && qrImages[location.id]}
								<img class="location-qr" src={qrImages[location.id]} alt={`QR absensi ${location.name}`} />
							{:else}
								<div class="qr-loading" role="status">{location.active ? 'Memperbarui QR…' : 'QR nonaktif'}</div>
							{/if}
							<div class="location-copy">
								<div class="location-title"><h3>{location.name}</h3><span class:off={!location.active} class="status">{location.active ? 'Aktif' : 'Nonaktif'}</span></div>
								<p>Radius <strong>{location.radius_meters} m</strong></p>
								<small>{location.latitude.toFixed(5)}, {location.longitude.toFixed(5)}</small>
								{#if location.active}<small>QR dinamis · {secondsLeft(location)} detik</small>
									<button class="quiet status-action" onclick={() => displayId = location.id}><Icon name="qr" size={16} /> Tampilkan QR</button>{/if}
								{#if canManage}
									<button class="quiet status-action" disabled={busy} onclick={() => void toggleLocation(location)}>
										{location.active ? 'Nonaktifkan QR' : 'Aktifkan QR'}
									</button>
								{/if}
							</div>
						</li>
					{/each}
				</ul>
			{/if}
		</section>

		{#if canManage}
			<form class="panel create-panel" onsubmit={createLocation}>
				<div class="section-heading"><div><p class="eyebrow">TITIK BARU</p><h2>Buat lokasi QR</h2></div><span class="form-icon"><Icon name="qr" size={18} /></span></div>
				<p class="form-intro">Masukkan koordinat pusat lokasi dan radius maksimum absensi.</p>
				<button type="button" class="quiet" onclick={detectLocation} disabled={locating || busy}>
					<Icon name="pin" size={16} /> {locating ? 'Mencari lokasi…' : 'Gunakan lokasi saya'}
				</button>
				{#if locationError}<p class="error-message" role="alert">{locationError}</p>{/if}
				{#if accuracy !== null}<p class="form-help" role="status">Akurasi perangkat ±{accuracy} m. Pastikan titik ini pusat lokasi kerja sebelum disimpan.</p>{/if}
				<label>Nama lokasi<input bind:value={name} required maxlength="120" autocomplete="off" placeholder="Contoh: Kantor pusat" /></label>
				<div class="coordinate-fields">
					<label>Latitude<input bind:value={latitude} type="number" min="-90" max="90" step="any" required inputmode="decimal" placeholder="-6.20000" /></label>
					<label>Longitude<input bind:value={longitude} type="number" min="-180" max="180" step="any" required inputmode="decimal" placeholder="106.80000" /></label>
				</div>
				<label>Radius maksimum (meter)<input bind:value={radiusMeters} type="number" min="1" max="10000" step="1" required /></label>
				<button type="submit" disabled={busy || locating || !canManage}>{busy ? 'Menyimpan…' : 'Buat QR dan lokasi'}</button>
				<p class="form-help">Gunakan layar dengan koneksi internet. QR dinamis tidak untuk dicetak.</p>
			</form>
		{/if}
	</div>
</main>

{#if display}
	<dialog use:openDisplay class="qr-display panel" aria-label={`QR ${display.name}`} onclose={() => displayId = null}>
			<h2>{display.name}</h2>
			<p>Radius {display.radius_meters} m</p>
			{#if display.active && secondsLeft(display) > 0 && qrImages[display.id]}
				<img src={qrImages[display.id]} alt={`QR dinamis ${display.name}`} />
				<p>Berubah dalam {secondsLeft(display)} detik</p>
			{:else}<div class="display-empty" role="status">{display.active ? 'Menunggu QR terbaru…' : 'Lokasi nonaktif'}</div>{/if}
			{#if error}<p role="alert">{error}</p>{/if}
			<button class="quiet" onclick={() => displayId = null}>Tutup layar QR</button>
	</dialog>
{/if}

<style>
	.qr-display::backdrop{background:rgba(16,32,20,.65)}
	.qr-display{width:min(calc(100% - 32px),560px);max-height:95dvh;overflow:auto;text-align:center;padding:24px;margin:auto;color:var(--ink);background:var(--surface);border:1px solid var(--line);border-radius:var(--radius-lg)}
	.qr-display img{display:block;width:min(100%,420px);margin:16px auto;border-radius:var(--radius);background:white;padding:12px}
	.qr-display p{color:var(--muted);margin:10px 0}
	.display-empty{min-height:240px;display:grid;place-items:center}
	.location-card,.security-note,.error-message{border-radius:var(--radius)}
	.location-qr,.qr-loading,.status,.form-icon{border-radius:var(--radius-sm)}
	.locations-layout{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(300px,.75fr);gap:20px;align-items:start}
	.security-note{display:flex;align-items:flex-start;gap:10px;margin-bottom:18px;color:var(--muted);font-size:12px;line-height:1.55}
	.security-note :global(svg){flex:0 0 auto;color:var(--primary-strong)}
	.security-note p{max-width:78ch}
	.locations-panel .section-heading{align-items:center}
	.location-list{display:grid;gap:12px;margin:0;padding:0;list-style:none}
	.location-card{display:flex;align-items:center;gap:16px;min-width:0;padding:14px;border:1px solid var(--line);background:var(--surface)}
	.location-card.inactive{opacity:.7}
	.location-qr,.qr-loading{width:120px;height:120px;flex:0 0 120px;object-fit:contain;border:1px solid var(--line);background:white}
	.qr-loading{display:grid;place-items:center;color:var(--muted);font-size:10px}
	.location-copy{min-width:0;flex:1}
	.location-title{display:flex;align-items:center;flex-wrap:wrap;gap:8px}
	.location-title h3{margin:0;overflow-wrap:anywhere;font-size:14px}
	.location-copy p{margin-top:7px!important;color:var(--muted);font-size:12px}
	.location-copy small{display:block;margin-top:5px;color:var(--muted);font-size:11px;font-variant-numeric:tabular-nums}
	.status{padding:3px 7px;background:var(--primary-soft);color:var(--primary-strong);font-size:10px;font-weight:700}
	.status.off{background:var(--surface-alt);color:var(--muted)}
	.status-action{margin-top:10px}
	.create-panel .section-heading{margin-bottom:8px}
	.form-icon{display:grid;place-items:center;width:38px;height:38px;background:var(--primary-soft);color:var(--primary-strong)}
	.form-intro,.form-help{color:var(--muted);font-size:12px;line-height:1.55}
	.create-panel label{margin:14px 0}
	.coordinate-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
	.create-panel>button{width:100%;margin-top:4px}
	.form-help{margin-top:12px}
	.error-message{padding:12px;border:1px solid #edd2ce;background:#fff5f3;color:#88443d;font-size:13px}
	.empty{padding:32px 8px;text-align:center}
	.empty h3{margin:0;font-size:14px}
	.empty p{max-width:48ch;margin:8px auto 0;color:var(--muted);font-size:12px;line-height:1.55}
	@media(max-width:900px){.locations-layout{grid-template-columns:minmax(0,1fr)}}
	@media(max-width:560px){.location-card{align-items:flex-start;gap:12px;padding:10px}.location-qr,.qr-loading{width:92px;height:92px;flex-basis:92px}.coordinate-fields{grid-template-columns:minmax(0,1fr)}}
</style>
