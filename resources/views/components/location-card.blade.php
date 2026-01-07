<div class="rounded-lg border border-gray-200 bg-white p-4 sm:p-6 shadow dark:border-gray-700 dark:bg-gray-800">
    <div class="flex items-center gap-3 mb-3">
        @if (isset($icon))
            <div class="p-2 bg-{{ $iconColor ?? 'blue' }}-100 dark:bg-{{ $iconColor ?? 'blue' }}-900/30 rounded-lg">
                <svg class="w-4 h-4 text-{{ $iconColor ?? 'blue' }}-600 dark:text-{{ $iconColor ?? 'blue' }}-400"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}" />
                </svg>
            </div>
        @endif
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $title }}</h3>

        <div class="flex items-center gap-2 ml-auto">
            @if ($showRefresh ?? false)
                <button onclick="refreshLocation()" id="refresh-location-btn" title="{{ __('Refresh Location') }}"
                    class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                    <svg class="w-4 h-4 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
            @endif
            <button onclick="toggleMap('{{ $mapId }}')" id="toggle-{{ $mapId }}-btn"
                class="text-blue-600 dark:text-blue-400 text-xs hover:underline transition flex items-center gap-1">
                <svg class="w-4 h-4 transition-transform duration-300" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
                <span>{{ __('Show Map') }}</span>
            </button>
        </div>
    </div>

    <div id="location-text-{{ $mapId }}">
        @if ($latitude && $longitude)
            <a href="#" onclick="window.openMap({{ $latitude }}, {{ $longitude }}); return false;"
                class="inline-flex items-center gap-2 text-xs text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                {{ $latitude . ', ' . $longitude }}
            </a>
        @else
            <span class="text-xs text-gray-500 dark:text-gray-400">
                @if (isset($showRefresh) && $showRefresh)
                    {{ __('Detecting location...') }}
                @else
                    {{ __('No location data') }}
                @endif
            </span>
        @endif
        <div id="location-updated-{{ $mapId }}" class="text-[10px] text-gray-400 mt-1" wire:ignore></div>
    </div>
    <div class="map-container hidden mt-4 rounded-lg overflow-hidden" id="{{ $mapId }}" style="height: 250px;" wire:ignore></div>
</div>
