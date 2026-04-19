import { Capacitor, registerPlugin } from "@capacitor/core";

const DEFAULT_WEB_OPTIONS = {
    enableHighAccuracy: false,
    timeout: 10000,
    maximumAge: 0,
};

const NativeGeolocation = registerPlugin("Geolocation");

const createPermissionStatus = (state) => ({
    location: state,
    coarseLocation: state,
});

const getWebGeolocation = () => {
    if (typeof navigator === "undefined" || !navigator.geolocation) {
        throw new Error("Geolocation not supported");
    }

    return navigator.geolocation;
};

const getNativeGeolocation = () => NativeGeolocation;

const getCurrentPositionWeb = (options = {}) =>
    new Promise((resolve, reject) => {
        getWebGeolocation().getCurrentPosition(
            resolve,
            reject,
            {
                ...DEFAULT_WEB_OPTIONS,
                ...options,
            }
        );
    });

const watchPositionWeb = (options = {}, callback) =>
    `${getWebGeolocation().watchPosition(
        (position) => callback(position),
        (error) => callback(null, error),
        {
            ...DEFAULT_WEB_OPTIONS,
            minimumUpdateInterval: 5000,
            ...options,
        }
    )}`;

export const CapacitorGeolocation = {
    async checkPermissions() {
        if (Capacitor.isNativePlatform()) {
            return getNativeGeolocation().checkPermissions();
        }

        if (typeof navigator === "undefined" || !navigator.geolocation) {
            return createPermissionStatus("denied");
        }

        if (!navigator.permissions?.query) {
            return createPermissionStatus("prompt");
        }

        try {
            const permission = await navigator.permissions.query({
                name: "geolocation",
            });

            return createPermissionStatus(permission.state);
        } catch {
            return createPermissionStatus("prompt");
        }
    },

    async requestPermissions(options) {
        if (Capacitor.isNativePlatform()) {
            return getNativeGeolocation().requestPermissions(options);
        }

        const current = await this.checkPermissions();

        if (current.location === "granted") {
            return current;
        }

        try {
            await getCurrentPositionWeb();
        } catch (error) {
            if (error?.code === 1) {
                return createPermissionStatus("denied");
            }
        }

        return this.checkPermissions();
    },

    async getCurrentPosition(options) {
        if (Capacitor.isNativePlatform()) {
            return getNativeGeolocation().getCurrentPosition(options);
        }

        return getCurrentPositionWeb(options);
    },

    async watchPosition(options, callback) {
        if (Capacitor.isNativePlatform()) {
            return getNativeGeolocation().watchPosition(options, callback);
        }

        return watchPositionWeb(options, callback);
    },

    async clearWatch(options) {
        if (Capacitor.isNativePlatform()) {
            return getNativeGeolocation().clearWatch(options);
        }

        const id = typeof options === "string" ? options : options?.id;
        getWebGeolocation().clearWatch(Number.parseInt(id, 10));
    },
};

export default CapacitorGeolocation;
