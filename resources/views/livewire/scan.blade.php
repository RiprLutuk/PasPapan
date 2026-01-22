<div id="scan-wrapper" class="w-full to-slate-100 dark:from-slate-900 dark:to-slate-800 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
    @php
        use Illuminate\Support\Carbon;
        $hasCheckedIn = !is_null($attendance?->time_in);
        $hasCheckedOut = !is_null($attendance?->time_out);
        $isComplete = $hasCheckedIn && $hasCheckedOut;
        $requirePhoto = \App\Models\Setting::getValue('feature.require_photo', 1);
    @endphp

    @pushOnce('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    @endpushOnce

    @pushOnce('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    @endpushOnce

    @if (!$isAbsence)
        <script src="{{ url('/assets/js/html5-qrcode.min.js') }}"></script>
    @endif

    <div>
        {{-- Hidden canvas for frame capture --}}
        <canvas id="capture-canvas" class="hidden"></canvas>
        
        {{-- Camera Flash Effect --}}
        <div id="camera-flash" class="fixed inset-0 bg-white z-[60] pointer-events-none opacity-0 transition-opacity duration-200"></div>

        {{-- Face Verification Modal --}}
        @if($requiresFaceVerification && $userFaceDescriptor)
            <div 
                x-data="faceVerificationModal()"
                x-show="showModal"
                x-cloak
                class="fixed inset-0 z-[70] flex items-center justify-center bg-black/60 backdrop-blur-sm"
                @face-verify.window="openModal($event.detail)"
            >
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden" @click.stop>
                    {{-- Header --}}
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="p-1.5 bg-primary-50 text-primary-600 dark:bg-primary-900/50 dark:text-primary-400 rounded-lg">👤</span>
                            {{ __('Face Verification') }}
                        </h3>
                        <button @click="closeModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    {{-- Camera Preview --}}
                    <div class="p-6">
                        <div class="relative aspect-square bg-gray-900 rounded-xl overflow-hidden mb-4">                 <video x-ref="video" autoplay playsinline muted class="w-full h-full object-cover"></video>
                            <canvas x-ref="overlay" class="absolute inset-0 w-full h-full"></canvas>
                            
                            {{-- Status Indicator --}}
                            <div class="absolute bottom-3 left-1/2 -translate-x-1/2 px-4 py-2 bg-black/60 backdrop-blur rounded-full text-white text-sm font-medium">
                                <span x-show="status === 'loading'" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    {{ __('Loading...') }}
                                </span>
                                <span x-show="status === 'ready'" class="text-yellow-400">{{ __('Look at the camera') }}</span>
                                <span x-show="status === 'verifying'" class="text-blue-400">{{ __('Verifying...') }}</span>
                                <span x-show="status === 'matched'" class="text-green-400 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                    {{ __('Face matched!') }}
                                </span>
                                <span x-show="status === 'failed'" class="text-red-400">{{ __('Face not matched. Try again.') }}</span>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex gap-3">
                            <button @click="closeModal()" class="flex-1 px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 font-semibold transition">
                                {{ __('Cancel') }}
                            </button>
                            <button 
                                @click="verify()" 
                                :disabled="status !== 'ready'"
                                :class="status === 'ready' ? 'bg-primary-600 hover:bg-primary-700' : 'bg-gray-300 dark:bg-gray-600 cursor-not-allowed'"
                                class="flex-1 px-4 py-3 text-white rounded-xl font-semibold transition flex items-center justify-center gap-2"
                            >
                                {{ __('Verify') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            @pushOnce('scripts')
            <script src="{{ asset('assets/js/face-api.min.js') }}"></script>
            <script>
                const storedFaceDescriptor = @json($userFaceDescriptor);

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
                                    const MODEL_URL = '{{ asset('models') }}';
                                    await Promise.all([
                                        faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                                        faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                                        faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
                                    ]);
                                    this.modelsLoaded = true;
                                }

                                // Start front camera
                                this.stream = await navigator.mediaDevices.getUserMedia({
                                    video: { facingMode: 'user', width: 480, height: 480 }
                                });
                                this.$refs.video.srcObject = this.stream;
                                await new Promise(resolve => { this.$refs.video.onloadedmetadata = resolve; });

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
                            const detection = await faceapi
                                .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                                .withFaceLandmarks()
                                .withFaceDescriptor();

                            if (!detection) {
                                this.status = 'failed';
                                setTimeout(() => { this.status = 'ready'; }, 2000);
                                return;
                            }

                            const capturedDescriptor = detection.descriptor;
                            const distance = faceapi.euclideanDistance(capturedDescriptor, new Float32Array(storedFaceDescriptor));

                            // Threshold: 0.6 is standard (lower = stricter)
                            if (distance < 0.6) {
                                this.status = 'matched';
                                setTimeout(() => {
                                    if (this.pendingCallback) {
                                        this.pendingCallback();
                                    }
                                    this.closeModal();
                                }, 1000);
                            } else {
                                this.status = 'failed';
                                setTimeout(() => { this.status = 'ready'; }, 2000);
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

        @if($approvedAbsence)
            <div class="w-full max-w-md mx-auto bg-white rounded-3xl shadow-xl overflow-hidden p-8 text-center mt-6">
                <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <span class="text-4xl">✅</span>
                </div>
                
                <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ __('You are on Leave') }}</h2>
                <div class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-bold uppercase tracking-wider bg-green-100 text-green-700 mb-6">
                    {{ __(ucfirst($approvedAbsence->status)) }}
                </div>

                <div class="bg-gray-50 rounded-2xl p-4 mb-6 text-left">
                    <p class="text-sm text-gray-500 mb-1">{{ __('Date') }}</p>
                    <p class="font-semibold text-gray-900 mb-3">{{ $approvedAbsence->date->format('d F Y') }}</p>
                    
                    <p class="text-sm text-gray-500 mb-1">{{ __('Note') }}</p>
                    <p class="font-semibold text-gray-900 italic">"{{ $approvedAbsence->note }}"</p>
                </div>

                <a href="{{ route('home') }}" class="block w-full py-4 rounded-xl bg-gray-900 text-white font-bold shadow-lg hover:shadow-xl hover:bg-black transition transform hover:-translate-y-1">
                    {{ __('Back to Dashboard') }}
                </a>
            </div>
        @elseif ($isComplete)
            {{-- Completion View --}}
            <div class="space-y-4 sm:space-y-6">
                {{-- Success Message --}}
                <div class="rounded-lg border border-gray-200 bg-white p-4 sm:p-6 shadow dark:border-gray-700 dark:bg-gray-800 text-center">
                    <div
                        class="success-checkmark mb-4 inline-flex items-center justify-center w-12 h-12 bg-gradient-to-br from-green-100 to-green-200 dark:from-green-500 dark:to-green-700 rounded-full shadow-lg">
                        <svg class="w-10 h-10 text-green-700 dark:text-white" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ __('Attendance Complete!') }}</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('You\'ve successfully completed today\'s attendance') }}</p>
                </div>

                {{-- Summary Cards (Removed - Moved to Header) --}}

                {{-- Location History Cards (Removed - Integrated into Header) --}}


                {{-- Action Buttons (Removed) --}}
            </div>
        @elseif ($hasCheckedIn && !$hasCheckedOut)
            {{-- Checked In View --}}
            <div class="space-y-4 sm:space-y-6">
                {{-- Status Banner --}}
                <div class="py-2 relative z-[60]">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-300 rounded-xl">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('You\'re Checked In!') }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('Scan QR to check out') }}</p>
                        </div>
                    </div>
                </div>

                <div class="w-full">
                    <div id="scanner-card-container">
                         @component('components.scanner-card', ['title' => __('Scan to Check Out')])
                            @slot('headerActions')
                                @include('components.shift-selector', ['disabled' => true])
                            @endslot

                            {{-- Nested Location Card --}}
                            <x-location-card 
                                :title="__('Current Location')"
                                mapId="currentLocationMap"
                                :latitude="$currentLiveCoords[0] ?? null"
                                :longitude="$currentLiveCoords[1] ?? null"
                                :showRefresh="true"
                                icon="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                iconColor="green"
                                class="!p-0"
                            />
                         @endcomponent
                    </div>

                     {{-- Selfie UI (Hidden by default) --}}
                     <div id="selfie-card-container" class="hidden rounded-2xl border border-gray-200 bg-white p-4 shadow-lg dark:border-gray-700 dark:bg-gray-800 relative overflow-hidden">
                         <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3 text-center uppercase tracking-wider">{{ __('Take a Selfie') }}</h3>
                         <div class="relative w-full aspect-square bg-gray-900 rounded-xl overflow-hidden mb-4">
                             <video id="selfie-video" autoplay playsinline class="w-full h-full object-cover transform -scale-x-100"></video>
                             <div class="absolute inset-0 border-[3px] border-white/50 rounded-[50%] m-8 pointer-events-none"></div> {{-- Face Guide --}}
                         </div>
                         <button onclick="window.captureAndSubmit()" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-lg flex items-center justify-center gap-2">
                             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
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
                        <div id="scanner-card-container">
                             @component('components.scanner-card', ['title' => __('Scan QR Code')])
                                @slot('headerActions')
                                    @include('components.shift-selector', ['disabled' => false])
                                @endslot

                                {{-- Nested Location Card --}}
                                <x-location-card 
                                    :title="__('Current Location')"
                                    mapId="currentLocationMap"
                                    :latitude="$currentLiveCoords[0] ?? null"
                                    :longitude="$currentLiveCoords[1] ?? null"
                                    :showRefresh="true"
                                    icon="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                    iconColor="green"
                                    class="!p-0"
                                />
                             @endcomponent
                        </div>

                         {{-- Selfie UI (Hidden by default) --}}
                         <div id="selfie-card-container" class="hidden rounded-2xl border border-gray-200 bg-white p-4 shadow-lg dark:border-gray-700 dark:bg-gray-800 relative overflow-hidden">
                             <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3 text-center uppercase tracking-wider">{{ __('Take a Selfie') }}</h3>
                             <div class="relative w-full aspect-square bg-gray-900 rounded-xl overflow-hidden mb-4">
                                 <video id="selfie-video" autoplay playsinline class="w-full h-full object-cover transform -scale-x-100"></video>
                                 <div class="absolute inset-0 border-[3px] border-white/50 rounded-[50%] m-8 pointer-events-none"></div> {{-- Face Guide --}}
                             </div>
                             <button onclick="window.captureAndSubmit()" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-lg flex items-center justify-center gap-2">
                                 <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                 {{ __('Capture & Check In') }}
                             </button>

                             {{-- Processing UI (Hidden by default) --}}
                             <div id="processing-card-container" class="hidden rounded-2xl border border-gray-200 bg-white p-8 shadow-lg dark:border-gray-700 dark:bg-gray-800 text-center">
                                <div class="relative w-20 h-20 mx-auto mb-6">
                                    <div class="absolute inset-0 border-4 border-gray-200 dark:border-gray-700 rounded-full"></div>
                                    <div class="absolute inset-0 border-4 border-blue-500 rounded-full border-t-transparent animate-spin"></div>
                                    
                                    {{-- Checkmark for final transition --}}
                                    <div id="processing-success" class="absolute inset-0 flex items-center justify-center opacity-0 transition-opacity duration-300">
                                        <svg class="w-10 h-10 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                </div>
                                <h3 id="processing-title" class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ __('Verifying...') }}</h3>
                                <p id="processing-text" class="text-sm text-gray-500 dark:text-gray-400 animate-pulse">{{ __('Syncing attendance data safely') }}</p>
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
            facingMode: 'environment', // Start with back camera for scanning
            lastPhoto: null,
            requirePhoto: {{ $requirePhoto ? 'true' : 'false' }},
            isSelfieMode: false,
            scannedCode: null,
            timeSettings: @json($timeSettings),
            requiresFaceVerification: {{ ($requiresFaceVerification && $userFaceDescriptor) ? 'true' : 'false' }},
            approvedAbsence: {{ $approvedAbsence ? 'true' : 'false' }}
        };

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
        }

        // Refresh Location Function
        window.refreshLocation = function() {
            if (state.isRefreshing) return;

            state.isRefreshing = true;
            const btn = document.getElementById('refresh-location-btn');
            const svg = btn.querySelector('svg');

            svg.style.animation = 'spin 1s linear infinite';

            getLocation(true).finally(() => {
                state.isRefreshing = false;
                svg.style.animation = '';
            });
        };

        // Enhanced GPS sampling for fake GPS detection
        async function getSingleGpsReading() {
            if (window.Capacitor?.isNativePlatform?.()) {
                // 1. Native Mock Location Check
                try {
                    // Check if plugin is available
                    if (Capacitor.Plugins.MockLocation) {
                         const mockResult = await Capacitor.Plugins.MockLocation.checkMockLocation();
                         if (mockResult.isMock) {
                             throw new Error('FAKE_GPS_DETECTED: Mock location is enabled. Please disable it to continue.');
                         }
                    }
                } catch (e) {
                    console.error('Mock check failed:', e);
                    // Decide if we block on error or continue. 
                    // If error is explicitly FAKE_GPS_DETECTED, rethrow.
                    if (e.message.includes('FAKE_GPS')) throw e;
                }

                const perm = await Capacitor.Plugins.Geolocation.requestPermissions();
                if (perm.location !== 'granted') {
                    throw new Error('Location permission denied');
                }
                return await Capacitor.Plugins.Geolocation.getCurrentPosition({
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                });
            } else {
                if (!navigator.geolocation) {
                    throw new Error('Geolocation not supported');
                }
                return await new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(resolve, reject, {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
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
                        .bindPopup(`{{ __('Your Current Location') }}<br><span class="text-xs text-gray-500">{{ __('Updated:') }} ${timeStr}</span>`)
                        .openPopup();
                }

                return true;

            } catch (err) {
                console.error(err);
                
                // Specific handling for Fake GPS
                if (err.message.includes('FAKE_GPS_DETECTED')) {
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __("Security Violation") }}',
                        text: '{{ __("Aplikasi Fake GPS terdeteksi! Mohon matikan Mock Location di pengaturan HP Anda.") }}',
                        allowOutsideClick: false,
                        confirmButtonText: 'OK'
                    });
                    
                    if (state.errorMsg) {
                       state.errorMsg.classList.remove('hidden');
                       state.errorMsg.innerHTML = '<span class="text-red-500 font-bold">{{ __("FAKE GPS DETECTED") }}</span>';
                       state.errorMsg.style.display = 'block';
                    }
                    return false;
                }

                const locationText = document.getElementById('location-text-currentLocationMap');
                if (locationText) {
                    locationText.innerHTML =
                        '<span class="text-red-600 dark:text-red-400">{{ __('Location access denied') }}</span>';
                }

                if (state.errorMsg) {
                    state.errorMsg.classList.remove('hidden');
                    state.errorMsg.innerHTML = '{{ __('Please enable location access') }}';
                }

                throw err;
            }
        }

        // Initialize Scanner
        function initScanner() {
            if (state.isAbsence || state.isComplete || state.approvedAbsence) return;

            const scannerEl = document.getElementById('scanner');
            if (!scannerEl || typeof Html5Qrcode === 'undefined') return;

            const scanner = new Html5Qrcode('scanner');
            const config = {
                formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
                fps: 30,
                // aspectRatio: 1.0, // Removed to allow natural camera ratio
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
            
            // Expose switchCamera globally
            window.switchCamera = async function() {
                if (window.isNativeApp()) {
                    if (window.switchNativeCamera) {
                        await window.switchNativeCamera(onScanSuccess);
                    }
                    return;
                }

                if (scanner.getState() === Html5QrcodeScannerState.SCANNING) {
                    await scanner.stop();
                    setShowOverlay(false);
                    state.facingMode = state.facingMode === 'environment' ? 'user' : 'environment';
                    await startScanning();
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
                     if (show) placeholder.style.display = 'none';
                     else placeholder.style.display = 'block';
                }
            }
            // Initial expose
            window.setShowOverlay = setShowOverlay;

            async function startScanning() {
                if (state.approvedAbsence) return;

                try {
                    // Update mirroring class based on facing mode
                    const scannerEl = document.getElementById('scanner');
                    if (scannerEl) {
                        if (state.facingMode === 'user') {
                            scannerEl.classList.add('mirrored');
                        } else {
                            scannerEl.classList.remove('mirrored');
                        }
                    }

                    if (window.isNativeApp()) {
                        try {
                            await window.startNativeBarcodeScanner(onScanSuccess);
                        } finally {
                            // No cleanup needed
                        }
                        return;
                    }

                    // LOGIC LAMA (WEB)
                    if (scanner.getState() === Html5QrcodeScannerState.PAUSED) {
                        return scanner.resume();
                    }

                    await scanner.start({
                            facingMode: state.facingMode
                        },

                        config,
                        onScanSuccess
                    );

                    // Force video to cover standard container for square ratio
                    const video = document.querySelector('#scanner video');
                    if(video) {
                        video.style.objectFit = 'cover';
                        video.style.borderRadius = '1rem';
                    }
                    
                    setShowOverlay(true);
                } catch (err) {
                    console.error('Scanner start error:', err);
                    setShowOverlay(false);
                }
            }



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
                 if (scanner.getState() === Html5QrcodeScannerState.SCANNING) {
                     scanner.pause(true);
                     setShowOverlay(false);
                 }
                
                // Save the code
                state.scannedCode = decodedText;

                // Validate Barcode First
                try {
                    const validation = await window.Livewire.find('{{ $_instance->getId() }}').call('validateBarcode', 
                        decodedText, 
                        state.userLat, 
                        state.userLng
                    );

                    if (validation !== true) {
                        // Validation Failed
                        await Swal.fire({
                            icon: 'error',
                            title: '{{ __("Scan Failed") }}',
                            text: validation,
                            timer: 2000,
                            showConfirmButton: false,
                            background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                            color: document.documentElement.classList.contains('dark') ? '#ffffff' : '#1f2937'
                        });
                        
                        setTimeout(() => {
                            if (window.isNativeApp()) {
                                startScanning();
                            } else if (scanner.getState() === Html5QrcodeScannerState.PAUSED) {
                                scanner.resume();
                                setShowOverlay(true);
                            }
                        }, 500);
                        return;
                    }

                    // Validation Success - Proceed
                    // Step 1: Check if photo is required
                    if (state.requirePhoto) {
                        enterSelfieMode();
                        return;
                    }
                    
                    // If photo not required, submit immediately
                    submitAttendance(decodedText, null);
                    
                } catch (error) {
                    console.error('Validation Error', error);
                    if (scanner.getState() === Html5QrcodeScannerState.PAUSED) {
                        scanner.resume();
                        setShowOverlay(true);
                    }
                }
            }
            
            async function enterSelfieMode() {
                state.isSelfieMode = true;
                
                // Stop scanner to switch camera
                if (scanner.getState() === Html5QrcodeScannerState.SCANNING || scanner.getState() === Html5QrcodeScannerState.PAUSED) {
                    await scanner.stop();
                }
                
                // Update UI: Hide Scanner Card, Show Selfie Card
                document.getElementById('scanner-card-container').classList.add('hidden');
                document.getElementById('selfie-card-container').classList.remove('hidden');
                
                // Start Camera for Selfie (User Facing)
                state.facingMode = 'user';
                await startSelfieCamera();
            }
            
            async function startSelfieCamera() {
                const video = document.getElementById('selfie-video');
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ 
                        video: { facingMode: 'user' } 
                    });
                    video.srcObject = stream;
                } catch (err) {
                    console.error('Selfie camera error', err);
                    Swal.fire("{{ __('Error') }}", "{{ __('Could not access selfie camera') }}", 'error');
                }
            }
            
            window.captureAndSubmit = async function() {
                 const video = document.getElementById('selfie-video');
                 const canvas = document.getElementById('capture-canvas');
                 const selfieContainer = document.getElementById('selfie-card-container');
                 const processingContainer = document.getElementById('processing-card-container');
                 
                 // Flash Effect
                 const flash = document.getElementById('camera-flash');
                 if (flash) {
                     flash.style.opacity = '0.8';
                     setTimeout(() => { flash.style.opacity = '0'; }, 100);
                 }

                 if (!video || !canvas) return;
                 
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
                 if(stream) stream.getTracks().forEach(track => track.stop());

                 try {
                     await submitAttendance(state.scannedCode, photo);
                 } catch (e) {
                     // Reset UI on error
                     if (processingContainer) processingContainer.classList.add('hidden');
                     if (selfieContainer) selfieContainer.classList.remove('hidden');
                     
                     // Restart Camera
                     await startSelfieCamera();
                 }
            }

            async function submitAttendance(code, photo) {
                 // Check Out Logic
                 if (state.hasCheckedIn && !state.hasCheckedOut) {
                    let note = null;

                    // Early Checkout Check
                    const attendanceData = await window.Livewire.find('{{ $_instance->getId() }}').call('getAttendance');

                    if (attendanceData && attendanceData.shift_end_time) {
                        const now = new Date();
                        // Parse shift_end_time (HH:mm:ss) to today's date obj
                        const [hours, minutes, seconds] = attendanceData.shift_end_time.split(':');
                        const shiftEnd = new Date();
                        shiftEnd.setHours(hours, minutes, seconds || 0);
                        

                        if (now < shiftEnd) {
                            const formattedTime = formatTime(attendanceData.shift_end_time);
                            const result = await Swal.fire({
                                title: "{{ __('Early Leave?') }}",
                                text: "{{ __('It is not yet time to leave') }} (" + formattedTime + "). {{ __('Please provide a reason:') }}",
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

                    const result = await window.Livewire.find('{{ $_instance->getId() }}').call('scan',
                        code, null, null, photo, note);
                    handleScanResult(result, scanner, startScanning);
                    return;
                }

                if (!(await checkTime())) {
                    // Retry scan flow
                    window.location.reload(); 
                    return;
                }

                // Face Verification Check for Check In
                if (state.requiresFaceVerification) {
                    // Dispatch event to open face verification modal
                    window.dispatchEvent(new CustomEvent('face-verify', {
                        detail: {
                            callback: async () => {
                                const result = await window.Livewire.find('{{ $_instance->getId() }}').call('scan',
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
                     setTimeout(() => { flash.style.opacity = '0'; }, 100);
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
                    if (scanner.getState() === Html5QrcodeScannerState.SCANNING || scanner.getState() === Html5QrcodeScannerState.PAUSED) {
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
                        background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                        color: document.documentElement.classList.contains('dark') ? '#ffffff' : '#1f2937'
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
                            title: '{{ __("Error") }}',
                            text: result,
                            background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                            color: document.documentElement.classList.contains('dark') ? '#ffffff' : '#1f2937'
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
                    // Check 1: Minimum attendance duration (1 minute safety to prevent accidental double taps)
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

            observer.observe(scannerEl, {
                childList: true,
                subtree: true
            });

            // Handle shift selector
            if (!state.hasCheckedIn) {
                const shift = document.querySelector('#shift_id');
                if (shift) {
                    const msg = 'Please select a shift first';
                    let isRendered = false;

                    setTimeout(() => {
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
                            scanner.pause(true);
                            if (state.errorMsg) {
                                state.errorMsg.classList.remove('hidden');
                                state.errorMsg.innerHTML = msg;
                            }
                        } else if (scanner.getState() === Html5QrcodeScannerState.PAUSED) {
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
                const {
                    Geolocation
                } = window.Capacitor.Plugins;

                const status = await Geolocation.checkPermissions();

                if (status.location === 'granted') return true;

                const req = await Geolocation.requestPermissions();
                return req.location === 'granted';
            }

            if (!navigator.geolocation) return false;

            if (navigator.permissions) {
                const perm = await navigator.permissions.query({
                    name: 'geolocation'
                });
                return perm.state === 'granted' || perm.state === 'prompt';
            }

            return true;
        }

        (async () => {
            if (state.approvedAbsence) return;

            const allowed = await ensureLocationPermission();

            if (allowed) {
                await getLocation();
            } else if (state.errorMsg) {
                state.errorMsg.classList.remove('hidden');
                state.errorMsg.innerHTML = 'Please enable location permission';
            }

            initScanner();
        })();

    });
</script>

