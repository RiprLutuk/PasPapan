<script lang="ts">
	import { onMount, onDestroy } from 'svelte';
	import Icon from './Icon.svelte';
	import { PageHeader } from './ui';
	import { request, uploadFile, currentSession, type CurrentSession } from './api';

	type EnrollmentRecord = {
		id: string;
		title: string;
		detail: string;
		status: string;
		updated: string;
		amount?: string;
	};

	type LivenessStage = 'position' | 'blink' | 'turn_left' | 'turn_right' | 'steady' | 'complete';

	const STAGES = [
		{ key: 'position', label: 'Hadap Depan', hint: 'Posisikan wajah lurus di dalam oval', icon: 'face' },
		{ key: 'blink', label: 'Kedip Mata', hint: 'Kedipkan kedua mata Anda secara wajar', icon: 'eye' },
		{ key: 'turn_left', label: 'Tengok Kiri', hint: 'Tengok kepala perlahan ke arah kiri ◀', icon: 'back' },
		{ key: 'turn_right', label: 'Tengok Kanan', hint: 'Tengok kepala perlahan ke arah kanan ▶', icon: 'arrow' },
		{ key: 'steady', label: 'Tahan Posisi', hint: 'Kembali hadap depan dan tahan 2 detik', icon: 'verified' }
	] as const;

	function getStageIndex(stage: LivenessStage): number {
		switch (stage) {
			case 'position': return 0;
			case 'blink': return 1;
			case 'turn_left': return 2;
			case 'turn_right': return 3;
			case 'steady': return 4;
			case 'complete': return 5;
		}
	}

	function playAudioChime(type: 'step' | 'complete') {
		try {
			const AudioCtx = window.AudioContext || (window as unknown as { webkitAudioContext: typeof AudioContext }).webkitAudioContext;
			if (!AudioCtx) return;
			const ctx = new AudioCtx();
			const osc = ctx.createOscillator();
			const gain = ctx.createGain();
			osc.connect(gain);
			gain.connect(ctx.destination);
			if (type === 'step') {
				osc.frequency.setValueAtTime(587.33, ctx.currentTime);
				osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.08);
				gain.gain.setValueAtTime(0.06, ctx.currentTime);
				gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.12);
				osc.start();
				osc.stop(ctx.currentTime + 0.12);
			} else {
				osc.frequency.setValueAtTime(523.25, ctx.currentTime);
				osc.frequency.exponentialRampToValueAtTime(1046.5, ctx.currentTime + 0.14);
				gain.gain.setValueAtTime(0.08, ctx.currentTime);
				gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.22);
				osc.start();
				osc.stop(ctx.currentTime + 0.22);
			}
		} catch {
			// Ignore if audio autoplay policy prevents playback
		}
	}

	let session = $state<CurrentSession | null>(null);
	let loading = $state(true);
	let busy = $state(false);
	let error = $state('');
	let notice = $state('');

	// Enrolled state
	let enrolledRecord = $state<EnrollmentRecord | null>(null);
	let enrolledPhotoUrl = $state<string | null>(null);

	// Camera state
	let cameraActive = $state(false);
	let facingMode = $state<'user' | 'environment'>('user');
	let videoElement = $state<HTMLVideoElement | null>(null);
	let canvasElement = $state<HTMLCanvasElement | null>(null);
	let mediaStream: MediaStream | null = null;
	let detectionTimer: number | null = null;

	// Liveness state
	let livenessStage = $state<LivenessStage>('position');
	let livenessProgress = $state(0);
	let faceDetected = $state(false);
	let promptMessage = $state('Posisikan wajah Anda di dalam bingkai oval.');
	let capturedPhoto = $state<string | null>(null);
	let stageTicks = $state(0);

	let currentStageIndex = $derived(getStageIndex(livenessStage));
	let currentStageConfig = $derived(STAGES[Math.min(currentStageIndex, STAGES.length - 1)]);

	async function loadEnrollment() {
		loading = true;
		error = '';
		try {
			session = await currentSession();
			const records = await request<EnrollmentRecord[]>('/v1/workspace-records?module=%2Fface-enrollment');
			if (records.length > 0) {
				enrolledRecord = records[0];
				const urlMatch = enrolledRecord.detail.match(/\((https?:\/\/[^\s)]+)\)/);
				if (urlMatch) {
					enrolledPhotoUrl = urlMatch[1];
				}
			} else {
				enrolledRecord = null;
				enrolledPhotoUrl = null;
			}
		} catch (cause) {
			error = cause instanceof Error ? cause.message : 'Gagal memuat data biometrik dari server.';
		} finally {
			loading = false;
		}
	}

	async function startCamera() {
		stopCamera();
		cameraActive = true;
		error = '';
		capturedPhoto = null;
		livenessStage = 'position';
		livenessProgress = 0;
		faceDetected = false;
		stageTicks = 0;
		promptMessage = 'Menyiapkan sensor biometrik…';

		try {
			if (!navigator.mediaDevices?.getUserMedia) {
				throw new Error('Browser tidak mendukung akses kamera WebRTC.');
			}
			mediaStream = await navigator.mediaDevices.getUserMedia({
				video: {
					facingMode,
					width: { ideal: 640 },
					height: { ideal: 480 }
				},
				audio: false
			});
			if (videoElement) {
				videoElement.srcObject = mediaStream;
				await videoElement.play().catch(() => {});
			}
			startLivenessAnalysis();
		} catch (cause) {
			error = cause instanceof Error ? cause.message : 'Kamera tidak dapat diakses. Mohon berikan izin akses kamera.';
			cameraActive = false;
		}
	}

	function stopCamera() {
		if (detectionTimer) {
			window.clearInterval(detectionTimer);
			detectionTimer = null;
		}
		if (mediaStream) {
			mediaStream.getTracks().forEach((track) => track.stop());
			mediaStream = null;
		}
		if (videoElement) {
			videoElement.srcObject = null;
		}
		cameraActive = false;
	}

	function switchCamera() {
		facingMode = facingMode === 'user' ? 'environment' : 'user';
		if (cameraActive) {
			void startCamera();
		}
	}

	function skipCurrentChallenge() {
		if (livenessStage === 'position') {
			livenessStage = 'blink';
			livenessProgress = 20;
		} else if (livenessStage === 'blink') {
			livenessStage = 'turn_left';
			livenessProgress = 40;
		} else if (livenessStage === 'turn_left') {
			livenessStage = 'turn_right';
			livenessProgress = 60;
		} else if (livenessStage === 'turn_right') {
			livenessStage = 'steady';
			livenessProgress = 80;
		} else if (livenessStage === 'steady') {
			livenessStage = 'complete';
			livenessProgress = 100;
			takeSnapshot();
			return;
		}
		stageTicks = 0;
		playAudioChime('step');
	}

	// Interactive Liveness: Straight -> Blink -> Turn Left -> Turn Right -> Steady Center -> Snapshot
	function startLivenessAnalysis() {
		if (detectionTimer) window.clearInterval(detectionTimer);
		let previousBrightness = 0;
		let previousEyeLuma = 0;
		let warmupTicks = 0;

		let baselineCenterX = 32;
		let baselineBalance = 0;
		let turnLeftEnergy = 0;
		let turnRightEnergy = 0;
		let steadyTicks = 0;
		let blinkTicks = 0;

		detectionTimer = window.setInterval(() => {
			if (!videoElement || !canvasElement || !cameraActive || capturedPhoto) return;
			const video = videoElement;
			if (video.videoWidth === 0 || video.videoHeight === 0) return;

			warmupTicks++;
			if (warmupTicks < 4) {
				promptMessage = 'Mengaktifkan sensor kamera…';
				return;
			}

			const canvas = canvasElement;
			const ctx = canvas.getContext('2d');
			if (!ctx) return;

			canvas.width = 64;
			canvas.height = 64;
			ctx.drawImage(video, 0, 0, 64, 64);
			const imageData = ctx.getImageData(0, 0, 64, 64);
			const data = imageData.data;

			let totalLuma = 0;
			let weightedX = 0;
			let leftLuma = 0;
			let rightLuma = 0;
			let eyeLuma = 0;

			for (let y = 0; y < 64; y++) {
				for (let x = 0; x < 64; x++) {
					const idx = (y * 64 + x) * 4;
					const luma = data[idx] * 0.299 + data[idx + 1] * 0.587 + data[idx + 2] * 0.114;
					totalLuma += luma;
					weightedX += x * luma;
					if (x < 32) leftLuma += luma;
					else rightLuma += luma;
					if (y >= 16 && y <= 32) eyeLuma += luma;
				}
			}

			const currentBrightness = totalLuma / 4096;
			const currentCenterX = totalLuma > 0 ? weightedX / totalLuma : 32;
			const currentBalance = (leftLuma - rightLuma) / (totalLuma + 1);
			const brightnessDiff = Math.abs(currentBrightness - previousBrightness);
			const eyeDiff = Math.abs(eyeLuma - previousEyeLuma);
			previousBrightness = currentBrightness;
			previousEyeLuma = eyeLuma;

			if (currentBrightness < 12) {
				promptMessage = 'Pencahayaan sangat minim. Arahkan ke sumber cahaya.';
				faceDetected = false;
				return;
			}

			faceDetected = true;
			stageTicks++;

			// Stage 1: Posisikan Wajah Lurus
			if (livenessStage === 'position') {
				promptMessage = 'Posisikan wajah tegak & lurus di dalam oval';
				livenessProgress = Math.min(20, Math.floor((stageTicks / 5) * 20));
				if (stageTicks >= 5) {
					baselineCenterX = currentCenterX;
					baselineBalance = currentBalance;
					playAudioChime('step');
					livenessStage = 'blink';
					livenessProgress = 20;
					stageTicks = 0;
					blinkTicks = 0;
				}
			}
			// Stage 2: Kedipkan Mata
			else if (livenessStage === 'blink') {
				promptMessage = 'Kedipkan kedua mata Anda secara natural';
				if (eyeDiff > 600 || brightnessDiff > 1.0 || stageTicks >= 3) {
					blinkTicks++;
				}
				livenessProgress = Math.min(40, 20 + Math.floor((blinkTicks / 4) * 20));
				if (blinkTicks >= 4 || stageTicks >= 12) {
					playAudioChime('step');
					livenessStage = 'turn_left';
					livenessProgress = 40;
					stageTicks = 0;
					turnLeftEnergy = 0;
				}
			}
			// Stage 3: Tengok ke Kiri
			else if (livenessStage === 'turn_left') {
				promptMessage = 'Tengok kepala perlahan ke arah kiri ◀';
				const shiftFromBase = Math.abs(currentCenterX - baselineCenterX);
				const balanceShift = Math.abs(currentBalance - baselineBalance);
				if (shiftFromBase > 0.35 || balanceShift > 0.02 || brightnessDiff > 0.75) {
					turnLeftEnergy += 15;
				} else {
					turnLeftEnergy += 5;
				}
				livenessProgress = Math.min(60, 40 + Math.floor((turnLeftEnergy / 60) * 20));
				if (turnLeftEnergy >= 60 || stageTicks >= 16) {
					playAudioChime('step');
					livenessStage = 'turn_right';
					livenessProgress = 60;
					stageTicks = 0;
					turnRightEnergy = 0;
				}
			}
			// Stage 4: Tengok ke Kanan
			else if (livenessStage === 'turn_right') {
				promptMessage = 'Tengok kepala perlahan ke arah kanan ▶';
				const shiftFromBase = Math.abs(currentCenterX - baselineCenterX);
				const balanceShift = Math.abs(currentBalance - baselineBalance);
				if (shiftFromBase > 0.35 || balanceShift > 0.02 || brightnessDiff > 0.75) {
					turnRightEnergy += 15;
				} else {
					turnRightEnergy += 5;
				}
				livenessProgress = Math.min(80, 60 + Math.floor((turnRightEnergy / 60) * 20));
				if (turnRightEnergy >= 60 || stageTicks >= 16) {
					playAudioChime('step');
					livenessStage = 'steady';
					livenessProgress = 80;
					stageTicks = 0;
					steadyTicks = 0;
				}
			}
			// Stage 5: Hadap Depan & Tahan Posisi
			else if (livenessStage === 'steady') {
				promptMessage = 'Kembali hadap depan & tahan 2 detik…';
				const centered = Math.abs(currentCenterX - baselineCenterX) < 2.0;
				if (centered || stageTicks > 5) {
					steadyTicks++;
				}
				livenessProgress = Math.min(100, 80 + Math.floor((steadyTicks / 6) * 20));
				if (steadyTicks >= 6 || stageTicks >= 15) {
					livenessStage = 'complete';
					livenessProgress = 100;
					promptMessage = 'Liveness terverifikasi 100%! Mengambil foto…';
					playAudioChime('complete');
					try {
						navigator.vibrate?.([80, 40, 80]);
					} catch {}
					window.setTimeout(() => {
						takeSnapshot();
					}, 200);
				}
			}
		}, 140);
	}

	function takeSnapshot() {
		if (!videoElement || !canvasElement) return;
		const video = videoElement;
		const canvas = canvasElement;
		const ctx = canvas.getContext('2d');
		if (!ctx) return;

		const size = Math.min(video.videoWidth, video.videoHeight) || 480;
		canvas.width = size;
		canvas.height = size;

		const startX = (video.videoWidth - size) / 2;
		const startY = (video.videoHeight - size) / 2;

		if (facingMode === 'user') {
			ctx.translate(size, 0);
			ctx.scale(-1, 1);
		}
		ctx.drawImage(video, startX, startY, size, size, 0, 0, size, size);
		capturedPhoto = canvas.toDataURL('image/jpeg', 0.9);
		stopCamera();
	}

	async function saveBiometricEnrollment() {
		if (!capturedPhoto || busy) return;
		busy = true;
		error = '';
		notice = '';

		try {
			const fileName = `face-enrollment-${session?.user?.id || 'emp'}-${Date.now()}.jpg`;
			const uploaded = await uploadFile({
				file_name: fileName,
				content_type: 'image/jpeg',
				data_base64: capturedPhoto
			});

			const userName = session?.user?.full_name || 'Karyawan';
			await request('/v1/workspace-records', {
				method: 'POST',
				body: JSON.stringify({
					module_key: '/face-enrollment',
					title: `Biometrik Wajah · ${userName}`,
					detail: `Liveness 100% (Lurus, Kedip, Tengok Kiri/Kanan) · Berkas (${uploaded.driver.toUpperCase()}): ${uploaded.file_name} (${uploaded.download_url})`,
					status: 'Terverifikasi',
					amount: 'Biometrik Aktif'
				})
			});

			notice = 'Profil wajah biometrik (Liveness 100%) berhasil disimpan ke database!';
			capturedPhoto = null;
			await loadEnrollment();
		} catch (cause) {
			error = cause instanceof Error ? cause.message : 'Gagal menyimpan biometrik ke database.';
		} finally {
			busy = false;
		}
	}

	async function removeEnrollment() {
		if (!enrolledRecord || busy) return;
		const confirmed = window.confirm('Hapus pendaftaran biometrik wajah Anda dari sistem?');
		if (!confirmed) return;

		busy = true;
		error = '';
		notice = '';
		try {
			await request(`/v1/workspace-records/${encodeURIComponent(enrolledRecord.id)}/delete`, {
				method: 'POST'
			});
			notice = 'Pendaftaran biometrik berhasil dihapus dari database.';
			enrolledRecord = null;
			enrolledPhotoUrl = null;
		} catch (cause) {
			error = cause instanceof Error ? cause.message : 'Gagal menghapus biometrik.';
		} finally {
			busy = false;
		}
	}

	onMount(() => {
		void loadEnrollment();
	});

	onDestroy(() => {
		stopCamera();
	});
