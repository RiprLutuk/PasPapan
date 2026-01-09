<div class="space-y-6">
    @pushOnce('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    @endpushOnce

    {{-- Main Card --}}
    <div>
        
        {{-- Header --}}
        <div class="mb-5 flex flex-row items-center justify-between gap-4">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="p-1.5 bg-primary-50 text-primary-600 dark:bg-primary-900/50 dark:text-primary-400 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </span>
                    {{ __('Calendar') }}
                </h3>
            </div>
            
            <div class="flex items-center gap-2">
                <div class="w-24 sm:w-28">
                    <x-tom-select id="selectedMonth" wire:model.live="selectedMonth" placeholder="{{ __('Month') }}"
                        :options="collect(range(1, 12))->map(fn($m) => ['id' => sprintf('%02d', $m), 'name' => Carbon\Carbon::create()->month($m)->translatedFormat('F')])->values()->toArray()" />
                </div>
                <div class="w-20">
                    <x-tom-select id="selectedYear" wire:model.live="selectedYear" placeholder="{{ __('Year') }}"
                        :options="collect(range(date('Y') - 5, date('Y') + 1))->map(fn($y) => ['id' => $y, 'name' => $y])->values()->toArray()" />
                </div>
            </div>
        </div>

        {{-- Calendar Grid --}}
        <div class="mb-6">
            {{-- Days Header --}}
            <div class="grid grid-cols-7 mb-2">
                @foreach ([__('Sun'), __('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat')] as $index => $day)
                    <div class="text-center text-[10px] uppercase tracking-wider font-bold text-gray-400 dark:text-gray-500 py-1 {{ $index === 0 ? 'text-rose-500' : ($index === 5 ? 'text-emerald-600 dark:text-emerald-500' : '') }}">
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
                        
                        // Check if this date is a holiday
                        $dateKey = $date->format('Y-m-d');
                        $holiday = $holidays[$dateKey] ?? null;
                        $isHoliday = !is_null($holiday);
                        
                        // Find attendance
                        $attendance = $attendances->firstWhere(fn($v, $k) => $v->date->isSameDay($date));
                        $status = ($attendance ?? [
                            'status' => $isWeekend || $isHoliday || !$date->isPast() ? '-' : 'absent',
                        ])['status'];

                        // Styles (Clean)
                        $bgClass = $isCurrentMonth ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-900/50 opacity-50';
                        $textClass = $isCurrentMonth ? 'text-gray-700 dark:text-gray-200' : 'text-gray-400 dark:text-gray-600';
                        $borderClass = $isToday ? 'ring-2 ring-primary-500 z-10' : ($isCurrentMonth ? 'border border-gray-100 dark:border-gray-700' : 'border border-transparent');
                        
                        // Holiday styling
                        if ($isHoliday && $isCurrentMonth) {
                            $bgClass = 'bg-rose-50 dark:bg-rose-900/10';
                            $textClass = 'text-rose-600 dark:text-rose-400';
                            $borderClass = $isToday ? 'ring-2 ring-primary-500 z-10' : 'border border-rose-100 dark:border-rose-900/20';
                        } elseif ($date->isSunday() && $isCurrentMonth) {
                            $textClass = 'text-rose-500 dark:text-rose-400';
                        } elseif ($date->isFriday() && $isCurrentMonth) {
                            $textClass = 'text-emerald-600 dark:text-emerald-400';
                        }

                        // Status Marker
                        $markerColor = match($status) {
                            'present' => 'bg-emerald-500',
                            'late' => 'bg-amber-500',
                            'excused', 'sick' => match($attendance['approval_status'] ?? 'approved') {
                                'pending' => 'bg-amber-300',
                                'rejected' => 'bg-rose-300', // Light/Whitish Red
                                default => $status === 'excused' ? 'bg-sky-500' : 'bg-violet-500'
                            },
                            'absent' => 'bg-red-700', // Dark Red
                            default => $isToday ? 'bg-primary-500' : null
                        };
                    @endphp

                    <div class="aspect-[1/1] sm:aspect-[4/3] group relative">
                        <button type="button"
                            @if($attendance) wire:click="show({{ $attendance['id'] }})" @endif
                            class="w-full h-full flex flex-col items-center justify-between p-1 rounded-xl transition-all duration-200 {{ $bgClass }} {{ $textClass }} {{ $borderClass }} hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ $attendance ? 'cursor-pointer hover:shadow-sm' : 'cursor-default' }}">
                            
                            {{-- Holiday Indicator --}}
                            @if($isHoliday && $isCurrentMonth)
                                <span class="absolute top-1 right-1 text-[6px] text-rose-500">●</span>
                            @endif
                            
                            {{-- Date Number --}}
                            <span class="text-xs font-bold leading-none mt-1">
                                {{ $date->day }}
                            </span>
                            
                            {{-- Holiday Name (visible on desktop) --}}
                            {{-- Removed as per simplification --}}

                            {{-- Status Indicator --}}
                            <div class="mb-1 h-3 flex items-center justify-center">
                                @if($markerColor && $status !== '-')
                                    <span class="inline-flex h-1.5 w-1.5 rounded-full {{ $markerColor }}"></span>
                                @elseif($isHoliday && $isCurrentMonth)
                                     <span class="text-[8px] text-rose-500 leading-none">H</span>
                                @endif
                                
                                @if($attendance && isset($attendance['time_in']) && !$isHoliday)
                                    <span class="hidden sm:inline-block ml-1 text-[8px] text-gray-400 font-mono leading-none">
                                        {{ \Carbon\Carbon::parse($attendance['time_in'])->format('H:i') }}
                                    </span>
                                @endif
                            </div>
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
        
        {{-- Holidays List --}}
        @if($holidays->isNotEmpty())
        <div class="mb-6 px-4 py-3 bg-red-50/50 dark:bg-rose-900/10 rounded-xl border border-red-100 dark:border-rose-900/20">
            <h4 class="text-[10px] font-bold text-red-600 dark:text-red-400 mb-2 uppercase tracking-wide">
                {{ __('Holidays this Month') }}
            </h4>
            <div class="flex flex-wrap gap-2">
                @foreach($holidays->sortBy(fn($h) => $h->date->day) as $holiday)
                    <div class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-white dark:bg-rose-900/40 border border-red-100 dark:border-rose-800/30 text-[10px] text-red-700 dark:text-red-300 shadow-sm">
                        <span class="font-bold">{{ $holiday->date->day }}</span>
                        <span class="opacity-75">{{ $holiday->name }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
        
        {{-- Stats Grid (Compact) --}}
        <div>
            <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 mb-3 uppercase tracking-wider">
                {{ __('Summary') }}
            </h4>
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                @foreach([
                    ['label' => 'Present', 'key' => 'present', 'color' => 'text-emerald-600', 'bg' => 'bg-emerald-50 dark:bg-emerald-900/20', 'border' => 'border-emerald-100 dark:border-emerald-800/30'],
                    ['label' => 'Late', 'key' => 'late', 'color' => 'text-amber-600', 'bg' => 'bg-amber-50 dark:bg-amber-900/20', 'border' => 'border-amber-100 dark:border-amber-800/30'],
                    ['label' => 'Excused', 'key' => 'excused', 'color' => 'text-sky-600', 'bg' => 'bg-sky-50 dark:bg-sky-900/20', 'border' => 'border-sky-100 dark:border-sky-800/30'],
                    ['label' => 'Sick', 'key' => 'sick', 'color' => 'text-violet-600', 'bg' => 'bg-violet-50 dark:bg-violet-900/20', 'border' => 'border-violet-100 dark:border-violet-800/30'],
                    ['label' => 'Absent', 'key' => 'absent', 'color' => 'text-red-700', 'bg' => 'bg-red-50 dark:bg-red-900/20', 'border' => 'border-red-100 dark:border-red-800/30']
                ] as $stat)
                <div class="rounded-xl p-3 border {{ $stat['border'] }} {{ $stat['bg'] }} text-center {{ $stat['key'] === 'absent' ? 'col-span-2 sm:col-span-1' : '' }}">
                    <p class="text-xl font-bold {{ $stat['color'] }} dark:text-white">{{ $counts[$stat['key']] ?? 0 }}</p>
                    <p class="text-[10px] uppercase font-bold text-gray-500 dark:text-gray-400 mt-1">{{ __($stat['label']) }}</p>
                </div>
                @endforeach
            </div>
        </div>
        
        {{-- Legenda Modern --}}
        <div class="px-5 py-2 bg-gray-50/50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-700/50">
            <div class="flex flex-wrap justify-center gap-4 text-[10px] text-gray-500 dark:text-gray-400">
                <span class="flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-full bg-amber-400"></span> {{ __('Pending') }}
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-full bg-rose-500"></span> {{ __('Rejected') }}
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-full bg-primary-500"></span> {{ __('Today') }}
                </span>
            </div>
        </div>
    </div>

    {{-- Include Modal Component --}}
    @include('components.attendance-detail-modal')

    @stack('attendance-detail-scripts')
</div>
