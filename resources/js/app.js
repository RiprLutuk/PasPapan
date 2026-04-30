import "./bootstrap";
import TomSelect from "tom-select";
import "tom-select/dist/css/tom-select.css";
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.css";
import Swal from "sweetalert2";
import Chart from "chart.js/auto";
import { Capacitor } from "@capacitor/core";
import { SystemBars, SystemBarType, SystemBarsStyle } from "@capacitor/core";
import { App } from "@capacitor/app";
import { Browser } from "@capacitor/browser";
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import "leaflet.markercluster";
import "leaflet.markercluster/dist/MarkerCluster.css";
import "leaflet.markercluster/dist/MarkerCluster.Default.css";
import markerIcon2x from "leaflet/dist/images/marker-icon-2x.png";
import markerIcon from "leaflet/dist/images/marker-icon.png";
import markerShadow from "leaflet/dist/images/marker-shadow.png";
import CapacitorGeolocation from "./services/capacitor-geolocation";

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

window.L = L;
window.TomSelect = TomSelect;
window.flatpickr = flatpickr;
window.Swal = Swal;
window.Chart = Chart;
window.Capacitor = window.Capacitor || Capacitor;
window.CapacitorGeolocation = CapacitorGeolocation;
window.CapacitorApp = App;

const resolveSystemBarAppearance = () => {
    const root = document.documentElement;
    const body = document.body;
    const isDark = root.classList.contains("dark");
    const isGuestPage = body?.classList.contains("guest-ui");
    const isAdminPage = body?.classList.contains("admin-ui");
    const isScanPage = body?.classList.contains("scan-attendance-route");

    if (isDark) {
        return {
            color: isGuestPage ? "#030712" : isAdminPage ? "#020617" : "#111827",
            style: SystemBarsStyle.Dark,
        };
    }

    return {
        color: isGuestPage ? "#f8fafc" : isScanPage ? "#f3f4f6" : "#ffffff",
        style: SystemBarsStyle.Light,
    };
};

const syncThemeColorMeta = (color) => {
    const themeColorMeta = document.querySelector('meta[name="theme-color"]');
    if (themeColorMeta) {
        themeColorMeta.setAttribute("content", color);
    }
};

const syncNativeSystemBars = async () => {
    if (!window.isNativeApp?.()) {
        return;
    }

    const { color, style } = resolveSystemBarAppearance();
    syncThemeColorMeta(color);

    try {
        await Promise.all([
            SystemBars.show({ bar: SystemBarType.StatusBar }),
            SystemBars.show({ bar: SystemBarType.NavigationBar }),
            SystemBars.setStyle({ bar: SystemBarType.StatusBar, style }),
            SystemBars.setStyle({ bar: SystemBarType.NavigationBar, style }),
        ]);
    } catch (error) {
        console.warn("Failed to sync native system bars", error);
    }
};

const scheduleNativeSystemBarSync = (() => {
    let frame = null;

    return () => {
        if (frame !== null) {
            window.cancelAnimationFrame(frame);
        }

        frame = window.requestAnimationFrame(() => {
            frame = null;
            void syncNativeSystemBars();
        });
    };
})();

const watchNativeSystemBarTriggers = () => {
    if (!window.MutationObserver) {
        return;
    }

    const rootObserver = new MutationObserver(() => {
        scheduleNativeSystemBarSync();
    });

    rootObserver.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ["class"],
    });

    if (document.body) {
        const bodyObserver = new MutationObserver(() => {
            scheduleNativeSystemBarSync();
        });

        bodyObserver.observe(document.body, {
            attributes: true,
            attributeFilter: ["class"],
        });
    }
};

const syncUiPickerWidth = (instance, input) => {
    const wrapper = instance?.altInput?.closest?.(".flatpickr-wrapper")
        || instance?.input?.closest?.(".flatpickr-wrapper")
        || input?.closest?.(".flatpickr-wrapper");

    if (wrapper) {
        wrapper.style.display = "block";
        wrapper.style.width = "100%";
    }

    [instance?.altInput, instance?.input, input].forEach((field) => {
        if (field) {
            field.style.display = "block";
            field.style.width = "100%";
        }
    });
};

