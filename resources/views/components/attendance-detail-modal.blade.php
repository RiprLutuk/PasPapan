<x-modal wire:model="showDetail" max-width="2xl">
    <div class="px-6 py-4">
        @if ($currentAttendance)
            @php
                $isExcused = in_array($currentAttendance['status'] ?? '', ['excused', 'sick']);
                $hasCheckIn = !empty($currentAttendance['latitude_in']) && !empty($currentAttendance['longitude_in']);
                $hasCheckOut =
                    !empty($currentAttendance['latitude_out']) && !empty($currentAttendance['longitude_out']);
                $showMaps = ($hasCheckIn || $hasCheckOut) && !$isExcused;
            @endphp

            <h3 class="mb-4 text-xl font-semibold text-gray-900 dark:text-white">
                {{ __('Attendance Detail') }} - {{ $currentAttendance['name'] ?? 'N/A' }}
            </h3>

            <div class="space-y-4">
                {{-- Basic Info --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-label for="nip" value="{{ __('NIP') }}"></x-label>
                        <x-input type="text" class="w-full" id="nip" disabled
                            value="{{ $currentAttendance['nip'] ?? '-' }}"></x-input>
                    </div>
                    <div>
                        <x-label for="status" value="{{ __('Status') }}"></x-label>
                        <x-input type="text" class="w-full" id="status" disabled
                            value="{{ __(ucfirst($currentAttendance['status'] ?? 'absent')) }}"></x-input>
                    </div>

                    @if($isExcused)
                    <div class="md:col-span-2">
                        <x-label value="{{ __('Status Pengajuan') }}"></x-label>
                        @php
                            $approvalStatus = $currentAttendance['approval_status'] ?? 'approved';
                            $statusColor = match($approvalStatus) {
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'approved' => 'bg-green-100 text-green-800',
                                'rejected' => 'bg-red-100 text-red-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                            $statusLabel = match($approvalStatus) {
                                'pending' => __('Pending Approval'),
                                'approved' => __('Approved'),
                                'rejected' => __('Rejected'),
                                default => ucfirst($approvalStatus)
                            };
                        @endphp
                        <div class="mt-1 px-3 py-2 rounded-md font-medium {{ $statusColor }}">
                            {{ $statusLabel }}
                        </div>
                    </div>

                    @if(($currentAttendance['approval_status'] ?? '') === 'rejected' && !empty($currentAttendance['rejection_note']))
                    <div class="md:col-span-2">
                         <x-label value="{{ __('Rejection Reason') }}"></x-label>
                         <div class="mt-1 px-3 py-2 rounded-md bg-red-50 text-red-700 border border-red-200">
                            {{ $currentAttendance['rejection_note'] }}
                         </div>
                    </div>
                    @endif
                    @endif
                </div>

                <div class="py-2">
                    <x-label for="date" value="{{ __('Date') }}"></x-label>
                    <x-input type="text" class="w-full" id="date" disabled
                        value="{{ $currentAttendance['date'] ?? '-' }}"></x-input>
                </div>

                @if ($isExcused && !empty($currentAttendance['address']))
                    <div>
                        <x-label for="address" value="{{ __('Address') }}" />
                        <x-input type="text" class="w-full" id="address" disabled
                            value="{{ $currentAttendance['address'] }}" />
                    </div>
                @endif

                {{-- Attachment --}}
                @if (!empty($currentAttendance['attachment']))
                    <div class="py-2">
                        <x-label for="attachment" value="{{ __('Attachment') }}"></x-label>
                        @php
                            $attachments = $currentAttendance['attachment'];
                            
                            // Decode if it's a JSON string
                            if (is_string($attachments)) {
                                $decoded = json_decode($attachments, true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                    $attachments = $decoded;
                                }
                            }
                        @endphp

                        @if (is_array($attachments))
                            <div class="grid grid-cols-2 gap-2 mt-2">
                                @foreach ($attachments as $key => $url)
                                    <div>
                                        <x-label value="{{ __(ucfirst($key)) }}" class="mb-1" />
                                        <img src="{{ route('attendance.photo', ['attendance' => $currentAttendance['id'], 'type' => is_string($key) ? $key : 'general', 'index' => $key]) }}" 
                                             alt="Attachment {{ $key }}"
                                             class="max-h-48 w-full object-contain rounded-lg border border-gray-200 dark:border-gray-700">
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <img src="{{ route('attendance.photo', ['attendance' => $currentAttendance['id'], 'type' => 'general']) }}" 
                                 alt="Attachment"
                                 class="mt-2 max-h-64 w-full object-contain rounded-lg border border-gray-200 dark:border-gray-700">
                        @endif
                    </div>
                @endif

                {{-- Note --}}
                @if (!empty($currentAttendance['note']))
                    <div class="py-2">
                        <x-label for="note" value="{{ __('Note') }}" />
                        <x-textarea id="note" class="w-full"
                            disabled>{{ $currentAttendance['note'] }}</x-textarea>
                    </div>
                @endif

                {{-- Time In/Out --}}
                @if (!empty($currentAttendance['time_in']) || !empty($currentAttendance['time_out']))
                    <div class="grid grid-cols-2 gap-4 py-2">
                        <div>
                            <x-label for="time_in" value="{{ __('Time In') }}"></x-label>
                            <x-input type="text" id="time_in" class="w-full" disabled
                                value="{{ \App\Helpers::format_time($currentAttendance['time_in']) }}"></x-input>
                        </div>
                        <div>
                            <x-label for="time_out" value="{{ __('Time Out') }}"></x-label>
                            <x-input type="text" id="time_out" class="w-full" disabled
                                value="{{ \App\Helpers::format_time($currentAttendance['time_out']) }}"></x-input>
                        </div>
                    </div>
                @endif

                {{-- Location Maps --}}
                @if ($showMaps)
                    <div class="mt-6">
                        <h4 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">{{ __('Attendance Location') }}</h4>

                        <div class="grid grid-cols-1 {{ $hasCheckIn && $hasCheckOut ? 'md:grid-cols-2' : '' }} gap-4">
                            {{-- Check In Location --}}
                            @if ($hasCheckIn)
                                <div class="space-y-3">
                                    <div class="flex items-center gap-2">
                                        <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                            </svg>
                                        </div>
                                        <span class="font-semibold text-gray-900 dark:text-white">Check In</span>
                                    </div>
                                    <a href="#" onclick="window.openMap({{ $currentAttendance['latitude_in'] }}, {{ $currentAttendance['longitude_in'] }}); return false;"
                                        class="block text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                        📍 {{ number_format($currentAttendance['latitude_in'], 6) }},
                                        {{ number_format($currentAttendance['longitude_in'], 6) }}
                                    </a>
                                    <div wire:ignore class="h-64 w-full rounded-lg overflow-hidden border-2 border-blue-200 dark:border-blue-800"
                                        id="map_in"></div>
                                </div>
                            @endif

                            {{-- Check Out Location --}}
                            @if ($hasCheckOut)
                                <div class="space-y-3">
                                    <div class="flex items-center gap-2">
                                        <div class="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                                            <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                            </svg>
                                        </div>
                                        <span class="font-semibold text-gray-900 dark:text-white">Check Out</span>
                                    </div>
                                    <a href="#" onclick="window.openMap({{ $currentAttendance['latitude_out'] }}, {{ $currentAttendance['longitude_out'] }}); return false;"
                                        class="block text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                        📍 {{ number_format($currentAttendance['latitude_out'], 6) }},
                                        {{ number_format($currentAttendance['longitude_out'], 6) }}
                                    </a>
                                    <div wire:ignore class="h-64 w-full rounded-lg overflow-hidden border-2 border-orange-200 dark:border-orange-800"
                                        id="map_out"></div>
                                </div>
                            @endif
                        </div>

                        {{-- Distance Calculation --}}
                        @if ($hasCheckIn && $hasCheckOut)
                            @php
                                // Calculate distance in PHP (Haversine formula)
                                $lat1 = deg2rad($currentAttendance['latitude_in']);
                                $lat2 = deg2rad($currentAttendance['latitude_out']);
                                $lon1 = deg2rad($currentAttendance['longitude_in']);
                                $lon2 = deg2rad($currentAttendance['longitude_out']);
                                
                                $dlat = $lat2 - $lat1;
                                $dlon = $lon2 - $lon1;
                                
                                $a = sin($dlat/2) * sin($dlat/2) + cos($lat1) * cos($lat2) * sin($dlon/2) * sin($dlon/2);
                                $c = 2 * atan2(sqrt($a), sqrt(1-$a));
                                $distance = 6371000 * $c; // Distance in meters
                                
                                if ($distance < 1000) {
                                    $distanceText = number_format($distance, 2) . ' meters';
                                    $colorClass = $distance < 100 ? 'text-green-600 dark:text-green-400' : ($distance < 500 ? 'text-yellow-600 dark:text-yellow-400' : 'text-orange-600 dark:text-orange-400');
                                } else {
                                    $distanceText = number_format($distance / 1000, 2) . ' km';
                                    $colorClass = 'text-red-600 dark:text-red-400';
                                }
                            @endphp
                            <div
                                class="mt-4 p-3 bg-gradient-to-r from-blue-50 to-orange-50 dark:from-blue-900/30 dark:to-orange-900/30 rounded-lg border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-sm text-gray-700 dark:text-gray-300">
                                        🗺️ {{ __('Distance Check In - Check Out') }}:
                                    </span>
                                    <span class="font-bold text-sm {{ $colorClass }}">{{ $distanceText }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Shift & Barcode Info --}}
                @if (!empty($currentAttendance['shift']) || !empty($currentAttendance['barcode']))
                    <div
                        class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        @if (!empty($currentAttendance['shift']))
                            <div>
                                <x-label for="shift" value="{{ __('Shift') }}"></x-label>
                                <x-input class="w-full" type="text" id="shift" disabled
                                    value="{{ $currentAttendance['shift']['name'] ?? '-' }}"></x-input>
                            </div>
                        @endif
                        @if (!empty($currentAttendance['barcode']))
                            <div>
                                <x-label for="barcode" value="{{ __('Barcode') }}"></x-label>
                                <x-input class="w-full" type="text" id="barcode" disabled
                                    value="{{ $currentAttendance['barcode']['name'] ?? '-' }}"></x-input>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Close Button --}}
            <div class="mt-6 flex justify-end">
                <x-secondary-button wire:click="$set('showDetail', false)" class="!px-3 !py-1.5">
                    {{ __('Close') }}
                </x-secondary-button>
            </div>
        @endif
    </div>
