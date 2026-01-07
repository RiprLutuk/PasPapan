<div class="p-0 lg:p-0">
    <script src="{{ url('/assets/js/qrcode.min.js') }}"></script>
    <x-button class="mb-4 mr-2" href="{{ route('admin.barcodes.create') }}">
        Buat Barcode Baru
    </x-button>
    <x-secondary-button class="mb-4">
        <a href="{{ route('admin.barcodes.downloadall') }}">Download Semua</a>
    </x-secondary-button>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @foreach ($barcodes as $barcode)
            <div class="flex flex-col rounded-xl bg-white dark:bg-gray-800 shadow-sm hover:shadow-md transition-shadow duration-200 border border-gray-100 dark:border-gray-700 overflow-hidden">
                <!-- Header -->
                <div class="px-4 pt-4 pb-2">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white truncate" title="{{ $barcode->name }}">
                        {{ $barcode->name }}
                    </h3>
                </div>

                <!-- QR Code Body -->
                <div class="flex-1 flex flex-col items-center justify-center p-2 bg-white dark:bg-gray-800 relative group">
                    <div id="qrcode{{ $barcode->id }}" class="p-2 bg-white rounded-lg shadow-sm border border-gray-100"></div>
                </div>

                <!-- Info Section -->
                <div class="px-4 pb-2 text-sm space-y-2">
                    <div class="flex items-start gap-2 text-gray-600 dark:text-gray-400">
                        <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <a href="#" onclick="window.openMap({{ $barcode->latitude }}, {{ $barcode->longitude }}); return false;"
                            class="hover:text-blue-600 hover:underline truncate">
                            {{ $barcode->latitude }}, {{ $barcode->longitude }}
                        </a>
                    </div>
                    <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-8a2 2 0 012-2h14a2 2 0 012 2v8M12 3v16M8 8V6a2 2 0 114 0h0"></path>
                        </svg>
                        <span>{{ __('Radius') }}: <span class="font-medium text-gray-900 dark:text-gray-200">{{ $barcode->radius }}m</span></span>
                    </div>
                </div>

                <!-- Actions Footer -->
                <div class="px-2 pb-2 pt-4 grid grid-cols-3 gap-3">
                    <a href="{{ route('admin.barcodes.download', $barcode->id) }}"
                       class="flex flex-col items-center justify-center py-2 px-2 text-xs font-medium rounded-lg text-blue-700 bg-blue-50 hover:bg-blue-100 dark:text-blue-300 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 transition-colors gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        
                    </a>
                    <a href="{{ route('admin.barcodes.edit', $barcode->id) }}"
                       class="flex flex-col items-center justify-center py-2 px-2 text-xs font-medium rounded-lg text-amber-700 bg-amber-50 hover:bg-amber-100 dark:text-amber-300 dark:bg-amber-900/30 dark:hover:bg-amber-900/50 transition-colors gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        
                    </a>
                    <button wire:click="confirmDeletion({{ $barcode->id }}, '{{ $barcode->name }}')"
                            class="flex flex-col items-center justify-center py-2 px-2 text-xs font-medium rounded-lg text-red-700 bg-red-50 hover:bg-red-100 dark:text-red-300 dark:bg-red-900/30 dark:hover:bg-red-900/50 transition-colors gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <x-confirmation-modal wire:model="confirmingDeletion">
        <x-slot name="title">
            Hapus Barcode
        </x-slot>

        <x-slot name="content">
            Apakah Anda yakin ingin menghapus <b>{{ $deleteName }}</b>?
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('confirmingDeletion')" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ml-2" wire:click="delete" wire:loading.attr="disabled">
                {{ __('Confirm') }}
            </x-danger-button>
        </x-slot>
    </x-confirmation-modal>
</div>

@script
    <script type="text/javascript">
        let barcodes = @json($barcodes->map(fn($b) => ['id' => $b->id, 'val' => $b->value]));

        let isDark = $store.darkMode.on;

        function renderQRs() {
             barcodes.forEach(el => {
                const container = document.getElementById("qrcode" + el.id);
                if (!container) return;
                
                container.innerHTML = "";
                if (typeof QRCode !== 'undefined') {
                    new QRCode(container, {
                        text: el.val,
                        colorDark: $store.darkMode.on ? "#ffffff" : "#000000",
                        colorLight: $store.darkMode.on ? "#000000" : "#ffffff",
                        correctLevel: QRCode.CorrectLevel.M
                    });
                    container.removeAttribute('title'); 
                }
            });
        }

        setTimeout(renderQRs, 300);

        let interval = setInterval(() => {
            if (isDark == $store.darkMode.on) return;
            isDark = $store.darkMode.on;
            renderQRs();
        }, 500);

        return () => {
            clearInterval(interval);
        };
    </script>
@endscript
