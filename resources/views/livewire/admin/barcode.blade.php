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
                    <x-heroicon-o-magnifying-glass class="h-5 w-5" />
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
                        <x-heroicon-o-map-pin class="mt-0.5 h-4 w-4 shrink-0" />
                        <a href="#"
                            onclick="window.openMap({{ $barcode->latitude }}, {{ $barcode->longitude }}); return false;"
                            aria-label="{{ __('Open map for barcode') }}: {{ $barcode->name }}"
                            class="rounded hover:text-blue-600 hover:underline focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 dark:focus:ring-offset-gray-800 truncate">
                            {{ $barcode->latitude }}, {{ $barcode->longitude }}
                        </a>
                    </div>
                    <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                        <x-heroicon-o-arrows-pointing-out class="h-4 w-4 shrink-0" />
                        <span>{{ __('Radius') }}: <span
                                class="font-medium text-gray-900 dark:text-gray-200">{{ $barcode->radius }}m</span></span>
                    </div>
                    @if ($barcode->dynamic_enabled)
                        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                            <x-heroicon-o-clock class="h-4 w-4 shrink-0" />
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
                            <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                            <span class="sr-only">{{ __('Download') }}</span>
                        </x-actions.icon-button>
                    @endif
                    <x-actions.icon-button href="{{ route('admin.barcodes.edit', $barcode->id) }}"
                        label="{{ __('Edit barcode') }}: {{ $barcode->name }}" variant="warning"
                        class="h-auto w-full rounded-lg py-2">
                        <x-heroicon-o-pencil-square class="h-4 w-4" />
                        <span class="sr-only">{{ __('Edit') }}</span>
                    </x-actions.icon-button>
                    <x-actions.icon-button type="button"
                        wire:click="confirmDeletion({{ $barcode->id }})"
                        label="{{ __('Delete barcode') }}: {{ $barcode->name }}" variant="danger"
                        class="h-auto w-full rounded-lg py-2">
                        <x-heroicon-o-trash class="h-4 w-4" />
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
