<script lang="ts">
	import { onMount, tick } from 'svelte';
	import type { Html5Qrcode as Html5QrCodeInstance } from 'html5-qrcode';
	import type * as Leaflet from 'leaflet';
	import { checkIn, checkOut, todayAttendance, type TodayAttendance } from '../../../lib/api';
	import Icon from '../../../lib/Icon.svelte';
	import 'leaflet/dist/leaflet.css';

	type CurrentLocation = {
		latitude: number;
		longitude: number;
		accuracy: number;
		updatedAt: Date;
	};

	let scannerElement = $state<HTMLDivElement>();
	let mapElement = $state<HTMLDivElement>();
	let scanner: Html5QrCodeInstance | null = null;
	let scannerGeneration = 0;
	let scannerRunning = false;
	let map: Leaflet.Map | null = null;
	let locationMarker: Leaflet.CircleMarker | null = null;
	let facingMode: 'environment' | 'user' = 'environment';
	let starting = $state(false);
	let scanning = $state(false);
	let submitting = $state(false);
	let loadingAttendance = $state(true);
	let attendanceError = $state('');
	let cameraError = $state('');
	let error = $state('');
	let notice = $state('');
	let result = $state('');
	let attendance = $state<TodayAttendance | null>(null);
	let location = $state<CurrentLocation | null>(null);
	let locationLoading = $state(false);
	let locationError = $state('');
	let mapOpen = $state(false);
	let manualValue = $state('');
	let manualEntryOpen = $state(false);
	let checkedIn = $derived(attendance?.status === 'checked_in');
	let checkedOut = $derived(attendance?.status === 'checked_out');

	async function loadAttendance(): Promise<boolean> {
		loadingAttendance = true;
		attendanceError = '';
		try {
			attendance = await todayAttendance();
			return true;
		} catch (cause) {
			attendanceError = cause instanceof Error ? cause.message : 'Status absensi tidak dapat dimuat.';
			return false;
		} finally {
			loadingAttendance = false;
		}
	}

	async function prepareAttendancePage(isActive: () => boolean = () => true) {
		const loaded = await loadAttendance();
		if (!isActive() || !loaded || checkedOut) return;
		await tick();
		if (!isActive()) return;
		await Promise.all([startScan(), refreshLocation()]);
	}

	function locationErrorMessage(cause: GeolocationPositionError) {
		if (cause.code === cause.PERMISSION_DENIED) return 'Izin lokasi ditolak. Aktifkan izin lokasi untuk absensi.';
		if (cause.code === cause.POSITION_UNAVAILABLE) return 'Lokasi belum ditemukan. Periksa GPS dan coba lagi.';
		return 'Pencarian lokasi melewati batas waktu. Coba lagi.';
	}

	function isGeolocationError(cause: unknown): cause is GeolocationPositionError {
		return typeof cause === 'object' && cause !== null && 'code' in cause &&
			typeof cause.code === 'number' && 'message' in cause;
	}

	let locationRequest: Promise<boolean> | null = null;
	let locationRequestSequence = 0;
	function refreshLocation(force = false): Promise<boolean> {
		if (locationRequest && !force) return locationRequest;
		if (!navigator.geolocation) {
			locationError = 'Perangkat ini tidak menyediakan GPS.';
			return Promise.resolve(false);
		}
		const requestId = ++locationRequestSequence;
		locationLoading = true;
		locationError = '';
		const request = new Promise<GeolocationPosition>((resolve, reject) => {
				navigator.geolocation.getCurrentPosition(resolve, reject, {
					enableHighAccuracy: true,
					timeout: 15_000,
					maximumAge: 0
				});
			})
			.then((position) => {
				if (requestId !== locationRequestSequence) return false;
				location = {
					latitude: position.coords.latitude,
					longitude: position.coords.longitude,
					accuracy: position.coords.accuracy,
					updatedAt: new Date(position.timestamp)
				};
				if (map && locationMarker) {
					locationMarker.setLatLng([location.latitude, location.longitude]);
					map.setView([location.latitude, location.longitude], map.getZoom());
				}
				return true;
			})
			.catch((cause: unknown) => {
				if (requestId !== locationRequestSequence) return false;
				locationError = isGeolocationError(cause)
					? locationErrorMessage(cause)
					: cause instanceof Error ? cause.message : 'Lokasi tidak dapat dimuat.';
				return false;
			})
			.finally(() => {
				if (requestId === locationRequestSequence) {
					locationLoading = false;
					locationRequest = null;
				}
			});
		locationRequest = request;
		return locationRequest;
	}

	function cameraErrorMessage(cause: unknown) {
		const name = cause instanceof Error ? cause.name : '';
		const message = cause instanceof Error ? cause.message.toLowerCase() : '';
		if (name === 'NotAllowedError' || name === 'PermissionDeniedError' ||
			message.includes('notallowed') || message.includes('permission denied')) {
			return 'Izin kamera ditolak. Izinkan kamera di browser lalu coba lagi.';
		}
		if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
			return 'Kamera tidak ditemukan pada perangkat ini.';
		}
		return 'Kamera belum aktif. Periksa izin kamera lalu coba lagi.';
	}

	async function startScan() {
		if (starting || scanning || checkedOut || loadingAttendance || !scannerElement) return;
		cameraError = '';
		error = '';
		result = '';
		starting = true;
		const generation = ++scannerGeneration;
		try {
			const { Html5Qrcode, Html5QrcodeSupportedFormats } = await import('html5-qrcode');
			const createScanner = () => new Html5Qrcode('qr-reader', {
				verbose: false,
				formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE]
			});
			const config = {
				fps: 15,
				aspectRatio: 1,
				qrbox: (width: number, height: number) => {
					const size = Math.floor(Math.min(width, height) * 0.72);
					return { width: size, height: size };
				}
			};
			const onSuccess = (decodedText: string) => {
				if (generation !== scannerGeneration || result) return;
				const token = decodedText.trim();
				if (!/^[a-f0-9]{64}$/i.test(token)) {
					cameraError = 'QR ini bukan kode lokasi absensi.';
					void stopScan();
					return;
				}
				result = token;
				void stopScan();
			};
			const instance = createScanner();
			scanner = instance;
			try {
				await instance.start({ facingMode }, config, onSuccess, () => {});
			} catch (primaryError) {
				const cameras = await Html5Qrcode.getCameras();
				if (!cameras.length) throw primaryError;
				const frontCamera = facingMode === 'user';
				cameras.sort((left, right) => {
					const pattern = frontCamera ? /front|user|selfie|face/i : /back|rear|environment|main/i;
					return Number(pattern.test(right.label)) - Number(pattern.test(left.label));
				});
				try {
					instance.clear();
				} catch (cause) {
					console.warn('Unable to clear the initial camera selection.', cause);
				}
				const fallback = createScanner();
				scanner = fallback;
				await fallback.start(cameras[0].id, config, onSuccess, () => {});
				if (/front|user|selfie|face/i.test(cameras[0].label)) facingMode = 'user';
				else if (/back|rear|environment|main/i.test(cameras[0].label)) facingMode = 'environment';
			}
			if (generation !== scannerGeneration) {
				const startedScanner = scanner ?? instance;
				await startedScanner.stop();
				startedScanner.clear();
				return;
			}
			scannerRunning = true;
			scanning = true;
		} catch (cause) {
			cameraError = cameraErrorMessage(cause);
			if (scanner === null) return;
			if (generation === scannerGeneration) await stopScan();
		} finally {
			starting = false;
		}
	}

	async function stopScan() {
		scannerGeneration++;
		const activeScanner = scanner;
		const wasRunning = scannerRunning;
		scanner = null;
		scannerRunning = false;
		scanning = false;
		if (!activeScanner) return;
		if (wasRunning) {
			try {
				await activeScanner.stop();
			} catch (cause) {
				console.error('Unable to stop QR scanner cleanly.', cause);
			}
		}
		try {
			activeScanner.clear();
		} catch (cause) {
			console.error('Unable to clear QR scanner.', cause);
		}
	}

	async function switchCamera() {
		const resume = scanning || !!cameraError;
		facingMode = facingMode === 'environment' ? 'user' : 'environment';
		await stopScan();
		if (resume) await startScan();
	}

	async function submitAttendance() {
		if (!result || !attendance || submitting || checkedOut) return;
		submitting = true;
		error = '';
		notice = '';
		if (!await refreshLocation() || !location) {
			error = locationError || 'Lokasi tidak tersedia. Aktifkan GPS lalu coba lagi.';
			submitting = false;
			return;
		}
		try {
			const wasCheckedIn = checkedIn;
			attendance = await (wasCheckedIn
				? checkOut(location.latitude, location.longitude, result)
				: checkIn(location.latitude, location.longitude, result));
			result = '';
			manualValue = '';
			manualEntryOpen = false;
			notice = wasCheckedIn ? 'Check-out tercatat.' : 'Check-in tercatat.';
			await tick();
			if (!checkedOut) await startScan();
		} catch (cause) {
			error = cause instanceof Error ? cause.message : 'Absensi tidak dapat disimpan.';
		} finally {
			submitting = false;
		}
	}

	function useManualValue() {
		const candidate = manualValue.trim();
		if (!/^[a-fA-F0-9]{64}$/.test(candidate)) {
			error = 'Token QR harus berisi 64 karakter heksadesimal.';
			return;
		}
		result = candidate;
		error = '';
		cameraError = '';
		notice = '';
		void stopScan();
	}

	async function toggleMap() {
		mapOpen = !mapOpen;
		if (!mapOpen || !location) return;
		await tick();
		if (!mapElement) return;
		const L = await import('leaflet');
		if (!map) {
			map = L.map(mapElement, { zoomControl: false }).setView([location.latitude, location.longitude], 16);
			L.control.zoom({ position: 'bottomright' }).addTo(map);
			L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
				maxZoom: 19,
				attribution: '&copy; OpenStreetMap contributors'
			}).addTo(map);
			locationMarker = L.circleMarker([location.latitude, location.longitude], {
				radius: 8,
				color: '#ffffff',
				weight: 3,
				fillColor: '#44733a',
				fillOpacity: 1
			}).addTo(map);
		} else {
			locationMarker?.setLatLng([location.latitude, location.longitude]);
			map.setView([location.latitude, location.longitude], 16);
		}
		requestAnimationFrame(() => map?.invalidateSize());
	}

	function coordinatePair() {
		if (!location) return '';
		return `${location.latitude.toFixed(6)}, ${location.longitude.toFixed(6)}`;
	}

	onMount(() => {
		let active = true;
		void prepareAttendancePage(() => active);
		return () => {
			active = false;
			locationRequestSequence++;
			locationRequest = null;
			void stopScan();
			map?.remove();
			map = null;
		};
	});

