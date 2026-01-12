<div class="w-full to-slate-100 dark:from-slate-900 dark:to-slate-800">
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

        {{-- Header Section Removed --}}

        @include('components.alert-messages')

        @if ($isComplete)
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
                <div class="py-2">
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

                <div class="flex flex-col gap-4 sm:gap-6 lg:flex-row">
                    @if (!$isAbsence)
                        <div class="flex flex-col gap-4 sm:gap-6 lg:w-2/5">
                            @include('components.shift-selector', ['disabled' => true])
                            
                            <div id="scanner-card-container">
                                @include('components.scanner-card', ['title' => __('Scan to Check Out')])
                            </div>
                            
                            {{-- Selfie UI (Hidden by default) --}}
                            <div id="selfie-card-container" class="hidden rounded-lg border border-gray-200 bg-white p-4 sm:p-6 shadow dark:border-gray-700 dark:bg-gray-800">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 text-center">{{ __('Take a Selfie') }}</h3>
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
                    @endif

                    <div class="flex-1 space-y-4 sm:space-y-6">
                        {{-- Status & Location History Removed (Integrated into Header) --}}

                        {{-- Current Location for Check Out --}}
                        @include('components.location-card', [
                            'title' => __('Current Location'),
                            'mapId' => 'currentLocationMap',
                            'latitude' => $currentLiveCoords[0] ?? null,
                            'longitude' => $currentLiveCoords[1] ?? null,
                            'showRefresh' => true,
                            'icon' =>
                                'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z',
                            'iconColor' => 'green',
                        ])

                        {{-- Action Buttons (Removed) --}}
                    </div>
                </div>
            </div>
        @else
            {{-- Initial State - Not Checked In --}}
            <div class="flex flex-col gap-4 sm:gap-6 lg:flex-row">
                @if (!$isAbsence)
                    <div class="flex flex-col gap-4 sm:gap-6 lg:w-2/5">
                        <div id="scanner-card-container">
                             @component('components.scanner-card', ['title' => __('Scan QR Code')])
                                @slot('headerActions')
                                    @include('components.shift-selector')
                                @endslot
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
                         </div>
                    </div>
                @endif

                <div class="flex-1 space-y-4 sm:space-y-6">
                    @include('components.location-card', [
                        'title' => __('Current Location'),
                        'mapId' => 'currentLocationMap',
                        'latitude' => $currentLiveCoords[0] ?? null,
                        'longitude' => $currentLiveCoords[1] ?? null,
                        'showRefresh' => true,
                        'icon' =>
                            'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z',
                        'iconColor' => 'green',
                    ])

                    {{-- Time Cards Removed --}}

                    {{-- Action Buttons (Removed) --}}
                </div>
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
            isRefreshing: false,
            userLat: null,
            userLng: null,
            isRefreshing: false,
            facingMode: 'environment', // Start with back camera for scanning
            lastPhoto: null,
            requirePhoto: {{ $requirePhoto ? 'true' : 'false' }},
            isSelfieMode: false,
            scannedCode: null,
            timeSettings: @json($timeSettings)
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

        async function getLocation(isRefresh = false) {
            try {
                let lat, lng;

                if (window.Capacitor?.isNativePlatform?.()) {
                    const perm = await Capacitor.Plugins.Geolocation.requestPermissions();

                    if (perm.location !== 'granted') {
                        throw new Error('Location permission denied');
                    }

                    const position = await Capacitor.Plugins.Geolocation.getCurrentPosition({
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    });

                    lat = position.coords.latitude.toFixed(6);
                    lng = position.coords.longitude.toFixed(6);
                } else {
                    if (!navigator.geolocation) {
                        throw new Error('Geolocation not supported');
                    }

                    const position = await new Promise((resolve, reject) => {
                        navigator.geolocation.getCurrentPosition(
                            resolve,
                            reject, {
                                enableHighAccuracy: true,
                                timeout: 10000,
                                maximumAge: 0
                            }
                        );
                    });

                    lat = position.coords.latitude.toFixed(6);
                    lng = position.coords.longitude.toFixed(6);
                }

                state.userLat = parseFloat(lat);
                state.userLng = parseFloat(lng);

                if (window.Livewire) {
                    window.Livewire.find('{{ $_instance->getId() }}')
                        .set('currentLiveCoords', [state.userLat, state.userLng]);
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
            if (state.isAbsence || state.isComplete) return;

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
                if (scanner.getState() === Html5QrcodeScannerState.SCANNING) {
                    await scanner.stop();
                    setShowOverlay(false);
                    state.facingMode = state.facingMode === 'environment' ? 'user' : 'environment';
                    await startScanning();
                }
            };

            function setShowOverlay(show) {
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

            async function startScanning() {
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
                        await window.startNativeBarcodeScanner(onScanSuccess);
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

                // Step 1: Check if photo is required
                if (state.requirePhoto) {
                    enterSelfieMode();
                    return;
                }
                
                // If photo not required, submit immediately
                submitAttendance(decodedText, null);
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
                 
                 // Flash
                 const flash = document.getElementById('camera-flash');
                 if (flash) {
                     flash.style.opacity = '0.8';
                     setTimeout(() => { flash.style.opacity = '0'; }, 100);
                 }

                 if (!video || !canvas) return;
                 
                 const context = canvas.getContext('2d');
                 canvas.width = video.videoWidth;
                 canvas.height = video.videoHeight;
                 context.drawImage(video, 0, 0, canvas.width, canvas.height);
                 
                 const photo = canvas.toDataURL('image/jpeg', 0.8);
                 state.lastPhoto = photo;
                 
                 // Stop Stream
                 const stream = video.srcObject;
                 if(stream) stream.getTracks().forEach(track => track.stop());

                 await submitAttendance(state.scannedCode, photo);
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
                        window.location.reload();
                    });
                } else if (typeof result === 'string') {
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
<script>
    window.startNativeBarcodeScanner = async function(onSuccess) {
        try {
            if (!window.Capacitor?.Plugins?.BarcodeScanner) {
                throw new Error('BarcodeScanner plugin not available');
            }

            const {
                BarcodeScanner
            } = window.Capacitor.Plugins;

            // Permission
            const perm = await BarcodeScanner.checkPermission({
                force: true
            });
            if (!perm.granted) {
                throw new Error('Camera permission denied');
            }

            // Hide WebView background
            await BarcodeScanner.hideBackground();
            document.body.classList.add('scanner-active');

            // Start scan (FULLSCREEN)
            const result = await BarcodeScanner.startScan({
                targetedFormats: ['QR_CODE'],
                cameraDirection: 'rear'
            });

            // Restore UI
            await BarcodeScanner.showBackground();
            await BarcodeScanner.stopScan();
            document.body.classList.remove('scanner-active');

            if (result?.hasContent) {
                onSuccess(result.content);
            }

        } catch (err) {
            console.error('[Native Scanner Error]', err);

            await window.Capacitor.Plugins.BarcodeScanner?.showBackground();
            await window.Capacitor.Plugins.BarcodeScanner?.stopScan();
            document.body.classList.remove('scanner-active');

            const errorEl = document.getElementById('scanner-error');
            if (errorEl) {
                errorEl.classList.remove('hidden');
                errorEl.innerHTML = err.message || 'Failed to open camera';
            }
        }
    };
</script>