</x-modal>

@push('scripts')
    <script>
        window.attendanceMaps = window.attendanceMaps || {
            in: null,
            out: null
        };

        // Define openMap globally
        window.openMap = function(lat, lng) {
            window.open(`https://www.google.com/maps/search/?api=1&query=${lat},${lng}`, '_blank');
        };

        document.addEventListener('livewire:init', () => {
            Livewire.on('attendance-detail-loaded', (event) => {
                const {
                    latIn,
                    lngIn,
                    latOut,
                    lngOut
                } = event;
                
                // Wait for modal transition (usually 300ms-500ms) and DOM update
                setTimeout(() => {
                    initAttendanceMaps(latIn, lngIn, latOut, lngOut);
                }, 500); 
            });
            
            Livewire.on('attendance-detail-closed', () => {
                removeAllMaps();
            });
        });

        function initAttendanceMaps(latIn, lngIn, latOut, lngOut) {
            removeAllMaps();

            // Check In Map
            const mapInEl = document.getElementById('map_in');
            if (mapInEl && latIn && lngIn) {
                attendanceMaps.in = L.map('map_in').setView([Number(latIn), Number(lngIn)], 18);

                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap'
                }).addTo(attendanceMaps.in);

                // Use circleMarker instead of divIcon for reliability
                L.circleMarker([Number(latIn), Number(lngIn)], {
                    radius: 12,
                    fillColor: '#3b82f6',
                    color: '#ffffff',
                    weight: 3,
                    opacity: 1,
                    fillOpacity: 1
                }).addTo(attendanceMaps.in)
                  .bindPopup('<b>📍 {{ __("Check In Location") }}</b>');
                
                // Force map resize calculation after render
                setTimeout(() => {
                    if (attendanceMaps.in) attendanceMaps.in.invalidateSize();
                }, 100);
            }

            // Check Out Map
            const mapOutEl = document.getElementById('map_out');
            if (mapOutEl && latOut && lngOut) {
                attendanceMaps.out = L.map('map_out').setView([Number(latOut), Number(lngOut)], 18);

                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap'
                }).addTo(attendanceMaps.out);

                // Use circleMarker instead of divIcon for reliability
                L.circleMarker([Number(latOut), Number(lngOut)], {
                    radius: 12,
                    fillColor: '#f97316',
                    color: '#ffffff',
                    weight: 3,
                    opacity: 1,
                    fillOpacity: 1
                }).addTo(attendanceMaps.out)
                  .bindPopup('<b>📍 {{ __("Check Out Location") }}</b>');
                
                // Force map resize calculation after render
                setTimeout(() => {
                    if (attendanceMaps.out) attendanceMaps.out.invalidateSize();
                }, 100);
            }
        }

        function calculateDistance(lat1, lng1, lat2, lng2) {
            const R = 6371e3;
            const φ1 = lat1 * Math.PI / 180;
            const φ2 = lat2 * Math.PI / 180;
            const Δφ = (lat2 - lat1) * Math.PI / 180;
            const Δλ = (lng2 - lng1) * Math.PI / 180;

            const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
                Math.cos(φ1) * Math.cos(φ2) *
                Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            const distance = R * c;

            const distEl = document.getElementById('distance-info');
            if (distEl) {
                let text, colorClass;
                if (distance < 1000) {
                    text = `${distance.toFixed(2)} meters`;
                    colorClass = distance < 100 ? 'text-green-600 dark:text-green-400' :
                        distance < 500 ? 'text-yellow-600 dark:text-yellow-400' :
                        'text-orange-600 dark:text-orange-400';
                } else {
                    text = `${(distance / 1000).toFixed(2)} km`;
                    colorClass = 'text-red-600 dark:text-red-400';
                }
                distEl.textContent = text;
                distEl.className = `font-bold text-sm ${colorClass}`;
            } else {
                console.warn('Distance element not found');
            }
        }

        function removeAllMaps() {
            if (attendanceMaps.in) {
                attendanceMaps.in.remove();
                attendanceMaps.in = null;
            }
            if (attendanceMaps.out) {
                attendanceMaps.out.remove();
                attendanceMaps.out = null;
            }
        }
    </script>
@endpush
