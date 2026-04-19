<x-app-layout>
    <x-admin.page-shell
        :title="__('New Barcode')"
        :description="__('Create a new attendance checkpoint and map its valid scan radius.')"
    >
        <div class="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800">
            <div class="p-4 lg:p-6">
                <form action="{{ route('admin.barcodes.store') }}" method="post">
                        @csrf

                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:gap-3">
                            <div class="w-full">
                                <x-forms.label for="name">Nama Barcode</x-forms.label>
                                <x-forms.input name="name" id="name" class="mt-1 block w-full" type="text"
                                    :value="old('name')" placeholder="Barcode Baru" />
                                @error('name')
                                    <x-forms.input-error for="name" class="mt-2" message="{{ $message }}" />
                                @enderror
                            </div>
                            <div id="barcode-value-field" class="w-full">
                                <x-forms.label for="value">{{ __('Value Barcode') }}</x-forms.label>
                                @livewire('admin.barcode-value-input-component')
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ __('Wajib untuk barcode statis. Untuk mode dinamis, kode internal akan dibuat otomatis.') }}
                                </p>
                            </div>
                            <div id="dynamic-barcode-value-info" class="hidden w-full rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 text-sm text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300">
                                {{ __('Mode dinamis aktif: Value Barcode disembunyikan dan akan dibuat otomatis sebagai kode internal acak. Gunakan halaman Live Display untuk QR yang discan.') }}
                            </div>
                        </div>

                        <div class="mt-4 flex gap-3">
                            <div class="w-full">
                                <x-forms.label for="radius">Radius Valid Absen</x-forms.label>
                                <x-forms.input name="radius" id="radius" class="mt-1 block w-full" type="number"
                                    :value="old('radius')" placeholder="50 (meter)" />
                                @error('radius')
                                    <x-forms.input-error for="radius" class="mt-2" message="{{ $message }}" />
                                @enderror
                            </div>
                            <div class="w-full">
                            </div>
                        </div>

                        <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-900/70">
                            <div class="flex items-start gap-3">
                                <input
                                    id="dynamic_enabled"
                                    name="dynamic_enabled"
                                    type="checkbox"
                                    value="1"
                                    @checked(old('dynamic_enabled'))
                                    class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                >
                                <div class="w-full">
                                    <x-forms.label for="dynamic_enabled" value="{{ __('Aktifkan barcode dinamis') }}" />
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                        {{ __('QR akan berubah otomatis dengan nonce acak, dan barcode lama menjadi tidak valid setelah masa aktifnya habis.') }}
                                    </p>

                                    <div class="mt-4 max-w-xs">
                                        <x-forms.label for="dynamic_ttl_seconds" value="{{ __('Durasi aktif barcode dinamis (detik)') }}" />
                                        <x-forms.input
                                            name="dynamic_ttl_seconds"
                                            id="dynamic_ttl_seconds"
                                            class="mt-1 block w-full"
                                            type="number"
                                            min="30"
                                            max="300"
                                            :value="old('dynamic_ttl_seconds', 60)"
                                        />
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                            {{ __('Disarankan 30-60 detik. Setiap token punya nonce acak agar pola QR tidak mudah ditebak.') }}
                                        </p>
                                        @error('dynamic_ttl_seconds')
                                            <x-forms.input-error for="dynamic_ttl_seconds" class="mt-2" message="{{ $message }}" />
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5">
                            <h3 class="text-lg font-semibold dark:text-gray-400">{{ __('Coordinate') }}</h3>

                            <div class="mt-3 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-900/70">
                                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Location helper') }}</p>
                                        <p id="location-helper-status" class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                            {{ __('Use your current location or type latitude and longitude manually to position the checkpoint.') }}
                                        </p>
                                    </div>
                                    <x-actions.button type="button" id="detect-current-location" variant="secondary">
                                        <x-heroicon-o-map-pin class="mr-2 h-5 w-5" />
                                        {{ __('Use Current Location') }}
                                    </x-actions.button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <div class="w-full">
                                    <x-forms.label for="lat">Latitude</x-forms.label>
                                    <x-forms.input name="lat" id="lat" class="mt-1 block w-full" type="text"
                                        :value="old('lat')" placeholder="cth: -6.12345" />
                                    @error('lat')
                                        <x-forms.input-error for="lat" class="mt-2" message="{{ $message }}" />
                                    @enderror
                                </div>
                                <div class="w-full">
                                    <x-forms.label for="lng">Longitude</x-forms.label>
                                    <x-forms.input name="lng" id="lng" class="mt-1 block w-full" type="text"
                                        :value="old('lng')" placeholder="cth: 6.12345" />
                                    @error('lng')
                                        <x-forms.input-error for="lng" class="mt-2" message="{{ $message }}" />
                                    @enderror
                                </div>
                            </div>

                            <div class="flex flex-col items-start gap-3 md:flex-row">
                                <x-actions.button type="button" onclick="toggleMap()" class="text-nowrap mt-4">
                                    <x-heroicon-s-map-pin class="mr-2 h-5 w-5" /> Tampilkan/Sembunyikan Peta
                                </x-actions.button>

                                <div id="map" class="my-6 h-72 w-full md:h-96"></div>
                            </div>

                            <div class="mb-3 mt-4 flex items-center justify-end">
                                <x-actions.button class="ms-4">
                                    {{ __('Save') }}
                                </x-actions.button>
                            </div>
                        </div>
                </form>
            </div>
        </div>
    </x-admin.page-shell>

    @pushOnce('scripts')
        <script>
            window.addEventListener("load", function() {
                const latInput = document.getElementById('lat');
                const lngInput = document.getElementById('lng');
                const detectButton = document.getElementById('detect-current-location');
                const helperStatus = document.getElementById('location-helper-status');
                const dynamicCheckbox = document.getElementById('dynamic_enabled');
                const barcodeValueField = document.getElementById('barcode-value-field');
                const dynamicBarcodeValueInfo = document.getElementById('dynamic-barcode-value-info');
                let syncTimer = null;

                const syncBarcodeValueMode = () => {
                    const isDynamic = dynamicCheckbox?.checked === true;
                    const valueInput = barcodeValueField?.querySelector('input[name="value"]');
                    const generateButton = barcodeValueField?.querySelector('button');

                    barcodeValueField?.classList.toggle('hidden', isDynamic);
                    dynamicBarcodeValueInfo?.classList.toggle('hidden', !isDynamic);
                    valueInput?.toggleAttribute('disabled', isDynamic);
                    valueInput?.toggleAttribute('required', !isDynamic);
                    generateButton?.toggleAttribute('disabled', isDynamic);
                };

                syncBarcodeValueMode();
                dynamicCheckbox?.addEventListener('change', syncBarcodeValueMode);

                const setStatus = (message, tone = 'default') => {
                    helperStatus.textContent = message;
                    helperStatus.classList.remove('text-slate-500', 'dark:text-slate-400', 'text-emerald-600', 'dark:text-emerald-400', 'text-red-600', 'dark:text-red-400');

                    if (tone === 'success') {
                        helperStatus.classList.add('text-emerald-600', 'dark:text-emerald-400');
                    } else if (tone === 'error') {
                        helperStatus.classList.add('text-red-600', 'dark:text-red-400');
                    } else {
                        helperStatus.classList.add('text-slate-500', 'dark:text-slate-400');
                    }
                };

                const syncMapFromInputs = () => {
                    const lat = Number(latInput.value);
                    const lng = Number(lngInput.value);

                    if (Number.isFinite(lat) && Number.isFinite(lng)) {
                        window.setMapLocation({
                            location: [lat, lng],
                        });
                        setStatus('Koordinat manual diterapkan ke peta.', 'success');
                    }
                };

                const requestBrowserLocation = (options) => new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(
                        (position) => resolve({
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude,
                            accuracy: position.coords.accuracy,
                        }),
                        reject,
                        options
                    );
                });

                const watchBrowserLocation = (options) => new Promise((resolve, reject) => {
                    let settled = false;
                    const watchId = navigator.geolocation.watchPosition(
                        (position) => {
                            if (settled) {
                                return;
                            }

                            settled = true;
                            navigator.geolocation.clearWatch(watchId);
                            resolve({
                                latitude: position.coords.latitude,
                                longitude: position.coords.longitude,
                                accuracy: position.coords.accuracy,
                            });
                        },
                        (error) => {
                            if (settled) {
                                return;
                            }

                            settled = true;
                            navigator.geolocation.clearWatch(watchId);
                            reject(error);
                        },
                        options
                    );

                    setTimeout(() => {
                        if (settled) {
                            return;
                        }

                        settled = true;
                        navigator.geolocation.clearWatch(watchId);
                        reject(new Error('WatchPosition timeout'));
                    }, (options?.timeout || 15000) + 2000);
                });

                const getBrowserLocation = async (permissionState = null) => {
                    try {
                        return await requestBrowserLocation({
                            enableHighAccuracy: true,
                            timeout: 15000,
                            maximumAge: 0,
                        });
                    } catch (error) {
                        const shouldRetry = permissionState === 'granted'
                            && (error?.code === 1 || error?.code === 2 || error?.code === 3);

                        if (!shouldRetry) {
                            throw error;
                        }

                        try {
                            setStatus('Izin sudah aktif. Mencoba ulang dengan mode lokasi kompatibel...');

                            return await requestBrowserLocation({
                                enableHighAccuracy: false,
                                timeout: 45000,
                                maximumAge: 300000,
                            });
                        } catch (fallbackError) {
                            setStatus('Mencoba fallback lokasi dari pembaruan GPS perangkat...');

                            return await watchBrowserLocation({
                                enableHighAccuracy: false,
                                timeout: 20000,
                                maximumAge: 300000,
                            });
                        }
                    }
                };

                const getLocationPermissionState = async () => {
                    if (!navigator.permissions?.query) {
                        return null;
                    }

                    try {
                        const permission = await navigator.permissions.query({ name: 'geolocation' });
                        return permission.state;
                    } catch (error) {
                        return null;
                    }
                };

                const isNativeApp = () => window.isNativeApp?.() === true;

                const describeLocationError = (error) => {
                    if (!error) {
                        return 'unknown error';
                    }

                    const details = [
                        error.code ? `code=${error.code}` : null,
                        error.message ? `message=${error.message}` : null,
                    ].filter(Boolean);

                    return details.length > 0 ? details.join(', ') : String(error);
                };

                const getLocationErrorMessage = (error, permissionState = null) => {
                    const message = String(error?.message || '').toLowerCase();

                    if (window.isSecureContext === false) {
                        return 'Deteksi lokasi di web hanya bisa lewat HTTPS atau localhost. Jangan buka dari http://IP-LAN.';
                    }

                    if (message.includes('https') || message.includes('secure')) {
                        return 'Deteksi lokasi membutuhkan HTTPS atau localhost. Buka halaman ini lewat HTTPS, localhost, atau aplikasi mobile.';
                    }

                    if (error?.code === 1 || message.includes('denied') || message.includes('permission')) {
                        if (permissionState === 'granted') {
                            return `Browser sudah mengizinkan lokasi, tapi sistem/perangkat tetap menolak (${describeLocationError(error)}). Aktifkan Location Services/GPS untuk browser ini di pengaturan OS, lalu reload halaman.`;
                        }

                        if (permissionState === 'prompt') {
                            return 'Permintaan lokasi belum disetujui. Klik Allow/Izinkan pada prompt lokasi browser, lalu coba lagi.';
                        }

                        return 'Izin lokasi ditolak. Aktifkan izin lokasi untuk browser/aplikasi ini, lalu coba lagi.';
                    }

                    if (error?.code === 3 || message.includes('timeout')) {
                        return 'Pengambilan lokasi timeout. Pastikan GPS aktif dan sinyal lokasi stabil, lalu coba lagi.';
                    }

                    if (error?.code === 2 || message.includes('unavailable')) {
                        return 'Lokasi perangkat belum tersedia. Pastikan GPS aktif dan coba lagi dari area dengan sinyal lebih baik.';
                    }

                    return 'Gagal mengambil lokasi saat ini. Pastikan izin lokasi dan GPS sudah aktif.';
                };

                window.initializeMap({
                    onUpdate: (lat, lng) => {
                        latInput.value = Number(lat).toFixed(6);
                        lngInput.value = Number(lng).toFixed(6);
                    },
                    location: @if (old('lat') && old('lng'))
                        [Number({{ old('lat') }}), Number({{ old('lng') }})]
                    @else
                        undefined
                    @endif
                });

                [latInput, lngInput].forEach((input) => {
                    input.addEventListener('input', () => {
                        clearTimeout(syncTimer);
                        syncTimer = setTimeout(syncMapFromInputs, 250);
                    });
                });

                detectButton?.addEventListener('click', async () => {
                    if (!isNativeApp() && window.isSecureContext === false) {
                        setStatus('Deteksi lokasi di web hanya bisa lewat HTTPS atau localhost. Jangan buka dari http://IP-LAN.', 'error');
                        return;
                    }

                    if ((isNativeApp() && !window.deviceManager?.getCurrentLocation) || (!isNativeApp() && !navigator.geolocation)) {
                        setStatus('Browser/perangkat ini tidak mendukung deteksi lokasi.', 'error');
                        return;
                    }

                    try {
                        detectButton.setAttribute('disabled', 'disabled');
                        setStatus('Mengambil lokasi perangkat saat ini...');
                        const permissionState = await getLocationPermissionState();

                        let location;

                        if (isNativeApp()) {
                            location = await window.deviceManager.getCurrentLocation();
                        } else {
                            location = await getBrowserLocation(permissionState);
                        }

                        latInput.value = Number(location.latitude).toFixed(6);
                        lngInput.value = Number(location.longitude).toFixed(6);
                        syncMapFromInputs();
                        setStatus('Lokasi saat ini berhasil dipakai untuk checkpoint.', 'success');
                    } catch (error) {
                        const permissionState = await getLocationPermissionState();
                        console.warn('Failed to get current location for barcode admin form', {
                            permissionState,
                            error,
                        });
                        setStatus(getLocationErrorMessage(error, permissionState), 'error');
                    } finally {
                        detectButton.removeAttribute('disabled');
                    }
                });
            });

            let map = document.getElementById('map');

            function toggleMap() {
                map.style.display = map.style.display === "none" ? "block" : "none";
            }
        </script>
    @endPushOnce
</x-app-layout>
