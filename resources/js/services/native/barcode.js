import {
    BarcodeScanner,
    CameraDirection,
    SupportedFormat,
} from "@capacitor-community/barcode-scanner";

let isScanning = false;
let currentFacingMode = 'environment';
let isSwitching = false; // Flag to suppress error alerts during intentional stop

function scanOptions() {
    return {
        cameraDirection: currentFacingMode === 'user' ? CameraDirection.FRONT : CameraDirection.BACK,
        targetedFormats: [SupportedFormat.QR_CODE],
    };
}

export async function startNativeBarcodeScanner(onScanSuccess, facingMode = null) {
    if (isScanning) return;
    isScanning = true;
    
    if (facingMode) currentFacingMode = facingMode;

    try {
        const perm = await BarcodeScanner.checkPermission({ force: true });

        if (perm.denied) {
            console.error("Camera permission denied");
            if (!isSwitching) {
                window.PasPapanAlert?.modal({
                    icon: "warning",
                    title: "Camera permission denied",
                    text: "Please enable camera access in settings.",
                });
            }
            isScanning = false;
            return;
        }

        if (!perm.granted) {
            console.error("Camera permission not granted");
            if (!isSwitching) {
                window.PasPapanAlert?.modal({
                    icon: "warning",
                    title: "Camera permission is required",
                    text: "Please allow camera access to scan attendance QR codes.",
                });
            }
            isScanning = false;
            return;
        }

        document.body.classList.add('is-native-scanning');
        document.documentElement.classList.add('is-native-scanning');

        try { await BarcodeScanner.prepare(scanOptions()); } catch(e){}
        await BarcodeScanner.hideBackground();

        if (window.setShowOverlay) window.setShowOverlay(true);

        const result = await BarcodeScanner.startScan(scanOptions());

        if (result?.hasContent) {
            await onScanSuccess(result.content);
        }
    } catch (e) {
        // Only show error if this is NOT an intentional stop (e.g. during camera switch)
        if (!isSwitching) {
            console.error("Scanner failed", e);
            // Use non-blocking console error instead of blocking alert
            window.PasPapanAlert?.modal({
                icon: "error",
                title: "Scanner Error",
                text: e.message || String(e),
            });
        }
    } finally {
        // Only do cleanup if we're NOT in the middle of a switch
        // (during switch, the new scan will handle UI state)
        if (!isSwitching) {
            if (window.setShowOverlay) window.setShowOverlay(false);
            
            BarcodeScanner.showBackground();
            document.body.classList.remove('is-native-scanning');
            document.documentElement.classList.remove('is-native-scanning');
            
            try { await BarcodeScanner.stopScan(); } catch(e){}
        }
        isScanning = false;
    }
}

export async function stopNativeBarcodeScanner() {
    BarcodeScanner.showBackground();
    document.body.classList.remove('is-native-scanning');
    document.documentElement.classList.remove('is-native-scanning');
    try { await BarcodeScanner.stopScan(); } catch(e){}
    isScanning = false;
}

export async function switchNativeCamera(onScanSuccess) {
    if (!isScanning) return; // Can't switch if not scanning

    // Set flag to suppress error alerts and cleanup during the stop
    isSwitching = true;

    try {
        // Stop the old scanner completely
        BarcodeScanner.showBackground();
        document.body.classList.remove('is-native-scanning');
        document.documentElement.classList.remove('is-native-scanning');
        
        try { await BarcodeScanner.stopScan(); } catch(e){}
        
        // Toggle camera direction
        currentFacingMode = currentFacingMode === 'environment' ? 'user' : 'environment';

        // CRITICAL: Wait for camera hardware release on Android
        await new Promise(resolve => setTimeout(resolve, 800));
        
        // Clear the switching flag before starting so the new scan manages UI properly
        isSwitching = false;
        isScanning = false; // Reset scanning state so startNativeBarcodeScanner allows entry
        
        // Start the new scanner with the switched camera
        await startNativeBarcodeScanner(onScanSuccess, currentFacingMode);
    } catch(e) {
        console.error('[NATIVE CAM] Switch native camera failed:', e);
        isSwitching = false;
        
        window.PasPapanAlert?.modal({
            icon: "error",
            title: "Switch Failed",
            text: "Could not switch native camera: " + (e.message || String(e)),
        });
    }
}
