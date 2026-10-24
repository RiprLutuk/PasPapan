import test from 'node:test';
import assert from 'node:assert/strict';
import { getCurrentLocation } from '../resources/js/services/location.service.js';

test('browser geolocation preserves coordinates/options without native plugins', async () => {
    const previousWindow = Object.getOwnPropertyDescriptor(globalThis, 'window');
    const previousNavigator = Object.getOwnPropertyDescriptor(globalThis, 'navigator');
    try {
        Object.defineProperty(globalThis, 'window', { configurable: true, value: { isSecureContext: true } });
        let receivedOptions;
        Object.defineProperty(globalThis, 'navigator', { configurable: true, value: {
            geolocation: { getCurrentPosition(resolve, reject, options) { receivedOptions = options; resolve({ coords: { latitude: -6.2, longitude: 106.8, accuracy: 8 } }); } },
        } });
        assert.deepEqual(await getCurrentLocation({ timeout: 5000 }), { latitude: -6.2, longitude: 106.8, accuracy: 8 });
        assert.deepEqual(receivedOptions, { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 });
        window.isSecureContext = false;
        await assert.rejects(getCurrentLocation(), /HTTPS or localhost/);
        window.isSecureContext = true;
        navigator.geolocation.getCurrentPosition = (_resolve, reject) => reject(new Error('Permission denied'));
        await assert.rejects(getCurrentLocation(), /Permission denied/);
        delete navigator.geolocation;
        await assert.rejects(getCurrentLocation(), /not supported/);
    } finally {
        if (previousWindow) Object.defineProperty(globalThis, 'window', previousWindow); else delete globalThis.window;
        if (previousNavigator) Object.defineProperty(globalThis, 'navigator', previousNavigator); else delete globalThis.navigator;
    }
});
