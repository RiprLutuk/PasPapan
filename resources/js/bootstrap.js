import axios from "axios";
import Echo from "laravel-echo";
import Pusher from "pusher-js";
import CapacitorDeviceManager from "./CapacitorDeviceManager";

window.axios = axios;
window.deviceManager = new CapacitorDeviceManager();
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

const bootEcho = () => {
    const config = window.PasPapanBroadcast || {};

    if (!config.enabled || window.Echo) {
        return;
    }

    const connection = config.connection;
    const driver = config[connection] || {};

    if (!driver.key) {
        return;
    }

    window.Pusher = Pusher;

    const scheme = driver.scheme || (window.location.protocol === "https:" ? "https" : "http");
    const port = Number(driver.port || (scheme === "https" ? 443 : 80));
    const forceTLS = scheme === "https";

    if (connection === "reverb") {
        const options = {
            broadcaster: "reverb",
            key: driver.key,
            wsHost: driver.host || window.location.hostname,
            wsPort: port,
            wssPort: port,
            forceTLS,
            enabledTransports: ["ws", "wss"],
        };

        if (driver.path) {
            options.wsPath = driver.path;
        }

        window.Echo = new Echo(options);

        return;
    }

    if (connection === "pusher") {
        window.Echo = new Echo({
            broadcaster: "pusher",
            key: driver.key,
            cluster: driver.cluster || "mt1",
            wsHost: driver.host || undefined,
            wsPort: port,
            wssPort: port,
            forceTLS,
            enabledTransports: ["ws", "wss"],
        });
    }
};

bootEcho();
