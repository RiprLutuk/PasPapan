import { Capacitor } from "@capacitor/core";
import CapacitorGeolocation from "./capacitor-geolocation";

export async function getCurrentLocation(options = {}) {
    const geoOptions = {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0,
        ...options,
    };

    // 📱 ANDROID / IOS (APK)
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

    // 🌐 WEB (HTTP / HTTPS)
    return new Promise((resolve, reject) => {
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
            (err) => reject(err),
            geoOptions
        );
    });
}
