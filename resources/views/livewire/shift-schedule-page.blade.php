<div class="py-6 lg:py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            
            {{-- Header --}}
            <div class="px-5 py-4 lg:px-8 lg:py-6 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-white dark:bg-gray-800 relative z-10">
                <div class="flex items-center gap-3">
                    <x-secondary-button href="{{ url()->previous() }}" class="!rounded-xl !px-3 !py-2 border-gray-200 dark:border-gray-600 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600">
                        <x-heroicon-o-arrow-left class="h-4 w-4 text-gray-500 dark:text-gray-300" />
                    </x-secondary-button>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="p-1.5 bg-primary-50 text-primary-600 dark:bg-primary-900/50 dark:text-primary-400 rounded-lg">
                            📅
                        </span>
                        {{ __('My Schedule') }}
                    </h3>
                </div>
            </div>

            <div class="p-0">
                @if($schedules->isNotEmpty())
                    <ul role="list" class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @foreach($schedules as $schedule)
                            <li class="group relative hover:bg-gray-50/80 dark:hover:bg-gray-700/30 transition-colors duration-200">
                                <div class="p-4 sm:p-5 flex items-center gap-4">
                                     {{-- Date Box --}}
                                    <div class="flex flex-col items-center justify-center w-12 h-12 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-600 shadow-sm">
                                        <span class="text-[10px] font-bold text-red-500 uppercase leading-none mb-0.5">{{ $schedule->date->format('M') }}</span>
                                        <span class="text-lg font-black text-gray-800 dark:text-white leading-none">{{ $schedule->date->format('d') }}</span>
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <div class="text-[10px] text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider mb-0.5">
                                            {{ $schedule->date->format('l') }}
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">
                                                {{ $schedule->is_off ? __('Off Day') : ($schedule->shift->name ?? '-') }}
                                            </h3>
                                            @if($schedule->date->isToday())
                                                <span class="px-2 py-0.5 bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400 rounded text-[10px] font-bold uppercase tracking-wide border border-emerald-100 dark:border-emerald-800">
                                                    {{ __('Today') }}
                                                </span>
                                            @endif
                                        </div>
                                        
                                        @if(!$schedule->is_off && $schedule->shift)
                                            <div class="flex items-center gap-2 mt-1.5 text-xs text-gray-600 dark:text-gray-300">
                                                <span class="bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400 px-1.5 py-0.5 rounded font-mono font-medium">
                                                    {{ \Carbon\Carbon::parse($schedule->shift->start_time)->format('H:i') }}
                                                </span>
                                                <span class="text-gray-300 dark:text-gray-600">➜</span>
                                                <span class="bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400 px-1.5 py-0.5 rounded font-mono font-medium">
                                                    {{ \Carbon\Carbon::parse($schedule->shift->end_time)->format('H:i') }}
                                                </span>
                                            </div>
                                        @else
                                            <div class="mt-1 text-xs text-gray-400 italic">
                                                {{ __('No shift assigned') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-center py-12 px-6">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-50 dark:bg-gray-700/50 mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('No upcoming shifts') }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Your schedule hasn\'t been generated yet.') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
