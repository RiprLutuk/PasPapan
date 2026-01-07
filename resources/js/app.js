import "./bootstrap";

document.addEventListener('alpine:init', () => {
    Alpine.store("darkMode", {
        on: false,
        init() {
            if (localStorage.getItem("isDark")) {
                this.on = localStorage.getItem("isDark") === "true";
            } else {
                this.on = window.matchMedia("(prefers-color-scheme: dark)").matches;
            }
            
            if (this.on) {
                document.documentElement.classList.add("dark");
            } else {
                document.documentElement.classList.remove("dark");
            }
        },
        toggle() {
            this.on = !this.on;
            localStorage.setItem("isDark", this.on);
            if (this.on) {
                document.documentElement.classList.add("dark");
            } else {
                document.documentElement.classList.remove("dark");
            }
        },
    });
});

document.addEventListener("livewire:navigated", () => {
    if (localStorage.getItem("isDark") === "true") {
        document.documentElement.classList.add("dark");
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

window.isNativeApp = () =>
    !!window.Capacitor && Capacitor.isNativePlatform === true;

window.openMap = (lat, lng) => {
    if (window.isNativeApp()) {
        window.open(`geo:${lat},${lng}?q=${lat},${lng}`, '_system');
    } else {
        window.open(`https://www.google.com/maps?q=${lat},${lng}`, '_blank');
    }
};

/*
(function () {
    if (!window.Capacitor?.isNativePlatform?.()) return;

    const EDGE_WIDTH = 24;
    const MIN_SWIPE_X = 120;
    const MAX_SWIPE_Y = 80;

    let startX = 0;
    let startY = 0;
    let isEdgeSwipe = false;

    document.addEventListener(
        "touchstart",
        (e) => {
            const touch = e.touches[0];
            startX = touch.clientX;
            startY = touch.clientY;
            isEdgeSwipe = startX <= EDGE_WIDTH;
        },
        { passive: true }
    );

    document.addEventListener(
        "touchend",
        (e) => {
            if (!isEdgeSwipe) return;

            const touch = e.changedTouches[0];
            const diffX = touch.clientX - startX;
            const diffY = Math.abs(touch.clientY - startY);

            if (diffX > MIN_SWIPE_X && diffY < MAX_SWIPE_Y) {
                if (window.history.length > 1) {
                    window.history.back();
                }
            }

            isEdgeSwipe = false;
        },
        { passive: true }
    );
})();
*/
