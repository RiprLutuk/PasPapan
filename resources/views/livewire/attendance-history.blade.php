<div class="space-y-6">
    @pushOnce('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    @endpushOnce

    {{-- Main Card --}}
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
        {{-- Header --}}
        <div class="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Riwayat Absensi
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Klik tanggal untuk melihat detail.
                </p>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="w-full sm:w-32">
                    <x-tom-select id="selectedMonth" wire:model.live="selectedMonth" placeholder="{{ __('Month') }}"
                        :options="collect(range(1, 12))->map(fn($m) => ['id' => sprintf('%02d', $m), 'name' => Carbon\Carbon::create()->month($m)->translatedFormat('F')])->values()->toArray()" />
                </div>
                <div class="w-full sm:w-24">
                    <x-tom-select id="selectedYear" wire:model.live="selectedYear" placeholder="{{ __('Year') }}"
                        :options="collect(range(date('Y') - 5, date('Y') + 1))->map(fn($y) => ['id' => $y, 'name' => $y])->values()->toArray()" />
                </div>
            </div>
        </div>

        {{-- Calendar Grid --}}
        <div class="p-4 sm:p-6">
            {{-- Days Header --}}
            <div class="grid grid-cols-7 mb-2">
                @foreach (['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'] as $index => $day)
                    <div class="text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 py-2 {{ $index === 0 ? 'text-red-500' : ($index === 5 ? 'text-green-600 dark:text-green-500' : '') }}">
                        {{ $day }}
                    </div>
                @endforeach
            </div>

            {{-- Calendar Dates --}}
            <div class="grid grid-cols-7 gap-1 sm:gap-2">
                @foreach ($dates as $date)
                    @php
                        $isCurrentMonth = $date->month == $currentMonth;
                        $isToday = $date->isToday();
                        $isWeekend = $date->isWeekend();
                        
                        // Find attendance
                        $attendance = $attendances->firstWhere(fn($v, $k) => $v->date->isSameDay($date));
                        $status = ($attendance ?? [
                            'status' => $isWeekend || !$date->isPast() ? '-' : 'absent',
                        ])['status'];

                        // Styles
                        $bgClass = $isCurrentMonth ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-900/50';
                        $textClass = $isCurrentMonth ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-600';
                        $borderClass = $isToday ? 'ring-2 ring-blue-500 z-10' : 'border border-gray-100 dark:border-gray-700';
                        
                        if ($date->isSunday() && $isCurrentMonth) $textClass = 'text-red-500 dark:text-red-400 shadow-red-50';
                        if ($date->isFriday() && $isCurrentMonth) $textClass = 'text-green-600 dark:text-green-400';

                        // Status Marker
                        $markerColor = match($status) {
                            'present' => 'bg-green-500',
                            'late' => 'bg-amber-500',
                            'excused' => 'bg-blue-500',
                            'sick' => 'bg-purple-500',
                            'absent' => 'bg-red-500',
                            default => $isToday ? 'bg-blue-500' : null
                        };
                    @endphp

                    <div class="relative aspect-[1/1] sm:aspect-[4/3] group">
                        <button type="button"
                            @if($attendance) wire:click="show({{ $attendance['id'] }})" @endif
                            class="w-full h-full flex flex-col items-center justify-between p-1 sm:p-2 rounded-lg transition-all duration-200 {{ $bgClass }} {{ $textClass }} {{ $borderClass }} hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ $attendance ? 'cursor-pointer hover:shadow-md' : 'cursor-default' }}">
                            
                            {{-- Date Number --}}
                            <span class="text-xs sm:text-sm font-medium {{ !$isCurrentMonth ? 'opacity-50' : '' }}">
                                {{ $date->day }}
                            </span>

                            {{-- Status Indicator --}}
                            @if($markerColor && $status !== '-')
                                <div class="mb-1">
                                    <span class="inline-flex h-2 w-2 rounded-full {{ $markerColor }}"></span>
                                    <span class="sr-only">{{ ucfirst($status) }}</span>
                                    
                                    {{-- Time for desktop (optional) --}}
                                    @if($attendance && isset($attendance['time_in']))
                                         <span class="hidden sm:inline-block text-[10px] text-gray-500 dark:text-gray-400">
                                            {{ \App\Helpers::format_time($attendance['time_in']) }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
        
        {{-- Legend / Summary --}}
        <div class="p-4 sm:p-6 bg-gray-50 dark:bg-gray-700/20 border-t border-gray-200 dark:border-gray-700 rounded-b-lg">
            <div class="flex flex-wrap justify-center gap-3 sm:gap-6">
                 @foreach([
                    'present' => ['label' => 'Hadir', 'color' => 'bg-green-500', 'text' => 'text-green-700 dark:text-green-400'],
                    'late' => ['label' => 'Terlambat', 'color' => 'bg-amber-500', 'text' => 'text-amber-700 dark:text-amber-400'],
                    'excused' => ['label' => 'Izin', 'color' => 'bg-blue-500', 'text' => 'text-blue-700 dark:text-blue-400'],
                    'sick' => ['label' => 'Sakit', 'color' => 'bg-purple-500', 'text' => 'text-purple-700 dark:text-purple-400'],
                    'absent' => ['label' => 'Absen', 'color' => 'bg-red-500', 'text' => 'text-red-700 dark:text-red-400']
                ] as $key => $meta)
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700">
                        <span class="h-2.5 w-2.5 rounded-full {{ $meta['color'] }}"></span>
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ $meta['label'] }}:</span>
                        <span class="text-sm font-bold {{ $meta['text'] }}">{{ $counts[$key] ?? 0 }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Include Modal Component --}}
    @include('components.attendance-detail-modal')

    @stack('attendance-detail-scripts')
</div>
