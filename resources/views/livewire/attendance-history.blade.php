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
                    {{ __('Attendance History') }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ __('Click date to view details.') }}
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
                @foreach ([__('Sun'), __('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat')] as $index => $day)
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
                        
                        // Check if this date is a holiday
                        $dateKey = $date->format('Y-m-d');
                        $holiday = $holidays[$dateKey] ?? null;
                        $isHoliday = !is_null($holiday);
                        
                        // Find attendance
                        $attendance = $attendances->firstWhere(fn($v, $k) => $v->date->isSameDay($date));
                        $status = ($attendance ?? [
                            'status' => $isWeekend || $isHoliday || !$date->isPast() ? '-' : 'absent',
                        ])['status'];

                        // Styles
                        $bgClass = $isCurrentMonth ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-900/50';
                        $textClass = $isCurrentMonth ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-600';
                        $borderClass = $isToday ? 'ring-2 ring-blue-500 z-10' : 'border border-gray-100 dark:border-gray-700';
                        
                        // Holiday styling (priority over weekend)
                        if ($isHoliday && $isCurrentMonth) {
                            $bgClass = 'bg-rose-50 dark:bg-rose-900/20';
                            $textClass = 'text-rose-600 dark:text-rose-400';
                            $borderClass = $isToday ? 'ring-2 ring-blue-500 z-10' : 'border border-rose-200 dark:border-rose-700';
                        } elseif ($date->isSunday() && $isCurrentMonth) {
                            $textClass = 'text-red-500 dark:text-red-400 shadow-red-50';
                        } elseif ($date->isFriday() && $isCurrentMonth) {
                            $textClass = 'text-green-600 dark:text-green-400';
                        }

                        // Status Marker
                        $markerColor = match($status) {
                            'present' => 'bg-green-500',
                            'late' => 'bg-amber-500',
                            'excused', 'sick' => match($attendance['approval_status'] ?? 'approved') {
                                'pending' => 'bg-yellow-400 ring-2 ring-yellow-200',
                                'rejected' => 'bg-red-600 ring-2 ring-red-200',
                                default => $status === 'excused' ? 'bg-blue-500' : 'bg-purple-500'
                            },
                            'absent' => 'bg-red-500',
                            default => $isToday ? 'bg-blue-500' : null
                        };
                    @endphp

                    <div class="relative aspect-[1/1] sm:aspect-[4/3] group">
                        <button type="button"
                            @if($attendance) wire:click="show({{ $attendance['id'] }})" @endif
                            class="w-full h-full flex flex-col items-center justify-between p-1 sm:p-2 rounded-lg transition-all duration-200 {{ $bgClass }} {{ $textClass }} {{ $borderClass }} hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ $attendance ? 'cursor-pointer hover:shadow-md' : 'cursor-default' }}">
                            
                            {{-- Holiday Indicator --}}
                            @if($isHoliday && $isCurrentMonth)
                                <span class="absolute top-0.5 right-0.5 text-[8px] sm:text-[10px]" title="{{ $holiday->name }}">🎌</span>
                            @endif
                            
                            {{-- Date Number --}}
                            <span class="text-xs sm:text-sm font-medium {{ !$isCurrentMonth ? 'opacity-50' : '' }}">
                                {{ $date->day }}
                            </span>
                            
                            {{-- Holiday Name (visible on desktop) --}}
                            @if($isHoliday && $isCurrentMonth)
                                <span class="hidden sm:block text-[9px] leading-tight text-rose-500 dark:text-rose-400 font-medium truncate max-w-full px-1">
                                    {{ Str::limit($holiday->name, 10) }}
                                </span>
                            @endif

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
        
        {{-- Holidays List (like real calendar) --}}
        @if($holidays->isNotEmpty())
        <div class="p-4 sm:p-6 bg-rose-50 dark:bg-rose-900/10 border-t border-rose-200 dark:border-rose-700/50">
            <h4 class="text-sm font-semibold text-rose-700 dark:text-rose-400 mb-3 flex items-center gap-2">
                🎌 {{ __('Holidays This Month') }}
            </h4>
            <div class="space-y-2">
                @foreach($holidays->sortBy(fn($h) => $h->date->day) as $holiday)
                    <div class="flex items-center gap-3 text-sm">
                        <span class="font-bold text-rose-600 dark:text-rose-400 w-8">{{ $holiday->date->day }}</span>
                        <span class="text-gray-800 dark:text-gray-200">{{ $holiday->name }}</span>
                        @if($holiday->description)
                            <span class="text-gray-500 dark:text-gray-400 text-xs">- {{ $holiday->description }}</span>
                        @endif
                        @if($holiday->is_recurring)
                            <span class="text-[10px] px-1.5 py-0.5 bg-rose-200 dark:bg-rose-800 text-rose-700 dark:text-rose-300 rounded">{{ __('Yearly') }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    
    {{-- Summary Card (Separate) --}}
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="p-4 sm:p-6">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">{{ __('Attendance Summary') }}</h4>
            <div class="flex flex-wrap justify-center gap-3 sm:gap-4">
                 @foreach([
                    'present' => ['label' => __('Present'), 'color' => 'bg-green-500', 'text' => 'text-green-700 dark:text-green-400'],
                    'late' => ['label' => __('Late'), 'color' => 'bg-amber-500', 'text' => 'text-amber-700 dark:text-amber-400'],
                    'excused' => ['label' => __('Excused'), 'color' => 'bg-blue-500', 'text' => 'text-blue-700 dark:text-blue-400'],
                    'sick' => ['label' => __('Sick'), 'color' => 'bg-purple-500', 'text' => 'text-purple-700 dark:text-purple-400'],
                    'absent' => ['label' => __('Absent'), 'color' => 'bg-red-500', 'text' => 'text-red-700 dark:text-red-400'],
                ] as $key => $meta)
                    <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-100 dark:border-gray-700">
                        <span class="h-3 w-3 rounded-full {{ $meta['color'] }}"></span>
                        <span class="text-sm text-gray-600 dark:text-gray-300">{{ $meta['label'] }}</span>
                        <span class="text-lg font-bold {{ $meta['text'] }}">{{ $counts[$key] ?? 0 }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        
        {{-- Legend --}}
        <div class="px-4 sm:px-6 pb-4 sm:pb-6">
            <div class="flex flex-wrap justify-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-yellow-400 ring-2 ring-yellow-200"></span> {{ __('Pending') }}</span>
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-red-600 ring-2 ring-red-200"></span> {{ __('Rejected') }}</span>
                <span class="flex items-center gap-1.5">🎌 {{ __('Holiday') }}</span>
            </div>
        </div>
    </div>

    {{-- Include Modal Component --}}
    @include('components.attendance-detail-modal')

    @stack('attendance-detail-scripts')
</div>
