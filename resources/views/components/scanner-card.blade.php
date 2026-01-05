<div class="rounded-lg border border-gray-200 bg-white p-4 sm:p-6 shadow dark:border-gray-700 dark:bg-gray-800" id="scanner-card" wire:ignore>

    <div class="flex justify-between items-center mb-4">
        <h3 class="text-sm font-semibold text-gray-800 dark:text-white">
            {{ $title }}
        </h3>
        <button type="button" id="switch-camera-btn" onclick="window.switchCamera?.()" class="text-xs flex items-center gap-1 text-blue-600 dark:text-blue-400 hover:underline">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Switch Camera
        </button>
    </div>

    <div class="scanner-container w-full max-w-sm mx-auto aspect-square rounded-2xl bg-gray-100 dark:bg-gray-900
                cursor-pointer flex items-center justify-center overflow-hidden relative group"
        id="scanner" onclick="handleScanClick()">
        
        <!-- Custom Overlay (Visible when scanning) -->
        <div id="scanner-overlay" class="absolute inset-0 z-10 pointer-events-none hidden">
            <!-- Scan Line Animation -->
            <div class="absolute inset-x-4 h-0.5 bg-red-500/80 shadow-[0_0_15px_rgba(239,68,68,0.8)] z-20 animate-scan-line"></div>
            
            <!-- Corners (Brackets) -->
            <div class="absolute top-6 left-6 w-12 h-12 border-l-4 border-t-4 border-gray-300/80 rounded-tl-xl"></div>
            <div class="absolute top-6 right-6 w-12 h-12 border-r-4 border-t-4 border-gray-300/80 rounded-tr-xl"></div>
            <div class="absolute bottom-6 left-6 w-12 h-12 border-l-4 border-b-4 border-gray-300/80 rounded-bl-xl"></div>
            <div class="absolute bottom-6 right-6 w-12 h-12 border-r-4 border-b-4 border-gray-300/80 rounded-br-xl"></div>
            
            <!-- Pulse Effect Center (Subtle Target) -->
            <div class="absolute inset-0 flex items-center justify-center">
                 <div class="w-48 h-48 border border-white/10 rounded-xl"></div>
            </div>
        </div>

        <span id="scanner-placeholder" class="text-gray-600 dark:text-gray-300 z-0">
            Tap to scan
        </span>
    </div>

    <div id="scanner-result" class="hidden mt-3 text-green-600 dark:text-green-400 font-medium text-center text-sm">
    </div>

    <div id="scanner-error" class="hidden mt-3 text-red-600 dark:text-red-400 font-medium text-center text-sm">
    </div>

    <style>
        /* Force Video Clean Look */
        #scanner video {
            object-fit: cover !important;
            border-radius: 1rem !important;
            width: 100% !important;
            height: 100% !important;
        }
        
        #scanner.mirrored video {
            transform: scaleX(-1) !important;
        }

        /* Animation Keyframes */
        @keyframes scan-line {
            0% { top: 0%; opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { top: 100%; opacity: 0; }
        }

        .animate-scan-line {
            animation: scan-line 2s linear infinite;
        }
        
        /* Hide Default Library Elements if any leak through */
        #html5-qrcode-anchor-scan-type-change, 
        #html5-qrcode-button-camera-permission,
        #html5-qrcode-select-camera {
             display: none !important;
        }
    </style>
</div>
