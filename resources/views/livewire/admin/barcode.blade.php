<div class="p-0 lg:p-0">
    <script src="{{ url('/assets/js/qrcode.min.js') }}"></script>
    <x-admin.page-tools class="mb-4">
        <x-slot name="summary">
            <div class="rounded-xl bg-slate-100 px-3 py-2 text-sm text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                {{ trans_choice(':count barcode|:count barcodes', $barcodes->count(), ['count' => $barcodes->count()]) }}
            </div>
        </x-slot>

        <div class="md:col-span-2 xl:col-span-8">
            <x-forms.label for="barcode-search" value="{{ __('Search barcodes') }}" class="mb-1.5 block" />
            <div class="relative">
                <span
                    class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 dark:text-gray-500">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M9 3.5a5.5 5.5 0 1 0 3.472 9.766l3.63 3.63a.75.75 0 1 0 1.06-1.06l-3.63-3.63A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0a4 4 0 0 1-8 0Z"
                            clip-rule="evenodd" />
                    </svg>
                </span>
                <x-forms.input id="barcode-search" type="search" wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Search name, coordinates, or QR value...') }}" class="w-full pl-11" />
            </div>
        </div>

        <div class="xl:col-span-4">
            <x-forms.label for="barcode-mode-filter" value="{{ __('QR Mode') }}" class="mb-1.5 block" />
            <x-forms.select id="barcode-mode-filter" wire:model.live="modeFilter" class="w-full">
                <option value="all">{{ __('All barcode modes') }}</option>
                <option value="static">{{ __('Static QR') }}</option>
                <option value="dynamic">{{ __('Dynamic QR') }}</option>
            </x-forms.select>
        </div>

        <x-slot name="actions">
            <x-actions.button href="{{ route('admin.barcodes.create') }}">
                {{ __('Create New Barcode') }}
            </x-actions.button>
            <x-actions.secondary-button href="{{ route('admin.barcodes.downloadall') }}">
                {{ __('Download All') }}
            </x-actions.secondary-button>
        </x-slot>
    </x-admin.page-tools>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @foreach ($barcodes as $barcode)
            <div
                class="flex flex-col rounded-xl bg-white dark:bg-gray-800 shadow-sm hover:shadow-md transition-shadow duration-200 border border-gray-100 dark:border-gray-700 overflow-hidden">
                <!-- Header -->
                <div class="px-4 pt-4 pb-2">
                    <div class="flex items-start justify-between gap-3">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white truncate"
                            title="{{ $barcode->name }}">
                            {{ $barcode->name }}
                        </h3>
                        @if ($barcode->dynamic_enabled)
                            <x-admin.status-badge tone="warning">{{ __('Dynamic') }}</x-admin.status-badge>
                        @else
                            <x-admin.status-badge tone="neutral">{{ __('Static') }}</x-admin.status-badge>
                        @endif
                    </div>
                </div>

                <!-- QR Code Body -->
                <div
                    class="flex-1 flex flex-col items-center justify-center p-2 bg-white dark:bg-gray-800 relative group">
                    @if ($barcode->dynamic_enabled)
                        <div
                            class="flex min-h-[176px] w-full flex-col items-center justify-center rounded-lg border border-dashed border-amber-300 bg-amber-50 px-4 py-6 text-center dark:border-amber-800 dark:bg-amber-950/20">
                            <x-heroicon-o-arrow-path class="h-10 w-10 text-amber-500" />
                            <p class="mt-3 text-sm font-semibold text-amber-700 dark:text-amber-300">
                                {{ __('Dynamic QR Active') }}</p>
                            <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                                {{ __('Use the live display page to show the rotating QR token.') }}
                            </p>
                        </div>
                    @else
                        <div id="qrcode{{ $barcode->id }}"
                            class="p-2 bg-white rounded-lg shadow-sm border border-gray-100"></div>
                    @endif
                </div>

                <!-- Info Section -->
                <div class="px-4 pb-2 text-sm space-y-2">
                    <div class="flex items-start gap-2 text-gray-600 dark:text-gray-400">
                        <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                            </path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <a href="#"
                            onclick="window.openMap({{ $barcode->latitude }}, {{ $barcode->longitude }}); return false;"
                            aria-label="{{ __('Open map for barcode') }}: {{ $barcode->name }}"
                            class="rounded hover:text-blue-600 hover:underline focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 dark:focus:ring-offset-gray-800 truncate">
                            {{ $barcode->latitude }}, {{ $barcode->longitude }}
                        </a>
                    </div>
                    <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 21v-8a2 2 0 012-2h14a2 2 0 012 2v8M12 3v16M8 8V6a2 2 0 114 0h0"></path>
                        </svg>
                        <span>{{ __('Radius') }}: <span
                                class="font-medium text-gray-900 dark:text-gray-200">{{ $barcode->radius }}m</span></span>
                    </div>
                    @if ($barcode->dynamic_enabled)
                        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0a9 9 0 0118 0z"></path>
                            </svg>
                            <span>{{ __('TTL') }}: <span
                                    class="font-medium text-gray-900 dark:text-gray-200">{{ $barcode->dynamic_ttl_seconds }}s</span></span>
                        </div>
                    @endif
                </div>

                <!-- Actions Footer -->
                <div class="px-2 pb-2 pt-4 grid grid-cols-3 gap-3">
                    @if ($barcode->dynamic_enabled)
                        <x-actions.icon-button href="{{ route('admin.barcodes.dynamic-display', $barcode) }}"
                            label="{{ __('Show dynamic barcode') }}: {{ $barcode->name }}" variant="warning"
                            class="h-auto w-full rounded-lg py-2">
                            <x-heroicon-o-eye class="w-4 h-4" />
                            <span class="sr-only">{{ __('Display') }}</span>
                        </x-actions.icon-button>
                    @else
                        <x-actions.icon-button href="{{ route('admin.barcodes.download', $barcode->id) }}"
                            label="{{ __('Download barcode') }}: {{ $barcode->name }}" variant="primary"
                            class="h-auto w-full rounded-lg py-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            <span class="sr-only">{{ __('Download') }}</span>
                        </x-actions.icon-button>
                    @endif
                    <x-actions.icon-button href="{{ route('admin.barcodes.edit', $barcode->id) }}"
                        label="{{ __('Edit barcode') }}: {{ $barcode->name }}" variant="warning"
                        class="h-auto w-full rounded-lg py-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                            </path>
                        </svg>
                        <span class="sr-only">{{ __('Edit') }}</span>
                    </x-actions.icon-button>
                    <x-actions.icon-button type="button"
                        wire:click="confirmDeletion({{ $barcode->id }}, @js($barcode->name))"
                        label="{{ __('Delete barcode') }}: {{ $barcode->name }}" variant="danger"
                        class="h-auto w-full rounded-lg py-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                            </path>
                        </svg>
                        <span class="sr-only">{{ __('Delete') }}</span>
                    </x-actions.icon-button>
                </div>
            </div>
        @endforeach
    </div>

    <x-overlays.confirmation-modal wire:model="confirmingDeletion">
        <x-slot name="title">
            {{ __('Delete Barcode') }}
        </x-slot>

        <x-slot name="content">
            {{ __('Are you sure you want to delete') }} <b>{{ $deleteName }}</b>?
        </x-slot>

        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$toggle('confirmingDeletion')" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-actions.secondary-button>

            <x-actions.danger-button class="ml-2" wire:click="delete" wire:loading.attr="disabled">
                {{ __('Confirm') }}
            </x-actions.danger-button>
        </x-slot>
    </x-overlays.confirmation-modal>
</div>

@script
    <script type="text/javascript">
        let barcodes = @json($barcodes->where('dynamic_enabled', false)->map(fn($b) => ['id' => $b->id, 'val' => $b->value])->values());

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
