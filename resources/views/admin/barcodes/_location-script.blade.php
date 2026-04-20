@pushOnce('scripts')
    <script>
        window.addEventListener("load", function() {
            const latInput = document.getElementById('lat');
            const lngInput = document.getElementById('lng');
            const detectButton = document.getElementById('detect-current-location');
            const helperStatus = document.getElementById('location-helper-status');
            const dynamicCheckbox = document.getElementById('dynamic_enabled');
            const barcodeValueField = document.getElementById('barcode-value-field');
            const dynamicBarcodeConfig = document.getElementById('dynamic-barcode-config');
            let syncTimer = null;

            const syncBarcodeValueMode = () => {
                const isDynamic = dynamicCheckbox?.checked === true;
                const valueInput = barcodeValueField?.querySelector('input[name="value"]');
                const generateButton = barcodeValueField?.querySelector('button');
                const ttlInput = document.getElementById('dynamic_ttl_seconds');

                barcodeValueField?.classList.toggle('hidden', isDynamic);
                dynamicBarcodeConfig?.classList.toggle('hidden', !isDynamic);
                valueInput?.toggleAttribute('disabled', isDynamic);
                valueInput?.toggleAttribute('required', !isDynamic);
                generateButton?.toggleAttribute('disabled', isDynamic);
                ttlInput?.toggleAttribute('disabled', !isDynamic);
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
                    setStatus(@json(__('Coordinates updated on the map.')), 'success');
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
                        if (settled) return;
                        settled = true;
                        navigator.geolocation.clearWatch(watchId);
                        resolve({
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude,
                            accuracy: position.coords.accuracy,
                        });
                    },
                    (error) => {
                        if (settled) return;
                        settled = true;
                        navigator.geolocation.clearWatch(watchId);
                        reject(error);
                    },
                    options
                );

                setTimeout(() => {
                    if (settled) return;
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
                        setStatus(@json(__('Permission already granted. Retrying with compatibility mode...')));
                        return await requestBrowserLocation({
                            enableHighAccuracy: false,
                            timeout: 45000,
                            maximumAge: 300000,
                        });
                    } catch (fallbackError) {
                        setStatus(@json(__('Trying GPS fallback from the device...')));
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
                if (!error) return 'unknown error';

                const details = [
                    error.code ? `code=${error.code}` : null,
                    error.message ? `message=${error.message}` : null,
                ].filter(Boolean);

                return details.length > 0 ? details.join(', ') : String(error);
            };

            const getLocationErrorMessage = (error, permissionState = null) => {
                const message = String(error?.message || '').toLowerCase();

                if (window.isSecureContext === false) {
                    return @json(__('Location detection on web requires HTTPS or localhost.'));
                }

                if (message.includes('https') || message.includes('secure')) {
                    return @json(__('Location detection requires HTTPS, localhost, or the mobile app.'));
                }

                if (error?.code === 1 || message.includes('denied') || message.includes('permission')) {
                    if (permissionState === 'granted') {
                        return `@json(__('The browser already has permission, but the device still blocked location access. Enable GPS or Location Services for this browser and reload the page.')) (${describeLocationError(error)})`;
                    }

                    if (permissionState === 'prompt') {
                        return @json(__('Approve the browser location prompt, then try again.'));
                    }

                    return @json(__('Location permission is blocked. Enable it for this browser or app, then try again.'));
                }

                if (error?.code === 3 || message.includes('timeout')) {
                    return @json(__('Location request timed out. Make sure GPS is active and try again.'));
                }

                if (error?.code === 2 || message.includes('unavailable')) {
                    return @json(__('Device location is unavailable. Turn on GPS and try again.'));
                }

                return @json(__('Could not read the current location. Make sure permission and GPS are enabled.'));
            };

            const initialLat = @json($initialLat);
            const initialLng = @json($initialLng);
            const hasInitialCoordinates = initialLat !== null && initialLng !== null && initialLat !== '' && initialLng !== '';

            window.initializeMap({
                onUpdate: (lat, lng) => {
                    latInput.value = Number(lat).toFixed(6);
                    lngInput.value = Number(lng).toFixed(6);
                },
                location: hasInitialCoordinates ? [Number(initialLat), Number(initialLng)] : undefined,
            });

            [latInput, lngInput].forEach((input) => {
                input?.addEventListener('input', () => {
                    clearTimeout(syncTimer);
                    syncTimer = setTimeout(syncMapFromInputs, 250);
                });
            });

            detectButton?.addEventListener('click', async () => {
                if (!isNativeApp() && window.isSecureContext === false) {
                    setStatus(@json(__('Location detection on web requires HTTPS or localhost.')), 'error');
                    return;
                }

                if ((isNativeApp() && !window.deviceManager?.getCurrentLocation) || (!isNativeApp() && !navigator.geolocation)) {
                    setStatus(@json(__('This browser or device does not support location detection.')), 'error');
                    return;
                }

                try {
                    detectButton.setAttribute('disabled', 'disabled');
                    setStatus(@json(__('Reading your current location...')));
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
                    setStatus(@json(__('Current location applied successfully.')), 'success');
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
    </script>
@endPushOnce
