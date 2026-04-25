<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <section aria-labelledby="my-schedule-title" class="user-page-surface">
            <x-user.page-header
                :back-href="route('home')"
                :title="__('My Schedule')"
                title-id="my-schedule-title">
                <x-slot name="icon">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-rose-50 via-white to-orange-50 text-rose-700 ring-1 ring-inset ring-rose-100 shadow-sm dark:from-rose-900/30 dark:via-gray-800 dark:to-orange-900/20 dark:text-rose-300 dark:ring-rose-800/60">
                        <x-heroicon-o-calendar-days class="h-5 w-5" />
                    </div>
                </x-slot>
                <x-slot name="actions">
                    <a href="{{ route('shift-swap-requests') }}"
                        class="wcag-touch-target inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 hover:text-primary-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white sm:w-auto">
                        <x-heroicon-o-arrows-right-left class="h-5 w-5" />
                        <span>{{ __('Shift Swap') }}</span>
                    </a>
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">
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
                                            <div class="mt-3">
                                                @if (in_array($schedule->id, $pendingSwapScheduleIds, true))
                                                    <span class="inline-flex rounded-full bg-yellow-100 px-2.5 py-1 text-xs font-semibold text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                        {{ __('Swap request pending') }}
                                                    </span>
                                                @else
                                                    <a href="{{ route('shift-swap-requests', ['schedule' => $schedule->id]) }}"
                                                        class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-600 transition hover:border-primary-200 hover:bg-primary-50 hover:text-primary-700 dark:border-gray-700 dark:text-gray-300 dark:hover:border-primary-800 dark:hover:bg-primary-900/20 dark:hover:text-primary-300">
                                                        <x-heroicon-o-arrows-right-left class="h-4 w-4" />
                                                        {{ __('Request swap') }}
                                                    </a>
                                                @endif
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
                    <div class="user-empty-state">
                        <div class="user-empty-state__icon">
                            <x-heroicon-o-calendar-days class="h-8 w-8 text-gray-400" />
                        </div>
                        <h3 class="user-empty-state__title">{{ __('No upcoming shifts') }}</h3>
                        <p class="user-empty-state__copy">{{ __('Your schedule hasn\'t been generated yet.') }}</p>
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
