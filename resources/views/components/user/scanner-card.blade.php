<div class="p-4 sm:p-6 relative overflow-visible bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700" id="scanner-card" wire:ignore>
    
    {{-- Decorative Background Blob --}}
    <div class="absolute top-0 right-0 -mt-10 -mr-10 w-32 h-32 bg-primary-50 dark:bg-primary-900/20 rounded-full blur-3xl opacity-50 pointer-events-none"></div>

    <div class="scanner-header flex flex-col gap-4 mb-3 relative z-[60]">
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

    <!-- On-screen Debug Log for Camera Issues -->
    <div class="hidden mb-3 w-full max-w-sm mx-auto bg-gray-900 border border-red-500/50 rounded-lg p-2 overflow-y-auto max-h-32 shadow-inner">
        <div class="text-[10px] text-gray-400 font-mono flex items-center justify-between mb-1 border-b border-gray-700 pb-1">
            <span>Camera Debug Log</span>
            <button onclick="this.parentElement.parentElement.classList.add('hidden')" class="text-gray-500 hover:text-white">✕</button>
        </div>
        <div id="debug-log" class="text-[10px] font-mono text-green-400 space-y-0.5 whitespace-pre-wrap word-break"></div>
    </div>

    <div class="scanner-container w-full max-w-sm mx-auto aspect-square rounded-2xl bg-gray-100 dark:bg-gray-900
                cursor-pointer flex items-center justify-center overflow-hidden relative group"
        id="scanner" onclick="handleScanClick()">
        
        <!-- Custom Overlay (Visible when scanning) -->
        <div id="scanner-overlay" class="absolute inset-0 z-10 pointer-events-none hidden">
            <!-- Scan Line Animation -->
            <div class="absolute inset-x-4 h-0.5 bg-blue-500/80 shadow-[0_0_15px_rgba(59,130,246,0.8)] z-20 animate-scan-line"></div>
            
            <!-- Standard QR Box Border (For Both Web & Native) -->
            <div class="absolute inset-4 border-2 border-white/50 rounded-xl"></div>
            
            <!-- Corner Accents -->
            <div class="absolute top-4 left-4 w-6 h-6 border-l-4 border-t-4 border-blue-500 rounded-tl-xl"></div>
            <div class="absolute top-4 right-4 w-6 h-6 border-r-4 border-t-4 border-blue-500 rounded-tr-xl"></div>
            <div class="absolute bottom-4 left-4 w-6 h-6 border-l-4 border-b-4 border-blue-500 rounded-bl-xl"></div>
            <div class="absolute bottom-4 right-4 w-6 h-6 border-r-4 border-b-4 border-blue-500 rounded-br-xl"></div>
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
        <div class="scanner-footer mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
            {{ $slot }}
        </div>
    @endif
</div>
