<script lang="ts">
	import { onMount, onDestroy } from 'svelte';
	import Icon from './Icon.svelte';
	import { PageHeader } from './ui';
	import { request, uploadFile, currentSession, type CurrentSession } from './api';
	import { advanceLiveness, createLivenessState, resetTransientEvidence } from './biometric/liveness.js';
	import { uiText as t } from './i18n';

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

	function calculateEyeAspectRatio(points: { x: number; y: number }[]): number {
		if (points.length < 6) return 0.3;
		const dist = (p1: { x: number; y: number }, p2: { x: number; y: number }) => Math.hypot(p1.x - p2.x, p1.y - p2.y);
		const p1 = points[0], p2 = points[1], p3 = points[2], p4 = points[3], p5 = points[4], p6 = points[5];
		const vA = dist(p2, p6);
		const vB = dist(p3, p5);
		const h = dist(p1, p4);
		if (h === 0) return 0.3;
		return (vA + vB) / (2.0 * h);
	}

	let session = $state<CurrentSession | null>(null);
	let loading = $state(true);
	let busy = $state(false);
	let error = $state('');
	let notice = $state('');

	// AI Engine state (@vladmandic/face-api)
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	let faceapiInstance: any = null;
	let aiModelLoading = $state(false);
	let aiModelReady = $state(false);
	let livenessModelReady = $state(false);
	let recognitionModelReady = $state(false);
	let switchingCamera = $state(false);
	let currentCameraDeviceId = $state<string | null>(null);
	let enrolledDescriptor = $state<number[] | null>(null);
	let capturedDescriptor = $state<number[] | null>(null);

	// Enrolled state
	let enrolledRecord = $state<EnrollmentRecord | null>(null);
	let enrolledPhotoUrl = $state<string | null>(null);
	let enrolledDetailSummary = $state<string>('');

	// Camera state
	let cameraActive = $state(false);
	let facingMode = $state<'user' | 'environment'>('user');
	let videoElement = $state<HTMLVideoElement | null>(null);
	let canvasElement = $state<HTMLCanvasElement | null>(null);
	let landmarkCanvasElement = $state<HTMLCanvasElement | null>(null);
	let mediaStream: MediaStream | null = null;
	let detectionTimer: number | null = null;

	// Liveness state
	let livenessStage = $state<LivenessStage>('position');
	let livenessProgress = $state(0);
	let faceDetected = $state(false);
	let promptMessage = $state('Posisikan wajah Anda di dalam bingkai oval.');
	let capturedPhoto = $state<string | null>(null);

	let currentStageIndex = $derived(getStageIndex(livenessStage));
	let currentStageConfig = $derived(STAGES[Math.min(currentStageIndex, STAGES.length - 1)]);

	async function loadAiModels() {
		if (typeof window === 'undefined' || livenessModelReady || aiModelLoading) return;
		aiModelLoading = true;
		try {
			const mod = await import('@vladmandic/face-api/dist/face-api.esm.js');
			faceapiInstance = mod.default || mod;

			// Progressive load Phase 1: Tiny Detector (189 KB) + Tiny 68-Landmark (75 KB) -> Total only ~264 KB!
			await Promise.all([
				faceapiInstance.nets.tinyFaceDetector.loadFromUri('/models'),
				faceapiInstance.nets.faceLandmark68TinyNet.loadFromUri('/models')
			]);
			livenessModelReady = true;
			aiModelReady = true;

			// Progressive load Phase 2: Recognition model (for 128-D vector) loads lazily in background
			// so the user experiences zero lag or stutter during interactive liveness challenges!
			void faceapiInstance.nets.faceRecognitionNet.loadFromUri('/models').then(() => {
				recognitionModelReady = true;
			}).catch((err: unknown) => {
				console.warn('Background face recognition model load deferred:', err);
			});
		} catch (err) {
			console.warn('Face-API AI model loading deferred, using native high-speed vision:', err);
		} finally {
			aiModelLoading = false;
		}
	}

	async function loadEnrollment() {
		loading = true;
		error = '';
		try {
			session = await currentSession();
			const records = await request<EnrollmentRecord[]>('/v1/workspace-records?module=%2Fface-enrollment');
			if (records.length > 0) {
				enrolledRecord = records[0];
				try {
					const parsed = JSON.parse(enrolledRecord.detail);
					enrolledPhotoUrl = parsed.photo_url || null;
					enrolledDescriptor = Array.isArray(parsed.descriptor) ? parsed.descriptor : null;
					enrolledDetailSummary = `Wajah Terverifikasi · ${parsed.file_name || 'Tersimpan'}`;
				} catch {
					const urlMatch = enrolledRecord.detail.match(/\((https?:\/\/[^\s)]+)\)/);
					enrolledPhotoUrl = urlMatch ? urlMatch[1] : null;
					enrolledDescriptor = null;
					enrolledDetailSummary = 'Wajah Terverifikasi';
				}
			} else {
				enrolledRecord = null;
				enrolledPhotoUrl = null;
				enrolledDescriptor = null;
				enrolledDetailSummary = '';
			}
		} catch (cause) {
			error = cause instanceof Error ? cause.message : t('Gagal memuat data pendaftaran wajah dari server.');
		} finally {
			loading = false;
		}
	}

	async function openCameraStream(targetFacing: 'user' | 'environment', preferredDeviceId?: string): Promise<MediaStream> {
		if (!navigator.mediaDevices?.getUserMedia) {
			throw new Error('Browser tidak mendukung akses kamera WebRTC.');
		}

		if (preferredDeviceId) {
			try {
				return await navigator.mediaDevices.getUserMedia({
					video: {
						deviceId: { exact: preferredDeviceId },
						width: { ideal: 640 },
						height: { ideal: 480 }
					},
					audio: false
				});
			} catch (err) {
				console.warn('Direct deviceId camera open failed, trying facingMode fallback:', err);
			}
		}

		try {
			return await navigator.mediaDevices.getUserMedia({
				video: {
					facingMode: { ideal: targetFacing },
					width: { ideal: 640 },
					height: { ideal: 480 }
				},
				audio: false
			});
		} catch (err) {
			console.warn('Ideal facingMode camera open failed, trying loose constraint:', err);
		}

		return await navigator.mediaDevices.getUserMedia({
			video: true,
			audio: false
		});
	}

	async function startCamera() {
		stopCamera();
		cameraActive = true;
		error = '';
		capturedPhoto = null;
		capturedDescriptor = null;
		livenessStage = 'position';
		livenessProgress = 0;
		faceDetected = false;
		promptMessage = aiModelReady ? 'Menyiapkan pemindai wajah…' : 'Mengaktifkan sensor kamera…';

		// Trigger background model load if not ready yet
		if (!aiModelReady && !aiModelLoading) {
			void loadAiModels();
		}

		try {
			const stream = await openCameraStream(facingMode, currentCameraDeviceId || undefined);
			mediaStream = stream;
			const track = stream.getVideoTracks()[0];
			if (track) {
				const settings = track.getSettings();
				if (settings.deviceId) currentCameraDeviceId = settings.deviceId;
				if (settings.facingMode) {
					facingMode = settings.facingMode === 'environment' ? 'environment' : 'user';
				}
			}
			if (videoElement) {
				videoElement.srcObject = stream;
				await videoElement.play().catch(() => {});
			}
			startLivenessAnalysis();
		} catch (cause) {
			error = cause instanceof Error ? cause.message : t('Kamera tidak dapat diakses. Mohon berikan izin akses kamera.');
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
		if (landmarkCanvasElement) {
			const ctx = landmarkCanvasElement.getContext('2d');
			if (ctx) ctx.clearRect(0, 0, landmarkCanvasElement.width, landmarkCanvasElement.height);
		}
		cameraActive = false;
	}

	async function switchCamera() {
		if (!cameraActive || switchingCamera) return;
		switchingCamera = true;
		error = '';

		const previousFacing = facingMode;
		const targetFacing: 'user' | 'environment' = previousFacing === 'user' ? 'environment' : 'user';

		// Stop detection loop while switching
		if (detectionTimer) {
			window.clearInterval(detectionTimer);
			detectionTimer = null;
		}

		try {
			// Query available video devices
			const allDevices = await navigator.mediaDevices.enumerateDevices().catch(() => []);
			const videoDevices = allDevices.filter((d) => d.kind === 'videoinput');

			// If only 1 video device exists, inform user and keep current camera running
			if (videoDevices.length === 1 && previousFacing === 'user') {
				promptMessage = 'Perangkat hanya memiliki satu kamera aktif.';
				startLivenessAnalysis();
				switchingCamera = false;
				return;
			}

			// Find candidate device matching target facing mode
			let targetDeviceId: string | undefined;
			if (videoDevices.length > 1) {
				const isTargetUser = targetFacing === 'user';
				const matched = videoDevices.find((d) =>
					isTargetUser
						? /front|user|selfie|face/i.test(d.label)
						: /back|rear|environment|main|wide/i.test(d.label)
				);
				if (matched) {
					targetDeviceId = matched.deviceId;
				} else {
					// Cycle to next device in list
					const activeDeviceId = mediaStream?.getVideoTracks()[0]?.getSettings()?.deviceId;
					const currentIndex = videoDevices.findIndex((d) => d.deviceId === activeDeviceId);
					const nextIndex = currentIndex >= 0 ? (currentIndex + 1) % videoDevices.length : 0;
					targetDeviceId = videoDevices[nextIndex].deviceId;
				}
			}

			// Stop previous stream tracks cleanly
			if (mediaStream) {
				mediaStream.getTracks().forEach((track) => track.stop());
				mediaStream = null;
			}

			const newStream = await openCameraStream(targetFacing, targetDeviceId);
			mediaStream = newStream;
			facingMode = targetFacing;

			const track = newStream.getVideoTracks()[0];
			if (track) {
				const settings = track.getSettings();
				if (settings.deviceId) currentCameraDeviceId = settings.deviceId;
				if (settings.facingMode) {
					facingMode = settings.facingMode === 'environment' ? 'environment' : 'user';
				}
			}

			if (videoElement) {
				videoElement.srcObject = newStream;
				await videoElement.play().catch(() => {});
			}

			// Reset liveness progress smoothly for the new perspective
			livenessStage = 'position';
			livenessProgress = 0;
			faceDetected = false;
			startLivenessAnalysis();
		} catch (cause) {
			console.error('Camera switch failed:', cause);
			// Revert to original facing mode stream if switch failed
			try {
				facingMode = previousFacing;
				const restoredStream = await openCameraStream(previousFacing);
				mediaStream = restoredStream;
				if (videoElement) {
					videoElement.srcObject = restoredStream;
					await videoElement.play().catch(() => {});
				}
				startLivenessAnalysis();
			} catch {
				error = t('Gagal mengakses kamera setelah peralihan. Silakan coba lagi.');
				cameraActive = false;
			}
		} finally {
			switchingCamera = false;
		}
	}

	// Interactive Liveness: Straight -> Blink -> Turn Left -> Turn Right -> Steady Center -> Snapshot
	function startLivenessAnalysis() {
		if (detectionTimer) window.clearInterval(detectionTimer);
		let warmupTicks = 0;
		let analysisInFlight = false;
		const livenessState = createLivenessState(facingMode);

		detectionTimer = window.setInterval(async () => {
			if (!videoElement || !cameraActive || capturedPhoto) return;
			if (analysisInFlight) return;
			analysisInFlight = true;
			try {
				const video = videoElement;
				if (video.videoWidth === 0 || video.videoHeight === 0) return;

			warmupTicks++;
			if (warmupTicks < 3) {
				promptMessage = 'Mengaktifkan sensor kamera…';
				return;
			}

			// Must wait for face detector
			if (aiModelLoading || !aiModelReady || !faceapiInstance) {
				promptMessage = 'Menyiapkan pemindai wajah… Mohon tunggu sebentar.';
				faceDetected = false;
				return;
			}

			// --- Primary AI Detection via @vladmandic/face-api ---
			// eslint-disable-next-line @typescript-eslint/no-explicit-any
			let detection: any = null;
			try {
				const options = new faceapiInstance.TinyFaceDetectorOptions({ inputSize: 160, scoreThreshold: 0.45 });
				let detectionTask = faceapiInstance.detectSingleFace(video, options).withFaceLandmarks(true);
				if (recognitionModelReady && (livenessStage === 'steady' || livenessStage === 'complete')) {
					detectionTask = detectionTask.withFaceDescriptor();
				}
				detection = await detectionTask;
			} catch (err) {
				console.warn('Face detection pass error:', err);
			}

			// STRICT: If no face is detected, STOP and DO NOT ADVANCE!
			if (!detection) {
				faceDetected = false;
				promptMessage = 'Wajah tidak terdeteksi. Posisikan wajah Anda di dalam oval.';
				resetTransientEvidence(livenessState);
				if (landmarkCanvasElement) {
					const lCanvas = landmarkCanvasElement;
					const lCtx = lCanvas.getContext('2d');
					if (lCtx) lCtx.clearRect(0, 0, lCanvas.width, lCanvas.height);
				}
				return;
			}

			faceDetected = true;
			const positions = detection.landmarks.positions;
			const box = detection.detection.box;

			// Check centering & distance relative to camera viewport
			const faceCenterX = (box.x + box.width / 2) / video.videoWidth;
			const faceCenterY = (box.y + box.height / 2) / video.videoHeight;
			const relWidth = box.width / video.videoWidth;

			// Keep landmark overlay clean without dot clutter
			if (landmarkCanvasElement) {
				const lCanvas = landmarkCanvasElement;
				if (lCanvas.width !== video.videoWidth || lCanvas.height !== video.videoHeight) {
					lCanvas.width = video.videoWidth;
					lCanvas.height = video.videoHeight;
				}
				const lCtx = lCanvas.getContext('2d');
				if (lCtx) {
					lCtx.clearRect(0, 0, lCanvas.width, lCanvas.height);
				}
			}

			// Save 128-D descriptor when extracted
			if (detection.descriptor) {
				capturedDescriptor = Array.from(detection.descriptor);
			}

			// Distance & Framing checks
			if (relWidth < 0.20) {
				promptMessage = 'Dekatkan wajah Anda ke kamera.';
				resetTransientEvidence(livenessState);
				return;
			}
			if (faceCenterX < 0.20 || faceCenterX > 0.80 || faceCenterY < 0.15 || faceCenterY > 0.85) {
				promptMessage = 'Posisikan wajah Anda tepat di tengah oval.';
				resetTransientEvidence(livenessState);
				return;
			}

			// Real landmark biometric metrics
			let leftEAR = 0.28;
			let rightEAR = 0.28;
			if (positions.length >= 48) {
				leftEAR = calculateEyeAspectRatio(positions.slice(36, 42));
				rightEAR = calculateEyeAspectRatio(positions.slice(42, 48));
			}

			let yawRatio = 1.0;
			if (positions.length >= 31) {
				const leftJaw = positions[0];
				const rightJaw = positions[16];
				const nose = positions[30];
				const dLeft = Math.hypot(nose.x - leftJaw.x, nose.y - leftJaw.y);
				const dRight = Math.hypot(nose.x - rightJaw.x, nose.y - rightJaw.y);
				yawRatio = dLeft / (dRight + 1e-4);
			}

			const outcome = advanceLiveness(livenessState, {
				leftEAR,
				rightEAR,
				yawRatio,
				descriptorReady: capturedDescriptor?.length === 128
			});
			livenessStage = livenessState.stage as LivenessStage;
			livenessProgress = livenessState.progress;
			promptMessage = livenessState.prompt;

			if (outcome === 'step') playAudioChime('step');
			if (outcome === 'complete') {
				promptMessage = 'Verifikasi selesai. Mengambil foto…';
				playAudioChime('complete');
				try {
					navigator.vibrate?.([80, 40, 80]);
				} catch {}
				window.setTimeout(() => {
					void takeSnapshot();
				}, 200);
			}
			} finally {
				analysisInFlight = false;
			}
		}, 100);
	}

	async function takeSnapshot() {
		if (!videoElement || !canvasElement) return;
		const video = videoElement;

		// 1. Strict face verification before taking snapshot
		if (faceapiInstance && aiModelReady) {
			try {
				const options = new faceapiInstance.TinyFaceDetectorOptions({ inputSize: 160, scoreThreshold: 0.45 });
				let detTask = faceapiInstance.detectSingleFace(video, options).withFaceLandmarks(true);
				if (recognitionModelReady) {
					detTask = detTask.withFaceDescriptor();
				}
				const det = await detTask;
				if (!det) {
					error = t('Wajah tidak terdeteksi! Posisikan wajah Anda tepat di dalam bingkai sebelum mengambil foto.');
					promptMessage = 'Wajah tidak terdeteksi. Posisikan wajah di dalam oval.';
					faceDetected = false;
					return;
				}
				if (det.descriptor) {
					capturedDescriptor = Array.from(det.descriptor);
				}
			} catch (err) {
				console.warn('Face verification error on snapshot:', err);
			}
		}

		if (!faceDetected && (!capturedDescriptor || capturedDescriptor.length !== 128)) {
			error = t('Wajah tidak terdeteksi. Pastikan wajah Anda terlihat jelas di dalam kamera.');
			return;
		}

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
		if (!capturedDescriptor || capturedDescriptor.length !== 128) {
			error = t('Data wajah tidak terdeteksi lengkap. Silakan ulangi pemindaian.');
			return;
		}
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
			const payload = {
				version: '1.0',
				liveness_verified: true,
				challenges_passed: ['position', 'blink', 'turn_left', 'turn_right', 'steady'],
				photo_url: uploaded.download_url,
				storage_driver: uploaded.driver,
				file_name: uploaded.file_name,
				descriptor: capturedDescriptor || [],
				descriptor_dimension: capturedDescriptor ? capturedDescriptor.length : 0,
				enrolled_at: new Date().toISOString()
			};

			await request('/v1/workspace-records', {
				method: 'POST',
				body: JSON.stringify({
					module_key: '/face-enrollment',
					title: `Biometrik Wajah · ${userName}`,
					detail: JSON.stringify(payload),
					status: 'Terverifikasi',
					amount: 'Wajah Terdaftar'
				})
			});

			notice = t('Data wajah berhasil didaftarkan dan siap digunakan untuk presensi!');
			capturedPhoto = null;
			await loadEnrollment();
		} catch (cause) {
			error = cause instanceof Error ? cause.message : t('Gagal menyimpan data wajah ke server.');
		} finally {
			busy = false;
		}
	}

	async function removeEnrollment() {
		if (!enrolledRecord || busy) return;
		const confirmed = window.confirm(t('Hapus data pendaftaran wajah Anda dari sistem?'));
		if (!confirmed) return;

		busy = true;
		error = '';
		notice = '';
		try {
			await request(`/v1/workspace-records/${encodeURIComponent(enrolledRecord.id)}/delete`, {
				method: 'POST'
			});
			notice = t('Pendaftaran wajah berhasil dihapus dari sistem.');
			enrolledRecord = null;
			enrolledPhotoUrl = null;
			enrolledDescriptor = null;
			enrolledDetailSummary = '';
		} catch (cause) {
			error = cause instanceof Error ? cause.message : t('Gagal menghapus data wajah.');
		} finally {
			busy = false;
		}
	}

	onMount(() => {
		void loadEnrollment();
		void loadAiModels();
	});

	onDestroy(() => {
		stopCamera();
	});
