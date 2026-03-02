<div class="py-6 lg:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden relative">

            {{-- Header --}}
            <div class="px-5 py-4 lg:px-8 lg:py-6 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-white dark:bg-gray-800 relative z-10">
                <div class="flex items-center gap-3">
                    <x-secondary-button href="{{ route('home') }}" class="!rounded-xl !px-3 !py-2 border-gray-200 dark:border-gray-600 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600">
                        <x-heroicon-o-arrow-left class="h-4 w-4 text-gray-500 dark:text-gray-300" />
                    </x-secondary-button>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="p-1.5 bg-primary-50 text-primary-600 dark:bg-primary-900/50 dark:text-primary-400 rounded-lg">
                            👤
                        </span>
                        {{ __('Face ID Setup') }}
                        @if(\App\Helpers\Editions::attendanceLocked()) 🔒 @endif
                    </h3>
                </div>
            </div>

            <div class="p-6 lg:p-8">
                @if($isEnrolled && !$isCapturing)
                {{-- Enrolled State --}}
                <div class="max-w-md mx-auto text-center">
                    <div class="w-24 h-24 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-12 h-12 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ __('Face ID Active') }}</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-8">{{ __('Your face is registered for attendance verification.') }}</p>

                    <div class="flex flex-col gap-3">
                        @php
                        $lockedIcon = \App\Helpers\Editions::attendanceLocked() ? ' 🔒' : '';
                        @endphp

                        @if(\App\Helpers\Editions::attendanceLocked())
                        <button type="button" @click.prevent="$dispatch('feature-lock', { title: 'Face ID Locked', message: 'Face ID Biometrics is an Enterprise Feature 🔒. Please Upgrade.' })" class="w-full px-4 py-3 bg-primary-600 text-white rounded-xl hover:bg-primary-700 font-semibold transition flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            {{ __('Update Face ID') }}{{ $lockedIcon }}
                        </button>
                        <button type="button" @click.prevent="$dispatch('feature-lock', { title: 'Face ID Locked', message: 'Face ID Biometrics is an Enterprise Feature 🔒. Please Upgrade.' })" class="w-full px-4 py-3 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800 rounded-xl hover:bg-red-100 dark:hover:bg-red-900/40 font-semibold transition">
                            {{ __('Remove Face ID') }}{{ $lockedIcon }}
                        </button>
                        @else
                        <button wire:click="startCapture" class="w-full px-4 py-3 bg-primary-600 text-white rounded-xl hover:bg-primary-700 font-semibold transition flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            {{ __('Update Face ID') }}
                        </button>
                        <button wire:click="removeFace" wire:confirm="{{ __('Are you sure you want to remove Face ID?') }}" class="w-full px-4 py-3 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800 rounded-xl hover:bg-red-100 dark:hover:bg-red-900/40 font-semibold transition">
                            {{ __('Remove Face ID') }}
                        </button>
                        @endif
                    </div>
                </div>

                @else
                {{-- Capture State --}}
                <div
                    x-data="faceEnrollment()"
                    x-init="init()"
                    class="max-w-lg mx-auto">
                    {{-- Camera Preview --}}
                    <div class="relative aspect-[4/3] bg-gray-900 rounded-2xl overflow-hidden mb-6">
                        <video
                            x-ref="video"
                            autoplay
                            playsinline
                            muted
                            class="w-full h-full object-cover"></video>

                        {{-- Face Detection Overlay --}}
                        <canvas x-ref="overlay" class="absolute inset-0 w-full h-full"></canvas>

                        {{-- Status Indicator --}}
                        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 px-4 py-2 bg-black/60 backdrop-blur rounded-full text-white text-sm font-medium flex items-center gap-2">
                            <template x-if="status === 'loading'">
                                <span class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ __('Loading models...') }}
                                </span>
                            </template>
                            <template x-if="status === 'ready'">
                                <span class="text-yellow-400">{{ __('Position your face in frame') }}</span>
                            </template>
                            <template x-if="status === 'detected'">
                                <span class="text-green-400 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    {{ __('Face detected!') }}
                                </span>
                            </template>
                            <template x-if="status === 'capturing'">
                                <span class="text-blue-400">{{ __('Capturing...') }}</span>
                            </template>
                        </div>
                    </div>

                    {{-- Instructions --}}
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 mb-6">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">{{ __('Tips for best results:') }}</h4>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                {{ __('Ensure good lighting') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                {{ __('Look directly at the camera') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                {{ __('Remove glasses or hats if possible') }}
                            </li>
                        </ul>
                    </div>

                    {{-- Actions --}}
                    <div class="flex gap-3">
                        @if($isEnrolled)
                        <button wire:click="cancelCapture" class="flex-1 px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 font-semibold transition">
                            {{ __('Cancel') }}
                        </button>
                        @endif

                        @php
                        $lockedIcon = \App\Helpers\Editions::attendanceLocked() ? ' 🔒' : '';
                        @endphp

                        @if(\App\Helpers\Editions::attendanceLocked())
                        <button
                            @click.prevent="$dispatch('feature-lock', { title: 'Face ID Locked', message: 'Face ID Biometrics is an Enterprise Feature 🔒. Please Upgrade.' })"
                            class="flex-1 px-4 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-semibold transition flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            {{ __('Capture Face') }}{{ $lockedIcon }}
                        </button>
                        @else
                        <button
                            @click="capture()"
                            :disabled="status !== 'detected'"
                            :class="status === 'detected' ? 'bg-primary-600 hover:bg-primary-700' : 'bg-gray-300 dark:bg-gray-600 cursor-not-allowed'"
                            class="flex-1 px-4 py-3 text-white rounded-xl font-semibold transition flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            {{ __('Capture Face') }}
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
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script>
    function faceEnrollment() {
        return {
            status: 'loading',
            stream: null,
            detectionInterval: null,
            currentDescriptor: null,

            async init() {
                try {
                    // Load face-api.js models from CDN
                    const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model';
                    await Promise.all([
                        faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                        faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                        faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
                    ]);

                    // Start camera
                    try {
                        this.stream = await navigator.mediaDevices.getUserMedia({
                            video: {
                                facingMode: 'user',
                                width: 640,
                                height: 480
                            }
                        });
                    } catch (err) {
                        console.warn('Primary enrollment camera request failed, attempting fallback...', err);
                        try {
                            this.stream = await navigator.mediaDevices.getUserMedia({
                                video: true
                            });
                        } catch (fallbackErr) {
                            console.error('Enrollment Fallback Camera start error:', fallbackErr);
                            throw fallbackErr;
                        }
                    }

                    this.$refs.video.srcObject = this.stream;

                    // Wait for video to be ready
                    await new Promise(resolve => {
                        this.$refs.video.onloadedmetadata = resolve;
                    });

                    this.status = 'ready';
                    this.startDetection();

                } catch (error) {
                    console.error('Face enrollment init error:', error);
                    this.status = 'error';
                    Swal.fire("{{ __('Camera Error') }}", "{{ __('Could not access camera for Face ID setup.') }}<br><br><small>Error: " + (error?.name || error?.message || 'Unknown error') + "</small>", 'error');
                }
            },

            startDetection() {
                const video = this.$refs.video;
                const canvas = this.$refs.overlay;
                const displaySize = {
                    width: video.videoWidth,
                    height: video.videoHeight
                };
                faceapi.matchDimensions(canvas, displaySize);

                this.detectionInterval = setInterval(async () => {
                    if (this.status === 'capturing') return;

                    const detections = await faceapi
                        .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                        .withFaceLandmarks()
                        .withFaceDescriptor();

                    const ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, canvas.width, canvas.height);

                    if (detections) {
                        // Draw face box
                        const resizedDetections = faceapi.resizeResults(detections, displaySize);
                        faceapi.draw.drawDetections(canvas, resizedDetections);

                        this.currentDescriptor = Array.from(detections.descriptor);
                        this.status = 'detected';
                    } else {
                        this.currentDescriptor = null;
                        if (this.status !== 'loading') {
                            this.status = 'ready';
                        }
                    }
                }, 200);
            },

            async capture() {
                if (!this.currentDescriptor || this.currentDescriptor.length !== 128) {
                    return;
                }

                this.status = 'capturing';

                // Send descriptor to Livewire
                @this.call('saveFaceDescriptor', this.currentDescriptor);

                // Stop camera
                this.cleanup();
            },

            cleanup() {
                if (this.detectionInterval) {
                    clearInterval(this.detectionInterval);
                }
                if (this.stream) {
                    this.stream.getTracks().forEach(track => track.stop());
                }
            }
        };
    }

    // Cleanup on page leave
    document.addEventListener('livewire:navigating', () => {
        const component = Alpine.$data(document.querySelector('[x-data^="faceEnrollment"]'));
        if (component && component.cleanup) {
            component.cleanup();
        }
    });
</script>
@endpush