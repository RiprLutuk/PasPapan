import { Capacitor } from "@capacitor/core";
import CapacitorGeolocation from "./services/capacitor-geolocation";

export default class CapacitorDeviceManager {
    async getCurrentLocation(options = {}) {
        const geoOptions = {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0,
            ...options,
        };

        if (Capacitor.isNativePlatform()) {
            const perm = await CapacitorGeolocation.requestPermissions();

            if (perm.location !== "granted") {
                throw new Error("Location permission denied");
            }

            const pos = await CapacitorGeolocation.getCurrentPosition(geoOptions);

            return {
                latitude: pos.coords.latitude,
                longitude: pos.coords.longitude,
                accuracy: pos.coords.accuracy,
            };
        }

        return new Promise((resolve, reject) => {
            if (window.isSecureContext === false) {
                reject(new Error("Geolocation requires HTTPS or localhost"));
                return;
            }

            if (!navigator.geolocation) {
                reject(new Error("Geolocation not supported"));
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (pos) =>
                    resolve({
                        latitude: pos.coords.latitude,
                        longitude: pos.coords.longitude,
                        accuracy: pos.coords.accuracy,
                    }),
                reject,
                geoOptions
            );
        });
    }
}