</script>

<svelte:head>
	<title>{t('Pendaftaran Wajah')} · PasPapan</title>
</svelte:head>

<main class="face-page">
	<PageHeader
		backHref="/app/today"
		title={t('Pendaftaran Wajah')}
	/>

	{#if notice}
		<div class="notice-banner" role="status">
			<Icon name="verified" size={15} />
			<span>{t(notice)}</span>
		</div>
	{/if}

	{#if error}
		<div class="error-banner" role="alert">
			<Icon name="close" size={15} />
			<span>{t(error)}</span>
		</div>
	{/if}

	{#if loading}
		<section class="panel loading-panel">
			<p class="muted">{t('Memuat data pendaftaran wajah…')}</p>
		</section>
	{:else if cameraActive}
		<!-- Live Camera & Interactive Biometric Liveness Verification -->
		<section class="panel camera-panel">
			<div class="stepper-bar-wrap" aria-label={`${t('Tahap Liveness')}: ${t(currentStageConfig.label)}`}>
				<div class="challenge-meta">
					<span>{t('Verifikasi gerakan')}</span>
					<span class="challenge-state"><span class="live-dot green"></span>{livenessStage === 'complete' ? t('Selesai') : t('Aktif')}</span>
				</div>
				<div class="stepper-segments">
					{#each STAGES as stage, idx}
						{@const isDone = currentStageIndex > idx}
						{@const isCurrent = currentStageIndex === idx}
						<div class="segment-track" class:done={isDone} class:active={isCurrent}>
							{#if isDone}
								<div class="segment-fill full"></div>
							{:else if isCurrent}
								<div class="segment-fill pulse" style={`width: ${Math.max(20, ((livenessProgress % 20) / 20) * 100)}%`}></div>
							{/if}
						</div>
					{/each}
				</div>
				<div class="stepper-header-row">
					<span class="stepper-count-badge">{Math.min(currentStageIndex + 1, 5)} {t('dari')} 5</span>
					<strong class="stepper-stage-title">{t(currentStageConfig.label)}</strong>
					<span class="stepper-percent">{livenessProgress}%</span>
				</div>
			</div>

			<!-- Mobile Portrait Camera Viewport HUD -->
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
				<canvas
					bind:this={landmarkCanvasElement}
					class="landmark-overlay-canvas"
					class:mirrored={facingMode === 'user'}
				></canvas>
				<canvas bind:this={canvasElement} class="hidden-canvas"></canvas>

				<!-- Floating Top Status & Camera Flip -->
				<div class="viewport-floating-top">
					<div class="ai-live-pill" class:active={faceDetected}>
						<span class="live-dot" class:green={faceDetected}></span>
						<span>{faceDetected ? t('Wajah terkunci') : t('Cari wajah')}</span>
					</div>

					<button
						type="button"
						class="switch-camera-pill"
						class:loading={switchingCamera}
						disabled={switchingCamera}
						aria-label={t('Balik kamera depan/belakang')}
						title={t('Balik kamera depan/belakang')}
						onclick={switchCamera}
					>
						<Icon name="switch" size={15} />
					</button>
				</div>

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

					<!-- Biometric Corner Target Brackets -->
					<div class="hud-corner top-left"></div>
					<div class="hud-corner top-right"></div>
					<div class="hud-corner bottom-left"></div>
					<div class="hud-corner bottom-right"></div>

					<!-- In-Oval Motion Direction Prompts -->
					{#if faceDetected}
						{#if livenessStage === 'turn_left'}
							<div class="in-oval-prompt left-turn">
								<span class="animated-arrow left">◀</span>
								<span class="turn-text">{t('Tengok Kiri')}</span>
							</div>
						{:else if livenessStage === 'turn_right'}
							<div class="in-oval-prompt right-turn">
								<span class="turn-text">{t('Tengok Kanan')}</span>
								<span class="animated-arrow right">▶</span>
							</div>
						{:else if livenessStage === 'blink'}
							<div class="in-oval-prompt blink-act">
								<Icon name="eye" size={28} />
								<span class="turn-text">{t('Kedipkan Mata')}</span>
							</div>
						{:else if livenessStage === 'steady'}
							<div class="in-oval-prompt steady-act">
								<Icon name="verified" size={28} />
								<span class="turn-text">{t('Tahan Posisi')}</span>
							</div>
						{/if}
					{:else}
						<div class="in-oval-prompt missing-face">
							<Icon name="face" size={34} />
							<span class="turn-text">{t('Arahkan Wajah')}</span>
						</div>
					{/if}
				</div>
			</div>

			<div class="guidance-banner" aria-live="polite">
				<div class="guidance-icon-badge" class:success={faceDetected}>
					{#if livenessStage === 'blink'}
						<Icon name="eye" size={18} />
					{:else if livenessStage === 'turn_left' || livenessStage === 'turn_right'}
						<Icon name="face" size={18} />
					{:else if livenessStage === 'steady'}
						<Icon name="verified" size={18} />
					{:else}
						<Icon name="face" size={18} />
					{/if}
				</div>
				<div class="guidance-body">
					<span class="guidance-label">{t('Instruksi saat ini')}</span>
					<p class="guidance-prompt">{t(promptMessage)}</p>
				</div>
			</div>

			<!-- Bottom Touch Action Bar (Cancel Only - No Bypass Allowed) -->
			<div class="camera-actions-grid">
				<button type="button" class="action-btn secondary full-width" onclick={stopCamera}>
					{t('Batalkan verifikasi')}
				</button>
			</div>
		</section>
	{:else if capturedPhoto}
		<!-- Snapshot Preview & Database Confirmation -->
		<section class="panel preview-panel">
			<div class="preview-card">
				<div class="photo-frame">
					<img src={capturedPhoto} alt={t('Snapshot Wajah')} class="face-preview-img" />
					<div class="verified-stamp">
						<Icon name="verified" size={13} />
						<span>{t('Terverifikasi')}</span>
					</div>
				</div>
				<div class="preview-details">
					<h3>{t('Pendaftaran Selesai')}</h3>
					<p class="muted">
						{t('Wajah Anda berhasil diverifikasi dan siap digunakan untuk validasi presensi.')}
					</p>
					<div class="preview-meta">
						<span class="meta-item"><Icon name="check" size={13} /> {t('Wajah Terverifikasi Jelas')}</span>
						<span class="meta-item"><Icon name="check" size={13} /> {t('Gerakan Refleks Alami Valid')}</span>
						<span class="meta-item"><Icon name="check" size={13} /> {t('Siap Digunakan untuk Presensi')}</span>
					</div>
				</div>
			</div>

			<div class="preview-actions-grid">
				<button type="button" class="action-btn secondary" disabled={busy} onclick={startCamera}>
					<Icon name="refresh" size={14} />
					<span>{t('Foto Ulang')}</span>
				</button>
				<button type="button" class="action-btn primary" disabled={busy} onclick={saveBiometricEnrollment}>
					<Icon name="verified" size={15} />
					<span>{busy ? t('Menyimpan…') : t('Simpan Data Wajah')}</span>
				</button>
			</div>
		</section>
	{:else if enrolledRecord}
		<!-- Already Enrolled State -->
		<section class="panel status-panel">
			<div class="enrolled-card">
				<div class="face-thumbnail-wrap">
					{#if enrolledPhotoUrl}
						<img src={enrolledPhotoUrl} alt={t('Foto Profil Wajah')} class="face-thumbnail" />
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
							<span class="live-dot green"></span>
							{enrolledRecord.status}
						</span>
					</div>
					<p class="meta">{enrolledDetailSummary ? t(enrolledDetailSummary) : t('Wajah aktif untuk presensi')}</p>
					<div class="enrolled-badges">
						<span class="feature-chip">
							<Icon name="check" size={11} />
							{t('Wajah Aktif untuk Presensi')}
						</span>
						<span class="updated-time">{t('Tersimpan')}: {enrolledRecord.updated}</span>
					</div>
				</div>
			</div>

			<div class="enrolled-actions">
				<button type="button" class="action-btn secondary danger-text" disabled={busy} onclick={removeEnrollment}>
					{t('Hapus Data Wajah')}
				</button>
				<button type="button" class="action-btn primary" onclick={startCamera}>
					<Icon name="refresh" size={14} />
					{t('Pindai Ulang')}
				</button>
			</div>
		</section>
	{:else}
		<!-- Introduction / Ready to Enroll -->
		<section class="panel intro-panel">
			<div class="intro-hero">
				<div class="intro-icon-badge">
					<Icon name="face" size={32} />
				</div>
				<h3>{t('Pendaftaran Wajah Presensi')}</h3>
				<p class="muted intro-desc">
					{t('Daftarkan wajah Anda untuk verifikasi identitas kehadiran yang cepat, praktis, dan akurat saat melakukan scan presensi.')}
				</p>
			</div>

			<div class="tips-card">
				<span class="tips-label">{t('Petunjuk Pengambilan:')}</span>
				<div class="tip-row">
					<span class="tip-num">1</span>
					<div class="tip-body">
						<strong>{t('Posisikan Wajah Tegak')}</strong>
						<p>{t('Pegang ponsel sejajar mata dan posisikan wajah di dalam bingkai oval.')}</p>
					</div>
				</div>
				<div class="tip-row">
					<span class="tip-num">2</span>
					<div class="tip-body">
						<strong>{t('Pencahayaan yang Cukup')}</strong>
						<p>{t('Pastikan ruangan terang dan hindari sorotan lampu dari belakang kepala.')}</p>
					</div>
				</div>
				<div class="tip-row">
					<span class="tip-num">3</span>
					<div class="tip-body">
						<strong>{t('Ikuti Arahan Gerakan')}</strong>
						<p>{t('Lakukan kedipan mata dan tengok santai sesuai panduan di layar.')}</p>
					</div>
				</div>
			</div>

			<button type="button" class="action-btn primary full-width start-enroll-btn" onclick={startCamera}>
				<Icon name="face" size={16} />
				<span>{t('Mulai Pendaftaran Wajah')}</span>
			</button>
		</section>
	{/if}
</main>

<style>
	.face-page {
		max-width: 480px;
		margin-inline: auto;
		padding: 16px 14px 36px;
		display: grid;
		gap: 14px;
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

	/* Sleek Segmented Stepper Bar */
	.stepper-bar-wrap {
		display: flex;
		flex-direction: column;
		gap: 7px;
		margin-bottom: 9px;
		padding: 9px 10px;
		border: 1px solid var(--line);
		border-radius: var(--radius);
		background: #f7faf7;
	}
	.challenge-meta {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 8px;
		color: var(--muted);
		font-size: 9.5px;
		font-weight: 750;
		letter-spacing: .08em;
		text-transform: uppercase;
	}
	.challenge-state {
		display: inline-flex;
		align-items: center;
		gap: 4px;
		letter-spacing: 0;
		color: var(--primary-strong);
	}
	.stepper-segments {
		display: grid;
		grid-template-columns: repeat(5, 1fr);
		gap: 6px;
		width: 100%;
	}
	.segment-track {
		height: 3px;
		border-radius: 999em;
		background: var(--line, #e2e8f0);
		overflow: hidden;
		position: relative;
	}
	.segment-fill {
		height: 100%;
		border-radius: 999em;
		background: var(--primary, #1e4d2b);
		transition: width 0.25s ease;
	}
	.segment-fill.full {
		width: 100%;
		background: #10b981;
	}
	.segment-fill.pulse {
		animation: barPulse 1.2s ease-in-out infinite alternate;
	}
	@keyframes barPulse {
		0% { opacity: 0.75; }
		100% { opacity: 1; }
	}
	.stepper-header-row {
		display: flex;
		align-items: center;
		justify-content: space-between;
		font-size: 12px;
	}
	.stepper-count-badge {
		font-weight: 700;
		color: var(--primary, #1e4d2b);
		background: var(--primary-soft);
		padding: 2px 6px;
		border-radius: var(--radius-sm);
		font-size: 11px;
	}
	.stepper-stage-title {
		font-weight: 600;
		color: var(--text, #0f172a);
	}
	.stepper-percent {
		font-weight: 700;
		color: var(--muted, #64748b);
		font-variant-numeric: tabular-nums;
	}

	/* Camera Viewport HUD (Mobile Portrait Ratio) */
	.camera-viewport-wrap {
		position: relative;
		width: 100%;
		aspect-ratio: 3 / 4;
		min-height: 380px;
		max-height: 480px;
		background: #111914;
		border-radius: var(--radius-lg);
		overflow: hidden;
		display: flex;
		align-items: center;
		justify-content: center;
		border: 1px solid #26362b;
		box-shadow: 0 12px 28px -18px rgba(10, 20, 13, 0.62);
	}
	.camera-video,
	.landmark-overlay-canvas {
		position: absolute;
		inset: 0;
		width: 100%;
		height: 100%;
		object-fit: cover;
	}
	.camera-video.mirrored,
	.landmark-overlay-canvas.mirrored {
		transform: scaleX(-1);
	}
	.landmark-overlay-canvas {
		pointer-events: none;
		z-index: 1;
	}
	.hidden-canvas {
		display: none;
	}

	/* Top Floating Status & Controls */
	.viewport-floating-top {
		position: absolute;
		top: 12px;
		left: 12px;
		right: 12px;
		display: flex;
		align-items: center;
		justify-content: space-between;
		pointer-events: none;
		z-index: 3;
	}
	.ai-live-pill {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		padding: 4px 10px;
		border-radius: var(--radius-sm);
		background: rgba(10, 15, 29, 0.75);
		backdrop-filter: blur(8px);
		-webkit-backdrop-filter: blur(8px);
		border: 1px solid rgba(255, 255, 255, 0.12);
		color: #e2e8f0;
		font-size: 11px;
		font-weight: 600;
		pointer-events: auto;
	}
	.live-dot {
		width: 6px;
		height: 6px;
		border-radius: 50%;
		background: #f87171;
	}
	.live-dot.green {
		background: #10b981;
		box-shadow: 0 0 6px #10b981;
		animation: dotPulse 1.2s infinite;
	}
	@keyframes dotPulse {
		0%, 100% { transform: scale(1); opacity: 1; }
		50% { transform: scale(1.4); opacity: 0.5; }
	}
	.switch-camera-pill {
		width: 34px;
		height: 34px;
		border-radius: 50%;
		display: grid;
		place-items: center;
		background: rgba(10, 15, 29, 0.75);
		backdrop-filter: blur(8px);
		-webkit-backdrop-filter: blur(8px);
		border: 1px solid rgba(255, 255, 255, 0.12);
		color: #f8fafc;
		cursor: pointer;
		pointer-events: auto;
		transition: background 0.15s ease, transform 0.15s ease;
	}
	.switch-camera-pill:active {
		transform: scale(0.92);
	}
	.switch-camera-pill:disabled {
		opacity: 0.6;
		cursor: not-allowed;
	}
	.switch-camera-pill.loading :global(svg) {
		animation: spinCamera 0.8s linear infinite;
	}
	@keyframes spinCamera {
		from {
			transform: rotate(0deg);
		}
		to {
			transform: rotate(360deg);
		}
	}

	/* Responsive Biometric Oval HUD */
	.scanner-oval {
		position: absolute;
		width: 196px;
		height: 260px;
		max-width: 68%;
		border: 2px dashed rgba(255, 255, 255, 0.45);
		border-radius: 50% / 50%;
		box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.54);
		pointer-events: none;
		transition: border-color 0.25s ease, box-shadow 0.25s ease;
		overflow: hidden;
		display: flex;
		align-items: center;
		justify-content: center;
		z-index: 2;
	}
	.scanner-oval:not(.detected) {
		border-color: rgba(248, 113, 113, 0.75);
		border-style: dashed;
		box-shadow: 0 0 0 9999px rgba(5, 12, 8, 0.34);
	}
	.scanner-oval.detected {
		border-color: #8acb82;
		border-style: solid;
		box-shadow: 0 0 0 9999px rgba(5, 12, 8, 0.34), 0 0 0 4px rgba(138, 203, 130, 0.14);
	}
	.scanner-oval.detected.stage-blink {
		border-color: #eab937;
		box-shadow: 0 0 0 9999px rgba(5, 12, 8, 0.34), 0 0 0 4px rgba(234, 185, 55, 0.16);
	}
	.scanner-oval.detected.stage-left,
	.scanner-oval.detected.stage-right {
		border-color: #c084fc;
		box-shadow: 0 0 0 9999px rgba(5, 12, 8, 0.34), 0 0 0 4px rgba(192, 132, 252, 0.15);
	}
	.scanner-oval.detected.stage-steady,
	.scanner-oval.detected.stage-complete {
		border-color: #10b981;
		box-shadow: 0 0 0 9999px rgba(5, 12, 8, 0.34), 0 0 0 4px rgba(16, 185, 129, 0.16);
	}

	/* Corner Target Brackets inside Oval */
	.hud-corner {
		position: absolute;
		width: 14px;
		height: 14px;
		color: rgba(56, 189, 248, 0.7);
		pointer-events: none;
	}
	.hud-corner.top-left {
		top: 18px;
		left: 18px;
		border-top: 2px solid currentColor;
		border-left: 2px solid currentColor;
	}
	.hud-corner.top-right {
		top: 18px;
		right: 18px;
		border-top: 2px solid currentColor;
		border-right: 2px solid currentColor;
	}
	.hud-corner.bottom-left {
		bottom: 18px;
		left: 18px;
		border-bottom: 2px solid currentColor;
		border-left: 2px solid currentColor;
	}
	.hud-corner.bottom-right {
		bottom: 18px;
		right: 18px;
		border-bottom: 2px solid currentColor;
		border-right: 2px solid currentColor;
	}

	.scan-laser-beam {
		display: none;
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
		gap: 6px;
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
		-webkit-backdrop-filter: blur(4px);
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
	.missing-face {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 6px;
		color: rgba(248, 113, 113, 0.95);
		animation: pulseMissing 1.6s ease-in-out infinite;
	}
	@keyframes pulseMissing {
		0%, 100% { opacity: 0.6; transform: scale(0.96); }
		50% { opacity: 1; transform: scale(1.04); }
	}

	/* Clean Guidance Banner Below Viewport */
	.guidance-banner {
		display: flex;
		align-items: center;
		gap: 10px;
		background: #f8fbf8;
		border: 1px solid var(--line);
		border-radius: var(--radius);
		padding: 9px 11px;
		margin-top: 8px;
	}
	.guidance-icon-badge {
		width: 32px;
		height: 32px;
		border-radius: 50%;
		display: grid;
		place-items: center;
		background: rgba(30, 77, 43, 0.08);
		color: var(--primary);
		flex-shrink: 0;
	}
	.guidance-icon-badge.success {
		background: #ebf8f0;
		color: #1e6b37;
	}
	.guidance-body {
		flex: 1;
		min-width: 0;
	}
	.guidance-label {
		display: block;
		margin-bottom: 1px;
		color: var(--muted);
		font-size: 8.5px;
		font-weight: 800;
		letter-spacing: .08em;
		text-transform: uppercase;
	}
	.guidance-prompt {
		margin: 0;
		font-size: 13px;
		font-weight: 600;
		color: var(--text);
		line-height: 1.35;
	}

	/* Bottom Actions */
	.camera-actions-grid {
		display: flex;
		margin-top: 10px;
		width: 100%;
	}
	.preview-actions-grid {
		display: grid;
		grid-template-columns: 1fr 1.6fr;
		gap: 8px;
		margin-top: 10px;
		width: 100%;
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
		background: var(--accent-solid);
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
	.enrolled-badges {
		display: flex;
		align-items: center;
		gap: 8px;
		flex-wrap: wrap;
	}
	.feature-chip {
		display: inline-flex;
		align-items: center;
		gap: 3px;
		font-size: 10.5px;
		font-weight: 700;
		color: #065f46;
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

	/* Intro Hero Panel (Clean & Professional) */
	.intro-panel {
		display: grid;
		gap: 14px;
		text-align: center;
	}
	.intro-hero {
		display: flex;
		flex-direction: column;
		align-items: center;
		padding: 10px 4px 4px;
	}
	.intro-icon-badge {
		width: 58px;
		height: 58px;
		border-radius: 50%;
		display: grid;
		place-items: center;
		background: rgba(30, 77, 43, 0.08);
		color: var(--primary);
		margin-bottom: 12px;
	}
	.intro-hero h3 {
		margin: 0 0 6px;
		font-size: 16.5px;
		font-weight: 750;
		color: var(--text);
	}
	.intro-desc {
		margin: 0;
		font-size: 12.5px;
		line-height: 1.5;
		color: var(--muted);
		max-width: 380px;
	}
	.tips-card {
		background: var(--surface-subtle, #f8fafc);
		border: 1px solid var(--line);
		border-radius: var(--radius);
		padding: 12px 14px;
		text-align: left;
		display: grid;
		gap: 10px;
	}
	.tips-label {
		font-size: 10.5px;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: 0.5px;
		color: var(--muted);
	}
	.tip-row {
		display: flex;
		align-items: flex-start;
		gap: 10px;
	}
	.tip-num {
		width: 20px;
		height: 20px;
		border-radius: 50%;
		background: rgba(30, 77, 43, 0.1);
		color: var(--primary);
		font-size: 10.5px;
		font-weight: 800;
		display: grid;
		place-items: center;
		flex-shrink: 0;
		margin-top: 1px;
	}
	.tip-body {
		min-width: 0;
		flex: 1;
	}
	.tip-body strong {
		display: block;
		font-size: 12px;
		font-weight: 650;
		color: var(--text);
		margin-bottom: 1px;
	}
	.tip-body p {
		margin: 0;
		font-size: 11px;
		color: var(--muted);
		line-height: 1.35;
	}
	.start-enroll-btn {
		min-height: 42px;
		font-size: 13px;
		font-weight: 650;
	}

	@media (max-width: 760px) {
		.face-page { padding: 10px 10px 20px; gap: 9px; }
		.panel { padding: 10px; }
		.notice-banner, .error-banner { padding: 6px 9px; font-size: 10.5px; }
		.intro-panel { gap: 10px; }
		.intro-hero { padding: 5px 2px 2px; }
		.intro-icon-badge { width: 42px; height: 42px; margin-bottom: 8px; }
		.intro-icon-badge :global(svg) { width: 24px; height: 24px; }
		.intro-hero h3 { margin-bottom: 4px; font-size: 14px; }
		.intro-desc { max-width: 330px; font-size: 11px; line-height: 1.42; }
		.tips-card { padding: 9px 10px; gap: 7px; }
		.tips-label { font-size: 9px; }
		.tip-row { gap: 8px; }
		.tip-num { width: 18px; height: 18px; font-size: 9px; }
		.tip-body strong { font-size: 10.5px; }
		.tip-body p { font-size: 10px; line-height: 1.3; }
		.start-enroll-btn { min-height: 36px; font-size: 11.5px; }
		.camera-panel { padding: 9px; }
		.stepper-bar-wrap { gap: 5px; margin-bottom: 7px; padding: 7px 8px; }
		.stepper-segments { gap: 5px; }
		.stepper-header-row { font-size: 10.5px; }
		.stepper-count-badge { padding: 1px 5px; font-size: 9.5px; }
		.camera-viewport-wrap { aspect-ratio: auto; min-height: 0; height: min(88vw, 350px); max-height: 350px; }
		.viewport-floating-top { inset: 9px 9px auto; }
		.ai-live-pill { gap: 5px; padding: 3px 7px; font-size: 9.5px; }
		.switch-camera-pill { width: 30px; height: 30px; }
		.scanner-oval { width: 148px; height: 202px; }
		.guidance-banner { gap: 8px; padding: 7px 9px; margin-top: 7px; }
		.guidance-icon-badge { width: 27px; height: 27px; }
		.guidance-prompt { font-size: 11px; }
		.camera-actions-grid { margin-top: 7px; }
		.action-btn.full-width { min-height: 34px; font-size: 11px; }
	}
</style>
