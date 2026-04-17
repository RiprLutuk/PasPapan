<div id="scan-wrapper"
    class="w-full to-slate-100 dark:from-slate-900 dark:to-slate-800 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
    @php
        use Illuminate\Support\Carbon;
        $hasCheckedIn = !is_null($attendance?->time_in);
        $hasCheckedOut = !is_null($attendance?->time_out);
        $isComplete = $hasCheckedIn && $hasCheckedOut;
        $requirePhoto = \App\Models\Setting::getValue('feature.require_photo', 1);
    @endphp
    @if (!$isAbsence)
        <script src="{{ url('/assets/js/html5-qrcode.min.js') }}"></script>
    @endif

    @pushOnce('scripts')
        <script>
            const prefetchAttendanceScanAssets = () => {
                window.prefetchAttendanceScan?.();
            };

            document.addEventListener('DOMContentLoaded', prefetchAttendanceScanAssets, {
                once: true
            });
            document.addEventListener('livewire:navigated', prefetchAttendanceScanAssets);
        </script>
    @endpushOnce

    {{-- Always load face-api for selfie face detection --}}
    @if (!$isComplete && !$isAbsence)
        <script src="/assets/js/face-api.min.js"></script>
    @endif

    <div>
        @if (!$approvedAbsence && !$isComplete)
            <div
                class="mb-4 rounded-2xl border border-primary-100 bg-primary-50/80 p-4 text-sm text-primary-800 dark:border-primary-900/40 dark:bg-primary-950/30 dark:text-primary-200">
                <div class="flex items-start gap-3">
                    <div
                        class="mt-0.5 rounded-xl bg-white/80 p-2 text-primary-600 dark:bg-primary-900/40 dark:text-primary-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-bold">{{ __('Quick guide') }}</p>
                        <p class="mt-1 leading-relaxed">
                            @if ($hasCheckedIn && !$hasCheckedOut)
                                {{ __('Make sure your location is updated, scan the same work QR, then complete the selfie if your attendance flow requires it.') }}
                            @else
                                {{ __('Pick the correct shift, refresh your location if needed, then scan the work QR. If selfie verification appears, complete it before submitting.') }}
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Hidden canvas for frame capture --}}
        <canvas id="capture-canvas" class="hidden"></canvas>

        {{-- Camera Flash Effect --}}
        <div id="camera-flash"
            class="fixed inset-0 bg-white z-[60] pointer-events-none opacity-0 transition-opacity duration-200"></div>

        {{-- Face Verification Modal --}}
        @if ($requiresFaceVerification && $userFaceDescriptor)
            <div x-data="faceVerificationModal()" x-show="showModal" x-cloak
                class="fixed inset-0 z-[70] flex items-center justify-center bg-black/60 backdrop-blur-sm"
                @face-verify.window="openModal($event.detail)">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden"
                    @click.stop>
                    {{-- Header --}}
                    <div
                        class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span
                                class="p-1.5 bg-primary-50 text-primary-600 dark:bg-primary-900/50 dark:text-primary-400 rounded-lg">👤</span>
                            {{ __('Face Verification') }}
                        </h3>
                        <button @click="closeModal()"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    {{-- Camera Preview --}}
                    <div class="p-6">
                        <div class="relative aspect-square bg-gray-900 rounded-xl overflow-hidden mb-4"> <video
                                x-ref="video" autoplay playsinline muted class="w-full h-full object-cover"></video>
                            <canvas x-ref="overlay" class="absolute inset-0 w-full h-full"></canvas>

                            {{-- Status Indicator --}}
                            <div
                                class="absolute bottom-3 left-1/2 -translate-x-1/2 px-4 py-2 bg-black/60 backdrop-blur rounded-full text-white text-sm font-medium">
                                <span x-show="status === 'loading'" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    {{ __('Loading...') }}
                                </span>
                                <span x-show="status === 'ready'"
                                    class="text-yellow-400">{{ __('Look at the camera') }}</span>
                                <span x-show="status === 'verifying'"
                                    class="text-blue-400">{{ __('Verifying...') }}</span>
                                <span x-show="status === 'matched'" class="text-green-400 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    {{ __('Face matched!') }}
                                </span>
                                <span x-show="status === 'failed'"
                                    class="text-red-400">{{ __('Face not matched. Try again.') }}</span>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex gap-3">
                            <button @click="closeModal()"
                                class="flex-1 px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 font-semibold transition">
                                {{ __('Cancel') }}
                            </button>
                            <button @click="verify()" :disabled="status !== 'ready'"
                                :class="status === 'ready' ? 'bg-primary-600 hover:bg-primary-700' :
                                    'bg-gray-300 dark:bg-gray-600 cursor-not-allowed'"
                                class="flex-1 px-4 py-3 text-white rounded-xl font-semibold transition flex items-center justify-center gap-2">
                                {{ __('Verify') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            @pushOnce('scripts')
                <script>
                    const storedFaceDescriptor = @json($userFaceDescriptor);
                    const usesGeometryDescriptor = Array.isArray(storedFaceDescriptor) &&
                        storedFaceDescriptor.length === 129 &&
                        storedFaceDescriptor[0] === 2;
                    const geometryFaceThreshold = 1.35;

                    function modalPointDistance(a, b) {
                        return Math.hypot(a.x - b.x, a.y - b.y);
                    }

                    function modalAveragePoint(points) {
                        const total = points.reduce((carry, point) => ({
                            x: carry.x + point.x,
                            y: carry.y + point.y
                        }), {
                            x: 0,
                            y: 0
                        });

                        return {
                            x: total.x / points.length,
                            y: total.y / points.length
                        };
                    }

                    function buildFaceGeometryDescriptor(landmarks) {
                        const leftEyeCenter = modalAveragePoint(landmarks.getLeftEye());
                        const rightEyeCenter = modalAveragePoint(landmarks.getRightEye());
                        const eyeMidX = (leftEyeCenter.x + rightEyeCenter.x) / 2;
                        const eyeMidY = (leftEyeCenter.y + rightEyeCenter.y) / 2;
                        const eyeDistance = Math.max(modalPointDistance(leftEyeCenter, rightEyeCenter), 1);
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

                    function geometryDistance(sourceDescriptor, targetDescriptor) {
                        let sum = 0;

                        for (let index = 1; index < sourceDescriptor.length; index += 1) {
                            const delta = Number(sourceDescriptor[index]) - Number(targetDescriptor[index]);
                            sum += delta * delta;
                        }

                        return Math.sqrt(sum);
                    }

                    function faceVerificationModal() {
                        return {
                            showModal: false,
                            status: 'loading',
                            stream: null,
                            detectionInterval: null,
                            pendingCallback: null,
                            modelsLoaded: false,

                            async openModal(detail) {
                                this.pendingCallback = detail.callback;
                                this.showModal = true;
                                this.status = 'loading';

                                await this.$nextTick();
                                await this.init();
                            },

                            closeModal() {
                                this.cleanup();
                                this.showModal = false;
                                this.status = 'loading';
                                this.pendingCallback = null;
                            },

                            async init() {
                                try {
                                    // Load models if not already loaded
                                    if (!this.modelsLoaded) {
                                        const MODEL_URL = window.resolveRuntimeAssetUrl('/models');
                                        const modelLoads = [
                                            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                                            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL)
                                        ];

                                        if (!usesGeometryDescriptor) {
                                            modelLoads.push(faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL));
                                        }

                                        await Promise.all(modelLoads);
                                        this.modelsLoaded = true;
                                    }

                                    // Start front camera
                                    try {
                                        this.stream = await navigator.mediaDevices.getUserMedia({
                                            video: {
                                                facingMode: 'user',
                                                width: 480,
                                                height: 480
                                            }
                                        });
                                    } catch (err) {
                                        console.warn('Primary face verification camera request failed, attempting fallback...',
                                        err);
                                        try {
                                            this.stream = await navigator.mediaDevices.getUserMedia({
                                                video: true
                                            });
                                        } catch (fallbackErr) {
                                            console.error('Face Verification Fallback Camera error:', fallbackErr);
                                            throw fallbackErr;
                                        }
                                    }

                                    this.$refs.video.srcObject = this.stream;
                                    await new Promise(resolve => {
                                        this.$refs.video.onloadedmetadata = resolve;
                                    });

                                    this.status = 'ready';
                                } catch (error) {
                                    console.error('Face verification init error:', error);
                                    this.status = 'failed';
                                }
                            },

                            async verify() {
                                if (this.status !== 'ready') return;
                                this.status = 'verifying';

                                const video = this.$refs.video;
                                let detection;
                                if (usesGeometryDescriptor) {
                                    detection = await faceapi
                                        .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                                        .withFaceLandmarks();
                                } else {
                                    detection = await faceapi
                                        .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                                        .withFaceLandmarks()
                                        .withFaceDescriptor();
                                }

                                if (!detection) {
                                    this.status = 'failed';
                                    setTimeout(() => {
                                        this.status = 'ready';
                                    }, 2000);
                                    return;
                                }

                                const distance = usesGeometryDescriptor
                                    ? geometryDistance(buildFaceGeometryDescriptor(detection.landmarks), storedFaceDescriptor)
                                    : faceapi.euclideanDistance(detection.descriptor, new Float32Array(storedFaceDescriptor));

                                // Threshold: 0.6 is standard (lower = stricter)
                                if (distance < (usesGeometryDescriptor ? geometryFaceThreshold : 0.6)) {
                                    this.status = 'matched';
                                    setTimeout(() => {
                                        if (this.pendingCallback) {
                                            this.pendingCallback();
                                        }
                                        this.closeModal();
                                    }, 1000);
                                } else {
                                    this.status = 'failed';
                                    setTimeout(() => {
                                        this.status = 'ready';
                                    }, 2000);
                                }
                            },

                            cleanup() {
                                if (this.stream) {
                                    this.stream.getTracks().forEach(track => track.stop());
                                    this.stream = null;
                                }
                            }
                        };
                    }
                </script>
            @endpushOnce
        @endif

        @include('components.alert-messages')

        @if ($approvedAbsence)
            <div class="w-full max-w-md mx-auto bg-white rounded-3xl shadow-xl overflow-hidden p-8 text-center mt-6">
                <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <span class="text-4xl">✅</span>
                </div>

                <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ __('You are on Leave') }}</h2>
                <div
                    class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-bold uppercase tracking-wider bg-green-100 text-green-700 mb-6">
                    {{ __(ucfirst($approvedAbsence->status)) }}
                </div>

                <div class="bg-gray-50 rounded-2xl p-4 mb-6 text-left">
                    <p class="text-sm text-gray-500 mb-1">{{ __('Date') }}</p>
                    <p class="font-semibold text-gray-900 mb-3">{{ $approvedAbsence->date->format('d F Y') }}</p>

                    <p class="text-sm text-gray-500 mb-1">{{ __('Note') }}</p>
                    <p class="font-semibold text-gray-900 italic">"{{ $approvedAbsence->note }}"</p>
                </div>

                <a href="{{ route('home') }}"
                    class="block w-full py-4 rounded-xl bg-gray-900 text-white font-bold shadow-lg hover:shadow-xl hover:bg-black transition transform hover:-translate-y-1">
                    {{ __('Back to Dashboard') }}
                </a>
            </div>
        @elseif ($isComplete)
            {{-- Completion View --}}
            <div class="space-y-4 sm:space-y-6">
                <div
                    class="rounded-lg border border-gray-200 bg-white p-4 sm:p-6 shadow dark:border-gray-700 dark:bg-gray-800 text-center">
                    <div
                        class="success-checkmark mb-4 inline-flex items-center justify-center w-12 h-12 bg-gradient-to-br from-green-100 to-green-200 dark:from-green-500 dark:to-green-700 rounded-full shadow-lg">
                        <svg class="w-10 h-10 text-green-700 dark:text-white" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ __('Attendance Complete!') }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        {{ __('You\'ve successfully completed today\'s attendance') }}</p>
                </div>
            </div>
        @elseif ($hasCheckedIn && !$hasCheckedOut)
            {{-- Checked In View --}}
            <div class="space-y-4 sm:space-y-6">
                <div class="py-2 relative z-[60]">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-300 rounded-xl">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                                {{ __('You\'re Checked In!') }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('Scan QR to check out') }}</p>
                        </div>
                    </div>
                </div>

                <div class="w-full">
                    <div id="scanner-card-container" wire:ignore>
                        @component('components.scanner-card', ['title' => __('Scan to Check Out')])
                            @slot('headerActions')
                                @include('components.shift-selector', ['disabled' => true])
                            @endslot

                            {{-- Nested Location Card --}}
                            <x-location-card :title="__('Current Location')" mapId="currentLocationMap" :latitude="$currentLiveCoords[0] ?? null"
                                :longitude="$currentLiveCoords[1] ?? null" :showRefresh="true"
                                icon="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                iconColor="green" class="!p-0" />
                        @endcomponent
                    </div>

                    {{-- Selfie UI (Hidden by default) --}}
                    <div id="selfie-card-container"
                        class="hidden rounded-2xl border border-gray-200 bg-white p-4 shadow-lg dark:border-gray-700 dark:bg-gray-800 relative overflow-hidden">
                        <h3
                            class="text-sm font-bold text-gray-900 dark:text-white mb-3 text-center uppercase tracking-wider">
                            {{ __('Take a Selfie') }}</h3>
                        <div class="relative w-full aspect-square bg-gray-900 rounded-xl overflow-hidden mb-4">
                            <video id="selfie-video" autoplay playsinline
                                class="w-full h-full object-cover transform -scale-x-100"></video>
                            <div
                                class="absolute inset-0 border-[3px] border-white/50 rounded-[50%] m-8 pointer-events-none">
                            </div> {{-- Face Guide --}}
                        </div>
                        <div data-selfie-liveness-status
                            class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-700 dark:border-amber-900/70 dark:bg-amber-950/40 dark:text-amber-300">
                            {{ __('Center your face inside the guide') }}
                        </div>
                        <button data-selfie-capture-button onclick="window.captureAndSubmit()" disabled
                            class="w-full py-3 bg-gray-300 text-white font-bold rounded-lg shadow-lg flex items-center justify-center gap-2 cursor-not-allowed transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z">
                                </path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            {{ __('Capture & Check Out') }}
                        </button>
                    </div>
                </div>
            </div>
        @else
            {{-- Initial State - Not Checked In --}}
            <div class="flex flex-col gap-4 sm:gap-6 lg:flex-row">
                @if (!$isAbsence)
                    <div class="w-full">
                        <div id="scanner-card-container" wire:ignore>
                            @component('components.scanner-card', ['title' => __('Scan QR Code')])
                                @slot('headerActions')
                                    @include('components.shift-selector', ['disabled' => false])
                                @endslot

                                {{-- Nested Location Card --}}
                                <x-location-card :title="__('Current Location')" mapId="currentLocationMap" :latitude="$currentLiveCoords[0] ?? null"
                                    :longitude="$currentLiveCoords[1] ?? null" :showRefresh="true"
                                    icon="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                    iconColor="green" class="!p-0" />
                            @endcomponent
                        </div>

                        {{-- Selfie UI (Hidden by default) --}}
                        <div id="selfie-card-container"
                            class="hidden rounded-2xl border border-gray-200 bg-white p-4 shadow-lg dark:border-gray-700 dark:bg-gray-800 relative overflow-hidden">
                            <h3
                                class="text-sm font-bold text-gray-900 dark:text-white mb-3 text-center uppercase tracking-wider">
                                {{ __('Take a Selfie') }}</h3>
                            <div class="relative w-full aspect-square bg-gray-900 rounded-xl overflow-hidden mb-4">
                                <video id="selfie-video" autoplay playsinline
                                    class="w-full h-full object-cover transform -scale-x-100"></video>
                                <div
                                    class="absolute inset-0 border-[3px] border-white/50 rounded-[50%] m-8 pointer-events-none">
                                </div> {{-- Face Guide --}}
                            </div>
                            <div data-selfie-liveness-status
                                class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-700 dark:border-amber-900/70 dark:bg-amber-950/40 dark:text-amber-300">
                                {{ __('Center your face inside the guide') }}
                            </div>
                            <button data-selfie-capture-button onclick="window.captureAndSubmit()" disabled
                                class="w-full py-3 bg-gray-300 text-white font-bold rounded-lg shadow-lg flex items-center justify-center gap-2 cursor-not-allowed transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z">
                                    </path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                {{ __('Capture & Check In') }}
                            </button>

                            {{-- Processing UI (Hidden by default) --}}
                            <div id="processing-card-container"
                                class="hidden rounded-2xl border border-gray-200 bg-white p-8 shadow-lg dark:border-gray-700 dark:bg-gray-800 text-center">
                                <div class="relative w-20 h-20 mx-auto mb-6">
                                    <div
                                        class="absolute inset-0 border-4 border-gray-200 dark:border-gray-700 rounded-full">
                                    </div>
                                    <div
                                        class="absolute inset-0 border-4 border-blue-500 rounded-full border-t-transparent animate-spin">
                                    </div>

                                    {{-- Checkmark for final transition --}}
                                    <div id="processing-success"
                                        class="absolute inset-0 flex items-center justify-center opacity-0 transition-opacity duration-300">
                                        <svg class="w-10 h-10 text-green-500" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                </div>
                                <h3 id="processing-title"
                                    class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                    {{ __('Verifying...') }}</h3>
                                <p id="processing-text"
                                    class="text-sm text-gray-500 dark:text-gray-400 animate-pulse">
                                    {{ __('Syncing attendance data safely') }}</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