const uiPickerValue = (input) => input?.value || input?.getAttribute?.("value") || "";

const isUiPickerDetached = (instance) => {
    if (!instance) {
        return true;
    }

    if (!document.documentElement.contains(instance.input)) {
        return true;
    }

    if (instance.config?.altInput && !document.documentElement.contains(instance.altInput)) {
        return true;
    }

    return false;
};

const syncUiPickerValue = (instance, input) => {
    const value = uiPickerValue(input);

    if (!instance || !value || instance.input.value === value) {
        return;
    }

    instance.setDate(value, false, instance.config?.dateFormat);
};

const initUiPickers = (root = document) => {
    if (!window.flatpickr) {
        return;
    }

    const inputs = [];

    if (root.matches?.("[data-ui-picker]")) {
        inputs.push(root);
    }

    root.querySelectorAll?.("[data-ui-picker]").forEach((input) => {
        inputs.push(input);
    });

    inputs.forEach((input) => {
        const mode = input.dataset.uiPicker;
        const staticPicker = input.dataset.uiPickerStatic === "true";

        if (input._flatpickr) {
            if (isUiPickerDetached(input._flatpickr)) {
                input._flatpickr.destroy();
            } else {
                syncUiPickerValue(input._flatpickr, input);
                syncUiPickerWidth(input._flatpickr, input);
                input.setAttribute("data-ui-picker-ready", "true");
                return;
            }
        }

        if (input._flatpickr) {
            input.setAttribute("data-ui-picker-ready", "true");
            return;
        }

        input.setAttribute("data-ui-picker-ready", "true");

        const config = {
            allowInput: true,
            disableMobile: true,
            static: staticPicker,
            onChange: () => {
                input.dispatchEvent(new Event("input", { bubbles: true }));
                input.dispatchEvent(new Event("change", { bubbles: true }));
            },
            onValueUpdate: () => {
                input.dispatchEvent(new Event("input", { bubbles: true }));
            },
            onReady: (_selectedDates, _dateStr, instance) => {
                syncUiPickerValue(instance, input);
                syncUiPickerWidth(instance, input);
            },
            onOpen: (_selectedDates, _dateStr, instance) => {
                syncUiPickerValue(instance, input);
                syncUiPickerWidth(instance, input);
            },
        };

        const defaultDate = uiPickerValue(input);

        if (defaultDate) {
            config.defaultDate = defaultDate;
        }

        if (!staticPicker) {
            config.appendTo = input.closest("[role='dialog']")
                || input.closest(".jetstream-modal")
                || document.body;
        }

        if (input.min) {
            config.minDate = input.min;
        }

        if (input.max) {
            config.maxDate = input.max;
        }

        if (mode === "time") {
            Object.assign(config, {
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",
                time_24hr: true,
            });
        } else if (mode === "datetime") {
            Object.assign(config, {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                altInput: true,
                altFormat: "d M Y H:i",
                time_24hr: true,
            });
        } else {
            Object.assign(config, {
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d M Y",
            });
        }

        syncUiPickerWidth(window.flatpickr(input, config), input);
    });
};

const scheduleUiPickerInit = (root = document) => {
    window.requestAnimationFrame(() => initUiPickers(root));
};

let uiPickerMountObserver = null;

const watchUiPickerMounts = () => {
    initUiPickers();

    if (!window.MutationObserver || uiPickerMountObserver) {
        return;
    }

    uiPickerMountObserver = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    initUiPickers(node);
                }
            });
        }
    });

    uiPickerMountObserver.observe(document.body, {
        childList: true,
        subtree: true,
    });
};

["click", "focusin"].forEach((eventName) => {
    document.addEventListener(eventName, (event) => {
        const input = event.target?.closest?.("[data-ui-picker]");

        if (input && !input._flatpickr) {
            initUiPickers(input);
        }
    });
});

