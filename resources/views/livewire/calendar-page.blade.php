<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <span>🗓️</span> {{ __('Calendar') }}
        </h2>
        
        <div class="flex items-center bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-1">
            <button wire:click="prevMonth" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </button>
            <span class="px-4 font-semibold text-gray-800 dark:text-gray-200 min-w-[140px] text-center">
                {{ $currentDate->format('F Y') }}
            </span>
            <button wire:click="nextMonth" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </button>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
        <!-- Weekday Headers -->
        <div class="grid grid-cols-7 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50">
            @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)
                <div class="py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">
                    {{ __($day) }}
                </div>
            @endforeach
        </div>

        <!-- Days Grid -->
        <div class="grid grid-cols-7 auto-rows-fr bg-gray-100 dark:bg-gray-700 gap-px">
            @foreach($days as $day)
                <div class="min-h-[100px] sm:min-h-[120px] bg-white dark:bg-gray-800 p-2 sm:p-3 relative group hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors
                    {{ !$day['isCurrentMonth'] ? 'bg-gray-50/50 dark:bg-gray-900/20 text-gray-400 dark:text-gray-600' : '' }}
                    {{ $day['isToday'] ? 'bg-blue-50/30 dark:bg-blue-900/10' : '' }}
                ">
                    <!-- Date Number -->
                    <div class="flex justify-between items-start">
                        <span class="text-sm font-semibold rounded-full w-7 h-7 flex items-center justify-center
                            {{ $day['isToday'] ? 'bg-primary-600 text-white shadow-md shadow-primary-500/30' : ($day['isCurrentMonth'] ? 'text-gray-700 dark:text-gray-200' : 'text-gray-400 dark:text-gray-600') }}
                            {{ isset($day['holiday']) ? 'text-red-500 dark:text-red-400' : '' }}
                        ">
                            {{ $day['day'] }}
                        </span>
                        
                        @if($day['isToday'])
                            <span class="text-[10px] font-bold text-primary-600 dark:text-primary-400 uppercase tracking-tight hidden sm:inline-block">{{ __('Today') }}</span>
                        @endif
                    </div>

                    <!-- Holiday / Events -->
                    <div class="mt-2 space-y-1">
                        @if($day['holiday'])
                            <div class="text-[10px] sm:text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 px-1.5 py-0.5 rounded border border-red-100 dark:border-red-900/30 truncate" title="{{ $day['holiday']->name }}">
                                {{ $day['holiday']->name }}
                            </div>
                        @elseif($day['isWeekend'])
                             <div class="hidden sm:block text-[10px] text-gray-300 dark:text-gray-600 font-medium ml-1">
                                
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
