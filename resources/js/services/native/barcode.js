import { BarcodeScanner } from "@capacitor-community/barcode-scanner";

let isScanning = false;
let currentFacingMode = 'environment'; // environment (back) or user (front)

export async function startNativeBarcodeScanner(onScanSuccess, facingMode = null) {
    if (isScanning) return;
    isScanning = true;
    
    if (facingMode) currentFacingMode = facingMode;

    const perm = await BarcodeScanner.checkPermission({ force: true });

    // Check if user denied permission forever
    if (perm.denied) {
        alert("Camera permission denied. Please enable in settings.");
        isScanning = false;
        return;
    }

    if (!perm.granted) {
        alert("Camera permission is required");
        isScanning = false;
        return;
    }

    document.body.classList.add('is-native-scanning');
    
    // Ensure Webview is transparent
    BarcodeScanner.hideBackground();
    
    // Show Overlay (Brackets & Line)
    if (window.setShowOverlay) window.setShowOverlay(true);

    try {
        // Prepare options
        // Note: Community plugin usually uses 'cameraDirection' param in startScan options
        // cameraDirection: 0 (BACK), 1 (FRONT) for some plugins, or string 'BACK'/'FRONT'
        // For @capacitor-community/barcode-scanner, explicit direction often needs stop/start
        
        // However, startScan() options might not directly support direction in all versions.
        // Usually, we just start. If we need to switch, we stop and restart with DIFFERENT config if supported,
        // or effectively the community plugin doesn't support 'user' facing mode easily in startScan without quirks.
        // Let's assume standard behavior:
        
        const result = await BarcodeScanner.startScan({ 
             cameraDirection: currentFacingMode === 'user' ? 1 : 0 
        });

        if (result?.hasContent) {
            await onScanSuccess(result.content);
        }
    } catch (e) {
        console.error("Scanner failed", e);
    } finally {
        // Hide Overlay
        if (window.setShowOverlay) window.setShowOverlay(false);
        
        BarcodeScanner.showBackground();
        document.body.classList.remove('is-native-scanning');
        BarcodeScanner.stopScan();
        isScanning = false;
    }
}

export async function stopNativeBarcodeScanner() {
    BarcodeScanner.showBackground();
    document.body.classList.remove('is-native-scanning');
    await BarcodeScanner.stopScan();
    isScanning = false;
}

export async function switchNativeCamera(onScanSuccess) {
    await stopNativeBarcodeScanner();
    // Toggle
    currentFacingMode = currentFacingMode === 'environment' ? 'user' : 'environment';
    // Restart with new mode
    // We need to wait a small bit for cleanup?
    setTimeout(() => {
        startNativeBarcodeScanner(onScanSuccess, currentFacingMode);
    }, 300);
}
