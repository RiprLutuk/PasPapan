<div {{ $attributes->merge(['class' => 'location-card-surface p-4 sm:p-6 relative overflow-visible']) }}>
    
    {{-- Decorative Background Blob --}}
    <div class="absolute top-0 right-0 -mt-10 -mr-10 w-32 h-32 bg-primary-50 dark:bg-primary-900/20 rounded-full blur-3xl opacity-50 pointer-events-none"></div>

    <div class="flex items-center justify-between mb-3 relative z-10">
        <div class="flex items-center gap-3">
            @if (isset($icon))
                <div class="p-2 bg-{{ $iconColor ?? 'blue' }}-100 dark:bg-{{ $iconColor ?? 'blue' }}-900/50 rounded-xl text-{{ $iconColor ?? 'blue' }}-600 dark:text-{{ $iconColor ?? 'blue' }}-400">
                    <x-heroicon-o-map-pin class="h-5 w-5" />
                </div>
            @endif
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ $title }}</h3>
        </div>

        <div class="flex items-center gap-2">
            @if ($showRefresh ?? false)
                <button onclick="refreshLocation()" id="refresh-location-btn" title="{{ __('Refresh Location') }}"
                    class="p-2 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-full hover:bg-primary-100 dark:hover:bg-primary-900/50 hover:text-primary-600 dark:hover:text-primary-400 transition-all shadow-sm">
                    <x-heroicon-o-arrow-path class="h-4 w-4" />
                </button>
            @endif
            <button onclick="toggleMap('{{ $mapId }}')" id="toggle-{{ $mapId }}-btn"
                class="text-xs font-medium px-3 py-1.5 rounded-full bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 hover:bg-primary-100 dark:hover:bg-primary-900/50 transition flex items-center gap-1.5">
                <x-heroicon-o-chevron-down class="h-3.5 w-3.5 transition-transform duration-300" />
                <span>{{ __('Show Map') }}</span>
            </button>
        </div>
    </div>

    <div id="location-text-{{ $mapId }}" class="relative z-10 pl-1">
        @if ($latitude && $longitude)
            <div class="flex items-center gap-2 mt-1">
                <a href="#" onclick="window.openMap({{ $latitude }}, {{ $longitude }}); return false;"
                    class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition bg-gray-50 dark:bg-gray-800/50 px-2 py-1 rounded-md border border-gray-100 dark:border-gray-700">
                    <x-heroicon-o-map-pin class="h-3.5 w-3.5 flex-shrink-0 text-primary-500" />
                    {{ $latitude . ', ' . $longitude }}
                </a>
            </div>
        @else
            <span class="text-xs text-gray-500 dark:text-gray-400 block mt-1 italic pl-1">
                @if (isset($showRefresh) && $showRefresh)
                    {{ __('Detecting location...') }}
                @else
                    {{ __('No location data') }}
                @endif
            </span>
        @endif
        <div id="location-updated-{{ $mapId }}" class="text-[10px] text-gray-400 mt-1 pl-1" wire:ignore></div>
    </div>

    {{-- Collapsible Map Container --}}
    <div class="map-container hidden mt-4 rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 shadow-inner relative z-10" id="{{ $mapId }}" style="height: 250px;" wire:ignore></div>
</div>

<script>
    function toggleMap(mapId) {
        const mapContainer = document.getElementById(mapId);
        const btn = document.getElementById('toggle-' + mapId + '-btn');
        const icon = btn.querySelector('svg');
        const text = btn.querySelector('span');
        
        if (mapContainer.classList.contains('hidden')) {
            // Show Map
            mapContainer.classList.remove('hidden');
            text.textContent = "{{ __('Tutup Peta') }}";
            icon.classList.add('rotate-180');
            
            // Trigger leaflet resize if needed
            setTimeout(() => {
                window.dispatchEvent(new Event('resize'));
            }, 100);
            
            // Initialize map if function exists (handled by scan component usually)
            if (typeof initMap === 'function') {
                // initMap(mapId); // Might need specific logic depends on how maps are initialized
            }
        } else {
            // Hide Map
            mapContainer.classList.add('hidden');
            text.textContent = "{{ __('Lihat Peta') }}";
            icon.classList.remove('rotate-180');
        }
    }
</script>
