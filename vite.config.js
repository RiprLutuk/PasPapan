import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

function resolveVendorChunk(id) {
    if (!id.includes('node_modules')) {
        return undefined;
    }

    if (id.includes('/leaflet/') || id.includes('/leaflet.markercluster/')) {
        return 'vendor-maps';
    }

    if (id.includes('/chart.js/')) {
        return 'vendor-charts';
    }

    if (
        id.includes('/sweetalert2/') ||
        id.includes('/tom-select/') ||
        id.includes('/@orchidjs/')
    ) {
        return 'vendor-ui';
    }

    if (
        id.includes('/@capacitor-community/barcode-scanner/') ||
        id.includes('/@zxing/')
    ) {
        return 'vendor-scanner';
    }

    if (id.includes('/@dewakoding/')) {
        return 'vendor-native-optional';
    }

    if (
        id.includes('/@capacitor/') ||
        id.includes('/@capacitor-community/')
    ) {
        return 'vendor-native';
    }

    return 'vendor-core';
}

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    css: {
        devSourcemap: true,
    },
    server: {
        host: '127.0.0.1',
        cors: true,
        hmr: {
            host: '127.0.0.1',
        },
    },
    build: {
        chunkSizeWarningLimit: 1000,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    return resolveVendorChunk(id);
                }
            },
        },
    },
});
