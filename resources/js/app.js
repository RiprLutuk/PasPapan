import "./bootstrap";
import TomSelect from "tom-select";
import "tom-select/dist/css/tom-select.css";
import Swal from "sweetalert2";
import Chart from "chart.js/auto";
import { Capacitor } from "@capacitor/core";
import { App } from "@capacitor/app";
import { Browser } from "@capacitor/browser";
import { Geolocation } from "@capacitor/geolocation";
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import "leaflet.markercluster";
import "leaflet.markercluster/dist/MarkerCluster.css";
import "leaflet.markercluster/dist/MarkerCluster.Default.css";
import markerIcon2x from "leaflet/dist/images/marker-icon-2x.png";
import markerIcon from "leaflet/dist/images/marker-icon.png";
import markerShadow from "leaflet/dist/images/marker-shadow.png";

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

window.L = L;
window.TomSelect = TomSelect;
window.Swal = Swal;
window.Chart = Chart;
window.Capacitor = window.Capacitor || Capacitor;
window.CapacitorGeolocation = Geolocation;
window.CapacitorApp = App;

let nativeBarcodeModulePromise;
let mockLocationModulePromise;

const loadNativeBarcodeModule = () => {
    nativeBarcodeModulePromise ??= import("./services/native/barcode");
    return nativeBarcodeModulePromise;
};

const loadMockLocationModule = () => {
    mockLocationModulePromise ??= import("./services/native/mock-location");
    return mockLocationModulePromise;
};

const resolveRuntimeAssetUrl = (path) => {
    if (!path) {
        return window.location.origin;
    }

    if (/^https?:\/\//i.test(path)) {
        return path;
    }

    if (path.startsWith("/")) {
        return new URL(path, window.location.origin).toString();
    }

    return new URL(path, window.location.href).toString();
};

window.prefetchAttendanceScan = ({ includeMockLocation = true } = {}) => {
    if (!window.isNativeApp?.()) {
        return Promise.resolve();
    }

    const work = () =>
        Promise.all([
            loadNativeBarcodeModule(),
            includeMockLocation ? loadMockLocationModule() : Promise.resolve(),
        ]).catch((error) => {
            console.warn("Attendance scan prefetch failed", error);
        });

    if ("requestIdleCallback" in window) {
        window.requestIdleCallback(() => {
            void work();
        });
        return Promise.resolve();
    }

    setTimeout(() => {
        void work();
    }, 150);

    return Promise.resolve();
};

document.addEventListener("livewire:navigated", () => {
    const isDark = localStorage.getItem("isDark") === "true";
    const systemDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
    
    if (localStorage.getItem("isDark")) {
        if (isDark) {
            document.documentElement.classList.add("dark");
        } else {
            document.documentElement.classList.remove("dark");
        }
    } else {
        if (systemDark) {
            document.documentElement.classList.add("dark");
        } else {
            document.documentElement.classList.remove("dark");
        }
    }
});

let map;

window.initializeMap = ({ onUpdate, location }) => {
    let defaultLocation = location ?? [-6.8905504, 109.3808162];
    map = L.map("map").setView(defaultLocation, 13);

    L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 21,
    }).addTo(map);

    let marker = L.marker(defaultLocation, {
        draggable: true,
    }).addTo(map);

    marker.on("dragend", function (event) {
        let position = marker.getLatLng();
        updateCoordinates(position.lat, position.lng);
    });

    map.on("move", function () {
        let center = map.getCenter();
        marker.setLatLng(center);
        updateCoordinates(center.lat, center.lng);
    });

    updateCoordinates(defaultLocation[0], defaultLocation[1]);

    function updateCoordinates(lat, lng) {
        onUpdate(lat, lng);
    }
};

window.setMapLocation = ({ location }) => {
    if (location == null) return;

    map.setView(location, 13);
};

window.startNativeBarcodeScanner = async (...args) => {
    const module = await loadNativeBarcodeModule();
    return module.startNativeBarcodeScanner(...args);
};

window.stopNativeBarcodeScanner = async (...args) => {
    const module = await loadNativeBarcodeModule();
    return module.stopNativeBarcodeScanner(...args);
};

window.switchNativeCamera = async (...args) => {
    const module = await loadNativeBarcodeModule();
    return module.switchNativeCamera(...args);
};

window.checkMockLocation = async (...args) => {
    const module = await loadMockLocationModule();
    return module.checkMockLocation(...args);
};

window.resolveRuntimeAssetUrl = resolveRuntimeAssetUrl;

window.isNativeApp = () =>
    !!(window.Capacitor && window.Capacitor.isNativePlatform && window.Capacitor.isNativePlatform());

window.openNativeAppSettings = async () => {
    try {
        if (window.NativeSettingsBridge?.openAppSettings) {
            window.NativeSettingsBridge.openAppSettings();
            return true;
        }
    } catch (error) {
        console.warn("Native app settings bridge failed", error);
    }

    return false;
};

window.openNativeLocationSettings = async () => {
    try {
        if (window.NativeSettingsBridge?.openLocationSettings) {
            window.NativeSettingsBridge.openLocationSettings();
            return true;
        }
    } catch (error) {
        console.warn("Native location settings bridge failed", error);
    }

    return false;
};

