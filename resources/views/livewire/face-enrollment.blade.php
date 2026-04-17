<div class="py-6 lg:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div
            class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden relative">

            <div
                class="px-5 py-4 lg:px-8 lg:py-6 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-white dark:bg-gray-800 relative z-10">
                <div class="flex items-center gap-3">
                    <x-secondary-button href="{{ route('home') }}"
                        class="!rounded-xl !px-3 !py-2 border-gray-200 dark:border-gray-600 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600">
                        <x-heroicon-o-arrow-left class="h-4 w-4 text-gray-500 dark:text-gray-300" />
                    </x-secondary-button>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span
                            class="p-1.5 bg-primary-50 text-primary-600 dark:bg-primary-900/50 dark:text-primary-400 rounded-lg">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="M15.75 6.75a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.118a7.5 7.5 0 0115 0A17.933 17.933 0 0112 21.75a17.933 17.933 0 01-7.5-1.632z">
                                </path>
                            </svg>
                        </span>
                        {{ __('Face ID Setup') }}
                        @if (\App\Helpers\Editions::attendanceLocked())
                            🔒
                        @endif
                    </h3>
                </div>
            </div>

            <div class="p-6 lg:p-8">
                @if ($isEnrolled && !$isCapturing)
                    <div class="max-w-md mx-auto text-center">
                        <div
                            class="w-24 h-24 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                            <svg class="w-12 h-12 text-green-600 dark:text-green-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ __('Face ID Active') }}</h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-8">
                            {{ __('Your face is registered for attendance verification.') }}</p>

                        <div class="flex flex-col gap-3">
                            @php
                                $lockedIcon = \App\Helpers\Editions::attendanceLocked() ? ' 🔒' : '';
                            @endphp

                            @if (\App\Helpers\Editions::attendanceLocked())
                                <button type="button"
                                    @click.prevent="$dispatch('feature-lock', { title: 'Face ID Locked', message: 'Face ID Biometrics is an Enterprise Feature 🔒. Please Upgrade.' })"
                                    class="w-full px-4 py-3 bg-primary-600 text-white rounded-xl hover:bg-primary-700 font-semibold transition flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                        </path>
                                    </svg>
                                    {{ __('Update Face ID') }}{{ $lockedIcon }}
                                </button>
                                <button type="button"
                                    @click.prevent="$dispatch('feature-lock', { title: 'Face ID Locked', message: 'Face ID Biometrics is an Enterprise Feature 🔒. Please Upgrade.' })"
                                    class="w-full px-4 py-3 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800 rounded-xl hover:bg-red-100 dark:hover:bg-red-900/40 font-semibold transition">
                                    {{ __('Remove Face ID') }}{{ $lockedIcon }}
                                </button>
                            @else
                                <button wire:click="startCapture"
                                    class="w-full px-4 py-3 bg-primary-600 text-white rounded-xl hover:bg-primary-700 font-semibold transition flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                        </path>
                                    </svg>
                                    {{ __('Update Face ID') }}
                                </button>
                                <button wire:click="removeFace"
                                    wire:confirm="{{ __('Are you sure you want to remove Face ID?') }}"
                                    class="w-full px-4 py-3 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800 rounded-xl hover:bg-red-100 dark:hover:bg-red-900/40 font-semibold transition">
                                    {{ __('Remove Face ID') }}
                                </button>
                            @endif
                        </div>
                    </div>
                @else
                    <div data-face-enrollment-root
                        wire:key="face-enrollment-{{ $isEnrolled ? 'enrolled' : 'capture' }}-{{ $isCapturing ? 'capturing' : 'idle' }}"
                        x-data="faceEnrollment()" x-init="init()" class="max-w-lg mx-auto">
                        <div class="relative aspect-[4/3] bg-gray-900 rounded-2xl overflow-hidden mb-5">
                            <video x-ref="video" autoplay playsinline muted class="w-full h-full object-cover"></video>
                            <canvas x-ref="overlay" class="absolute inset-0 w-full h-full"></canvas>
                        </div>

                        <div class="min-h-[48px] flex items-center justify-center mb-5">
                            <div class="px-4 py-2 rounded-full text-sm font-medium flex items-center gap-2 text-center"
                                :class="statusToneClass()">
                                <template x-if="showSpinner()">
                                    <svg class="animate-spin h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                </template>
                                <span x-text="statusMessage"></span>
                            </div>
                        </div>

                        <div class="text-sm text-center text-gray-500 dark:text-gray-400 mb-6 min-h-[20px]"
                            x-text="hintMessage"></div>

                        <div class="flex gap-3">
                            @if ($isEnrolled)
                                <button wire:click="cancelCapture"
                                    class="flex-1 px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 font-semibold transition">
                                    {{ __('Cancel') }}
                                </button>
                            @endif

                            @php
                                $lockedIcon = \App\Helpers\Editions::attendanceLocked() ? ' 🔒' : '';
                            @endphp

                            @if (\App\Helpers\Editions::attendanceLocked())
                                <button
                                    @click.prevent="$dispatch('feature-lock', { title: 'Face ID Locked', message: 'Face ID Biometrics is an Enterprise Feature 🔒. Please Upgrade.' })"
                                    class="flex-1 px-4 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-semibold transition flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z">
                                        </path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    {{ __('Capture Face') }}{{ $lockedIcon }}
                                </button>
                            @else
                                <button @click="capture()" :disabled="!canCapture()"
                                    :class="canCapture() ? 'bg-primary-600 hover:bg-primary-700 text-white' :
                                        'bg-gray-300 dark:bg-gray-600 text-white cursor-not-allowed'"
                                    class="@if ($isEnrolled) flex-1 @else w-full @endif px-4 py-3 rounded-xl font-semibold transition flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z">
                                        </path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span x-text="buttonLabel()"></span>
                                </button>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script src="/assets/js/face-api.min.js"></script>
    <script>
        function pointDistance(a, b) {
            return Math.hypot(a.x - b.x, a.y - b.y);
        }

        function averagePoint(points) {
            const total = points.reduce((carry, point) => ({
                x: carry.x + point.x,
                y: carry.y + point.y,
            }), {
                x: 0,
                y: 0,
            });

            return {
                x: total.x / points.length,
                y: total.y / points.length,
            };
        }

        function eyeAspectRatio(eye) {
            const verticalA = pointDistance(eye[1], eye[5]);
            const verticalB = pointDistance(eye[2], eye[4]);
            const horizontal = pointDistance(eye[0], eye[3]);

            return horizontal ? (verticalA + verticalB) / (2 * horizontal) : 0;
        }

        function getYawScore(landmarks) {
            const leftEyeCenter = averagePoint(landmarks.getLeftEye());
            const rightEyeCenter = averagePoint(landmarks.getRightEye());
            const nose = landmarks.getNose();
            const noseTip = nose[3] || nose[0];
            const eyeMidX = (leftEyeCenter.x + rightEyeCenter.x) / 2;
            const eyeDistance = Math.max(Math.abs(rightEyeCenter.x - leftEyeCenter.x), 1);

            return (noseTip.x - eyeMidX) / eyeDistance;
        }

        function buildFaceGeometryDescriptor(landmarks) {
            const leftEyeCenter = averagePoint(landmarks.getLeftEye());
            const rightEyeCenter = averagePoint(landmarks.getRightEye());
            const eyeMidX = (leftEyeCenter.x + rightEyeCenter.x) / 2;
            const eyeMidY = (leftEyeCenter.y + rightEyeCenter.y) / 2;
            const eyeDistance = Math.max(pointDistance(leftEyeCenter, rightEyeCenter), 1);
            const roll = Math.atan2(
                rightEyeCenter.y - leftEyeCenter.y,
                rightEyeCenter.x - leftEyeCenter.x
            );
            const cos = Math.cos(-roll);
            const sin = Math.sin(-roll);
            const excluded = new Set([0, 1, 15, 16]);
            const descriptor = [2];

            landmarks.positions.forEach((point, index) => {
                if (excluded.has(index)) {
                    return;
                }

                const translatedX = (point.x - eyeMidX) / eyeDistance;
                const translatedY = (point.y - eyeMidY) / eyeDistance;
                const rotatedX = (translatedX * cos) - (translatedY * sin);
                const rotatedY = (translatedX * sin) + (translatedY * cos);

                descriptor.push(Number(rotatedX.toFixed(6)));
                descriptor.push(Number(rotatedY.toFixed(6)));
            });

            return descriptor;
        }

        function faceEnrollment() {
            const messages = {
                loadingModels: @js(__('Loading Face ID models...')),
                openingCamera: @js(__('Opening camera...')),
                centerFace: @js(__('Center your face inside the guide')),
                holdStill: @js(__('Hold still for a moment')),
                passChallenge: @js(__('Complete the face challenge naturally')),
                liveConfirmed: @js(__('Live face confirmed. Ready to capture.')),
                savingFace: @js(__('Saving Face ID...')),
                capturingFrames: @js(__('Capturing face frames...')),
                tooManyFaces: @js(__('Only one face can be visible')),
                moveCloser: @js(__('Move a little closer to the camera')),
                moveBack: @js(__('Move slightly back from the camera')),
                alignFace: @js(__('Keep your face centered inside the guide')),
                descriptorFailed: @js(__('Face capture failed. Keep steady and try again.')),
                cameraError: @js(__('Could not access camera for Face ID setup.')),
                faceError: @js(__('We could not verify a live face right now. Please try again.')),
                captureFace: @js(__('Capture Face')),
                permissionHint: @js(__('Allow camera access to continue')),
                livenessHint: @js(__('Natural face check: blink once or turn your head slightly')),
                readyHint: @js(__('You can capture now')),
                loadingHint: @js(__('Preparing browser face detection')),
                blinkHint: @js(__('Blink once naturally')),
                turnHint: @js(__('Turn your head a little to one side')),
                recenterHint: @js(__('Face forward briefly')),
            };
            const modelUrls = [
                window.resolveRuntimeAssetUrl ? window.resolveRuntimeAssetUrl('/models') :
                `${window.location.origin}/models`,
            ].filter((value, index, array) => value && array.indexOf(value) === index);
            const previewOptions = {
                inputSize: 160,
                scoreThreshold: 0.5,
            };
            const captureOptions = [{
                    inputSize: 224,
                    scoreThreshold: 0.3,
                },
                {
                    inputSize: 224,
                    scoreThreshold: 0.2,
                },
            ];

            return {
                status: 'loading-models',
                statusMessage: messages.loadingModels,
                hintMessage: messages.loadingHint,
                stream: null,
                detectionTimer: null,
                isDetecting: false,
                previewModelUrl: null,
                initialized: false,
                stableFrames: 0,
                requiredStableFrames: 2,
                baselineYaw: null,
                blinkArmed: false,
                livenessPassed: false,
                captureBusy: false,
                challengeStep: 'collect-actions',
                blinkDetected: false,
                turnDetected: false,
                recenterFrames: 0,
                requiredRecenterFrames: 1,
                baselineEar: null,

                async init() {
                    if (this.initialized) {
                        return;
                    }

                    this.initialized = true;

                    try {
                        this.cleanup();
                        await this.loadPreviewModels();
                        await this.startCamera();
                        this.startDetection();
                    } catch (error) {
                        await this.reportClientError('init', error);
                        this.failHard(messages.cameraError, error);
                    }
                },

                setStage(stage, message, hint = null) {
                    this.status = stage;
                    this.statusMessage = message;
                    this.hintMessage = hint ?? '';
                },

                formatError(error) {
                    if (!error) {
                        return 'Unknown error';
                    }

                    if (typeof error === 'string') {
                        return error;
                    }

                    return error.message || error.name || JSON.stringify(error);
                },

                async reportClientError(stage, error, context = {}) {
                    try {
                        await this.$wire.call('reportClientError', stage, this.formatError(error), context);
                    } catch (_) {
                        // Swallow logging failures so the capture flow remains usable.
                    }
                },

                showSpinner() {
                    return ['loading-models', 'opening-camera', 'saving'].includes(this.status);
                },

                statusToneClass() {
                    if (this.status === 'ready-to-capture') {
                        return 'bg-green-50 text-green-700 dark:bg-green-950/40 dark:text-green-300';
                    }

                    if (['blink-or-turn', 'arming-liveness', 'recenter-face'].includes(this.status)) {
                        return 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300';
                    }

                    if (this.status === 'error') {
                        return 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300';
                    }

                    return 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200';
                },

                buttonLabel() {
                    return this.status === 'saving' ? messages.savingFace : messages.captureFace;
                },

                canCapture() {
                    return this.status === 'ready-to-capture' && !this.captureBusy;
                },

                withTimeout(promise, timeoutMs, label) {
                    return Promise.race([
                        promise,
                        new Promise((_, reject) => {
                            window.setTimeout(() => {
                                reject(new Error(`${label} timed out after ${timeoutMs}ms`));
                            }, timeoutMs);
                        }),
                    ]);
                },

                async waitForRef(name, timeoutMs = 2200) {
                    const startedAt = Date.now();

                    while (Date.now() - startedAt < timeoutMs) {
                        await this.$nextTick();

                        if (this.$refs?.[name]) {
                            return this.$refs[name];
                        }

                        await new Promise((resolve) => window.setTimeout(resolve, 50));
                    }

                    throw new TypeError(`${name} element is not ready`);
                },

                async waitForGetUserMedia(timeoutMs = 2200) {
                    const startedAt = Date.now();

                    while (Date.now() - startedAt < timeoutMs) {
                        const getUserMedia = navigator.mediaDevices?.getUserMedia?.bind(navigator.mediaDevices);

                        if (getUserMedia) {
                            return getUserMedia;
                        }

                        await new Promise((resolve) => window.setTimeout(resolve, 50));
                    }

                    throw new TypeError('Camera API is not available');
                },

                async loadPreviewModels() {
                    if (typeof faceapi === 'undefined') {
                        throw new Error('face-api.js is unavailable');
                    }

                    let lastError = null;
                    for (const modelUrl of modelUrls) {
                        try {
                            await Promise.all([
                                faceapi.nets.tinyFaceDetector.loadFromUri(modelUrl),
                                faceapi.nets.faceLandmark68Net.loadFromUri(modelUrl),
                            ]);
                            this.previewModelUrl = modelUrl;
                            return;
                        } catch (error) {
                            lastError = error;
                        }
                    }

                    throw lastError ?? new Error('Preview models failed to load');
                },

                async startCamera() {
                    this.setStage('opening-camera', messages.openingCamera, messages.permissionHint);
                    const video = await this.waitForRef('video');
                    const getUserMedia = await this.waitForGetUserMedia();

                    try {
                        this.stream = await getUserMedia({
                            video: {
                                facingMode: 'user',
                                width: {
                                    ideal: 480,
                                },
                                height: {
                                    ideal: 360,
                                },
                                frameRate: {
                                    ideal: 24,
                                    max: 24,
                                },
                            },
                        });
                    } catch (error) {
                        this.stream = await getUserMedia({
                            video: true,
                        });
                    }

                    video.srcObject = this.stream;
                    await new Promise((resolve) => {
                        if (video.readyState >= 1) {
                            resolve();
                            return;
                        }

                        video.onloadedmetadata = () => resolve();
                    });
                    await video.play();

                    this.resetLiveness(messages.centerFace);
                },

                startDetection() {
                    this.stopDetection();
                    this.queueDetection(120);
                },

                stopDetection() {
                    if (this.detectionTimer) {
                        clearTimeout(this.detectionTimer);
                        this.detectionTimer = null;
                    }
                    this.isDetecting = false;
                },

                queueDetection(delay = 420) {
                    this.stopDetection();
                    this.detectionTimer = setTimeout(() => {
                        void this.runDetection();
                    }, delay);
                },

                resetLiveness(message, stage = 'align-face') {
                    this.stableFrames = 0;
                    this.baselineYaw = null;
                    this.baselineEar = null;
                    this.blinkArmed = false;
                    this.livenessPassed = false;
                    this.challengeStep = 'collect-actions';
                    this.blinkDetected = false;
                    this.turnDetected = false;
                    this.recenterFrames = 0;
                    this.setStage(stage, message, messages.livenessHint);
                },

                getGuideRect(width, height) {
                    const guideWidth = width * 0.58;
                    const guideHeight = height * 0.76;

                    return {
                        x: (width - guideWidth) / 2,
                        y: (height - guideHeight) / 2,
                        width: guideWidth,
                        height: guideHeight,
                    };
                },

                ensureCanvasSize(width, height) {
                    const canvas = this.$refs.overlay;
                    if (!canvas) {
                        return;
                    }

                    if (canvas.width !== width) {
                        canvas.width = width;
                    }

                    if (canvas.height !== height) {
                        canvas.height = height;
                    }
                },

                drawOverlay(detections = [], guideTone = 'neutral') {
                    const video = this.$refs.video;
                    const canvas = this.$refs.overlay;
                    if (!video || !canvas || !video.videoWidth || !video.videoHeight) {
                        return;
                    }

                    this.ensureCanvasSize(video.videoWidth, video.videoHeight);
                    const ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, canvas.width, canvas.height);

                    const guide = this.getGuideRect(canvas.width, canvas.height);
                    const guideStroke = guideTone === 'success' ?
                        '#22c55e' :
                        guideTone === 'warning' ?
                        '#f59e0b' :
                        guideTone === 'danger' ?
                        '#ef4444' :
                        'rgba(255,255,255,0.75)';

                    ctx.fillStyle = 'rgba(15, 23, 42, 0.28)';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    ctx.clearRect(guide.x, guide.y, guide.width, guide.height);

                    ctx.strokeStyle = guideStroke;
                    ctx.lineWidth = 3;
                    ctx.strokeRect(guide.x, guide.y, guide.width, guide.height);

                    for (const detection of detections) {
                        const box = detection.detection ? detection.detection.box : detection.box;
                        ctx.strokeStyle = detections.length > 1 ? '#ef4444' : guideStroke;
                        ctx.lineWidth = 2;
                        ctx.strokeRect(box.x, box.y, box.width, box.height);
                    }
                },

                isFaceAligned(box, guide, video) {
                    const centerX = box.x + (box.width / 2);
                    const centerY = box.y + (box.height / 2);
                    const minWidth = video.videoWidth * 0.24;
                    const maxWidth = video.videoWidth * 0.7;
                    const withinGuide = centerX >= guide.x + (guide.width * 0.08) &&
                        centerX <= guide.x + guide.width - (guide.width * 0.08) &&
                        centerY >= guide.y + (guide.height * 0.08) &&
                        centerY <= guide.y + guide.height - (guide.height * 0.08);

                    return {
                        aligned: withinGuide && box.width >= minWidth && box.width <= maxWidth,
                        tooSmall: box.width < minWidth,
                        tooLarge: box.width > maxWidth,
                    };
                },

                async runDetection() {
                    const video = this.$refs.video;
                    if (!video || !this.stream || this.captureBusy) {
                        this.stopDetection();
                        return;
                    }

                    if (!video.videoWidth || !video.videoHeight) {
                        this.queueDetection(250);
                        return;
                    }

                    if (this.isDetecting) {
                        this.queueDetection(250);
                        return;
                    }

                    this.isDetecting = true;

                    try {
                        const options = new faceapi.TinyFaceDetectorOptions(previewOptions);
                        const detections = await faceapi
                            .detectAllFaces(video, options)
                            .withFaceLandmarks();

                        const guide = this.getGuideRect(video.videoWidth, video.videoHeight);

                        if (!detections.length) {
                            this.drawOverlay([], 'neutral');
                            this.resetLiveness(messages.centerFace);
                            return;
                        }

                        if (detections.length > 1) {
                            this.drawOverlay(detections, 'danger');
                            this.resetLiveness(messages.tooManyFaces);
                            return;
                        }

                        const detection = detections[0];
                        const faceBox = detection.detection.box;
                        const alignment = this.isFaceAligned(faceBox, guide, video);

                        if (!alignment.aligned) {
                            this.drawOverlay([detection], alignment.tooSmall || alignment.tooLarge ? 'warning' :
                                'neutral');
                            if (alignment.tooSmall) {
                                this.resetLiveness(messages.moveCloser);
                            } else if (alignment.tooLarge) {
                                this.resetLiveness(messages.moveBack);
                            } else {
                                this.resetLiveness(messages.alignFace);
                            }
                            return;
                        }

                        const landmarks = detection.landmarks;
                        const averageEar = (eyeAspectRatio(landmarks.getLeftEye()) + eyeAspectRatio(landmarks
                            .getRightEye())) / 2;
                        const yaw = getYawScore(landmarks);

                        this.drawOverlay([detection], this.livenessPassed ? 'success' : 'warning');

                        if (this.livenessPassed) {
                            this.setStage('ready-to-capture', messages.liveConfirmed, messages.readyHint);
                            return;
                        }

                        if (this.stableFrames < this.requiredStableFrames) {
                            this.stableFrames += 1;
                            this.setStage('arming-liveness', messages.holdStill, messages.livenessHint);
                            return;
                        }

                        if (this.baselineYaw === null) {
                            this.baselineYaw = yaw;
                        }

                        if (this.baselineEar === null) {
                            this.baselineEar = averageEar;
                        } else if (!this.blinkDetected) {
                            this.baselineEar = Math.max(this.baselineEar, averageEar);
                        }

                        const blinkReadyThreshold = Math.max((this.baselineEar ?? averageEar) - 0.035, 0.19);
                        const blinkClosedThreshold = Math.min(
                            0.24,
                            Math.max(0.17, (this.baselineEar ?? averageEar) * 0.85)
                        );
                        const headTurnThreshold = 0.05;
                        const recenterThreshold = 0.12;

                        if (averageEar > blinkReadyThreshold) {
                            this.blinkArmed = true;
                        }

                        const blinkDetected = this.blinkArmed &&
                            averageEar < blinkClosedThreshold;
                        const headTurnDetected = Math.abs(yaw - this.baselineYaw) > headTurnThreshold;
                        const reCentered = Math.abs(yaw - this.baselineYaw) < recenterThreshold;

                        if (this.challengeStep === 'collect-actions') {
                            if (blinkDetected) {
                                this.blinkDetected = true;
                                this.livenessPassed = true;
                                this.stopDetection();
                                this.setStage('ready-to-capture', messages.liveConfirmed, messages.readyHint);
                                return;
                            }

                            if (headTurnDetected) {
                                this.turnDetected = true;
                                this.challengeStep = 'recenter';
                                this.recenterFrames = 0;
                                this.setStage('recenter-face', messages.holdStill, messages.recenterHint);
                                return;
                            }

                            this.setStage('blink-or-turn', messages.passChallenge, messages.livenessHint);
                            return;
                        }

                        if (this.challengeStep === 'recenter') {
                            if (reCentered) {
                                this.recenterFrames += 1;
                            } else {
                                this.recenterFrames = 0;
                            }

                            if (this.recenterFrames >= this.requiredRecenterFrames && this.turnDetected) {
                                this.livenessPassed = true;
                                this.stopDetection();
                                this.setStage('ready-to-capture', messages.liveConfirmed, messages.readyHint);
                                return;
                            }

                            this.setStage('recenter-face', messages.holdStill, messages.recenterHint);
                            return;
                        }
                    } catch (error) {
                        await this.reportClientError('liveness', error, {
                            status: this.status,
                            challenge_step: this.challengeStep,
                        });
                        this.drawOverlay([], 'danger');
                        this.setStage('error', messages.faceError, messages.livenessHint);
                    } finally {
                        this.isDetecting = false;
                        if (!this.captureBusy && !this.livenessPassed) {
                            this.queueDetection();
                        }
                    }
                },

                snapshotCanvas() {
                    const video = this.$refs.video;
                    const width = video.videoWidth;
                    const height = video.videoHeight;

                    if (!width || !height) {
                        throw new Error('Camera frame is not ready');
                    }

                    const scale = Math.min(1, 360 / width);
                    const canvas = document.createElement('canvas');
                    canvas.width = Math.max(1, Math.round(width * scale));
                    canvas.height = Math.max(1, Math.round(height * scale));
                    canvas.getContext('2d', {
                        willReadFrequently: true,
                    }).drawImage(video, 0, 0, canvas.width, canvas.height);

                    return canvas;
                },

                async captureSnapshots() {
                    const snapshots = [];

                    for (let index = 0; index < 2; index += 1) {
                        snapshots.push(this.snapshotCanvas());
                        await new Promise((resolve) => window.setTimeout(resolve, 120));
                    }

                    return snapshots;
                },

                async describeSnapshots(snapshots) {
                    let lastError = null;

                    for (const [snapshotIndex, snapshot] of snapshots.entries()) {
                        for (const [optionIndex, options] of captureOptions.entries()) {
                            try {
                                const detection = await this.withTimeout(
                                    faceapi
                                    .detectSingleFace(snapshot, new faceapi.TinyFaceDetectorOptions(options))
                                    .withFaceLandmarks(),
                                    4000,
                                    'face landmark extraction'
                                );

                                if (detection?.landmarks?.positions?.length === 68) {
                                    return buildFaceGeometryDescriptor(detection.landmarks);
                                }
                            } catch (error) {
                                lastError = error;
                            }
                        }
                    }

                    if (lastError) {
                        throw lastError;
                    }

                    throw new Error(messages.descriptorFailed);
                },

                async capture() {
                    if (!this.canCapture()) {
                        this.setStage('blink-or-turn', messages.passChallenge, messages.livenessHint);
                        return;
                    }

                    this.captureBusy = true;
                    this.stopDetection();
                    this.setStage('saving', messages.capturingFrames, messages.readyHint);

                    try {
                        const snapshots = await this.captureSnapshots();
                        const descriptor = await this.describeSnapshots(snapshots);

                        if (!descriptor || descriptor.length !== 129 || descriptor[0] !== 2) {
                            throw new Error(messages.descriptorFailed);
                        }

                        this.setStage('saving', messages.savingFace, messages.readyHint);
                        await this.$wire.call('saveFaceDescriptor', descriptor);
                        this.cleanup();
                    } catch (error) {
                        await this.reportClientError('capture', error, {
                            descriptor_mode: 'geometry',
                        });
                        this.captureBusy = false;
                        this.resetLiveness(messages.descriptorFailed);
                        this.startDetection();
                        Swal.fire(
                            @js(__('Face ID Error')),
                            `${messages.faceError}<br><br><small>Error: ${error?.message || error?.name || 'Unknown error'}</small>`,
                            'error'
                        );
                    }
                },

                stopStream() {
                    if (this.stream) {
                        this.stream.getTracks().forEach((track) => track.stop());
                        this.stream = null;
                    }

                    if (this.$refs?.video) {
                        this.$refs.video.pause?.();
                        this.$refs.video.srcObject = null;
                    }
                },

                clearOverlay() {
                    if (!this.$refs?.overlay) {
                        return;
                    }

                    const ctx = this.$refs.overlay.getContext?.('2d');
                    ctx?.clearRect(0, 0, this.$refs.overlay.width, this.$refs.overlay.height);
                },

                cleanup() {
                    this.stopDetection();
                    this.stopStream();
                    this.clearOverlay();
                    this.captureBusy = false;
                },

                failHard(message, error) {
                    this.cleanup();
                    this.setStage('error', message, messages.permissionHint);
                    void this.reportClientError('fatal', error, {
                        user_message: message,
                    });
                    Swal.fire(
                        @js(__('Camera Error')),
                        `${message}<br><br><small>Error: ${error?.name || error?.message || 'Unknown error'}</small>`,
                        'error'
                    );
                },
            };
        }
    </script>
@endpush