window.profilePhotoEditor = (config = {}) => ({
    currentPhotoUrl: config.initialPhotoUrl || null,
    defaultFileName: config.defaultFileName || "profile-photo.jpg",
    messages: {
        invalidFile: "Please choose a valid image file.",
        uploadFailed: "The photo could not be uploaded. Please try again.",
        processFailed: "The photo could not be processed. Please try another image.",
        ...(config.messages || {}),
    },
    cropModalOpen: false,
    uploadError: null,
    uploading: false,
    fileName: null,
    image: null,
    baseScale: 1,
    zoom: 1,
    offsetX: 0,
    offsetY: 0,
    dragging: false,
    dragPointerId: null,
    dragStartX: 0,
    dragStartY: 0,
    dragOriginX: 0,
    dragOriginY: 0,

    handleFileChange(event) {
        const [file] = event.target.files || [];

        if (!file) {
            return;
        }

        if (!file.type.startsWith("image/")) {
            this.uploadError = this.messages.invalidFile;
            this.$refs.photo.value = "";
            return;
        }

        this.fileName = file.name || this.defaultFileName;

        const reader = new FileReader();
        reader.onload = () => {
            const image = new Image();

            image.onload = () => {
                this.image = image;
                this.cropModalOpen = true;

                this.$nextTick(() => {
                    this.resetCropState();
                    this.renderCropCanvas();
                });
            };

            image.onerror = () => {
                this.uploadError = this.messages.processFailed;
                this.$refs.photo.value = "";
            };

            image.src = reader.result;
        };
        reader.onerror = () => {
            this.uploadError = this.messages.processFailed;
            this.$refs.photo.value = "";
        };
        reader.readAsDataURL(file);
    },

    resetCropState() {
        if (!this.image || !this.$refs.cropCanvas) {
            return;
        }

        const canvas = this.$refs.cropCanvas;
        this.baseScale = Math.max(canvas.width / this.image.width, canvas.height / this.image.height);
        this.zoom = 1.1;
        this.offsetX = 0;
        this.offsetY = 0;
        this.dragging = false;
    },

    closeCropModal() {
        if (this.uploading) {
            return;
        }

        this.cropModalOpen = false;
        this.dragging = false;
        this.$refs.photo.value = "";
    },

    startDrag(event) {
        if (!this.image) {
            return;
        }

        this.dragging = true;
        this.dragPointerId = event.pointerId;
        this.dragStartX = event.clientX;
        this.dragStartY = event.clientY;
        this.dragOriginX = this.offsetX;
        this.dragOriginY = this.offsetY;
        event.target.setPointerCapture?.(event.pointerId);
    },

    onDrag(event) {
        if (!this.dragging || this.dragPointerId !== event.pointerId) {
            return;
        }

        this.offsetX = this.dragOriginX + (event.clientX - this.dragStartX);
        this.offsetY = this.dragOriginY + (event.clientY - this.dragStartY);
        this.renderCropCanvas();
    },

    stopDrag() {
        this.dragging = false;
        this.dragPointerId = null;
    },

    clampOffsets() {
        if (!this.image || !this.$refs.cropCanvas) {
            return;
        }

        const canvas = this.$refs.cropCanvas;
        const scale = this.baseScale * this.zoom;
        const renderedWidth = this.image.width * scale;
        const renderedHeight = this.image.height * scale;
        const maxOffsetX = Math.max(0, (renderedWidth - canvas.width) / 2);
        const maxOffsetY = Math.max(0, (renderedHeight - canvas.height) / 2);

        this.offsetX = Math.min(maxOffsetX, Math.max(-maxOffsetX, this.offsetX));
        this.offsetY = Math.min(maxOffsetY, Math.max(-maxOffsetY, this.offsetY));
    },

    renderCropCanvas() {
        if (!this.image || !this.$refs.cropCanvas) {
            return;
        }

        this.clampOffsets();

        const canvas = this.$refs.cropCanvas;
        const context = canvas.getContext("2d");
        const scale = this.baseScale * this.zoom;
        const renderedWidth = this.image.width * scale;
        const renderedHeight = this.image.height * scale;
        const x = (canvas.width - renderedWidth) / 2 + this.offsetX;
        const y = (canvas.height - renderedHeight) / 2 + this.offsetY;

        context.clearRect(0, 0, canvas.width, canvas.height);
        context.fillStyle = "#f4f8f2";
        context.fillRect(0, 0, canvas.width, canvas.height);
        context.imageSmoothingQuality = "high";
        context.drawImage(this.image, x, y, renderedWidth, renderedHeight);
    },

    getCroppedBlob() {
        return new Promise((resolve, reject) => {
            const canvas = this.$refs.cropCanvas;

            if (!canvas) {
                reject(new Error("Canvas unavailable"));
                return;
            }

            canvas.toBlob((blob) => {
                if (blob) {
                    resolve(blob);
                    return;
                }

                reject(new Error("Unable to crop image"));
            }, "image/jpeg", 0.92);
        });
    },

    async saveCroppedPhoto() {
        if (!this.image || this.uploading) {
            return;
        }

        this.uploading = true;
        this.uploadError = null;

        try {
            const blob = await this.getCroppedBlob();
            const file = new File([blob], this.fileName || this.defaultFileName, {
                type: "image/jpeg",
            });

            this.$wire.upload("photo", file, () => {
                this.currentPhotoUrl = URL.createObjectURL(file);
                this.cropModalOpen = false;
                this.$refs.photo.value = "";
                this.uploading = false;
                this.$wire.updateProfileInformation();
            }, () => {
                this.uploadError = this.messages.uploadFailed;
                this.uploading = false;
            });
        } catch (error) {
            this.uploadError = this.messages.processFailed;
            this.uploading = false;
        }
    },
});

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