window.openMap = async (lat, lng) => {
    const url = `https://www.google.com/maps?q=${lat},${lng}`;
    
    if (window.isNativeApp()) {
        try {
            // Try using Capacitor Browser plugin (opens in external browser/app)
            if (Browser) {
                await Browser.open({ url });
                return;
            }
            // Fallback: Try App Launcher for geo intent (opens Google Maps app directly)
            if (App) {
                await App.openUrl({ url: `geo:${lat},${lng}?q=${lat},${lng}` });
                return;
            }
        } catch (e) {
            console.warn('Capacitor open failed, using fallback', e);
        }
        // Fallback to _system target
        window.open(url, '_system');
    } else {
        window.open(url, '_blank');
    }
};

// Mosallas-Group Pull-to-Refresh Logic Port
document.addEventListener('DOMContentLoaded', () => {
    const refreshContainer = document.querySelector(".refresh-container");
    const spinner = document.querySelector(".spinner");
    
    // We don't strictly need 'main' for Overlay style if we aren't moving it
    // But we might want to check if it exists purely for safety
    if (!refreshContainer || !spinner) return;

    let isLoading = false;
    let pStartY = 0;
    
    // Threshold to trigger refresh
    const THRESHOLD = 80;

    const loadInit = () => {
        refreshContainer.classList.add("load-init");
        isLoading = true;
    };

    const swipeStart = (e) => {
        if (isLoading) return;
        
        // Only track if we are at the top (or very close)
        // Using 2px tolerance for safe measure
        if (window.scrollY > 2) return;

        pStartY = e.touches[0].screenY;
        
        // Remove transitions during drag
        refreshContainer.style.transition = 'none';
        spinner.style.transition = 'none';
    };

    const swipe = (e) => {
        if (isLoading || pStartY === 0) return;

        const touch = e.touches[0];
        const currentY = touch.screenY;
        const diff = currentY - pStartY;

        // Only handle Pull Down when at Top
        if (diff > 0 && window.scrollY <= 2) {
            // Prevent native scroll/overscroll behavior
            if (e.cancelable) e.preventDefault();

            // Resistance Logic
            // Formula: (diff * 0.5) to feel "heavy"
            const pullDistance = diff * 0.5;

            // Cap the visual pull distance
            if (pullDistance <= 250) {
                // Determine Offset:
                // Start: -50px (hidden)
                // Pull 0px -> -50px
                // Pull 100px -> 50px visual
                const marginTop = pullDistance - 50;
                
                refreshContainer.style.marginTop = `${marginTop}px`;
                spinner.style.transform = `rotate(${pullDistance * 2.5}deg)`;
            }
        }
    };

    const swipeEnd = (e) => {
        if (isLoading || pStartY === 0) {
            pStartY = 0; // Reset
            return;
        }

        const touch = e.changedTouches[0];
        const currentY = touch.screenY;
        const diff = currentY - pStartY;
        const pullDistance = diff * 0.5;

        // Restore Transitions
        refreshContainer.style.transition = 'margin-top 0.3s cubic-bezier(0.25, 1, 0.5, 1)';
        spinner.style.transition = 'transform 0.3s linear';

        pStartY = 0; // Reset start marker

        // Trigger?
        if (pullDistance >= THRESHOLD && window.scrollY <= 2) {
            // YES - Refresh
            loadInit();
            
            // Snap to "Active" position
            // e.g. 20px from top
            refreshContainer.style.marginTop = "15px"; 
            refreshContainer.classList.add("load-start");
            
            // Reload after delay
            setTimeout(() => {
                window.location.reload();
            }, 800);
            
            // Safety timeout
            setTimeout(() => {
                resetLayout();
            }, 5000);

        } else {
            // NO - Reset
            resetLayout();
        }
    };

    const resetLayout = () => {
        isLoading = false;
        refreshContainer.classList.remove("load-init");
        refreshContainer.classList.remove("load-start");
        refreshContainer.style.marginTop = "-50px";
        spinner.style.transform = "rotate(0deg)";
    };
    
    // Use non-passive listener to allow preventDefault()
    document.addEventListener("touchstart", swipeStart, { passive: true });
    // IMPORTANT: passive: false is required to block native scroll
    document.addEventListener("touchmove", swipe, { passive: false });
    document.addEventListener("touchend", swipeEnd, { passive: true });
});

document.addEventListener('pagehide', () => {
    if (window.stopNativeBarcodeScanner) {
        window.stopNativeBarcodeScanner();
    }
});

document.addEventListener("livewire:navigating", () => {
    const root = document.querySelector("[data-face-enrollment-root]");

    if (!root || !window.Alpine?.$data) {
        return;
    }

    const component = window.Alpine.$data(root);

    if (component?.cleanup) {
        component.cleanup();
    }
});

document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'hidden' && window.stopNativeBarcodeScanner) {
        window.stopNativeBarcodeScanner();
    }
});

// Livewire Navigation (SPA-style links)
document.addEventListener('livewire:navigating', () => {
    if (window.stopNativeBarcodeScanner) {
        window.stopNativeBarcodeScanner();
    }
});

// Capacitor Back Button
if (window.isNativeApp()) {
    App.addListener('backButton', () => {
        if (window.stopNativeBarcodeScanner) {
            window.stopNativeBarcodeScanner();
        }
    });
}
