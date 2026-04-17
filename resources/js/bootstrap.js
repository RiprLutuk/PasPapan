import axios from "axios";
import CapacitorDeviceManager from "./CapacitorDeviceManager";

window.axios = axios;
window.deviceManager = new CapacitorDeviceManager();
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