const OFFLINE_PAGE_PATH = "/offline.html";
const OFFLINE_RETURN_KEY = "app:offline:return-url";

const isOfflinePagePath = (pathname = window.location.pathname) =>
    pathname === OFFLINE_PAGE_PATH || pathname === "/offline";

const getCurrentRelativeUrl = () =>
    `${window.location.pathname}${window.location.search}${window.location.hash}`;

const normalizeRelativeUrl = (target) => {
    try {
        const url = new URL(target, window.location.origin);
        return `${url.pathname}${url.search}${url.hash}`;
    } catch (error) {
        return "/home";
    }
};

const buildOfflineRedirectUrl = (returnTo) => {
    const url = new URL(OFFLINE_PAGE_PATH, window.location.origin);

    if (returnTo) {
        url.searchParams.set("returnTo", normalizeRelativeUrl(returnTo));
    }

    return url.toString();
};

const storeOfflineReturnUrl = (target) => {
    const normalizedTarget = normalizeRelativeUrl(target);

    if (isOfflinePagePath(new URL(normalizedTarget, window.location.origin).pathname)) {
        return;
    }

    sessionStorage.setItem(OFFLINE_RETURN_KEY, normalizedTarget);
};

const redirectToOfflinePage = ({ force = false, returnTo = null } = {}) => {
    if (isOfflinePagePath()) {
        return;
    }

    if (!force && navigator.onLine) {
        return;
    }

    const target = returnTo || getCurrentRelativeUrl();
    storeOfflineReturnUrl(target);
    window.location.replace(buildOfflineRedirectUrl(target));
};

const restoreFromOfflinePage = () => {
    if (!isOfflinePagePath() || !navigator.onLine) {
        return;
    }

    const searchParams = new URLSearchParams(window.location.search);
    const returnTo =
        searchParams.get("returnTo") ||
        sessionStorage.getItem(OFFLINE_RETURN_KEY) ||
        "/home";

    sessionStorage.removeItem(OFFLINE_RETURN_KEY);
    window.location.replace(normalizeRelativeUrl(returnTo));
};

window.AppOffline = {
    redirectToOfflinePage,
    restoreFromOfflinePage,
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

    scheduleNativeSystemBarSync();
    scheduleUiPickerInit();
});

document.addEventListener("livewire:init", () => {
    if (!window.Livewire?.hook) {
        return;
    }

    window.Livewire.hook("morph.added", ({ el }) => {
        if (el instanceof Element) {
            scheduleUiPickerInit(el);
        }
    });

    window.Livewire.hook("morph.updated", ({ el }) => {
        if (el instanceof Element && el.querySelector?.("[data-ui-picker]")) {
            scheduleUiPickerInit(el);
        }
    });
});

