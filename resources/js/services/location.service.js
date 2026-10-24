
export async function getCurrentLocation(options = {}) {
    const geoOptions = {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0,
        ...options,
    };

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
            (err) => reject(err),
            geoOptions
        );
    });
}