<script>
    document.addEventListener('livewire:navigated', function() {
        const state = {
            errorMsg: document.querySelector('#scanner-error'),
            hasCheckedIn: {{ $hasCheckedIn ? 'true' : 'false' }},
            hasCheckedOut: {{ $hasCheckedOut ? 'true' : 'false' }},
            isComplete: {{ $isComplete ? 'true' : 'false' }},
            isAbsence: {{ $isAbsence ? 'true' : 'false' }},
            maps: {},
            userLat: null,
            userLng: null,
            userAccuracy: null,
            gpsVariance: null,
            isRefreshing: false,
            facingMode: new URLSearchParams(window.location.search).get('camera') || 'environment',
            lastPhoto: null,
            requirePhoto: {{ $requirePhoto ? 'true' : 'false' }},
            isSelfieMode: false,
            scannedCode: null,
            timeSettings: @json($timeSettings),
            requiresFaceVerification: {{ $requiresFaceVerification && $userFaceDescriptor ? 'true' : 'false' }},
            userFaceDescriptor: {!! $userFaceDescriptor ? json_encode($userFaceDescriptor) : 'null' !!},
            faceModelsLoaded: false,
            approvedAbsence: {{ $approvedAbsence ? 'true' : 'false' }},
            faceDetectorOptions: null,
            selfieDetectionTimer: null,
            selfieDetecting: false,
            selfieLivenessPassed: false,
            selfieBaselineYaw: null,
            selfieBlinkArmed: false,
            captureBusy: false
        };

        function setSelfieLivenessStatus(message, tone = 'warning') {
            document.querySelectorAll('[data-selfie-liveness-status]').forEach((el) => {
                el.textContent = message;
                el.className = 'mb-4 rounded-xl px-3 py-2 text-sm font-medium';

                if (tone === 'success') {
                    el.classList.add('border', 'border-green-200', 'bg-green-50', 'text-green-700',
                        'dark:border-green-900/70', 'dark:bg-green-950/40', 'dark:text-green-300');
                    return;
                }

                if (tone === 'danger') {
                    el.classList.add('border', 'border-red-200', 'bg-red-50', 'text-red-700',
                        'dark:border-red-900/70', 'dark:bg-red-950/40', 'dark:text-red-300');
                    return;
                }

                el.classList.add('border', 'border-amber-200', 'bg-amber-50', 'text-amber-700',
                    'dark:border-amber-900/70', 'dark:bg-amber-950/40', 'dark:text-amber-300');
            });
        }

        function setSelfieCaptureEnabled(enabled) {
            document.querySelectorAll('[data-selfie-capture-button]').forEach((button) => {
                button.disabled = !enabled;
                button.classList.remove('bg-primary-600', 'hover:bg-primary-700', 'bg-gray-300',
                    'cursor-not-allowed');
                button.classList.add(enabled ? 'bg-primary-600' : 'bg-gray-300');

                if (enabled) {
                    button.classList.add('hover:bg-primary-700');
                } else {
                    button.classList.add('cursor-not-allowed');
                }
            });
        }

        function resetSelfieLiveness(message = @js(__('Center your face inside the guide'))) {
            state.selfieLivenessPassed = false;
            state.selfieBaselineYaw = null;
            state.selfieBlinkArmed = false;
            setSelfieLivenessStatus(message, 'warning');
            setSelfieCaptureEnabled(false);
        }

        function stopSelfieDetection() {
            if (state.selfieDetectionTimer) {
                clearTimeout(state.selfieDetectionTimer);
                state.selfieDetectionTimer = null;
            }
            state.selfieDetecting = false;
        }

        function queueSelfieDetection() {
            stopSelfieDetection();
            state.selfieDetectionTimer = setTimeout(runSelfieDetection, 400);
        }

        function pointDistance(a, b) {
            return Math.hypot(a.x - b.x, a.y - b.y);
        }

        function averagePoint(points) {
            const total = points.reduce((carry, point) => ({
                x: carry.x + point.x,
                y: carry.y + point.y
            }), {
                x: 0,
                y: 0
            });

            return {
                x: total.x / points.length,
                y: total.y / points.length
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

        function isGeometryFaceDescriptor(descriptor) {
            return Array.isArray(descriptor) && descriptor.length === 129 && descriptor[0] === 2;
        }

        function buildScanFaceGeometryDescriptor(landmarks) {
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

        function scanGeometryDistance(sourceDescriptor, targetDescriptor) {
            let sum = 0;

            for (let index = 1; index < sourceDescriptor.length; index += 1) {
                const delta = Number(sourceDescriptor[index]) - Number(targetDescriptor[index]);
                sum += delta * delta;
            }

            return Math.sqrt(sum);
        }

        function evaluateSelfieLiveness(detection, video) {
            const box = detection.detection.box;
            const minFaceWidth = video.videoWidth * 0.24;

            if (box.width < minFaceWidth) {
                resetSelfieLiveness(@js(__('Move a little closer to the camera')));
                return false;
            }

            if (state.selfieLivenessPassed) {
                setSelfieLivenessStatus(@js(__('Live face confirmed. Ready to continue.')), 'success');
                setSelfieCaptureEnabled(true);
                return true;
            }

            const landmarks = detection.landmarks;
            const averageEar = (eyeAspectRatio(landmarks.getLeftEye()) + eyeAspectRatio(landmarks
            .getRightEye())) / 2;
            const yaw = getYawScore(landmarks);

            if (state.selfieBaselineYaw === null) {
                state.selfieBaselineYaw = yaw;
            }

            if (averageEar > 0.24) {
                state.selfieBlinkArmed = true;
            }

            const blinkDetected = state.selfieBlinkArmed && averageEar < 0.19;
            const headTurnDetected = Math.abs(yaw - state.selfieBaselineYaw) > 0.11;

            if (blinkDetected || headTurnDetected) {
                state.selfieLivenessPassed = true;
                setSelfieLivenessStatus(@js(__('Live face confirmed. Ready to continue.')), 'success');
                setSelfieCaptureEnabled(true);
                return true;
            }

            setSelfieLivenessStatus(@js(__('Blink once or turn your head slightly')), 'warning');
            setSelfieCaptureEnabled(false);
            return false;
        }

        async function runSelfieDetection() {
            const video = document.getElementById('selfie-video');

            if (!video || !state.isSelfieMode || state.captureBusy) {
                stopSelfieDetection();
                return;
            }

            if (!state.faceModelsLoaded || !state.faceDetectorOptions) {
                queueSelfieDetection();
                return;
            }

            if (!video.videoWidth || !video.videoHeight) {
                queueSelfieDetection();
                return;
            }

            if (state.selfieDetecting) {
                queueSelfieDetection();
                return;
            }

            state.selfieDetecting = true;

            try {
                const detection = await faceapi
                    .detectSingleFace(video, state.faceDetectorOptions)
                    .withFaceLandmarks();

                if (detection) {
                    evaluateSelfieLiveness(detection, video);
                } else {
                    resetSelfieLiveness(@js(__('Center your face inside the guide')));
                }
            } catch (error) {
                console.warn('Selfie liveness detection error:', error);
                setSelfieLivenessStatus(@js(__('We could not verify a live face right now. Please try again.')), 'danger');
            } finally {
                state.selfieDetecting = false;
                queueSelfieDetection();
            }
        }

        // Toggle Map Function
        // Toggle Map Function
        window.toggleMap = function(mapId) {
            const mapEl = document.getElementById(mapId);
            const btn = document.getElementById(`toggle-${mapId}-btn`);
            const svg = btn.querySelector('svg');
            const span = btn.querySelector('span');

            if (mapEl.classList.contains('hidden')) {
                mapEl.classList.remove('hidden');
                svg.style.transform = 'rotate(180deg)';
                span.textContent = '{{ __('Hide Map') }}';

                if (!state.maps[mapId]) {
                    initMap(mapId);
                }

                // Fix Leaflet rendering issues when showing hidden map
                setTimeout(() => {
                    if (state.maps[mapId]) {
                        state.maps[mapId].invalidateSize();
                    }
                }, 200);
            } else {
                mapEl.classList.add('hidden');
                svg.style.transform = 'rotate(0deg)';
                span.textContent = '{{ __('Show Map') }}';
            }
        };

        // Initialize Map
        function initMap(mapId) {
            let lat, lng, popupText, markerColor;

            if (mapId === 'checkInMap') {
                lat = {{ $attendance?->latitude_in ?? 0 }};
                lng = {{ $attendance?->longitude_in ?? 0 }};
                popupText = '{{ __('Check In Location') }}';
                markerColor = 'blue';
            } else if (mapId === 'checkOutMap') {
                lat = {{ $attendance?->latitude_out ?? 0 }};
                lng = {{ $attendance?->longitude_out ?? 0 }};
                popupText = '{{ __('Check Out Location') }}';
                markerColor = 'orange';
            } else {
                lat = state.userLat;
                lng = state.userLng;
                popupText = '{{ __('Your Current Location') }}';
                markerColor = 'green';
            }

            if (lat && lng) {
                state.maps[mapId] = L.map(mapId).setView([lat, lng], 18);
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 21,
                }).addTo(state.maps[mapId]);

                const marker = L.marker([lat, lng]).addTo(state.maps[mapId]);
                marker.bindPopup(popupText).openPopup();
            }
        }

        // Update Location Display
        function updateLocationDisplay(lat, lng, mapId = 'currentLocationMap') {
            const locationText = document.getElementById(`location-text-${mapId}`);
            const updatedText = document.getElementById(`location-updated-${mapId}`);
            const timeStr = new Date().toLocaleTimeString();

            if (locationText) {
                locationText.innerHTML = `
                    <a href="https://www.google.com/maps?q=${lat},${lng}" target="_blank"
                       class="inline-flex items-center gap-2 text-xs text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        ${lat}, ${lng}
                    </a>
                `;
            }

            if (updatedText) {
                updatedText.textContent = `Last updated: ${timeStr}`;
            }

            if (state.errorMsg) {
                state.errorMsg.classList.add('hidden');
                state.errorMsg.innerHTML = '';
            }
        }

        // Refresh Location Function
        window.refreshLocation = function() {
            if (state.isRefreshing) return;

            state.isRefreshing = true;
            const btn = document.getElementById('refresh-location-btn');
            const svg = btn?.querySelector('svg');

            if (svg) {
                svg.style.animation = 'spin 1s linear infinite';
            }

            ensureLocationReady({
                refresh: true,
                interactive: true
            }).then((ready) => {
                if (ready && !window.Capacitor?.isNativePlatform?.()) {
                    window.startScannerIfReady?.();
                }
            }).finally(() => {
                state.isRefreshing = false;
                if (svg) {
                    svg.style.animation = '';
                }
            });
        };

        function hasCachedLocation() {
            return Number.isFinite(state.userLat) && Number.isFinite(state.userLng);
        }

        function requireWebLocationBeforeScan() {
            if (window.Capacitor?.isNativePlatform?.() || hasCachedLocation()) {
                return true;
            }

            if (state.errorMsg) {
                state.errorMsg.classList.remove('hidden');
                state.errorMsg.innerHTML = 'Perbarui lokasi dulu, baru kamera scan akan aktif.';
            }

            const locationText = document.getElementById('location-text-currentLocationMap');
            if (locationText) {
                locationText.innerHTML =
                    '<span class="text-amber-600 dark:text-amber-400">Klik refresh lokasi untuk mulai scan</span>';
            }

            return false;
        }

        function isLocationPermissionGranted(status) {
            return status?.location === 'granted' || status?.coarseLocation === 'granted';
        }

        function isLocationPermissionPromptable(status) {
            return status?.location === 'prompt' ||
                status?.location === 'prompt-with-rationale' ||
                status?.coarseLocation === 'prompt' ||
                status?.coarseLocation === 'prompt-with-rationale';
        }

        function getLocationErrorMessage(error) {
            return String(error?.message || error || '').toLowerCase();
        }

        function isLocationServicesDisabled(error) {
            const message = getLocationErrorMessage(error);
            return message.includes('location services are not enabled') ||
                message.includes('system location services') ||
                message.includes('location settings') ||
                message.includes('location disabled') ||
                message.includes('location is disabled') ||
                message.includes('please turn on location') ||
                message.includes('gps') && message.includes('disabled');
        }

        function isLocationPermissionDenied(error) {
            const message = getLocationErrorMessage(error);
            return error?.code === 1 ||
                message.includes('permission denied') ||
                message.includes('location permission denied') ||
                message.includes('denied');
        }

        function showLocationPermissionMessage(kind = 'permission') {
            const locationText = document.getElementById('location-text-currentLocationMap');
            const locationLabel = kind === 'services'
                ? '{{ __('No location data') }}'
                : '{{ __('Location access denied') }}';
            const detailMessage = kind === 'services'
                ? '{{ __('Please enable your location') }}'
                : '{{ __('Please enable location access') }}';

            if (locationText) {
                locationText.innerHTML =
                    `<span class="text-red-600 dark:text-red-400">${locationLabel}</span>`;
            }

            if (state.errorMsg) {
                state.errorMsg.classList.remove('hidden');
                state.errorMsg.innerHTML = detailMessage;
            }
        }

        async function openSettingsForLocation(kind = 'permission') {
            if (!window.isNativeApp?.()) {
                return false;
            }

            if (kind === 'services') {
                return await window.openNativeLocationSettings?.();
            }

            return await window.openNativeAppSettings?.();
        }

        async function promptLocationSettings(kind = 'permission') {
            if (!window.Capacitor?.isNativePlatform?.()) {
                return false;
            }

            const isServices = kind === 'services';
            const title = isServices
                ? '{{ __('Please enable your location') }}'
                : '{{ __('Please enable location access') }}';
            const text = isServices
                ? 'Nyalakan GPS/Lokasi perangkat, lalu kembali ke scanner.'
                : 'Izin lokasi diblokir. Buka pengaturan aplikasi lalu izinkan lokasi agar bisa lanjut.';
            const confirmButtonText = isServices ? '{{ __('Open Settings') }}' : '{{ __('Open App Settings') }}';

            const result = await Swal.fire({
                icon: 'warning',
                title,
                text,
                showCancelButton: true,
                confirmButtonText,
                cancelButtonText: '{{ __('Cancel') }}',
                confirmButtonColor: '#16a34a',
                background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#ffffff' : '#1f2937'
            });

            if (result.isConfirmed) {
                await openSettingsForLocation(kind);
            }

            return false;
        }

        async function requestNativeLocationPermission(forcePrompt = false) {
            let status = null;

            try {
                status = await window.CapacitorGeolocation.checkPermissions();
            } catch (error) {
                if (isLocationServicesDisabled(error)) {
                    showLocationPermissionMessage('services');
                    await promptLocationSettings('services');
                    return false;
                }

                throw error;
            }

            if (isLocationPermissionGranted(status)) {
                return true;
            }

            if (!forcePrompt && !isLocationPermissionPromptable(status)) {
                showLocationPermissionMessage('permission');
                await promptLocationSettings('permission');
                return false;
            }

            try {
                const requested = await window.CapacitorGeolocation.requestPermissions({
                    permissions: ['location', 'coarseLocation']
                });

                if (isLocationPermissionGranted(requested)) {
                    return true;
                }

                showLocationPermissionMessage('permission');
                await promptLocationSettings('permission');
                return false;
            } catch (error) {
                if (isLocationServicesDisabled(error)) {
                    showLocationPermissionMessage('services');
                    await promptLocationSettings('services');
                    return false;
                }

                if (isLocationPermissionDenied(error)) {
                    showLocationPermissionMessage('permission');
                    await promptLocationSettings('permission');
                    return false;
                }

                throw error;
            }
        }

        // Enhanced GPS sampling for fake GPS detection
        async function getSingleGpsReading() {
            if (window.Capacitor?.isNativePlatform?.()) {
                // 1. Native Mock Location Check
                try {
                    // Check using global wrapper
                    if (window.checkMockLocation) {
                        const mockResult = await window.checkMockLocation();
                        if (mockResult.isMock) {
                            throw new Error(
                                'FAKE_GPS_DETECTED: Mock location is enabled. Please disable it to continue.'
                                );
                        }
                    }
                } catch (e) {
                    console.error('Mock check failed:', e);
                    if (e.message.includes('FAKE_GPS')) throw e;
                }

                const perm = await window.CapacitorGeolocation.checkPermissions();
                if (!isLocationPermissionGranted(perm)) {
                    throw new Error('Location permission denied');
                }
                return await window.CapacitorGeolocation.getCurrentPosition({
                    enableHighAccuracy: true,
                    timeout: 30000,
                    maximumAge: 3000,
                    enableLocationFallback: true,
                });
            } else {
                if (!navigator.geolocation) {
                    throw new Error('Geolocation not supported');
                }
                return await new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(resolve, reject, {
                        enableHighAccuracy: true,
                        timeout: 30000,
                        maximumAge: 3000
                    });
                });
            }
        }

        // Calculate standard deviation (variance) of coordinates
        function calculateGpsVariance(samples) {
            if (samples.length < 2) return 0;

            const lats = samples.map(s => s.lat);
            const lngs = samples.map(s => s.lng);

            const avgLat = lats.reduce((a, b) => a + b) / lats.length;
            const avgLng = lngs.reduce((a, b) => a + b) / lngs.length;

            const latVariance = lats.reduce((sum, lat) => sum + Math.pow(lat - avgLat, 2), 0) / lats.length;
            const lngVariance = lngs.reduce((sum, lng) => sum + Math.pow(lng - avgLng, 2), 0) / lngs.length;

            return Math.sqrt(latVariance + lngVariance);
        }

        async function getLocation(isRefresh = false) {
            try {
                // Collect 3 GPS samples for fake GPS detection
                const samples = [];
                const sampleCount = 3;
                const delayMs = 400;

                for (let i = 0; i < sampleCount; i++) {
                    const position = await getSingleGpsReading();
                    samples.push({
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                        accuracy: position.coords.accuracy
                    });

                    if (i < sampleCount - 1) {
                        await new Promise(r => setTimeout(r, delayMs));
                    }
                }

                // Use the last sample as the final position
                const finalSample = samples[samples.length - 1];
                const lat = finalSample.lat.toFixed(6);
                const lng = finalSample.lng.toFixed(6);
                const accuracy = finalSample.accuracy;

                // Calculate variance across samples
                const variance = calculateGpsVariance(samples);

                state.userLat = parseFloat(lat);
                state.userLng = parseFloat(lng);
                state.userAccuracy = accuracy;
                state.gpsVariance = variance;

                if (window.Livewire) {
                    window.Livewire.find('{{ $_instance->getId() }}')
                        .set('currentLiveCoords', [state.userLat, state.userLng]);
                    window.Livewire.find('{{ $_instance->getId() }}')
                        .set('gpsAccuracy', accuracy);
                    window.Livewire.find('{{ $_instance->getId() }}')
                        .set('gpsVariance', variance);
                }

                updateLocationDisplay(lat, lng);

                if (state.maps['currentLocationMap'] && isRefresh) {
                    state.maps['currentLocationMap'].setView(
                        [state.userLat, state.userLng],
                        18
                    );

                    state.maps['currentLocationMap'].eachLayer(layer => {
                        if (layer instanceof L.Marker) {
                            state.maps['currentLocationMap'].removeLayer(layer);
                        }
                    });

                    const timeStr = new Date().toLocaleTimeString();
                    L.marker([state.userLat, state.userLng])
                        .addTo(state.maps['currentLocationMap'])
                        .bindPopup(
                            `{{ __('Your Current Location') }}<br><span class="text-xs text-gray-500">{{ __('Updated:') }} ${timeStr}</span>`
                            )
                        .openPopup();
                }

                return true;

            } catch (err) {
                console.error(err);
                const errorMessage = String(err?.message || err || '');

                // Specific handling for Fake GPS
                if (errorMessage.includes('FAKE_GPS_DETECTED')) {
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __('Security Violation') }}',
                        text: '{{ __('Aplikasi Fake GPS terdeteksi! Mohon matikan Mock Location di pengaturan HP Anda.') }}',
                        allowOutsideClick: false,
                        confirmButtonText: 'OK'
                    });

                    if (state.errorMsg) {
                        state.errorMsg.classList.remove('hidden');
                        state.errorMsg.innerHTML =
                            '<span class="text-red-500 font-bold">{{ __('FAKE GPS DETECTED') }}</span>';
                        state.errorMsg.style.display = 'block';
                    }
                    return false;
                }

                if (isLocationServicesDisabled(err)) {
                    showLocationPermissionMessage('services');
                } else if (isLocationPermissionDenied(err)) {
                    showLocationPermissionMessage('permission');
                }

                throw err;
            }
        }

        async function ensureLocationReady({
            refresh = false,
            interactive = false
        } = {}) {
            if (!refresh && hasCachedLocation()) {
                return true;
            }

            if (window.Capacitor?.isNativePlatform?.()) {
                const permitted = await requestNativeLocationPermission(interactive);
                if (!permitted) {
                    return false;
                }
            }

            try {
                const result = await getLocation(refresh);
                return result === true;
            } catch (error) {
                if (isLocationServicesDisabled(error)) {
                    showLocationPermissionMessage('services');
                    if (interactive && window.Capacitor?.isNativePlatform?.()) {
                        await promptLocationSettings('services');
                    }
                } else if (isLocationPermissionDenied(error)) {
                    showLocationPermissionMessage('permission');
                    if (interactive && window.Capacitor?.isNativePlatform?.()) {
                        await promptLocationSettings('permission');
                    }
                }

                return false;
            }
        }

        // Initialize Scanner
        function initScanner() {
            if (state.isAbsence || state.isComplete || state.approvedAbsence) return;

            let scanner = null;
            const scannerEl = document.getElementById('scanner');

            // Web: Init Html5Qrcode (now used for ALL platforms)
            if (scannerEl && typeof Html5Qrcode !== 'undefined') {
                scanner = new Html5Qrcode('scanner');
            }

            const config = {
                formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
                fps: 30,
                qrbox: function(viewfinderWidth, viewfinderHeight) {
                    let minEdgePercentage = 0.7; // 70%
                    let minEdgeSize = Math.min(viewfinderWidth, viewfinderHeight);
                    let qrboxSize = Math.floor(minEdgeSize * minEdgePercentage);
                    return {
                        width: qrboxSize,
                        height: qrboxSize
                    };
                },
                supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA]
            };

            // Expose switchCamera globally — in-page switch without reload
            window.switchCamera = async function() {
                if (_scannerStarting) return;

                if (isNativeScannerRuntime()) {
                    try {
                        await window.switchNativeCamera(onScanSuccess);
                    } catch (e) {
                        console.error('[CAM] Native switch error:', e);
                        await Swal.fire({
                            icon: 'error',
                            title: 'Switch Failed',
                            text: 'Failed to switch camera. Please try again.',
                            confirmButtonColor: '#16a34a'
                        });
                    }
                    return;
                }

                // Show loading state while switching
                const btn = document.querySelector('button[onclick="window.switchCamera()"]');
                if (btn) btn.style.opacity = '0.5';

                try {
                    // Stop current scanner gracefully
                    if (scanner) {
                        try {
                            if (scanner.getState() === Html5QrcodeScannerState.SCANNING ||
                                scanner.getState() === Html5QrcodeScannerState.PAUSED) {
                                await scanner.stop();
                            }
                        } catch (e) {
                            console.warn('[CAM] Stop error:', e);
                        }
                    }

                    // Force kill floating tracks just in case
                    document.querySelectorAll('video').forEach(v => {
                        if (v.srcObject) {
                            v.srcObject.getTracks().forEach(t => t.stop());
                            v.srcObject = null;
                        }
                    });

                    // Wait for camera hardware to fully release
                    await new Promise(r => setTimeout(r, 600));

                    // Toggle mode
                    state.facingMode = state.facingMode === 'environment' ? 'user' : 'environment';

                    // Restart scanner with new mode
                    await startScanning();

                } catch (e) {
                    console.error('[CAM] Switch error:', e);
                    await Swal.fire({
                        icon: 'error',
                        title: 'Switch Failed',
                        text: 'Failed to switch camera. Please reload the page.',
                        confirmButtonColor: '#6366f1'
                    });
                } finally {
                    if (btn) btn.style.opacity = '1';
                }
            };

            function setShowOverlay(show) {
                // Expose to window for native scanner
                window._setShowOverlay = setShowOverlay;

                const overlay = document.getElementById('scanner-overlay');
                const placeholder = document.getElementById('scanner-placeholder');
                if (overlay) {
                    if (show) overlay.classList.remove('hidden');
                    else overlay.classList.add('hidden');
                }
                if (placeholder) {
                    if (document.body.classList.contains('is-native-scanning')) {
                        placeholder.style.display = 'none';
                    } else {
                        if (show) placeholder.style.display = 'none';
                        else placeholder.style.display = 'block';
                    }
                }
            }
            // Initial expose
            window.setShowOverlay = setShowOverlay;

            let _scannerStarting = false;

            function isNativeScannerRuntime() {
                return !!(window.isNativeApp?.() && window.startNativeBarcodeScanner);
            }

            async function startScanning() {
                if (state.approvedAbsence) return;
                if (!requireWebLocationBeforeScan()) return;

                // CRITICAL: Prevent concurrent camera starts
                if (_scannerStarting) {
                    console.log('[CAM DEBUG] startScanning() blocked. Already starting.');
                    return;
                }

                _scannerStarting = true;

                try {
                    const scannerEl = document.getElementById('scanner');
                    if (scannerEl) {
                        scannerEl.classList.toggle('mirrored', state.facingMode === 'user');
                    }

                    if (isNativeScannerRuntime()) {
                        setShowOverlay(true);
                        await window.startNativeBarcodeScanner(onScanSuccess, state.facingMode);
                        return;
                    }

                    try {
                        if (scanner && scanner.getState() === Html5QrcodeScannerState.SCANNING) return;
                        if (scanner && scanner.getState() === Html5QrcodeScannerState.PAUSED) {
                            scanner.resume();
                            return;
                        }
                    } catch (e) {}

                    if (!scanner) {
                        const el = document.getElementById('scanner');
                        if (el && typeof Html5Qrcode !== 'undefined') {
                            scanner = new Html5Qrcode('scanner');
                        } else {
                            throw new Error('Scanner element or library not available');
                        }
                    }

                    function logDebug(msg) {
                        console.log('[CAM DEBUG]', msg);
                        // Hide UI debug log per user request
                        /*
                        const dbg = document.getElementById('debug-log');
                        if (dbg) {
                            dbg.parentElement.classList.remove('hidden');
                            dbg.innerHTML += '<div>' + new Date().toISOString().substring(11,19) + ': ' + msg + '</div>';
                        }
                        */
                    }

                    logDebug('Starting camera with facingMode: ' + state.facingMode);

                    try {
                        await scanner.start({
                            facingMode: state.facingMode
                        }, config, onScanSuccess);
                        logDebug('Success using facingMode');
                    } catch (err1) {
                        const errStr = typeof err1 === 'string' ? err1 : (err1 && err1.message ? err1
                            .message : JSON.stringify(err1));
                        logDebug('facingMode failed: ' + errStr);
                        logDebug('Falling back to enumerating all devices...');

                        const devices = await navigator.mediaDevices.enumerateDevices();
                        const videoDevices = devices.filter(d => d.kind === 'videoinput');

                        logDebug('Found ' + videoDevices.length + ' video devices');

                        if (videoDevices.length === 0) {
                            throw new Error("No cameras found on device.");
                        }

                        try {
                            scanner.clear();
                        } catch (e) {}
                        scanner = new Html5Qrcode('scanner');

                        const isUser = state.facingMode === 'user';
                        const sortedDevices = videoDevices.sort((a, b) => {
                            const aIsTarget = isUser ? /front|user|selfie|face/i.test(a.label) :
                                /back|rear|environment|main/i.test(a.label);
                            const bIsTarget = isUser ? /front|user|selfie|face/i.test(b.label) :
                                /back|rear|environment|main/i.test(b.label);
                            if (aIsTarget && !bIsTarget) return -1;
                            if (!aIsTarget && bIsTarget) return 1;
                            return 0;
                        });

                        let started = false;
                        let lastErr = errStr;

                        for (let i = 0; i < sortedDevices.length; i++) {
                            const device = sortedDevices[i];
                            logDebug('Trying device ' + (i + 1) + '/' + sortedDevices.length + ': ' + (
                                device.label || device.deviceId.substring(0, 8)));

                            try {
                                await new Promise(r => setTimeout(r, 600));
                                await scanner.start(device.deviceId, config, onScanSuccess);

                                const deviceName = device.label || device.deviceId.substring(0, 8);
                                logDebug('Success with device: ' + deviceName);

                                // CRITICAL: Sync facingMode state so "Switch" toggles correctly
                                if (/front|user|selfie|face/i.test(deviceName)) {
                                    state.facingMode = 'user';
                                } else if (/back|rear|environment|main/i.test(deviceName)) {
                                    state.facingMode = 'environment';
                                }

                                started = true;
                                break;
                            } catch (fallbackErr) {
                                lastErr = typeof fallbackErr === 'string' ? fallbackErr : (fallbackErr &&
                                    fallbackErr.message ? fallbackErr.message : JSON.stringify(
                                        fallbackErr));
                                logDebug('Device failed: ' + lastErr.substring(0, 50));
                                try {
                                    scanner.clear();
                                } catch (e) {}
                                scanner = new Html5Qrcode('scanner');
                            }
                        }

                        if (!started) {
                            logDebug('ALL CAMERAS FAILED.');
                            throw new Error('All cameras failed. Last error: ' + lastErr);
                        }
                    }

                    const video = document.querySelector('#scanner video');
                    if (video) {
                        video.style.objectFit = 'cover';
                        video.style.borderRadius = '1rem';
                    }

                    setShowOverlay(true);
                } catch (err) {
                    console.error('[CAM] Failed:', err);
                    const errorMsg = typeof err === 'string' ? err : (err && err.message ? err.message :
                        JSON.stringify(err));
                    const videoStream = document.querySelector('#scanner video');

                    if (videoStream && videoStream.srcObject && videoStream.srcObject.active) {
                        console.warn(
                            '[CAM DEBUG] Suppressing ghost error because hardware is actively streaming!',
                            err);
                        setShowOverlay(true);
                        return;
                    }

                    try {
                        if (scanner && scanner.getState() === 2) { // 2 = SCANNING
                            console.warn('[CAM DEBUG] Ignoring error because scanner state is SCANNING',
                                err);
                            setShowOverlay(true);
                            return;
                        }
                    } catch (e) {}

                    console.error('[CAM DEBUG] Silenced Ghost Error Popup:', errorMsg);
                    /*
                    await Swal.fire({
                        icon: 'error',
                        title: 'Camera Error',
                        text: errorMsg || 'Unknown error',
                        confirmButtonColor: '#6366f1'
                    });
                    */

                    setShowOverlay(false);
                } finally {
                    _scannerStarting = false;
                }
            }

            window.startScannerIfReady = startScanning;



            function formatTime(timeString) {
                if (!timeString) return '';
                const parts = timeString.split(':');
                const hours = parts[0];
                const minutes = parts[1];
                let h = parseInt(hours);

                const use24h = state.timeSettings ? state.timeSettings.format === '24' : true;

                if (use24h) {
                    return `${hours.padStart(2, '0')}:${minutes.padStart(2, '0')}`;
                } else {
                    const ampm = h >= 12 ? 'PM' : 'AM';
                    h = h % 12;
                    h = h ? h : 12;
                    return `${h}:${minutes.padStart(2, '0')} ${ampm}`;
                }
            }

            async function onScanSuccess(decodedText) {
                if (scanner && scanner.getState() === Html5QrcodeScannerState.SCANNING) {
                    scanner.pause(true);
                    setShowOverlay(false);
                }

                // Save the code
                state.scannedCode = decodedText;

                let hasLocation = hasCachedLocation();

                if (!hasLocation && window.Capacitor?.isNativePlatform?.()) {
                    hasLocation = await ensureLocationReady({
                        interactive: true
                    });
                }

                if (!hasLocation) {
                    if (!window.Capacitor?.isNativePlatform?.()) {
                        if (state.errorMsg) {
                            state.errorMsg.classList.remove('hidden');
                            state.errorMsg.innerHTML = 'Perbarui lokasi dulu sebelum scan QR.';
                        }

                        await Swal.fire({
                            icon: 'warning',
                            title: 'Lokasi belum siap',
                            text: 'Tekan refresh lokasi dulu, lalu scan QR lagi.',
                            confirmButtonColor: '#16a34a',
                            background: document.documentElement.classList.contains('dark') ?
                                '#1f2937' : '#ffffff',
                            color: document.documentElement.classList.contains('dark') ? '#ffffff' :
                                '#1f2937'
                        });
                    }

                    setTimeout(() => {
                        if (isNativeScannerRuntime()) {
                            startScanning();
                        } else if (scanner && scanner.getState() === Html5QrcodeScannerState.PAUSED) {
                            scanner.resume();
                            setShowOverlay(true);
                        }
                    }, 300);
                    return;
                }

                // Validate Barcode First
                try {
                    const validation = await window.Livewire.find('{{ $_instance->getId() }}').call(
                        'validateBarcode',
                        decodedText,
                        state.userLat,
                        state.userLng
                    );

                    if (validation !== true) {
                        await Swal.fire({
                            icon: 'error',
                            title: '{{ __('Scan Failed') }}',
                            text: validation,
                            timer: 2000,
                            showConfirmButton: false,
                            background: document.documentElement.classList.contains('dark') ?
                                '#1f2937' : '#ffffff',
                            color: document.documentElement.classList.contains('dark') ? '#ffffff' :
                                '#1f2937'
                        });

                        setTimeout(() => {
                            if (isNativeScannerRuntime()) {
                                startScanning();
                            } else if (scanner && scanner.getState() === Html5QrcodeScannerState
                                .PAUSED) {
                                scanner.resume();
                                setShowOverlay(true);
                            }
                        }, 500);
                        return;
                    }

                    if (state.requirePhoto) {
                        enterSelfieMode();
                        return;
                    }

                    submitAttendance(decodedText, null);

                } catch (error) {
                    console.error('Validation Error', error);
                    if (isNativeScannerRuntime()) {
                        startScanning();
                    } else if (scanner && scanner.getState() === Html5QrcodeScannerState.PAUSED) {
                        scanner.resume();
                        setShowOverlay(true);
                    }
                }
            }

            async function enterSelfieMode() {
                state.isSelfieMode = true;
                state.captureBusy = false;
                resetSelfieLiveness();

                if (isNativeScannerRuntime()) {
                    await window.stopNativeBarcodeScanner();
                } else if (scanner && (scanner.getState() === Html5QrcodeScannerState.SCANNING || scanner
                        .getState() === Html5QrcodeScannerState.PAUSED)) {
                    await scanner.stop();
                }

                // Update UI: Hide Scanner Card, Show Selfie Card
                document.getElementById('scanner-card-container').classList.add('hidden');
                document.getElementById('selfie-card-container').classList.remove('hidden');

                // Pre-load face-api models for face detection during selfie
                if (typeof faceapi !== 'undefined' && !state.faceModelsLoaded) {
                    try {
                        const MODEL_URL = window.resolveRuntimeAssetUrl('/models');
                        const modelLoads = [
                            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL)
                        ];

                        if (state.userFaceDescriptor && !isGeometryFaceDescriptor(state.userFaceDescriptor)) {
                            modelLoads.push(faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL));
                        }

                        await Promise.all(modelLoads);
                        state.faceDetectorOptions = new faceapi.TinyFaceDetectorOptions({
                            inputSize: 160,
                            scoreThreshold: 0.5
                        });
                        state.faceModelsLoaded = true;
                    } catch (e) {
                        console.warn('Failed to preload face models:', e);
                    }
                }

                // Start Camera for Selfie (User Facing)
                state.facingMode = 'user';
                await startSelfieCamera();
            }

            async function startSelfieCamera() {
                const video = document.getElementById('selfie-video');
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: 'user',
                            width: {
                                ideal: 480
                            },
                            height: {
                                ideal: 360
                            },
                            frameRate: {
                                ideal: 24,
                                max: 24
                            }
                        }
                    });
                    video.srcObject = stream;
                } catch (err) {
                    console.warn('Primary selfie camera request failed, attempting fallback...', err);
                    try {
                        const streamFallback = await navigator.mediaDevices.getUserMedia({
                            video: true
                        });
                        video.srcObject = streamFallback;
                    } catch (fallbackErr) {
                        console.error('Selfie camera fallback error', fallbackErr);
                        Swal.fire("{{ __('Camera Error') }}",
                            "{{ __('Could not access camera. Please ensure permissions are granted.') }}<br><br><small>" +
                            fallbackErr.message + "</small>", 'error');
                        return;
                    }
                }

                await new Promise((resolve) => {
                    video.onloadedmetadata = resolve;
                });
                await video.play();

                if (state.faceModelsLoaded) {
                    resetSelfieLiveness(@js(__('Checking face liveliness...')));
                    queueSelfieDetection();
                } else {
                    setSelfieCaptureEnabled(true);
                    setSelfieLivenessStatus(@js(__('Face verification models are not ready yet. You can continue, but live-face checks are unavailable.')), 'warning');
                }
            }

            window.captureAndSubmit = async function() {
                const video = document.getElementById('selfie-video');
                const canvas = document.getElementById('capture-canvas');
                const selfieContainer = document.getElementById('selfie-card-container');
                const processingContainer = document.getElementById('processing-card-container');

                if (!video || !canvas) return;
                if (state.captureBusy) return;

                if (state.faceModelsLoaded && !state.selfieLivenessPassed) {
                    await Swal.fire({
                        icon: 'warning',
                        title: '{{ __('Live Face Required') }}',
                        text: '{{ __('Please use a live face. Blink once or move your head slightly before capturing.') }}',
                        confirmButtonColor: '#16a34a',
                        background: document.documentElement.classList.contains('dark') ?
                            '#1f2937' : '#ffffff',
                        color: document.documentElement.classList.contains('dark') ? '#ffffff' :
                            '#1f2937'
                    });
                    return;
                }

                state.captureBusy = true;
                stopSelfieDetection();
                setSelfieCaptureEnabled(false);

                // === FACE DETECTION CHECK ===
                if (typeof faceapi !== 'undefined' && state.faceModelsLoaded) {
                    try {
                        const detectorOptions = state.faceDetectorOptions || new faceapi.TinyFaceDetectorOptions();
                        const requiresLegacyRecognition = state.userFaceDescriptor && !isGeometryFaceDescriptor(state.userFaceDescriptor);
                        const detection = requiresLegacyRecognition
                            ? await faceapi
                                .detectSingleFace(video, detectorOptions)
                                .withFaceLandmarks()
                                .withFaceDescriptor()
                            : await faceapi
                                .detectSingleFace(video, detectorOptions)
                                .withFaceLandmarks();

                        if (!detection) {
                            await Swal.fire({
                                icon: 'error',
                                title: '{{ __('No Face Detected') }}',
                                text: '{{ __('Please make sure your face is clearly visible in the camera before capturing.') }}',
                                confirmButtonColor: '#16a34a',
                                background: document.documentElement.classList.contains(
                                    'dark') ? '#1f2937' : '#ffffff',
                                color: document.documentElement.classList.contains('dark') ?
                                    '#ffffff' : '#1f2937'
                            });
                            state.captureBusy = false;
                            queueSelfieDetection();
                            return;
                        }

                        // === FACE MATCH VERIFICATION (if user has registered face) ===
                        if (state.userFaceDescriptor) {
                            const distance = isGeometryFaceDescriptor(state.userFaceDescriptor)
                                ? scanGeometryDistance(
                                    buildScanFaceGeometryDescriptor(detection.landmarks),
                                    state.userFaceDescriptor
                                )
                                : faceapi.euclideanDistance(
                                    detection.descriptor,
                                    new Float32Array(state.userFaceDescriptor)
                                );

                            if (distance >= (isGeometryFaceDescriptor(state.userFaceDescriptor) ? 1.35 : 0.6)) {
                                await Swal.fire({
                                    icon: 'error',
                                    title: '{{ __('Face Not Matched') }}',
                                    text: '{{ __('Your face does not match the registered Face ID. Please try again or re-register your face.') }}',
                                    confirmButtonColor: '#16a34a',
                                    background: document.documentElement.classList.contains(
                                        'dark') ? '#1f2937' : '#ffffff',
                                    color: document.documentElement.classList.contains('dark') ?
                                        '#ffffff' : '#1f2937'
                                });
                                state.captureBusy = false;
                                queueSelfieDetection();
                                return;
                            }
                        }
                    } catch (faceErr) {
                        console.warn('Face detection error during capture:', faceErr);
                        await Swal.fire({
                            icon: 'error',
                            title: '{{ __('Face Verification Error') }}',
                            text: '{{ __('We could not verify a live face right now. Please try again.') }}',
                            confirmButtonColor: '#16a34a',
                            background: document.documentElement.classList.contains('dark') ?
                                '#1f2937' : '#ffffff',
                            color: document.documentElement.classList.contains('dark') ?
                                '#ffffff' : '#1f2937'
                        });
                        state.captureBusy = false;
                        queueSelfieDetection();
                        return;
                    }
                }

                // Flash Effect
                const flash = document.getElementById('camera-flash');
                if (flash) {
                    flash.style.opacity = '0.8';
                    setTimeout(() => {
                        flash.style.opacity = '0';
                    }, 100);
                }

                // 1. Instant Transition: Hide Selfie, Show Processing
                if (selfieContainer) selfieContainer.classList.add('hidden');
                if (processingContainer) processingContainer.classList.remove('hidden');

                // 2. Capture Frame
                const context = canvas.getContext('2d');

                // Resize Logic (Max 800px)
                const MAX_WIDTH = 800;
                const MAX_HEIGHT = 800;
                let width = video.videoWidth;
                let height = video.videoHeight;

                if (width > height) {
                    if (width > MAX_WIDTH) {
                        height *= MAX_WIDTH / width;
                        width = MAX_WIDTH;
                    }
                } else {
                    if (height > MAX_HEIGHT) {
                        width *= MAX_HEIGHT / height;
                        height = MAX_HEIGHT;
                    }
                }

                canvas.width = width;
                canvas.height = height;
                context.drawImage(video, 0, 0, width, height);

                // Compression: 0.6 quality
                const photo = canvas.toDataURL('image/jpeg', 0.6);
                state.lastPhoto = photo;

                // Stop Stream
                const stream = video.srcObject;
                if (stream) stream.getTracks().forEach(track => track.stop());
                video.srcObject = null;

                try {
                    await submitAttendance(state.scannedCode, photo);
                } catch (e) {
                    // Reset UI on error
                    if (processingContainer) processingContainer.classList.add('hidden');
                    if (selfieContainer) selfieContainer.classList.remove('hidden');

                    // Restart Camera
                    await startSelfieCamera();
                } finally {
                    state.captureBusy = false;
                }
            }

            async function submitAttendance(code, photo) {
                if (state.hasCheckedIn && !state.hasCheckedOut) {
                    let note = null;
                    const attendanceData = await window.Livewire.find('{{ $_instance->getId() }}').call(
                        'getAttendance');

                    if (attendanceData && attendanceData.shift_end_time) {
                        const now = new Date();
                        const [hours, minutes, seconds] = attendanceData.shift_end_time.split(':');
                        const shiftEnd = new Date();
                        shiftEnd.setHours(hours, minutes, seconds || 0);


                        if (now < shiftEnd) {
                            const formattedTime = formatTime(attendanceData.shift_end_time);
                            const result = await Swal.fire({
                                title: "{{ __('Early Leave?') }}",
                                text: "{{ __('It is not yet time to leave') }} (" + formattedTime +
                                    "). {{ __('Please provide a reason:') }}",
                                icon: 'warning',
                                input: 'textarea',
                                inputPlaceholder: "{{ __('Write your reason here...') }}",
                                inputAttributes: {
                                    'aria-label': "{{ __('Write your reason here') }}"
                                },
                                showCancelButton: true,
                                confirmButtonColor: '#d33',
                                cancelButtonColor: '#3085d6',
                                confirmButtonText: "{{ __('Save & Check Out') }}",
                                cancelButtonText: "{{ __('Cancel') }}",
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                inputValidator: (value) => {
                                    if (!value) {
                                        return "{{ __('Reason is required!') }}"
                                    }
                                }
                            });

                            if (!result.isConfirmed) {
                                window.location.reload();
                                return;
                            }
                            note = result.value;
                        }
                    }

                    // Face Verification for Check Out (if user has registered face)
                    if (state.requiresFaceVerification && !state.isSelfieMode) {
                        try {
                            if (isNativeScannerRuntime()) {
                                await window.stopNativeBarcodeScanner();
                            } else if (scanner && scanner.getState() === Html5QrcodeScannerState.SCANNING) {
                                await scanner.stop();
                            }
                            try {
                                scanner?.clear();
                            } catch (e) {}
                            document.querySelectorAll('video').forEach(v => {
                                if (v.srcObject) {
                                    v.srcObject.getTracks().forEach(t => t.stop());
                                    v.srcObject = null;
                                }
                            });
                        } catch (e) {
                            console.warn('Error stopping scanner for face verify:', e);
                        }

                        setShowOverlay(false);
                        await new Promise(r => setTimeout(r, 500));

                        window.dispatchEvent(new CustomEvent('face-verify', {
                            detail: {
                                callback: async () => {
                                    const result = await window.Livewire.find(
                                        '{{ $_instance->getId() }}').call('scan',
                                        code, null, null, photo, note);
                                    handleScanResult(result, scanner, startScanning);
                                }
                            }
                        }));
                        return;
                    }

                    const result = await window.Livewire.find('{{ $_instance->getId() }}').call('scan',
                        code, null, null, photo, note);
                    handleScanResult(result, scanner, startScanning);
                    return;
                }

                if (!(await checkTime())) {
                    window.location.reload();
                    return;
                }

                if (state.requiresFaceVerification) {
                    try {
                        if (isNativeScannerRuntime()) {
                            await window.stopNativeBarcodeScanner();
                        } else if (scanner && scanner.getState() === Html5QrcodeScannerState.SCANNING) {
                            await scanner.stop();
                        }
                        try {
                            scanner?.clear();
                        } catch (e) {}
                        document.querySelectorAll('video').forEach(v => {
                            if (v.srcObject) {
                                v.srcObject.getTracks().forEach(t => t.stop());
                                v.srcObject = null;
                            }
                        });
                    } catch (e) {
                        console.warn('Error stopping scanner for face verify:', e);
                    }

                    setShowOverlay(false);

                    await new Promise(r => setTimeout(r, 500));

                    window.dispatchEvent(new CustomEvent('face-verify', {
                        detail: {
                            callback: async () => {
                                const result = await window.Livewire.find(
                                    '{{ $_instance->getId() }}').call('scan',
                                    code, null, null, photo);
                                handleScanResult(result, scanner, startScanning);
                            }
                        }
                    }));
                    return;
                }

                const result = await window.Livewire.find('{{ $_instance->getId() }}').call('scan',
                    code, null, null, photo);
                handleScanResult(result, scanner, startScanning);
            }



            async function captureFrame() {
                const video = document.querySelector('#scanner video');
                const canvas = document.getElementById('capture-canvas');
                const flash = document.getElementById('camera-flash');

                // Trigger Flash
                if (flash) {
                    flash.style.opacity = '0.8';
                    setTimeout(() => {
                        flash.style.opacity = '0';
                    }, 100);
                }

                if (!video || !canvas) return null;

                const context = canvas.getContext('2d');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;

                context.drawImage(video, 0, 0, canvas.width, canvas.height);
                return canvas.toDataURL('image/jpeg', 0.8);
            }

            function handleScanResult(result, scanner, startScanning) {
                if (result === true) {
                    if (scanner && (scanner.getState() === Html5QrcodeScannerState.SCANNING || scanner
                        .getState() === Html5QrcodeScannerState.PAUSED)) {
                        scanner.stop();
                    }
                    setShowOverlay(false);
                    if (state.errorMsg) {
                        state.errorMsg.classList.add('hidden');
                        state.errorMsg.innerHTML = '';
                    }

                    // Handling via Processing UI (Selfie Mode)
                    if (state.isSelfieMode) {
                        const successIcon = document.getElementById('processing-success');
                        const spinner = document.querySelector('#processing-card-container .animate-spin');
                        const title = document.getElementById('processing-title');
                        const text = document.getElementById('processing-text');

                        if (successIcon) successIcon.classList.remove('opacity-0');
                        if (spinner) spinner.style.opacity = '0';
                        if (title) title.innerText = "{{ __('Success!') }}";
                        if (text) text.innerText = "{{ __('Attendance Recorded') }}";

                        setTimeout(() => {
                            window.location.href = "{{ route('home') }}";
                        }, 1500);
                        return;
                    }

                    // Fallback/Standard QR Success
                    Swal.fire({
                        icon: 'success',
                        title: "{{ __('Success!') }}",
                        text: "{{ __('Attendance recorded successfully') }}",
                        imageUrl: state.lastPhoto,
                        imageHeight: 200,
                        imageAlt: 'Captured Selfie',
                        timer: 3000,
                        showConfirmButton: false,
                        background: document.documentElement.classList.contains('dark') ? '#1f2937' :
                            '#ffffff',
                        color: document.documentElement.classList.contains('dark') ? '#ffffff' :
                            '#1f2937'
                    }).then(() => {
                        window.location.href = "{{ route('home') }}";
                    });

                } else if (typeof result === 'string') {
                    // Handle Selfie Mode Error
                    if (state.isSelfieMode) {
                        const selfieContainer = document.getElementById('selfie-card-container');
                        const processingContainer = document.getElementById('processing-card-container');

                        // Revert UI
                        if (processingContainer) processingContainer.classList.add('hidden');
                        if (selfieContainer) selfieContainer.classList.remove('hidden');

                        Swal.fire({
                            icon: 'error',
                            title: '{{ __('Error') }}',
                            text: result,
                            background: document.documentElement.classList.contains('dark') ?
                                '#1f2937' : '#ffffff',
                            color: document.documentElement.classList.contains('dark') ? '#ffffff' :
                                '#1f2937'
                        });

                        // Restart Camera
                        startSelfieCamera();
                        return;
                    }

                    if (state.errorMsg) {
                        state.errorMsg.classList.remove('hidden');
                        state.errorMsg.innerHTML = result;
                    }
                    setTimeout(startScanning, 500);
                }
            }

            async function checkTime() {
                const attendance = await window.Livewire.find('{{ $_instance->getId() }}').call(
                    'getAttendance');

                if (attendance?.time_in) {
                    const timeIn = new Date(attendance.time_in).valueOf();
                    const diff = (Date.now() - timeIn) / (1000 * 60); // minutes

                    if (diff < 1) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Too Fast!',
                            text: 'You just checked in. Please wait a moment before checking out.',
                            confirmButtonColor: '#3085d6',
                        });
                        return false;
                    }

                    // Check 2: Early Checkout Warning
                    if (attendance.shift_end_time) {
                        const now = new Date();
                        const shiftEnd = new Date();
                        const [hours, minutes, seconds] = attendance.shift_end_time.split(':');
                        shiftEnd.setHours(hours, minutes, seconds);

                        // If checkout is more than 5 minutes early
                        if (now < shiftEnd && (shiftEnd - now) > (5 * 60 * 1000)) {
                            const result = await Swal.fire({
                                icon: 'warning',
                                title: 'Early Checkout',
                                html: `Your shift ends at <b>${attendance.shift_end_time}</b>.<br>Are you sure you want to checkout now?`,
                                showCancelButton: true,
                                confirmButtonColor: '#d33',
                                cancelButtonColor: '#3085d6',
                                confirmButtonText: 'Yes, checkout',
                                cancelButtonText: 'Cancel'
                            });

                            return result.isConfirmed;
                        }
                    }
                }
                return true;
            }

            // Style scanner buttons
            const observer = new MutationObserver(() => {
                const baseClasses = ['px-4', 'py-2', 'rounded-xl', 'font-medium', 'transition',
                    'duration-200'
                ];
                const buttons = {
                    '#html5-qrcode-button-camera-start': [...baseClasses, 'bg-blue-600',
                        'hover:bg-blue-700', 'text-white'
                    ],
                    '#html5-qrcode-button-camera-stop': [...baseClasses, 'bg-red-600',
                        'hover:bg-red-700', 'text-white'
                    ],
                    '#html5-qrcode-button-file-selection': [...baseClasses, 'bg-blue-600',
                        'hover:bg-blue-700', 'text-white'
                    ],
                    '#html5-qrcode-button-camera-permission': [...baseClasses, 'bg-blue-600',
                        'hover:bg-blue-700', 'text-white'
                    ]
                };

                Object.entries(buttons).forEach(([selector, classes]) => {
                    const btn = document.querySelector(selector);
                    if (btn) btn.classList.add(...classes);
                });
            });

            if (scannerEl) {
                observer.observe(scannerEl, {
                    childList: true,
                    subtree: true
                });
            }

            // Handle shift selector
            if (!state.hasCheckedIn) {
                const shift = document.querySelector('#shift_id');
                if (shift) {
                    const msg = 'Please select a shift first';
                    let isRendered = false;

                    setTimeout(() => {
                        if (isRendered) return; // Already started by change handler
                        if (!shift.value) {
                            if (state.errorMsg) {
                                state.errorMsg.classList.remove('hidden');
                                state.errorMsg.innerHTML = msg;
                            }
                        } else {
                            startScanning();
                            isRendered = true;
                        }
                    }, 1000);

                    shift.addEventListener('change', () => {
                        if (!isRendered && shift.value) {
                            startScanning();
                            isRendered = true;
                        }

                        if (!shift.value) {
                            if (isNativeScannerRuntime()) {
                                window.stopNativeBarcodeScanner?.();
                            } else if (scanner) {
                                scanner.pause(true);
                            }
                            if (state.errorMsg) {
                                state.errorMsg.classList.remove('hidden');
                                state.errorMsg.innerHTML = msg;
                            }
                        } else if (isNativeScannerRuntime()) {
                            startScanning();
                            if (state.errorMsg) {
                                state.errorMsg.classList.add('hidden');
                                state.errorMsg.innerHTML = '';
                            }
                        } else if (scanner && scanner.getState() === Html5QrcodeScannerState.PAUSED) {
                            scanner.resume();
                            if (state.errorMsg) {
                                state.errorMsg.classList.add('hidden');
                                state.errorMsg.innerHTML = '';
                            }
                        }
                    });
                }
            } else {
                setTimeout(startScanning, 1000);
            }
        }

        async function ensureLocationPermission() {

            if (window.Capacitor?.isNativePlatform?.()) {
                try {
                    const status = await window.CapacitorGeolocation.checkPermissions();

                    return isLocationPermissionGranted(status);
                } catch (error) {
                    if (isLocationServicesDisabled(error)) {
                        showLocationPermissionMessage('services');
                        return false;
                    }

                    if (isLocationPermissionDenied(error)) {
                        showLocationPermissionMessage('permission');
                        return false;
                    }

                    throw error;
                }
            }

            if (!navigator.geolocation) return false;

            if (navigator.permissions) {
                try {
                    const perm = await navigator.permissions.query({
                        name: 'geolocation'
                    });
                    return perm.state === 'granted' || perm.state === 'prompt';
                } catch (error) {
                    console.warn('Permissions API geolocation query failed:', error);
                }
            }

            return true;
        }

        async function shouldAutoloadLocation() {
            if (window.Capacitor?.isNativePlatform?.()) {
                return await ensureLocationPermission();
            }

            return !!navigator.geolocation;
        }

        (async () => {
            if (state.approvedAbsence) return;

            const allowed = await shouldAutoloadLocation();

            if (allowed) {
                ensureLocationReady({
                    refresh: true,
                    interactive: true
                }).then((ready) => {
                    if (ready) {
                        window.startScannerIfReady?.();
                    }
                }).catch(console.error);
            } else if (window.Capacitor?.isNativePlatform?.() && state.errorMsg) {
                showLocationPermissionMessage('permission');
            }

            initScanner();
        })();

        document.addEventListener('livewire:navigating', function cleanupCamera(event) {
            console.log(
                '[CAM DEBUG] Livewire navigating away. FORCING HARD RELOAD to release Android camera locks...'
                );

            try {
                if (typeof scanner !== 'undefined' && scanner) {
                    scanner.stop().catch(() => {});
                }
            } catch (e) {}

            try {
                if (window.stopNativeBarcodeScanner) {
                    window.stopNativeBarcodeScanner();
                }
            } catch (e) {}

            document.querySelectorAll('video').forEach(v => {
                if (v.srcObject) {
                    v.srcObject.getTracks().forEach(t => t.stop());
                }
            });

            document.removeEventListener('livewire:navigating', cleanupCamera);
            if (event.detail && event.detail.url) {
                sessionStorage.setItem('force_reload_next', '1');
            }
        }, {
            once: true
        });

    });
</script>