document.addEventListener("DOMContentLoaded", () => {
    if (window.isNativeApp?.()) {
        document.body?.classList.add("is-native-platform");
        document.body?.classList.add(`platform-${Capacitor.getPlatform()}`);
    }

    scheduleNativeSystemBarSync();
    watchNativeSystemBarTriggers();
    watchUiPickerMounts();

    if (!navigator.onLine) {
        redirectToOfflinePage({ force: true });
        return;
    }

    restoreFromOfflinePage();
});

window.addEventListener("offline", () => {
    redirectToOfflinePage({ force: true });
});

window.addEventListener("online", () => {
    restoreFromOfflinePage();
});

window.addEventListener("pageshow", () => {
    scheduleNativeSystemBarSync();
});

document.addEventListener("click", (event) => {
    if (navigator.onLine || event.defaultPrevented) {
        return;
    }

    const link = event.target.closest("a[href]");

    if (!link || link.target === "_blank" || link.hasAttribute("download")) {
        return;
    }

    const nextUrl = new URL(link.href, window.location.origin);

    if (nextUrl.origin !== window.location.origin || isOfflinePagePath(nextUrl.pathname)) {
        return;
    }

    event.preventDefault();
    redirectToOfflinePage({
        force: true,
        returnTo: `${nextUrl.pathname}${nextUrl.search}${nextUrl.hash}`,
    });
});

document.addEventListener("submit", (event) => {
    if (navigator.onLine || event.defaultPrevented) {
        return;
    }

    const form = event.target;

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    event.preventDefault();
    redirectToOfflinePage({ force: true });
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
    const hasRefreshUi = () =>
        !!(refreshContainer && spinner && refreshContainer.style && spinner.style);
    const isPullRefreshDisabled = () =>
        document.body?.classList.contains("scan-attendance-route") ||
        document.body?.classList.contains("is-native-scanning") ||
        Boolean(document.querySelector(".scan-attendance-page"));
    
    // We don't strictly need 'main' for Overlay style if we aren't moving it
    // But we might want to check if it exists purely for safety
    if (!hasRefreshUi()) return;

    let isLoading = false;
    let pStartY = 0;
    
    // Threshold to trigger refresh
    const THRESHOLD = 80;

    const loadInit = () => {
        if (!hasRefreshUi() || isPullRefreshDisabled()) return;
        refreshContainer.classList.add("load-init");
        isLoading = true;
    };

    const swipeStart = (e) => {
        if (isLoading || isPullRefreshDisabled()) {
            pStartY = 0;
            return;
        }
        
        // Only track if we are at the top (or very close)
        // Using 2px tolerance for safe measure
        if (window.scrollY > 2) return;

        pStartY = e.touches[0].screenY;
        if (!hasRefreshUi()) return;
        
        // Remove transitions during drag
        refreshContainer.style.transition = 'none';
        spinner.style.transition = 'none';
    };

    const swipe = (e) => {
        if (isPullRefreshDisabled()) {
            pStartY = 0;
            resetLayout();
            return;
        }

        if (isLoading || pStartY === 0) return;

        const touch = e.touches[0];
        const currentY = touch.screenY;
        const diff = currentY - pStartY;

        // Only handle Pull Down when at Top
        if (diff > 0 && window.scrollY <= 2) {
            if (!hasRefreshUi()) return;
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
        if (isPullRefreshDisabled()) {
            pStartY = 0;
            resetLayout();
            return;
        }

        if (isLoading || pStartY === 0) {
            pStartY = 0; // Reset
            return;
        }

        const touch = e.changedTouches[0];
        const currentY = touch.screenY;
        const diff = currentY - pStartY;
        const pullDistance = diff * 0.5;
        if (!hasRefreshUi()) {
            pStartY = 0;
            return;
        }

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
        if (!hasRefreshUi()) return;
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

    if (!navigator.onLine) {
        redirectToOfflinePage({ force: true });
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
