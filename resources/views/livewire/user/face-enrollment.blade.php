<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <div class="user-page-surface relative overflow-hidden">
            <x-user.page-header
                :back-href="route('home')"
                :title="__('Face ID Setup')"
                title-id="face-enrollment-title">
                <x-slot name="icon">
                    <x-heroicon-o-face-smile class="h-5 w-5" />
                </x-slot>
            </x-user.page-header>

            <div class="p-6 lg:p-8">
                @if ($isEnrolled && !$isCapturing)
                    <div class="max-w-md mx-auto text-center">
                        <div
                            class="mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-full bg-sky-50 ring-1 ring-sky-100 dark:bg-sky-950/30 dark:ring-sky-900/50">
                            <x-heroicon-o-check-circle class="h-12 w-12 text-sky-600 dark:text-sky-300" />
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ __('Face ID Active') }}</h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-8">
                            {{ __('Your face is registered for attendance verification.') }}</p>

                        <div class="flex flex-col gap-3">
                            @if (\App\Helpers\Editions::attendanceLocked())
                                <button type="button"
                                    @click.prevent="$dispatch('feature-lock', { title: @js(__('Face ID Locked')), message: @js(__('Face ID Biometrics is an Enterprise Feature. Please Upgrade.')) })"
                                    class="w-full px-4 py-3 bg-primary-600 text-white rounded-xl hover:bg-primary-700 font-semibold transition flex items-center justify-center gap-2">
                                    <x-heroicon-o-arrow-path class="h-5 w-5" />
                                    {{ __('Update Face ID') }}
                                    <x-heroicon-o-lock-closed class="h-4 w-4" />
                                </button>
                                <button type="button"
                                    @click.prevent="$dispatch('feature-lock', { title: @js(__('Face ID Locked')), message: @js(__('Face ID Biometrics is an Enterprise Feature. Please Upgrade.')) })"
                                    class="w-full px-4 py-3 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800 rounded-xl hover:bg-red-100 dark:hover:bg-red-900/40 font-semibold transition">
                                    {{ __('Remove Face ID') }}
                                    <x-heroicon-o-lock-closed class="ml-2 inline h-4 w-4" />
                                </button>
                            @else
                                <button wire:click="startCapture" data-apk-face-update
                                    class="w-full px-4 py-3 bg-primary-600 text-white rounded-xl hover:bg-primary-700 font-semibold transition flex items-center justify-center gap-2">
                                    <x-heroicon-o-arrow-path class="h-5 w-5" />
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
                        <div class="relative mb-5 aspect-[4/3] overflow-hidden rounded-[2rem] bg-slate-950 shadow-2xl shadow-slate-950/20 ring-1 ring-white/10">
                            <video x-ref="video" autoplay playsinline muted class="h-full w-full object-cover contrast-105 saturate-105"></video>
                            <canvas x-ref="overlay" class="absolute inset-0 w-full h-full"></canvas>
                        </div>

                        <div class="min-h-[48px] flex items-center justify-center mb-5">
                            <div class="px-4 py-2 rounded-full text-sm font-medium flex items-center gap-2 text-center"
                                role="status" aria-live="polite" aria-atomic="true"
                                :class="statusToneClass()">
                                <template x-if="showSpinner()">
                                    <x-heroicon-o-arrow-path class="h-4 w-4 shrink-0 animate-spin" />
                                </template>
                                <span x-text="statusMessage"></span>
                            </div>
                        </div>

                        <div class="text-sm text-center text-gray-700 dark:text-gray-300 mb-6 min-h-[20px]"
                            x-text="hintMessage"></div>

                        <div class="flex gap-3">
                            @if ($isEnrolled)
                                <button wire:click="cancelCapture"
                                    class="flex-1 px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 font-semibold transition">
                                    {{ __('Cancel') }}
                                </button>
                            @endif

                            @if (\App\Helpers\Editions::attendanceLocked())
                                <button
                                    @click.prevent="$dispatch('feature-lock', { title: @js(__('Face ID Locked')), message: @js(__('Face ID Biometrics is an Enterprise Feature. Please Upgrade.')) })"
                                    class="flex-1 px-4 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-semibold transition flex items-center justify-center gap-2">
                                    <x-heroicon-o-camera class="h-5 w-5" />
                                    {{ __('Capture Face') }}
                                    <x-heroicon-o-lock-closed class="h-4 w-4" />
                                </button>
                            @else
                                <button @click="capture()" :disabled="!canCapture()"
                                    :class="canCapture() ? 'bg-primary-600 hover:bg-primary-700 text-white' :
                                        'bg-gray-300 dark:bg-gray-600 text-white cursor-not-allowed'"
                                    class="@if ($isEnrolled) flex-1 @else w-full @endif px-4 py-3 rounded-xl font-semibold transition flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
                                    <x-heroicon-o-camera class="h-5 w-5" />
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
                passChallenge: @js(__('Complete the face movement check')),
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
                livenessHint: @js(__('Turn your head to both sides naturally before capture')),
                readyHint: @js(__('You can capture now')),
                loadingHint: @js(__('Preparing browser face detection')),
                turnBothSidesHint: @js(__('Look to one side, face forward, then look to the other side')),
                turnOppositeHint: @js(__('Now turn your head to the other side')),
                recenterHint: @js(__('Face forward briefly before the next turn')),
                finalCenterHint: @js(__('Face forward briefly to finish verification')),
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
                livenessPassed: false,
                captureBusy: false,
                challengeStep: 'turn-first-side',
                firstTurnDirection: null,
                leftTurnDetected: false,
                rightTurnDetected: false,
                recenterFrames: 0,
                requiredRecenterFrames: 1,

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
                        return 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-100';
                    }

                    if (['turn-face', 'turn-opposite-face', 'arming-liveness', 'recenter-face'].includes(this.status)) {
                        return 'bg-emerald-50 text-emerald-900 ring-1 ring-emerald-700/20 dark:bg-emerald-950/40 dark:text-emerald-100 dark:ring-emerald-300/30';
                    }

                    if (this.status === 'error') {
                        return 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300';
                    }

                    return 'bg-slate-100 text-slate-900 dark:bg-slate-800 dark:text-slate-100';
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
                    this.livenessPassed = false;
                    this.challengeStep = 'turn-first-side';
                    this.firstTurnDirection = null;
                    this.leftTurnDetected = false;
                    this.rightTurnDetected = false;
                    this.recenterFrames = 0;
                    this.setStage(stage, message, messages.livenessHint);
                },

                getGuideRect(width, height) {
                    const guideWidth = width * 0.54;
                    const guideHeight = height * 0.8;

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
                    const toneStyles = {
                        neutral: {
                            stroke: 'rgba(255, 255, 255, 0.82)',
                            shadow: 'rgba(255, 255, 255, 0.18)',
                            dash: [],
                        },
                        warning: {
                            stroke: '#f59e0b',
                            shadow: 'rgba(245, 158, 11, 0.26)',
                            dash: [14, 10],
                        },
                        success: {
                            stroke: '#38bdf8',
                            shadow: 'rgba(56, 189, 248, 0.28)',
                            dash: [],
                        },
                        danger: {
                            stroke: '#fb7185',
                            shadow: 'rgba(251, 113, 133, 0.28)',
                            dash: [8, 8],
                        },
                    };
                    const guideStyle = toneStyles[guideTone] || toneStyles.neutral;
                    const centerX = guide.x + (guide.width / 2);
                    const centerY = guide.y + (guide.height / 2);
                    const radiusX = guide.width / 2;
                    const radiusY = guide.height / 2;

                    ctx.fillStyle = 'rgba(2, 6, 23, 0.18)';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    ctx.save();
                    ctx.globalCompositeOperation = 'destination-out';
                    ctx.beginPath();
                    ctx.ellipse(centerX, centerY, radiusX, radiusY, 0, 0, Math.PI * 2);
                    ctx.fill();
                    ctx.restore();

                    ctx.save();
                    ctx.setLineDash(guideStyle.dash);
                    ctx.strokeStyle = guideStyle.stroke;
                    ctx.lineWidth = 4;
                    ctx.shadowColor = guideStyle.shadow;
                    ctx.shadowBlur = 18;
                    ctx.beginPath();
                    ctx.ellipse(centerX, centerY, radiusX, radiusY, 0, 0, Math.PI * 2);
                    ctx.stroke();
                    ctx.restore();

                    if (detections.length > 1) {
                        for (const detection of detections) {
                            const box = detection.detection ? detection.detection.box : detection.box;
                            const faceCenterX = box.x + (box.width / 2);
                            const faceCenterY = box.y + (box.height / 2);

                            ctx.save();
                            ctx.setLineDash([8, 8]);
                            ctx.strokeStyle = toneStyles.danger.stroke;
                            ctx.lineWidth = 3;
                            ctx.beginPath();
                            ctx.ellipse(faceCenterX, faceCenterY, box.width / 2, box.height / 2, 0, 0, Math.PI * 2);
                            ctx.stroke();
                            ctx.restore();
                        }
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

                getTurnDirection(yaw, threshold) {
                    const yawDelta = yaw - this.baselineYaw;

                    if (yawDelta <= -threshold) {
                        return 'left';
                    }

                    if (yawDelta >= threshold) {
                        return 'right';
                    }

                    return null;
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

                        const headTurnThreshold = 0.05;
                        const recenterThreshold = 0.12;
                        const headTurnDirection = this.getTurnDirection(yaw, headTurnThreshold);
                        const reCentered = Math.abs(yaw - this.baselineYaw) < recenterThreshold;

                        if (this.challengeStep === 'turn-first-side') {
                            if (headTurnDirection) {
                                this.firstTurnDirection = headTurnDirection;
                                this.leftTurnDetected = this.leftTurnDetected || headTurnDirection === 'left';
                                this.rightTurnDetected = this.rightTurnDetected || headTurnDirection === 'right';
                                this.challengeStep = 'recenter-after-first-turn';
                                this.recenterFrames = 0;
                                this.setStage('recenter-face', messages.holdStill, messages.recenterHint);
                                return;
                            }

                            this.setStage('turn-face', messages.passChallenge, messages.turnBothSidesHint);
                            return;
                        }

                        if (this.challengeStep === 'recenter-after-first-turn') {
                            if (reCentered) {
                                this.recenterFrames += 1;
                            } else {
                                this.recenterFrames = 0;
                            }

                            if (this.recenterFrames >= this.requiredRecenterFrames && this.firstTurnDirection) {
                                this.challengeStep = 'turn-opposite-side';
                                this.setStage('turn-opposite-face', messages.passChallenge, messages.turnOppositeHint);
                                return;
                            }

                            this.setStage('recenter-face', messages.holdStill, messages.recenterHint);
                            return;
                        }

                        if (this.challengeStep === 'turn-opposite-side') {
                            if (headTurnDirection && headTurnDirection !== this.firstTurnDirection) {
                                this.leftTurnDetected = this.leftTurnDetected || headTurnDirection === 'left';
                                this.rightTurnDetected = this.rightTurnDetected || headTurnDirection === 'right';
                                this.challengeStep = 'recenter-final';
                                this.recenterFrames = 0;
                                this.setStage('recenter-face', messages.holdStill, messages.finalCenterHint);
                                return;
                            }

                            this.setStage('turn-opposite-face', messages.passChallenge, messages.turnOppositeHint);
                            return;
                        }

                        if (this.challengeStep === 'recenter-final') {
                            if (reCentered) {
                                this.recenterFrames += 1;
                            } else {
                                this.recenterFrames = 0;
                            }

                            if (this.recenterFrames >= this.requiredRecenterFrames && this.leftTurnDetected && this.rightTurnDetected) {
                                this.livenessPassed = true;
                                this.stopDetection();
                                this.setStage('ready-to-capture', messages.liveConfirmed, messages.readyHint);
                                return;
                            }

                            this.setStage('recenter-face', messages.holdStill, messages.finalCenterHint);
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
                        this.setStage('turn-face', messages.passChallenge, messages.turnBothSidesHint);
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
