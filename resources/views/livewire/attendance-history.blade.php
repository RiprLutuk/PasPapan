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
        
        {{-- Summary Section (Integrated) --}}
        <div class="p-4 sm:p-6 border-t border-gray-200 dark:border-gray-700">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                <span class="w-1 h-4 bg-gradient-to-b from-indigo-500 to-purple-500 rounded-full"></span>
                {{ __('Attendance Summary') }}
            </h4>
            
            {{-- Stats Grid --}}
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                {{-- Present - Emerald/Teal --}}
                <div class="relative group p-4 rounded-xl bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/30 dark:to-teal-900/30 border border-emerald-200/50 dark:border-emerald-700/30 hover:shadow-md transition-all">
                    <div class="flex flex-col items-center">
                        <span class="text-3xl font-black text-emerald-600 dark:text-emerald-400">{{ $counts['present'] ?? 0 }}</span>
                        <span class="text-xs font-medium text-emerald-700 dark:text-emerald-300 mt-1">{{ __('Present') }}</span>
                    </div>
                    <div class="absolute top-2 right-2 w-2 h-2 rounded-full bg-emerald-500"></div>
                </div>
                
                {{-- Late - Orange/Amber --}}
                <div class="relative group p-4 rounded-xl bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/30 dark:to-amber-900/30 border border-orange-200/50 dark:border-orange-700/30 hover:shadow-md transition-all">
                    <div class="flex flex-col items-center">
                        <span class="text-3xl font-black text-orange-600 dark:text-orange-400">{{ $counts['late'] ?? 0 }}</span>
                        <span class="text-xs font-medium text-orange-700 dark:text-orange-300 mt-1">{{ __('Late') }}</span>
                    </div>
                    <div class="absolute top-2 right-2 w-2 h-2 rounded-full bg-orange-500"></div>
                </div>
                
                {{-- Excused - Sky/Cyan --}}
                <div class="relative group p-4 rounded-xl bg-gradient-to-br from-sky-50 to-cyan-50 dark:from-sky-900/30 dark:to-cyan-900/30 border border-sky-200/50 dark:border-sky-700/30 hover:shadow-md transition-all">
                    <div class="flex flex-col items-center">
                        <span class="text-3xl font-black text-sky-600 dark:text-sky-400">{{ $counts['excused'] ?? 0 }}</span>
                        <span class="text-xs font-medium text-sky-700 dark:text-sky-300 mt-1">{{ __('Excused') }}</span>
                    </div>
                    <div class="absolute top-2 right-2 w-2 h-2 rounded-full bg-sky-500"></div>
                </div>
                
                {{-- Sick - Violet/Fuchsia --}}
                <div class="relative group p-4 rounded-xl bg-gradient-to-br from-violet-50 to-fuchsia-50 dark:from-violet-900/30 dark:to-fuchsia-900/30 border border-violet-200/50 dark:border-violet-700/30 hover:shadow-md transition-all">
                    <div class="flex flex-col items-center">
                        <span class="text-3xl font-black text-violet-600 dark:text-violet-400">{{ $counts['sick'] ?? 0 }}</span>
                        <span class="text-xs font-medium text-violet-700 dark:text-violet-300 mt-1">{{ __('Sick') }}</span>
                    </div>
                    <div class="absolute top-2 right-2 w-2 h-2 rounded-full bg-violet-500"></div>
                </div>
                
                {{-- Absent - Slate/Zinc (clearly different from Rejected red) --}}
                <div class="relative group p-4 rounded-xl bg-gradient-to-br from-slate-100 to-zinc-100 dark:from-slate-800/50 dark:to-zinc-800/50 border border-slate-300/50 dark:border-slate-600/30 hover:shadow-md transition-all col-span-2 sm:col-span-1">
                    <div class="flex flex-col items-center">
                        <span class="text-3xl font-black text-slate-600 dark:text-slate-300">{{ $counts['absent'] ?? 0 }}</span>
                        <span class="text-xs font-medium text-slate-700 dark:text-slate-400 mt-1">{{ __('Absent') }}</span>
                    </div>
                    <div class="absolute top-2 right-2 w-2 h-2 rounded-full bg-slate-500"></div>
                </div>
            </div>
        </div>
        
        {{-- Legend Footer --}}
        <div class="px-5 py-3 bg-white/50 dark:bg-gray-800/50 border-t border-gray-200/50 dark:border-gray-700/50">
            <div class="flex flex-wrap justify-center gap-5 text-xs text-gray-600 dark:text-gray-400">
                <span class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full bg-gradient-to-r from-yellow-400 to-amber-400 ring-2 ring-yellow-100 dark:ring-yellow-900"></span> 
                    {{ __('Pending') }}
                </span>
                <span class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full bg-gradient-to-r from-red-500 to-rose-600 ring-2 ring-red-100 dark:ring-red-900"></span> 
                    {{ __('Rejected') }}
                </span>
                <span class="flex items-center gap-2">
                    🎌 {{ __('Holiday') }}
                </span>
            </div>
        </div>
    </div>

    {{-- Include Modal Component --}}
    @include('components.attendance-detail-modal')

    @stack('attendance-detail-scripts')
</div>
