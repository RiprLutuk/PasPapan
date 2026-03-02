import { BarcodeScanner } from "@capacitor-community/barcode-scanner";

let isScanning = false;
let currentFacingMode = 'environment';
let isSwitching = false; // Flag to suppress error alerts during intentional stop

export async function startNativeBarcodeScanner(onScanSuccess, facingMode = null) {
    if (isScanning) return;
    isScanning = true;
    
    if (facingMode) currentFacingMode = facingMode;

    try {
        const perm = await BarcodeScanner.checkPermission({ force: true });

        if (perm.denied) {
            console.error("Camera permission denied");
            if (!isSwitching) {
                alert("Camera permission denied. Please enable in settings.");
            }
            isScanning = false;
            return;
        }

        if (!perm.granted) {
            console.error("Camera permission not granted");
            if (!isSwitching) {
                alert("Camera permission is required");
            }
            isScanning = false;
            return;
        }

        document.body.classList.add('is-native-scanning');
        document.documentElement.classList.add('is-native-scanning');
        
        await BarcodeScanner.hideBackground();
        
        if (window.setShowOverlay) window.setShowOverlay(true);

        const result = await BarcodeScanner.startScan({ 
            cameraDirection: currentFacingMode === 'user' ? 1 : 0 
        });

        if (result?.hasContent) {
            await onScanSuccess(result.content);
        }
    } catch (e) {
        // Only show error if this is NOT an intentional stop (e.g. during camera switch)
        if (!isSwitching) {
            console.error("Scanner failed", e);
            // Use non-blocking console error instead of blocking alert
            if (window.Swal) {
                window.Swal.fire({
                    icon: 'error',
                    title: 'Scanner Error',
                    text: e.message || String(e),
                    confirmButtonColor: '#6366f1'
                });
            }
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
    console.log('[NATIVE CAM] Switching camera direction. Current:', currentFacingMode);
    
    try {
        // Stop the old scanner completely
        BarcodeScanner.showBackground();
        document.body.classList.remove('is-native-scanning');
        document.documentElement.classList.remove('is-native-scanning');
        
        try { await BarcodeScanner.stopScan(); } catch(e){}
        
        // Toggle camera direction
        currentFacingMode = currentFacingMode === 'environment' ? 'user' : 'environment';
        console.log('[NATIVE CAM] New target direction:', currentFacingMode);
        
        // CRITICAL: Wait for camera hardware release on Android
        await new Promise(resolve => setTimeout(resolve, 800));
        
        // Clear the switching flag before starting so the new scan manages UI properly
        isSwitching = false;
        isScanning = false; // Reset scanning state so startNativeBarcodeScanner allows entry
        
        // Start the new scanner with the switched camera
        await startNativeBarcodeScanner(onScanSuccess, currentFacingMode);
        console.log('[NATIVE CAM] Switch complete');
    } catch(e) {
        console.error('[NATIVE CAM] Switch native camera failed:', e);
        isSwitching = false;
        
        if (window.Swal) {
            window.Swal.fire({
                icon: 'error',
                title: 'Switch Failed',
                text: 'Could not switch native camera: ' + (e.message || String(e)),
                confirmButtonColor: '#6366f1'
            });
        }
    }
}