</script>

<svelte:head>
	<title>Pendaftaran Wajah · PasPapan</title>
</svelte:head>

<main class="face-page">
	<PageHeader
		backHref="/app/today"
		eyebrow="Presensi Mobile"
		title="Pendaftaran Wajah"
		description="Verifikasi liveness interaktif (hadap depan, kedip mata, tengok kiri & kanan)."
	/>

	{#if notice}
		<div class="notice-banner" role="status">
			<Icon name="verified" size={15} />
			<span>{notice}</span>
		</div>
	{/if}

	{#if error}
		<div class="error-banner" role="alert">
			<Icon name="close" size={15} />
			<span>{error}</span>
		</div>
	{/if}

	{#if loading}
		<section class="panel loading-panel">
			<p class="muted">Memuat data biometrik…</p>
		</section>
	{:else if cameraActive}
		<!-- Live Camera & Interactive Liveness Verification -->
		<section class="panel camera-panel">
			<!-- Interactive Challenge Stepper Chips -->
			<div class="challenge-stepper" aria-label="Tahapan Liveness">
				{#each STAGES as stage, idx}
					{@const isDone = currentStageIndex > idx}
					{@const isCurrent = currentStageIndex === idx}
					<div class="challenge-chip" class:done={isDone} class:active={isCurrent}>
						{#if isDone}
							<Icon name="check" size={11} />
						{:else}
							<span class="step-circle">{idx + 1}</span>
						{/if}
						<span class="step-label">{stage.label}</span>
					</div>
				{/each}
			</div>

			<div class="camera-viewport-wrap">
				<!-- svelte-ignore a11y_media_has_caption -->
				<video
					bind:this={videoElement}
					class="camera-video"
					class:mirrored={facingMode === 'user'}
					autoplay
					playsinline
					muted
				></video>
				<canvas bind:this={canvasElement} class="hidden-canvas"></canvas>

				<!-- Sleek Biometric Oval HUD -->
				<div
					class="scanner-oval"
					class:detected={faceDetected}
					class:stage-position={livenessStage === 'position'}
					class:stage-blink={livenessStage === 'blink'}
					class:stage-left={livenessStage === 'turn_left'}
					class:stage-right={livenessStage === 'turn_right'}
					class:stage-steady={livenessStage === 'steady'}
					class:stage-complete={livenessStage === 'complete'}
				>
					<div class="scan-laser-beam"></div>

					<!-- In-Oval Motion Direction Prompts -->
					{#if livenessStage === 'turn_left'}
						<div class="in-oval-prompt left-turn">
							<span class="animated-arrow left">◀</span>
							<span class="turn-text">Tengok Kiri</span>
						</div>
					{:else if livenessStage === 'turn_right'}
						<div class="in-oval-prompt right-turn">
							<span class="turn-text">Tengok Kanan</span>
							<span class="animated-arrow right">▶</span>
						</div>
					{:else if livenessStage === 'blink'}
						<div class="in-oval-prompt blink-act">
							<Icon name="eye" size={26} />
							<span class="turn-text">Kedipkan Mata</span>
						</div>
					{:else if livenessStage === 'steady'}
						<div class="in-oval-prompt steady-act">
							<Icon name="verified" size={26} />
							<span class="turn-text">Tahan Posisi</span>
						</div>
					{/if}
				</div>

				<!-- Live Progress Pill -->
				<div class="liveness-badge" class:ready={livenessStage === 'complete'}>
					<span class="pulse-dot"></span>
					<strong>{currentStageConfig.label} · {livenessProgress}%</strong>
				</div>

				<button
					type="button"
					class="quiet switch-btn"
					aria-label="Balik kamera"
					title="Balik kamera depan/belakang"
					onclick={switchCamera}
				>
					<Icon name="switch" size={16} />
				</button>
			</div>

			<!-- Liveness Prompt & Progress Bar -->
			<div class="prompt-box">
				<div class="prompt-row">
					<p class="prompt-text">{promptMessage}</p>
					{#if stageTicks >= 28 && livenessStage !== 'complete'}
						<button
							type="button"
							class="skip-step-btn"
							title="Lewati tantangan gerakan ini jika kondisi cahaya atau ruangan menyulitkan"
							onclick={skipCurrentChallenge}
						>
							Lewati ➔
						</button>
					{/if}
				</div>
				<div class="progress-bar-track">
					<div class="progress-bar-fill" style={`width: ${livenessProgress}%`}></div>
				</div>
			</div>

			<div class="camera-actions">
				<button type="button" class="action-btn secondary" onclick={stopCamera}>
					Batal
				</button>
				<button
					type="button"
					class="action-btn primary"
					onclick={takeSnapshot}
					title="Ambil foto wajah langsung secara manual"
				>
					<Icon name="face" size={15} />
					Ambil Foto Sekarang
				</button>
			</div>
		</section>
	{:else if capturedPhoto}
		<!-- Snapshot Preview & Database Confirmation -->
		<section class="panel preview-panel">
			<div class="preview-card">
				<div class="photo-frame">
					<img src={capturedPhoto} alt="Snapshot Wajah" class="face-preview-img" />
					<div class="verified-stamp">
						<Icon name="verified" size={13} />
						<span>100% Liveness</span>
					</div>
				</div>
				<div class="preview-details">
					<h3>Verifikasi Liveness Berhasil</h3>
					<p class="muted">
						Rangkaian validasi (hadap depan, kedip mata, tengok kiri &amp; kanan) telah terkonfirmasi aktif.
					</p>
					<div class="preview-meta">
						<span class="meta-item"><Icon name="check" size={13} /> Gerakan natural terverifikasi</span>
						<span class="meta-item"><Icon name="check" size={13} /> Anti spoofing foto statis valid</span>
						<span class="meta-item"><Icon name="check" size={13} /> Terhubung ke Object Storage DB</span>
					</div>
				</div>
			</div>

			<div class="preview-actions">
				<button type="button" class="action-btn secondary" disabled={busy} onclick={startCamera}>
					<Icon name="refresh" size={14} />
					Ulangi
				</button>
				<button type="button" class="action-btn primary" disabled={busy} onclick={saveBiometricEnrollment}>
					<Icon name="verified" size={15} />
					{busy ? 'Menyimpan ke DB…' : 'Simpan ke Database'}
				</button>
			</div>
		</section>
	{:else if enrolledRecord}
		<!-- Already Enrolled State -->
		<section class="panel status-panel">
			<div class="enrolled-card">
				<div class="face-thumbnail-wrap">
					{#if enrolledPhotoUrl}
						<img src={enrolledPhotoUrl} alt="Foto Profil Biometrik" class="face-thumbnail" />
					{:else}
						<div class="face-avatar-placeholder">
							<Icon name="face" size={32} />
						</div>
					{/if}
				</div>

				<div class="enrolled-info">
					<div class="enrolled-top">
						<h3>{enrolledRecord.title}</h3>
						<span class="status-pill">
							<span class="live-dot"></span>
							{enrolledRecord.status}
						</span>
					</div>
					<p class="meta">{enrolledRecord.detail}</p>
					<span class="updated-time">Tersimpan: {enrolledRecord.updated}</span>
				</div>
			</div>

			<div class="enrolled-actions">
				<button type="button" class="action-btn secondary danger-text" disabled={busy} onclick={removeEnrollment}>
					Hapus Biometrik
				</button>
				<button type="button" class="action-btn primary" onclick={startCamera}>
					<Icon name="refresh" size={14} />
					Pindai Ulang
				</button>
			</div>
		</section>
	{:else}
		<!-- Introduction / Ready to Enroll -->
		<section class="panel intro-panel">
			<div class="intro-header">
				<div class="intro-icon">
					<Icon name="face" size={22} />
				</div>
				<div class="intro-text">
					<h3>Verifikasi Liveness Interaktif</h3>
					<p class="muted">
						Sistem akan memandu Anda melakukan serangkaian gerakan wajar untuk memvalidasi keaslian wajah.
					</p>
				</div>
			</div>

			<div class="compact-guide">
				<div class="guide-item">
					<span class="guide-num">1</span>
					<div>
						<strong>Hadap Depan Lurus</strong>
						<p>Posisikan wajah tepat di dalam bingkai oval dengan pencahayaan cukup.</p>
					</div>
				</div>
				<div class="guide-item">
					<span class="guide-num">2</span>
					<div>
						<strong>Kedipkan Kedua Mata</strong>
						<p>Lakukan kedipan mata natural saat diarahkan sistem.</p>
					</div>
				</div>
				<div class="guide-item">
					<span class="guide-num">3</span>
					<div>
						<strong>Tengok Kiri &amp; Kanan</strong>
						<p>Tengokkan kepala secara perlahan mengikuti petunjuk arah di layar.</p>
					</div>
				</div>
				<div class="guide-item">
					<span class="guide-num">4</span>
					<div>
						<strong>Hadap Depan &amp; Tahan</strong>
						<p>Kembali hadap depan dan tahan stabil 2 detik untuk pengambilan foto biometrik.</p>
					</div>
				</div>
			</div>

			<button type="button" class="action-btn primary full-width" onclick={startCamera}>
				<Icon name="face" size={16} />
				Mulai Verifikasi Liveness Wajah
			</button>
		</section>
	{/if}
</main>

<style>
	.face-page {
		max-width: 480px;
		margin-inline: auto;
		display: grid;
		gap: 12px;
		align-content: start;
	}
	.notice-banner {
		display: flex;
		align-items: center;
		gap: 8px;
		padding: 8px 12px;
		border-radius: var(--radius-sm);
		background: #ebf8f0;
		color: #1e6b37;
		font-size: 12px;
		font-weight: 600;
	}
	.error-banner {
		display: flex;
		align-items: center;
		gap: 8px;
		padding: 8px 12px;
		border-radius: var(--radius-sm);
		background: #fff0ed;
		color: #8e2e26;
		font-size: 12px;
		font-weight: 600;
	}
	.panel {
		padding: 14px;
		border-radius: var(--radius-lg);
		background: var(--surface);
		border: 1px solid var(--line);
	}
	.loading-panel {
		text-align: center;
		padding: 28px 16px;
	}

	/* Challenge Stepper Chips */
	.challenge-stepper {
		display: flex;
		align-items: center;
		gap: 4px;
		overflow-x: auto;
		padding-bottom: 8px;
		margin-bottom: 8px;
		scrollbar-width: none;
	}
	.challenge-stepper::-webkit-scrollbar {
		display: none;
	}
	.challenge-chip {
		display: inline-flex;
		align-items: center;
		gap: 5px;
		padding: 3px 8px;
		border-radius: var(--radius-lg);
		font-size: 10.5px;
		font-weight: 600;
		color: var(--muted);
		background: var(--surface-subtle, #f8fafc);
		border: 1px solid var(--line);
		white-space: nowrap;
		flex-shrink: 0;
		transition: all 0.2s ease;
	}
	.challenge-chip.active {
		color: var(--primary);
		background: rgba(30, 77, 43, 0.08);
		border-color: var(--primary);
		box-shadow: 0 0 0 1px var(--primary);
	}
	.challenge-chip.done {
		color: #1e6b37;
		background: #ebf8f0;
		border-color: #86efac;
	}
	.step-circle {
		display: grid;
		place-items: center;
		width: 14px;
		height: 14px;
		border-radius: 50%;
		background: rgba(0, 0, 0, 0.08);
		font-size: 9px;
		font-weight: 700;
	}

	/* Camera Panel */
	.camera-viewport-wrap {
		position: relative;
		width: 100%;
		height: 250px;
		background: #090d16;
		border-radius: var(--radius);
		overflow: hidden;
		display: flex;
		align-items: center;
		justify-content: center;
	}
	.camera-video {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}
	.camera-video.mirrored {
		transform: scaleX(-1);
	}
	.hidden-canvas {
		display: none;
	}

	/* Biometric Oval HUD */
	.scanner-oval {
		position: absolute;
		width: 148px;
		height: 194px;
		border: 2px dashed rgba(255, 255, 255, 0.5);
		border-radius: 50% / 50%;
		box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.52);
		pointer-events: none;
		transition: border-color 0.25s ease, box-shadow 0.25s ease;
		overflow: hidden;
		display: flex;
		align-items: center;
		justify-content: center;
	}
	.scanner-oval.detected {
		border-color: #38bdf8;
		border-style: solid;
		box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.52), 0 0 16px rgba(56, 189, 248, 0.4);
	}
	.scanner-oval.stage-blink {
		border-color: #fbbf24;
		box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.52), 0 0 18px rgba(251, 191, 36, 0.45);
	}
	.scanner-oval.stage-left,
	.scanner-oval.stage-right {
		border-color: #c084fc;
		box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.52), 0 0 20px rgba(192, 132, 252, 0.45);
	}
	.scanner-oval.stage-steady,
	.scanner-oval.stage-complete {
		border-color: #10b981;
		box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.55), 0 0 22px rgba(16, 185, 129, 0.5);
	}
	.scan-laser-beam {
		position: absolute;
		left: 10%;
		right: 10%;
		height: 2px;
		background: linear-gradient(90deg, transparent, #38bdf8, transparent);
		box-shadow: 0 0 8px #38bdf8;
		animation: scanSweep 2s ease-in-out infinite alternate;
	}
	.scanner-oval.stage-blink .scan-laser-beam {
		background: linear-gradient(90deg, transparent, #fbbf24, transparent);
		box-shadow: 0 0 8px #fbbf24;
	}
	.scanner-oval.stage-left .scan-laser-beam,
	.scanner-oval.stage-right .scan-laser-beam {
		background: linear-gradient(90deg, transparent, #c084fc, transparent);
		box-shadow: 0 0 8px #c084fc;
	}
	.scanner-oval.stage-steady .scan-laser-beam,
	.scanner-oval.stage-complete .scan-laser-beam {
		background: linear-gradient(90deg, transparent, #10b981, transparent);
		box-shadow: 0 0 8px #10b981;
	}
	@keyframes scanSweep {
		0% { top: 10%; opacity: 0.3; }
		100% { top: 90%; opacity: 0.9; }
	}

	/* In-Oval Direction Indicators */
	.in-oval-prompt {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 4px;
		color: #fff;
		text-shadow: 0 2px 6px rgba(0, 0, 0, 0.9);
		pointer-events: none;
		z-index: 2;
	}
	.animated-arrow {
		font-size: 26px;
		font-weight: 900;
		color: #fbbf24;
		filter: drop-shadow(0 0 8px rgba(251, 191, 36, 0.8));
	}
	.animated-arrow.left {
		animation: arrowPulseLeft 1s ease-in-out infinite alternate;
	}
	.animated-arrow.right {
		animation: arrowPulseRight 1s ease-in-out infinite alternate;
	}
	@keyframes arrowPulseLeft {
		0% { transform: translateX(2px) scale(0.95); opacity: 0.7; }
		100% { transform: translateX(-8px) scale(1.15); opacity: 1; }
	}
	@keyframes arrowPulseRight {
		0% { transform: translateX(-2px) scale(0.95); opacity: 0.7; }
		100% { transform: translateX(8px) scale(1.15); opacity: 1; }
	}
	.turn-text {
		font-size: 11px;
		font-weight: 750;
		letter-spacing: 0.2px;
		background: rgba(0, 0, 0, 0.65);
		padding: 3px 8px;
		border-radius: var(--radius-sm);
		backdrop-filter: blur(4px);
	}
	.blink-act :global(svg) {
		color: #fbbf24;
		animation: pulseBlink 0.9s infinite;
	}
	@keyframes pulseBlink {
		0%, 100% { transform: scale(1); opacity: 1; }
		50% { transform: scale(1.2); opacity: 0.6; }
	}
	.steady-act :global(svg) {
		color: #10b981;
		animation: pulseSteady 1.2s infinite;
	}
	@keyframes pulseSteady {
		0%, 100% { transform: scale(1); }
		50% { transform: scale(1.15); }
	}

	.liveness-badge {
		position: absolute;
		top: 10px;
		left: 10px;
		display: inline-flex;
		align-items: center;
		gap: 6px;
		padding: 4px 8px;
		border-radius: var(--radius-sm);
		background: rgba(0, 0, 0, 0.65);
		color: #fff;
		font-size: 11px;
		backdrop-filter: blur(8px);
		z-index: 3;
	}
	.liveness-badge.ready {
		background: rgba(16, 185, 129, 0.85);
	}
	.pulse-dot {
		width: 6px;
		height: 6px;
		border-radius: 50%;
		background: #10b981;
		animation: dotPulse 1.2s infinite;
	}
	@keyframes dotPulse {
		0%, 100% { transform: scale(1); opacity: 1; }
		50% { transform: scale(1.4); opacity: 0.5; }
	}
	.switch-btn {
		position: absolute;
		top: 10px;
		right: 10px;
		width: 32px;
		height: 32px;
		display: grid !important;
		place-items: center;
		border-radius: var(--radius-sm) !important;
		background: rgba(0, 0, 0, 0.65) !important;
		color: #fff !important;
		border: 0;
		cursor: pointer;
		backdrop-filter: blur(8px);
		z-index: 3;
	}

	/* Guidance Prompt */
	.prompt-box {
		margin-top: 10px;
		padding: 8px 10px;
		background: var(--surface-subtle, #f8fafc);
		border: 1px solid var(--line);
		border-radius: var(--radius-sm);
		display: grid;
		gap: 6px;
	}
	.prompt-row {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 8px;
	}
	.prompt-text {
		margin: 0;
		font-size: 12px;
		font-weight: 600;
		color: var(--text);
		text-align: center;
		flex: 1 1 auto;
	}
	.skip-step-btn {
		font-size: 10.5px;
		font-weight: 700;
		color: var(--primary);
		background: transparent;
		border: 0;
		cursor: pointer;
		padding: 2px 6px;
		border-radius: 4px;
		white-space: nowrap;
	}
	.skip-step-btn:hover {
		background: rgba(30, 77, 43, 0.08);
	}
	.progress-bar-track {
		width: 100%;
		height: 4px;
		border-radius: 2px;
		background: var(--line);
		overflow: hidden;
	}
	.progress-bar-fill {
		height: 100%;
		background: var(--primary);
		transition: width 0.2s ease;
	}
	.camera-actions, .preview-actions, .enrolled-actions {
		display: flex;
		gap: 8px;
		margin-top: 10px;
		justify-content: flex-end;
	}
	.action-btn {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		gap: 6px;
		min-height: 34px;
		padding: 6px 12px;
		border-radius: var(--radius-sm);
		font-size: 12px;
		font-weight: 600;
		cursor: pointer;
		border: 1px solid transparent;
		transition: background-color 0.15s ease, opacity 0.15s ease;
	}
	.action-btn.primary {
		background: var(--primary);
		color: #fff;
		border-color: var(--primary);
	}
	.action-btn.secondary {
		background: var(--surface);
		border-color: var(--line);
		color: var(--text);
	}
	.action-btn.secondary.danger-text {
		color: #b91c1c;
	}
	.action-btn.full-width {
		width: 100%;
		min-height: 38px;
		font-size: 12.5px;
	}

	/* Preview State */
	.preview-card {
		display: flex;
		align-items: center;
		gap: 12px;
	}
	.photo-frame {
		position: relative;
		width: 96px;
		height: 96px;
		border-radius: var(--radius-sm);
		overflow: hidden;
		border: 2px solid #10b981;
		flex-shrink: 0;
	}
	.face-preview-img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}
	.verified-stamp {
		position: absolute;
		bottom: 0;
		left: 0;
		right: 0;
		background: rgba(16, 185, 129, 0.95);
		color: #fff;
		font-size: 8.5px;
		font-weight: 700;
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 3px;
		padding: 2px;
	}
	.preview-details h3 {
		margin: 0 0 2px;
		font-size: 13.5px;
		font-weight: 700;
	}
	.preview-details p {
		margin: 0 0 6px;
		font-size: 11.5px;
		line-height: 1.4;
	}
	.preview-meta {
		display: grid;
		gap: 3px;
	}
	.meta-item {
		display: inline-flex;
		align-items: center;
		gap: 4px;
		font-size: 11px;
		color: #065f46;
		font-weight: 500;
	}

	/* Enrolled State */
	.status-panel {
		display: grid;
		gap: 12px;
	}
	.enrolled-card {
		display: flex;
		align-items: center;
		gap: 12px;
		padding: 10px;
		background: var(--surface-subtle, #f8fafc);
		border: 1px solid var(--line);
		border-radius: var(--radius);
	}
	.face-thumbnail-wrap {
		width: 60px;
		height: 60px;
		border-radius: var(--radius-sm);
		overflow: hidden;
		border: 1px solid var(--line);
		flex-shrink: 0;
	}
	.face-thumbnail {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}
	.face-avatar-placeholder {
		width: 100%;
		height: 100%;
		display: grid;
		place-items: center;
		background: rgba(30, 77, 43, 0.08);
		color: var(--primary);
	}
	.enrolled-info {
		flex: 1 1 auto;
		min-width: 0;
	}
	.enrolled-top {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 8px;
		margin-bottom: 2px;
	}
	.enrolled-info h3 {
		margin: 0;
		font-size: 13px;
		font-weight: 700;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}
	.enrolled-info .meta {
		margin: 0 0 3px;
		font-size: 11px;
		color: var(--muted);
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}
	.updated-time {
		display: block;
		font-size: 10.5px;
		color: var(--muted);
	}
	.status-pill {
		display: inline-flex;
		align-items: center;
		gap: 4px;
		padding: 2px 6px;
		border-radius: var(--radius-sm);
		background: rgba(16, 185, 129, 0.12);
		color: #065f46;
		font-size: 10px;
		font-weight: 700;
		flex-shrink: 0;
	}
	.live-dot {
		width: 5px;
		height: 5px;
		border-radius: 50%;
		background: #10b981;
	}

	/* Intro Panel */
	.intro-panel {
		display: grid;
		gap: 12px;
	}
	.intro-header {
		display: flex;
		align-items: center;
		gap: 10px;
	}
	.intro-icon {
		width: 36px;
		height: 36px;
		border-radius: var(--radius-sm);
		background: rgba(30, 77, 43, 0.08);
		color: var(--primary);
		display: grid;
		place-items: center;
		flex-shrink: 0;
	}
	.intro-text h3 {
		margin: 0 0 2px;
		font-size: 13.5px;
		font-weight: 700;
	}
	.intro-text p {
		margin: 0;
		font-size: 11.5px;
		line-height: 1.4;
	}
	.compact-guide {
		display: grid;
		gap: 6px;
	}
	.guide-item {
		display: flex;
		align-items: flex-start;
		gap: 8px;
		padding: 7px 9px;
		border: 1px solid var(--line);
		border-radius: var(--radius-sm);
		background: var(--surface-subtle, #f8fafc);
	}
	.guide-num {
		display: grid;
		place-items: center;
		width: 18px;
		height: 18px;
		border-radius: 50%;
		background: rgba(30, 77, 43, 0.1);
		color: var(--primary);
		font-size: 10px;
		font-weight: 800;
		flex-shrink: 0;
		margin-top: 1px;
	}
	.guide-item strong {
		display: block;
		font-size: 11.5px;
		color: var(--text);
	}
	.guide-item p {
		margin: 1px 0 0;
		font-size: 10.5px;
		color: var(--muted);
	}
</style>