</script>

<svelte:head><title>Scan QR · PasPapan</title></svelte:head>

<main id="main-content" class="workspace-page scan-page" tabindex="-1">
	{#if loadingAttendance}
		<p class="loading-state" role="status">Memuat absensi…</p>
	{:else if attendanceError}
		<p class="scan-error" role="alert">{attendanceError} <button class="quiet" onclick={() => void prepareAttendancePage()} disabled={loadingAttendance}>Coba lagi</button></p>
	{:else if checkedOut}
		<header class="scan-heading"><h1>Absensi selesai</h1><a href="/app/today">Hari ini</a></header>
		<section class="panel completed-state"><Icon name="check" size={20} /><strong>Check-in dan check-out hari ini sudah tercatat.</strong></section>
	{:else}
		<section class="panel scanner-card" aria-label="Pemindai QR">
			<header class="scanner-heading">
				<div class="scanner-title">
					<span class="title-icon"><Icon name="qr" size={19} /></span>
					<div><h1>QR Absen {checkedIn ? 'Pulang' : 'Masuk'}</h1><span>{attendance?.shift_name ?? 'Jadwal belum ditentukan'}{#if attendance?.starts_at && attendance?.ends_at}{' · '}{attendance.starts_at}–{attendance.ends_at}{/if}</span></div>
				</div>
				<button class="camera-switch quiet" aria-label="Beralih kamera" title="Beralih kamera" onclick={() => void switchCamera()} disabled={starting || submitting}>
					<Icon name="switch" size={17} /><span>Beralih</span>
				</button>
			</header>
			{#if error}<p class="scan-error" role="alert">{error}</p>{/if}
			{#if notice}<p class="scan-notice" role="status">{notice}</p>{/if}
			<div class="camera-stage" class:camera-active={scanning} role="button" aria-disabled={scanning || !!result || starting} tabindex="0" onclick={() => { if (!scanning && !result) void startScan(); }} onkeydown={(event) => { if (!scanning && !result && (event.key === 'Enter' || event.key === ' ')) { event.preventDefault(); void startScan(); } }} aria-label={scanning ? 'Kamera sedang memindai QR' : 'Ketuk untuk membuka kamera'}>
				<div bind:this={scannerElement} id="qr-reader"></div>
				{#if !scanning && !result}
					<div class="camera-placeholder">
						<span class="scan-corners"></span>
						{#if !cameraError}<strong>{starting ? 'Menghubungkan kamera…' : 'Menyiapkan kamera…'}</strong>{/if}
					</div>
				{:else if scanning}
					<div class="scan-overlay" aria-hidden="true"><span class="scan-corners"></span></div>
				{/if}
			</div>
			{#if cameraError}
				<div class="scan-status scan-camera-error" role="alert"><span class="status-dot"></span><span>{cameraError}</span><button class="quiet" onclick={() => void startScan()} disabled={starting}>Coba lagi</button></div>
			{:else}
				<div class="scan-status" role="status"><span class:active={scanning} class="status-dot"></span>{#if scanning}Arahkan QR ke bingkai{:else if starting}Menghubungkan kamera…{:else if result}QR terbaca{:else}Kamera belum aktif{/if}</div>
			{/if}
			{#if result}
				<div class="scan-result">
					<div><strong>{checkedIn ? 'Konfirmasi check-out' : 'Konfirmasi check-in'}</strong><span>Lokasi {location ? 'siap diverifikasi' : 'belum tersedia'}</span></div>
					<button onclick={() => void submitAttendance()} disabled={submitting || !location}>
						{submitting ? 'Menyimpan…' : checkedIn ? 'Absen pulang' : 'Absen masuk'}
					</button>
					<button class="quiet" onclick={() => { result = ''; error = ''; void startScan(); }}>Scan lagi</button>
				</div>
			{/if}
			<details class="manual-entry" bind:open={manualEntryOpen}>
				<summary>Masukkan token manual</summary>
				<div class="manual-fields">
					<label for="manual-code">Token QR</label>
					<div><input id="manual-code" bind:value={manualValue} autocomplete="off" spellcheck="false" maxlength="64" /><button onclick={useManualValue}>Lanjut</button></div>
				</div>
			</details>
		</section>

		<section class="panel location-card" aria-labelledby="location-heading" aria-busy={locationLoading}>
			<div class="location-topline">
				<div class="location-title"><span class="location-icon"><Icon name="pin" size={17} /></span><h2 id="location-heading">Lokasi Saat Ini</h2></div>
				<div class="location-actions">
					<button class="icon-action quiet" class:refreshing={locationLoading} aria-label="Perbarui lokasi" title="Perbarui lokasi" aria-busy={locationLoading} onclick={() => void refreshLocation(true)}>
						<Icon name="refresh" size={17} />
					</button>
					<button class="map-toggle" onclick={() => void toggleMap()} disabled={!location}>
						<Icon name="chevron" size={16} />{mapOpen ? 'Tutup peta' : 'Lihat peta'}
					</button>
				</div>
			</div>
			{#if location}
				<p class="coordinates">{coordinatePair()}</p>
				<p class="location-meta">{locationLoading ? 'Memperbarui lokasi…' : `Akurasi ±${Math.round(location.accuracy)} m · ${location.updatedAt.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}`}</p>
				{#if locationError}<p class="location-error" role="alert">{locationError}</p>{/if}
			{:else}
				<p class="location-empty" role={locationError ? 'alert' : 'status'}>{locationError || (locationLoading ? 'Mencari lokasi…' : 'Lokasi belum tersedia.')}</p>
			{/if}
			{#if mapOpen}<div class="map-frame" bind:this={mapElement} aria-label="Peta lokasi saat ini"></div>{/if}
		</section>
	{/if}
</main>

<style>
	.scan-page { display: grid; gap: 12px; max-width: 820px; padding-top: 16px; align-content: start; align-items: start; }
	.loading-state { color: var(--muted); font-size: 13px; }
	.scan-page { --scan-radius: var(--radius-lg); --scan-radius-inner: var(--radius-sm); }
	.scanner-card, .location-card { padding: 12px !important; border-radius: var(--scan-radius) !important; }
	.scanner-heading, .location-topline { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
	.scanner-heading { margin-bottom: 10px; }
	.scanner-title, .location-title { display: flex; align-items: center; min-width: 0; gap: 9px; }
	.title-icon, .location-icon { display: grid; place-items: center; flex: 0 0 34px; width: 34px; height: 34px; border-radius: 50%; background: var(--primary-soft); color: var(--primary-strong); }
	.scanner-title > div { display: grid; min-width: 0; gap: 1px; }
	.scanner-title h1 { font-size: 17px; line-height: 1.3; letter-spacing: -.025em; }
	.scanner-title > div > span { overflow: hidden; color: var(--muted); font-size: 11px; text-overflow: ellipsis; white-space: nowrap; }
	.camera-switch, .map-toggle { display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; gap: 6px; min-height: 34px !important; padding: 6px 10px !important; border: 1px solid var(--line) !important; border-radius: 999px !important; color: var(--muted) !important; font-size: 11px !important; }
	.camera-stage { position: relative; display: block; width: 100%; height: min(68svh, 560px); min-height: 280px; overflow: hidden; padding: 0 !important; border: 0 !important; border-radius: var(--scan-radius) !important; background: #070a08 !important; box-shadow: none !important; cursor: pointer; }
	#qr-reader { position: absolute; inset: 0; width: 100%; height: 100%; }
	#qr-reader :global(video) { display: block; width: 100% !important; height: 100% !important; object-fit: cover; }
	#qr-reader :global(canvas) { display: none !important; }
	#qr-reader :global(div) { border: 0 !important; }
	.camera-placeholder, .scan-overlay { position: absolute; inset: 0; display: grid; place-items: center; align-content: center; gap: 12px; color: #fff; pointer-events: none; }
	.camera-placeholder { background: #101a14; font-size: 12px; }
	.scan-overlay { background: linear-gradient(rgba(0,0,0,.16), rgba(0,0,0,.16)); }
	.scan-corners { width: min(58vw, 230px); aspect-ratio: 1; border: 3px solid rgba(255,255,255,.9); border-radius: var(--scan-radius-inner); box-shadow: 0 0 0 100vmax rgba(0,0,0,.2); }
	.scan-status { display: flex; align-items: center; gap: 7px; min-height: 30px; color: var(--muted); font-size: 11px; }
	.scan-camera-error > span:nth-child(2) { flex: 1; }
	.scan-camera-error button { min-height: 30px; padding: 4px 8px; font-size: 10px; }
	.status-dot { width: 7px; height: 7px; flex: 0 0 7px; border-radius: 50%; background: #a7b3a9; }
	.status-dot.active { background: #3d8b4e; box-shadow: 0 0 0 3px var(--primary-soft); }
	.scan-error { display: flex; align-items: center; flex-wrap: wrap; gap: 7px; margin: 0 0 8px !important; padding: 8px 10px; border-radius: var(--radius); background: #fff0ed; color: #8e2e26; font-size: 11px; }
	.scan-error button { min-height: 30px; padding: 4px 8px; font-size: 10px; }
	.scan-notice { color: var(--primary-strong); font-size: 11px; font-weight: 650; }
	.scan-result { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; padding: 9px 0; border-top: 1px solid var(--line); }
	.scan-result > div { display: grid; flex: 1 1 150px; gap: 2px; font-size: 12px; }
	.scan-result > div span { color: var(--muted); font-size: 10px; }
	.scan-result button { min-height: 34px; padding: 6px 10px; font-size: 11px; }
	.manual-entry { border-top: 1px solid var(--line); padding-top: 8px; }
	.manual-entry summary { color: var(--muted); font-size: 10px; font-weight: 650; cursor: pointer; }
	.manual-fields { margin-top: 8px; }
	.manual-fields label { display: block; margin: 0 0 4px; font-size: 10px; }
	.manual-fields > div { display: flex; gap: 8px; }
	.manual-fields input { min-height: 34px; padding: 6px 9px; font-family: ui-monospace, monospace; font-size: 11px; }
	.manual-fields button { min-height: 34px; padding: 6px 10px; font-size: 11px; }
	.location-topline { margin-bottom: 8px; }
	.location-title h2 { margin: 0; font-size: 14px; }
	.location-actions { display: flex; align-items: center; gap: 7px; }
	.icon-action { display: grid !important; place-items: center; width: 34px; min-height: 34px !important; padding: 0 !important; border-radius: 50% !important; }
	.icon-action.refreshing :global(svg) { animation: location-refresh-spin 850ms linear infinite; }
	@keyframes location-refresh-spin { to { transform: rotate(360deg); } }
	.map-toggle { background: var(--primary-soft) !important; border-color: #d8ebd1 !important; color: var(--primary-strong) !important; text-transform: capitalize; }
	.map-toggle :global(svg) { transition: transform 150ms ease; }
	.map-frame { height: 220px; margin-top: 10px; overflow: hidden; border: 1px solid var(--line); border-radius: var(--scan-radius-inner); background: var(--surface-alt); }
	.coordinates { color: #326fe0; font-size: 12px; font-variant-numeric: tabular-nums; }
	.location-meta, .location-empty { margin-top: 3px !important; color: var(--muted); font-size: 10px; }
	.location-error { margin-top: 4px !important; color: #8e2e26; font-size: 10px; }
	.completed-state { display: flex; align-items: center; gap: 9px; padding: 14px !important; border-radius: var(--scan-radius) !important; color: var(--primary-strong); font-size: 13px; }
	@media (min-width: 1024px) {
		.scan-page { max-width: none; grid-template-columns: minmax(0, 2fr) minmax(300px, 1fr); }
		.scan-page > * { min-width: 0; }
		.location-topline { flex-wrap: wrap; }
		.loading-state, .scan-error, .scan-heading, .completed-state { grid-column: 1 / -1; }
	}
	@media (max-width: 760px) {
		.scan-page { gap: 10px; padding: 12px 12px calc(108px + env(safe-area-inset-bottom)); }
		.scanner-card, .location-card { padding: 10px !important; }
		.camera-stage { height: min(106vw, 54svh); min-height: 250px; }
		.location-card { margin-bottom: 2px; }
		.map-frame { height: 200px; }
	}
	@media (max-width: 360px) {
		.scanner-title h1 { font-size: 15px; }
		.camera-switch { width: 34px; padding: 0 !important; }
		.camera-switch span { display: none; }
		.camera-stage { min-height: 220px; }
	}
</style>
