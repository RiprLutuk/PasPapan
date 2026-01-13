import axios from "axios";
import CapacitorDeviceManager from "./CapacitorDeviceManager";
import { getCurrentLocation } from "./services/location.service";
import { startNativeBarcodeScanner, switchNativeCamera, stopNativeBarcodeScanner } from "./services/native/barcode";

window.axios = axios;
window.startNativeBarcodeScanner = startNativeBarcodeScanner;
window.switchNativeCamera = switchNativeCamera;
window.stopNativeBarcodeScanner = stopNativeBarcodeScanner;
window.deviceManager = new CapacitorDeviceManager();
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
