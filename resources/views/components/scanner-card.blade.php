<div class="p-4 sm:p-6 relative overflow-visible bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700" id="scanner-card" wire:ignore>
    
    {{-- Decorative Background Blob --}}
    <div class="absolute top-0 right-0 -mt-10 -mr-10 w-32 h-32 bg-primary-50 dark:bg-primary-900/20 rounded-full blur-3xl opacity-50 pointer-events-none"></div>

    <div class="flex flex-col gap-4 mb-4 relative z-10">
        <div class="flex justify-between items-center">
            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <div class="p-1.5 bg-primary-100 text-primary-600 dark:bg-primary-900/50 dark:text-primary-400 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                </div>
                {{ $title }}
            </h3>
            <button type="button" id="switch-camera-btn" onclick="window.switchCamera?.()" class="text-xs font-medium px-3 py-1.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>{{ __('Switch') }}</span>
            </button>
        </div>

        @if(isset($headerActions))
            <div class="w-full">
                {{ $headerActions }}
            </div>
        @endif
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

        <span id="scanner-placeholder" class="text-gray-400 dark:text-gray-500 z-0">
            <svg class="w-16 h-16 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        </span>
    </div>

    <div id="scanner-result" class="hidden mt-3 text-green-600 dark:text-green-400 font-medium text-center text-sm">
    </div>

    <div id="scanner-error" class="hidden mt-3 text-red-600 dark:text-red-400 font-medium text-center text-sm">
    </div>

    @if(isset($slot) && $slot->isNotEmpty())
        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
            {{ $slot }}
        </div>
    @endif

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

        /* Native Scanning Transparency - Double Donut Strategy */
        body.is-native-scanning {
            background-color: transparent !important;
        }

        /* 1. Make Main Wrapper Transparent */
        body.is-native-scanning #scan-wrapper, 
        body.is-native-scanning .min-h-screen,
        body.is-native-scanning main, 
        body.is-native-scanning #app {
            background-color: transparent !important;
            background-image: none !important;
        }

        /* 2. The Card Container */
        body.is-native-scanning #scanner-card {
            background-color: transparent !important;
            border-color: transparent !important;
            /* Clip the inner shadow (Card BG) to the rounded corners */
            overflow: hidden !important; 
            /* Create the Page Background (Outer Ring) */
            box-shadow: 0 0 0 9999px #f1f5f9 !important; /* light: slate-100 */
        }
        :root.dark body.is-native-scanning #scanner-card {
            box-shadow: 0 0 0 9999px #0f172a !important; /* dark: slate-900 */
        }

        /* 3. The Scanner Hole */
        body.is-native-scanning #scanner {
            background-color: transparent !important;
            /* Create the Card Background (Inner Ring) */
            box-shadow: 0 0 0 9999px #ffffff !important; /* light: white */
            border-color: transparent !important;
            /* Ensure it sits below text but above transparency */
            z-index: 0;
            position: relative;
        }
        :root.dark body.is-native-scanning #scanner {
            box-shadow: 0 0 0 9999px #1f2937 !important; /* dark: gray-800 */
        }

        /* 4. Hide Web Video */
        body.is-native-scanning #scanner video {
            display: none !important; 
        }

        /* 5. Ensure UI elements sit on top */
        #scanner-card h3, 
        #scanner-card button,
        #scanner-placeholder,
        #scanner-overlay,
        #scanner-result,
        #scanner-error {
            position: relative;
            z-index: 10;
        }
    </style>
</div>
